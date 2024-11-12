<?php
# VERM_IVR_rpt.inc - Vicidial Enhanced Reporting IVR report
#
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGELOG:
# 220825-1619 - First build
# 230106-1328 - Added page flushing to show page load progress
# 240801-1130 - Code updates for PHP8 compatibility
#

if (isset($_GET["page_no"]))			{$page_no=$_GET["page_no"];}
	elseif (isset($_POST["page_no"]))	{$page_no=$_POST["page_no"];}
if (isset($_GET["sort_ivr_details"]))			{$sort_ivr_details=$_GET["sort_ivr_details"];}
	elseif (isset($_POST["sort_ivr_details"]))	{$sort_ivr_details=$_POST["sort_ivr_details"];}

if ($non_latin < 1)
	{
	$sort_ivr_details = preg_replace('/[^\s\-_0-9a-zA-Z]/','',$sort_ivr_details);
	}
else
	{
	$sort_ivr_details = preg_replace('/[^\s\-_0-9\p{L}]/u','',$sort_ivr_details);
	}
$page_no=preg_replace('/[^0-9]/', '', $page_no);
if (!$page_no || $page_no<1) {$page_no=1;}

$ll=($page_no-1)*20;
$ul=($page_no*20);
$sort_clause=" order by call_date";
$sort_index=preg_replace('/ desc/', '', $sort_ivr_details);
if (preg_match('/ desc$/', $sort_ivr_details)) 
	{
	$sort_char="&#8595;"; 
	$reverse_link=preg_replace('/ desc$/', '', $sort_ivr_details);
	} 
else 
	{
	$sort_char="&#8593;"; 
	$reverse_link=$sort_ivr_details." desc";
	}
$sort_ivr_details_preg=preg_replace('/ desc$/', '', $sort_ivr_details);

$ivr_data=array();
$ivr_data_totals=array();
$path_data=array();
$ivr_details=array();

# For IVR details we can't use the comment_d clause as it kicks out records that should be in there
$stmt="select distinct uniqueid, comment_a from live_inbound_log $live_inbound_log_SQL order by uniqueid asc";
if ($DB) {$HTML_output.="<B>$stmt</B>";}
$rslt=mysql_to_mysqli($stmt, $link);
$i=0;
while ($row=mysqli_fetch_row($rslt))
	{
	$uniqueid=$row[0];
	$comment_a=$row[1];
	$total_call_flow="";
	$prompt_stmt="select start_time, comment_d, phone_ext from live_inbound_log where uniqueid='$uniqueid' and comment_a='$comment_a' order by start_time, comment_d asc";
	# print "$prompt_stmt\n";
	$prompt_rslt=mysql_to_mysqli($prompt_stmt, $link);

	$current_call_total_time=0;
	$prev_start_time="";
	$uid_is_valid=0;
	while ($prompt_row=mysqli_fetch_array($prompt_rslt))
		{
		$current_prompt=$prompt_row["comment_d"];

		if (preg_match("/$ivr_choice/i", $current_prompt)) # from search results
			{$uid_is_valid=1;}

		$current_start_time=$prompt_row["start_time"];
		# if (mysqli_num_rows($prompt_rslt)==1) {$prev_start_time=$current_start_time;} # Need this in case only one record is returned
		if (!$prev_start_time) {$prev_start_time=$current_start_time;}

		$array_key=preg_replace('/\>[^,]+,/', ',', $total_call_flow);
		$current_interval=strtotime("$current_start_time")-strtotime("$prev_start_time");
		$total_call_flow.="$current_prompt,";
		$array_key.="$current_prompt";	
		$current_call_total_time+=$current_interval;

		if ($i>=$ll && $i<$ul || $download_rpt)
			{
			$current_call_total_time_fmt=($current_call_total_time>=3600 ? intval(floor($current_call_total_time/3600)).date(":i:s", $current_call_total_time) : intval(date("i", $current_call_total_time)).":".date("s", $current_call_total_time));

			$ivr_details["$uniqueid"]["date"]=$prev_start_time;
			$ivr_details["$uniqueid"]["caller"]=$prompt_row["phone_ext"];
			$ivr_details["$uniqueid"]["ivr"]=$current_call_total_time_fmt;
			$ivr_details["$uniqueid"]["ivr_path"]=$array_key;
			$ivr_details["$uniqueid"]["did"]=GetDID($uniqueid);
			$ivr_details["$uniqueid"]["goal"]="";
			$ivr_details["$uniqueid"]["closecallid"]="";

			GetGoal($uniqueid);
			}

		$prev_prompt=$prompt_row["comment_d"];
		$prev_start_time=$prompt_row["start_time"];
		}
	
	# Does uid contain IVR choice?
	if ($uid_is_valid)
		{
		$i++;
		}
	else
		{
		unset($ivr_details["$uniqueid"]);
		}
	}
$ivr_records=$i;
$total_pages=ceil($i/20);

