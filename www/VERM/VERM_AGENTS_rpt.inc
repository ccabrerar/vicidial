<?php
# VERM_AGENTS_rpt.inc - Vicidial Enhanced Reporting agent report
#
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGELOG:
# 220825-1631 - First build
# 230106-1304 - Added data flushing for large page outputs
# 240801-1130 - Code updates for PHP8 compatibility
#

# NANQUE, not an unanswered call?
# TIMEOT, unanswered even though it was dropped into a call that was picked up
# Warm transfers count as two calls

# $PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
# $PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
# $PHP_SELF=$_SERVER['PHP_SELF'];
# $PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);

$startMS = microtime();

$vicidial_agent_log_table="vicidial_agent_log";

if (isset($_GET["SUBMIT"]))			{$SUBMIT=$_GET["SUBMIT"];}
	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}

# No campaigns, need to query for all.
$vicidial_user_log_SQL=preg_replace('/and campaign_id in \(\'\'\)/', '', $vicidial_user_log_SQL);

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

# Sessions
$vicidial_user_stmt="select distinct user from vicidial_user_log $vicidial_user_log_SQL and event in ('LOGIN', 'LOGOUT', 'TIMEOUTLOGOUT') order by user asc";
if ($DB) {$HTML_output.="<B>$vicidial_user_stmt</B>";}
# print "$vicidial_user_stmt<BR>\n";
$vicidial_user_rslt=mysql_to_mysqli($vicidial_user_stmt, $link);

#  WHILE loop constants
$interval_start_date="";
$interval_end_date="";
$prev_user="";
$TODAY=date("Y-m-d");
$NOW_TIME=date("H:i:s");

$agent_sessions_array=array();
$agent_sessions_hour_array=array();
$agent_billable_hour_array=array();
$agent_date_hour_array=array();
$user_groups_array=array();
$locations_array=array();
$campaign_sessions_array=array();

while ($vicidial_user_row=mysqli_fetch_row($vicidial_user_rslt))
	{
	$vicidial_user=$vicidial_user_row[0];

	# 1/24/2022- Added due to occurrence of agent (Jamar Jenkins) showing LOGout first thing on 01/21/2022, but no login.
	$event_date_clause_apx="";
	if (!$overnight_agents)
		{
		$init_start_stmt="select min(event_date) from vicidial_user_log $vicidial_user_log_SQL and event='LOGIN' and user='$vicidial_user'";
		$init_start_rslt=mysql_to_mysqli($init_start_stmt, $link);
		if (mysqli_num_rows($init_start_rslt)>0)
			{
			$isr_row=mysqli_fetch_row($init_start_rslt);
			$event_date_clause_apx=" and event_date>='$isr_row[0]' ";
			}
		}


	$session_stmt="select *,if(event='LOGOUT' or event='TIMEOUTLOGOUT', 1, 0) as priority from vicidial_user_log $vicidial_user_log_SQL $event_date_clause_apx and event in ('LOGIN', 'LOGOUT', 'TIMEOUTLOGOUT') and user='$vicidial_user' order by event_date, priority asc";
	if ($DB) {$HTML_output.="<B>$session_stmt</B>";}
	# print "$session_stmt<BR>\n";
	$session_rslt=mysql_to_mysqli($session_stmt, $link);
	$prev_date="";
	$start_event_date="";

	### ITERATE THROUGH ALL THE AGENTS SESSIONS MATCHING THE REPORT CRITERIA
	$row_no=0; $agent_logged_in=0;
	while ($session_row=mysqli_fetch_array($session_rslt))
		{
		# Need this for when the report runs with a start date right in the middle of agents logged in
		$original_session_start_date="$start_date $start_time";
		$original_session_cutoff_date="$start_date 00:00:00";
		$original_session_cutoff_eod="$start_date 23:59:59";

		$full_name=$fullname_info["$session_row[user]"];
		$user=$session_row["user"];
		$campaign_id=$session_row["campaign_id"];
		$user_group=$session_row["user_group"];
		$extension=$session_row["extension"];
		$event=$session_row["event"];
		$notes=$session_row["extension"];
		$event_epoch=$session_row["event_epoch"];
		$event_time=$session_row["event_date"];
		$server_ip=$session_row["server_ip"];
		$event_date=substr($event_time, 0, 10);
		if (!$prev_date) {$prev_date=$event_date;}

		$location=GetUserLocation($user);

		# END date in case agent is still logged in at that time
		if ($event_date!=$TODAY)
			{
			$event_date_eod="$event_date 23:59:59";
			}
		else
			{
			$event_date_eod="$event_date $NOW_TIME";
			}
		
		#### CHECK IF THE USER WAS LOGGED IN AT THE START DATE/TIME, BUT ONLY DO THIS FOR THE FIRST ROW RETURNED
		if ($row_no==0)
			{
			$override_login=0;
			$previous_interval_stmt="select * from vicidial_user_log where user='$user' $and_vicidial_user_log_SQL and event_date<='$original_session_start_date' and event_date>='$original_session_cutoff_date' and event in ('LOGIN', 'LOGOUT', 'TIMEOUTLOGOUT') order by user, event_date desc limit 1";
			$previous_interval_rslt=mysql_to_mysqli($previous_interval_stmt, $link);
			# print "$previous_interval_stmt;\n";
			if (mysqli_num_rows($previous_interval_rslt)>0)
				{

				#### GO BACKWARDS, IF THE PRECEDING RECORD IS A LOGIN, THEN USE THE SESSION DATE/TIME AS THE START TIME FOR THIS INTERVAL. 
				$previous_interval_row=mysqli_fetch_array($previous_interval_rslt);
				if ($previous_interval_row["event"]=="LOGIN")
					{
					$override_login=1;
					$login_notes="PRIOR LOGIN";
					}
				else
					{
					#### IF THE LAST RECORD IS A LOGOUT, CHECK THAT THE NEXT RECORD CHRONOLOGICALLY IS ALSO NOT A LOGOUT
					#### BECAUSE IF SO, THE AGENT WAS LAGGED AND WAS STILL LOGGED IN (OR THE LOGIN WASN'T LOGGED)
					$next_interval_stmt="select * from vicidial_user_log where user='$user' $and_vicidial_user_log_SQL and event_date>='$original_session_start_date' and event_date<='$original_session_cutoff_eod' and event in ('LOGIN', 'LOGOUT', 'TIMEOUTLOGOUT') order by user, event_date desc limit 1";
					# print "$next_interval_stmt;\n";
					$next_interval_rslt=mysql_to_mysqli($next_interval_stmt, $link);
					if (mysqli_num_rows($next_interval_rslt)>0)
						{
						$next_interval_row=mysqli_fetch_array($next_interval_rslt);
						if (preg_match('/LOGOUT/', $next_interval_row["event"]))
							{
							$override_login=1;
							$login_notes="DOUBLE LOGOUT";
							}
						}
					}

				if ($override_login==1)
					{
					$start_event_date=$original_session_start_date;
					$start_epoch_stmt="select unix_timestamp('$start_event_date')";
					$start_epoch_rslt=mysql_to_mysqli($start_epoch_stmt, $link);
					$start_epoch_row=mysqli_fetch_row($start_epoch_rslt);
					$override_event_epoch=$start_epoch_row[0];

					$agent_sessions_array["$user"]["$start_event_date"]["full_name"]=$full_name;
					$agent_sessions_array["$user"]["$start_event_date"]["user_group"]=$user_group;
					$agent_sessions_array["$user"]["$start_event_date"]["start_hour"]=$override_event_epoch;
					$agent_sessions_array["$user"]["$start_event_date"]["extension"]=$extension;
					$agent_sessions_array["$user"]["$start_event_date"]["server_ip"]=$server_ip;
					$agent_sessions_array["$user"]["$start_event_date"]["login_notes"]=$login_notes;

					$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["start_hour"]=$override_event_epoch;
					$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["login_notes"]=$login_notes;

					$agent_logged_in=1;
					}
				}
			}
		$row_no++; # Keeps from doing an override check

		#### CHECK IF THE DATE IS DIFFERENT FROM THE PRECEDING RECORD IF THE AGENT IS STILL LOGGED IN
		#### WE'RE ASSUMING THAT THE AGENTS DON'T WORK PAST MIDNIGHT FOR NOW
		/*
		if ($event_date!=$prev_date && !$override_login)
			{
			if ($agent_logged_in)
				{
				# Previous agent was still logged in - mark the eod
				if ($prev_date!=$TODAY)
					{
					$event_date_eod="$prev_date 23:59:59";
					$notes="EOD LOGOUT";
					}
				else
					{
					$event_date_eod="$prev_date $NOW_TIME";
					$notes="STILL LOGGED IN";
					}

				$eod_epoch_stmt="select unix_timestamp('$start_event_date'), unix_timestamp('$event_date_eod')";
				$eod_epoch_rslt=mysql_to_mysqli($eod_epoch_stmt, $link);
				$eod_epoch_row=mysqli_fetch_row($eod_epoch_rslt);
				$agent_sessions_array["$user"]["$start_event_date"]["start_hour"]=$eod_epoch_row[0];
				$agent_sessions_array["$user"]["$start_event_date"]["full_name"]=$prev_full_name;
				$agent_sessions_array["$user"]["$start_event_date"]["user_group"]=$prev_user_group;
				$agent_sessions_array["$user"]["$start_event_date"]["end_hour"]=$eod_epoch_row[1];
				$agent_sessions_array["$user"]["$start_event_date"]["duration"]=($agent_sessions_array["$user"]["$start_event_date"]["end_hour"]-$agent_sessions_array["$user"]["$start_event_date"]["start_hour"]);
				$agent_sessions_array["$user"]["$start_event_date"]["notes"]=$notes;
				}
			}
		*/

		if ($event=="LOGIN")
			{
			#### IF AGENT IS NOT LOGGED IN, CREATE A NEW INTERVAL
			# 2/23/22 - agent page when back-to-back logins, first login is overwritten, see Dougwanna Brown 2/15/22-2/22/22.  Purge it here
			# Will this affect that "if ($campaign_id!=$prev_campaign_id)" clause a few lines down?
			# 2/25/22 - Commented out as the sessions were causing lagged agent calls to not be counted
			# if ($agent_logged_in!=0)
			# 	{
			# 	unset($agent_sessions_array["$user"]["$start_event_date"]);
			# 	$agent_logged_in=0;
			# 	}

			if ($agent_logged_in==0)
				{
				$start_event_date=$event_time;
				$agent_sessions_array["$user"]["$start_event_date"]["full_name"]=$full_name;
				$agent_sessions_array["$user"]["$start_event_date"]["user_group"]=$user_group;
				$agent_sessions_array["$user"]["$start_event_date"]["start_hour"]=$event_epoch;
				$agent_sessions_array["$user"]["$start_event_date"]["extension"]=$extension;
				$agent_sessions_array["$user"]["$start_event_date"]["server_ip"]=$server_ip;

				$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["start_hour"]=$event_epoch;
				$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["notes"]="NEW INTERVAL";
				}
			else 
				{
				#### BACK TO BACK LOGINS - AGENT IS LAGGED, START A NEW CAMPAIGN INTERVAL IF THEY LOGGED INTO A NEW CAMPAIGN
				#### DOES NOT AFFECT AGENT INTERVAL
				if ($campaign_id!=$prev_campaign_id)
					{
					$campaign_sessions_array["$prev_campaign_id"]["$start_event_date"]["$user"]["end_hour"]=$event_epoch;
					$campaign_sessions_array["$prev_campaign_id"]["$start_event_date"]["$user"]["duration"]=($campaign_sessions_array["$prev_campaign_id"]["$start_event_date"]["$user"]["end_hour"]-$campaign_sessions_array["$prev_campaign_id"]["$start_event_date"]["$user"]["start_hour"]);
					$campaign_sessions_array["$prev_campaign_id"]["$start_event_date"]["$user"]["notes"]="LAGGED LOGOUT DIFF CAMPAIGN LOGIN";
					}
				
				# COMMENTED OUT, CAUSES PROBLEMS WHEN OVERWRITES BACK-TOBACK LOGIN
				# $campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["start_hour"]=$event_epoch;

				$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["notes"]="STILL LOGGED IN";
				}

			$agent_logged_in=1;
			}

		if (preg_match('/LOGOUT/', $event)) #  && $agent_logged_in==1, removed this due to back to back logouts (see user 105110 for 2022-01-10)
			{
			# 2/23/22 - agent page when back-to-back logouts, second logout is ignored, see Dougwanna Brown 2/15/22-2/22/22. 
			$agent_sessions_array["$user"]["$start_event_date"]["end_hour"]=$event_epoch;
			$agent_sessions_array["$user"]["$start_event_date"]["end_date"]=$event_time;
			$agent_sessions_array["$user"]["$start_event_date"]["duration"]=($agent_sessions_array["$user"]["$start_event_date"]["end_hour"]-$agent_sessions_array["$user"]["$start_event_date"]["start_hour"]);

			$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["end_hour"]=$event_epoch;
			$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["end_date"]=$event_time;
			$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["duration"]=($campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["end_hour"]-$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["start_hour"]);
			
			if ($agent_logged_in==1)
				{
			# 2/23/22 - agent page when back-to-back logouts, second logout is ignored, see Dougwanna Brown 2/15/22-2/22/22. 
			# $agent_sessions_array["$user"]["$start_event_date"]["end_hour"]=$event_epoch;
			# $agent_sessions_array["$user"]["$start_event_date"]["end_date"]=$event_time;
			# $agent_sessions_array["$user"]["$start_event_date"]["duration"]=($agent_sessions_array["$user"]["$start_event_date"]["end_hour"]-$agent_sessions_array["$user"]["$start_event_date"]["start_hour"]);
				$agent_sessions_array["$user"]["$start_event_date"]["notes"]="NORMAL LOGOUT";
				$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["notes"]="NORMAL LOGOUT";
				}
			else
				{
				$agent_sessions_array["$user"]["$start_event_date"]["notes"]="REPEAT LOGOUT";
				$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["notes"]="REPEAT LOGOUT";
				}

			$agent_logged_in=0;
			}
		
		$override_login=0;
		$prev_date=$event_date;
		$prev_event=$event;
		$prev_campaign_id=$campaign_id;
		}

	if($agent_logged_in)
		{ 
		if ($event_date!=$TODAY)
			{
			$event_date_eod="$event_date 23:59:59";
			$notes="EOD LOGOUT";
			}
		else
			{
			$event_date_eod="$event_date $NOW_TIME";
			$notes="STILL LOGGED IN";
			}

		$eod_epoch_stmt="select unix_timestamp('$start_event_date'), unix_timestamp('$event_date_eod')";
		$eod_epoch_rslt=mysql_to_mysqli($eod_epoch_stmt, $link);
		$eod_epoch_row=mysqli_fetch_row($eod_epoch_rslt);
		$agent_sessions_array["$user"]["$start_event_date"]["start_hour"]=$eod_epoch_row[0];
		$agent_sessions_array["$user"]["$start_event_date"]["full_name"]=$full_name;
		$agent_sessions_array["$user"]["$start_event_date"]["user_group"]=$user_group;
		$agent_sessions_array["$user"]["$start_event_date"]["end_hour"]=$eod_epoch_row[1];
		$agent_sessions_array["$user"]["$start_event_date"]["end_date"]=$event_date_eod;
		$agent_sessions_array["$user"]["$start_event_date"]["duration"]=($agent_sessions_array["$user"]["$start_event_date"]["end_hour"]-$agent_sessions_array["$user"]["$start_event_date"]["start_hour"]);
		$agent_sessions_array["$user"]["$start_event_date"]["notes"]=$notes;

		# IS IT SAFE HERE?
		$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["end_hour"]=$eod_epoch_row[1];
		$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["end_date"]=$event_date_eod;
		$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["duration"]=($campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["end_hour"]-$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["start_hour"]);

		$agent_logged_in=0;
		}
	}

