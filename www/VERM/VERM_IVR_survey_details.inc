<?php
# VERM_IVR_survey_details_rpt.inc - Vicidial Enhanced Reporting IVR survey detail report
#
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGELOG:
# 220825-1618 - First build
# 230106-1329 - Added CSV output, page flushing, and recording access logging
#

# $DB=1;
$CSV_output["ivr_survey_details"]="";
foreach ($ivr_survey_ingroups_detail as $comment_a => $callmenu_list) 
	{

	$ingrp_stmt="select group_name from vicidial_inbound_groups where group_id='$comment_a' UNION select menu_name from vicidial_call_menu where menu_id='$comment_a'";
	# echo "<!-- $ingrp_stmt //-->\n";
	if ($DB){$HTML_output.="$ingrp_stmt<BR>";}
	$ingrp_rslt=mysql_to_mysqli($ingrp_stmt, $link);
	while($ingrp_row=mysqli_fetch_row($ingrp_rslt))
		{
		$survey_name=$ingrp_row[0];
		}

	$comment_a_clause=" and comment_a='$comment_a' ";
	$ivr_stmt="select distinct uniqueid from live_inbound_log $live_inbound_log_SQL $comment_a_clause order by uniqueid asc";
	if ($DB){$HTML_output.="$ivr_stmt<BR>";}
	$ivr_rslt=mysql_to_mysqli($ivr_stmt, $link);
	if (mysqli_num_rows($ivr_rslt)>0)
		{

		$HTML_output.="<BR><h2 class='rpt_header'>"._QXZ("IVR Details for survey")." $survey_name: $NWB#VERM_IVR-ivr_survey_details$NWE</h2>";
		$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
		$HTML_output.="<table width='100%' id='rpt_table'>\n";
		$HTML_output.="<tr class='standard_font_small bold'>";
		$HTML_output.="<th>"._QXZ("Date")."</th>";
		$HTML_output.="<th>"._QXZ("Caller")."</th>";
		$HTML_output.="<th>"._QXZ("DID")."</th>";
		$CSV_output["ivr_survey_details"].="\""._QXZ("IVR Details for survey")." $survey_name:\"\n\""._QXZ("Date")."\",\""._QXZ("Caller")."\",\""._QXZ("DID")."\",";
	
		for ($t=0; $t<count($callmenu_list); $t++)
			{
			$svy_menu=$callmenu_list[$t];
			$cm_stmt="select menu_name from vicidial_call_menu where menu_id='$svy_menu'";
			$cm_rslt=mysql_to_mysqli($cm_stmt, $link);
			$cm_row=mysqli_fetch_row($cm_rslt);
			$HTML_output.="<th>".$svy_menu." - ".$cm_row[0]."</th>";
			$CSV_output["ivr_survey_details"].="\"$svy_menu - $cm_row[0]\",";
			}
		$HTML_output.="<th>"._QXZ("Survey voicemail")."</th>";
		$HTML_output.="<th>"._QXZ("Agent who handled call prior to survey")."</th>";
		$HTML_output.="<th>"._QXZ("Link to call recording prior to survey")."</th>";
		$HTML_output.="</tr>\n";
		$CSV_output["ivr_survey_details"].="\""._QXZ("Survey voicemail")."\",\""._QXZ("Agent who handled call prior to survey")."\",\""._QXZ("Link to call recording prior to survey")."\"\n";

		while ($ivr_row=mysqli_fetch_row($ivr_rslt))
			{
			$did_found=0;
			$svy_uniqueid=$ivr_row[0];
			$svy_goal=$ivr_details["$svy_uniqueid"]["goal"];

			$vcl_stmt="select user, phone_number, closecallid, xfercallid, call_date, lead_id from vicidial_closer_log where uniqueid='$svy_uniqueid' order by call_date desc limit 1";
			$vcl_rslt=mysql_to_mysqli($vcl_stmt, $link);
			while($vcl_row=mysqli_fetch_array($vcl_rslt))
				{
				$agent_who_handled=$fullname_info["$vcl_row[user]"];
				$caller=$vcl_row["phone_number"];
				$date=$vcl_row["call_date"];
				$vicidial_id=$vcl_row["closecallid"];
				$xfercallid=$vcl_row["xfercallid"];
				$closer_lead_id=$vcl_row["lead_id"];

				$rec_stmt="select filename, location, recording_id, lead_id from recording_log where vicidial_id='$vicidial_id'";
				$rec_rslt=mysql_to_mysqli($rec_stmt, $link);
				$rec_row=mysqli_fetch_row($rec_rslt);
				# $rec_link="<a href='$rec_row[1]'>$rec_row[0]</a>";
				if ($log_recording_access<1) 
					{$rec_link="<a href='$rec_row[1]'>$rec_row[0]</a>";}
				else
					{$rec_link = "<a href=\"recording_log_redirect.php?recording_id=$rec_row[2]&lead_id=$rec_row[3]&search_archived_data=$search_archived_data\" target=\"_blank\">$rec_row[0]</a>";}

				}

			$failsafe=0;
			$did_pattern="";
			$did_uniqueid=$svy_uniqueid;
			while(!$did_found)
				{
				$did_stmt="select call_date, did_id, extension, caller_id_number from vicidial_did_log where uniqueid='$did_uniqueid' order by call_date asc limit 1";
				if ($DB){$HTML_output.="$did_stmt<BR>";}
				$did_rslt=mysql_to_mysqli($did_stmt, $link);
				if (mysqli_num_rows($did_rslt)>0)
					{
					$did_row=mysqli_fetch_row($did_rslt);
					$did_id=$did_row[1];
					$did_pattern_stmt="select did_pattern from vicidial_inbound_dids where did_id='$did_id'";
					$did_pattern_rslt=mysql_to_mysqli($did_pattern_stmt, $link);
					$did_found=1;
					$did_pattern_row=mysqli_fetch_row($did_pattern_rslt);
					$did_pattern=$did_pattern_row[0];
					}

				$failsafe++;
				if ($failsafe>=5) {$did_found=1;}
				}


			$lil_stmt="select group_concat(comment_b) as survey_path from live_inbound_log where uniqueid='$svy_uniqueid' $comment_a_clause order by start_time";
			if ($DB){$HTML_output.="$lil_stmt<BR>";}
			$lil_rslt=mysql_to_mysqli($lil_stmt, $link);
			$lil_row=mysqli_fetch_row($lil_rslt);
			$lil_array=explode(",", $lil_row[0]);
			$full_path=$lil_row[0];

			$survey_array=array();
			for ($i=0; $i<count($lil_array); $i+=2)
				{
				$survey=$lil_array[$i];
				$survey_answer=$lil_array[($i+1)];
				$survey_array["$survey"]=$survey_answer;
				}

			$HTML_output.="<tr class='standard_font_small'>";
			$HTML_output.="<td>$date</td>";
			$HTML_output.="<td>$caller</td>";
			$HTML_output.="<td>$did_pattern</td>";
			$CSV_output["ivr_survey_details"].="\"$date\",\"$caller\",\"$did_pattern\",";

			for ($i=0; $i<count($callmenu_list); $i++)
				{
				$svy_menu=$callmenu_list[$i];
				$HTML_output.="<td>".(!$survey_array["$svy_menu"] ? "&nbsp;" : $survey_array["$svy_menu"])."</td>";
				$CSV_output["ivr_survey_details"].="\"".$survey_array["$svy_menu"]."\",";
				}

			if ($ivr_survey_ingroups_voicemails)
				{
				$voicemail="No";
				foreach($ivr_survey_ingroups_voicemails as $callmenu => $prompt)
					{
					$vm_prompt="$callmenu,$prompt";
					if (preg_match("/$vm_prompt/", $full_path))
						{
						$voicemail="Yes";
						}
					}
				}
			else
				{
				$voicemail="N/A";
				}

			$HTML_output.="<td>".$voicemail."</td>";
			$HTML_output.="<td>$agent_who_handled</td>";
			$HTML_output.="<td>$rec_link</td>";
			$HTML_output.="</tr>\n";
			$CSV_output["ivr_survey_details"].="\"$voicemail\",\"$agent_who_handled\",\"$rec_row[1]\"\n";

			if (!$download_rpt) {echo $HTML_output; ob_flush(); flush(); $HTML_output="";}

			}

		$HTML_output.="<tr class='export_row'>";
		$HTML_output.="<td class='export_row_cell' colspan='".(6+count($callmenu_list))."'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('IVR', 'ivr_survey_details')\" title=\"Export as a CSV file\" value='CSV'></td>";
		$HTML_output.="</tr>\n";
		$HTML_output.="</table>";
		$CSV_output["ivr_survey_details"].="\n";
		}
	}
?>
