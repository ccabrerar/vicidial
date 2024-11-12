<?php
# VERM_UNANSWERED_rpt.inc - Vicidial Enhanced Reporting unanswered call report
#
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGELOG:
# 220825-1614 - First build
# 230106-1506 - Uniqueid array modification to speed up page loading, added page flushing
# 240801-1130 - Code updates for PHP8 compatibility
#

if (isset($_GET["sort_agents_on_queue"]))			{$sort_agents_on_queue=$_GET["sort_agents_on_queue"];}
	elseif (isset($_POST["sort_agents_on_queue"]))	{$sort_agents_on_queue=$_POST["sort_agents_on_queue"];}

if ($non_latin < 1)
	{
	$sort_agents_on_queue = preg_replace('/[^\s\-_0-9a-zA-Z]/','',$sort_agents_on_queue);
	}
else
	{
	$sort_agents_on_queue = preg_replace('/[^\s\-_0-9\p{L}]/u','',$sort_agents_on_queue);
	}

## NOTE TO SELF - DESPITE THIS BEING UNANSWERED CALLS, THE BELOW STATEMENT INCLUDES NON-VDAD/VDCL CALLS BECAUSE WE NEED THEM IN THE SLA REPORT
$calls_stmt="select call_date, call_date+INTERVAL (length_in_sec) SECOND as end_date, if(call_date+INTERVAL (length_in_sec) SECOND<='$end_date $end_time', '1', '0') as within_interval, campaign_id, user, length_in_sec, '0' as queue_seconds, 'O' as direction, '1' as queue_position, term_reason, comments, status, uniqueid From vicidial_log $vicidial_log_SQL $exc_addtl_statuses UNION select call_date, call_date+INTERVAL (length_in_sec) SECOND as end_date, if(call_date+INTERVAL (length_in_sec) SECOND<='$end_date $end_time', '1', '0') as within_interval, campaign_id, user, if(comments='EMAIL', length_in_sec, length_in_sec-queue_seconds) as length_in_sec, if(comments='EMAIL', '0', queue_seconds) as queue_seconds, 'I' as direction, queue_position, term_reason, comments, status, uniqueid From vicidial_closer_log $vicidial_closer_log_SQL $exc_addtl_statuses"; #  and (user!='VDCL' $or_NANQUE_clause)

if ($DB) {$HTML_output.="<B>$calls_stmt</B>";}

$calls_rslt=mysqli_query($link, $calls_stmt);

### VARIABLES ###
$total_calls=0;
$total_queue_stats=array();

$total_answered_calls=0;
$max_call_length=0; 
$min_call_length=1000000;
$total_call_length=0;
$max_wait_time=0;
$min_wait_time=1000000;
$total_wait_time=0;
$total_position=0;
$max_position=0;
$min_position=1000000;
$agent_call_counts=array(); 
$transferred_counts=array(); 
$agent_call_lengths=array();
$queue_stats=array(); # campaign id breakdown BY ANSWERED CALLS
$term_reasons=array(); # ANSWERED CALLS ONLY
$call_directions=array(); # ANSWERED CALLS 
$call_stints=array(); # ANSWERED CALLS
$agent_events=array(); # ANSWERED CALLS
$queue_positions=array("Untracked" => 0); # ANSWERED CALLS

$total_unanswered_calls=0;
$max_unanswered_call_length=0; 
$min_unanswered_call_length=1000000;
$max_unanswered_wait_time=0;
$min_unanswered_wait_time=1000000;
$total_unanswered_wait_time=0;
$total_unanswered_position=0;
$max_unanswered_position=0;
$min_unanswered_position=1000000;
$unanswered_queue_stats=array(); # campaign id breakdown BY UNANSWERED CALLS
$unanswered_term_reasons=array(); # UNANSWERED CALLS ONLY
$unanswered_calls_by_agent=array();
$unanswered_call_directions=array(); # UNANSWERED CALLS 
$unanswered_call_stints=array(); # UNANSWERED CALLS
$unanswered_agent_events=array(); # UNANSWERED CALLS
$unanswered_queue_positions=array("Untracked" => 0); # UNANSWERED CALLS
$coverage="100%";
$coverage_wi="100%";
#################

### WITHIN-INTERVAL VARS ###
$total_calls_wi=0;

$total_answered_calls_wi=0;
$max_call_length_wi=0; 
$min_call_length_wi=1000000;
$total_call_length_wi=0;
$max_wait_time_wi=0;
$min_wait_time_wi=1000000;
$total_wait_time_wi=0;
$total_position_wi=0;
$max_position_wi=0;
$min_position_wi=1000000;

$total_unanswered_calls_wi=0;
$max_unanswered_call_length_wi=0; 
$min_unanswered_call_length_wi=1000000;
$max_unanswered_wait_time_wi=0;
$min_unanswered_wait_time_wi=1000000;
$total_unanswered_wait_time_wi=0;
$total_unanswered_position_wi=0;
$max_unanswered_position_wi=0;
$min_unanswered_position_wi=1000000;
$agent_stats_wi=array();
############################

$uniqueid_array=array();
$unans_uniqueid_array=array(); # For implosion in SQL queries
$unanswered_uniqueid_array=array(); # Associative, for checking

while ($calls_row=mysqli_fetch_array($calls_rslt)) 
	{
	$total_calls++;
	if ($calls_row["direction"]=="O")
		{
		$total_outbound_calls++;
		}
	else
		{
		$total_inbound_calls++;
		}
	# print "$total_call_length + $calls_row[length_in_sec] = ";
	$total_call_length+=$calls_row["length_in_sec"];
	# print "$total_call_length<BR>\n";
	$total_wait_time+=$calls_row["queue_seconds"];
	$stint=1;
	$campaign_name=$queue_names["$calls_row[campaign_id]"];
	
	if (preg_match('/VDAD|VDCL/', $calls_row["user"])) 
		{
#		if (preg_match('/^DROP$|TIMEOT|WAITTO|NANQUE/', $calls_row["status"]))
#			{
			$total_unanswered_calls++;
			$total_unanswered_call_length+=$calls_row["length_in_sec"];
			# array_push($unanswered_uniqueid_array, $calls_row["uniqueid"]);
			array_push($unans_uniqueid_array, $calls_row["uniqueid"]);
			$unanswered_uniqueid_array["$calls_row[uniqueid]"]="Y";
			$total_unanswered_wait_time+=$calls_row["queue_seconds"];
			if (preg_match('/^DROP$/', $calls_row["status"])) {$unanswered_coverage_calls++;} 
			if ($calls_row["direction"]=="O")
				{
				$unanswered_outbound_calls++;
				}
			else
				{
				$unanswered_inbound_calls++;
				}
			unset($call_was_answered);

			$unanswered_term_reasons["$calls_row[term_reason]"]++;

			$unanswered_queue_stats["$calls_row[campaign_id]"]++;

			# Have to do this because numeric IDs get fucked up when sorted further down.
			$total_queue_stats["$campaign_name"]["calls"]++;
			$total_queue_stats["$campaign_name"]["answered"]+=0;
			$total_queue_stats["$campaign_name"]["unanswered"]++;
			$total_queue_stats["$campaign_name"]["call_time"]+=$calls_row["length_in_sec"];
			$total_queue_stats["$campaign_name"]["wait_time"]+=$calls_row["queue_seconds"];

			$unanswered_calls_by_agent["$calls_row[user]"]++;

			if ($calls_row["direction"]=="O") {$unanswered_call_directions["Outbound calls"]++;} else {$unanswered_call_directions["Inbound calls"]++;}

			$total_unanswered_position+=$calls_row["queue_position"];
			$unanswered_queue_positions["$calls_row[queue_position]"]++;
#			}
#		else
#			{
#			# DO NOTHING FOR NANQUE - dunno why but phone 4698254386 shows only under one rep despite call length
#			}
		} 
	else 
		{
		# array_push($uniqueid_array, $calls_row["uniqueid"]);
		$uniqueid_array["$calls_row[uniqueid]"]="Y";
		$total_answered_calls++;
		$answered_coverage_calls++;
		$total_answered_wait_time+=$calls_row["queue_seconds"];
		if ($calls_row["direction"]=="O")
			{
			$answered_outbound_calls++;
			}
		else
			{
			$answered_inbound_calls++;
			}
		$call_was_answered=1;

		$transferred_counts["$transfer_status"]++;

		# Use full name here so we can sort in the 'Agents on queue' section painlessly.
		$agent_full_name=$fullname_info["$calls_row[user]"];
		$agent_call_counts["$agent_full_name"]++;
		$agent_call_lengths["$agent_full_name"]+=$calls_row["length_in_sec"];

		# Events?
		$agent_events["$calls_row[user]"][0]=0;
		$agent_events["$calls_row[user]"][1]=0;

		$term_reasons["$calls_row[term_reason]"]++;

		$queue_stats["$calls_row[campaign_id]"]++;

		$total_queue_stats["$campaign_name"]["calls"]++;
		$total_queue_stats["$campaign_name"]["answered"]++;
		$total_queue_stats["$campaign_name"]["unanswered"]+=0;
		$total_queue_stats["$campaign_name"]["call_time"]+=$calls_row["length_in_sec"];
		$total_queue_stats["$campaign_name"]["wait_time"]+=$calls_row["queue_seconds"];

		if ($calls_row["direction"]=="O") {$call_directions["Outbound calls"]++;} else {$call_directions["Inbound calls"]++;}

		$call_stints["$stint"]++;

		$total_answered_position+=$calls_row["queue_position"];
		$queue_positions["$calls_row[queue_position]"]++;
		}

	if ($calls_row["length_in_sec"]>$max_call_length && $call_was_answered) {$max_call_length=$calls_row["length_in_sec"];}
	if ($calls_row["length_in_sec"]<$min_call_length && $call_was_answered) {$min_call_length=$calls_row["length_in_sec"];}

	if ($calls_row["queue_seconds"]>$max_wait_time && $call_was_answered) {$max_wait_time=$calls_row["queue_seconds"];}
	if ($calls_row["queue_seconds"]<$min_wait_time && $call_was_answered) {$min_wait_time=$calls_row["queue_seconds"];}

	if ($calls_row["queue_position"]>$max_position && $call_was_answered) {$max_position=$calls_row["queue_position"];}
	if ($calls_row["queue_position"]<$min_position && $call_was_answered) {$min_position=$calls_row["queue_position"];}

	# Unanswered - don't need call length
	# if ($calls_row["length_in_sec"]>$max_unanswered_call_length && !$call_was_answered) {$max_unanswered_call_length=$calls_row["length_in_sec"];}
	# if ($calls_row["length_in_sec"]<$min_unanswered_call_length && !$call_was_answered) {$min_unanswered_call_length=$calls_row["length_in_sec"];}

	if ($calls_row["queue_seconds"]>$max_unanswered_wait_time && !$call_was_answered) {$max_unanswered_wait_time=$calls_row["queue_seconds"];}
	if ($calls_row["queue_seconds"]<$min_unanswered_wait_time && !$call_was_answered) {$min_unanswered_wait_time=$calls_row["queue_seconds"];}

	if ($calls_row["queue_position"]>$max_unanswered_position && !$call_was_answered) {$max_unanswered_position=$calls_row["queue_position"];}
	if ($calls_row["queue_position"]<$min_unanswered_position && !$call_was_answered) {$min_unanswered_position=$calls_row["queue_position"];}

	### FILTER FOR FULLY WITHIN-INTERVAL CALLS, SHOULD BE EXACTLY THE SAME (FOR NOW)
	if ($calls_row["within_interval"])
		{
		$total_calls_wi++;
		$total_call_length_wi+=$calls_row["length_in_sec"];
		$total_wait_time_wi+=$calls_row["queue_seconds"];
		$total_position_wi+=$calls_row["queue_position"];
		
		if (preg_match('/VDAD|VDCL/', $calls_row["user"])) #  && preg_match('/^DROP$|TIMEOT|WAITTO/', $calls_row["status"])
			{
			$total_unanswered_calls_wi++;
			$total_unanswered_position_wi+=$calls_row["queue_position"];
			$total_unanswered_wait_time_wi+=$calls_row["queue_seconds"];
			# if (preg_match('/^DROP$/', $calls_row["status"])) {$unanswered_coverage_calls_wi++;}
			} 
		else 
			{
			$total_answered_calls_wi++;
			$total_answered_position_wi+=$calls_row["queue_position"];
			$total_answered_wait_time_wi+=$calls_row["queue_seconds"];
			$answered_coverage_calls_wi++;
			}

		if ($calls_row["length_in_sec"]>$max_call_length_wi && $call_was_answered) {$max_call_length_wi=$calls_row["length_in_sec"];}
		if ($calls_row["length_in_sec"]<$min_call_length_wi && $call_was_answered) {$min_call_length_wi=$calls_row["length_in_sec"];}

		if ($calls_row["queue_seconds"]>$max_wait_time_wi && $call_was_answered) {$max_wait_time_wi=$calls_row["queue_seconds"];}
		if ($calls_row["queue_seconds"]<$min_wait_time_wi && $call_was_answered) {$min_wait_time_wi=$calls_row["queue_seconds"];}

		if ($calls_row["queue_position"]>$max_position_wi) {$max_position_wi=$calls_row["queue_position"];}
		if ($calls_row["queue_position"]<$min_position_wi) {$min_position_wi=$calls_row["queue_position"];}

		# Unanswered
		# if ($calls_row["length_in_sec"]>$max_unanswered_call_length_wi && !$call_was_answered) {$max_unanswered_call_length_wi=$calls_row["length_in_sec"];}
		# if ($calls_row["length_in_sec"]<$min_unanswered_call_length_wi && !$call_was_answered) {$min_unanswered_call_length_wi=$calls_row["length_in_sec"];}

		if ($calls_row["queue_seconds"]>$max_unanswered_wait_time_wi && !$call_was_answered) {$max_unanswered_wait_time_wi=$calls_row["queue_seconds"];}
		if ($calls_row["queue_seconds"]<$min_unanswered_wait_time_wi && !$call_was_answered) {$min_unanswered_wait_time_wi=$calls_row["queue_seconds"];}

		if ($calls_row["queue_position"]>$max_unanswered_position_wi && !$call_was_answered) {$max_unanswered_position_wi=$calls_row["queue_position"];}
		if ($calls_row["queue_position"]<$min_unanswered_position_wi && !$call_was_answered) {$min_unanswered_position_wi=$calls_row["queue_position"];}
		}
	}

$average_call_length=sprintf("%.1f", MathZDC($total_call_length, $total_answered_calls)); # Total calls instead?
$average_wait_time=sprintf("%.1f", MathZDC($total_wait_time, $total_answered_calls)); # Total calls instead?
$average_position=sprintf("%.1f", MathZDC($total_answered_position, $total_answered_calls));
$total_call_length_fmt=sprintf("%.1f", ($total_call_length/3600));
$total_wait_time_fmt=sprintf("%.1f", ($total_wait_time/3600));
$answered_coverage=sprintf("%.1f", MathZDC((100*$answered_coverage_calls), $total_answered_calls));