# Do round of calculations for agents logged in
foreach ($agent_sessions_array as $user => $sessions)
	{
	# Purge agents with no start_hours (this is remote agents)
	if (count(array_count_values(array_column($sessions,'start_hour')))==0)
		{
		unset($agent_sessions_array["$user"]);
		}

	foreach($sessions as $start_event_date => $activity)
		{
		if ($agent_sessions_array["$user"]["$start_event_date"]["start_hour"]) #
			{
			# print_r($activity);
			$full_name=$agent_sessions_array["$user"]["$start_event_date"]["full_name"];

			$pause_stmt="select sec_to_time(sum(pause_sec)), sum(pause_sec), count(*) from vicidial_agent_log where event_time>=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["start_hour"].") and event_time<=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["end_hour"].") and user='$user' and pause_sec>0";
			$pause_rslt=mysql_to_mysqli($pause_stmt, $link);
			$pause_row=mysqli_fetch_row($pause_rslt);
			$agent_sessions_array["$user"]["$start_event_date"]["pauses"]=$pause_row[2];
			$agent_sessions_array["$user"]["$start_event_date"]["pause_time"]=$pause_row[0];		
			$agent_sessions_array["$user"]["$start_event_date"]["pause_sec"]=$pause_row[1];		
			$agent_sessions_array["$user"]["$start_event_date"]["billable"]=0;  ## !!!!!!! THIS IS BILLABLE --> PAUSE <-- ONLY !!!!!!!!!!!
			$agent_sessions_array["$user"]["$start_event_date"]["calls"]=0;
			$agent_sessions_array["$user"]["$start_event_date"]["talk_sec"]=0;
			$agent_sessions_array["$user"]["$start_event_date"]["wait_sec"]=0;
			$agent_sessions_array["$user"]["$start_event_date"]["dispo_sec"]=0;

			$user_group=$agent_sessions_array["$user"]["$start_event_date"]["user_group"];
			$location=GetUserLocation($user);

			# GET PAUSE TYPES AND PAUSE TIMES
			$billable_stmt="select campaign_id, sub_status, if(sum(pause_sec) is null, 0, sum(pause_sec)) as total_pause, count(*) as pauses From vicidial_agent_log where event_time>=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["start_hour"].") and event_time<=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["end_hour"].") and user='$user' and pause_sec>0 group by campaign_id, sub_status order by campaign_id, sub_status";
			$billable_rslt=mysql_to_mysqli($billable_stmt, $link);
			while ($billable_row=mysqli_fetch_array($billable_rslt))
				{
				$campaign_id=$billable_row["campaign_id"];
				$sub_status=$billable_row["sub_status"];

				if (in_array($sub_status, $billable_pause_codes["$campaign_id"]) || in_array($sub_status, $billable_pause_codes["SYSTEM"]))
					{
					$agent_sessions_array["$user"]["$start_event_date"]["billable"]+=$billable_row["total_pause"];		
					# $campaign_sessions_array["$campaign_id"]["$start_event_date"]["billable"]+=$billable_row["total_pause"];		
					}
				}
			
			## GET CALLS, TALK AND WAIT TIME
			# 02/21/2022 - need to separate for outbound-only and inbound-only
			$call_stmt_outb="";
			if ($atomic_queue_campaigns_str!="")
				{
				$call_stmt_outb="select if(sum(talk_sec) is null, 0, sum(talk_sec)) as total_talk, if (sum(wait_sec) is null, 0, sum(wait_sec)) as total_wait, if (sum(dispo_sec) is null, 0, sum(dispo_sec)) as total_dispo, status, campaign_id, count(*) as calls, 'OUTBOUND' as direction From vicidial_agent_log where event_time>=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["start_hour"].") and event_time<=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["end_hour"].") and user='$user' and lead_id>0 and lead_id is not null and talk_epoch is not null and comments!='INBOUND' $and_atomic_queue_campaigns_clause group by status, campaign_id ";
				}
			
			$call_stmt_inb="";
			if ($atomic_queue_ingroups_str!="")
				{
				$vcl_uniqueid_stmt="select uniqueid, status, if(comments='EMAIL', length_in_sec, length_in_sec-queue_seconds)  from vicidial_closer_log $vicidial_closer_log_SQL and call_date+INTERVAL ceil(if(comments='EMAIL', 0, queue_seconds)) SECOND>=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["start_hour"].") and call_date+INTERVAL ceil(if(comments='EMAIL', 0, queue_seconds)) SECOND<=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["end_hour"].") and user='$user' and status!='AFTHRS'";
			
				$vcl_uniqueid_stmt.=" and call_date>=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["start_hour"].")-INTERVAL 24 HOUR and call_date<=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["end_hour"].")+INTERVAL 24 HOUR";
				# $DB=1;
				if ($DB) {print "\n\n<!--\n $vcl_uniqueid_stmt \n//-->\n\n";}
				
				$vcl_uniqueid_rslt=mysql_to_mysqli($vcl_uniqueid_stmt, $link);
				$val_uniqueid_array=array();
				while ($vcl_uniqueid_row=mysqli_fetch_row($vcl_uniqueid_rslt))
					{
					array_push($val_uniqueid_array, $vcl_uniqueid_row[0]);
					$status=$vcl_uniqueid_row[1];
					$talk_sec=$vcl_uniqueid_row[2];
					
					$agent_sessions_array["$user"]["$start_event_date"]["calls"]++;
					$agent_sessions_array["$user"]["$start_event_date"]["talk_sec"]+=$talk_sec;		

					$user_groups_array["$user_group"]["calls"]++;
					$user_groups_array["$user_group"]["talk_sec"]+=$talk_sec;

					$locations_array["$location"]["calls"]++;
					$locations_array["$location"]["talk_sec"]+=$talk_sec;

					# Moved from below due to agent log not carrying uniqueid over - stopgap because this may still have gaps in the talk/dispo time
					# due to uniqueid not carrying over
					# INBOUND CALL COUNTS FOR USERS AND USER GROUPS MUST BE TALLIED HERE BECAUSE THE CORRELATION BETWEEN
					if (in_array($status, $sale_array["$campaign_id"]) || in_array($status, $sale_array["SYSTEM"]))
						{
						$agent_sessions_array["$user"]["$start_event_date"]["sales"]++;		
						# $campaign_sessions_array["$campaign_id"]["$start_event_date"]["sales"]+=$call_row["calls"];		
						}
					if (in_array($status, $human_ans_array["$campaign_id"]) || in_array($status, $human_ans_array["SYSTEM"]))
						{
						$agent_sessions_array["$user"]["$start_event_date"]["human_answered"]++;		
						# $campaign_sessions_array["$campaign_id"]["$start_event_date"]["human_answered"]+=$call_row["calls"];		
						}
					if (in_array($status, $contact_array["$campaign_id"]) || in_array($status, $contact_array["SYSTEM"]))
						{
						$agent_sessions_array["$user"]["$start_event_date"]["contacts"]++;		
						# $campaign_sessions_array["$campaign_id"]["$start_event_date"]["contacts"]+=$call_row["calls"];		
						}
					
					}


				$call_stmt_inb="select if(sum(talk_sec) is null, 0, sum(talk_sec)) as total_talk, if (sum(wait_sec) is null, 0, sum(wait_sec)) as total_wait, if (sum(dispo_sec) is null, 0, sum(dispo_sec)) as total_dispo, status, campaign_id, count(*) as calls, 'INBOUND' as direction From vicidial_agent_log where event_time>=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["start_hour"].") and event_time<=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["end_hour"].") and user='$user' and lead_id>0 and lead_id is not null and talk_epoch is not null and comments='INBOUND' and uniqueid in ('".implode("', '", $val_uniqueid_array)."') group by status, campaign_id ";
				}

			$call_stmt=$call_stmt_outb.($call_stmt_outb!="" && $call_stmt_inb!="" ? " UNION " : "").$call_stmt_inb;
			
			# print "\n\n<!--\n $call_stmt \n//-->\n\n";

			# $call_stmt="select if(sum(talk_sec) is null, 0, sum(talk_sec)) as total_talk, if (sum(wait_sec) is null, 0, sum(wait_sec)) as total_wait, if (sum(dispo_sec) is null, 0, sum(dispo_sec)) as total_dispo, status, campaign_id, count(*) as calls From vicidial_agent_log where event_time>=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["start_hour"].") and event_time<=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["end_hour"].") and user='$user' and lead_id>0 and lead_id is not null group by status, campaign_id";

			$call_rslt=mysql_to_mysqli($call_stmt, $link);
			while($call_row=mysqli_fetch_array($call_rslt))
				{
				$call_direction=$call_row["direction"];

				# Commented out IN INBOUND because counts were not accurate, moved up
				if ($call_direction=="OUTBOUND")
					{
					$agent_sessions_array["$user"]["$start_event_date"]["calls"]+=$call_row["calls"];		
					$agent_sessions_array["$user"]["$start_event_date"]["talk_sec"]+=$call_row["total_talk"];		
					}
				$agent_sessions_array["$user"]["$start_event_date"]["wait_sec"]+=$call_row["total_wait"];		
				$agent_sessions_array["$user"]["$start_event_date"]["dispo_sec"]+=$call_row["total_dispo"];		

				# Commented out IN INBOUND because counts were not accurate, moved up
				if ($call_direction=="OUTBOUND")
					{
					$user_groups_array["$user_group"]["calls"]+=$call_row["calls"];
					$user_groups_array["$user_group"]["talk_sec"]+=$call_row["total_talk"];
					}

				# Commented out IN INBOUND because counts were not accurate, moved up
				if ($call_direction=="OUTBOUND")
					{
					$locations_array["$location"]["calls"]+=$call_row["calls"];
					$locations_array["$location"]["talk_sec"]+=$call_row["total_talk"];
					}
				$locations_array["$location"]["wait_sec"]+=$call_row["total_wait"];

				$campaign_id=$call_row["campaign_id"];
				$status=$call_row["status"];
				$agent_sessions_array["$user"]["$start_event_date"]["sales"]+=0;		
				$agent_sessions_array["$user"]["$start_event_date"]["human_answered"]+=0;		
				$agent_sessions_array["$user"]["$start_event_date"]["contacts"]+=0;		

				# Moved up to atomic_queue_ingroups_str FOR INBOUND to try and ensure accurate call/status counts
				if ($call_direction=="OUTBOUND")
					{
					if (in_array($status, $sale_array["$campaign_id"]) || in_array($status, $sale_array["SYSTEM"]))
						{
						$agent_sessions_array["$user"]["$start_event_date"]["sales"]+=$call_row["calls"];		
						# $campaign_sessions_array["$campaign_id"]["$start_event_date"]["sales"]+=$call_row["calls"];		
						}
					if (in_array($status, $human_ans_array["$campaign_id"]) || in_array($status, $human_ans_array["SYSTEM"]))
						{
						$agent_sessions_array["$user"]["$start_event_date"]["human_answered"]+=$call_row["calls"];		
						# $campaign_sessions_array["$campaign_id"]["$start_event_date"]["human_answered"]+=$call_row["calls"];		
						}
					if (in_array($status, $contact_array["$campaign_id"]) || in_array($status, $contact_array["SYSTEM"]))
						{
						$agent_sessions_array["$user"]["$start_event_date"]["contacts"]+=$call_row["calls"];		
						# $campaign_sessions_array["$campaign_id"]["$start_event_date"]["contacts"]+=$call_row["calls"];		
						}
					}
				}

			## POPULATE AGENT HOUR ARRAY
			$session_start_time=$agent_sessions_array["$user"]["$start_event_date"]["start_hour"];
			$session_end_time=$agent_sessions_array["$user"]["$start_event_date"]["end_hour"];

			while ($session_start_time<$session_end_time)
				{
				$hour_stmt="select date_format(from_unixtime($session_start_time), '%k'), date_format(from_unixtime($session_start_time), '%Y-%m-%d')";
				$hour_rslt=mysql_to_mysqli($hour_stmt, $link);
				$hour_row=mysqli_fetch_row($hour_rslt);
				$hour=$hour_row[0];
				$array_date=$hour_row[1];
				$agent_date_hour_array["$array_date"]["$hour"]["$user"]=1;

				$time_in_interval=(3600-($session_start_time%3600));
				if (($session_start_time+$time_in_interval)>$session_end_time)
					{
					$time_in_interval=$session_end_time-$session_start_time;
					}
				# Uses show_full_agent_info from settings container
				$agentName=($show_full_agent_info ? "$user - $full_name" : $full_name);
				$agent_sessions_hour_array["$agentName"]["$hour"]+=$time_in_interval;
				$agent_billable_hour_array["$agentName"]["$hour"]+=$time_in_interval;
				$session_start_time+=$time_in_interval;
				}

			## REMOVE NON-BILLABLE TIME FROM HOUR_ARRAY
			$nonbill_stmt="select sub_status, pause_epoch, pause_sec, event_time from vicidial_agent_log where event_time>=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["start_hour"].") and event_time<=from_unixtime(".$agent_sessions_array["$user"]["$start_event_date"]["end_hour"].") and user='$user' and pause_sec>0 and sub_status is not null and sub_status not in ('BLANK')";
			$nonbill_rslt=mysql_to_mysqli($nonbill_stmt, $link);
			while ($nb_row=mysqli_fetch_row($nonbill_rslt))
				{
				if (!in_array($nb_row[0], $billable_pause_codes["$campaign_id"]) && !in_array($nb_row[0], $billable_pause_codes["SYSTEM"]))
					{
					$pause_start_time=$nb_row[1];
					$pause_end_time=$nb_row[1]+$nb_row[2];
					# print "$user - $nb_row[3] => $nb_row[0], $pause_start_time for $nb_row[2] seconds\n";
					while ($pause_start_time<$pause_end_time)
						{
						$hour_stmt="select date_format(from_unixtime($pause_start_time), '%k'), date_format(from_unixtime($pause_start_time), '%Y-%m-%d')";
						$hour_rslt=mysql_to_mysqli($hour_stmt, $link);
						$hour_row=mysqli_fetch_row($hour_rslt);
						$hour=$hour_row[0];

						$time_in_interval=(3600-($pause_start_time%3600));
						if (($pause_start_time+$time_in_interval)>$pause_end_time)
							{
							$time_in_interval=$pause_end_time-$pause_start_time;
							}
						# Uses show_full_agent_info from settings container
						$agentName=($show_full_agent_info ? "$user - $full_name" : $full_name);
						$agent_billable_hour_array["$agentName"]["$hour"]-=$time_in_interval;
						$pause_start_time+=$time_in_interval;
						}
					}
				}

			## GET TOTAL TIME AND MAX TIMES FOR HEADER REPORT
			$total_agent_time+=$agent_sessions_array["$user"]["$start_event_date"]["duration"];
			if ($agent_sessions_array["$user"]["$start_event_date"]["duration"]<$min_agent_time || !$min_agent_time)
				{
				$min_agent_time=$agent_sessions_array["$user"]["$start_event_date"]["duration"];
				}

			if ($agent_sessions_array["$user"]["$start_event_date"]["duration"]>$max_agent_time || !$max_agent_time)
				{
				$max_agent_time=$agent_sessions_array["$user"]["$start_event_date"]["duration"];
				}


			}
		}
	}

