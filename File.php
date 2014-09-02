<?php namespace Tsg\Ar;

class File
{
    /* These are taken from /usr/include/ar.h */
    const ARMAG = "!<arch>\n";                  // String that begins an archive file.
    const SARMAG = 8;                           // Size of that string.
    const ARFMAG = "`\n";                       // String in ar_fmag at end of each header.
    protected $arhdr = "%16s%12d%6d%6d%8d%10d"; // Header format, built from struct ar_hdr
    protected $warhdr = "%-16s%-12d%-6d%-6d%-8d%-10d"; // Header format, as above, but with alignment for writing

    /* Internals */
    protected $filename;
    protected $fp;
    protected $mode;

    /* Cache of files in loaded archive */
    protected $fileList = array();

    /**
     * Constructor
     *
     * @param string $filename Filename of the file we've got open
     * @param string $fp       Open file pointer to the above file
     */
    public function __construct($filename, $fp, $mode = 'r')
    {
        if (!file_exists($filename)) {
            throw new \Exception('File not found:' . $filename);
        }
        $this->filename = $filename;

        if (!is_resource($fp)) {
            throw new \Exception('File pointer must be resource');
        }
        $this->fp = $fp;

        if (!in_array($mode, array('r','w'))) {
            throw new \Exception('Invalid file mode: ' . $mode);
        }
        $this->mode = $mode;
    }

    /**
     * Destructor
     *
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Open a file for read access
     *
     * @param string $filename Full path to file to open
     * @return \Tsg\Ar\File
     */
    public static function read($filename)
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
     * Open a file for write access
     *
     * @param string $filename Full path to file to open
     * @param boolean $overwrite Whether to overwrite an existing file
     * @return \Tsg\Ar\File
     */
    public static function write($filename, $overwrite = false)
    {
        // check if file already exists, and fail if we're not allowed to overwrite
        if (file_exists($filename) && $overwrite !== true) {
            throw new \Exception('File already exists and overwrite not allowed: ' . $filename);
        }

        // open with 'wb' for windows support, I guess
        $fp = fopen($filename, 'wb');
        if (!$fp) {
            throw new \Exception('Could not open file for binary write: ' . $filename);
        }

        // start by writing the file magic header
        fwrite($fp, self::ARMAG);

        return new self($filename, $fp, 'w');
    }

    /**
     * Close any open file
     *
     */
    public function close()
    {
        if (is_resource($this->fp)) {
            fclose($this->fp);
        }
    }

    /**
     * List files inside the opened archive
     *
     * @return array
     */
    public function ls()
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

    /**
     * Writes a file to the opened archive
     *
     * @param string $filename File name to write
     * @param string $data File data to write
     * @param integer $mode File mode (in octal)
     * @param integer $modified File last modified timestamp
     * @param integer $uid File UID
     * @param integer $gid File GID
     * @return \Tsg\Ar\File
     */
    public function add($filename, $data, $mode = 100644, $modified = null, $uid = 0, $gid = 0)
    {
        if (!$this->fp) {
            throw new \Exception('Cannot write file, no file open');
        }

        if ($this->mode != 'w') {
            throw new \Exception('Cannot write file, opened in read mode');
        }

        if (is_null($modified)) {
            $modified = time();
        }

        // prepare and write this file's header
        $size = strlen($data);
        $header = sprintf($this->warhdr . self::ARFMAG, $filename, $modified, $uid, $gid, $mode, $size);
        fwrite($this->fp, $header, strlen($header));

        // write the file data
        fwrite($this->fp, $data, $size);

        // make sure we're at a 2-byte offset
        if ($size % 2 != 0) {
            // if not, write an additional \n per spec
            fwrite($this->fp, "\n", 1);
        }

        return $this;
    }
}
