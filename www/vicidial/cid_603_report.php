<?php 
# cid_603_report.php
# 
# Copyright (C) 2026  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 260910-1726 - First build
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$report_name='CID SIP 603 Report';

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

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$NOW_DATE = date("Y-m-d");
if (!isset($query_date)) {$query_date = $NOW_DATE;}
$time_an_hour_ago = date('H:i:s', strtotime('-1 hour'));
if (strlen($query_date_D) < 6) {$query_date_D = $time_an_hour_ago;}
if (strlen($query_date_T) < 6) {$query_date_T = "23:59:59";}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,enable_languages,language_method,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {$MAIN.="$stmt\n";}
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
	$SSallow_web_debug =			$row[6];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$query_date = preg_replace('/[^- \:\_0-9a-zA-Z]/', '', $query_date);
$query_date_D = preg_replace('/[^- \:\_0-9a-zA-Z]/', '', $query_date_D);
$query_date_T = preg_replace('/[^- \:\_0-9a-zA-Z]/', '', $query_date_T);
$lower_limit = preg_replace('/[^-_0-9a-zA-Z]/', '', $lower_limit);
$upper_limit = preg_replace('/[^-_0-9a-zA-Z]/', '', $upper_limit);
$submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $submit);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/', '', $SUBMIT);
$report_display_type = preg_replace('/[^-_0-9a-zA-Z]/', '', $report_display_type);
$file_download = preg_replace('/[^-_0-9a-zA-Z]/', '', $file_download);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	}

