<?php
# web_form_forward.php - custom script forward agent to web page and alter vars
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# You will need to customize this to your needs
#
# CHANGELOG:
# 80626-1121 - First Build
# 90508-0644 - Changed to PHP long tags
# 130902-0757 - Changed to mysqli PHP functions
# 170527-0003 - Added variable filtering
# 220228-1107 - Added more variable filtering
#

if (isset($_GET["phone_number"]))	{$phone_number=$_GET["phone_number"];}
if (isset($_GET["source_id"]))		{$source_id=$_GET["source_id"];}
if (isset($_GET["user"]))			{$user=$_GET["user"];}

$user = preg_replace("/\<|\>|\'|\"|\\\\|;| /", '', $user);
$source_id = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $source_id);
$phone_number = preg_replace("/\<|\>|\'|\"|\\\\|;/", '', $phone_number);

require("dbconnect_mysqli.php");
require("functions.php");

$stmt="SELECT full_name from vicidial_users where user='$user';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$fullname=$row[0];

$URL = "http://astguiclient.sf.net/test.php?userid=$user&phone=$phone_number&Rep=$fullname&source_id=$source_id";

header("Location: $URL");
#exit;
echo"<HTML><HEAD>\n";
echo"<TITLE>Group1</TITLE>\n";
echo"<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=iso-8859-1\">\n";
echo"<META HTTP-EQUIV=Refresh CONTENT=\"0; URL=$URL\">\n";
echo"</HEAD>\n";
echo"<BODY BGCOLOR=#FFFFFF marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
echo"<a href=\"$URL\">click here to continue. . .</a>\n";
echo"</BODY></HTML>\n";

?>
