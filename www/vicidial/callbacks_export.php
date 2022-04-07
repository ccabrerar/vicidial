<?php
# callbacks_export.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 200622-1615 - First build 
# 220228-2126 - Added allow_web_debug system setting
#

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["file_download"]))				{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))		{$file_download=$_POST["file_download"];}
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["end_date"]))			{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))	{$end_date=$_POST["end_date"];}
if (isset($_GET["cb_groups"]))			{$cb_groups=$_GET["cb_groups"];}
	elseif (isset($_POST["cb_groups"]))	{$cb_groups=$_POST["cb_groups"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$StarTtimE = date("U");
$TODAY = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
if (!isset($cb_groups)) {$cb_groups=array();}
if (!isset($query_date)) {$query_date = $TODAY;}
if (!isset($end_date)) {$end_date = $TODAY;}
$ip = getenv("REMOTE_ADDR");
$date = date("r");
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");
$report_name="CALLBACKS EXPORT";

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable,outbound_autodial_active,enable_languages,language_method,qc_features_active,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$webroot_writable =				$row[1];
	$SSoutbound_autodial_active =	$row[2];
	$SSenable_languages =			$row[3];
	$SSlanguage_method =			$row[4];
	$SSqc_features_active =			$row[5];
	$SSallow_web_debug =			$row[6];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$query_date = preg_replace('/[^- \:\_0-9a-zA-Z]/', '', $query_date);
$end_date = preg_replace('/[^- \:\_0-9a-zA-Z]/', '', $end_date);
$submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $submit);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/', '', $SUBMIT);
$search_archived_data = preg_replace('/[^-_0-9a-zA-Z]/', '', $search_archived_data);
$file_download = preg_replace('/[^-_0-9a-zA-Z]/', '', $file_download);
$report_display_type = preg_replace('/[^-_0-9a-zA-Z]/', '', $report_display_type);

# Variables filtered further down in the code
# $cb_groups

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

$stmt="SELECT selected_language,qc_enabled from vicidial_users where user='$PHP_AUTH_USER';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$sl_ct = mysqli_num_rows($rslt);
if ($sl_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$VUselected_language =		$row[0];
	$qc_auth =					$row[1];
	}

$category='';
$record_id='';

$auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'',1,0);
if ($auth_message == 'GOOD')
	{$auth=1;}

if ($auth < 1)
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

$stmt="SELECT full_name,change_agent_campaign,modify_timeclock_log,user_group,user_level,modify_leads from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGfullname =				$row[0];
$change_agent_campaign =	$row[1];
$modify_timeclock_log =		$row[2];
$LOGuser_group =			$row[3];
$user_level =				$row[4];
$LOGmodify_leads =			$row[5];
if ($user_level==9) 
	{
	$ul_clause="where user_level<=9";
	}
else 
	{
	$ul_clause="where user_level<$user_level";
	}

if ($LOGmodify_leads < 1)
	{
	Header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions to modify leads").": |$PHP_AUTH_USER|\n";
	exit;
	}

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {echo "|$stmt|\n";}
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

$LOGadmin_viewable_groupsSQL='';
$whereLOGadmin_viewable_groupsSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i',$LOGadmin_viewable_groups)) and (strlen($LOGadmin_viewable_groups) > 3) )
	{
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ -/",'',$LOGadmin_viewable_groups);
	$rawLOGadmin_viewable_groupsSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_groupsSQL);
	$LOGadmin_viewable_groupsSQL = "and user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	$whereLOGadmin_viewable_groupsSQL = "where user_group IN('---ALL---','$rawLOGadmin_viewable_groupsSQL')";
	}

$LOGadmin_viewable_call_timesSQL='';
$whereLOGadmin_viewable_call_timesSQL='';
if ( (!preg_match('/\-\-ALL\-\-/i', $LOGadmin_viewable_call_times)) and (strlen($LOGadmin_viewable_call_times) > 3) )
	{
	$rawLOGadmin_viewable_call_timesSQL = preg_replace("/ -/",'',$LOGadmin_viewable_call_times);
	$rawLOGadmin_viewable_call_timesSQL = preg_replace("/ /","','",$rawLOGadmin_viewable_call_timesSQL);
	$LOGadmin_viewable_call_timesSQL = "and call_time_id IN('---ALL---','$rawLOGadmin_viewable_call_timesSQL')";
	$whereLOGadmin_viewable_call_timesSQL = "where call_time_id IN('---ALL---','$rawLOGadmin_viewable_call_timesSQL')";
	}

$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";


###############

