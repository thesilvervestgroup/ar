# AR archive file handling

Opening and reading AR archives (like .deb files) in PHP

## Usage

Create a reader object:

    $ar = \Tsg\Ar\Reader::open('/path/to/file');

Get a list of files inside archive:

    $list = $ar->ls();

This returns an array of files in format:

    $list = [ 'filename' => [ 'fpos' => position in file, 'size' => size in bytes, 'uid' => user ID, 'gid' => group ID, 'mode' => file mode ] , ... ];

Retrieve a file's contents from inside archive:

    $data = $ar->get('filename');

This returns the data read directly from the file as found in the ls() method above. You can directly write this data to disk using fwrite() or file_put_contents()
