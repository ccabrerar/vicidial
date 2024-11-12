<?php
$startMS = microtime();
$ip = getenv("REMOTE_ADDR");
$browser = getenv("HTTP_USER_AGENT");

require("dbconnect_mysqli.php");
require("functions.php");

$report_name = 'User Stats';
$db_source = 'M';

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);
$QUERY_STRING = getenv("QUERY_STRING");

if (isset($_GET["DB"]))						{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))			{$DB=$_POST["DB"];}
if (isset($_GET["default_submit"]))					{$default_submit=$_GET["default_submit"];}
	elseif (isset($_POST["default_submit"]))		{$default_submit=$_POST["default_submit"];}
if (isset($_GET["location"]))					{$location=$_GET["location"];}
	elseif (isset($_POST["location"]))		{$location=$_POST["location"];}
if (isset($_GET["message"]))					{$message=$_GET["message"];}
	elseif (isset($_POST["message"]))		{$message=$_POST["message"];}
if (isset($_GET["message_type"]))					{$message_type=$_GET["message_type"];}
	elseif (isset($_POST["message_type"]))		{$message_type=$_POST["message_type"];}
if (isset($_GET["meeting_field"]))					{$meeting_field=$_GET["meeting_field"];}
	elseif (isset($_POST["meeting_field"]))		{$meeting_field=$_POST["meeting_field"];}
if (isset($_GET["closed_field"]))					{$closed_field=$_GET["closed_field"];}
	elseif (isset($_POST["closed_field"]))		{$closed_field=$_POST["closed_field"];}
if (isset($_GET["weather_field"]))					{$weather_field=$_GET["weather_field"];}
	elseif (isset($_POST["weather_field"]))		{$weather_field=$_POST["weather_field"];}
if (isset($_GET["holiday_field"]))					{$holiday_field=$_GET["holiday_field"];}
	elseif (isset($_POST["holiday_field"]))		{$holiday_field=$_POST["holiday_field"];}
if (isset($_GET["custom_field"]))					{$custom_field=$_GET["custom_field"];}
	elseif (isset($_POST["custom_field"]))		{$custom_field=$_POST["custom_field"];}
if (isset($_GET["audio_file"]))					{$audio_file=$_GET["audio_file"];}
	elseif (isset($_POST["audio_file"]))		{$audio_file=$_POST["audio_file"];}
if (isset($_GET["from_date"]))					{$from_date=$_GET["from_date"];}
	elseif (isset($_POST["from_date"]))		{$from_date=$_POST["from_date"];}
if (isset($_GET["from_hour"]))					{$from_hour=$_GET["from_hour"];}
	elseif (isset($_POST["from_hour"]))		{$from_hour=$_POST["from_hour"];}
if (isset($_GET["from_min"]))					{$from_min=$_GET["from_min"];}
	elseif (isset($_POST["from_min"]))		{$from_min=$_POST["from_min"];}
if (isset($_GET["to_date"]))					{$to_date=$_GET["to_date"];}
	elseif (isset($_POST["to_date"]))		{$to_date=$_POST["to_date"];}
if (isset($_GET["to_hour"]))					{$to_hour=$_GET["to_hour"];}
	elseif (isset($_POST["to_hour"]))		{$to_hour=$_POST["to_hour"];}
if (isset($_GET["to_min"]))					{$to_min=$_GET["to_min"];}
	elseif (isset($_POST["to_min"]))		{$to_min=$_POST["to_min"];}
if (isset($_GET["insert_flag"]))					{$insert_flag=$_GET["insert_flag"];}
	elseif (isset($_POST["insert_flag"]))		{$insert_flag=$_POST["insert_flag"];}
if (isset($_GET["interval_id"]))					{$interval_id=$_GET["interval_id"];}
	elseif (isset($_POST["interval_id"]))		{$interval_id=$_POST["interval_id"];}
if (isset($_GET["holiday_id"]))					{$holiday_id=$_GET["holiday_id"];}
	elseif (isset($_POST["holiday_id"]))		{$holiday_id=$_POST["holiday_id"];}
if (isset($_GET["action"]))					{$action=$_GET["action"];}
	elseif (isset($_POST["action"]))		{$action=$_POST["action"];}




if ($insert_flag) {$action="insert_interval";}
$firstlastname_display_user_stats=0;
$add_copy_disabled=0;
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
$from_date = preg_replace('/[^\-0-9]/', '', $from_date);
$from_hour = preg_replace('/[^0-9]/', '', $from_hour);
$from_min = preg_replace('/[^0-9]/', '', $from_min);
$to_date = preg_replace('/[^\-0-9]/', '', $to_date);
$to_hour = preg_replace('/[^0-9]/', '', $to_hour);
$to_min = preg_replace('/[^0-9]/', '', $to_min);
$insert_flag = preg_replace('/[^0-9]/', '', $insert_flag);
$interval_id = preg_replace('/[^0-9]/', '', $interval_id);

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	$default_submit = preg_replace('/[^-_\s0-9a-zA-Z]/', '', $default_submit);
	$location = preg_replace('/[^-_0-9a-zA-Z]/', '', $location);
	$message_type = preg_replace('/[^a-zA-Z]/', '', $message_type);
	$audio_file = preg_replace('/[^-_0-9a-zA-Z]/', '', $audio_file);
	$meeting_field = preg_replace('/[^-_0-9a-zA-Z]/', '', $meeting_field);
	$closed_field = preg_replace('/[^-_0-9a-zA-Z]/', '', $closed_field);
	$weather_field = preg_replace('/[^-_0-9a-zA-Z]/', '', $weather_field);
	$holiday_field = preg_replace('/[^-_0-9a-zA-Z]/', '', $holiday_field);
	$custom_field = preg_replace('/[^-_0-9a-zA-Z]/', '', $custom_field);
	$holiday_id = preg_replace('/[^-_0-9a-zA-Z]/','',$holiday_id);
	$action = preg_replace('/[^-_0-9a-zA-Z]/', '', $action);
	$message = preg_replace('/[^-_0-9a-zA-Z]/', '', $message);
	}
else
	{
	$PHP_AUTH_USER = preg_replace('/[^\-_0-9\p{L}]/u', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^\-_0-9\p{L}]/u', '', $PHP_AUTH_PW);
	$default_submit = preg_replace('/[^\-_\s0-9\p{L}]/u', '', $default_submit);
	$location = preg_replace('/[^\-_0-9\p{L}]/u', '', $location);
	$message_type = preg_replace('/[^\p{L}]/u', '', $message_type);
	$audio_file = preg_replace('/[^\-_0-9\p{L}]/u', '', $audio_file);
	$meeting_field = preg_replace('/[^\-_0-9\p{L}]/u', '', $meeting_field);
	$closed_field = preg_replace('/[^\-_0-9\p{L}]/u', '', $closed_field);
	$weather_field = preg_replace('/[^\-_0-9\p{L}]/u', '', $weather_field);
	$holiday_field = preg_replace('/[^-_0-9\p{L}]/', '', $holiday_field);
	$custom_field = preg_replace('/[^\-_\.0-9\p{L}]/u', '', $custom_field);
	$holiday_id = preg_replace('/[^\-_0-9\p{L}]/u','',$holiday_id);
	$action = preg_replace('/[^\-_0-9\p{L}]/u', '', $action);
	$message = preg_replace('/[^\-_0-9\p{L}]/u', '', $message);
	}



$user_auth=0;
$auth=0;
$reports_auth=0;
$qc_auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'QC',1,0);
if ($auth_message == 'GOOD')
	{$user_auth=1;}

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

	$stmt="select user_level, user_group from vicidial_users where user='$PHP_AUTH_USER'";
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$user_level=$row[0];

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

$expected_files=array("meeting", "closed", "weather", "holiday", "custom");
$container_default_str="^".implode("\s|^", $expected_files)."\s";
$audio_file_str=implode("|", $expected_files);

if ($default_submit=="Set as defaults")
	{
	$upd_container_text="";
	$found_files=array();

	$container_stmt="select container_entry from vicidial_settings_containers where container_id='INBOUND_LINE_CONTROLS'";
	$container_rslt=mysql_to_mysqli($container_stmt, $link);
	$container_row=mysqli_fetch_row($container_rslt);
	# echo "<!--\n$container_row[0]\n------\n";
	$container_text=explode("\n", $container_row[0]);
	for ($i=0; $i<count($container_text); $i++)
		{
		$current_row=trim($container_text[$i]);
		if (preg_match("/$container_default_str/i", $current_row, $matches))
			{
			$matches[0]=trim($matches[0]);
			$audio_key=$matches[0]."_field";
			$upd_container_text.=$matches[0]." => ".$$audio_key."\n";
			array_push($found_files, $matches[0]);
			}
		else
			{
			$upd_container_text.=$current_row."\n";
			}
		}

	$missing_fields=array_diff($expected_files, $found_files);
	foreach ($missing_fields as $key => $val)
		{
		$audio_key=$val."_field";
		$upd_container_text.=$val." => ".$$audio_key."\n";
		}

	# echo $upd_container_text."\n\\-->\n";

	$upd_stmt="UPDATE vicidial_settings_containers set container_entry='".mysqli_escape_string($link, $upd_container_text)."' where container_id='INBOUND_LINE_CONTROLS'";
	$upd_rslt=mysql_to_mysqli($upd_stmt, $link);
	}	

$container_stmt="select container_entry from vicidial_settings_containers where container_id='INBOUND_LINE_CONTROLS'";
$container_rslt=mysql_to_mysqli($container_stmt, $link);
$location_array=array();
$audio_array=array();
$locations_calltimes_array=array();
$completed_days=1;

if (mysqli_num_rows($container_rslt)==0)
	{
	echo "ERROR: This feature requires a settings container named 'INBOUND_LINE_CONTROLS'";
	echo ($user_level==9 ? "<BR><BR><a href='admin.php?ADD=192111111111'>Create a new settings container here</a>" : "");
	echo "<BR><BR><a href='admin.php'>Go back to admin</a>";
	exit;
	}

