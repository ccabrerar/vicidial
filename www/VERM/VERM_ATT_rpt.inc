<?php
# VERM_ATT_rpt.inc - Vicidial Enhanced Reporting ACD report
#
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGELOG:
# 220825-1626 - First build
# 230106-1311 - Added page flushing to show page load progress
#

# if (isset($_GET["SUBMIT"]))			{$SUBMIT=$_GET["SUBMIT"];}
# 	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}

# $PHP_SELF=$_SERVER['PHP_SELF'];

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

	if (preg_match('/VDAD|VDCL/', $user) && preg_match('/^DROP$|TIMEOT|WAITTO/', $status)) 
		{
		# UNANSWERED, DO NOTHING
		$unanswered++;
		}
	else
		{
		if (in_array($status, $lost_statuses_array))
			{
			$ACD_queue_counts["$campaign_id"]["lost"]++;
			$ACD_queue_counts["$campaign_id"]["lost_ring_time"]+=$ring_time;
		
			$ACD_terminal_counts["$user"]["lost"]++;
			$ACD_terminal_counts["$user"]["lost_ring_time"]+=$ring_time;
			}
		else
			{
			$ACD_queue_counts["$campaign_id"]["calls"]++;
			$ACD_queue_counts["$campaign_id"]["ring_time"]+=$ring_time;
		
			$ACD_terminal_counts["$user"]["calls"]++;
			$ACD_terminal_counts["$user"]["ring_time"]+=$ring_time;
			}
		}
	}

/*
$stmt="";
$rslt=mysql_to_mysqli($stmt, $link);
while ($row=mysqli_fetch_array($rslt))
	{
	$campaign_id=$row["campaign_id"];
	$user=$row["user"];
	$calls=$row["ct"];
	$ring_time=$row["ring_time"];

	$ACD_queue_counts["$campaign_id"]["calls"]+=$calls;
	$ACD_queue_counts["$campaign_id"]["ring_time"]+=$ring_time;
	
	$ACD_terminal_counts["$user"]["calls"]+=$calls;
	$ACD_terminal_counts["$user"]["ring_time"]+=$ring_time;
	}
*/

ksort($ACD_queue_counts);
ksort($ACD_terminal_counts);


##### ACD ATTEMPTS BY CAMPAIGN/INGROUP #####
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("ACD attempts by Campaign/ingroup").": $NWB#VERM_ATT-acd_by_queue$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='details_table'>";
$HTML_output.="<tr><th>"._QXZ("Campaign/ingroup")."</th><th>"._QXZ("N. lost")."</th><th>"._QXZ("Avr ring")."</th><th>"._QXZ("Ring (s)")."</th><th>"._QXZ("N. Taken")."</th><th>"._QXZ("Avg ring")."</th><th>"._QXZ("Ring (s)")."</th></tr>";

$CSV_output["acd_attempts_by_queue"]="\""._QXZ("Campaign/ingroup")."\",\""._QXZ("N. lost")."\",\""._QXZ("Avg ring")."\",\""._QXZ("Ring (s)")."\",\""._QXZ("N. Taken")."\",\""._QXZ("Avg ring")."\",\""._QXZ("Ring (s)")."\"\n";
$i=0;
foreach ($ACD_queue_counts as $queue => $data)
	{
	if (!$data["lost"]) # !$data["lost_ring_time"] ||  - commented out 6/13/22 because we want avr ring to show zero if there ARE lost.
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


	$lost_time=($data["lost_ring_time"]>=3600 ? intval(floor($data["lost_ring_time"]/3600)).date(":i:s", $data["lost_ring_time"]) : intval(date("i", $data["lost_ring_time"])).":".date("s", $data["lost_ring_time"]));
	$ring_time=($data["ring_time"]>=3600 ? intval(floor($data["ring_time"]/3600)).date(":i:s", $data["ring_time"]) : intval(date("i", $data["ring_time"])).":".date("s", $data["ring_time"]));

	$queue_name=$queue_names["$queue"];

	$CSV_output["acd_attempts_by_queue"].="\"".$queue_name."\",";
	$CSV_output["acd_attempts_by_queue"].="\"".(!$data["lost"] ? "0" : $data["lost"])."\",";
	$CSV_output["acd_attempts_by_queue"].="\"".$avg_lost_time."\",";
	$CSV_output["acd_attempts_by_queue"].="\"".$lost_time."\",";
	$CSV_output["acd_attempts_by_queue"].="\"".(!$data["calls"] ? "0" : $data["calls"])."\",";
	$CSV_output["acd_attempts_by_queue"].="\"".$avg_ring_time."\",";
	$CSV_output["acd_attempts_by_queue"].="\"".$ring_time."\",";
	$CSV_output["acd_attempts_by_queue"].="\n";

	$HTML_output.="<tr>";
	$HTML_output.="<td>".$queue_name."</td>";
	$HTML_output.="<td>".(!$data["lost"] ? "0" : $data["lost"])."</td>";
	$HTML_output.="<td>".$avg_lost_time."</td>";
	$HTML_output.="<td>".$lost_time."</td>";
	$HTML_output.="<td>".(!$data["calls"] ? "0": $data["calls"])."</td>";
	$HTML_output.="<td>".$avg_ring_time."</td>";
	$HTML_output.="<td>".$ring_time."</td>";
	$HTML_output.="</tr>";	
	}
$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='7'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=acd_attempts_by_queue\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td class='export_row_cell' colspan='7'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('ATT', 'acd_attempts_by_queue')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table><BR>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
####################################


##### ACD ATTEMPTS BY AGENT #####
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("ACD attempts by agent").": $NWB#VERM_ATT-acd_by_agent$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='details_table'>";
$HTML_output.="<tr><th>"._QXZ("Agent")."</th><th>"._QXZ("N. lost")."</th><th>"._QXZ("Avr ring")."</th><th>"._QXZ("Ring (s)")."</th><th>"._QXZ("N. Taken")."</th><th>"._QXZ("Avg ring")."</th><th>"._QXZ("Ring (s)")."</th></tr>";

$CSV_output["acd_attempts_by_terminal"]="\""._QXZ("Campaign/ingroup")."\",\""._QXZ("N. lost")."\",\""._QXZ("Avg ring")."\",\""._QXZ("Ring (s)")."\",\""._QXZ("N. Taken")."\",\""._QXZ("Avg ring")."\",\""._QXZ("Ring (s)")."\"\n";
$i=0;
foreach ($ACD_terminal_counts as $queue => $data)
	{
	if (!$data["lost"])  # !$data["lost_ring_time"] ||  - commented out 6/13/22 because we want avr ring to show zero if there ARE lost.
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

	$terminal=($show_full_agent_info && $terminal_names["$queue"]!="" ? "$queue - " : "").($terminal_names["$queue"]!="" ? $terminal_names["$queue"] : "($queue)");

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
$HTML_output.="<td class='export_row_cell' colspan='7'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('ATT', 'acd_attempts_by_terminal')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table><BR>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
#############################

if ($download_rpt)
	{
	$data_to_download=$CSV_output["$download_rpt"];

	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "ATT_RPT_".$download_rpt."_".$FILE_TIME.".csv";
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
