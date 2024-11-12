<?php
# VERM_AREA_rpt.inc - Vicidial Enhanced Reporting area code/prefix report
#
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGELOG:
# 220825-1627 - First build
# 230106-1729 - Added page flushing so page elements appear as they load
#

# if (isset($_GET["SUBMIT"]))			{$SUBMIT=$_GET["SUBMIT"];}
# 	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["CLI_digits"]))			{$CLI_digits=$_GET["CLI_digits"];}
	elseif (isset($_POST["CLI_digits"]))	{$CLI_digits=$_POST["CLI_digits"];}
if (isset($_GET["CLI_position"]))			{$CLI_position=$_GET["CLI_position"];}
	elseif (isset($_POST["CLI_position"]))	{$CLI_position=$_POST["CLI_position"];}

$CLI_digits=preg_replace('/[^0-9]/', '', $CLI_digits);
$CLI_position=preg_replace('/[^0-9]/', '', $CLI_position);

if (!$CLI_digits) {$CLI_digits=3;}
if (!$CLI_position) {$CLI_position=1;}

$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Area Code Report").":</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";

# $HTML_output.="<form action='".$PHP_SELF."' method='post'>";
$HTML_output.="<table width='100%' id='details_table'>";
$HTML_output.="<tr>";
$HTML_output.="\t<td>"._QXZ("Number of CLID digits to search").": $NWB#VERM_AREA-CLID$NWE</td>";
$HTML_output.="\t<td><select name='CLI_digits' id='CLI_digits'>";
for ($i=1; $i<=12; $i++)
	{
	$HTML_output.="<option value='$i'".($i==$CLI_digits ? " selected" : "").">$i</option>";
	}
$HTML_output.="</td>";
$HTML_output.="</tr>";
$HTML_output.="<tr>";
$HTML_output.="\t<td>"._QXZ("Starting from position").": $NWB#VERM_AREA-starting_position$NWE</td>";
$HTML_output.="\t<td><select name='CLI_position' id='CLI_position'>";
for ($i=1; $i<=12; $i++)
	{
	$HTML_output.="<option value='$i'".($i==$CLI_position ? " selected" : "").">$i</option>";
	}
$HTML_output.="</td>";
$HTML_output.="</tr>";
$HTML_output.="<tr>";
$HTML_output.="<td>&nbsp;</td>";
$HTML_output.="<td><input type='submit' name='SUBMIT' class='actButton' value='"._QXZ("SEARCH")."'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
# $HTML_output.="</form>";

$answered_array=array();
$answered_waiting_array=array();
$unanswered_array=array();
$unanswered_waiting_array=array();
$uid_array=array();

$outb_stmt="select substr(phone_number, $CLI_position, $CLI_digits) as snippet, user, length_in_sec, uniqueid, lead_id from vicidial_log $vicidial_log_SQL $and_NANQUE_clause $exc_addtl_statuses";
if ($DB) {$HTML_output.="<B>$outb_stmt</B>";}
# print $outb_stmt."\n";
$outb_rslt=mysql_to_mysqli($outb_stmt, $link);
while ($outb_row=mysqli_fetch_array($outb_rslt))	
	{
	$snippet=$outb_row["snippet"];
	$user=$outb_row["user"];
	$length_in_sec=$outb_row["length_in_sec"];
	$uniqueid=$outb_row["uniqueid"];
	$lead_id=$outb_row["lead_id"];

	if (preg_match("/VDAD/", $user))
		{
		$xfer_stmt="select close_uniqueid from vicidial_xfer_log where front_uniqueid='$uniqueid'";
		$xfer_rslt=mysql_to_mysqli($xfer_stmt, $link);
#		if (mysqli_num_rows($xfer_rslt)==0)
#			{
#			while ($xfer_row=mysqli_fetch_row($xfer_rslt))
#				{
#				
#				}
#			}
#		else
#			{
			$unanswered_array["$snippet"]["calls"]++;
			$unanswered_array["$snippet"]["outbound_calls"]++;
			$unanswered_array["$snippet"]["wait_time"]+=$length_in_sec;

			$unanswered_waiting_array["$snippet"]["calls"]++;
			$unanswered_waiting_array["$snippet"]["wait_time"]+=$length_in_sec;
#			}
		array_push($uid_array, "$uniqueid");
		}
	else
		{
	#	$length_in_sec=GetAgentLogTalkTime($uniqueid, $user, $lead_id, $length_in_sec);
		$answered_array["$snippet"]["calls"]++;
		$answered_array["$snippet"]["outbound_calls"]++;
		$answered_array["$snippet"]["call_time"]+=$length_in_sec;
		}
	}

