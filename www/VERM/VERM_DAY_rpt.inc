<?php
# VERM_DAY_rpt.inc - Vicidial Enhanced Reporting per-day report
#
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGELOG:
# 220825-1624 - First build
# 230106-1311 - Added page flushing to show page load progress
# 240801-1130 - Code updates for PHP8 compatibility
#

$vicidial_user_log_table="vicidial_user_log";

# if (isset($_GET["SUBMIT"]))			{$SUBMIT=$_GET["SUBMIT"];}
# 	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}

#### CUSTOM REPORT VARIABLES ####
#if (isset($_GET["agents"]))			{$agents=$_GET["agents"];}
#	elseif (isset($_POST["agents"]))	{$agents=$_POST["agents"];}
#if (isset($_GET["locations"]))			{$locations=$_GET["locations"];}
#	elseif (isset($_POST["locations"]))	{$locations=$_POST["locations"];}
#if (isset($_GET["user_groups"]))			{$user_groups=$_GET["user_groups"];}
#	elseif (isset($_POST["user_groups"]))	{$user_groups=$_POST["user_groups"];}
#if (isset($_GET["statuses"]))			{$statuses=$_GET["statuses"];}
#	elseif (isset($_POST["statuses"]))	{$statuses=$_POST["statuses"];}
#if (isset($_GET["disconnection_causes"]))			{$disconnection_causes=$_GET["disconnection_causes"];}
#	elseif (isset($_POST["disconnection_causes"]))	{$disconnection_causes=$_POST["disconnection_causes"];}
#if (isset($_GET["call_counts"]))			{$call_counts=$_GET["call_counts"];}
#	elseif (isset($_POST["call_counts"]))	{$call_counts=$_POST["call_counts"];}
#if (isset($_GET["DIDs"]))			{$DIDs=$_GET["DIDs"];}
#	elseif (isset($_POST["DIDs"]))	{$DIDs=$_POST["DIDs"];}
#if (isset($_GET["IVR_choice"]))			{$IVR_choice=$_GET["IVR_choice"];}
#	elseif (isset($_POST["IVR_choice"]))	{$IVR_choice=$_POST["IVR_choice"];}
#if (isset($_GET["server"]))			{$server=$_GET["server"];}
#	elseif (isset($_POST["server"]))	{$server=$_POST["server"];}
#if (isset($_GET["days_of_week"]))			{$days_of_week=$_GET["days_of_week"];}
#	elseif (isset($_POST["days_of_week"]))	{$days_of_week=$_POST["days_of_week"];}
#if (isset($_GET["hour_of_day"]))			{$hour_of_day=$_GET["hour_of_day"];}
#	elseif (isset($_POST["hour_of_day"]))	{$hour_of_day=$_POST["hour_of_day"];}

### VARIABLES ###
$total_calls=0;
$total_answered_calls=0;
$unanswered_calls=0;
$max_call_length=0; 
$min_call_length=1000000;
$total_call_length=0;
$max_wait_time=0;
$min_wait_time=1000000;
$total_wait_time=0;
$total_position=0;
$max_position=0;
$min_position=1000000;
$total_distinct_users_per_date=0;
$total_agent_login_time=0; 
$all_calls_array=array(); # Regardless of outcome
$calls_array=array(); # Dependent on outcome
$answered_calls_array=array(); # Dependent on outcome
$unanswered_calls_array=array(); # Dependent on outcome
$user_array=array();
$agent_login_time_array=array();
$sales_array=array();
$queue_array=array();

$agent_call_counts=array(); 
$transferred_counts=array(); 
$agent_call_lengths=array();
$queue_stats=array(); # campaign id breakdown BY ANSWERED CALLS
$term_reasons=array(); # ANSWERED CALLS ONLY
$call_directions=array(); # ANSWERED CALLS 
$call_stints=array(); # ANSWERED CALLS
$agent_events=array(); # ANSWERED CALLS
$coverage="100%";
$coverage_wi="100%";
#################