$average_call_length_wi=sprintf("%.1f", MathZDC($total_call_length_wi, $total_answered_calls_wi)); # Total calls instead?
$average_wait_time_wi=sprintf("%.1f", MathZDC($total_wait_time_wi, $total_answered_calls_wi)); # Total calls instead?
$average_position_wi=sprintf("%.1f", MathZDC($total_answered_position_wi, $total_answered_calls_wi));
$total_call_length_fmt_wi=sprintf("%.1f", ($total_call_length_wi/3600));
$total_wait_time_fmt_wi=sprintf("%.1f", ($total_wait_time_wi/3600));
$answered_coverage_wi=sprintf("%.1f", MathZDC((100*$answered_coverage_calls_wi), $total_answered_calls_wi));

# Unanswered 
$average_unanswered_call_length=sprintf("%.1f", MathZDC($total_unanswered_call_length, $total_unanswered_calls)); # Total calls instead?
$average_unanswered_wait_time=sprintf("%.1f", MathZDC($total_unanswered_wait_time, $total_unanswered_calls)); # Total calls instead?
$average_unanswered_position=sprintf("%.1f", MathZDC($total_unanswered_position, $total_unanswered_calls));
$total_unanswered_call_length_fmt=sprintf("%.1f", ($total_unanswered_call_length/3600));
$total_unanswered_wait_time_fmt=sprintf("%.1f", ($total_unanswered_wait_time/3600));
$unanswered_coverage=sprintf("%.1f", MathZDC((100*$unanswered_coverage_calls), $total_unanswered_calls));

$average_unanswered_call_length_wi=sprintf("%.1f", MathZDC($total_unanswered_call_length_wi, $total_unanswered_calls_wi)); # Total calls instead?
$average_unanswered_wait_time_wi=sprintf("%.1f", MathZDC($total_unanswered_wait_time_wi, $total_unanswered_calls_wi)); # Total calls instead?
$average_unanswered_position_wi=sprintf("%.1f", MathZDC($total_unanswered_position_wi, $total_unanswered_calls_wi));
$total_unanswered_call_length_fmt_wi=sprintf("%.1f", ($total_unanswered_call_length_wi/3600));
$total_unanswered_wait_time_fmt_wi=sprintf("%.1f", ($total_unanswered_wait_time_wi/3600));
$unanswered_coverage_wi=sprintf("%.1f", MathZDC((100*$unanswered_coverage_calls_wi), $total_unanswered_calls_wi));

# Wipe unadjusted variables (i.e. no records)
$min_call_length=($min_call_length==1000000 ? "0" : $min_call_length);
$min_wait_time=($min_wait_time==1000000 ? "0" : $min_wait_time);
$min_position=($min_position==1000000 ? "0" : $min_position);
$min_unanswered_call_length=($min_unanswered_call_length==1000000 ? "0" : $min_unanswered_call_length);
$min_unanswered_wait_time=($min_unanswered_wait_time==1000000 ? "0" : $min_unanswered_wait_time);
$min_unanswered_position=($min_unanswered_position==1000000 ? "0" : $min_unanswered_position);

$min_call_length_wi=($min_call_length_wi==1000000 ? "0" : $min_call_length_wi);
$min_wait_time_wi=($min_wait_time_wi==1000000 ? "0" : $min_wait_time_wi);
$min_position_wi=($min_position_wi==1000000 ? "0" : $min_position_wi);
$min_unanswered_call_length_wi=($min_unanswered_call_length_wi==1000000 ? "0" : $min_unanswered_call_length_wi);
$min_unanswered_wait_time_wi=($min_unanswered_wait_time_wi==1000000 ? "0" : $min_unanswered_wait_time_wi);
$min_unanswered_position_wi=($min_unanswered_position_wi==1000000 ? "0" : $min_unanswered_position_wi);


$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("All calls")." $NWB#VERM_UNANSWERED-all_calls$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>"._QXZ("All calls").":</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="</tr>";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("N. calls unanswered").":</td>";
$HTML_output.="<td>".$total_unanswered_calls."</td>";
$HTML_output.="</tr>";
$HTML_output.="<td>"._QXZ("Average call waiting time before disconnection").": $NWB#VERM_UNANSWERED-all_calls_waiting_time$NWE</td>";
$HTML_output.="<td>".$average_unanswered_wait_time." s</td>";
$HTML_output.="</tr>";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Min waiting time before disconnection").":</td>";
$HTML_output.="<td>".($min_unanswered_wait_time>=3600 ? floor($min_unanswered_wait_time/3600).":" : "").gmdate("i:s", $min_unanswered_wait_time)."</td>";
$HTML_output.="</tr>";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Max waiting time before disconnection").":</td>";
$HTML_output.="<td>".($max_unanswered_wait_time>=3600 ? floor($max_unanswered_wait_time/3600).":" : "").gmdate("i:s", $max_unanswered_wait_time)."</td>";
$HTML_output.="</tr>";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Total waiting time before disconnection").":</td>";
$HTML_output.="<td>".$total_unanswered_wait_time_fmt." H</td>";
$HTML_output.="</tr>";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Average initial position").": $NWB#VERM_UNANSWERED-all_calls_position$NWE</td>";
$HTML_output.="<td>".$average_unanswered_position."</td>";
$HTML_output.="</tr>";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Min initial position").":</td>";
$HTML_output.="<td>".$min_unanswered_position."</td>";
$HTML_output.="</tr>";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Max initial position").":</td>";
$HTML_output.="<td>".$max_unanswered_position."</td>";
$HTML_output.="</tr>";

### Commented out 5/19/22 - not needed
### $HTML_output.="<tr>";
### $HTML_output.="<td>Coverage: $NWB#VERM_UNANSWERED-coverage$NWE</td>";
### $HTML_output.="<td>".$unanswered_coverage." %</td>";
### $HTML_output.="</tr>";
### $HTML_output.="<tr>";
### $HTML_output.="<td>Average queue position at disconnection: $NWB#VERM_UNANSWERED-position$NWE</td>";
### $HTML_output.="<td>".$average_unanswered_position."</td>";
### $HTML_output.="</tr>";
### $HTML_output.="<tr>";
### $HTML_output.="<td>Min queue position at disconnection:</td>";
### $HTML_output.="<td>".$min_unanswered_position."</td>";
### $HTML_output.="</tr>";
### $HTML_output.="<tr>";
### $HTML_output.="<td>Max queue position at disconnection:</td>";
### $HTML_output.="<td>".$max_unanswered_position."</td>";
### $HTML_output.="</tr>";

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td colspan='2' class='export_row_cell'>"._QXZ("Export as")."...<a href=\"".$PHP_SELF."?download_rpt=all_calls\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='2' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('UNANSWERED', 'all_calls')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";

$CSV_output["all_calls"]="\""._QXZ("All calls")."\",\"\"\n";
$CSV_output["all_calls"].="\""._QXZ("N. calls answered by operators").":\",\"$total_unanswered_calls\"\n";
# $CSV_output["all_calls"].="\""._QXZ("Average call length").":\",\"$average_unanswered_call_length s.\"\n";
# $CSV_output["all_calls"].="\""._QXZ("Min call length").":\",\"".($min_unanswered_call_length>=3600 ? floor($min_unanswered_call_length/3600).":" : "").gmdate("i:s", $min_unanswered_call_length)."\"\n";
# $CSV_output["all_calls"].="\""._QXZ("Max call length").":\",\"".($max_unanswered_call_length>=3600 ? floor($max_unanswered_call_length/3600).":" : "").gmdate("i:s", $max_unanswered_call_length)."\"\n";
# $CSV_output["all_calls"].="\""._QXZ("Total call length").":\",\"".$total_unanswered_call_length_fmt." H\"\n";
$CSV_output["all_calls"].="\""._QXZ("Average call waiting time").":\",\"".$average_unanswered_wait_time." s\"\n";
$CSV_output["all_calls"].="\""._QXZ("Min waiting time").":\",\"".($min_unanswered_wait_time>=3600 ? floor($min_unanswered_wait_time/3600).":" : "").gmdate("i:s", $min_unanswered_wait_time)."\"\n";
$CSV_output["all_calls"].="\""._QXZ("Max waiting time").":\",\"".($max_unanswered_wait_time>=3600 ? floor($max_unanswered_wait_time/3600).":" : "").gmdate("i:s", $max_unanswered_wait_time)."\"\n";
$CSV_output["all_calls"].="\""._QXZ("Total waiting time").":\",\"".$total_unanswered_wait_time_fmt." H\"\n";
$CSV_output["all_calls"].="\""._QXZ("Average initial position").":\",\"".$average_unanswered_position."\"\n";
$CSV_output["all_calls"].="\""._QXZ("Min initial position").":\",\"".$min_unanswered_position."\"\n";
$CSV_output["all_calls"].="\""._QXZ("Max initial position").":\",\"".$max_unanswered_position."\"\n";
if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

### Commented out 5/19/22 - not needed
### $CSV_output["all_calls"].="\""._QXZ("Coverage").":\",\"".$coverage." H\"\n";
######################

$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Calls fully within the given time interval")."</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>"._QXZ("All calls").": $NWB#VERM_UNANSWERED-all_calls_wi$NWE</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="</tr>";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("N. calls unanswered").":</td>";
$HTML_output.="<td>".$total_unanswered_calls_wi."</td>";
$HTML_output.="</tr>";
$HTML_output.="<td>"._QXZ("Average call waiting time before disconnection").": $NWB#VERM_UNANSWERED-all_calls_waiting_time_wi$NWE</td>";
$HTML_output.="<td>".$average_unanswered_wait_time_wi." s</td>";
$HTML_output.="</tr>";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Min waiting time before disconnection").":</td>";
$HTML_output.="<td>".($min_unanswered_wait_time_wi>=3600 ? floor($min_unanswered_wait_time_wi/3600).":" : "").gmdate("i:s", $min_unanswered_wait_time_wi)."</td>";
$HTML_output.="</tr>";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Max waiting time before disconnection").":</td>";
$HTML_output.="<td>".($max_unanswered_wait_time_wi>=3600 ? floor($max_unanswered_wait_time_wi/3600).":" : "").gmdate("i:s", $max_unanswered_wait_time_wi)."</td>";
$HTML_output.="</tr>";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Total waiting time before disconnection").":</td>";
$HTML_output.="<td>".$total_unanswered_wait_time_fmt_wi." H</td>";
$HTML_output.="</tr>";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Average initial position").": $NWB#VERM_UNANSWERED-all_calls_position_wi$NWE</td>";
$HTML_output.="<td>".$average_unanswered_position_wi."</td>";
$HTML_output.="</tr>";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Min initial position").":</td>";
$HTML_output.="<td>".$min_unanswered_position_wi."</td>";
$HTML_output.="</tr>";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Max initial position").":</td>";
$HTML_output.="<td>".$max_unanswered_position."</td>";
$HTML_output.="</tr>";

### Commented out 5/19/22 - not needed
### $HTML_output.="<tr>";
### $HTML_output.="<td>Coverage: $NWB#VERM_UNANSWERED-coverage_wi$NWE</td>";
### $HTML_output.="<td>".$unanswered_coverage_wi." %</td>";
### $HTML_output.="</tr>";
### $HTML_output.="<tr>";
### $HTML_output.="<td>Average queue position at disconnection: $NWB#VERM_UNANSWERED-position_wi$NWE</td>";
### $HTML_output.="<td>".$average_unanswered_position_wi."</td>";
### $HTML_output.="</tr>";
### $HTML_output.="<tr>";
### $HTML_output.="<td>Min queue position at disconnection:</td>";
### $HTML_output.="<td>".$min_unanswered_position_wi."</td>";
### $HTML_output.="</tr>";
### $HTML_output.="<tr>";
### $HTML_output.="<td>Max queue position at disconnection:</td>";
### $HTML_output.="<td>".$max_unanswered_position_wi."</td>";
### $HTML_output.="</tr>";

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td colspan='2' class='export_row_cell'>"._QXZ("Export as")."...<a href=\"".$PHP_SELF."?download_rpt=all_calls\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='2' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('UNANSWERED', 'all_calls_wi')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";

$CSV_output["all_calls_wi"]="\""._QXZ("All unanswered calls fully within the given time interval")."\",\"\"\n";
$CSV_output["all_calls_wi"].="\""._QXZ("N. calls unanswered by operators").":\",\"$total_unanswered_calls_wi\"\n";
# $CSV_output["all_calls_wi"].="\""._QXZ("Average call length").":\",\"$average_unanswered_call_length_wi s.\"\n";
# $CSV_output["all_calls_wi"].="\""._QXZ("Min call length").":\",\"".($min_unanswered_call_length_wi>=3600 ? floor($min_unanswered_call_length_wi/3600).":" : "").gmdate("i:s", $min_unanswered_call_length_wi)."\"\n";
# $CSV_output["all_calls_wi"].="\""._QXZ("Max call length").":\",\"".($max_unanswered_call_length_wi>=3600 ? floor($max_unanswered_call_length_wi/3600).":" : "").gmdate("i:s", $max_unanswered_call_length_wi)."\"\n";
# $CSV_output["all_calls_wi"].="\""._QXZ("Total call length").":\",\"".$total_unanswered_call_length_fmt_wi." H\"\n";
$CSV_output["all_calls_wi"].="\""._QXZ("Average call waiting time").":\",\"".$average_unanswered_wait_time_wi." s\"\n";
$CSV_output["all_calls_wi"].="\""._QXZ("Min waiting time").":\",\"".($min_unanswered_wait_time_wi>=3600 ? floor($min_unanswered_wait_time_wi/3600).":" : "").gmdate("i:s", $min_unanswered_wait_time_wi)."\"\n";
$CSV_output["all_calls_wi"].="\""._QXZ("Max waiting time").":\",\"".($max_unanswered_wait_time_wi>=3600 ? floor($max_unanswered_wait_time_wi/3600).":" : "").gmdate("i:s", $max_unanswered_wait_time_wi)."\"\n";
$CSV_output["all_calls_wi"].="\""._QXZ("Total waiting time").":\",\"".$total_unanswered_wait_time_fmt_wi." H\"\n";
$CSV_output["all_calls_wi"].="\""._QXZ("Average initial position").":\",\"".$average_unanswered_position_wi."\"\n";
$CSV_output["all_calls_wi"].="\""._QXZ("Min initial position").":\",\"".$min_unanswered_position_wi."\"\n";
$CSV_output["all_calls_wi"].="\""._QXZ("Max initial position").":\",\"".$max_unanswered_position_wi."\"\n";
if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

### Commented out 5/19/22 - not needed
### $CSV_output["all_calls_wi"].="\""._QXZ("Coverage").":\",\"".$coverage_wi." H\"\n";
######################