$inb_stmt="select substr(phone_number, $CLI_position, $CLI_digits) as snippet, user, uniqueid, if(comments='EMAIL', length_in_sec, length_in_sec-queue_seconds) as talk_time, if(comments='EMAIL', 0, queue_seconds) as queue_seconds, queue_position, lead_id from vicidial_closer_log $vicidial_closer_log_SQL $and_NANQUE_clause $exc_addtl_statuses";
if ($DB) {$HTML_output.="<B>$inb_stmt</B>";}
# print $inb_stmt."\n";
$inb_rslt=mysql_to_mysqli($inb_stmt, $link);
while ($inb_row=mysqli_fetch_array($inb_rslt))	
	{
	$snippet=$inb_row["snippet"];
	$user=$inb_row["user"];
	$length_in_sec=$inb_row["talk_time"];
	$wait_time=$inb_row["queue_seconds"];
	$uniqueid=$inb_row["uniqueid"];
	$position=$inb_row["queue_position"];
	$lead_id=$inb_row["lead_id"];
	
	if (preg_match("/VDCL/", $user))
		{
#		$xfer_stmt="select close_uniqueid from vicidial_xfer_log where front_uniqueid='$uniqueid'";
#		$xfer_rslt=mysql_to_mysqli($xfer_stmt, $link);
#		if (mysqli_num_rows($xfer_rslt)==0)
#			{
			$unanswered_array["$snippet"]["calls"]++;
			$unanswered_array["$snippet"]["inbound_calls"]++;
			$unanswered_array["$snippet"]["wait_time"]+=$wait_time;
			$unanswered_array["$snippet"]["position"]+=$position;

			$unanswered_waiting_array["$snippet"]["calls"]++;
			$unanswered_waiting_array["$snippet"]["wait_time"]+=$wait_time;
#			}
		}
	else
		{
#		$length_in_sec=GetAgentLogTalkTime($uniqueid, $user, $lead_id, $length_in_sec);
		$answered_array["$snippet"]["calls"]++;
		$answered_array["$snippet"]["inbound_calls"]++;
		$answered_array["$snippet"]["position"]+=$position;
		$answered_array["$snippet"]["call_time"]+=$length_in_sec;
		$answered_array["$snippet"]["wait_time"]+=$wait_time;
		}
	}

ksort($answered_array);
ksort($unanswered_array);

if ($show_an)
	{
	print_r($answered_array);
	}
if ($show_un)
	{
	print_r($unanswered_array);
	}

##### ANSWERED OUTPUT #####
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Detail for answered calls").": $NWB#VERM_AREA-answered_details$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='details_table'>";
$HTML_output.="<tr><th>"._QXZ("Area code/Caller id")."</th><th>"._QXZ("Taken calls")."</th><th>"._QXZ("Total calling time")."</th><th>"._QXZ("Average time per call")."</th><th>"._QXZ("Average wait time per call")."</th></tr>";

$CSV_output["answered_area"]="\""._QXZ("Area code/Caller id")."\",\""._QXZ("Taken calls")."\",\""._QXZ("Total calling time")."\",\""._QXZ("Average time per call")."\",\""._QXZ("Average wait time per call")."\"\n";

$i=0;
foreach ($answered_array as $prefix => $data)
	{
	$i++;
	$call_time=($data["call_time"]>=3600 ? intval(floor($data["call_time"]/3600)).date(":i:s", $data["call_time"]) : intval(date("i", $data["call_time"])).":".date("s", $data["call_time"]));

	$average_time=round($data["call_time"]/$data["calls"]);
	$average_time=($average_time>=3600 ? intval(floor($average_time/3600)).date(":i:s", $average_time) : intval(date("i", $average_time)).":".date("s", $average_time));

	$average_wait_time=round($data["wait_time"]/$data["calls"]);
	$average_wait_time=($average_wait_time>=3600 ? intval(floor($average_wait_time/3600)).date(":i:s", $average_wait_time) : intval(date("i", $average_wait_time)).":".date("s", $average_wait_time));

	$CSV_output["answered_area"].="\"".$prefix."\",";
	$CSV_output["answered_area"].="\"".$data["calls"]."\",";
	$CSV_output["answered_area"].="\"".$call_time."\",";
	$CSV_output["answered_area"].="\"".$average_time_str."\",";
	$CSV_output["answered_area"].="\"".$average_wait_time."\"";
	$CSV_output["answered_area"].="\n";

	$HTML_output.="<tr bgcolor=\"#".($i%2==0 ? "FFF" : "CCC")."\">";
	$HTML_output.="<td>".$prefix."</td>";
	$HTML_output.="<td>".$data["calls"]."</td>";
	$HTML_output.="<td>".$call_time."</td>";
	$HTML_output.="<td>".$average_time."</td>";
	$HTML_output.="<td>".$average_wait_time."</td>";
	$HTML_output.="</tr>";

	}
