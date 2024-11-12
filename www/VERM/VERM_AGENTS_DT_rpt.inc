<?php
# VERM_AGENTS_DT_rpt.inc - Vicidial Enhanced Reporting agent detail report
#
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGELOG:
# 220825-1632 - First build
# 230106-1301 - Added auto-download for large page displays to avoid memory errors
# 240422-1750 - Added agent dead time section
#

# NANQUE, not an unanswered call?
# TIMEOT, unanswered even though it was dropped into a call that was picked up
# Warm transfers count as two calls

# $PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
# $PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
# $PHP_SELF=$_SERVER['PHP_SELF'];
# $PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);

$vicidial_agent_log_table="vicidial_agent_log";

# if (isset($_GET["SUBMIT"]))			{$SUBMIT=$_GET["SUBMIT"];}
# 	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}

if (isset($_GET["sort_agent_sessions_detail"]))			{$sort_agent_sessions_detail=$_GET["sort_agent_sessions_detail"];}
	elseif (isset($_POST["sort_agent_sessions_detail"]))	{$sort_agent_sessions_detail=$_POST["sort_agent_sessions_detail"];}
if (isset($_GET["sort_agent_pause_detail"]))			{$sort_agent_pause_detail=$_GET["sort_agent_pause_detail"];}
	elseif (isset($_POST["sort_agent_pause_detail"]))	{$sort_agent_pause_detail=$_POST["sort_agent_pause_detail"];}
if (isset($_GET["sort_agent_dead_detail"]))			{$sort_agent_dead_detail=$_GET["sort_agent_dead_detail"];}
	elseif (isset($_POST["sort_agent_dead_detail"]))	{$sort_agent_dead_detail=$_POST["sort_agent_dead_detail"];}

# No campaigns, need to query for all.
$vicidial_user_log_SQL=preg_replace('/and campaign_id in \(\'\'\)/', '', $vicidial_user_log_SQL);
$vicidial_agent_log_SQL=preg_replace('/and campaign_id in \(\'\'\)/', '', $vicidial_agent_log_SQL);

if ($non_latin < 1)
	{
	$sort_agent_sessions_detail = preg_replace('/[^\s\-_0-9a-zA-Z]/','',$sort_agent_sessions_detail);
	$sort_agent_pause_detail = preg_replace('/[^\s\-_0-9a-zA-Z]/','',$sort_agent_pause_detail);
	$sort_agent_dead_detail = preg_replace('/[^\s\-_0-9a-zA-Z]/','',$sort_agent_dead_detail);
	}
else
	{
	$sort_agent_sessions_detail = preg_replace('/[^\s\-_0-9\p{L}]/u','',$sort_agent_sessions_detail);
	$sort_agent_pause_detail = preg_replace('/[^\s\-_0-9\p{L}]/u','',$sort_agent_pause_detail);
	$sort_agent_dead_detail = preg_replace('/[^\s\-_0-9\p{L}]/u','',$sort_agent_dead_detail);
	}

if (preg_match('/ desc$/', $sort_agent_pause_detail)) 
	{
	$sort_char="&#8595;"; 
	$reverse_link=preg_replace('/ desc$/', '', $sort_agent_pause_detail);
	} 
else 
	{
	$sort_char="&#8593;"; 
	$reverse_link=$sort_agent_pause_detail." desc";
	}
$sort_agent_pause_detail_preg=preg_replace('/ desc$/', '', $sort_agent_pause_detail);

if (preg_match('/ desc$/', $sort_agent_dead_detail)) 
	{
	$sort_char="&#8595;"; 
	$reverse_dead_link=preg_replace('/ desc$/', '', $sort_agent_dead_detail);
	} 
else 
	{
	$sort_char="&#8593;"; 
	$reverse_dead_link=$sort_agent_dead_detail." desc";
	}
$sort_agent_dead_detail_preg=preg_replace('/ desc$/', '', $sort_agent_dead_detail);

/*
$start_date="2021-12-20";
$start_time="00:00:00";
$end_date="2021-12-20";
$end_time="23:59:59";
*/

$agent_sessions_array=array();

$session_stmt="select *,if(event='LOGOUT' or event='TIMEOUTLOGOUT', 1, 0) as priority from vicidial_user_log $vicidial_user_log_SQL and event in ('LOGIN', 'LOGOUT', 'TIMEOUTLOGOUT') order by user, event_date, priority asc";
if ($DB) {$HTML_output.="<B>$session_stmt</B>";}
# print "$session_stmt<BR>\n";
$session_rslt=mysql_to_mysqli($session_stmt, $link);

#  WHILE loop constants
$agent_logged_in=0;
$interval_start_date="";
$interval_end_date="";
$prev_user="";
$prev_full_name="";
$prev_date="";
$TODAY=date("Y-m-d");
$NOW_TIME=date("H:i:s");