$stmt="select distinct uniqueid, comment_a from live_inbound_log $live_inbound_log_SQL and comment_d like '%>%' order by uniqueid asc";
if ($DB) {$HTML_output.="<B>$stmt</B>";}
$rslt=mysql_to_mysqli($stmt, $link);
while ($row=mysqli_fetch_row($rslt))
	{
	$uniqueid=$row[0];
	$comment_a=$row[1];
	$total_call_flow="";
	$current_call_total_time=0;
	$prompt_stmt="select start_time, comment_d, phone_ext from live_inbound_log where uniqueid='$uniqueid' and comment_a='$comment_a' order by start_time, comment_d asc";
	$prompt_rslt=mysql_to_mysqli($prompt_stmt, $link);

	while ($prompt_row=mysqli_fetch_array($prompt_rslt))
		{
		$current_prompt=$prompt_row["comment_d"];
		$current_start_time=$prompt_row["start_time"];
		if (preg_match('/\>/', $current_prompt))
			{
			$array_key=preg_replace('/\>[^,]+,/', ',', $total_call_flow);
			$total_call_flow.="$current_prompt,";
			$array_key.="$current_prompt";	
			$current_interval=strtotime("$current_start_time")-strtotime("$prev_start_time");
			$current_call_total_time+=$current_interval;
			$ivr_data["$array_key"]["calls"]++;
			$hangup_array=GetHangups($uniqueid);
			$ivr_data["$array_key"]["hangups"]+=$hangup_array[0];
			if ($hangup_array[0]==0)
				{
				$ivr_data["$array_key"]["goals"]++;
				$ivr_data["$array_key"]["goal_time"]+=$hangup_array[1];
				}
			else
				{
				$ivr_data["$array_key"]["goals"]+=0;
				$ivr_data["$array_key"]["goal_time"]+=0;
				}
			$ivr_data["$array_key"]["time"]+=$current_interval;

			#$ivr_details["$uniqueid"]["date"]=$prev_start_time;
			#$ivr_details["$uniqueid"]["caller"]=$prompt_row["phone_ext"];
			#$ivr_details["$uniqueid"]["ivr"]=$current_interval;
			#$ivr_details["$uniqueid"]["ivr_path"]=$array_key;
			#$ivr_details["$uniqueid"]["did"]=GetDID($uniqueid);
			#$ivr_details["$uniqueid"]["goal"]="";

			if (!$ivr_data["$array_key"]["minimum_time"]) {$ivr_data["$array_key"]["minimum_time"]=86400;}
			if (!$ivr_data["$array_key"]["maximum_time"]) {$ivr_data["$array_key"]["maximum_time"]=0;}
			if ($ivr_data["$array_key"]["minimum_time"]>$current_interval) {$ivr_data["$array_key"]["minimum_time"]=$current_interval;}
			if ($ivr_data["$array_key"]["maximum_time"]<$current_interval) {$ivr_data["$array_key"]["maximum_time"]=$current_interval;}

			GetCallPath($uniqueid, $comment_a); # , $array_key, $current_interval
			}
		$prev_prompt=$prompt_row["comment_d"];
		$prev_start_time=$prompt_row["start_time"];
		}
	
	}

ksort($ivr_data);
ksort($path_data);

$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("IVR Traversal").": $NWB#VERM_IVR-ivr_traversal$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>\n";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>"._QXZ("IVR path")."</th>";
$HTML_output.="<th>"._QXZ("N. calls")."</th>";
$HTML_output.="<th>"._QXZ("IVR goal report")."</th>";
$HTML_output.="<th>"._QXZ("Success rate")."</th>";
$HTML_output.="<th>"._QXZ("Hangups")."</th>";
$HTML_output.="<th>"._QXZ("Attrition rate")."</th>";
$HTML_output.="</tr>\n";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

$CSV_output["ivr_traversal"]="\""._QXZ("IVR path")."\",\""._QXZ("N. Calls")."\",\""._QXZ("IVR goal report")."\",\""._QXZ("Success rate")."\",\""._QXZ("Hangups")."\",\""._QXZ("Attrition rate")."\"\n";

$prev_ivr_key="";
$IVR_prompt_header=array();
$IVR_prompt_subrows="";
foreach ($ivr_data as $ivr_path => $data)
	{
	if (preg_match("/$ivr_choice/i", $ivr_path)) # from search results
		{
		$ivr_path_array=explode(">", $ivr_path);
		$ivr_path_base=$ivr_path_array[0]."-TOTAL";
		$ivr_prompt=$ivr_path_array[1];

		$ivr_data["$ivr_path_base"]["calls"]+=$data["calls"];
		$ivr_data["$ivr_path_base"]["hangups"]+=$data["hangups"];
		$ivr_data["$ivr_path_base"]["time"]+=$data["time"];
		$ivr_data["$ivr_path_base"]["goals"]+=$data["goals"];
		$ivr_data["$ivr_path_base"]["goal_time"]+=$data["goal_time"];
		if (!$ivr_data["$ivr_path_base"]["minimum_time"]) {$ivr_data["$ivr_path_base"]["minimum_time"]=86400;}
		if (!$ivr_data["$ivr_path_base"]["maximum_time"]) {$ivr_data["$ivr_path_base"]["maximum_time"]=0;}
		if ($ivr_data["$ivr_path_base"]["minimum_time"]>$ivr_data["$ivr_path"]["minimum_time"]) {$ivr_data["$ivr_path_base"]["minimum_time"]=$ivr_data["$ivr_path"]["minimum_time"];}
		if ($ivr_data["$ivr_path_base"]["maximum_time"]<$ivr_data["$ivr_path"]["maximum_time"]) {$ivr_data["$ivr_path_base"]["maximum_time"]=$ivr_data["$ivr_path"]["maximum_time"];}

	/*
		if ($prev_ivr_key!=$ivr_path_base)
			{
			$HTML_output.="<tr class='standard_font bold'>";
			$HTML_output.="<th>".$callmenu_names["$ivr_prompt"]."</th>";
			$HTML_output.="<th>".$IVR_prompt_header["$ivr_prompt"]["calls"]."</th>";
			$HTML_output.="<th>IVR goal report</th>";
			$HTML_output.="<th>Success rate</th>";
			$HTML_output.="<th>Hangups</th>";
			$HTML_output.="<th>Attrition rate</th>";
			$HTML_output.="</tr>";

			$IVR_prompt_header=array();
			}
		
		$prev_ivr_key=$ivr_path_base;
	*/
		}
	}

