<!--http://omega.uta.edu/~svs5361/project8/album.php-->
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title>Using the Cloud</title>
    </head>
    <body>
        <div style="text-align: center;font-size: 20px">
    <div style="width: 100%; margin: 0 auto;font-size: larger"><b>Programming Assignment 8: Using the Cloud </b></div><div style="margin: 6px"><b>(Developed by: Salman V. Siddique, UTA ID: 1001115361)</b></div>
     <hr>
    </div>
        <form action="album.php" method="post" enctype="multipart/form-data">
    <div align="center"> Upload .jpg OR .jpeg OR .gif OR .png files to DropBox:
    <input type="file" name="fileToUpload" id="fileToUpload"/></div></br>
    <div align="center"><input type="submit" value="Upload Image" name="submit"/></div>
</form>
     <?php
error_reporting(E_ALL);
ini_set('display_errors','On');
set_time_limit(0);
require_once("DropboxClient.php");

$dropbox = new DropboxClient(array(
	'app_key' => "32ohriinciwhbeu",      // Put your Dropbox API key here
	'app_secret' => "u06xwgi7o8t4fkj",   // Put your Dropbox API secret here
	'app_full_access' => false,
),'en');

$access_token = load_token("access");
if(!empty($access_token)) {
	$dropbox->SetAccessToken($access_token);
}
elseif(!empty($_GET['auth_callback'])) // are we coming from dropbox's auth page?
{
	// then load our previosly created request token
	$request_token = load_token($_GET['oauth_token']);
	if(empty($request_token)) die('Request token not found!');
	// get & store access token, the request token is not needed anymore
	$access_token = $dropbox->GetAccessToken($request_token);	
	store_token($access_token, "access");
	delete_token($_GET['oauth_token']);
}

// checks if access token is required
if(!$dropbox->IsAuthorized())
{
	// redirect user to dropbox auth page
	$return_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?auth_callback=1";
	$auth_url = $dropbox->BuildAuthorizeUrl($return_url);
	$request_token = $dropbox->GetRequestToken();
	store_token($request_token, $request_token['t']);
	die("Authentication required. <a href='$auth_url'>Click here.</a>");
}

if(isset($_POST["submit"])) {    
    if(getimagesize($_FILES["fileToUpload"]["tmp_name"])) {
        $imageFileType = pathinfo(basename($_FILES["fileToUpload"]["name"]),PATHINFO_EXTENSION);
        if(strtolower($imageFileType) == "jpg" or strtolower($imageFileType) == "png" or strtolower($imageFileType) == "jpeg"
or strtolower($imageFileType) == "gif" ) {
    $target='uploads/'.basename($_FILES['fileToUpload']['name']);
    if(move_uploaded_file($_FILES['fileToUpload']['tmp_name'],$target)) {
     $dropbox->UploadFile($target);
     $files=array_merge($dropbox->Search("/", ".jpg"),$dropbox->Search("/", ".gif"),$dropbox->Search("/", ".jpeg"),$dropbox->Search("/", ".png"));
     echo "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
    }else{
        echo "Sorry, there was an error uploading your file.";
    }}else{ echo "Sorry, only JPG, JPEG, PNG and GIF files you can upload .";
    }}else{ echo "Sorry, the selected file for upload is not an image file please select only JPG, JPEG, PNG and GIF image file to upload.";}
}

$files=array_merge($dropbox->Search("/", ".jpg"),$dropbox->Search("/", ".gif"),$dropbox->Search("/", ".jpeg"),$dropbox->Search("/", ".png"));
if(isset($_GET["del"]) and trim($_GET["del"])!="")
{
  foreach ($files as $file)
{
    if($file->path==urldecode($_GET["del"]))
    {
        $dropbox->Delete($file->path);
    }
}
}

$files=array_merge($dropbox->Search("/", ".jpg"),$dropbox->Search("/", ".gif"),$dropbox->Search("/", ".jpeg"),$dropbox->Search("/", ".png"));
//echo "\r\n\r\n<b>Files:</b>\r\n";
echo "</br><table align='center'><tr><th>&nbsp;&nbsp;File Name&nbsp;&nbsp;</th><th>&nbsp;&nbsp;View & Download&nbsp;&nbsp;</th><th align='center'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Delete&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</th><th>&nbsp;&nbsp;Image Section&nbsp;&nbsp;</th></tr><tr><td>&nbsp;</td></tr>";
foreach ($files as $file)
{
    echo "<tr><td>  ".$file->path."  </td><td align='center'><a href='album.php?df=".$file->path."'>  View & Download  </a></td><td align='center'><a href='album.php?del=".$file->path."'>Delete</a></td>";
}

if(isset($_GET["df"]) and trim($_GET["df"])!="")
{
  foreach ($files as $file)
{
    if($file->path==urldecode($_GET["df"]))
    {
        //echo "File Path: ".$file->path." get df is: ".$_GET["df"]."\n";
        $test_file = "downloads/".basename($file->path);
        $dropbox->DownloadFile($file, $test_file);
	    $img_data = base64_encode($dropbox->GetThumbnail($file->path,'l'));
	    echo "<td valign='top'><img src=\"data:image/jpeg;base64,$img_data\" alt=\"Generating PDF thumbnail failed!\" style=\"border: 1px solid black;\" /></td>";
    }
}
}
echo "</tr></table>";



function store_token($token, $name)
{
	if(!file_put_contents("tokens/$name.token", serialize($token)))
		die('<br />Could not store token! <b>Make sure that the directory `tokens` exists and is writable!</b>');
}

function load_token($name)
{
	if(!file_exists("tokens/$name.token")) return null;
	return @unserialize(@file_get_contents("tokens/$name.token"));
}

function delete_token($name)
{
	@unlink("tokens/$name.token");
}

function enable_implicit_flush()
{
	@apache_setenv('no-gzip', 1);
	@ini_set('zlib.output_compression', 0);
	@ini_set('implicit_flush', 1);
	for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
	ob_implicit_flush(1);
	echo "<!-- ".str_repeat(' ', 2000)." -->";
}
?>  
    </body>
</html>
