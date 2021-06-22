<?php 
# AST_vdad_debug_log_report.php
# 
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 161124-0737 - First build
# 161201-0815 - Added more statistics and options
# 170409-1550 - Added IP List validation code
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$report_name='VDAD Debug Log Report';

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
if (isset($_GET["stage"]))					{$stage=$_GET["stage"];}
	elseif (isset($_POST["stage"]))			{$stage=$_POST["stage"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}
if (isset($_GET["lower_limit"]))			{$lower_limit=$_GET["lower_limit"];}
	elseif (isset($_POST["lower_limit"]))	{$lower_limit=$_POST["lower_limit"];}
if (isset($_GET["upper_limit"]))			{$upper_limit=$_GET["upper_limit"];}
	elseif (isset($_POST["upper_limit"]))	{$upper_limit=$_POST["upper_limit"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["DBX"]))					{$DBX=$_GET["DBX"];}
	elseif (isset($_POST["DBX"]))			{$DBX=$_POST["DBX"];}
if (isset($_GET["archive"]))				{$archive=$_GET["archive"];}
	elseif (isset($_POST["archive"]))		{$archive=$_POST["archive"];}
if (isset($_GET["lrerr_statuses"]))				{$lrerr_statuses=$_GET["lrerr_statuses"];}
	elseif (isset($_POST["lrerr_statuses"]))	{$lrerr_statuses=$_POST["lrerr_statuses"];}
if (isset($_GET["uncounted_statuses"]))				{$uncounted_statuses=$_GET["uncounted_statuses"];}
	elseif (isset($_POST["uncounted_statuses"]))	{$uncounted_statuses=$_POST["uncounted_statuses"];}
if (isset($_GET["submit"]))					{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))		{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}


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

$NOW_DATE = date("Y-m-d");

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$stage = preg_replace('/[^-_0-9a-zA-Z]/', '', $stage);
	}