ksort($ivr_data); # Again, so we have totals

# print_r($ivr_data); 

foreach ($ivr_data as $ivr_path => $data)
	{
	if(preg_match("/$ivr_choice/i", $ivr_path)) # from search results
		{
		if(preg_match('/\-TOTAL$/', $ivr_path))
			{
			$ivr_value="";
			$ivr_base=preg_replace('/\-TOTAL$/', '', $ivr_path);
			$ivr_array=explode(",", $ivr_base);
			for($i=0; $i<count($ivr_array); $i++)
				{
				# if ($callmenu_names["$ivr_array[$i]"]) {$ivr_base=preg_replace("/$ivr_array[$i]/", $callmenu_names["$ivr_array[$i]"], $ivr_base);}
				$ivr_value.=$ivr_array[$i].($callmenu_names["$ivr_array[$i]"] ? " - ".$callmenu_names["$ivr_array[$i]"] : "")." => ";
				}
			# $ivr_value=$ivr_base." - ".$callmenu_names["$ivr_base"];
			# $CSV_ivr_value=$ivr_base." - ".$callmenu_names["$ivr_base"];
			$ivr_value=preg_replace('/ \=\> $/', "", $ivr_value);
			$CSV_ivr_value=$ivr_value;
			}
		else
			{
			$ivr_value=preg_replace("/^$ivr_base\>/", "&nbsp;#&nbsp;", $ivr_path);
			$CSV_ivr_value=preg_replace("/^$ivr_base\>/", " # ", $ivr_path);
			}

		$HTML_output.="<tr class='standard_font_small'>";
		$HTML_output.="<td>".$ivr_value."</td>";
		$HTML_output.="<td>".$data["calls"]."</td>";
		$HTML_output.="<td>".$data["goals"]."</td>";
		$HTML_output.="<td>".sprintf("%.1f", (MathZDC(100*($data["calls"]-$data["hangups"]), $data["calls"])))." %</td>";
		$HTML_output.="<td>".$data["hangups"]."</td>";
		$HTML_output.="<td>".sprintf("%.1f", (MathZDC(100*$data["hangups"], $data["calls"])))." %</td>";
		$HTML_output.="</tr>\n";

		if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

		$CSV_output["ivr_traversal"].="\"".$CSV_ivr_value."\",\"".$data["calls"]."\",\"".($data["calls"]-$data["hangups"])."\",\"".sprintf("%.1f", (MathZDC(100*($data["calls"]-$data["hangups"]), $data["calls"])))." %\",\"".$data["hangups"]."\",\"".sprintf("%.1f", (MathZDC(100*$data["hangups"], $data["calls"])))." %\"\n";

		if(preg_match('/\-TOTAL$/', $ivr_path)) {unset($ivr_data["$ivr_path"]);} # Remove total, don't need anymore
		}
	}
$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='6'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=ivr_traversal\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td class='export_row_cell' colspan='6'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('IVR', 'ivr_traversal')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>\n";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

# print $HTML_output; die;
# print_r($ivr_data); die;

# IVR traversal & timing output

$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("IVR Timing").": $NWB#VERM_IVR-ivr_timing$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>\n";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>"._QXZ("IVR path")."</th>";
$HTML_output.="<th>"._QXZ("N. calls")."</th>";
$HTML_output.="<th>"._QXZ("Average time")."</th>";
$HTML_output.="<th>"._QXZ("Total time")."</th>";
$HTML_output.="<th>"._QXZ("Minimum time")."</th>";
$HTML_output.="<th>"._QXZ("Maximum time")."</th>";
$HTML_output.="</tr>\n";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

$CSV_output["ivr_timing"]="\""._QXZ("IVR path")."\",\""._QXZ("N. Calls")."\",\""._QXZ("Average time")."\",\""._QXZ("Total time")."\",\""._QXZ("Minimum time")."\",\""._QXZ("Maximum time")."\"\n";

