<?php
# admin_campaign_multi_alt.php
# 
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# this screen will control the campaign settings needed for alternate number 
# dialing using multiple leads with the same account number and different phone 
# numbers. the leads will use different owner values and this script will alter 
# the ranks for those leads as well as alter the special campaign filter and 
# change some campaign settings.
#
# changes:
# 110317-1219 - First Build
# 110406-1818 - Updated logging
# 120223-2335 - Removed logging of good login passwords if webroot writable is enabled
# 130610-1116 - Finalized changing of all ereg instances to preg
# 130621-2009 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130902-0755 - Changed to mysqli PHP functions
# 141001-2200 - Finalized adding QXZ translation to all admin files
# 141230-0024 - Added code for on-the-fly language translations display
# 150728-1046 - Added option for secondary sorting by vendor_lead_code, Issue #833
# 170409-1535 - Added IP List validation code
# 180329-1344 - Added screen colors
# 220222-0902 - Added allow_web_debug system setting
#

$admin_version = '2.14-12';
$build = '220222-0902';

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
if (isset($_GET["DB"]))							{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))				{$DB=$_POST["DB"];}
if (isset($_GET["action"]))						{$action=$_GET["action"];}
	elseif (isset($_POST["action"]))			{$action=$_POST["action"];}
if (isset($_GET["campaign_id"]))				{$campaign_id=$_GET["campaign_id"];}
	elseif (isset($_POST["campaign_id"]))		{$campaign_id=$_POST["campaign_id"];}
if (isset($_GET["lead_order_randomize"]))			{$lead_order_randomize=$_GET["lead_order_randomize"];}
	elseif (isset($_POST["lead_order_randomize"]))	{$lead_order_randomize=$_POST["lead_order_randomize"];}
if (isset($_GET["lead_order_secondary"]))			{$lead_order_secondary=$_GET["lead_order_secondary"];}
	elseif (isset($_POST["lead_order_secondary"]))	{$lead_order_secondary=$_POST["lead_order_secondary"];}
if (isset($_GET["SUBMIT"]))						{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))			{$SUBMIT=$_POST["SUBMIT"];}

if (strlen($action) < 2)
	{$action = 'BLANK';}
if (strlen($DB) < 1)
	{$DB=0;}
$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable,enable_languages,language_method,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {echo "$stmt\n";}
$ss_conf_ct = mysqli_num_rows($rslt);
if ($ss_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$webroot_writable =				$row[1];
	$SSenable_languages =			$row[2];
	$SSlanguage_method =			$row[3];
	$SSallow_web_debug =			$row[4];
	}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$lead_order_randomize = preg_replace('/[^-_0-9a-zA-Z]/','',$lead_order_randomize);
$lead_order_secondary = preg_replace('/[^-_0-9a-zA-Z]/','',$lead_order_secondary);
$action = preg_replace('/[^-_0-9a-zA-Z]/','',$action);
$SUBMIT = preg_replace('/[^-_0-9a-zA-Z]/','',$SUBMIT);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/','',$PHP_AUTH_PW);
	$campaign_id = preg_replace('/[^-_0-9a-zA-Z]/','',$campaign_id);
	}	# end of non_latin
else
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$campaign_id = preg_replace('/[^-_0-9\p{L}]/u', '', $campaign_id);
	}

$STARTtime = date("U");
$TODAY = date("Y-m-d");
$NOW_TIME = date("Y-m-d H:i:s");
$date = date("r");
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");
$user = $PHP_AUTH_USER;

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
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'',1,0);
if ( ($auth_message == 'GOOD') or ($auth_message == '2FA') )
	{
	$auth=1;
	if ($auth_message == '2FA')
		{
		header ("Content-type: text/html; charset=utf-8");
		echo _QXZ("Your session is expired").". <a href=\"admin.php\">"._QXZ("Click here to log in")."</a>.\n";
		exit;
		}
	}

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

$stmt="SELECT full_name,modify_campaigns,user_level from vicidial_users where user='$PHP_AUTH_USER';";
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$LOGfullname =				$row[0];
$LOGmodify_campaigns =		$row[1];
$LOGuser_level =			$row[2];

if ($LOGmodify_campaigns < 1)
	{
	Header ("Content-type: text/html; charset=utf-8");
	echo _QXZ("You do not have permissions to modify campaigns")."\n";
	exit;
	}

$stmt="SELECT count(*) from vicidial_campaigns where campaign_id='$campaign_id' and auto_alt_dial='MULTI_LEAD';";
if ($DB) {echo "|$stmt|\n";}
$rslt=mysql_to_mysqli($stmt, $link);
$row=mysqli_fetch_row($rslt);
$camp_multi=$row[0];

?>
<html>
<head>

