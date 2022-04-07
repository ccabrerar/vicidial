<?php 
# AST_VICIDIAL_hopperlist.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 60619-1654 - Added variable filtering to eliminate SQL injection attack threat
#            - Added required user/pass to gain access to this page
# 70115-1614 - Added ALT field for vicidial_hopper alt_dial column
# 71029-0852 - Added list_id to the output
# 71030-2118 - Added priority to display
# 90508-0644 - Changed to PHP long tags
# 91023-1540 - Changed to only show hopper status of READY
# 101111-1253 - Added source field
# 111103-1207 - Added admin_hide_phone_data and admin_hide_lead_data options
# 120210-1218 - Added vendor_lead_code to output
# 120221-0059 - Added User Group restriction settings
# 130414-0159 - Added report logging
# 130610-0955 - Finalized changing of all ereg instances to preg
# 130620-2222 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-2003 - Changed to mysqli PHP functions
# 140108-0729 - Added webserver and hostname to report logging
# 141114-0700 - Finalized adding QXZ translation to all admin files
# 141230-0048 - Added code for on-the-fly language translations display
# 161026-0931 - Added links to leads and lists, added phone code, age, last call columns
# 170409-1544 - Added IP List validation code
# 180310-2250 - Added optional source_id display
# 190716-2119 - Added rank
# 201111-1435 - Added Campaign Drop-Run load
# 210514-1615 - Added owner to DB=1 output
# 220301-1627 - Added allow_web_debug system setting
#

$startMS = microtime();

$report_name='Hopper List Report';

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["DB"]))					{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))		{$DB=$_POST["DB"];}
if (isset($_GET["group"]))				{$group=$_GET["group"];}
	elseif (isset($_POST["group"]))		{$group=$_POST["group"];}
if (isset($_GET["submit"]))				{$submit=$_GET["submit"];}
	elseif (isset($_POST["submit"]))	{$submit=$_POST["submit"];}
