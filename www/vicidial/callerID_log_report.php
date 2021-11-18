<?php
# callerID_log_report.php
# 
# A report giving a call count per caller ID used - allows for searching by date range, campaign, and status
# Created to find problematic caller IDs, which can be found if bad outbound calls are dispositioned 
# particular statuses
#
# Copyright (C) 2020  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 200115-1512 - First build
# 200120-1430 - Added total calls, percentage of calls matched, default CID notation
# 201111-1630 - Translation issue fix, Issue #1231
#

$startMS = microtime();

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["query_date"]))				{$query_date=$_GET["query_date"];}
	elseif (isset($_POST["query_date"]))	{$query_date=$_POST["query_date"];}
if (isset($_GET["end_date"]))				{$end_date=$_GET["end_date"];}
	elseif (isset($_POST["end_date"]))		{$end_date=$_POST["end_date"];}
if (isset($_GET["query_time"]))				{$query_time=$_GET["query_time"];}
	elseif (isset($_POST["query_time"]))	{$query_time=$_POST["query_time"];}
if (isset($_GET["end_time"]))				{$end_time=$_GET["end_time"];}
	elseif (isset($_POST["end_time"]))		{$end_time=$_POST["end_time"];}
if (isset($_GET["campaign"]))				{$campaign=$_GET["campaign"];}
	elseif (isset($_POST["campaign"]))		{$campaign=$_POST["campaign"];}
if (isset($_GET["status"]))					{$status=$_GET["status"];}
	elseif (isset($_POST["status"]))		{$status=$_POST["status"];}
if (isset($_GET["interval"]))						{$interval=$_GET["interval"];}
	elseif (isset($_POST["interval"]))			{$interval=$_POST["interval"];}
if (isset($_GET["search_archived_data"]))			{$search_archived_data=$_GET["search_archived_data"];}
	elseif (isset($_POST["search_archived_data"]))	{$search_archived_data=$_POST["search_archived_data"];}
if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["SUBMIT"]))					{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))		{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["file_download"]))			{$file_download=$_GET["file_download"];}
	elseif (isset($_POST["file_download"]))	{$file_download=$_POST["file_download"];}

if (strlen($shift)<2) {$shift='ALL';}

$report_name = 'Caller ID Log Report';
$db_source = 'M';
$file_exported=0;
$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";
if (!isset($interval)) {$interval = '0';}
$interval=preg_replace('/[^0-9]/', '', $interval);

### ARCHIVED DATA CHECK CONFIGURATION
$archives_available="N";
$log_tables_array=array("vicidial_dial_log", "vicidial_log");
for ($t=0; $t<count($log_tables_array); $t++) 
	{
	$table_name=$log_tables_array[$t];
	$archive_table_name=use_archive_table($table_name);
	if ($archive_table_name!=$table_name) {$archives_available="Y";}
	}

if ($search_archived_data) 
	{
	$vicidial_dial_log_table=use_archive_table("vicidial_dial_log");
	$vicidial_log_table=use_archive_table("vicidial_log");
	}
else
	{
	$vicidial_dial_log_table="vicidial_dial_log";
	$vicidial_log_table="vicidial_log";
	}
#############


#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,custom_fields_enabled,enable_languages,language_method,active_modules FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$outbound_autodial_active =		$row[1];
	$slave_db_server =				$row[2];
	$reports_use_slave_db =			$row[3];
	$custom_fields_enabled =		$row[4];
	$SSenable_languages =			$row[5];
	$SSlanguage_method =			$row[6];
	$active_modules =				$row[7];
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

$stmt="SELECT export_reports,user_group,admin_hide_lead_data,admin_hide_phone_data,admin_cf_show_hidden from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGexport_reports =			$row[0];
$LOGuser_group =				$row[1];
$LOGadmin_hide_lead_data =		$row[2];
$LOGadmin_hide_phone_data =		$row[3];
$LOGadmin_cf_show_hidden =		$row[4];