/*
#### AGENTS ON QUEUE ####
switch ($sort_agents_on_queue)
	{
		case "ncalls":
			$ncalls_sort_value="ncalls_desc";
			$agent_sort_value="agent";
			$ncalls_char="&#8593;";
			$agent_char="";
			asort($agent_call_counts);
			break;
		case "ncalls_desc":
			$ncalls_sort_value="ncalls";
			$agent_sort_value="agent";
			$ncalls_char="&#8595;";
			$agent_char="";
			arsort($agent_call_counts);
			break;
		case "agent":
			$ncalls_sort_value="ncalls";
			$agent_sort_value="agent_desc";
			$ncalls_char="";
			$agent_char="&#8593;";
			ksort($agent_call_counts, SORT_NATURAL | SORT_FLAG_CASE);
			break;
		case "agent_desc":
			$ncalls_sort_value="ncalls";
			$agent_sort_value="agent";
			$ncalls_char="";
			$agent_char="&#8595;";
			krsort($agent_call_counts, SORT_NATURAL | SORT_FLAG_CASE);
			break;
		default:
			$ncalls_sort_value="ncalls";
			$agent_sort_value="agent";
			$ncalls_char="";
			$agent_char="";
			ksort($agent_call_counts, SORT_NATURAL | SORT_FLAG_CASE);
			break;
	}

$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Agents on queue").": $NWB#VERM_UNANSWERED-calls_by_queue$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table border='0' cellpadding='0' cellspacing='0'>";
$HTML_output.="<tr><td width='60%'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th><a class='header_link' name='aoq_sort_agent' id='aoq_sort_agent' href='".$PHP_SELF."?sort_agents_on_queue=".$agent_sort_value."#aoq_sort_agent'>Agent ".$agent_char."</a></th>";
$HTML_output.="<th><a class='header_link' name='aoq_sort_ncalls' id='aoq_sort_ncalls' href='".$PHP_SELF."?sort_agents_on_queue=".$ncalls_sort_value."#aoq_sort_ncalls'>N. Calls ".$ncalls_char."</a></th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="<th>Total call time</th>";
$HTML_output.="<th>Average call time</th>";
$HTML_output.="</tr>";

$CSV_output["agents_on_queue"]="\"Agents on queue\"\n";
$CSV_output["agents_on_queue"].="\"Agent\",\"N. Calls\",\"\",\"...\",\"Total call time\",\"Average call time\"\n";

$highest_agent_call_count=max($agent_call_counts);

$agents_on_queue_graph_labels="[";
$agents_on_queue_graph_data="[";
$agents_on_queue_graph_bgcolor="["; # SAME FOR hoverBackgroundColor
$agents_on_queue_graph_hovborder="[";
$x=0;
foreach ($agent_call_counts as $agentName => $value)
	{
	$call_count_percent=sprintf("%.1f", (100*$value)/$total_answered_calls);
	$agent_total_call_length=$agent_call_lengths["$agentName"];
	$agent_total_call_length_fmt=($agent_total_call_length>=3600 ? floor($agent_total_call_length/3600).":" : "").gmdate("i:s", $agent_total_call_length);
	$agent_avg_call_length=round($agent_total_call_length/$value);
	$agent_avg_call_length_fmt=($agent_avg_call_length>=3600 ? floor($agent_avg_call_length/3600).":" : "").gmdate("i:s", $agent_avg_call_length);

	$HTML_output.="<tr>";
	$HTML_output.="<td>".$agentName."</td>";
	$HTML_output.="<td>".$value."</td>";
	$HTML_output.="<td>".$call_count_percent." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".((100*$value)/$highest_agent_call_count)."%'></td>";
	$HTML_output.="<td>".$agent_total_call_length_fmt."</td>";
	$HTML_output.="<td>".$agent_avg_call_length_fmt."</td>";
	$HTML_output.="</tr>";

	$CSV_output["agents_on_queue"].="\"".$agentName."\",\"".$value."\",\"".$call_count_percent." %\",\"\",\"".$agent_total_call_length_fmt."\",\"".$agent_avg_call_length_fmt."\"\n";

	$agents_on_queue_graph_labels.="\"$agentName\",";
	$agents_on_queue_graph_data.="\"$value\",";
	$agents_on_queue_graph_bgcolor.="\"".$background_colors[$x]."\",";
	$agents_on_queue_graph_hovborder.="\"".$border_colors[$x]."\",";

	$x++;
	}
$agents_on_queue_graph_labels=preg_replace('/,$/', '', $agents_on_queue_graph_labels)."]";
$agents_on_queue_graph_data=preg_replace('/,$/', '', $agents_on_queue_graph_data)."]";
$agents_on_queue_graph_bgcolor=preg_replace('/,$/', '', $agents_on_queue_graph_bgcolor)."]";
$agents_on_queue_graph_hovborder=preg_replace('/,$/', '', $agents_on_queue_graph_hovborder)."]";

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td colspan='6' class='export_row_cell'>"._QXZ("Export as")."...<a href=\"".$PHP_SELF."?download_rpt=agents_on_queue&sort_agents_on_queue=".$sort_agents_on_queue."\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='6' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('UNANSWERED', 'agents_on_queue')\" title=\"Export as a CSV file\" value='CSV'><input type='hidden' name='sort_agents_on_queue' value='$sort_agents_on_queue'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
$HTML_output.="<div class=\"chart-container\" style=\"height:80vh; width:80vh\"><canvas id='agents_on_queue_display' role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";
######################

#### SERVICE LEVEL ####
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Service Level Agreement (inbound calls only)").": $NWB#VERM_UNANSWERED-inclusive_SLA$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table border='0' cellpadding='0' cellspacing='0'>";
$HTML_output.="<tr><td width='60%'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>Answer</th>";
$HTML_output.="<th>N. Calls</th>";
$HTML_output.="<th>Delta</th>";
$HTML_output.="<th>Percent</th>";
$HTML_output.="<th>Of Offered</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="</tr>";

$CSV_output["service_level_agreement"]="\"Service Level Agreement (inbound calls only)\"\n";
$CSV_output["service_level_agreement"].="\"Answer\",\"N. Calls\",\"Delta\",\"Percent\",\"Of Offered\",\"...\"\n";

# $SLA_query is in VERM_global_vars.inc
$svc_lvl_stmt="select $SLA_query from vicidial_closer_log $vicidial_closer_log_SQL and (user!='VDCL' $or_NANQUE_clause)"; #  and campaign_id in ($ingroup_str) and user in ('VDCL', $user_str)
if ($DB) {$HTML_output.="<B>$svc_lvl_stmt</B>";}
$svc_lvl_rslt=mysqli_query($link, $svc_lvl_stmt);
$svc_lvl_row=mysqli_fetch_assoc($svc_lvl_rslt);

$service_level_agreement_graph_labels="[";
$service_level_agreement_graph_delta_data="[";
$service_level_agreement_graph_delta_bgcolor="["; # SAME FOR hoverBackgroundColor
$service_level_agreement_graph_delta_hovborder="[";
$service_level_agreement_graph_percent_data="[";
$service_level_agreement_graph_percent_bgcolor="["; # SAME FOR hoverBackgroundColor
$service_level_agreement_graph_percent_hovborder="[";

$x=0;
foreach($svc_lvl_row as $interval => $sum)
	{
	$int_name=ucwords(preg_replace("/_/", " ", $interval));
	if ($prev_int) {$delta="+ ".($sum-$prev_int);} else {$delta="&nbsp;";}
	
	$HTML_output.="<tr>";
	$HTML_output.="<td>$int_name seconds:</td>";
	$HTML_output.="<td>".$sum."</td>";
	$HTML_output.="<td align='right'><i>$delta</i>&nbsp;&nbsp;</td>";
	$HTML_output.="<td align='right'>".sprintf("%.1f", ((100*$sum)/$answered_inbound_calls))." %</td>";
	$HTML_output.="<td align='right'>".sprintf("%.1f", ((100*$sum)/$total_inbound_calls))." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".((100*$sum)/$answered_inbound_calls)."%'></td>";
	$HTML_output.="</tr>";

	$CSV_output["service_level_agreement"].="\"$int_name seconds:\",\"$sum\",\"$delta\",\"".sprintf("%.1f", ((100*$sum)/$answered_inbound_calls))." %\",\"".sprintf("%.1f", ((100*$sum)/$total_inbound_calls))." %\"\n";

	$service_level_agreement_graph_labels.="\"$int_name\",";
	$service_level_agreement_graph_delta_data.="\"".($prev_int ? ($sum-$prev_int) : "0")."\",";
	$service_level_agreement_graph_delta_bgcolor.="\"#33CCCC\",";
	$service_level_agreement_graph_delta_hovborder.="\"#66FFFF\",";
	$service_level_agreement_graph_percent_data.="\"".sprintf("%.1f", ((100*$sum)/$answered_inbound_calls))."\",";
	$service_level_agreement_graph_percent_bgcolor.="\"#FFCC66\",";
	$service_level_agreement_graph_percent_hovborder.="\"#FFFF99\",";

	$prev_int=$sum;
	$x++;
	}

$service_level_agreement_graph_labels=preg_replace('/,$/', '', $service_level_agreement_graph_labels)."]";
$service_level_agreement_graph_delta_data=preg_replace('/,$/', '', $service_level_agreement_graph_delta_data)."]";
$service_level_agreement_graph_delta_bgcolor=preg_replace('/,$/', '', $service_level_agreement_graph_delta_bgcolor)."]";
$service_level_agreement_graph_delta_hovborder=preg_replace('/,$/', '', $service_level_agreement_graph_delta_hovborder)."]";
$service_level_agreement_graph_percent_data=preg_replace('/,$/', '', $service_level_agreement_graph_percent_data)."]";
$service_level_agreement_graph_percent_bgcolor=preg_replace('/,$/', '', $service_level_agreement_graph_percent_bgcolor)."]";
$service_level_agreement_graph_percent_hovborder=preg_replace('/,$/', '', $service_level_agreement_graph_percent_hovborder)."]";

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='6'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=service_level_agreement\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='6' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('UNANSWERED', 'service_level_agreement')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
$HTML_output.="<div class=\"chart-container\" style=\"height:80vh; width:80vh\"><canvas id='service_level_agreement_display' role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";
######################
*/

#### DISCONNECTIONS ###
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Disconnection causes").": $NWB#VERM_UNANSWERED-disconnections$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table border='0' cellpadding='0' cellspacing='5'>";
$HTML_output.="<tr><td width='60%'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>"._QXZ("Cause")."</th>";
$HTML_output.="<th>"._QXZ("N. Calls")."</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="</tr>";

$CSV_output["disconnection_causes"]="\""._QXZ("Disconnection causes").":\"\n";
$CSV_output["disconnection_causes"].="\""._QXZ("Cause")."\",\""._QXZ("N. Calls")."\",\"\",\"...\"\n";

$disconnection_causes_graph_labels="[";
$disconnection_causes_graph_data="[";
$disconnection_causes_graph_bgcolor="["; # SAME FOR hoverBackgroundColor
$disconnection_causes_graph_hovborder="[";

$max_term_reason=(count($unanswered_term_reasons)>0 ? max($unanswered_term_reasons) : 0);
$x=0;
foreach ($unanswered_term_reasons as $reason => $value)
	{
	$term_reason_pct=sprintf("%.1f", (100*$value)/$total_answered_calls);
	$HTML_output.="<tr>";
	$HTML_output.="<td class='rpt_cell'>".$reason."</td>";
	$HTML_output.="<td class='rpt_cell'>".$value."</td>";
	$HTML_output.="<td class='rpt_cell'>".$term_reason_pct." %</td>";
	$HTML_output.="<td class='rpt_cell' width='200'><img src='images/shade-histo.gif' height='10' width='".(100*($value/$max_term_reason))."%'></td>";
	$HTML_output.="</tr>";

	$CSV_output["disconnection_causes"].="\"".$reason."\",\"".$value."\",\"".$term_reason_pct." %\"\n";

	$disconnection_causes_graph_labels.="\"$reason\",";
	$disconnection_causes_graph_data.="\"$value\",";
	$disconnection_causes_graph_bgcolor.="\"".$background_colors[$x]."\",";
	$disconnection_causes_graph_hovborder.="\"".$border_colors[$x]."\",";

	$x++;
	}
$disconnection_causes_graph_labels=preg_replace('/,$/', '', $disconnection_causes_graph_labels)."]";
$disconnection_causes_graph_data=preg_replace('/,$/', '', $disconnection_causes_graph_data)."]";
$disconnection_causes_graph_bgcolor=preg_replace('/,$/', '', $disconnection_causes_graph_bgcolor)."]";
$disconnection_causes_graph_hovborder=preg_replace('/,$/', '', $disconnection_causes_graph_hovborder)."]";

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='4'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=disconnection_causes\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='4' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('UNANSWERED', 'disconnection_causes')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
$HTML_output.="<div class=\"chart-container\" style=\"height:50vh; width:50vh\"><canvas id='disconnection_causes_display' role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

######################

/*
#### TRANSFERS ###
$HTML_output.="<BR><h2 class='rpt_header'>Transfers:</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>Transfer to:</th>";
$HTML_output.="<th>N. Calls</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="</tr>";

$CSV_output["transfers"]="\"Transfers:\"\n";
$CSV_output["transfers"].="\"Transfer to:\",\"N. Calls\",\"\",\"...\"\n";

$max_xfer_count=max($transferred_counts);
$x=0;
foreach ($transferred_counts as $reason => $value)
	{
	$xfer_pct=sprintf("%.1f", (100*$value)/$total_answered_calls);
	$HTML_output.="<tr>";
	$HTML_output.="<td>".$reason."</td>";
	$HTML_output.="<td>".$value."</td>";
	$HTML_output.="<td>".$xfer_pct." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(100*($value/$max_xfer_count))."%'></td>";
	$HTML_output.="</tr>";

	$CSV_output["transfers"].="\"".$reason."\",\"".$value."\",\"".$xfer_pct." %\",\"...\"\n";
	
	$x++;
	}
$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='4'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=transfers\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='4' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('UNANSWERED', 'transfers')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
######################
*/

#### UNANSWERED CALLS BY CAMPAIGN/INGROUP ###
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Unanswered calls, by campaign/ingroup").": $NWB#VERM_UNANSWERED-calls_by_queue$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table border='0' cellpadding='0' cellspacing='5'>";
$HTML_output.="<tr><td width='60%'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>"._QXZ("Campaign/ingroup")."</th>";
$HTML_output.="<th>"._QXZ("N. Calls")."</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="</tr>";

$CSV_output["unanswered_calls_by_queue"]="\""._QXZ("Unanswered calls, by queue").":\"\n";
$CSV_output["unanswered_calls_by_queue"].="\"\",\""._QXZ("Campaign/ingroup")."\",\""._QXZ("N. Calls")."\",\"\",\"...\"\n";

$unanswered_calls_by_queue_graph_labels="[";
$unanswered_calls_by_queue_graph_data="[";
$unanswered_calls_by_queue_graph_bgcolor="["; # SAME FOR hoverBackgroundColor
$unanswered_calls_by_queue_graph_hovborder="[";

