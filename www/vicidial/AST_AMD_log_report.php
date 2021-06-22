<?php 
# AST_AMD_log_report.php
# 
# Copyright (C) 2019  Joe Johnson, Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 190329-1852 - First build, based on AST_carrier_log_report.php
# 191013-0839 - Fixes for PHP7
#

$startMS = microtime();

$report_name='AMD Log Report';

require("dbconnect_mysqli.php");
require("functions.php");

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
if (isset($_GET["server_ip"]))				{$server_ip=$_GET["server_ip"];}
	elseif (isset($_POST["server_ip"]))		{$server_ip=$_POST["server_ip"];}
if (isset($_GET["AMDSTATUS"]))				{$AMDSTATUS=$_GET["AMDSTATUS"];}
	elseif (isset($_POST["AMDSTATUS"]))		{$AMDSTATUS=$_POST["AMDSTATUS"];}
if (isset($_GET["AMDRESPONSE"]))			{$AMDRESPONSE=$_GET["AMDRESPONSE"];}
	elseif (isset($_POST["AMDRESPONSE"]))	{$AMDRESPONSE=$_POST["AMDRESPONSE"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["lower_limit"]))			{$lower_limit=$_GET["lower_limit"];}
	elseif (isset($_POST["lower_limit"]))	{$lower_limit=$_POST["lower_limit"];}
if (isset($_GET["upper_limit"]))			{$upper_limit=$_GET["upper_limit"];}
	elseif (isset($_POST["upper_limit"]))	{$upper_limit=$_POST["upper_limit"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["report_display_type"]))			{$report_display_type=$_GET["report_display_type"];}
	elseif (isset($_POST["report_display_type"]))	{$report_display_type=$_POST["report_display_type"];}

$START_TIME=date("U");

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
	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 7 and view_reports='1';";
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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name', url='$LOGfull_url', webserver='$webserver_id';";
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

if (strlen($query_date_D) < 6) {$query_date_D = "00:00:00";}
if (strlen($query_date_T) < 6) {$query_date_T = "23:59:59";}
$NOW_DATE = date("Y-m-d");
if (!isset($server_ip)) {$server_ip = array();}
if (!isset($AMDSTATUS)) {$AMDSTATUS = array();}
if (!isset($AMDRESPONSE)) {$AMDRESPONSE = array();}
if (!isset($query_date)) {$query_date = $NOW_DATE;}

$master_AMDSTATUS_array=array("HANGUP", "HUMAN", "MACHINE", "NOTSURE");
$master_AMDRESPONSE_array=array("HUMAN", "INITIALSILENCE", "LONGGREETING", "MAXWORDLENGTH", "MAXWORDS", "NOAUDIODATA", "TOOLONG");

$AMDSTATUSes_to_print=count($master_AMDSTATUS_array);
$AMDRESPONSEs_to_print=count($master_AMDRESPONSE_array);

$server_ip_string='|';
$server_ip_ct = count($server_ip);
$i=0;
while($i < $server_ip_ct)
	{
	$server_ip_string .= "$server_ip[$i]|";
	$i++;
	}

$server_stmt="SELECT server_ip,server_description from servers where active_asterisk_server='Y' order by server_ip asc";
if ($DB) {echo "|$server_stmt|\n";}
$server_rslt=mysql_to_mysqli($server_stmt, $link);
$servers_to_print=mysqli_num_rows($server_rslt);
$i=0;
$LISTserverIPs=array();
$LISTserver_names=array();
while ($i < $servers_to_print)
	{
	$row=mysqli_fetch_row($server_rslt);
	$LISTserverIPs[$i] =		$row[0];
	$LISTserver_names[$i] =	$row[1];
	if (preg_match('/\-ALL/',$server_ip_string) )
		{
		$server_ip[$i] = $LISTserverIPs[$i];
		}
	$i++;
	}