$location_restrictions=array();
$event_restrictions=array();
while ($container_row=mysqli_fetch_row($container_rslt))
	{	
	$inbound_instructions=explode("\n", $container_row[0]);
	for ($i=0; $i<count($inbound_instructions); $i++)
		{
		$inbound_instructions[$i]=trim($inbound_instructions[$i]);
		if (!preg_match('/^#/', $inbound_instructions[$i]) && preg_match('/\=\>/', $inbound_instructions[$i]))
			{
			$instructions=explode("=>", $inbound_instructions[$i]);
			if (preg_match('/^minimum_required_level_locations/', $instructions[0]))
				{
				$instructions[1]=preg_replace("/\s/", "", $instructions[1]);
				$location_sets=explode("|", $instructions[1]);
				for ($j=0; $j<count($location_sets); $j++)
					{
					if(preg_match('/,[0-9]$/', $location_sets[$j]))
						{
						$location_values=explode(",", $location_sets[$j]);
						$location_values[0]=preg_replace('/[^-_0-9a-zA-Z]/', '', $location_values[0]);
						$location_values[1]=preg_replace('/[^0-9]/', '', $location_values[1]);
						$location_restrictions["$location_values[0]"]=$location_values[1];
						}
					}
				}
			else if (preg_match('/^minimum_required_level_events/', $instructions[0]))
				{
				$instructions[1]=preg_replace("/\s/", "", $instructions[1]);
				$event_sets=explode("|", $instructions[1]);
				for ($j=0; $j<count($event_sets); $j++)
					{
					if(preg_match('/,[0-9]$/', $event_sets[$j]))
						{
						$event_values=explode(",", $event_sets[$j]);
						$event_values[0]=preg_replace('/[^-_0-9a-zA-Z]/', '', $event_values[0]);
						$event_values[1]=preg_replace('/[^0-9]/', '', $event_values[1]);
						$event_restrictions["$event_values[0]"]=$event_values[1];
						}
					}
				}
			else if(preg_match('/location/i', $instructions[0]))
				{
				$instructions[1]=preg_replace("/\s/", "", $instructions[1]);
				$more_locations=explode(",", $instructions[1]);
				$location_array = array_merge($location_array, $more_locations);
				}
/*
			else if(preg_match('/^events/i', $instructions[0]))
				{
				$events[1]=preg_replace("/\s/", "", $instructions[1]);
				$event_array=explode(",", $instructions[1]);
				$custom_key=array_search("custom", $event_array);
				if ($custom_key)
					{
					unset($event_array[$custom_key]);
					array_push($event_array, "custom");
					}
				}
*/
			else if(preg_match('/display_completed_days/i', $instructions[0]))
				{
				$completed_days=preg_replace("/[^0-9]/", "", $instructions[1]);
				}
			else if (preg_match("/$audio_file_str/i", $instructions[0], $matches))
				{
				for($j=0; $j<count($matches); $j++)
					{
					$audio_array["$matches[$j]"]=trim($instructions[1]);
					}
				}
			else if (preg_match('/^affected_calltimes/', $instructions[0]))
				{
				$ct_location=preg_replace('/^affected_calltimes[_]?/', '', trim($instructions[0]));
				if (!isset($locations_calltimes_array["$ct_location"])) {$locations_calltimes_array["$ct_location"]=array();}

				$instructions[1]=preg_replace("/\s/", "", $instructions[1]);
				$ct_locations=explode(",", $instructions[1]);
				$locations_calltimes_array["$ct_location"] = array_merge($locations_calltimes_array["$ct_location"], $ct_locations);
				}
			}
		}
	}

foreach ($location_restrictions as $current_location => $required_user_level)
	{
	if($user_level<$required_user_level)
		{
		if (($index = array_search($current_location, $location_array)) !== false) 
			{
			unset($location_array[$index]);
			}
		}
	}
$location_array=array_values($location_array);

foreach ($locations_calltimes_array as $active_location => $array)
	{
	$locations_calltimes_array["$active_location"]=array_unique($array);
	}

if ($action=="get_local_time")
	{
	echo date("Y-m-d-H-i");
	exit;
	}

if ($audio_file && $action=="move_audio_file")
	{
	$temp_filename = "../agc/polly/generated/".$audio_file;
	passthru("cp $temp_filename ../$sounds_web_directory/");
	$success_msg="SUCCESS"."\n***";
	echo trim($success_msg); 

	$stmt="UPDATE servers SET sounds_update='Y';";
	$rslt=mysql_to_mysqli($stmt, $link);

	### LOG INSERTION Admin Log Table ###
	$SQL_log = "$stmt|";
	$SQL_log = preg_replace('/;/', '', $SQL_log);
	$SQL_log = addslashes($SQL_log);
	$stmt="INSERT INTO vicidial_admin_log set event_date=NOW(), user='$PHP_AUTH_USER', ip_address='$ip', event_section='AUDIOSTORE', event_type='COPY', record_id='manualupload', event_code='$audio_file', event_sql=\"$SQL_log\", event_notes='NEW: $audio_file  POLLY GENERATED';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	
	exit;
	}

if ($action=="insert_interval" && $location && $message && $message_type && $from_date && $from_hour && $from_min && $to_date && $to_hour && $to_min)
	{
	$error_msg="";

	$dup_stmt="select count(*) from inbound_disabled_entries where start_datetime='".$from_date." ".$from_hour.":".$from_min.":00' and end_datetime='".$to_date." ".$to_hour.":".$to_min.":00' and location in ('$location'".(preg_match('/\|/', $location) ? ", '".preg_replace('/\|/', "', '", $location)."'" : "").") and status in ('LIVE', 'ACTIVE')";
	$dup_rslt=mysql_to_mysqli($dup_stmt, $link);
	$dup_row=mysqli_fetch_row($dup_rslt);
	if ($dup_row[0]>0)
		{
		$error_msg.="ERROR: This interval (".$from_date." ".$from_hour.":".$from_min.":00 to ".$to_date." ".$to_hour.":".$to_min.":00 for ".preg_replace('/\|/', ", ", $location).") is already scheduled for a live/upcoming downtime";
		# $error_msg.="<BR>$dup_stmt";
		}

	$msg_array=CheckHolidayConflicts($location, $locations_calltimes_array, $from_date, $from_hour, $from_min, $to_date, $to_hour, $to_min, "", 1);

	$error_msg.=$msg_array[0];
	$ok_msg=$msg_array[1];
	$interval_time_array=$msg_array[2];
	# echo "ERR: $error_msg<BR><BR>OK: $ok_msg";

	# exit;

	if (strlen($error_msg)==0)
		{
		$holiday_id="AG_".date("YmdHis")."_".$message_type;
		$ins_stmt="INSERT INTO inbound_disabled_entries(start_datetime, end_datetime, location, message, message_type, user, holiday_id) VALUES('".$from_date." ".$from_hour.":".$from_min.":00', '".$to_date." ".$to_hour.":".$to_min.":00', '$location', '$message', '$message_type', '$PHP_AUTH_USER', '$holiday_id')";
		$ins_rslt=mysql_to_mysqli($ins_stmt, $link);
		if (mysqli_affected_rows($link)>0)
			{

			$h=1;
			foreach ($interval_time_array as $date => $times)
				{
				$VD_holiday_id=$holiday_id."_".$h;
				$holiday_stmt="insert into vicidial_call_time_holidays VALUES('$VD_holiday_id', 'Play $message_type $message message', 'Created by $PHP_AUTH_USER on ".date("Y-m-d H:i:s")." to be played $date', '$date', 'ACTIVE', '".$times["start"]."', '".$times["end"]."', '$message', 'LOCKED_HOLIDAY', 'ADDITION_REVERSE')";
				# echo $holiday_stmt."<BR>";
				$holiday_rslt=mysql_to_mysqli($holiday_stmt, $link);

				$selected_locations=explode("|", $location);
				for ($n=0; $n<count($selected_locations); $n++) 
					{
					$current_location=$selected_locations[$n];
					if (isset($locations_calltimes_array["$current_location"]))
						{
						for ($q=0; $q<=count($locations_calltimes_array["$current_location"]); $q++)
							{	
							$call_time=$locations_calltimes_array["$current_location"][$q];

							$stmt="SELECT ct_holidays from vicidial_call_times where call_time_id='".$call_time."';";
							$rslt=mysql_to_mysqli($stmt, $link);
							if(mysqli_num_rows($rslt)>0)
								{
								$row=mysqli_fetch_row($rslt);
								$ct_holidays = $row[0];

								if (preg_match('/\|$/i',$ct_holidays))
									{$ct_holidays = "$ct_holidays$VD_holiday_id\|";}
								else
									{$ct_holidays = "$ct_holidays\|$VD_holiday_id\|";}
								$stmt="UPDATE vicidial_call_times set ct_holidays='$ct_holidays' where call_time_id='".$call_time."';";
								$rslt=mysql_to_mysqli($stmt, $link);

								### LOG INSERTION Admin Log Table ###
								$SQL_log = "$stmt|";
								$SQL_log = preg_replace('/;/', '', $SQL_log);
								$SQL_log = addslashes($SQL_log);
								$stmt="INSERT INTO vicidial_admin_log set event_date=now(), user='$PHP_AUTH_USER', ip_address='$ip', event_section='CALLTIMES', event_type='MODIFY', record_id='".$call_time."', event_code='ADMIN MODIFY CALL TIME ADD HOLIDAY RULE', event_sql=\"$SQL_log\", event_notes='Holiday Rule Added: $VD_holiday_id';";
								if ($DB) {echo "|$stmt|\n";}
								$rslt=mysql_to_mysqli($stmt, $link);
								}
							}
						}
					}
				$h++;
				}
			}
		header("Location: $PHP_SELF");
		} 
	else
		{
		# echo "ERROR: $error_msg";
		}	
	}

