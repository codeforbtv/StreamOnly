<?php
/**
 * Script that fetches the protected audio file
 * Will be invoked by the browser when it downloads the audio file
 */

/**
 * The script MUST NOT output any HTML prior to sending the audio file.
 * It operates outside of the Omeka environment, so it cannot rely on
 *   the database or codebase (e.g. error reporting).
 */

// Must match definitions found in helpers/StreamOnlyConstants.php
// Info stored in .m3u file
const M3U_FILENAME = 0;
const M3U_FILEID   = 1;
const M3U_EXPIRES  = 2;
const M3U_LICENSES = 3;

/**
 * Removes some number of directories from the end of a file path.
 * Helpful for moving up the tree structure of the file system.
 *
 * @param $path string, filepath
 * @param $count - int, # directories to remove from the end of $path
 * @return string
 */

function _remove_nodes($path, $count) {

    while ($count > 0) {
        $path = preg_replace("(/[\w]+$)", "", $path, 1);
        $count--;
    }

    return $path;
}

// error_reporting(E_ALL);

$m3uDir = _remove_nodes(dirname(__FILE__), 3)
                        . DIRECTORY_SEPARATOR ."files"
                        . DIRECTORY_SEPARATOR ."m3u";

// TODO better error handling
if (!file_exists($m3uDir)) {
    die("Can't find playlist directory: $m3uDir");
}

if (preg_match("#/(\d+).m3u/$#", $_SERVER['PATH_INFO'], $matches)) {
    $m3uID = $matches[1];
} else {
    die("Badly formed script name");
}


$m3uFile = $m3uDir . DIRECTORY_SEPARATOR . $m3uID . ".m3u";

$playlist = file($m3uFile);

// Handle unauthorized attempt to download the file.
if (!$playlist) {
    readfile($m3uDir . DIRECTORY_SEPARATOR . "custom_msg.txt");
    exit;
}

// Delete the .m3u file so audio file can't be downloaded again.
unlink($m3uFile);

if (!$playlist[M3U_FILENAME]) {
    die("Playlist corrupt; no audio file listed: $m3uFile, $playlist");
}

$mp3Filename = trim($playlist[M3U_FILENAME]);
$mp3FileID = trim($playlist[M3U_FILEID]);


if (!file_exists($mp3Filename)) {
    die("Can't find audio file: $mp3Filename");
}

// Ensure that the output buffer is empty
// TODO this may be superfluous
ob_end_clean();
ob_start();

// TODO Add option for admin to specify use of mod_xsendfile
//if ($xSendOption && extension_loaded("X-Sendfile")) {
if (false)
    header("X-Sendfile: $mp3Filename");
else {
    $handle = fopen($mp3Filename, "rb");

    // Send out the headers
    header("Content-Disposition: inline", true);
//    header("Content-Type: audio/mpeg", true);
//    header("Content-Length: " . filesize($mp3Filename), true);
    //header("Pragma: no-cache", true); // for IE
    //$timestamp = gmdate("D, d M Y H:i:s") . " GMT";
    //header("Expires: $timestamp");
    //header("Last-Modified: $timestamp");
    //header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0", false);
    //header("Cache-Control: post-check=0, pre-check=0", false);
    $temp = fpassthru($handle);
    $temp2 = fclose($handle);

}


// Flush the buffer
ob_end_flush();

// Explicitly close the persistent connection
//   as PHP does not do this when a script ends successfully
//   and Firefox keeps the connection open when preloading
//header("Connection: close");

//
exit();

//// First read everything EXCEPT the id3v1.x tag
////  at the end.
//
//$size = filesize($filename);
//$id3TagSize = 128;
//$limit = $size - $id3TagSize;
//$pos = 0;
//while ($pos < $limit) {
//    $chunk = 8192; // TODO move this outside of loop
//    if ($pos + $chunk > $limit) {
//        $chunk = $limit - $pos;
//    }
//    $data = fread($handle, $chunk);
//    print $data;
//    $pos += $chunk;
//}
//
//// Now grab the last 128 bytes, which should be the ID3 tag,
//// and rewrite the comment field before sending it to the
//// browser. If we don't see an ID3 tag signature, output
//// what we did grab, which will be the tail end of the audio,
//// and then invent our own ID3 tag.
//
//$id = fread($handle, $id3TagSize);
//
//if (substr($id, 0, 3) != "TAG") {
//    # Not really an ID3 tag, so write
//    # out the last of the audio data and
//    # invent our own ID3 tag
//    print $id;
//    # Now make an empty ID3 tag to append
//    $id = pack("a128", "TAG");
//}
//
//// Record the IP address and time of download in the actual MP3 file,
//// as a comment. When you find your files on someone else's site you
//// can then determine when they were stolen and from what IP address.
//// That information can be used to pursue legal remedies, beginning by
//// obtaining the identity of the original downloader from their ISP
//// using this information. Note that the time logged in the file is
//// always GMT.
//$comment = $_SERVER['REMOTE_ADDR'] .
//    " " . gmdate("Y-m-d h:i:sa", time());
//
//$newid = substr($id, 0, 97) . pack("a29", $comment) .
//    substr($id, 126, 2);
//print $newid;
//fclose($handle);
//exit;

?>