foreach ($ivr_data as $comment_d => $data)
	{
	if (preg_match("/$ivr_choice/i", $comment_d)) # from search results
		{

		$base_path=preg_replace('/\>.+$/', '', $comment_d);
		$selection=preg_replace('/^[^\>]+\>/', '', $comment_d);
		if(!$prev_base_path) {$prev_base_path=$base_path;}
		if (!$total_minimum_time) {$total_minimum_time=86400;}
		if (!$total_maximum_time) {$total_maximum_time=0;}

		if ($prev_base_path!=$base_path)
			{
			# Calculate totals for base path
			$total_success_rate=sprintf("%.1f", (100*MathZDC(($total_prompt_calls-$total_prompt_hangups), $total_prompt_calls)));
			$total_hangup_rate=sprintf("%.1f", (100*MathZDC($total_prompt_hangups, $total_prompt_calls)));
			$total_average_sec=round(MathZDC($total_prompt_time, $total_prompt_calls));
			$base_average_time=($total_average_sec>=3600 ? intval(floor($total_average_sec/3600)).date(":i:s", $total_average_sec) : intval(date("i", $total_average_sec)).":".date("s", $total_average_sec));
			$base_total_time=($total_prompt_time>=3600 ? intval(floor($total_prompt_time/3600)).date(":i:s", $total_prompt_time) : intval(date("i", $total_prompt_time)).":".date("s", $total_prompt_time));
			$base_minimum_time=($total_minimum_time>=3600 ? intval(floor($total_minimum_time/3600)).date(":i:s", $total_minimum_time) : intval(date("i", $total_minimum_time)).":".date("s", $total_minimum_time));
			$base_maximum_time=($total_maximum_time>=3600 ? intval(floor($total_maximum_time/3600)).date(":i:s", $total_maximum_time) : intval(date("i", $total_maximum_time)).":".date("s", $total_maximum_time));

			$base_path_array=explode(",", $prev_base_path);
			for ($i=0; $i<count($base_path_array); $i++)
				{
				$base_path_array[$i].=" - ".$callmenu_names["$base_path_array[$i]"];
				}
			$base_path_output=implode(" => ", $base_path_array);

			#$traversal_header="$base_path_output -> $total_prompt_calls, ".($total_prompt_calls-$total_prompt_hangups).", $total_success_rate %, $total_prompt_hangups, $total_hangup_rate %\n";
			#$traversal_output.=$traversal_header.$traversal_outputs."\n";

			# $time_header="$base_path_output -> $total_prompt_calls, $base_average_time, $base_total_time, $base_minimum_time, $base_maximum_time \n";
			# $CSV_time_output.=$time_header.$CSV_time_outputs."\n";

			$HTML_subheader="<tr class='standard_font_small'><td>$base_path_output</td><td>$total_prompt_calls</td><td>$base_average_time</td><td>$base_total_time</td><td>$base_minimum_time</td><td>$base_maximum_time</td></tr>";
			$HTML_output.=$HTML_subheader.$HTML_datarows;
			$HTML_datarows="";

			if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

			$CSV_header="\"$base_path_output\",\"$total_prompt_calls\",\"$base_average_time\",\"$base_total_time\",\"$base_minimum_time\",\"$base_maximum_time\"\n";
			$CSV_output["ivr_timing"].=$CSV_header.$CSV_row_output;
			$CSV_row_output="";


			# Reset data
			$traversal_outputs="";
			$CSV_time_outputs="";
			$total_prompt_calls=0;
			$total_prompt_hangups=0;
			$total_prompt_time=0;
			$total_minimum_time=86400;
			$total_maximum_time=0;
			}

		$average_sec=round(MathZDC($data["time"], $data["calls"]));
		$average_time=($average_sec>=3600 ? intval(floor($average_sec/3600)).date(":i:s", $average_sec) : intval(date("i", $average_sec)).":".date("s", $average_sec));
		$total_time=($data["time"]>=3600 ? intval(floor($data["time"]/3600)).date(":i:s", $data["time"]) : intval(date("i", $data["time"])).":".date("s", $data["time"]));
		$minimum_time=($data["minimum_time"]>=3600 ? intval(floor($data["minimum_time"]/3600)).date(":i:s", $data["minimum_time"]) : intval(date("i", $data["minimum_time"])).":".date("s", $data["minimum_time"]));
		$maximum_time=($data["maximum_time"]>=3600 ? intval(floor($data["maximum_time"]/3600)).date(":i:s", $data["maximum_time"]) : intval(date("i", $data["maximum_time"])).":".date("s", $data["maximum_time"]));
		
		$success_rate=sprintf("%.1f", (100*(MathZDC(($data["calls"]-$data["hangups"]), $data["calls"]))));
		$hangup_rate=sprintf("%.1f", (100*(MathZDC($data["hangups"], $data["calls"]))));
		#$traversal_outputs.="     - $selection -> ".$data["calls"].", ".($data["calls"]-$data["hangups"]).", $success_rate %, ".$data["hangups"].", $hangup_rate % \n";
		#$CSV_time_outputs.="     - $selection -> ".$data["calls"].", $average_time, $total_time, $minimum_time, $maximum_time\n";
		$HTML_datarows.="<tr class='standard_font_small'><td>&nbsp;#&nbsp; $selection</td><td>".$data["calls"]."</td><td>$average_time</td><td>$total_time</td><td>$minimum_time</td><td>$maximum_time</td></tr>\n";
		$CSV_row_output.="\" # $selection\",\"".$data["calls"]."\",\"$average_time\",\"$total_time\",\"$minimum_time\",\"$maximum_time\"\n";
		$total_prompt_calls+=$data["calls"];
		$total_prompt_hangups+=$data["hangups"];
		$total_prompt_time+=$data["time"];

		if ($total_minimum_time>$data["minimum_time"]) {$total_minimum_time=$data["minimum_time"];}
		if ($total_maximum_time<$data["maximum_time"]) {$total_maximum_time=$data["maximum_time"];}

		$prev_base_path=$base_path;
		}
	}