else
	{
	$PHP_AUTH_PW = preg_replace("/'|\"|\\\\|;/","",$PHP_AUTH_PW);
	$PHP_AUTH_USER = preg_replace("/'|\"|\\\\|;/","",$PHP_AUTH_USER);
	$stage = preg_replace("/'|\"|\\\\|;/","",$stage);
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
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (strlen($stage) < 2) {$stage = 'SUMMARY';}
if (strlen($lrerr_statuses) < 1) {$lrerr_statuses = 0;}
if (strlen($uncounted_statuses) < 1) {$uncounted_statuses = 0;}
$vicidial_vdad_log = 'vicidial_vdad_log';
if ($archive > 0) {$vicidial_vdad_log = 'vicidial_vdad_log_archive';}
$vicidial_log = 'vicidial_log';

/*
$server_ip_string='|';
$server_ip_ct = count($server_ip);
$i=0;
while($i < $server_ip_ct)
	{
	$server_ip_string .= "$server_ip[$i]|";
	$i++;
	}

$server_stmt="select server_ip,server_description from servers where active_asterisk_server='Y' order by server_ip asc;";
$server_rslt=mysql_to_mysqli($server_stmt, $link);
$servers_to_print=mysqli_num_rows($server_rslt);
$i=0;
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
	$server_rpt_string="- "._QXZ("ALL servers")." ";
	if (preg_match('/\-\-ALL\-\-/',$server_ip_string)) {$server_ipQS="&server_ip[]=--ALL--";}
	}
else
	{
	$server_ip_SQL = preg_replace('/,$/i', '',$server_ip_SQL);
	$server_ip_SQL = "and server_ip IN($server_ip_SQL)";
	$server_rpt_string="- server(s) ".preg_replace('/\|/', ", ", substr($server_ip_string, 1, -1));
	}
if (strlen($server_ip_SQL)<3) {$server_ip_SQL="";}
*/

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
$MAIN.=_QXZ("Report Type").":\n";
$MAIN.="<SELECT NAME='stage'><option value='SUMMARY'>"._QXZ("SUMMARY")."</option><option value='CALL'>"._QXZ("CALL")>"</option><option value='DETAIL'>"._QXZ("DETAIL")."</option><option SELECTED value='$stage'>"._QXZ("$stage")."</option></SELECT> &nbsp; &nbsp; &nbsp; ";
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

$MAIN.=" &nbsp; &nbsp; &nbsp; &nbsp; <INPUT TYPE=TEXT NAME=query_date_D SIZE=9 MAXLENGTH=8 VALUE=\"$query_date_D\">";

$MAIN.=" "._QXZ("to")." <INPUT TYPE=TEXT NAME=query_date_T SIZE=9 MAXLENGTH=8 VALUE=\"$query_date_T\"> &nbsp; ";

/*
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
$MAIN.="</SELECT></TD><TD ROWSPAN=2 VALIGN=middle align=center>\n";
*/

$MAIN.="<INPUT TYPE=submit NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'><BR/>\n";
$MAIN.="</TD></TR></TABLE>\n";


if ($SUBMIT) {
	/*
	$stmt="select hangup_cause, dialstatus, count(*) as ct From vicidial_carrier_log where call_date>='$query_date $query_date_D' and call_date<='$query_date $query_date_T' $server_ip_SQL group by hangup_cause, dialstatus order by hangup_cause, dialstatus";
	$rslt=mysql_to_mysqli($stmt, $link);
	$MAIN.="<PRE><font size=2>\n";
	if ($DB) {$MAIN.=$stmt."\n";}
	if (mysqli_num_rows($rslt)>0) {
		$MAIN.="--- "._QXZ("DIAL STATUS BREAKDOWN FOR")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T $server_rpt_string\n";
		$MAIN.="+--------------+-------------+---------+\n";
		$MAIN.="| "._QXZ("HANGUP CAUSE",12)." | "._QXZ("DIAL STATUS",11)." | "._QXZ("COUNT",7)." |\n";
		$MAIN.="+--------------+-------------+---------+\n";
		$total_count=0;
		while ($row=mysqli_fetch_array($rslt)) {
			$MAIN.="| ".sprintf("%-13s", $row["hangup_cause"]);
			$MAIN.="| ".sprintf("%-12s", $row["dialstatus"]);
			$MAIN.="| ".sprintf("%-8s", $row["ct"]);
			$MAIN.="|\n";
			$total_count+=$row["ct"];
		}
		$MAIN.="+--------------+-------------+---------+\n";
		$MAIN.="| "._QXZ("TOTAL",26,"r")." | ".sprintf("%-8s", $total_count)."|\n";
		$MAIN.="+--------------+-------------+---------+\n\n";

		$stmt="select sip_hangup_cause,sip_hangup_reason,count(*) as ct From vicidial_carrier_log where call_date>='$query_date $query_date_D' and call_date<='$query_date $query_date_T' $server_ip_SQL group by sip_hangup_cause,sip_hangup_reason order by sip_hangup_cause,sip_hangup_reason";
		$rslt=mysql_to_mysqli($stmt, $link);
		$MAIN.="<PRE><font size=2>\n";
		if ($DB) {$MAIN.=$stmt."\n";}
		if (mysqli_num_rows($rslt)>0) {
			$MAIN.="--- "._QXZ("SIP ERROR REASON BREAKDOWN FOR")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T $server_rpt_string\n";
			$MAIN.="+----------+--------------------------------+---------+\n";
			$MAIN.="| "._QXZ("SIP CODE",8)." | "._QXZ("SIP HANGUP REASON",30)." | "._QXZ("COUNT",7)." |\n";
			$MAIN.="+----------+--------------------------------+---------+\n";
			$total_count=0;
			while ($row=mysqli_fetch_array($rslt)) {
				$MAIN.="| ".sprintf("%8s", $row["sip_hangup_cause"])." ";
				$MAIN.="| ".sprintf("%-31s", $row["sip_hangup_reason"]);
				$MAIN.="| ".sprintf("%-8s", $row["ct"]);
				$MAIN.="|\n";
				$total_count+=$row["ct"];
			}
			$MAIN.="+----------+--------------------------------+---------+\n";
			$MAIN.="| "._QXZ("TOTAL",41,"r")." | ".sprintf("%-8s", $total_count)."|\n";
			$MAIN.="+-------------------------------------------+---------+\n\n\n";
		}

		CREATE TABLE vicidial_vdad_log (
		caller_code VARCHAR(30) NOT NULL,
		server_ip VARCHAR(15),
		call_date DATETIME,
		epoch_micro VARCHAR(20) default '',
		db_time DATETIME NOT NULL,
		run_time VARCHAR(20) default '0',
		vdad_script VARCHAR(40) NOT NULL,
		lead_id INT(10) UNSIGNED default '0',
		stage VARCHAR(100) default '',
		step SMALLINT(5) UNSIGNED default '0',
		index (caller_code),
		KEY vdad_dbtime_key (db_time)
		) ENGINE=MyISAM CHARSET=utf8 COLLATE=utf8_unicode_ci;

	*/

	##### BEGIN SUMMARY SECTION #####
	if ($stage == 'SUMMARY')
		{
		$uncounted=0;
		$server_ct=0;
		$server_array[0]='';
		$server_calls[0]=0;
		$server_LRERR[0]=0;
		$server_LRERR_total[0]=0;
		$server_preroute[0]=0;
		$server_preroute_time[0]=0;
		$server_LRcount[0]=0;
		$server_LRcount_total[0]=0;
		$server_LRpreroute[0]=0;
		$server_LRpreroute_time[0]=0;
		$server_nLRpreroute[0]=0;
		$server_nLRpreroute_time[0]=0;
		$status_ct=0;
		$status_array[0]='';
		$status_calls[0]=0;
		$ucstatus_ct=0;
		$ucstatus_array[0]='';
		$ucstatus_calls[0]=0;
		$MAIN.="<PRE><font size=2>\n";

		$stmt = "SELECT caller_code,server_ip,count(*) FROM $vicidial_vdad_log where db_time >= '$query_date $query_date_D' and db_time <= '$query_date $query_date_T' group by caller_code,server_ip order by caller_code limit 100000;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$cc_ct = mysqli_num_rows($rslt);
		$i=0;
		while ($cc_ct > $i)
			{
			$row=mysqli_fetch_row($rslt);
			$Acaller_code[$i] =		$row[0];
			$Aserver_ip[$i] =		$row[1];
			$Acc_count[$i] =		$row[2];
			if ($i==0) 
				{
				$server_ct++;
				$server_array[0]=$Aserver_ip[$i];
				$server_calls[0]++;
				}
			else
				{
				$k=0;
				$server_found=0;
				while ($k < $server_ct)
					{
					if ($Aserver_ip[$i] == $server_array[$k])
						{
						$server_calls[$k]++;
						$server_found=1;
						}
					$k++;
					}
				if ($server_found < 1)
					{
					$server_array[$server_ct]=$Aserver_ip[$i];
					$server_calls[$server_ct]++;
					$server_ct++;
					}
				}
			$i++;
			}
		if ($cc_ct < 1) 
			{
			$MAIN.="*** "._QXZ("NO RECORDS FOUND")." ***\n";
			}
		else
			{
			$MAIN.="$i "._QXZ("CALLS FOUND")."\n";
			$MAIN.="$server_ct "._QXZ("SERVERS")."\n";
			$MAIN.="\n";
			}

		$i=0;
		$m=0;
		$LRERR=0;
		$LRERR_total=0;
		$preroute=0;
		$preroute_total=0;
		$LRcount=0;
		$LRcount_total=0;
		$LRpreroute=0;
		$LRpreroute_total=0;
		$nLRpreroute=0;
		$nLRpreroute_total=0;
		while ($cc_ct > $i)
			{
			if ($DBX > 0)
				{$MAIN.= "$i  $Acaller_code[$i]  $Aserver_ip[$i]  $Acc_count[$i]\n";}

			$start_epoch=0;
			$last_epoch=0;
			$last_runtime=0;
			$preroute_time=0;
			$found_preroute=0;
			$this_call_LR=0;
			$call_counted=0;

			$stmt = "SELECT stage,step,epoch_micro,run_time FROM $vicidial_vdad_log where caller_code='$Acaller_code[$i]' and db_time >= '$query_date $query_date_D' and db_time <= '$query_date $query_date_T' order by epoch_micro;";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB) {$MAIN.="$stmt\n";}
			$cd_ct = mysqli_num_rows($rslt);
			$j=0;
			while ($cd_ct > $j)
				{
				$row=mysqli_fetch_row($rslt);
				$Astage[$j] =		$row[0];
				$Astep[$j] =		$row[1];
				$Aepoch_micro[$j] =	$row[2];
				$Arun_time[$j] =	$row[3];
				$j++;
				}
			$j=0;
			while ($cd_ct > $j)
				{
				if ($j < 1) {$start_epoch=$Aepoch_micro[$j];}
				$last_epoch=$Aepoch_micro[$j];
				$last_runtime=$Arun_time[$j];
				if ($Astage[$j] == 'LocalEXIT-5') 
					{
					$this_call_LR++;
					}
				if ($Astage[$j] == 'LocalEXIT-8') 
					{
					$call_counted++;
					$LRERR++;
					$LRERR_time = ( ($last_epoch - $start_epoch) + $last_runtime);
					$LRERR_total = ($LRERR_total + $LRERR_time);
					$k=0;
					while ($k < $server_ct)
						{
						if ($Aserver_ip[$i] == $server_array[$k])
							{
							$server_LRERR[$k]++;
							$server_LRERR_time[$k] = ($server_LRERR_time[$k] + $LRERR_time);
							}
						$k++;
						}
					
					if ($lrerr_statuses > 0)
						{
						$start_epoch = $Aepoch_micro[$j];
						$start_epochA = explode('.',$start_epoch);
						$start_epoch = ($start_epochA[0] - 240);
						$start_date = date("Y-m-d H:i:s",$start_epoch);
						$CIDlead_id = $Acaller_code[$i];
						$CIDlead_id = substr($CIDlead_id, 10, 10);
						$CIDlead_id = ($CIDlead_id + 0);
						$stmt = "SELECT status,uniqueid FROM $vicidial_log where lead_id='$CIDlead_id' and call_date > \"$start_date\" order by call_date limit 1;";
						$rslt=mysql_to_mysqli($stmt, $link);
						if ($DB) {$MAIN.="$stmt\n";}
						$status_row_ct = mysqli_num_rows($rslt);
						if ($status_row_ct > 0)
							{
							$row=mysqli_fetch_row($rslt);

							if ($m==0) 
								{
								$status_ct++;
								$status_array[0]=$row[0];
								$status_calls[0]++;
								}
							else
								{
								$k=0;
								$status_found=0;
								while ($k < $status_ct)
									{
									if ($row[0] == $status_array[$k])
										{
										$status_calls[$k]++;
										$status_found=1;
										}
									$k++;
									}
								if ($status_found < 1)
									{
									$status_array[$status_ct]=$row[0];
									$status_calls[$status_ct]++;
									$status_ct++;
									}
								}
							}
						}
					$m++;
					}
				if ($Astage[$j] == 'preroute')
					{
					$call_counted++;
					$found_preroute=1;
					$preroute++;
					$preroute_time = ( ($last_epoch - $start_epoch) + $last_runtime);
					$preroute_total = ($preroute_total + $preroute_time);
					$k=0;
					while ($k < $server_ct)
						{
						if ($Aserver_ip[$i] == $server_array[$k])
							{
							$server_preroute[$k]++;
							$server_preroute_time[$k] = ($server_preroute_time[$k] + $preroute_time);
							}
						$k++;
						}
					if ($this_call_LR > 0)
						{
						$LRcount++;
						$LRcount_total = ($LRcount_total + $this_call_LR);

						$LRpreroute++;
						$LRpreroute_time = ( ($last_epoch - $start_epoch) + $last_runtime);
						$LRpreroute_total = ($LRpreroute_total + $LRpreroute_time);

						$k=0;
						while ($k < $server_ct)
							{
							if ($Aserver_ip[$i] == $server_array[$k])
								{
								$server_LRpreroute[$k]++;
								$server_LRpreroute_time[$k] = ($server_LRpreroute_time[$k] + $LRpreroute_time);
								}
							$k++;
							}
						}
					else
						{
						$nLRpreroute++;
						$nLRpreroute_time = ( ($last_epoch - $start_epoch) + $last_runtime);
						$nLRpreroute_total = ($nLRpreroute_total + $nLRpreroute_time);

						$k=0;
						while ($k < $server_ct)
							{
							if ($Aserver_ip[$i] == $server_array[$k])
								{
								$server_nLRpreroute[$k]++;
								$server_nLRpreroute_time[$k] = ($server_nLRpreroute_time[$k] + $nLRpreroute_time);
								}
							$k++;
							}
						}
					}
				if ($DBX > 0)
					{$MAIN.= "          $j  $Aepoch_micro[$j]  $Arun_time[$j]  $Astep[$j]  $Astage[$j]\n";}
				$j++;
				}
			if ($call_counted < 1)
				{
				$uncounted++;
				if ($uncounted_statuses > 0)
					{
					$start_epoch = $Aepoch_micro[$j];
					$start_epochA = explode('.',$start_epoch);
					$start_epoch = ($start_epochA[0] - 240);
					$start_date = date("Y-m-d H:i:s",$start_epoch);
					$CIDlead_id = $Acaller_code[$i];
					$CIDlead_id = substr($CIDlead_id, 10, 10);
					$CIDlead_id = ($CIDlead_id + 0);
					$stmt = "SELECT status,uniqueid FROM $vicidial_log where lead_id='$CIDlead_id' and call_date > \"$start_date\" order by call_date limit 1;";
					$rslt=mysql_to_mysqli($stmt, $link);
					if ($DB) {$MAIN.="$stmt\n";}
					$ucstatus_row_ct = mysqli_num_rows($rslt);
					if ($ucstatus_row_ct > 0)
						{
						$row=mysqli_fetch_row($rslt);

						if ($m==0) 
							{
							$ucstatus_ct++;
							$ucstatus_array[0]=$row[0];
							$ucstatus_calls[0]++;
							}
						else
							{
							$k=0;
							$ucstatus_found=0;
							while ($k < $ucstatus_ct)
								{
								if ($row[0] == $ucstatus_array[$k])
									{
									$ucstatus_calls[$k]++;
									$ucstatus_found=1;
									}
								$k++;
								}
							if ($ucstatus_found < 1)
								{
								$ucstatus_array[$ucstatus_ct]=$row[0];
								$ucstatus_calls[$ucstatus_ct]++;
								$ucstatus_ct++;
								}
							}
						}
					}
				}
			$processing_time = ($last_epoch - $start_epoch);
			$routing_time = ($processing_time + $last_runtime);
			if ($DBX > 0)
				{$MAIN.= "     PROCESSING TIME: $processing_time   ROUTING TIME: $routing_time   PRE-ROUTE TIME: $preroute_time\n";}

			$i++;
			}

		$LRERR_pct=( ($LRERR / $i) * 100);
		$LRERR_pct = sprintf("%01.2f", $LRERR_pct);
		$LRERR_avg = ($LRERR_total / $LRERR);
		$LRERR_avg = sprintf("%01.6f", $LRERR_avg);

		$preroute_avg = ($preroute_total / $preroute);
		$preroute_avg = sprintf("%01.6f", $preroute_avg);
		$LRpreroute_avg = ($LRpreroute_total / $LRpreroute);
		$LRpreroute_avg = sprintf("%01.6f", $LRpreroute_avg);
		$nLRpreroute_avg = ($nLRpreroute_total / $nLRpreroute);
		$nLRpreroute_avg = sprintf("%01.6f", $nLRpreroute_avg);

		$MAIN.= "\nFINISHED - \n";
		$MAIN.= "TOTAL ANSWERED CALLS:  $i\n";
		$MAIN.= "LRERR:                 $LRERR  ($LRERR_pct%)   average $LRERR_avg sec\n";
		$MAIN.= "preroute:              $preroute  (average $preroute_avg sec)\n";
		$MAIN.= "LRpreroute:            $LRpreroute  (average $LRpreroute_avg sec)\n";
		$MAIN.= "nLRpreroute:           $nLRpreroute  (average $nLRpreroute_avg sec)\n";
		$MAIN.= "uncounted:             $uncounted\n";

		if ($uncounted_statuses > 0)
			{
			$MAIN.= "UNCOUNTED STATUS SUMMARY - \n";
			$k=0;
			while ($k < $ucstatus_ct)
				{
				$ucstatus_array[$k] = sprintf("%6s", $ucstatus_array[$k]);
				$ucstatus_calls[$k] = sprintf("%6s", $ucstatus_calls[$k]);
				$MAIN.= "     $ucstatus_array[$k]:    $ucstatus_calls[$k]\n";
				$k++;
				}
			}
		if ($lrerr_statuses > 0)
			{
			$MAIN.= "LRERR STATUS SUMMARY - \n";
			$k=0;
			while ($k < $status_ct)
				{
				$status_array[$k] = sprintf("%6s", $status_array[$k]);
				$status_calls[$k] = sprintf("%6s", $status_calls[$k]);
				$MAIN.= "     $status_array[$k]:    $status_calls[$k]\n";
				$k++;
				}
			}
		$MAIN.= "SERVER SUMMARY - \n";

		$k=0;
		while ($k < $server_ct)
			{
			$server_description='';
			$asterisk_version='';
			$stmt = "SELECT asterisk_version,server_id,server_description FROM servers where server_ip='$server_array[$k]';";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB) {$MAIN.="$stmt\n";}
			$srv_ct = mysqli_num_rows($rslt);
			if ($srv_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$asterisk_version =			"asterisk $row[0]";
				$server_description =		"$row[1] - $row[2]";
				}

			$LRERR_pct=( ($server_LRERR[$k] / $server_calls[$k]) * 100);
			$LRERR_pct = sprintf("%01.2f", $LRERR_pct);
			$LRERR_avg = ($server_LRERR_time[$k] / $server_LRERR[$k]);
			$LRERR_avg = sprintf("%01.6f", $LRERR_avg);

			$preroute_avg = ($server_preroute_time[$k] / $server_preroute[$k]);
			$preroute_avg = sprintf("%01.6f", $preroute_avg);
			$LRpreroute_avg = ($server_LRpreroute_time[$k] / $server_LRpreroute[$k]);
			$LRpreroute_avg = sprintf("%01.6f", $LRpreroute_avg);
			$nLRpreroute_avg = ($server_nLRpreroute_time[$k] / $server_nLRpreroute[$k]);
			$nLRpreroute_avg = sprintf("%01.6f", $nLRpreroute_avg);

			$MAIN.= "     $server_array[$k] - $server_description - $asterisk_version\n";
			$MAIN.= "          TOTAL:        $server_calls[$k]\n";
			$MAIN.= "          LRERR:        $server_LRERR[$k]  ($LRERR_pct%)    average $LRERR_avg sec\n";
			$MAIN.= "          preroute:     $server_preroute[$k]  (average $preroute_avg sec)\n";
			$MAIN.= "          LRpreroute:   $server_LRpreroute[$k]  (average $LRpreroute_avg sec)\n";
			$MAIN.= "          nLRpreroute:  $server_nLRpreroute[$k]  (average $nLRpreroute_avg sec)\n";
			$k++;
			}

		$MAIN.="</font></PRE>\n";

		$MAIN.="</form></BODY></HTML>\n";
		}
	##### END SUMMARY SECTION #####


	##### BEGIN CALL SECTION #####
	if ($stage == 'CALL')
		{
		$MAIN.="<PRE><font size=2>\n";
		$stmt = "SELECT caller_code,server_ip,count(*) FROM $vicidial_vdad_log where db_time >= '$query_date $query_date_D' and db_time <= '$query_date $query_date_T' group by caller_code,server_ip order by caller_code;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {$MAIN.="$stmt\n";}
		$cc_ct = mysqli_num_rows($rslt);
		$i=0;
		while ($cc_ct > $i)
			{
			$row=mysqli_fetch_row($rslt);
			$Acaller_code[$i] =		$row[0];
			$Aserver_ip[$i] =		$row[1];
			$Acc_count[$i] =		$row[2];
			$i++;
			}
		if ($cc_ct < 1) 
			{
			$MAIN.="*** "._QXZ("NO RECORDS FOUND")." ***\n";
			}

		$i=0;
		while ($cc_ct > $i)
			{
			if ($DB > 0)
				{$MAIN.= "$i  $Acaller_code[$i]  $Aserver_ip[$i]  $Acc_count[$i]\n";}
			$i++;
			}

		$MAIN.="</font></PRE>\n";

		$MAIN.="</form></BODY></HTML>\n";
		}
	##### END CALL SECTION #####


	##### BEGIN DETAIL SECTION #####
	if ($stage == 'DETAIL')
		{
		$MAIN.="<PRE><font size=2>\n";
		$stageSQL='';
		if (strlen($stage) > 1)
			{$stageSQL = "and user='$stage'";}
		$rpt_stmt="select caller_code,call_date,db_time,run_time,vdad_script,stage,lead_id,step,epoch_micro from $vicidial_vdad_log where db_time >= '$query_date $query_date_D' and db_time <= '$query_date $query_date_T' order by db_time desc, epoch_micro desc;";
		$rpt_rslt=mysql_to_mysqli($rpt_stmt, $link);
		if ($DB) {$MAIN.=$rpt_stmt."\n";}
		if (mysqli_num_rows($rpt_rslt)>0) 
			{
			if (!$lower_limit) {$lower_limit=1;}
			if ($lower_limit+999>=mysqli_num_rows($rpt_rslt)) {$upper_limit=($lower_limit+mysqli_num_rows($rpt_rslt)%1000)-1;} else {$upper_limit=$lower_limit+999;}
			
			$MAIN.="--- "._QXZ("VDAD DEBUG LOG DETAIL RECORDS FOR")." $query_date, $query_date_D "._QXZ("TO")." $query_date_T $server_rpt_string, "._QXZ("RECORDS")." #$lower_limit-$upper_limit               <a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T&stage=$stage&lower_limit=$lower_limit&upper_limit=$upper_limit&file_download=1\">["._QXZ("DOWNLOAD")."]</a>\n";
			$agntdb_rpt.="+----------------------+---------------------+---------------------+-------------------+------------+----------------------+--------------------------------+------------+-------+\n";
			$agntdb_rpt.="| "._QXZ("CALL",20)." | "._QXZ("CALL DATE",19)." | "._QXZ("DB DATE",19)." | "._QXZ("EPOCH MICRO",17)." | "._QXZ("RUN TIME",10)." | "._QXZ("SCRIPT",20)." | "._QXZ("STAGE",30)." | "._QXZ("LEAD_ID",10)." | "._QXZ("STEP",5)." |\n";
			$agntdb_rpt.="+----------------------+---------------------+---------------------+-------------------+------------+----------------------+--------------------------------+------------+-------+\n";
			$CSV_text="\""._QXZ("CALL")."\",\""._QXZ("CALL DATE")."\",\""._QXZ("DB DATE")."\",\""._QXZ("EPOCH MICRO")."\",\""._QXZ("RUN TIME")."\",\""._QXZ("SCRIPT")."\",\""._QXZ("STAGE")."\",\""._QXZ("LEAD_ID")."\",\""._QXZ("STEP")."\"\n";

			for ($i=1; $i<=mysqli_num_rows($rpt_rslt); $i++) 
				{
				$row=mysqli_fetch_array($rpt_rslt);

				$CSV_text.="\"$row[caller_code]\",\"$row[call_date]\",\"$row[db_time]\",\"$row[epoch_micro]\",\"$row[run_time]\",\"$row[vdad_script]\",\"$row[stage]\",\"$row[lead_id]\",\"$row[step]\"\n";
				if ($i>=$lower_limit && $i<=$upper_limit) 
					{
					$agntdb_rpt.="| ".sprintf("%-20s", $row["caller_code"]); 
					$agntdb_rpt.=" | ".sprintf("%-19s", $row["call_date"]); 
					$agntdb_rpt.=" | ".sprintf("%-19s", $row["db_time"]); 
					$agntdb_rpt.=" | ".sprintf("%-17s", $row["epoch_micro"]); 
					if (strlen($row["run_time"])>10) {$row["run_time"]=substr($row["run_time"],0,10)."";}
					$run_color='color=black';
					if ($row["run_time"] > 1) {$run_color='color=blue';} 
					if ($row["run_time"] > 2) {$run_color='color=purple';} 
					if ($row["run_time"] > 3) {$run_color='color=red';} 
					$agntdb_rpt.=" | <font size=2 $run_color>".sprintf("%-10s", $row["run_time"])."</font>"; 
					if (strlen($row["vdad_script"])>20) {$row["vdad_script"]=substr($row["vdad_script"],-20)."";}
					$agntdb_rpt.=" | ".sprintf("%-20s", $row["vdad_script"]); 
					if (strlen($row["stage"])>27) {$row["stage"]=substr($row["stage"],0,27)."...";}
					$agntdb_rpt.=" | ".sprintf("%-30s", $row["stage"]); 
					$agntdb_rpt.=" | ".sprintf("%-10s", $row["lead_id"]); 
					if (strlen($row["step"])>4) {$row["step"]=substr($row["step"],0,4)."...";}
					$agntdb_rpt.=" | ".sprintf("%-5s", $row["step"])." |\n"; 
					}
				}
			$agntdb_rpt.="+----------------------+---------------------+---------------------+-------------------+------------+----------------------+--------------------------------+------------+-------+\n";

			$agntdb_rpt_hf="";
			$ll=$lower_limit-1000;
			if ($ll>=1) 
				{
				$agntdb_rpt_hf.="<a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T&stage=$stage&lower_limit=$ll\">[<<< "._QXZ("PREV")." 1000 "._QXZ("records")."]</a>";
				} 
			else 
				{
				$agntdb_rpt_hf.=sprintf("%-23s", " ");
				}
			$agntdb_rpt_hf.=sprintf("%-145s", " ");
			if (($lower_limit+1000)<mysqli_num_rows($rpt_rslt)) 
				{
				if ($upper_limit+1000>=mysqli_num_rows($rpt_rslt)) {$max_limit=mysqli_num_rows($rpt_rslt)-$upper_limit;} else {$max_limit=1000;}
				$agntdb_rpt_hf.="<a href=\"$PHP_SELF?SUBMIT=$SUBMIT&DB=$DB&type=$type&query_date=$query_date&query_date_D=$query_date_D&query_date_T=$query_date_T&stage=$stage&lower_limit=".($lower_limit+1000)."\">["._QXZ("NEXT")." $max_limit "._QXZ("records")." >>>]</a>";
				} 
			else 
				{
				$agntdb_rpt_hf.=sprintf("%23s", " ");
				}
			$agntdb_rpt_hf.="\n";
			$MAIN.=$agntdb_rpt_hf.$agntdb_rpt.$agntdb_rpt_hf;
			}
		else 
			{
			$MAIN.="*** "._QXZ("NO RECORDS FOUND")." ***\n";
			}
		$MAIN.="</font></PRE>\n";

		$MAIN.="</form></BODY></HTML>\n";
		}
	##### END DETAIL SECTION #####

	}
	
if ($file_download>0) 
	{
	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "AST_vdad_debug_log_report_$US$FILE_TIME.csv";
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