if ($action=="update_interval" && $interval_id && $holiday_id && $from_date && $from_hour && $from_min && $to_date && $to_hour && $to_min)
	{
	$msg_array=CheckHolidayConflicts($location, $locations_calltimes_array, $from_date, $from_hour, $from_min, $to_date, $to_hour, $to_min, $holiday_id, 0);

	$error_msg=$msg_array[0];
	$ok_msg=$msg_array[1];
	$interval_time_array=$msg_array[2];
	# echo "ERR: $error_msg<BR><BR>OK: $ok_msg";


	if (strlen($error_msg)==0)
		{
		$upd_stmt="UPDATE inbound_disabled_entries set start_datetime='".$from_date." ".$from_hour.":".$from_min.":00', end_datetime='".$to_date." ".$to_hour.":".$to_min.":00', modified_by='$PHP_AUTH_USER' where interval_id='$interval_id' ";
		$upd_rslt=mysql_to_mysqli($upd_stmt, $link);
		if (mysqli_affected_rows($link)>0)
			{
			echo "SUCCESS\n";

			# Get interval info in case more inserts need to be done
			$int_stmt="select * from inbound_disabled_entries where interval_id='$interval_id'";
			$int_rslt=mysql_to_mysqli($int_stmt, $link);
			while($int_row=mysqli_fetch_array($int_rslt))
				{
				$message=$int_row["message"];
				$message_type=$int_row["message_type"];
				$upd_location=$int_row["location"];
				}

			$ct_stmt="";

			# Update holidays table anything that is now not in the date range
			# $holiday_stmt="UPDATE vicidial_call_time_holidays set holiday_status='INACTIVE' where holiday_id like '$holiday_id%' and holiday_date not in ('".implode("', '", array_keys($interval_time_array))."')";
			$holiday_stmt="DELETE from vicidial_call_time_holidays where holiday_id like '$holiday_id%'";
			$holiday_rslt=mysql_to_mysqli($holiday_stmt, $link);
			$ct_stmt.="$holiday_stmt|";

			# Remove old holidays
			$selected_locations=explode("|", $upd_location);
			for ($n=0; $n<count($selected_locations); $n++) 
				{
				$current_location=$selected_locations[$n];
				if (isset($locations_calltimes_array["$current_location"]))
					{
					for ($q=0; $q<=count($locations_calltimes_array["$current_location"]); $q++)
						{	
						$call_time=$locations_calltimes_array["$current_location"][$q];
						$stmt="SELECT ct_holidays from vicidial_call_times where call_time_id='".$call_time."';";
						$rslt=mysql_to_mysqli($stmt, $link);
						if(mysqli_num_rows($rslt)>0)
							{
							$row=mysqli_fetch_row($rslt);
							$ct_holidays = $row[0];

							$holiday_pattern=$holiday_id."\_[0-9]+";
							$ct_holidays = preg_replace("/$holiday_pattern/i", '', $ct_holidays);
							$ct_holidays = preg_replace("/\|+/", '|', $ct_holidays);
							$stmt="UPDATE vicidial_call_times set ct_holidays='$ct_holidays' where call_time_id='".$call_time."';";
							$rslt=mysql_to_mysqli($stmt, $link);
							}
						}
					}
				}

			/* Unnecessary with above switching to 'DELETE 
			# Get latest ID in case update adds a new day to the range necessitating a new holiday
			$latest_stmt="select holiday_id from vicidial_call_time_holidays where holiday_id like '$holiday_id%' order by holiday_id desc limit 1";
			$latest_rslt=mysql_to_mysqli($latest_stmt, $link);
			while($latest_row=mysqli_fetch_row($latest_rslt))
				{
				$h=$latest_row[0];
				$h=preg_replace("/$holiday_id/", "", $h);
				$h=preg_replace("/[^0-9]/", "", $h);
				}
			*/
			
			$h=1;
			foreach ($interval_time_array as $date => $times)
				{
				/* Unnecessary with above switching to 'DELETE 
				#$holiday_stmt="select count(*) From vicidial_call_time_holidays where holiday_id like '$holiday_id%' and holiday_date='$date'";
				#$holiday_rslt=mysql_to_mysqli($holiday_stmt, $link);
				#$holiday_ct=mysqli_fetch_row($holiday_rslt);

				#if ($holiday_ct[0]==0)
				#	{
				*/

					$VD_holiday_id=$holiday_id."_".$h;
					$holiday_stmt_ins="INSERT into vicidial_call_time_holidays VALUES('$VD_holiday_id', 'Play $message_type $message message', 'Added by $PHP_AUTH_USER on ".date("Y-m-d H:i:s")." to be played $date', '$date', 'ACTIVE', '".$times["start"]."', '".$times["end"]."', '$message', 'LOCKED_HOLIDAY', 'ADDITION_REVERSE')";
					$holiday_rslt_ins=mysql_to_mysqli($holiday_stmt_ins, $link);
					$ct_stmt.="$holiday_stmt_ins|";

					$selected_locations=explode("|", $upd_location);
					for ($n=0; $n<count($selected_locations); $n++) 
						{
						$current_location=$selected_locations[$n];
						if (isset($locations_calltimes_array["$current_location"]))
							{
							for ($q=0; $q<=count($locations_calltimes_array["$current_location"]); $q++)
								{	
								$call_time=$locations_calltimes_array["$current_location"][$q];

								$stmt="SELECT ct_holidays from vicidial_call_times where call_time_id='".$call_time."';";
								$rslt=mysql_to_mysqli($stmt, $link);
								if(mysqli_num_rows($rslt)>0)
									{
									$row=mysqli_fetch_row($rslt);
									$ct_holidays = $row[0];

									if (preg_match('/\|$/i',$ct_holidays))
										{$ct_holidays = "$ct_holidays$VD_holiday_id\|";}
									else
										{$ct_holidays = "$ct_holidays\|$VD_holiday_id\|";}
									$stmt="UPDATE vicidial_call_times set ct_holidays='$ct_holidays' where call_time_id='".$call_time."';";
									$rslt=mysql_to_mysqli($stmt, $link);

									### LOG INSERTION Admin Log Table ###
									$SQL_log = "$stmt|";
									$SQL_log = preg_replace('/;/', '', $SQL_log);
									$SQL_log = addslashes($SQL_log);
									$stmt="INSERT INTO vicidial_admin_log set event_date=now(), user='$PHP_AUTH_USER', ip_address='$ip', event_section='CALLTIMES', event_type='MODIFY', record_id='".$call_time."', event_code='ADMIN MODIFY CALL TIME MODIFY HOLIDAY RULE', event_sql=\"$SQL_log\", event_notes='Holiday Rule Added: $VD_holiday_id';";
									if ($DB) {echo "|$stmt|\n";}
									$rslt=mysql_to_mysqli($stmt, $link);
									}
								}
							}
						}
						$h++;
			
				/* Unnecessary with above switching to 'DELETE 
					}
				else
					{
					$holiday_stmt_upd="UPDATE vicidial_call_time_holidays set holiday_status='ACTIVE', ct_default_start='".$times["start"]."', ct_default_stop='".$times["end"]."' where holiday_id like '$holiday_id%' and holiday_date='$date'";
					$holiday_rslt_upd=mysql_to_mysqli($holiday_stmt_upd, $link);
					$ct_stmt.="$holiday_stmt_upd|";
					}
				*/

				$upd_stmt="update inbound_disabled_entries set status='LIVE' where status='ACTIVE' and start_datetime<=now() and end_datetime>=now()";
				$upd_rslt=mysql_to_mysqli($upd_stmt, $link);				
				}

			$inb_stmt="select * from inbound_disabled_entries where interval_id='$interval_id'";
			$inb_rslt=mysql_to_mysqli($inb_stmt, $link);
			if (mysqli_num_rows($inb_rslt)>0)
				{
				$row=mysqli_fetch_array($inb_rslt);
				$location=preg_replace('/\|/', ', ', $row["location"]);

				$full_name="";
				$fn_stmt="select full_name from vicidial_users where user='$row[user]'";
				$fn_rslt=mysql_to_mysqli($fn_stmt, $link);
				if(mysqli_num_rows($fn_rslt)>0)
					{
					$fn_row=mysqli_fetch_row($fn_rslt);
					$full_name=", $fn_row[0]";
					}

				### LOG INSERTION Admin Log Table ###
				$SQL_log = "$ct_stmt";
				$SQL_log = preg_replace('/;/', '', $SQL_log);
				$SQL_log = addslashes($SQL_log);
				$stmt="INSERT INTO vicidial_admin_log set event_date=now(), user='$PHP_AUTH_USER', ip_address='$ip', event_section='CALLTIMES', event_type='MODIFY', record_id='$holiday_id', event_code='ADMIN MODIFY CALL TIME CHANGE HOLIDAY RULE', event_sql=\"$SQL_log\", event_notes='".$call_time."';";
				if ($DB) {echo "|$stmt|\n";}
				$rslt=mysql_to_mysqli($stmt, $link);


				echo "<font color='#900'>UPDATED: Inbound $location lines are currently down from $row[start_datetime] to $row[end_datetime], requested by $row[user]$full_name</font>\n";
				echo "$row[start_datetime]|$row[end_datetime]|".date("Y-m-d H:i:s")."|$row[user]$full_name\n";
				}
			$current_time=date("YmdHi");
			$upd_from_time=preg_replace('/[^0-9]/', '', $from_date.$from_hour.$from_min);

			if ($current_time>=$upd_from_time) {echo "RELOAD_IMMEDIATELY";}
			exit;
			}
		else
			{
			echo "FAILED";
			exit;
			}
		} 
	else
		{
		echo "FAILED\n$error_msg";
		exit;
		}
	}

if ($action=="delete_interval" && $interval_id && $holiday_id)
	{
	$del_stmt="UPDATE inbound_disabled_entries set status='CANCELLED', modified_by='$PHP_AUTH_USER' where status in ('ACTIVE', 'LIVE') and interval_id='$interval_id'";
	$del_rslt=mysql_to_mysqli($del_stmt, $link);
	if (mysqli_affected_rows($link)>0)
		{
		$data_stmt="select * from inbound_disabled_entries where interval_id='$interval_id'";
		$data_rslt=mysql_to_mysqli($data_stmt, $link);
		$data_row=mysqli_fetch_array($data_rslt);
		$location=$data_row["location"];

		$location_array_delete=explode("|", $location);
		for ($n=0; $n<count($location_array_delete); $n++) 
			{
			$current_location=$location_array_delete[$n];
			if (isset($locations_calltimes_array["$current_location"]))
				{
				for ($q=0; $q<=count($locations_calltimes_array["$current_location"]); $q++)
					{
					$call_time=$locations_calltimes_array["$current_location"][$q];

					$stmt="SELECT ct_holidays from vicidial_call_times where call_time_id='".$call_time."';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$ct_holidays = $row[0];

					$holiday_pattern=$holiday_id."\_[0-9]+";
					$ct_holidays = preg_replace("/$holiday_pattern/i", '', $ct_holidays);
					$ct_holidays = preg_replace("/\|+/", '|', $ct_holidays);
					$stmt="UPDATE vicidial_call_times set ct_holidays='$ct_holidays' where call_time_id='".$call_time."';";
					# echo $stmt."<BR>";
					$rslt=mysql_to_mysqli($stmt, $link);

					### LOG INSERTION Admin Log Table ###
					$SQL_log = "$stmt|";
					$SQL_log = preg_replace('/;/', '', $SQL_log);
					$SQL_log = addslashes($SQL_log);
					$stmt="INSERT INTO vicidial_admin_log set event_date=now(), user='$PHP_AUTH_USER', ip_address='$ip', event_section='CALLTIMES', event_type='MODIFY', record_id='$call_time_id', event_code='ADMIN MODIFY CALL TIME REMOVE HOLIDAY RULE', event_sql=\"$SQL_log\", event_notes='".$call_time."';";
					if ($DB) {echo "|$stmt|\n";}
					$rslt=mysql_to_mysqli($stmt, $link);
					}
				}
			}

#		echo _QXZ("Holiday Rule Removed").": $holiday_rule<BR>\n";

		$ct_stmt="UPDATE vicidial_call_time_holidays set holiday_status='INACTIVE' where holiday_id like '$holiday_id%'";
		$ct_rslt=mysql_to_mysqli($ct_stmt, $link);		

		header("Location: $PHP_SELF");		
		}
	}