<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=utf-8">
<title><?php echo _QXZ("ADMINISTRATION: Campaign Multi-Alt-Leads"); ?>
<?php 


##### BEGIN Set variables to make header show properly #####
$ADD =					'31';
$SUB =					'26';
$hh =					'campaigns';
$sh =					'detail';
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
$campaigns_color =		'#FFFF99';
$campaigns_font =		'BLACK';
$campaigns_color =		'#E6E6E6';
$subcamp_color =	'#C6C6C6';
##### END Set variables to make header show properly #####

require("admin_header.php");

if ($camp_multi < 1)
	{
	echo _QXZ("This campaign is not set to Auto-Alt-Dial MULTI_LEAD")."\n";
	exit;
	}

if ($DB > 0)
	{
	echo "$DB,$action,$campaign_id,$lead_order_randomize,$lead_order_secondary\n<BR>";
	}

$stmt="SELECT list_id,active from vicidial_lists where campaign_id='$campaign_id' order by list_id;";
$rslt=mysql_to_mysqli($stmt, $link);
$lists_to_print = mysqli_num_rows($rslt);
$o=0;
while ($lists_to_print > $o)
	{
	$row=mysqli_fetch_row($rslt);
	if (preg_match('/Y/',$row[1]))
		{
		$active_lists++;
		$camp_lists .= "'$row[0]',";
		}
	else
		{
		$inactive_lists++;
		$camp_lists .= "'$row[0]',";
		}
	$o++;
	}
$camp_lists = preg_replace('/.$/i','',$camp_lists);;




################################################################################
##### BEGIN multi-lead alt dial submit form
if ($action == "ALT_MULTI_SUBMIT")
	{
	$settings_updated=0;
	if ( (strlen($lead_order_randomize) > 0) and (strlen($lead_order_secondary) > 0) )
		{
		$stmt="SELECT distinct owner from vicidial_list where list_id IN($camp_lists) limit 1000;";
		$rslt=mysql_to_mysqli($stmt, $link);
		if ($DB) {echo "$stmt\n";}
		$do_values_ct = mysqli_num_rows($rslt);
		$o=0;
		while ($do_values_ct > $o)
			{
			$row=mysqli_fetch_row($rslt);
			$owners[$o] =	$row[0];
			$o++;
			}

		$o=0;
		$update_stmts='';
		$new_filter_sql='';
		while ($do_values_ct > $o)
			{
			$owner_check='';
			$owner_rank='';
			$owner_raw =		$owners[$o];
			$owner = preg_replace('/ |\n|\r|\t/','',$owner_raw);
			if (isset($_GET["$campaign_id$US$owner"]))				{$owner_check=$_GET["$campaign_id$US$owner"];}
				elseif (isset($_POST["$campaign_id$US$owner"]))		{$owner_check=$_POST["$campaign_id$US$owner"];}
			if (isset($_GET["rank_$campaign_id$US$owner"]))				{$owner_rank=$_GET["rank_$campaign_id$US$owner"];}
				elseif (isset($_POST["rank_$campaign_id$US$owner"]))	{$owner_rank=$_POST["rank_$campaign_id$US$owner"];}
			$owner_check = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$owner_check);
			$owner_rank = preg_replace("/\<|\>|\'|\"|\\\\|;/","",$owner_rank);

			if ($owner_check=='YES')
				{$new_filter_sql .= "'$owner_raw',";}

			$stmt = "UPDATE vicidial_list SET rank='$owner_rank' where list_id IN($camp_lists) and owner='$owner_raw';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$affected_rows = mysqli_affected_rows($link);
			if ($DB > 0) {echo "OWNER: $o|$campaign_id|$owner|$owner_check|$owner_rank|$affected_rows\n<BR>";}
			$update_stmts .= "|$affected_rows|$owner_check|$stmt";
			$o++;
			}

		$filter_stmt="INSERT IGNORE INTO vicidial_lead_filters SET lead_filter_id='ML$campaign_id',lead_filter_name='DO NOT DELETE MULTI_$campaign_id',lead_filter_comments=NOW(),lead_filter_sql=\"owner IN($new_filter_sql'99827446572348452235')\" ON DUPLICATE KEY UPDATE lead_filter_comments=NOW(),lead_filter_sql=\"owner IN($new_filter_sql'99827446572348452235')\";";
		$rslt=mysql_to_mysqli($filter_stmt, $link);
		$affected_rows = mysqli_affected_rows($link);
		if ($DB > 0) {echo "$affected_rows|$filter_stmt\n<BR>";}

		$stmt = "UPDATE vicidial_campaigns SET lead_order_randomize='$lead_order_randomize',lead_order_secondary='$lead_order_secondary',lead_filter_id='ML$campaign_id',lead_order='DOWN RANK',campaign_changedate=NOW() where campaign_id='$campaign_id';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$affected_rows = mysqli_affected_rows($link);
		if ($DB > 0) {echo "$campaign_id|$lead_order_randomize|$lead_order_secondary|ML$campaign_id|DOWN RANK|$affected_rows|$stmt\n<BR>";}

		if ($affected_rows > 0)
			{
			$phone_alias_entry[$p] .= "$login,";

			### LOG INSERTION Admin Log Table ###
			$SQL_log = "$stmt|$filter_stmt|$update_stmts|";
			$SQL_log = preg_replace('/;/', '', $SQL_log);
			$SQL_log = addslashes($SQL_log);
			$stmt="INSERT INTO vicidial_admin_log set event_date='$NOW_TIME', user='$PHP_AUTH_USER', ip_address='$ip', event_section='CAMPAIGNS', event_type='MODIFY', record_id='$campaign_id', event_code='MODIFY CAMPAIGN MULTI LEAD', event_sql=\"$SQL_log\", event_notes='';";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($DB > 0) {echo "$campaign_id|$stmt\n<BR>";}

			echo "<BR><b>"._QXZ("MULTI-LEAD ALT DIAL SETTINGS UPDATED")."</b><BR><BR>";

			}
		else
			{echo _QXZ("ERROR: Problem updating campaign").":  $affected_rows|$stmt\n<BR>";}

		}
	else
		{echo _QXZ("ERROR: problem with data").": $campaign_id\n<BR>";}

	$action='BLANK';
	}
