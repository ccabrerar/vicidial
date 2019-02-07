<?php
# www_phrases_recent.php
# 
# Copyright (C) 2015  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# This script is designed to pull records from the www_phrases table from a 
# specified date until the present. Here is an example URL:
#  http://server/vicidial/www_phrases_recent.php?date=2015-02-20+00:00:00&format=default
#
# OPTIONS:
# date - must be in this format: YYYY-MM-DD+HH:MM:SS
# format - default will export one phrase per line
#
# CHANGES
# 150228-1242 - First Build
#

header ("Content-type: text/html; charset=utf-8");

require("dbconnect_mysqli.php");
require("functions.php");

if (isset($_GET["date"]))				{$date=$_GET["date"];}
	elseif (isset($_POST["date"]))		{$date=$_POST["date"];}
if (isset($_GET["format"]))				{$format=$_GET["format"];}
	elseif (isset($_POST["format"]))	{$format=$_POST["format"];}
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}

###############################################################
##### START SYSTEM_SETTINGS INFO LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
$ss_conf_ct = mysqli_num_rows($rslt);
if ($ss_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$webroot_writable =				$row[1];
	}
##### END SYSTEM_SETTINGS INFO LOOKUP #####
#############################################################

if ($non_latin < 1)
	{
	$date = preg_replace("/'|\"|\\\\|;/","",$date);
	$format = preg_replace("/'|\"|\\\\|;/","",$format);
	}
else
	{
	$date = preg_replace("/'|\"|\\\\|;/","",$date);
	$format = preg_replace("/'|\"|\\\\|;/","",$format);
	}
$date=preg_replace("/\+/",' ',$date);

$record_count=0;

if (strlen($date) > 18)
	{
	$stmt = "SELECT phrase_text FROM www_phrases where insert_date >=\"$date\" order by insert_date;";
	if ($DB > 0) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$did_call_ct = mysqli_num_rows($rslt);
	$i=0;
	while ($did_call_ct > $i)
		{
		$row=mysqli_fetch_row($rslt);
		$phrase_text =	$row[0];
		if ($DB > 0) {echo "$i|";}
		echo "$phrase_text\n";
		$i++;
		if ( ($DB > 0) and ($webroot_writable > 0) )
			{
			$fp = fopen ("./www_phrases_recent_log.txt", "a");
			fwrite ($fp, "$i|$date|$phrase_text|$did_call_ct|$stmt\n");
			fclose($fp);
			}
		}
	}

if ($DB > 0) {echo "DONE: |$date|\n";}

?>