# Do round of calculations for campaigns logged in
foreach ($campaign_sessions_array as $campaign_id => $logins)
	{
	foreach($logins as $start_event_date => $users)
		{
		foreach($users as $user => $activity) 
			{
			# Purge records that have no start time - these seem to be remote agents
			if (!$activity["start_hour"])
				{
				unset($campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]);
				}
			else
				{
				# Fill in end date if still logged in (may need to ignore the situation where the date is not today)
				if ($activity["notes"]=="STILL LOGGED IN")
					{
					if (substr($start_event_date, 0, 10)!=$TODAY)
						{
						$event_date_eod=substr($start_event_date, 0, 10)." 23:59:59";
						}
					else
						{
						$event_date_eod=substr($start_event_date, 0, 10)." $NOW_TIME";
						}
					$eod_epoch_stmt="select unix_timestamp('$start_event_date'), unix_timestamp('$event_date_eod')";
					$eod_epoch_rslt=mysql_to_mysqli($eod_epoch_stmt, $link);
					$eod_epoch_row=mysqli_fetch_row($eod_epoch_rslt);
					$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["start_hour"]=$eod_epoch_row[0];
					$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["end_hour"]=$eod_epoch_row[1];
					$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["duration"]=($campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["end_hour"]-$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["start_hour"]);
					}

				# FINALLY...
				$pause_stmt="select sec_to_time(sum(pause_sec)), sum(pause_sec), count(*) from vicidial_agent_log where event_time>=from_unixtime(".$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["start_hour"].") and event_time<=from_unixtime(".$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["end_hour"].") and user='$user' and pause_sec>0";
				$pause_rslt=mysql_to_mysqli($pause_stmt, $link);
				$pause_row=mysqli_fetch_row($pause_rslt);
				$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["pauses"]=$pause_row[2];
				$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["pause_time"]=$pause_row[0];		
				$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["pause_sec"]=$pause_row[1];		
				$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["billable"]=0;
				$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["calls"]=0;
				$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["talk_sec"]=0;
				$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["wait_sec"]=0;

				# Get pause types and pause times
				$billable_stmt="select campaign_id, sub_status, if(sum(pause_sec) is null, 0, sum(pause_sec)) as total_pause, count(*) as pauses From vicidial_agent_log where event_time>=from_unixtime(".$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["start_hour"].") and event_time<=from_unixtime(".$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["end_hour"].") and campaign_id='$campaign_id' and user='$user' and pause_sec>0 group by campaign_id, sub_status order by campaign_id, sub_status";
				$billable_rslt=mysql_to_mysqli($billable_stmt, $link);
				while ($billable_row=mysqli_fetch_array($billable_rslt))
					{
					$campaign_id=$billable_row["campaign_id"];
					$sub_status=$billable_row["sub_status"];

					if (in_array($sub_status, $billable_pause_codes["$campaign_id"]) || in_array($sub_status, $billable_pause_codes["SYSTEM"]))
						{
						$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["billable"]+=$billable_row["total_pause"];		
						}
					}

				$call_stmt="select if(sum(talk_sec) is null, 0, sum(talk_sec)) as total_talk, if (sum(wait_sec) is null, 0, sum(wait_sec)) as total_wait, if (sum(dispo_sec) is null, 0, sum(dispo_sec)) as total_dispo, status, campaign_id, count(*) as calls From vicidial_agent_log where event_time>=from_unixtime(".$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["start_hour"].") and event_time<=from_unixtime(".$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["end_hour"].") and user='$user' and campaign_id='$campaign_id' and lead_id>0 and lead_id is not null group by status, campaign_id";
				$call_rslt=mysql_to_mysqli($call_stmt, $link);
				while($call_row=mysqli_fetch_array($call_rslt))
					{

					# $campaign_id=$call_row["campaign_id"];
					$status=$call_row["status"];
					$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["calls"]+=$call_row["calls"];		
					$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["talk_sec"]+=$call_row["total_talk"];		
					$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["wait_sec"]+=$call_row["total_wait"];		
					$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["dispo_sec"]+=$call_row["total_dispo"];		
					$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["sales"]+=0;		
					$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["human_answered"]+=0;		
					$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["contacts"]+=0;		

					if (in_array($status, $sale_array["$campaign_id"]) || in_array($status, $sale_array["SYSTEM"]))
						{
						$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["sales"]+=$call_row["calls"];		
						}
					if (in_array($status, $human_ans_array["$campaign_id"]) || in_array($status, $human_ans_array["SYSTEM"]))
						{
						$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["human_answered"]+=$call_row["calls"];		
						}
					if (in_array($status, $contact_array["$campaign_id"]) || in_array($status, $contact_array["SYSTEM"]))
						{
						$campaign_sessions_array["$campaign_id"]["$start_event_date"]["$user"]["contacts"]+=$call_row["calls"];		
						}
					}
				}
			}

		}
	}