$max_queue_count=(count($unanswered_queue_stats)>0 ? max($unanswered_queue_stats) : 0);
ksort($unanswered_queue_stats);
$x=0;
foreach ($unanswered_queue_stats as $queue => $value)
	{
	$queue_pct=sprintf("%.1f", (100*$value)/$total_unanswered_calls);
	$HTML_output.="<tr>";
	$HTML_output.="<td>".($SOME_IMAGE_GOES_HERE)."</td>";
	$HTML_output.="<td>".$queue_names["$queue"]."</td>";
	$HTML_output.="<td>".$value."</td>";
	$HTML_output.="<td>".$queue_pct." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(100*($value/$max_queue_count))."%'></td>";
	$HTML_output.="</tr>";
	$CSV_output["unanswered_calls_by_queue"].="\"\",\"".$queue_names["$queue"]."\",\"".$value."\",\"".$queue_pct." %\",\"\"\n";

	$unanswered_calls_by_queue_graph_labels.="\"".$queue_names["$queue"]."\",";
	$unanswered_calls_by_queue_graph_data.="\"$value\",";
	$unanswered_calls_by_queue_graph_bgcolor.="\"".$background_colors[$x]."\",";
	$unanswered_calls_by_queue_graph_hovborder.="\"".$border_colors[$x]."\",";

	$x++;
	}
$unanswered_calls_by_queue_graph_labels=preg_replace('/,$/', '', $unanswered_calls_by_queue_graph_labels)."]";
$unanswered_calls_by_queue_graph_data=preg_replace('/,$/', '', $unanswered_calls_by_queue_graph_data)."]";
$unanswered_calls_by_queue_graph_bgcolor=preg_replace('/,$/', '', $unanswered_calls_by_queue_graph_bgcolor)."]";
$unanswered_calls_by_queue_graph_hovborder=preg_replace('/,$/', '', $unanswered_calls_by_queue_graph_hovborder)."]";

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='5'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=unanswered_calls_by_queue\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='5' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('UNANSWERED', 'unanswered_calls_by_queue')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
$HTML_output.="<div class=\"chart-container\" style=\"height:40vh; width:60vh\"><canvas id='unanswered_calls_by_queue_display' role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

#####################

#### UNANSWERED CALLS BY AGENT ###
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Unanswered calls, by agent").": $NWB#VERM_UNANSWERED-calls_by_agent$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>"._QXZ("Campaign/ingroup")."</th>";
$HTML_output.="<th>"._QXZ("N. Calls")."</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="</tr>";

$CSV_output["unanswered_calls_by_agent"]="\""._QXZ("Unanswered calls, by agent").":\"\n";
$CSV_output["unanswered_calls_by_agent"].="\"\",\""._QXZ("Campaign/ingroup")."\",\""._QXZ("N. Calls")."\",\"\",\"...\"\n";

$max_queue_count=(count($unanswered_calls_by_agent)>0 ? max($unanswered_calls_by_agent) : 0);
ksort($unanswered_calls_by_agent);
$x=0;
foreach ($unanswered_calls_by_agent as $user => $value)
	{
	$queue_pct=sprintf("%.1f", (100*$value)/$total_unanswered_calls);
	$HTML_output.="<tr>";
	$HTML_output.="<td>".($SOME_IMAGE_GOES_HERE)."</td>";
	$HTML_output.="<td>".$fullname_info["$user"]."</td>";
	$HTML_output.="<td>".$value."</td>";
	$HTML_output.="<td>".$queue_pct." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(100*($value/$max_queue_count))."%'></td>";
	$HTML_output.="</tr>";
	$CSV_output["unanswered_calls_by_agent"].="\"\",\"".$fullname_info["$queue"]."\",\"".$value."\",\"".$queue_pct." %\",\"\"\n";

	$x++;
	}
$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='5'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=unanswered_calls_by_agent\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='5' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('UNANSWERED', 'unanswered_calls_by_agent')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

#####################

#### UNANSWERED CALLS - DISTRIBUTION BY LENGTH ###
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Unanswered calls - distribution by length (inbound calls only)").": $NWB#VERM_UNANSWERED-calls_by_length$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table border='0' cellpadding='0' cellspacing='0'>";
$HTML_output.="<tr><td width='60%'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>"._QXZ("Hangup")."</th>";
$HTML_output.="<th>"._QXZ("N. Calls")."</th>";
$HTML_output.="<th>"._QXZ("Delta")."</th>";
$HTML_output.="<th>"._QXZ("Percent")."</th>";
$HTML_output.="<th>"._QXZ("Of Offered")."</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="</tr>";

$CSV_output["unanswered_calls_by_distribution"]="\""._QXZ("Unanswered calls - distribution by length")."\"\n";
$CSV_output["unanswered_calls_by_distribution"].="\""._QXZ("Hangup")."\",\""._QXZ("N. Calls")."\",\""._QXZ("Delta")."\",\""._QXZ("Percent")."\",\""._QXZ("Of Offered")."\",\"...\"\n";

# $SLA_query is in VERM_global_vars.inc
$svc_lvl_stmt="select $SLA_query from vicidial_closer_log $vicidial_closer_log_SQL and (user='VDCL' $and_NANQUE_clause) $exc_addtl_statuses"; #  and campaign_id in ($ingroup_str) and user in ('VDCL', $user_str)
if ($DB) {$HTML_output.="<B>$svc_lvl_stmt</B>";}
$svc_lvl_rslt=mysqli_query($link, $svc_lvl_stmt);
#print ($svc_lvl_stmt)."\n";
$svc_lvl_row=mysqli_fetch_assoc($svc_lvl_rslt);
#print_r($svc_lvl_row);
#	{
#	print "$interval - $sum\n";
#	}

$unanswered_calls_by_distribution_graph_labels="[";
$unanswered_calls_by_distribution_graph_delta_data="[";
$unanswered_calls_by_distribution_graph_delta_bgcolor="["; # SAME FOR hoverBackgroundColor
$unanswered_calls_by_distribution_graph_delta_hovborder="[";
$unanswered_calls_by_distribution_graph_percent_data="[";
$unanswered_calls_by_distribution_graph_percent_bgcolor="["; # SAME FOR hoverBackgroundColor
$unanswered_calls_by_distribution_graph_percent_hovborder="[";

$x=0;
foreach($svc_lvl_row as $interval => $sum)
	{
	$int_name=ucwords(preg_replace("/_/", " ", $interval));
	if ($prev_int || $x>0) {$delta="+ ".($sum-$prev_int);} else {$delta="&nbsp;";}
	
	$HTML_output.="<tr>";
	$HTML_output.="<td>$int_name "._QXZ("seconds").":</td>";
	$HTML_output.="<td>".$sum."</td>";
	$HTML_output.="<td align='right'><i>$delta</i>&nbsp;&nbsp;</td>";
	$HTML_output.="<td align='right'>".sprintf("%.1f", (MathZDC((100*$sum), $unanswered_inbound_calls)))." %</td>";
	$HTML_output.="<td align='right'>".sprintf("%.1f", (MathZDC((100*$sum), $total_unanswered_calls)))." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(MathZDC((100*$sum), $unanswered_inbound_calls))."%'></td>";
	$HTML_output.="</tr>";

	$CSV_output["unanswered_calls_by_distribution"].="\"$int_name seconds:\",\"$sum\",\"$delta\",\"".sprintf("%.1f", (MathZDC((100*$sum), $unanswered_inbound_calls)))." %\",\"".sprintf("%.1f", (MathZDC((100*$sum), $total_inbound_calls)))." %\"\n";

	$unanswered_calls_by_distribution_graph_labels.="\"$int_name\",";
	$unanswered_calls_by_distribution_graph_delta_data.="\"".($prev_int || $x>0 ? ($sum-$prev_int) : "0")."\",";
	$unanswered_calls_by_distribution_graph_delta_bgcolor.="\"#33CCCC\",";
	$unanswered_calls_by_distribution_graph_delta_hovborder.="\"#66FFFF\",";
	$unanswered_calls_by_distribution_graph_percent_data.="\"".sprintf("%.1f", (MathZDC((100*$sum), $unanswered_inbound_calls)))."\",";
	$unanswered_calls_by_distribution_graph_percent_bgcolor.="\"#FFCC66\",";
	$unanswered_calls_by_distribution_graph_percent_hovborder.="\"#FFFF99\",";

	$prev_int=$sum;
	$x++;
	}

$unanswered_calls_by_distribution_graph_labels=preg_replace('/,$/', '', $unanswered_calls_by_distribution_graph_labels)."]";
$unanswered_calls_by_distribution_graph_delta_data=preg_replace('/,$/', '', $unanswered_calls_by_distribution_graph_delta_data)."]";
$unanswered_calls_by_distribution_graph_delta_bgcolor=preg_replace('/,$/', '', $unanswered_calls_by_distribution_graph_delta_bgcolor)."]";
$unanswered_calls_by_distribution_graph_delta_hovborder=preg_replace('/,$/', '', $unanswered_calls_by_distribution_graph_delta_hovborder)."]";
$unanswered_calls_by_distribution_graph_percent_data=preg_replace('/,$/', '', $unanswered_calls_by_distribution_graph_percent_data)."]";
$unanswered_calls_by_distribution_graph_percent_bgcolor=preg_replace('/,$/', '', $unanswered_calls_by_distribution_graph_percent_bgcolor)."]";
$unanswered_calls_by_distribution_graph_percent_hovborder=preg_replace('/,$/', '', $unanswered_calls_by_distribution_graph_percent_hovborder)."]";

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='6'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=unanswered_calls_by_distribution\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='6' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('UNANSWERED', 'unanswered_calls_by_distribution')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
$HTML_output.="<div class=\"chart-container\" style=\"height:80vh; width:80vh\"><canvas id='unanswered_calls_by_distribution_display' role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

###############################

#### SERVICE LEVEL ####
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Inclusive SLA (computed on both answered and unanswered calls, inbound only)").": $NWB#VERM_UNANSWERED-inclusive_SLA$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table border='0' cellpadding='0' cellspacing='0'>";
$HTML_output.="<tr><td width='60%'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>"._QXZ("Answer")."</th>";
$HTML_output.="<th>"._QXZ("N. Calls")."</th>";
$HTML_output.="<th>"._QXZ("Delta")."</th>";
$HTML_output.="<th>"._QXZ("Percent")."</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="</tr>";

$CSV_output["service_level_agreement"]="\""._QXZ("Inclusive SLA (computed on both answered and unanswered calls, inbound only)")."\"\n";
$CSV_output["service_level_agreement"].="\""._QXZ("Answer")."\",\""._QXZ("N. Calls")."\",\""._QXZ("Delta")."\",\""._QXZ("Percent")."\",\""._QXZ("Of Offered")."\",\"...\"\n";

# $SLA_query is in VERM_global_vars.inc
$svc_lvl_stmt="select $SLA_query from vicidial_closer_log $vicidial_closer_log_SQL $and_NANQUE_clause  $exc_addtl_statuses"; #  and campaign_id in ($ingroup_str) and user in ('VDCL', $user_str)
if ($DB) {$HTML_output.="<B>$svc_lvl_stmt</B>";}
$svc_lvl_rslt=mysqli_query($link, $svc_lvl_stmt);
$svc_lvl_row=mysqli_fetch_assoc($svc_lvl_rslt);

$service_level_agreement_graph_labels="[";
$service_level_agreement_graph_delta_data="[";
$service_level_agreement_graph_delta_bgcolor="["; # SAME FOR hoverBackgroundColor
$service_level_agreement_graph_delta_hovborder="[";
$service_level_agreement_graph_percent_data="[";
$service_level_agreement_graph_percent_bgcolor="["; # SAME FOR hoverBackgroundColor
$service_level_agreement_graph_percent_hovborder="[";

$x=0;
$sum=0; $prev_int="";
foreach($svc_lvl_row as $interval => $sum)
	{
	$int_name=ucwords(preg_replace("/_/", " ", $interval));
	if ($prev_int) {$delta="+ ".($sum-$prev_int);} else {$delta="&nbsp;";}
	
	$HTML_output.="<tr>";
	$HTML_output.="<td>$int_name "._QXZ("seconds").":</td>";
	$HTML_output.="<td>".$sum."</td>";
	$HTML_output.="<td align='right'><i>$delta</i>&nbsp;&nbsp;</td>";
	$HTML_output.="<td align='right'>".sprintf("%.1f", (MathZDC((100*$sum), $total_inbound_calls)))." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(MathZDC((100*$sum), $total_inbound_calls))."%'></td>";
	$HTML_output.="</tr>";

	$CSV_output["service_level_agreement"].="\"$int_name seconds:\",\"$sum\",\"$delta\",\"".sprintf("%.1f", (MathZDC((100*$sum), $total_inbound_calls)))." %\"\n";

	$service_level_agreement_graph_labels.="\"$int_name\",";
	$service_level_agreement_graph_delta_data.="\"".($prev_int ? ($sum-$prev_int) : "0")."\",";
	$service_level_agreement_graph_delta_bgcolor.="\"#33CCCC\",";
	$service_level_agreement_graph_delta_hovborder.="\"#66FFFF\",";
	$service_level_agreement_graph_percent_data.="\"".sprintf("%.1f", (MathZDC((100*$sum), $total_inbound_calls)))."\",";
	$service_level_agreement_graph_percent_bgcolor.="\"#FFCC66\",";
	$service_level_agreement_graph_percent_hovborder.="\"#FFFF99\",";

	$prev_int=$sum;
	$x++;
	}

$service_level_agreement_graph_labels=preg_replace('/,$/', '', $service_level_agreement_graph_labels)."]";
$service_level_agreement_graph_delta_data=preg_replace('/,$/', '', $service_level_agreement_graph_delta_data)."]";
$service_level_agreement_graph_delta_bgcolor=preg_replace('/,$/', '', $service_level_agreement_graph_delta_bgcolor)."]";
$service_level_agreement_graph_delta_hovborder=preg_replace('/,$/', '', $service_level_agreement_graph_delta_hovborder)."]";
$service_level_agreement_graph_percent_data=preg_replace('/,$/', '', $service_level_agreement_graph_percent_data)."]";
$service_level_agreement_graph_percent_bgcolor=preg_replace('/,$/', '', $service_level_agreement_graph_percent_bgcolor)."]";
$service_level_agreement_graph_percent_hovborder=preg_replace('/,$/', '', $service_level_agreement_graph_percent_hovborder)."]";

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='6'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=service_level_agreement\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='6' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('UNANSWERED', 'service_level_agreement')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
$HTML_output.="<div class=\"chart-container\" style=\"height:80vh; width:80vh\"><canvas id='service_level_agreement_display' role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

######################

#### UNANSWERED CALLS BY KEYPRESS ###
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Unanswered calls by keypress/option").": $NWB#VERM_UNANSWERED-calls_by_keypress$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>"._QXZ("Key").":</th>";
$HTML_output.="<th>"._QXZ("N. Calls")."</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="</tr>";

$CSV_output["unanswered_calls_by_keypress"].="\""._QXZ("Key")."\",\""._QXZ("N. Calls")."\",\"\",\"...\"\n";

$keypress_stmt="select extension, count(*) as ct From live_inbound_log where uniqueid in ('".implode("', '", $unans_uniqueid_array)."') and extension not in ('s', 'i', 't') and comment_a='CALLMENU' group by extension order by extension";
if ($DB) {$HTML_output.="<B>$keypress_stmt</B>";}
$keypress_rslt=mysql_to_mysqli($keypress_stmt, $link);
$kp_array=array();
while ($kp_row=mysqli_fetch_array($keypress_rslt))
	{
	$kp_array["$kp_row[extension]"]+=$kp_row["ct"];
	}