$i=0;
$server_ips_string='|';
$server_ip_ct = count($server_ip);
while($i < $server_ip_ct)
	{
	if ( (strlen($server_ip[$i]) > 0) and (preg_match("/\|$server_ip[$i]\|/",$server_ip_string)) )
		{
		$server_ips_string .= "$server_ip[$i]|";
		$server_ip_SQL .= "'$server_ip[$i]',";
		$server_ipQS .= "&server_ip[]=$server_ip[$i]";
		}
	$i++;
	}

if ( (preg_match('/\-\-ALL\-\-/',$server_ip_string) ) or ($server_ip_ct < 1) )
	{
	$server_ip_SQL = "";
	$server_rpt_string="- ALL servers ";
	if (preg_match('/\-\-ALL\-\-/',$server_ip_string)) {$server_ipQS="&server_ip[]=--ALL--";}
	}
else
	{
	$server_ip_SQL = preg_replace('/,$/i', '',$server_ip_SQL);
	$server_ip_SQL = "and server_ip IN($server_ip_SQL)";
	$server_rpt_string="- server(s) ".preg_replace('/\|/', ", ", substr($server_ip_string, 1, -1));
	}
if (strlen($server_ip_SQL)<3) {$server_ip_SQL="";}

########### AMD STATUSES
$AMDSTATUS_string='|';
$AMDRESPONSE_string='|';

$AMDSTATUS_ct = count($AMDSTATUS);
$AMDRESPONSE_ct = count($AMDRESPONSE);

$i=0;
while($i < $AMDSTATUS_ct)
	{
	$AMDSTATUS_string .= "$AMDSTATUS[$i]|";
	$i++;
	}

$j=0;
while($j < $AMDRESPONSE_ct)
	{
	$AMDRESPONSE_string .= "$AMDRESPONSE[$j]|";
	$j++;
	}

$i=0;

$i=0; $j=0;
$AMDSTATUSes_string='|';
$AMDRESPONSEs_string='|';
while($i < $AMDSTATUS_ct)
	{
	if ( (strlen($AMDSTATUS[$i]) > 0) and (preg_match("/\|$AMDSTATUS[$i]\|/",$AMDSTATUS_string)) ) 
		{
		$AMDSTATUSes_string .= "$AMDSTATUS[$i]|";
		$AMDSTATUSQS .= "&AMDSTATUS[]=$AMDSTATUS[$i]";
		}
	$i++;
	}

while ($j < $AMDRESPONSE_ct) 
	{
	if ( (strlen($AMDRESPONSE[$j]) > 0) and (preg_match("/\|$AMDRESPONSE[$j]\|/",$AMDRESPONSE_string)) ) 
		{
		$AMDRESPONSEs_string .= "$AMDRESPONSE[$j]|";
		$AMDRESPONSEQS .= "&AMDRESPONSE[]=$AMDRESPONSE[$j]";
		}
	$j++;
	}

$i=0; 
while($i < $AMDSTATUS_ct)
	{
	$j=0;
	while ($j < $AMDRESPONSE_ct) 
		{
		if ( (strlen($AMDSTATUS[$i]) > 0) and (preg_match("/\|$AMDSTATUS[$i]\|/",$AMDSTATUS_string)) and (strlen($AMDRESPONSE[$j]) > 0) and (preg_match("/\|$AMDRESPONSE[$j]\|/",$AMDRESPONSE_string)) )
			{
			if ( preg_match('/\-\-ALL\-\-/',$AMDSTATUS_string) ) {$HC_subclause="";} else {$HC_subclause="AMDSTATUS='$AMDSTATUS[$i]'";}
			if ( preg_match('/\-\-ALL\-\-/',$AMDRESPONSE_string) ) {$DS_subclause="";} else {$DS_subclause="AMDRESPONSE='$AMDRESPONSE[$j]'";}
			if ($HC_subclause=="" || $DS_subclause=="") {$conjunction="";} else {$conjunction=" and ";}
			$AMDSTATUS_SQL .= "($HC_subclause$conjunction$DS_subclause) OR";
			$AMDSTATUS_SQL=preg_replace('/\(\) OR$/', '', $AMDSTATUS_SQL);
			#$AMDSTATUS_SQL .= "(AMDSTATUS='$AMDSTATUS[$i]' and AMDRESPONSE='$AMDRESPONSE[$j]') OR";
			}
		$j++;
		}
	$i++;
	}