# Fill in hour array
foreach ($agent_sessions_hour_array as $user => $hour)
	{
	for ($i=0; $i<=23; $i++)
		{
		$agent_sessions_hour_array["$user"][$i]+=0;
		$agent_billable_hour_array["$user"][$i]+=0;
		}
	}

# print_r($agent_sessions_array); 
# print_r($campaign_sessions_array);
# print_r($agent_sessions_hour_array); die;
# print_r($agent_date_hour_array); die;

$min_agent_time+=0; # Create these if not existing
$max_agent_time+=0; # create this if not existing
$total_agent_time+=0; # create this if not existing
$agents_available=count($agent_sessions_array);
$agent_sessions=array_sum(array_map("count", $agent_sessions_array));
$grand_total_sessions=array_sum(array_map("count", $agent_sessions_array));
$average_agent_time=round(MathZDC($total_agent_time, $agent_sessions));
ksort($agent_sessions_array, SORT_NATURAL | SORT_FLAG_CASE);

######### AGENT SESSION DETAIL #########
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Agent Session Detail")." $NWB#VERM_AGENTS-session_detail$NWE</h2>"; 
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr>\n";
$HTML_output.="<th>"._QXZ("Agent sessions").":</th>";
$HTML_output.="<td>&nbsp;</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>\n";
$HTML_output.="<td>"._QXZ("N. of agents available").":</td>";
$HTML_output.="<td>$agents_available</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>\n";
$HTML_output.="<td>"._QXZ("Average agent time").":</td>";
$HTML_output.="<td>".($average_agent_time>=3600 ? floor($average_agent_time/3600).":" : "").gmdate("i:s", $average_agent_time)."</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>\n";
$HTML_output.="<td>"._QXZ("Min agent time").":</td>";
$HTML_output.="<td>".($min_agent_time>=3600 ? floor($min_agent_time/3600).":" : "").gmdate("i:s", $min_agent_time)."</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>\n";
$HTML_output.="<td>"._QXZ("Max agent time").":</td>";
$HTML_output.="<td>".($max_agent_time>=3600 ? floor($max_agent_time/3600).":" : "").gmdate("i:s", $max_agent_time)."</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>\n";
$HTML_output.="<td>"._QXZ("Total agent time").":</td>";
$HTML_output.="<td>".sprintf("%.1f", ($total_agent_time/3600))." H</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr class='export_row'>\n";
$HTML_output.="<td class='export_row_cell' colspan='2'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('AGENTS', 'agent_session_detail')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";

$CSV_output["agent_session_detail"]="\""._QXZ("Agent Sessions").":\"\n";
$CSV_output["agent_session_detail"].="\""._QXZ("N. of agents available").":\",\"$agents_available\"\n";
$CSV_output["agent_session_detail"].="\""._QXZ("Average agent time").":\",\"".($average_agent_time>=3600 ? floor($average_agent_time/3600).":" : "").gmdate("i:s", $average_agent_time)."\"\n";
$CSV_output["agent_session_detail"].="\""._QXZ("Min agent time").":\",\"".($min_agent_time>=3600 ? floor($min_agent_time/3600).":" : "").gmdate("i:s", $min_agent_time)."\"\n";
$CSV_output["agent_session_detail"].="\""._QXZ("Max agent time").":\",\"".($max_agent_time>=3600 ? floor($max_agent_time/3600).":" : "").gmdate("i:s", $max_agent_time)."\"\n";
$CSV_output["agent_session_detail"].="\""._QXZ("Total agent time").":\",\"".sprintf("%.1f", ($total_agent_time/3600))." H\"\n";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
########################################

######### AGENT PAUSE DETAIL ###########
# print_r($agent_sessions_array);
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Agent Pause Detail")." $NWB#VERM_AGENTS-pause_detail$NWE</h2>"; 
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr>\n";
### Removed 5/19
### $HTML_output.="<th>"._QXZ("Level")."</th>";
$HTML_output.="<th>"._QXZ("Agent")."</th>";
$HTML_output.="<th>"._QXZ("Sessions")."</th>";
$HTML_output.="<th>"._QXZ("Avg Session")."</th>";
$HTML_output.="<th>"._QXZ("Pauses")."</th>";
$HTML_output.="<th>"._QXZ("Avg Pause")."</th>";
$HTML_output.="<th>"._QXZ("Pause")." %</th>";
$HTML_output.="<th>"._QXZ("Pauses per session")."</th>";
$HTML_output.="</tr>\n";

$CSV_output["agent_pause_detail"]="\""._QXZ("Agent")."\",\""._QXZ("Sessions")."\",\""._QXZ("Avg Session")."\",\""._QXZ("Pauses")."\",\""._QXZ("Avg Pause")."\",\""._QXZ("Pause")." %\",\""._QXZ("Pauses per session")."\"\n";

$grand_total_duration=0;
$grand_total_calls=0;
$max_agent_duration=0;
foreach ($agent_sessions_array as $user => $sessions)
	{
	$total_calls=array_sum(array_column($sessions,'calls'));
	$total_duration=array_sum(array_column($sessions,'duration'));
	$total_pauses=array_sum(array_column($sessions,'pauses'));
	$total_pause_time=array_sum(array_column($sessions,'pause_sec'));
	$total_talk_time=array_sum(array_column($sessions,'talk_sec'));
	$total_wait_time=array_sum(array_column($sessions,'wait_sec'));
	$total_billable_time=array_sum(array_column($sessions,'billable'));
	$total_sales=array_sum(array_column($sessions,'sales'));
	$total_human_answered=array_sum(array_column($sessions,'human_answered'));
	$total_contacts=array_sum(array_column($sessions,'contacts'));
	$total_dispo_time=array_sum(array_column($sessions,'dispo_sec'));
	$total_ALL_billable_time=($total_talk_time+$total_wait_time+$total_billable_time+$total_dispo_time);

	$average_pause_time=sprintf('%02d', floor(MathZDC($total_pause_time, $total_pauses)/3600)).gmdate(":i:s", round(MathZDC($total_pause_time, $total_pauses)));
	$total_sessions=count($sessions);
	$avg_session=sprintf('%02d', floor(MathZDC($total_duration, $total_sessions)/3600)).gmdate(":i:s", round(MathZDC($total_duration, $total_sessions)));
	$pauses_pct=sprintf("%.1f", 100*MathZDC($total_pause_time, $total_duration));
	$pauses_per_session=sprintf("%.1f", MathZDC($total_pauses, $total_sessions));

	$total_pause_His=sprintf('%02d', floor($total_pause_time/3600)).gmdate(":i:s", $total_pause_time);
	$total_available_His=sprintf('%02d', floor($total_available_time/3600)).gmdate(":i:s", $total_available_time);
	$total_duration_His=sprintf('%02d', floor($total_duration/3600)).gmdate(":i:s", $total_duration);
	$total_billable_His=sprintf('%02d', floor($total_billable_time/3600)).gmdate(":i:s", $total_billable_time);
	$total_nonbillable_His=sprintf('%02d', floor($total_nonbillable_time/3600)).gmdate(":i:s", $total_nonbillable_time);
	$total_ALL_billable_His=sprintf('%02d', floor($total_ALL_billable_time/3600)).gmdate(":i:s", $total_ALL_billable_time);

	# Use these in later loops
	$grand_total_duration+=$total_duration;
	$grand_total_calls+=$total_calls;
	$grand_total_sales+=$total_sales;
	$grand_total_human_answered+=$total_human_answered;
	$grand_total_contacts+=$total_contacts;
	$grand_total_talk_time+=$total_talk_time;
	$grand_total_pause_time+=$total_pause_time;
	$grand_total_wait_time+=$total_wait_time;
	$grand_total_billable_time+=$total_billable_time;

	if($total_duration>$max_agent_duration) {$max_agent_duration=$total_duration;}
	
	$agentName=($show_full_agent_info ? "$user - ".$fullname_info["$user"] : $fullname_info["$user"]);

	$HTML_output.="<tr>\n";
	### $HTML_output.="<td>Undefined</td>";
	$HTML_output.="<td><a class='popup_link' onClick=\"ShowAgentDetails('$user', '$start_date $start_time', '$end_date $end_time', '$total_duration_His', '$total_pause_His', '$total_ALL_billable_His', '$total_billable_His')\">".$agentName."</a></td>";
	$HTML_output.="<td>".$total_sessions."</td>";
	$HTML_output.="<td>".$avg_session."</td>";
	$HTML_output.="<td>".$total_pauses."</td>";
	$HTML_output.="<td>".$average_pause_time."</td>";
	$HTML_output.="<td>".$pauses_pct." %</td>";
	$HTML_output.="<td>".$pauses_per_session."</td>";
	$HTML_output.="</tr>\n";

	$CSV_output["agent_pause_detail"].="\"".$agentName."\",\"".$total_sessions."\",\"".$avg_session."\",\"".$total_pauses."\",\"".$average_pause_time."\",\"".$pauses_pct." %\",\"".$pauses_per_session."\"\n";

	}
$HTML_output.="<tr class='export_row'>\n";
$HTML_output.="<td class='export_row_cell' colspan='7'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('AGENTS', 'agent_pause_detail')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
########################################

######### AGENT AVAILABILITY ###########
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Agent availability (for all the campaign/ingroups they are members of)")." $NWB#VERM_AGENTS-agent_availability$NWE</h2>"; # Inbound
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr>\n";
### Removed 5/19
### $HTML_output.="<th>"._QXZ("Level")."</th>";
$HTML_output.="<th>"._QXZ("Agent")."</th>";
$HTML_output.="<th>"._QXZ("Available Time (non-paused)")."</th>";
$HTML_output.="<th>"._QXZ("On Pause")."</th>";
$HTML_output.="<th>"._QXZ("Billable")."</th>";
$HTML_output.="<th>"._QXZ("Non bill.")."</th>";
### $HTML_output.="<th>"._QXZ("Overlapping")."</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="</tr>\n";

$CSV_output["agent_availability"]="\""._QXZ("Agent")."\",\""._QXZ("Available Time (non-paused)")."\",\""._QXZ("On Pause")."\",\""._QXZ("Billable")."\",\""._QXZ("Non bill.")."\",\"\"\n";

foreach ($agent_sessions_array as $user => $sessions)
	{
	$total_duration=array_sum(array_column($sessions,'duration'));
	$total_pause_time=array_sum(array_column($sessions,'pause_sec'));
	$total_wait_time=array_sum(array_column($sessions,'wait_sec'));
	$total_talk_time=array_sum(array_column($sessions,'talk_sec'));
	$total_dispo_time=array_sum(array_column($sessions,'dispo_sec'));
	$total_available_time=$total_duration-$total_pause_time;
	$total_billable_time=array_sum(array_column($sessions,'billable'));
	$total_nonbillable_time=$total_pause_time-$total_billable_time;
	$total_ALL_billable_time=($total_talk_time+$total_wait_time+$total_billable_time+$total_dispo_time);

	$total_pause_His=sprintf('%02d', floor($total_pause_time/3600)).gmdate(":i:s", $total_pause_time);
	$total_available_His=sprintf('%02d', floor($total_available_time/3600)).gmdate(":i:s", $total_available_time);
	$total_duration_His=sprintf('%02d', floor($total_duration/3600)).gmdate(":i:s", $total_duration);
	$total_billable_His=sprintf('%02d', floor($total_billable_time/3600)).gmdate(":i:s", $total_billable_time);
	$total_nonbillable_His=sprintf('%02d', floor($total_nonbillable_time/3600)).gmdate(":i:s", $total_nonbillable_time);
	$total_ALL_billable_His=sprintf('%02d', floor($total_ALL_billable_time/3600)).gmdate(":i:s", $total_ALL_billable_time);

	$available_pct=sprintf("%.1f", 100*MathZDC($total_duration, $grand_total_duration));

	$overlapping="0:00";

	$agentName=($show_full_agent_info ? "$user - ".$fullname_info["$user"] : $fullname_info["$user"]);

	$HTML_output.="<tr>\n";
	### $HTML_output.="<td>Undefined</td>";
	$HTML_output.="<td><a class='popup_link' onClick=\"ShowAgentDetails('$user', '$start_date $start_time', '$end_date $end_time', '$total_duration_His', '$total_pause_His', '$total_ALL_billable_His', '$total_billable_His')\">".$agentName."</a></td>";
	$HTML_output.="<td>".$total_available_His."</td>";
	$HTML_output.="<td>".$total_pause_His."</td>";
	$HTML_output.="<td>".$total_billable_His."</td>";
	$HTML_output.="<td>".$total_nonbillable_His."</td>";
	### $HTML_output.="<td>".$overlapping."</td>";
	$HTML_output.="<td>".$available_pct." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(MathZDC((100*$total_duration), $max_agent_duration))."%'></td>";
	$HTML_output.="</tr>\n";

	$CSV_output["agent_availability"].="\"".$agentName."\",\"".$total_available_His."\",\"".$total_pause_His."\",\"".$total_billable_His."\",\"".$total_nonbillable_His."\",\"".$available_pct." %\"\n";
	}