$total_kps=array_sum($kp_array);

ksort($kp_array);
$x=0;
foreach ($kp_array as $kps => $value)
	{
	$kp_pct=sprintf("%.1f", (100*$value)/$total_kps);
	$HTML_output.="<tr>";
	$HTML_output.="<td>".$kps."</td>";
	$HTML_output.="<td>".$value."</td>";
	$HTML_output.="<td>".$kp_pct." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(100*($value/$total_kps))."%'></td>";
	$HTML_output.="</tr>";
	$CSV_output["unanswered_calls_by_keypress"].="\"".$kps."\",\"".$value."\",\"".$kp_pct." %\",\"\"\n";

	$x++;
	}
$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='5'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=unanswered_calls_by_keypress\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='5' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('UNANSWERED', 'unanswered_calls_by_keypress')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="** does not include start/invalid/timeout options<BR>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

#####################################

/*
#### ANSWERED CALLS BY DIRECTION ###
$HTML_output.="<BR><h2 class='rpt_header'>Answered calls, by direction:</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table border='0' cellpadding='0' cellspacing='5'>";
$HTML_output.="<tr><td width='60%'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>Direction</th>";
$HTML_output.="<th>N. Calls</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="</tr>";

$CSV_output["answered_calls_by_direction"]="\"Answered calls, by direction:\"\n";
$CSV_output["answered_calls_by_direction"].="\"\",\"Direction\",\"N. Calls\",\"\",\"...\"\n";

$answered_calls_by_direction_graph_labels="[";
$answered_calls_by_direction_graph_data="[";
$answered_calls_by_direction_graph_bgcolor="["; # SAME FOR hoverBackgroundColor
$answered_calls_by_direction_graph_hovborder="[";

$max_call_direction=max($call_directions);
ksort($call_directions);
$call_directions["Any calls"]=0;
$x=0;
foreach ($call_directions as $direction => $value)
	{
	$direction_pct=sprintf("%.1f", (100*$value)/$total_answered_calls);
	$HTML_output.="<tr>";
	$HTML_output.="<td>".($SOME_IMAGE_GOES_HERE)."</td>";
	$HTML_output.="<td>".$direction."</td>";
	$HTML_output.="<td>".$value."</td>";
	$HTML_output.="<td>".$direction_pct." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(100*($value/$max_call_direction))."%'></td>";
	$HTML_output.="</tr>";

	$CSV_output["answered_calls_by_direction"].="\"\",\"".$direction."\",\"".$value."\",\"".$direction_pct." %\",\"\"\n";

	$answered_calls_by_direction_graph_labels.="\"".$direction."\",";
	$answered_calls_by_direction_graph_data.="\"$value\",";
	$answered_calls_by_direction_graph_bgcolor.="\"".$background_colors[$x]."\",";
	$answered_calls_by_direction_graph_hovborder.="\"".$border_colors[$x]."\",";

	$x++;
	}
$answered_calls_by_direction_graph_labels=preg_replace('/,$/', '', $answered_calls_by_direction_graph_labels)."]";
$answered_calls_by_direction_graph_data=preg_replace('/,$/', '', $answered_calls_by_direction_graph_data)."]";
$answered_calls_by_direction_graph_bgcolor=preg_replace('/,$/', '', $answered_calls_by_direction_graph_bgcolor)."]";
$answered_calls_by_direction_graph_hovborder=preg_replace('/,$/', '', $answered_calls_by_direction_graph_hovborder)."]";

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='5'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=answered_calls_by_direction\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='5' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('UNANSWERED', 'answered_calls_by_direction')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
$HTML_output.="<div class=\"chart-container\" style=\"height:40vh; width:40vh\"><canvas id='answered_calls_by_direction_display' role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";
#####################

#### ANSWERED CALLS BY STINTS ###
$HTML_output.="<BR><h2 class='rpt_header'>Answered calls, by stints:</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>Number of stints</th>";
$HTML_output.="<th>N. Calls</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="</tr>";

$CSV_output["answered_calls_by_stints"]="\"Answered calls, by stints:\"\n";
$CSV_output["answered_calls_by_stints"].="\"Number of stints\",\"N. Calls\",\"\",\"...\"\n";

$max_call_stint=max($call_stints);
$x=0;
foreach ($call_stints as $stint => $value)
	{
	$stint_pct=sprintf("%.1f", (100*$value)/$total_answered_calls);
	$HTML_output.="<tr>";
	$HTML_output.="<td>".$stint."</td>";
	$HTML_output.="<td>".$value."</td>";
	$HTML_output.="<td>".$stint_pct." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(100*($value/$max_call_stint))."%'></td>";
	$HTML_output.="</tr>";

	$CSV_output["answered_calls_by_stints"].="\"".$stint."\",\"".$value."\",\"".$stint_pct." %\",\"\"\n";

	$x++;
	}
$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='4'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=answered_calls_by_stints\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='4' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('UNANSWERED', 'answered_calls_by_stints')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
#####################
*/

#### UNANSWERED CALLS BY POSITION ###
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Enter queue position").": $NWB#VERM_UNANSWERED-queue_position$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table border='0' cellpadding='0' cellspacing='5'>";
$HTML_output.="<tr><td width='60%'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>"._QXZ("Position at enter").":</th>";
$HTML_output.="<th>"._QXZ("N. Calls")."</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="</tr>";

$CSV_output["queue_position"]="\""._QXZ("Enter queue position").":\"\n";
$CSV_output["queue_position"].="\""._QXZ("Position at enter").":\",\""._QXZ("N. Calls")."\",\"\",\"...\"\n";

$queue_position_graph_labels="[";
$queue_position_graph_data="[";
$queue_position_graph_bgcolor="["; # SAME FOR hoverBackgroundColor
$queue_position_graph_hovborder="[";

$max_queue_count=(count($unanswered_queue_positions)>0 ? max($unanswered_queue_positions) : 0);
$x=0;
foreach ($unanswered_queue_positions as $position => $value)
	{
	$position_pct=sprintf("%.1f", MathZDC((100*$value), $total_unanswered_calls));
	$HTML_output.="<tr>";
	$HTML_output.="<td>".$position."</td>";
	$HTML_output.="<td>".$value."</td>";
	$HTML_output.="<td>".$position_pct." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(100*MathZDC($value, $max_queue_count))."%'></td>";
	$HTML_output.="</tr>";

	$CSV_output["queue_position"].="\"".$position."\",\"".$value."\",\"".$position_pct." %\",\"\"\n";

	$queue_position_graph_labels.="\"".$position."\",";
	$queue_position_graph_data.="\"$value\",";
	$queue_position_graph_bgcolor.="\"".$background_colors[$x]."\",";
	$queue_position_graph_hovborder.="\"".$border_colors[$x]."\",";

	$x++;
	
	# For next table/graph...
	$queue_positions["$position"]+=$value;
	}
$queue_position_graph_labels=preg_replace('/,$/', '', $queue_position_graph_labels)."]";
$queue_position_graph_data=preg_replace('/,$/', '', $queue_position_graph_data)."]";
$queue_position_graph_bgcolor=preg_replace('/,$/', '', $queue_position_graph_bgcolor)."]";
$queue_position_graph_hovborder=preg_replace('/,$/', '', $queue_position_graph_hovborder)."]";

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='4'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=queue_position\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='4' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('UNANSWERED', 'queue_position')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
$HTML_output.="<div class=\"chart-container\" style=\"height:40vh; width:40vh\"><canvas id='queue_position_display' role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

#####################

#### UNANSWERED CALLS BY POSITION ###
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Queue position, all calls").": $NWB#VERM_UNANSWERED-queue_position_all_calls$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table border='0' cellpadding='0' cellspacing='5'>";
$HTML_output.="<tr><td width='60%'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>"._QXZ("Position at enter").":</th>";
$HTML_output.="<th>"._QXZ("N. Calls")."</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="</tr>";

$CSV_output["queue_position_all_calls"]="\""._QXZ("Queue position, all calls").":\"\n";
$CSV_output["queue_position_all_calls"].="\""._QXZ("Position at enter").":\",\""._QXZ("N. Calls")."\",\"\",\"...\"\n";

$queue_position_all_calls_graph_labels="[";
$queue_position_all_calls_graph_data="[";
$queue_position_all_calls_graph_bgcolor="["; # SAME FOR hoverBackgroundColor
$queue_position_all_calls_graph_hovborder="[";

ksort($queue_positions);
$max_queue_count=(count($queue_positions)>0 ? max($queue_positions) : 0);
$x=0;
foreach ($queue_positions as $position => $value)
	{
	$position_pct=sprintf("%.1f", MathZDC((100*$value), $total_calls));
	$HTML_output.="<tr>";
	$HTML_output.="<td>".$position."</td>";
	$HTML_output.="<td>".$value."</td>";
	$HTML_output.="<td>".$position_pct." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(100*MathZDC($value, $max_queue_count))."%'></td>";
	$HTML_output.="</tr>";

	$CSV_output["queue_position_all_calls_all_calls"].="\"".$position."\",\"".$value."\",\"".$position_pct." %\",\"\"\n";

	$queue_position_all_calls_graph_labels.="\"".$position."\",";
	$queue_position_all_calls_graph_data.="\"$value\",";
	$queue_position_all_calls_graph_bgcolor.="\"".$background_colors[$x]."\",";
	$queue_position_all_calls_graph_hovborder.="\"".$border_colors[$x]."\",";

	$x++;
	}
$queue_position_all_calls_graph_labels=preg_replace('/,$/', '', $queue_position_all_calls_graph_labels)."]";
$queue_position_all_calls_graph_data=preg_replace('/,$/', '', $queue_position_all_calls_graph_data)."]";
$queue_position_all_calls_graph_bgcolor=preg_replace('/,$/', '', $queue_position_all_calls_graph_bgcolor)."]";
$queue_position_all_calls_graph_hovborder=preg_replace('/,$/', '', $queue_position_all_calls_graph_hovborder)."]";

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='4'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=queue_position_all_calls\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='4' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('UNANSWERED', 'queue_position_all_calls')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
$HTML_output.="<div class=\"chart-container\" style=\"height:40vh; width:40vh\"><canvas id='queue_position_all_calls_display' role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

#####################

##### UN ANSWERED IVR STATS #####
$ivr_stmt="select uniqueid,extension,start_time,comment_a,comment_b,comment_d,UNIX_TIMESTAMP(start_time),phone_ext from live_inbound_log $live_inbound_log_SQL and comment_a IN('CALLMENU') order by uniqueid,start_time";
if ($DB) {$HTML_output.="<B>$ivr_stmt</B>";}
$ivr_rslt=mysqli_query($link, $ivr_stmt);
$ivr_paths=array(); # 0 - total calls, 1 - total time, 2 - min time, 3 - max time
$ivr_counts=array();
$untracked_calls=$total_unanswered_calls;
while ($ivr_row=mysqli_fetch_array($ivr_rslt))
	{
	$uniqueid=$ivr_row[0];

	# if (in_array($uniqueid, $unanswered_uniqueid_array))
	if (isset($unanswered_uniqueid_array["$uniqueid"]))
		{
		if (!$prev_id) 
			{
			$prev_id=$uniqueid;
			$ivr_start_time=$ivr_row[6];
			}

		if ($uniqueid!=$prev_id) 
			{
			$ivr_call_time=$ivr_end_time-$ivr_start_time;
			$untracked_calls--;
			# print "$prev_id --> $ivr_path: $ivr_end_time - $ivr_start_time = $ivr_call_time\n";

			if (!array_key_exists("$ivr_path", $ivr_paths))
				{
				$ivr_paths["$ivr_path"][0]=0;
				$ivr_paths["$ivr_path"][1]=0;
				$ivr_paths["$ivr_path"][2]=10000000;
				$ivr_paths["$ivr_path"][3]=0;
				}

	#		if ($tracked>0)
	#			{
				$ivr_counts["$ivr_path"]++;
				$ivr_paths["$ivr_path"][0]++;
				$ivr_paths["$ivr_path"][1]+=$ivr_call_time;
				if ($ivr_paths["$ivr_path"][2]>$ivr_call_time) {$ivr_paths["$ivr_path"][2]=$ivr_call_time;}
				if ($ivr_paths["$ivr_path"][3]<$ivr_call_time) {$ivr_paths["$ivr_path"][3]=$ivr_call_time;}
	#			}
	#		else
	#			{
	#			$ivr_paths["Untracked"][0]++;
	#			}
			$tracked=0;
			$ivr_path="";
			$ivr_start_time=$ivr_row[6];
			}
		else
			{
			$tracked++;
			}

		$ivr_path.=$ivr_row[4];
		$ivr_end_time=$ivr_row[6];
		$prev_id=$uniqueid;
		}

	}

if ($ivr_path!="")
	{
	$untracked_calls--;
	$ivr_counts["$ivr_path"]++;
	$ivr_paths["$ivr_path"][0]++;
	$ivr_call_time=$ivr_end_time-$ivr_start_time;
	$ivr_paths["$ivr_path"][1]+=$ivr_call_time;
	$ivr_paths["$ivr_path"][2]+=0;
	$ivr_paths["$ivr_path"][3]+=0;
	if ($ivr_paths["$ivr_path"][2]>$ivr_call_time || $ivr_call_time=0) {$ivr_paths["$ivr_path"][2]=$ivr_call_time;}
	if ($ivr_paths["$ivr_path"][3]<$ivr_call_time) {$ivr_paths["$ivr_path"][3]=$ivr_call_time;}
	}

$max_ivr_count=(count($ivr_counts)>0 ? max($ivr_counts) : 0);
if ($max_ivr_count<$untracked_calls) {$max_ivr_count=$untracked_calls;}
$untracked_ivr_pct=sprintf("%.1f", MathZDC((100*$untracked_calls), $total_unanswered_calls));

$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("IVR selection").": $NWB#VERM_UNANSWERED-ivr_selection$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table border='0' cellpadding='0' cellspacing='5'>";
$HTML_output.="<tr><td width='60%'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>"._QXZ("IVR")."</th>";
$HTML_output.="<th>"._QXZ("N. Calls")."</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="<th>"._QXZ("Total Duration")."</th>";
$HTML_output.="<th>"._QXZ("Average Duration")."</th>";
$HTML_output.="<th>"._QXZ("Min Duration")."</th>";
$HTML_output.="<th>"._QXZ("Max Duration")."</th>";
$HTML_output.="</tr>";

$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Untracked")."</td>";
$HTML_output.="<td>$untracked_calls</td>";
$HTML_output.="<td>".$untracked_ivr_pct." %</td>";
$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(100*MathZDC($untracked_calls, $total_unanswered_calls))."%'></td>";
$HTML_output.="<td>&nbsp;</td>";
$HTML_output.="<td>&nbsp;</td>";
$HTML_output.="<td>&nbsp;</td>";
$HTML_output.="<td>&nbsp;</td>";
$HTML_output.="</tr>";

