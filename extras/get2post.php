<?php
# get2post.php
# 
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed to take a url as part of the query string and convert 
# it to a POST HTTP request and log to the url log table. uniqueid is required!
#
# -headers- option allows you to define HTTP headers in the POST, each header separated by 5 dashes, with their values separated by 3 dashes: 
#   "headers=HEADERXYZ---XYZvalue-----HEADERABC---ABCvalue"
#
# Example Dispo Call URL input:
# VARhttp://127.0.0.1/agc/get2post.php?uniqueid=--A--uniqueid--B--&type=dispo&HTTPURLTOPOST=127.0.0.1/agc/vdc_call_url_test.php?lead_id=--A--lead_id--B--
# VARhttp://127.0.0.1/agc/get2post.php?uniqueid=--A--uniqueid--B--&type=start&HTTPSURLTOPOST=127.0.0.1/agc/vdc_call_url_test.php?lead_id=--A--lead_id--B--
# VARhttp://127.0.0.1/agc/get2post.php?uniqueid=--A--uniqueid--B--&type=start&headers=HEADERXYZ---XYZvalue-----HEADERABC---ABCvalue&HTTPURLTOPOST=127.0.0.1/agc/vdc_call_url_test.php?lead_id=--A--lead_id--B--
#
# Example Agent Events Push URL:
# get2post.php?uniqueid=--A--epoch--B--.--A--agent_log_id--B--&type=event&HTTPURLTOPOST=192.168.1.3/agc/vdc_call_url_test.php?user=--A--user--B--&lead_id=--A--lead_id--B--&event=--A--event--B--&message=--A--message--B--
#
# CHANGELOG:
# 160302-1159 - First build of script
# 170324-1218 - Added headers options
# 170418-1257 - Added ability to use some special characters in headers
# 170531-0925 - Added ability to accept POST query string
#

$version = '2.14-4';
$build = '170531-0925';

require("dbconnect_mysqli.php");
require("functions.php");

$query_string = getenv("QUERY_STRING");
$request_uri = getenv("REQUEST_URI");
$POST_URI = '';
foreach($_POST as $key=>$value)
	{$POST_URI .= '&'.$key.'='.$value;}
if (strlen($POST_URI)>1)
	{$POST_URI = preg_replace("/^&/",'',$POST_URI);}
$POST_URI = preg_replace("/'|\"|\\\\/","",$POST_URI);
if ( (strlen($query_string) < 3) and (strlen($POST_URI) > 2) )
	{$query_string = $POST_URI;}

if (isset($_GET["uniqueid"]))			{$uniqueid=$_GET["uniqueid"];}
	elseif (isset($_POST["uniqueid"]))	{$uniqueid=$_POST["uniqueid"];}
if (isset($_GET["type"]))				{$type=$_GET["type"];}
	elseif (isset($_POST["type"]))		{$type=$_POST["type"];}
if (isset($_GET["headers"]))			{$headers=$_GET["headers"];}
	elseif (isset($_POST["headers"]))	{$headers=$_POST["headers"];}
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}

header ("Content-type: text/html; charset=utf-8");
header ("Cache-Control: no-cache, must-revalidate");  // HTTP/1.1
header ("Pragma: no-cache");                          // HTTP/1.0

$txt = '.txt';
$StarTtime = date("U");
$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$CIDdate = date("mdHis");
$ENTRYdate = date("YmdHis");
$MT[0]='';


#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =				$row[0];
	$webroot_writable =			$row[1];
	}
##### END SETTINGS LOOKUP #####
###########################################

$headers=preg_replace('/[^- !\=\$\.\_0-9a-zA-Z]/',"",$headers);
$uniqueid=preg_replace('/[^-\.\_0-9a-zA-Z]/',"",$uniqueid);
$type=preg_replace('/[^-\_0-9a-zA-Z]/',"",$type);
$DB=preg_replace('/[^0-9]/',"",$DB);

# default optional vars if not set
if (!isset($type))   {$type="get2post";}

if (strlen($uniqueid) < 10)
	{print "ERROR: uniqueid is not valid";   exit;}

