<?php
///////////////////////////////
// User-specific configuration
///////////////////////////////
$ROOT_DIR = 'D:\Movies';
$PATH_SEPARATOR = '\\';
///////////////////////////////


/* Used first, to cut down on file processing time from finfo_file, and because
 * it ain't very good at figuring out Matroska.
 */
$VIDEO_EXTS = array(
    '3g2',
    '3gp',
    'avi',
    'flv',
    'mkv',
    'mov',
    'mp4',
    'mpg',
    'mpeg',
    'vob',
    'wmv'
);
$VIDEO_MIMES = array(
    'application/annodex',
    'application/mp4',
    'application/ogg',
    'application/vnd.rn-realmedia',
    'application/x-matroska',
    'video/3gpp',
    'video/3gpp2',
    'video/annodex',
    'video/divx',
    'video/flv',
    'video/h264',
    'video/mp4',
    'video/mp4v-es',
    'video/mpeg',
    'video/mpeg-2',
    'video/mpeg4',
    'video/ogg',
    'video/ogm',
    'video/quicktime',
    'video/ty',
    'video/vdo',
    'video/vivo',
    'video/vnd.rn-realvideo',
    'video/vnd.vivo',
    'video/webm',
    'video/x-bin',
    'video/x-cdg',
    'video/x-divx',
    'video/x-dv',
    'video/x-flv',
    'video/x-la-asf',
    'video/x-m4v',
    'video/x-matroska',
    'video/x-motion-jpeg',
    'video/x-ms-asf',
    'video/x-ms-dvr',
    'video/x-ms-wm',
    'video/x-ms-wmv',
    'video/x-msvideo',
    'video/x-sgi-movie',
    'video/x-tivo',
    'video/avi',
    'video/x-ms-asx',
    'video/x-ms-wvx',
    'video/x-ms-wmx'
);

// Courtesy of Walter Tross (http://stackoverflow.com/users/1046007/walter-tross)
// http://stackoverflow.com/questions/834303/php-startswith-and-endswith-functions#comment14073881_834355
function startswith($haystack, $needle)
{
    return !strncmp($haystack, $needle, strlen($needle));
}

// Courtesy of deceze (http://stackoverflow.com/users/476/deceze)
// http://stackoverflow.com/a/1091219/148585
function join_paths() {
    $args = func_get_args();
    $paths = array();
    foreach ($args as $arg) {
        $paths = array_merge($paths, (array)$arg);
    }

    $paths = array_map(create_function('$p', 'return trim($p, "/");'), $paths);
    $paths = array_filter($paths);
    return join('/', $paths);
}

function fix_path_separators($path)
{
    global $PATH_SEPARATOR;
    return str_replace(array('/', '\\'), array($PATH_SEPARATOR, $PATH_SEPARATOR), $path);
}

// Return media info for AJAX request
if (array_key_exists('v', $_GET) && startswith(realpath($_GET['v']), $ROOT_DIR) && file_exists(realpath($_GET['v'])))
{
    $MediaInfo = escapeshellcmd(fix_path_separators(join_paths('MediaInfo', 'MediaInfo')));
    exec($MediaInfo . ' ' . escapeshellarg(fix_path_separators($_GET['v'])), $output);
    
    // Make shit pretty
    $html = trim(implode("\n", $output));
    $html = preg_replace('/^(.*)$/m', '<span class="title">$1</span>', $html, 1);
    $html = preg_replace('/\n\n(.*)$/m', "\n\n<span class=\"title\">$1</span>", $html);
    $html = preg_replace_callback('/^(.*?)\s*:\s*(.*?)$/m', function ($match) {
        $attr = htmlspecialchars($match[1]);
        $value = htmlspecialchars($match[2]);
        return '<span class="attr">' . $attr . '</span>: <span class="value">' . $value . '</span>';
    }, $html);
    
    $html = str_replace("\n", "<br />\n", $html);
    
    die($html);
}

