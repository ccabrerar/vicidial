<?php
# agent_timeoff_interface.php
# 
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>, Joe Johnson <freewermadmin@gmail.com>    LICENSE: AGPLv2
#
# Admin utility for applying hours off for agents, to be used in reports
#
# 240415-1730 - First build
# 240801-1130 - Code updates for PHP8 compatibility
#

$startMS = microtime();
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");

require("dbconnect_mysqli.php");
require("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
$QUERY_STRING = getenv("QUERY_STRING");
$startMS = microtime();

if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["action"]))					{$action=$_GET["action"];}
	elseif (isset($_POST["action"]))		{$action=$_POST["action"];}
if (isset($_GET["add_user"]))				{$add_user=$_GET["add_user"];}
	elseif (isset($_POST["add_user"]))		{$add_user=$_POST["add_user"];}
if (isset($_GET["submit_time_off"]))			{$submit_time_off=$_GET["submit_time_off"];}
	elseif (isset($_POST["submit_time_off"]))	{$submit_time_off=$_POST["submit_time_off"];}
if (isset($_GET["month_to_display"]))			{$month_to_display=$_GET["month_to_display"];}
	elseif (isset($_POST["month_to_display"]))	{$month_to_display=$_POST["month_to_display"];}
if (isset($_GET["rpt_month"]))				{$rpt_month=$_GET["rpt_month"];}
	elseif (isset($_POST["rpt_month"]))		{$rpt_month=$_POST["rpt_month"];}
if (isset($_GET["update_month"]))			{$update_month=$_GET["update_month"];}
	elseif (isset($_POST["update_month"]))	{$update_month=$_POST["update_month"];}
if (isset($_GET["new_agent"]))				{$new_agent=$_GET["new_agent"];}
	elseif (isset($_POST["new_agent"]))		{$new_agent=$_POST["new_agent"];}
if (isset($_GET["new_timeoff_type"]))			{$new_timeoff_type=$_GET["new_timeoff_type"];}
	elseif (isset($_POST["new_timeoff_type"]))	{$new_timeoff_type=$_POST["new_timeoff_type"];}
if (isset($_GET["new_hours"]))				{$new_hours=$_GET["new_hours"];}
	elseif (isset($_POST["new_hours"]))		{$new_hours=$_POST["new_hours"];}
if (isset($_GET["new_month"]))				{$new_month=$_GET["new_month"];}
	elseif (isset($_POST["new_month"]))		{$new_month=$_POST["new_month"];}
if (isset($_GET["to_types"]))				{$to_types=$_GET["to_types"];}
	elseif (isset($_POST["to_types"]))		{$to_types=$_POST["to_types"];}
if (isset($_GET["update_user"]))			{$update_user=$_GET["update_user"];}
	elseif (isset($_POST["update_user"]))	{$update_user=$_POST["update_user"];}
if (isset($_GET["download_month"]))				{$download_month=$_GET["download_month"];}
	elseif (isset($_POST["download_month"]))	{$download_month=$_POST["download_month"];}

if (file_exists('options.php'))
	{
	require('options.php');
	}

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,outbound_autodial_active,slave_db_server,reports_use_slave_db,user_territories_active,webroot_writable,allow_emails,level_8_disable_add,enable_languages,language_method,log_recording_access,admin_screen_colors,mute_recordings,sounds_web_server,sounds_web_directory,admin_web_directory,allow_web_debug FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
#if ($DB) {$MAIN.="$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$SSoutbound_autodial_active =	$row[1];
	$slave_db_server =				$row[2];
	$reports_use_slave_db =			$row[3];
	$user_territories_active =		$row[4];
	$webroot_writable =				$row[5];
	$allow_emails =					$row[6];
	$SSlevel_8_disable_add =		$row[7];
	$SSenable_languages =			$row[8];
	$SSlanguage_method =			$row[9];
	$log_recording_access =			$row[10];
	$SSadmin_screen_colors =		$row[11];
	$SSmute_recordings =			$row[12];
	$sounds_web_server =			$row[13];
	$sounds_web_directory =			$row[14];
	$admin_web_dir =				$row[15];
	$SSallow_web_debug =			$row[16];
	}

if (!preg_match('/^http/', $sounds_web_server)) {$http_prefix="http://";} else {$http_prefix="";}
if ($SSallow_web_debug < 1) {$DB=0;}
##### END SETTINGS LOOKUP #####
###########################################

$DB=preg_replace("/[^0-9a-zA-Z]/","",$DB);
if (preg_match('/^\-/', $new_hours)) {$hoff=-1;} else {$hoff=1;}
$new_hours = preg_replace('/[^0-9\.]/', '', $new_hours);
$month_to_display=preg_replace("/[^-0-9]/",'',$month_to_display);
$download_month=preg_replace("/[^-0-9]/",'',$download_month);
$update_month=preg_replace("/[^-0-9]/",'',$update_month);
$new_month=preg_replace("/[^-0-9]/",'',$new_month);
$rpt_month=preg_replace("/[^-0-9]/",'',$rpt_month);
if(!is_array($to_types)) {$to_types=array();}

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$action = preg_replace('/[^-_0-9a-zA-Z]/', '', $action);
	$add_user=preg_replace("/[^-_0-9a-zA-Z]/",'',$add_user);
	$update_user=preg_replace("/[^-_0-9a-zA-Z]/",'',$update_user);
	$user=preg_replace("/[^-_0-9a-zA-Z]/",'',$user);
	$new_agent=preg_replace("/[^-_0-9a-zA-Z]/",'',$new_agent);
	$submit_time_off=preg_replace("/[^-_0-9a-zA-Z]/",'',$submit_time_off);
	$new_timeoff_type=preg_replace("/[^_0-9a-zA-Z]/",'',$new_timeoff_type);
	$to_types=preg_replace("/[^_\,\.\-0-9a-zA-Z]/",'',$to_types);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^\-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^\-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$action = preg_replace('/[^\-_0-9\p{L}]/u', '', $action);
	$add_user = preg_replace('/[^-_0-9\p{L}]/u','',$add_user);
	$update_user = preg_replace('/[^-_0-9\p{L}]/u','',$update_user);
	$user = preg_replace('/[^-_0-9\p{L}]/u','',$user);
	$new_agent = preg_replace('/[^-_0-9\p{L}]/u','',$new_agent);
	$submit_time_off = preg_replace('/[^-_0-9\p{L}]/u','',$submit_time_off);
	$new_timeoff_type=preg_replace("/[^_0-9\p{L}]/u",'',$new_timeoff_type);
	$to_types=preg_replace("/[^_\,\.\-0-9\p{L}]/u",'',$to_types);
	}

