<?php
# VERM_ANSWERED_rpt.inc - Vicidial Enhanced Reporting answered call report
#
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGELOG:
# 220825-1629 - First build
# 230106-1310 - Added auto download and page flushing for large data output, modification to uniqueid handling to speed up page load
# 240801-1130 - Code updates for PHP8 compatibility
#

# DROP, WAITTO, TIMEOT are unanswered statuses, not NANQUE
# $PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
# $PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
# $PHP_SELF=$_SERVER['PHP_SELF'];
# $PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);

$startMS = microtime();

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


$calls_stmt="select call_date, call_date+INTERVAL (length_in_sec) SECOND as end_date, if(call_date+INTERVAL (length_in_sec) SECOND<='$end_date $end_time', '1', '0') as within_interval, campaign_id, user, length_in_sec, '0' as queue_seconds, 'O' as direction, '1' as queue_position, term_reason, comments, status, uniqueid From vicidial_log $vicidial_log_SQL and user!='VDAD' UNION select call_date, call_date+INTERVAL (length_in_sec) SECOND as end_date, if(call_date+INTERVAL (length_in_sec) SECOND<='$end_date $end_time', '1', '0') as within_interval, campaign_id, user, if(comments='EMAIL', length_in_sec, length_in_sec-queue_seconds) as length_in_sec, if(comments='EMAIL', '0', queue_seconds) as queue_seconds, 'I' as direction, queue_position, term_reason, comments, status, uniqueid From vicidial_closer_log $vicidial_closer_log_SQL and (user!='VDCL' $or_NANQUE_clause)";

if ($DB) {$HTML_output.="<B>$calls_stmt</B>";}

$calls_rslt=mysqli_query($link, $calls_stmt);

### VARIABLES ###
$total_calls=0;
$total_answered_calls=0;
$total_unanswered_calls=0;
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
$coverage="100%";
$coverage_wi="100%";
#################

### WITHIN-INTERVAL VARS ###
$total_calls_wi=0;
$total_answered_calls_wi=0;
$total_unanswered_calls_wi=0;
$max_call_length_wi=0; 
$min_call_length_wi=1000000;
$total_call_length_wi=0;
$max_wait_time_wi=0;
$min_wait_time_wi=1000000;
$total_wait_time_wi=0;
$total_position_wi=0;
$max_position_wi=0;
$min_position_wi=1000000;
$agent_stats_wi=array();
############################

$uniqueid_array=array();

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
	
	if (preg_match('/VDAD|VDCL/', $calls_row["user"])) 
		{
		/* IRRELEVANT
		if (preg_match('/^DROP$|TIMEOT|WAITTO/', $calls_row["status"]))
			{
			$total_unanswered_calls++;
			if ($calls_row["direction"]=="O")
				{
				$unanswered_outbound_calls++;
				}
			else
				{
				$unanswered_inbound_calls++;
				}
			$call_was_answered=0;
			}
		else
			{
			# DO NOTHING FOR NANQUE - dunno why but phone 4698254386 shows only under one rep despite call length
			}
		*/
		} 
	else 
		{
		# array_push($uniqueid_array, $calls_row["uniqueid"]);
		$uniqueid_array["$calls_row[uniqueid]"]="Y";
		$total_answered_calls++;
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
		if (!$agent_full_name) {$agent_full_name=$calls_row["user"];}
		$agent_full_name=($show_full_agent_info ? $calls_row["user"]." - " : "").$fullname_info["$calls_row[user]"];
		$agent_call_counts["$agent_full_name"]++;
		$agent_call_lengths["$agent_full_name"]+=$calls_row["length_in_sec"];

		# Events?
		$agent_events["$calls_row[user]"][0]=0;
		$agent_events["$calls_row[user]"][1]=0;

		$term_reasons["$calls_row[term_reason]"]++;

		$queue_stats["$calls_row[campaign_id]"]++;

		if ($calls_row["direction"]=="O") {$call_directions["Outbound calls"]++;} else {$call_directions["Inbound calls"]++;}

		$call_stints["$stint"]++;

		$total_position+=$calls_row["queue_position"];
		$queue_positions["$calls_row[queue_position]"]++;
		}

	if ($calls_row["length_in_sec"]>$max_call_length && $call_was_answered) {$max_call_length=$calls_row["length_in_sec"];}
	if ($calls_row["length_in_sec"]<$min_call_length && $call_was_answered) {$min_call_length=$calls_row["length_in_sec"];}

	if ($calls_row["queue_seconds"]>$max_wait_time && $call_was_answered) {$max_wait_time=$calls_row["queue_seconds"];}
	if ($calls_row["queue_seconds"]<$min_wait_time && $call_was_answered) {$min_wait_time=$calls_row["queue_seconds"];}

	if ($calls_row["queue_position"]>$max_position) {$max_position=$calls_row["queue_position"];}
	if ($calls_row["queue_position"]<$min_position) {$min_position=$calls_row["queue_position"];}

	### FILTER FOR FULLY WITHIN-INTERVAL CALLS, SHOULD BE EXACTLY THE SAME (FOR NOW)
	if ($calls_row["within_interval"])
		{
		$total_calls_wi++;
		$total_call_length_wi+=$calls_row["length_in_sec"];
		$total_wait_time_wi+=$calls_row["queue_seconds"];
		$total_position_wi+=$calls_row["queue_position"];
		
		if (preg_match('/VDAD|VDCL/', $calls_row["user"]) && preg_match('/^DROP$|TIMEOT|WAITTO/', $calls_row["status"])) 
			{
			$total_unanswered_calls_wi++;
			} 
		else 
			{
			$total_answered_calls_wi++;
			}

		if ($calls_row["length_in_sec"]>$max_call_length_wi) {$max_call_length_wi=$calls_row["length_in_sec"];}
		if ($calls_row["length_in_sec"]<$min_call_length_wi) {$min_call_length_wi=$calls_row["length_in_sec"];}

		if ($calls_row["queue_seconds"]>$max_wait_time_wi && $call_was_answered) {$max_wait_time_wi=$calls_row["queue_seconds"];}
		if ($calls_row["queue_seconds"]<$min_wait_time_wi && $call_was_answered) {$min_wait_time_wi=$calls_row["queue_seconds"];}

		if ($calls_row["queue_position"]>$max_position_wi) {$max_position_wi=$calls_row["queue_position"];}
		if ($calls_row["queue_position"]<$min_position_wi) {$min_position_wi=$calls_row["queue_position"];}
		}
	}

