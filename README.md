## Movie Directory Listing

A quick little PHP script I whipped up to serve a listing of all the video files in my Movies directory, along with media info, courtesy of the extraordinarily useful [MediaInfo](http://mediainfo.sourceforge.net).

### Using it yourself

1. **Plop** the files in a directory served by your preferred web server which runs PHP scripts.
2. **Download** the correct distribution of **MediaInfo** ([download page](http://mediainfo.sourceforge.net/Download)) for your platform and place the executables in the MediaInfo directory.
3. **Add** or **uncomment** `extension=php_fileinfo.ext` in your `php.ini` file &mdash; this enables the [Fileinfo](http://www.php.net/manual/en/book.fileinfo.php) PHP extension, required for [`finfo_file`](http://www.php.net/manual/en/function.finfo-file.php).
4. **Configure** the `index.php` file:
   
   ```php
   $ROOT_DIR = '<path/to/your/movies>';
   $PATH_SEPARATOR = '\\';
   ```
   
   **Note**: `$ROOT_DIR` can be a relative path, but due to the lack of good protection and security, an absolute path is recommended. *When executing MediaInfo (in the shell!) the script checks only that `$ROOT_PATH` is at the beginning of the user-supplied path, and that the path exists as a file. It's potentially possible to access unintended files, depending on your file tree.*
5. **Open in your browser**, *et voilá!*