$HTML_output.="<tr class='export_row'>\n";
$HTML_output.="<td class='export_row_cell' colspan='7'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('AGENTS', 'agent_availability')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
########################################

######### ANSWERED CALLS ###########
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Answered calls (for selected campaign/ingroups)")." $NWB#VERM_AGENTS-answered_calls$NWE</h2>"; # Inbound
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr>\n";
### Removed 5/19
### $HTML_output.="<th>"._QXZ("Level")."</th>";
$HTML_output.="<th>"._QXZ("Agent")."</th>";
$HTML_output.="<th>"._QXZ("N. Calls")."</th>";
$HTML_output.="<th>"._QXZ("Total call time")."</th>";
$HTML_output.="<th>"._QXZ("Avg call time")."</th>";
$HTML_output.="<th>"._QXZ("Avg agent wait time")."</th>";
$HTML_output.="<th>% "._QXZ("of calls")."</th>";
$HTML_output.="</tr>\n";

$CSV_output["answered_calls"]="\""._QXZ("Agent")."\",\""._QXZ("N. Calls")."\",\""._QXZ("Total call time")."\",\""._QXZ("Avg call time")."\",\""._QXZ("Avg agent wait time")."\",\"% "._QXZ("of calls")."\"\n";

foreach ($agent_sessions_array as $user => $sessions)
	{
	$total_duration=array_sum(array_column($sessions,'duration'));
	$total_calls=array_sum(array_column($sessions,'calls'));
	$total_pause_time=array_sum(array_column($sessions,'pause_sec'));
	$total_wait_time=array_sum(array_column($sessions,'wait_sec'));
	$total_talk_time=array_sum(array_column($sessions,'talk_sec'));
	$total_dispo_time=array_sum(array_column($sessions,'dispo_sec'));
	$total_billable_time=array_sum(array_column($sessions,'billable'));
	$total_ALL_billable_time=($total_talk_time+$total_wait_time+$total_billable_time+$total_dispo_time);

	$total_talk_His=sprintf('%02d', floor($total_talk_time/3600)).gmdate(":i:s", $total_talk_time);
	$avg_talk_His=gmdate("H:i:s", round(MathZDC($total_talk_time, $total_calls)));
	$avg_wait_His=gmdate("H:i:s", round(MathZDC($total_wait_time, $total_calls)));
	$total_pause_His=sprintf('%02d', floor($total_pause_time/3600)).gmdate(":i:s", $total_pause_time);
	$total_available_His=sprintf('%02d', floor($total_available_time/3600)).gmdate(":i:s", $total_available_time);
	$total_duration_His=sprintf('%02d', floor($total_duration/3600)).gmdate(":i:s", $total_duration);
	$total_billable_His=sprintf('%02d', floor($total_billable_time/3600)).gmdate(":i:s", $total_billable_time);
	$total_ALL_billable_His=sprintf('%02d', floor($total_ALL_billable_time/3600)).gmdate(":i:s", $total_ALL_billable_time);

	$queue_pct=sprintf("%.1f", 100*MathZDC($total_calls, $grand_total_calls));

	$agentName=($show_full_agent_info ? "$user - ".$fullname_info["$user"] : $fullname_info["$user"]);

	$HTML_output.="<tr>\n";
	### $HTML_output.="<td>"._QXZ("Undefined")."</td>";
	$HTML_output.="<td><a class='popup_link' onClick=\"ShowAgentDetails('$user', '$start_date $start_time', '$end_date $end_time', '$total_duration_His', '$total_pause_His', '$total_ALL_billable_His', '$total_billable_His')\">".$agentName."</a></td>";
	$HTML_output.="<td>".$total_calls."</td>";
	$HTML_output.="<td>".$total_talk_His."</td>";
	$HTML_output.="<td>".$avg_talk_His."</td>";
	$HTML_output.="<td>".$avg_wait_His."</td>";
	$HTML_output.="<td>".$queue_pct." %</td>";
	$HTML_output.="</tr>\n";

	$CSV_output["answered_calls"].="\"".$agentName."\",\"".$total_calls."\",\"".$total_talk_His."\",\"".$avg_talk_His."\",\"".$avg_wait_His."\",\"".$queue_pct." %\"\n";

	}
$HTML_output.="<tr class='export_row'>\n";
$HTML_output.="<td class='export_row_cell' colspan='6'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('AGENTS', 'answered_calls')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
########################################

######### ANSWERED CALLS BY USER GROUP ###########
# print_r($user_groups_array);
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Answered calls by user group")." $NWB#VERM_AGENTS-answered_by_user_group$NWE</h2>"; # Inbound
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr>\n";
$HTML_output.="<th>"._QXZ("Agent group")."</th>";
$HTML_output.="<th>"._QXZ("N. Calls")."</th>";
$HTML_output.="<th>"._QXZ("Total call time")."</th>";
$HTML_output.="<th>"._QXZ("Avg call time")."</th>";
$HTML_output.="<th>% "._QXZ("of calls")."</th>";
$HTML_output.="</tr>\n";

$CSV_output["answered_calls_by_user_group"]="\""._QXZ("Agent group")."\",\""._QXZ("N. Calls")."\",\""._QXZ("Total call time")."\",\""._QXZ("Avg call time")."\",\"% "._QXZ("of calls")."\"\n";

foreach ($user_groups_array as $user_group => $data)
	{
	$total_talk_His=sprintf('%02d', floor($data["talk_sec"]/3600)).gmdate(":i:s", $data["talk_sec"]);
	$avg_talk_His=gmdate("H:i:s", MathZDC(round($data["talk_sec"]), $data["calls"]));

	$queue_pct=sprintf("%.1f", 100*MathZDC($data["calls"], $grand_total_calls));

	$HTML_output.="<tr>\n";
	$HTML_output.="<td>$user_group"."-".$usergroup_info["$user_group"]."</td>";
	$HTML_output.="<td>".$data["calls"]."</td>";
	$HTML_output.="<td>".$total_talk_His."</td>";
	$HTML_output.="<td>".$avg_talk_His."</td>";
	$HTML_output.="<td>".$queue_pct." %</td>";
	$HTML_output.="</tr>\n";

	$CSV_output["answered_calls_by_user_group"].="\"$user_group"."-".$usergroup_info["$user_group"]."\",\"".$data["calls"]."\",\"".$total_talk_His."\",\"".$avg_talk_His."\",\"".$queue_pct." %\"\n";

	}
$HTML_output.="<tr class='export_row'>\n";
$HTML_output.="<td class='export_row_cell' colspan='5'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('AGENTS', 'answered_calls_by_user_group')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
####################################

######### ANSWERED CALLS BY LOCATION ###########
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Answered calls by location")." $NWB#VERM_AGENTS-answered_by_location$NWE</h2>"; # 
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr>\n";
$HTML_output.="<th>"._QXZ("Location")."</th>";
$HTML_output.="<th>"._QXZ("N. Calls")."</th>";
$HTML_output.="<th>"._QXZ("Total call time")."</th>";
$HTML_output.="<th>"._QXZ("Avg call time")."</th>";
$HTML_output.="<th>"._QXZ("Avg wait time")."</th>";
$HTML_output.="<th>% "._QXZ("of calls")."</th>";
$HTML_output.="</tr>\n";

$CSV_output["answered_calls_by_location"]="\""._QXZ("Location")."\",\""._QXZ("N. Calls")."\",\""._QXZ("Total call time")."\",\""._QXZ("Avg call time")."\",\""._QXZ("Avg wait time")."\",\"% "._QXZ("of calls")."\"\n";

foreach ($locations_array as $location => $data)
	{
	$total_talk_His=sprintf('%02d', floor($data["talk_sec"]/3600)).gmdate(":i:s", $data["talk_sec"]);
	$avg_talk_His=gmdate("H:i:s", round(MathZDC($data["talk_sec"], $data["calls"])));
	$avg_wait_His=gmdate("H:i:s", round(MathZDC($data["wait_sec"], $data["calls"])));

	$queue_pct=sprintf("%.1f", 100*MathZDC($data["calls"], $grand_total_calls));


	$HTML_output.="<tr>\n";
	$HTML_output.="<td>".$location."</td>";
	$HTML_output.="<td>".$data["calls"]."</td>";
	$HTML_output.="<td>".$total_talk_His."</td>";
	$HTML_output.="<td>".$avg_talk_His."</td>";
	$HTML_output.="<td>".$avg_wait_His."</td>";
	$HTML_output.="<td>".$queue_pct." %</td>";
	$HTML_output.="</tr>\n";

	$CSV_output["answered_calls_by_location"].="\"".$location."\",\"".$data["calls"]."\",\"".$total_talk_His."\",\"".$avg_talk_His."\",\"".$avg_wait_His."\",\"".$queue_pct." %\"\n";

	}
$HTML_output.="<tr class='export_row'>\n";
$HTML_output.="<td class='export_row_cell' colspan='6'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('AGENTS', 'answered_calls_by_location')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
####################################

######### DREADED OCCUPANCY ###########
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Agent Occupancy Report")." $NWB#VERM_AGENTS-occupancy$NWE</h2>"; # Inbound
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr>\n";
### Removed 5/19
### $HTML_output.="<th>"._QXZ("Level")."</th>";
$HTML_output.="<th>"._QXZ("Agent")."</th>";
$HTML_output.="<th>"._QXZ("Sessions")."</th>";
$HTML_output.="<th>"._QXZ("Pau Bill")."</th>";
$HTML_output.="<th>"._QXZ("Pau N/Bill")."</th>";
$HTML_output.="<th>"._QXZ("Pauses")."</th>";
$HTML_output.="<th>"._QXZ("Total session time")."</th>"; #Overlapping
$HTML_output.="<th>"._QXZ("Total call time")."</th>";
$HTML_output.="<th>"._QXZ("Out of sessions call time")."</th>";
$HTML_output.="<th>"._QXZ("Occupancy")." %</th>";
$HTML_output.="</tr>\n";

$CSV_output["occupancy"]="\""._QXZ("Agent")."\",\""._QXZ("Sessions")."\",\""._QXZ("Pau Bill")."\",\""._QXZ("Pau N/Bill")."\",\""._QXZ("Pauses")."\",\""._QXZ("Total session time")."\",\""._QXZ("Total call time")."\",\""._QXZ("Out of sessions call time")."\",\""._QXZ("Occupancy")." %\"\n";

$grand_total_nonbillable_time=$grand_total_pause_time-$grand_total_billable_time;