$stmt="select substr(call_date, 1, 10) as cdate, campaign_id, status, user, length_in_sec as call_length, uniqueid, '0' as wait_sec, 'O' as direction, '1' as queue_position, lead_id from vicidial_log $vicidial_log_SQL $and_NANQUE_clause $exc_addtl_statuses UNION ALL select substr(call_date, 1, 10) as cdate, campaign_id, status, user, if(comments='EMAIL', length_in_sec, length_in_sec-queue_seconds) as call_length, uniqueid, queue_seconds as wait_sec, 'I' as direction, queue_position, lead_id from vicidial_closer_log $vicidial_closer_log_SQL $and_NANQUE_clause $exc_addtl_statuses";
if ($DB) {$HTML_output.="<B>$stmt</B>";}
# print $stmt."\n";
$rslt=mysql_to_mysqli($stmt, $link);
while ($row=mysqli_fetch_array($rslt))
	{
	$call_date=$row["cdate"];
	$status=$row["status"];
	$user=$row["user"];
	$lead_id=$row["lead_id"];
	$length_in_sec=$row["call_length"];
	$wait_sec=$row["wait_sec"];
	$uniqueid=$row["uniqueid"];
	$direction=$row["direction"];
	$campaign_id=$row["campaign_id"];
	$queue_position=$row["queue_position"];

	# $total_calls++; # Doesn't factor out NANQUE

	if ($direction=="I")
		{
		$calls_array["$call_date"]["inbound"]++;
		}
	else
		{
		$calls_array["$call_date"]["outbound"]++;
		}

	if (preg_match('/VDAD|VDCL/', $user)) # && preg_match('/^DROP$|TIMEOT|WAITTO/', $status) 
		{
		$total_unanswered_calls++;

		$unanswered_calls_array["$call_date"]["total_length"]+=$length_in_sec;
		$unanswered_calls_array["$call_date"]["total_wait"]+=$wait_sec;

		$calls_array["$call_date"]["unanswered"]++;
		if ($direction=="I")
			{
			$unanswered_calls_array["$call_date"]["inbound"]++;
			}
		else
			{
			$unanswered_calls_array["$call_date"]["outbound"]++;
			}

		# Min/max call length
		if (!$unanswered_calls_array["$call_date"]["min_call_length"] || $length_in_sec<$unanswered_calls_array["$call_date"]["min_call_length"])
			{
			$unanswered_calls_array["$call_date"]["min_call_length"]=$length_in_sec;
			}
		if (!$unanswered_calls_array["$call_date"]["max_call_length"] || $length_in_sec>$unanswered_calls_array["$call_date"]["max_call_length"])
			{
			$unanswered_calls_array["$call_date"]["max_call_length"]=$length_in_sec;
			}

		# Min/max wait length
		if (!$unanswered_calls_array["$call_date"]["min_wait_time"] || $wait_sec<$unanswered_calls_array["$call_date"]["min_wait_time"])
			{
			$unanswered_calls_array["$call_date"]["min_wait_time"]=$wait_sec;
			}
		if (!$unanswered_calls_array["$call_date"]["max_wait_time"] || $wait_sec>$unanswered_calls_array["$call_date"]["max_wait_time"])
			{
			$unanswered_calls_array["$call_date"]["max_wait_time"]=$wait_sec;
			}
		
		} 
	else 
		{
		$total_answered_calls++;


		# Total counts/lengths
		$calls_array["$call_date"]["answered"]++;
		$calls_array["$call_date"]["total_length"]+=$length_in_sec;
		$calls_array["$call_date"]["total_wait"]+=$wait_sec;

		$answered_calls_array["$call_date"]["total_length"]+=$length_in_sec;
		$answered_calls_array["$call_date"]["total_wait"]+=$wait_sec;

		# Min/max call length
		if (!$answered_calls_array["$call_date"]["min_call_length"] || $length_in_sec<$answered_calls_array["$call_date"]["min_call_length"])
			{
			$answered_calls_array["$call_date"]["min_call_length"]=$length_in_sec;
			}
		if (!$answered_calls_array["$call_date"]["max_call_length"] || $length_in_sec>$answered_calls_array["$call_date"]["max_call_length"])
			{
			$answered_calls_array["$call_date"]["max_call_length"]=$length_in_sec;
			}

		# Min/max wait length
		if (!$answered_calls_array["$call_date"]["min_wait_time"] || $wait_sec<$answered_calls_array["$call_date"]["min_wait_time"])
			{
			$answered_calls_array["$call_date"]["min_wait_time"]=$wait_sec;
			}
		if (!$answered_calls_array["$call_date"]["max_wait_time"] || $wait_sec>$answered_calls_array["$call_date"]["max_wait_time"])
			{
			$answered_calls_array["$call_date"]["max_wait_time"]=$wait_sec;
			}
		
		if ($direction=="I")
			{
			$inbound_calls["$call_date"]++;
			$answered_calls_array["$call_date"]["inbound"]++;
			$campaign_id_stmt="select campaign_id from vicidial_agent_log where lead_id='$lead_id' and user='$user' and uniqueid='$uniqueid'";
			$campaign_id_rslt=mysql_to_mysqli($campaign_id_stmt, $link);
			if(mysqli_num_rows($campaign_id_rslt)>0)
				{
				$campaign_id_row=mysqli_fetch_array($campaign_id_rslt);
				$campaign_id=$campaign_id_row["campaign_id"];
				}
			}
		else
			{
			$answered_calls_array["$call_date"]["outbound"]++;
			$outbound_calls["$call_date"]++;
			}

		### These counts are in here because by definition these SHOULD be answers, but you never know with some morons.
		### "Real" campaign_id should have been gotten under the direction clause above

		if ((array_key_exists("$campaign_id", $sale_array) && in_array($status, $sale_array["$campaign_id"])) || in_array($status, $sale_array["SYSTEM"]))
			{
			$calls_array["$call_date"]["sales"]++;
			$total_sales++;
			}
		if ((array_key_exists("$campaign_id", $human_ans_array) && in_array($status, $human_ans_array["$campaign_id"])) || in_array($status, $human_ans_array["SYSTEM"]))
			{
			$calls_array["$call_date"]["human_answered"]++;
			$total_human_answered++;
			}
		if ((array_key_exists("$campaign_id", $contact_array) && in_array($status, $contact_array["$campaign_id"])) || in_array($status, $contact_array["SYSTEM"]))
			{
			$calls_array["$call_date"]["customer_contacts"]++;
			$total_customer_contacts++;
			}


		# User counts
		if (!array_key_exists("$call_date", $user_array)) {$user_array["$call_date"]=array();}
		if (!in_array($user, $user_array["$call_date"]) && !preg_match('/VDAD|VDCL/', $user)) 
			{
			$total_distinct_users_per_date++;  # Need for adherence report
			array_push($user_array["$call_date"], $user);
			}

		# Sale check
		if (in_array($status, $sale_array["SYSTEM"]) || (array_key_exists("$campaign_id", $sale_array) && in_array($status, $sale_array["$campaign_id"])))
			{
			$sales_array["$call_date"]["sales"]++;
			}

		# Contact check
		if (in_array($status, $contact_array["SYSTEM"]) || (array_key_exists("$campaign_id", $contact_array) && in_array($status, $contact_array["$campaign_id"])))
			{
			$sales_array["$call_date"]["contacts"]++;
			}
		}

	# Queue array
	$queue_array["$call_date"]["total_calls"]++;
	$queue_array["$call_date"]["total_queue"]+=$queue_position;
	$queue_array["$call_date"]["avg_position"]=MathZDC($queue_array["$call_date"]["total_queue"], $queue_array["$call_date"]["total_calls"]);
	# Min/max queue
	if (!$queue_array["$call_date"]["min_queue_position"] || $queue_position<$queue_array["$call_date"]["min_queue_position"])
		{
		$queue_array["$call_date"]["min_queue_position"]=$queue_position;
		}
	if (!$queue_array["$call_date"]["max_queue_position"] || $queue_position>$queue_array["$call_date"]["max_queue_position"])
		{
		$queue_array["$call_date"]["max_queue_position"]=$queue_position;
		}

	}
