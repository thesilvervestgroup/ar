<?php namespace Tsg\Ar;

/**
 * AR file writer
 *
 * Small lib for writing GNU ar files (i.e. .deb files)
 *
 * @package    Tsg\Ar
 * @author     Nick Silvestro <nick@silvervest.net>
 * @copyright  2014 The Silvervest Group
 * @license    MIT License - http://opensource.org/licenses/MIT
 * @link       https://github.com/thesilvervestgroup/ar
 */
class Writer
{
    /* Holder for the File object */
    protected $file;

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    /**
     * Creates a file for output, overwriting existing file if told to
     *
     * @param string $filename The full path to the file to create
     * @param boolean $overwrite Whether to overwrite an existing file or not
     * @return \Tsg\Ar\Reader
     */
    public static function create($filename, $overwrite = false)
    {
        return new self(File::write($filename, $overwrite));
    }

    /**
     * Takes a path to a local file, reads it in and adds it to the archive
     *
     * @param string $filename Full path to the local file to add
     * @return array
     */
    public function add($filename)
    {
        if (!file_exists($filename) || !is_readable($filename)) {
            throw new \Exception('Could not read file for adding: ' . $filename);
        }

        // get file info and data
        $stat = stat($filename);
        if ($stat === false || !is_array($stat)) {
            throw new \Exception('Could not get file information: ' . $filename);
        }
        $data = file_get_contents($filename);
        $name = basename($filename);

        $this->file->add($name, $data, $stat[2], $stat[9], $stat[4], $stat[5]);

        return $this;
    }

    public function close()
    {
        $this->file->close();
    }
}
