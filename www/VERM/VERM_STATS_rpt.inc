<?php
# VERM_STATS_rpt.inc - Vicidial Enhanced Reporting stats report
#
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGELOG:
# 220825-1616 - First build
# 240801-1130 - Code updates for PHP8 compatibility
#

# DROP, WAITTO, TIMEOT are unanswered statuses, not NANQUE
# $PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
# $PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
# $PHP_SELF=$_SERVER['PHP_SELF'];
# $PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);

if (isset($_GET["sort_agents_on_queue"]))			{$sort_agents_on_queue=$_GET["sort_agents_on_queue"];}
	elseif (isset($_POST["sort_agents_on_queue"]))	{$sort_agents_on_queue=$_POST["sort_agents_on_queue"];}

if ($non_latin < 1)
	{
	$sort_agents_on_queue = preg_replace('/[^\s-_0-9a-zA-Z]/','',$sort_agents_on_queue);
	}
else
	{
	$sort_agents_on_queue = preg_replace('/[^\s-_0-9\p{L}]/u','',$sort_agents_on_queue);
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
		array_push($uniqueid_array, $calls_row["uniqueid"]);
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

$average_call_length=sprintf("%.1f", $total_call_length/$total_answered_calls); # Total calls instead?
$average_wait_time=sprintf("%.1f", $total_wait_time/$total_answered_calls); # Total calls instead?
$average_position=sprintf("%.1f", $total_position/$total_answered_calls);
$total_call_length_fmt=sprintf("%.1f", ($total_call_length/3600));
$total_wait_time_fmt=sprintf("%.1f", ($total_wait_time/3600));

$average_call_length_wi=sprintf("%.1f", $total_call_length_wi/$total_answered_calls_wi); # Total calls instead?
$average_wait_time_wi=sprintf("%.1f", $total_wait_time_wi/$answered_call_wis); # Total calls instead?
$average_position_wi=sprintf("%.1f", $total_position_wi/$total_answered_calls_wi);
$total_call_length_fmt_wi=sprintf("%.1f", ($total_call_length_wi/3600));
$total_wait_time_fmt_wi=sprintf("%.1f", ($total_wait_time_wi/3600));

# Wipe unadjusted variables (i.e. no records)
$min_call_length=($min_call_length==1000000 ? "0" : $min_call_length);
$min_wait_time=($min_wait_time==1000000 ? "0" : $min_wait_time);
$min_position=($min_position==1000000 ? "0" : $min_position);
$min_call_length_wi=($min_call_length_wi==1000000 ? "0" : $min_call_length_wi);
$min_wait_time_wi=($min_wait_time_wi==1000000 ? "0" : $min_wait_time_wi);
$min_position_wi=($min_position_wi==1000000 ? "0" : $min_position_wi);

$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("All calls")."</h2>";
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
$HTML_output.="<td>"._QXZ("Average call length").":</td>";
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
$HTML_output.="<td>"._QXZ("Average call waiting time").":</td>";
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
$HTML_output.="<td>"._QXZ("Average initial position").":</td>";
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
### $HTML_output.="<td>Coverage:</td>";
### $HTML_output.="<td>".$coverage."</td>";
### $HTML_output.="</tr>\n";

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td colspan='2' class='export_row_cell'>"._QXZ("Export as")."...<a href=\"".$PHP_SELF."?download_rpt=all_calls\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td class='export_row_cell' colspan='2'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('STATS', 'all_calls')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";

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

#### AGENTS ON QUEUE ####
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

$HTML_output.="<BR><h2 class='rpt_header' name='aoq_anchor' id='aoq_anchor'>"._QXZ("Agents on queue").":</h2>";
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
$HTML_output.="<td colspan='6' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('STATS', 'agents_on_queue')\" title=\"Export as a CSV file\" value='CSV'><input type='hidden' name='sort_agents_on_queue' value='$sort_agents_on_queue'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
$HTML_output.="<div class=\"chart-container\" style=\"height:80vh; width:80vh\"><canvas id='agents_on_queue_display' role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";
$HTML_output.="<input type='hidden' name='sort_agents_on_queue' id='sort_agents_on_queue' value='sort_agents_on_queue'>";
######################

#### SERVICE LEVEL ####
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Service Level Agreement (inbound calls only)").":</h2>";
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

$CSV_output["service_level_agreement"]="\""._QXZ("Service Level Agreement (inbound calls only)").\"\n";
$CSV_output["service_level_agreement"].="\""._QXZ("Answer").\",\""._QXZ("N. Calls").\",\""._QXZ("Delta").\",\""._QXZ("Percent").\",\""._QXZ("Of Offered").\",\"...\"\n";

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
	$HTML_output.="<td>$int_name "._QXZ("seconds").:</td>";
	$HTML_output.="<td>".$sum."</td>";
	$HTML_output.="<td align='right'><i>$delta</i>&nbsp;&nbsp;</td>";
	$HTML_output.="<td align='right'>".sprintf("%.1f", ((100*$sum)/$answered_inbound_calls))." %</td>";
	$HTML_output.="<td align='right'>".sprintf("%.1f", ((100*$sum)/$total_inbound_calls))." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".((100*$sum)/$answered_inbound_calls)."%'></td>";
	$HTML_output.="</tr>\n";

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
$HTML_output.="<td colspan='2' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('STATS', 'service_level_agreement')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
$HTML_output.="<div class=\"chart-container\" style=\"height:80vh; width:80vh\"><canvas id='service_level_agreement_display' role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";
######################

############ ALL UNANSWERED CALLLS ###########
##############################################


#### DISCONNECTIONS ###
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Disconnection causes").":</h2>";
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
$HTML_output.="<td colspan='4' class='export_row_cell'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('STATS', 'disconnection_causes')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>\n";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
$HTML_output.="<div class=\"chart-container\" style=\"height:50vh; width:50vh\"><canvas id='disconnection_causes_display' role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";
######################


$ACD_queue_counts=array();
$ACD_terminal_counts=array();

# ONLY COUNT ANSWERED CALLS
$stmt="select campaign_id, user, status, 0 as ring_time from vicidial_log $vicidial_log_SQL $and_NANQUE_clause UNION ALL select campaign_id, user, status, queue_seconds as ring_time from vicidial_closer_log $vicidial_closer_log_SQL $and_NANQUE_clause";
if ($DB) {$HTML_output.="<B>$stmt</B>";}
$rslt=mysql_to_mysqli($stmt, $link);
while ($row=mysqli_fetch_array($rslt))
	{
	$campaign_id=$row["campaign_id"];
	$user=$row["user"];
	$status=$row["status"];
	$ring_time=$row["ring_time"];

	if (preg_match('/VDAD|VDCL/', $user))  # && preg_match('/^DROP$|TIMEOT|WAITTO/', $status)
		{
		# UNANSWERED, DO NOTHING
		$unanswered++;
		}
	else
		{
		$ACD_queue_counts["$campaign_id"]["calls"]++;
		$ACD_queue_counts["$campaign_id"]["ring_time"]+=$ring_time;
		
		$ACD_terminal_counts["$user"]["calls"]++;
		$ACD_terminal_counts["$user"]["ring_time"]+=$ring_time;
		}
	}

ksort($ACD_queue_counts);
ksort($ACD_terminal_counts);


##### ACD ATTEMPTS BY TERMINAL #####
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("ACD attempts by terminal").":</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='details_table'>";
$HTML_output.="<tr><th>"._QXZ("Queue")."</th><th>"._QXZ("N. lost")."</th><th>"._QXZ("Avr ring")."</th><th>"._QXZ("Ring (s)")."</th><th>"._QXZ("N. Taken</th><th>"._QXZ("Avg ring")."</th><th>"._QXZ("Ring (s)")."</th></tr>";

$CSV_output["acd_attempts_by_terminal"]="\""._QXZ("Queue")."\",\""._QXZ("N. lost")."\",\""._QXZ("Avg ring")."\",\""._QXZ("Ring (s)")."\",\""._QXZ("N. Taken")."\",\""._QXZ("Avg ring")."\",\""._QXZ("Ring (s)")."\"\n";
$i=0;
foreach ($ACD_terminal_counts as $queue => $data)
	{
	if (!$data["lost_ring_time"] || !$data["lost"]) 
		{
		$avg_lost_time=" - ";
		}
	else
		{
		$avg_lost_time=round($data["lost_ring_time"]/$data["lost"]);
		$avg_lost_time=($avg_lost_time>=3600 ? intval(floor($avg_lost_time/3600)).date(":i:s", $avg_lost_time) : intval(date("i", $avg_lost_time)).":".date("s", $avg_lost_time));
		}

	if (!$data["ring_time"] || !$data["calls"]) 
		{
		$avg_ring_time="0:00";
		}
	else
		{
		$avg_ring_time=round($data["ring_time"]/$data["calls"]);
		$avg_ring_time=($avg_ring_time>=3600 ? intval(floor($avg_ring_time/3600)).date(":i:s", $avg_ring_time) : intval(date("i", $avg_ring_time)).":".date("s", $avg_ring_time));
		}

	$terminal=($terminal_names["$queue"]!="" ? $terminal_names["$queue"] : "($queue)");
	$lost_time=($data["lost_ring_time"]>=3600 ? intval(floor($data["lost_ring_time"]/3600)).date(":i:s", $data["lost_ring_time"]) : intval(date("i", $data["lost_ring_time"])).":".date("s", $data["lost_ring_time"]));
	$ring_time=($data["ring_time"]>=3600 ? intval(floor($data["ring_time"]/3600)).date(":i:s", $data["ring_time"]) : intval(date("i", $data["ring_time"])).":".date("s", $data["ring_time"]));

	$CSV_output["acd_attempts_by_terminal"].="\"".$terminal."\",";
	$CSV_output["acd_attempts_by_terminal"].="\"".(!$data["lost"] ? "0" : $data["lost"])."\",";
	$CSV_output["acd_attempts_by_terminal"].="\"".$avg_lost_time."\",";
	$CSV_output["acd_attempts_by_terminal"].="\"".$lost_time."\",";
	$CSV_output["acd_attempts_by_terminal"].="\"".(!$data["calls"] ? "0" : $data["calls"])."\",";
	$CSV_output["acd_attempts_by_terminal"].="\"".$avg_ring_time."\",";
	$CSV_output["acd_attempts_by_terminal"].="\"".$ring_time."\",";
	$CSV_output["acd_attempts_by_terminal"].="\n";

	$HTML_output.="<tr>";
	$HTML_output.="<td>".$terminal."</td>";
	$HTML_output.="<td>".(!$data["lost"] ? "0": $data["lost"])."</td>";
	$HTML_output.="<td>".$avg_lost_time."</td>";
	$HTML_output.="<td>".$lost_time."</td>";
	$HTML_output.="<td>".(!$data["calls"] ? "0" : $data["calls"])."</td>";
	$HTML_output.="<td>".$avg_ring_time."</td>";
	$HTML_output.="<td>".$ring_time."</td>";
	$HTML_output.="</tr>";	
	}
$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='7'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=acd_attempts_by_terminal\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td class='export_row_cell' colspan='7'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('STATS', 'acd_attempts_by_terminal')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table><BR>";
#############################

if ($download_rpt)
	{
	$data_to_download=$CSV_output["$download_rpt"];

	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "STATS_RPT_".$download_rpt."_".$FILE_TIME.".csv";
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

/*
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
*/

</script>