$total_calls=$total_answered_calls+$total_unanswered_calls;

# print_r($calls_array); die;
#### ANSWERED CALL DISTRIBUTION PER DAY ###
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Answered call distribution per day").": $NWB#VERM_DAY-answered_call_distribution$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table border='0' cellpadding='0' cellspacing='0'>";
$HTML_output.="<tr><td width='60%' valign='top'>";
$HTML_output.="<table width='100%' id='details_table'>";
$HTML_output.="<tr>";
$HTML_output.="<th>"._QXZ("Day")."</th>";
$HTML_output.="<th>"._QXZ("Num")."</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="<th>"._QXZ("Answered calls")."</th>";
$HTML_output.="<th>"._QXZ("Avg")."</th>";
$HTML_output.="<th>"._QXZ("Min")."</th>";
$HTML_output.="<th>"._QXZ("Max")."</th>";
$HTML_output.="<th>"._QXZ("Avg Duration")."</th>";
$HTML_output.="</tr>\n";

$CSV_output["answered_call_distribution"]="\""._QXZ("Day")."\",\""._QXZ("Num")."\",\"\",\""._QXZ("Answered calls")."\",\""._QXZ("Avg")."\",\""._QXZ("Min")."\",\""._QXZ("Max")."\",\""._QXZ("Avg Duration")."\"\n";

$max_avg_call_length=0;
foreach ($calls_array as $call_date_key => $call_date_data) # Use calls array here to iterate through all dates that have calls
	{
	$avg_call_length=round(MathZDC($answered_calls_array["$call_date_key"]["total_length"], ($answered_calls_array["$call_date_key"]["inbound"]+$answered_calls_array["$call_date_key"]["outbound"])));
	$answered_calls_array["$call_date_key"]["avg_call_length"]=$avg_call_length+0;
	if ($max_avg_call_length<$avg_call_length) {$max_avg_call_length=$avg_call_length;}
	}
