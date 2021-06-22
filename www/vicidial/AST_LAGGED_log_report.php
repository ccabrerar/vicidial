<?php 
# AST_LAGGED_log_report.php
# 
# Copyright (C) 2017  Joe Johnson, Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 130622-1026 - First build
# 130901-2027 - Changed to mysqli PHP functions
# 140108-0736 - Added webserver and hostname to report logging
# 141114-0832 - Finalized adding QXZ translation to all admin files
# 141230-1446 - Added code for on-the-fly language translations display
# 170409-1536 - Added IP List validation code
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$report_name='LAGGED Agent Log Report';

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
if (isset($_GET["url_type"]))				{$url_type=$_GET["url_type"];}
	elseif (isset($_POST["url_type"]))		{$url_type=$_POST["url_type"];}
if (isset($_GET["response_sec"]))			{$response_sec=$_GET["response_sec"];}
	elseif (isset($_POST["response_sec"]))	{$response_sec=$_POST["response_sec"];}
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

$NOW_DATE = date("Y-m-d");

if (strlen($query_date_D) < 6) {$query_date_D = "00:00:00";}
if (strlen($query_date_T) < 6) {$query_date_T = "23:59:59";}
if (!isset($query_date)) {$query_date = $NOW_DATE;}

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
$MAIN.="</TD></TR></TABLE>\n";
if ($SUBMIT && $query_date) {
	$stmt="SELECT server_ip, count(*) as ct From vicidial_agent_log where event_time>='$query_date $query_date_D' and event_time<='$query_date $query_date_T' and sub_status='LAGGED' group by server_ip order by server_ip";
	$rslt=mysql_to_mysqli($stmt, $link);
	$ASCII_text="<PRE><font size=2>\n";
	$HTML_text="";
	if ($DB) {$ASCII_text.=$stmt."\n";}
	if (mysqli_num_rows($rslt)>0) {
		$ASCII_text.="--- "._QXZ("SERVER IP BREAKDOWN FOR LAGGED RECORDS")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T \n";
		$ASCII_text.="+-----------------+---------+\n";
		$ASCII_text.="| "._QXZ("SERVER IP",15)." | "._QXZ("COUNT",7)." |\n";
		$ASCII_text.="+-----------------+---------+\n";
		$HTML_text.="<table border='0' cellpadding='0' cellspacing='2' width='350'>";
		$HTML_text.="<TR><TH colspan='2' class='small_standard_bold grey_graph_cell'>"._QXZ("SERVER IP BREAKDOWN FOR LAGGED RECORDS")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T</TH></TR>";
		$HTML_text.="<TR><TH class='small_standard_bold grey_graph_cell'>"._QXZ("SERVER IP")."</th><TH class='small_standard_bold grey_graph_cell'>"._QXZ("COUNT")."</th></tr>";

		$total_count=0;
		while ($row=mysqli_fetch_array($rslt)) {
			$ASCII_text.="| ".sprintf("%-16s", $row["server_ip"]);
			$ASCII_text.="| ".sprintf("%-8s", $row["ct"]);
			$ASCII_text.="|\n";
			$HTML_text.="<TR><TD class='small_standard'>$row[server_ip]</td><TD class='small_standard'>$row[ct]</td></tr>";
			$total_count+=$row["ct"];
		}
		$ASCII_text.="+-----------------+---------+\n";
		$ASCII_text.="| "._QXZ("TOTAL",15,"r")." | ".sprintf("%-8s", $total_count)."|\n";
		$ASCII_text.="+-----------------+---------+\n\n\n";
		$HTML_text.="<TR><TH class='small_standard_bold grey_graph_cell'>"._QXZ("TOTAL")."</th><TH class='small_standard_bold grey_graph_cell'>$total_count</th></tr></table>";


		$rpt_stmt="SELECT * from vicidial_agent_log where sub_status='LAGGED' and event_time>='$query_date $query_date_D' and event_time<='$query_date $query_date_T' $url_type_SQL order by user, event_time asc";
		$rpt_rslt=mysql_to_mysqli($rpt_stmt, $link);
		if ($DB) {$ASCII_text.=$rpt_stmt."\n";}

		if (!$lower_limit) {$lower_limit=1;}
		if ($lower_limit+999>=mysqli_num_rows($rpt_rslt)) {$upper_limit=($lower_limit+mysqli_num_rows($rpt_rslt)%1000)-1;} else {$upper_limit=$lower_limit+999;}

		$ASCII_text.="--- "._QXZ("LAGGED LOG RECORDS FOR")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T $server_rpt_string, "._QXZ("RECORDS")." #$lower_limit-$upper_limit               <a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$url_typeQS&lower_limit=$lower_limit&upper_limit=$upper_limit&file_download=1\">["._QXZ("DOWNLOAD")."]</a>\n";
		$lagged_rpt.="+--------------+------------+-----------------+---------------------+-----------+----------+--------+----------------------+----------------------+----------------------+\n";
		$lagged_rpt.="| "._QXZ("AGENT LOG ID",12)." | "._QXZ("USER",10)." | "._QXZ("SERVER IP",15)." | "._QXZ("EVENT TIME",19)." | "._QXZ("LEAD ID",9)." | "._QXZ("CAMPAIGN",8)." | "._QXZ("STATUS",6)." | "._QXZ("USER GROUP",20)." | "._QXZ("COMMENTS",20)." | "._QXZ("UNIQUE ID",20)." |\n";
		$lagged_rpt.="+--------------+------------+-----------------+---------------------+-----------+----------+--------+----------------------+----------------------+----------------------+\n";

		$HTML_text.="<BR><BR><table border='0' cellpadding='0' cellspacing='2' width='1000'>";
		$HTML_rpt.="<TR><TH colspan='9' class='small_standard_bold grey_graph_cell'>"._QXZ("LAGGED LOG RECORDS FOR")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T $server_rpt_string, "._QXZ("RECORDS")." #$lower_limit-$upper_limit</TH><TD align='right' class='small_standard_bold grey_graph_cell'><a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$url_typeQS&lower_limit=$lower_limit&upper_limit=$upper_limit&file_download=1\">["._QXZ("DOWNLOAD")."]</a></td></TR>";
		$HTML_rpt.="<TR><TH class='small_standard_bold grey_graph_cell' width='90'>"._QXZ("AGENT LOG ID")."</TH><TH class='small_standard_bold grey_graph_cell' width='80'>"._QXZ("USER")."</TH><TH class='small_standard_bold grey_graph_cell' width='120'>"._QXZ("SERVER IP")."</TH><TH class='small_standard_bold grey_graph_cell' width='100'>"._QXZ("EVENT TIME")."</TH><TH class='small_standard_bold grey_graph_cell' width='80'>"._QXZ("LEAD ID")."</TH><TH class='small_standard_bold grey_graph_cell' width='80'>"._QXZ("CAMPAIGN")."</TH><TH class='small_standard_bold grey_graph_cell' width='60'>"._QXZ("STATUS")."</TH><TH class='small_standard_bold grey_graph_cell' width='80'>"._QXZ("USER GROUP")."</TH><TH class='small_standard_bold grey_graph_cell' width='160'>"._QXZ("COMMENTS")."</TH><TH class='small_standard_bold grey_graph_cell' width='120'>"._QXZ("UNIQUE ID")."</TH></TR>";

		$CSV_text="\""._QXZ("AGENT LOG ID")."\",\""._QXZ("USER")."\",\""._QXZ("SERVER IP")."\",\""._QXZ("EVENT TIME")."\",\""._QXZ("LEAD ID")."\",\""._QXZ("CAMPAIGN")."\",\""._QXZ("STATUS")."\",\""._QXZ("USER GROUP")."\",\""._QXZ("COMMENTS")."\",\""._QXZ("UNIQUE ID")."\"\n";

		for ($i=1; $i<=mysqli_num_rows($rpt_rslt); $i++) {
			$row=mysqli_fetch_array($rpt_rslt);

			$CSV_text.="\"$row[agent_log_id]\",\"$row[user]\",\"$row[server_ip]\",\"$row[event_time]\",\"$row[lead_id]\",\"$row[campaign_id]\",\"$row[status]\",\"$row[user_group]\",\"$row[comments]\",\"$row[uniqueid]\"\n";
			if ($i>=$lower_limit && $i<=$upper_limit) {
				if ($i%2==0) {$color_class="grey_graph_cell";} else {$color_class='white_graph_cell';}

				$HTML_rpt.="<TR valign='top'><td class='small_standard_bold $color_class' width='90'>$row[agent_log_id]</td><td class='small_standard_bold $color_class' width='80'>$row[user]</td><td class='small_standard_bold $color_class' width='120'>$row[server_ip]</td><td class='small_standard_bold $color_class' width='100'>$row[event_time]</td><td class='small_standard_bold $color_class' width='80'>$row[lead_id]</td><td class='small_standard_bold $color_class' width='80'>$row[campaign_id]</td><td class='small_standard_bold $color_class' width='60'>$row[status]</td><td class='small_standard_bold $color_class' width='80'>$row[user_group]</td><td class='small_standard_bold $color_class' width='160'>$row[comments]</td><td class='small_standard_bold $color_class' width='120'>$row[uniqueid]</td></TR>";

				$lagged_rpt.="| ".sprintf("%-13s", $row["agent_log_id"]); 
				$lagged_rpt.="| ".sprintf("%-11s", $row["user"]); 
				$lagged_rpt.="| ".sprintf("%-16s", $row["server_ip"]); 
				$lagged_rpt.="| ".sprintf("%-20s", $row["event_time"]);
				$lagged_rpt.="| ".sprintf("%-10s", $row["lead_id"]);
				$lagged_rpt.="| ".sprintf("%-9s", $row["campaign_id"]);
				$lagged_rpt.="| ".sprintf("%-7s", $row["status"]);
				$lagged_rpt.="| ".sprintf("%-21s", $row["user_group"]);
				$lagged_rpt.="| ".sprintf("%-21s", $row["comments"]);
				$lagged_rpt.="| ".sprintf("%-21s", $row["uniqueid"]);
				$lagged_rpt.="|\n";
			}
		}
		$lagged_rpt.="+--------------+------------+-----------------+---------------------+-----------+----------+--------+----------------------+----------------------+----------------------+\n";

		$lagged_rpt_hf="";
		$HTML_rpt_hf="<TR>";
		$ll=$lower_limit-1000;
		if ($ll<1 || ($lower_limit+1000)>=mysqli_num_rows($rpt_rslt)) {$HTML_colspan=6;} else {$HTML_colspan=3;}

		if ($ll>=1) {
			$lagged_rpt_hf.="<a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&report_display_type=$report_display_type&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$url_typeQS&lower_limit=$ll\">[<<< "._QXZ("PREV 1000 records")."]</a>";
			$HTML_rpt_hf.="<Td colspan='$HTML_colspan' class='small_standard_bold grey_graph_cell' align='left'><a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&report_display_type=$report_display_type&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$url_typeQS&lower_limit=$ll\">[<<< "._QXZ("PREV 1000 records")."]</a></TH>";

		} else {
			$lagged_rpt_hf.=sprintf("%-23s", " ");
		}
		$lagged_rpt_hf.=sprintf("%-145s", " ");
		if (($lower_limit+1000)<mysqli_num_rows($rpt_rslt)) {
			if ($upper_limit+1000>=mysqli_num_rows($rpt_rslt)) {$max_limit=mysqli_num_rows($rpt_rslt)-$upper_limit;} else {$max_limit=1000;}
			$lagged_rpt_hf.="<a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&report_display_type=$report_display_type&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$url_typeQS&lower_limit=".($lower_limit+1000)."\">["._QXZ("NEXT")." $max_limit "._QXZ("records")." >>>]</a>";
			$HTML_rpt_hf.="<Td colspan='$HTML_colspan' class='small_standard_bold grey_graph_cell' align='right'><a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&report_display_type=$report_display_type&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$url_typeQS&lower_limit=".($lower_limit+1000)."\">["._QXZ("NEXT")." $max_limit "._QXZ("records")." >>>]</a></TH>";
		} else {
			$lagged_rpt_hf.=sprintf("%23s", " ");
		}
		$HTML_rpt_hf.="</TR>";
		$lagged_rpt_hf.="\n";
		$ASCII_text.=$lagged_rpt_hf.$lagged_rpt.$lagged_rpt_hf;
		$HTML_text.=$HTML_rpt_hf.$HTML_rpt.$HTML_rpt_hf."</table>";
	} else {
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
	if ($file_download>0) {
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

$stmt="UPDATE vicidial_report_log set run_time='$TOTALrun' where report_log_id='$report_log_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);

exit;

?>