$grand_total_talk_His=sprintf('%02d', floor($grand_total_talk_time/3600)).gmdate(":i:s", $grand_total_talk_time);
$grand_total_pause_His=sprintf('%02d', floor($grand_total_pause_time/3600)).gmdate(":i:s", $grand_total_pause_time);
$grand_total_duration_His=sprintf('%02d', floor($grand_total_duration/3600)).gmdate(":i:s", $grand_total_duration);
$grand_total_billable_His=sprintf('%02d', floor($grand_total_billable_time/3600)).gmdate(":i:s", $grand_total_billable_time);
$grand_total_nonbillable_His=sprintf('%02d', floor($grand_total_nonbillable_time/3600)).gmdate(":i:s", $grand_total_nonbillable_time);
$grand_total_occupancy=sprintf("%.1f", 100*(MathZDC($grand_total_talk_time, ($grand_total_duration-$grand_total_pause_time))));

$HTML_output.="<tr>\n";
### $HTML_output.="<td>&nbsp;</td>";
$HTML_output.="<td>"._QXZ("All")."</td>";
$HTML_output.="<td>".$grand_total_duration_His."</td>";
$HTML_output.="<td>".$grand_total_billable_His."</td>";
$HTML_output.="<td>".$grand_total_nonbillable_His."</td>";
$HTML_output.="<td>".$grand_total_pause_His."</td>";
$HTML_output.="<td>00:00:00</td>";
$HTML_output.="<td>".$grand_total_talk_His."</td>";
$HTML_output.="<td>00:00:00</td>";
$HTML_output.="<td>$grand_total_occupancy %</td>";
$HTML_output.="</tr>\n";

$CSV_output["occupancy"].="\""._QXZ("All")."\",\"".$grand_total_duration_His."\",\"".$grand_total_billable_His."\",\"".$grand_total_nonbillable_His."\",\"".$grand_total_pause_His."\",\"00:00:00\",\"".$grand_total_talk_His."\",\"00:00:00\",\"$grand_total_occupancy %\"\n";

foreach ($agent_sessions_array as $user => $sessions)
	{
	$total_calls=array_sum(array_column($sessions,'calls'));
	$total_duration=array_sum(array_column($sessions,'duration'));
	$total_pause_time=array_sum(array_column($sessions,'pause_sec'));
	$total_wait_time=array_sum(array_column($sessions,'wait_sec'));
	$total_talk_time=array_sum(array_column($sessions,'talk_sec'));
	$total_dispo_time=array_sum(array_column($sessions,'dispo_sec'));
	$total_billable_time=array_sum(array_column($sessions,'billable'));
	$total_nonbillable_time=$total_pause_time-$total_billable_time;
	$total_ALL_billable_time=($total_talk_time+$total_wait_time+$total_billable_time+$total_dispo_time);

	$total_talk_His=sprintf('%02d', floor($total_talk_time/3600)).gmdate(":i:s", $total_talk_time);
	$total_pause_His=sprintf('%02d', floor($total_pause_time/3600)).gmdate(":i:s", $total_pause_time);
	$total_duration_His=sprintf('%02d', floor($total_duration/3600)).gmdate(":i:s", $total_duration);
	$total_billable_His=sprintf('%02d', floor($total_billable_time/3600)).gmdate(":i:s", $total_billable_time);
	$total_nonbillable_His=sprintf('%02d', floor($total_nonbillable_time/3600)).gmdate(":i:s", $total_nonbillable_time);
	$total_ALL_billable_His=sprintf('%02d', floor($total_ALL_billable_time/3600)).gmdate(":i:s", $total_ALL_billable_time);

	$occupancy=sprintf("%.1f", 100*MathZDC($total_talk_time, ($total_duration-$total_pause_time)));

	$agentName=($show_full_agent_info ? "$user - ".$fullname_info["$user"] : $fullname_info["$user"]);

	$HTML_output.="<tr>\n";
	### $HTML_output.="<td>"._QXZ("Undefined")."</td>";
	$HTML_output.="<td><a class='popup_link' onClick=\"ShowAgentDetails('$user', '$start_date $start_time', '$end_date $end_time', '$total_duration_His', '$total_pause_His', '$total_ALL_billable_His', '$total_billable_His')\">".$agentName."</a></td>";
	$HTML_output.="<td>".$total_duration_His."</td>";
	$HTML_output.="<td>".$total_billable_His."</td>";
	$HTML_output.="<td>".$total_nonbillable_His."</td>";
	$HTML_output.="<td>".$total_pause_His."</td>";
	$HTML_output.="<td>00:00:00</td>"; 
	$HTML_output.="<td>".$total_talk_His."</td>";
	$HTML_output.="<td>00:00:00</td>";
	$HTML_output.="<td>$occupancy %</td>";
	$HTML_output.="</tr>\n";

	$CSV_output["occupancy"].="\"".$agentName."\",\"".$total_duration_His."\",\"".$total_billable_His."\",\"".$total_nonbillable_His."\",\"".$total_pause_His."\",\"00:00:00\",\"".$total_talk_His."\",\"00:00:00\",\"$occupancy %\"\n";

	}
$HTML_output.="<tr class='export_row'>\n";
$HTML_output.="<td class='export_row_cell' colspan='9'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('AGENTS', 'occupancy')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
####################################

##### AGENT SESSION TIME BY HOUR ###
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Agent Session Time by Hour")." $NWB#VERM_AGENTS-session_time_by_hour$NWE</h2>"; # Inbound
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr>\n";
$HTML_output.="<th>Agent</th>";

$CSV_output["agent_sessions_by_hour"]="\"Agent\",";

for ($i=0; $i<=23; $i++)
	{
	$HTML_output.="<th>$i</th>";

	$CSV_output["agent_sessions_by_hour"].="\"$i\",";
	}
$HTML_output.="<th>Total time</th>";
$HTML_output.="</tr>\n";

$CSV_output["agent_sessions_by_hour"].="\"Total time\"\n";

foreach ($agent_sessions_hour_array as $agent => $hours)
	{
	$agent_total_hours=0;
	$HTML_output.="<tr>\n";
	$HTML_output.="<td class='small_text'>$agent</td>";

	$CSV_output["agent_sessions_by_hour"].="\"$agent\",";

	for ($i=0; $i<=23; $i++)
		{
		$agent_total_hours+=$hours[$i];
		$HTML_output.="<td class='small_text'>".sprintf('%02d', floor($hours[$i]/3600)).gmdate(":i:s", $hours[$i])."</td>";

		$CSV_output["agent_sessions_by_hour"].="\"".sprintf('%02d', floor($hours[$i]/3600)).gmdate(":i:s", $hours[$i])."\",";
		}
	$HTML_output.="<td class='small_text'>".sprintf('%02d', floor($agent_total_hours/3600)).gmdate(":i:s", $agent_total_hours)."</td>";
	$HTML_output.="</tr>\n";	

	$CSV_output["agent_sessions_by_hour"].="\"".sprintf('%02d', floor($agent_total_hours/3600)).gmdate(":i:s", $agent_total_hours)."\"\n";
	}
$HTML_output.="<tr class='export_row'>\n";
$HTML_output.="<td class='export_row_cell' colspan='26'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('AGENTS', 'agent_sessions_by_hour')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
####################################

# No payable, not used

#### AGENT BILLABLE TIME BY HOUR ###
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Agent Billable Time by Hour")." $NWB#VERM_AGENTS-billable_time_by_hour$NWE</h2>"; # Inbound
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr>\n";
$HTML_output.="<th>"._QXZ("Agent")."</th>";

$CSV_output["agent_billable_by_hour"]="\"Agent\",";

for ($i=0; $i<=23; $i++)
	{
	$HTML_output.="<th>$i</th>";

	$CSV_output["agent_billable_by_hour"].="\"$i\",";
	}
$HTML_output.="<th>"._QXZ("Total time")."</th>";
$HTML_output.="</tr>\n";

$CSV_output["agent_billable_by_hour"].="\"Total time\"\n";

foreach ($agent_billable_hour_array as $agent => $hours)
	{
	$agent_total_hours=0;
	$HTML_output.="<tr>\n";
	$HTML_output.="<td class='small_text'>$agent</td>";

	$CSV_output["agent_billable_by_hour"].="\"$agent\",";

	for ($i=0; $i<=23; $i++)
		{
		$agent_total_hours+=$hours[$i];
		$HTML_output.="<td class='small_text'>".sprintf('%02d', floor($hours[$i]/3600)).gmdate(":i:s", $hours[$i])."</td>";

		$CSV_output["agent_billable_by_hour"].="\"".sprintf('%02d', floor($hours[$i]/3600)).gmdate(":i:s", $hours[$i])."\",";
		}
	$HTML_output.="<td class='small_text'>".sprintf('%02d', floor($agent_total_hours/3600)).gmdate(":i:s", $agent_total_hours)."</td>";
	$HTML_output.="</tr>\n";

	$CSV_output["agent_billable_by_hour"].="\"".sprintf('%02d', floor($agent_total_hours/3600)).gmdate(":i:s", $agent_total_hours)."\"\n";
	}
$HTML_output.="<tr class='export_row'>\n";
$HTML_output.="<td class='export_row_cell' colspan='26'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('AGENTS', 'agent_billable_by_hour')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
####################################

#### AGENTS BY HOUR ###
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Agents by Hour")." $NWB#VERM_AGENTS-agents_by_hour$NWE</h2>"; # Inbound
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr>\n";
$HTML_output.="<th>"._QXZ("Date")."</th>";

$CSV_output["agents_by_hour"]="\""._QXZ("Date")."\",";

for ($i=0; $i<=23; $i++)
	{
	$HTML_output.="<th>$i</th>";

	$CSV_output["agents_by_hour"].="\"$i\",";
	}
$HTML_output.="<th>"._QXZ("Total")."</th>";
$HTML_output.="</tr>\n";

$CSV_output["agents_by_hour"].="\""._QXZ("Total")."\"\n";

ksort($agent_date_hour_array);
foreach ($agent_date_hour_array as $array_date => $hour)
	{
	$total_distinct_agents=array();
	$HTML_output.="<tr>\n";
	$HTML_output.="<td>$array_date</td>";

	$CSV_output["agents_by_hour"].="\"$array_date\",";

	for ($i=0; $i<=23; $i++)
		{
		#2/10/22 - added below to remove error
		if(!$hour[$i]) {$hour[$i]=array();}

		$HTML_output.="<td>".count($hour[$i])."</td>";	

		$CSV_output["agents_by_hour"].="\"".count($hour[$i])."\",";
		foreach ($hour[$i] as $agent_id => $mrker)
			{
			array_push($total_distinct_agents, $agent_id);
			}
		}
	# echo "*** "; print_r($total_distinct_agents);
	$HTML_output.="<td>".count(array_unique($total_distinct_agents))."</td>";
	$HTML_output.="</tr>\n";

	$CSV_output["agents_by_hour"].="\"".count(array_unique($total_distinct_agents))."\"\n";
	}
$HTML_output.="<tr class='export_row'>\n";
$HTML_output.="<td class='export_row_cell' colspan='26'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('AGENTS', 'agents_by_hour')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
####################################