$average_call_length=sprintf("%.1f", MathZDC($total_call_length, $total_answered_calls)); # Total calls instead?
$average_wait_time=sprintf("%.1f", MathZDC($total_wait_time, $total_answered_calls)); # Total calls instead?
$average_position=sprintf("%.1f", MathZDC($total_position, $total_answered_calls));
$total_call_length_fmt=sprintf("%.1f", ($total_call_length/3600));
$total_wait_time_fmt=sprintf("%.1f", ($total_wait_time/3600));

$average_call_length_wi=sprintf("%.1f", MathZDC($total_call_length_wi, $total_answered_calls_wi)); # Total calls instead?
$average_wait_time_wi=sprintf("%.1f", MathZDC($total_wait_time_wi, $answered_call_wis)); # Total calls instead?
$average_position_wi=sprintf("%.1f", MathZDC($total_position_wi, $total_answered_calls_wi));
$total_call_length_fmt_wi=sprintf("%.1f", ($total_call_length_wi/3600));
$total_wait_time_fmt_wi=sprintf("%.1f", ($total_wait_time_wi/3600));

# Wipe unadjusted variables (i.e. no records)
$min_call_length=($min_call_length==1000000 ? "0" : $min_call_length);
$min_wait_time=($min_wait_time==1000000 ? "0" : $min_wait_time);
$min_position=($min_position==1000000 ? "0" : $min_position);
$min_call_length_wi=($min_call_length_wi==1000000 ? "0" : $min_call_length_wi);
$min_wait_time_wi=($min_wait_time_wi==1000000 ? "0" : $min_wait_time_wi);
$min_position_wi=($min_position_wi==1000000 ? "0" : $min_position_wi);

$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("All calls")." $NWB#VERM_ANSWERED-all_calls$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>"._QXZ("All calls").":</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("N. calls answered by operators").":</td>";
$HTML_output.="<td>".$total_answered_calls."</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Average call length").": $NWB#VERM_ANSWERED-all_calls_call_length$NWE</td>";
$HTML_output.="<td>".$average_call_length." s.</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Min call length").":</td>";
$HTML_output.="<td>".($min_call_length>=3600 ? floor($min_call_length/3600).":" : "").gmdate("i:s", $min_call_length)."</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Max call length").":</td>";
$HTML_output.="<td>".($max_call_length>=3600 ? floor($max_call_length/3600).":" : "").gmdate("i:s", $max_call_length)."</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Total call length").":</td>";
$HTML_output.="<td>".$total_call_length_fmt." H</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Average call waiting time").": $NWB#VERM_ANSWERED-all_calls_waiting_time$NWE</td>";
$HTML_output.="<td>".$average_wait_time." s</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Min waiting time").":</td>";
$HTML_output.="<td>".($min_wait_time>=3600 ? floor($min_wait_time/3600).":" : "").gmdate("i:s", $min_wait_time)."</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Max waiting time").":</td>";
$HTML_output.="<td>".($max_wait_time>=3600 ? floor($max_wait_time/3600).":" : "").gmdate("i:s", $max_wait_time)."</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Total waiting time").":</td>";
$HTML_output.="<td>".$total_wait_time_fmt." H</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Average initial position").": $NWB#VERM_ANSWERED-all_calls_position$NWE</td>";
$HTML_output.="<td>".$average_position."</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Min initial position").":</td>";
$HTML_output.="<td>".$min_position."</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Max initial position").":</td>";
$HTML_output.="<td>".$max_position."</td>";
$HTML_output.="</tr>\n";

### Commented out 5/19/22 - not needed
### $HTML_output.="<tr>";
### $HTML_output.="<td>"._QXZ("Coverage").": $NWB#VERM_ANSWERED-all_calls_coverage$NWE</td>";
### $HTML_output.="<td>".$coverage."</td>";
### $HTML_output.="</tr>\n";

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td colspan='2' class='export_row_cell'>"._QXZ("Export as")."...<a href=\"".$PHP_SELF."?download_rpt=all_calls\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td class='export_row_cell' colspan='2'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('ANSWERED', 'all_calls')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

$CSV_output["all_calls"]="\""._QXZ("All calls")."\",\"\"\n";
$CSV_output["all_calls"].="\""._QXZ("N. calls answered by operators").":\",\"$total_answered_calls\"\n";
$CSV_output["all_calls"].="\""._QXZ("Average call length").":\",\"$average_call_length s.\"\n";
$CSV_output["all_calls"].="\""._QXZ("Min call length").":\",\"".($min_call_length>=3600 ? floor($min_call_length/3600).":" : "").gmdate("i:s", $min_call_length)."\"\n";
$CSV_output["all_calls"].="\""._QXZ("Max call length").":\",\"".($max_call_length>=3600 ? floor($max_call_length/3600).":" : "").gmdate("i:s", $max_call_length)."\"\n";
$CSV_output["all_calls"].="\""._QXZ("Total call length").":\",\"".$total_call_length_fmt." H\"\n";
$CSV_output["all_calls"].="\""._QXZ("Average call waiting time").":\",\"".$average_wait_time." H\"\n";
$CSV_output["all_calls"].="\""._QXZ("Min waiting time").":\",\"".($min_wait_time>=3600 ? floor($min_wait_time/3600).":" : "").gmdate("i:s", $min_wait_time)."\"\n";
$CSV_output["all_calls"].="\""._QXZ("Max waiting time").":\",\"".($max_wait_time>=3600 ? floor($max_wait_time/3600).":" : "").gmdate("i:s", $max_wait_time)."\"\n";
$CSV_output["all_calls"].="\""._QXZ("Total waiting time").":\",\"".$total_wait_time_fmt." H\"\n";
$CSV_output["all_calls"].="\""._QXZ("Average initial position").":\",\"".$average_position."\"\n";
$CSV_output["all_calls"].="\""._QXZ("Min initial position").":\",\"".$min_position."\"\n";
$CSV_output["all_calls"].="\""._QXZ("Max initial position").":\",\"".$max_position."\"\n";

### Commented out 5/19/22 - not needed
### $CSV_output["all_calls"].="\"Coverage:\",\"".$coverage."\"\n";

######################