foreach ($answered_calls_array as $call_date_key => $call_date_data)
	{
	$total_date_calls=($call_date_data["inbound"]+$call_date_data["outbound"]);
	$total_date_calls_answered=($answered_calls_array["$call_date_key"]["inbound"]+$answered_calls_array["$call_date_key"]["outbound"]);
	$total_date_calls_unanswered=($unanswered_calls_array["$call_date_key"]["inbound"]+$unanswered_calls_array["$call_date_key"]["outbound"]);

	$avg_call_length=round(MathZDC($answered_calls_array["$call_date_key"]["total_length"], $total_date_calls_answered));
	$avg_call_length_fmt=($avg_call_length>=3600 ? floor($avg_call_length/3600).":" : "").gmdate("i:s", $avg_call_length);

	$min_call_length_fmt=($answered_calls_array["$call_date_key"]["min_call_length"]>=3600 ? floor($answered_calls_array["$call_date_key"]["min_call_length"]/3600).":" : "").gmdate("i:s", $answered_calls_array["$call_date_key"]["min_call_length"]);
	$max_call_length_fmt=($answered_calls_array["$call_date_key"]["max_call_length"]>=3600 ? floor($answered_calls_array["$call_date_key"]["max_call_length"]/3600).":" : "").gmdate("i:s", $answered_calls_array["$call_date_key"]["max_call_length"]);

	$HTML_output.="<tr>";
	$HTML_output.="<td>".$call_date_key."</td>";
	$HTML_output.="<td>".$total_date_calls_answered."</td>";
	$HTML_output.="<td>".sprintf("%.1f", (MathZDC((100*$total_date_calls_answered), $total_answered_calls)))." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(MathZDC((100*$total_date_calls_answered), $total_answered_calls))."%'></td>";
	$HTML_output.="<td>".$avg_call_length_fmt."</td>";
	$HTML_output.="<td>".$min_call_length_fmt."</td>";
	$HTML_output.="<td>".$max_call_length_fmt."</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(MathZDC((100*$avg_call_length), $max_avg_call_length))."%'></td>";
	$HTML_output.="</tr>";

	$CSV_output["answered_call_distribution"].="\"".$call_date_key."\",\"".$total_date_calls_answered."\",\"".sprintf("%.1f", (MathZDC((100*$total_date_calls_answered), $total_answered_calls)))." %\",\"\",\"".$avg_call_length_fmt."\",\"".$min_call_length_fmt."\",\"".$max_call_length_fmt."\",\"\"\n";
	}
$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td colspan='8' class='export_row_cell'>"._QXZ("Export as")."...<a href=\"".$PHP_SELF."?download_rpt=answered_call_distribution\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td class='export_row_cell' colspan='8'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('DAY', 'answered_call_distribution')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
# $HTML_output.="<div class=\"chart-container\" style=\"height:80vh; width:80vh\"><canvas id='agents_on_queue_display' role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
###########################################

#### ANSWERED CALL WAIT TIME PER DAY ###
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Answered call wait time per day").": $NWB#VERM_DAY-answered_call_wait_time$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table border='0' cellpadding='0' cellspacing='0'>";
$HTML_output.="<tr><td width='60%' valign='top'>";
$HTML_output.="<table width='100%' id='details_table'>";
$HTML_output.="<tr>";
$HTML_output.="<th>"._QXZ("Day")."</th>";
$HTML_output.="<th>"._QXZ("Num")."</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="<th>"._QXZ("Answered calls")."</th>";
$HTML_output.="<th>"._QXZ("Avg")."</th>";
$HTML_output.="<th>"._QXZ("Min")."</th>";
$HTML_output.="<th>"._QXZ("Max")."</th>";
$HTML_output.="<th>"._QXZ("Avg Duration")."</th>";
$HTML_output.="</tr>\n";

$CSV_output["answered_call_wait_time"]="\""._QXZ("Day")."\",\""._QXZ("Num")."\",\"\",\""._QXZ("Answered calls")."\",\""._QXZ("Avg")."\",\""._QXZ("Min")."\",\""._QXZ("Max")."\",\""._QXZ("Avg Duration")."\"\n";

$max_avg_wait_time=0;
foreach ($calls_array as $call_date_key => $call_date_data)
	{
	$avg_wait_time=round(MathZDC($answered_calls_array["$call_date_key"]["total_wait"], ($answered_calls_array["$call_date_key"]["inbound"]+$answered_calls_array["$call_date_key"]["outbound"])));
	$answered_calls_array["$call_date_key"]["avg_wait_time"]=$avg_wait_time+0;
	if ($max_avg_wait_time<$avg_wait_time) {$max_avg_wait_time=$avg_wait_time;}
	}
foreach ($answered_calls_array as $call_date_key => $call_date_data)
	{
	$total_date_calls=($call_date_data["inbound"]+$call_date_data["outbound"]);
	$total_date_calls_answered=($answered_calls_array["$call_date_key"]["inbound"]+$answered_calls_array["$call_date_key"]["outbound"]);
	$total_date_calls_unanswered=($unanswered_calls_array["$call_date_key"]["inbound"]+$unanswered_calls_array["$call_date_key"]["outbound"]);

	$avg_wait_time=round(MathZDC($answered_calls_array["$call_date_key"]["total_wait"], $total_date_calls_answered));
	$avg_wait_time_fmt=($avg_wait_time>=3600 ? floor($avg_wait_time/3600).":" : "").gmdate("i:s", $avg_wait_time);

	$min_wait_time_fmt=($answered_calls_array["$call_date_key"]["min_wait_time"]>=3600 ? floor($answered_calls_array["$call_date_key"]["min_wait_time"]/3600).":" : "").gmdate("i:s", $answered_calls_array["$call_date_key"]["min_wait_time"]);
	$max_wait_time_fmt=($answered_calls_array["$call_date_key"]["max_wait_time"]>=3600 ? floor($answered_calls_array["$call_date_key"]["max_wait_time"]/3600).":" : "").gmdate("i:s", $answered_calls_array["$call_date_key"]["max_wait_time"]);

	$HTML_output.="<tr>";
	$HTML_output.="<td>".$call_date_key."</td>";
	$HTML_output.="<td>".$total_date_calls_answered."</td>";
	$HTML_output.="<td>".sprintf("%.1f", (MathZDC((100*$total_date_calls_answered), $total_answered_calls)))." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(MathZDC((100*$total_date_calls_answered), $total_answered_calls))."%'></td>";
	$HTML_output.="<td>".$avg_wait_time_fmt."</td>";
	$HTML_output.="<td>".$min_wait_time_fmt."</td>";
	$HTML_output.="<td>".$max_wait_time_fmt."</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(MathZDC((100*$avg_wait_time), $max_avg_wait_time))."%'></td>";
	$HTML_output.="</tr>";

	$CSV_output["answered_call_wait_time"].="\"".$call_date_key."\",\"".$total_date_calls_answered."\",\"".sprintf("%.1f", (MathZDC((100*$total_date_calls_answered), $total_answered_calls)))." %\",\"\",\"".$avg_wait_time_fmt."\",\"".$min_wait_time_fmt."\",\"".$max_wait_time_fmt."\",\"\"\n";
	}