### END multi-lead alt dial submit form





################################################################################
##### BEGIN multi-lead alt dial control form
if ($action == "BLANK")
	{
	$stmt = "SELECT lead_order_randomize,lead_order_secondary FROM vicidial_campaigns where campaign_id='$campaign_id';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$vc_values_ct = mysqli_num_rows($rslt);
	if ($vc_values_ct > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$lead_order_randomize =		$row[0];
		$lead_order_secondary =		$row[1];
		}

	$lead_filter_sql='';
	$lead_filter_comments='';
	$stmt = "SELECT lead_filter_sql,lead_filter_comments FROM vicidial_lead_filters where lead_filter_id='ML$campaign_id' and lead_filter_name='DO NOT DELETE MULTI_$campaign_id';";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$vlf_values_ct = mysqli_num_rows($rslt);
	if ($vlf_values_ct > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$lead_filter_sql =			$row[0];
		$lead_filter_comments =		$row[1];
		if ($DB) {echo "$lead_filter_sql|$lead_filter_comments";}
		}

	echo "<TABLE><TR><TD>\n";
	echo "<FONT FACE=\"ARIAL,HELVETICA\" COLOR=BLACK SIZE=2>";
	echo "<br>"._QXZ("Multi-Lead Auto Alt Dialing Settings Form")."<form action=$PHP_SELF method=POST>\n";
	echo "<input type=hidden name=DB value=\"$DB\">\n";
	echo "<input type=hidden name=campaign_id value=\"$campaign_id\">\n";
	echo "<input type=hidden name=action value=ALT_MULTI_SUBMIT>\n";
	echo "<center><TABLE width=$section_width cellspacing=3>\n";
	echo "<tr><td align=center colspan=2>\n";


	echo "<br><b>"._QXZ("PHONE TYPES WITHIN THE LISTS FOR THIS CAMPAIGN").": <a href=\"./admin.php?ADD=31&campaign_id=$campaign_id\">$campaign_id</a></b>\n";
	echo "<TABLE width=700 cellspacing=3>\n";
	echo "<tr><td>"._QXZ("PHONE NUMBER TYPE")."</td><td>"._QXZ("CALLED")."</td><td>"._QXZ("NOT CALLED")."</td><td>"._QXZ("SELECTED")."</td><td>"._QXZ("OLD RANK")."</td><td>"._QXZ("NEW RANK")."</td></tr>\n";

	$leads_in_list = 0;
	$leads_in_list_N = 0;
	$leads_in_list_Y = 0;
	$stmt="SELECT owner,called_since_last_reset,count(*),rank from vicidial_list where list_id IN($camp_lists) group by owner,rank,called_since_last_reset order by rank,owner,called_since_last_reset limit 1000;";
	if ($DB) {echo "$stmt\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$types_to_print = mysqli_num_rows($rslt);

	$o=0;
	$lead_list['count'] = 0;
	$lead_list['Y_count'] = 0;
	$lead_list['N_count'] = 0;
	while ($types_to_print > $o) 
		{
		$rowx=mysqli_fetch_row($rslt);
		
		$lead_list['count'] = ($lead_list['count'] + $rowx[2]);
		if ($rowx[1] == 'N') 
			{
			$since_reset = 'N';
			$since_resetX = 'Y';
			}
		else 
			{
			$since_reset = 'Y';
			$since_resetX = 'N';
			} 
		$lead_list[$since_reset][$rowx[0]] = ($lead_list[$since_reset][$rowx[0]] + $rowx[2]);
		$lead_list[$since_reset.'_count'] = ($lead_list[$since_reset.'_count'] + $rowx[2]);
		#If opposite side is not set, it may not in the future so give it a value of zero
		if (!isset($lead_list[$since_resetX][$rowx[0]])) 
			{
			$lead_list[$since_resetX][$rowx[0]]=0;
			}
		$o++;
		}
 
	$o=0;
	if ($lead_list['count'] > 0)
		{
#		while (list($owner,) = each($lead_list[$since_reset]))
		foreach($lead_list[$since_reset] as $owner => $blank)
			{
			$owner_var = preg_replace('/ |\n|\r|\t/','',$owner);
			if (preg_match('/1$|3$|5$|7$|9$/i', $o))
				{$bgcolor='bgcolor="#'. $SSstd_row2_background .'"';} 
			else
				{$bgcolor='bgcolor="#' . $SSstd_row1_background . '"';}

			$o++;
			echo "<tr $bgcolor>";
			echo "<td><font size=1>$owner</td>";
			echo "<td><font size=1>".$lead_list['Y'][$owner]."</td>";
			echo "<td><font size=1>".$lead_list['N'][$owner]." </td>";
			echo "<td><font size=1>";
			if (preg_match("/\\\'$owner\\\'|'$owner'/",$lead_filter_sql))
				{echo "<input type=checkbox name=\"$campaign_id$US$owner_var\" id=\"$campaign_id$US$owner_var\" value=\"YES\" CHECKED>";}
			else
				{echo "<input type=checkbox name=\"$campaign_id$US$owner_var\" id=\"$campaign_id$US$owner_var\" value=\"YES\">";}
			echo "</td>";
			echo "<td><font size=1>$o</td>";
			echo "<td><font size=1><input type=text size=3 maxlength=3 name=\"rank_$campaign_id$US$owner_var\" id=\"rank_$campaign_id$US$owner_var\" value=\"$o\"></td>";
			echo "</tr>\n";
			}
		}

	echo "<tr><td colspan=2><font size=1>"._QXZ("SUBTOTALS")."</td><td><font size=1>$lead_list[Y_count]</td><td><font size=1>$lead_list[N_count]</td></tr>\n";
	echo "<tr bgcolor=\"#$SSstd_row1_background\"><td><font size=1>"._QXZ("TOTAL")."</td><td colspan=3 align=center><font size=1>$lead_list[count]</td><td align=center><font size=1>$o</td></tr>\n";

	echo "</table></center><br>\n";
	unset($lead_list);				


	echo "</td></tr>\n";

	echo "<tr bgcolor=#$SSstd_row1_background><td align=right>"._QXZ("List Order Randomize").": </td><td align=left><select size=1 name=lead_order_randomize><option value='Y'>"._QXZ("Y")."</option><option value='N'>"._QXZ("N")."</option><option value='$lead_order_randomize' SELECTED>"._QXZ("$lead_order_randomize")."</option></select></td></tr>\n";
	echo "<tr bgcolor=#$SSstd_row1_background><td align=right>"._QXZ("List Order Secondary").": </td><td align=left><select size=1 name=lead_order_secondary><option value='LEAD_ASCEND'>"._QXZ("LEAD_ASCEND")."</option><option value='LEAD_DESCEND'>"._QXZ("LEAD_DESCEND")."</option><option value='CALLTIME_ASCEND'>"._QXZ("CALLTIME_ASCEND")."</option><option value='CALLTIME_DESCEND'>"._QXZ("CALLTIME_DESCEND")."</option><option value='VENDOR_ASCEND'>"._QXZ("VENDOR_ASCEND")."</option><option value='VENDOR_DESCEND'>"._QXZ("VENDOR_DESCEND")."</option><option value='$lead_order_secondary' SELECTED>"._QXZ("$lead_order_secondary")."</option></select></td></tr>\n";

	echo "<tr bgcolor=#$SSstd_row1_background><td align=center colspan=2><input style='background-color:#$SSbutton_color' type=submit name=SUBMIT value='"._QXZ("SUBMIT")."'></td></tr>\n";
	echo "</TABLE></center>\n";
	echo "</TD></TR></TABLE>\n";
	}
### END multi-lead alt dial control form





$ENDtime = date("U");
$RUNtime = ($ENDtime - $STARTtime);
echo "\n\n\n<br><br><br>\n<font size=1> "._QXZ("runtime").": $RUNtime "._QXZ("seconds")." &nbsp; &nbsp; &nbsp; &nbsp; "._QXZ("Version").": $admin_version &nbsp; &nbsp; "._QXZ("Build").": $build</font>";

?>

</body>
</html>