$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Calls fully within the given time interval")." $NWB#VERM_ANSWERED-all_calls_wi$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>"._QXZ("All calls").":</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("N. calls answered by operators").":</td>";
$HTML_output.="<td>".$total_answered_calls_wi."</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Average call length").": $NWB#VERM_ANSWERED-all_calls_call_length_wi$NWE</td>";
$HTML_output.="<td>".$average_call_length_wi." s.</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Min call length").":</td>";
$HTML_output.="<td>".($min_call_length_wi>=3600 ? floor($min_call_length_wi/3600).":" : "").gmdate("i:s", $min_call_length_wi)."</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Max call length").":</td>";
$HTML_output.="<td>".($max_call_length_wi>=3600 ? floor($max_call_length_wi/3600).":" : "").gmdate("i:s", $max_call_length_wi)."</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Total call length").":</td>";
$HTML_output.="<td>".$total_call_length_fmt_wi." H</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Average call waiting time").": $NWB#VERM_ANSWERED-all_calls_waiting_time_wi$NWE</td>";
$HTML_output.="<td>".$average_wait_time_wi." s</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Min waiting time").":</td>";
$HTML_output.="<td>".($min_wait_time_wi>=3600 ? floor($min_wait_time_wi/3600).":" : "").gmdate("i:s", $min_wait_time_wi)."</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Max waiting time").":</td>";
$HTML_output.="<td>".($max_wait_time_wi>=3600 ? floor($max_wait_time_wi/3600).":" : "").gmdate("i:s", $max_wait_time_wi)."</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Total waiting time").":</td>";
$HTML_output.="<td>".$total_wait_time_fmt_wi." H</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Average initial position").": $NWB#VERM_ANSWERED-all_calls_position_wi$NWE</td>";
$HTML_output.="<td>".$average_position_wi." H</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Min initial position").":</td>";
$HTML_output.="<td>".$min_position_wi." H</td>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Max initial position").":</td>";
$HTML_output.="<td>".$max_position_wi." H</td>";
$HTML_output.="</tr>\n";

### Commented out 5/19/22 - not needed
### $HTML_output.="<tr>";
### $HTML_output.="<td>"._QXZ("Coverage").": $NWB#VERM_ANSWERED-all_calls_coverage_wi$NWE</td>";
### $HTML_output.="<td>".$coverage_wi."</td>";
### $HTML_output.="</tr>\n";

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td colspan='2' class='export_row_cell'>"._QXZ("Export as")."...<a href=\"".$PHP_SELF."?download_rpt=all_calls_wi\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='2' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('ANSWERED', 'all_calls_wi')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

$CSV_output["all_calls_wi"]="\""._QXZ("All calls fully within the given time interval")."\",\"\"\n";
$CSV_output["all_calls_wi"].="\""._QXZ("N. calls answered by operators").":\",\"$total_answered_calls_wi\"\n";
$CSV_output["all_calls_wi"].="\""._QXZ("Average call length").":\",\"$average_call_length_wi s.\"\n";
$CSV_output["all_calls_wi"].="\""._QXZ("Min call length").":\",\"".($min_call_length_wi>=3600 ? floor($min_call_length_wi/3600).":" : "").gmdate("i:s", $min_call_length_wi)."\"\n";
$CSV_output["all_calls_wi"].="\""._QXZ("Max call length").":\",\"".($max_call_length_wi>=3600 ? floor($max_call_length_wi/3600).":" : "").gmdate("i:s", $max_call_length_wi)."\"\n";
$CSV_output["all_calls_wi"].="\""._QXZ("Total call length").":\",\"".$total_call_length_fmt_wi." H\"\n";
$CSV_output["all_calls_wi"].="\""._QXZ("Average call waiting time").":\",\"".$average_wait_time_wi." H\"\n";
$CSV_output["all_calls_wi"].="\""._QXZ("Min waiting time").":\",\"".($min_wait_time_wi>=3600 ? floor($min_wait_time_wi/3600).":" : "").gmdate("i:s", $min_wait_time_wi)."\"\n";
$CSV_output["all_calls_wi"].="\""._QXZ("Max waiting time").":\",\"".($max_wait_time_wi>=3600 ? floor($max_wait_time_wi/3600).":" : "").gmdate("i:s", $max_wait_time_wi)."\"\n";
$CSV_output["all_calls_wi"].="\""._QXZ("Total waiting time").":\",\"".$total_wait_time_fmt_wi." H\"\n";
$CSV_output["all_calls_wi"].="\""._QXZ("Average initial position").":\",\"".$average_position_wi."\"\n";
$CSV_output["all_calls_wi"].="\""._QXZ("Min initial position").":\",\"".$min_position_wi."\"\n";
$CSV_output["all_calls_wi"].="\""._QXZ("Max initial position").":\",\"".$max_position_wi."\"\n";

### Commented out 5/19/22 - not needed
### $CSV_output["all_calls_wi"].="\""._QXZ("Coverage").":\",\"".$coverage_wi."\"\n";
######################