$total_average_sec=round(MathZDC($total_prompt_time, $total_prompt_calls));
$base_average_time=($total_average_sec>=3600 ? intval(floor($total_average_sec/3600)).date(":i:s", $total_average_sec) : intval(date("i", $total_average_sec)).":".date("s", $total_average_sec));
$base_total_time=($total_prompt_time>=3600 ? intval(floor($total_prompt_time/3600)).date(":i:s", $total_prompt_time) : intval(date("i", $total_prompt_time)).":".date("s", $total_prompt_time));
$base_minimum_time=($total_minimum_time>=3600 ? intval(floor($total_minimum_time/3600)).date(":i:s", $total_minimum_time) : intval(date("i", $total_minimum_time)).":".date("s", $total_minimum_time));
$base_maximum_time=($total_maximum_time>=3600 ? intval(floor($total_maximum_time/3600)).date(":i:s", $total_maximum_time) : intval(date("i", $total_maximum_time)).":".date("s", $total_maximum_time));

#$traversal_header="$prev_base_path -> $total_prompt_calls, ".($total_prompt_calls-$total_prompt_hangups).", $total_success_rate %, $total_prompt_hangups, $total_hangup_rate %\n";
#$traversal_output.=$traversal_header.$traversal_outputs;

# $time_header="$prev_base_path -> $total_prompt_calls, $base_average_time, $base_total_time, $base_minimum_time, $base_maximum_time \n";
# $CSV_time_output.=$time_header.$CSV_time_outputs."\n";

$HTML_subheader="<tr class='standard_font_small'><td>$prev_base_path - $callmenu_names[$prev_base_path]</td><td>$total_prompt_calls</td><td>$base_average_time</td><td>$base_total_time</td><td>$base_minimum_time</td><td>$base_maximum_time</td></tr>\n";
$HTML_output.=$HTML_subheader.$HTML_datarows;
$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='6'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=ivr_timing\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td class='export_row_cell' colspan='6'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('IVR', 'ivr_timing')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>\n";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

$CSV_header="\"$prev_base_path - $callmenu_names[$prev_base_path]\",\"$total_prompt_calls\",\"$base_average_time\",\"$base_total_time\",\"$base_minimum_time\",\"$base_maximum_time\"\n";
$CSV_output["ivr_timing"].=$CSV_header.$CSV_row_output;

# print $CSV_time_output;

# print $CSV_output["ivr_timing"]; die;

# print $HTML_output;

# IVR goal output

$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("IVR Goals").": $NWB#VERM_IVR-ivr_goals$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>\n";
$HTML_output.="<tr class='standard_font bold'>";
$HTML_output.="<th>"._QXZ("Goal")."</th>";
$HTML_output.="<th>"._QXZ("IVR path")."</th>";
$HTML_output.="<th>"._QXZ("Selection")."</th>";
$HTML_output.="<th>"._QXZ("N. calls")."</th>";
$HTML_output.="<th>"._QXZ("Average time")."</th>";
$HTML_output.="<th>"._QXZ("Minimum time")."</th>";
$HTML_output.="<th>"._QXZ("Maximum time")."</th>";
$HTML_output.="</tr>\n";

$CSV_output["ivr_goal"]="\""._QXZ("Goal")."\",\""._QXZ("IVR path")."\",\""._QXZ("Selection")."\",\""._QXZ("N. calls")."\",\""._QXZ("Average time")."\",\""._QXZ("Minimum time")."\",\""._QXZ("Maximum time")."\"\n";

