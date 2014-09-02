<?php namespace Tsg\Ar;

/**
 * AR file reader
 *
 * Small lib for reading GNU ar files (i.e. .deb files)
 *
 * @package    Tsg\Ar
 * @author     Nick Silvestro <nick@silvervest.net>
 * @copyright  2014 The Silvervest Group
 * @license    MIT License - http://opensource.org/licenses/MIT
 * @link       https://github.com/thesilvervestgroup/ar
 */
class Reader
{
    /* Holder for the File object */
    protected $file;

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    /**
     * Opens a file and makes sure it's a valid ar archive
     *
     * @param string $filename The full path to the file to open
     * @return \Tsg\Ar\Reader
     */
    public static function open($filename)
    {
        return new self(File::read($filename));
    }

    /**
     * List files inside the opened archive
     *
     * @return array
     */
    public function ls()
    {
        return $this->file->ls();
    }

    /**
     * Gets a file's contents from inside the opened archived
     *
     * @param string $filename The file from the archive to retrieve
     * @return string
     */
    public function get($filename)
    {
        return $this->file->get($filename);
    }
}