$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='5'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?CLI_position=$CLI_position&CLI_digits=$CLI_digits&download_rpt=answered_area\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td class='export_row_cell' colspan='5'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('AREA', 'answered_area')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
#############################

##### UNANSWERED OUTPUT #####
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Detail for unanswered calls").": $NWB#VERM_AREA-unanswered_details$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='details_table'>";
$HTML_output.="<tr><th>"._QXZ("Area code/Caller id")."</th><th>"._QXZ("Lost calls")."</th><th>"._QXZ("Average wait time per call")."</th><th>"._QXZ("Average position")."</th></tr>";

$CSV_output["unanswered_area"]="\""._QXZ("Area code/Caller id")."\",\""._QXZ("Lost calls")."\",\""._QXZ("Average wait time per call")."\",\""._QXZ("Average position")."\"\n";
$i=0;
foreach ($unanswered_array as $prefix => $data)
	{
	$i++;
	$call_time=($data["call_time"]>=3600 ? intval(floor($data["call_time"]/3600)).date(":i:s", $data["call_time"]) : intval(date("i", $data["call_time"])).":".date("s", $data["call_time"]));

	$average_position=sprintf("%.1f", ($data["position"]/$data["calls"]));
	# $average_time=($average_time>=3600 ? intval(floor($average_time/3600)).date(":i:s", $average_time) : intval(date("i", $average_time)).":".date("s", $average_time));

	$average_wait_time=round($data["wait_time"]/$data["calls"]);
	$average_wait_time=($average_wait_time>=3600 ? intval(floor($average_wait_time/3600)).date(":i:s", $average_wait_time) : intval(date("i", $average_wait_time)).":".date("s", $average_wait_time));

	$CSV_output["unanswered_area"].="\"".$prefix."\",";
	$CSV_output["unanswered_area"].="\"".$data["calls"]."\",";
	$CSV_output["unanswered_area"].="\"".$average_wait_time."\"";
	$CSV_output["unanswered_area"].="\"".$average_position."\"";
	$CSV_output["unanswered_area"].="\n";

	$HTML_output.="<tr bgcolor=\"#".($i%2==0 ? "FFF" : "CCC")."\">";
	$HTML_output.="<td>".$prefix."</td>";
	$HTML_output.="<td>".$data["calls"]."</td>";
	$HTML_output.="<td>".$average_wait_time."</td>";
	$HTML_output.="<td>".$average_position."</td>";
	$HTML_output.="</tr>";
	}
$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='4'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?CLI_position=$CLI_position&CLI_digits=$CLI_digits&download_rpt=unanswered_area\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td class='export_row_cell' colspan='4'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('AREA', 'unanswered_area')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
#############################

if ($download_rpt)
	{
	$data_to_download=$CSV_output["$download_rpt"];

	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "AREA_RPT_".$download_rpt."_".$FILE_TIME.".csv";
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

<?php 
function GetAgentLogTalkTime($call_uniqueid, $call_user, $call_lead_id, $current_call_time)
	{
	global $DB, $link;
	$val_stmt="select length_in_sec from vicidial_agent_log where lead_id='$call_lead_id' and uniqueid='$call_uniqueid' and user='$call_user' order by event_time asc limit 1";
	$val_rslt=mysql_to_mysqli($val_stmt, $link);
	while ($val_row=mysqli_fetch_row($val_rslt))
		{
		$talk_sec=$val_row[0];
		if ($talk_sec<$current_call_time-1 || $talk_sec>$current_call_time+1) 
			{
			echo "WARNING: $call_uniqueid shows $current_call_time length, agent logs show $talk_sec\n";
			$current_call_time=$talk_sec;
			}
		}
	
	return $current_call_time;
	}

?>