foreach ($path_data as $goal => $path)
	{
	ksort($path);
	foreach ($path as $key => $data)
		{
		if (preg_match("/$ivr_choice/i", $key)) # from search results
			{
			$key_array=explode(">", $key);

			$final_key_path="";
			$ivr_array=explode(",", $key_array[0]);
			for($i=0; $i<count($ivr_array); $i++)
				{
				# if ($callmenu_names["$ivr_array[$i]"]) {$ivr_base=preg_replace("/$ivr_array[$i]/", $callmenu_names["$ivr_array[$i]"], $ivr_base);}
				$final_key_path.=$ivr_array[$i].($callmenu_names["$ivr_array[$i]"] ? " - ".$callmenu_names["$ivr_array[$i]"] : "")." => ";
				}
			$final_key_path=preg_replace('/ \=\> $/', "", $final_key_path);
			$CSV_final_key_path=$final_key_path;

			$average_sec=round(MathZDC($data["time"], $data["calls"]));
			$average_time=($average_sec>=3600 ? intval(floor($average_sec/3600)).date(":i:s", $average_sec) : intval(date("i", $average_sec)).":".date("s", $average_sec));
			$minimum_time=($data["minimum_time"]>=3600 ? intval(floor($data["minimum_time"]/3600)).date(":i:s", $data["minimum_time"]) : intval(date("i", $data["minimum_time"])).":".date("s", $data["minimum_time"]));
			$maximum_time=($data["maximum_time"]>=3600 ? intval(floor($data["maximum_time"]/3600)).date(":i:s", $data["maximum_time"]) : intval(date("i", $data["maximum_time"])).":".date("s", $data["maximum_time"]));

			$HTML_output.="<tr class='standard_font_small'>";
			$HTML_output.="<td>"._QXZ("Campaign/ingroup").": ".$ingroup_names["$goal"]."</td>";
#			$HTML_output.="<td>".$key_array[0]." - ".$callmenu_names["$key_array[0]"]."</td>";
			$HTML_output.="<td>".$final_key_path."</td>";
			$HTML_output.="<td>".$key_array[1]."</td>";
			$HTML_output.="<td>".$data["calls"]."</td>";
			$HTML_output.="<td>".$average_time."</td>";
			$HTML_output.="<td>".$minimum_time."</td>";
			$HTML_output.="<td>".$maximum_time."</td>";
			$HTML_output.="</tr>\n";

			if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

			$CSV_output["ivr_goal"].="\""._QXZ("Campaign/ingroup").": ".$ingroup_names["$goal"]."\",";
#			$CSV_output["ivr_goal"].="\"".$key_array[0]." - ".$callmenu_names["$key_array[0]"]."\",";
			$CSV_output["ivr_goal"].="\"".$CSV_final_key_path."\",";
			$CSV_output["ivr_goal"].="\"".$key_array[1]."\",";
			$CSV_output["ivr_goal"].="\"".$data["calls"]."\",";
			$CSV_output["ivr_goal"].="\"".$average_time."\",";
			$CSV_output["ivr_goal"].="\"".$minimum_time."\",";
			$CSV_output["ivr_goal"].="\"".$maximum_time."\"";
			$CSV_output["ivr_goal"].="\n";
			}
		}
	}

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' colspan='7'>"._QXZ("Export as")."...<a  href=\"".$PHP_SELF."?download_rpt=ivr_goal\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td class='export_row_cell' colspan='7'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('IVR', 'ivr_goal')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

#############################

$HTML_output.="<BR><h2 class='rpt_header' id='sid_anchor'>"._QXZ("IVR Details").": $NWB#VERM_IVR-ivr_details$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>\n";
$HTML_output.="<tr class='export_row'>";
$HTML_output.="<td class='export_row_cell' align='left'>";
$HTML_output.="<input type='button' class='download_button' onClick=\"GoToDetailsPage('IVR', '1')\" title=\"First page\" value='|<'>&nbsp;&nbsp;&nbsp;&nbsp;";
$HTML_output.="<input type='button' class='download_button' onClick=\"GoToDetailsPage('IVR', '".($page_no-1)."')\" title=\"Prev page\" value='<<'>";
$HTML_output.="</td>";
$HTML_output.="<td class='export_row_cell' align='center' colspan='5'>&nbsp;</td>";
$HTML_output.="<td class='export_row_cell' align='right'>";
$HTML_output.="<input type='button' class='download_button' onClick=\"GoToDetailsPage('IVR', '".($page_no<$total_pages ? $page_no+1 : $total_pages)."')\" title=\"Next page\" value='>>'>&nbsp;&nbsp;&nbsp;&nbsp;";
$HTML_output.="<input type='button' class='download_button' onClick=\"GoToDetailsPage('IVR', '".($total_pages)."')\" title=\"Last page\" value='>|'>";
$HTML_output.="</td>";
$HTML_output.="</tr>";

$ivr_details_columns=array(
_QXZ("Date") => "date",
_QXZ("Caller") => "caller",
_QXZ("IVR") => "ivr",
_QXZ("IVR Path") => "ivr_path",
_QXZ("DID") => "did",
_QXZ("Goal") => "goal",
);

