<?php namespace Tsg\Ar;

/**
 * AR file reader
 *
 * Small lib for handling GNU ar files (i.e. .deb files)
 *
 * @package    Tsg\Ar
 * @author     Nick Silvestro <nick@silvervest.net>
 * @copyright  2014 The Silvervest Group
 * @license    MIT License - http://opensource.org/licenses/MIT
 * @link       https://github.com/thesilvervestgroup/ar
 */
class Reader
{
    /* These are taken from /usr/include/ar.h */
    const ARMAG = "!<arch>\n";                  // String that begins an archive file.
    const SARMAG = 8;                           // Size of that string.
    const ARFMAG = "`\n";                       // String in ar_fmag at end of each header.
    protected $arhdr = "%16s%12d%6d%6d%8d%10d"; // Header format, built from struct ar_hdr

    /* Internals */
    protected $filename;
    protected $fp;

    /* Cache of files in loaded archive */
    protected $fileList = array();

    /**
     * Constructor
     *
     * @param string $filename Filename of the file we've got open
     * @param string $fp       Open file pointer to the above file
     */
    public function __construct($filename, $fp)
    {
        $this->filename = $filename;
        $this->fp = $fp;
    }

    /**
     * Destructor
     *
     */
    public function __destruct()
    {
        if ($this->fp) {
            fclose($this->fp);
        }
    }

    /**
     * Opens a file and makes sure it's a valid ar archive
     *
     * @param string $filename The full path to the file to open
     * @return \Tsg\Ar\Reader
     */
    public static function open($filename)
    {
        // open with 'rb' for windows support, I guess
        $fp = fopen($filename, 'rb');
        if (!$fp) {
            throw new \Exception('Could not open file for binary read: ' . $filename);
        }

        // verify the file magic header
        $magic = fread($fp, self::SARMAG);
        if ($magic !== self::ARMAG) {
            throw new \Exception('File is not an archive: ' . $filename);
        }

        return new self($filename, $fp);
    }

    /**
     * List files inside the opened archive
     *
     * @return array
     */
    public function ls($file = null)
    {
        if (!empty($this->fileList)) {
            return $this->fileList;
        }

        $fileList = array();

        // start at the start, after the magic header
        $pos = self::SARMAG;
        fseek($this->fp, $pos);

        do {
            // read 60 bytes for the file header
            $header = fread($this->fp, 60);
            // if we didn't get 60 bytes, we're probably at the end of the file so just break
            if (strlen($header) < 60) {
                break;
            }

            // parse the header
            if (substr($header, -2) !== self::ARFMAG) {
                throw new \Exception('Invalid looking file header, possibly corrupted file');
            }
            $data = sscanf($header, $this->arhdr);

            // add this file to the array
            $fileList[$data[0]] = array('fpos' => ftell($this->fp), 'size' => $data[5], 'uid' => $data[2], 'gid' => $data[3], 'mode' => $data[4]);

            // skip ahead in front of this file
            // make sure seek ends on a 2-byte, if not increment by 1
            fseek($this->fp, $data[5] % 2 == 0 ? $data[5] : $data[5] + 1, SEEK_CUR);

            // repeat
        } while (!feof($this->fp));

        // cache for later
        $this->fileList = $fileList;

        return $fileList;
    }

    /**
     * Gets a file's contents from inside the opened archived
     *
     * @param string $filename The file from the archive to retrieve
     * @return string
     */
    public function get($filename)
    {
        if (empty($this->fileList)) {
            $this->ls();
        }

        if (!isset($this->fileList[$filename])) {
            throw new \Exception('File not found in archive: ' . $filename);            
        }

        // get the file data out
        $fileData = $this->fileList[$filename];

        // seek the file to the known pos
        fseek($this->fp, $fileData['fpos']);

        // read the data and return
        $data = fread($this->fp, $fileData['size']);

        return $data;
    }
}
