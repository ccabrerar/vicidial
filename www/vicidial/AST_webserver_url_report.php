<?php 
# AST_webserver_url_report.php
# 
# Copyright (C) 2019  Joe Johnson, Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 140225-0229 - First build
# 141114-0057 - Finalized adding QXZ translation to all admin files
# 141230-1352 - Added code for on-the-fly language translations display
# 170409-1534 - Added IP List validation code
# 170818-2130 - Added HTML formatting
# 170829-0040 - Added screen color settings
# 191013-0907 - Fixes for PHP7
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$report_name='Webserver-URL Report';

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["query_date_D"]))			{$query_date_D=$_GET["query_date_D"];}
	elseif (isset($_POST["query_date_D"]))	{$query_date_D=$_POST["query_date_D"];}
if (isset($_GET["query_date_T"]))			{$query_date_T=$_GET["query_date_T"];}
	elseif (isset($_POST["query_date_T"]))	{$query_date_T=$_POST["query_date_T"];}
if (isset($_GET["end_date"]))				{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))	{$end_date=$_POST["end_date"];}
if (isset($_GET["end_date_D"]))			{$end_date_D=$_GET["end_date_D"];}
	elseif (isset($_POST["end_date_D"]))	{$end_date_D=$_POST["end_date_D"];}
if (isset($_GET["end_date_T"]))			{$end_date_T=$_GET["end_date_T"];}
	elseif (isset($_POST["end_date_T"]))	{$end_date_T=$_POST["end_date_T"];}
if (isset($_GET["webserver"]))				{$webserver=$_GET["webserver"];}
	elseif (isset($_POST["webserver"]))		{$webserver=$_POST["webserver"];}
if (isset($_GET["url"]))					{$url=$_GET["url"];}
	elseif (isset($_POST["url"]))			{$url=$_POST["url"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}

$NOW_DATE = date("Y-m-d");

if (strlen($query_date_D) < 6) {$query_date_D = "00:00:00";}
if (strlen($query_date_T) < 6) {$query_date_T = "23:59:59";}
if (!isset($webserver)) {$webserver = array();}
if (!isset($url)) {$url = array();}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($end_date)) {$end_date = $NOW_DATE;}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {$MAIN.="$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$outbound_autodial_active =		$row[1];
	$slave_db_server =				$row[2];
	$reports_use_slave_db =			$row[3];
	$SSenable_languages =			$row[4];
	$SSlanguage_method =			$row[5];
	}
##### END SETTINGS LOOKUP #####
###########################################

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	}
else
	{
	$PHP_AUTH_PW = preg_replace("/'|\"|\\\\|;/","",$PHP_AUTH_PW);
	$PHP_AUTH_USER = preg_replace("/'|\"|\\\\|;/","",$PHP_AUTH_USER);
	}

$NOW_DATE = date("Y-m-d");

$stmt="SELECT selected_language from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	}

$auth=0;
$reports_auth=0;
$admin_auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'REPORTS',1,0);
if ($auth_message == 'GOOD')
	{$auth=1;}