######## AGENT SESSIONS VIEW #######
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Agent Sessions View")." $NWB#VERM_AGENTS-agent_sessions_view$NWE</h2>"; # Inbound
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr>\n";
$HTML_output.="<th>"._QXZ("Agent")."</th>";
$HTML_output.="<th>"._QXZ("Login")."</th>";
$HTML_output.="<th>"._QXZ("Talk")."</th>";
$HTML_output.="<th>"._QXZ("Pause + Wait")."</th>";
$HTML_output.="<th>"._QXZ("Pause")."</th>";
$HTML_output.="<th>"._QXZ("Billable (pause)")."</th>";
$HTML_output.="<th>"._QXZ("Not Billable (pause)")."</th>";
$HTML_output.="<th>"._QXZ("Tot. Wait")."</th>";
$HTML_output.="<th>"._QXZ("Sales")."</th>";
$HTML_output.="<th>"._QXZ("S PH")."</th>";
$HTML_output.="<th>"._QXZ("Cust Cont")."</th>";
$HTML_output.="<th>"._QXZ("CC PH")."</th>";
$HTML_output.="<th>"._QXZ("Human Ans")."</th>";
$HTML_output.="<th>"._QXZ("HA PH")."</th>";
### Removed 5/19
### $HTML_output.="<th>"._QXZ("Offered")."</th>";
$HTML_output.="<th>"._QXZ("Calls")."</th>";
$HTML_output.="<th>"._QXZ("HA Conv.")."</th>";
$HTML_output.="<th>"._QXZ("CC Conv.")."</th>";
$HTML_output.="<th>"._QXZ("Goal Ftrs")."</th>";
$HTML_output.="<th>"._QXZ("Ftrs")."</th>";
$HTML_output.="<th>"._QXZ("Avg")."</th>";
$HTML_output.="<th>&nbsp;</th>";
### Removed 6/14, may re-instate if "taken/lost" becomes necessary/desired. Remove above td if so
### $HTML_output.="<th>"._QXZ("T")."</th>";
### $HTML_output.="<th>"._QXZ("L")."</th>";
$HTML_output.="</tr>\n";

$CSV_output["agent_sessions_view"]="\""._QXZ("Agent")."\",\""._QXZ("Login")."\",\""._QXZ("Talk")."\",\""._QXZ("Pause + Wait")."\",\""._QXZ("Pause")."\",\""._QXZ("Billable (pause)")."\",\""._QXZ("Not Billable (pause)")."\",\""._QXZ("Tot. Wait")."\",\""._QXZ("Sales")."\",\""._QXZ("S PH")."\",\""._QXZ("Cust Cont")."\",\""._QXZ("CC PH")."\",\""._QXZ("Human Ans")."\",\""._QXZ("HA PH")."\",\""._QXZ("Answered")."\",\""._QXZ("HA Conv.")."\",\""._QXZ("CC Conv.")."\",\""._QXZ("Goal Ftrs")."\",\""._QXZ("Ftrs")."\",\""._QXZ("Avg")."\"\n";

$grand_total_idle_time=$grand_total_pause_time+$grand_total_wait_time;
$grand_sph=sprintf("%.1f", MathZDC($grand_total_sales, ($grand_total_duration/3600)));
$grand_haph=sprintf("%.1f", MathZDC($grand_total_human_answered, ($grand_total_duration/3600)));
$grand_ccph=sprintf("%.1f", MathZDC($grand_total_contacts, ($grand_total_duration/3600)));
$grand_ha_conv=sprintf("%.1f", 100*MathZDC($grand_total_sales, $grand_total_human_answered));
$grand_cc_conv=sprintf("%.1f", 100*MathZDC($grand_total_sales, $grand_total_contacts));
$grand_avg_talk_time=round(MathZDC($grand_total_talk_time, $grand_total_calls));

$grand_total_talk_His=sprintf('%02d', floor($grand_total_talk_time/3600)).gmdate(":i:s", $grand_total_talk_time);
$grand_total_wait_His=sprintf('%02d', floor($grand_total_wait_time/3600)).gmdate(":i:s", $grand_total_wait_time);
$grand_total_idle_His=sprintf('%02d', floor($grand_total_idle_time/3600)).gmdate(":i:s", $grand_total_idle_time);
$grand_total_pause_His=sprintf('%02d', floor($grand_total_pause_time/3600)).gmdate(":i:s", $grand_total_pause_time);
$grand_total_duration_His=sprintf('%02d', floor($grand_total_duration/3600)).gmdate(":i:s", $grand_total_duration);
$grand_total_billable_His=sprintf('%02d', floor($grand_total_billable_time/3600)).gmdate(":i:s", $grand_total_billable_time);
$grand_total_nonbillable_His=sprintf('%02d', floor($grand_total_nonbillable_time/3600)).gmdate(":i:s", $grand_total_nonbillable_time);
$grand_avg_talk_His=sprintf('%02d', floor($grand_avg_talk_time/3600)).gmdate(":i:s", $grand_avg_talk_time);

$HTML_output.="<tr>\n";
$HTML_output.="<td class='small_text'>&nbsp;</td>";
$HTML_output.="<td class='small_text'>$grand_total_duration_His</td>";
$HTML_output.="<td class='small_text'>$grand_total_talk_His</td>";
$HTML_output.="<td class='small_text'>$grand_total_idle_His</td>";
$HTML_output.="<td class='small_text'>$grand_total_pause_His</td>";
$HTML_output.="<td class='small_text'>$grand_total_billable_His</td>";
$HTML_output.="<td class='small_text'>$grand_total_nonbillable_His</td>";
$HTML_output.="<td class='small_text'>$grand_total_wait_His</td>";
$HTML_output.="<td class='small_text'>$grand_total_sales</td>";
$HTML_output.="<td class='small_text'>$grand_sph</td>";
$HTML_output.="<td class='small_text'>$grand_total_contacts</td>";
$HTML_output.="<td class='small_text'>$grand_ccph</td>";
$HTML_output.="<td class='small_text'>$grand_total_human_answered</td>";
$HTML_output.="<td class='small_text'>$grand_haph</td>";
### Removed 5/19
### $HTML_output.="<td class='small_text'>$grand_total_calls</td>";
$HTML_output.="<td class='small_text'>$grand_total_calls</td>";
$HTML_output.="<td class='small_text'>$grand_ha_conv %</td>"; # $sales/$hq
$HTML_output.="<td class='small_text'>$grand_cc_conv %</td>"; # $sales/$cc
$HTML_output.="<td class='small_text'>0</td>";
$HTML_output.="<td class='small_text'>0</td>";
$HTML_output.="<td class='small_text'>$avg_talk_His</td>";
$HTML_output.="<td class='small_text'>&nbsp;</td>";
### Removed 6/14 - may reinstate if taken/lost is desired for this
### $HTML_output.="<td class='small_text'>&nbsp;</td>";
$HTML_output.="</tr>\n";

$CSV_output["agent_sessions_view"].="\"\",\"$grand_total_duration_His\",\"$grand_total_talk_His\",\"$grand_total_idle_His\",\"$grand_total_pause_His\",\"$grand_total_billable_His\",\"$grand_total_nonbillable_His\",\"$grand_total_wait_His\",\"$grand_total_sales\",\"$grand_sph\",\"$grand_total_contacts\",\"$grand_ccph\",\"$grand_total_human_answered\",\"$grand_haph\",\"$grand_total_calls\",\"$grand_ha_conv\",\"$grand_cc_conv\",\"0\",\"0\",\"$avg_talk_His\"\n";

foreach ($agent_sessions_array as $user => $sessions)
	{
	$total_calls=array_sum(array_column($sessions,'calls'));
	$total_wait_time=array_sum(array_column($sessions,'wait_sec'));
	$total_talk_time=array_sum(array_column($sessions,'talk_sec'));
	$total_duration=array_sum(array_column($sessions,'duration'));
	$total_pause_time=array_sum(array_column($sessions,'pause_sec'));
	$total_billable_time=array_sum(array_column($sessions,'billable'));
	$total_nonbillable_time=$total_pause_time-$total_billable_time;
	$total_idle_time=$total_pause_time+$total_wait_time;
	$total_sales=array_sum(array_column($sessions,'sales'));
	$total_human_answered=array_sum(array_column($sessions,'human_answered'));
	$total_contacts=array_sum(array_column($sessions,'contacts'));

	$sph=sprintf("%.1f", MathZDC($total_sales, ($total_duration/3600)));
	$haph=sprintf("%.1f", MathZDC($total_human_answered, ($total_duration/3600)));
	$ccph=sprintf("%.1f", MathZDC($total_contacts, ($total_duration/3600)));
	$ha_conv=sprintf("%.1f", 100*MathZDC($total_sales, $total_human_answered));
	$cc_conv=sprintf("%.1f", 100*MathZDC($total_sales, $total_contacts));
	$avg_talk_time=round(MathZDC($total_talk_time, $total_calls));

	$total_talk_His=sprintf('%02d', floor($total_talk_time/3600)).gmdate(":i:s", $total_talk_time);
	$total_wait_His=sprintf('%02d', floor($total_wait_time/3600)).gmdate(":i:s", $total_wait_time);
	$total_idle_His=sprintf('%02d', floor($total_idle_time/3600)).gmdate(":i:s", $total_idle_time);
	$total_pause_His=sprintf('%02d', floor($total_pause_time/3600)).gmdate(":i:s", $total_pause_time);
	$total_duration_His=sprintf('%02d', floor($total_duration/3600)).gmdate(":i:s", $total_duration);
	$total_billable_His=sprintf('%02d', floor($total_billable_time/3600)).gmdate(":i:s", $total_billable_time);
	$total_nonbillable_His=sprintf('%02d', floor($total_nonbillable_time/3600)).gmdate(":i:s", $total_nonbillable_time);
	$avg_talk_His=sprintf('%02d', floor($avg_talk_time/3600)).gmdate(":i:s", $avg_talk_time);

	$agentName=($show_full_agent_info ? "$user - ".$fullname_info["$user"] : $fullname_info["$user"]);

	$HTML_output.="<tr>\n";
	$HTML_output.="<td class='small_text'>".$agentName."</td>";
	$HTML_output.="<td class='small_text'>$total_duration_His</td>";
	$HTML_output.="<td class='small_text'>$total_talk_His</td>";
	$HTML_output.="<td class='small_text'>$total_idle_His</td>";
	$HTML_output.="<td class='small_text'>$total_pause_His</td>";
	$HTML_output.="<td class='small_text'>$total_billable_His</td>";
	$HTML_output.="<td class='small_text'>$total_nonbillable_His</td>";
	$HTML_output.="<td class='small_text'>$total_wait_His</td>";
	$HTML_output.="<td class='small_text'>$total_sales</td>";
	$HTML_output.="<td class='small_text'>$sph</td>";
	$HTML_output.="<td class='small_text'>$total_contacts</td>";
	$HTML_output.="<td class='small_text'>$ccph</td>";
	$HTML_output.="<td class='small_text'>$total_human_answered</td>";
	$HTML_output.="<td class='small_text'>$haph</td>";
	### $HTML_output.="<td class='small_text'>$total_calls</td>";
	$HTML_output.="<td class='small_text'>$total_calls</td>";
	$HTML_output.="<td class='small_text'>$ha_conv %</td>"; # $sales/$hq
	$HTML_output.="<td class='small_text'>$cc_conv %</td>"; # $sales/$cc
	$HTML_output.="<td class='small_text'>0</td>";
	$HTML_output.="<td class='small_text'>0</td>";
	$HTML_output.="<td class='small_text'>$avg_talk_His</td>";
	$HTML_output.="<td><a onClick=\"ShowOutcomesDetails('', '$start_date $start_time', '$end_date $end_time', '$user')\"><svg width='20' height='20' viewBox='0 0 20 20' data-svg='search'><circle fill='none' stroke='#000' stroke-width='1.1' cx='9' cy='9' r='7'></circle><path fill='none' stroke='#000' stroke-width='1.1' d='M14,14 L18,18 L14,14 Z'></path></svg></a></td>";
	### Removed 6/14, may re-instate if "taken/lost" becomes necessary/desired
	### $HTML_output.="<td><a onClick=\"ShowOutcomesDetails('THERE IS NO SUCH THING', '$start_date $start_time', '$end_date $end_time')\"><svg width='20' height='20' viewBox='0 0 20 20' data-svg='search'><circle fill='none' stroke='#000' stroke-width='1.1' cx='9' cy='9' r='7'></circle><path fill='none' stroke='#000' stroke-width='1.1' d='M14,14 L18,18 L14,14 Z'></path></svg></a></td>";
	$HTML_output.="</tr>\n";

	$CSV_output["agent_sessions_view"].="\"".$agentName."\",\"$total_duration_His\",\"$total_talk_His\",\"$total_idle_His\",\"$total_pause_His\",\"$total_billable_His\",\"$total_nonbillable_His\",\"$total_wait_His\",\"$total_sales\",\"$sph\",\"$total_contacts\",\"$ccph\",\"$total_human_answered\",\"$haph\",\"$total_calls\",\"$ha_conv\",\"$cc_conv\",\"0\",\"0\",\"$avg_talk_His\"\n";
}
$HTML_output.="<tr class='export_row'>\n";
$HTML_output.="<td class='export_row_cell' colspan='21'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('AGENTS', 'agent_sessions_view')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
####################################