if ($SUBMIT) 
	{
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


	$SSmenu_background='015B91';
	$SSframe_background='D9E6FE';
	$SSstd_row1_background='9BB9FB';
	$SSstd_row2_background='B9CBFD';
	$SSstd_row3_background='8EBCFD';
	$SSstd_row4_background='B6D3FC';
	$SSstd_row5_background='A3C3D6';
	$SSalt_row1_background='BDFFBD';
	$SSalt_row2_background='99FF99';
	$SSalt_row3_background='CCFFCC';

	if ($SSadmin_screen_colors != 'default')
		{
		$stmt = "SELECT menu_background,frame_background,std_row1_background,std_row2_background,std_row3_background,std_row4_background,std_row5_background,alt_row1_background,alt_row2_background,alt_row3_background,web_logo FROM vicidial_screen_colors where colors_id='$SSadmin_screen_colors';";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$colors_ct = mysqli_num_rows($rslt);
		if ($colors_ct > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$SSmenu_background =		$row[0];
			$SSframe_background =		$row[1];
			$SSstd_row1_background =	$row[2];
			$SSstd_row2_background =	$row[3];
			$SSstd_row3_background =	$row[4];
			$SSstd_row4_background =	$row[5];
			$SSstd_row5_background =	$row[6];
			$SSalt_row1_background =	$row[7];
			$SSalt_row2_background =	$row[8];
			$SSalt_row3_background =	$row[9];
			$SSweb_logo =				$row[10];
			}
		}

	if (count($cb_groups)>0 && $query_date)
		{
		for ($i=0; $i<count($cb_groups); $i++) 
			{
			$cb_groups[$i] = preg_replace('/[^-_0-9\p{L}]/u', '', $cb_groups[$i]);
			}

		if (in_array("ALL", $cb_groups)) 
			{
			$groups_stmt="SELECT distinct campaign_id from vicidial_callbacks $whereLOGallowed_campaignsSQL";
			$groups_rslt=mysql_to_mysqli($groups_stmt, $link);
			$groupsSQL=" and vc.campaign_id in (";
			$groupsQS="";
			while($groups_row=mysqli_fetch_row($groups_rslt)) 
				{
				$groupsSQL.="'$groups_row[0]',";
				$groupsQS.="&cb_groups[]=".$groups_row[0];
				}
			$groupsSQL=preg_replace('/,$/', '', $groupsSQL);
			$groupsSQL.=") ";
			}
		else
			{
			$groupsSQL=" and vc.campaign_id in ('".implode("','", $cb_groups)."') ";
			$groupsQS="";
			for ($i=0; $i<count($cb_groups); $i++) 
				{
				$groupsQS.="&cb_groups[]=".$cb_groups[$i];
				}
			}

		if (!$end_date) {$end_date=$query_date;}
		$daySQL=" and vc.callback_time>='$query_date 00:00:00' and vc.callback_time<='$end_date 23:59:59' ";

		$callback_stmt="select vc.lead_id, phone_number, vc.status, entry_time, callback_time, vc.comments from vicidial_callbacks vc, vicidial_list v where v.lead_id=vc.lead_id $groupsSQL $daySQL order by callback_time asc, entry_date asc";
		$callback_rslt=mysql_to_mysqli($callback_stmt, $link);

		if (mysqli_num_rows($callback_rslt)>0) 
			{
			$rpt_output="<table width='100%' cellspacing='0' cellpadding='3'>";
			$rpt_output.="<tr><th colspan='6'><FONT FACE=\"ARIAL,HELVETICA\" SIZE=2><a href='$PHP_SELF?query_date=$query_date&end_date=$end_date$groupsQS&file_download=1&SUBMIT=$SUBMIT'>[DOWNLOAD AS CSV FILE]</A></font></th></tr>";
			$rpt_output.="<tr bgcolor='#000'>";
			$rpt_output.="<th nowrap><FONT FACE=\"ARIAL,HELVETICA\" COLOR=WHITE SIZE=1>"._QXZ("Lead ID")."</FONT></th>";
			$rpt_output.="<th nowrap><FONT FACE=\"ARIAL,HELVETICA\" COLOR=WHITE SIZE=1>"._QXZ("Phone number")."</FONT></th>";
			$rpt_output.="<th nowrap><FONT FACE=\"ARIAL,HELVETICA\" COLOR=WHITE SIZE=1>"._QXZ("Callback status")."</FONT></th>";
			$rpt_output.="<th nowrap><FONT FACE=\"ARIAL,HELVETICA\" COLOR=WHITE SIZE=1>"._QXZ("Callback entry time")."</FONT></th>";
			$rpt_output.="<th nowrap><FONT FACE=\"ARIAL,HELVETICA\" COLOR=WHITE SIZE=1>"._QXZ("Scheduled callback time")."</FONT></th>";
			$rpt_output.="<th><FONT FACE=\"ARIAL,HELVETICA\" COLOR=WHITE SIZE=1>"._QXZ("Notes")."</FONT></th>";
			$rpt_output.="</tr>";

			$CSV_output="\"LEAD ID\",\"PHONE NUMBER\",\"STATUS\",\"CALLBACK ENTRY TIME\",\"SCHEDULED CALLBACK TIME\",\"NOTES\"\n";

			$i=0;
			while($callback_row=mysqli_fetch_array($callback_rslt))
				{
				$i++;
				if ($i%2==0) {$bgcolor=$SSstd_row1_background;} else {$bgcolor=$SSstd_row2_background;}
				$rpt_output.="<tr bgcolor='".$bgcolor."'>\n";
				$rpt_output.="<th nowrap><FONT FACE=\"ARIAL,HELVETICA\" SIZE=1>$callback_row[lead_id]</FONT></th>";
				$rpt_output.="<th nowrap><FONT FACE=\"ARIAL,HELVETICA\" SIZE=1>$callback_row[phone_number]</FONT></th>";
				$rpt_output.="<th nowrap><FONT FACE=\"ARIAL,HELVETICA\" SIZE=1>$callback_row[status]</FONT></th>";
				$rpt_output.="<th nowrap><FONT FACE=\"ARIAL,HELVETICA\" SIZE=1>$callback_row[entry_time]</FONT></th>";
				$rpt_output.="<th nowrap><FONT FACE=\"ARIAL,HELVETICA\" SIZE=1>$callback_row[callback_time]</FONT></th>";
				$rpt_output.="<th><FONT FACE=\"ARIAL,HELVETICA\" SIZE=1>$callback_row[comments]</FONT></th>";
				$rpt_output.="</tr>";
				$CSV_output.="\"$callback_row[lead_id]\",\"$callback_row[phone_number]\",\"$callback_row[status]\",\"$callback_row[entry_time]\",\"$callback_row[callback_time]\",\"$callback_row[comments]\"\n";
				}
			$rpt_output.="</table>";
		#	$rpt_output.=$callback_stmt."*\n";
			if ($DB) {$rpt_output.=$callback_stmt."*\n";}

			$endMS = microtime();
			$startMSary = explode(" ",$startMS);
			$endMSary = explode(" ",$endMS);
			$runS = ($endMSary[0] - $startMSary[0]);
			$runM = ($endMSary[1] - $startMSary[1]);
			$TOTALrun = ($runS + $runM);

			$stmt="UPDATE vicidial_report_log set run_time='$TOTALrun' where report_log_id='$report_log_id';";
			if ($DB) {echo "|$stmt|\n";}
			$rslt=mysql_to_mysqli($stmt, $link);


			if ($file_download)
				{
				$FILE_TIME = date("Ymd-His");
				$CSVfilename = "CALLBACKS_EXPORT_$FILE_TIME.csv";

				header('Content-type: application/octet-stream');
				header("Content-Disposition: attachment; filename=\"$CSVfilename\"");
				header('Expires: 0');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
				ob_clean();
				flush();

				echo "$CSV_output";

				if ($db_source == 'S')
					{
					mysqli_close($link);
					$use_slave_server=0;
					$db_source = 'M';
					require("dbconnect_mysqli.php");
					}

				### LOG INSERTION Admin Log Table ###
				$SQL_log = "$callback_stmt";
				$SQL_log = preg_replace('/;/', '', $SQL_log);
				$SQL_log = addslashes($SQL_log);
				$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$PHP_AUTH_USER', ip_address='$ip', event_section='CALLBACKS', event_type='EXPORT', record_id='', event_code='ADMIN EXPORT CALLBACKS REPORT', event_sql=\"$SQL_log\", event_notes='';";
				if ($DB) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);

				exit;
				}
			}
		else
			{
			$err_msg="<tr><th colspan=2><B><font color='#F00'>** NO RESULTS FOUND **</th></tr>";
			}
		}
	else 
		{
		$err_msg="<tr><th colspan=2><B><font color='#F00'>** REPORT NOT RUN - SOME VALUES MISSING **</th></tr>";
		}
	}


