# AR archive file handling

Opening, reading and creating AR archives (like .deb files) in PHP

## Usage

### Reading

Create a reader object:

    $ar = \Tsg\Ar\Reader::open('/path/to/file');

Get a list of files inside archive:

    $list = $ar->ls();

This returns an array of files in format:

    $list = [ 'filename' => [ 'fpos' => position in file, 'size' => size in bytes, 'uid' => user ID, 'gid' => group ID, 'mode' => file mode ] , ... ];

Retrieve a file's contents from inside archive:

    $data = $ar->get('filename');

This returns the data read directly from the file as found in the ls() method above. You can directly write this data to disk using fwrite() or file_put_contents()

### Writing

Create a writer object:

    $ar = \Tsg\Ar\Writer::create('/path/to/file');

Add a file to it:

    $ar->add('/path/to/file');

Or add multiple files at once using a glob:

    $ar->add('/path/to/files/*');

Or chain add files!

    $ar->add('1.file')->add('2.file')->add('3.file');

Then close the file

    $ar->close();

After the file is closed, it is ready to be used as a standard ar archive. You can confirm it's been written using the `ar` tool.