if ( (preg_match('/\-\-ALL\-\-/',$AMDSTATUS_string) ) or ($AMDSTATUS_ct < 1) )
	{
	$HC_rpt_string="- "._QXZ("ALL AMD statuses")." ";
	if (preg_match('/\-\-ALL\-\-/',$AMDSTATUS_string)) {$AMDSTATUSQS="&AMDSTATUS[]=--ALL--";}
	}
else
	{
	$AMDSTATUSes_string=preg_replace('/\!/', "-", $AMDSTATUSes_string);
	$HC_rpt_string="AND AMD status(es) ".preg_replace('/\|/', ", ", substr($AMDSTATUSes_string, 1, -1));
	}

if ( (preg_match('/\-\-ALL\-\-/',$AMDRESPONSE_string) ) or ($AMDRESPONSE_ct < 1) )
	{
	$AMDRESPONSE_SQL = "";
	$DS_rpt_string="- "._QXZ("ALL AMD responses")." ";
	if (preg_match('/\-\-ALL\-\-/',$AMDRESPONSE_string)) {$AMDRESPONSEQS="&AMDRESPONSE[]=--ALL--";}
	}
else
	{
	#$AMDSTATUS_SQL=preg_replace('/ OR$/', '', $AMDSTATUS_SQL);
	#$AMDSTATUS_SQL = preg_replace('/,$/i', '',$AMDSTATUS_SQL);
	#$AMDSTATUS_SQL = "and ($AMDSTATUS_SQL)";
	$AMDRESPONSEs_string=preg_replace('/\!/', "-", $AMDRESPONSEs_string);
	$DS_rpt_string="AND AMD respons(es) ".preg_replace('/\|/', ", ", substr($AMDRESPONSEs_string, 1, -1));
	}
$AMDSTATUS_SQL=preg_replace('/ OR$/', '', $AMDSTATUS_SQL);
$AMDSTATUS_SQL = preg_replace('/,$/i', '',$AMDSTATUS_SQL);
$AMDSTATUS_SQL = "and ($AMDSTATUS_SQL)";

require("screen_colors.php");

if (strlen($AMDSTATUS_SQL)<7) {$AMDSTATUS_SQL="";}

########################
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
$MAIN.=_QXZ("Date").":\n";
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

$MAIN.="<BR><BR><INPUT TYPE=TEXT NAME=query_date_D SIZE=9 MAXLENGTH=8 VALUE=\"$query_date_D\">";

$MAIN.="<BR> "._QXZ("to")." <BR><INPUT TYPE=TEXT NAME=query_date_T SIZE=9 MAXLENGTH=8 VALUE=\"$query_date_T\">";