$CSV_output["ivr_details"]="";
$HTML_output.="<tr class='standard_font bold'>";
foreach ($ivr_details_columns as $display_name => $column_name)
	{
	if ($display_name!="uniqueid")
		{
		$CSV_output["ivr_details"].="\"$display_name\",";
		# $HTML_output.="<th><a class='header_link' name='call_detail_sort_".$column_name."' id='call_detail_sort_".$column_name."' href='".$PHP_SELF."?sort_ivr_details=".($column_name==$sort_ivr_details_preg ? "$reverse_link" : "$column_name")."&page_no=".$page_no."#call_detail_sort_".$column_name."'>".$display_name.($column_name==$sort_ivr_details_preg ? " $sort_char" : "")."</a></th>";

		$HTML_output.="<th><input type='button' class='sort_button' value='".$display_name.($column_name==$sort_ivr_details_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_ivr_details').value='".($column_name==$sort_ivr_details_preg ? "$reverse_link" : "$column_name")."'; this.form.action+='#sid_anchor'; this.form.submit()\"></th>\n";

		if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
		}
	}
$CSV_output["ivr_details"].="\n";
$HTML_output.="<th>&nbsp;</th></tr>\n";

if ($sort_index)
	{
	foreach ($ivr_details as $sorting_array) {
        $sort_array_holder[] = $sorting_array["$sort_index"];
    }
	if (preg_match('/ desc$/', $sort_ivr_details))
		{
		array_multisort($sort_array_holder,SORT_DESC, SORT_STRING,$ivr_details);
		}
	else
		{
		array_multisort($sort_array_holder,SORT_ASC, SORT_STRING,$ivr_details);
		}
	}

foreach ($ivr_details as $uniqueid => $data_row)
	{
	$detail_id=$uniqueid;

	if (preg_match("/$ivr_choice/i", $data_row["ivr_path"])) # from search results
		{

		$HTML_output.="<tr class='standard_font_small'>";
		foreach ($data_row as $key => $value)
			{
			if ($key=="closecallid")
				{
				$detail_id.="|$value";
				}
			else 
				{
				if ($key=="date")
					{
					$myDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $value);
					$value = $myDateTime->format('m/d - H:i:s');
					}
				if ($key=="goal")
					{
					$value=$ingroup_names["$value"];
					}

				if ($key=="ivr_path")
					{
					$final_key_path="";
					$ivr_path_array=explode(">", $value);
					$ivr_array=explode(",", $ivr_path_array[0]);
					for($i=0; $i<count($ivr_array); $i++)
						{
						# if ($callmenu_names["$ivr_array[$i]"]) {$ivr_base=preg_replace("/$ivr_array[$i]/", $callmenu_names["$ivr_array[$i]"], $ivr_base);}
						$final_key_path.=$ivr_array[$i].($callmenu_names["$ivr_array[$i]"] ? " - ".$callmenu_names["$ivr_array[$i]"] : "")." => ";
						}
					$final_key_path=preg_replace('/ \=\> $/', "", $final_key_path)." > ".$ivr_path_array[1];
					$value=$final_key_path;
					}
			

				$HTML_output.="<td>".$value."</td>";
				$CSV_output["ivr_details"].="\"".$value."\",";
				}
			}
		$HTML_output.="<td><a onClick=\"ShowCallDetails('$detail_id', 'ivr')\"><svg width='20' height='20' viewBox='0 0 20 20' data-svg='search'><circle fill='none' stroke='#000' stroke-width='1.1' cx='9' cy='9' r='7'></circle><path fill='none' stroke='#000' stroke-width='1.1' d='M14,14 L18,18 L14,14 Z'></path></svg></a></td>";
		
		$CSV_output["ivr_details"].="\n";

		$HTML_output.="</tr>";

		if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
		}
	}
$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' align='left'><B><a class='header_link' href='".$PHP_SELF."?sort_ivr_details=".$sort_ivr_details."&page_no=1#page_anchor' alt='First page of results'>|<</a>&nbsp;&nbsp;&nbsp;&nbsp;<a class='header_link' href='".$PHP_SELF."?sort_ivr_details=".$sort_ivr_details."&page_no=".($page_no-1)."#page_anchor' alt='Prev page of results'><<</a></B></td>";
$HTML_output.="<td class='export_row_cell' align='left'>";
$HTML_output.="<input type='button' class='download_button' onClick=\"GoToDetailsPage('IVR', '1')\" title=\"First page\" value='|<'>&nbsp;&nbsp;&nbsp;&nbsp;";
$HTML_output.="<input type='button' class='download_button' onClick=\"GoToDetailsPage('IVR', '".($page_no-1)."')\" title=\"Prev page\" value='<<'>";
$HTML_output.="</td>";
# $HTML_output.="<td class='export_row_cell' align='center' colspan='5'>"._QXZ("Export as")."... &nbsp;&nbsp;&nbsp;&nbsp;<a  href=\"".$PHP_SELF."?download_rpt=ivr_details&sort_ivr_details=".$sort_ivr_details."\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a>&nbsp;&nbsp;&nbsp;&nbsp;"._QXZ("Current page").": $page_no / $total_pages</td>";
$HTML_output.="<td class='export_row_cell' align='center' colspan='5'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('IVR', 'ivr_details')\" title=\"Export as a CSV file\" value='CSV'>&nbsp;&nbsp;&nbsp;&nbsp;"._QXZ("Current page").": $page_no / $total_pages</td>";
# $HTML_output.="<td class='export_row_cell' align='right'><B><a class='header_link' href='".$PHP_SELF."?sort_ivr_details=".$sort_ivr_details."&page_no=".($page_no+1)."#page_anchor' alt='Next page of results'>>></a>&nbsp;&nbsp;&nbsp;&nbsp;<a class='header_link' href='".$PHP_SELF."?sort_ivr_details=".$sort_ivr_details."&page_no=".($total_pages-1)."#page_anchor' alt='Last page of results'>>|</a></B></td>";
$HTML_output.="<td class='export_row_cell' align='right'>";
$HTML_output.="<input type='button' class='download_button' onClick=\"GoToDetailsPage('IVR', '".($page_no<$total_pages ? $page_no+1 : $total_pages)."')\" title=\"Next page\" value='>>'>&nbsp;&nbsp;&nbsp;&nbsp;";
$HTML_output.="<input type='button' class='download_button' onClick=\"GoToDetailsPage('IVR', '".($total_pages)."')\" title=\"Last page\" value='>|'>";
$HTML_output.="</td>";

