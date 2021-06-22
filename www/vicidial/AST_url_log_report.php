<?php 
# AST_url_log_report.php
# 
# Copyright (C) 2017  Joe Johnson, Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 130620-0806 - First build
# 130901-2005 - Changed to mysqli PHP functions
# 140108-0732 - Added webserver and hostname to report logging
# 141114-0713 - Finalized adding QXZ translation to all admin files
# 141230-1415 - Added code for on-the-fly language translations display
# 151211-0950 - Added missing url types
# 170409-1536 - Added IP List validation code
# 170710-1801 - Added webform url type
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$report_name='URL Log Report';

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

if (strlen($query_date_D) < 6) {$query_date_D = "00:00:00";}
if (strlen($query_date_T) < 6) {$query_date_T = "23:59:59";}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($url_type)) {$url_type = array();}

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


$url_type_string='|';
$url_type_ct = count($url_type);
$i=0;
while($i < $url_type_ct)
	{
	$url_type_string .= "$url_type[$i]|";
	$i++;
	}

$LISTurltypes=array("add_lead", "custom", "dispo", "na_callurl", "non-agent", "other", "qm_socket", "start", "start_ra","nva_phone","DID_FILTER","DNCcom","park_ivr_c","webform");

$url_types_to_print=count($LISTurltypes);
$i=0;
while ($i < $url_types_to_print)
	{
	if (preg_match('/\-ALL/',$url_type_string) )
		{
		$url_type[$i] = $LISTurltypes[$i];
		}
	$i++;
	}

$i=0;
$url_types_string='|';
$url_type_ct = count($url_type);
while($i < $url_type_ct)
	{
	if ( (strlen($url_type[$i]) > 0) and (preg_match("/\|$url_type[$i]\|/",$url_type_string)) )
		{
		$url_types_string .= "$url_type[$i]|";
		$url_type_SQL .= "'$url_type[$i]',";
		$url_typeQS .= "&url_type[]=$url_type[$i]";
		}
	$i++;
	}

if ( (preg_match('/\-\-ALL\-\-/',$url_type_string) ) or ($url_type_ct < 1) )
	{
	$url_type_SQL = "";
	$url_rpt_string="- "._QXZ("ALL servers")." ";
	if (preg_match('/\-\-ALL\-\-/',$url_type_string)) {$url_typeQS="&url_type[]=--ALL--";}
	}
else
	{
	$url_type_SQL = preg_replace('/,$/i', '',$url_type_SQL);
	$url_type_SQL = "and url_type IN($url_type_SQL)";
	$url_rpt_string="- "._QXZ("server(s)")." ".preg_replace('/\|/', ", ", substr($url_type_string, 1, -1));
	}
if (strlen($url_type_SQL)<3) {$url_type_SQL="";}

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
$MAIN.="</script>\n";

$MAIN.="<BR><BR><INPUT TYPE=TEXT NAME=query_date_D SIZE=9 MAXLENGTH=8 VALUE=\"$query_date_D\">";

$MAIN.="<BR> "._QXZ("to")." <BR><INPUT TYPE=TEXT NAME=query_date_T SIZE=9 MAXLENGTH=8 VALUE=\"$query_date_T\">";

$MAIN.="</TD><TD ROWSPAN=2 VALIGN=TOP>"._QXZ("URL type").":<BR/>\n";
$MAIN.="<SELECT SIZE=5 NAME=url_type[] multiple>\n";
if  (preg_match('/--ALL--/',$url_type_string))
	{$MAIN.="<option value=\"--ALL--\" selected>-- "._QXZ("ALL URL TYPES")." --</option>\n";}
else
	{$MAIN.="<option value=\"--ALL--\">-- "._QXZ("ALL URL TYPES")." --</option>\n";}
$o=0;
while ($url_types_to_print > $o)
	{
	if (preg_match("/\|$LISTurltypes[$o]\|/",$url_type_string)) 
		{$MAIN.="<option selected value=\"$LISTurltypes[$o]\">"._QXZ(preg_replace("/_/", " ", $LISTurltypes[$o]))."</option>\n";}
	else
		{$MAIN.="<option value=\"$LISTurltypes[$o]\">"._QXZ(preg_replace("/_/", " ", $LISTurltypes[$o]))."</option>\n";}
	$o++;
	}