$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Campaign/Ingroup Sessions View")." $NWB#VERM_AGENTS-queue_sessions_view$NWE</h2>"; # Inbound
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr>\n";
$HTML_output.="<th>"._QXZ("Campaign/ingroup")."</th>";
$HTML_output.="<th>"._QXZ("Login")."</th>";
$HTML_output.="<th>"._QXZ("Talk")."</th>";
$HTML_output.="<th>"._QXZ("Pause + Wait")."</th>";
$HTML_output.="<th>"._QXZ("Billable")."</th>";
$HTML_output.="<th>"._QXZ("Not Billable")."</th>";
$HTML_output.="<th>"._QXZ("Sales")."</th>";
$HTML_output.="<th>"._QXZ("S PH")."</th>";
$HTML_output.="<th>"._QXZ("QC")."</th>";
$HTML_output.="<th>"._QXZ("QC PH")."</th>";
$HTML_output.="<th>"._QXZ("Cont.")."</th>";
$HTML_output.="<th>"._QXZ("C PH")."</th>";
### Removed 5/19
### $HTML_output.="<th>"._QXZ("Offered")."</th>";
$HTML_output.="<th>"._QXZ("Calls")."</th>";
$HTML_output.="<th>"._QXZ("Conv.")."</th>";
$HTML_output.="<th>"._QXZ("QConv.")."</th>";
$HTML_output.="<th>"._QXZ("Goal Ftrs")."</th>";
$HTML_output.="<th>"._QXZ("Ftrs")."</th>";
$HTML_output.="<th>"._QXZ("Avg")."</th>";
### Removed 6/14, may re-instate if "taken/lost" becomes necessary/desired
### $HTML_output.="<th>"._QXZ("T")."</th>";
### $HTML_output.="<th>"._QXZ("L")."</th>";
$HTML_output.="</tr>\n";

$CSV_output["queue_sessions_view"]="\""._QXZ("Campaign/ingroup")."\",\""._QXZ("Login")."\",\""._QXZ("Talk")."\",\""._QXZ("Pause + Wait")."\",\""._QXZ("Billable")."\",\""._QXZ("Not Billable")."\",\""._QXZ("Sales")."\",\""._QXZ("S PH")."\",\""._QXZ("QC")."\",\""._QXZ("QC PH")."\",\""._QXZ("Cont.")."\",\""._QXZ("C PH")."\",\""._QXZ("Calls")."\",\""._QXZ("Conv.")."\",\""._QXZ("QConv.")."\",\""._QXZ("Goal Ftrs")."\",\""._QXZ("Ftrs")."\",\""._QXZ("Avg")."\"\n";

$HTML_output.="<tr>\n";
$HTML_output.="<td class='small_text'>&nbsp;</td>";
$HTML_output.="<td class='small_text'>$grand_total_duration_His</td>";
$HTML_output.="<td class='small_text'>$grand_total_talk_His</td>";
$HTML_output.="<td class='small_text'>$grand_total_idle_His</td>";
$HTML_output.="<td class='small_text'>$grand_total_billable_His</td>";
$HTML_output.="<td class='small_text'>$grand_total_nonbillable_His</td>";
$HTML_output.="<td class='small_text'>$grand_total_sales</td>";
$HTML_output.="<td class='small_text'>$grand_sph</td>";
$HTML_output.="<td class='small_text'>$grand_total_contacts</td>";
$HTML_output.="<td class='small_text'>$grand_ccph</td>";
$HTML_output.="<td class='small_text'>$grand_total_human_answered</td>";
$HTML_output.="<td class='small_text'>$grand_haph</td>";
### $HTML_output.="<td class='small_text'>$grand_total_calls</td>";
$HTML_output.="<td class='small_text'>$grand_total_calls</td>";
$HTML_output.="<td class='small_text'>$grand_ha_conv %</td>"; # $sales/$hq
$HTML_output.="<td class='small_text'>$grand_cc_conv %</td>"; # $sales/$cc
$HTML_output.="<td class='small_text'>0</td>";
$HTML_output.="<td class='small_text'>0</td>";
$HTML_output.="<td class='small_text'>$grand_avg_talk_His</td>";
### Removed 6/14, may re-instate if "taken/lost" becomes necessary/desired.  Add magnifying glass and pop-up if so
### $HTML_output.="<td class='small_text'>&nbsp;</td>";
### $HTML_output.="<td class='small_text'>&nbsp;</td>";
$HTML_output.="</tr>\n";

$CSV_output["queue_sessions_view"].="\"&nbsp;\",\"$grand_total_duration_His\",\"$grand_total_talk_His\",\"$grand_total_idle_His\",\"$grand_total_billable_His\",\"$grand_total_nonbillable_His\",\"$grand_total_sales\",\"$grand_sph\",\"$grand_total_contacts\",\"$grand_ccph\",\"$grand_total_human_answered\",\"$grand_haph\",\"$grand_total_calls\",\"$grand_ha_conv\",\"$grand_cc_conv\",\"0\",\"0\",\"$grand_avg_talk_His\"\n";

foreach ($campaign_sessions_array as $campaign => $start_dates)
	{
	$total_calls=0;
	$total_wait_time=0;
	$total_talk_time=0;
	$total_duration=0;
	$total_pause_time=0;
	$total_billable_time=0;
	$total_nonbillable_time=0;
	$total_idle_time=0;
	$total_sales=0;
	$total_human_answered=0;
	$total_contacts=0;

	foreach ($start_dates as $user => $sessions)
		{
		$total_calls+=array_sum(array_column($sessions,'calls'));
		$total_wait_time+=array_sum(array_column($sessions,'wait_sec'));
		$total_talk_time+=array_sum(array_column($sessions,'talk_sec'));
		$total_duration+=array_sum(array_column($sessions,'duration'));
		$total_pause_time+=array_sum(array_column($sessions,'pause_sec'));
		$total_billable_time+=array_sum(array_column($sessions,'billable'));
		$total_sales+=array_sum(array_column($sessions,'sales'));
		$total_human_answered+=array_sum(array_column($sessions,'human_answered'));
		$total_contacts+=array_sum(array_column($sessions,'contacts'));
		}

	$total_nonbillable_time+=$total_pause_time-$total_billable_time;
	$total_idle_time+=$total_pause_time+$total_wait_time;

	$sph=sprintf("%.1f", MathZDC($total_sales, ($total_duration/3600)));
	$haph=sprintf("%.1f", MathZDC($total_human_answered, ($total_duration/3600)));
	$ccph=sprintf("%.1f", MathZDC($total_contacts, ($total_duration/3600)));
	$ha_conv=sprintf("%.1f", 100*MathZDC($total_sales, $total_human_answered));
	$cc_conv=sprintf("%.1f", 100*MathZDC($total_sales, $total_contacts));
	$avg_talk_time=round(MathZDC($total_talk_time, $total_calls));

	$total_talk_His=sprintf('%02d', floor($total_talk_time/3600)).gmdate(":i:s", floor($total_talk_time));
	$total_wait_His=sprintf('%02d', floor($total_wait_time/3600)).gmdate(":i:s", floor($total_wait_time));
	$total_idle_His=sprintf('%02d', floor($total_idle_time/3600)).gmdate(":i:s", floor($total_idle_time));
	$total_pause_His=sprintf('%02d', floor($total_pause_time/3600)).gmdate(":i:s", floor($total_pause_time));
	$total_duration_His=sprintf('%02d', floor($total_duration/3600)).gmdate(":i:s", floor($total_duration));
	$total_billable_His=sprintf('%02d', floor($total_billable_time/3600)).gmdate(":i:s", floor($total_billable_time));
	$total_nonbillable_His=sprintf('%02d', floor($total_nonbillable_time/3600)).gmdate(":i:s", floor($total_nonbillable_time));
	$avg_talk_His=sprintf('%02d', floor($avg_talk_time/3600)).gmdate(":i:s", $avg_talk_time);

	$HTML_output.="<tr>\n";
	$HTML_output.="<td class='small_text'>".$queue_names["$campaign"]."</td>";
	$HTML_output.="<td class='small_text'>$total_duration_His</td>";
	$HTML_output.="<td class='small_text'>$total_talk_His</td>";
	$HTML_output.="<td class='small_text'>$total_idle_His</td>";
	$HTML_output.="<td class='small_text'>$total_billable_His</td>";
	$HTML_output.="<td class='small_text'>$total_nonbillable_His</td>";
	$HTML_output.="<td class='small_text'>$total_sales</td>";
	$HTML_output.="<td class='small_text'>$sph</td>";
	$HTML_output.="<td class='small_text'>$total_contacts</td>";
	$HTML_output.="<td class='small_text'>$ccph</td>";
	$HTML_output.="<td class='small_text'>$total_human_answered</td>";
	$HTML_output.="<td class='small_text'>$haph</td>";
	### $HTML_output.="<td class='small_text'>$total_calls</td>";
	$HTML_output.="<td class='small_text'>$total_calls</td>";
	$HTML_output.="<td class='small_text'>$ha_conv %</td>"; # $sales/$hq
	$HTML_output.="<td class='small_text'>$cc_conv %</td>"; # $sales/$cc
	$HTML_output.="<td class='small_text'>0</td>";
	$HTML_output.="<td class='small_text'>0</td>";
	$HTML_output.="<td class='small_text'>$avg_talk_His</td>";
	### Removed 6/14, may re-instate if "taken/lost" becomes necessary/desired - add magnifying glass
	### $HTML_output.="<td class='small_text'>&nbsp;</td>";
	### $HTML_output.="<td class='small_text'>&nbsp;</td>";
	$HTML_output.="</tr>\n";

	$CSV_output["queue_sessions_view"].="\"".$queue_names["$campaign"]."\",\"$total_duration_His\",\"$total_talk_His\",\"$total_idle_His\",\"$total_billable_His\",\"$total_nonbillable_His\",\"$total_sales\",\"$sph\",\"$total_contacts\",\"$ccph\",\"$total_human_answered\",\"$haph\",\"$total_calls\",\"$ha_conv\",\"$cc_conv\",\"0\",\"0\",\"$avg_talk_His\"\n";
	}
$HTML_output.="<tr class='export_row'>\n";
$HTML_output.="<td class='export_row_cell' colspan='18'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('AGENTS', 'queue_sessions_view')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
####################################

/* Removed 5/19
$HTML_output.="<BR><h2 class='rpt_header'>Agent Performance by ACD Group</h2>"; # Inbound
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr>\n";
$HTML_output.="<th>Level</th>";
$HTML_output.="<th>Agent</th>";
$HTML_output.="<th>Login</th>";
$HTML_output.="<th>Dur.</th>";
$HTML_output.="<th>Calls in</th>";
$HTML_output.="<th>Calls out</th>";
$HTML_output.="<th>Tot calls</th>";
$HTML_output.="<th>Avg in</th>";
$HTML_output.="<th>Avg out</th>";
$HTML_output.="<th>Avg dur</th>";
$HTML_output.="<th>Avail.</th>";
$HTML_output.="<th>On call in</th>";
$HTML_output.="<th>On call out</th>";
$HTML_output.="<th>Pau Bill</th>";
$HTML_output.="<th>Pau N/Bill</th>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";
*/

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}


if ($download_rpt)
	{
	$data_to_download=$CSV_output["$download_rpt"];

	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "AGENTS_RPT_".$download_rpt."_".$FILE_TIME.".csv";
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