if ($auth > 0)
	{
	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 7;";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$admin_auth=$row[0];

	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 6 and view_reports='1';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$reports_auth=$row[0];

	if ($reports_auth < 1)
		{
		$VDdisplayMESSAGE = _QXZ("You are not allowed to view reports");
		Header ("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
		}
	if ( ($reports_auth > 0) and ($admin_auth < 1) )
		{
		$ADD=999999;
		$reports_only_user=1;
		}
	}
else
	{
	$VDdisplayMESSAGE = _QXZ("Login incorrect, please try again");
	if ($auth_message == 'LOCK')
		{
		$VDdisplayMESSAGE = _QXZ("Too many login attempts, try again in 15 minutes");
		Header ("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
		}
	if ($auth_message == 'IPBLOCK')
		{
		$VDdisplayMESSAGE = _QXZ("Your IP Address is not allowed") . ": $ip";
		Header ("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
		}
	Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
	Header("HTTP/1.0 401 Unauthorized");
	echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$PHP_AUTH_PW|$auth_message|\n";
	exit;
	}


##### BEGIN log visit to the vicidial_report_log table #####
$LOGip = getenv("REMOTE_ADDR");
$LOGbrowser = getenv("HTTP_USER_AGENT");
$LOGscript_name = getenv("SCRIPT_NAME");
$LOGserver_name = getenv("SERVER_NAME");
$LOGserver_port = getenv("SERVER_PORT");
$LOGrequest_uri = getenv("REQUEST_URI");
$LOGhttp_referer = getenv("HTTP_REFERER");
$LOGbrowser=preg_replace("/\'|\"|\\\\/","",$LOGbrowser);
$LOGrequest_uri=preg_replace("/\'|\"|\\\\/","",$LOGrequest_uri);
$LOGhttp_referer=preg_replace("/\'|\"|\\\\/","",$LOGhttp_referer);
if (preg_match("/443/i",$LOGserver_port)) {$HTTPprotocol = 'https://';}
  else {$HTTPprotocol = 'http://';}
if (($LOGserver_port == '80') or ($LOGserver_port == '443') ) {$LOGserver_port='';}
else {$LOGserver_port = ":$LOGserver_port";}
$LOGfull_url = "$HTTPprotocol$LOGserver_name$LOGserver_port$LOGrequest_uri";

$LOGhostname = php_uname('n');
if (strlen($LOGhostname)<1) {$LOGhostname='X';}
if (strlen($LOGserver_name)<1) {$LOGserver_name='X';}

$stmt="SELECT webserver_id FROM vicidial_webservers where webserver='$LOGserver_name' and hostname='$LOGhostname' LIMIT 1;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$webserver_id_ct = mysqli_num_rows($rslt);
if ($webserver_id_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$webserver_id = $row[0];
	}
else
	{
	##### insert webserver entry
	$stmt="INSERT INTO vicidial_webservers (webserver,hostname) values('$LOGserver_name','$LOGhostname');";
	if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$affected_rows = mysqli_affected_rows($link);
	$webserver_id = mysqli_insert_id($link);
	}

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$query_date, $end_date, $lower_limit, $upper_limit, $file_download, $report_display_type|', url='$LOGfull_url', webserver='$webserver_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$report_log_id = mysqli_insert_id($link);
##### END log visit to the vicidial_report_log table #####


if ( (strlen($slave_db_server)>5) and (preg_match("/$report_name/",$reports_use_slave_db)) )
	{
	mysqli_close($link);
	$use_slave_server=1;
	$db_source = 'S';
	require("dbconnect_mysqli.php");
	$MAIN.="<!-- Using slave server $slave_db_server $db_source -->\n";
	}

$webserver_string='|';
$webserver_ct = count($webserver);
$i=0;
while($i < $webserver_ct)
	{
	$webserver_string .= "$webserver[$i]|";
	$i++;
	}

$webserver_array=array();
$webserver_stmt="select webserver_id,webserver,hostname from vicidial_webservers order by webserver asc;";
$webserver_rslt=mysql_to_mysqli($webserver_stmt, $link);
$webservers_to_print=mysqli_num_rows($webserver_rslt);
$i=0;
$LISTwebserver_ids=array();
$LISTwebservers=array();
$LISThostnames=array();
while ($i < $webservers_to_print)
	{
	$row=mysqli_fetch_row($webserver_rslt);
	$LISTwebserver_ids[$i] =		$row[0];
	$LISTwebservers[$i] =		$row[1];
	$LISThostnames[$i] =	$row[2];
	$webserver_array[$row[0]]=$row[1];
	if (preg_match('/\-ALL/',$webserver_ip_string) )
		{
		$webserver_ids[$i] = $LISTwebserver_ids[$i];
		}
	$i++;
	}

$i=0;
$webservers_string='|';
$webserver_ct = count($webserver);
while($i < $webserver_ct)
	{
	if ( (strlen($webserver[$i]) > 0) and (preg_match("/\|$webserver[$i]\|/",$webserver_string)) )
		{
		$webservers_string .= "$webserver[$i]|";
		$webserver_SQL .= "'$webserver[$i]',";
		$webserverQS .= "&webserver[]=$webserver[$i]";
		}
	$i++;
	}

if ( (preg_match('/\-\-ALL\-\-/',$webserver_string) ) or ($webserver_ct < 1) )
	{
	$webserver_SQL = "";
	$webserver_rpt_string="- "._QXZ("ALL servers")." ";
	if (preg_match('/\-\-ALL\-\-/',$webserver_string)) {$webserverQS="&webserver[]=--ALL--";}
	}
else
	{
	$webserver_SQL = preg_replace('/,$/i', '',$webserver_SQL);
	$webserver_SQL = "and webserver IN($webserver_SQL)";
	$webserver_rpt_string="- "._QXZ("webserver ID(s)")." ".preg_replace('/\|/', ", ", substr($webserver_string, 1, -1));
	}
if (strlen($webserver_SQL)<3) {$webserver_SQL="";}

$url_string='|';
$url_ct = count($url);
$i=0;
while($i < $url_ct)
	{
	$url_string .= "$url[$i]|";
	$i++;
	}

$url_array=array();
$url_stmt="select url_id,url from vicidial_urls order by url_id asc;";
$url_rslt=mysql_to_mysqli($url_stmt, $link);
$urls_to_print=mysqli_num_rows($url_rslt);
$i=0;
while ($i < $urls_to_print)
	{
	$row=mysqli_fetch_row($url_rslt);
	$LISTurl_ids[$i] =		$row[0];
	$LISTurls[$i] =		$row[1];
	$url_array[$row[0]]=$row[1];
	if (preg_match('/\-ALL/',$url_string) )
		{
		$url_ids[$i] = $LISTurl_ids[$i];
		}
	$i++;
	}

$urls_string='|';
$i=0; 
$url_SQL="";
while($i < $url_ct)
	{
	if ( (strlen($url[$i]) > 0) and (preg_match("/\|$url[$i]\|/",$url_string)) ) 
		{
		$urls_string .= "$url[$i]|";
		$urlQS .= "&url[]=$url[$i]";
		$url_SQL.="$url[$i],";
		}
	$i++;
	}

if ( (preg_match('/\-\-ALL\-\-/',$url_string) ) or ($url_ct < 1) )
	{
	$URL_rpt_string="- "._QXZ("ALL URLS");
	if (preg_match('/\-\-ALL\-\-/',$url_string)) 
		{
		$urlQS="&url[]=--ALL--";
		$url_SQL="";
		}
	}
else
	{
	$urls_string=preg_replace('/\!/', "-", $urls_string);
	$URL_rpt_string="AND URL ID(S) ".preg_replace('/\|/', ", ", substr($urls_string, 1, -1));
	}
$url_SQL = preg_replace('/,$/i', '',$url_SQL);
if (strlen($url_SQL)>0) {
	$api_url_SQL="and api_url in ($url_SQL)";
	$login_url_SQL="and login_url in ($url_SQL)";
}

require("screen_colors.php");

$HEADER.="<HTML>\n";
$HEADER.="<HEAD>\n";
$HEADER.="<STYLE type=\"text/css\">\n";
$HEADER.="<!--\n";
$HEADER.="   .green {color: white; background-color: green}\n";
$HEADER.="   .red {color: white; background-color: red}\n";
$HEADER.="   .blue {color: white; background-color: blue}\n";
$HEADER.="   .purple {color: white; background-color: purple}\n";
$HEADER.="-->\n";
$HEADER.=" </STYLE>\n";
$HEADER.="<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
$HEADER.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HEADER.="<link rel=\"stylesheet\" href=\"horizontalbargraph.css\">\n";
$HEADER.="<link rel=\"stylesheet\" href=\"verticalbargraph.css\">\n";
$HEADER.="<script language=\"JavaScript\" src=\"wz_jsgraphics.js\"></script>\n";
$HEADER.="<script language=\"JavaScript\" src=\"line.js\"></script>\n";
$HEADER.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HEADER.="<TITLE>"._QXZ("$report_name")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

$short_header=1;

$MAIN.="<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>";
$MAIN.="<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
$MAIN.="<TABLE BORDER=0 cellspacing=5 cellpadding=5><TR><TD VALIGN=TOP align=center>\n";
$MAIN.="<INPUT TYPE=HIDDEN NAME=DB VALUE=\"$DB\">\n";
$MAIN.=_QXZ("Date").":\n<BR>";
$MAIN.="<INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\">";
$MAIN.="<script language=\"JavaScript\">\n";
$MAIN.="var o_cal = new tcal ({\n";
$MAIN.="	// form name\n";
$MAIN.="	'formname': 'vicidial_report',\n";
$MAIN.="	// input name\n";
$MAIN.="	'controlname': 'query_date'\n";
$MAIN.="});\n";
$MAIN.="o_cal.a_tpl.yearscroll = false;\n";
$MAIN.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$MAIN.="</script>\n";

#$MAIN.="<BR><BR><INPUT TYPE=TEXT NAME=query_date_D SIZE=9 MAXLENGTH=8 VALUE=\"$query_date_D\">";

#$MAIN.="<BR> to <BR><INPUT TYPE=TEXT NAME=query_date_T SIZE=9 MAXLENGTH=8 VALUE=\"$query_date_T\">";

$MAIN.="</TD>";

$MAIN.="<TD VALIGN=TOP align=center><BR>\n "._QXZ("to").":  <INPUT TYPE=TEXT NAME=end_date SIZE=10 MAXLENGTH=10 VALUE=\"$end_date\">";
$MAIN.="<script language=\"JavaScript\">\n";
$MAIN.="var o_cal = new tcal ({\n";
$MAIN.="	// form name\n";
$MAIN.="	'formname': 'vicidial_report',\n";
$MAIN.="	// input name\n";
$MAIN.="	'controlname': 'end_date'\n";
$MAIN.="});\n";
$MAIN.="o_cal.a_tpl.yearscroll = false;\n";
$MAIN.="// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
$MAIN.="</script>\n";
#$MAIN.="<BR><BR><INPUT TYPE=TEXT NAME=end_date_D SIZE=9 MAXLENGTH=8 VALUE=\"$end_date_D\">";
#$MAIN.="<BR> to <BR><INPUT TYPE=TEXT NAME=end_date_T SIZE=9 MAXLENGTH=8 VALUE=\"$end_date_T\">";
$MAIN.="</TD>";

$MAIN.="<TD ROWSPAN=2 VALIGN=TOP>"._QXZ("Webservers").":<BR/>\n";
$MAIN.="<SELECT SIZE=5 NAME=webserver[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$webserver_string))
	{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL WEBSERVERS")." --</option>\n";}
else
	{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL WEBSERVERS")." --</option>\n";}
$o=0;

while ($webservers_to_print > $o)
	{
	if (preg_match("/\|$LISTwebserver_ids[$o]\|/",$webserver_string)) 
		{$MAIN.="<option selected value=\"$LISTwebserver_ids[$o]\">$LISTwebservers[$o] - $LISThostnames[$o]</option>\n";}
	else
		{$MAIN.="<option value=\"$LISTwebserver_ids[$o]\">$LISTwebservers[$o] - $LISThostnames[$o]</option>\n";}
	$o++;
	}

$MAIN.="</SELECT></TD>";

$MAIN.="<TD ROWSPAN=2 VALIGN=top align=center>"._QXZ("URLs").":<BR/>";
$MAIN.="<SELECT SIZE=5 NAME=url[] multiple>\n";
if  (preg_match('/--ALL--/',$urls_string))
	{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL URLS")." --</option>\n";}
else
	{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL URLS")." --</option>\n";}

$o=0;
while ($urls_to_print > $o)
	{
	if (preg_match("/\|$LISTurl_ids[$o]\|/",$urls_string)) 
		{$MAIN.="<option selected value=\"$LISTurl_ids[$o]\">$LISTurl_ids[$o] - $LISTurls[$o]</option>\n";}
	else
		{$MAIN.="<option value=\"$LISTurl_ids[$o]\">$LISTurl_ids[$o] - $LISTurls[$o]</option>\n";}
	$o++;
	}
$MAIN.="</SELECT>";
$MAIN.="</TD>";

$MAIN.="<TD ROWSPAN=2 VALIGN=middle align=center>\n";

$MAIN.=_QXZ("Display as:");
$MAIN.="<select name='report_display_type'>";
if ($report_display_type) {$MAIN.="<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
$MAIN.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>\n<BR><BR>";

$MAIN.="<INPUT TYPE=submit NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'><BR/><BR/>\n";
$MAIN.="</TD></TR></TABLE>\n";

if ($SUBMIT && $query_date && $end_date) {
		$TEXT.="<PRE><font size=2>\n";
		$TEXT.="--- "._QXZ("WEBSERVER/URL LOG RECORDS FOR")." $query_date "._QXZ("TO")." $end_date $webserver_rpt_string, $URL_rpt_string\n";
		#$TEXT.="--- RECORDS #$lower_limit-$upper_limit";
		$TEXT.="<a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$server_ipQS$sip_hangup_causeQS&lower_limit=$lower_limit&report_display_type=$report_display_type&upper_limit=$upper_limit&file_download=1\">["._QXZ("DOWNLOAD")."]</a>\n";

		$HTML.="<BR>"._QXZ("WEBSERVER/URL LOG RECORDS FOR")." $query_date "._QXZ("TO")." $end_date $webserver_rpt_string, $URL_rpt_string\n";
		$HTML.="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$server_ipQS$sip_hangup_causeQS&lower_limit=$lower_limit&report_display_type=$report_display_type&upper_limit=$upper_limit&file_download=1\">["._QXZ("DOWNLOAD")."]</a><BR><BR>\n";

		$stmt="select webserver, login_url, count(*) from vicidial_user_log where event_date>='$query_date 00:00:00' and event_date<='$end_date 23:59:59' $webserver_SQL $login_url_SQL group by webserver, login_url order by webserver, login_url";
		$rslt=mysql_to_mysqli($stmt, $link);
		$CSV_text="\""._QXZ("VICIDIAL USER LOG")."\"\n";
		$CSV_text.="\""._QXZ("WEB SERVER")."\",\""._QXZ("URL")."\",\""._QXZ("COUNT")."\"\n";

		$vulog_div="+----------------------------------------------------+------------------------------------------------------------------------------------------------------+--------+\n";
		$TEXT.="\n"._QXZ("VICIDIAL USER LOG")."\n";
		$TEXT.=$vulog_div;
		    $TEXT.="| "._QXZ("WEB SERVER",50)." | "._QXZ("URL",100)." | "._QXZ("COUNT",6)." |\n";
		$TEXT.=$vulog_div;

		$HTML.="<table border='0' cellpadding='3' cellspacing='1'>";
		$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML.="<th colspan='3'><font size='2'>"._QXZ("VICIDIAL USER LOG")."</font></th>";
		$HTML.="</tr>\n";
		$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML.="<th><font size='2'>"._QXZ("WEB SERVER")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("URL")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("COUNT")."</font></th>";
		$HTML.="</tr>\n";
		
		$user_TOTAL=0;
		while($row=mysqli_fetch_row($rslt)) {
			$TEXT.="| ".sprintf("%-50s", substr($webserver_array[$row[0]], 0, 100))." | ".sprintf("%-100s", substr($url_array[$row[1]], 0, 100))." | ".sprintf("%6s", $row[2])." |\n";
			$CSV_text.="\"".$webserver_array[$row[0]]."\",\"".$url_array[$row[1]]."\",\"$row[2]\"\n";
			$user_TOTAL+=$row[2];
			$HTML.="<tr bgcolor='#".$SSstd_row2_background."'>";
			$HTML.="<td><font size='2'>".$webserver_array[$row[0]]."</font></td>";
			$HTML.="<td><font size='2'>".$url_array[$row[1]]."</font></td>";
			$HTML.="<td><font size='2'>".$row[2]."</font></td>";
			$HTML.="</tr>\n";
		}
		$TEXT.=$vulog_div;
		$TEXT.="| "._QXZ("TOTAL",153,"r")." | ".sprintf("%6s", $user_TOTAL)." |\n";
		$CSV_text.="\"\",\""._QXZ("TOTAL")."\",\"$user_TOTAL\"\n\n";
		$TEXT.=$vulog_div;
		$TEXT.="\n";
		$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML.="<th colspan='2'><font size='2'>"._QXZ("TOTAL")."</font></th>";
		$HTML.="<th><font size='2'>".$user_TOTAL."</font></th>";
		$HTML.="</tr>\n";
		$HTML.="</table><BR><BR>\n";

		$stmt="select webserver, api_url, count(*) from vicidial_api_log where api_date>='$query_date 00:00:00' and api_date<='$end_date 23:59:59' $webserver_SQL $api_url_SQL group by webserver, api_url order by webserver, api_url";
		$rslt=mysql_to_mysqli($stmt, $link);
		$CSV_text.="\""._QXZ("VICIDIAL API LOG")."\"\n";
		$CSV_text.="\""._QXZ("WEB SERVER")."\",\""._QXZ("URL")."\",\""._QXZ("COUNT")."\"\n";
		$valog_div="+----------------------------------------------------+------------------------------------------------------------------------------------------------------+--------+\n";
		$TEXT.="\n"._QXZ("VICIDIAL API LOG")."\n";
		$TEXT.=$valog_div;
		$TEXT.="| "._QXZ("WEB SERVER",50)." | "._QXZ("URL",100)." | "._QXZ("COUNT",6)." |\n";
		$TEXT.=$valog_div;

		$HTML.="<table border='0' cellpadding='3' cellspacing='1'>";
		$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML.="<th colspan='3'><font size='2'>"._QXZ("VICIDIAL API LOG")."</font></th>";
		$HTML.="</tr>\n";
		$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML.="<th><font size='2'>"._QXZ("WEB SERVER")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("URL")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("COUNT")."</font></th>";
		$HTML.="</tr>\n";

		$api_TOTAL=0;
		while($row=mysqli_fetch_row($rslt)) {
			$TEXT.="| ".sprintf("%-50s", substr($webserver_array[$row[0]], 0, 100))." | ".sprintf("%-100s", substr($url_array[$row[1]], 0, 100))." | ".sprintf("%6s", $row[2])." |\n";
			$CSV_text.="\"".$webserver_array[$row[0]]."\",\"".$url_array[$row[1]]."\",\"$row[2]\"\n";
			$api_TOTAL+=$row[2];
			$HTML.="<tr bgcolor='#".$SSstd_row2_background."'>";
			$HTML.="<td><font size='2'>".$webserver_array[$row[0]]."</font></td>";
			$HTML.="<td><font size='2'>".$url_array[$row[1]]."</font></td>";
			$HTML.="<td><font size='2'>".$row[2]."</font></td>";
			$HTML.="</tr>\n";
		}
		$TEXT.=$valog_div;
		$TEXT.="| "._QXZ("TOTAL",153,"r")." | ".sprintf("%6s", $api_TOTAL)." |\n";
		$CSV_text.="\"\",\""._QXZ("TOTAL")."\",\"$api_TOTAL\"\n\n";
		$TEXT.=$valog_div;
		$TEXT.="\n\n";
		$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML.="<th colspan='2'><font size='2'>"._QXZ("TOTAL")."</font></th>";
		$HTML.="<th><font size='2'>".$api_TOTAL."</font></th>";
		$HTML.="</tr>\n";
		$HTML.="</table><BR><BR>\n";

		$stmt="select webserver, count(*) from vicidial_report_log where event_date>='$query_date 00:00:00' and event_date<='$end_date 23:59:59' $webserver_SQL group by webserver order by webserver";
		$rslt=mysql_to_mysqli($stmt, $link);
		$CSV_text.="\""._QXZ("VICIDIAL REPORT LOG")."\"\n";
		$CSV_text.="\""._QXZ("WEB SERVER")."\",\""._QXZ("COUNT")."\"\n";
		$vrlog_div="+----------------------------------------------------+--------+\n";
		$TEXT.="\n"._QXZ("VICIDIAL REPORT LOG")."\n";
		$TEXT.=$vrlog_div;
		$TEXT.="| "._QXZ("WEB SERVER",50)." | "._QXZ("COUNT",6)." |\n";
		$TEXT.=$vrlog_div;

		$HTML.="<table border='0' cellpadding='3' cellspacing='1'>";
		$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML.="<th colspan='2'><font size='2'>"._QXZ("VICIDIAL REPORT LOG")."</font></th>";
		$HTML.="</tr>\n";
		$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML.="<th><font size='2'>"._QXZ("WEB SERVER")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("COUNT")."</font></th>";
		$HTML.="</tr>\n";

		$report_TOTAL=0;
		while($row=mysqli_fetch_row($rslt)) {
			$TEXT.="| ".sprintf("%-50s", substr($webserver_array[$row[0]], 0, 50))." | ".sprintf("%6s", $row[1])." |\n";
			$CSV_text.="\"".$webserver_array[$row[0]]."\",\"$row[1]\"\n";
			$report_TOTAL+=$row[1];
			$HTML.="<tr bgcolor='#".$SSstd_row2_background."'>";
			$HTML.="<td><font size='2'>".$webserver_array[$row[0]]."</font></td>";
			$HTML.="<td><font size='2'>".$row[1]."</font></td>";
			$HTML.="</tr>\n";
		}
		$TEXT.=$vrlog_div;
		$TEXT.="| "._QXZ("TOTAL",50,"r")." | ".sprintf("%6s", $report_TOTAL)." |\n";
		$CSV_text.="\""._QXZ("TOTAL")."\",\"$report_TOTAL\"\n\n";
		$TEXT.=$vrlog_div;
		$TEXT.="\n\n";
		$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML.="<th><font size='2'>"._QXZ("TOTAL")."</font></th>";
		$HTML.="<th><font size='2'>".$report_TOTAL."</font></th>";
		$HTML.="</tr>\n";
		$HTML.="</table>\n";

		$TEXT.="</PRE>\n";

}
	if ($file_download>0) {
		$FILE_TIME = date("Ymd-His");
		$CSVfilename = "AST_webserver_url_report_$US$FILE_TIME.csv";
		$CSV_text=preg_replace('/ +\"/', '"', $CSV_text);
		$CSV_text=preg_replace('/\" +/', '"', $CSV_text);
		// We'll be outputting a TXT file
		header('Content-type: application/octet-stream');

		// It will be called LIST_101_20090209-121212.txt
		header("Content-Disposition: attachment; filename=\"$CSVfilename\"");
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		ob_clean();
		flush();

		echo "$CSV_text";

	} else {
		echo $HEADER;
		require("admin_header.php");

		if ($report_display_type=="HTML") {
			$MAIN.=$HTML;
		} else {
			$MAIN.=$TEXT;
		}
		$MAIN.="</form></BODY></HTML>\n";

		echo $MAIN;
	}

$endMS = microtime();
$startMSary = explode(" ",$startMS);
$endMSary = explode(" ",$endMS);
$runS = ($endMSary[0] - $startMSary[0]);
$runM = ($endMSary[1] - $startMSary[1]);
$TOTALrun = ($runS + $runM);

$END_TIME=date("U");

#print "Total run time: ".($END_TIME-$START_TIME);

$stmt="UPDATE vicidial_report_log set run_time='$TOTALrun' where report_log_id='$report_log_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);

?>