function CheckHolidayConflicts($locale, $locale_calltimes_array, $fdate, $fhour, $fmin, $tdate, $thour, $tmin, $holiday_id, $DB)
	{
	global $link;

	$error_msg="";
	$ok_msg="";
	$start_period=strtotime($fdate);
	$end_period=strtotime($tdate);

	$interval_time_array=array();
	for ($period=$start_period; $period<=$end_period; $period+=86400) {
		$key=date('Y-m-d', $period);
		$start_time="0000";
		$end_time="2359";
		if ($key==$fdate) {$start_time=$fhour.$fmin;}
		if ($key==$tdate) {$end_time=$thour.$tmin;}
		$interval_time_array["$key"]["start"]=$start_time;
		$interval_time_array["$key"]["end"]=$end_time;
	}


	/*  Skip ALL this, as duplicate entries are fine

	if ($DB) {print_r($interval_time_array);}
	if ($DB) {"<BR><BR>".print_r($interval_holiday_array);}

	$location_array=explode("|", $locale);
	for ($n=0; $n<count($location_array); $n++) 
		{
		$current_location=$location_array[$n];
		$call_times_array=$locale_calltimes_array["$current_location"];
	
		if ($DB) {echo "<BR>Location: ".$locale."<BR>";}

		$all_ct_holidays=array();
		$dup_check_stmt="select ct_holidays From vicidial_call_times where call_time_id in ('".implode("', '", $call_times_array)."') and ct_holidays is not null and length(ct_holidays)>=2";
		$dup_check_rslt=mysql_to_mysqli($dup_check_stmt, $link);

		if ($DB) {echo $dup_check_stmt."<BR>";}

		while($dc_row=mysqli_fetch_row($dup_check_rslt))
			{
			$ct_array=explode("|", $dc_row[0]);
			$ct_array=array_filter($ct_array);
			$all_ct_holidays=array_merge($all_ct_holidays, $ct_array);
			}
		$all_ct_holidays=array_unique($all_ct_holidays);
		print_r($all_ct_holidays);

		$active_holidays=array();
		$holiday_stmt="select holiday_id, holiday_date, ct_default_start, ct_default_stop from vicidial_call_time_holidays where holiday_id in ('".implode("', '", $all_ct_holidays)."') and holiday_date in ('".implode("', '", array_keys($interval_time_array))."') and holiday_status='ACTIVE'";
		if ($holiday_id) {$holiday_stmt.=" and holiday_id not like '".$holiday_id."%'";}
		$holiday_rslt=mysql_to_mysqli($holiday_stmt, $link);
		# if ($DB) {echo $holiday_stmt."<BR>"; print_r($holiday_rslt); exit;}
		while ($holiday_row=mysqli_fetch_array($holiday_rslt))
			{
			$holiday_id=$holiday_row["holiday_id"];
			$holiday_date=$holiday_row["holiday_date"];
			$ct_default_start=$holiday_row["ct_default_start"];
            $ct_default_stop=$holiday_row["ct_default_stop"];
          
			foreach ($interval_time_array as $date => $times)
				{
				$date=preg_replace('/X/', '', $date);
				if ($holiday_date==$date)
					{
					if (($times["start"]<$ct_default_start && $ct_default_start<$times["end"]) || ($times["start"]<$ct_default_stop && $ct_default_stop<$times["end"]))
						{
						$error_msg.="ERROR: New interval conflicts with holiday $holiday_id on $holiday_date.  Interval covers $times[start] to $times[end] - Holiday covers $ct_default_start to $ct_default_stop<BR>";
						}
					else
						{
						$ok_msg.="OK - Interval shares date with holiday $holiday_id on $holiday_date.  Interval covers $times[start] to $times[end] - Holiday covers $ct_default_start to $ct_default_stop<BR>";
						}
					}
				}
			}
		}
	*/
	return array($error_msg, $ok_msg, $interval_time_array);
	}



### UPDATE INTERVAL STATUSES
$upd_stmt="update inbound_disabled_entries set status='LIVE' where status='ACTIVE' and start_datetime<=now() and end_datetime>=now()";
$upd_rslt=mysql_to_mysqli($upd_stmt, $link);

$upd_stmt="update inbound_disabled_entries set status='COMPLETED' where status in ('LIVE', 'ACTIVE') and end_datetime<=now()";
$upd_rslt=mysql_to_mysqli($upd_stmt, $link);

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
$HEADER.="<TITLE>VOX inbound line controls</TITLE>\n";

header ("Content-type: text/html; charset=utf-8");
echo $HEADER;
require("admin_header.php");

echo "<form action='$PHP_SELF' method='post' id='inbound_form' name='inbound_form'>";
echo "<TABLE WIDTH=970 BGCOLOR=#E6E6E6 cellpadding=2 cellspacing=0><TR BGCOLOR=#E6E6E6><TD ALIGN=LEFT colspan='4'><FONT CLASS='standard_bold'>\n";
# echo $holiday_stmt."<BR>";
echo "Inbound line controls:";
echo "</TD><TD ALIGN=RIGHT><FONT CLASS='standard'> &nbsp; </TD></TR>\n";
?>

<TR BGCOLOR=#E6E6E6>
	<TD ALIGN='CENTER' colspan='2'><font class='standard_bold'>These settings will disable the inbound lines for the selected locations:</font></TD>
</TR>

<TR BGCOLOR=#E6E6E6>
	<TD ALIGN='LEFT' width='50'><font class='standard'>1) </font></TD>
	<TD ALIGN='LEFT'><font class='standard'>Please select the locations that you wish to disable Inbound calling:</font></TD>
</TR>

<TR BGCOLOR=#E6E6E6>
	<TD ALIGN='LEFT'><font class='standard'></TD>
	<TD ALIGN='LEFT'><font class='standard'>
	<?php
	$k=0;
	for ($l=0; $l<count($location_array); $l++)
		{
		$k++;
		# option - use checkboxes
		echo "<input type='radio' name='location' id='location".$k."' value='".trim($location_array[$l])."'".($location==$location_array[$l] ? " checked" : "").">".trim($location_array[$l])."<BR>\n";
		}
	$k++;
	echo "<input type='radio' name='location' id='location".$k."' value='".implode("|", $location_array)."'".($location==implode("|", $location_array) ? " checked" : "").">All locations";

	?>
	</font></TD>
</TR>

<TR BGCOLOR=#E6E6E6>
	<TD ALIGN='LEFT' width='50' valign='top'><font class='standard'>2) </font></TD>
	<TD ALIGN='LEFT' valign='top'>
	<TABLE WIDTH='880' cellpadding=3 cellspacing='0' border='0' valign='top'>
		<TR>
			<TD colspan='5' valign='top'><font class='standard'>While the inbound lines are down, please play the following turn-away message:</font> &nbsp;&nbsp;<font class='small_standard'><?php echo ($user_level==9 ? "<a href='admin.php?ADD=392111111111&container_id=INBOUND_LINE_CONTROLS'>(edit default messages)</a>" : ""); ?></font></TD>
		</TR>
		<TR BGCOLOR=#E6E6E6>
		<?php
			echo "\t\t\t<TD ALIGN='LEFT'>&nbsp;</TD>\n";

			$event_cols=5;
			foreach ($event_restrictions as $current_event => $required_user_level)
				{
				if(in_array($current_event, $expected_files) && $user_level<$required_user_level)
					{
					$event_cols--;
					}
				}

			for ($i=0; $i<count($expected_files); $i++)
				{
				$event=$expected_files[$i];
				$mand_lvl=($event=="CUSTOM" ? "7" : "9");

				if ($user_level>=$event_restrictions["$event"])
					{
					echo "\t\t\t<TD ALIGN='LEFT'><font class='standard'>\n";
					echo "\t\t\t\t<input type='radio' name='message' id='".$event."_message' value='".$audio_array["$event"]."' onClick=\"javascript:document.getElementById('message_type').value='".strtoupper($event)."'\" ".($message_type==strtoupper($event) ? "checked" : "").">".ucfirst($event)."</font>&nbsp;";
					echo ($user_level>=$mand_lvl ? "<font class='small_standard'><a href=\"javascript:launch_chooser('".$event."_field','date');\">change</a></font>" : "");
					echo "\n\t\t\t</TD>\n";
					}
				}
		?>
			<!--
			<TD ALIGN='LEFT'><font class='standard'><input type='radio' name='message' id='meeting_message' value='<?php echo $audio_array["meeting"]; ?>' onClick="javascript:document.getElementById('message_type').value='MEETING'" <?php echo ($message_type=="MEETING" ? "checked" : ""); ?>>Meeting</font>&nbsp;
			<?php echo ($user_level==9 ? "<font class='small_standard'><a href=\"javascript:launch_chooser('meeting_field','date');\">change</a></font>" : ""); #  StartRefresh();?>
			</TD>
			<TD ALIGN='LEFT'><font class='standard'><input type='radio' name='message' id='closed_message' value='<?php echo $audio_array["closed"]; ?>' onClick="javascript:document.getElementById('message_type').value='CLOSED'" <?php echo ($message_type=="CLOSED" ? "checked" : ""); ?>>Closed</font>&nbsp;
			<?php echo ($user_level==9 ? "<font class='small_standard'><a href=\"javascript:launch_chooser('closed_field','date');\">change</a></font>" : ""); #  StartRefresh(); ?>
			</TD>
			<TD ALIGN='LEFT'><font class='standard'><input type='radio' name='message' id='weather_message' value='<?php echo $audio_array["weather"]; ?>' onClick="javascript:document.getElementById('message_type').value='WEATHER'" <?php echo ($message_type=="WEATHER" ? "checked" : ""); ?>>Inclement weather</font>&nbsp;
			<?php echo ($user_level==9 ? "<font class='small_standard'><a href=\"javascript:launch_chooser('weather_field','date');\">change</a></font>" : ""); #  StartRefresh(); ?>
			</TD>
			<TD ALIGN='LEFT'><font class='standard'><input type='radio' name='message' id='custom_message' value='<?php echo $audio_array["custom"]; ?>' onClick="javascript:document.getElementById('message_type').value='CUSTOM'" <?php echo ($message_type=="CUSTOM" ? "checked" : ""); ?>>Custom</font>&nbsp;
			<?php echo ($user_level>=7 ? "<font class='small_standard'><a href=\"javascript:launch_chooser('custom_field','date');\">change</a></font>" : ""); #  StartRefresh(); ?>
			//-->
			</TD>
		</TR>
		<TR BGCOLOR=#E6E6E6>
		<?php

			echo "\t\t\t<TD ALIGN='LEFT' width='*' nowrap><font class='standard'>".($user_level==9 ? "<input type='submit' class='green_btn' name='default_submit' value='Set as defaults'>==>>" : "")."</TD>\n";

			for ($i=0; $i<count($expected_files); $i++)
				{
				$event=$expected_files[$i];
				if ($user_level>=$event_restrictions["$event"])
					{
					echo "<TD ALIGN='center' width='".floor(740/$event_cols)."'><font class='standard'>";
					echo "<input type='hidden' size='12' name='".$event."_field' id='".$event."_field' value='".$audio_array["$event"]."'>";
					echo "<span id='".$event."_span'>";
					echo ($audio_array["$event"] ? "<a href=\"$http_prefix$sounds_web_server/$sounds_web_directory/".$audio_array["$event"].".wav\" target='_blank'>".$audio_array["$event"]."</a>" : "(none set)");
					echo "</span>";
					echo "</font></TD>";
					}
				}
