<?php
# VERM_OUTCOEMS_rpt.inc - Vicidial Enhanced Reporting call outcomes report
#
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGELOG:
# 220825-1617 - First build
# 230106-1500 - Added page flushing
# 230124-1218 - Updated LookupCall function, added unknown statuses option, outcome status/lagged status overrides
# 240801-1130 - Code updates for PHP8 compatibility
#

# NANQUE, not an unanswered call?
# TIMEOT, unanswered even though it was dropped into a call that was picked up
# Warm transfers count as two calls

# $PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
# $PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
# $PHP_SELF=$_SERVER['PHP_SELF'];
# $PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);

# if (isset($_GET["SUBMIT"]))			{$SUBMIT=$_GET["SUBMIT"];}
# 	elseif (isset($_POST["SUBMIT"]))	{$SUBMIT=$_POST["SUBMIT"];}
if (isset($_GET["sort_call_results"]))			{$sort_call_results=$_GET["sort_call_results"];}
	elseif (isset($_POST["sort_call_results"]))	{$sort_call_results=$_POST["sort_call_results"];}
if (isset($_GET["sort_billable_activities"]))			{$sort_billable_activities=$_GET["sort_billable_activities"];}
	elseif (isset($_POST["sort_billable_activities"]))	{$sort_billable_activities=$_POST["sort_billable_activities"];}
if (isset($_GET["sort_nonbillable_activities"]))		{$sort_nonbillable_activities=$_GET["sort_nonbillable_activities"];}
	elseif (isset($_POST["sort_nonbillable_activities"]))	{$sort_nonbillable_activities=$_POST["sort_nonbillable_activities"];}
if (isset($_GET["sort_outcomes_per_agent"]))			{$sort_outcomes_per_agent=$_GET["sort_outcomes_per_agent"];}
	elseif (isset($_POST["sort_outcomes_per_agent"]))	{$sort_outcomes_per_agent=$_POST["sort_outcomes_per_agent"];}

if (!$known_outcome_statuses) {$known_outcome_statues=array();}

if ($non_latin < 1)
	{
	$sort_call_results = preg_replace('/[^\s\-_0-9a-zA-Z]/','',$sort_call_results);
	$sort_billable_activities = preg_replace('/[^\s\-_0-9a-zA-Z]/','',$sort_billable_activities);
	$sort_nonbillable_activities = preg_replace('/[^\s\-_0-9a-zA-Z]/','',$sort_nonbillable_activities);
	$sort_outcomes_per_agent = preg_replace('/[^\s\-_0-9a-zA-Z]/','',$sort_outcomes_per_agent);
	$known_outcome_statuses = preg_replace('/[^- \.\,\_0-9a-zA-Z]/u','',$known_outcome_statuses);
	}
else
	{
	$sort_call_results = preg_replace('/[^\s\-_0-9\p{L}]/u','',$sort_call_results);
	$sort_billable_activities = preg_replace('/[^\s\-_0-9\p{L}]/u','',$sort_billable_activities);
	$sort_nonbillable_activities = preg_replace('/[^\s\-_0-9\p{L}]/u','',$sort_nonbillable_activities);
	$sort_outcomes_per_agent = preg_replace('/[^\s\-_0-9\p{L}]/u','',$sort_outcomes_per_agent);
	$known_outcome_statuses = preg_replace('/[^- \.\,\_0-9\p{L}]/u','',$known_outcome_statuses);
	}