$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="<input type=hidden name=page_no id=page_no value='$page_no'>";
$HTML_output.="<input type='hidden' name='sort_ivr_details' id='sort_ivr_details' value='$sort_ivr_details'>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

if (is_array($ivr_survey_ingroups_detail) && count($ivr_survey_ingroups_detail)>0)
	{
	require("VERM_IVR_survey_details.inc");
	}

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

if ($download_rpt)
	{
	$data_to_download=$CSV_output["$download_rpt"];

	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "IVR_RPT_".$download_rpt."_".$FILE_TIME.".csv";
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
function GetHangups($call_uniqueid)
	{
	global $DB, $link;
	$hangups=array("AFTHRS", "NANQUE");
	$status="";

	$vcl_stmt="select status, campaign_id, queue_seconds from vicidial_closer_log where uniqueid='$call_uniqueid' order by call_date asc limit 1";
	$vcl_rslt=mysql_to_mysqli($vcl_stmt, $link);
	while ($vcl_row=mysqli_fetch_row($vcl_rslt))
		{
	#	if (!in_array("$vcl_row[0]", $hangups)) 
	#		{
			return array(0, $vcl_row[2]);
	#		}
		}
	return array(1, 0);
	}

function GetGoal($call_uniqueid)
	{
	global $DB, $link, $path_data, $ivr_details, $array_key;
	$vcl_stmt="select campaign_id, closecallid from vicidial_closer_log where uniqueid='$call_uniqueid' order by call_date asc limit 1";
	#print $vcl_stmt."\n";
	$vcl_rslt=mysql_to_mysqli($vcl_stmt, $link);
	while ($vcl_row=mysqli_fetch_row($vcl_rslt))
		{
		$ivr_details["$call_uniqueid"]["goal"]=$vcl_row[0];
		$ivr_details["$call_uniqueid"]["closecallid"]=$vcl_row[1];
		}
	}

function GetCallPath($call_uniqueid, $comment_a)
	{
	global $DB, $link, $path_data, $array_key;
	$vcl_stmt="select campaign_id from vicidial_closer_log where uniqueid='$call_uniqueid' order by call_date asc limit 1";
	#print $vcl_stmt."\n";
	$vcl_rslt=mysql_to_mysqli($vcl_stmt, $link);
	while ($vcl_row=mysqli_fetch_row($vcl_rslt))
		{
		$path_data["$vcl_row[0]"]["$array_key"]["calls"]++;
		# $ivr_details["$call_uniqueid"]["goal"]=$vcl_row[0];

		$time_stmt="select timestampdiff(SECOND, min(start_time), max(start_time)) from live_inbound_log where uniqueid='$call_uniqueid' and comment_a='$comment_a'";
		$time_rslt=mysql_to_mysqli($time_stmt, $link);
		if (mysqli_num_rows($time_rslt)>0)
			{
			$time_row=mysqli_fetch_row($time_rslt);
			$total_call_time=$time_row[0];

			$path_data["$vcl_row[0]"]["$array_key"]["time"]+=$total_call_time;
			
			if (!$path_data["$vcl_row[0]"]["$array_key"]["minimum_time"]) {$path_data["$vcl_row[0]"]["$array_key"]["minimum_time"]=86400;}
			if (!$path_data["$vcl_row[0]"]["$array_key"]["maximum_time"]) {$path_data["$vcl_row[0]"]["$array_key"]["maximum_time"]=0;}

			if ($path_data["$vcl_row[0]"]["$array_key"]["minimum_time"]>$total_call_time) {$path_data["$vcl_row[0]"]["$array_key"]["minimum_time"]=$total_call_time;}
			if ($path_data["$vcl_row[0]"]["$array_key"]["maximum_time"]<$total_call_time) {$path_data["$vcl_row[0]"]["$array_key"]["maximum_time"]=$total_call_time;}
			}
		}
	}

function GetDID($uniqueid)
	{
	global $link, $DB, $did_id_info, $did_pattern_info;

	$did_str="";

	$did_stmt="select extension, did_id from vicidial_did_log where uniqueid in ('$uniqueid')";
	$did_rslt=mysqli_query($link, $did_stmt);
	$did_row=mysqli_fetch_array($did_rslt);
	$did_str=$did_row["extension"]." - ".$did_id_info["$did_row[did_id]"];

	return $did_str;
	}

?>