/*
			echo "<TD ALIGN='center' width='".floor(740/$event_cols)."'><font class='standard'>";
			echo "<input type='hidden' size='12' name='closed_field' id='closed_field' value='".$audio_array["closed"]."'>";
			echo "<span id='closed_span'>";
			echo ($audio_array["closed"] ? "<a href=\"$http_prefix$sounds_web_server/$sounds_web_directory/".$audio_array["closed"].".wav\" target='_blank'>".$audio_array["closed"]."</a>" : "(none set)");
			echo "</span>";
			echo "</font></TD>";
			
			echo "<TD ALIGN='center' width='".floor(740/$event_cols)."'><font class='standard'>";
			echo "<input type='hidden' size='12' name='weather_field' id='weather_field' value='".$audio_array["weather"]."'>";
			echo "<span id='weather_span'>";
			echo ($audio_array["weather"] ? "<a href=\"$http_prefix$sounds_web_server/$sounds_web_directory/".$audio_array["weather"].".wav\" target='_blank'>".$audio_array["weather"]."</a>" : "(none set)");
			echo "</span>";
			echo "</font></TD>";
			
			echo "<TD ALIGN='center' width='".floor(740/$event_cols)."'><font class='standard'>";
			echo "<input type='hidden' size='12' name='holiday_field' id='holiday_field' value='".$audio_array["holiday"]."'>";
			echo "<span id='holiday_span'>";
			echo ($audio_array["holiday"] ? "<a href=\"$http_prefix$sounds_web_server/$sounds_web_directory/".$audio_array["holiday"].".wav\" target='_blank'>".$audio_array["holiday"]."</a>" : "(none set)");
			echo "</span>";
			
			echo "<TD ALIGN='center' width='".floor(740/$event_cols)."'><font class='standard'>";
			echo "<input type='hidden' size='12' name='custom_field' id='custom_field' value='".$audio_array["custom"]."'>";
			echo "<span id='custom_span'>";
			echo ($audio_array["custom"] ? "<a href=\"$http_prefix$sounds_web_server/$sounds_web_directory/".$audio_array["custom"].".wav\" target='_blank'>".$audio_array["custom"]."</a>" : "(none set)");
			echo "</span>";
*/
		?>
		</TR>
		<TR>
			<TD>&nbsp;</td>
			<TD colspan='<?php echo $event_cols; ?>' align='right'>
			<font class='standard'>
			<?php 
			if (file_exists('../agc/vdc_AWS_polly_TTS.php'))
				{
				echo "<input type='button' class='blue_btn' style='width:180px' onClick=\"ToggleSpan('custom_msg_span'); document.getElementById('submit_custom_msg_span').innerHTML='';\" value='CREATE CUSTOM MESSAGE'>";
				echo "<span id='custom_msg_span' style='display:none'><BR>Enter custom message text here:<BR>\n";
				echo "<textarea rows='3' cols='30' name='custom_msg_field' id='custom_msg_field'></textarea><BR>\n";
				echo "<input type='button' class='red_btn' onClick=\"PollyGenerator('')\" value='SUBMIT MESSAGE' style='width:242px'>";
				echo "</span>";
				}
			else
				{
				echo "&nbsp;";
				}
			?>
			<span id='submit_custom_msg_span'>
			</span>
			</font></TD>
		</TR>
	</TABLE>
	</TD>
	</TR>
<TR BGCOLOR=#E6E6E6>
	<TD ALIGN='LEFT' width='50'><font class='standard'>3) </font></TD>
	<TD ALIGN='LEFT'><font class='standard'>Select the dates and times that the inbound lines should be down:</font></TD>
</TR>
<TR BGCOLOR=#E6E6E6>
	<TD ALIGN='LEFT' width='50'><font class='standard'>&nbsp; </font></TD>
	<TD ALIGN='LEFT' valign='top'>
	<TABLE WIDTH='500' cellpadding=3 cellspacing='0'>
		<TR>
		<TD ALIGN='LEFT' width='150'>
			<input type=text id=from_date name=from_date value="<?php echo ($from_date ? $from_date : date("Y-m-d")); ?>" size=10 maxsize=10>
			<script language="JavaScript">
			var o_cal = new tcal ({
				// form name
				'formname': 'inbound_form',
				// input name
				'controlname': 'from_date'
			});
			o_cal.a_tpl.yearscroll = false;
			// o_cal.a_tpl.weekstart = 1; // Monday week start
			</script><BR><BR>
			<input list="from_hour_list" onclick="this.value=''" type="TEXT" id="from_hour" name="from_hour" size="2" maxlength="2" value="<?php echo $from_hour; ?>">
				<datalist id="from_hour_list">
				<option data-value="00" value="00"></option>
				<option data-value="01" value="01"></option>
				<option data-value="02" value="02"></option>
				<option data-value="03" value="03"></option>
				<option data-value="04" value="04"></option>
				<option data-value="05" value="05"></option>
				<option data-value="06" value="06"></option>
				<option data-value="07" value="07"></option>
				<option data-value="08" value="08"></option>
				<option data-value="09" value="09"></option>
				<option data-value="10" value="10"></option>
				<option data-value="11" value="11"></option>
				<option data-value="12" value="12"></option>
				<option data-value="13" value="13"></option>
				<option data-value="14" value="14"></option>
				<option data-value="15" value="15"></option>
				<option data-value="16" value="16"></option>
				<option data-value="17" value="17"></option>
				<option data-value="18" value="18"></option>
				<option data-value="19" value="19"></option>
				<option data-value="20" value="20"></option>
				<option data-value="21" value="21"></option>
				<option data-value="22" value="22"></option>
				<option data-value="23" value="23"></option>
				</datalist>
			:<input list="from_min_list" onclick="this.value=''" type="TEXT" id="from_min" name="from_min" size="2" maxlength="2" value="<?php echo $from_min; ?>">
				<datalist id="from_min_list">
				<option data-value="00" value="00"></option>
				<option data-value="05" value="05"></option>
				<option data-value="10" value="10"></option>
				<option data-value="15" value="15"></option>
				<option data-value="20" value="20"></option>
				<option data-value="25" value="25"></option>
				<option data-value="30" value="30"></option>
				<option data-value="35" value="35"></option>
				<option data-value="40" value="40"></option>
				<option data-value="45" value="45"></option>
				<option data-value="50" value="50"></option>
				<option data-value="55" value="55"></option>
				</datalist>
		</TD>
		<TD ALIGN='CENTER' class='standard' width='30'>to</TD>
		<TD ALIGN='LEFT' width="*">		
			<input type=text id=to_date name=to_date value="<?php echo ($to_date ? $to_date : date("Y-m-d")); ?>" size=10 maxsize=10>
			<script language="JavaScript">
			var o_cal = new tcal ({
				// form name
				'formname': 'inbound_form',
				// input name
				'controlname': 'to_date'
			});
			o_cal.a_tpl.yearscroll = false;
			// o_cal.a_tpl.weekstart = 1; // Monday week start
			</script><BR><BR>
			<input list="to_hour_list" onclick="this.value=''" type="TEXT" id="to_hour" name="to_hour" size="2" maxlength="2" value="<?php echo $to_hour; ?>">
				<datalist id="to_hour_list">
				<option data-value="00" value="00"></option>
				<option data-value="01" value="01"></option>
				<option data-value="02" value="02"></option>
				<option data-value="03" value="03"></option>
				<option data-value="04" value="04"></option>
				<option data-value="05" value="05"></option>
				<option data-value="06" value="06"></option>
				<option data-value="07" value="07"></option>
				<option data-value="08" value="08"></option>
				<option data-value="09" value="09"></option>
				<option data-value="10" value="10"></option>
				<option data-value="11" value="11"></option>
				<option data-value="12" value="12"></option>
				<option data-value="13" value="13"></option>
				<option data-value="14" value="14"></option>
				<option data-value="15" value="15"></option>
				<option data-value="16" value="16"></option>
				<option data-value="17" value="17"></option>
				<option data-value="18" value="18"></option>
				<option data-value="19" value="19"></option>
				<option data-value="20" value="20"></option>
				<option data-value="21" value="21"></option>
				<option data-value="22" value="22"></option>
				<option data-value="23" value="23"></option>
				</datalist>
			:<input list="to_min_list" onclick="this.value=''" type="TEXT" id="to_min" name="to_min" size="2" maxlength="2" value="<?php echo $to_min; ?>">
				<datalist id="to_min_list">
				<option data-value="00" value="00"></option>
				<option data-value="05" value="05"></option>
				<option data-value="10" value="10"></option>
				<option data-value="15" value="15"></option>
				<option data-value="20" value="20"></option>
				<option data-value="25" value="25"></option>
				<option data-value="30" value="30"></option>
				<option data-value="35" value="35"></option>
				<option data-value="40" value="40"></option>
				<option data-value="45" value="45"></option>
				<option data-value="50" value="50"></option>
				<option data-value="55" value="55"></option>
				</datalist>
		</TD>
		</TR>
	</TABLE>
	<font class='standard_bold' color='#900'>** All times for inbound disable are in <?php echo substr(date("T"), 0, 1).substr(date("T"), -1); ?> time, using 24-hour military time **</font><BR>&nbsp;
	</TD>
</TR>

<TR BGCOLOR=#E6E6E6>
	<TD ALIGN='LEFT' width='50'><font class='standard'>4) </font></TD>
	<TD ALIGN='LEFT'><font class='standard'>Hit Submit to Execute :</font>&nbsp;&nbsp;<input type='button' onClick="ValidateForm(this.form)" name='submit_schedule' id='submit_schedule' class='red_btn' value='SUBMIT'><input type='hidden' id='insert_flag' name='insert_flag' value=''>
	<?php # echo "<BR>$insert_flag|$action|$location|$message|$message_type|$from_date|$from_hour|$from_min|$to_date|$to_hour|$to_min"; ?>
	</TD>
</TR>

<?php
if (strlen($error_msg)>0) 
	{
	echo "<TR BGCOLOR=#F00>";
	echo "<TD ALIGN='center' colspan='2'>";
	echo "<font class='standard_bold' color='#FFF'>$error_msg</font>";
	echo "</TD>";
	echo "</TR>";
	}
?>

<?php
usleep(500000); # Need this to keep updates/inserts correct, the above insert/delete processes don't seem to finish fast enough be