while ($session_row=mysqli_fetch_array($session_rslt))
	{
	$full_name=$fullname_info["$session_row[user]"];
	$user=$session_row["user"];
	$extension=$session_row["extension"];
	$event=$session_row["event"];
	$notes=$session_row["extension"];
	$event_epoch=$session_row["event_epoch"];
	$event_time=$session_row["event_date"];
	$server_ip=$session_row["server_ip"];
	$event_date=substr($event_time, 0, 10);

	# END date in case agent is still logged in at that time
	if ($prev_date!=$TODAY)
		{
		$prev_date_eod="$prev_date 23:59:59";
		}
	else
		{
		$prev_date_eod="$prev_date $NOW_TIME";
		}

	if ($event_date!=$TODAY)
		{
		$event_date_eod="$event_date 23:59:59";
		}
	else
		{
		$event_date_eod="$event_date $NOW_TIME";
		}

	if (($prev_user!=$user || $event_date!=$prev_date) && $prev_user!="" && $prev_date!="")  
		{
		#
		# Agent is no longer logged in
		if ($agent_logged_in)
			{
			$eod_epoch_stmt="select unix_timestamp('$prev_date_eod')"; # , '$prev_user' as previous_user, '$prev_full_name' as prev_full_name
			$eod_epoch_rslt=mysql_to_mysqli($eod_epoch_stmt, $link);
			$eod_epoch_row=mysqli_fetch_row($eod_epoch_rslt);
			$agent_sessions_array["$prev_user"]["$start_date_epoch"]["end_hour"]=$eod_epoch_row[0];
			$agent_sessions_array["$prev_user"]["$start_date_epoch"]["full_name"]=$prev_full_name;

			# Agent logged in and day is today, they are currently logged in, else EOD LOGOUT
			$notes=($eod_epoch_row[0]>=strtotime(date("Y-m-d 00:00:00")) ? "CURRENTLY LOGGED IN" : "EOD LOGOUT");
			$agent_sessions_array["$prev_user"]["$start_date_epoch"]["notes"]=$notes;

			$agent_logged_in=0;
			}
		}


	if ($event=="LOGIN")
		{
		if ($agent_logged_in==0)
			{
			# $start_date_epoch=$event_epoch;
			$start_date_epoch=$event_time;
			$agent_sessions_array["$user"]["$start_date_epoch"]["full_name"]=$full_name;
			$agent_sessions_array["$user"]["$start_date_epoch"]["start_hour"]=$event_epoch;
			$agent_sessions_array["$user"]["$start_date_epoch"]["extension"]=$extension;
			$agent_sessions_array["$user"]["$start_date_epoch"]["server_ip"]=$server_ip;
			}
		else 
			{
			"$session_row[user] is already logged in at $start_date_epoch<BR>\n";
			}
		$agent_logged_in=1;
		}

	if (preg_match('/LOGOUT/', $event)) # && $agent_logged_in==1
		{
		$agent_sessions_array["$user"]["$start_date_epoch"]["end_hour"]=$event_epoch;
		$agent_sessions_array["$user"]["$start_date_epoch"]["duration"]=($agent_sessions_array["$user"]["$start_date_epoch"]["end_hour"]-$agent_sessions_array["$user"]["$start_date_epoch"]["start_hour"]);
		$agent_sessions_array["$user"]["$start_date_epoch"]["notes"]=$notes;

/* Removed 9/20 - leaving in just in case
		$pause_stmt="select sec_to_time(sum(pause_sec)), count(*) from vicidial_agent_log where event_time>=from_unixtime(".$agent_sessions_array["$user"]["$start_date_epoch"]["start_hour"].") and event_time<=from_unixtime(".$agent_sessions_array["$user"]["$start_date_epoch"]["end_hour"].") and user='$user' and pause_sec>0";
		# print $pause_stmt."<BR>\n";
		$pause_rslt=mysql_to_mysqli($pause_stmt, $link);
		$pause_row=mysqli_fetch_row($pause_rslt);
		$agent_sessions_array["$user"]["$start_date_epoch"]["pauses"]=$pause_row[1];
		$agent_sessions_array["$user"]["$start_date_epoch"]["pause_time"]=($pause_row[0]!="" ? $pause_row[0] : "00:00:00");		
*/
		if ($agent_sessions_array["$user"]["$start_date_epoch"]["start_hour"]) # 9/8/22 added to prevent warnings
			{ 
			$pause_stmt="select sec_to_time(sum(pause_sec)), count(*) from vicidial_agent_log where event_time>=from_unixtime(".$agent_sessions_array["$user"]["$start_date_epoch"]["start_hour"].") and event_time<=from_unixtime(".$agent_sessions_array["$user"]["$start_date_epoch"]["end_hour"].") and user='$user' and pause_sec>0";
			# print $pause_stmt."<BR>\n";
			$pause_rslt=mysql_to_mysqli($pause_stmt, $link);
			
			if (mysqli_num_rows($pause_rslt)>0) # 9/8/22 added to prevent warnings
				{
				$pause_row=mysqli_fetch_row($pause_rslt);
				$agent_sessions_array["$user"]["$start_date_epoch"]["pauses"]=$pause_row[1];
				$agent_sessions_array["$user"]["$start_date_epoch"]["pause_time"]=($pause_row[0]!="" ? $pause_row[0] : "00:00:00");		
				}
			else
				{
				$agent_sessions_array["$user"]["$start_date_epoch"]["pauses"]=0;
				$agent_sessions_array["$user"]["$start_date_epoch"]["pause_time"]="00:00:00";		
				}
			}
		else
			{
			$agent_sessions_array["$user"]["$start_date_epoch"]["pauses"]=0;
			$agent_sessions_array["$user"]["$start_date_epoch"]["pause_time"]="00:00:00";		
			}
		$start_date_epoch=0;
		$agent_logged_in=0;
		}
	
	$prev_user=$user;
	$prev_full_name=$full_name;
	$prev_date=$event_date;
	$prev_event_epoch=$event_epoch;
	}

