<?php
# 
# functions.php    version 2.14
#
# functions for administrative scripts and reports
#
# Copyright (C) 2021  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
#
# CHANGES:
# 90524-1503 - First Build
# 110708-1723 - Added HF precision option
# 111222-2124 - Added max stats bar chart function
# 120125-1235 - Small changes to max stats function to allow for total system stats
# 120213-1417 - Changes to allow for ra stats
# 120713-2137 - Added download function for max stats
# 130615-2111 - Added user authentication function and login lockout for 15 minutes after 10 failed login
# 130705-1957 - Added password encryption compatibility
# 130831-0919 - Changed to mysqli PHP functions
# 140319-1924 - Added MathZDC function
# 140918-1609 - Added admin QXZ print/echo function with length padding
# 141118-0109 - Added options for up to 9 ordered variables within QXZ function output
# 141229-1535 - Added code to QXZ allowing for on-the-fly mysql phrase lookups
# 150210-1358 - Added precision S default to 0 in sec_convert
# 150216-1528 - Fixed non-latin problem, issue #828
# 150514-1522 - Added lookup_gmt function, copied from agc/functions.php
# 150516-1206 - Added missing TZCODE segment to gmt_lookup function
# 160802-1149 - Added hex2rgb function
# 161102-0039 - Patched sec_convert function to display hours 
# 170227-2230 - Change to allow horizontal_bar_chart header to be translated, issue #991
# 170409-1003 - Added IP List features to user_authorization
# 170503-2130 - Patched sec_convert to fix rounding time bug, issue #1011
# 170526-2142 - Added additional auth variable filtering, issue #1016
# 170930-0852 - Added code for custom reports variable display
# 180508-2215 - Added new help display
# 191013-0901 - Fixes for PHP7
# 200401-1920 - Added TimeToText function to convert integers to strings
# 210311-2316 - Added 2FA check in user_authorization function
# 210316-0924 - Small fix for 2FA consistency
# 210406-1740 - Moved 'dialable_leads' function to this script
# 210615-0952 - Default security fix, CVE-2021-28854
#

##### BEGIN validate user login credentials, check for failed lock out #####
function user_authorization($user,$pass,$user_option,$user_update,$api_call)
	{
	global $link,$php_script;
#	require("dbconnect_mysqli.php");

	#############################################
	##### START SYSTEM_SETTINGS LOOKUP #####
	$stmt = "SELECT use_non_latin,webroot_writable,pass_hash_enabled,pass_key,pass_cost,allow_ip_lists,system_ip_blacklist,two_factor_auth_hours,two_factor_container FROM system_settings;";
	$rslt=mysql_to_mysqli($stmt, $link);
	if ($DB) {echo "$stmt\n";}
	$qm_conf_ct = mysqli_num_rows($rslt);
	if ($qm_conf_ct > 0)
		{
		$row=mysqli_fetch_row($rslt);
		$non_latin =					$row[0];
		$SSwebroot_writable =			$row[1];
		$SSpass_hash_enabled =			$row[2];
		$SSpass_key =					$row[3];
		$SSpass_cost =					$row[4];
		$SSallow_ip_lists =				$row[5];
		$SSsystem_ip_blacklist =		$row[6];
		$SStwo_factor_auth_hours =		$row[7];
		$SStwo_factor_container =		$row[8];
		}
	##### END SETTINGS LOOKUP #####
	###########################################

	$STARTtime = date("U");
	$TODAY = date("Y-m-d");
	$NOW_TIME = date("Y-m-d H:i:s");
	$ip = getenv("REMOTE_ADDR");
	$browser = getenv("HTTP_USER_AGENT");
	$LOCK_over = ($STARTtime - 900); # failed login lockout time is 15 minutes(900 seconds)
	$LOCK_trigger_attempts = 10;

	$user = preg_replace("/\||`|&|\'|\"|\\\\|;| /","",$user);
	$pass = preg_replace("/\||`|&|\'|\"|\\\\|;| /","",$pass);

	$passSQL = "pass='$pass'";

	if ($SSpass_hash_enabled > 0)
		{
		if (file_exists("../agc/bp.pl"))
			{$pass_hash = exec("../agc/bp.pl --pass='$pass'");}
		else
			{$pass_hash = exec("../../agc/bp.pl --pass='$pass'");}
		$pass_hash = preg_replace("/PHASH: |\n|\r|\t| /",'',$pass_hash);
		$passSQL = "pass_hash='$pass_hash'";
		}

	$stmt="SELECT count(*) from vicidial_users where user='$user' and $passSQL and user_level > 7 and active='Y' and ( (failed_login_count < $LOCK_trigger_attempts) or (UNIX_TIMESTAMP(last_login_date) < $LOCK_over) );";
	if ($user_option == 'REPORTS')
		{$stmt="SELECT count(*) from vicidial_users where user='$user' and $passSQL and user_level > 6 and active='Y' and ( (failed_login_count < $LOCK_trigger_attempts) or (UNIX_TIMESTAMP(last_login_date) < $LOCK_over) );";}
	if ($user_option == 'REMOTE')
		{$stmt="SELECT count(*) from vicidial_users where user='$user' and $passSQL and user_level > 3 and active='Y' and ( (failed_login_count < $LOCK_trigger_attempts) or (UNIX_TIMESTAMP(last_login_date) < $LOCK_over) );";}
	if ($user_option == 'QC')
		{$stmt="SELECT count(*) from vicidial_users where user='$user' and $passSQL and user_level > 1 and active='Y' and ( (failed_login_count < $LOCK_trigger_attempts) or (UNIX_TIMESTAMP(last_login_date) < $LOCK_over) );";}
	if ($DB) {echo "|$stmt|\n";}
	if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$auth=$row[0];

	if ($auth < 1)
		{
		$auth_key='BAD';
		$stmt="SELECT failed_login_count,UNIX_TIMESTAMP(last_login_date) from vicidial_users where user='$user';";
		if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
		$rslt=mysql_to_mysqli($stmt, $link);
		$cl_user_ct = mysqli_num_rows($rslt);
		if ($cl_user_ct > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$failed_login_count =	$row[0];
			$last_login_date =		$row[1];

			if ($failed_login_count < $LOCK_trigger_attempts)
				{
				$stmt="UPDATE vicidial_users set failed_login_count=(failed_login_count+1),last_ip='$ip' where user='$user';";
				$rslt=mysql_to_mysqli($stmt, $link);
				}
			else
				{
				if ($LOCK_over > $last_login_date)
					{
					$stmt="UPDATE vicidial_users set last_login_date=NOW(),failed_login_count=1,last_ip='$ip' where user='$user';";
					$rslt=mysql_to_mysqli($stmt, $link);
					}
				else
					{$auth_key='LOCK';}
				}
			}
		if ($SSwebroot_writable > 0)
			{
			$fp = fopen ("./project_auth_entries.txt", "w");
			fwrite ($fp, "ADMIN|FAIL|$NOW_TIME|$browser|\n");
			fclose($fp);
			}
		}
	else
		{
		##### BEGIN IP LIST FEATURES #####
		if ($SSallow_ip_lists > 0)
			{
			$ignore_ip_list=0;
			$whitelist_match=1;
			$blacklist_match=0;
			$stmt="SELECT user_group,ignore_ip_list from vicidial_users where user='$user';";
			if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
			$rslt=mysql_to_mysqli($stmt, $link);
			$iipl_user_ct = mysqli_num_rows($rslt);
			if ($iipl_user_ct > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$user_group =		$row[0];
				$ignore_ip_list =	$row[1];
				}
			if ($ignore_ip_list < 1)
				{
				$ip_class_c = preg_replace('~\.\d+(?!.*\.\d+)~', '.x', $ip);

				if (strlen($SSsystem_ip_blacklist) > 1)
					{
					$blacklist_match=1;
					$stmt="SELECT count(*) from vicidial_ip_list_entries where ip_list_id='$SSsystem_ip_blacklist' and ip_address IN('$ip','$ip_class_c');";
					$rslt=mysql_to_mysqli($stmt, $link);
					$vile_ct = mysqli_num_rows($rslt);
					if ($vile_ct > 0)
						{
						$row=mysqli_fetch_row($rslt);
						$blacklist_match =	$row[0];
						}
					}

				$stmt="SELECT admin_ip_list,api_ip_list from vicidial_user_groups where user_group='$user_group';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$iipl_user_ct = mysqli_num_rows($rslt);
				if ($iipl_user_ct > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$admin_ip_list =	$row[0];
					$api_ip_list =		$row[1];

					if ($api_call != '1')
						{$check_ip_list = $admin_ip_list;}
					else
						{$check_ip_list = $api_ip_list;}

					if (strlen($check_ip_list) > 1)
						{
						$whitelist_match=0;
						$stmt="SELECT count(*) from vicidial_ip_list_entries where ip_list_id='$check_ip_list' and ip_address IN('$ip','$ip_class_c');";
						$rslt=mysql_to_mysqli($stmt, $link);
						$vile_ct = mysqli_num_rows($rslt);
						if ($vile_ct > 0)
							{
							$row=mysqli_fetch_row($rslt);
							$whitelist_match =	$row[0];
							}
						}
					}
				}

			if ( ($whitelist_match < 1) or ($blacklist_match > 0) )
				{
				$auth_key='IPBLOCK';
				$login_problem++;
				}
			}
		##### END IP LIST FEATURES #####

		if ($login_problem < 1)
			{
			if ($user_update > 0)
				{
				$stmt="UPDATE vicidial_users set last_login_date=NOW(),last_ip='$ip',failed_login_count=0 where user='$user';";
				$rslt=mysql_to_mysqli($stmt, $link);
				}
			$auth_key='GOOD';

			if ( ($SStwo_factor_auth_hours > 0) and ($SStwo_factor_container != '') and ($SStwo_factor_container != '---DISABLED---') and ($php_script != 'admin.php') )
				{
				$VUtwo_factor_override='';
				$stmt="SELECT two_factor_override from vicidial_users where user='$user';";
				if ($non_latin > 0) {$rslt=mysql_to_mysqli("SET NAMES 'UTF8'", $link);}
				$rslt=mysql_to_mysqli($stmt, $link);
				$two_factor_user_ct = mysqli_num_rows($rslt);
				if ($two_factor_user_ct > 0)
					{
					$row=mysqli_fetch_row($rslt);
					$VUtwo_factor_override = $row[0];
					}
				if ($VUtwo_factor_override != 'DISABLED')
					{
					$VALID_2FA=1;
					# check for 2FA being active, and if so, see if there is a non-expired 2FA auth
					$stmt="SELECT count(*) from vicidial_two_factor_auth where user='$user' and auth_stage='1' and auth_exp_date > NOW();";
					$rslt=mysql_to_mysqli($stmt, $link);
					if ($DB) {echo "$stmt\n";}
					$auth_check_to_print = mysqli_num_rows($rslt);
					if ($auth_check_to_print < 1)
						{$VALID_2FA=0;}
					else
						{
						$row=mysqli_fetch_row($rslt);
						$VALID_2FA = $row[0];
						}
					if ($VALID_2FA < 1)
						{
						$auth_key='2FA';
						}
					}
				}
			}
		}
	return $auth_key;
	}
##### END validate user login credentials, check for failed lock out #####


##### BEGIN reformat seconds into HH:MM:SS or MM:SS #####
function sec_convert($sec,$precision)
	{
	$sec = round($sec,0);

	if ($sec < 1)
		{
		if ($precision == 'HF' || $precision == 'H')
			{return "0:00:00";}
		elseif ($precision == 'S')
			{return "0";}
		else
			{return "0:00";}
		}

	if ($precision == 'HF')
		{$precision='H';}
	else
		{
		# if ( ($sec < 3600) and ($precision != 'S') ) {$precision='M';}
		}

	if ($precision == 'H')
		{
		$hours = floor($sec / 3600);
		$hours_sec = $hours * 3600;
		$seconds = $sec - $hours_sec;
		$minutes = floor($seconds / 60);
		$seconds -= ($minutes * 60);
		$Ftime = sprintf('%s:%s:%s',
			$hours,
			str_pad($minutes,2,'0',STR_PAD_LEFT),
			str_pad($seconds,2,'0',STR_PAD_LEFT)
		);
		}
	elseif ($precision == 'M')
		{
		$minutes = floor($sec / 60);
		$seconds = $sec - ($minutes * 60);
		$Ftime = sprintf('%s:%s',
			$minutes,
			str_pad($seconds,2,'0',STR_PAD_LEFT)
		);
		}
	else 
		{
		$Ftime = $sec;
		}
	return "$Ftime";
	}
##### END reformat seconds into HH:MM:SS or MM:SS #####


##### BEGIN counts like elements in an array, optional sort asc desc #####
function array_group_count($array, $sort = false) 
	{
	$tally_array = array();

	$i=0;
	foreach (array_unique($array) as $value) 
		{
		$count = 0;
		foreach ($array as $element) 
			{
		    if ($element == "$value")
		        {$count++;}
			}

		$count =		sprintf("%010s", $count);
		$tally_array[$i] = "$count $value";
		$i++;
		}
	
	if ( $sort == 'desc' )
		{rsort($tally_array);}
	elseif ( $sort == 'asc' )
		{sort($tally_array);}

	return $tally_array;
	}
##### END counts like elements in an array, optional sort asc desc #####