$stmt="select *, if(start_datetime<=now() and end_datetime>=now(), 0, 1) as live, if(status='LIVE', 0, if(status='ACTIVE', 1, 2)) as dispo_rank from inbound_disabled_entries where status in ('ACTIVE', 'LIVE') order by dispo_rank asc, location desc, live asc, start_datetime asc";
$rslt=mysql_to_mysqli($stmt, $link);
if (mysqli_num_rows($rslt)>0)
	{
	echo "<TR BGCOLOR=#E6E6E6>\n";
	echo "\t<TD ALIGN='LEFT' colspan='2'>\n";

	$starttime_str="";
	$endtime_str="";
	$array_str="";

	echo "<table width='100%' align='center' cellpadding='3' cellspacing='0' border='0'>\n";

#	echo "\t<TD ALIGN='LEFT' width='50' colspan='2'><font class='standard_bold'><BR><BR><BR>Currently Active Inbound Disable Status:</font></TD>\n";
#	echo "</TR>\n";

	$i=0;
	while ($row=mysqli_fetch_array($rslt))
		{

		if($row["dispo_rank"]==0 && !$live_header)
			{
			$live_header="<TR BGCOLOR=#E6E6E6>\n\t<TD ALIGN='LEFT' colspan='9'><font class='standard_bold'><BR>CURRENTLY LIVE DOWNTIMES:</font><HR></TD>\n</TR>\n";
			echo $live_header;
			echo "<TR BGCOLOR=#E6E6E6>\n";
			echo "<TD>&nbsp;</TD>";
			echo "<TH><font class='standard_bold'>Created By</font></TH>";
			echo "<TH><font class='standard_bold'>Interval Start Date/Time</font></TH>";
			echo "<TH><font class='standard_bold'>Interval End Date/Time</font></TH>";
			echo "<TH><font class='standard_bold'>Location</font></TH>";
			echo "<TH><font class='standard_bold'>Message</font></TH>";
			echo "<TH><font class='standard_bold'>Status</font></TH>";
			echo "<TH><font class='standard_bold'>Last Modified</font></TH>";
			echo "<TH><font class='standard_bold'>Last Modified By</font></TH>";
			echo "</TR>\n";
			}
		else if($row["dispo_rank"]==1 && !$active_header)
			{
			$active_header="<TR BGCOLOR=#E6E6E6>\n\t<TD ALIGN='LEFT' colspan='9'><font class='standard_bold'><BR>UPCOMING SCHEDULED DOWNTIMES:</font><HR></TD>\n</TR>\n";
			echo $active_header;
			# echo "<BR><BR>";
#			echo "<TR BGCOLOR=#E6E6E6>\n";
#			echo "\t<TD ALIGN='LEFT' colspan='2'>\n";
			echo "<table width='100%' align='center' cellpadding='5' cellspacing='0' border='0'>\n";
			echo "<TR BGCOLOR=#E6E6E6>\n";
			echo "<TD>&nbsp;</TD>";
			echo "<TH><font class='standard_bold'>Created By</font></TH>";
			echo "<TH><font class='standard_bold'>Interval Start Date/Time</font></TH>";
			echo "<TH><font class='standard_bold'>Interval End Date/Time</font></TH>";
			echo "<TH><font class='standard_bold'>Location</font></TH>";
			echo "<TH><font class='standard_bold'>Message</font></TH>";
			echo "<TH><font class='standard_bold'>Status</font></TH>";
			echo "<TH><font class='standard_bold'>Last Modified</font></TH>";
			echo "<TH><font class='standard_bold'>Last Modified By</font></TH>";
			echo "</TR>\n";
			$i=0;
			}

		
		if ($i%2==0)
			{$bgcolor="FFFFFF";} else {$bgcolor="E6E6E6";}

		$full_name="";
		$fn_stmt="select full_name from vicidial_users where user='$row[user]'";
		$fn_rslt=mysql_to_mysqli($fn_stmt, $link);
		if(mysqli_num_rows($fn_rslt)>0)
			{
			$fn_row=mysqli_fetch_row($fn_rslt);
			$full_name="<BR>$fn_row[0]";
			}

		$modify_name="";
		$fn_stmt="select full_name from vicidial_users where user='$row[modified_by]'";
		$fn_rslt=mysql_to_mysqli($fn_stmt, $link);
		if(mysqli_num_rows($fn_rslt)>0)
			{
			$fn_row=mysqli_fetch_row($fn_rslt);
			$modify_name="<BR>$fn_row[0]";
			}

		$font_class=($row["status"]=="LIVE" ? "blink" : "small_standard");

		$location=preg_replace('/\|/', ', ', $row["location"]);

#		echo "<TD valign='top' rowspan='2'><p class='$font_class'>$row[status]</p></TD>";
#		echo "<TD ALIGN='LEFT' valign='top'><p class='$font_class'><span id='downtime_info_".$row["interval_id"]."'>Inbound $location lines are scheduled to be down from ".substr($row["start_datetime"], 0, -3)." to ".substr($row["end_datetime"], 0, -3).", requested by $row[user]$full_name<BR> Message being played: <a href=\"$http_prefix$sounds_web_server/$sounds_web_directory/".$row["message"].".wav\" target='_blank'>".$row["message"]."</a></span></p></td>";
#		echo "</TR>";

		echo "<TR BGCOLOR=#".$bgcolor.">\n";
		echo "<TD ALIGN='LEFT' valign='middle' rowspan='2' nowrap>\n";
		echo "<input type='button' class='green_btn' style='width:60px' onClick=\"ToggleSpan('active_downtime_".$row["interval_id"]."')\" value='MODIFY'>&nbsp;";
		if ($row["status"]!="LIVE")
			{
			$starttime_str.="'".preg_replace('/[^0-9]/', '', $row["start_datetime"])."', ";
			$button_value="CANCEL";
			}
		else
			{
			$endtime_str.="'".preg_replace('/[^0-9]/', '', $row["end_datetime"])."', ";
			$button_value="RESUME";
			}
		$array_str.="\n['$row[interval_id]', '".preg_replace('/[^0-9]/', '', $row["start_datetime"])."', '".preg_replace('/[^0-9]/', '', $row["end_datetime"])."', '".$row["status"]."'],";
		echo "<input type='button' class='red_btn' style='width:60px' onClick=\"CancelInterval('".$row["interval_id"]."', '".$row["holiday_id"]."', this.form)\" value='$button_value'>";
		echo "</TD>\n";
		echo "<TD><font class='$font_class'>$row[user]$full_name</font></TD>";
		echo "<TD nowrap align='center'><font class='$font_class'><span id='start_datetime_".$row["interval_id"]."'>".substr($row["start_datetime"], 0, -3)."</span></font></TD>";
		echo "<TD nowrap align='center'><font class='$font_class'><span id='end_datetime_".$row["interval_id"]."'>".substr($row["end_datetime"], 0, -3)."</span></font></TD>";
		echo "<TD><font class='$font_class'>$location</font></TD>";
		echo "<TD nowrap align='center'><font class='$font_class'>$row[message_type]<BR><a href=\"$http_prefix$sounds_web_server/$sounds_web_directory/".$row["message"].".wav\" target='_blank'>Play message</a></font></TD>";
		echo "<TD><font class='$font_class'>".($row["status"]!="LIVE" ? "<font color='#009'><B>SCHEDULED</B></font>" : "LIVE")."</font></TD>";
		echo "<TD nowrap align='center'><font class='$font_class'><span id='modify_date_".$row["interval_id"]."'>$row[modify_date]</span></font></TD>";
		echo "<TD><font class='$font_class'><span id='modify_user_".$row["interval_id"]."'>$row[modified_by]$modify_name</span></font></TD>";
		echo "</TR>\n";

		echo "<TR BGCOLOR=#".$bgcolor.">\n";
		echo "\t<TD colspan='8' align='center' class='small_standard'>\n";
		echo "<span id='active_downtime_".$row["interval_id"]."' style='display:none'>";
		echo "Modify disabled inbound interval:<BR>";
		$startdate_array=explode(" ", $row["start_datetime"]);
		$starttime_array=explode(":", $startdate_array[1]);
		$enddate_array=explode(" ", $row["end_datetime"]);
		$endtime_array=explode(":", $enddate_array[1]);
		echo "<table width='650' BGCOLOR=#".$bgcolor.">";
		echo "<tr>";
		echo "<td align='right'>";
		if ($row["status"]!="LIVE")
			{
			echo "<input type=text id=from_date_".$row["interval_id"]." name=from_date_".$row["interval_id"]." value=\"".$startdate_array[0]."\" size=10 maxsize=10>";
			echo "\t<script language=\"JavaScript\">\n";
			echo "\tvar o_cal = new tcal ({\n";
			echo "\t\t// form name\n";
			echo "\t\t'formname': 'inbound_form',\n";
			echo "\t\t// input name\n";
			echo "\t\t'controlname': 'from_date_".$row["interval_id"]."'\n";
			echo "\t});\n";
			echo "\to_cal.a_tpl.yearscroll = false;\n";
			echo "// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
			echo "</script>\n";
			echo "&nbsp;&nbsp;";
			echo "<input list=\"from_hour_list\" onclick=\"this.value=''\" type=\"TEXT\" id=\"from_hour_".$row["interval_id"]."\" name=\"from_hour_".$row["interval_id"]."\" value=\"".$starttime_array[0]."\" size=\"2\" maxlength=\"2\">:";
			echo "<input list=\"from_min_list\" onclick=\"this.value=''\" type=\"TEXT\" id=\"from_min_".$row["interval_id"]."\" name=\"from_min_".$row["interval_id"]."\" value=\"".$starttime_array[1]."\" size=\"2\" maxlength=\"2\">"; 
			}
		else
			{
			echo "<font class='standard_bold'>\n";
			echo "<input type=hidden id=\"from_date_".$row["interval_id"]."\" name=\"from_date_".$row["interval_id"]."\" value=\"".$startdate_array[0]."\">$startdate_array[0]";
			echo "&nbsp;&nbsp;";
			echo "<input type=hidden id=\"from_hour_".$row["interval_id"]."\" name=\"from_hour_".$row["interval_id"]."\" value=\"".$starttime_array[0]."\">$starttime_array[0]:";
			echo "<input type=hidden id=\"from_min_".$row["interval_id"]."\" name=\"from_min_".$row["interval_id"]."\" value=\"".$starttime_array[1]."\">$starttime_array[1]";
			echo "</font>";
			}
		echo "</td>";
		echo "<td align='center' class='small_standard'>To:</td>";
		echo "<td align='left'><input type=text id=to_date_".$row["interval_id"]." name=to_date_".$row["interval_id"]." value=\"".$enddate_array[0]."\" size=10 maxsize=10>";
		echo "\t<script language=\"JavaScript\">\n";
		echo "\tvar o_cal = new tcal ({\n";
		echo "\t\t// form name\n";
		echo "\t\t'formname': 'inbound_form',\n";
		echo "\t\t// input name\n";
		echo "\t\t'controlname': 'to_date_".$row["interval_id"]."'\n";
		echo "\t});\n";
		echo "\to_cal.a_tpl.yearscroll = false;\n";
		echo "// o_cal.a_tpl.weekstart = 1; // Monday week start\n";
		echo "</script>\n";
		echo "&nbsp;&nbsp;";
		echo "<input list=\"to_hour_list\" onclick=\"this.value=''\" type=\"TEXT\" id=\"to_hour_".$row["interval_id"]."\" name=\"to_hour_".$row["interval_id"]."\" value=\"".$endtime_array[0]."\" size=\"2\" maxlength=\"2\">:";
		echo "<input list=\"to_min_list\" onclick=\"this.value=''\" type=\"TEXT\" id=\"to_min_".$row["interval_id"]."\" name=\"to_min_".$row["interval_id"]."\" value=\"".$endtime_array[1]."\" size=\"2\" maxlength=\"2\"></td>";
		echo "</tr>";
		echo "<tr>";
		echo "<th colspan='3'><input type='button' class='tiny_blue_btn' onClick=\"UpdateInterval(".$row["interval_id"].", '".$row["holiday_id"]."')\" value='UPDATE INTERVAL DATE/TIMES'></th>";
		echo "</tr>";
		echo "</table>";
		echo "</span>";

		echo "</TD>";
		echo "</TR>\n";
#		echo "<TR BGCOLOR=#E6E6E6>\n";
#		echo "<TD valign='top' colspan='9' height='20'>&nbsp;";
#		echo "</TD>";
#		echo "</TR>\n";
		$i++;
		}
	$starttime_str=preg_replace('/, $/', '', $starttime_str);
	$endtime_str=preg_replace('/, $/', '', $endtime_str);
	$array_str=preg_replace('/,$/', '', $array_str);

	echo "\t</table>\n\t</TD>\n</TR>\n";
	}