$user_auth=0;
$auth=0;
$reports_auth=0;
$qc_auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'QC',1,0);
if ($auth_message == 'GOOD')
	{$user_auth=1;}
if ($user_auth > 0)
	{
	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 7 and api_only_user != '1';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$auth=$row[0];

	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 6 and view_reports='1' and api_only_user != '1';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$reports_auth=$row[0];

	$stmt="SELECT count(*) user_level,modify_users from vicidial_users where user='$PHP_AUTH_USER' and modify_users='1';";
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$users_auth=$row[0];

	$stmt="select user_level, user_group from vicidial_users where user='$PHP_AUTH_USER'";
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$user_level=$row[0];
	$user_group=$row[1];

	$reports_only_user=0;
	$qc_only_user=0;
	if ( ($reports_auth > 0) and ($auth < 1) )
		{
		$ADD=999999;
		$reports_only_user=1;
		}
	if ( ($reports_auth < 1) and ($auth < 1) )
		{
		if ( ($ADD != '881') and ($ADD != '100000000000000') )
			{
            $ADD=100000000000000;
			}
		$qc_only_user=1;
		}
	if ( ($reports_auth < 1) and ($auth < 1) )
		{
		$VDdisplayMESSAGE = _QXZ("You do not have permission to be here");
		Header ("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
		}
	if ($users_auth < 1)
		{
		$VDdisplayMESSAGE = _QXZ("You do not have permissions to modify users");
		Header ("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
		}
	if ( (strlen($VUuser_admin_redirect_url) > 5) and ($SSuser_admin_redirect > 0) )
		{
		Header('Location: '.$VUuser_admin_redirect_url);
		echo"<TITLE>"._QXZ("Admin Redirect")."</TITLE>\n";
		echo"<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=iso-8859-1\">\n";
		echo"<META HTTP-EQUIV=Refresh CONTENT=\"0; URL=$VUuser_admin_redirect_url\">\n";
		echo"</HEAD>\n";
		echo"<BODY BGCOLOR=#FFFFFF marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";
		echo"<a href=\"$VUuser_admin_redirect_url\">"._QXZ("click here to continue").". . .</a>\n";
		exit;
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

$container_stmt="select container_entry from vicidial_settings_containers where container_id='VICIDIAL_TIMEOFF_SETTINGS'";
$container_rslt=mysql_to_mysqli($container_stmt, $link);
$timeoff_types=array();
$time_column_SQL="";
$user_filter_SQL="";
if (mysqli_num_rows($container_rslt)==0) {echo "ERROR: no VICIDIAL_TIMEOFF_SETTINGS settings container defined"; exit;}
while ($container_row=mysqli_fetch_row($container_rslt))
	{	
	$to_settings=explode("\n", $container_row[0]);
	for ($i=0; $i<count($to_settings); $i++)
		{
		$to_settings[$i]=trim($to_settings[$i]);
		if (!preg_match('/^#/', $to_settings[$i]) && preg_match('/\=\>/', $to_settings[$i]))
			{
			$setting=explode("=>", $to_settings[$i]);
			if (preg_match('/^display_all_agents/', $setting[0]))
				{
				$display_all_agents=$setting[1];
				}
			if (preg_match('/^timeoff_types/', $setting[0]))
				{
				$setting[1]=preg_replace("/[^_0-9a-zA-Z,]/", "", $setting[1]);
				$timeoff_types=explode(",", $setting[1]);
				}
			if (preg_match('/^user_filter_SQL/', $setting[0]))
				{
				$user_filter_SQL=preg_replace("/[^\s\'\(\)_0-9a-zA-Z,]/", "", $setting[1]);
				}
			if (preg_match('/^sort_SQL/', $setting[0]))
				{
				$sort_SQL=preg_replace("/[^\s\'\(\)_0-9a-zA-Z,]/", "", $setting[1]);
				}
			if (preg_match('/^custom_download/', $setting[0]))
				{
				$custom_download=preg_replace("/[^0-9]/", "", $setting[1]);
				}
			}
		}
	}	
if (!$sort_SQL) {$sort_SQL="full_name asc, user asc";}

$vug_stmt="select admin_viewable_groups from vicidial_user_groups where user_group='$user_group'";
$vug_rslt=mysql_to_mysqli($vug_stmt, $link);
while($vug_row=mysqli_fetch_row($vug_rslt))
	{
	$admin_viewable_groups=trim($vug_row[0]);
	if(!preg_match('/\-\-\-ALL\-\-\-/', $admin_viewable_groups))
		{	
		$admin_viewable_groups_andSQL="and user_group in ('".preg_replace('/\s/', "', '", $admin_viewable_groups)."')";
		$admin_viewable_groups_whereSQL="where user_group in ('".preg_replace('/\s/', "', '", $admin_viewable_groups)."')";
		}
	else
		{
		$admin_viewable_groups_andSQL="";
		$admin_viewable_groups_whereSQL="";
		}
	}	

$all_users_stmt="select user, user_location, full_name from vicidial_users";
$all_users_rslt=mysql_to_mysqli($all_users_stmt, $link);
$master_users=array();
while($au_row=mysqli_fetch_array($all_users_rslt))
	{
	$master_users["$au_row[user]"]="$au_row[full_name]";
	}

for ($t=0; $t<count($timeoff_types); $t++)
	{
	$time_column_SQL.=", sum(if(timeoff_type='".$timeoff_types[$t]."', hours, 0)) as ".$timeoff_types[$t];
	}

if ($submit_time_off=="SUBMIT" && $new_agent && $new_timeoff_type && $new_hours)
	{
	$ins_stmt="INSERT into vicidial_timeoff_log(user, timeoff_month, timeoff_type, hours, entry_date, entered_by, last_modified_by) VALUES('$new_agent', '$new_month', '$new_timeoff_type', '".($new_hours*$hoff)."', now(), '$PHP_AUTH_USER', '$PHP_AUTH_USER') on DUPLICATE KEY UPDATE hours=hours+".($new_hours*$hoff).", last_modified_by='$PHP_AUTH_USER'";
	$ins_rslt=mysql_to_mysqli($ins_stmt, $link);	

	### LOG INSERTION Admin Log Table ###
	$SQL_log = "$ins_stmt|";
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = addslashes($SQL_log);
	$stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='USERS', event_type='MODIFY', record_id='$new_agent', event_code='USER TIME OFF LOGGED', event_sql=\"$SQL_log\", event_notes='USER: $new_agent TIME OFF ADDED';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	}

if ($action=="update_user" && $update_user && count($to_types)>0 && $update_month)
	{
	$span_update_str="";
	for ($i=0; $i<count($to_types); $i++)
		{
		$data=explode(",", $to_types[$i]);

		if ($non_latin < 1)
			{
			$data[0]=preg_replace("/[^_0-9a-zA-Z]/",'',$data[0]);
			}
		else
			{
			$data[0]=preg_replace("/[^_0-9\p{L}]/u",'',$data[0]);
			}

		if (preg_match('/^\-/', $data[1])) {$hoff=-1;} else {$hoff=1;}
		$data[1] = preg_replace('/[^0-9\.]/', '', $data[1]);

		# $upd_stmt="update vicidial_timeoff_log set hours=".($data[1]*$hoff).", last_modified_by='$PHP_AUTH_USER' where user='$update_user' and timeoff_type='$data[0]'";
		$upd_stmt="INSERT into vicidial_timeoff_log(user, timeoff_month, timeoff_type, hours, entry_date, entered_by, last_modified_by) VALUES('$update_user', '$update_month', '$data[0]', '".($data[1]*$hoff)."', now(), '$PHP_AUTH_USER', '$PHP_AUTH_USER') on DUPLICATE KEY UPDATE hours=".($data[1]*$hoff).", last_modified_by='$PHP_AUTH_USER'";
		$upd_rslt=mysql_to_mysqli($upd_stmt, $link);
		if (mysqli_affected_rows($link)>0)
			{
			$span_id="span_".$update_user."_".$data[0];
			$span_update_str.="\n$span_id|".($data[1]*$hoff);
			}

		### LOG INSERTION Admin Log Table ###
		$SQL_log = "$upd_stmt|";
		$SQL_log = preg_replace('/;/', '', $SQL_log);
		$SQL_log = addslashes($SQL_log);
		$stmt="INSERT INTO vicidial_admin_log set event_date='$SQLdate', user='$PHP_AUTH_USER', ip_address='$ip', event_section='USERS', event_type='MODIFY', record_id='$update_user', event_code='USER TIME OFF LOGGED', event_sql=\"$SQL_log\", event_notes='USER: $new_agent TIME OFF UPDATED';";
		if ($DB) {echo "|$stmt|\n";}
		$rslt=mysql_to_mysqli($stmt, $link);		
		}
	echo $PHP_AUTH_USER." - ".$master_users["$PHP_AUTH_USER"]."\n".date("Y-m-d H:i:s").$span_update_str;
	exit;
	}

if ($action=="display_past_month" && $month_to_display)
	{
	echo "<table width='100%' border=0 cellpadding=3 cellspacing=0>\n";

	echo "<TR BGCOLOR=#E6E6E6>\n";
	echo "\t<TD ALIGN='LEFT' colspan='".(4+(count($timeoff_types)<1 ? 1 : count($timeoff_types)))."'><font class='standard_bold'>CURRENT MONTH DISPLAYED: ";
	echo "<select name='timeoff_month_to_display' onChange='LoadPTOMonth(this.value)'>\n";

	if ($month_to_display)
		{
		$download_month=$month_to_display;
		$default_month_text=date("M-y", strtotime(date("$month_to_display-01")));
		echo "<option value='$month_to_display' selected>$default_month_text</option>\n";
		echo "<option value=''>-------</option>\n";
		}
	else
		{
		$month_to_display=date("Y-m");
		$download_month=date("Y-m");
		$default_month_text=date("M-y", strtotime(date("Y-m-01")));
		}	

	for ($i = 0; $i < 12; $i++) 
		{
		$month_no=date("Y-m", strtotime( date( 'Y-m-01' )." -$i months"));
		$month_text=date("M-y", strtotime( date( 'Y-m-01' )." -$i months"));
		echo "<option value='$month_no'>$month_text</option>\n";
		}

	echo "<select>";
	echo "</font></TD>\n";
	echo "\t<TD ALIGN='RIGHT' colspan='2'><font class='standard_bold'><font class='standard_bold'><a href='agent_timeoff_interface.php?action=download_csv&download_month=".$download_month."'>[DOWNLOAD]</a></font></TD>\n";
	echo "</TR>\n";


	
	echo "<TR BGCOLOR=#000000>\n";
	echo "<TH rowspan='2'><font class='standard_bold' color='#FFFFFF'>Agent</font></TH>";
	echo "<TH rowspan='2'><font class='standard_bold' color='#FFFFFF'>Location</font></TH>";
	echo "<TH rowspan='2'><font class='standard_bold' color='#FFFFFF'>Month</font></TH>";
	echo "<TH colspan='".(count($timeoff_types)<1 ? 1 : count($timeoff_types))."'><font class='standard_bold' color='#FFFFFF'>Planned Hours</font></TH>";
	echo "<TH rowspan='2'><font class='standard_bold' color='#FFFFFF'>Saved/Updated Date</font></TH>";
	echo "<TH rowspan='2'><font class='standard_bold' color='#FFFFFF'>Updated By</font></TH>";
	echo "<TH rowspan='2'><font class='standard_bold' color='#FFFFFF'>&nbsp;</font></TH>";
	echo "</TR>\n";
	if (count($timeoff_types)<1)
		{
		echo "<TR BGCOLOR=#000000>\n";
		echo "<TH>&nbsp;</TH>";
		echo "</TR>\n";
		}
	else
		{
		echo "<TR BGCOLOR=#000000>\n";
		for ($t=0; $t<count($timeoff_types); $t++)
			{
			echo "<TH><font class='standard_bold' color='#FFFFFF'>".$timeoff_types[$t]."</font></TH>";
			}
		echo "</TR>\n";
		}

	$i=0;
	$btn_class="blue_btn";
	$agent_dropdown="";

	$stmt="select user, user_location, full_name from vicidial_users where active='Y' $admin_viewable_groups_andSQL $user_filter_SQL order by $sort_SQL";
	$rslt=mysql_to_mysqli($stmt, $link);
	while($row=mysqli_fetch_array($rslt))
		{
		$agent_dropdown.="<option value='$row[user]'>$row[user] - $row[full_name]</option>\n";
	
		$location=$row["user_location"];

		$user_stmt="select DATE_FORMAT(concat(timeoff_month, '-01'), '%b-%y') as display_month, max(modify_date) as last_modify_date".$time_column_SQL." From vicidial_timeoff_log where timeoff_month='$month_to_display' AND user='$row[user]'";
		$user_rslt=mysql_to_mysqli($user_stmt, $link);
		$user_row=mysqli_fetch_array($user_rslt);
		if ($user_row["display_month"])
			{
			$i++;
			if ($i%2==0)
				{
				$bgcolor="FFFFFF";
				} 
			else 
				{
				$bgcolor="E6E6E6";
				}

			$lmb_stmt="select last_modified_by from vicidial_timeoff_log where user='$row[user]' and modify_date='$user_row[last_modify_date]' limit 1";
			$lmb_rslt=mysql_to_mysqli($lmb_stmt, $link);
			$lmb_row=mysqli_fetch_row($lmb_rslt);

			echo "<TR BGCOLOR=#".$bgcolor.">\n";
			echo "<TD align='left'><font class='standard'>$row[user] - $row[full_name]</font></TD>\n";
			echo "<TD align='center'><font class='standard'>$location</font></TD>\n";
			echo "<TD align='center'><font class='standard'>$user_row[display_month]</font></TD>\n";
			for ($t=0; $t<count($timeoff_types); $t++)
				{
				echo "<TD align='center'><font class='standard'><span id='span_".$row["user"]."_".$timeoff_types[$t]."'>".($user_row["$timeoff_types[$t]"]+0)."</span></font></TD>\n";
				}
			echo "<TD align='center'><font class='standard'><span id='".$row["user"]."_lmd'>$user_row[last_modify_date]</span></font></TD>\n";
			echo "<TD align='center'><font class='standard'><span id='".$row["user"]."_lmb'>".$lmb_row[0]." - ".$master_users["$lmb_row[0]"]."</span></font></TD>\n";
			echo "<TD><input type='button' class='$btn_class' value='MODIFY' onClick=\"ToggleSpan('".$row["user"]."_UPDATE')\"></TD>\n";
			echo "</TR>\n";

			echo "<TR BGCOLOR=#".$bgcolor.">\n";
			echo "<td colspan='3'></td>\n";
			echo "<td colspan='".(count($timeoff_types))."' align='center'><span id='".$row["user"]."_UPDATE' style='display:none'><form action='$PHP_SELF' method='post'><table width='".(75*count($timeoff_types))."'>\n";
			$timeoff_types_str="";
			for ($t=0; $t<count($timeoff_types); $t++)
				{
				$timeoff_types_str.=$timeoff_types[$t]."|";
				echo "<tr><td align='right'><font class='standard_bold'>".$timeoff_types[$t].": </td><td align='left'><input type='text' value='".($user_row["$timeoff_types[$t]"]+0)."' name='".$row["user"]."_".$timeoff_types[$t]."' id='".$row["user"]."_".$timeoff_types[$t]."' size='3' maxlength='5'></td></tr>"; 
				}
			$timeoff_types_str=preg_replace('/\|$/', "", $timeoff_types_str);
			echo "<tr><td colspan='2' align='center'>\n";
			echo "<input type='button' class='red_btn' style='width:".(70*count($timeoff_types))."px' value='UPDATE' onClick=\"UpdateTimeInfo('$row[user]', '$timeoff_types_str', '$month_to_display')\"\n";
			echo "</td></tr></table></form>\n";
			echo "</span></td>\n";
			echo "<td colspan='3'></td>\n";
			echo "</tr>";			
			}
		else if ($display_all_agents>0)
			{
			$i++;
			if ($i%2==0)
				{
				$bgcolor="FFFFFF";
				} 
			else 
				{
				$bgcolor="E6E6E6";
				}

			echo "<TR BGCOLOR=#".$bgcolor.">\n";
			echo "<TD align='left'><font class='standard'>$row[user] - $row[full_name]</font></TD>\n";
			echo "<TD align='center'><font class='standard'>$location</font></TD>\n";
			echo "<TD align='center'><font class='standard'>$default_month_text</font></TD>\n";
			for ($t=0; $t<count($timeoff_types); $t++)
				{
				echo "<TD align='center'><font class='standard'><span id='span_".$row["user"]."_".$timeoff_types[$t]."'>0</span></font></TD>\n";
				}
			echo "<TD align='center'><font class='standard'><span id='".$row["user"]."_lmd'>--</span></font></TD>\n";
			echo "<TD align='center'><font class='standard'><span id='".$row["user"]."_lmb'>--</span></font></TD>\n";
			echo "<TD><input type='button' class='$btn_class' value='MODIFY' onClick=\"ToggleSpan('".$row["user"]."_UPDATE')\"></TD>\n";
			echo "</TR>\n";

			echo "<TR BGCOLOR=#".$bgcolor.">\n";
			echo "<td colspan='3'></td>\n";
			echo "<td colspan='".(count($timeoff_types))."' align='center'><span id='".$row["user"]."_UPDATE' style='display:none'><form action='$PHP_SELF' method='post'><table width='".(75*count($timeoff_types))."'>\n";
			$timeoff_types_str="";
			for ($t=0; $t<count($timeoff_types); $t++)
				{
				$timeoff_types_str.=$timeoff_types[$t]."|";
				echo "<tr><td align='right'><font class='standard_bold'>".$timeoff_types[$t].": </td><td align='left'><input type='text' value='".($user_row["$timeoff_types[$t]"]+0)."' name='".$row["user"]."_".$timeoff_types[$t]."' id='".$row["user"]."_".$timeoff_types[$t]."' size='3' maxlength='5'></td></tr>"; 
				}
			$timeoff_types_str=preg_replace('/\|$/', "", $timeoff_types_str);
			echo "<tr><td colspan='2' align='center'>\n";
			echo "<input type='button' class='red_btn' style='width:".(70*count($timeoff_types))."px' value='UPDATE' onClick=\"UpdateTimeInfo('$row[user]', '$timeoff_types_str', '$month_to_display')\"\n";
			echo "</td></tr></table></form>\n";
			echo "</span></td>\n";
			echo "<td colspan='3'></td>\n";
			echo "</tr>";		
			}
		}

	if (!$display_all_agents || $display_all_agents==0)
		{
		echo "<TR>\n";
		echo "<td colspan='".(6+(count($timeoff_types)<1 ? 1 : count($timeoff_types)))."'>\n";
		echo "<form action='$PHP_SELF' method='post'>\n";
		echo "<input type='hidden' name='new_month' id='new_month' value='$month_to_display'>\n";
		echo "\t<table width='100%'>\n";
		echo "\t\t<tr>\n";
		echo "<td align='right'><font class='standard_bold'>ADD TIMEOFF DATA:</font></td>\n";
		echo "<td align='left'><font class='standard'>Select an agent to add:</font><BR><select name='new_agent' id='new_agent'>\n";
		echo $agent_dropdown;
		echo "</select></td>";

		echo "<td align='right'><font class='standard'>Select timeoff type:</font></td><td align='left'><font class='standard'>";
		for ($t=0; $t<count($timeoff_types); $t++)
			{
			echo "<input type='radio' name='new_timeoff_type' value='$timeoff_types[$t]'>$timeoff_types[$t]<BR>"; #  onClick=\"javascript:document.getElementById('submit_timeoff_type').value=this.value\"
			}
		echo "</font></td>";

		echo "<td align='right'><font class='standard'>Hours to add:</font></td><td align='left'><input type='text' size='3' maxlength='5' name='new_hours' id='new_hours'></td>\n";

		#echo "<td align='right'><font class='standard'>PTO hours:</font></td><td align='left'><input type='text' size='2' maxlength='2' name='new_pto' id='new_pto'><input type='hidden' name='submit_pto' id='submit_pto'></td>\n";

		echo "<th><input type='submit' class='red_btn' name='submit_time_off' id='submit_time_off' value='SUBMIT'></th>\n";
		echo "\t\t</tr>\n";
		echo "\t</table>\n";
		echo "</form>";
		echo "</td>\n";
		echo "</TR>\n";
		}


	if ($custom_download && file_exists("agent_timeoff_interface_download.inc"))
		{
		include("agent_timeoff_interface_download.inc");
		}	

	echo "</table>\n";
	exit;
	}

if ($action=="download_csv" && $download_month)
	{
	$report_name="VICIDIAL TIME OFF";

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

	$stmt="INSERT INTO vicidial_report_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$LOGip', report_name='$report_name', browser='$LOGbrowser', referer='$LOGhttp_referer', notes='$LOGserver_name:$LOGserver_port $LOGscript_name |Month downloaded: $download_month', url='$LOGfull_url', webserver='$webserver_id';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$report_log_id = mysqli_insert_id($link);
	##### END log visit to the vicidial_report_log table #####

	
	
	$CSV_output="\"Agent\",\"Location\",\"Month\"";
	for ($t=0; $t<count($timeoff_types); $t++)
		{
		$CSV_output.=",$timeoff_types[$t]";
		}
	$CSV_output.=",\"Saved/Updated Date\"";
	$CSV_output.=",\"Updated By\"\n";

	$stmt="select user, user_location, full_name from vicidial_users where active='Y' $admin_viewable_groups_andSQL $user_filter_SQL order by $sort_SQL";
	$rslt=mysql_to_mysqli($stmt, $link);

	while($row=mysqli_fetch_array($rslt))
		{
		$location=$row["user_location"];

		$user_stmt="select DATE_FORMAT(concat(timeoff_month, '-01'), '%b-%y') as display_month, max(modify_date) as last_modify_date".$time_column_SQL." From vicidial_timeoff_log where timeoff_month='$download_month' AND user='$row[user]'";
		$user_rslt=mysql_to_mysqli($user_stmt, $link);
		$user_row=mysqli_fetch_array($user_rslt);
		if ($user_row["display_month"])
			{
			$lmb_stmt="select last_modified_by from vicidial_timeoff_log where user='$row[user]' and modify_date='$user_row[last_modify_date]' limit 1";
			$lmb_rslt=mysql_to_mysqli($lmb_stmt, $link);
			$lmb_row=mysqli_fetch_row($lmb_rslt);

			$CSV_output.="\"$row[user] - $row[full_name]\",";
			$CSV_output.="\"$location\",";
			$CSV_output.="\"$download_month\"";
			for ($t=0; $t<count($timeoff_types); $t++)
				{
				$CSV_output.=",\"".($user_row["$timeoff_types[$t]"]+0)."\"";
				}
			$CSV_output.=",\"$user_row[last_modify_date]\"";
			$CSV_output.=",\"".$lmb_row[0]." - ".$master_users["$lmb_row[0]"]."\"\n";
			}
		else if ($display_all_agents>0)
			{
			$CSV_output.="\"$row[user] - $row[full_name]\",";
			$CSV_output.="\"$location\",";
			$CSV_output.="\"$download_month\"";
			for ($t=0; $t<count($timeoff_types); $t++)
				{
				$CSV_output.=",\"0\"";
				}
			$CSV_output.=",\"--\"";
			$CSV_output.=",\"--\"\n";
			}		
		}

	$US='_';
	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "AGENT_TIMEOFF_".$download_month."_".$PHP_AUTH_USER.$US.$FILE_TIME.".csv";

	// We'll be outputting a TXT file
	header('Content-type: application/octet-stream');

	// It will be called LIST_101_20090209-121212.txt
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
	}

if ($custom_download && file_exists("agent_timeoff_interface_PHP.inc"))
	{
	include("agent_timeoff_interface_PHP.inc");
	}

$HEADER.="<html>\n";
$HEADER.="<head>\n";
$HEADER.="<script language=\"JavaScript\" src=\"calendar_db.js\"></script>\n";
$HEADER.="<script language=\"JavaScript\" src=\"help.js\"></script>\n";

$HEADER.="<script language=\"JavaScript\">\n";
$HEADER.="function ToggleSpan(spanID) {\n";
$HEADER.="  if (document.getElementById(spanID).style.display == 'none') {\n";
$HEADER.="    document.getElementById(spanID).style.display = 'block';\n";
$HEADER.="  } else {\n";
$HEADER.="    document.getElementById(spanID).style.display = 'none';\n";
$HEADER.="  }\n";
$HEADER.=" }\n";
$HEADER.="</script>\n";

$HEADER.="<link rel=\"stylesheet\" href=\"calendar.css\">\n";
$HEADER.="<link rel=\"stylesheet\" type=\"text/css\" href=\"vicidial_stylesheet.php\">\n";

$HEADER.="<div id='HelpDisplayDiv' class='help_info' style='display:none;'></div>\n";

$HEADER.="<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";
$HEADER.="<TITLE>Agent time-off interface</TITLE>\n";

header ("Content-type: text/html; charset=utf-8");
echo $HEADER;
?>
<script language="Javascript">
function LoadPTOMonth(selected_month)
	{
	if (!selected_month)
		{
		return false;
		}
	// ##### BEGIN interval update #####
	var xmlhttp=false;
	/*@cc_on @*/
	/*@if (@_jscript_version >= 5)
	// JScript gives us Conditional compilation, we can cope with old IE versions.
	// and security blocked creation of the objects.
	 try {
	  xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	 } catch (e) {
	  try {
	   xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	  } catch (E) {
	   xmlhttp = false;
	  }
	 }
	@end @*/
	if (!xmlhttp && typeof XMLHttpRequest!='undefined')
		{
		xmlhttp = new XMLHttpRequest();
		}
	if (xmlhttp) 
		{ 
		post_URL = '<?php echo $PHP_SELF; ?>';
		var_str = "action=display_past_month&month_to_display="+selected_month;
		// alert(var_str); 
		xmlhttp.open('POST', post_URL); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(var_str); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				month_results = xmlhttp.responseText;
				document.getElementById("past_timeoff_display").innerHTML=month_results;
				}
			}
		delete xmlhttp;
		}
	}

function UpdateTimeInfo(user, to_types, update_month)
	{
	update_span=user+"_UPDATE";
	ToggleSpan(update_span);
	
	url_str="action=update_user&update_user="+user+"&update_month="+update_month;
	var tt_array=to_types.split("|");
	for (var i=0; i<tt_array.length; i++)
		{
		var fieldID=user+"_"+tt_array[i];
		if (document.getElementById(fieldID))
			{
			url_str+="&to_types[]="+tt_array[i]+","+document.getElementById(fieldID).value;
			}
		}
	// alert(url_str);

	// ##### BEGIN interval update #####
	var xmlhttp=false;
	/*@cc_on @*/
	/*@if (@_jscript_version >= 5)
	// JScript gives us Conditional compilation, we can cope with old IE versions.
	// and security blocked creation of the objects.
	 try {
	  xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	 } catch (e) {
	  try {
	   xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	  } catch (E) {
	   xmlhttp = false;
	  }
	 }
	@end @*/
	if (!xmlhttp && typeof XMLHttpRequest!='undefined')
		{
		xmlhttp = new XMLHttpRequest();
		}
	if (xmlhttp) 
		{ 
		post_URL = '<?php echo $PHP_SELF; ?>';
		// alert(var_str); 
		xmlhttp.open('POST', post_URL); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(url_str); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				upd_results = xmlhttp.responseText;
				// alert(upd_results);
				results_array=upd_results.split("\n");

				last_modified_by=results_array[0];
				lmb_span=user+"_lmb";
				document.getElementById(lmb_span).innerHTML=last_modified_by;

				last_modified_date=results_array[1];
				lmd_span=user+"_lmd";
				document.getElementById(lmd_span).innerHTML=last_modified_date;

				for (var j=2; j<results_array.length; j++)
					{
					update_span_info=results_array[j].split("|");
					document.getElementById(update_span_info[0]).innerHTML=update_span_info[1];
					}
				}
			}
		delete xmlhttp;
		}
	
	}

<?php
if ($custom_download && file_exists("agent_timeoff_interface_JS.inc"))
	{
	include("agent_timeoff_interface_JS.inc");
	}
?>
</script>
<?php
require("admin_header.php");


# echo "<form action='$PHP_SELF' method='post' id='inbound_form' name='inbound_form'>";
echo "<TABLE WIDTH=970 BGCOLOR=#E6E6E6 cellpadding=2 cellspacing=0><TR BGCOLOR=#E6E6E6><TD ALIGN=LEFT colspan='4'><FONT CLASS='standard_bold'>\n";
# echo $holiday_stmt."<BR>";
echo "Agent time-off interface<BR><BR>";
echo "</TD><TD ALIGN=RIGHT><FONT CLASS='standard'> &nbsp; </TD></TR>\n";



echo "<TR BGCOLOR=#E6E6E6>\n";
echo "\t<TD ALIGN='LEFT' colspan='2'>\n";


# SHOW CURRENT MONTH SUPERVISOR ENTRIES
# $stmt="select * from vicidial_timeoff_log where timeoff_month>=DATE_FORMAT(now(), '%Y-%m-01') and timeoff_month<DATE_FORMAT(now(), '%Y-%m-01')+INTERVAL 1 MONTH";
echo "<span id='past_timeoff_display'>\n";
echo "<table width='100%' border=0 cellpadding=3 cellspacing=0>\n";
echo "<TR BGCOLOR=#E6E6E6>\n";
echo "\t<TD ALIGN='LEFT' colspan='".(4+(count($timeoff_types)<1 ? 1 : count($timeoff_types)))."'><font class='standard_bold'>CURRENT MONTH DISPLAYED: ";
echo "<select name='timeoff_month_to_display' onChange='LoadPTOMonth(this.value)'>\n";

if ($month_to_display)
	{
	$download_month=$month_to_display;
    $month_text=date("M-y", strtotime(date("$month_to_display-01")));
	echo "<option value='$month_to_display' selected>$month_text</option>\n";
	echo "<option value=''>-------</option>\n";
	}
else
	{
	$month_to_display="now()";
	$download_month=date("Y-m");
	}	

for ($i = 0; $i < 12; $i++) 
	{
    $month_no=date("Y-m", strtotime( date( 'Y-m-01' )." -$i months"));
    $month_text=date("M-y", strtotime( date( 'Y-m-01' )." -$i months"));
	echo "<option value='$month_no'>$month_text</option>\n";
	}

echo "<select>";
echo "</font></TD>\n";
echo "\t<TD ALIGN='RIGHT' colspan='2'><font class='standard_bold'><font class='standard_bold'><a href='agent_timeoff_interface.php?action=download_csv&download_month=".$download_month."'>[DOWNLOAD]</a></font></TD>\n";
echo "</TR>\n";


echo "<TR BGCOLOR=#000000>\n";
echo "<TH rowspan='2'><font class='standard_bold' color='#FFFFFF'>Agent</font></TH>";
echo "<TH rowspan='2'><font class='standard_bold' color='#FFFFFF'>Location</font></TH>";
echo "<TH rowspan='2'><font class='standard_bold' color='#FFFFFF'>Month</font></TH>";
echo "<TH colspan='".(count($timeoff_types)<1 ? 1 : count($timeoff_types))."'><font class='standard_bold' color='#FFFFFF'>Planned Hours</font></TH>";
echo "<TH rowspan='2'><font class='standard_bold' color='#FFFFFF'>Saved/Updated Date</font></TH>";
echo "<TH rowspan='2'><font class='standard_bold' color='#FFFFFF'>Updated By</font></TH>";
echo "<TH rowspan='2'><font class='standard_bold' color='#FFFFFF'>&nbsp;</font></TH>";
echo "</TR>\n";

if (count($timeoff_types)<1)
	{
	echo "<TR BGCOLOR=#000000>\n";
	echo "<TH>&nbsp;</TH>";
	echo "</TR>\n";
	}
else
	{
	echo "<TR BGCOLOR=#000000>\n";
	for ($t=0; $t<count($timeoff_types); $t++)
		{
		echo "<TH><font class='standard_bold' color='#FFFFFF'>".$timeoff_types[$t]."</font></TH>";
		}
	echo "</TR>\n";
	}

$agent_dropdown="";
$stmt="select user, user_location, full_name from vicidial_users where active='Y' $admin_viewable_groups_andSQL $user_filter_SQL order by $sort_SQL";
$rslt=mysql_to_mysqli($stmt, $link);


while($row=mysqli_fetch_array($rslt))
	{
	$agent_dropdown.="<option value='$row[user]'>$row[user] - $row[full_name]</option>\n";

	if ($i%2==0)
		{
		$bgcolor="FFFFFF";
		$btn_class="blue_btn";
		} 
	else 
		{
		$bgcolor="E6E6E6";
		$btn_class="blue_btn";
		}

	$location=$row["user_location"];

	$user_stmt="select DATE_FORMAT(concat(timeoff_month, '-01'), '%b-%y') as display_month, max(modify_date) as last_modify_date".$time_column_SQL." From vicidial_timeoff_log where timeoff_month=DATE_FORMAT($month_to_display, '%Y-%m') AND user='$row[user]'";
	$user_rslt=mysql_to_mysqli($user_stmt, $link);
	$user_row=mysqli_fetch_array($user_rslt);
	if ($user_row["display_month"])
		{
		$lmb_stmt="select last_modified_by from vicidial_timeoff_log where user='$row[user]' and modify_date='$user_row[last_modify_date]' limit 1";
		$lmb_rslt=mysql_to_mysqli($lmb_stmt, $link);
		$lmb_row=mysqli_fetch_row($lmb_rslt);

		echo "<TR BGCOLOR=#".$bgcolor.">\n";
		echo "<TD align='left'><font class='standard'>$row[user] - $row[full_name]</font></TD>\n";
		echo "<TD align='center'><font class='standard'>$location</font></TD>\n";
		echo "<TD align='center'><font class='standard'>$user_row[display_month]</font></TD>\n";
		for ($t=0; $t<count($timeoff_types); $t++)
			{
			echo "<TD align='center'><font class='standard'><span id='span_".$row["user"]."_".$timeoff_types[$t]."'>".($user_row["$timeoff_types[$t]"]+0)."</span></font></TD>\n";
			}
		echo "<TD align='center'><font class='standard'><span id='".$row["user"]."_lmd'>$user_row[last_modify_date]</span></font></TD>\n";
		echo "<TD align='center'><font class='standard'><span id='".$row["user"]."_lmb'>".$lmb_row[0]." - ".$master_users["$lmb_row[0]"]."</span></font></TD>\n";
		echo "<TD><input type='button' class='$btn_class' value='MODIFY' onClick=\"ToggleSpan('".$row["user"]."_UPDATE')\"></TD>\n";
		echo "</TR>\n";

		echo "<TR BGCOLOR=#".$bgcolor.">\n";
		echo "<td colspan='3'></td>\n";
		echo "<td colspan='".(count($timeoff_types))."' align='center'><span id='".$row["user"]."_UPDATE' style='display:none'><form action='$PHP_SELF' method='post'><table width='".(75*count($timeoff_types))."'>\n";
		$timeoff_types_str="";
		for ($t=0; $t<count($timeoff_types); $t++)
			{
			$timeoff_types_str.=$timeoff_types[$t]."|";
			echo "<tr><td align='right'><font class='standard_bold'>".$timeoff_types[$t].": </td><td align='left'><input type='text' value='".($user_row["$timeoff_types[$t]"]+0)."' name='".$row["user"]."_".$timeoff_types[$t]."' id='".$row["user"]."_".$timeoff_types[$t]."' size='3' maxlength='5'></td></tr>"; 
			}
		$timeoff_types_str=preg_replace('/\|$/', "", $timeoff_types_str);
		echo "<tr><td colspan='2' align='center'>\n";
		echo "<input type='button' class='red_btn' style='width:".(70*count($timeoff_types))."px' value='UPDATE' onClick=\"UpdateTimeInfo('$row[user]', '$timeoff_types_str', '$download_month')\"\n";
		echo "</td></tr></table></form>\n";
		echo "</span></td>\n";
		echo "<td colspan='3'></td>\n";
		echo "</tr>";

		}
	else if ($display_all_agents>0)
		{
		echo "<TR BGCOLOR=#".$bgcolor.">\n";
		echo "<TD align='left'><font class='standard'>$row[user] - $row[full_name]</font></TD>\n";
		echo "<TD align='center'><font class='standard'>$location</font></TD>\n";
		echo "<TD align='center'><font class='standard'>".date("M-y")."</font></TD>\n";
		for ($t=0; $t<count($timeoff_types); $t++)
			{
			echo "<TD align='center'><font class='standard'><span id='span_".$row["user"]."_".$timeoff_types[$t]."'>0</span></font></TD>\n";
			}
		echo "<TD align='center'><font class='standard'><span id='".$row["user"]."_lmd'>--</span></font></TD>\n";
		echo "<TD align='center'><font class='standard'><span id='".$row["user"]."_lmb'>--</span></font></TD>\n";
		echo "<TD><input type='button' class='$btn_class' value='MODIFY' onClick=\"ToggleSpan('".$row["user"]."_UPDATE')\"></TD>\n";
		echo "</TR>\n";

		echo "<TR BGCOLOR=#".$bgcolor.">\n";
		echo "<td colspan='3'></td>\n";
		echo "<td colspan='".(count($timeoff_types))."' align='center'><span id='".$row["user"]."_UPDATE' style='display:none'><form action='$PHP_SELF' method='post'><table width='".(75*count($timeoff_types))."'>\n";
		$timeoff_types_str="";
		for ($t=0; $t<count($timeoff_types); $t++)
			{
			$timeoff_types_str.=$timeoff_types[$t]."|";
			echo "<tr><td align='right'><font class='standard_bold'>".$timeoff_types[$t].": </td><td align='left'><input type='text' value='".($user_row["$timeoff_types[$t]"]+0)."' name='".$row["user"]."_".$timeoff_types[$t]."' id='".$row["user"]."_".$timeoff_types[$t]."' size='3' maxlength='5'></td></tr>"; 
			}
		$timeoff_types_str=preg_replace('/\|$/', "", $timeoff_types_str);
		echo "<tr><td colspan='2' align='center'>\n";
		echo "<input type='button' class='red_btn' style='width:".(70*count($timeoff_types))."px' value='UPDATE' onClick=\"UpdateTimeInfo('$row[user]', '$timeoff_types_str', '$download_month')\"\n";
		echo "</td></tr></table></form>\n";
		echo "</span></td>\n";
		echo "<td colspan='3'></td>\n";
		echo "</tr>";		
		}
	
	$i++;
	}

if (!$display_all_agents || $display_all_agents==0)
	{
	echo "<TR>\n";
	echo "<td colspan='".(6+(count($timeoff_types)<1 ? 1 : count($timeoff_types)))."'>\n";
	echo "<form action='$PHP_SELF' method='post'>\n";
	echo "<input type='hidden' name='new_month' id='new_month' value='$download_month'>\n";
	echo "\t<table width='100%'>\n";
	echo "\t\t<tr>\n";
	echo "<td align='right'><font class='standard_bold'>ADD TIMEOFF DATA:</font></td>\n";
	echo "<td align='left'><font class='standard'>Select an agent to add:</font><BR><select name='new_agent' id='new_agent'>\n";
	echo $agent_dropdown;
	echo "</select></td>";

	echo "<td align='right'><font class='standard'>Select timeoff type:</font></td><td align='left'><font class='standard'>";
	for ($t=0; $t<count($timeoff_types); $t++)
		{
		echo "<input type='radio' name='new_timeoff_type' value='$timeoff_types[$t]'>$timeoff_types[$t]<BR>"; #  onClick=\"javascript:document.getElementById('submit_timeoff_type').value=this.value\"
		}
	echo "</font></td>";

	echo "<td align='right'><font class='standard'>Hours to add:</font></td><td align='left'><input type='text' size='3' maxlength='5' name='new_hours' id='new_hours'></td>\n";

	#echo "<td align='right'><font class='standard'>PTO hours:</font></td><td align='left'><input type='text' size='2' maxlength='2' name='new_pto' id='new_pto'><input type='hidden' name='submit_pto' id='submit_pto'></td>\n";

	echo "<th><input type='submit' class='red_btn' name='submit_time_off' id='submit_time_off' value='SUBMIT'></th>\n";
	echo "\t\t</tr>\n";
	echo "\t</table>\n";
	echo "</form>";
	echo "</td>\n";
	echo "</TR>\n";
	}

if ($custom_download && file_exists("agent_timeoff_interface_download.inc"))
	{
	include("agent_timeoff_interface_download.inc");
	}	

echo "</table>\n";
echo "</span>\n";

# SHOW PAST MONTH LOG ENTRIES
/*
echo "<BR><HR><BR>\n";

echo "<table width='100%' border=0 cellpadding=0 cellspacing=0>\n";
echo "<TR BGCOLOR=#E6E6E6>\n\t<TD ALIGN='LEFT'><font class='standard_bold'>PAST MONTH TIME OFF:</font></TD>\n";
echo "\t<TD ALIGN='RIGHT' color='#FFFFFF'><font class='standard_bold'>Select a past month to display:</font><select name='timeoff_month_to_display' onChange='LoadPTOMonth(this.value)'>\n";

$stmt="select distinct timeoff_month, DATE_FORMAT(timeoff_month, '%b-%y') from vicidial_timeoff_log where timeoff_month<DATE_FORMAT(now(), '%Y-%m') order by timeoff_month desc";
$rslt=mysql_to_mysqli($stmt, $link);
while ($row=mysqli_fetch_row($rslt))
	{
	echo "<option value='$row[0]'>$row[1]</option>\n";
	}

echo "<select></TD></TR>\n";
echo "<TR BGCOLOR=#E6E6E6>\n\t<TD ALIGN='LEFT' colspan='2'><span id='past_timeoff_display'>\n";
echo "</span></TD></TR>\n";
echo "</TABLE>\n";
echo "</FORM>\n";

*/
?>


<?php
$ENDtime = date("U");

$RUNtime = ($ENDtime - $STARTtime);

echo "\n\n\n<br><br><br>\n\n";


echo "<font size=0>\n\n\n<br><br><br>\n"._QXZ("script runtime").": $RUNtime "._QXZ("seconds")."|$db_source</font>";

# print_r($location_array);

echo "</TD></TR><TABLE>";
echo "</body>";
echo "</html>";

# header ("Content-type: text/html; charset=utf-8");
# echo $HEADER;
# require("admin_header.php");
# echo $MAIN;

?>