$unanswered_ivr_selection_graph_labels="[\""._QXZ("Untracked")."\",";
$unanswered_ivr_selection_graph_data="[\"$untracked_calls\",";
$unanswered_ivr_selection_graph_bgcolor="[\"".$background_colors[0]."\","; # SAME FOR hoverBackgroundColor
$unanswered_ivr_selection_graph_hovborder="[\"".$border_colors[0]."\",";


$CSV_output["unanswered_ivr_selection"]="\""._QXZ("IVR selection").":\"\n";
$CSV_output["unanswered_ivr_selection"].="\""._QXZ("IVR")."\",\""._QXZ("N. Calls")."\",\"\",\"\",\""._QXZ("Total Duration")."\",\""._QXZ("Average Duration")."\",\""._QXZ("Min Duration")."\",\""._QXZ("Max Duration")."\"\n";
$CSV_output["unanswered_ivr_selection"].="\""._QXZ("Untracked")."\",\"$untracked_calls\",\"".$untracked_ivr_pct." %\",\"\",\"\",\"\",\"\",\"\"\n";

ksort($ivr_paths);
$x=1;
foreach ($ivr_paths as $pathname => $value)
	{
	$ivr_pct=sprintf("%.1f", (100*$value[0])/$total_unanswered_calls);
	$total_duration=($value[1]>=3600 ? floor($value[1]/3600).":" : "").gmdate("i:s", $value[1]);
	$avg_call_length=round($value[1]/$value[0]);
	$average_duration=($avg_call_length>=3600 ? floor($avg_call_length/3600).":" : "").gmdate("i:s", $avg_call_length);
	$min_duration=($value[2]>=3600 ? floor($value[2]/3600).":" : "").gmdate("i:s", $value[2]);
	$max_duration=($value[3]>=3600 ? floor($value[3]/3600).":" : "").gmdate("i:s", $value[3]);

	$HTML_output.="<tr>";
	$HTML_output.="<td>".$pathname."</td>";
	$HTML_output.="<td>".$value[0]."</td>";
	$HTML_output.="<td>".$ivr_pct." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(100*MathZDC($value[0], $total_unanswered_calls))."%'></td>";
	$HTML_output.="<td>".$total_duration."</td>";
	$HTML_output.="<td>".$average_duration."</td>";
	$HTML_output.="<td>".$min_duration."</td>";
	$HTML_output.="<td>".$max_duration."</td>";
	$HTML_output.="</tr>";

	$CSV_output["unanswered_ivr_selection"].="\"".$pathname."\",\"".$value[0]."\",\"".$ivr_pct." %\",\"\",\"".$total_duration."\",\"".$average_duration."\",\"".$min_duration."\",\"".$max_duration."\"\n";

	$unanswered_ivr_selection_graph_labels.="\"".$pathname."\",";
	$unanswered_ivr_selection_graph_data.="\"$value[0]\",";
	$unanswered_ivr_selection_graph_bgcolor.="\"".$background_colors[$x]."\",";
	$unanswered_ivr_selection_graph_hovborder.="\"".$border_colors[$x]."\",";

	$x++;
	}
$unanswered_ivr_selection_graph_labels=preg_replace('/,$/', '', $unanswered_ivr_selection_graph_labels)."]";
$unanswered_ivr_selection_graph_data=preg_replace('/,$/', '', $unanswered_ivr_selection_graph_data)."]";
$unanswered_ivr_selection_graph_bgcolor=preg_replace('/,$/', '', $unanswered_ivr_selection_graph_bgcolor)."]";
$unanswered_ivr_selection_graph_hovborder=preg_replace('/,$/', '', $unanswered_ivr_selection_graph_hovborder)."]";

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='8'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=unanswered_ivr_selection\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='8' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('UNANSWERED', 'unanswered_ivr_selection')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
$HTML_output.="<div class=\"chart-container\" style=\"height:400px; width:500px\"><canvas id='unanswered_ivr_selection_display'  style=\"height:400px; width:500px\" role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

#####################

##### IVR STATS #####
$ivr_stmt="select uniqueid,extension,start_time,comment_a,comment_b,comment_d,UNIX_TIMESTAMP(start_time),phone_ext from live_inbound_log $live_inbound_log_SQL and comment_a IN('CALLMENU') order by uniqueid,start_time";
if ($DB) {$HTML_output.="<B>$ivr_stmt</B>";}
$ivr_rslt=mysqli_query($link, $ivr_stmt);
$ivr_paths=array(); # 0 - total calls, 1 - total time, 2 - min time, 3 - max time
$ivr_counts=array();
$untracked_calls=$total_calls;
$prev_id=""; $ivr_path="";
while ($ivr_row=mysqli_fetch_array($ivr_rslt))
	{
	$uniqueid=$ivr_row[0];

	# if (in_array($uniqueid, $unanswered_uniqueid_array) || in_array($uniqueid, $uniqueid_array))
	if (isset($unanswered_uniqueid_array["$uniqueid"])|| isset($uniqueid_array["$uniqueid"]))
		{
		if (!$prev_id) 
			{
			$prev_id=$uniqueid;
			$ivr_start_time=$ivr_row[6];
			}

		if ($uniqueid!=$prev_id) 
			{
			$ivr_call_time=$ivr_end_time-$ivr_start_time;
			$untracked_calls--;
			# print "$prev_id --> $ivr_path: $ivr_end_time - $ivr_start_time = $ivr_call_time\n";

			if (!array_key_exists("$ivr_path", $ivr_paths))
				{
				$ivr_paths["$ivr_path"][0]=0;
				$ivr_paths["$ivr_path"][1]=0;
				$ivr_paths["$ivr_path"][2]=10000000;
				$ivr_paths["$ivr_path"][3]=0;
				}

	#		if ($tracked>0)
	#			{
				$ivr_counts["$ivr_path"]++;
				$ivr_paths["$ivr_path"][0]++;
				$ivr_paths["$ivr_path"][1]+=$ivr_call_time;
				if ($ivr_paths["$ivr_path"][2]>$ivr_call_time) {$ivr_paths["$ivr_path"][2]=$ivr_call_time;}
				if ($ivr_paths["$ivr_path"][3]<$ivr_call_time) {$ivr_paths["$ivr_path"][3]=$ivr_call_time;}
	#			}
	#		else
	#			{
	#			$ivr_paths["Untracked"][0]++;
	#			}
			$tracked=0;
			$ivr_path="";
			$ivr_start_time=$ivr_row[6];
			}
		else
			{
			$tracked++;
			}

		$ivr_path.=$ivr_row[4];
		$ivr_end_time=$ivr_row[6];
		$prev_id=$uniqueid;
		}

	}

if ($ivr_path!="")
	{
	$untracked_calls--;
	$ivr_counts["$ivr_path"]++;
	$ivr_paths["$ivr_path"][0]++;
	$ivr_call_time=$ivr_end_time-$ivr_start_time;
	$ivr_paths["$ivr_path"][1]+=$ivr_call_time;
	$ivr_paths["$ivr_path"][2]+=0;
	$ivr_paths["$ivr_path"][3]+=0;
	if ($ivr_paths["$ivr_path"][2]>$ivr_call_time || $ivr_call_time=0) {$ivr_paths["$ivr_path"][2]=$ivr_call_time;}
	if ($ivr_paths["$ivr_path"][3]<$ivr_call_time) {$ivr_paths["$ivr_path"][3]=$ivr_call_time;}
	}

$max_ivr_count=(count($ivr_counts)>0 ? max($ivr_counts) : 0);
if ($max_ivr_count<$untracked_calls) {$max_ivr_count=$untracked_calls;}
$untracked_ivr_pct=sprintf("%.1f", MathZDC((100*$untracked_calls), $total_calls));

$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("IVR selection, all calls").": $NWB#VERM_UNANSWERED-IVR_selection_all_calls$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table border='0' cellpadding='0' cellspacing='5'>";
$HTML_output.="<tr><td width='60%'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>"._QXZ("IVR")."</th>";
$HTML_output.="<th>"._QXZ("N. Calls")."</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="<th>"._QXZ("Total Duration")."</th>";
$HTML_output.="<th>"._QXZ("Average Duration")."</th>";
$HTML_output.="<th>"._QXZ("Min Duration")."</th>";
$HTML_output.="<th>"._QXZ("Max Duration")."</th>";
$HTML_output.="</tr>";


$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Untracked")."</td>";
$HTML_output.="<td>$untracked_calls</td>";
$HTML_output.="<td>".$untracked_ivr_pct." %</td>";
$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(100*MathZDC($untracked_calls, $total_calls))."%'></td>";
$HTML_output.="<td>&nbsp;</td>";
$HTML_output.="<td>&nbsp;</td>";
$HTML_output.="<td>&nbsp;</td>";
$HTML_output.="<td>&nbsp;</td>";
$HTML_output.="</tr>";

$CSV_output["unanswered_ivr_selection"]="\""._QXZ("IVR selection, all calls").":\"\n";
$CSV_output["unanswered_ivr_selection"].="\""._QXZ("IVR")."\",\""._QXZ("N. Calls")."\",\"\",\"\",\""._QXZ("Total Duration")."\",\""._QXZ("Average Duration")."\",\""._QXZ("Min Duration")."\",\""._QXZ("Max Duration")."\"\n";
$CSV_output["ivr_selection"].="\""._QXZ("Untracked")."\",\"$untracked_calls\",\"".$untracked_ivr_pct." %\",\"\",\"\",\"\",\"\",\"\"\n";

$ivr_selection_graph_labels="[\""._QXZ("Untracked")."\",";
$ivr_selection_graph_data="[\"$untracked_calls\",";
$ivr_selection_graph_bgcolor="[\"".$background_colors[0]."\","; # SAME FOR hoverBackgroundColor
$ivr_selection_graph_hovborder="[\"".$border_colors[0]."\",";

ksort($ivr_paths);
$x=1;
foreach ($ivr_paths as $pathname => $value)
	{
	$ivr_pct=sprintf("%.1f", (100*$value[0])/$total_calls);
	$total_duration=($value[1]>=3600 ? floor($value[1]/3600).":" : "").gmdate("i:s", $value[1]);
	$avg_call_length=round($value[1]/$value[0]);
	$average_duration=($avg_call_length>=3600 ? floor($avg_call_length/3600).":" : "").gmdate("i:s", $avg_call_length);
	$min_duration=($value[2]>=3600 ? floor($value[2]/3600).":" : "").gmdate("i:s", $value[2]);
	$max_duration=($value[3]>=3600 ? floor($value[3]/3600).":" : "").gmdate("i:s", $value[3]);

	$HTML_output.="<tr>";
	$HTML_output.="<td>".$pathname."</td>";
	$HTML_output.="<td>".$value[0]."</td>";
	$HTML_output.="<td>".$ivr_pct." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(100*MathZDC($value[0], $total_calls))."%'></td>";
	$HTML_output.="<td>".$total_duration."</td>";
	$HTML_output.="<td>".$average_duration."</td>";
	$HTML_output.="<td>".$min_duration."</td>";
	$HTML_output.="<td>".$max_duration."</td>";
	$HTML_output.="</tr>";

	$CSV_output["ivr_selection"].="\"".$pathname."\",\"".$value[0]."\",\"".$ivr_pct." %\",\"\",\"".$total_duration."\",\"".$average_duration."\",\"".$min_duration."\",\"".$max_duration."\"\n";

	$ivr_selection_graph_labels.="\"".$pathname."\",";
	$ivr_selection_graph_data.="\"$value[0]\",";
	$ivr_selection_graph_bgcolor.="\"".$background_colors[$x]."\",";
	$ivr_selection_graph_hovborder.="\"".$border_colors[$x]."\",";

	$x++;
	}
$ivr_selection_graph_labels=preg_replace('/,$/', '', $ivr_selection_graph_labels)."]";
$ivr_selection_graph_data=preg_replace('/,$/', '', $ivr_selection_graph_data)."]";
$ivr_selection_graph_bgcolor=preg_replace('/,$/', '', $ivr_selection_graph_bgcolor)."]";
$ivr_selection_graph_hovborder=preg_replace('/,$/', '', $ivr_selection_graph_hovborder)."]";

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='8'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=ivr_selection\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='8' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('UNANSWERED', 'ivr_selection')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
$HTML_output.="<div class=\"chart-container\" style=\"height:500px; width:400px\"><canvas id='ivr_selection_display'  style=\"height:500px; width:400px\" role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

#####################

###### UNANSWERED inbound DID used #####
$did_stmt="select extension,uniqueid from vicidial_did_log $vicidial_did_log_SQL order by extension";
if ($DB) {$HTML_output.="<B>$did_stmt</B>";}
$did_rslt=mysql_to_mysqli($did_stmt, $link);
$did_counts=array(); # 0 - total calls, 1 - total time, 2 - min time, 3 - max time
# $did_counts["Untracked"]=$untracked_calls2
$untracked_did_calls=$total_unanswered_calls;
while ($did_row=mysqli_fetch_array($did_rslt))
	{
	# if(in_array($did_row["uniqueid"], $unanswered_uniqueid_array))
	if(isset($unanswered_uniqueid_array["$did_row[uniqueid]"]))
		{
		$did_counts["$did_row[extension]"]++;
		$untracked_did_calls--;
		}
	}
$max_did=(count($did_counts)>0 ? max($did_counts) : 0);
if ($max_did<$untracked_did_calls) {$max_did=$untracked_did_calls;}

$untracked_did_pct=sprintf("%.1f", (MathZDC((100*$untracked_did_calls), $total_unanswered_calls)));
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Inbound DID used").": $NWB#VERM_UNANSWERED-did_used$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table border='0' cellpadding='0' cellspacing='5'>";
$HTML_output.="<tr><td width='60%'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>"._QXZ("DID").":</th>";
$HTML_output.="<th>"._QXZ("N. Calls")."</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="</tr>";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Untracked")."</td>";
$HTML_output.="<td>$untracked_did_calls</td>";
$HTML_output.="<td>$untracked_did_pct %</td>";
$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".$untracked_did_pct."%'></td>";
$HTML_output.="</tr>";

$CSV_output["unanswered_did_used"]="\""._QXZ("Inbound DID used").":\"\n";
$CSV_output["unanswered_did_used"].="\""._QXZ("DID").":\",\""._QXZ("N. Calls")."\",\"\",\"\"\n";
$CSV_output["unanswered_did_used"].="\""._QXZ("Untracked")."\",\"$untracked_did_calls\",\"".$untracked_did_pct." %\",\"\",\"\"\n";

$unanswered_did_used_graph_labels="[\""._QXZ("Untracked")."\",";
$unanswered_did_used_graph_data="[\"$untracked_did_calls\",";
$unanswered_did_used_graph_bgcolor="[\"".$background_colors[0]."\","; # SAME FOR hoverBackgroundColor
$unanswered_did_used_graph_hovborder="[\"".$border_colors[0]."\",";