$stmt="select * from inbound_disabled_entries where status in ('COMPLETED', 'CANCELLED')  and end_datetime>=now()-INTERVAL $completed_days DAY order by status asc, end_datetime desc, start_datetime desc";
$rslt=mysql_to_mysqli($stmt, $link);
if (mysqli_num_rows($rslt)>0)
	{
	echo "<TR BGCOLOR=#E6E6E6>\n\t<TD ALIGN='LEFT' colspan='2'><font class='standard_bold'><BR><BR><BR>Recent Completed/Cancelled Disabled Inbound - past $completed_days days:</font></TD>\n</TR>\n";
	echo "<TR BGCOLOR=#E6E6E6><TD ALIGN='LEFT' colspan='2'>";
	echo "<table width='100%' align='center' cellpadding='3' cellspacing='0'>\n";
	echo "<tr>\n";
	echo "\t<th class='standard_bold'>Created by</th>\n";
	echo "\t<th class='standard_bold'>Interval start date/time</th>\n";
	echo "\t<th class='standard_bold'>Interval end date/time</th>\n";
	echo "\t<th class='standard_bold'>Location</th>\n";
	echo "\t<th class='standard_bold'>Message</th>\n";
	echo "\t<th class='standard_bold'>Status</th>\n";
	echo "\t<th class='standard_bold'>Last Modified</th>\n";
	echo "\t<th class='standard_bold'>Modified By</th>\n";
	echo "</tr>\n";

	$i=0;
	while($row=mysqli_fetch_array($rslt))
		{
		if ($i%2==0)
			{$bgcolor="FFFFFF";} else {$bgcolor="E6E6E6";}

		$location=preg_replace('/\|/', ', ', $row["location"]);

		$full_name="";
		$fn_stmt="select full_name from vicidial_users where user='$row[user]'";
		$fn_rslt=mysql_to_mysqli($fn_stmt, $link);
		if(mysqli_num_rows($fn_rslt)>0)
			{
			$fn_row=mysqli_fetch_row($fn_rslt);
			$full_name=", $fn_row[0]";
			}


		echo "<tr bgcolor='#".$bgcolor."'>\n";
		echo "\t<td align='center' class='small_standard'>".$row["user"].$full_name."</td>\n";
		echo "\t<td align='center' class='small_standard'>".substr($row["start_datetime"], 0, -3)."</td>\n";
		echo "\t<td align='center' class='small_standard'>".substr($row["end_datetime"], 0, -3)."</td>\n";
		echo "\t<td align='center' class='small_standard'>".$location."</td>\n";
		echo "\t<td align='center' class='small_standard'>".$row["message_type"]." - ".$row["message"]."</td>\n";
		echo "\t<td align='center' class='small_standard'>".$row["status"]."</td>\n";
		echo "\t<td align='center' class='small_standard'>".$row["modify_date"]."</td>\n";
		echo "\t<td align='center' class='small_standard'>".$row["modified_by"]."</td>\n";
		echo "</tr>\n";
		$i++;
		}

	echo "</table>\n";
	echo "</TD></TR>\n";
	}
?>

</TABLE>
<input type='hidden' name='message_type' id='message_type' value="<?php echo $message_type; ?>">
</FORM>

<script language="Javascript">
var rInt=null;
var tInt=null;
var local_time=null;

function srvTime(){
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
		var_str = "action=get_local_time";
		// alert(var_str); 
		xmlhttp.open('POST', post_URL); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(var_str); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				var update_results = null;
				update_results = xmlhttp.responseText;
				local_time=update_results;
				local_time=local_time.replace(/^\n+/, '');
				return local_time;
				}
			}
		delete xmlhttp;
		}
}

function UpdateInterval(interval_id, holiday_id)
	{
	var local_timestamp=srvTime();

	var from_date_field='from_date_'+interval_id;
	var from_hour_field='from_hour_'+interval_id;
	var from_min_field='from_min_'+interval_id;

	var to_date_field='to_date_'+interval_id;
	var to_hour_field='to_hour_'+interval_id;
	var to_min_field='to_min_'+interval_id;



	var fromTimeStamp=document.getElementById(from_date_field).value+document.getElementById(from_hour_field).value+document.getElementById(from_min_field).value;
	fromTimeStamp = fromTimeStamp.replace(/[^0-9]/g,'');

	var toTimeStamp=document.getElementById(to_date_field).value+document.getElementById(to_hour_field).value+document.getElementById(to_min_field).value;
	toTimeStamp = toTimeStamp.replace(/[^0-9]/g,'');
	
	if (toTimeStamp<=fromTimeStamp)
		{
		alert("Please select a 'from' date/time that is earlier than the 'to' date/time");
		return false;
		}
	
	// Get local time for server
	var offset=<?php echo date("Z")/3600; ?>;
	var nd= new Date();
	var utc=nd.getTime()+(nd.getTimezoneOffset()*60000);
	var currentdate=new Date(utc+(3600000*offset));

	// alert(currentdate+"\n"+nd); return false;
	curMonth="0"+(currentdate.getMonth()+1);
	curDate="0"+currentdate.getDate();
	curHour="0"+currentdate.getHours();
	curMin="0"+currentdate.getMinutes();
	var currentTimeStamp=""+currentdate.getFullYear() + curMonth.substring(curMonth.length - 2) + curDate.substring(curDate.length - 2) + curHour.substring(curHour.length - 2) + curMin.substring(curMin.length - 2); 
	currentTimeStamp = currentTimeStamp.replace(/[^0-9]/g,'');
	if (currentTimeStamp>=toTimeStamp)
		{
		alert("Invalid - time interval has already passed.");
		return false;
		}


	var confirmUpdateSuccess = new RegExp("^SUCCESS","g");
	var reloadPageFlag = new RegExp("RELOAD_IMMEDIATELY","g");

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
		var_str = "action=update_interval&interval_id="+interval_id+"&holiday_id="+holiday_id+"&from_date="+document.getElementById(from_date_field).value+"&from_hour="+document.getElementById(from_hour_field).value+"&from_min="+document.getElementById(from_min_field).value+"&to_date="+document.getElementById(to_date_field).value+"&to_hour="+document.getElementById(to_hour_field).value+"&to_min="+document.getElementById(to_min_field).value;
		// alert(var_str); 
		xmlhttp.open('POST', post_URL); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(var_str); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				var update_results = null;
				update_results = xmlhttp.responseText;
				update_results=update_results.replace(/^\n+/, '');
				if (update_results.match(reloadPageFlag))
					{
					// alert("reloading...");
					window.location.href="<?php echo $PHP_SELF; ?>";
					}
				else if (update_results.match(confirmUpdateSuccess))
					{
					update_array=update_results.split("\n");
					// alert(update_results);
					// result_span="downtime_info_"+interval_id;
					// document.getElementById(result_span).innerHTML=update_array[1];

					var individualResults=update_array[2].split("\|");
					start_span="start_datetime_"+interval_id;
					document.getElementById(start_span).innerHTML="<font color='#900'><B>"+individualResults[0]+"</B></font>";
					end_span="end_datetime_"+interval_id;
					document.getElementById(end_span).innerHTML="<font color='#900'><B>"+individualResults[1]+"</B></font>";
					date_span="modify_date_"+interval_id;
					document.getElementById(date_span).innerHTML="<font color='#900'><B>"+individualResults[2]+"</B></font>";
					user_span="modify_user_"+interval_id;
					document.getElementById(user_span).innerHTML="<font color='#900'><B>"+individualResults[3]+"</font>";

					for (q=0; q<OpenIntervals.length; q++)
						{
						if (OpenIntervals[q][0]==interval_id)
							{
							newFromDateTime=document.getElementById(from_date_field).value+document.getElementById(from_hour_field).value+document.getElementById(from_min_field).value+"00";
							newToDateTime=document.getElementById(to_date_field).value+document.getElementById(to_hour_field).value+document.getElementById(to_min_field).value+"00";

							newFromDateTime = newFromDateTime.replace(/[^0-9]/g,'');
							newToDateTime = newToDateTime.replace(/[^0-9]/g,'');

							OpenIntervals[q][1]=newFromDateTime;
							OpenIntervals[q][2]=newToDateTime;
							}
						}
					}
				else
					{
					alert("Update failed\n\n"+update_results);
					}
				}
			}
		delete xmlhttp;
		}

	}

function CancelInterval(interval_id, holiday_id, formInfo)
	{
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
		var_str = "action=delete_interval&interval_id="+interval_id+"&holiday_id="+holiday_id;
		xmlhttp.open('POST', post_URL); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(var_str); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				var update_results = null;
				update_results = xmlhttp.responseText;
				}
			}
		delete xmlhttp;
		}

	formInfo.reset();
	formInfo.submit();
	}

function ValidateForm(formInfo)
	{
	var temp_location=null;
	locations=document.getElementsByName('location');
	for (i=0; i < locations.length; i++)
		{
		if (locations[i].checked==true) 
			{
			temp_location=locations[i].value;
			}	
		}

	var temp_message=null;
	messages=document.getElementsByName('message');
	for (i=0; i < messages.length; i++)
		{
		if (messages[i].checked==true) 
			{
			temp_message=messages[i].value;
			}	
		}

	var fromTimeStamp=document.getElementById('from_date').value+document.getElementById('from_hour').value+document.getElementById('from_min').value;
	fromTimeStamp = fromTimeStamp.replace(/[^0-9]/g,'');

	var toTimeStamp=document.getElementById('to_date').value+document.getElementById('to_hour').value+document.getElementById('to_min').value;
	toTimeStamp = toTimeStamp.replace(/[^0-9]/g,'');

	if (!temp_location || !temp_message || fromTimeStamp.length!=12 || toTimeStamp.length!=12)
		{
		alert("Please fill out the form completely.  Location, message, and a complete from and to date/time are required."+"\n"+temp_location+"\n"+temp_message+"\n"+fromTimeStamp+"\n"+toTimeStamp);
		return false;
		}

	if (toTimeStamp<=fromTimeStamp)
		{
		alert("Please select a 'from' date/time that is earlier than the 'to' date/time");
		return false;
		}

	// var currentdate = new Date();

	// Get local time for server
	var offset=<?php echo date("Z")/3600; ?>;
	var nd= new Date();
	var utc=nd.getTime()+(nd.getTimezoneOffset()*60000);
	var currentdate=new Date(utc+(3600000*offset));
	
	curMonth="0"+(currentdate.getMonth()+1);
	curDate="0"+currentdate.getDate();
	curHour="0"+currentdate.getHours();
	curMin="0"+currentdate.getMinutes();

	var currentDateStamp=""+currentdate.getFullYear() + curMonth.substring(curMonth.length - 2) + curDate.substring(curDate.length - 2) + "0000";
	currentDateStamp = currentDateStamp.replace(/[^0-9]/g,'');

	var currentTimeStamp=""+currentdate.getFullYear() + curMonth.substring(curMonth.length - 2) + curDate.substring(curDate.length - 2) + curHour.substring(curHour.length - 2) + curMin.substring(curMin.length - 2); 
	currentTimeStamp = currentTimeStamp.replace(/[^0-9]/g,'');
	if (currentDateStamp>=fromTimeStamp)
		{
		alert("Invalid - date has already passed.");
		return false;
		}
	if (currentTimeStamp>=toTimeStamp)
		{
		alert("Invalid - time interval has already passed.");
		return false;
		}
	

	document.getElementById('insert_flag').value=1;
	formInfo.submit();
	}