$HTTPheader = array();
if (strlen($headers) > 1)
	{
	# &headers=HEADERXYZ---XYZvalue-----HEADERABC---ABCvalue
	$headersARY = explode('-----',$headers);
	$headersARYct = count($headersARY);
	$h=0;
	while ($h < $headersARYct)
		{
		$headerXary = explode('---',$headersARY[$h]);
		$HTTPheader[] = $headerXary[0] . ": " . $headerXary[1];
		if ($DB) {echo "HTTP Header SET: |" . $headerXary[0] . ": " . $headerXary[1] ."|\n";}
		$h++;
		}
	}

if (preg_match("/HTTPURLTOPOST=|HTTPSURLTOPOST=/",$query_string))
	{
	$post_url='';
	$curl_ready=0;
	if (preg_match("/HTTPURLTOPOST=/",$query_string))
		{
		$post_url_prep = explode('HTTPURLTOPOST=',$query_string);
		$post_url = "http://" . $post_url_prep[1];
		$post_page = $post_url;
		$post_vars='';
		if (preg_match("/\?/",$post_url))
			{
			$post_var_prep = explode('?',$post_url);
			$post_page = $post_var_prep[0];
			$post_vars = $post_var_prep[1];
			}
		$curl_ready++;
		}
	if (preg_match("/HTTPSURLTOPOST/",$query_string))
		{
		$post_url_prep = explode('HTTPSURLTOPOST=',$query_string);
		$post_url = "https://" . $post_url_prep[1];
		$post_page = $post_url;
		$post_vars='';
		if (preg_match("/\?/",$post_url))
			{
			$post_var_prep = explode('?',$post_url);
			$post_page = $post_var_prep[0];
			$post_vars = $post_var_prep[1];
			}
		$curl_ready++;
		}
	if ( ($curl_ready > 0) and (strlen($post_page) > 8) )
		{
		### insert a new url log entry
		$SQL_log = "$post_url";
		$SQL_log = preg_replace('/;/','',$SQL_log);
		$SQL_log = addslashes($SQL_log);
		$stmt = "INSERT INTO vicidial_url_log SET uniqueid='$uniqueid',url_date='$NOW_TIME',url_type='$type',url='$SQL_log',url_response='';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$affected_rows = mysqli_affected_rows($link);
		$url_id = mysqli_insert_id($link);

		$URLstart_sec = date("U");

		# use cURL to call the copy custom fields code
		$curl = curl_init();

		# Set some options - we are passing in a useragent too here
		curl_setopt_array($curl, array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_POST => 1,
			CURLOPT_URL => $post_page,
			CURLOPT_POSTFIELDS => $post_vars,
			CURLOPT_HTTPHEADER => $HTTPheader,
			CURLOPT_USERAGENT => 'VICIdial get2post'
		));

		# Send the request & save response to $resp
		$resp = curl_exec($curl);

		# Close request to clear up some resources
		curl_close($curl);
		
		if ($DB) 
			{
			echo "|$uniqueid|$type|<br>\n";
			echo "|$query_string|<br>\n";
			echo "|$post_url|<br>\n";
			echo "|$post_page|<br>\n";
			echo "|$post_vars|<br>\n";
			echo "|$resp|<br>\n";
			}


		### update url log entry
		$URLend_sec = date("U");
		$URLdiff_sec = ($URLend_sec - $URLstart_sec);

		$stmt = "UPDATE vicidial_url_log SET response_sec='$URLdiff_sec',url_response='$resp' where url_log_id='$url_id';";
		if ($DB) {echo "$stmt\n";}
		$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) {mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'00422',$user,$server_ip,$session_name,$one_mysql_log);}
		$affected_rows = mysqli_affected_rows($link);

		}
	else
		{print "ERROR: post url is invalid";   exit;}
	}
else
	{print "ERROR: post url is not populated - " . strlen($query_string);   exit;}


#$output = '';
#$output .= "$uniqueid|$type|$DB|";

if (strlen($resp) > 0)
	{echo "$resp";}

if ($webroot_writable > 0)
	{
	$fp = fopen ("./get2post.txt", "a");
	fwrite ($fp, "$output|$query_string\n");
	fclose($fp);
	}

exit;

?>