##### BEGIN bar chart using max stats data #####
function horizontal_bar_chart($campaign_id,$days_graph,$title,$link,$metric,$metric_name,$more_link,$END_DATE,$download_link,$chart_title)
	{
	$stats_start_time = time();
	if ($END_DATE) 
		{
		$Bstats_date[0]=$END_DATE;
		}
	else
		{
		$Bstats_date[0]=date("Y-m-d");
		}
	$Btotal_calls[0]=0;
	$link_text='';
	$max_count=0;
	$i=0;
	# $NWB = "$download_link &nbsp; <a href=\"javascript:openNewWindow('help.php?ADD=99999";
	# $NWE = "')\"><IMG SRC=\"help.png\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></A>";

	$NWB = "<IMG SRC=\"help.png\" onClick=\"FillAndShowHelpDiv(event, '";
	$NWE = "')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP>";


	### get stats for last X days
	$stmt="SELECT stats_date,$metric from vicidial_daily_max_stats where campaign_id='$campaign_id' and stats_flag='OPEN' and stats_date<='$Bstats_date[0]';";
	if ($metric=='total_calls_inbound_all')
		{$stmt="SELECT stats_date,sum(total_calls) from vicidial_daily_max_stats where stats_type='INGROUP' and stats_flag='OPEN' and stats_date<='$Bstats_date[0]' group by stats_date;";}
	if ($metric=='total_calls_outbound_all')
		{$stmt="SELECT stats_date,sum(total_calls) from vicidial_daily_max_stats where stats_type='CAMPAIGN' and stats_flag='OPEN' and stats_date<='$Bstats_date[0]' group by stats_date;";}
	if ($metric=='ra_total_calls')
		{$stmt="SELECT stats_date,total_calls from vicidial_daily_ra_stats where stats_flag='OPEN' and stats_date<='$Bstats_date[0]' and user='$campaign_id';";}
	if ($metric=='ra_concurrent_calls')
		{$stmt="SELECT stats_date,max_calls from vicidial_daily_ra_stats where stats_flag='OPEN' and stats_date<='$Bstats_date[0]' and user='$campaign_id';";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$Xstats_to_print = mysqli_num_rows($rslt);
	if ($Xstats_to_print > 0) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$Bstats_date[0] =  $rowx[0];
		$Btotal_calls[0] = $rowx[1];
		if ($max_count < $Btotal_calls[0]) {$max_count = $Btotal_calls[0];}
		}
	$stats_date_ARRAY = explode("-",$Bstats_date[0]);
	$stats_start_time = mktime(10, 10, 10, $stats_date_ARRAY[1], $stats_date_ARRAY[2], $stats_date_ARRAY[0]);
	while($i <= $days_graph)
		{
		$Bstats_date[$i] =  date("Y-m-d", $stats_start_time);
		$Btotal_calls[$i]=0;
		$stmt="SELECT stats_date,$metric from vicidial_daily_max_stats where campaign_id='$campaign_id' and stats_date='$Bstats_date[$i]';";
		if ($metric=='total_calls_inbound_all')
			{$stmt="SELECT stats_date,sum(total_calls) from vicidial_daily_max_stats where stats_date='$Bstats_date[$i]' and stats_type='INGROUP' group by stats_date;";}
		if ($metric=='total_calls_outbound_all')
			{$stmt="SELECT stats_date,sum(total_calls) from vicidial_daily_max_stats where stats_date='$Bstats_date[$i]' and stats_type='CAMPAIGN' group by stats_date;";}
		if ($metric=='ra_total_calls')
			{$stmt="SELECT stats_date,total_calls from vicidial_daily_ra_stats where stats_date='$Bstats_date[$i]' and user='$campaign_id';";}
		if ($metric=='ra_concurrent_calls')
			{$stmt="SELECT stats_date,max_calls from vicidial_daily_ra_stats where stats_date='$Bstats_date[$i]' and user='$campaign_id';";}
		echo "<!-- $i) $stmt \\-->\n";
		$rslt=mysql_to_mysqli($stmt, $link);
		$Ystats_to_print = mysqli_num_rows($rslt);
		if ($Ystats_to_print > 0) 
			{
			$rowx=mysqli_fetch_row($rslt);
			$Btotal_calls[$i] =		$rowx[1];
			if ($max_count < $Btotal_calls[$i]) {$max_count = $Btotal_calls[$i];}
			}
		$i++;
		$stats_start_time = ($stats_start_time - 86400);
		}
	if ($max_count < 1) 
		{echo "<!-- no max stats cache summary information available -->";}
	else
		{
		if ($title=='campaign') {$out_in_type=' outbound';}
		if ($title=='in-group') {$out_in_type=' inbound';}
		if (strlen($chart_title)<4) {$chart_title = "$days_graph Day $out_in_type $metric_name for this $title";}
		if ($more_link > 0) {$link_text = "<a href=\"$PHP_SELF?ADD=999993&campaign_id=$campaign_id&stage=$title\"><font size=1>more summary stats...</font></a>";}
		echo "<table cellspacing=\"1\" cellpadding=\"0\" bgcolor=\"white\" summary=\"Multiple day $metric_name.\" style=\"background-image:url(images/bg_fade.png); background-repeat:repeat-x; background-position:left top; width: 33em;\">\n";
		echo "<caption align=\"top\">$chart_title &nbsp; $link_text  &nbsp; $NWB#max_stats$NWE<br /></caption>\n";
		echo "<tr>\n";
		echo "<th scope=\"col\" style=\"text-align: left; vertical-align:top;\"><span class=\"auraltext\">date</span> </th>\n";
		echo "<th scope=\"col\" style=\"text-align: left; vertical-align:top;\"><span class=\"auraltext\">$metric_name</span> </th>\n";
		echo "</tr>\n";

		$max_multi = MathZDC(400, $max_count);
		$i=0;
		while($i < $days_graph)
			{
			$bar_width = intval($max_multi * $Btotal_calls[$i]);
			if ($Btotal_calls[$i] < 1) {$Btotal_calls[$i] = "-"._QXZ("none")."-";}
			echo "<tr>\n";
			echo "<td class=\"chart_td\"><font style=\"font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 60%;\">$Bstats_date[$i] </font></td>\n";
			echo "<td class=\"chart_td\"><img src=\"images/bar.png\" alt=\"\" width=\"$bar_width\" height=\"10\" style=\"vertical-align: middle; margin: 2px 2px 2px 0;\"/><font style=\"font-family: Verdana, Arial, Helvetica, sans-serif; font-size: 60%;\"> $Btotal_calls[$i]</font></td>\n";
			echo "</tr>\n";
			$i++;
			}
		echo "</table>\n";
		}
	}
##### END bar chart using max stats data #####