#### AGENTS IN CAMPAINS/INGROUPS ####
switch ($sort_agents_on_queue)
	{
		case "ncalls":
			$ncalls_sort_value="ncalls desc";
			$agent_sort_value="agent";
			$ncalls_char="&#8593;";
			$agent_char="";
			asort($agent_call_counts);
			break;
		case "ncalls desc":
			$ncalls_sort_value="ncalls";
			$agent_sort_value="agent";
			$ncalls_char="&#8595;";
			$agent_char="";
			arsort($agent_call_counts);
			break;
		case "agent":
			$ncalls_sort_value="ncalls";
			$agent_sort_value="agent desc";
			$ncalls_char="";
			$agent_char="&#8593;";
			ksort($agent_call_counts, SORT_NATURAL | SORT_FLAG_CASE);
			break;
		case "agent desc":
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
if (preg_match('/ desc$/', $sort_agents_on_queue)) 
	{
	$sort_char="&#8595;"; 
	$reverse_link=preg_replace('/ desc$/', '', $sort_agents_on_queue);
	} 
else 
	{
	$sort_char="&#8593;"; 
	$reverse_link=$sort_agents_on_queue." desc";
	}
$sort_agents_on_queue_preg=preg_replace('/ desc$/', '', $sort_agents_on_queue);

$HTML_output.="<BR><h2 class='rpt_header' name='aoq_anchor' id='aoq_anchor'>"._QXZ("Agents in campaigns/ingroups").": $NWB#VERM_ANSWERED-agents_on_queue$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table border='0' cellpadding='0' cellspacing='0'>";
$HTML_output.="<tr><td width='60%'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";

# $HTML_output.="<th><a class='header_link' name='aoq_sort_agent' id='aoq_sort_agent' href='".$PHP_SELF."?sort_agents_on_queue=".$agent_sort_value."#aoq_sort_agent'>"._QXZ("Agent")." ".$agent_char."</a></th>";
# $HTML_output.="<th><a class='header_link' name='aoq_sort_ncalls' id='aoq_sort_ncalls' href='".$PHP_SELF."?sort_agents_on_queue=".$ncalls_sort_value."#aoq_sort_ncalls'>"._QXZ("N. Calls")." ".$ncalls_char."</a></th>";

	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Agent").("agent"==$sort_agents_on_queue_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_agents_on_queue').value='".("agent"==$sort_agents_on_queue_preg ? "$reverse_link" : "agent")."'; this.form.action+='#aoq_anchor'; this.form.submit()\"></th>\n";
	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("N. Calls").("ncalls"==$sort_agents_on_queue_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_agents_on_queue').value='".("ncalls"==$sort_agents_on_queue_preg ? "$reverse_link" : "ncalls")."'; this.form.action+='#aoq_anchor'; this.form.submit()\"></th>\n";

$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="<th>"._QXZ("Total call time")."</th>";
$HTML_output.="<th>"._QXZ("Average call time")."</th>";
$HTML_output.="</tr>\n";

$CSV_output["agents_on_queue"]="\""._QXZ("Agents on queue")."\"\n";
$CSV_output["agents_on_queue"].="\""._QXZ("Agent")."\",\""._QXZ("N. Calls")."\",\"\",\"...\",\""._QXZ("Total call time")."\",\""._QXZ("Average call time")."\"\n";

$highest_agent_call_count=(count($agent_call_counts)>0 ? max($agent_call_counts) : 0);

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

	$HTML_output.="<tr class='standard_font rpt_background_".($x%2)."'>";
	$HTML_output.="<td>".$agentName."</td>";
	$HTML_output.="<td>".$value."</td>";
	$HTML_output.="<td>".$call_count_percent." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".((100*$value)/$highest_agent_call_count)."%'></td>";
	$HTML_output.="<td>".$agent_total_call_length_fmt."</td>";
	$HTML_output.="<td>".$agent_avg_call_length_fmt."</td>";
	$HTML_output.="</tr>\n";

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
$HTML_output.="<td colspan='6' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('ANSWERED', 'agents_on_queue')\" title=\"Export as a CSV file\" value='CSV'><input type='hidden' name='sort_agents_on_queue' value='$sort_agents_on_queue'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
$HTML_output.="<div class=\"chart-container\" style=\"height:80vh; width:80vh\"><canvas id='agents_on_queue_display' role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";
$HTML_output.="<input type='hidden' name='sort_agents_on_queue' id='sort_agents_on_queue' value='sort_agents_on_queue'>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

######################

#### SERVICE LEVEL ####
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Service Level Agreement (inbound calls only)").": $NWB#VERM_ANSWERED-SLA$NWE</h2>";
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
$HTML_output.="</tr>\n";

$CSV_output["service_level_agreement"]="\""._QXZ("Service Level Agreement (inbound calls only)")."\"\n";
$CSV_output["service_level_agreement"].="\""._QXZ("Answer")."\",\""._QXZ("N. Calls")."\",\""._QXZ("Delta")."\",\""._QXZ("Percent")."\",\""._QXZ("Of Offered")."\",\"...\"\n";

# $SLA_query is in VERM_global_vars.inc
$svc_lvl_stmt="select $SLA_query from vicidial_closer_log $vicidial_closer_log_SQL and (user!='VDCL')"; #  and campaign_id in ($ingroup_str) and user in ('VDCL', $user_str)
$svc_lvl_rslt=mysqli_query($link, $svc_lvl_stmt);
if ($DB) {$HTML_output.="<B>$svc_lvl_stmt</B>";}
$svc_lvl_row=mysqli_fetch_assoc($svc_lvl_rslt);
#print_r($svc_lvl_row);
#	{
#	print "$interval - $sum\n";
#	}

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
	
	$HTML_output.="<tr class='standard_font rpt_background_".($x%2)."'>";
	$HTML_output.="<td>$int_name seconds:</td>";
	$HTML_output.="<td>".$sum."</td>";
	$HTML_output.="<td align='right'><i>$delta</i>&nbsp;&nbsp;</td>";
	$HTML_output.="<td align='right'>".sprintf("%.1f", MathZDC((100*$sum), $answered_inbound_calls))." %</td>";
	$HTML_output.="<td align='right'>".sprintf("%.1f", MathZDC((100*$sum), $total_inbound_calls))." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".MathZDC((100*$sum), $answered_inbound_calls)."%'></td>";
	$HTML_output.="</tr>\n";

	$CSV_output["service_level_agreement"].="\"$int_name seconds:\",\"$sum\",\"$delta\",\"".sprintf("%.1f", MathZDC((100*$sum), $answered_inbound_calls))." %\",\"".sprintf("%.1f", MathZDC((100*$sum), $total_inbound_calls))." %\"\n";

	$service_level_agreement_graph_labels.="\"$int_name\",";
	$service_level_agreement_graph_delta_data.="\"".($prev_int ? ($sum-$prev_int) : "0")."\",";
	$service_level_agreement_graph_delta_bgcolor.="\"#33CCCC\",";
	$service_level_agreement_graph_delta_hovborder.="\"#66FFFF\",";
	$service_level_agreement_graph_percent_data.="\"".sprintf("%.1f", MathZDC((100*$sum), $answered_inbound_calls))."\",";
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
$HTML_output.="<td colspan='2' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('ANSWERED', 'service_level_agreement')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
$HTML_output.="<div class=\"chart-container\" style=\"height:80vh; width:80vh\"><canvas id='service_level_agreement_display' role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

######################

#### DISCONNECTIONS ###
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Disconnection causes").": $NWB#VERM_ANSWERED-disconnections$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table border='0' cellpadding='0' cellspacing='5'>";
$HTML_output.="<tr><td width='60%'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>"._QXZ("Cause")."</th>";
$HTML_output.="<th>"._QXZ("N. Calls")."</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="</tr>\n";

$CSV_output["disconnection_causes"]="\""._QXZ("Disconnection causes").":\"\n";
$CSV_output["disconnection_causes"].="\""._QXZ("Cause")."\",\""._QXZ("N. Calls")."\",\"\",\"...\"\n";

$disconnection_causes_graph_labels="[";
$disconnection_causes_graph_data="[";
$disconnection_causes_graph_bgcolor="["; # SAME FOR hoverBackgroundColor
$disconnection_causes_graph_hovborder="[";

$max_term_reason=(count($term_reasons)>0 ? max($term_reasons) : 0);
$x=0;
foreach ($term_reasons as $reason => $value)
	{
	$term_reason_pct=sprintf("%.1f", (100*$value)/$total_answered_calls);
	$HTML_output.="<tr class='standard_font rpt_background_".($x%2)."'>";
	$HTML_output.="<td class='rpt_cell'>".$reason."</td>";
	$HTML_output.="<td class='rpt_cell'>".$value."</td>";
	$HTML_output.="<td class='rpt_cell'>".$term_reason_pct." %</td>";
	$HTML_output.="<td class='rpt_cell' width='200'><img src='images/shade-histo.gif' height='10' width='".(100*($value/$max_term_reason))."%'></td>";
	$HTML_output.="</tr>\n";

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
$HTML_output.="<td colspan='4' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('ANSWERED', 'disconnection_causes')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
$HTML_output.="<div class=\"chart-container\" style=\"height:50vh; width:50vh\"><canvas id='disconnection_causes_display' role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

######################

#### TRANSFERS ###
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Transfers").": $NWB#VERM_ANSWERED-transfers$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>"._QXZ("Transfer to").":</th>";
$HTML_output.="<th>"._QXZ("N. Calls")."</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="</tr>\n";

$CSV_output["transfers"]="\""._QXZ("Transfers").":\"\n";
$CSV_output["transfers"].="\""._QXZ("Transfer to").":\",\""._QXZ("N. Calls")."\",\"\",\"...\"\n";

$transferred_counts=array();
$total_transfers=0;
$xfer_stmt="select campaign_id, count(*) from vicidial_xfer_log $vicidial_xfer_log_SQL group by campaign_id order by campaign_id";
# print $xfer_stmt."<BR>";
$xfer_rslt=mysql_to_mysqli($xfer_stmt, $link);
while ($xfer_row=mysqli_fetch_row($xfer_rslt))
	{
	$transferred_counts["$xfer_row[0]"]+=$xfer_row[1];
	$total_transfers+=$xfer_row[1];
	}
# print_r($transferred_counts);

$max_xfer_count=(count($transferred_counts)>0 ? max($transferred_counts) : 0);
$x=0;
foreach ($transferred_counts as $reason => $value)
	{
	$xfer_pct=sprintf("%.1f", (100*$value)/$total_transfers); # $total_answered_calls
	$HTML_output.="<tr class='standard_font rpt_background_".($x%2)."'>";
	$HTML_output.="<td>".$reason."</td>";
	$HTML_output.="<td>".$value."</td>";
	$HTML_output.="<td>".$xfer_pct." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(100*($value/$max_xfer_count))."%'></td>";
	$HTML_output.="</tr>\n";

	$CSV_output["transfers"].="\"".$reason."\",\"".$value."\",\"".$xfer_pct." %\"\n";
	
	$x++;
	}
$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='4'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=transfers\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='4' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('ANSWERED', 'transfers')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

######################

#### ANSWERED CALLS BY CAMPAIGN/INGROUP ###
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Answered calls, by campaign/ingroup").": $NWB#VERM_ANSWERED-calls_by_queue$NWE</h2>";
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
$HTML_output.="</tr>\n";

$CSV_output["answered_calls_by_queue"]="\""._QXZ("Answered calls, by queue").":\"\n";
$CSV_output["answered_calls_by_queue"].="\"\",\""._QXZ("Campaign/ingroup")."\",\""._QXZ("N. Calls")."\",\"\",\"...\"\n";

$answered_calls_by_queue_graph_labels="[";
$answered_calls_by_queue_graph_data="[";
$answered_calls_by_queue_graph_bgcolor="["; # SAME FOR hoverBackgroundColor
$answered_calls_by_queue_graph_hovborder="[";

$max_queue_count=(count($queue_stats)>0 ? max($queue_stats) : 0);
ksort($queue_stats);
$x=0;
foreach ($queue_stats as $queue => $value)
	{
	$queue_pct=sprintf("%.1f", (100*$value)/$total_answered_calls);
	$HTML_output.="<tr class='standard_font rpt_background_".($x%2)."'>";
	$HTML_output.="<td>".($SOME_IMAGE_GOES_HERE)."</td>";
	$HTML_output.="<td>".$queue_names["$queue"]."</td>";
	$HTML_output.="<td>".$value."</td>";
	$HTML_output.="<td>".$queue_pct." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(100*($value/$max_queue_count))."%'></td>";
	$HTML_output.="</tr>\n";
	$CSV_output["answered_calls_by_queue"].="\"\",\"".$queue_names["$queue"]."\",\"".$value."\",\"".$queue_pct." %\",\"\"\n";

	$answered_calls_by_queue_graph_labels.="\"".$queue_names["$queue"]."\",";
	$answered_calls_by_queue_graph_data.="\"$value\",";
	$answered_calls_by_queue_graph_bgcolor.="\"".$background_colors[$x]."\",";
	$answered_calls_by_queue_graph_hovborder.="\"".$border_colors[$x]."\",";

	$x++;
	}
$answered_calls_by_queue_graph_labels=preg_replace('/,$/', '', $answered_calls_by_queue_graph_labels)."]";
$answered_calls_by_queue_graph_data=preg_replace('/,$/', '', $answered_calls_by_queue_graph_data)."]";
$answered_calls_by_queue_graph_bgcolor=preg_replace('/,$/', '', $answered_calls_by_queue_graph_bgcolor)."]";
$answered_calls_by_queue_graph_hovborder=preg_replace('/,$/', '', $answered_calls_by_queue_graph_hovborder)."]";

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='5'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=answered_calls_by_queue\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='5' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('ANSWERED', 'answered_calls_by_queue')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
$HTML_output.="<div class=\"chart-container\" style=\"height:40vh; width:60vh\"><canvas id='answered_calls_by_queue_display' role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

#####################

#### ANSWERED CALLS BY DIRECTION ###
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Answered calls, by direction").": $NWB#VERM_ANSWERED-calls_by_direction$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table border='0' cellpadding='0' cellspacing='5'>";
$HTML_output.="<tr><td width='60%'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>"._QXZ("Direction")."</th>";
$HTML_output.="<th>"._QXZ("N. Calls")."</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="</tr>\n";

$CSV_output["answered_calls_by_direction"]="\""._QXZ("Answered calls, by direction").":\"\n";
$CSV_output["answered_calls_by_direction"].="\"\",\""._QXZ("Direction")."\",\""._QXZ("N. Calls")."\",\"\",\"...\"\n";

$answered_calls_by_direction_graph_labels="[";
$answered_calls_by_direction_graph_data="[";
$answered_calls_by_direction_graph_bgcolor="["; # SAME FOR hoverBackgroundColor
$answered_calls_by_direction_graph_hovborder="[";

$max_call_direction=(count($call_directions)>0 ? max($call_directions) : 0);
ksort($call_directions);
$call_directions["Any calls"]=0;
$x=0;
foreach ($call_directions as $direction => $value)
	{
	$direction_pct=sprintf("%.1f", MathZDC((100*$value), $total_answered_calls));
	$HTML_output.="<tr class='standard_font rpt_background_".($x%2)."'>";
	$HTML_output.="<td>".($SOME_IMAGE_GOES_HERE)."</td>";
	$HTML_output.="<td>".$direction."</td>";
	$HTML_output.="<td>".$value."</td>";
	$HTML_output.="<td>".$direction_pct." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(100*MathZDC($value, $max_call_direction))."%'></td>";
	$HTML_output.="</tr>\n";

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
$HTML_output.="<td colspan='5' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('ANSWERED', 'answered_calls_by_direction')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
$HTML_output.="<div class=\"chart-container\" style=\"height:40vh; width:40vh\"><canvas id='answered_calls_by_direction_display' role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

#####################

/*
#### ANSWERED CALLS BY STINTS ###
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Answered calls, by stints").":</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>"._QXZ("Number of stints")."</th>";
$HTML_output.="<th>"._QXZ("N. Calls")."</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="</tr>\n";

$CSV_output["answered_calls_by_stints"]="\""._QXZ("Answered calls, by stints").":\"\n";
$CSV_output["answered_calls_by_stints"].="\""._QXZ("Number of stints")."\",\""._QXZ("N. Calls")."\",\"\",\"...\"\n";

$max_call_stint=max($call_stints);
$x=0;
foreach ($call_stints as $stint => $value)
	{
	$stint_pct=sprintf("%.1f", (100*$value)/$total_answered_calls);
	$HTML_output.="<tr class='standard_font rpt_background_".($x%2)."'>";
	$HTML_output.="<td>".$stint."</td>";
	$HTML_output.="<td>".$value."</td>";
	$HTML_output.="<td>".$stint_pct." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(100*($value/$max_call_stint))."%'></td>";
	$HTML_output.="</tr>\n";

	$CSV_output["answered_calls_by_stints"].="\"".$stint."\",\"".$value."\",\"".$stint_pct." %\",\"\"\n";

	$x++;
	}
$HTML_output.="<tr class='export_row'>";
$HTML_output.="<td class='export_row_cell' colspan='4'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=answered_calls_by_stints\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";
#####################
*/

#### ANSWERED CALLS BY POSITION ###
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Queue position").": $NWB#VERM_ANSWERED-queue_position$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table border='0' cellpadding='0' cellspacing='5'>";
$HTML_output.="<tr><td width='70%'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>"._QXZ("Position at enter").":</th>";
$HTML_output.="<th>"._QXZ("N. Calls")."</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="</tr>\n";

$CSV_output["queue_position"]="\""._QXZ("Queue position").":\"\n";
$CSV_output["queue_position"].="\""._QXZ("Position at enter").":\",\""._QXZ("N. Calls")."\",\"\",\"...\"\n";

$queue_position_graph_labels="[";
$queue_position_graph_data="[";
$queue_position_graph_bgcolor="["; # SAME FOR hoverBackgroundColor
$queue_position_graph_hovborder="[";

$max_queue_count=(count($queue_positions)>0 ? max($queue_positions) : 0);
$x=0;
foreach ($queue_positions as $position => $value)
	{
	$position_pct=sprintf("%.1f", MathZDC((100*$value), $total_answered_calls));
	$HTML_output.="<tr class='standard_font rpt_background_".($x%2)."'>";
	$HTML_output.="<td>".$position."</td>";
	$HTML_output.="<td>".$value."</td>";
	$HTML_output.="<td>".$position_pct." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(100*MathZDC($value, $max_queue_count))."%'></td>";
	$HTML_output.="</tr>\n";

	$CSV_output["queue_position"].="\"".$position."\",\"".$value."\",\"".$position_pct." %\",\"\"\n";

	$queue_position_graph_labels.="\"".$position."\",";
	$queue_position_graph_data.="\"$value\",";
	$queue_position_graph_bgcolor.="\"".$background_colors[$x]."\",";
	$queue_position_graph_hovborder.="\"".$border_colors[$x]."\",";

	$x++;
	}
$queue_position_graph_labels=preg_replace('/,$/', '', $queue_position_graph_labels)."]";
$queue_position_graph_data=preg_replace('/,$/', '', $queue_position_graph_data)."]";
$queue_position_graph_bgcolor=preg_replace('/,$/', '', $queue_position_graph_bgcolor)."]";
$queue_position_graph_hovborder=preg_replace('/,$/', '', $queue_position_graph_hovborder)."]";

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='4'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=queue_position\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='4' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('ANSWERED', 'queue_position')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
$HTML_output.="<div  style=\"height:20px; width:300px\"><canvas id='queue_position_display' style=\"height:200px; width:300px\" role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

#####################

##### IVR STATS #####
$ivr_stmt="select uniqueid,extension,start_time,comment_a,comment_b,comment_d,UNIX_TIMESTAMP(start_time),phone_ext from live_inbound_log $live_inbound_log_SQL and comment_a IN('CALLMENU') order by uniqueid,start_time";
if ($DB) {$HTML_output.="<B>$ivr_stmt</B>";}
if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
$ivr_rslt=mysql_to_mysqli($ivr_stmt, $link);
$ivr_paths=array(); # 0 - total calls, 1 - total time, 2 - min time, 3 - max time
$ivr_counts=array();
$untracked_calls=$total_answered_calls;
while ($ivr_row=mysqli_fetch_array($ivr_rslt))
	{
	$uniqueid=$ivr_row[0];

	# if (in_array($uniqueid, $uniqueid_array))
	if (isset($uniqueid_array["$uniqueid"]))
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
$untracked_ivr_pct=sprintf("%.1f", MathZDC((100*$untracked_calls), $total_answered_calls));

$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("IVR selection").": $NWB#VERM_ANSWERED-ivr_selection$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table border='0' cellpadding='0' cellspacing='5'>";
$HTML_output.="<tr><td width='70%'>";
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
$HTML_output.="</tr>\n";

$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Untracked")."</td>";
$HTML_output.="<td>$untracked_calls</td>";
$HTML_output.="<td>".$untracked_ivr_pct." %</td>";
$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(100*MathZDC($untracked_calls, $total_answered_calls))."%'></td>";
$HTML_output.="<td>&nbsp;</td>";
$HTML_output.="<td>&nbsp;</td>";
$HTML_output.="<td>&nbsp;</td>";
$HTML_output.="<td>&nbsp;</td>";
$HTML_output.="</tr>\n";

$ivr_selection_graph_labels="[\""._QXZ("Untracked")."\",";
$ivr_selection_graph_data="[\"$untracked_calls\",";
$ivr_selection_graph_bgcolor="[\"".$background_colors[0]."\","; # SAME FOR hoverBackgroundColor
$ivr_selection_graph_hovborder="[\"".$border_colors[0]."\",";

$CSV_output["ivr_selection"]="\""._QXZ("IVR selection").":\"\n";
$CSV_output["ivr_selection"].="\""._QXZ("IVR")."\",\""._QXZ("N. Calls")."\",\"\",\"\",\""._QXZ("Total Duration")."\",\""._QXZ("Average Duration")."\",\""._QXZ("Min Duration")."\",\""._QXZ("Max Duration")."\"\n";
$CSV_output["ivr_selection"].="\""._QXZ("Untracked")."\",\"$untracked_calls\",\"".$untracked_ivr_pct." %\",\"\",\"\",\"\",\"\",\"\"\n";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

ksort($ivr_paths);
$x=1;
foreach ($ivr_paths as $pathname => $value)
	{
	$ivr_pct=sprintf("%.1f", (100*$value[0])/$total_answered_calls);
	$total_duration=($value[1]>=3600 ? floor($value[1]/3600).":" : "").gmdate("i:s", $value[1]);
	$avg_call_length=round($value[1]/$value[0]);
	$average_duration=($avg_call_length>=3600 ? floor($avg_call_length/3600).":" : "").gmdate("i:s", $avg_call_length);
	$min_duration=($value[2]>=3600 ? floor($value[2]/3600).":" : "").gmdate("i:s", $value[2]);
	$max_duration=($value[3]>=3600 ? floor($value[3]/3600).":" : "").gmdate("i:s", $value[3]);

	$HTML_output.="<tr class='standard_font rpt_background_".($x%2)."'>";
	$HTML_output.="<td>".$pathname."</td>";
	$HTML_output.="<td>".$value[0]."</td>";
	$HTML_output.="<td>".$ivr_pct." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(100*($value[0]/$total_answered_calls))."%'></td>";
	$HTML_output.="<td>".$total_duration."</td>";
	$HTML_output.="<td>".$average_duration."</td>";
	$HTML_output.="<td>".$min_duration."</td>";
	$HTML_output.="<td>".$max_duration."</td>";
	$HTML_output.="</tr>\n";

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
$HTML_output.="<td colspan='8' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('ANSWERED', 'ivr_selection')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
$HTML_output.="<div class=\"chart-container\" style=\"height:60vh; width:30vw\"><canvas id='ivr_selection_display'  style=\"height:60vh; width:30vw\" role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

#####################

###### Inbound DID used #####
$did_stmt="select extension,uniqueid from vicidial_did_log where call_date>='$start_date $start_time' and call_date<='$end_date $end_time' order by extension";
$did_rslt=mysql_to_mysqli($did_stmt, $link);
if ($DB) {$HTML_output.="<B>$did_stmt</B>";}
$did_counts=array(); # 0 - total calls, 1 - total time, 2 - min time, 3 - max time
# $did_counts["Untracked"]=$untracked_calls2
$untracked_did_calls=$total_answered_calls;
while ($did_row=mysqli_fetch_array($did_rslt))
	{
	# if(in_array($did_row["uniqueid"], $uniqueid_array))
	if (isset($uniqueid_array["$did_row[uniqueid]"]))
		{
		$did_counts["$did_row[extension]"]++;
		$untracked_did_calls--;
		}
	}
$max_did=(count($did_counts)>0 ? max($did_counts) : 0);
if ($max_did<$untracked_did_calls) {$max_did=$untracked_did_calls;}

$untracked_did_pct=sprintf("%.1f", MathZDC((100*$untracked_did_calls), $total_answered_calls));
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Inbound DID used").":</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table border='0' cellpadding='0' cellspacing='5'>";
$HTML_output.="<tr><td width='60%'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>"._QXZ("DID").": $NWB#VERM_ANSWERED-did_used$NWE</th>";
$HTML_output.="<th>"._QXZ("N. Calls")."</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="</tr>\n";
$HTML_output.="<tr>";
$HTML_output.="<td>"._QXZ("Untracked")."</td>";
$HTML_output.="<td>$untracked_did_calls</td>";
$HTML_output.="<td>$untracked_did_pct %</td>";
$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".$untracked_did_pct."%'></td>";
$HTML_output.="</tr>\n";

$CSV_output["did_used"]="\""._QXZ("Inbound DID used").":\"\n";
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
	$did_pct=sprintf("%.1f", (100*$value)/$total_answered_calls);
	$HTML_output.="<tr class='standard_font rpt_background_".($x%2)."'>";
	$HTML_output.="<td>".$did." - ".$did_pattern_info["$did"]."</td>";
	$HTML_output.="<td>$value</td>";
	$HTML_output.="<td>$did_pct %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".$did_pct."%'></td>";
	$HTML_output.="</tr>\n";

	$CSV_output["did_used"].="\"".$did." - ".$did_pattern_info["$did"]."\",\"$value\",\"".$did_pct." %\",\"\"\n";

	$did_used_graph_labels.="\"".$did." - ".$did_pattern_info["$did"]."\",";
	$did_used_graph_data.="\"$value\",";
	$did_used_graph_bgcolor.="\"".$background_colors[$x]."\",";
	$did_used_graph_hovborder.="\"".$border_colors[$x]."\",";

	if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

	$x++;
	}
$did_used_graph_labels=preg_replace('/,$/', '', $did_used_graph_labels)."]";
$did_used_graph_data=preg_replace('/,$/', '', $did_used_graph_data)."]";
$did_used_graph_bgcolor=preg_replace('/,$/', '', $did_used_graph_bgcolor)."]";
$did_used_graph_hovborder=preg_replace('/,$/', '', $did_used_graph_hovborder)."]";

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='4'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=did_used\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td colspan='4' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('ANSWERED', 'did_used')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
$HTML_output.="<div class=\"chart-container\" style=\"height:40vh; width:40vw\"><canvas id='did_used_display' style=\"height:40vh; width:40vw\" role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

######################

#### MUSIC ON HOLD ####
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Parked Calls by Agent").": $NWB#VERM_ANSWERED-parked_calls_by_agent$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>"._QXZ("Agent")."</th>";
$HTML_output.="<th>"._QXZ("Total Parks")."</th>";
$HTML_output.="<th>"._QXZ("Parks per Call")."</th>";
$HTML_output.="<th>"._QXZ("Total Duration")."</th>";
$HTML_output.="<th>"._QXZ("Average Duration")."</th>";
$HTML_output.="</tr>\n";

$CSV_output["moh_agent"]="\""._QXZ("Music-on-Hold by Agent").":\"\n";
$CSV_output["moh_agent"].="\""._QXZ("Agent")."\",\""._QXZ("Total Parks")."\",\""._QXZ("Parks per Call")."\",\""._QXZ("Total Duration")."\",\""._QXZ("Average Duration")."\"\n";

$parked_counts=array();
$total_parks=0;
$park_stmt="select user, sum(parked_sec), count(*) from park_log $park_log_SQL group by user order by user";
$park_rslt=mysql_to_mysqli($park_stmt, $link);
while ($park_row=mysqli_fetch_row($park_rslt))
	{
	$parked_counts["$park_row[0]"][0]=$park_row[2];
	$parked_counts["$park_row[0]"][1]=$park_row[1];
	$total_parks+=$park_row[2];
	}

$x=0;
foreach ($parked_counts as $agent => $value)
	{
	$agentName=($show_full_agent_info ? "$agent - ".$fullname_info["$agent"] : $fullname_info["$agent"]);

	$parks_per_call=sprintf("%.1f", MathZDC($value[0], $agent_call_counts["$agent"]));
	$average_duration=sprintf("%.1f", MathZDC($value[1], $value[0])); # Use $agent_call_counts["$agent"] if want average duration per CALL, not PARK.
	$HTML_output.="<tr class='standard_font rpt_background_".($x%2)."'>";
	$HTML_output.="<td>".$agentName."</td>";
	$HTML_output.="<td>".$value[0]."</td>";
	$HTML_output.="<td>".$parks_per_call."</td>";
	$HTML_output.="<td>".($value[1]>=3600 ? floor($value[1]/3600).":" : "").gmdate("i:s", $value[1])."</td>";
	$HTML_output.="<td>".$average_duration."</td>";
	$HTML_output.="</tr>\n";

	$CSV_output["moh_agent"].="\"".$agentName."\",\"".$value[0]."\",\"".$parks_per_call."\",\"".($value[1]>=3600 ? floor($value[1]/3600).":" : "").gmdate("i:s", $value[1])."\",\"".$average_duration." s\"\n";

	$x++;
	}
$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='5'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=moh_agent\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td class='export_row_cell' colspan='5'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('ANSWERED', 'moh_agent')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>\n";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
#####################

if ($download_rpt)
	{
	$data_to_download=$CSV_output["$download_rpt"];

	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "ANSWERS_RPT_".$download_rpt."_".$FILE_TIME.".csv";
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
var agents_on_queue_data = {
		labels: <?php echo $agents_on_queue_graph_labels; ?>,
		datasets: [
			{
				label: "",
				fill: false,
				data: <?php echo $agents_on_queue_graph_data; ?>,
				backgroundColor: <?php echo $agents_on_queue_graph_bgcolor; ?>,
				hoverBackgroundColor: <?php echo $agents_on_queue_graph_bgcolor; ?>,
				hoverBorderColor: <?php echo $agents_on_queue_graph_hovborder; ?>,
				hoverBorderWidth: 2,
				tension: 0,
				fillColor: "rgba(255,230,230,0.5)",
				borderColor: "rgba(255,255,255,0.8)",
			}
		]
	}
var agents_on_queue_canvas = document.getElementById("agents_on_queue_display");
var agents_on_queue_graph = new Chart(agents_on_queue_canvas, {type: 'pie', options: { plugins: {legend: { position: 'right', maxHeight:900, labels: {font:{size:10}} }}},
data: agents_on_queue_data});

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

var answered_calls_by_queue_data = {
		labels: <?php echo $answered_calls_by_queue_graph_labels; ?>,
		datasets: [
			{
				label: "",
				fill: false,
				data: <?php echo $answered_calls_by_queue_graph_data; ?>,
				backgroundColor: <?php echo $answered_calls_by_queue_graph_bgcolor; ?>,
				hoverBackgroundColor: <?php echo $answered_calls_by_queue_graph_bgcolor; ?>,
				hoverBorderColor: <?php echo $answered_calls_by_queue_graph_hovborder; ?>,
				hoverBorderWidth: 2,
				tension: 0,
				fillColor: "rgba(255,230,230,0.5)",
				borderColor: "rgba(255,255,255,0.8)",
			}
		]
	}
var answered_calls_by_queue_canvas = document.getElementById("answered_calls_by_queue_display");
var answered_calls_by_queue_graph = new Chart(answered_calls_by_queue_canvas, {type: 'pie', options: { plugins: {legend: { position: 'right' }}},
data: answered_calls_by_queue_data});

var answered_calls_by_direction_data = {
		labels: <?php echo $answered_calls_by_direction_graph_labels; ?>,
		datasets: [
			{
				label: "",
				fill: false,
				data: <?php echo $answered_calls_by_direction_graph_data; ?>,
				backgroundColor: <?php echo $answered_calls_by_direction_graph_bgcolor; ?>,
				hoverBackgroundColor: <?php echo $answered_calls_by_direction_graph_bgcolor; ?>,
				hoverBorderColor: <?php echo $answered_calls_by_direction_graph_hovborder; ?>,
				hoverBorderWidth: 2,
				tension: 0,
				fillColor: "rgba(255,230,230,0.5)",
				borderColor: "rgba(255,255,255,0.8)",
			}
		]
	}
var answered_calls_by_direction_canvas = document.getElementById("answered_calls_by_direction_display");
var answered_calls_by_direction_graph = new Chart(answered_calls_by_direction_canvas, {type: 'pie', options: { plugins: {legend: { position: 'right' }}},
data: answered_calls_by_direction_data});

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