function StartRefresh() 
	{
	var rInt=window.setInterval(function() {RefreshLinks()}, 1000);
	var tInt=window.setInterval(function() {IntervalCheck()}, 1000);
	}

function RefreshLinks() {
	var http_prefix='<?php echo $http_prefix; ?>';
	var sounds_web_server='<?php echo $sounds_web_server; ?>';
	var sounds_web_directory='<?php echo $sounds_web_directory; ?>';

<?php
for ($i=0; $i<count($expected_files); $i++)
	{
	$event=$expected_files[$i];
	if ($user_level>=$event_restrictions["$event"])
		{
		echo "\tif (document.getElementById('".$event."_field').value!=\"\")\n"; 
		echo "\t\t{\n";
		echo "\t\t".$event."HTML=\"<a href=\\\"\"+http_prefix+sounds_web_server+\"/\"+sounds_web_directory+\"/\"+document.getElementById('".$event."_field').value+\".wav\\\" target='_blank'>\"+document.getElementById('".$event."_field').value+\"</a>\";\n";
		echo "\t\tdocument.getElementById('".$event."_message').value=document.getElementById('".$event."_field').value;\n";
		echo "\t\t}\n";
		echo "\telse\n";
		echo "\t\t{\n";
		echo "\t\t".$event."HTML=\"(none set)\";\n";
		echo "\t}\n";
		echo "\tdocument.getElementById('".$event."_span').innerHTML=".$event."HTML;\n\n";
		}
	}
?>
/*
	if (document.getElementById('meeting_field').value!="") 
		{
		meetingHTML="<a href=\""+http_prefix+sounds_web_server+"/"+sounds_web_directory+"/"+document.getElementById('meeting_field').value+".wav\" target='_blank'>"+document.getElementById('meeting_field').value+"</a>";
		document.getElementById('meeting_message').value=document.getElementById('meeting_field').value;
		}
	else
		{
		meetingHTML="(none set)";
		}
	document.getElementById('meeting_span').innerHTML=meetingHTML;

	if (document.getElementById('closed_field').value!="") 
		{
		closedHTML="<a href=\""+http_prefix+sounds_web_server+"/"+sounds_web_directory+"/"+document.getElementById('closed_field').value+".wav\" target='_blank'>"+document.getElementById('closed_field').value+"</a>";
		document.getElementById('closed_message').value=document.getElementById('closed_field').value;
		}
	else
		{
		closedHTML="(none set)";
		}
	document.getElementById('closed_span').innerHTML=closedHTML;

	if (document.getElementById('weather_field').value!="") 
		{
		weatherHTML="<a href=\""+http_prefix+sounds_web_server+"/"+sounds_web_directory+"/"+document.getElementById('weather_field').value+".wav\" target='_blank'>"+document.getElementById('weather_field').value+"</a>";
		document.getElementById('weather_message').value=document.getElementById('weather_field').value;
		}
	else
		{
		weatherHTML="(none set)";
		}
	document.getElementById('weather_span').innerHTML=weatherHTML;

	if (document.getElementById('holiday_field').value!="") 
		{
		holidayHTML="<a href=\""+http_prefix+sounds_web_server+"/"+sounds_web_directory+"/"+document.getElementById('holiday_field').value+".wav\" target='_blank'>"+document.getElementById('holiday_field').value+"</a>";
		document.getElementById('holiday_message').value=document.getElementById('holiday_field').value;
		}
	else
		{
		holidayHTML="(none set)";
		}
	document.getElementById('holiday_span').innerHTML=holidayHTML;

	if (document.getElementById('custom_field').value!="") 
		{
		customHTML="<a href=\""+http_prefix+sounds_web_server+"/"+sounds_web_directory+"/"+document.getElementById('custom_field').value+".wav\" target='_blank'>"+document.getElementById('custom_field').value+"</a>";
		document.getElementById('custom_message').value=document.getElementById('custom_field').value;
		}
	else
		{
		customHTML="(none set)";
		}
	document.getElementById('custom_span').innerHTML=customHTML;
*/
}

var GoLiveTriggers=[<?php echo $starttime_str; ?>];
var CompletedTriggers=[<?php echo $endtime_str; ?>];
var OpenIntervals=[<?php echo $array_str; ?>];

function IntervalCheck()
	{
	var reload_flag=0;

	// Get local time for server
	var offset=<?php echo date("Z")/3600; ?>;
	var nd= new Date();
	var utc=nd.getTime()+(nd.getTimezoneOffset()*60000);
	var currentdate=new Date(utc+(3600000*offset));

	// alert(currentdate+"\n"+nd); return false;
	curMonth="0"+(currentdate.getMonth()+1);
	curDate="0"+currentdate.getDate();
	curHour="0"+currentdate.getHours();
	curMin="0"+currentdate.getMinutes();
	curSec="0"+currentdate.getSeconds();

	var checkTimeStamp=""+currentdate.getFullYear() + curMonth.substring(curMonth.length - 2) + curDate.substring(curDate.length - 2) + curHour.substring(curHour.length - 2) + curMin.substring(curMin.length - 2) + curSec.substring(curSec.length - 2); 
	checkTimeStamp = checkTimeStamp.replace(/[^0-9]/g,'');
	// if (curSec%10==0) {console.log(checkTimeStamp);}
	if (curSec%10==0) 
		{
		// console.log("[");
		for(var i = 0; i < OpenIntervals.length; i++) 
			{
			var console_str="";
			for(var j = 0; j < OpenIntervals[i].length; j++) 
				{
				console_str+=OpenIntervals[i][j]+", ";
				}
			// console.log(console_str+"\n");
			}		
		// console.log("]");
		}

	for(var i = 0; i < OpenIntervals.length; i++) 
		{
		if ((OpenIntervals[i][1]<=checkTimeStamp && OpenIntervals[i][3]=="ACTIVE") || (OpenIntervals[i][2]<=checkTimeStamp && OpenIntervals[i][3]=="LIVE"))
			{
			clearInterval(rInt);
			clearInterval(tInt);
			reload_flag++;
			}
		}		

/*
	for (i=0; i<GoLiveTriggers.length; i++)
		{
		// if (curSec%10==0) {console.log("ACTIVE - "+GoLiveTriggers[i]); console.log("ACTIVE triggers remaning: "+GoLiveTriggers.length);}
		if (GoLiveTriggers[i]<=checkTimeStamp)
			{
			delete GoLiveTriggers[i];
			// alert("Interval going live...");
			clearInterval(rInt);
			clearInterval(tInt);
			// window.location.href="<?php echo $PHP_SELF; ?>";
			reload_flag++;
			}
		}
	for (j=0; j<CompletedTriggers.length; j++)
		{
		// if (curSec%10==0) {console.log("LIVE - "+CompletedTriggers[j]); console.log("LIVE triggers remaning: "+CompletedTriggers.length);}
		if (CompletedTriggers[j]<=checkTimeStamp)
			{
			delete CompletedTriggers[j];
			// alert("Interval ending...");
			clearInterval(rInt);
			clearInterval(tInt);
			// window.location.href="<?php echo $PHP_SELF; ?>";
			reload_flag++;
			}
		}
*/

	if (reload_flag>0) {window.location.href="<?php echo $PHP_SELF; ?>";}
	}	

function PollyGenerator()
	{
	custom_msg = document.getElementById("custom_msg_field").value;
	custom_msg = custom_msg.replace(/[^a-zA-Z0-9,\s\.\:\-]+/g,'');
	var counter=Date.now();
	var audio_file=counter+"_TTS.wav";
	var confirmAudioSuccess = new RegExp("^SUCCESS","g");

	if (custom_msg.length >= 10)
		{
		// ##### BEGIN audio generation #####
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
			post_URL = '../agc/vdc_AWS_polly_TTS.php';

			polly_var_str = "user=6666&pass=1234&counter="+counter+"&voice=Joanna&message=<speak>"+custom_msg+"</speak>";
			xmlhttp.open('POST', post_URL); 
			xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
			xmlhttp.send(polly_var_str); 
			xmlhttp.onreadystatechange = function() 
				{ 
				if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
					{
					var audio_results = null;
					audio_results = xmlhttp.responseText;
					// console.log(xmlhttp.responseText);
					if (audio_results.match(confirmAudioSuccess))
						{
						document.getElementById("submit_custom_msg_span").innerHTML="<BR><BR><input type='button' class='green_btn' VALUE='SUCCESS - CLICK TO MOVE TO AUDIO STORE' onClick=\"MoveAudioFile('"+audio_file+"')\">";
						document.getElementById("custom_msg_field").value='';
						MoveAudioFile(audio_file);
						ToggleSpan('custom_msg_span');
						}
					else
						{
						alert("AUDIO GENERATION FAILED");
						}
					}
				}
			delete xmlhttp;
			}
		}
	else
		{
		alert("Please enter a custom message of at least 10 characters in length");
		}
	}

function MoveAudioFile(audioFilename)
	{
	var confirmAudioSuccess = new RegExp("^SUCCESS","g");

	// ##### BEGIN audio generation #####
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
		var_str = "audio_file="+audioFilename+"&action=move_audio_file";
		// alert(var_str); 
		xmlhttp.open('POST', post_URL); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(var_str); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				var audio_results = null;
				audio_results = xmlhttp.responseText;
				audio_results=audio_results.replace(/^\n+/, '');
				console.log(audio_results);
				if (audio_results.match(confirmAudioSuccess))
					{
					audioFilename_nosuffix=audioFilename.replace(/\.(wav|gsm)$/, '');
					document.getElementById("submit_custom_msg_span").innerHTML="<BR><BR><font color='green' class='standard_bold'>*** FILE MOVED SUCCESSFULLY ***</font>";
					<?php 
					# if ($user_level<9)
					#	{
						echo "	document.getElementById('message_type').value='CUSTOM';\n";
						echo "	document.getElementById(\"custom_message\").checked=true;\n";
						echo "	document.getElementById(\"custom_field\").value=audioFilename_nosuffix;\n";
					#	echo "	StartRefresh();\n";
					#	echo "					document.getElementById(\"custom_span\").innerHTML=\"<a href='$http_prefix$sounds_web_server/$sounds_web_directory/\"+audioFilename+\"' target='_blank'>\"+audioFilename_nosuffix+\"</a>\";\n";
					#	}
					?>
					}
				}
			}
		delete xmlhttp;
		}
	}

StartRefresh();
</script>
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