$MAIN.="</SELECT></TD><TD ROWSPAN=2 VALIGN=middle align=center>\n";
$MAIN.=_QXZ("Display as").":<BR>";
$MAIN.="<select name='report_display_type'>";
if ($report_display_type) {$MAIN.="<option value='$report_display_type' selected>"._QXZ("$report_display_type")."</option>";}
$MAIN.="<option value='TEXT'>"._QXZ("TEXT")."</option><option value='HTML'>"._QXZ("HTML")."</option></select>\n<BR><BR>";
$MAIN.="<INPUT TYPE=submit NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'><BR/><BR/>\n";
$MAIN.="</TD></TR></TABLE>\n";
if ($SUBMIT && $url_type_ct>0) {
	$stmt="select url_type, count(*) as ct From vicidial_url_log where url_date>='$query_date $query_date_D' and url_date<='$query_date $query_date_T' $url_type_SQL $server_ip_SQL group by url_type order by url_type";
	$rslt=mysql_to_mysqli($stmt, $link);
	$ASCII_text="<PRE><font size=2>\n";
	$HTML_text="";
	if ($DB) {$ASCII_text.=$stmt."\n";}
	if (mysqli_num_rows($rslt)>0) {
		$ASCII_text.="--- "._QXZ("URL TYPE BREAKDOWN FOR")." $query_date, $query_date_D TO $query_date_T $server_rpt_string\n";
		$ASCII_text.="+--------------+---------+\n";
		$ASCII_text.="| "._QXZ("URL TYPE",12)." | "._QXZ("COUNT",7)." |\n";
		$ASCII_text.="+--------------+---------+\n";
		$HTML_text.="<table border='0' cellpadding='0' cellspacing='2' width='350'>";
		$HTML_text.="<TR><TH colspan='2' class='small_standard_bold grey_graph_cell'>"._QXZ("URL TYPE BREAKDOWN FOR")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T $server_rpt_string</TH></TR>";
		$HTML_text.="<TR><TH class='small_standard_bold grey_graph_cell'>"._QXZ("URL TYPE")."</th><TH class='small_standard_bold grey_graph_cell'>"._QXZ("COUNT")."</th></tr>";

		$total_count=0;
		while ($row=mysqli_fetch_array($rslt)) {
			$ASCII_text.="| ".sprintf("%-13s", $row["url_type"]);
			$ASCII_text.="| ".sprintf("%-8s", $row["ct"]);
			$ASCII_text.="|\n";
			$HTML_text.="<TR><TD class='small_standard'>$row[url_type]</td><TD class='small_standard'>$row[ct]</td></tr>";
			$total_count+=$row["ct"];
		}
		$ASCII_text.="+--------------+---------+\n";
		$ASCII_text.="| "._QXZ("TOTAL",12,"r")." | ".sprintf("%-8s", $total_count)."|\n";
		$ASCII_text.="+--------------+---------+\n\n";
		$HTML_text.="<TR><TH class='small_standard_bold grey_graph_cell'>"._QXZ("TOTAL")."</th><TH class='small_standard_bold grey_graph_cell'>$total_count</th></tr></table>";


		$rpt_stmt="select * from vicidial_url_log where url_date>='$query_date $query_date_D' and url_date<='$query_date $query_date_T' $url_type_SQL order by url_date asc";
		$rpt_rslt=mysql_to_mysqli($rpt_stmt, $link);
		if ($DB) {$ASCII_text.=$rpt_stmt."\n";}

		if (!$lower_limit) {$lower_limit=1;}
		if ($lower_limit+999>=mysqli_num_rows($rpt_rslt)) {$upper_limit=($lower_limit+mysqli_num_rows($rpt_rslt)%1000)-1;} else {$upper_limit=$lower_limit+999;}
		
		$ASCII_text.="--- "._QXZ("URL LOG RECORDS FOR")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T $server_rpt_string, "._QXZ("RECORDS")." #$lower_limit-$upper_limit               <a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$url_typeQS&lower_limit=$lower_limit&upper_limit=$upper_limit&file_download=1\">["._QXZ("DOWNLOAD")."]</a>\n";
		$url_rpt.="+----------------------+---------------------+--------------+----------+----------------------------------------------------------------------------------+----------------------------------------------------------------------------------+\n";
		$url_rpt.="| "._QXZ("UNIQUE ID",20)." | "._QXZ("URL DATE",19)." | "._QXZ("URL TYPE",12)." | "._QXZ("RESP SEC",8)." | "._QXZ("URL",80)." | "._QXZ("URL RESPONSE",80)." |\n";
		$url_rpt.="+----------------------+---------------------+--------------+----------+----------------------------------------------------------------------------------+----------------------------------------------------------------------------------+\n";

		$HTML_text.="<BR><BR><table border='0' cellpadding='0' cellspacing='2' width='1000'>";
		$HTML_rpt.="<TR><TH colspan='5' class='small_standard_bold grey_graph_cell'>"._QXZ("URL LOG RECORDS FOR")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T $server_rpt_string, "._QXZ("RECORDS")." #$lower_limit-$upper_limit</TH><TD align='right' class='small_standard_bold grey_graph_cell'><a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$url_typeQS&lower_limit=$lower_limit&upper_limit=$upper_limit&file_download=1\">["._QXZ("DOWNLOAD")."]</a></td></TR>";
		$HTML_rpt.="<TR><TH class='small_standard_bold grey_graph_cell' width='90'>"._QXZ("UNIQUE ID")."</TH><TH class='small_standard_bold grey_graph_cell' width='120'>"._QXZ("URL DATE")."</TH><TH class='small_standard_bold grey_graph_cell' width='70'>"._QXZ("URL TYPE")."</TH><TH class='small_standard_bold grey_graph_cell' width='70'>"._QXZ("RESP SEC")."</TH><TH class='small_standard_bold grey_graph_cell' width='300'>"._QXZ("URL")."</TH><TH class='small_standard_bold grey_graph_cell' width='300'>"._QXZ("URL RESPONSE")."</TH></TR>";

		$CSV_text="\""._QXZ("UNIQUE ID")."\",\""._QXZ("URL DATE")."\",\""._QXZ("URL TYPE")."\",\""._QXZ("RESP SEC")."\",\""._QXZ("URL")."\",\""._QXZ("URL RESPONSE")."\"\n";

		for ($i=1; $i<=mysqli_num_rows($rpt_rslt); $i++) {
			$row=mysqli_fetch_array($rpt_rslt);
			$phone_number=""; $phone_note="";

			if (strlen($row["phone_number"])==0) {
				$stmt2="select phone_number, alt_phone, address3 from vicidial_list where lead_id='$row[lead_id]'";
				$rslt2=mysql_to_mysqli($stmt2, $link);
				while ($row2=mysqli_fetch_array($rslt2)) {
					if (strlen($row2["alt_phone"])>=7 && preg_match("/$row2[alt_phone]/", $channel)) {$phone_number=$row2["alt_phone"]; $phone_note="ALT";}
					else if (strlen($row2["address3"])>=7 && preg_match("/$row2[address3]/", $channel)) {$phone_number=$row2["address3"]; $phone_note="ADDR3";}
					else if (strlen($row2["phone_number"])>=7 && preg_match("/$row2[phone_number]/", $channel)) {$phone_number=$row2["phone_number"]; $phone_note="*";}
				}
			} else {
				$phone_number=$row["phone_number"];
			}

			$CSV_text.="\"$row[uniqueid]\",\"$row[url_date]\",\"$row[url_type]\",\"$row[response_sec]\",\"$row[url]\",\"$row[url_response]\"\n";
			if ($i>=$lower_limit && $i<=$upper_limit) {
				if ($i%2==0) {$color_class="grey_graph_cell";} else {$color_class='white_graph_cell';}
				$row["url_response"]=preg_replace("/\r/", "\\r", $row["url_response"]);
				$row["url_response"]=preg_replace("/\n/", "\\n", $row["url_response"]);

				$HTML_rpt.="<TR valign='top'><td class='small_standard_bold $color_class' width='90'><div style='width: 90px' class='wordwrap'>$row[uniqueid]</div></td><td class='small_standard_bold $color_class' width='120'><div style='width: 120px'>$row[url_date]</div></td><td class='small_standard_bold $color_class' width='70'><div style='width: 70px'>$row[url_type]</div></td><td class='small_standard_bold $color_class' width='70'><div style='width: 70px'>$row[response_sec]</div></td><td class='small_standard_bold $color_class' width='350'><div style='width: 350px' class='wordwrap'>$row[url]</div></td><td class='small_standard_bold $color_class' width='300'><div style='width: 300px' class='wordwrap'>$row[url_response]</div></td></TR>";

				if (mb_strlen($row["url"])>mb_strlen($row["url_response"])) {
					$max_url_length=mb_strlen($row["url"]);
				} else {
					$max_url_length=mb_strlen($row["url_response"]);
				}
				$lines_to_print=ceil($max_url_length/80);
				for ($j=1; $j<=$lines_to_print; $j++) {
					if ($j==1) {
						$url_text=substr($row["url"], (80*($j-1)), 80);
						$url_response_text=substr($row["url_response"], (80*($j-1)), 80);

						$url_rpt.="| ".sprintf("%-21s", $row["uniqueid"]); 
						$url_rpt.="| ".sprintf("%-20s", $row["url_date"]); 
						$url_rpt.="| ".sprintf("%-13s", $row["url_type"]); 
						$url_rpt.="| ".sprintf("%-9s", $row["response_sec"]);

						$url_rpt.="| ";
						$url_rpt.=htmlspecialchars($url_text);
						$blanks=81-strlen($url_text);
						if ($blanks>0) {for ($k=1; $k<=$blanks; $k++) {$url_rpt.=" ";}}
					
						$url_rpt.="| ";
						$url_rpt.=htmlspecialchars($url_response_text);
						$blanks=81-strlen($url_response_text);
						if ($blanks>0) {for ($k=1; $k<=$blanks; $k++) {$url_rpt.=" ";}}			
						$url_rpt.="|\n";
					} else {
						$url_text=substr($row["url"], (80*($j-1)), 80);
						$url_response_text=substr($row["url_response"], (80*($j-1)), 80);

						$url_rpt.="| ".sprintf("%-21s", ""); 
						$url_rpt.="| ".sprintf("%-20s", ""); 
						$url_rpt.="| ".sprintf("%-13s", ""); 
						$url_rpt.="| ".sprintf("%-9s", ""); 

						$url_rpt.="| ";
						$url_rpt.=htmlspecialchars($url_text);
						$blanks=81-strlen($url_text);
						if ($blanks>0) {for ($k=1; $k<=$blanks; $k++) {$url_rpt.=" ";}}

						$url_rpt.="| ";
						$url_rpt.=htmlspecialchars($url_response_text);
						$blanks=81-strlen($url_response_text);
						if ($blanks>0) {for ($k=1; $k<=$blanks; $k++) {$url_rpt.=" ";}}
						$url_rpt.="|\n";
					}
				}
			}
		}
		$url_rpt.="+----------------------+---------------------+--------------+----------+----------------------------------------------------------------------------------+----------------------------------------------------------------------------------+\n";

		$url_rpt_hf="";
		$HTML_rpt_hf="<TR>";
		$ll=$lower_limit-1000;
		if ($ll<1 || ($lower_limit+1000)>=mysqli_num_rows($rpt_rslt)) {$HTML_colspan=6;} else {$HTML_colspan=3;}

		if ($ll>=1) {
			$url_rpt_hf.="<a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&report_display_type=$report_display_type&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$url_typeQS&lower_limit=$ll\">[<<< "._QXZ("PREV 1000 records")."]</a>";
			$HTML_rpt_hf.="<Td colspan='$HTML_colspan' class='small_standard_bold grey_graph_cell' align='left'><a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&report_display_type=$report_display_type&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$url_typeQS&lower_limit=$ll\">[<<< "._QXZ("PREV 1000 records")."]</a></TH>";

		} else {
			$url_rpt_hf.=sprintf("%-23s", " ");
		}
		$url_rpt_hf.=sprintf("%-145s", " ");
		if (($lower_limit+1000)<mysqli_num_rows($rpt_rslt)) {
			if ($upper_limit+1000>=mysqli_num_rows($rpt_rslt)) {$max_limit=mysqli_num_rows($rpt_rslt)-$upper_limit;} else {$max_limit=1000;}
			$url_rpt_hf.="<a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&report_display_type=$report_display_type&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$url_typeQS&lower_limit=".($lower_limit+1000)."\">["._QXZ("NEXT")." $max_limit "._QXZ("records")." >>>]</a>";
			$HTML_rpt_hf.="<Td colspan='$HTML_colspan' class='small_standard_bold grey_graph_cell' align='right'><a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&report_display_type=$report_display_type&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T$url_typeQS&lower_limit=".($lower_limit+1000)."\">["._QXZ("NEXT")." $max_limit "._QXZ("records")." >>>]</a></TH>";
		} else {
			$url_rpt_hf.=sprintf("%23s", " ");
		}
		$HTML_rpt_hf.="</TR>";
		$url_rpt_hf.="\n";
		$ASCII_text.=$url_rpt_hf.$url_rpt.$url_rpt_hf;
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