###############

echo "<html>\n";
echo "<head>\n";
echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";

echo "<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
echo "<link rel=\"stylesheet\" href=\"calendar.css\">\n";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";
echo "<script language=\"JavaScript\" src=\"help.js\"></script>\n";
echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>\n";

echo "<title>"._QXZ("ADMINISTRATION: Callbacks Bulk Move");

##### BEGIN Set variables to make header show properly #####
$ADD =					'311111';
$hh =					'usergroups';
$LOGast_admin_access =	'1';
$ADMIN =				'admin.php';
$page_width='770';
$section_width='750';
$header_font_size='3';
$subheader_font_size='2';
$subcamp_font_size='2';
$header_selected_bold='<b>';
$header_nonselected_bold='';
$usergroups_color =		'#FFFF99';
$usergroups_font =		'BLACK';
$usergroups_color =		'#E6E6E6';
$subcamp_color =	'#C6C6C6';
##### END Set variables to make header show properly #####

require("admin_header.php");


echo "<CENTER>\n";
echo "<TABLE WIDTH=680 BGCOLOR='#".$SSmenu_background."' cellpadding=2 cellspacing=0><TR BGCOLOR='#".$SSmenu_background."'><TD ALIGN=LEFT><FONT FACE=\"ARIAL,HELVETICA\" COLOR=WHITE SIZE=2><B> &nbsp; "._QXZ("Callbacks Export")."$NWB#cb-export$NWE</TD><TD ALIGN=RIGHT><FONT FACE=\"ARIAL,HELVETICA\" COLOR=WHITE SIZE=2><B> &nbsp; </TD></TR>\n";