$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td colspan='8' class='export_row_cell'>"._QXZ("Export as")."...<a href=\"".$PHP_SELF."?download_rpt=answered_call_wait_time\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td class='export_row_cell' colspan='8'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('DAY', 'answered_call_wait_time')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
# $HTML_output.="<div class=\"chart-container\" style=\"height:80vh; width:80vh\"><canvas id='agents_on_queue_display' role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
###########################################

#### UNANSWERED CALL WAIT TIME PER DAY ###
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Unanswered call wait time per day").": $NWB#VERM_DAY-unanswered_call_wait_time$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table border='0' cellpadding='0' cellspacing='0'>";
$HTML_output.="<tr><td width='60%' valign='top'>";
$HTML_output.="<table width='100%' id='details_table'>";
$HTML_output.="<tr>";
$HTML_output.="<th>"._QXZ("Day")."</th>";
$HTML_output.="<th>"._QXZ("Num")."</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="<th>"._QXZ("Unanswered calls")."</th>";
$HTML_output.="<th>"._QXZ("Avg")."</th>";
$HTML_output.="<th>"._QXZ("Min")."</th>";
$HTML_output.="<th>"._QXZ("Max")."</th>";
$HTML_output.="<th>"._QXZ("Avg Duration")."</th>";
$HTML_output.="</tr>\n";

$CSV_output["unanswered_call_wait_time"]="\""._QXZ("Day")."\",\""._QXZ("Num")."\",\"\",\""._QXZ("Unanswered calls")."\",\""._QXZ("Avg")."\",\""._QXZ("Min")."\",\""._QXZ("Max")."\",\""._QXZ("Avg Duration")."\"\n";

$max_avg_wait_time=0;
foreach ($calls_array as $call_date_key => $call_date_data)
	{
	$avg_wait_time=round(MathZDC($unanswered_calls_array["$call_date_key"]["total_wait"], ($unanswered_calls_array["$call_date_key"]["inbound"]+$unanswered_calls_array["$call_date_key"]["outbound"])));
	# print $unanswered_calls_array["$call_date_key"]["total_wait"]."/".($unanswered_calls_array["$call_date_key"]["inbound"]+$unanswered_calls_array["$call_date_key"]["outbound"])."\n";
	$unanswered_calls_array["$call_date_key"]["avg_wait_time"]=$avg_wait_time+0;
	if ($max_avg_wait_time<$avg_wait_time) {$max_avg_wait_time=$avg_wait_time;}
	}
# print_r($unanswered_calls_array); die;
foreach ($unanswered_calls_array as $call_date_key => $call_date_data)
	{
	$total_date_calls=($call_date_data["inbound"]+$call_date_data["outbound"]);
	$total_date_calls_answered=($unanswered_calls_array["$call_date_key"]["inbound"]+$unanswered_calls_array["$call_date_key"]["outbound"]);
	$total_date_calls_unanswered=($unanswered_calls_array["$call_date_key"]["inbound"]+$unanswered_calls_array["$call_date_key"]["outbound"]);

	$avg_wait_time=round(MathZDC($unanswered_calls_array["$call_date_key"]["total_wait"], $total_date_calls_unanswered));
	$avg_wait_time_fmt=($avg_wait_time>=3600 ? floor($avg_wait_time/3600).":" : "").gmdate("i:s", $avg_wait_time);

	$min_wait_time_fmt=($unanswered_calls_array["$call_date_key"]["min_wait_time"]>=3600 ? floor($unanswered_calls_array["$call_date_key"]["min_wait_time"]/3600).":" : "").gmdate("i:s", $unanswered_calls_array["$call_date_key"]["min_wait_time"]);
	$max_wait_time_fmt=($unanswered_calls_array["$call_date_key"]["max_wait_time"]>=3600 ? floor($unanswered_calls_array["$call_date_key"]["max_wait_time"]/3600).":" : "").gmdate("i:s", $unanswered_calls_array["$call_date_key"]["max_wait_time"]);

	$HTML_output.="<tr>";
	$HTML_output.="<td>".$call_date_key."</td>";
	$HTML_output.="<td>".$total_date_calls_unanswered."</td>";
	$HTML_output.="<td>".sprintf("%.1f", (MathZDC((100*$total_date_calls_unanswered), $total_unanswered_calls)))." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(MathZDC((100*$total_date_calls_unanswered), $total_unanswered_calls))."%'></td>";
	$HTML_output.="<td>".$avg_wait_time_fmt."</td>";
	$HTML_output.="<td>".$min_wait_time_fmt."</td>";
	$HTML_output.="<td>".$max_wait_time_fmt."</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(MathZDC((100*$avg_wait_time), $max_avg_wait_time))."%'></td>";
	$HTML_output.="</tr>";

	$CSV_output["unanswered_call_wait_time"].="\"".$call_date_key."\",\"".$total_date_calls_unanswered."\",\"".sprintf("%.1f", (MathZDC((100*$total_date_calls_unanswered), $total_unanswered_calls)))." %\",\"\",\"".$avg_wait_time_fmt."\",\"".$min_wait_time_fmt."\",\"".$max_wait_time_fmt."\",\"\"\n";

	}