ksort($did_counts);
$x=1;
foreach ($did_counts as $did => $value)
	{
	$did_pct=sprintf("%.1f", (100*$value)/$total_unanswered_calls);
	$HTML_output.="<tr>";
	$HTML_output.="<td>".$did." - ".$did_pattern_info["$did"]."</td>";
	$HTML_output.="<td>$value</td>";
	$HTML_output.="<td>$did_pct %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".$did_pct."%'></td>";
	$HTML_output.="</tr>";

	$CSV_output["unanswered_did_used"].="\"".$did." - ".$did_pattern_info["$did"]."\",\"$value\",\"".$did_pct." %\",\"\"\n";

	$unanswered_did_used_graph_labels.="\"".$did." - ".$did_pattern_info["$did"]."\",";
	$unanswered_did_used_graph_data.="\"$value\",";
	$unanswered_did_used_graph_bgcolor.="\"".$background_colors[$x]."\",";
	$unanswered_did_used_graph_hovborder.="\"".$border_colors[$x]."\",";

	$x++;
	}
$unanswered_did_used_graph_labels=preg_replace('/,$/', '', $unanswered_did_used_graph_labels)."]";
$unanswered_did_used_graph_data=preg_replace('/,$/', '', $unanswered_did_used_graph_data)."]";
$unanswered_did_used_graph_bgcolor=preg_replace('/,$/', '', $unanswered_did_used_graph_bgcolor)."]";
$unanswered_did_used_graph_hovborder=preg_replace('/,$/', '', $unanswered_did_used_graph_hovborder)."]";

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='4'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=unanswered_did_used\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='4' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('UNANSWERED', 'unanswered_did_used')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
$HTML_output.="<div class=\"chart-container\" style=\"height:600px; width:400px\"><canvas id='unanswered_did_used_display' role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

######################

###### Inbound DID used #####
$did_stmt="select extension,uniqueid from vicidial_did_log $vicidial_did_log_SQL order by extension";
if ($DB) {$HTML_output.="<B>$did_stmt</B>";}
$did_rslt=mysql_to_mysqli($did_stmt, $link);
$did_counts=array(); # 0 - total calls, 1 - total time, 2 - min time, 3 - max time
# $did_counts["Untracked"]=$untracked_calls2
$untracked_did_calls=$total_calls;
while ($did_row=mysqli_fetch_array($did_rslt))
	{
	# if(in_array($did_row["uniqueid"], $unanswered_uniqueid_array) || in_array($did_row["uniqueid"], $uniqueid_array))
	if(isset($unanswered_uniqueid_array["$did_row[uniqueid]"]) || isset($uniqueid_array["$did_row[uniqueid]"]))
		{
		$did_counts["$did_row[extension]"]++;
		$untracked_did_calls--;
		}
	}
$max_did=(count($did_counts)>0 ? max($did_counts) : 0);
if ($max_did<$untracked_did_calls) {$max_did=$untracked_did_calls;}

$untracked_did_pct=sprintf("%.1f", (MathZDC((100*$untracked_did_calls), $total_calls)));
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Inbound DID used, for all calls").": $NWB#VERM_UNANSWERED-did_used_all_calls$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table border='0' cellpadding='0' cellspacing='5'>";
$HTML_output.="<tr><td width='60%'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>"._QXZ("DID").":</th>";
$HTML_output.="<th>"._QXZ("N. Calls")."</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="</tr>";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Untracked")."</td>";
$HTML_output.="<td>$untracked_did_calls</td>";
$HTML_output.="<td>$untracked_did_pct %</td>";
$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".$untracked_did_pct."%'></td>";
$HTML_output.="</tr>";

$CSV_output["did_used"]="\""._QXZ("Inbound DID used, for all calls").":\"\n";
$CSV_output["did_used"].="\""._QXZ("DID").":\",\""._QXZ("N. Calls")."\",\"\",\"\"\n";
$CSV_output["did_used"].="\""._QXZ("Untracked")."\",\"$untracked_did_calls\",\"".$untracked_did_pct." %\",\"\",\"\"\n";

$did_used_graph_labels="[\""._QXZ("Untracked")."\",";
$did_used_graph_data="[\"$untracked_did_calls\",";
$did_used_graph_bgcolor="[\"".$background_colors[0]."\","; # SAME FOR hoverBackgroundColor
$did_used_graph_hovborder="[\"".$border_colors[0]."\",";


ksort($did_counts);
$x=1;
foreach ($did_counts as $did => $value)
	{
	$did_pct=sprintf("%.1f", MathZDC((100*$value), $total_answered_calls));
	$HTML_output.="<tr>";
	$HTML_output.="<td>".$did." - ".$did_pattern_info["$did"]."</td>";
	$HTML_output.="<td>$value</td>";
	$HTML_output.="<td>$did_pct %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".$did_pct."%'></td>";
	$HTML_output.="</tr>";

	$CSV_output["did_used"].="\"".$did." - ".$did_pattern_info["$did"]."\",\"$value\",\"".$did_pct." %\",\"\"\n";

	$did_used_graph_labels.="\"".$did." - ".$did_pattern_info["$did"]."\",";
	$did_used_graph_data.="\"$value\",";
	$did_used_graph_bgcolor.="\"".$background_colors[$x]."\",";
	$did_used_graph_hovborder.="\"".$border_colors[$x]."\",";

	$x++;
	}
$did_used_graph_labels=preg_replace('/,$/', '', $did_used_graph_labels)."]";
$did_used_graph_data=preg_replace('/,$/', '', $did_used_graph_data)."]";
$did_used_graph_bgcolor=preg_replace('/,$/', '', $did_used_graph_bgcolor)."]";
$did_used_graph_hovborder=preg_replace('/,$/', '', $did_used_graph_hovborder)."]";

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='4'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=did_used\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='4' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('UNANSWERED', 'did_used')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
$HTML_output.="<div class=\"chart-container\" style=\"height:600px; width:400px\"><canvas id='did_used_display' role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

######################

#### CALL OVERVIEW ####
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Call overview").": $NWB#VERM_UNANSWERED-call_overview$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>"._QXZ("Queue")."</th>";
$HTML_output.="<th>"._QXZ("Calls offered")."</th>";
$HTML_output.="<th>"._QXZ("Calls answered")."</th>";
$HTML_output.="<th>"._QXZ("Calls lost")."</th>";
$HTML_output.="<th>"._QXZ("Average call length").":</th>";
$HTML_output.="<th>"._QXZ("Total call length").":</th>";
$HTML_output.="<th>"._QXZ("Average call waiting time").":</th>";
$HTML_output.="<th>"._QXZ("Total waiting time").":</th>";
$HTML_output.="</tr>";

$CSV_output["call_overview"]="\""._QXZ("Call overview")."\"\n";
$CSV_output["call_overview"].="\""._QXZ("Queue")."\",\""._QXZ("Calls offered")."\",\""._QXZ("Calls answered")."\",\""._QXZ("Calls lost")."\",\""._QXZ("Average call length").":\",\""._QXZ("Total call length").":\",\""._QXZ("Average call waiting time").":\",\""._QXZ("Total waiting time").":\"\n";

/*
foreach ($total_queue_stats as $campaign => $data) {
	print ($campaign)."<BR>\n";
	$campaign_array_holder[] = $campaign;
	$sort_array_holder[] = $data["calls"];
}
print_r($sort_array_holder);
array_multisort($sort_array_holder,SORT_ASC, SORT_NUMERIC,$total_queue_stats);
*/

$col = array_column( $total_queue_stats, "calls" );
array_multisort( $col, SORT_ASC, $total_queue_stats );

$total_queue_calls=0;
$total_answered=0;
$total_unanswered=0;
$total_call_time=0;
$total_wait_time=0;
foreach($total_queue_stats as $campaign_name => $data)
	{
	$data["calls"]+=0;
	$data["answered"]+=0;
	$data["unanswered"]+=0;
	$data["call_time"]+=0;
	$data["wait_time"]+=0;
	
	$total_queue_calls+=$data["calls"];
	$total_queue_answered+=$data["answered"];
	$total_queue_unanswered+=$data["unanswered"];
	$total_queue_call_time+=$data["call_time"];
	$total_queue_wait_time+=$data["wait_time"];

	$avg_call_time=round($data["call_time"]/$data["answered"]);
	$avg_wait_time=round($data["wait_time"]/$data["calls"]);

	$HTML_output.="<tr>";
	$HTML_output.="<td>".$campaign_name."</td>";
	$HTML_output.="<td>".$data["calls"]."</td>";
	$HTML_output.="<td>".$data["answered"]."</td>";
	$HTML_output.="<td>".$data["unanswered"]."</td>";
	$HTML_output.="<td>".($avg_call_time>=3600 ? floor($avg_call_time/3600).":" : "").gmdate("i:s", $avg_call_time)."</td>";
	$HTML_output.="<td>".($data["call_time"]>=3600 ? floor($data["call_time"]/3600).":" : "").gmdate("i:s", $data["call_time"])."</td>";
	$HTML_output.="<td>".($avg_wait_time>=3600 ? floor($avg_wait_time/3600).":" : "").gmdate("i:s", $avg_wait_time)."</td>";
	$HTML_output.="<td>".($data["wait_time"]>=3600 ? floor($data["wait_time"]/3600).":" : "").gmdate("i:s", $data["wait_time"])."</td>";
	$HTML_output.="</tr>";
	
	$CSV_output["call_overview"].="\"".$campaign_name."\",\"".$data["calls"]."\",\"".$data["answered"]."\",\"".$data["unanswered"]."\",\"".($avg_call_time>=3600 ? floor($avg_call_time/3600).":" : "").gmdate("i:s", $avg_call_time)."\",\"".($data["call_time"]>=3600 ? floor($data["call_time"]/3600).":" : "").gmdate("i:s", $data["call_time"])."\",\"".($avg_wait_time>=3600 ? floor($avg_wait_time/3600).":" : "").gmdate("i:s", $avg_wait_time)."\",\"".($data["wait_time"]>=3600 ? floor($data["wait_time"]/3600).":" : "").gmdate("i:s", $data["wait_time"])."\"\n";	
	}

$avg_call_time=round(MathZDC($total_queue_call_time, $total_queue_answered));
$avg_wait_time=round(MathZDC($total_queue_wait_time, $total_queue_calls));

$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("TOTAL")."</td>";
$HTML_output.="<td>".$total_queue_calls."</td>";
$HTML_output.="<td>".$total_queue_answered."</td>";
$HTML_output.="<td>".$total_queue_unanswered."</td>";
$HTML_output.="<td>".($avg_call_time>=3600 ? floor($avg_call_time/3600).":" : "").gmdate("i:s", $avg_call_time)."</td>";
$HTML_output.="<td>".($total_queue_call_time>=3600 ? floor($total_queue_call_time/3600).":" : "").gmdate("i:s", $total_queue_call_time)."</td>";
$HTML_output.="<td>".($avg_wait_time>=3600 ? floor($avg_wait_time/3600).":" : "").gmdate("i:s", $avg_wait_time)."</td>";
$HTML_output.="<td>".($total_queue_wait_time>=3600 ? floor($total_queue_wait_time/3600).":" : "").gmdate("i:s", $total_queue_wait_time)."</td>";
$HTML_output.="</tr>";
$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='8'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=call_overview\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='8' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('UNANSWERED', 'call_overview')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";

$CSV_output["call_overview"].="\"TOTAL\",\"".$total_queue_calls."\",\"".$total_queue_answered."\",\"".$total_queue_unanswered."\",\"".($avg_call_time>=3600 ? floor($avg_call_time/3600).":" : "").gmdate("i:s", $avg_call_time)."\",\"".($total_queue_call_time>=3600 ? floor($total_queue_call_time/3600).":" : "").gmdate("i:s", $total_queue_call_time)."\",\"".($avg_wait_time>=3600 ? floor($avg_wait_time/3600).":" : "").gmdate("i:s", $avg_wait_time)."\",\"".($total_queue_wait_time>=3600 ? floor($total_queue_wait_time/3600).":" : "").gmdate("i:s", $total_queue_wait_time)."\"\n";	

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

#######################

#### SERVICE LEVEL ####
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Inclusive answered SLA (inbound answered calls only)").": $NWB#VERM_UNANSWERED-inclusive_answered_SLA$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table border='0' cellpadding='0' cellspacing='0'>";
$HTML_output.="<tr><td width='60%'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>"._QXZ("Answer")."</th>";
$HTML_output.="<th>"._QXZ("N. Calls")."</th>";
$HTML_output.="<th>"._QXZ("Delta")."</th>";
$HTML_output.="<th>"._QXZ("Percent")."</th>";
$HTML_output.="<th>"._QXZ("Of Offered")."</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="</tr>";

$CSV_output["answered_service_level_agreement"]="\""._QXZ("Inclusive answered SLA (inbound answered calls only)")."\"\n";
$CSV_output["answered_service_level_agreement"].="\""._QXZ("Answer")."\",\""._QXZ("N. Calls")."\",\""._QXZ("Delta")."\",\""._QXZ("Percent")."\",\""._QXZ("Of Offered")."\",\"...\"\n";

# $SLA_query is in VERM_global_vars.inc
$svc_lvl_stmt="select $SLA_query from vicidial_closer_log $vicidial_closer_log_SQL and (user!='VDCL')"; #  and campaign_id in ($ingroup_str) and user in ('VDCL', $user_str)
if ($DB) {$HTML_output.="<B>$svc_lvl_stmt</B>";}
$svc_lvl_rslt=mysqli_query($link, $svc_lvl_stmt);
$svc_lvl_row=mysqli_fetch_assoc($svc_lvl_rslt);

$answered_service_level_agreement_graph_labels="[";
$answered_service_level_agreement_graph_delta_data="[";
$answered_service_level_agreement_graph_delta_bgcolor="["; # SAME FOR hoverBackgroundColor
$answered_service_level_agreement_graph_delta_hovborder="[";
$answered_service_level_agreement_graph_percent_data="[";
$answered_service_level_agreement_graph_percent_bgcolor="["; # SAME FOR hoverBackgroundColor
$answered_service_level_agreement_graph_percent_hovborder="[";

$x=0; $prev_int="";
foreach($svc_lvl_row as $interval => $sum)
	{
	$int_name=ucwords(preg_replace("/_/", " ", $interval));
	if ($prev_int) {$delta="+ ".($sum-$prev_int);} else {$delta="&nbsp;";}
	
	$HTML_output.="<tr>";
	$HTML_output.="<td>$int_name "._QXZ("seconds").":</td>";
	$HTML_output.="<td>".$sum."</td>";
	$HTML_output.="<td align='right'><i>$delta</i>&nbsp;&nbsp;</td>";
	$HTML_output.="<td align='right'>".sprintf("%.1f", (MathZDC((100*$sum), $answered_inbound_calls)))." %</td>";
	$HTML_output.="<td align='right'>".sprintf("%.1f", (MathZDC((100*$sum), $total_inbound_calls)))." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(MathZDC((100*$sum), $answered_inbound_calls))."%'></td>";
	$HTML_output.="</tr>";

	$CSV_output["answered_service_level_agreement"].="\"$int_name "._QXZ("seconds").":\",\"$sum\",\"$delta\",\"".sprintf("%.1f", (MathZDC((100*$sum), $answered_inbound_calls)))." %\",\"".sprintf("%.1f", (MathZDC((100*$sum), $total_inbound_calls)))." %\"\n";

	$answered_service_level_agreement_graph_labels.="\"$int_name\",";
	$answered_service_level_agreement_graph_delta_data.="\"".($prev_int ? ($sum-$prev_int) : "0")."\",";
	$answered_service_level_agreement_graph_delta_bgcolor.="\"#33CCCC\",";
	$answered_service_level_agreement_graph_delta_hovborder.="\"#66FFFF\",";
	$answered_service_level_agreement_graph_percent_data.="\"".sprintf("%.1f", (MathZDC((100*$sum), $answered_inbound_calls)))."\",";
	$answered_service_level_agreement_graph_percent_bgcolor.="\"#FFCC66\",";
	$answered_service_level_agreement_graph_percent_hovborder.="\"#FFFF99\",";

	$prev_int=$sum;
	$x++;
	}