if ($LOGexport_reports < 1)
	{
	Header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions for export reports").": |$PHP_AUTH_USER|\n";
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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name Carrier', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$campaign[0], $query_date, $end_date|', url='$LOGfull_url', webserver='$webserver_id';";
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
#	echo "<!-- Using slave server $slave_db_server $db_source -->\n";
	}

$stmt="SELECT allowed_campaigns,allowed_reports,admin_viewable_groups,admin_viewable_call_times from vicidial_user_groups where user_group='$LOGuser_group';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGallowed_campaigns =			$row[0];
$LOGallowed_reports =			$row[1];
$LOGadmin_viewable_groups =		$row[2];
$LOGadmin_viewable_call_times =	$row[3];

if ( (!preg_match("/$report_name/",$LOGallowed_reports)) and (!preg_match("/ALL REPORTS/",$LOGallowed_reports)) )
	{
    Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
    Header("HTTP/1.0 401 Unauthorized");
    echo _QXZ("You are not allowed to view this report").": |$PHP_AUTH_USER|$report_name|\n";
    exit;
	}

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

##### START RUN THE EXPORT AND OUTPUT FLAT DATA FILE #####
if ($SUBMIT)
	{
	$US='_';
	$MT[0]='';
	$ip = getenv("REMOTE_ADDR");
	$NOW_DATE = date("Y-m-d");
	$NOW_TIME = date("Y-m-d H:i:s");
	$FILE_TIME = date("Ymd-His");
	$STARTtime = date("U");
	if (!isset($campaign)) {$campaign = array();}
	if (!isset($status)) {$status = array();}
	if (!isset($query_date)) {$query_date = $NOW_DATE;}
	if (!isset($end_date)) {$end_date = $NOW_DATE;}
	$log_calls=0;
	$total_calls=0;

	$campaign_ct = count($campaign);
	$status_ct = count($status);
	$CID=array();
	$campaign_string='|';
	$group_string='|';
	$user_group_string='|';
	$list_string='|';
	$status_string='|';

	$campaignQS="";
	$groupQS="";
	$user_groupQS="";
	$listQS="";
	$statusQS="";

	$i=0;
	while($i < $campaign_ct)
		{
		if ( (preg_match("/ $campaign[$i] /",$regexLOGallowed_campaigns)) or (preg_match("/-ALL/",$LOGallowed_campaigns)) )
			{
			$campaign_string .= "$campaign[$i]|";
			$campaign_SQL .= "'$campaign[$i]',";
			$campaignQS .= "&campaign[]=$campaign[$i]";
			}
		$i++;
		}
	if ( $campaign_ct < 1 )
		{
		$campaign_SQL = "campaign_id IN('')";
		$RUNcampaign=0;
		}
	else if (preg_match('/\-\-\-ALL\-\-\-/',$campaign_string) )  
		{
		$campaign_SQL = "";
		$campaign_SQL_where = "";
		$RUNcampaign=1;
		}
	else
		{
		$campaign_SQL = preg_replace('/,$/i', '',$campaign_SQL);
		$campaign_SQL = "and campaign_id IN($campaign_SQL)";
		$campaign_SQL_where = "where campaign_id IN($campaign_SQL)";
		$RUNcampaign++;
		}

	$i=0;
	while($i < $status_ct)
		{
		$status_string .= "$status[$i]|";
		$status_SQL .= "'$status[$i]',";
		$statusQS .= "&status[]=$status[$i]";
		$i++;
		}
	if ( (preg_match('/\-\-ALL\-\-/',$status_string) ) or ($status_ct < 1) )
		{
		$status_SQL = "";
		}
	else
		{
		$status_SQL = preg_replace('/,$/i', '',$status_SQL);
		$status_SQL = "and vl.status IN($status_SQL)";
		}

	$CCIDs=array();
	$campaign_CID_stmt="select campaign_cid from vicidial_campaigns $campaign_SQL_where";
	$campaign_CID_rslt=mysql_to_mysqli($campaign_CID_stmt, $link);
	while ($ccid_row=mysqli_fetch_row($campaign_CID_rslt)) {
		array_push($CCIDs, "$ccid_row[0]");
	}

	$stmt="select lead_id, call_date, status from ".$vicidial_log_table." vl where call_date>='$query_date $query_time' and call_date<='$end_date $end_time' $campaign_SQL";
	$rslt=mysql_to_mysqli($stmt, $link);
	$vlog_calls=mysqli_num_rows($rslt);
	while ($row=mysqli_fetch_row($rslt)) 
		{
		$lead_id=$row[0];
		$call_date=$row[1];
		$status=$row[2];
		$stmt2="select call_date, channel, outbound_cid from ".$vicidial_dial_log_table." where lead_id='$lead_id' and call_date>='$call_date'-INTERVAL $interval SECOND and call_date<='$call_date'+INTERVAL $interval SECOND";
		#print $stmt2."<BR>\n";
		$rslt2=mysql_to_mysqli($stmt2, $link);
		while($row2=mysqli_fetch_row($rslt2)) 
			{
			preg_match('/<[0-9]+>/', $row2[2], $matches);
			$caller_id=preg_replace('/[^0-9]/', '', $matches[0]);
			$total_calls++;
			$CID["$caller_id"][0]++;
			if (preg_match("/\|($status|\-\-\-ALL\-\-\-)\|/i", $status_string))
				{
				$CID["$caller_id"][1]++;
				$log_calls++;
				}
			else 
				{
				if ($DB) {print "$status not in $status_string<BR>\n";}
				}
			}
		}

	ksort($CID);

	$alt_stmt="select vdl.call_date, vdl.channel, vdl.outbound_cid from ".$vicidial_dial_log_table." vdl, ".$vicidial_log_table." vl where vl.call_date>='$query_date 00:00:00' and vl.call_date<='$end_date 23:59:59' $status_SQL $campaign_SQL and vdl.lead_id=vl.lead_id and vdl.call_date>=vl.call_date-INTERVAL $interval SECOND and vdl.call_date<=vl.call_date+INTERVAL $interval SECOND";
/*
	$alt_rslt=mysql_to_mysqli($alt_stmt, $link);
	$found_calls=mysqli_num_rows($alt_rslt);
	$log_calls=mysqli_num_rows($alt_rslt);
	while ($alt_row=mysqli_fetch_row($alt_rslt)) 
		{
		preg_match('/<[0-9]+>/', $alt_row[2], $matches);
		$caller_id=preg_replace('/[^0-9]/', '', $matches[0]);
		$CID["$caller_id"]++;
		}
*/

	if ($vlog_calls>0) 
		{
		if ($file_download==1)
			{
			$TXTfilename = "CALLER_ID_REPORT_$FILE_TIME.txt";

			// We'll be outputting a TXT file
			header('Content-type: application/octet-stream');

			// It will be called LIST_101_20090209-121212.txt
			header("Content-Disposition: attachment; filename=\"$TXTfilename\"");
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			ob_clean();
			flush();

			$i=0;

			echo "CALLER ID\tCALLS WITH STATUS\tTOTAL CALLS\tPERCENT\n";

			foreach($CID as $key => $value)
				{
				if (in_array($key, $CCIDs))
					{
					$apx=" **";
					} 
				else 
					{
					$apx="";
					}

				$pct=round((1000*$value[1])/$value[0])/10;
				echo $key.$apx."\t".($value[1]+0)."\t".$value[0]."\t".$pct." %\n";
				}

			exit;

			}
		else
			{
			$rslt_msg="<TABLE BORDER=0 align='center' width='600' cellpadding=5 cellspacing=0>";
			$rslt_msg.="<tr bgcolor=black>";
			$rslt_msg.="<td align='center'><font SIZE=3 FACE=\"Arial,Helvetica\" color=white align=left><B>"._QXZ("Caller ID")."</B></font></td>";
			$rslt_msg.="<td align='center'><font SIZE=3 FACE=\"Arial,Helvetica\" color=white align=left><B>"._QXZ("CALLS W/STATUS")."</B></font></td>";
			$rslt_msg.="<td align='center'><font SIZE=3 FACE=\"Arial,Helvetica\" color=white align=left><B>"._QXZ("TOTAL CALLS")."</B></font></td>";
			$rslt_msg.="<td align='center'><font SIZE=3 FACE=\"Arial,Helvetica\" color=white align=left><B>"._QXZ("PCT")."</B></font></td>";
			$rslt_msg.="</tr>\n";

			foreach($CID as $key => $value)
				{
				if (preg_match('/1$|3$|5$|7$|9$/i', $p))
					{$bgcolor='class="records_list_x"';} 
				else
					{$bgcolor='class="records_list_y"';}
				if (in_array($key, $CCIDs))
					{
					$bgcolor="bgcolor='$SSstd_row3_background'";
					$apx=" **";
					} 
				else 
					{
					$apx="";
					}

				$rslt_msg.="<tr $bgcolor>";
				$rslt_msg.="<td align='left'>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<font SIZE=2 FACE=\"Arial,Helvetica\" color=black align=left>".$key.$apx."</font></td>";
				$rslt_msg.="<td align='center'><font SIZE=2 FACE=\"Arial,Helvetica\" color=black align=left>".($value[1]+0)."</font></td>";
				$rslt_msg.="<td align='center'><font SIZE=2 FACE=\"Arial,Helvetica\" color=black align=left>".$value[0]."</font></td>";
				$pct=round((1000*$value[1])/$value[0])/10;
				$rslt_msg.="<td align='center'><font SIZE=2 FACE=\"Arial,Helvetica\" color=black align=left>".$pct." %</font></td>";
				$rslt_msg.="</tr>";
				$p++;
				}
			$rslt_msg.="<tr bgcolor=black>";
			$rslt_msg.="<td align='center'><font SIZE=3 FACE=\"Arial,Helvetica\" color=white align=left><B>"._QXZ("TOTAL")."</B></font></td>";
			$rslt_msg.="<td align='center'><font SIZE=3 FACE=\"Arial,Helvetica\" color=white align=left>$log_calls</font></td>";
			$rslt_msg.="<td align='center'><font SIZE=3 FACE=\"Arial,Helvetica\" color=white align=left>$total_calls</font></td>";
			$pct=round((1000*$log_calls)/$total_calls)/10;
			$rslt_msg.="<td align='center'><font SIZE=2 FACE=\"Arial,Helvetica\" color=white align=left>".$pct." %</font></td>";
			$rslt_msg.="</tr>\n";
			$rslt_msg.="<tr><th colspan='4'><font SIZE=3 FACE=\"Arial,Helvetica\"><a href='$PHP_SELF?query_date=$query_date&end_date=$end_date$campaignQS$statusQS&SUBMIT=SUBMIT&file_download=1&interval=$interval&search_archived_data=$search_archived_data'>Download file here</a></font></th></tr>";
			$rslt_msg.="<tr><td align='left'><font SIZE=2 FACE=\"Arial,Helvetica\">** - denotes default campaign CID</font></td></tr>";
			$rslt_msg.="</table>";



			if ($DB > 0)
				{
				echo "<BR>\n";
				echo "$campaign_ct|$campaign_string|$campaign_SQL\n";
				echo "<BR>\n";
				echo "$status_ct|$status_string|$status_SQL\n";
				echo "<BR>$Tstmt<BR>$stmt<BR>$alt_stmt<BR>\n";
				}
			}
		}
	}