function read_dir_recursive($path, $tab=3)
{
    global $VIDEO_MIMES, $VIDEO_EXTS;
    if (is_dir($path) && $handle = opendir($path)) {
        ob_start();
        
        $files = 0;
        $videos = 0;
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
?>
<ul>
<?php
        while (false !== ($entry = readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                $files++;
                $entry_path = join_paths($path, $entry);
                list($sub_entries, $sub_videos, $sub_out) = read_dir_recursive($entry_path);
                
                $title = $entry;
                $is_video = false;
                if ($sub_entries > 0)
                {
                    $sub_entries_text = "$sub_entries " . ngettext('entry', 'entries', $sub_entries);
                    if ($sub_videos > 0)
                        $sub_entries_text .= ", <span class=\"num_videos\">$sub_videos " . ngettext('video', 'videos', $sub_videos) . '</span>';
                    $title .= " <span class=\"num_entries\">($sub_entries_text)</span>";
                }
                elseif (is_file($entry_path))
                {
                    $mime = finfo_file($finfo, $entry_path);
                    $ext = pathinfo($entry_path, PATHINFO_EXTENSION);
                    if (in_array($ext, $VIDEO_EXTS) || in_array($mime, $VIDEO_MIMES, true))
                    {
                        $is_video = true;
                        $videos++;
                    }
                    
                    $title .= ' <span class="mimetype">(' . $mime . ')</span>';
                }
?>
  <li>
    <span title="<?= $entry_path ?>" class="entry<?= $is_video ? ' is_video ' . str_replace('/', '__', $mime) : ''; ?>"><?= $title ?></span>
<?= $sub_out ?>
  </li>
<?php
            }
        }
?>
</ul>
<?php
        
        closedir($handle);
        
        $out = ob_get_contents();
        ob_end_clean();
        
        if ($files > 0)
        {
            $out = preg_replace('/^/m', str_repeat('  ', $tab), $out);
            return array($files, $videos, $out);
        }
        
        finfo_close($finfo);
    }
    
    return array(0, 0, '');
}
?>
<html>
  <head>
    <title>Listing of videos in /d/Movies</title>
    <script type="text/javascript" src="js/jquery-1.7.2.min.js"></script>
    <script type="text/javascript" src="js/spin.min.js"></script>
    <script type="text/javascript">
      $(function() {
        $('ul > li:has(> ul) > .entry')
          .click(function() {
            $(this).next().toggle();
            return false;
          })
          .addClass('has_entries')
          .next().hide();
        
        $('.entry.is_video')
          .click(function() {
            var info = $(this).next();
            if (!info.exists())
            {
              info = $('<div class="media_info">');
              
              // Add spinner to show we're loading...
              var spin = new Spinner({
                lines: 8,
                length: 4,
                width: 3,
                radius: 5,
                top: 5,
                left: 5
              });
              spin.spin(info[0]);
              info.append($('<span class="loading">Loading media info...</span>'));
              
              info.appendTo($(this).parent());
              info.load('?v=' + encodeURI($(this).attr('title')));
            }
            else
              info.toggle();
          });
      });
    </script>
    <style type="text/css">
      body { font-family: monospace; }
      ul {
        list-style-type: square;
        margin-bottom: 5px;
      }
      ul > li:nth-child(even) > .entry { background-color: #ddd; }
      ul > li:nth-child(odd) > ul > li:nth-child(odd) > .entry { background-color: #ddd; }
      ul > li:nth-child(odd) > ul > li:nth-child(even) > .entry { background-color: white; }
      .entry {
        border-radius: 3px;
        display: block;
        padding: 2px;
        width: 100%;
      }
      .entry:hover { background-color: turquoise !important; }
      .entry.has_entries {
        color: blue;
        cursor: pointer;
      }
      .entry.has_entries > .num_entries { color: black; }
      .entry.is_video, .num_videos { color: green; }
      .entry.is_video { cursor: pointer; }
      .mimetype { color: purple; }
      
      .media_info {
        border: 1px solid black;
        border-radius: 0 0 15px 15px;
        border-top: 0 none;
        margin-bottom: 5px;
        margin-left: 10px;
        min-height: 30px;
        padding-bottom: 10px;
        padding-left: 10px;
      }
      .media_info > .title {
        border-bottom: 1px solid;
        display: block;
        font-size: 1.2em;
        font-weight: bold;
        margin: 4px 0;
        width: 100%;
      }
      .media_info > .title:first-child { margin-top: 0; }
      .media_info > .title + br { display: none; }
      .media_info > .attr {
        font-weight: bold;
        margin-left: 10px;
      }
      .media_info > .value { font-style: italic; }
      .media_info > .loading {
        display: block;
        font-style: italic;
        font-weight: bold;
        padding: 10px 0 0 40px;
      }
    </style>
  </head>
  <body>
    <h1>Listing all files and directories under "<?= $ROOT_DIR ?>"</h1>
    <p>
      <span style="color: green;">green</span> means video file<br />
      <span style="color: blue;">blue</span> means directory<br />
      <span style="color: purple;">purple</span> means <a href="http://en.wikipedia.org/wiki/Internet_media_type">mimetype</a><br />
    </p>
<?php list($sub_entries, $sub_videos, $sub_out) = read_dir_recursive($ROOT_DIR); echo $sub_out; ?>
  </body>
</html>