$MAIN.="</TD><TD ROWSPAN=2 VALIGN=TOP>"._QXZ("Server IP").":<BR/>\n";
$MAIN.="<SELECT SIZE=5 NAME=server_ip[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$server_ip_string))
	{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL SERVERS")." --</option>\n";}
else
	{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL SERVERS")." --</option>\n";}
$o=0;
while ($servers_to_print > $o)
	{
	if (preg_match("/\|$LISTserverIPs[$o]\|/",$server_ip_string)) 
		{$MAIN.="<option selected value=\"$LISTserverIPs[$o]\">$LISTserverIPs[$o] - $LISTserver_names[$o]</option>\n";}
	else
		{$MAIN.="<option value=\"$LISTserverIPs[$o]\">$LISTserverIPs[$o] - $LISTserver_names[$o]</option>\n";}
	$o++;
	}
$MAIN.="</SELECT></TD>";

$MAIN.="<TD ROWSPAN=2 VALIGN=top align=center>"._QXZ("AMD Status").":<BR/>";
$MAIN.="<SELECT SIZE=5 NAME=AMDSTATUS[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$AMDSTATUSes_string))
	{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL AMD STATUSES")." --</option>\n";}
else
	{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL AMD STATUSES")." --</option>\n";}

$o=0;
while ($AMDSTATUSes_to_print > $o)
	{
	if (preg_match("/\|$master_AMDSTATUS_array[$o]\|/",$AMDSTATUSes_string)) 
		{$MAIN.="<option selected value=\"$master_AMDSTATUS_array[$o]\">"._QXZ("$master_AMDSTATUS_array[$o]")."</option>\n";}
	else
		{$MAIN.="<option value=\"$master_AMDSTATUS_array[$o]\">"._QXZ("$master_AMDSTATUS_array[$o]")."</option>\n";}
	$o++;
	}
$MAIN.="</SELECT>";
$MAIN.="</TD>";

$MAIN.="<TD ROWSPAN=2 VALIGN=top align=center>"._QXZ("AMD Response").":<BR/>";
$MAIN.="<SELECT SIZE=5 NAME=AMDRESPONSE[] multiple>\n";
if  (preg_match('/\-\-ALL\-\-/',$AMDRESPONSEs_string))
	{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL AMD RESPONSES")." --</option>\n";}
else
	{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL AMD RESPONSES")." --</option>\n";}

$o=0;

while ($AMDRESPONSEs_to_print > $o)
	{
	if (preg_match("/\|$master_AMDRESPONSE_array[$o]\|/",$AMDRESPONSEs_string)) 
		{$MAIN.="<option selected value=\"$master_AMDRESPONSE_array[$o]\">"._QXZ("$master_AMDRESPONSE_array[$o]")."</option>\n";}
	else
		{$MAIN.="<option value=\"$master_AMDRESPONSE_array[$o]\">"._QXZ("$master_AMDRESPONSE_array[$o]")."</option>\n";}
	$o++;
	}
$MAIN.="</SELECT>";
$MAIN.="</TD>";

$MAIN.="<TD ROWSPAN=2 VALIGN=middle align=center>\n";
$MAIN.=_QXZ("Display as:")."<BR>";
$MAIN.="<select name='report_display_type'>";
if ($report_display_type) {$MAIN.="<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
$MAIN.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>\n<BR><BR>";
$MAIN.="<INPUT TYPE=submit NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'><BR/><BR/>\n";
$MAIN.="</TD></TR></TABLE>\n";
if ($SUBMIT && $server_ip_ct>0) {
	$stmt="SELECT AMDSTATUS, AMDRESPONSE, count(*) as ct From vicidial_amd_log where call_date>='$query_date $query_date_D' and call_date<='$query_date $query_date_T' $server_ip_SQL $AMDSTATUS_SQL $AMDRESPONSE_SQL group by AMDSTATUS, AMDRESPONSE order by AMDSTATUS, AMDRESPONSE";

	$rslt=mysql_to_mysqli($stmt, $link);
	$TEXT.="<PRE><font size=2>\n";
	if ($DB) {$TEXT.=$stmt."\n";}
	if (mysqli_num_rows($rslt)>0) {
		$TEXT.="--- "._QXZ("AMD BREAKDOWN FOR")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T $server_rpt_string\n";
		$TEXT.="+------------+----------------------+---------+\n";
		$TEXT.="| "._QXZ("AMD STATUS",10)." | "._QXZ("AMD RESPONSE",20)." |  "._QXZ("COUNT",6)." |\n";
		$TEXT.="+------------+----------------------+---------+\n";

		$HTML.="<BR><table border='0' cellpadding='3' cellspacing='1'>";
		$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML.="<th colspan='3'><font size='2'>"._QXZ("AMD BREAKDOWN FOR")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T $server_rpt_string</font></th>";
		$HTML.="</tr>\n";
		$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML.="<th><font size='2'>"._QXZ("AMD STATUS")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("AMD RESPONSE")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("COUNT")."</font></th>";
		$HTML.="</tr>\n";

		$total_count=0;
		while ($row=mysqli_fetch_array($rslt)) {
			$TEXT.="| ".sprintf("%-11s", $row["AMDSTATUS"]);
			$TEXT.="| ".sprintf("%-21s", $row["AMDRESPONSE"]);
			$TEXT.="| ".sprintf("%-8s", $row["ct"]);
			$TEXT.="|\n";
			$total_count+=$row["ct"];
			$HTML.="<tr bgcolor='#".$SSstd_row2_background."'>";
			$HTML.="<th><font size='2'>".$row["AMDSTATUS"]."</font></th>";
			$HTML.="<th><font size='2'>".$row["AMDRESPONSE"]."</font></th>";
			$HTML.="<th><font size='2'>".$row["ct"]."</font></th>";
			$HTML.="</tr>\n";
		}
		$TEXT.="+------------+----------------------+---------+\n";
		$TEXT.="| "._QXZ("TOTAL",33,"r")." | ".sprintf("%-8s", $total_count)."|\n";
		$TEXT.="+------------+----------------------+---------+\n";
		$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML.="<th colspan='2'><font size='2'>"._QXZ("TOTAL")."</font></th>";
		$HTML.="<th><font size='2'>".$total_count."</font></th>";
		$HTML.="</tr></table><BR><BR>\n";

		$rpt_stmt="SELECT call_date,caller_code,lead_id,uniqueid,channel,server_ip,AMDSTATUS,AMDRESPONSE,AMDCAUSE,run_time,AMDSTATS from vicidial_amd_log where call_date>='$query_date $query_date_D' and call_date<='$query_date $query_date_T' $server_ip_SQL $AMDSTATUS_SQL $AMDCAUSE_SQL order by call_date asc";
		$rpt_rslt=mysql_to_mysqli($rpt_stmt, $link);
		if ($DB) {$TEXT.=$rpt_stmt."\n";}

		if (!$lower_limit) {$lower_limit=1;}
		if ($lower_limit+999>=mysqli_num_rows($rpt_rslt)) {$upper_limit=($lower_limit+mysqli_num_rows($rpt_rslt)%1000)-1;} else {$upper_limit=$lower_limit+999;}
		
		$TEXT.="\n\n--- "._QXZ("AMD LOG RECORDS FOR")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T $server_rpt_string, $HC_rpt_string, $DS_rpt_string\n --- "._QXZ("RECORDS")." #$lower_limit-$upper_limit               <a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$server_ipQS$AMDSTATUSQS$AMDRESPONSEQS&lower_limit=$lower_limit&upper_limit=$upper_limit&file_download=1\">["._QXZ("DOWNLOAD")."]</a>\n\n";
		$carrier_rpt.="+---------------------+--------------------------------+-----------+----------------------+------------------------------------------+-----------------+------------+----------------------+--------------------------------+----------------------+------------------------------------------+----------------------+\n";
		$carrier_rpt.="| "._QXZ("CALL DATE",19)." | "._QXZ("CALLER CODE",30)." | "._QXZ("LEAD ID",9)." | "._QXZ("UNIQUE ID",20)." | "._QXZ("CHANNEL",40)." | "._QXZ("SERVER IP",15)." | "._QXZ("AMD STATUS",10)." | "._QXZ("AMD RESPONSE",20)." | "._QXZ("AMD CAUSE",30)." | "._QXZ("RUN TIME",20)." | "._QXZ("AMD STATS",40)." | "._QXZ("PHONE NUMBER",20)." |\n";
		$carrier_rpt.="+---------------------+--------------------------------+-----------+----------------------+------------------------------------------+-----------------+------------+----------------------+--------------------------------+----------------------+------------------------------------------+----------------------+\n";
		$CSV_text="\""._QXZ("CALL DATE")."\",\""._QXZ("CALLER CODE")."\",\""._QXZ("LEAD ID")."\",\""._QXZ("UNIQUE ID")."\",\""._QXZ("CHANNEL")."\",\""._QXZ("SERVER IP")."\",\""._QXZ("AMD STATUS")."\",\""._QXZ("AMD RESPONSE")."\",\""._QXZ("AMD CAUSE")."\",\""._QXZ("RUN TIME")."\",\""._QXZ("AMD STATS")."\",\""._QXZ("PHONE NUMBER")."\"\n";

		$HTML.="<table border='0' cellpadding='3' cellspacing='1'>";
		$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML.="<th colspan='10'><font size='2'>"._QXZ("AMD LOG RECORDS FOR")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T $server_rpt_string, $HC_rpt_string, $DS_rpt_string\n --- "._QXZ("RECORDS")." #$lower_limit-$upper_limit</font></th>";
		$HTML.="<th colspan='2'><font size='2'><a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$server_ipQS$AMDSTATUSQS$AMDRESPONSEQS&lower_limit=$lower_limit&upper_limit=$upper_limit&file_download=1\">["._QXZ("DOWNLOAD")."]</a></font></th>";
		$HTML.="</tr>\n";
		$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
		$HTML.="<th><font size='2'>"._QXZ("CALL DATE")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("CALLER CODE")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("LEAD ID")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("UNIQUE ID")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("CHANNEL")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("SERVER IP")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("AMD STATUS")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("AMD RESPONSE")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("AMD CAUSE")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("RUN TIME")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("AMD STATS")."</font></th>";
		$HTML.="<th><font size='2'>"._QXZ("PHONE NUMBER")."</font></th>";
		$HTML.="</tr>\n";

		for ($i=1; $i<=mysqli_num_rows($rpt_rslt); $i++) {
			$row=mysqli_fetch_array($rpt_rslt);
			$phone_number=""; $phone_note="";

			$stmt2="SELECT phone_number, alt_phone, address3 from vicidial_list where lead_id='$row[lead_id]'";
			$rslt2=mysql_to_mysqli($stmt2, $link);
			$channel=$row["channel"];
			while ($row2=mysqli_fetch_array($rslt2)) {
				if (strlen($row2["alt_phone"])>=7 && preg_match("/$row2[alt_phone]/", $channel)) {$phone_number=$row2["alt_phone"]; $phone_note="ALT";}
				else if (strlen($row2["address3"])>=7 && preg_match("/$row2[address3]/", $channel)) {$phone_number=$row2["address3"]; $phone_note="ADDR3";}
				else {$phone_number=$row2["phone_number"];}
			}


			$CSV_text.="\"$row[call_date]\",\"$row[caller_code]\",\"$row[lead_id]\",\"$row[uniqueid]\",\"$row[channel]\",\"$row[server_ip]\",\"$row[AMDSTATUS]\",\"$row[AMDRESPONSE]\",\"$row[AMDCAUSE]\",\"$row[run_time]\",\"$row[AMDSTATS]\",\"$phone_number\"\n";
			if ($i>=$lower_limit && $i<=$upper_limit) {
				if (strlen($row["channel"])>37) {$row["channel"]=substr($row["channel"],0,37)."...";}
				$carrier_rpt.="| ".sprintf("%-20s", $row["call_date"]); 
				$carrier_rpt.="| ".sprintf("%-31s", $row["caller_code"]); 
				$carrier_rpt.="| <a href=\"./admin_modify_lead.php?lead_id=".$row["lead_id"]."&CIDdisplay=Yes\">".sprintf("%-10s", $row["lead_id"])."</a>"; 
				$carrier_rpt.="| ".sprintf("%-21s", $row["uniqueid"]); 
				$carrier_rpt.="| ".sprintf("%-41s", $row["channel"]); 
				$carrier_rpt.="| ".sprintf("%-16s", $row["server_ip"]); 
				$carrier_rpt.="| ".sprintf("%-11s", $row["AMDSTATUS"]); 
				$carrier_rpt.="| ".sprintf("%-21s", $row["AMDRESPONSE"]); 
				$carrier_rpt.="| ".sprintf("%-31s", $row["AMDCAUSE"]); 
				$carrier_rpt.="| ".sprintf("%-21s", $row["run_time"]); 
				$carrier_rpt.="| ".sprintf("%-41s", $row["AMDSTATS"]); 
				$carrier_rpt.="| ".sprintf("%-21s", $phone_number)."|\n"; 

				$HTML.="<tr bgcolor='#".$SSstd_row2_background."'>";
				$HTML.="<th><font size='2'>".$row["call_date"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["caller_code"]."</font></th>";
				$HTML.="<th><font size='2'><a href=\"./admin_modify_lead.php?lead_id=".$row["lead_id"]."&CIDdisplay=Yes\">".$row["lead_id"]."</a></font></th>";
				$HTML.="<th><font size='2'>".$row["uniqueid"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["channel"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["server_ip"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["AMDSTATUS"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["AMDRESPONSE"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["AMDCAUSE"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["run_time"]."</font></th>";
				$HTML.="<th><font size='2'>".$row["AMDSTATS"]."</font></th>";
				$HTML.="<th><font size='2'>".$phone_number."</font></th>";
				$HTML.="</tr>\n";
			}
		}
		$carrier_rpt.="+---------------------+--------------------------------+-----------+----------------------+------------------------------------------+-----------------+------------+----------------------+--------------------------------+----------------------+------------------------------------------+----------------------+\n";

		$carrier_rpt_hf="";
		$ll=$lower_limit-1000;
		$HTML.="<tr bgcolor='#".$SSstd_row1_background."'>";
		if ($ll>=1) {
			$carrier_rpt_hf.="<a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$server_ipQS$AMDSTATUSQS$AMDRESPONSEQS&lower_limit=$ll\">[<<< "._QXZ("PREV")." 1000 "._QXZ("records")."]</a>";
			$HTML.="<td align='left' colspan='6'><font size='2'><a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$server_ipQS$AMDSTATUSQS$AMDRESPONSEQS&lower_limit=$ll\">[<<< "._QXZ("PREV")." 1000 "._QXZ("records")."]</a></font></th>";
		} else {
			$carrier_rpt_hf.=sprintf("%-23s", " ");
			$HTML.="<th colspan='6'>&nbsp;</th>";
		}
		$carrier_rpt_hf.=sprintf("%-264s", " ");
		if (($lower_limit+1000)<mysqli_num_rows($rpt_rslt)) {
			if ($upper_limit+1000>=mysqli_num_rows($rpt_rslt)) {$max_limit=mysqli_num_rows($rpt_rslt)-$upper_limit;} else {$max_limit=1000;}
			$carrier_rpt_hf.="<a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$server_ipQS$AMDSTATUSQS$AMDRESPONSEQS&lower_limit=".($lower_limit+1000)."\">["._QXZ("NEXT")." $max_limit "._QXZ("records")." >>>]</a>";
			$HTML.="<td align='right' colspan='6'><font size='2'><a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$server_ipQS$AMDSTATUSQS$AMDRESPONSEQS&lower_limit=".($lower_limit+1000)."\">["._QXZ("NEXT")." $max_limit "._QXZ("records")." >>>]</a></font></th>";
		} else {
			$carrier_rpt_hf.=sprintf("%23s", " ");
			$HTML.="<th colspan='6'>&nbsp;</th>";
		}
		$carrier_rpt_hf.="\n";
		$TEXT.=$carrier_rpt_hf.$carrier_rpt.$carrier_rpt_hf;
		$HTML.="</tr></table>";

	} else {
		$TEXT.="*** "._QXZ("NO RECORDS FOUND")." ***\n";
		$HTML.="*** "._QXZ("NO RECORDS FOUND")." ***\n";
	}
	$TEXT.="</font></PRE>\n";

	if ($report_display_type=="HTML") {
		$MAIN.=$HTML;
	} else {
		$MAIN.=$TEXT;
	}

	$MAIN.="</form></BODY></HTML>\n";


}
	if ($file_download>0) {
		$FILE_TIME = date("Ymd-His");
		$CSVfilename = "AST_AMD_log_report_$US$FILE_TIME.csv";
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
		echo $MAIN;
	}

if ($db_source == 'S')
	{
	mysqli_close($link);
	$use_slave_server=0;
	$db_source = 'M';
	require("dbconnect_mysqli.php");
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

exit;

?>