##### BEGIN download max stats data #####
function download_max_system_stats($campaign_id,$days_graph,$title,$metric,$metric_name,$END_DATE)
	{
	global $CSV_text, $link;
	$stats_start_time = time();
	if ($END_DATE) 
		{
		$Bstats_date[0]=$END_DATE;
		}
	else
		{
		$Bstats_date[0]=date("Y-m-d");
		}
	$Btotal_calls[0]=0;
	$link_text='';
	$i=0;

	### get stats for last X days
	$stmt="SELECT stats_date,$metric from vicidial_daily_max_stats where campaign_id='$campaign_id' and stats_flag='OPEN' and stats_date<='$Bstats_date[0]';";
	if ($metric=='total_calls_inbound_all')
		{$stmt="SELECT stats_date,sum(total_calls) from vicidial_daily_max_stats where stats_type='INGROUP' and stats_flag='OPEN' and stats_date<='$Bstats_date[0]' group by stats_date;";}
	if ($metric=='total_calls_outbound_all')
		{$stmt="SELECT stats_date,sum(total_calls) from vicidial_daily_max_stats where stats_type='CAMPAIGN' and stats_flag='OPEN' and stats_date<='$Bstats_date[0]' group by stats_date;";}
	if ($metric=='ra_total_calls')
		{$stmt="SELECT stats_date,total_calls from vicidial_daily_ra_stats where stats_flag='OPEN' and stats_date<='$Bstats_date[0]' and user='$campaign_id';";}
	if ($metric=='ra_concurrent_calls')
		{$stmt="SELECT stats_date,max_calls from vicidial_daily_ra_stats where stats_flag='OPEN' and stats_date<='$Bstats_date[0]' and user='$campaign_id';";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$Xstats_to_print = mysqli_num_rows($rslt);
	if ($Xstats_to_print > 0) 
		{
		$rowx=mysqli_fetch_row($rslt);
		$Bstats_date[0] =  $rowx[0];
		$Btotal_calls[0] = $rowx[1];
		if ($max_count < $Btotal_calls[0]) {$max_count = $Btotal_calls[0];}
		}
	$stats_date_ARRAY = explode("-",$Bstats_date[0]);
	$stats_start_time = mktime(10, 10, 10, $stats_date_ARRAY[1], $stats_date_ARRAY[2], $stats_date_ARRAY[0]);
	while($i <= $days_graph)
		{
		$Bstats_date[$i] =  date("Y-m-d", $stats_start_time);
		$Btotal_calls[$i]=0;
		$stmt="SELECT stats_date,$metric from vicidial_daily_max_stats where campaign_id='$campaign_id' and stats_date='$Bstats_date[$i]';";
		if ($metric=='total_calls_inbound_all')
			{$stmt="SELECT stats_date,sum(total_calls) from vicidial_daily_max_stats where stats_date='$Bstats_date[$i]' and stats_type='INGROUP' group by stats_date;";}
		if ($metric=='total_calls_outbound_all')
			{$stmt="SELECT stats_date,sum(total_calls) from vicidial_daily_max_stats where stats_date='$Bstats_date[$i]' and stats_type='CAMPAIGN' group by stats_date;";}
		if ($metric=='ra_total_calls')
			{$stmt="SELECT stats_date,total_calls from vicidial_daily_ra_stats where stats_date='$Bstats_date[$i]' and user='$campaign_id';";}
		if ($metric=='ra_concurrent_calls')
			{$stmt="SELECT stats_date,max_calls from vicidial_daily_ra_stats where stats_date='$Bstats_date[$i]' and user='$campaign_id';";}
		$rslt=mysql_to_mysqli($stmt, $link);
		$Ystats_to_print = mysqli_num_rows($rslt);
		if ($Ystats_to_print > 0) 
			{
			$rowx=mysqli_fetch_row($rslt);
			$Btotal_calls[$i] =		$rowx[1];
			if ($max_count < $Btotal_calls[$i]) {$max_count = $Btotal_calls[$i];}
			}
		$i++;
		$stats_start_time = ($stats_start_time - 86400);
		}

	if ($title=='campaign') {$out_in_type=' outbound';}
	if ($title=='in-group') {$out_in_type=' inbound';}
	$CSV_text.="\"$days_graph Day $out_in_type $metric_name for this $title\"\n";

	if ($max_count < 1) 
		{$CSV_text.="\"no max stats cache summary information available\"\n";}
	else
		{
		$CSV_text.="\"DATE\",\"$metric_name\"\n";

		$i=0;
		while($i < $days_graph)
			{
			$bar_width = intval($max_multi * $Btotal_calls[$i]);
			if ($Btotal_calls[$i] < 1) {$Btotal_calls[$i] = "-none-";}
			$CSV_text.="\"$Bstats_date[$i]\",\"$Btotal_calls[$i]\"\n";
			$i++;
			}

		$CSV_text.="\n\n";
		}
	}
##### END download max stats data #####


##### LOOKUP GMT, FINDS THE CURRENT GMT OFFSET FOR A PHONE NUMBER #####

function lookup_gmt($phone_code,$USarea,$state,$LOCAL_GMT_OFF_STD,$Shour,$Smin,$Ssec,$Smon,$Smday,$Syear,$postalgmt,$postal_code,$owner,$USprefix)
	{
	global $link;

	$postalgmt_found=0;
	if ( (preg_match("/POSTAL/i",$postalgmt)) && (strlen($postal_code)>4) )
		{
		if (preg_match('/^1$/', $phone_code))
			{
			$stmt="select postal_code,state,GMT_offset,DST,DST_range,country,country_code from vicidial_postal_codes where country_code='$phone_code' and postal_code LIKE \"$postal_code%\";";
			$rslt=mysql_to_mysqli($stmt, $link);
			$pc_recs = mysqli_num_rows($rslt);
			if ($pc_recs > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$gmt_offset =	$row[2];	 $gmt_offset = preg_replace("/\+/i","",$gmt_offset);
				$dst =			$row[3];
				$dst_range =	$row[4];
				$PC_processed++;
				$postalgmt_found++;
				$post++;
				}
			}
		}
	if ( ($postalgmt=="TZCODE") && (strlen($owner)>1) )
		{
		$dst_range='';
		$dst='N';
		$gmt_offset=0;

		$stmt="select GMT_offset from vicidial_phone_codes where tz_code='$owner' and country_code='$phone_code' limit 1;";
		$rslt=mysql_to_mysqli($stmt, $link);
		$pc_recs = mysqli_num_rows($rslt);
		if ($pc_recs > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$gmt_offset =	$row[0];	 $gmt_offset = preg_replace("/\+/i","",$gmt_offset);
			$PC_processed++;
			$postalgmt_found++;
			$post++;
			}

		$stmt = "select distinct DST_range from vicidial_phone_codes where tz_code='$owner' and country_code='$phone_code' order by DST_range desc limit 1;";
		$rslt=mysql_to_mysqli($stmt, $link);
		$pc_recs = mysqli_num_rows($rslt);
		if ($pc_recs > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$dst_range =	$row[0];
			if (strlen($dst_range)>2) {$dst = 'Y';}
			}
		}
	if ( (preg_match("/NANPA/i",$tz_method)) && (strlen($USarea)>2) && (strlen($USprefix)>2) )
		{
		$stmt="select GMT_offset,DST from vicidial_nanpa_prefix_codes where areacode='$USarea' and prefix='$USprefix';";
		$rslt=mysql_to_mysqli($stmt, $link);
		$pc_recs = mysqli_num_rows($rslt);
		if ($pc_recs > 0)
			{
			$row=mysqli_fetch_row($rslt);
			$gmt_offset =	$row[0];	 $gmt_offset = preg_replace("/\+/i","",$gmt_offset);
			$dst =			$row[1];
			$dst_range =	'';
			if ($dst == 'Y')
				{$dst_range =	'SSM-FSN';}
			$PC_processed++;
			$postalgmt_found++;
			$post++;
			}
		}
	if ($postalgmt_found < 1)
		{
		$PC_processed=0;
		### UNITED STATES ###
		if ($phone_code =='1')
			{
			$stmt="select country_code,country,areacode,state,GMT_offset,DST,DST_range,geographic_description from vicidial_phone_codes where country_code='$phone_code' and areacode='$USarea';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$pc_recs = mysqli_num_rows($rslt);
			if ($pc_recs > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$gmt_offset =	$row[4];	 $gmt_offset = preg_replace("/\+/i","",$gmt_offset);
				$dst =			$row[5];
				$dst_range =	$row[6];
				$PC_processed++;
				}
			}
		### MEXICO ###
		if ($phone_code =='52')
			{
			$stmt="select country_code,country,areacode,state,GMT_offset,DST,DST_range,geographic_description from vicidial_phone_codes where country_code='$phone_code' and areacode='$USarea';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$pc_recs = mysqli_num_rows($rslt);
			if ($pc_recs > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$gmt_offset =	$row[4];	 $gmt_offset = preg_replace("/\+/i","",$gmt_offset);
				$dst =			$row[5];
				$dst_range =	$row[6];
				$PC_processed++;
				}
			}
		### AUSTRALIA ###
		if ($phone_code =='61')
			{
			$stmt="select country_code,country,areacode,state,GMT_offset,DST,DST_range,geographic_description from vicidial_phone_codes where country_code='$phone_code' and state='$state';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$pc_recs = mysqli_num_rows($rslt);
			if ($pc_recs > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$gmt_offset =	$row[4];	 $gmt_offset = preg_replace("/\+/i","",$gmt_offset);
				$dst =			$row[5];
				$dst_range =	$row[6];
				$PC_processed++;
				}
			}
		### ALL OTHER COUNTRY CODES ###
		if (!$PC_processed)
			{
			$PC_processed++;
			$stmt="select country_code,country,areacode,state,GMT_offset,DST,DST_range,geographic_description from vicidial_phone_codes where country_code='$phone_code';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$pc_recs = mysqli_num_rows($rslt);
			if ($pc_recs > 0)
				{
				$row=mysqli_fetch_row($rslt);
				$gmt_offset =	$row[4];	 $gmt_offset = preg_replace("/\+/i","",$gmt_offset);
				$dst =			$row[5];
				$dst_range =	$row[6];
				$PC_processed++;
				}
			}
		}

	### Find out if DST to raise the gmt offset ###
	$AC_GMT_diff = ($gmt_offset - $LOCAL_GMT_OFF_STD);
	$AC_localtime = mktime(($Shour + $AC_GMT_diff), $Smin, $Ssec, $Smon, $Smday, $Syear);
		$hour = date("H",$AC_localtime);
		$min = date("i",$AC_localtime);
		$sec = date("s",$AC_localtime);
		$mon = date("m",$AC_localtime);
		$mday = date("d",$AC_localtime);
		$wday = date("w",$AC_localtime);
		$year = date("Y",$AC_localtime);
	$dsec = ( ( ($hour * 3600) + ($min * 60) ) + $sec );

	$AC_processed=0;
	if ( (!$AC_processed) and ($dst_range == 'SSM-FSN') )
		{
		if ($DBX) {print "     "._QXZ("Second Sunday March to First Sunday November")."\n";}
		#**********************************************************************
		# SSM-FSN
		#     This is returns 1 if Daylight Savings Time is in effect and 0 if 
		#       Standard time is in effect.
		#     Based on Second Sunday March to First Sunday November at 2 am.
		#     INPUTS:
		#       mm              INTEGER       Month.
		#       dd              INTEGER       Day of the month.
		#       ns              INTEGER       Seconds into the day.
		#       dow             INTEGER       Day of week (0=Sunday, to 6=Saturday)
		#     OPTIONAL INPUT:
		#       timezone        INTEGER       hour difference UTC - local standard time
		#                                      (DEFAULT is blank)
		#                                     make calculations based on UTC time, 
		#                                     which means shift at 10:00 UTC in April
		#                                     and 9:00 UTC in October
		#     OUTPUT: 
		#                       INTEGER       1 = DST, 0 = not DST
		#
		# S  M  T  W  T  F  S
		# 1  2  3  4  5  6  7
		# 8  9 10 11 12 13 14
		#15 16 17 18 19 20 21
		#22 23 24 25 26 27 28
		#29 30 31
		# 
		# S  M  T  W  T  F  S
		#    1  2  3  4  5  6
		# 7  8  9 10 11 12 13
		#14 15 16 17 18 19 20
		#21 22 23 24 25 26 27
		#28 29 30 31
		# 
		#**********************************************************************

			$USACAN_DST=0;
			$mm = $mon;
			$dd = $mday;
			$ns = $dsec;
			$dow= $wday;

			if ($mm < 3 || $mm > 11) {
			$USACAN_DST=0;   
			} elseif ($mm >= 4 and $mm <= 10) {
			$USACAN_DST=1;   
			} elseif ($mm == 3) {
			if ($dd > 13) {
				$USACAN_DST=1;   
			} elseif ($dd >= ($dow+8)) {
				if ($timezone) {
				if ($dow == 0 and $ns < (7200+$timezone*3600)) {
					$USACAN_DST=0;   
				} else {
					$USACAN_DST=1;   
				}
				} else {
				if ($dow == 0 and $ns < 7200) {
					$USACAN_DST=0;   
				} else {
					$USACAN_DST=1;   
				}
				}
			} else {
				$USACAN_DST=0;   
			}
			} elseif ($mm == 11) {
			if ($dd > 7) {
				$USACAN_DST=0;   
			} elseif ($dd < ($dow+1)) {
				$USACAN_DST=1;   
			} elseif ($dow == 0) {
				if ($timezone) { # UTC calculations
				if ($ns < (7200+($timezone-1)*3600)) {
					$USACAN_DST=1;   
				} else {
					$USACAN_DST=0;   
				}
				} else { # local time calculations
				if ($ns < 7200) {
					$USACAN_DST=1;   
				} else {
					$USACAN_DST=0;   
				}
				}
			} else {
				$USACAN_DST=0;   
			}
			} # end of month checks
		if ($DBX) {print "     DST: $USACAN_DST\n";}
		if ($USACAN_DST) {$gmt_offset++;}
		$AC_processed++;
		}

	if ( (!$AC_processed) and ($dst_range == 'FSA-LSO') )
		{
		if ($DBX) {print "     "._QXZ("First Sunday April to Last Sunday October")."\n";}
		#**********************************************************************
		# FSA-LSO
		#     This is returns 1 if Daylight Savings Time is in effect and 0 if 
		#       Standard time is in effect.
		#     Based on first Sunday in April and last Sunday in October at 2 am.
		#**********************************************************************
			
			$USA_DST=0;
			$mm = $mon;
			$dd = $mday;
			$ns = $dsec;
			$dow= $wday;

			if ($mm < 4 || $mm > 10) {
			$USA_DST=0;
			} elseif ($mm >= 5 and $mm <= 9) {
			$USA_DST=1;
			} elseif ($mm == 4) {
			if ($dd > 7) {
				$USA_DST=1;
			} elseif ($dd >= ($dow+1)) {
				if ($timezone) {
				if ($dow == 0 and $ns < (7200+$timezone*3600)) {
					$USA_DST=0;
				} else {
					$USA_DST=1;
				}
				} else {
				if ($dow == 0 and $ns < 7200) {
					$USA_DST=0;
				} else {
					$USA_DST=1;
				}
				}
			} else {
				$USA_DST=0;
			}
			} elseif ($mm == 10) {
			if ($dd < 25) {
				$USA_DST=1;
			} elseif ($dd < ($dow+25)) {
				$USA_DST=1;
			} elseif ($dow == 0) {
				if ($timezone) { # UTC calculations
				if ($ns < (7200+($timezone-1)*3600)) {
					$USA_DST=1;
				} else {
					$USA_DST=0;
				}
				} else { # local time calculations
				if ($ns < 7200) {
					$USA_DST=1;
				} else {
					$USA_DST=0;
				}
				}
			} else {
				$USA_DST=0;
			}
			} # end of month checks

		if ($DBX) {print "     DST: $USA_DST\n";}
		if ($USA_DST) {$gmt_offset++;}
		$AC_processed++;
		}

	if ( (!$AC_processed) and ($dst_range == 'LSM-LSO') )
		{
		if ($DBX) {print "     "._QXZ("Last Sunday March to Last Sunday October")."\n";}
		#**********************************************************************
		#     This is s 1 if Daylight Savings Time is in effect and 0 if 
		#       Standard time is in effect.
		#     Based on last Sunday in March and last Sunday in October at 1 am.
		#**********************************************************************
			
			$GBR_DST=0;
			$mm = $mon;
			$dd = $mday;
			$ns = $dsec;
			$dow= $wday;

			if ($mm < 3 || $mm > 10) {
			$GBR_DST=0;
			} elseif ($mm >= 4 and $mm <= 9) {
			$GBR_DST=1;
			} elseif ($mm == 3) {
			if ($dd < 25) {
				$GBR_DST=0;
			} elseif ($dd < ($dow+25)) {
				$GBR_DST=0;
			} elseif ($dow == 0) {
				if ($timezone) { # UTC calculations
				if ($ns < (3600+($timezone-1)*3600)) {
					$GBR_DST=0;
				} else {
					$GBR_DST=1;
				}
				} else { # local time calculations
				if ($ns < 3600) {
					$GBR_DST=0;
				} else {
					$GBR_DST=1;
				}
				}
			} else {
				$GBR_DST=1;
			}
			} elseif ($mm == 10) {
			if ($dd < 25) {
				$GBR_DST=1;
			} elseif ($dd < ($dow+25)) {
				$GBR_DST=1;
			} elseif ($dow == 0) {
				if ($timezone) { # UTC calculations
				if ($ns < (3600+($timezone-1)*3600)) {
					$GBR_DST=1;
				} else {
					$GBR_DST=0;
				}
				} else { # local time calculations
				if ($ns < 3600) {
					$GBR_DST=1;
				} else {
					$GBR_DST=0;
				}
				}
			} else {
				$GBR_DST=0;
			}
			} # end of month checks
			if ($DBX) {print "     DST: $GBR_DST\n";}
		if ($GBR_DST) {$gmt_offset++;}
		$AC_processed++;
		}
	if ( (!$AC_processed) and ($dst_range == 'LSO-LSM') )
		{
		if ($DBX) {print "     "._QXZ("Last Sunday October to Last Sunday March")."\n";}
		#**********************************************************************
		#     This is s 1 if Daylight Savings Time is in effect and 0 if 
		#       Standard time is in effect.
		#     Based on last Sunday in October and last Sunday in March at 1 am.
		#**********************************************************************
			
			$AUS_DST=0;
			$mm = $mon;
			$dd = $mday;
			$ns = $dsec;
			$dow= $wday;

			if ($mm < 3 || $mm > 10) {
			$AUS_DST=1;
			} elseif ($mm >= 4 and $mm <= 9) {
			$AUS_DST=0;
			} elseif ($mm == 3) {
			if ($dd < 25) {
				$AUS_DST=1;
			} elseif ($dd < ($dow+25)) {
				$AUS_DST=1;
			} elseif ($dow == 0) {
				if ($timezone) { # UTC calculations
				if ($ns < (3600+($timezone-1)*3600)) {
					$AUS_DST=1;
				} else {
					$AUS_DST=0;
				}
				} else { # local time calculations
				if ($ns < 3600) {
					$AUS_DST=1;
				} else {
					$AUS_DST=0;
				}
				}
			} else {
				$AUS_DST=0;
			}
			} elseif ($mm == 10) {
			if ($dd < 25) {
				$AUS_DST=0;
			} elseif ($dd < ($dow+25)) {
				$AUS_DST=0;
			} elseif ($dow == 0) {
				if ($timezone) { # UTC calculations
				if ($ns < (3600+($timezone-1)*3600)) {
					$AUS_DST=0;
				} else {
					$AUS_DST=1;
				}
				} else { # local time calculations
				if ($ns < 3600) {
					$AUS_DST=0;
				} else {
					$AUS_DST=1;
				}
				}
			} else {
				$AUS_DST=1;
			}
			} # end of month checks						
		if ($DBX) {print "     DST: $AUS_DST\n";}
		if ($AUS_DST) {$gmt_offset++;}
		$AC_processed++;
		}

	if ( (!$AC_processed) and ($dst_range == 'FSO-LSM') )
		{
		if ($DBX) {print "     "._QXZ("First Sunday October to Last Sunday March")."\n";}
		#**********************************************************************
		#   TASMANIA ONLY
		#     This is s 1 if Daylight Savings Time is in effect and 0 if 
		#       Standard time is in effect.
		#     Based on first Sunday in October and last Sunday in March at 1 am.
		#**********************************************************************
			
			$AUST_DST=0;
			$mm = $mon;
			$dd = $mday;
			$ns = $dsec;
			$dow= $wday;

			if ($mm < 3 || $mm > 10) {
			$AUST_DST=1;
			} elseif ($mm >= 4 and $mm <= 9) {
			$AUST_DST=0;
			} elseif ($mm == 3) {
			if ($dd < 25) {
				$AUST_DST=1;
			} elseif ($dd < ($dow+25)) {
				$AUST_DST=1;
			} elseif ($dow == 0) {
				if ($timezone) { # UTC calculations
				if ($ns < (3600+($timezone-1)*3600)) {
					$AUST_DST=1;
				} else {
					$AUST_DST=0;
				}
				} else { # local time calculations
				if ($ns < 3600) {
					$AUST_DST=1;
				} else {
					$AUST_DST=0;
				}
				}
			} else {
				$AUST_DST=0;
			}
			} elseif ($mm == 10) {
			if ($dd > 7) {
				$AUST_DST=1;
			} elseif ($dd >= ($dow+1)) {
				if ($timezone) {
				if ($dow == 0 and $ns < (7200+$timezone*3600)) {
					$AUST_DST=0;
				} else {
					$AUST_DST=1;
				}
				} else {
				if ($dow == 0 and $ns < 3600) {
					$AUST_DST=0;
				} else {
					$AUST_DST=1;
				}
				}
			} else {
				$AUST_DST=0;
			}
			} # end of month checks						
		if ($DBX) {print "     DST: $AUST_DST\n";}
		if ($AUST_DST) {$gmt_offset++;}
		$AC_processed++;
		}

	if ( (!$AC_processed) and ($dst_range == 'FSO-FSA') )
		{
		if ($DBX) {print "     "._QXZ("Sunday in October to First Sunday in April")."\n";}
		#**********************************************************************
		# FSO-FSA
		#   2008+ AUSTRALIA ONLY (country code 61)
		#     This is returns 1 if Daylight Savings Time is in effect and 0 if 
		#       Standard time is in effect.
		#     Based on first Sunday in October and first Sunday in April at 1 am.
		#**********************************************************************
		
		$AUSE_DST=0;
		$mm = $mon;
		$dd = $mday;
		$ns = $dsec;
		$dow= $wday;

		if ($mm < 4 or $mm > 10) {
		$AUSE_DST=1;   
		} elseif ($mm >= 5 and $mm <= 9) {
		$AUSE_DST=0;   
		} elseif ($mm == 4) {
		if ($dd > 7) {
			$AUSE_DST=0;   
		} elseif ($dd >= ($dow+1)) {
			if ($timezone) {
			if ($dow == 0 and $ns < (3600+$timezone*3600)) {
				$AUSE_DST=1;   
			} else {
				$AUSE_DST=0;   
			}
			} else {
			if ($dow == 0 and $ns < 7200) {
				$AUSE_DST=1;   
			} else {
				$AUSE_DST=0;   
			}
			}
		} else {
			$AUSE_DST=1;   
		}
		} elseif ($mm == 10) {
		if ($dd >= 8) {
			$AUSE_DST=1;   
		} elseif ($dd >= ($dow+1)) {
			if ($timezone) {
			if ($dow == 0 and $ns < (7200+$timezone*3600)) {
				$AUSE_DST=0;   
			} else {
				$AUSE_DST=1;   
			}
			} else {
			if ($dow == 0 and $ns < 3600) {
				$AUSE_DST=0;   
			} else {
				$AUSE_DST=1;   
			}
			}
		} else {
			$AUSE_DST=0;   
		}
		} # end of month checks
		if ($DBX) {print "     DST: $AUSE_DST\n";}
		if ($AUSE_DST) {$gmt_offset++;}
		$AC_processed++;
		}

	if ( (!$AC_processed) and ($dst_range == 'FSO-TSM') )
		{
		if ($DBX) {print "     "._QXZ("First Sunday October to Third Sunday March")."\n";}
		#**********************************************************************
		#     This is s 1 if Daylight Savings Time is in effect and 0 if 
		#       Standard time is in effect.
		#     Based on first Sunday in October and third Sunday in March at 1 am.
		#**********************************************************************
			
			$NZL_DST=0;
			$mm = $mon;
			$dd = $mday;
			$ns = $dsec;
			$dow= $wday;

			if ($mm < 3 || $mm > 10) {
			$NZL_DST=1;
			} elseif ($mm >= 4 and $mm <= 9) {
			$NZL_DST=0;
			} elseif ($mm == 3) {
			if ($dd < 14) {
				$NZL_DST=1;
			} elseif ($dd < ($dow+14)) {
				$NZL_DST=1;
			} elseif ($dow == 0) {
				if ($timezone) { # UTC calculations
				if ($ns < (3600+($timezone-1)*3600)) {
					$NZL_DST=1;
				} else {
					$NZL_DST=0;
				}
				} else { # local time calculations
				if ($ns < 3600) {
					$NZL_DST=1;
				} else {
					$NZL_DST=0;
				}
				}
			} else {
				$NZL_DST=0;
			}
			} elseif ($mm == 10) {
			if ($dd > 7) {
				$NZL_DST=1;
			} elseif ($dd >= ($dow+1)) {
				if ($timezone) {
				if ($dow == 0 and $ns < (7200+$timezone*3600)) {
					$NZL_DST=0;
				} else {
					$NZL_DST=1;
				}
				} else {
				if ($dow == 0 and $ns < 3600) {
					$NZL_DST=0;
				} else {
					$NZL_DST=1;
				}
				}
			} else {
				$NZL_DST=0;
			}
			} # end of month checks						
		if ($DBX) {print "     DST: $NZL_DST\n";}
		if ($NZL_DST) {$gmt_offset++;}
		$AC_processed++;
		}

	if ( (!$AC_processed) and ($dst_range == 'LSS-FSA') )
		{
		if ($DBX) {print "     "._QXZ("Last Sunday in September to First Sunday in April")."\n";}
		#**********************************************************************
		# LSS-FSA
		#   2007+ NEW ZEALAND (country code 64)
		#     This is returns 1 if Daylight Savings Time is in effect and 0 if 
		#       Standard time is in effect.
		#     Based on last Sunday in September and first Sunday in April at 1 am.
		#**********************************************************************
		
		$NZLN_DST=0;
		$mm = $mon;
		$dd = $mday;
		$ns = $dsec;
		$dow= $wday;

		if ($mm < 4 || $mm > 9) {
		$NZLN_DST=1;   
		} elseif ($mm >= 5 && $mm <= 9) {
		$NZLN_DST=0;   
		} elseif ($mm == 4) {
		if ($dd > 7) {
			$NZLN_DST=0;   
		} elseif ($dd >= ($dow+1)) {
			if ($timezone) {
			if ($dow == 0 && $ns < (3600+$timezone*3600)) {
				$NZLN_DST=1;   
			} else {
				$NZLN_DST=0;   
			}
			} else {
			if ($dow == 0 && $ns < 7200) {
				$NZLN_DST=1;   
			} else {
				$NZLN_DST=0;   
			}
			}
		} else {
			$NZLN_DST=1;   
		}
		} elseif ($mm == 9) {
		if ($dd < 25) {
			$NZLN_DST=0;   
		} elseif ($dd < ($dow+25)) {
			$NZLN_DST=0;   
		} elseif ($dow == 0) {
			if ($timezone) { # UTC calculations
			if ($ns < (3600+($timezone-1)*3600)) {
				$NZLN_DST=0;   
			} else {
				$NZLN_DST=1;   
			}
			} else { # local time calculations
			if ($ns < 3600) {
				$NZLN_DST=0;   
			} else {
				$NZLN_DST=1;   
			}
			}
		} else {
			$NZLN_DST=1;   
		}
		} # end of month checks
		if ($DBX) {print "     DST: $NZLN_DST\n";}
		if ($NZLN_DST) {$gmt_offset++;}
		$AC_processed++;
		}

	if ( (!$AC_processed) and ($dst_range == 'TSO-LSF') )
		{
		if ($DBX) {print "     "._QXZ("Third Sunday October to Last Sunday February")."\n";}
		#**********************************************************************
		# TSO-LSF
		#     This is returns 1 if Daylight Savings Time is in effect and 0 if 
		#       Standard time is in effect. Brazil
		#     Based on Third Sunday October to Last Sunday February at 1 am.
		#**********************************************************************
			
			$BZL_DST=0;
			$mm = $mon;
			$dd = $mday;
			$ns = $dsec;
			$dow= $wday;

			if ($mm < 2 || $mm > 10) {
			$BZL_DST=1;   
			} elseif ($mm >= 3 and $mm <= 9) {
			$BZL_DST=0;   
			} elseif ($mm == 2) {
			if ($dd < 22) {
				$BZL_DST=1;   
			} elseif ($dd < ($dow+22)) {
				$BZL_DST=1;   
			} elseif ($dow == 0) {
				if ($timezone) { # UTC calculations
				if ($ns < (3600+($timezone-1)*3600)) {
					$BZL_DST=1;   
				} else {
					$BZL_DST=0;   
				}
				} else { # local time calculations
				if ($ns < 3600) {
					$BZL_DST=1;   
				} else {
					$BZL_DST=0;   
				}
				}
			} else {
				$BZL_DST=0;   
			}
			} elseif ($mm == 10) {
			if ($dd < 22) {
				$BZL_DST=0;   
			} elseif ($dd < ($dow+22)) {
				$BZL_DST=0;   
			} elseif ($dow == 0) {
				if ($timezone) { # UTC calculations
				if ($ns < (3600+($timezone-1)*3600)) {
					$BZL_DST=0;   
				} else {
					$BZL_DST=1;   
				}
				} else { # local time calculations
				if ($ns < 3600) {
					$BZL_DST=0;   
				} else {
					$BZL_DST=1;   
				}
				}
			} else {
				$BZL_DST=1;   
			}
			} # end of month checks
		if ($DBX) {print "     DST: $BZL_DST\n";}
		if ($BZL_DST) {$gmt_offset++;}
		$AC_processed++;
		}

	if (!$AC_processed)
		{
		if ($DBX) {print "     "._QXZ("No DST Method Found")."\n";}
		if ($DBX) {print "     DST: 0\n";}
		$AC_processed++;
		}

	return $gmt_offset;
	}


function mysql_to_mysqli($stmt, $link) 
	{
	$rslt=mysqli_query($link, $stmt);
	return $rslt;
	}


function MathZDC($dividend, $divisor, $quotient=0) 
	{
	if ($divisor==0) 
		{
		return $quotient;
		}
	else if ($dividend==0) 
		{
		return 0;
		}
	else 
		{
		return ($dividend/$divisor);
		}
	}

function use_archive_table($table_name) 
	{
	global $link;
	$tbl_stmt="show tables like '".$table_name."_archive'";
	$tbl_rslt=mysql_to_mysqli($tbl_stmt, $link);
	if (mysqli_num_rows($tbl_rslt)>0) 
		{
		$tbl_row=mysqli_fetch_row($tbl_rslt);
		return $tbl_row[0];
		}
	else
		{
		return $table_name;
		}
	}

function table_exists($table_name) 
	{
	global $link;
	$tbl_stmt="show tables like '".$table_name."'";
	$tbl_rslt=mysql_to_mysqli($tbl_stmt, $link);
	if (mysqli_num_rows($tbl_rslt)>0) 
		{
		$tbl_row=mysqli_fetch_row($tbl_rslt);
		return $tbl_row[0];
		}
	else
		{
		return false;
		}
	}

# function to print/echo content, options for length, alignment and ordered internal variables are included
function _QXZ($English_text, $sprintf=0, $align="l", $v_one='', $v_two='', $v_three='', $v_four='', $v_five='', $v_six='', $v_seven='', $v_eight='', $v_nine='')
	{
	global $SSenable_languages, $SSlanguage_method, $VUselected_language, $link;

	if ($SSenable_languages == '1')
		{
		if ($SSlanguage_method != 'DISABLED')
			{
			if ( (strlen($VUselected_language) > 0) and ($VUselected_language != 'default English') )
				{
				if ($English_text=="Y" && !preg_match('/english/i', $VUselected_language)) {$English_text="YES";}
				if ($English_text=="N" && !preg_match('/english/i', $VUselected_language)) {$English_text="NO";}	

				if ($SSlanguage_method == 'MYSQL')
					{
					$stmt="SELECT translated_text from vicidial_language_phrases where english_text='$English_text' and language_id='$VUselected_language';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$sl_ct = mysqli_num_rows($rslt);
					if ($sl_ct > 0)
						{
						$row=mysqli_fetch_row($rslt);
						$English_text =		$row[0];
						}
					}
				}
			}
		}

	if (preg_match("/%\ds/",$English_text))
		{
		$English_text = preg_replace("/%1s/", $v_one, $English_text);
		$English_text = preg_replace("/%2s/", $v_two, $English_text);
		$English_text = preg_replace("/%3s/", $v_three, $English_text);
		$English_text = preg_replace("/%4s/", $v_four, $English_text);
		$English_text = preg_replace("/%5s/", $v_five, $English_text);
		$English_text = preg_replace("/%6s/", $v_six, $English_text);
		$English_text = preg_replace("/%7s/", $v_seven, $English_text);
		$English_text = preg_replace("/%8s/", $v_eight, $English_text);
		$English_text = preg_replace("/%9s/", $v_nine, $English_text);
		}
	### uncomment to test output
	#	$English_text = str_repeat('*', strlen($English_text));
	if ($sprintf>0) 
		{
		if ($align=="r") 
			{
			$fmt="%".$sprintf."s";
			} 
		else 
			{
			$fmt="%-".$sprintf."s";
			}
		$English_text=sprintf($fmt, substr($English_text, 0, $sprintf));
		}
	return $English_text;
	}

function hex2rgb($hex) {
	$hex = str_replace("#", "", $hex);

	if(strlen($hex) == 3) {
		$r = hexdec(substr($hex,0,1).substr($hex,0,1));
		$g = hexdec(substr($hex,1,1).substr($hex,1,1));
		$b = hexdec(substr($hex,2,1).substr($hex,2,1));
	} else {
		$r = hexdec(substr($hex,0,2));
		$g = hexdec(substr($hex,2,2));
		$b = hexdec(substr($hex,4,2));
	}
	$rgb = array($r, $g, $b);
	return $rgb; // returns an array with the rgb values
}
function ConvertPresets($URL_string)
	{
	$from_array=array(
		"--A--today--B--", 
		"--A--yesterday--B--", 
		"--A--datetime--B--", 
		"--A--filedatetime--B--", 
		"--A--6days--B--", 
		"--A--7days--B--", 
		"--A--8days--B--", 
		"--A--13days--B--", 
		"--A--14days--B--", 
		"--A--15days--B--", 
		"--A--30days--B--"
	);

	$to_array=array(
		date("Y-m-d"), 
		date("Y-m-d", time()-86400),
		date("Y-m-d H:i:s"),
		date("YmdHis"),
		date("Y-m-d", time()-(86400*6)),
		date("Y-m-d", time()-(86400*7)),
		date("Y-m-d", time()-(86400*8)),
		date("Y-m-d", time()-(86400*13)),
		date("Y-m-d", time()-(86400*14)),
		date("Y-m-d", time()-(86400*15)),
		date("Y-m-d", time()-(86400*30))
	);

	$URL_string = strtr($URL_string, array_combine($from_array, $to_array));
	return $URL_string;
	}

function TimeToText($interval) 
	{
	$interval=preg_replace('/[^0-9]/', '', $interval);
	$interval+=0;
	if ($interval==0) 
		{
		return "0 "._QXZ("seconds");
		}
	$return_str="";

	$minutes=floor($interval/60);
	$seconds=floor($interval%60);
	if ($minutes>0) 
		{
		if ($minutes==1) {$return_str.="$minutes "._QXZ("minute").", ";} 
		else {$return_str.="$minutes "._QXZ("minutes").", ";}
		}
	if ($seconds>0) 
		{
		if ($seconds==1) {$return_str.="$seconds "._QXZ("second");}
		else {$return_str.="$seconds "._QXZ("seconds");}
		}
	$return_str=preg_replace('/, $/','', $return_str);
	return $return_str;
	}
function createDateRangeArray($strDateFrom,$strDateTo)
	{
	$aryRange=array();

	$iDateFrom=mktime(1,0,0,substr($strDateFrom,5,2),     substr($strDateFrom,8,2),substr($strDateFrom,0,4));
	$iDateTo=mktime(1,0,0,substr($strDateTo,5,2),     substr($strDateTo,8,2),substr($strDateTo,0,4));

	if ($iDateTo>=$iDateFrom)
	{
		array_push($aryRange,date('Y-m-d',$iDateFrom)); // first entry
		while ($iDateFrom<$iDateTo)
		{
			$iDateFrom+=86400; // add 24 hours
			array_push($aryRange,date('Y-m-d',$iDateFrom));
		}
	}
	return $aryRange;
	}


##### CALCULATE DIALABLE LEADS #####
function dialable_leads($DB,$link,$local_call_time,$dial_statuses,$camp_lists,$drop_lockout_time,$call_count_limit,$single_status,$fSQL,$only_return)
{
global $full_dialable_SQL;
$dialable_output='';
##### BEGIN calculate what gmt_offset_now values are within the allowed local_call_time setting ###
if (isset($camp_lists))
	{
	if (strlen($camp_lists)>1)
		{
		if (strlen($dial_statuses)>2)
			{
			$g=0;
			$p='13';
			$GMT_gmt[0] = '';
			$GMT_hour[0] = '';
			$GMT_day[0] = '';
			$YMD =  date("Y-m-d");	
			while ($p > -13)
				{
				$pzone=3600 * $p;
				$pmin=(gmdate("i", time() + $pzone));
				$phour=( (gmdate("G", time() + $pzone)) * 100);
				$pday=gmdate("w", time() + $pzone);
				$tz = sprintf("%.2f", $p);	
				$GMT_gmt[$g] = "$tz";
				$GMT_day[$g] = "$pday";
				$GMT_hour[$g] = ($phour + $pmin);
				$p = ($p - 0.25);
				$g++;
				}

			$stmt="SELECT call_time_id,call_time_name,call_time_comments,ct_default_start,ct_default_stop,ct_sunday_start,ct_sunday_stop,ct_monday_start,ct_monday_stop,ct_tuesday_start,ct_tuesday_stop,ct_wednesday_start,ct_wednesday_stop,ct_thursday_start,ct_thursday_stop,ct_friday_start,ct_friday_stop,ct_saturday_start,ct_saturday_stop,ct_state_call_times FROM vicidial_call_times where call_time_id='$local_call_time';";
			if ($DB) {$dialable_output .= "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$rowx=mysqli_fetch_row($rslt);
			$Gct_default_start =	$rowx[3];
			$Gct_default_stop =		$rowx[4];
			$Gct_sunday_start =		$rowx[5];
			$Gct_sunday_stop =		$rowx[6];
			$Gct_monday_start =		$rowx[7];
			$Gct_monday_stop =		$rowx[8];
			$Gct_tuesday_start =	$rowx[9];
			$Gct_tuesday_stop =		$rowx[10];
			$Gct_wednesday_start =	$rowx[11];
			$Gct_wednesday_stop =	$rowx[12];
			$Gct_thursday_start =	$rowx[13];
			$Gct_thursday_stop =	$rowx[14];
			$Gct_friday_start =		$rowx[15];
			$Gct_friday_stop =		$rowx[16];
			$Gct_saturday_start =	$rowx[17];
			$Gct_saturday_stop =	$rowx[18];
			$Gct_state_call_times = $rowx[19];
			$Gct_holidays =			$rowx[20];

			### BEGIN Check for outbound holiday ###
			$holiday_id = '';
			if (strlen($Gct_holidays)>2)
				{
				$Gct_holidaysSQL = preg_replace("/\|/", "','", "$Gct_holidays");
				$Gct_holidaysSQL = "'".$Gct_holidaysSQL."'";
				
				$stmt = "SELECT holiday_id,holiday_date,holiday_name,ct_default_start,ct_default_stop from vicidial_call_time_holidays where holiday_id IN($Gct_holidaysSQL) and holiday_status='ACTIVE' and holiday_date='$YMD' order by holiday_id;";
				$rslt=mysql_to_mysqli($stmt, $link);
				if ($DB) {$dialable_output .= "$stmt\n";}
				$sthCrows=mysqli_num_rows($rslt);
				if ($sthCrows > 0)
					{
					$aryC=mysqli_fetch_row($rslt);
					$holiday_id =				$aryC[0];
					$holiday_date =				$aryC[1];
					$holiday_name =				$aryC[2];
					if($Gct_default_start < $aryC[3])		{$Gct_default_start = $aryC[3];}
					if($Gct_default_stop > $aryC[4])		{$Gct_default_stop = $aryC[4];}						
					if($Gct_sunday_start < $aryC[3])		{$Gct_sunday_start = $aryC[3];}
					if($Gct_sunday_stop > $aryC[4])			{$Gct_sunday_stop = $aryC[4];}
					if($Gct_monday_start < $aryC[3])		{$Gct_monday_start = $aryC[3];}
					if($Gct_monday_stop >	$aryC[4])		{$Gct_monday_stop =	$aryC[4];}
					if($Gct_tuesday_start < $aryC[3])		{$Gct_tuesday_start = $aryC[3];}
					if($Gct_tuesday_stop > $aryC[4])		{$Gct_tuesday_stop = $aryC[4];}
					if($Gct_wednesday_start < $aryC[3]) 	{$Gct_wednesday_start = $aryC[3];}
					if($Gct_wednesday_stop > $aryC[4])		{$Gct_wednesday_stop = $aryC[4];}
					if($Gct_thursday_start < $aryC[3])		{$Gct_thursday_start = $aryC[3];}
					if($Gct_thursday_stop > $aryC[4])		{$Gct_thursday_stop = $aryC[4];}
					if($Gct_friday_start < $aryC[3])		{$Gct_friday_start = $aryC[3];}
					if($Gct_friday_stop > $aryC[4])			{$Gct_friday_stop = $aryC[4];}
					if($Gct_saturday_start < $aryC[3])		{$Gct_saturday_start = $aryC[3];}
					if($Gct_saturday_stop > $aryC[4])		{$Gct_saturday_stop = $aryC[4];}
					if ($DB) {$dialable_output .= "CALL TIME HOLIDAY FOUND!   $local_call_time|$holiday_id|$holiday_date|$holiday_name|$Gct_default_start|$Gct_default_stop|\n";}
					}
				}
			### END Check for outbound holiday ###

			$ct_states = '';
			$ct_state_gmt_SQL = '';
			$ct_srs=0;
			$b=0;
			if (strlen($Gct_state_call_times)>2)
				{
				$state_rules = explode('|',$Gct_state_call_times);
				$ct_srs = ((count($state_rules)) - 2);
				}
			while($ct_srs >= $b)
				{
				if (strlen($state_rules[$b])>1)
					{
					$stmt="SELECT state_call_time_id,state_call_time_state,state_call_time_name,state_call_time_comments,sct_default_start,sct_default_stop,sct_sunday_start,sct_sunday_stop,sct_monday_start,sct_monday_stop,sct_tuesday_start,sct_tuesday_stop,sct_wednesday_start,sct_wednesday_stop,sct_thursday_start,sct_thursday_stop,sct_friday_start,sct_friday_stop,sct_saturday_start,sct_saturday_stop from vicidial_state_call_times where state_call_time_id='$state_rules[$b]';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$Gstate_call_time_id =		$row[0];
					$Gstate_call_time_state =	$row[1];
					$Gsct_default_start =		$row[4];
					$Gsct_default_stop =		$row[5];
					$Gsct_sunday_start =		$row[6];
					$Gsct_sunday_stop =			$row[7];
					$Gsct_monday_start =		$row[8];
					$Gsct_monday_stop =			$row[9];
					$Gsct_tuesday_start =		$row[10];
					$Gsct_tuesday_stop =		$row[11];
					$Gsct_wednesday_start =		$row[12];
					$Gsct_wednesday_stop =		$row[13];
					$Gsct_thursday_start =		$row[14];
					$Gsct_thursday_stop =		$row[15];
					$Gsct_friday_start =		$row[16];
					$Gsct_friday_stop =			$row[17];
					$Gsct_saturday_start =		$row[18];
					$Gsct_saturday_stop =		$row[19];
					$Sct_holidays =				$row[20];
					$ct_states .="'$Gstate_call_time_state',";

					### BEGIN Check for outbound state holiday ###
					$Sholiday_id = '';
					if ((strlen($Sct_holidays)>2) or ((strlen($holiday_id)>2) and (strlen($Sholiday_id)<2))) 
						{
						# Apply state holiday
						if (strlen($Sct_holidays)>2)
							{								
							$Sct_holidaysSQL = preg_replace("/\|/", "','", "$Sct_holidays");
							$Sct_holidaysSQL = "'".$Sct_holidaysSQL."'";
							$stmt = "SELECT holiday_id,holiday_date,holiday_name,ct_default_start,ct_default_stop from vicidial_call_time_holidays where holiday_id IN($Sct_holidaysSQL) and holiday_status='ACTIVE' and holiday_date='$YMD' order by holiday_id;";
							$holidaytype = "STATE CALL TIME HOLIDAY FOUND!   ";
							}
						# Apply call time wide holiday
						elseif ((strlen($holiday_id)>2) and (strlen($Sholiday_id)<2))
							{
							$stmt = "SELECT holiday_id,holiday_date,holiday_name,ct_default_start,ct_default_stop from vicidial_call_time_holidays where holiday_id='$holiday_id' and holiday_status='ACTIVE' and holiday_date='$YMD' order by holiday_id;";
							$holidaytype = "NO STATE HOLIDAY APPLYING CALL TIME HOLIDAY!   ";
							}				
						$rslt=mysql_to_mysqli($stmt, $link);
						if ($DB) {$dialable_output .= "$stmt\n";}
						$sthCrows=mysqli_num_rows($rslt);
						if ($sthCrows > 0)
							{
							$aryC=mysqli_fetch_row($rslt);
							$Sholiday_id =				$aryC[0];
							$Sholiday_date =			$aryC[1];
							$Sholiday_name =			$aryC[2];
							if ( ($Gsct_default_start < $aryC[3]) && ($Gsct_default_stop > 0) )		{$Gsct_default_start = $aryC[3];}
							if ( ($Gsct_default_stop > $aryC[4]) && ($Gsct_default_stop > 0) )		{$Gsct_default_stop = $aryC[4];}
							if ( ($Gsct_sunday_start < $aryC[3]) && ($Gsct_sunday_stop > 0) )		{$Gsct_sunday_start = $aryC[3];}
							if ( ($Gsct_sunday_stop > $aryC[4]) && ($Gsct_sunday_stop > 0) )		{$Gsct_sunday_stop = $aryC[4];}
							if ( ($Gsct_monday_start < $aryC[3]) && ($Gsct_monday_stop > 0) )		{$Gsct_monday_start = $aryC[3];}
							if ( ($Gsct_monday_stop > $aryC[4]) && ($Gsct_monday_stop > 0) )		{$Gsct_monday_stop = $aryC[4];}
							if ( ($Gsct_tuesday_start < $aryC[3]) && ($Gsct_tuesday_stop > 0) )		{$Gsct_tuesday_start = $aryC[3];}
							if ( ($Gsct_tuesday_stop > $aryC[4]) && ($Gsct_tuesday_stop > 0) )		{$Gsct_tuesday_stop = $aryC[4];}
							if ( ($Gsct_wednesday_start < $aryC[3]) && ($Gsct_wednesday_stop > 0) ) {$Gsct_wednesday_start = $aryC[3];}
							if ( ($Gsct_wednesday_stop > $aryC[4]) && ($Gsct_wednesday_stop > 0) )	{$Gsct_wednesday_stop = $aryC[4];}
							if ( ($Gsct_thursday_start < $aryC[3]) && ($Gsct_thursday_stop > 0) )	{$Gsct_thursday_start = $aryC[3];}
							if ( ($Gsct_thursday_stop > $aryC[4]) && ($Gsct_thursday_stop > 0) )	{$Gsct_thursday_stop = $aryC[4];}
							if ( ($Gsct_friday_start < $aryC[3]) && ($Gsct_friday_stop > 0) )		{$Gsct_friday_start = $aryC[3];}
							if ( ($Gsct_friday_stop > $aryC[4]) && ($Gsct_friday_stop > 0) )		{$Gsct_friday_stop = $aryC[4];}
							if ( ($Gsct_saturday_start < $aryC[3]) && ($Gsct_saturday_stop > 0) )	{$Gsct_saturday_start = $aryC[3];}
							if ( ($Gsct_saturday_stop > $aryC[4]) && ($Gsct_saturday_stop > 0) )	{$Gsct_saturday_stop = $aryC[4];}
							if ($DB) {$dialable_output .= "$holidaytype   |$Gstate_call_time_id|$Gstate_call_time_state|$Sholiday_id|$Sholiday_date|$Sholiday_name|$Gsct_default_start|$Gsct_default_stop|\n";}
							}
						}
					$r=0;
					$state_gmt='';
					while($r < $g)
						{
						if ($GMT_day[$r]==0)	#### Sunday local time
							{
							if (($Gsct_sunday_start==0) and ($Gsct_sunday_stop==0))
								{
								if ( ($GMT_hour[$r]>=$Gsct_default_start) and ($GMT_hour[$r]<$Gsct_default_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							else
								{
								if ( ($GMT_hour[$r]>=$Gsct_sunday_start) and ($GMT_hour[$r]<$Gsct_sunday_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							}
						if ($GMT_day[$r]==1)	#### Monday local time
							{
							if (($Gsct_monday_start==0) and ($Gsct_monday_stop==0))
								{
								if ( ($GMT_hour[$r]>=$Gsct_default_start) and ($GMT_hour[$r]<$Gsct_default_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							else
								{
								if ( ($GMT_hour[$r]>=$Gsct_monday_start) and ($GMT_hour[$r]<$Gsct_monday_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							}
						if ($GMT_day[$r]==2)	#### Tuesday local time
							{
							if (($Gsct_tuesday_start==0) and ($Gsct_tuesday_stop==0))
								{
								if ( ($GMT_hour[$r]>=$Gsct_default_start) and ($GMT_hour[$r]<$Gsct_default_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							else
								{
								if ( ($GMT_hour[$r]>=$Gsct_tuesday_start) and ($GMT_hour[$r]<$Gsct_tuesday_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							}
						if ($GMT_day[$r]==3)	#### Wednesday local time
							{
							if (($Gsct_wednesday_start==0) and ($Gsct_wednesday_stop==0))
								{
								if ( ($GMT_hour[$r]>=$Gsct_default_start) and ($GMT_hour[$r]<$Gsct_default_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							else
								{
								if ( ($GMT_hour[$r]>=$Gsct_wednesday_start) and ($GMT_hour[$r]<$Gsct_wednesday_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							}
						if ($GMT_day[$r]==4)	#### Thursday local time
							{
							if (($Gsct_thursday_start==0) and ($Gsct_thursday_stop==0))
								{
								if ( ($GMT_hour[$r]>=$Gsct_default_start) and ($GMT_hour[$r]<$Gsct_default_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							else
								{
								if ( ($GMT_hour[$r]>=$Gsct_thursday_start) and ($GMT_hour[$r]<$Gsct_thursday_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							}
						if ($GMT_day[$r]==5)	#### Friday local time
							{
							if (($Gsct_friday_start==0) and ($Gsct_friday_stop==0))
								{
								if ( ($GMT_hour[$r]>=$Gsct_default_start) and ($GMT_hour[$r]<$Gsct_default_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							else
								{
								if ( ($GMT_hour[$r]>=$Gsct_friday_start) and ($GMT_hour[$r]<$Gsct_friday_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							}
						if ($GMT_day[$r]==6)	#### Saturday local time
							{
							if (($Gsct_saturday_start==0) and ($Gsct_saturday_stop==0))
								{
								if ( ($GMT_hour[$r]>=$Gsct_default_start) and ($GMT_hour[$r]<$Gsct_default_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							else
								{
								if ( ($GMT_hour[$r]>=$Gsct_saturday_start) and ($GMT_hour[$r]<$Gsct_saturday_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							}
						$r++;
						}
					$state_gmt = "$state_gmt'99'";
					$ct_state_gmt_SQL .= "or (state='$Gstate_call_time_state' and gmt_offset_now IN($state_gmt)) ";
					}

				$b++;
				}
			if (strlen($ct_states)>2)
				{
				$ct_states = preg_replace('/,$/i', '',$ct_states);
				$ct_statesSQL = "and state NOT IN($ct_states)";
				}
			else
				{
				$ct_statesSQL = "";
				}

			$r=0;
			$default_gmt='';
			while($r < $g)
				{
				if ($GMT_day[$r]==0)	#### Sunday local time
					{
					if (($Gct_sunday_start==0) and ($Gct_sunday_stop==0))
						{
						if ( ($GMT_hour[$r]>=$Gct_default_start) and ($GMT_hour[$r]<$Gct_default_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					else
						{
						if ( ($GMT_hour[$r]>=$Gct_sunday_start) and ($GMT_hour[$r]<$Gct_sunday_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					}
				if ($GMT_day[$r]==1)	#### Monday local time
					{
					if (($Gct_monday_start==0) and ($Gct_monday_stop==0))
						{
						if ( ($GMT_hour[$r]>=$Gct_default_start) and ($GMT_hour[$r]<$Gct_default_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					else
						{
						if ( ($GMT_hour[$r]>=$Gct_monday_start) and ($GMT_hour[$r]<$Gct_monday_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					}
				if ($GMT_day[$r]==2)	#### Tuesday local time
					{
					if (($Gct_tuesday_start==0) and ($Gct_tuesday_stop==0))
						{
						if ( ($GMT_hour[$r]>=$Gct_default_start) and ($GMT_hour[$r]<$Gct_default_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					else
						{
						if ( ($GMT_hour[$r]>=$Gct_tuesday_start) and ($GMT_hour[$r]<$Gct_tuesday_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					}
				if ($GMT_day[$r]==3)	#### Wednesday local time
					{
					if (($Gct_wednesday_start==0) and ($Gct_wednesday_stop==0))
						{
						if ( ($GMT_hour[$r]>=$Gct_default_start) and ($GMT_hour[$r]<$Gct_default_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					else
						{
						if ( ($GMT_hour[$r]>=$Gct_wednesday_start) and ($GMT_hour[$r]<$Gct_wednesday_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					}
				if ($GMT_day[$r]==4)	#### Thursday local time
					{
					if (($Gct_thursday_start==0) and ($Gct_thursday_stop==0))
						{
						if ( ($GMT_hour[$r]>=$Gct_default_start) and ($GMT_hour[$r]<$Gct_default_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					else
						{
						if ( ($GMT_hour[$r]>=$Gct_thursday_start) and ($GMT_hour[$r]<$Gct_thursday_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					}
				if ($GMT_day[$r]==5)	#### Friday local time
					{
					if (($Gct_friday_start==0) and ($Gct_friday_stop==0))
						{
						if ( ($GMT_hour[$r]>=$Gct_default_start) and ($GMT_hour[$r]<$Gct_default_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					else
						{
						if ( ($GMT_hour[$r]>=$Gct_friday_start) and ($GMT_hour[$r]<$Gct_friday_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					}
				if ($GMT_day[$r]==6)	#### Saturday local time
					{
					if (($Gct_saturday_start==0) and ($Gct_saturday_stop==0))
						{
						if ( ($GMT_hour[$r]>=$Gct_default_start) and ($GMT_hour[$r]<$Gct_default_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					else
						{
						if ( ($GMT_hour[$r]>=$Gct_saturday_start) and ($GMT_hour[$r]<$Gct_saturday_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					}
				$r++;
				}

			$default_gmt = "$default_gmt'99'";
			$all_gmtSQL = "(gmt_offset_now IN($default_gmt) $ct_statesSQL) $ct_state_gmt_SQL";

			$dial_statuses = preg_replace("/ -$/","",$dial_statuses);
			$Dstatuses = explode(" ", $dial_statuses);
			$Ds_to_print = (count($Dstatuses) - 0);
			$Dsql = '';
			$o=0;
			while ($Ds_to_print > $o) 
				{
				$o++;
				$Dsql .= "'$Dstatuses[$o]',";
				}
			$Dsql = preg_replace("/,$/","",$Dsql);
			if (strlen($Dsql) < 2) {$Dsql = "''";}

			$DLTsql='';
			if ($drop_lockout_time > 0)
				{
				$DLseconds = ($drop_lockout_time * 3600);
				$DLseconds = floor($DLseconds);
				$DLseconds = intval("$DLseconds");
				$DLTsql = "and ( ( (status IN('DROP','XDROP')) and (last_local_call_time < CONCAT(DATE_ADD(NOW(), INTERVAL -$DLseconds SECOND),' ',CURTIME()) ) ) or (status NOT IN('DROP','XDROP')) )";
				}

			$CCLsql='';
			if ($call_count_limit > 0)
				{
				$CCLsql = "and (called_count < $call_count_limit)";
				}

			$EXPsql='';
			$expired_lists='';
			$REPORTdate = date("Y-m-d");
			$stmt="SELECT list_id FROM vicidial_lists where list_id IN($camp_lists) and (active='Y') and (expiration_date < \"$REPORTdate\");";
			#$DB=1;
			if ($DB) {$dialable_output .= "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$rslt_rows = mysqli_num_rows($rslt);
			$f=0;
			while ($rslt_rows > $f)
				{
				$rowx=mysqli_fetch_row($rslt);
				$expired_lists .= "'$rowx[0]',";
				$f++;
				}
			$expired_lists = preg_replace("/,$/",'',$expired_lists);
			if (strlen($expired_lists) < 2) {$expired_lists = "''";}
			$EXPsql = "and list_id NOT IN($expired_lists)";


			#################################								
			# Camp List
			$stmt="SELECT list_id FROM vicidial_lists where list_id IN($camp_lists) and active='Y';";
			$rslt_list=mysql_to_mysqli($stmt, $link);
			$camplists_ct = mysqli_num_rows($rslt_list);
			if ($DB) {$dialable_output .= "$camplists_ct|$stmt\n";}
			$k=0;
			$camp_lists='';
			while ($camplists_ct > $k)
				{
				$rowA = mysqli_fetch_row($rslt_list);
				$camp_lists .=	"'$rowA[0]',";

				##### Get Call List call time settings
				##### BEGIN calculate what gmt_offset_now values are within the allowed local_call_time setting ###
				$g=0;
				$p='13';
				$GMT_gmt[0] = '';
				$GMT_hour[0] = '';
				$GMT_day[0] = '';
				$YMD =  date("Y-m-d");
				while ($p > -13)
					{
					$pzone=3600 * $p;
					$pmin=(gmdate("i", time() + $pzone));
					$phour=( (gmdate("G", time() + $pzone)) * 100);
					$pday=gmdate("w", time() + $pzone);
					$tz = sprintf("%.2f", $p);	
					$GMT_gmt[$g] = "$tz";
					$GMT_day[$g] = "$pday";
					$GMT_hour[$g] = ($phour + $pmin);
					$p = ($p - 0.25);
					$g++;
					}

				# Set List ID Variable
				$cur_list_id = $rowA[0];			
				$list_local_call_time = "";

				# Pull the call times for the lists
				$stmt="SELECT local_call_time FROM vicidial_lists where list_id='$cur_list_id';";
				$rslt=mysql_to_mysqli($stmt, $link);
				$rslt_ct = mysqli_num_rows($rslt);
				if ($rslt_ct > 0)
					{
					#set Cur call_time
					$row=mysqli_fetch_row($rslt);
					$cur_call_time  =	$row[0];
					}

				# check that call time exists
				if ($cur_call_time != "campaign") 
					{
					$stmt="SELECT count(*) from vicidial_call_times where call_time_id='$cur_call_time';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$call_time_exists  =	$row[0];
					if ($call_time_exists < 1) 
						{$cur_call_time = 'campaign';}
					}

				##### BEGIN local call time for list set different than campaign #####
				if ($cur_call_time != "campaign")
					{
					##### BEGIN calculate what gmt_offset_now values are within the allowed local_call_time setting ###
					$stmt = "SELECT call_time_id,call_time_name,call_time_comments,ct_default_start,ct_default_stop,ct_sunday_start,ct_sunday_stop,ct_monday_start,ct_monday_stop,ct_tuesday_start,ct_tuesday_stop,ct_wednesday_start,ct_wednesday_stop,ct_thursday_start,ct_thursday_stop,ct_friday_start,ct_friday_stop,ct_saturday_start,ct_saturday_stop,ct_state_call_times,ct_holidays FROM vicidial_call_times where call_time_id='$cur_call_time';";
					if ($DB) {$dialable_output .= "$stmt\n";}
					$rsltD=mysql_to_mysqli($stmt, $link);
					$aryD=mysqli_fetch_row($rsltD);
					$Gct_default_start =	$aryD[3];
					$Gct_default_stop =		$aryD[4];
					$Gct_sunday_start =		$aryD[5];
					$Gct_sunday_stop =		$aryD[6];
					$Gct_monday_start =		$aryD[7];
					$Gct_monday_stop =		$aryD[8];
					$Gct_tuesday_start =	$aryD[9];
					$Gct_tuesday_stop =		$aryD[10];
					$Gct_wednesday_start =	$aryD[11];
					$Gct_wednesday_stop =	$aryD[12];
					$Gct_thursday_start =	$aryD[13];
					$Gct_thursday_stop =	$aryD[14];
					$Gct_friday_start =		$aryD[15];
					$Gct_friday_stop =		$aryD[16];
					$Gct_saturday_start =	$aryD[17];
					$Gct_saturday_stop =	$aryD[18];
					$Gct_state_call_times = $aryD[19];
					$Gct_holidays =			$aryD[20];

					### BEGIN Check for outbound holiday ###
					$holiday_id = '';
					if (strlen($Gct_holidays)>2)
						{
						$Gct_holidaysSQL = preg_replace("/\|/", "','", "$Gct_holidays");
						$Gct_holidaysSQL = "'".$Gct_holidaysSQL."'";
						
						$stmt = "SELECT holiday_id,holiday_date,holiday_name,ct_default_start,ct_default_stop from vicidial_call_time_holidays where holiday_id IN($Gct_holidaysSQL) and holiday_status='ACTIVE' and holiday_date='$YMD' order by holiday_id;";
						$rslt=mysql_to_mysqli($stmt, $link);
						$sthCrows=mysqli_num_rows($rslt);
						if ($sthCrows > 0)
							{
							$aryC=mysqli_fetch_row($rslt);
							$holiday_id =				$aryC[0];
							$holiday_date =				$aryC[1];
							$holiday_name =				$aryC[2];
							if ( ($Gct_default_start < $aryC[3]) && ($Gct_default_stop > 0) )		{$Gct_default_start = $aryC[3];}
							if ( ($Gct_default_stop > $aryC[4]) && ($Gct_default_stop > 0) )		{$Gct_default_stop = $aryC[4];}
							if ( ($Gct_sunday_start < $aryC[3]) && ($Gct_sunday_stop > 0) )			{$Gct_sunday_start = $aryC[3];}
							if ( ($Gct_sunday_stop > $aryC[4]) && ($Gct_sunday_stop > 0) )			{$Gct_sunday_stop = $aryC[4];}
							if ( ($Gct_monday_start < $aryC[3]) && ($Gct_monday_stop > 0) )			{$Gct_monday_start = $aryC[3];}
							if ( ($Gct_monday_stop >	$aryC[4]) && ($Gct_monday_stop > 0) )		{$Gct_monday_stop =	$aryC[4];}
							if ( ($Gct_tuesday_start < $aryC[3]) && ($Gct_tuesday_stop > 0) )		{$Gct_tuesday_start = $aryC[3];}
							if ( ($Gct_tuesday_stop > $aryC[4]) && ($Gct_tuesday_stop > 0) )		{$Gct_tuesday_stop = $aryC[4];}
							if ( ($Gct_wednesday_start < $aryC[3]) && ($Gct_wednesday_stop > 0) ) 	{$Gct_wednesday_start = $aryC[3];}
							if ( ($Gct_wednesday_stop > $aryC[4]) && ($Gct_wednesday_stop > 0) )	{$Gct_wednesday_stop = $aryC[4];}
							if ( ($Gct_thursday_start < $aryC[3]) && ($Gct_thursday_stop > 0) )		{$Gct_thursday_start = $aryC[3];}
							if ( ($Gct_thursday_stop > $aryC[4]) && ($Gct_thursday_stop > 0) )		{$Gct_thursday_stop = $aryC[4];}
							if ( ($Gct_friday_start < $aryC[3]) && ($Gct_friday_stop > 0) )			{$Gct_friday_start = $aryC[3];}
							if ( ($Gct_friday_stop > $aryC[4]) && ($Gct_friday_stop > 0) )			{$Gct_friday_stop = $aryC[4];}
							if ( ($Gct_saturday_start < $aryC[3]) && ($Gct_saturday_stop > 0) )		{$Gct_saturday_start = $aryC[3];}
							if ( ($Gct_saturday_stop > $aryC[4]) && ($Gct_saturday_stop > 0) )		{$Gct_saturday_stop = $aryC[4];}
							if ($DB) {$dialable_output .= "LIST CALL TIME HOLIDAY FOUND!   $local_call_time|$holiday_id|$holiday_date|$holiday_name|$Gct_default_start|$Gct_default_stop|\n";}
							}
						}
					### END Check for outbound holiday ###

					$ct_states = '';
					$ct_state_gmt_SQL = '';
					$ct_srs=0;
					$b=0;
					if (strlen($Gct_state_call_times)>2)
						{
						$state_rules = explode('|',$Gct_state_call_times);
						$ct_srs = ((count($state_rules)) - 2);
						}
					while($ct_srs >= $b)
						{
						if (strlen($state_rules[$b])>1)
							{
							$stmt = "SELECT state_call_time_id,state_call_time_state,state_call_time_name,state_call_time_comments,sct_default_start,sct_default_stop,sct_sunday_start,sct_sunday_stop,sct_monday_start,sct_monday_stop,sct_tuesday_start,sct_tuesday_stop,sct_wednesday_start,sct_wednesday_stop,sct_thursday_start,sct_thursday_stop,sct_friday_start,sct_friday_stop,sct_saturday_start,sct_saturday_stop,ct_holidays from vicidial_state_call_times where state_call_time_id='$state_rules[$b]';";
							$rslt=mysql_to_mysqli($stmt, $link);
							$sthCrows=mysqli_num_rows($rslt);
							if ($sthCrows > 0)
								{
								$aryC=mysqli_fetch_row($rslt);
								$Gstate_call_time_id =		$aryC[0];
								$Gstate_call_time_state =	$aryC[1];
								$Gsct_default_start =		$aryC[4];
								$Gsct_default_stop =		$aryC[5];
								$Gsct_sunday_start =		$aryC[6];
								$Gsct_sunday_stop =			$aryC[7];
								$Gsct_monday_start =		$aryC[8];
								$Gsct_monday_stop =			$aryC[9];
								$Gsct_tuesday_start =		$aryC[10];
								$Gsct_tuesday_stop =		$aryC[11];
								$Gsct_wednesday_start =		$aryC[12];
								$Gsct_wednesday_stop =		$aryC[13];
								$Gsct_thursday_start =		$aryC[14];
								$Gsct_thursday_stop =		$aryC[15];
								$Gsct_friday_start =		$aryC[16];
								$Gsct_friday_stop =			$aryC[17];
								$Gsct_saturday_start =		$aryC[18];
								$Gsct_saturday_stop =		$aryC[19];
								$Sct_holidays =				$aryC[20];
								$ct_states .="'$Gstate_call_time_state',";
								}

							### BEGIN Check for outbound state holiday ###
							$Sholiday_id = '';
							if ((strlen($Sct_holidays)>2) or ((strlen($holiday_id)>2) and (strlen($Sholiday_id)<2))) 
								{
								#Apply state holiday
								if (strlen($Sct_holidays)>2)
									{								
									$Sct_holidaysSQL = preg_replace("/\|/", "','", "$Sct_holidays");
									$Sct_holidaysSQL = "'".$Sct_holidaysSQL."'";
									$stmt = "SELECT holiday_id,holiday_date,holiday_name,ct_default_start,ct_default_stop from vicidial_call_time_holidays where holiday_id IN($Sct_holidaysSQL) and holiday_status='ACTIVE' and holiday_date='$YMD' order by holiday_id;";
									$holidaytype = "LIST STATE CALL TIME HOLIDAY FOUND!   ";
									}
								#Apply call time wide holiday
								elseif ((strlen($holiday_id)>2) and (strlen($Sholiday_id)<2))
									{
									$stmt = "SELECT holiday_id,holiday_date,holiday_name,ct_default_start,ct_default_stop from vicidial_call_time_holidays where holiday_id='$holiday_id' and holiday_status='ACTIVE' and holiday_date='$YMD' order by holiday_id;";
									$holidaytype = "LIST NO STATE HOLIDAY APPLYING CALL TIME HOLIDAY!   ";
									}				
								$rslt=mysql_to_mysqli($stmt, $link);
								if ($DB) {$dialable_output .= "$stmt\n";}
								$sthCrows=mysqli_num_rows($rslt);
								if ($sthCrows > 0)
									{
									$aryC=mysqli_fetch_row($rslt);
									$Sholiday_id =				$aryC[0];
									$Sholiday_date =			$aryC[1];
									$Sholiday_name =			$aryC[2];
									if ( ($Gsct_default_start < $aryC[3]) && ($Gsct_default_stop > 0) )		{$Gsct_default_start = $aryC[3];}
									if ( ($Gsct_default_stop > $aryC[4]) && ($Gsct_default_stop > 0) )		{$Gsct_default_stop = $aryC[4];}
									if ( ($Gsct_sunday_start < $aryC[3]) && ($Gsct_sunday_stop > 0) )		{$Gsct_sunday_start = $aryC[3];}
									if ( ($Gsct_sunday_stop > $aryC[4]) && ($Gsct_sunday_stop > 0) )		{$Gsct_sunday_stop = $aryC[4];}
									if ( ($Gsct_monday_start < $aryC[3]) && ($Gsct_monday_stop > 0) )		{$Gsct_monday_start = $aryC[3];}
									if ( ($Gsct_monday_stop >	$aryC[4]) && ($Gsct_monday_stop > 0) )		{$Gsct_monday_stop =	$aryC[4];}
									if ( ($Gsct_tuesday_start < $aryC[3]) && ($Gsct_tuesday_stop > 0) )		{$Gsct_tuesday_start = $aryC[3];}
									if ( ($Gsct_tuesday_stop > $aryC[4]) && ($Gsct_tuesday_stop > 0) )		{$Gsct_tuesday_stop = $aryC[4];}
									if ( ($Gsct_wednesday_start < $aryC[3]) && ($Gsct_wednesday_stop > 0) ) {$Gsct_wednesday_start = $aryC[3];}
									if ( ($Gsct_wednesday_stop > $aryC[4]) && ($Gsct_wednesday_stop > 0) )	{$Gsct_wednesday_stop = $aryC[4];}
									if ( ($Gsct_thursday_start < $aryC[3]) && ($Gsct_thursday_stop > 0) )	{$Gsct_thursday_start = $aryC[3];}
									if ( ($Gsct_thursday_stop > $aryC[4]) && ($Gsct_thursday_stop > 0) )	{$Gsct_thursday_stop = $aryC[4];}
									if ( ($Gsct_friday_start < $aryC[3]) && ($Gsct_friday_stop > 0) )		{$Gsct_friday_start = $aryC[3];}
									if ( ($Gsct_friday_stop > $aryC[4]) && ($Gsct_friday_stop > 0) )		{$Gsct_friday_stop = $aryC[4];}
									if ( ($Gsct_saturday_start < $aryC[3]) && ($Gsct_saturday_stop > 0) )	{$Gsct_saturday_start = $aryC[3];}
									if ( ($Gsct_saturday_stop > $aryC[4]) && ($Gsct_saturday_stop > 0) )	{$Gsct_saturday_stop = $aryC[4];}
									if ($DB) {$dialable_output .= "$holidaytype   |$Gstate_call_time_id|$Gstate_call_time_state|$Sholiday_id|$Sholiday_date|$Sholiday_name|$Gsct_default_start|$Gsct_default_stop|\n";}
									}
								}
							### END Check for outbound state holiday ###

							$r=0;
							$state_gmt='';
							$del_state_gmt='';
							while($r < $g)
								{
								if ($GMT_day[$r]==0)	#### Sunday local time
									{
									if (($Gsct_sunday_start==0) && ($Gsct_sunday_stop==0))
										{
										if ( ($GMT_hour[$r]>=$Gsct_default_start) && ($GMT_hour[$r]<$Gsct_default_stop) )
											{$state_gmt.="'$GMT_gmt[$r]',";}
										}
									else
										{
										if ( ($GMT_hour[$r]>=$Gsct_sunday_start) && ($GMT_hour[$r]<$Gsct_sunday_stop) )
											{$state_gmt.="'$GMT_gmt[$r]',";}
										}
									}
								if ($GMT_day[$r]==1)	#### Monday local time
									{
									if (($Gsct_monday_start==0) && ($Gsct_monday_stop==0))
										{
										if ( ($GMT_hour[$r]>=$Gsct_default_start) && ($GMT_hour[$r]<$Gsct_default_stop) )
											{$state_gmt.="'$GMT_gmt[$r]',";}
										}
									else
										{
										if ( ($GMT_hour[$r]>=$Gsct_monday_start) && ($GMT_hour[$r]<$Gsct_monday_stop) )
											{$state_gmt.="'$GMT_gmt[$r]',";}
										}
									}
								if ($GMT_day[$r]==2)	#### Tuesday local time
									{
									if (($Gsct_tuesday_start==0) && ($Gsct_tuesday_stop==0))
										{
										if ( ($GMT_hour[$r]>=$Gsct_default_start) && ($GMT_hour[$r]<$Gsct_default_stop) )
											{$state_gmt.="'$GMT_gmt[$r]',";}
										}
									else
										{
										if ( ($GMT_hour[$r]>=$Gsct_tuesday_start) && ($GMT_hour[$r]<$Gsct_tuesday_stop) )
											{$state_gmt.="'$GMT_gmt[$r]',";}
										}
									}
								if ($GMT_day[$r]==3)	#### Wednesday local time
									{
									if (($Gsct_wednesday_start==0) && ($Gsct_wednesday_stop==0))
										{
										if ( ($GMT_hour[$r]>=$Gsct_default_start) && ($GMT_hour[$r]<$Gsct_default_stop) )
											{$state_gmt.="'$GMT_gmt[$r]',";}
										}
									else
										{
										if ( ($GMT_hour[$r]>=$Gsct_wednesday_start) && ($GMT_hour[$r]<$Gsct_wednesday_stop) )
											{$state_gmt.="'$GMT_gmt[$r]',";}
										}
									}
								if ($GMT_day[$r]==4)	#### Thursday local time
									{
									if (($Gsct_thursday_start==0) && ($Gsct_thursday_stop==0))
										{
										if ( ($GMT_hour[$r]>=$Gsct_default_start) && ($GMT_hour[$r]<$Gsct_default_stop) )
											{$state_gmt.="'$GMT_gmt[$r]',";}
										}
									else
										{
										if ( ($GMT_hour[$r]>=$Gsct_thursday_start) && ($GMT_hour[$r]<$Gsct_thursday_stop) )
											{$state_gmt.="'$GMT_gmt[$r]',";}
										}
									}
								if ($GMT_day[$r]==5)	#### Friday local time
									{
									if (($Gsct_friday_start==0) && ($Gsct_friday_stop==0))
										{
										if ( ($GMT_hour[$r]>=$Gsct_default_start) && ($GMT_hour[$r]<$Gsct_default_stop) )
											{$state_gmt.="'$GMT_gmt[$r]',";}
										}
									else
										{
										if ( ($GMT_hour[$r]>=$Gsct_friday_start) && ($GMT_hour[$r]<$Gsct_friday_stop) )
											{$state_gmt.="'$GMT_gmt[$r]',";}
										}
									}
								if ($GMT_day[$r]==6)	#### Saturday local time
									{
									if (($Gsct_saturday_start==0) && ($Gsct_saturday_stop==0))
										{
										if ( ($GMT_hour[$r]>=$Gsct_default_start) && ($GMT_hour[$r]<$Gsct_default_stop) )
											{$state_gmt.="'$GMT_gmt[$r]',";}
										}
									else
										{
										if ( ($GMT_hour[$r]>=$Gsct_saturday_start) && ($GMT_hour[$r]<$Gsct_saturday_stop) )
											{$state_gmt.="'$GMT_gmt[$r]',";}
										}
									}
								$r++;
								}
							$state_gmt = "$state_gmt'99'";
		
							$del_list_state_gmt_SQL .= "or (List_id=\"$cur_list_id\" and state='$Gstate_call_time_state' and gmt_offset_now NOT IN($state_gmt)) ";
							$list_state_gmt_SQL .= "or (List_id=\"$cur_list_id\" and state='$Gstate_call_time_state' and gmt_offset_now IN($state_gmt)) ";
							}

						$b++;
						}
					if (strlen($ct_states)>2)
						{
						$ct_states = preg_replace("/,$/i",'',$ct_states);
						$ct_statesSQL = "and state NOT IN($ct_states)";
						}
					else
						{
						$ct_statesSQL = "";
						}

					$r=0;
					$dgA=0;
					$list_default_gmt='';
					while($r < $g)
						{
						if ($DB > 0) 
							{$dialable_output .= "LCT_gmt: $r|$GMT_day[$r]|$GMT_gmt[$r]|$Gct_sunday_start|$Gct_sunday_stop|$GMT_hour[$r]|$Gct_default_start|$Gct_default_stop\n";}

						if ($GMT_day[$r]==0)	#### Sunday local time
							{
							if (($Gct_sunday_start==0) && ($Gct_sunday_stop==0))
								{
								if ( ($GMT_hour[$r]>=$Gct_default_start) && ($GMT_hour[$r]<$Gct_default_stop) )
									{}
								else
									{$list_default_gmt.="'$GMT_gmt[$r]',";}
								}
							else
								{
								if ( ($GMT_hour[$r]>=$Gct_sunday_start) && ($GMT_hour[$r]<$Gct_sunday_stop) )
									{}
								else
									{$list_default_gmt.="'$GMT_gmt[$r]',";}
								}
							}
						if ($GMT_day[$r]==1)	#### Monday local time
							{
							if (($Gct_monday_start==0) && ($Gct_monday_stop==0))
								{
								if ( ($GMT_hour[$r]>=$Gct_default_start) && ($GMT_hour[$r]<$Gct_default_stop) )
									{}
								else
									{$list_default_gmt.="'$GMT_gmt[$r]',";}
								}
							else
								{
								if ( ($GMT_hour[$r]>=$Gct_monday_start) && ($GMT_hour[$r]<$Gct_monday_stop) )
									{}
								else
									{$list_default_gmt.="'$GMT_gmt[$r]',";}
								}
							}
						if ($GMT_day[$r]==2)	#### Tuesday local time
							{
							if (($Gct_tuesday_start==0) && ($Gct_tuesday_stop==0))
								{
								if ( ($GMT_hour[$r]>=$Gct_default_start) && ($GMT_hour[$r]<$Gct_default_stop) )
									{}
								else
									{$list_default_gmt.="'$GMT_gmt[$r]',";}
								}
							else
								{
								if ( ($GMT_hour[$r]>=$Gct_tuesday_start) && ($GMT_hour[$r]<$Gct_tuesday_stop) )
									{}
								else
									{$list_default_gmt.="'$GMT_gmt[$r]',";}
								}
							}
						if ($GMT_day[$r]==3)	#### Wednesday local time
							{
							if (($Gct_wednesday_start==0) && ($Gct_wednesday_stop==0))
								{
								if ( ($GMT_hour[$r]>=$Gct_default_start) && ($GMT_hour[$r]<$Gct_default_stop) )
									{}
								else
									{$list_default_gmt.="'$GMT_gmt[$r]',";}
								}
							else
								{
								if ( ($GMT_hour[$r]>=$Gct_wednesday_start) && ($GMT_hour[$r]<$Gct_wednesday_stop) )
									{}
								else
									{$list_default_gmt.="'$GMT_gmt[$r]',";}
								}
							}
						if ($GMT_day[$r]==4)	#### Thursday local time
							{
							if (($Gct_thursday_start==0) && ($Gct_thursday_stop==0))
								{
								if ( ($GMT_hour[$r]>=$Gct_default_start) && ($GMT_hour[$r]<$Gct_default_stop) )
									{}
								else
									{$list_default_gmt.="'$GMT_gmt[$r]',";}
								}
							else
								{
								if ( ($GMT_hour[$r]>=$Gct_thursday_start) && ($GMT_hour[$r]<$Gct_thursday_stop) )
									{}
								else
									{$list_default_gmt.="'$GMT_gmt[$r]',";}
								}
							}
						if ($GMT_day[$r]==5)	#### Friday local time
							{
							if (($Gct_friday_start==0) && ($Gct_friday_stop==0))
								{
								if ( ($GMT_hour[$r]>=$Gct_default_start) && ($GMT_hour[$r]<$Gct_default_stop) )
									{}
								else
									{$list_default_gmt.="'$GMT_gmt[$r]',";}
								}
							else
								{
								if ( ($GMT_hour[$r]>=$Gct_friday_start) && ($GMT_hour[$r]<$Gct_friday_stop) )
									{}
								else
									{$list_default_gmt.="'$GMT_gmt[$r]',";}
								}
							}
						if ($GMT_day[$r]==6)	#### Saturday local time
							{
							if (($Gct_saturday_start==0) && ($Gct_saturday_stop==0))
								{
								if ( ($GMT_hour[$r]>=$Gct_default_start) && ($GMT_hour[$r]<$Gct_default_stop) )
									{}
								else
									{$list_default_gmt.="'$GMT_gmt[$r]',";}
								}
							else
								{
								if ( ($GMT_hour[$r]>=$Gct_saturday_start) && ($GMT_hour[$r]<$Gct_saturday_stop) )
									{}
								else
									{$list_default_gmt.="'$GMT_gmt[$r]',";}
								}
							}
						$r++;
						}

					$list_default_gmt = "$list_default_gmt'99'";
					$LCTlist_id_sql .= " or ((list_id='$cur_list_id' and gmt_offset_now NOT IN($list_default_gmt) $ct_statesSQL) $list_state_gmt_SQL)";
					}
				##### END local call time for list set different than campaign #####

				else
					{
					if (strlen($list_id_sql) < 3) 
						{
						$list_id_sql = "(list_id IN('$cur_list_id'";
						}
					else 
						{
						$list_id_sql .= ",'$cur_list_id'";
						}
					}

				$k++;
				}
			$camp_lists = preg_replace("/.$/i","",$camp_lists);
			if (strlen($camp_lists) < 4) {$camp_lists="''";}
			if (strlen($list_id_sql) < 4) {$list_id_sql="list_id=''";}
			else {$list_id_sql .= '))';}
			if (strlen($LCTlist_id_sql) > 1) {$list_id_sql .= "$LCTlist_id_sql";}


			$stmt="SELECT count(*) FROM vicidial_list where called_since_last_reset='N' and status IN($Dsql) and list_id IN($camp_lists) and ($list_id_sql) and ($all_gmtSQL) $CCLsql $DLTsql $fSQL $EXPsql";
			$full_dialable_SQL="called_since_last_reset='N' and status IN($Dsql) and list_id IN($camp_lists) and ($list_id_sql) and ($all_gmtSQL) $CCLsql $DLTsql $fSQL $EXPsql";
			#$DB=1;
			if ($DB) {$dialable_output .= "$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$rslt_rows = mysqli_num_rows($rslt);
			if ($rslt_rows)
				{
				$rowx=mysqli_fetch_row($rslt);
				$active_leads = "$rowx[0]";
				}
			else {$active_leads = '0';}

			if ($DB > 0) {$dialable_output .= "|$DB|\n";}
			if ( ($single_status > 0) or ($only_return > 0) )
				{return $active_leads;}
			else
				{$dialable_output .= _QXZ("This campaign has")." $active_leads "._QXZ("leads to be dialed in those lists")."\n";}
			}
		else
			{
			$dialable_output .= _QXZ("no dial statuses selected for this campaign")."\n";
			}
		}
	else
		{
		$dialable_output .= _QXZ("no active lists selected for this campaign")."\n";
		}
	}
else
	{
	$dialable_output .= _QXZ("no active lists selected for this campaign")."\n";
	}

if ($only_return < 1)
	{
	echo $dialable_output;
	}

##### END calculate what gmt_offset_now values are within the allowed local_call_time setting ###
}
?>
