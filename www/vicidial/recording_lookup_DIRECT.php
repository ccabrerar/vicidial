<?php
### recording_lookup_DIRECT.php
#
#	REQUIRED! - check all paths and directory names, need to create a temp directory
#   CUSTOMIZATION OF THIS SCRIPT IS REQUIRED FOR IT TO WORK!!!
#
#	On the normal audio recording interface you now have the option of
#	downloading the WAV or GSM file:
#	http://1.1.1.1/vicidial/recording_lookup.php
#	 - user/pass: VDC
#
#	I have also added a new download page that works by query string(URL)
#	only. Simply goto and address like this one and the audio will
#	download immediately:
#	http://1.1.1.1/vicidial/recording_lookup_DIRECT.php?phone=7275551212&format=GSM&auth=VDC1234593JH654398722
#
#	The variables are "phone", "format" and "auth".
#	 - phone: 10 digit phone number of the customer
#	 - format: either GSM or WAV
#	 - auth: should always be VDC1234593JH654398722
#
#	This should work well for a direct link from your CRM system, or
#	possibly through a bulk downloading script on your side.
#
#
### remove temp files
# 1 7 * * * /usr/bin/find /usr/local/apache2/htdocs/vicidial/temp/ -maxdepth 1 -type f -mtime +1 -print | xargs rm -f
#
# Copyright (C) 2013  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#

# CHANGES
# 71112-1409 - First Build
# 90508-0644 - Changed to PHP long tags
# 130610-1132 - Finalized changing of all ereg instances to preg
# 130616-2225 - Added filtering of input to prevent SQL injection attacks
# 130901-0855 - Changed to mysqli PHP functions
#

$STARTtime = date("U");
$TODAYstart = date("H/i/s 00:00:00");

#$linkAST=mysql_connect("1.1.1.1", "cron", "1234");
#mysql_select_db("asterisk");
$linkAST=mysqli_connect("1.1.1.1", "cron", "1234", "asterisk");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["phone"]))				{$phone=$_GET["phone"];}
	elseif (isset($_POST["phone"]))		{$phone=$_POST["phone"];}
if (isset($_GET["format"]))				{$format=$_GET["format"];}
	elseif (isset($_POST["format"]))	{$format=$_POST["format"];}
if (isset($_GET["auth"]))				{$auth=$_GET["auth"];}
	elseif (isset($_POST["auth"]))		{$auth=$_POST["auth"];}

$phone = preg_replace("/'|\"|\\\\|;/","",$phone);
$format = preg_replace("/'|\"|\\\\|;/","",$format);
$auth = preg_replace("/'|\"|\\\\|;/","",$auth);

$US='_';

if(preg_match("/VDC1234593JH654398722/i",$auth))
	{$nothing=1;}
else
	{
	echo "auth code: |$auth|\n";
	exit;
	}

$fp = fopen ("/usr/local/apache2/htdocs/vicidial/auth_entries.txt", "w");
$date = date("r");
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");
fwrite ($fp, "AUTH|VDC   |$date|\n");
fclose($fp);

if (strlen($format)<3) {$format='WAV';}
if ( (strlen($phone)<10) or (strlen($phone)>10) ) 
	{
	echo "<html>\n";
	echo "<head>\n";
	echo "<title>Recording ID Lookup: </title>\n";
	echo "</head>\n";
	echo "<body bgcolor=white>\n";
	echo "<br><br>\n";
	echo "You need to use only a 10-digit phone number<BR>\n";
	echo "recording_lookup_DIRECT.php?format=WAV&phone=7275551212&auth=VDC1234593JH654398722\n<BR>";
	exit;
	}
else
	{
	$stmt="select recording_id,filename,location,start_time from recording_log where filename LIKE \"%$phone%\" order by recording_id desc LIMIT 1;";
	$rslt=mysql_to_mysqli($stmt, $linkAST);
	$logs_to_print = mysqli_num_rows($rslt);

	$u=0;
	if ($logs_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$recording_id = $row[0]; 
		$filename =		$row[1];
		$location =		$row[2];
		$start_time =	$row[3];
		$AUDname =	explode("/",$location);
		$AUDnamect =	(count($AUDname)) - 1;
		
		preg_replace('/10\.10\.10\.16/i', "10.10.10.16",$AUDname[$AUDnamect]);

		$fileGSM=$AUDname[$AUDnamect];
		$locationGSM=$location;
		$fileGSM = preg_replace('/\.wav/i', ".gsm",$fileGSM);
		if (!preg_match('/gsm/i',$locationGSM))
			{
			$locationGSM = preg_replace('/10\.10\.10\.16/i', "10.10.10.16/GSM",$locationGSM);
			$locationGSM = preg_replace('/\.wav/i', ".gsm",$locationGSM);
			}
		if ($format == 'WAV')
			{
			exec("/usr/local/apache2/htdocs/vicidial/wget --output-document=/usr/local/apache2/htdocs/vicidial/temp/$AUDname[$AUDnamect] $location\n");

			$AUDIOfile = "/usr/local/apache2/htdocs/vicidial/temp/$AUDname[$AUDnamect]";
			$AUDIOfilename = "$AUDname[$AUDnamect]";
			// We'll be outputting a PDF
			header('Content-type: audio/wav');
			// It will be named properly
			header("Content-Disposition: attachment; filename=\"$AUDIOfilename\"");
			// The PDF source is in original.pdf
			readfile($AUDIOfile);
			}

		if ($format == 'GSM')
			{
			passthru("/usr/local/apache2/htdocs/vicidial/wget --output-document=/usr/local/apache2/htdocs/vicidial/temp/$fileGSM $locationGSM\n");

			$AUDIOfile = "/usr/local/apache2/htdocs/vicidial/temp/$fileGSM";
			$AUDIOfilename = "$fileGSM";
			// We'll be outputting a PDF
			header('Content-type: audio/gsm');
			// It will be named properly
			header("Content-Disposition: attachment; filename=\"$AUDIOfilename\"");
			// The PDF source is in original.pdf
			readfile($AUDIOfile);
			}
		}
	else
		{
		echo "ERROR:        $phone|$format\n";
		}
	}
?>