##### END RUN THE EXPORT AND OUTPUT FLAT DATA FILE #####


if ($file_exported < 1)
	{
	$NOW_DATE = date("Y-m-d");
	$NOW_TIME = date("Y-m-d H:i:s");
	$STARTtime = date("U");
	if (!isset($campaign)) {$campaign = array();}
	if (!isset($status)) {$status = array();}
	if (!isset($query_date)) {$query_date = $NOW_DATE;}
	if (!isset($end_date)) {$end_date = $NOW_DATE;}
	if (!isset($query_time)) {$query_time = "00:00:00";}
	if (!isset($end_time)) {$end_time = "23:59:59";}

	$stmt="select campaign_id from vicidial_campaigns $whereLOGallowed_campaignsSQL order by campaign_id;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$campaigns_to_print = mysqli_num_rows($rslt);
	$i=0;
		$LISTcampaigns[$i]='---ALL---';
		$i++;
		$campaigns_to_print++;
	while ($i < $campaigns_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$LISTcampaigns[$i] =$row[0];
		$i++;
		}

	$stmt="select status from vicidial_statuses order by status;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$statuses_to_print = mysqli_num_rows($rslt);
	$i=0;
		$LISTstatus[$i]='---ALL---';
		$i++;
		$statuses_to_print++;
	while ($i < $statuses_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$LISTstatus[$i] =$row[0];
		$i++;
		}

	$stmt="select distinct status from vicidial_campaign_statuses $whereLOGallowed_campaignsSQL order by status;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$Cstatuses_to_print = mysqli_num_rows($rslt);
	$j=0;
	while ($j < $Cstatuses_to_print)
		{
		$row=mysqli_fetch_row($rslt);
		$LISTstatus[$i] =$row[0];
		$i++;
		$j++;
		}
	$statuses_to_print = ($statuses_to_print + $Cstatuses_to_print);

	echo "<HTML><HEAD>\n";

	echo "<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
	echo "<script language=\"JavaScript\" src=\"help.js\"></script>\n";

	echo "<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>";

	echo "<link rel=\"stylesheet\" href=\"calendar.css\">\n";
	echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";

	echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
	echo "<TITLE>"._QXZ("ADMINISTRATION").": "._QXZ("$report_name");

	##### BEGIN Set variables to make header show properly #####
	$ADD =					'100';
	# $hh =					'reports';
	$LOGast_admin_access =	'1';
	$SSoutbound_autodial_active = '1';
	$ADMIN =				'admin.php';
	$page_width='770';
	$section_width='750';
	$header_font_size='3';
	$subheader_font_size='2';
	$subcamp_font_size='2';
	$header_selected_bold='<b>';
	$header_nonselected_bold='';
	$lists_color =		'#FFFF99';
	$lists_font =		'BLACK';
	$lists_color =		'#E6E6E6';
	$subcamp_color =	'#C6C6C6';
	##### END Set variables to make header show properly #####

	require("admin_header.php");


	echo "<CENTER><BR>\n";
	echo "<FONT SIZE=3 FACE=\"Arial,Helvetica\"><B>"._QXZ("$report_name");
	if ($ivr_export == 'YES')
		{echo " IVR";}
	echo "</B></FONT> $NWB#cid_log_report$NWE<BR>\n";
	echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET name=vicidial_report id=vicidial_report>\n";
	echo "<INPUT TYPE=HIDDEN NAME=DB VALUE=\"$DB\">";
	echo "<INPUT TYPE=HIDDEN NAME=run_export VALUE=\"1\">";
	echo "<TABLE BORDER=0 CELLSPACING=8><TR><TD ALIGN=LEFT VALIGN=TOP>\n";

	echo "<font class=\"select_bold\"><B>"._QXZ("Date Range").":</B></font><BR><CENTER>\n";
	echo "<INPUT TYPE=TEXT NAME=query_date SIZE=10 MAXLENGTH=10 VALUE=\"$query_date\">";

	?>
	<script language="JavaScript">
	var o_cal = new tcal ({
		// form name
		'formname': 'vicidial_report',
		// input name
		'controlname': 'query_date'
	});
	o_cal.a_tpl.yearscroll = false;
	// o_cal.a_tpl.weekstart = 1; // Monday week start
	</script>
	<?php
	echo "<INPUT TYPE=TEXT NAME=query_time SIZE=8 MAXLENGTH=8 VALUE=\"$query_time\">";

	echo "<BR>"._QXZ("to")."<BR>\n";
	echo "<INPUT TYPE=TEXT NAME=end_date SIZE=10 MAXLENGTH=10 VALUE=\"$end_date\">";

	?>
	<script language="JavaScript">
	var o_cal = new tcal ({
		// form name
		'formname': 'vicidial_report',
		// input name
		'controlname': 'end_date'
	});
	o_cal.a_tpl.yearscroll = false;
	// o_cal.a_tpl.weekstart = 1; // Monday week start
	</script>
	<?php
	echo "<INPUT TYPE=TEXT NAME=end_time SIZE=8 MAXLENGTH=8 VALUE=\"$end_time\">";

	echo "<BR><BR>\n";

	### bottom of first column

	echo "</TD><TD ALIGN=LEFT VALIGN=TOP>\n";
	echo "<font class=\"select_bold\"><B>"._QXZ("Campaigns").":</B></font><BR><CENTER>\n";
	echo "<SELECT SIZE=5 NAME=campaign[] multiple>\n";
		$o=0;
		while ($campaigns_to_print > $o)
		{
			if (preg_match("/\|$LISTcampaigns[$o]\|/",$campaign_string)) 
				{echo "<option selected value=\"$LISTcampaigns[$o]\">"._QXZ("$LISTcampaigns[$o]")."</option>\n";}
			else 
				{echo "<option value=\"$LISTcampaigns[$o]\">"._QXZ("$LISTcampaigns[$o]")."</option>\n";}
			$o++;
		}
	echo "</SELECT>\n";

	echo "</TD><TD ALIGN=LEFT VALIGN=TOP>\n";
	echo "<font class=\"select_bold\"><B>"._QXZ("Statuses").":</B></font><BR><CENTER>\n";
	echo "<SELECT SIZE=5 NAME=status[] multiple>\n";
		$o=0;
		while ($statuses_to_print > $o)
		{
			if (preg_match("/\|$LISTstatus[$o]\|/",$status_string)) 
				{echo "<option selected value=\"$LISTstatus[$o]\">"._QXZ("$LISTstatus[$o]")."</option>\n";}
			else 
				{echo "<option value=\"$LISTstatus[$o]\">"._QXZ("$LISTstatus[$o]")."</option>\n";}
			$o++;
		}
	echo "</SELECT>\n";
	echo "</TD><TD ALIGN=LEFT VALIGN=TOP>\n";

	echo "<font class=\"select_bold\"><B>"._QXZ("Log second diff").":</B></font>$NWB#callerID_log_report-log_second_diff$NWE<BR><CENTER>\n";
	echo "<INPUT TYPE=TEXT NAME=interval SIZE=2 MAXLENGTH=2 VALUE=\"".$interval."\">";
	echo "<BR><BR>";
	if ($archives_available=="Y") 
		{
		echo "<input type='checkbox' name='search_archived_data' value='checked' $search_archived_data>"._QXZ("Search archived data")."<BR>\n";
		echo "<BR><BR>";
		}
	echo "<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";	
	echo "</TD></TR><TR></TD><TD ALIGN=LEFT VALIGN=TOP COLSPAN=4> &nbsp; \n";

	echo $rslt_msg;

	echo "</TD></TR></TABLE>\n";
	echo "</FORM>\n\n";

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

if ($file_exported > 0)
	{
	### LOG INSERTION Admin Log Table ###
	$SQL_log = "$stmt|$stmtA|";
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = addslashes($SQL_log);
	$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$PHP_AUTH_USER', ip_address='$ip', event_section='CALLS', event_type='EXPORT', record_id='', event_code='CALLER ID LOG REPORT', event_sql=\"$SQL_log\", event_notes='';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	}

exit;

?>
