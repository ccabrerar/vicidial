<?php
### recording_lookup.php
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
### remove temp files
# 1 7 * * * /usr/bin/find /usr/local/apache2/htdocs/vicidial/temp/ -maxdepth 1 -type f -mtime +1 -print | xargs rm -f
#
# Copyright (C) 2013  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#

# CHANGES
# 71015-0845 - First build
# 71112-1347 - added GSM option
# 90508-0644 - Changed to PHP long tags
# 120223-2129 - Removed logging of good login passwords if webroot writable is enabled
# 130610-1108 - Finalized changing of all ereg instances to preg
# 130616-2228 - Added filtering of input to prevent SQL injection attacks
# 130901-0856 - Changed to mysqli PHP functions
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
if (isset($_GET["QUERY_recid"]))				{$QUERY_recid=$_GET["QUERY_recid"];}
	elseif (isset($_POST["QUERY_recid"]))		{$QUERY_recid=$_POST["QUERY_recid"];}

$QUERY_recid = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$QUERY_recid);
$PHP_AUTH_USER = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$PHP_AUTH_USER);
$PHP_AUTH_PW = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$PHP_AUTH_PW);

$web_server = '1.1.1.1';
$US='_';

if( (preg_match("/VDC/i",$PHP_AUTH_USER)) or (preg_match("/VDC/i",$PHP_AUTH_PW)) )
	{
	#	$package='';
	}
else
	{
	Header("WWW-Authenticate: Basic realm=\"VICI-VERIF\"");
	Header("HTTP/1.0 401 Unauthorized");
	echo "Invalid Username/Password: |$PHP_AUTH_USER|$PHP_AUTH_PW|\n";
	exit;
	}

#	$fp = fopen ("/usr/local/apache2/htdocs/vicidial/auth_entries.txt", "a");
#	$date = date("r");
#	$ip = getenv("REMOTE_ADDR");
#	$browser = getenv("HTTP_USER_AGENT");
#	fwrite ($fp, "AUTH|VDC   |$date|$username|$passwd|$ip|$QUERY_recid|$browser|\n");
#	fclose($fp);

require("screen_colors.php");

?>
<html>
<head>
<title>Recording ID Lookup: </title>
</head>
<body bgcolor=white>

<?php 


echo "<br><br>\n";

#echo "<center>\n";

if (strlen($QUERY_recid)<10)
	{
	echo "Please enter a recording ID(customer phone number) below:\n";
	}
else
	{
	$logs_to_print=0;
	echo "<B>searching for: $QUERY_recid</B>\n";
	echo "<PRE>\n";

	$stmt="select recording_id,lead_id,user,filename,location,start_time,length_in_sec from recording_log where filename LIKE \"%$QUERY_recid%\" order by recording_id desc LIMIT 1;";
	$rslt=mysql_to_mysqli($stmt, $linkAST);
	$logs_to_print = mysqli_num_rows($rslt);
	#echo "|$stmt|";

	$u=0;
	if ($logs_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$phone = $QUERY_recid;
		$recording_id = $row[0]; 
		$lead_id =		$row[1]; 
		$user =			$row[2];
		$filename =		$row[3];
		$location =		$row[4];
		$start_time =	$row[5];
		$length_in_sec = $row[6];
		$AUDname =	explode("/",$location);
		$AUDnamect =	(count($AUDname)) - 1;

		
		preg_replace('/10\.10\.10\.16/i', "10.10.10.16",$AUDname[$AUDnamect]);

		echo "Call Date/Time:        $start_time\n";
		echo "Recording Length:      $length_in_sec\n";
		echo "Phone Number:          $phone\n";
		echo "Recording ID:          $recording_id\n";
		echo "Agent:                 $user\n";
	#	echo "filename:              $filename\n";
	#	echo "Location:              $location\n";
	#	echo "AUDname:               $AUDname[$AUDnamect]\n";
		echo "Unique ID:             $lead_id\n";

		$fileGSM=$AUDname[$AUDnamect];
		$locationGSM=$location;
		$fileGSM = preg_replace('/\.wav/i', ".gsm",$fileGSM);
		if (!preg_match('/gsm/i',$locationGSM))
			{
			$locationGSM = preg_replace('/10\.10\.10\.16/i', "10.10.10.16/GSM",$locationGSM);
			$locationGSM = preg_replace('/\.wav/i', ".gsm",$locationGSM);
			}
		passthru("/usr/local/apache2/htdocs/vicidial/wget --output-document=/usr/local/apache2/htdocs/vicidial/temp/$AUDname[$AUDnamect] $location\n");
		passthru("/usr/local/apache2/htdocs/vicidial/wget --output-document=/usr/local/apache2/htdocs/vicidial/temp/$fileGSM $locationGSM\n");

		echo "Link Uncompressed WAV: <a href=\"./temp/$AUDname[$AUDnamect]\">$AUDname[$AUDnamect]</a>\n";
		echo "Link Compressed GSM:   <a href=\"./temp/$fileGSM\">$fileGSM</a>\n";
		}
	else
		{
		echo "ERROR:        $QUERY_recid\n";
		}

	echo "</PRE>\n";
	}

$ENDtime = date("U");

$RUNtime = ($ENDtime - $STARTtime);

echo "\n\n\n<br><br><br>\n<FORM ACTION=\"$PHP_SELF\" METHOD=GET>\n";
echo "<INPUT TYPE=text name=QUERY_recid size=12 maxlength=10>\n";
echo "<INPUT style='background-color:#$SSbutton_color' type=submit name=submit value='"._QXZ("submit")."'>\n";
echo "</FORM>\n";


echo "\n\n\n<br><br><br>\nscript runtime: $RUNtime seconds";


?>

</body>
</html>