echo "<TR BGCOLOR=\"#".$SSstd_row1_background."\"><TD ALIGN=center COLSPAN=2><FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=3><B> &nbsp; \n";

### callbacks change form
echo "<form action=$PHP_SELF method=GET name='vicidial_report'>\n";
echo "<input type=hidden name=DB value=\"$DB\">\n";

$purge_trigger=0;
$callback_statuses="'LIVE'";
$purge_verbiage=array();

	echo "<table width='650' align='center' border=0>";
	echo $err_msg;
	echo "<tr>";
	echo "<td align='left' width='270'>";
	$stmt="SELECT campaign_id, count(*) as ct from vicidial_callbacks $whereLOGallowed_campaignsSQL group by campaign_id order by campaign_id";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	echo "<B>"._QXZ("Campaigns with callbacks").":</B>$NWB#cb-bulk-campaigns$NWE<BR><select name='cb_groups[]' size=5 multiple>\n";
	echo "<option value='ALL'>--- "._QXZ("ALL CAMPAIGNS")." ---</option>\n";
	if (mysqli_num_rows($rslt)>0) 
		{
		while ($row=mysqli_fetch_array($rslt)) 
			{
			if (in_array($row["campaign_id"], $cb_groups)) {$s=" selected";} else {$s="";}
			echo "\t<option value='$row[campaign_id]'$s>".($row["campaign_id"] ? $row["campaign_id"] : "(none)")." - ($row[ct] "._QXZ("callbacks").")</option>\n";
			}
		}
	else
		{
		echo "\t<option value=''>**"._QXZ("NO CALLBACKS")."**</option>\n";
		}
	echo "</select>";
	echo "</td>";

	echo "<TD VALIGN=TOP align='left'><B>"._QXZ("Scheduled Callback Dates").":$NWB#cb-export-cb-dates$NWE<BR>";
	echo "<INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\">";
	echo "<script language=\"JavaScript\">\n";
	echo "function openNewWindow(url)\n";
	echo "  {\n";
	echo "  window.open (url,\"\",'width=620,height=300,scrollbars=yes,menubar=yes,address=yes');\n";
	echo "  }\n";
	echo "var o_cal = new tcal ({\n";
	echo "	// form name\n";
	echo "	'formname': 'vicidial_report',\n";
	echo "	// input name\n";
	echo "	'controlname': 'query_date'\n";
	echo "});\n";
	echo "o_cal.a_tpl.yearscroll = false;\n";
	echo "// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
	echo "</script>\n";
	echo "<BR> "._QXZ("to")." <BR><INPUT TYPE=TEXT NAME=end_date SIZE=10 MAXLENGTH=10 VALUE=\"$end_date\">";
	echo "<script language=\"JavaScript\">\n";
	echo "var o_cal = new tcal ({\n";
	echo "	// form name\n";
	echo "	'formname': 'vicidial_report',\n";
	echo "	// input name\n";
	echo "	'controlname': 'end_date'\n";
	echo "});\n";
	echo "o_cal.a_tpl.yearscroll = false;\n";
	echo "// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
	echo "</script>\n";
	echo "</B></TD>";

	echo "<tr>";
	echo "<td align='center' colspan='2'>";
	echo "<input type='submit' name='SUBMIT' value='SUBMIT'><BR/><BR/>";
	echo "</td>";
	echo "</tr>";

	echo "</TR>";
	echo "</table>";

	echo "</table>";

	echo "<BR><BR>";

	echo $rpt_output;

echo "</form>\n";


$ENDtime = date("U");

$RUNtime = ($ENDtime - $StarTtimE);

echo "\n\n\n<br><br><br>\n\n";


echo "<font size=0>\n\n\n<br><br><br>\n"._QXZ("script runtime").": $RUNtime "._QXZ("seconds")."</font>";

?>


</TD></TR><TABLE>

</body>
</html>