if (isset($_GET["SUBMIT"]))				{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

$NOW_DATE = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$STARTtime = date("U");
if (!isset($group)) {$group = '';}
if (!isset($query_date)) {$query_date = $NOW_DATE;}
if (!isset($server_ip)) {$server_ip = '10.10.10.15';}
$isdst = date("I");

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,enable_languages,language_method,source_id_display,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$SSenable_languages =			$row[1];
	$SSlanguage_method =			$row[2];
	$SSsource_id_display =			$row[3];
	$SSallow_web_debug =			$row[4];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$submit = preg_replace('/[^-_0-9a-zA-Z]/', '', $submit);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/', '', $SUBMIT);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$group = preg_replace('/[^-_0-9a-zA-Z]/', '', $group);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$group = preg_replace('/[^-_0-9\p{L}]/u', '', $group);
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

$stmt="SELECT modify_campaigns,user_group from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGmodify_campaigns =	$row[0];
$LOGuser_group =		$row[1];

if ($LOGmodify_campaigns < 1)
	{
	Header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions for campaign modification").": |$PHP_AUTH_USER|\n";
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

$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |$group, $query_date, $end_date, $shift, $file_download, $report_display_type|', url='$LOGfull_url', webserver='$webserver_id';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$report_log_id = mysqli_insert_id($link);
##### END log visit to the vicidial_report_log table #####

$stmt="SELECT full_name,user_group,admin_hide_lead_data,admin_hide_phone_data from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGfullname =				$row[0];
$LOGuser_group =			$row[1];
$LOGadmin_hide_lead_data =	$row[2];
$LOGadmin_hide_phone_data =	$row[3];

### Grab Server GMT value from the database
$SERVER_GMT=-5;
$stmt="SELECT local_gmt FROM servers where active='Y' limit 1;";
$rslt=mysql_to_mysqli($stmt, $link);
$gmt_recs = mysqli_num_rows($rslt);
if ($gmt_recs > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$DBSERVER_GMT		=		$row[0];
	if (strlen($DBSERVER_GMT)>0)	{$SERVER_GMT = $DBSERVER_GMT;}
	if ($isdst) {$SERVER_GMT++;} 
	}
else
	{
	$SERVER_GMT = date("O");
	$SERVER_GMT = preg_replace("/\+/i","",$SERVER_GMT);
	$SERVER_GMT = ($SERVER_GMT + 0);
	$SERVER_GMT = ($SERVER_GMT / 100);
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


$stmt="select campaign_id,campaign_name from vicidial_campaigns $whereLOGallowed_campaignsSQL order by campaign_id;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$campaigns_to_print = mysqli_num_rows($rslt);
$i=0;
while ($i < $campaigns_to_print)
	{
	$row=mysqli_fetch_row($rslt);
	$campaign_id[$i] =$row[0];
	$campaign_name[$i] =$row[1];
	$i++;
	}
?>

<HTML>
<HEAD>
<STYLE type="text/css">
<!--
   .green {color: white; background-color: green}
   .red {color: white; background-color: red}
   .blue {color: white; background-color: blue}
   .purple {color: white; background-color: purple}
-->
 </STYLE>

<?php 
echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
echo "<TITLE>"._QXZ("Hopper List Report")."</TITLE></HEAD><BODY BGCOLOR=WHITE marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

	$short_header=1;

	require("admin_header.php");

echo "<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>";
echo "<FORM ACTION=\"$PHP_SELF\" METHOD=GET>\n";
echo "<SELECT SIZE=1 NAME=group>\n";
$o=0;
while ($campaigns_to_print > $o)
	{
	if ($campaign_id[$o] == $group) {echo "<option selected value=\"$campaign_id[$o]\">$campaign_id[$o] - $campaign_name[$o]</option>\n";}
	else {echo "<option value=\"$campaign_id[$o]\">$campaign_id[$o] - $campaign_name[$o]</option>\n";}
	$o++;
	}
echo "</SELECT>\n";
echo "<INPUT TYPE=SUBMIT NAME=SUBMIT VALUE='"._QXZ("SUBMIT")."'>\n";
echo " &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <a href=\"./admin.php?ADD=34&campaign_id=$group\">"._QXZ("MODIFY")."</a> \n";
echo "</FORM>\n\n";

echo "<PRE><FONT SIZE=2>\n\n";


if (!$group)
	{
	echo "\n\n";
	echo _QXZ("PLEASE SELECT A CAMPAIGN ABOVE AND CLICK SUBMIT")."\n";
	}

else
	{
	echo _QXZ("Live Current Hopper List",45)." $NOW_TIME\n";

	echo "\n";
	echo "---------- "._QXZ("TOTALS")."\n";

	$stmt="select count(*) from vicidial_hopper where campaign_id='" . mysqli_real_escape_string($link, $group) . "' $LOGallowed_campaignsSQL;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$row=mysqli_fetch_row($rslt);

	$TOTALcalls =	sprintf("%10s", $row[0]);

	echo _QXZ("Total leads in hopper right now").":       $TOTALcalls\n";


	##############################
	#########  LEAD STATS

	$SIDhead='';
	$SIDline='';
	if ($SSsource_id_display > 0)
		{
		$SIDhead='+----------------------';
		$SIDline="| "._QXZ("SOURCE ID",20)." ";
		}
	echo "\n";
	echo "---------- "._QXZ("LEADS IN HOPPER")."\n";
	echo "+------+--------+-----------+------------+-------------+---------+-------+--------+-------+--------+--------+-------+-------+----------------------$SIDhead+----------+-----------+\n";
	if ($DB) 
		{
		echo "|"._QXZ("ORDER",5)." |"._QXZ("PRIORITY",8)."| "._QXZ("LEAD ID",9)." | "._QXZ("LIST ID",10)." | "._QXZ("PHONE NUM",11)." | "._QXZ("PH CODE",7)." | "._QXZ("STATE",5)." | "._QXZ("STATUS",6)." | "._QXZ("COUNT",5)." | "._QXZ("GMT",6)." | "._QXZ("RANK",6)." | "._QXZ("ALT",5)." | "._QXZ("SOURCE",6)."| "._QXZ("VENDOR LEAD CODE",20)." $SIDline| "._QXZ("AGE DAYS",8)." | "._QXZ("LAST CALL",9)." | "._QXZ("HOPPER ID",10)." | "._QXZ("OWNER",10)."\n";
		}
	else
		{
		echo "|"._QXZ("ORDER",5)." |"._QXZ("PRIORITY",8)."| "._QXZ("LEAD ID",9)." | "._QXZ("LIST ID",10)." | "._QXZ("PHONE NUM",11)." | "._QXZ("PH CODE",7)." | "._QXZ("STATE",5)." | "._QXZ("STATUS",6)." | "._QXZ("COUNT",5)." | "._QXZ("GMT",6)." | "._QXZ("RANK",6)." | "._QXZ("ALT",5)." | "._QXZ("SOURCE",6)."| "._QXZ("VENDOR LEAD CODE",20)." $SIDline| "._QXZ("AGE DAYS",8)." | "._QXZ("LAST CALL",9)." |\n";
		}
	echo "+------+--------+-----------+------------+-------------+---------+-------+--------+-------+--------+--------+-------+-------+----------------------$SIDhead+----------+-----------+\n";

	$stmt="select vicidial_hopper.lead_id,phone_number,vicidial_hopper.state,vicidial_list.status,called_count,vicidial_hopper.gmt_offset_now,hopper_id,alt_dial,vicidial_hopper.list_id,vicidial_hopper.priority,vicidial_hopper.source,vicidial_hopper.vendor_lead_code, phone_code,UNIX_TIMESTAMP(entry_date),UNIX_TIMESTAMP(last_local_call_time),source_id,vicidial_list.rank,vicidial_list.owner from vicidial_hopper,vicidial_list where vicidial_hopper.campaign_id='" . mysqli_real_escape_string($link, $group) . "' and vicidial_hopper.status='READY' and vicidial_hopper.lead_id=vicidial_list.lead_id $LOGallowed_campaignsSQL order by priority desc,hopper_id limit 5000;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$users_to_print = mysqli_num_rows($rslt);
	$i=0;
	while ($i < $users_to_print)
		{
		$DBdata='';
		$row=mysqli_fetch_row($rslt);

		if ($LOGadmin_hide_phone_data != '0')
			{
			if ($DB > 0) {$DBdata .= "HIDEPHONEDATA|$row[1]|$LOGadmin_hide_phone_data|   ";}
			$phone_temp = $row[1];
			if (strlen($phone_temp) > 0)
				{
				if ($LOGadmin_hide_phone_data == '4_DIGITS')
					{$row[1] = str_repeat("X", (strlen($phone_temp) - 4)) . substr($phone_temp,-4,4);}
				elseif ($LOGadmin_hide_phone_data == '3_DIGITS')
					{$row[1] = str_repeat("X", (strlen($phone_temp) - 3)) . substr($phone_temp,-3,3);}
				elseif ($LOGadmin_hide_phone_data == '2_DIGITS')
					{$row[1] = str_repeat("X", (strlen($phone_temp) - 2)) . substr($phone_temp,-2,2);}
				else
					{$row[1] = preg_replace("/./",'X',$phone_temp);}
				}
			}
		if ($LOGadmin_hide_lead_data != '0')
			{
			if ($DB > 0) {$DBdata .= "HIDELEADDATA|$row[2]|$LOGadmin_hide_lead_data|   ";}
			if (strlen($row[2]) > 0)
				{$state_temp = $row[2];   $row[2] = preg_replace("/./",'X',$state_temp);}
			if (strlen($row[11]) > 0)
				{$vlc_temp = $row[11];   $row[11] = preg_replace("/./",'X',$vlc_temp);}
			}

		$FMT_i =		sprintf("%-4s", $i);
		$lead_id =		sprintf("%-9s", $row[0]);
		$lead_id_ns = 		$row[0];
		$phone_number =	sprintf("%-11s", $row[1]);
		$state =		sprintf("%-5s", $row[2]);
		$status =		sprintf("%-6s", $row[3]);
		$count =		sprintf("%-5s", $row[4]);
		$gmt =			sprintf("%-6s", $row[5]);
		$hopper_id =	sprintf("%-10s", $row[6]);
		$alt_dial =		sprintf("%-5s", $row[7]);
		$list_id =		sprintf("%-10s", $row[8]);
		$list_id_ns =		$row[8];
		$priority =		sprintf("%-6s", $row[9]);
		$source =		sprintf("%-5s", $row[10]);
		$vendor_lead_code =	sprintf("%-20s", $row[11]);
		$phone_code =		sprintf("%-7s", $row[12]);
		$entry_epoch =		$row[13];
		$last_call_epoch =	$row[14];
		$source_id_TEXT =	"| ".sprintf("%-20s", $row[15])." ";
		$rank =			sprintf("%-6s", $row[16]);
		$owner =		sprintf("%-10s", $row[17]);

		$lead_age = intval(($STARTtime - $entry_epoch) / 86400);
		$lead_age =		sprintf("%-8s", $lead_age);

		$lead_offset = ($gmt - $SERVER_GMT);
		if (($lead_offset > 0) or ($lead_offset < 0))
			{$lead_offset = ($lead_offset * 3600);}
		$last_call_epoch = ($last_call_epoch + $lead_offset);
		$last_call_age = intval(($STARTtime - $last_call_epoch) / 3600);
		if ($DB > 0) {$DBdata .= "GMT: $lead_offset($gmt|$SERVER_GMT)|LC: $last_call_epoch($row[14])|$last_call_age|   ";}
		if ($last_call_age < 24)
			{
			$last_call_age_TEXT = $last_call_age." "._QXZ("HOURS",6);
			$last_call_age_TEXT = sprintf("%-9s", $last_call_age_TEXT);
			}
		else
			{
			$last_call_age = intval(($last_call_age) / 24);
			if ($last_call_age < 365)
				{
				$last_call_age_TEXT = $last_call_age." "._QXZ("DAYS",6);
				$last_call_age_TEXT = sprintf("%-9s", $last_call_age_TEXT);
				}
			else
				{
				$last_call_age = intval(($last_call_age) / 365);
				if ($last_call_age < 30)
					{
					$last_call_age_TEXT = $last_call_age." "._QXZ("YEARS",6);
					$last_call_age_TEXT = sprintf("%-9s", $last_call_age_TEXT);
					}
				else
					{
					$last_call_age_TEXT = _QXZ("NEVER",9);
					$last_call_age_TEXT = sprintf("%-9s", $last_call_age_TEXT);
					}
				}
			}

		if ($SSsource_id_display < 1)
			{$source_id_TEXT='';}

		if ($DB) {echo "| $FMT_i | $priority | <a href='./admin_modify_lead.php?lead_id=$lead_id_ns&archive_search=No&archive_log=0'>$lead_id</a> | <a href='./admin.php?ADD=311&list_id=$list_id_ns'>$list_id</a> | $phone_number  $phone_code || $state | $status | $count | $gmt | $rank | $alt_dial | $source | $vendor_lead_code | $lead_age | $last_call_age_TEXT | $hopper_id | $owner | $DBdata\n";}
		else {echo "| $FMT_i | $priority | <a href='./admin_modify_lead.php?lead_id=$lead_id_ns&archive_search=No&archive_log=0'>$lead_id</a> | <a href='./admin.php?ADD=311&list_id=$list_id_ns'>$list_id</a> | $phone_number | $phone_code | $state | $status | $count | $gmt | $rank | $alt_dial | $source | $vendor_lead_code $source_id_TEXT| $lead_age | $last_call_age_TEXT |\n";}

		$i++;
		}

	echo "+------+--------+-----------+------------+-------------+---------+-------+--------+-------+--------+--------+-------+-------+----------------------$SIDhead+----------+-----------+\n";
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

?>
</PRE>
<?php echo _QXZ("Sources"); ?>:<br>
A = <?php echo _QXZ("Auto-alt-dial"); ?><br>
C = <?php echo _QXZ("Scheduled Callbacks"); ?><br>
N = <?php echo _QXZ("Xth New lead order"); ?><br>
P = <?php echo _QXZ("Non-Agent API hopper load"); ?><br>
Q = <?php echo _QXZ("No-hopper queue insert"); ?><br>
R = <?php echo _QXZ("Recycled leads"); ?><br>
S = <?php echo _QXZ("Standard hopper load"); ?><br>
D = <?php echo _QXZ("Campaign Drop-Run load"); ?><br>



</TD></TR></TABLE>

</BODY></HTML>