if($agent_logged_in)
	{
	$agent_sessions_array["$user"]["$start_date_epoch"]["end_hour"]=$event_epoch;
	$agent_sessions_array["$user"]["$start_date_epoch"]["duration"]=($agent_sessions_array["$user"]["$start_date_epoch"]["end_hour"]-$agent_sessions_array["$user"]["$start_date_epoch"]["start_hour"]);
	$agent_sessions_array["$user"]["$start_date_epoch"]["notes"]=$notes;


	$pause_stmt="select sec_to_time(sum(pause_sec)), count(*) from vicidial_agent_log where event_time>=from_unixtime(".$agent_sessions_array["$user"]["$start_date_epoch"]["start_hour"].") and event_time<=from_unixtime(".$agent_sessions_array["$user"]["$start_date_epoch"]["end_hour"].") and user='$user' and pause_sec>0";
	# print $pause_stmt."<BR>\n";
	$pause_rslt=mysql_to_mysqli($pause_stmt, $link);
	$pause_row=mysqli_fetch_row($pause_rslt);
	$agent_sessions_array["$user"]["$start_date_epoch"]["pauses"]=$pause_row[1];
	if ($pause_row[1]==0) {$pause_row[0]=0;}
	$agent_sessions_array["$user"]["$start_date_epoch"]["pause_time"]=$pause_row[0];		

	}
ksort($agent_sessions_array, SORT_NATURAL | SORT_FLAG_CASE);
# print_r($agent_sessions_array); die;

####### AGENT SESSION DETAIL ##########
$HTML_output.="<BR><h2 class='rpt_header' id='sasd_anchor'>"._QXZ("Detail of agent sessions").": $NWB#VERM_AGENTS_DT-detail_of_agent_sessions$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";

$sort_asd_index=preg_replace('/ desc/', '', $sort_agent_sessions_detail);
if (preg_match('/ desc$/', $sort_agent_sessions_detail)) 
	{
	$sort_char="&#8595;"; 
	$reverse_link=preg_replace('/ desc$/', '', $sort_agent_sessions_detail);
	} 
else 
	{
	$sort_char="&#8593;"; 
	$reverse_link=$sort_agent_sessions_detail." desc";
	}
$sort_agent_sessions_detail_preg=preg_replace('/ desc$/', '', $sort_agent_sessions_detail);