$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td colspan='8' class='export_row_cell'>"._QXZ("Export as")."...<a href=\"".$PHP_SELF."?download_rpt=unanswered_call_wait_time\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td class='export_row_cell' colspan='8'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('DAY', 'unanswered_call_wait_time')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="</td><td width='*' align='center' valign='top'>";
# $HTML_output.="<div class=\"chart-container\" style=\"height:80vh; width:80vh\"><canvas id='agents_on_queue_display' role=\"img\"> </canvas></div>";
$HTML_output.="</td></tr></table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
###########################################

#### SALES PER DAY ###
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Sales per Day").": $NWB#VERM_DAY-sales_per_day$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='details_table'>";
$HTML_output.="<tr>";
$HTML_output.="<th>"._QXZ("Day")."</th>";
$HTML_output.="<th>"._QXZ("Human ans. conv")."</th>";
$HTML_output.="<th>"._QXZ("Customer cont. conv")."</th>";
$HTML_output.="<th>"._QXZ("Sales")."</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>"._QXZ("Human answers")."</th>";
$HTML_output.="<th>"._QXZ("Customer contacts")."</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="</tr>\n";

$CSV_output["sales_per_day"]="\""._QXZ("Day")."\",\""._QXZ("Human ans. conv")."\",\""._QXZ("Customer cont. conv")."\",\""._QXZ("Sales")."\",\"\",\"\",\""._QXZ("Human answers")."\",\""._QXZ("Customer contacts")."\",\"\",\"\"\n";

#print_r($calls_array);
$max_day_sales=(count(array_column($calls_array, 'sales'))>0 ? max(array_column($calls_array, 'sales')) : 0);
$max_day_customer_contacts=(count(array_column($calls_array, 'customer_contacts'))>0 ? max(array_column($calls_array, 'customer_contacts')) : 0);
#print "<BR>$max_day_sales<BR>\n";
foreach ($calls_array as $call_date_key => $data)
	{
	$HTML_output.="<tr>";
	$HTML_output.="<td>$call_date_key</td>";
	$HTML_output.="<td>".sprintf("%.1f", (MathZDC((100*$data["sales"]), $data["human_answered"])))." %</td>";
	$HTML_output.="<td>".sprintf("%.1f", (MathZDC((100*$data["sales"]), $data["customer_contacts"])))." %</td>";
	$HTML_output.="<td>".($data["sales"]+0)."</td>";
	$HTML_output.="<td>".sprintf("%.1f", (MathZDC((100*$data["sales"]), $total_sales)))." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(MathZDC((100*$data["sales"]), $max_day_sales))."%'></td>";
	$HTML_output.="<td>".$data["human_answered"]."</td>";
	$HTML_output.="<td>".$data["customer_contacts"]."</td>";
	$HTML_output.="<td>".sprintf("%.1f", (MathZDC((100*$data["customer_contacts"]), $total_customer_contacts)))." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(MathZDC((100*$data["customer_contacts"]), $max_day_customer_contacts))."%'></td>";
	$HTML_output.="</tr>\n";

	$CSV_output["sales_per_day"].="\"$call_date_key\",\"".sprintf("%.1f", (MathZDC((100*$data["sales"]), $data["human_answered"])))." %\",\"".sprintf("%.1f", (MathZDC((100*$data["sales"]), $data["customer_contacts"])))." %\",\"".($data["sales"]+0)."\",\"".sprintf("%.1f", (MathZDC((100*$data["sales"]), $total_sales)))." %\",\"\",\"".$data["human_answered"]."\",\"".$data["customer_contacts"]."\",\"".sprintf("%.1f", (MathZDC((100*$data["customer_contacts"]), $total_customer_contacts)))." %\",\"\"\n";
	}
$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td colspan='10' class='export_row_cell'>"._QXZ("Export as")."...<a href=\"".$PHP_SELF."?download_rpt=sales_per_day\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td class='export_row_cell' colspan='10'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('DAY', 'sales_per_day')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
###########################################