$answered_service_level_agreement_graph_labels=preg_replace('/,$/', '', $answered_service_level_agreement_graph_labels)."]";
$answered_service_level_agreement_graph_delta_data=preg_replace('/,$/', '', $answered_service_level_agreement_graph_delta_data)."]";
$answered_service_level_agreement_graph_delta_bgcolor=preg_replace('/,$/', '', $answered_service_level_agreement_graph_delta_bgcolor)."]";
$answered_service_level_agreement_graph_delta_hovborder=preg_replace('/,$/', '', $answered_service_level_agreement_graph_delta_hovborder)."]";
$answered_service_level_agreement_graph_percent_data=preg_replace('/,$/', '', $answered_service_level_agreement_graph_percent_data)."]";
$answered_service_level_agreement_graph_percent_bgcolor=preg_replace('/,$/', '', $answered_service_level_agreement_graph_percent_bgcolor)."]";
$answered_service_level_agreement_graph_percent_hovborder=preg_replace('/,$/', '', $answered_service_level_agreement_graph_percent_hovborder)."]";

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='6'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=answered_service_level_agreement\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='6' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('UNANSWERED', 'answered_service_level_agreement')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
$HTML_output.="<div class=\"chart-container\" style=\"height:80vh; width:80vh\"><canvas id='answered_service_level_agreement_display' role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

######################

if ($download_rpt)
	{
	$data_to_download=$CSV_output["$download_rpt"];

	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "UNANSWERED_RPT_".$download_rpt."_".$FILE_TIME.".csv";
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

<script language='Javascript'>
var disconnection_causes_data = {
		labels: <?php echo $disconnection_causes_graph_labels; ?>,
		datasets: [
			{
				label: "",
				fill: false,
				data: <?php echo $disconnection_causes_graph_data; ?>,
				backgroundColor: <?php echo $disconnection_causes_graph_bgcolor; ?>,
				hoverBackgroundColor: <?php echo $disconnection_causes_graph_bgcolor; ?>,
				hoverBorderColor: <?php echo $disconnection_causes_graph_hovborder; ?>,
				hoverBorderWidth: 2,
				tension: 0,
				fillColor: "rgba(255,230,230,0.5)",
				borderColor: "rgba(255,255,255,0.8)",
			}
		]
	}
var disconnection_causes_canvas = document.getElementById("disconnection_causes_display");
var disconnection_causes_graph = new Chart(disconnection_causes_canvas, {type: 'pie', options: { plugins: {legend: { position: 'right' }}},
data: disconnection_causes_data});

var unanswered_calls_by_queue_data = {
		labels: <?php echo $unanswered_calls_by_queue_graph_labels; ?>,
		datasets: [
			{
				label: "",
				fill: false,
				data: <?php echo $unanswered_calls_by_queue_graph_data; ?>,
				backgroundColor: <?php echo $unanswered_calls_by_queue_graph_bgcolor; ?>,
				hoverBackgroundColor: <?php echo $unanswered_calls_by_queue_graph_bgcolor; ?>,
				hoverBorderColor: <?php echo $unanswered_calls_by_queue_graph_hovborder; ?>,
				hoverBorderWidth: 2,
				tension: 0,
				fillColor: "rgba(255,230,230,0.5)",
				borderColor: "rgba(255,255,255,0.8)",
			}
		]
	}
var unanswered_calls_by_queue_canvas = document.getElementById("unanswered_calls_by_queue_display");
var unanswered_calls_by_queue_graph = new Chart(unanswered_calls_by_queue_canvas, {type: 'pie', options: { plugins: {legend: { position: 'right' }}},
data: unanswered_calls_by_queue_data});

var unanswered_calls_by_distribution_data = {
		labels: <?php echo $unanswered_calls_by_distribution_graph_labels; ?>,
		datasets: [
			{
				type: 'bar',
				label: "Delta",
				fill: false,
				data: <?php echo $unanswered_calls_by_distribution_graph_delta_data; ?>,
				backgroundColor: <?php echo $unanswered_calls_by_distribution_graph_delta_bgcolor; ?>,
				hoverBackgroundColor: <?php echo $unanswered_calls_by_distribution_graph_delta_bgcolor; ?>,
				hoverBorderColor: <?php echo $unanswered_calls_by_distribution_graph_delta_hovborder; ?>,
				hoverBorderWidth: 2,
				tension: 0,
				order: 1
			},
			{
				type: 'line',
				label: "Percent",
				fill: true,
				data: <?php echo $unanswered_calls_by_distribution_graph_percent_data; ?>,
				backgroundColor: "rgba(255,204,102,0.5)",
				hoverBorderWidth: 2,
				tension: 0,
				fillColor: "rgba(255,204,102,0.5)",
				borderColor: "rgba(255,204,102,0.8)",
				pointBorderColor: "rgba(255,255,153,1)",
				pointBackgroundColor: "#FF9",
				pointHoverBackgroundColor: "rgba(255,255,153,0.75)",
				pointHoverBorderColor: "rgba(255,255,153,1)",
				order: 2
			}			
		]
	}
var unanswered_calls_by_distribution_canvas = document.getElementById("unanswered_calls_by_distribution_display");
var unanswered_calls_by_distribution_graph = new Chart(unanswered_calls_by_distribution_canvas, {options: { plugins: {legend: { position: 'right' }}},
data: unanswered_calls_by_distribution_data});

var service_level_agreement_data = {
		labels: <?php echo $service_level_agreement_graph_labels; ?>,
		datasets: [
			{
				type: 'bar',
				label: "Delta",
				fill: false,
				data: <?php echo $service_level_agreement_graph_delta_data; ?>,
				backgroundColor: <?php echo $service_level_agreement_graph_delta_bgcolor; ?>,
				hoverBackgroundColor: <?php echo $service_level_agreement_graph_delta_bgcolor; ?>,
				hoverBorderColor: <?php echo $service_level_agreement_graph_delta_hovborder; ?>,
				hoverBorderWidth: 2,
				tension: 0,
				order: 1
			},
			{
				type: 'line',
				label: "Percent",
				fill: true,
				data: <?php echo $service_level_agreement_graph_percent_data; ?>,
				backgroundColor: "rgba(255,204,102,0.5)",
				hoverBorderWidth: 2,
				tension: 0,
				fillColor: "rgba(255,204,102,0.5)",
				borderColor: "rgba(255,204,102,0.8)",
				pointBorderColor: "rgba(255,255,153,1)",
				pointBackgroundColor: "#FF9",
				pointHoverBackgroundColor: "rgba(255,255,153,0.75)",
				pointHoverBorderColor: "rgba(255,255,153,1)",
				order: 2
			}			
		]
	}
var service_level_agreement_canvas = document.getElementById("service_level_agreement_display");
var service_level_agreement_graph = new Chart(service_level_agreement_canvas, {options: { plugins: {legend: { position: 'right' }}},
data: service_level_agreement_data});

var answered_service_level_agreement_data = {
		labels: <?php echo $answered_service_level_agreement_graph_labels; ?>,
		datasets: [
			{
				type: 'bar',
				label: "Delta",
				fill: false,
				data: <?php echo $answered_service_level_agreement_graph_delta_data; ?>,
				backgroundColor: <?php echo $answered_service_level_agreement_graph_delta_bgcolor; ?>,
				hoverBackgroundColor: <?php echo $answered_service_level_agreement_graph_delta_bgcolor; ?>,
				hoverBorderColor: <?php echo $answered_service_level_agreement_graph_delta_hovborder; ?>,
				hoverBorderWidth: 2,
				tension: 0,
				order: 1
			},
			{
				type: 'line',
				label: "Percent",
				fill: true,
				data: <?php echo $answered_service_level_agreement_graph_percent_data; ?>,
				backgroundColor: "rgba(255,204,102,0.5)",
				hoverBorderWidth: 2,
				tension: 0,
				fillColor: "rgba(255,204,102,0.5)",
				borderColor: "rgba(255,204,102,0.8)",
				pointBorderColor: "rgba(255,255,153,1)",
				pointBackgroundColor: "#FF9",
				pointHoverBackgroundColor: "rgba(255,255,153,0.75)",
				pointHoverBorderColor: "rgba(255,255,153,1)",
				order: 2
			}			
		]
	}
var answered_service_level_agreement_canvas = document.getElementById("answered_service_level_agreement_display");
var answered_service_level_agreement_graph = new Chart(answered_service_level_agreement_canvas, {options: { plugins: {legend: { position: 'right' }}},
data: answered_service_level_agreement_data});

var queue_position_data = {
		labels: <?php echo $queue_position_graph_labels; ?>,
		datasets: [
			{
				label: "",
				fill: false,
				data: <?php echo $queue_position_graph_data; ?>,
				backgroundColor: <?php echo $queue_position_graph_bgcolor; ?>,
				hoverBackgroundColor: <?php echo $queue_position_graph_bgcolor; ?>,
				hoverBorderColor: <?php echo $queue_position_graph_hovborder; ?>,
				hoverBorderWidth: 2,
				tension: 0,
				fillColor: "rgba(255,230,230,0.5)",
				borderColor: "rgba(255,255,255,0.8)",
			}
		]
	}
var queue_position_canvas = document.getElementById("queue_position_display");
var queue_position_graph = new Chart(queue_position_canvas, {type: 'pie', options: { plugins: {legend: { position: 'right' }}},
data: queue_position_data});

var queue_position_all_calls_data = {
		labels: <?php echo $queue_position_all_calls_graph_labels; ?>,
		datasets: [
			{
				label: "",
				fill: false,
				data: <?php echo $queue_position_all_calls_graph_data; ?>,
				backgroundColor: <?php echo $queue_position_all_calls_graph_bgcolor; ?>,
				hoverBackgroundColor: <?php echo $queue_position_all_calls_graph_bgcolor; ?>,
				hoverBorderColor: <?php echo $queue_position_all_calls_graph_hovborder; ?>,
				hoverBorderWidth: 2,
				tension: 0,
				fillColor: "rgba(255,230,230,0.5)",
				borderColor: "rgba(255,255,255,0.8)",
			}
		]
	}
var queue_position_all_calls_canvas = document.getElementById("queue_position_all_calls_display");
var queue_position_all_calls_graph = new Chart(queue_position_all_calls_canvas, {type: 'pie', options: { plugins: {legend: { position: 'right' }}},
data: queue_position_all_calls_data});


var unanswered_ivr_selection_data = {
		labels: <?php echo $unanswered_ivr_selection_graph_labels; ?>,
		datasets: [
			{
				label: "",
				fill: false,
				data: <?php echo $unanswered_ivr_selection_graph_data; ?>,
				backgroundColor: <?php echo $unanswered_ivr_selection_graph_bgcolor; ?>,
				hoverBackgroundColor: <?php echo $unanswered_ivr_selection_graph_bgcolor; ?>,
				hoverBorderColor: <?php echo $unanswered_ivr_selection_graph_hovborder; ?>,
				hoverBorderWidth: 2,
				tension: 0,
				fillColor: "rgba(255,230,230,0.5)",
				borderColor: "rgba(255,255,255,0.8)",
			}
		]
	}
var unanswered_ivr_selection_canvas = document.getElementById("unanswered_ivr_selection_display");
var unanswered_ivr_selection_graph = new Chart(unanswered_ivr_selection_canvas, {type: 'pie', options: {responsive: false,  plugins: {legend: { position: 'right' }}},
data: unanswered_ivr_selection_data});

var ivr_selection_data = {
		labels: <?php echo $ivr_selection_graph_labels; ?>,
		datasets: [
			{
				label: "",
				fill: false,
				data: <?php echo $ivr_selection_graph_data; ?>,
				backgroundColor: <?php echo $ivr_selection_graph_bgcolor; ?>,
				hoverBackgroundColor: <?php echo $ivr_selection_graph_bgcolor; ?>,
				hoverBorderColor: <?php echo $ivr_selection_graph_hovborder; ?>,
				hoverBorderWidth: 2,
				tension: 0,
				fillColor: "rgba(255,230,230,0.5)",
				borderColor: "rgba(255,255,255,0.8)",
			}
		]
	}
var ivr_selection_canvas = document.getElementById("ivr_selection_display");
var ivr_selection_graph = new Chart(ivr_selection_canvas, {type: 'pie', options: {responsive: false,  plugins: {legend: { position: 'right' }}},
data: ivr_selection_data});

var unanswered_did_used_data = {
		labels: <?php echo $unanswered_did_used_graph_labels; ?>,
		datasets: [
			{
				label: "",
				fill: false,
				data: <?php echo $unanswered_did_used_graph_data; ?>,
				backgroundColor: <?php echo $unanswered_did_used_graph_bgcolor; ?>,
				hoverBackgroundColor: <?php echo $unanswered_did_used_graph_bgcolor; ?>,
				hoverBorderColor: <?php echo $unanswered_did_used_graph_hovborder; ?>,
				hoverBorderWidth: 2,
				tension: 0,
				fillColor: "rgba(255,230,230,0.5)",
				borderColor: "rgba(255,255,255,0.8)",
			}
		]
	}
var unanswered_did_used_canvas = document.getElementById("unanswered_did_used_display");
var unanswered_did_used_graph = new Chart(unanswered_did_used_canvas, {type: 'pie', options: { responsive: false, plugins: {legend: { position: 'right' }}},
data: unanswered_did_used_data});

var did_used_data = {
		labels: <?php echo $did_used_graph_labels; ?>,
		datasets: [
			{
				label: "",
				fill: false,
				data: <?php echo $did_used_graph_data; ?>,
				backgroundColor: <?php echo $did_used_graph_bgcolor; ?>,
				hoverBackgroundColor: <?php echo $did_used_graph_bgcolor; ?>,
				hoverBorderColor: <?php echo $did_used_graph_hovborder; ?>,
				hoverBorderWidth: 2,
				tension: 0,
				fillColor: "rgba(255,230,230,0.5)",
				borderColor: "rgba(255,255,255,0.8)",
			}
		]
	}
var did_used_canvas = document.getElementById("did_used_display");
var did_used_graph = new Chart(did_used_canvas, {type: 'pie', options: { responsive: false, plugins: {legend: { position: 'right' }}},
data: did_used_data});
</script>