$stmt="SELECT selected_language,user_group from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	$LOGuser_group =			$row[1];
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

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {$HTML_text.="|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =			$row[0];
$LOGallowed_reports =			$row[1];
$LOGadmin_viewable_groups =		$row[2];
$LOGadmin_viewable_call_times =	$row[3];

$LOGallowed_campaignsSQL='';
$whereLOGallowed_campaignsSQL='';
if ( (!preg_match('/\-ALL/i', $LOGallowed_campaigns)) )
	{
	$rawLOGallowed_campaignsSQL = preg_replace("/ -/",'',$LOGallowed_campaigns);
	$rawLOGallowed_campaignsSQL = preg_replace("/ /","','",$rawLOGallowed_campaignsSQL);
	$LOGallowed_campaignsSQL = "and campaign_id IN('$rawLOGallowed_campaignsSQL')";
	$whereLOGallowed_campaignsSQL = "where campaign_id IN('$rawLOGallowed_campaignsSQL')";
	}
$regexLOGallowed_campaigns = " $LOGallowed_campaigns ";

if ( (!preg_match("/$report_name/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
	{
    Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo "You are not allowed to view this report: |$PHP_AUTH_USER|$report_name|\n";
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
$LOGbrowser=preg_replace("/<|>|\'|\"|\\\\/","",$LOGbrowser);
$LOGrequest_uri=preg_replace("/<|>|\'|\"|\\\\/","",$LOGrequest_uri);
$LOGhttp_referer=preg_replace("/<|>|\'|\"|\\\\/","",$LOGhttp_referer);
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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$query_date, $end_date, $lower_limit, $upper_limit, $file_download|', url='$LOGfull_url', webserver='$webserver_id';";
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


$HEADER.="<HTML>\n";
$HEADER.="<HEAD>\n";
$HEADER.="<STYLE type=\"text/css\">\n";
$HEADER.="<!--\n";
$HEADER.="   .green {color: white; background-color: green}\n";
$HEADER.="   .red {color: white; background-color: red}\n";
$HEADER.="   .blue {color: white; background-color: blue}\n";
$HEADER.="   .purple {color: white; background-color: purple}\n";
$HEADER.="   .small_standard {  font-family: Arial, Helvetica, sans-serif; font-size: 8pt}\n";
$HEADER.="   .small_standard_bold {  font-family: Arial, Helvetica, sans-serif; font-size: 8pt; font-weight: bold}\n";
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

### BEGIN  figure out where the CID is in the vicidial_dial_log
$CIDbegin=0;
$CIDend=0;
$CIDlength=0;
$stmt = "SELECT outbound_cid FROM vicidial_dial_log where lead_id > 0 and (outbound_cid LIKE '\"M%' or outbound_cid LIKE '\"V%') order by call_date desc limit 1;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {$MAIN.="$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$temp_outbound_cid =			$row[0];
	$temp_outbound_cid_LENGTH = strlen($temp_outbound_cid);
	$g=0;
	while ($g < $temp_outbound_cid_LENGTH)
		{
		$temp_char = substr($temp_outbound_cid,$g,1);
		if ($temp_char == '<')
			{$CIDbegin = $g;}
		if ($temp_char == '>')
			{$CIDend = $g;}

	#	echo "DEBUG: $g|$temp_char| CIDbegin: $CIDbegin   CIDend: $CIDend   CIDlength: $CIDlength   CIDraw: $temp_outbound_cid ($temp_outbound_cid_LENGTH) \n";
		$g++;
		}
	if ( ($CIDbegin > 0) and ($CIDend > 0) )
		{
		$CIDlength = (($CIDend - $CIDbegin) - 1);
		$CIDbegin = ($CIDbegin + 2);
		}
	else
		{
		echo $HEADER;
		require("admin_header.php");
		echo "ERROR: No dial logs to analyze \n";
		exit;
		}
	}
# echo "DEBUG: CIDbegin: $CIDbegin   CIDlength: $CIDlength   CIDraw: $temp_outbound_cid ($temp_outbound_cid_LENGTH) \n";
### END  figure out where the CID is in the vicidial_dial_log

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
$MAIN.="</script></tD>\n";

$MAIN.="<TD VALIGN=TOP align=center><INPUT TYPE=TEXT NAME=query_date_D SIZE=9 MAXLENGTH=8 VALUE=\"$query_date_D\">";

$MAIN.=" "._QXZ("to")." <INPUT TYPE=TEXT NAME=query_date_T SIZE=9 MAXLENGTH=8 VALUE=\"$query_date_T\">";

$MAIN.="</TD><TD VALIGN=middle align=center>\n";
$MAIN.=_QXZ("Display as").":";
$MAIN.="<select name='report_display_type'>";
if ($report_display_type) {$MAIN.="<option value='$report_display_type' selected>$report_display_type</option>";}
$MAIN.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select></TD>";
$MAIN.="<TD VALIGN=TOP align=center><INPUT TYPE=submit NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
$MAIN.="</TD><TD>";
$MAIN.="</TD></TR></TABLE>\n";
if ($SUBMIT && $query_date) 
	{
	#	SELECT count(*) as calls,SUM(CASE WHEN sip_hangup_cause='603' THEN 1 ELSE 0 END) AS SOT, round((SUM(CASE WHEN sip_hangup_cause='603' THEN 1 ELSE 0 END) / (count(*)) * 100 ),2) as SOTpct, SUBSTRING(vdl.outbound_cid FROM 25 FOR 10) as CID FROM vicidial_dial_log vdl WHERE (vdl.call_date >= '2026-09-10 15:00:00') and (vdl.call_date <= '2026-09-10 16:00:00') group by CID order by SOTpct desc limit 5000;

	$stmt="SELECT SUBSTRING(vdl.outbound_cid FROM $CIDbegin FOR $CIDlength) as CID, count(*) as calls,SUM(CASE WHEN sip_hangup_cause='603' THEN 1 ELSE 0 END) AS SOT, round((SUM(CASE WHEN sip_hangup_cause='603' THEN 1 ELSE 0 END) / (count(*)) * 100 ),2) as SOTpct FROM vicidial_dial_log vdl WHERE (vdl.call_date >= '$query_date $query_date_D') and (vdl.call_date <= '$query_date $query_date_T') group by CID order by SOTpct desc limit 50000;";

	$rslt=mysql_to_mysqli($stmt, $link);
	$ASCII_text="<PRE><font size=2>\n";
	$HTML_text="";
	$CIDs_count=0;
	if ($DB) {$ASCII_text.=$stmt."\n";}
	if (mysqli_num_rows($rslt)>0) 
		{
		$ASCII_text.="--- "._QXZ("OUTBOUND CALLERID NUMBER SIP 603 REPORT")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T &nbsp; &nbsp; ";
		$ASCII_text.="<a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T&lower_limit=$lower_limit&upper_limit=$upper_limit&file_download=1\">["._QXZ("DOWNLOAD")."]</a> \n";
		$ASCII_text.="+-----------------+---------+---------+----------+\n";
		$ASCII_text.="| "._QXZ("CID Number",15)." | "._QXZ("CALLS",7)." | "._QXZ("603s",7)." | "._QXZ("603 pct",8)." |\n";
		$ASCII_text.="+-----------------+---------+---------+----------+\n";
		$HTML_text.="<table border='0' cellpadding='0' cellspacing='2' width='350'>";
		$HTML_text.="<TR><TH colspan='2' class='small_standard_bold grey_graph_cell'>"._QXZ("OUTBOUND CALLERID NUMBER SIP 603 REPORT")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T</TH></TR>";
		$HTML_text.="<TR><TH class='small_standard_bold grey_graph_cell'>"._QXZ("CID Number")."</th><TH class='small_standard_bold grey_graph_cell'>"._QXZ("COUNT")."</th><TH class='small_standard_bold grey_graph_cell'>"._QXZ("603s")."</th><TH class='small_standard_bold grey_graph_cell'>"._QXZ("603 pct")."</th></tr>";
		$CSV_text="\""._QXZ("CID Number")."\",\""._QXZ("CALLS")."\",\""._QXZ("603s")."\",\""._QXZ("603 pct")."\"\n";

		$total_count=0;
		$total_countSOT=0;
		while ($row=mysqli_fetch_array($rslt)) 
			{
			$ASCII_text.="| ".sprintf("%-16s", $row["CID"]);
			$ASCII_text.="| ".sprintf("%8s", $row["calls"]);
			$ASCII_text.="| ".sprintf("%8s", $row["SOT"]);
			$ASCII_text.="| ".sprintf("%8s", $row["SOTpct"])."%";
			$ASCII_text.="|\n";
			$HTML_text.="<TR><TD class='small_standard'>$row[CID]</td><TD class='small_standard'>$row[calls]</td><TD class='small_standard'>$row[SOT]</td><TD class='small_standard'>$row[SOTpct]</td></tr>";
			$CSV_text.="\"$row[CID]\",\"$row[calls]\",\"$row[SOT]\",\"$row[SOTpct]%\"\n";
			$total_count+=$row["calls"];
			$total_countSOT+=$row["SOT"];
			$CIDs_count++;
			}
		$ASCII_text.="+-----------------+---------+---------+----------+\n";
		$ASCII_text.="| "._QXZ("TOTAL",15,"r")." | ".sprintf("%8s", $total_count)."| ".sprintf("%8s", $total_countSOT)."|\n";
		$ASCII_text.="+-----------------+---------+---------+\n";
		$ASCII_text.=" "._QXZ("CIDs Count",10,"r").": $CIDs_count \n\n\n";
		$HTML_text.="<TR><TH class='small_standard_bold grey_graph_cell'>"._QXZ("TOTAL")."</th><TH class='small_standard_bold grey_graph_cell'>$total_count</th><TH class='small_standard_bold grey_graph_cell'>$total_countSOT</th></tr></table>";
		} 
	else
		{
		$MAIN.="*** "._QXZ("NO RECORDS FOUND")." ***\n";
		}
	$ASCII_text.="</font></PRE>\n";

	if ($report_display_type=="HTML")
		{
		$MAIN.=$HTML_text;
		}
	else
		{
		$MAIN.=$ASCII_text;
		}


	$MAIN.="</form></BODY></HTML>\n";


	}
	if ($file_download>0) 
		{
		$FILE_TIME = date("Ymd-His");
		$CSVfilename = "AST_url_log_report_$US$FILE_TIME.csv";
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

		exit;
		}
	else 
		{
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

$stmt="UPDATE vicidial_report_log set run_time='$TOTALrun' where report_log_id='$report_log_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);

exit;

?>