#### QUEUE LENGTH PER DAY ###
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Queue length per day").": $NWB#VERM_DAY-queue_length_per_day$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='details_table'>";
$HTML_output.="<tr>";
$HTML_output.="<th>"._QXZ("Day")."</th>";
$HTML_output.="<th>"._QXZ("Avg")."</th>";
$HTML_output.="<th>...</th>";
$HTML_output.="<th>"._QXZ("Min")."</th>";
$HTML_output.="<th>"._QXZ("Max")."</th>";
$HTML_output.="<th>...</th>";
### Removed 5/19/22
### $HTML_output.="<th>Cover.</th>";
$HTML_output.="<th>"._QXZ("ASQPPH")." $NWB#VERM_DAY-queue_length_per_day$NWE</th>";
$HTML_output.="</tr>\n";

### Removed Coverage column before Steps 5/19/22 - not needed
$CSV_output["queue_length_per_day"]="\""._QXZ("Day")."\",\""._QXZ("Avg")."\",\"...\",\""._QXZ("Min")."\",\""._QXZ("Max")."\",\"...\",\""._QXZ("Average Sum of Queue Positions Per Hour")."\"\n";

$max_call_position=(count(array_column($queue_array, 'max_queue_position'))>0 ? max(array_column($queue_array, 'max_queue_position')) : 0);
$max_avg_position=(count(array_column($queue_array, 'avg_position'))>0 ? max(array_column($queue_array, 'avg_position')) : 0);
foreach ($queue_array as $call_date_key => $data)
	{
	$HTML_output.="<tr>";
	$HTML_output.="<td>$call_date_key</td>";
	$HTML_output.="<td>".sprintf("%.1f", $queue_array["$call_date"]["avg_position"])."</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(MathZDC((100*$queue_array["$call_date_key"]["avg_position"]), $max_avg_position))."%'></td>";
	$HTML_output.="<td>".$queue_array["$call_date_key"]["min_queue_position"]."</td>";
	$HTML_output.="<td>".$queue_array["$call_date_key"]["max_queue_position"]."</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(MathZDC((100*$queue_array["$call_date_key"]["max_queue_position"]), $max_call_position))."%'></td>";
	### $HTML_output.="<td>100% **</td>";
	$HTML_output.="<td>".sprintf("%.1f", $queue_array["$call_date_key"]["total_queue"]/24)."</td>"; # Always seems to be 24 hours for the date - even when I ran starting at 8am.
	$HTML_output.="</tr>\n";

	$CSV_output["queue_length_per_day"].="\"$call_date_key\",\"".sprintf("%.1f", $queue_array["$call_date"]["avg_position"])."\",\"...\",\"".$queue_array["$call_date_key"]["min_queue_position"]."\",\"".$queue_array["$call_date_key"]["max_queue_position"]."\",\"...\",\"".sprintf("%.1f", $queue_array["$call_date_key"]["total_queue"]/24)."\"\n";
	}
$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td colspan='8' class='export_row_cell'>"._QXZ("Export as")."...<a href=\"".$PHP_SELF."?download_rpt=queue_length_per_day\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td class='export_row_cell' colspan='7'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('DAY', 'queue_length_per_day')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
### $HTML_output.="** - coverage = percent of calls with queue position.  Assumed 100% if including outbound with a default position of '1' - also true for avg position<BR><BR>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
###########################################

#### SCHEDULE ADHERENCE PER DAY ###
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Schedule Adherence per Day").": $NWB#VERM_DAY-schedule_adherence$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='details_table'>";
$HTML_output.="<tr>";
$HTML_output.="<th>"._QXZ("Day")."</th>";
$HTML_output.="<th>"._QXZ("Agents")."</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="</tr>\n";

$CSV_output["schedule_adherence_per_day"]="\""._QXZ("Day")."\",\""._QXZ("Agents")."\",\"\",\"\"\n";

foreach ($user_array as $call_date_key => $users_that_day)
	{
	$HTML_output.="<tr>";
	$HTML_output.="<td>$call_date_key</td>";
	$HTML_output.="<td>".count($users_that_day)."</td>";
	$HTML_output.="<td>".sprintf("%.1f", (MathZDC(100*count($users_that_day), $total_distinct_users_per_date)))." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".sprintf("%.1f", (MathZDC(100*count($users_that_day), $total_distinct_users_per_date)))."%'></td>";
	$HTML_output.="</tr>\n";

	if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

	$CSV_output["schedule_adherence_per_day"].="\"$call_date_key\",\"".count($users_that_day)."\",\"".sprintf("%.1f", (MathZDC(100*count($users_that_day), $total_distinct_users_per_date)))." %\",\"\"\n";

	# print "agents for $call_date_key: ".count($users_that_day)."\n";

	for ($i=0; $i<count($users_that_day); $i++)
		{
		# Get total agent time 
		$stmt="SELECT event,event_epoch,event_date,campaign_id,user_group,session_id,server_ip,extension,computer_ip,phone_login,phone_ip from ".$vicidial_user_log_table." where user='" . mysqli_real_escape_string($link, $users_that_day[$i]) . "' and event_date >= '" . mysqli_real_escape_string($link, $call_date_key) . " 0:00:01'  and event_date <= '" . mysqli_real_escape_string($link, $call_date_key) . " 23:59:59' order by event_date;";
		$rslt=mysql_to_mysqli($stmt, $link);
		while ($row=mysqli_fetch_row($rslt))
			{
			if (preg_match('/LOGIN/', $row[0])) 
				{
				$event_start_seconds = $row[1];
				}
			if (preg_match('/LOGOUT/', $row[0]))
				{
				if ($event_start_seconds)
					{
					$event_stop_seconds = $row[1];
					$event_seconds = ($event_stop_seconds - $event_start_seconds);
					$agent_login_time_array["$call_date_key"]+=$event_seconds;
					}
				}
			}
		}
	}