$HTML_output.="<tr class='standard_font bold'>";
/*
$HTML_output.="<th><a class='header_link' name='agent_sessions_sort_agent' id='agent_sessions_sort_agent' href='".$PHP_SELF."?sort_agent_pause_detail=".$sort_agent_pause_detail."&sort_agent_sessions_detail=".("full_name"==$sort_agent_sessions_detail_preg ? "$reverse_link" : "full_name")."#agent_sessions_sort_agent'>Agent".("full_name"==$sort_agent_sessions_detail_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='agent_sessions_sort_start_hour' id='agent_sessions_sort_start_hour' href='".$PHP_SELF."?sort_agent_pause_detail=".$sort_agent_pause_detail."&sort_agent_sessions_detail=".("start_hour"==$sort_agent_sessions_detail_preg ? "$reverse_link" : "start_hour")."#agent_sessions_sort_start_hour'>Start hour".("start_hour"==$sort_agent_sessions_detail_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='agent_sessions_sort_end_hour' id='agent_sessions_sort_end_hour' href='".$PHP_SELF."?sort_agent_pause_detail=".$sort_agent_pause_detail."&sort_agent_sessions_detail=".("end_hour"==$sort_agent_sessions_detail_preg ? "$reverse_link" : "end_hour")."#agent_sessions_sort_end_hour'>End hour".("end_hour"==$sort_agent_sessions_detail_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='agent_sessions_sort_duration' id='agent_sessions_sort_duration' href='".$PHP_SELF."?sort_agent_pause_detail=".$sort_agent_pause_detail."&sort_agent_sessions_detail=".("duration"==$sort_agent_sessions_detail_preg ? "$reverse_link" : "duration")."#agent_sessions_sort_duration'>Duration".("duration"==$sort_agent_sessions_detail_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='agent_sessions_sort_extension' id='agent_sessions_sort_extension' href='".$PHP_SELF."?sort_agent_pause_detail=".$sort_agent_pause_detail."&sort_agent_sessions_detail=".("extension"==$sort_agent_sessions_detail_preg ? "$reverse_link" : "extension")."#agent_sessions_sort_extension'>Ext.".("extension"==$sort_agent_sessions_detail_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='agent_sessions_sort_notes' id='agent_sessions_sort_notes' href='".$PHP_SELF."?sort_agent_pause_detail=".$sort_agent_pause_detail."&sort_agent_sessions_detail=".("notes"==$sort_agent_sessions_detail_preg ? "$reverse_link" : "notes")."#agent_sessions_sort_notes'>Termination".("notes"==$sort_agent_sessions_detail_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='agent_sessions_sort_pauses' id='agent_sessions_sort_pauses' href='".$PHP_SELF."?sort_agent_pause_detail=".$sort_agent_pause_detail."&sort_agent_sessions_detail=".("pauses"==$sort_agent_sessions_detail_preg ? "$reverse_link" : "pauses")."#agent_sessions_sort_pauses'>Pause".("pauses"==$sort_agent_sessions_detail_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='agent_sessions_sort_pause_time' id='agent_sessions_sort_pause_time' href='".$PHP_SELF."?sort_agent_pause_detail=".$sort_agent_pause_detail."&sort_agent_sessions_detail=".("pause_time"==$sort_agent_sessions_detail_preg ? "$reverse_link" : "pause_time")."#agent_sessions_sort_pause_time'>P. Time".("pause_time"==$sort_agent_sessions_detail_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='agent_sessions_sort_server_ip' id='agent_sessions_sort_server_ip' href='".$PHP_SELF."?sort_agent_pause_detail=".$sort_agent_pause_detail."&sort_agent_sessions_detail=".("server_ip"==$sort_agent_sessions_detail_preg ? "$reverse_link" : "server_ip")."#agent_sessions_sort_server_ip'>Srv".("server_ip"==$sort_agent_sessions_detail_preg ? " $sort_char" : "")."</a></th>";
*/

	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Agent").("full_name"==$sort_agent_sessions_detail_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_agent_sessions_detail').value='".("full_name"==$sort_agent_sessions_detail_preg ? "$reverse_link" : "full_name")."'; this.form.action+='#sasd_anchor'; this.form.submit()\"></th>\n";
	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Start hour").("start_hour"==$sort_agent_sessions_detail_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_agent_sessions_detail').value='".("start_hour"==$sort_agent_sessions_detail_preg ? "$reverse_link" : "start_hour")."'; this.form.action+='#sasd_anchor'; this.form.submit()\"></th>\n";
	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("End hour").("end_hour"==$sort_agent_sessions_detail_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_agent_sessions_detail').value='".("end_hour"==$sort_agent_sessions_detail_preg ? "$reverse_link" : "end_hour")."'; this.form.action+='#sasd_anchor'; this.form.submit()\"></th>\n";
	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Duration").("duration"==$sort_agent_sessions_detail_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_agent_sessions_detail').value='".("duration"==$sort_agent_sessions_detail_preg ? "$reverse_link" : "duration")."'; this.form.action+='#sasd_anchor'; this.form.submit()\"></th>\n";
	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Ext.").("extension"==$sort_agent_sessions_detail_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_agent_sessions_detail').value='".("extension"==$sort_agent_sessions_detail_preg ? "$reverse_link" : "extension")."'; this.form.action+='#sasd_anchor'; this.form.submit()\"></th>\n";
	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Termination").("notes"==$sort_agent_sessions_detail_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_agent_sessions_detail').value='".("notes"==$sort_agent_sessions_detail_preg ? "$reverse_link" : "notes")."'; this.form.action+='#sasd_anchor'; this.form.submit()\"></th>\n";
	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Pause").("pauses"==$sort_agent_sessions_detail_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_agent_sessions_detail').value='".("pauses"==$sort_agent_sessions_detail_preg ? "$reverse_link" : "pauses")."'; this.form.action+='#sasd_anchor'; this.form.submit()\"></th>\n";
	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("P. Time").("pause_time"==$sort_agent_sessions_detail_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_agent_sessions_detail').value='".("pause_time"==$sort_agent_sessions_detail_preg ? "$reverse_link" : "pause_time")."'; this.form.action+='#sasd_anchor'; this.form.submit()\"></th>\n";
	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Srv").("server_ip"==$sort_agent_sessions_detail_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_agent_sessions_detail').value='".("server_ip"==$sort_agent_sessions_detail_preg ? "$reverse_link" : "server_ip")."'; this.form.action+='#sasd_anchor'; this.form.submit()\"></th>\n";

$HTML_output.="</tr>\n";

$CSV_output["agent_sessions"]="\""._QXZ("Agent")."\",\""._QXZ("Start hour")."\",\""._QXZ("End hour")."\",\""._QXZ("Duration")."\",\""._QXZ("Ext.")."\",\""._QXZ("Termination")."\",\""._QXZ("Pause")."\",\""._QXZ("P.Time")."\",\""._QXZ("Srv")."\"\n";
$CSV_output["auto_download"]="\"WARNING:\",\"OVER $auto_download_limit RECORDS\",\"AUTOMATIC DOWNLOAD\"\n\n\n";
$CSV_output["auto_download"]="\""._QXZ("Agent")."\",\""._QXZ("Start hour")."\",\""._QXZ("End hour")."\",\""._QXZ("Duration")."\",\""._QXZ("Ext.")."\",\""._QXZ("Termination")."\",\""._QXZ("Pause")."\",\""._QXZ("P.Time")."\",\""._QXZ("Srv")."\"\n";

## Compress multi-multi-dimensional array into multi-dimensional array
$agent_sessions_final_array=array();
$i=0;
foreach ($agent_sessions_array as $user => $sessions)
	{
	foreach ($sessions as $login_start_date => $session_data)
		{
		$pauses=$session_data["pauses"];
		$pause_time=$session_data["pause_time"];
		$server_ip=$session_data["server_ip"];
		# $agentName=$session_data["full_name"];
		$agentName=($show_full_agent_info ? $user." - " : "").$session_data["full_name"];


		$time_stmt="select from_unixtime(".$session_data["start_hour"]."), from_unixtime(".$session_data["end_hour"]."), sec_to_time(".($session_data["end_hour"]-$session_data["start_hour"]).")";
		# print $time_stmt."<BR>\n";
		$time_rslt=mysql_to_mysqli($time_stmt, $link);
		$time_row=mysqli_fetch_row($time_rslt);
		$start_hour=$time_row[0];
		$end_hour=$time_row[1];
		$duration=$time_row[2];

		$agent_sessions_final_array[$i]["full_name"]=$agentName;
		$agent_sessions_final_array[$i]["start_hour"]=$start_hour;
		$agent_sessions_final_array[$i]["end_hour"]=$end_hour;
		$agent_sessions_final_array[$i]["duration"]=$duration;
		$agent_sessions_final_array[$i]["extension"]=$session_data["extension"];
		$agent_sessions_final_array[$i]["notes"]=$session_data["notes"];
		$agent_sessions_final_array[$i]["pauses"]=$pauses;
		$agent_sessions_final_array[$i]["pause_time"]=$pause_time;
		$agent_sessions_final_array[$i]["server_ip"]=$server_ip;
		$i++;
		}
	}
/*
*/

if ($sort_asd_index)
	{
#	print_r($agent_sessions_final_array);

	$col = array_column( $agent_sessions_final_array, "$sort_asd_index" );
	if (preg_match('/ desc$/', $sort_agent_sessions_detail))
		{
		array_multisort( $col, SORT_DESC, SORT_STRING, $agent_sessions_final_array );
		}
	else
		{
		array_multisort( $col, SORT_ASC, SORT_STRING, $agent_sessions_final_array );
		}

#	print_r($agent_sessions_final_array);

	}

foreach ($agent_sessions_final_array as $i => $row)
	{
	$HTML_output.="<tr class='standard_font'>";
	$HTML_output.="<td>".$row["full_name"]."</td>";
	$HTML_output.="<td>".$row["start_hour"]."</td>";
	$HTML_output.="<td>".$row["end_hour"]."</td>";
	$HTML_output.="<td>".$row["duration"]."</td>";
	$HTML_output.="<td>".$row["extension"]."</td>";
	$HTML_output.="<td>".$row["notes"]."</td>";
	$HTML_output.="<td>".$row["pauses"]."</td>";
	$HTML_output.="<td>".$row["pause_time"]."</td>";
	$HTML_output.="<td>".$row["server_ip"]."</td>";
	$HTML_output.="</tr>\n";

	$CSV_output["agent_sessions"].="\"".$row["full_name"]."\",\"".$row["start_hour"]."\",\"".$row["end_hour"]."\",\"".$row["duration"]."\",\"".$row["extension"]."\",\"".$row["notes"]."\",\"".$row["pauses"]."\",\"".$row["pause_time"]."\",\"".$row["server_ip"]."\"\n";
	$CSV_output["auto_download"].="\"".$row["full_name"]."\",\"".$row["start_hour"]."\",\"".$row["end_hour"]."\",\"".$row["duration"]."\",\"".$row["extension"]."\",\"".$row["notes"]."\",\"".$row["pauses"]."\",\"".$row["pause_time"]."\",\"".$row["server_ip"]."\"\n";
	}

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td colspan='9' class='export_row_cell'>"._QXZ("Export as")."...<a href=\"".$PHP_SELF."?download_rpt=agent_sessions&sort_agent_pause_detail=".$sort_agent_pause_detail."&sort_agent_sessions_detail=".$sort_agent_sessions_detail."\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='9' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('AGENTS_DT', 'agent_sessions')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>\n";
$HTML_output.="<input type='hidden' name='sort_agent_sessions_detail' id='sort_agent_sessions_detail' value='$sort_agent_sessions_detail'>";
##################################### 


####### AGENT PAUSE DETAIL ##########
$sort_apd_index=preg_replace('/ desc/', '', $sort_agent_pause_detail);
if (preg_match('/ desc$/', $sort_agent_pause_detail)) 
	{
	$sort_char="&#8595;"; 
	$reverse_link=preg_replace('/ desc$/', '', $sort_agent_pause_detail);
	} 
else 
	{
	$sort_char="&#8593;"; 
	$reverse_link=$sort_agent_pause_detail." desc";
	}
$sort_agent_pause_detail_preg=preg_replace('/ desc$/', '', $sort_agent_pause_detail);


$HTML_output.="<BR><h2 class='rpt_header' id='sapd_anchor'>"._QXZ("Detail of agent pauses").": $NWB#VERM_AGENTS_DT-detail_of_agent_pauses$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
/*
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>Agent</th>";
$HTML_output.="<th>Ext.</th>";
$HTML_output.="<th>Code</th>";
$HTML_output.="<th>Activity</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="<th>Billable?</th>";
$HTML_output.="<th>Start hour</th>";
$HTML_output.="<th>End hour</th>";
$HTML_output.="<th>Duration</th>";
$HTML_output.="</tr>";
*/

$HTML_output.="<tr class='standard_font bold'>";
/*
$HTML_output.="<th><a class='header_link' name='agent_pause_sort_agent' id='agent_pause_sort_agent' href='".$PHP_SELF."?sort_agent_sessions_detail=".$sort_agent_sessions_detail."&sort_agent_pause_detail=".("user"==$sort_agent_pause_detail_preg ? "$reverse_link" : "user")."#agent_pause_sort_agent'>Agent".("user"==$sort_agent_pause_detail_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='agent_pause_sort_extension' id='agent_pause_sort_extension' href='".$PHP_SELF."?sort_agent_sessions_detail=".$sort_agent_sessions_detail."&sort_agent_pause_detail=".("extension"==$sort_agent_pause_detail_preg ? "$reverse_link" : "extension")."#agent_pause_sort_extension'>Ext.".("extension"==$sort_agent_pause_detail_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='agent_pause_sort_pause_code' id='agent_pause_sort_pause_code' href='".$PHP_SELF."?sort_agent_sessions_detail=".$sort_agent_sessions_detail."&sort_agent_pause_detail=".("pause_code"==$sort_agent_pause_detail_preg ? "$reverse_link" : "pause_code")."#agent_pause_sort_pause_code'>Code".("pause_code"==$sort_agent_pause_detail_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='agent_pause_sort_pause_code_name' id='agent_pause_sort_pause_code_name' href='".$PHP_SELF."?sort_agent_sessions_detail=".$sort_agent_sessions_detail."&sort_agent_pause_detail=".("pause_code_name"==$sort_agent_pause_detail_preg ? "$reverse_link" : "pause_code_name")."#agent_pause_sort_pause_code_name'>Activity".("pause_code_name"==$sort_agent_pause_detail_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='agent_pause_sort_billable_code' id='agent_pause_sort_billable_code' href='".$PHP_SELF."?sort_agent_sessions_detail=".$sort_agent_sessions_detail."&sort_agent_pause_detail=".("billable_code"==$sort_agent_pause_detail_preg ? "$reverse_link" : "billable_code")."#agent_pause_sort_billable_code'>...".("billable_code"==$sort_agent_pause_detail_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='agent_pause_sort_is_billable' id='agent_pause_sort_is_billable' href='".$PHP_SELF."?sort_agent_sessions_detail=".$sort_agent_sessions_detail."&sort_agent_pause_detail=".("is_billable"==$sort_agent_pause_detail_preg ? "$reverse_link" : "is_billable")."#agent_pause_sort_is_billable'>Billable?".("is_billable"==$sort_agent_pause_detail_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='agent_pause_sort_start_hour' id='agent_pause_sort_start_hour' href='".$PHP_SELF."?sort_agent_sessions_detail=".$sort_agent_sessions_detail."&sort_agent_pause_detail=".("start_hour"==$sort_agent_pause_detail_preg ? "$reverse_link" : "start_hour")."#agent_pause_sort_start_hour'>Start hour".("start_hour"==$sort_agent_pause_detail_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='agent_pause_sort_end_hour' id='agent_pause_sort_end_hour' href='".$PHP_SELF."?sort_agent_sessions_detail=".$sort_agent_sessions_detail."&sort_agent_pause_detail=".("end_hour"==$sort_agent_pause_detail_preg ? "$reverse_link" : "end_hour")."#agent_pause_sort_end_hour'>End hour".("end_hour"==$sort_agent_pause_detail_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='agent_pause_sort_duration' id='agent_pause_sort_duration' href='".$PHP_SELF."?sort_agent_sessions_detail=".$sort_agent_sessions_detail."&sort_agent_pause_detail=".("duration"==$sort_agent_pause_detail_preg ? "$reverse_link" : "duration")."#agent_pause_sort_duration'>Duration".("duration"==$sort_agent_pause_detail_preg ? " $sort_char" : "")."</a></th>";
*/
		$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Agent").("user"==$sort_agent_pause_detail_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_agent_pause_detail').value='".("user"==$sort_agent_pause_detail_preg ? "$reverse_link" : "user")."'; this.form.action+='#sapd_anchor'; this.form.submit()\"></th>\n";
		$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Ext.").("extension"==$sort_agent_pause_detail_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_agent_pause_detail').value='".("extension"==$sort_agent_pause_detail_preg ? "$reverse_link" : "extension")."'; this.form.action+='#sapd_anchor'; this.form.submit()\"></th>\n";
		$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Code").("pause_code"==$sort_agent_pause_detail_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_agent_pause_detail').value='".("pause_code"==$sort_agent_pause_detail_preg ? "$reverse_link" : "pause_code")."'; this.form.action+='#sapd_anchor'; this.form.submit()\"></th>\n";
		$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Activity").("pause_code_name"==$sort_agent_pause_detail_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_agent_pause_detail').value='".("pause_code_name"==$sort_agent_pause_detail_preg ? "$reverse_link" : "pause_code_name")."'; this.form.action+='#sapd_anchor'; this.form.submit()\"></th>\n";
		$HTML_output.="<th><input type='button' class='sort_button' value='...".("billable_code"==$sort_agent_pause_detail_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_agent_pause_detail').value='".("billable_code"==$sort_agent_pause_detail_preg ? "$reverse_link" : "billable_code")."'; this.form.action+='#sapd_anchor'; this.form.submit()\"></th>\n";
		$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Billable?").("is_billable"==$sort_agent_pause_detail_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_agent_pause_detail').value='".("is_billable"==$sort_agent_pause_detail_preg ? "$reverse_link" : "is_billable")."'; this.form.action+='#sapd_anchor'; this.form.submit()\"></th>\n";
		$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Start hour").("start_hour"==$sort_agent_pause_detail_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_agent_pause_detail').value='".("start_hour"==$sort_agent_pause_detail_preg ? "$reverse_link" : "start_hour")."'; this.form.action+='#sapd_anchor'; this.form.submit()\"></th>\n";
		$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("End hour").("end_hour"==$sort_agent_pause_detail_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_agent_pause_detail').value='".("end_hour"==$sort_agent_pause_detail_preg ? "$reverse_link" : "end_hour")."'; this.form.action+='#sapd_anchor'; this.form.submit()\"></th>\n";
		$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Duration").("duration"==$sort_agent_pause_detail_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_agent_pause_detail').value='".("duration"==$sort_agent_pause_detail_preg ? "$reverse_link" : "duration")."'; this.form.action+='#sapd_anchor'; this.form.submit()\"></th>\n";

$HTML_output.="</tr>\n";

$CSV_output["agent_pause_details"]="\""._QXZ("Agent")."\",\""._QXZ("Ext.")."\",\""._QXZ("Code")."\",\""._QXZ("Activity")."\",\"...\",\""._QXZ("Billable?")."\",\""._QXZ("Start hour")."\",\""._QXZ("End hour")."\",\""._QXZ("Duration")."\"\n";
$CSV_output["auto_download"].="\n\n\""._QXZ("Agent")."\",\""._QXZ("Ext.")."\",\""._QXZ("Code")."\",\""._QXZ("Activity")."\",\"...\",\""._QXZ("Billable?")."\",\""._QXZ("Start hour")."\",\""._QXZ("End hour")."\",\""._QXZ("Duration")."\"\n";

$agent_pause_stmt="select campaign_id, user, '' as extension, if (sub_status in ('BLANK', '') or sub_status is null, '-', sub_status) as pause_code, event_time, event_time+INTERVAL pause_sec SECOND as end_hour, sec_to_time(pause_sec) as duration from vicidial_agent_log $vicidial_agent_log_SQL and pause_sec>0";
if ($DB) {$HTML_output.="<B>$agent_pause_stmt</B>";}
$agent_pause_rslt=mysql_to_mysqli($agent_pause_stmt, $link);
$agent_pause_array=array(); $i=0;
while ($agent_pause_row=mysqli_fetch_array($agent_pause_rslt))
	{
	$campaign_id=$agent_pause_row["campaign_id"];
	$user=$agent_pause_row["user"];
	$extension=$agent_pause_row["extension"];
	$start_hour=$agent_pause_row["event_time"];
	$end_hour=$agent_pause_row["end_hour"];
	$duration=$agent_pause_row["duration"];
	$pause_code=$agent_pause_row["pause_code"];
	# $agentName=$fullname_info["$user"];
	$agentName=($show_full_agent_info ? $user." - " : "").$fullname_info["$user"];

	$pause_key=$campaign_id."-".$pause_code;
	$pause_code_name=$pause_code_names["$pause_key"];
	if (strlen($pause_code_name)==0) {$pause_code_name="-";}
	
	$is_billable="No"; $bp="N"; $pp="N";
	if (in_array($pause_code, $billable_pause_codes["$campaign_id"]) || in_array($pause_code, $billable_pause_codes["SYSTEM"]))
		{
		$is_billable="Yes"; $bp="";
		}
	if (!in_array($pause_code, $payable_pause_codes["$campaign_id"]) && in_array($pause_code, $payable_pause_codes["SYSTEM"]))
		{
		$pp="";
		}

	$billable_code=$bp."B".$pp."P";

	$agent_pause_array[$i]["user"]=$agentName;
	$agent_pause_array[$i]["extension"]=$extension;
	$agent_pause_array[$i]["pause_code"]=$pause_code;
	$agent_pause_array[$i]["pause_code_name"]=$pause_code_name;
	$agent_pause_array[$i]["billable_code"]=$billable_code;
	$agent_pause_array[$i]["is_billable"]=$is_billable;
	$agent_pause_array[$i]["start_hour"]=$start_hour;
	$agent_pause_array[$i]["end_hour"]=$end_hour;
	$agent_pause_array[$i]["duration"]=$duration;
	$i++;
	}

if ($sort_apd_index)
	{
	# print_r($agent_pause_array);

	$col = array_column( $agent_pause_array, "$sort_apd_index" );
	if (preg_match('/ desc$/', $sort_agent_pause_detail))
		{
		array_multisort( $col, SORT_DESC, SORT_STRING, $agent_pause_array );
		}
	else
		{
		array_multisort( $col, SORT_ASC, SORT_STRING, $agent_pause_array );
		}

	# print_r($agent_pause_array);
	}

$q=0;
foreach ($agent_pause_array as $i => $row)
	{
	$q++;
	$myDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $row["start_hour"]);
	$start_hour_value = $myDateTime->format('m/d - H:i:s');

	$myDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $row["end_hour"]);
	$end_hour_value = $myDateTime->format('m/d - H:i:s');

	$HTML_output.="<tr class='standard_font'>";
	$HTML_output.="<td>".$row["user"]."</td>";
	$HTML_output.="<td>".$row["extension"]."</td>";
	$HTML_output.="<td>".$row["pause_code"]."</td>";
	$HTML_output.="<td>".$row["pause_code_name"]."</td>";
	$HTML_output.="<td>".$row["billable_code"]."</td>";
	$HTML_output.="<td>".$row["is_billable"]."</td>";
	$HTML_output.="<td>".$start_hour_value."</td>";
	$HTML_output.="<td>".$end_hour_value."</td>";
	$HTML_output.="<td>".$row["duration"]."</td>";
	$HTML_output.="</tr>\n";
	
	if (!$download_rpt && $q%100==0) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

	$CSV_output["agent_pause_details"].="\"".$row["user"]."\",\"".$row["extension"]."\",\"".$row["pause_code"]."\",\"".$row["pause_code_name"]."\",\"".$row["billable_code"]."\",\"".$row["is_billable"]."\",\"".$start_hour_value."\",\"".$end_hour_value."\",\"".$row["duration"]."\"\n";
	$CSV_output["auto_download"].="\"".$row["user"]."\",\"".$row["extension"]."\",\"".$row["pause_code"]."\",\"".$row["pause_code_name"]."\",\"".$row["billable_code"]."\",\"".$row["is_billable"]."\",\"".$start_hour_value."\",\"".$end_hour_value."\",\"".$row["duration"]."\"\n";
	}

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td colspan='9' class='export_row_cell'>"._QXZ("Export as")."...<a href=\"".$PHP_SELF."?download_rpt=agent_pause_details&sort_agent_sessions_detail=".$sort_agent_sessions_detail."&sort_agent_pause_detail=".$sort_agent_pause_detail."\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='9' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('AGENTS_DT', 'agent_pause_details')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="<input type='hidden' name='sort_agent_pause_detail' id='sort_agent_pause_detail' value='$sort_agent_pause_detail'>";
#####################################

####### AGENT DEAD DETAIL ##########
$sort_add_index=preg_replace('/ desc/', '', $sort_agent_dead_detail);
if (preg_match('/ desc$/', $sort_agent_dead_detail)) 
	{
	$sort_char="&#8595;"; 
	$reverse_link=preg_replace('/ desc$/', '', $sort_agent_dead_detail);
	} 
else 
	{
	$sort_char="&#8593;"; 
	$reverse_link=$sort_agent_dead_detail." desc";
	}
$sort_agent_dead_detail_preg=preg_replace('/ desc$/', '', $sort_agent_dead_detail);


$HTML_output.="<BR><h2 class='rpt_header' id='sadd_anchor'>"._QXZ("Detail of agent dead time").": </h2>"; # $NWB#VERM_AGENTS_DT-detail_of_agent_dead_time$NWE
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";

$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th><input type='button' class='sort_button' value='Agent".("user"==$sort_agent_dead_detail_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_agent_dead_detail').value='".("user"==$sort_agent_dead_detail_preg ? "$reverse_link" : "user")."'; this.form.action+='#sadd_anchor'; this.form.submit()\"></th>\n";
$HTML_output.="<th><input type='button' class='sort_button' value='Agent ID".("ID"==$sort_agent_dead_detail_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_agent_dead_detail').value='".("ID"==$sort_agent_dead_detail_preg ? "$reverse_link" : "ID")."'; this.form.action+='#sadd_anchor'; this.form.submit()\"></th>\n";
$HTML_output.="<th><input type='button' class='sort_button' value='Total Dead Time".("total_dead_time"==$sort_agent_dead_detail_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_agent_dead_detail').value='".("total_dead_time"==$sort_agent_dead_detail_preg ? "$reverse_link" : "total_dead_time")."'; this.form.action+='#sadd_anchor'; this.form.submit()\"></th>\n";
$HTML_output.="<th><input type='button' class='sort_button' value='Average Dead Time".("average_dead_time"==$sort_agent_dead_detail_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_agent_dead_detail').value='".("average_dead_time"==$sort_agent_dead_detail_preg ? "$reverse_link" : "average_dead_time")."'; this.form.action+='#sadd_anchor'; this.form.submit()\"></th>\n";

$HTML_output.="</tr>\n";


$CSV_output["agent_dead_details"]="\""._QXZ("Agent")."\",\""._QXZ("Agent ID")."\",\""._QXZ("Total Dead Time")."\",\""._QXZ("Average Dead Time")."\"\n";
$CSV_output["auto_download"]="\""._QXZ("Agent")."\",\""._QXZ("Agent ID")."\",\""._QXZ("Total Dead Time")."\",\""._QXZ("Average Dead Time")."\"\n";

$agent_dead_stmt="select user, count(*) as calls, sec_to_time(sum(dead_sec)) as total_dead_time, sec_to_time(round(sum(dead_sec)/count(*))) as average_dead_time from vicidial_agent_log $vicidial_agent_log_SQL and lead_id is not null group by user";
if ($DB) {$HTML_output.="<B>$agent_dead_stmt</B>";}
$agent_dead_rslt=mysql_to_mysqli($agent_dead_stmt, $link);
$agent_dead_array=array(); $i=0;
while ($agent_dead_row=mysqli_fetch_array($agent_dead_rslt))
	{
	$user=$agent_dead_row["user"];
	$agentName=$fullname_info["$user"];
	
	$agent_dead_array[$i]["user"]=$agentName;
	$agent_dead_array[$i]["ID"]=$user;
	$agent_dead_array[$i]["total_dead_time"]=$agent_dead_row["total_dead_time"];
	$agent_dead_array[$i]["average_dead_time"]=$agent_dead_row["average_dead_time"];
	$i++;

	}

if (!$sort_add_index) {$sort_add_index="user";}

if ($sort_add_index)
	{

	$col = array_column( $agent_dead_array, "$sort_add_index" );
	if (preg_match('/ desc$/', $sort_agent_dead_detail))
		{
		array_multisort( $col, SORT_DESC, SORT_STRING, $agent_dead_array );
		}
	else
		{
		array_multisort( $col, SORT_ASC, SORT_STRING, $agent_dead_array );
		}

	# print_r($agent_dead_array);
	}

$q=0;
foreach ($agent_dead_array as $i => $row)
	{
	$q++;

	$HTML_output.="<tr class='standard_font'>";
	$HTML_output.="<td>".$row["user"]."</td>";
	$HTML_output.="<td>".$row["ID"]."</td>";
	$HTML_output.="<td>".$row["total_dead_time"]."</td>";
	$HTML_output.="<td>".$row["average_dead_time"]."</td>";
	$HTML_output.="</tr>\n";
	
	if (!$download_rpt && $q%100==0) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

	$CSV_output["agent_dead_details"].="\"".$row["user"]."\",\"".$row["ID"]."\",\"".$row["total_dead_time"]."\",\"".$row["average_dead_time"]."\"\n";
	$CSV_output["auto_download"].="\"".$row["user"]."\",\"".$row["ID"]."\",\"".$row["total_dead_time"]."\",\"".$row["average_dead_time"]."\"\n";
	}

$HTML_output.="<tr class='export_row'>";
$HTML_output.="<td colspan='4' class='export_row_cell'>Export as...<input type='button' class='download_button' onClick=\"DownloadReport('AGENTS_DT', 'agent_dead_details')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="<input type='hidden' name='sort_agent_dead_detail' id='sort_agent_dead_detail' value='$sort_agent_dead_detail'>";
#####################################

if ($download_rpt)
	{
	$data_to_download=$CSV_output["$download_rpt"];

	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "AGENTS_DT_RPT_".$download_rpt."_".$FILE_TIME.".csv";
	header('Content-type: application/octet-stream');

	header("Content-Disposition: attachment; filename=\"$CSVfilename\"");
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	ob_clean();
	flush();

	echo "$data_to_download";

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

	#	$stmt="UPDATE vicidial_report_log set run_time='$TOTALrun' where report_log_id='$report_log_id';";
	#	if ($DB) {echo "|$stmt|\n";}
	#	$rslt=mysql_to_mysqli($stmt, $link);

	exit;

	}

echo $HTML_output; 
?>
</body>
</html>