### FUNCTION
function LookUpCall($uniqueid, $comments, $user)
	{
	global $link, $DB;

	$call_outcome="";

	if ($uniqueid && strlen($uniqueid)>0)
		{
		if ($comments=="INBOUND")
			{
			$table_name="vicidial_closer_log";
			}
		else
			{
			$table_name="vicidial_log";
			}

		$vl_stmt="select status from ".$table_name." where uniqueid='$uniqueid' and user='$user'";
		$vl_rslt=mysqli_query($link, $vl_stmt);
		if (mysqli_num_rows($vl_rslt)>0)
			{
			$vl_row=mysqli_fetch_row($vl_rslt);
			$call_outcome=$vl_row[0];
			}
		return $call_outcome;
		}
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
if ($DB) {$HTML_output.="<B>$vcl_uniqueid_stmt</B><BR>$included_campaigns_ct<BR>";}
# COVERS AGENT ANSWERED CALLS ONLY - NEED SEPARATE SECTION FOR NON-ANSWERS!!!!
# 1/23/2022- added status!='' call, meaning calls with no dispos don't get displayed
# Had to add this otherwise the counts go screwy

$outcomes_stmt_outb="";
if ($atomic_queue_campaigns_str!="")
	{
	$outcomes_stmt_outb="select *, if (sub_status in ('BLANK', '') or sub_status is null, '-', sub_status) as pause_code from vicidial_agent_log $vicidial_agent_log_SQL and comments!='INBOUND' "; #  and status!=''
	}

$outcomes_stmt_inb="";
if ($atomic_queue_ingroups_str!="")  #  && count($val_uniqueid_array)>0
	{
	$outcomes_stmt_inb="select *, if (sub_status in ('BLANK', '') or sub_status is null, '-', sub_status) as pause_code from vicidial_agent_log $vicidial_agent_log_UID_SQL and comments='INBOUND'"; 
	#  and uniqueid in ('".implode("', '", $val_uniqueid_array)."')
	}

$outcomes_stmt=$outcomes_stmt_outb.($outcomes_stmt_outb!="" && $outcomes_stmt_inb!="" ? " UNION " : "").$outcomes_stmt_inb;

/*
if (count($val_uniqueid_array)>0 && $atomic_queue_campaigns_str=="")  // No OUTBOUND campaigns in queue, INBOUND ONLY.  Need to account for this!
	{
	$outcomes_stmt.=" UNION select *, if (sub_status in ('BLANK', '') or sub_status is null, '-', sub_status) as pause_code from vicidial_agent_log $vicidial_agent_log_UID_SQL and uniqueid in ('".implode("', '", $val_uniqueid_array)."')";
	}
*/

# $HTML_output.="\n\n<!-- $outcomes_stmt //-->\n\n";

if ($DB) {$HTML_output.="Statuses:<B>$outcomes_stmt</B><BR>";}

$outcomes_array=array();
$call_results_array=array();
$call_types_array=array();
$billable_activities_array=array();
$nonbillable_activities_array=array();
$agent_details_array=array();
$outcomes_per_agent_array=array();
$call_results_total_calls=0;

if ($outcomes_stmt) 
	{
	$outcomes_rslt=mysql_to_mysqli($outcomes_stmt, $link);
	while ($outcomes_row=mysqli_fetch_array($outcomes_rslt))
		{
		$lead_id=$outcomes_row["lead_id"];
		$campaign_id=$outcomes_row["campaign_id"];
		$status=$outcomes_row["status"];
		$user=$outcomes_row["user"];
		$pause_code=$outcomes_row["pause_code"];
		$pause_sec=$outcomes_row["pause_sec"];
		$talk_sec=$outcomes_row["talk_sec"];
		$wait_sec=$outcomes_row["wait_sec"];
		$dispo_sec=$outcomes_row["dispo_sec"];
		$uniqueid=$outcomes_row["uniqueid"];
		$comments=$outcomes_row["comments"];
		$call_type="Unknown";
		$pause_name_key=$campaign_id."-".$pause_code;
		$pause_code_name=$pause_code;

		$full_name=$fullname_info["$user"];
		if (!$agent_details_array["$full_name"])
			{
			$agent_details_array["$full_name"]["available"]=0;
			$agent_details_array["$full_name"]["billable"]=0;
			$agent_details_array["$full_name"]["nonbillable"]=0;
			$agent_details_array["$full_name"]["sales"]=0;
			$agent_details_array["$full_name"]["human_answered"]=0;
			$agent_details_array["$full_name"]["customer_contacts"]=0;
			}
		

		$outcomes_array["total_billable_time"]+=($talk_sec+$wait_sec+$dispo_sec);
		$outcomes_array["total_agent_available_time"]+=($talk_sec+$wait_sec+$dispo_sec);

		$agent_details_array["$full_name"]["available"]+=($talk_sec+$wait_sec+$dispo_sec);

		# Billable/nonbillable activity
		if (in_array($pause_code, $billable_pause_codes["$campaign_id"]) || in_array($pause_code, $billable_pause_codes["SYSTEM"]))
			{
			if ($pause_code_names["$pause_name_key"])
				{
				$pause_code_name=$pause_code_names["$pause_name_key"];
				}

			$outcomes_array["total_billable_time"]+=$pause_sec;
			$outcomes_array["total_agent_billable_activities"]+=$pause_sec;

			if (!$billable_activities_array["$pause_code_name"])
				{
				$billable_activities_array["$pause_code_name"]["n_times"]=0;
				$billable_activities_array["$pause_code_name"]["total_time"]=0;
				$billable_activities_array["$pause_code_name"]["max"]=0;
				$billable_activities_array["$pause_code_name"]["min"]=100000;
				}	
			$billable_activities_array["$pause_code_name"]["n_times"]++;
			$billable_activities_array["$pause_code_name"]["total_time"]+=$pause_sec;
			if ($billable_activities_array["$pause_code_name"]["max"]<$pause_sec) {$billable_activities_array["$pause_code_name"]["max"]=$pause_sec;}
			if ($billable_activities_array["$pause_code_name"]["min"]>$pause_sec) {$billable_activities_array["$pause_code_name"]["min"]=$pause_sec;}

			$agent_details_array["$full_name"]["billable"]+=$pause_sec;
			}
		else
			{
			if ($pause_code_names["$pause_name_key"])
				{
				$pause_code_name=$pause_code_names["$pause_name_key"];
				}

			$outcomes_array["total_nonbillable_time"]+=$pause_sec;

			if (!$nonbillable_activities_array["$pause_code_name"])
				{
				$nonbillable_activities_array["$pause_code_name"]["n_times"]=0;
				$nonbillable_activities_array["$pause_code_name"]["total_time"]=0;
				$nonbillable_activities_array["$pause_code_name"]["max"]=0;
				$nonbillable_activities_array["$pause_code_name"]["min"]=100000;
				}
			
			$nonbillable_activities_array["$pause_code_name"]["n_times"]++;
			$nonbillable_activities_array["$pause_code_name"]["total_time"]+=$pause_sec;
			if ($nonbillable_activities_array["$pause_code_name"]["max"]<$pause_sec) {$nonbillable_activities_array["$pause_code_name"]["max"]=$pause_sec;}
			if ($nonbillable_activities_array["$pause_code_name"]["min"]>$pause_sec) {$nonbillable_activities_array["$pause_code_name"]["min"]=$pause_sec;}

			$agent_details_array["$full_name"]["nonbillable"]+=$pause_sec;
			}

		$call_outcome=$status;

		if (strlen($call_outcome)<=1)
			{
			$call_type="None";
			$status=LookUpCall($uniqueid, $comments, $user);
			# print "$status=LookUpCall($uniqueid, $comments);<BR>\n";
			$call_outcome=$status;
			}
	# JCJ 12/6/22 - added for overriding certain statuses 
		if (array_key_exists($status, $outcome_status_overrides))
			{
			$call_outcome=$outcome_status_overrides["$status"];
			$status=$call_outcome;
			}
	# JCJ 12/6/22 - added for removing certain statuses that should basically be lagged in certain conditions
	# 1/27/23 - changed $outcome_lagged_status_overrides to normal array instead of associative
		else if (in_array($status, $outcome_lagged_status_overrides) && strlen($uniqueid)<1)
			{
			$status="LAGGED";
			$call_outcome="";
			}
	/* JCJ 12/6/22 - commented out, may be useful later
	# 1/27/23 - changed $outcome_lagged_status_overrides to normal array instead of associative, below section won't work now
		else if (array_key_exists($status, $outcome_lagged_status_overrides))
			{
			# print_r($outcome_lagged_status_overrides);
			# $HTML_output.="<!-- \n*** $uniqueid, $call_outcome, $status\n";
			$status=LookUpCall($uniqueid, $comments, $user);
			if (in_array($status, $outcome_lagged_status_overrides["$call_outcome"]))
				{
				$status="LAGGED";
				$call_outcome="";
				}
			else
				{
				$status=$call_outcome;
				}
			# $HTML_output.="*** $uniqueid, $call_outcome, $status //-->\n";
			}
	*/

		if ($status_names["$status"] && !in_array($status, $unknown_network_statuses))
			{
			$call_outcome=$status_names["$status"];

			$call_type="Known";
			}
		else if (in_array($status, $unknown_network_statuses) || strlen($call_outcome)<=1)
			{
			# $call_outcome.=" (unknown)";
			$call_outcome="Network/LAGGED";
			}
		else
			{
			$call_outcome.=" (unknown)"; # commented out 10/13, uncommented 11/2 (known_outcome_stauses not working as they expect)
			}

		if (strlen($call_outcome)<=1)
			{
			$call_type="None";
			}

	/* Added 10/13, removed 11/2 - known_outcome_statuses not having desired effect
		if (in_array($status, $selectable_array["$campaign_id"]) || in_array($status, $selectable_array["SYSTEM"]) || in_array($status, $known_outcome_statuses))
			{
			$outcomes_array["selected"]++;

			$agent_details_array["$full_name"]["selected"]++;

			$call_type="Known";
			}
		else
			{
			$status="---";
			$call_type="System/Auto";
			$call_outcome="(unknown)";
			}
	*/

		# Human answered (contact)
		if (in_array($status, $human_ans_array["$campaign_id"]) || in_array($status, $human_ans_array["SYSTEM"]))
			{
			$outcomes_array["human_answered"]++;

			$agent_details_array["$full_name"]["human_answered"]++;

			$call_type="Human answered";
			}
		
		# Customer contact (qualified contact)
		if (in_array($status, $contact_array["$campaign_id"]) || in_array($status, $contact_array["SYSTEM"]))
			{
			$outcomes_array["customer_contacts"]++;

			$agent_details_array["$full_name"]["customer_contacts"]++;

			$call_type="Customer contact";
			}

		# Sales
		if (in_array($status, $sale_array["$campaign_id"]) || in_array($status, $sale_array["SYSTEM"]))
			{
			$outcomes_array["sales"]++;

			$agent_details_array["$full_name"]["sales"]++;

			$call_type="Sale";
			}
		
		if (!$call_types_array["$call_outcome"]) {$call_types_array["$call_outcome"]=$call_type;}

		# Only count results if this is an agent log record with a lead id, i.e. an actual call
		if (strlen($lead_id)>0)
			{
			$call_results_array["$call_outcome"]["total"]++;
			$call_results_total_calls++;

			if (in_array($status, $lost_statuses_array))
				{
				$call_results_array["$call_outcome"]["lost"]++;
				$call_results_array["$call_outcome"]["taken"]+=0;
				}
			else
				{
				$call_results_array["$call_outcome"]["lost"]+=0;
				$call_results_array["$call_outcome"]["taken"]++;
				}
			$call_results_array["$call_outcome"]["call_type"]=$call_type;

			# $outcomes_per_agent_array["$full_name"]["$call_type"]["$call_outcome"]["$status"]++; # 

			$opa_key="$full_name|$call_type|$call_outcome|$status";
			$outcomes_per_agent_array["$opa_key"]++;

			# $outcomes_per_agent_array_agents["$full_name"]++; # 
			# $outcomes_per_agent_array_types["$call_type"]++; # 
			# $outcomes_per_agent_array_outcomes["$call_outcome"]++; # 
			# $outcomes_per_agent_array_statuses["$full_name"]++; # 
			}
		}
	}

# print "\n\n<!-- $outcomes_stmt //-->\n\n";
# print_r($call_results_array); die;

# Get LOST calls here for $call_results_array values - query for VDAD and VDCL
/* COMMENTED OUT 2/17 - DOES NOTHING (missing "nousers" variables) AND ERRORS ANYWAY
$lost_stmt="select status, campaign_id, user from vicidial_log $vicidial_log_SQL_nousers and user='VDAD' $and_NANQUE_clause UNION ALL select status, campaign_id, user from vicidial_closer_log $vicidial_closer_log_SQL_nousers and user='VDCL' $and_NANQUE_clause";
if ($DB) {$HTML_output.="<B>Lost: $lost_stmt</B>";}
$lost_rslt=mysql_to_mysqli($lost_stmt, $link);
while ($lost_row=mysqli_fetch_row($lost_rslt))
	{
	$status=$lost_row[0];
	$campaign_id=$lost_row[1];
	# print "$status - $campaign_id<BR>\n";

	$call_outcome=$status;
	if ($status_names["$status"])
		{
		$call_outcome=$status_names["$status"];
		}
	else
		{
		$call_outcome=$status_names["$status"]." (unknown)";
# 		print "$status not in all statuses...<BR>\n";
		}

	switch ($status) 
		{
		case $status_names["$status"]:
			$call_type="Known";
			break;
		case (strlen($call_outcome)<=1):
			$call_type="None";
			break;
		case (in_array($status, $human_ans_array["$campaign_id"]) || in_array($status, $human_ans_array["SYSTEM"])):
			$call_type="Human answered";
			break;
		case (in_array($status, $contact_array["$campaign_id"]) || in_array($status, $contact_array["SYSTEM"])):
			$call_type="Customer contact";
			break;
		case (in_array($status, $sale_array["$campaign_id"]) || in_array($status, $sale_array["SYSTEM"])):
			$call_type="Sale";
			break;
		default:
			$call_type="Unknown";
		}
	
	if (!$call_results_array["$call_outcome"]["call_type"]) {$call_results_array["$call_outcome"]["call_type"]=$call_type;}

	$call_results_array["$call_outcome"]["total"]++;
	$call_results_total_calls++;
	$call_results_array["$call_outcome"]["taken"]+=0;
	$call_results_array["$call_outcome"]["lost"]++;
	}
# print_r($status_names);
*/

$max_call_results_total_pct=0;
$max_call_results_taken_pct=0;
foreach ($call_results_array as $outcome => $data)
	{
	$call_results_array["$outcome"]["total_pct"]=sprintf("%.1f", 100*(MathZDC($data["total"], $call_results_total_calls)));
	$call_results_array["$outcome"]["taken_pct"]=sprintf("%.1f", 100*(MathZDC($data["taken"], $call_results_total_calls)));
	if ($max_call_results_total_pct<$call_results_array["$outcome"]["total_pct"]) {$max_call_results_total_pct=$call_results_array["$outcome"]["total_pct"];}
	if ($max_call_results_taken_pct<$call_results_array["$outcome"]["taken_pct"]) {$max_call_results_taken_pct=$call_results_array["$outcome"]["taken_pct"];}
	}

$max_billable_activities_total_time=0;
$billable_activities_grand_total_time=0;
foreach ($billable_activities_array as $pause_code => $data)
	{
	$billable_activities_array["$pause_code"]["avg"]=round(MathZDC($data["total_time"], $data["n_times"]));
	if ($max_billable_activities_total_time<$data["total_time"]) {$max_billable_activities_total_time=$data["total_time"];}
	$billable_activities_grand_total_time+=$data["total_time"];
	}

$max_nonbillable_activities_total_time=0;
$nonbillable_activities_grand_total_time=0;
foreach ($nonbillable_activities_array as $pause_code => $data)
	{
	$nonbillable_activities_array["$pause_code"]["avg"]=round(MathZDC($data["total_time"], $data["n_times"]));
	if ($max_nonbillable_activities_total_time<$data["total_time"]) {$max_nonbillable_activities_total_time=$data["total_time"];}
	$nonbillable_activities_grand_total_time+=$data["total_time"];
	}

#### OUTCOMES ####
$sph=sprintf("%.1f", MathZDC($outcomes_array["sales"], ($outcomes_array["total_billable_time"] / 3600)));
$ccph=sprintf("%.1f", MathZDC($outcomes_array["customer_contacts"], ($outcomes_array["total_billable_time"] / 3600)));
$haph=sprintf("%.1f", MathZDC($outcomes_array["human_answered"], ($outcomes_array["total_billable_time"] / 3600)));
$ha_conv_index=sprintf("%.1f", 100*(MathZDC($outcomes_array["sales"], $outcomes_array["customer_contacts"])));
$cc_conv_index=sprintf("%.1f", 100*(MathZDC($outcomes_array["sales"], $outcomes_array["human_answered"])));

$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Statuses").":$NWB#VERM_OUTCOMES-outcomes$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr><th>"._QXZ("Report Details").":</th><td>&nbsp;</td></tr>";
$HTML_output.="<tr><td>"._QXZ("Total billable time").":</td><td>".floor($outcomes_array["total_billable_time"] / 3600).gmdate(":i:s", $outcomes_array["total_billable_time"] % 3600)."</td></tr>";
$HTML_output.="<tr><td>"._QXZ("Total agent available time").":</td><td>".floor($outcomes_array["total_agent_available_time"] / 3600).gmdate(":i:s", $outcomes_array["total_agent_available_time"] % 3600)."</td></tr>";
$HTML_output.="<tr><td>"._QXZ("Total agent billable activities").":</td><td>".floor($outcomes_array["total_agent_billable_activities"] / 3600).gmdate(":i:s", $outcomes_array["total_agent_billable_activities"] % 3600)."</td></tr>";
$HTML_output.="<tr><td>"._QXZ("Total nonbillable time").":</td><td>".floor($outcomes_array["total_nonbillable_time"] / 3600).gmdate(":i:s", $outcomes_array["total_nonbillable_time"] % 3600)."</td></tr>";
$HTML_output.="<tr><td>"._QXZ("Number of Sales").":</td><td>".$outcomes_array["sales"]."</td></tr>";
$HTML_output.="<tr><td>"._QXZ("Number of Customer Contacts").":</td><td>".$outcomes_array["customer_contacts"]."</td></tr>";
$HTML_output.="<tr><td>"._QXZ("Number of Human Answered").":</td><td>".$outcomes_array["human_answered"]."</td></tr>";
$HTML_output.="<tr><td>"._QXZ("Sales per Hour").":</td><td>".$sph."</td></tr>";
$HTML_output.="<tr><td>"._QXZ("Customer Contacts per Hour").":</td><td>".$ccph."</td></tr>";
$HTML_output.="<tr><td>"._QXZ("Human Answered per Hour").":</td><td>".$haph."</td></tr>";
$HTML_output.="<tr><td>"._QXZ("Conversion index").":</td><td>".$ha_conv_index." %</td></tr>";
$HTML_output.="<tr><td>"._QXZ("Contact conversion index").":</td><td>".$cc_conv_index." %</td></tr>";

$CSV_output["outcomes"]="\""._QXZ("Report Details").":\",\"\"\n";
$CSV_output["outcomes"].="\""._QXZ("Total billable time").":\",\"".floor($outcomes_array["total_billable_time"] / 3600).gmdate(":i:s", $outcomes_array["total_billable_time"] % 3600)."\"\n";
$CSV_output["outcomes"].="\""._QXZ("Total agent available time").":\",\"".floor($outcomes_array["total_agent_available_time"] / 3600).gmdate(":i:s", $outcomes_array["total_agent_available_time"] % 3600)."\"\n";
$CSV_output["outcomes"].="\""._QXZ("Total agent billable activities").":\",\"".floor($outcomes_array["total_agent_billable_activities"] / 3600).gmdate(":i:s", $outcomes_array["total_agent_billable_activities"] % 3600)."\"\n";
$CSV_output["outcomes"].="\""._QXZ("Total nonbillable time").":\",\"".floor($outcomes_array["total_nonbillable_time"] / 3600).gmdate(":i:s", $outcomes_array["total_nonbillable_time"] % 3600)."\"\n";
$CSV_output["outcomes"].="\""._QXZ("Number of Sales").":\",\"".$outcomes_array["sales"]."\"\n";
$CSV_output["outcomes"].="\""._QXZ("Number of Customer Contacts").":\",\"".$outcomes_array["customer_contacts"]."\"\n";
$CSV_output["outcomes"].="\""._QXZ("Number of Human Answered").":\",\"".$outcomes_array["human_answered"]."\"\n";
$CSV_output["outcomes"].="\""._QXZ("Sales per Hour").":\",\"$sph\"\n";
$CSV_output["outcomes"].="\""._QXZ("Customer Contacts per Hour").":\",\"$ccph\"\n";
$CSV_output["outcomes"].="\""._QXZ("Human Answered per Hour").":\",\"$haph\"\n";
$CSV_output["outcomes"].="\""._QXZ("Conversion index").":\",\"".$ha_conv_index." %\"\n";
$CSV_output["outcomes"].="\""._QXZ("Contact conversion index").":\",\"".$cc_conv_index." %\"\n";

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td colspan='2' class='export_row_cell'>"._QXZ("Export as")."...<a href=\"".$PHP_SELF."?download_rpt=outcomes\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td class='export_row_cell' colspan='2'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('OUTCOMES', 'outcomes')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
##################

#### CALL RESULTS OUTCOMES ####

#### SORT RESULTS OF MULTI-DIMENSIONAL ARRAY ###
#default sort by key, always do this so the subsort is by array key
ksort($call_results_array);

$sort_cr_index=preg_replace('/ desc/', '', $sort_call_results);
if (preg_match('/ desc$/', $sort_call_results)) 
	{
	$sort_char="&#8595;"; 
	$reverse_link=preg_replace('/ desc$/', '', $sort_call_results);
	} 
else 
	{
	$sort_char="&#8593;"; 
	$reverse_link=$sort_call_results." desc";
	}
$sort_call_results_preg=preg_replace('/ desc$/', '', $sort_call_results);

if ($sort_call_results=="outcomes desc")
	{
	krsort($call_results_array);
	}
else if(!preg_match('/^outcomes/', $sort_call_results))
	{
	if ($sort_cr_index)
		{
		$col = array_column( $call_results_array, "$sort_cr_index" );
		if (preg_match('/ desc$/', $sort_call_results))
			{
			array_multisort( $col, SORT_DESC, $call_results_array );
			}
		else
			{
			array_multisort( $col, SORT_ASC, $call_results_array );
			}
		}
	}

$HTML_output.="<BR><h2 class='rpt_header' id='scr_anchor'>"._QXZ("Call results, by statuses").":$NWB#VERM_OUTCOMES-call_results_by_outcomes$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
/*
$HTML_output.="<th><a class='header_link' name='call_results_call_outcome' id='call_results_call_outcome' href='".$PHP_SELF."?sort_call_results=".("outcomes"==$sort_call_results_preg ? "$reverse_link" : "outcomes")."#call_results_call_outcome'>Call outcome".("outcomes"==$sort_call_results_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='call_results_type' id='call_results_type' href='".$PHP_SELF."?sort_call_results=".("call_type"==$sort_call_results_preg ? "$reverse_link" : "call_type")."#call_results_type'>Type".("call_type"==$sort_call_results_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='call_results_total' id='call_results_total' href='".$PHP_SELF."?sort_call_results=".("total"==$sort_call_results_preg ? "$reverse_link" : "total")."#call_results_total'>Total".("total"==$sort_call_results_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='call_results_taken' id='call_results_taken' href='".$PHP_SELF."?sort_call_results=".("taken"==$sort_call_results_preg ? "$reverse_link" : "taken")."#call_results_taken'>Taken".("taken"==$sort_call_results_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='call_results_lost' id='call_results_lost' href='".$PHP_SELF."?sort_call_results=".("lost"==$sort_call_results_preg ? "$reverse_link" : "lost")."#call_results_lost'>Lost".("lost"==$sort_call_results_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='call_results_pct' id='call_results_pct' href='".$PHP_SELF."?sort_call_results=".("total_pct"==$sort_call_results_preg ? "$reverse_link" : "total_pct")."#call_results_pct'>%".("total_pct"==$sort_call_results_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th><a class='header_link' name='call_results_pct_taken' id='call_results_pct_taken' href='".$PHP_SELF."?sort_call_results=".("taken_pct"==$sort_call_results_preg ? "$reverse_link" : "taken_pct")."#call_results_pct_taken'>% taken".("taken_pct"==$sort_call_results_preg ? " $sort_char" : "")."</a></th>";
*/

	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Call status").("outcomes"==$sort_call_results_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_call_results').value='".("outcomes"==$sort_call_results_preg ? "$reverse_link" : "outcomes")."'; this.form.action+='#scr_anchor'; this.form.submit()\"></th>\n";
	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Type").("call_type"==$sort_call_results_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_call_results').value='".("call_type"==$sort_call_results_preg ? "$reverse_link" : "call_type")."'; this.form.action+='#scr_anchor'; this.form.submit()\"></th>\n";
	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Total").("total"==$sort_call_results_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_call_results').value='".("total"==$sort_call_results_preg ? "$reverse_link" : "total")."'; this.form.action+='#scr_anchor'; this.form.submit()\"></th>\n";
	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Taken").("taken"==$sort_call_results_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_call_results').value='".("taken"==$sort_call_results_preg ? "$reverse_link" : "taken")."'; this.form.action+='#scr_anchor'; this.form.submit()\"></th>\n";
	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Lost").("lost"==$sort_call_results_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_call_results').value='".("lost"==$sort_call_results_preg ? "$reverse_link" : "lost")."'; this.form.action+='#scr_anchor'; this.form.submit()\"></th>\n";
	$HTML_output.="<th><input type='button' class='sort_button' value='%".("total_pct"==$sort_call_results_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_call_results').value='".("total_pct"==$sort_call_results_preg ? "$reverse_link" : "total_pct")."'; this.form.action+='#scr_anchor'; this.form.submit()\"></th>\n";
	$HTML_output.="<th>&nbsp;</th>";
	$HTML_output.="<th><input type='button' class='sort_button' value='% "._QXZ("taken").("taken_pct"==$sort_call_results_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_call_results').value='".("taken_pct"==$sort_call_results_preg ? "$reverse_link" : "taken_pct")."'; this.form.action+='#scr_anchor'; this.form.submit()\"></th>\n";


$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="</tr>\n";

$CSV_output["call_results"]="\""._QXZ("Call status")."\",\""._QXZ("Type")."\",\""._QXZ("Total")."\",\""._QXZ("Taken")."\",\""._QXZ("Lost")."\",\"%\",\"\",\"% "._QXZ("taken")."\"\n";

foreach ($call_results_array as $outcome => $data)
	{
	$dispo=array_search("$outcome", $status_names);
	$HTML_output.="<tr class='standard_font bold'>";
	$HTML_output.="<td>".$outcome."</td>";
	$HTML_output.="<td>".$data["call_type"]."</td>";
	$HTML_output.="<td>".$data["total"]."</td>";
	$HTML_output.="<td>".$data["taken"]."</td>";
	$HTML_output.="<td>".$data["lost"]."</td>";
	$HTML_output.="<td>".$data["total_pct"]." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(MathZDC((100*$data["total_pct"]), $max_call_results_total_pct))."%'></td>";
	$HTML_output.="<td>".$data["taken_pct"]." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(MathZDC((100*$data["taken_pct"]), $max_call_results_taken_pct))."%'></td>";
	$HTML_output.="<td><a onClick=\"ShowOutcomesDetails('', '$start_date $start_time', '$end_date $end_time', '', '', '".trim(preg_replace("/\(unknown\)/", "", $outcome))."')\"><svg width='20' height='20' viewBox='0 0 20 20' data-svg='search'><circle fill='none' stroke='#000' stroke-width='1.1' cx='9' cy='9' r='7'></circle><path fill='none' stroke='#000' stroke-width='1.1' d='M14,14 L18,18 L14,14 Z'></path></svg></a></td>";
	$HTML_output.="</tr>\n";

	$CSV_output["call_results"].="\"$outcome\",\"$data[call_type]\",\"$data[total]\",\"$data[taken]\",\"$data[lost]\",\"$data[total_pct] %\",\"\",\"$data[taken_pct] %\",\n";
	}

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td colspan='10' class='export_row_cell'>"._QXZ("Export as")."...<a href=\"".$PHP_SELF."?download_rpt=call_results&sort_call_results=".$sort_call_results."\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td class='export_row_cell' colspan='10'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('OUTCOMES', 'call_results')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="<input type='hidden' name='sort_call_results' id='sort_call_results' value='$sort_call_results'>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
###############################

#### BILLABLE ACTIVITIES   ####
ksort($billable_activities_array);

$sort_ba_index=preg_replace('/ desc/', '', $sort_billable_activities);
if (preg_match('/ desc$/', $sort_billable_activities)) 
	{
	$sort_char="&#8595;"; 
	$reverse_link=preg_replace('/ desc$/', '', $sort_billable_activities);
	} 
else 
	{
	$sort_char="&#8593;"; 
	$reverse_link=$sort_billable_activities." desc";
	}
$sort_billable_activities_preg=preg_replace('/ desc$/', '', $sort_billable_activities);

if ($sort_billable_activities=="activity desc")
	{
	krsort($billable_activities_array);
	}
else if (!preg_match('/^activity/', $sort_billable_activities))
	{
	if ($sort_ba_index)
		{
		$col = array_column( $billable_activities_array, "$sort_ba_index" );
		if (preg_match('/ desc$/', $sort_billable_activities))
			{
			array_multisort( $col, SORT_DESC, $billable_activities_array );
			}
		else
			{
			array_multisort( $col, SORT_ASC, $billable_activities_array );
			}
		}
	}


$HTML_output.="<BR><h2 class='rpt_header' id='sba_anchor'>"._QXZ("Billable activities").":$NWB#VERM_OUTCOMES-billable_activities$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
/*
$HTML_output.="<th><a class='header_link' name='billable_activities_call_activity' id='billable_activities_call_activity' href='".$PHP_SELF."?sort_billable_activities=".("activity"==$sort_billable_activities_preg ? "$reverse_link" : "activity")."#billable_activities_call_activity'>Activity".("activity"==$sort_billable_activities_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='billable_activities_ntimes' id='billable_activities_ntimes' href='".$PHP_SELF."?sort_billable_activities=".("n_times"==$sort_billable_activities_preg ? "$reverse_link" : "n_times")."#billable_activities_ntimes'>N. Times".("n_times"==$sort_billable_activities_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='billable_activities_total_time' id='billable_activities_total_time' href='".$PHP_SELF."?sort_billable_activities=".("total_time"==$sort_billable_activities_preg ? "$reverse_link" : "total_time")."#billable_activities_total_time'>Tot. Time".("total_time"==$sort_billable_activities_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='billable_activities_avg' id='billable_activities_avg' href='".$PHP_SELF."?sort_billable_activities=".("avg"==$sort_billable_activities_preg ? "$reverse_link" : "avg")."#billable_activities_avg'>Avg.".("avg"==$sort_billable_activities_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='billable_activities_min' id='billable_activities_min' href='".$PHP_SELF."?sort_billable_activities=".("min"==$sort_billable_activities_preg ? "$reverse_link" : "min")."#billable_activities_min'>Min.".("min"==$sort_billable_activities_preg ? " $sort_char" : "")."</a></th>";
*/

	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Activity").("activity"==$sort_billable_activities_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_billable_activities').value='".("activity"==$sort_billable_activities_preg ? "$reverse_link" : "activity")."'; this.form.action+='#sba_anchor'; this.form.submit()\"></th>\n";
	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("N. Times").("n_times"==$sort_billable_activities_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_billable_activities').value='".("n_times"==$sort_billable_activities_preg ? "$reverse_link" : "n_times")."'; this.form.action+='#sba_anchor'; this.form.submit()\"></th>\n";
	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Tot. Time").("total_time"==$sort_billable_activities_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_billable_activities').value='".("total_time"==$sort_billable_activities_preg ? "$reverse_link" : "total_time")."'; this.form.action+='#sba_anchor'; this.form.submit()\"></th>\n";
	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Avg.").("avg"==$sort_billable_activities_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_billable_activities').value='".("avg"==$sort_billable_activities_preg ? "$reverse_link" : "avg")."'; this.form.action+='#sba_anchor'; this.form.submit()\"></th>\n";
	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Min.").("min"==$sort_billable_activities_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_billable_activities').value='".("min"==$sort_billable_activities_preg ? "$reverse_link" : "min")."'; this.form.action+='#sba_anchor'; this.form.submit()\"></th>\n";

$HTML_output.="<th>"._QXZ("Max.")."</a></th>";
$HTML_output.="<th>%</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="</tr>\n";

$CSV_output["billable_activities"]="\""._QXZ("Activity")."\",\""._QXZ("N. Times")."\",\""._QXZ("Tot. Time")."\",\""._QXZ("Avg.")."\",\""._QXZ("Min.")."\",\""._QXZ("Max.")."\",\"%\"\n";

foreach ($billable_activities_array as $pause_code => $data)
	{
	$pct=sprintf("%.1f", (MathZDC((100*$data["total_time"]), $billable_activities_grand_total_time)));

	$HTML_output.="<tr class='standard_font bold'>";
	$HTML_output.="<td>".$pause_code."</td>";
	$HTML_output.="<td>".$data["n_times"]."</td>";
	$HTML_output.="<td>".($data["total_time"]>=3600 ? floor($data["total_time"]/3600).":" : "").gmdate("i:s", $data["total_time"])."</td>";
	$HTML_output.="<td>".($data["avg"]>=3600 ? floor($data["avg"]/3600).":" : "").gmdate("i:s", $data["avg"])."</td>";
	$HTML_output.="<td>".($data["min"]>=3600 ? floor($data["min"]/3600).":" : "").gmdate("i:s", $data["min"])."</td>";
	$HTML_output.="<td>".($data["max"]>=3600 ? floor($data["max"]/3600).":" : "").gmdate("i:s", $data["max"])."</td>";
	$HTML_output.="<td>".$pct." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".$pct."%'></td>";
	$HTML_output.="</tr>\n";

	$CSV_output["billable_activities"].="\"$pause_code\",\"$data[n_times]\",\"".($data["total_time"]>=3600 ? floor($data["total_time"]/3600).":" : "").gmdate("i:s", $data["total_time"])."\",\"".($data["avg"]>=3600 ? floor($data["avg"]/3600).":" : "").gmdate("i:s", $data["avg"])."\",\"".($data["min"]>=3600 ? floor($data["min"]/3600).":" : "").gmdate("i:s", $data["min"])."\",\"".($data["max"]>=3600 ? floor($data["max"]/3600).":" : "").gmdate("i:s", $data["max"])."\",\"$pct %\"\n";
	}

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td colspan='10' class='export_row_cell'>"._QXZ("Export as")."...<a href=\"".$PHP_SELF."?download_rpt=billable_activities&sort_billable_activities=".$sort_billable_activities."\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td class='export_row_cell' colspan='10'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('OUTCOMES', 'billable_activities')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="<input type='hidden' name='sort_billable_activities' id='sort_billable_activities' value='$sort_billable_activities'>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
###############################

#### NONBILLABLE ACTIVITIES ####
ksort($nonbillable_activities_array);

$sort_nba_index=preg_replace('/ desc/', '', $sort_nonbillable_activities);
if (preg_match('/ desc$/', $sort_nonbillable_activities)) 
	{
	$sort_char="&#8595;"; 
	$reverse_link=preg_replace('/ desc$/', '', $sort_nonbillable_activities);
	} 
else 
	{
	$sort_char="&#8593;"; 
	$reverse_link=$sort_nonbillable_activities." desc";
	}
$sort_nonbillable_activities_preg=preg_replace('/ desc$/', '', $sort_nonbillable_activities);

if ($sort_nonbillable_activities=="activity desc")
	{
	krsort($nonbillable_activities_array);
	}
else if (!preg_match('/^activity/', $sort_nonbillable_activities))
	{
	if ($sort_nba_index)
		{
		$col = array_column( $nonbillable_activities_array, "$sort_nba_index" );
		if (preg_match('/ desc$/', $sort_nonbillable_activities))
			{
			array_multisort( $col, SORT_DESC, SORT_STRING, $nonbillable_activities_array );
			}
		else
			{
			array_multisort( $col, SORT_ASC, SORT_STRING, $nonbillable_activities_array );
			}
		}
	}

$HTML_output.="<BR><h2 class='rpt_header' id='sna_anchor'>"._QXZ("Nonbillable activities").":$NWB#VERM_OUTCOMES-nonbillable_activities$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
/*
$HTML_output.="<th><a class='header_link' name='nonbillable_activity' id='nonbillable_activity' href='".$PHP_SELF."?sort_nonbillable_activities=".("activity"==$sort_nonbillable_activities_preg ? "$reverse_link" : "activity")."#nonbillable_activity'>Activity".("activity"==$sort_nonbillable_activities_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='nonbillable_activities_ntimes' id='nonbillable_activities_ntimes' href='".$PHP_SELF."?sort_nonbillable_activities=".("n_times"==$sort_nonbillable_activities_preg ? "$reverse_link" : "n_times")."#nonbillable_activities_ntimes'>N. Times".("n_times"==$sort_nonbillable_activities_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='nonbillable_activities_total_time' id='nonbillable_activities_total_time' href='".$PHP_SELF."?sort_nonbillable_activities=".("total_time"==$sort_nonbillable_activities_preg ? "$reverse_link" : "total_time")."#nonbillable_activities_total_time'>Tot. Time".("total_time"==$sort_nonbillable_activities_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='nonbillable_activities_avg' id='nonbillable_activities_avg' href='".$PHP_SELF."?sort_nonbillable_activities=".("avg"==$sort_nonbillable_activities_preg ? "$reverse_link" : "avg")."#nonbillable_activities_avg'>Avg.".("avg"==$sort_nonbillable_activities_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='nonbillable_activities_min' id='nonbillable_activities_min' href='".$PHP_SELF."?sort_nonbillable_activities=".("min"==$sort_nonbillable_activities_preg ? "$reverse_link" : "min")."#nonbillable_activities_min'>Min.".("min"==$sort_nonbillable_activities_preg ? " $sort_char" : "")."</a></th>";
*/

	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Activity").("activity"==$sort_nonbillable_activities_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_nonbillable_activities').value='".("activity"==$sort_nonbillable_activities_preg ? "$reverse_link" : "activity")."'; this.form.action+='#sna_anchor'; this.form.submit()\"></th>\n";
	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("N. Times").("n_times"==$sort_nonbillable_activities_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_nonbillable_activities').value='".("n_times"==$sort_nonbillable_activities_preg ? "$reverse_link" : "n_times")."'; this.form.action+='#sna_anchor'; this.form.submit()\"></th>\n";
	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Tot. Time").("total_time"==$sort_nonbillable_activities_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_nonbillable_activities').value='".("total_time"==$sort_nonbillable_activities_preg ? "$reverse_link" : "total_time")."'; this.form.action+='#sna_anchor'; this.form.submit()\"></th>\n";
	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Avg.").("avg"==$sort_nonbillable_activities_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_nonbillable_activities').value='".("avg"==$sort_nonbillable_activities_preg ? "$reverse_link" : "avg")."'; this.form.action+='#sna_anchor'; this.form.submit()\"></th>\n";
	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Min.").("min"==$sort_nonbillable_activities_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_nonbillable_activities').value='".("min"==$sort_nonbillable_activities_preg ? "$reverse_link" : "min")."'; this.form.action+='#sna_anchor'; this.form.submit()\"></th>\n";

$HTML_output.="<th>"._QXZ("Max.")."</a></th>";
$HTML_output.="<th>%</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="</tr>\n";

$CSV_output["nonbillable_activities"]="\""._QXZ("Activity")."\",\""._QXZ("N. Times")."\",\""._QXZ("Tot. Time")."\",\""._QXZ("Avg.")."\",\""._QXZ("Min.")."\",\""._QXZ("Max.")."\",\"%\"\n";

foreach ($nonbillable_activities_array as $pause_code => $data)
	{
	$pct=sprintf("%.1f", (MathZDC((100*$data["total_time"]), $nonbillable_activities_grand_total_time)));

	$HTML_output.="<tr class='standard_font bold'>";
	$HTML_output.="<td>".$pause_code."</td>";
	$HTML_output.="<td>".$data["n_times"]."</td>";
	$HTML_output.="<td>".($data["total_time"]>=3600 ? floor($data["total_time"]/3600).":" : "").gmdate("i:s", $data["total_time"])."</td>";
	$HTML_output.="<td>".($data["avg"]>=3600 ? floor($data["avg"]/3600).":" : "").gmdate("i:s", $data["avg"])."</td>";
	$HTML_output.="<td>".($data["min"]>=3600 ? floor($data["min"]/3600).":" : "").gmdate("i:s", $data["min"])."</td>";
	$HTML_output.="<td>".($data["max"]>=3600 ? floor($data["max"]/3600).":" : "").gmdate("i:s", $data["max"])."</td>";
	$HTML_output.="<td>".$pct." %</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".$pct."%'></td>";
	$HTML_output.="</tr>\n";

	$CSV_output["nonbillable_activities"].="\"$pause_code\",\"$data[n_times]\",\"".($data["total_time"]>=3600 ? floor($data["total_time"]/3600).":" : "").gmdate("i:s", $data["total_time"])."\",\"".($data["avg"]>=3600 ? floor($data["avg"]/3600).":" : "").gmdate("i:s", $data["avg"])."\",\"".($data["min"]>=3600 ? floor($data["min"]/3600).":" : "").gmdate("i:s", $data["min"])."\",\"".($data["max"]>=3600 ? floor($data["max"]/3600).":" : "").gmdate("i:s", $data["max"])."\",\"$pct %\"\n";
	}

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td colspan='10' class='export_row_cell'>"._QXZ("Export as")."...<a href=\"".$PHP_SELF."?download_rpt=nonbillable_activities&sort_nonbillable_activities=".$sort_nonbillable_activities."\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td class='export_row_cell' colspan='10'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('OUTCOMES', 'nonbillable_activities')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="<input type='hidden' name='sort_nonbillable_activities' id='sort_nonbillable_activities' value='$sort_nonbillable_activities'>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
###############################

#### AGENT DETAILS REPORT #####
$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("Detailed agent report").":$NWB#VERM_OUTCOMES-detailed_agent_report$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<th>"._QXZ("Agent name")."</th>";
$HTML_output.="<th>"._QXZ("Avail.")."</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>"._QXZ("Bill.")."</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>"._QXZ("Nonb.")."</th>";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="<th>"._QXZ("Sales")."</th>";
$HTML_output.="<th>"._QXZ("Cust Contact")."</th>";
$HTML_output.="<th>"._QXZ("Human Ans.")."</th>";
$HTML_output.="<th>"._QXZ("SPH")."</th>";
$HTML_output.="<th>"._QXZ("CCPH")."</th>";
$HTML_output.="<th>"._QXZ("HAPH")."</th>";
$HTML_output.="<th>"._QXZ("HA CONV")."</th>";
$HTML_output.="<th>"._QXZ("CC CONV")."</th>";
$HTML_output.="</tr>\n";

$CSV_output["agent_details"]="\""._QXZ("Agent name")."\",\""._QXZ("Avail.")."\",\" \",\""._QXZ("Bill.")."\",\" \",\""._QXZ("NonB.")."\",\" \",\""._QXZ("Sales")."\",\""._QXZ("Cust Contact")."\",\""._QXZ("Human Ans.")."\",\""._QXZ("SPH")."\",\""._QXZ("CCPH")."\",\""._QXZ("HAPH")."\",\""._QXZ("HA CONV")."\",\""._QXZ("CC CONV")."\"\n";

ksort($agent_details_array);
# print_r($agent_details_array);
foreach($agent_details_array as $agent_name => $data)
	{
	$billable_seconds=($data["available"]+$data["billable"]);
	$billable_hours=$billable_seconds/3600;

	$av_pct=sprintf("%.1f", (100*(MathZDC($data["available"], $billable_seconds))));
	$b_pct=sprintf("%.1f", (100*(MathZDC($data["billable"], $billable_seconds))));
	# ??? - Not sure of the logic below, but whatever.
	$ha_conv_pct=sprintf("%.1f", (100*MathZDC($data["sales"], $data["human_answered"], 0)));
	$cc_conv_pct=sprintf("%.1f", (100*MathZDC($data["sales"], $data["customer_contacts"], 0)));

	$nb_pct=sprintf("%.1f", (100*(MathZDC($data["nonbillable"], $billable_seconds))));
	$sph=sprintf("%.1f", (MathZDC($data["sales"], $billable_hours)));
	$ccph=sprintf("%.1f", (MathZDC($data["customer_contacts"], $billable_hours)));
	$haph=sprintf("%.1f", (MathZDC($data["human_answered"], $billable_hours)));

	$HTML_output.="<tr class='standard_font bold'>";
	$HTML_output.="<td>".$agent_name."</td>";
	$HTML_output.="<td>".($data["available"]>=3600 ? floor($data["available"]/3600).":" : "").gmdate("i:s", $data["available"])."</td>";
	$HTML_output.="<td>".$av_pct." %</td>";
	$HTML_output.="<td>".($data["billable"]>=3600 ? floor($data["billable"]/3600).":" : "").gmdate("i:s", $data["billable"])."</td>";
	$HTML_output.="<td>".$b_pct." %</td>";
	$HTML_output.="<td>".($data["nonbillable"]>=3600 ? floor($data["nonbillable"]/3600).":" : "").gmdate("i:s", $data["nonbillable"])."</td>";
	$HTML_output.="<td>".$nb_pct." %</td>";
	$HTML_output.="<td>".$data["sales"]."</td>";
	$HTML_output.="<td>".$data["customer_contacts"]."</td>";
	$HTML_output.="<td>".$data["human_answered"]."</td>";
	$HTML_output.="<td>".$sph."</td>";
	$HTML_output.="<td>".$ccph."</td>";
	$HTML_output.="<td>".$haph."</td>";
	$HTML_output.="<td>".$ha_conv_pct." %</td>";
	$HTML_output.="<td>".$cc_conv_pct." %</td>";
	$HTML_output.="</tr>\n";

	$CSV_output["agent_details"].="\"$agent_name\",\"".($data["available"]>=3600 ? floor($data["available"]/3600).":" : "").gmdate("i:s", $data["available"])."\",\"$av_pct %\",\"".($data["billable"]>=3600 ? floor($data["billable"]/3600).":" : "").gmdate("i:s", $data["billable"])."\",\"$b_pct %\",\"".($data["nonbillable"]>=3600 ? floor($data["nonbillable"]/3600).":" : "").gmdate("i:s", $data["nonbillable"])."\",\"$nb_pct %\",\"$data[sales]\",\"$data[customer_contacts]\",\"$data[human_answered]\",\"$sph\",\"$ccph\",\"$haph\",\"$ha_conv_pct %\",\"$cc_conv_pct %\"\n";
	}

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td colspan='15' class='export_row_cell'>"._QXZ("Export as")."...<a href=\"".$PHP_SELF."?download_rpt=agent_details\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td class='export_row_cell' colspan='15'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('OUTCOMES', 'agent_details')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";

if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
###############################

#### AGENT OUTCOMES REPORT ####
$final_opa_array=array();
$max_total_value=0;
foreach($outcomes_per_agent_array as $key => $value)
	{
	if ($value>$max_total_value) {$max_total_value=$value;}

	$new_array=explode("|", $key);
	$new_assoc_array=array(
		"agent" => $new_array[0],
		"type" => $new_array[1],
		"outcome" => $new_array[2],
		"status" => $new_array[3],
		"total" => $value
		);
	# array_push($new_array, $value);
	array_push($final_opa_array, $new_assoc_array);
	}

$sort_opa_index=preg_replace('/ desc/', '', $sort_outcomes_per_agent);
if (preg_match('/ desc$/', $sort_outcomes_per_agent)) 
	{
	$sort_char="&#8595;"; 
	$reverse_link=preg_replace('/ desc$/', '', $sort_outcomes_per_agent);
	} 
else 
	{
	$sort_char="&#8593;"; 
	$reverse_link=$sort_outcomes_per_agent." desc";
	}
$sort_outcomes_per_agent_preg=preg_replace('/ desc$/', '', $sort_outcomes_per_agent);

if (!$sort_opa_index) {$sort_opa_index="agent";}

# Default sort
$col = array_column( $final_opa_array, "agent" );
array_multisort( $col, SORT_ASC, $final_opa_array );

$col = array_column( $final_opa_array, "$sort_opa_index" );
if (preg_match('/ desc$/', $sort_outcomes_per_agent))
	{
	if ($sort_opa_index=="total")
		{
		array_multisort( $col, SORT_DESC, $final_opa_array );
		}
	else
		{
		array_multisort( $col, SORT_DESC, SORT_STRING, $final_opa_array );
		}
	}
else
	{
	if ($sort_opa_index=="total")
		{
		array_multisort( $col, SORT_ASC, $final_opa_array );
		}
	else
		{
		array_multisort( $col, SORT_ASC, SORT_STRING, $final_opa_array );
		}
	}

$HTML_output.="<BR><h2 class='rpt_header' id='sopa_anchor'>"._QXZ("Statuses per agent").":$NWB#VERM_OUTCOMES-outcomes_per_agent$NWE</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='rpt_table'>";
$HTML_output.="<tr class='standard_font bold'>";
/*
$HTML_output.="<th><a class='header_link' name='outcomes_per_agent' id='outcomes_per_agent' href='".$PHP_SELF."?sort_outcomes_per_agent=".("agent"==$sort_outcomes_per_agent_preg ? "$reverse_link" : "agent")."#outcomes_per_agent'>"._QXZ("Agent Name").("agent"==$sort_outcomes_per_agent_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='outcomes_per_agent_statuses' id='outcomes_per_agent_statuses' href='".$PHP_SELF."?sort_outcomes_per_agent=".("status"==$sort_outcomes_per_agent_preg ? "$reverse_link" : "status")."#outcomes_per_agent_statuses'>"._QXZ("Status").("status"==$sort_outcomes_per_agent_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='outcomes_per_agent_outcome' id='outcomes_per_agent_outcome' href='".$PHP_SELF."?sort_outcomes_per_agent=".("outcome"==$sort_outcomes_per_agent_preg ? "$reverse_link" : "outcome")."#outcomes_per_agent_outcome'>"._QXZ("Status Name").("outcome"==$sort_outcomes_per_agent_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='outcomes_per_agent_type' id='outcomes_per_agent_type' href='".$PHP_SELF."?sort_outcomes_per_agent=".("type"==$sort_outcomes_per_agent_preg ? "$reverse_link" : "type")."#outcomes_per_agent_type'>"._QXZ("Type").("type"==$sort_outcomes_per_agent_preg ? " $sort_char" : "")."</a></th>";
$HTML_output.="<th><a class='header_link' name='outcomes_per_agent_total' id='outcomes_per_agent_total' href='".$PHP_SELF."?sort_outcomes_per_agent=".("total"==$sort_outcomes_per_agent_preg ? "$reverse_link" : "total")."#outcomes_per_agent_total'>"._QXZ("Total").("total"==$sort_outcomes_per_agent_preg ? " $sort_char" : "")."</a></th>";
*/

	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Agent Name").("agent"==$sort_outcomes_per_agent_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_outcomes_per_agent').value='".("agent"==$sort_outcomes_per_agent_preg ? "$reverse_link" : "agent")."'; this.form.action+='#sopa_anchor'; this.form.submit()\"></th>\n";
	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Status").("status"==$sort_outcomes_per_agent_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_outcomes_per_agent').value='".("status"==$sort_outcomes_per_agent_preg ? "$reverse_link" : "status")."';this.form.action+='#sopa_anchor';  this.form.submit()\"></th>\n";
	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Status Name").("outcome"==$sort_outcomes_per_agent_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_outcomes_per_agent').value='".("outcome"==$sort_outcomes_per_agent_preg ? "$reverse_link" : "outcome")."'; this.form.action+='#sopa_anchor'; this.form.submit()\"></th>\n";
	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Type").("type"==$sort_outcomes_per_agent_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_outcomes_per_agent').value='".("type"==$sort_outcomes_per_agent_preg ? "$reverse_link" : "type")."'; this.form.action+='#sopa_anchor'; this.form.submit()\"></th>\n";
	$HTML_output.="<th><input type='button' class='sort_button' value='"._QXZ("Total").("total"==$sort_outcomes_per_agent_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_outcomes_per_agent').value='".("total"==$sort_outcomes_per_agent_preg ? "$reverse_link" : "total")."'; this.form.action+='#sopa_anchor'; this.form.submit()\"></th>\n";

$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="</tr>\n";

$CSV_output["agent_outcomes"]="\""._QXZ("Agent name")."\",\""._QXZ("Status")."\",\""._QXZ("Status name")."\",\""._QXZ("Type")."\",\""._QXZ("Total")."\",\n";

foreach($final_opa_array as $key => $row)
	{
	$HTML_output.="<tr class='standard_font bold'>";
	$HTML_output.="<td>".$row["agent"]."</td>";
	$HTML_output.="<td>".$row["status"]."</td>";
	$HTML_output.="<td>".$row["outcome"]."</td>";
	$HTML_output.="<td>".$row["type"]."</td>";
	$HTML_output.="<td>".$row["total"]."</td>";
	$HTML_output.="<td width='200'><img src='images/shade-histo.gif' height='10' width='".(MathZDC((100*$row["total"]), $max_total_value))."%'></td>";
	$HTML_output.="</tr>\n";

	$CSV_output["agent_outcomes"].="\"$row[agent]\",\"$row[status]\",\"$row[outcome]\",\"$row[type]\",\"$row[total]\"\n";
	}

$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td colspan='6' class='export_row_cell'>"._QXZ("Export as")."...<a href=\"".$PHP_SELF."?download_rpt=agent_outcomes&sort_outcomes_per_agent=".$sort_outcomes_per_agent."\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a></td>";
$HTML_output.="<td class='export_row_cell' colspan='6'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('OUTCOMES', 'agent_outcomes')\" title=\"Export as a CSV file\" value='CSV'></td>";
$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="<input type='hidden' name='sort_outcomes_per_agent' id='sort_outcomes_per_agent' value='$sort_outcomes_per_agent'>";


if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}
###############################

if ($download_rpt)
	{
	$data_to_download=$CSV_output["$download_rpt"];

	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "STATUSES_RPT_".$download_rpt."_".$FILE_TIME.".csv";
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

?>

<?php
echo $HTML_header;

echo $HTML_output; 
?>
</body>
</html>