#print_r($agent_login_time_array);
$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td colspan='4' class='export_row_cell'>"._QXZ("Export as")."...<a href=\"".$PHP_SELF."?download_rpt=schedule_adherence_per_day\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td class='export_row_cell' colspan='4'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('DAY', 'schedule_adherence_per_day')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
###########################################

#### SERVICE LEVEL PER DAY ####
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Inclusive SLA per day (inbound and outbound)").": $NWB#VERM_DAY-inclusive_SLA$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='details_table'>";
$HTML_output.="<tr>";
$HTML_output.="<th>"._QXZ("Day")."</th>";
$HTML_output.="<th>"._QXZ("Answered")."</th>";
$HTML_output.="<th>"._QXZ("Unanswered")."</th>";
$HTML_output.="<th>"._QXZ("Num")."</th>";

$CSV_output["inclusive_sla_per_day"]="\""._QXZ("Day")."\",\""._QXZ("Answered")."\",\""._QXZ("Unanswered")."\",\""._QXZ("Num")."\"";

for ($i=0; $i<count($SLA_column_names); $i++)
	{
	$HTML_output.="<th>".$SLA_column_names[$i]."</th>";
	$CSV_output["inclusive_sla_per_day"].=",\"".$SLA_column_names[$i]."\"";
	}
$HTML_output.="</tr>\n";

$CSV_output["inclusive_sla_per_day"].="\n";

$svc_lvl_row=array();
foreach ($calls_array as $call_date_key => $data)
	{	
	$svc_lvl_stmt="select $SLA_query from vicidial_closer_log where call_date>='".$call_date_key." $start_time' and call_date<='".$call_date_key." $end_time' $and_vicidial_closer_log_SQL and (user!='VDCL')"; #  and campaign_id in ($ingroup_str) and user in ('VDCL', $user_str)
	if ($DB) {$HTML_output.="<B>$svc_lvl_stmt</B><BR>";}
	$svc_lvl_rslt=mysql_to_mysqli($svc_lvl_stmt, $link); 
# 	print $svc_lvl_stmt."\n";
	$svc_lvl_row=mysqli_fetch_row($svc_lvl_rslt);

	$answered_calls=$answered_calls_array["$call_date_key"]["inbound"]+0;
	$answered_calls+=$answered_calls_array["$call_date_key"]["outbound"]+0; # "Inclusive" remember

	$HTML_output.="<tr bgcolor='$bgcolor'>";
	$HTML_output.="<td>$call_date_key</td>";
	$HTML_output.="<td>".($data["answered"]+0)."</td>";
	$HTML_output.="<td>".($data["unanswered"]+0)."</td>";
	$HTML_output.="<td>".($data["answered"]+$data["unanswered"]+0)."</td>";

	$CSV_output["inclusive_sla_per_day"].="\"$call_date_key\",\"".($data["answered"]+0)."\",\"".($data["unanswered"]+0)."\",\"".($data["answered"]+$data["unanswered"]+0)."\"";

	for ($f=0; $f<count($svc_lvl_row); $f++)
		{
		$svc_lvl_row[$f]+=$answered_calls_array["$call_date_key"]["outbound"];
		$HTML_output.="<td align='right'>".sprintf("%.1f", (MathZDC((100*$svc_lvl_row[$f]), $answered_calls)))." %</td>";
		$CSV_output["inclusive_sla_per_day"].=",\"".sprintf("%.1f", (MathZDC((100*$svc_lvl_row[$f]), $answered_calls)))." %\"";
		}
	$HTML_output.="</tr>\n";

	if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

	$CSV_output["inclusive_sla_per_day"].="\n";

	$prev_int=$interval;
	}
$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td colspan='".(4+count($svc_lvl_row))."' class='export_row_cell'>"._QXZ("Export as")."...<a href=\"".$PHP_SELF."?download_rpt=inclusive_sla_per_day\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td class='export_row_cell' colspan='".(4+count($svc_lvl_row))."'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('DAY', 'inclusive_sla_per_day')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
###########################################

if ($download_rpt)
	{
	$data_to_download=$CSV_output["$download_rpt"];

	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "DAY_RPT_".$download_rpt."_".$FILE_TIME.".csv";
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
