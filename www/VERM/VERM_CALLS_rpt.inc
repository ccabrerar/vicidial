<?php
# VERM_CALLS_rpt.inc - Vicidial Enhanced Reporting calls
#
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGELOG:
# 220825-1625 - First build
#

if (isset($_GET["page_no"]))			{$page_no=$_GET["page_no"];}
	elseif (isset($_POST["page_no"]))	{$page_no=$_POST["page_no"];}
if (isset($_GET["ans_page_no"]))			{$ans_page_no=$_GET["ans_page_no"];}
	elseif (isset($_POST["ans_page_no"]))	{$ans_page_no=$_POST["ans_page_no"];}
if (isset($_GET["unans_page_no"]))			{$unans_page_no=$_GET["unans_page_no"];}
	elseif (isset($_POST["unans_page_no"]))	{$unans_page_no=$_POST["unans_page_no"];}
if (isset($_GET["sort_answered_details"]))			{$sort_answered_details=$_GET["sort_answered_details"];}
	elseif (isset($_POST["sort_answered_details"]))	{$sort_answered_details=$_POST["sort_answered_details"];}
if (isset($_GET["sort_unanswered_details"]))			{$sort_unanswered_details=$_GET["sort_unanswered_details"];}
	elseif (isset($_POST["sort_unanswered_details"]))	{$sort_unanswered_details=$_POST["sort_unanswered_details"];}

if ($non_latin < 1)
	{
	$sort_answered_details = preg_replace('/[^\s-_0-9a-zA-Z]/','',$sort_answered_details);
	$sort_unanswered_details = preg_replace('/[^\s-_0-9a-zA-Z]/','',$sort_unanswered_details);
	}
else
	{
	$sort_answered_details = preg_replace('/[^\s-_0-9\p{L}]/u','',$sort_answered_details);
	$sort_unanswered_details = preg_replace('/[^\s-_0-9\p{L}]/u','',$sort_unanswered_details);
	}
$page_no=preg_replace('/[^0-9]/', '', $page_no);
$ans_page_no=preg_replace('/[^0-9]/', '', $ans_page_no);
$unans_page_no=preg_replace('/[^0-9]/', '', $unans_page_no);

if (!$ans_page_no || $ans_page_no<1) {$ans_page_no=1;}

$ans_ll=($ans_page_no-1)*20;
$ans_ul=($ans_page_no*20);

$unans_page_no=preg_replace('/[^0-9]/', '', $unans_page_no);
if (!$unans_page_no || $unans_page_no<1) {$unans_page_no=1;}

$unans_ll=($unans_page_no-1)*20; ### !!! ###
$unans_ul=($unans_page_no*20); ### !!! ###

#### SAME FOR BOTH ANSWERED AND UNANSWERED
$available_columns=array(
"Date" => "call_date",
"Caller" => "phone_number",
"Queue" => "campaign_id",
"IVR" => "ivr",
"Wait" => "wait",
"Duration" => "length_in_sec",
"Pos." => "queue_position",
"Disconnection" => "term_reason",
"Transferred to" => "transferred_to",
"Handled by" => "user",
"Attempts" => "attempts",
"Code" => "status",
"Stints" => "stints",
"Srv" => "srv",
"Asterisk UID" => "caller_code",
"MOH events" => "moh_events",
"MOH duration" => "moh_duration",
"IVR duration" => "ivr_duration",
"IVR path" => "ivr_path",
"DID" => "did",
"CRM" => "",
"Tag" => "tag",
"Feat" => "feat",
"Vars" => "vars",
"Feature Codes" => "feature_codes",
"Variables" => "variables"
);

 ### !!! ###
$selected_columns=array(
_QXZ("Date") => "call_date",
_QXZ("Caller") => "phone_number",
_QXZ("Queue") => "campaign_id",
_QXZ("IVR") => "ivr_duration",
_QXZ("Wait") => "wait",
_QXZ("Duration") => "duration",
_QXZ("Pos.") => "queue_position",
_QXZ("Disconnection") => "term_reason",
_QXZ("Handled by") => "user",
_QXZ("Attempts") => "attempts",
_QXZ("Code") => "status",
"uniqueid" => "detail_id"
);


############################################
$sort_char=""; ### !!! ###
$sort_index=""; ### !!! ###

$sort_clause=" order by call_date";
$sort_index=preg_replace('/ desc/', '', $sort_answered_details);
if (preg_match('/ desc$/', $sort_answered_details)) 
	{
	$sort_char="&#8595;"; 
	$reverse_link=preg_replace('/ desc$/', '', $sort_answered_details);
	} 
else 
	{
	$sort_char="&#8593;"; 
	$reverse_link=$sort_answered_details." desc";
	}
$sort_answered_details_preg=preg_replace('/ desc$/', '', $sort_answered_details);

$stmt="select call_date, phone_number, campaign_id, 0 as ivr, '0:00' as wait, sec_to_time(round(length_in_sec)) as duration, '1' as queue_position, CAST(term_reason AS CHAR) as term_reason, user, '1' as attempts, status, uniqueid, 0 as moh_events, '00:00:00' as moh_duration, '' as ivr_duration, '' as ivr_path, '' as did, '' as url, '' as tag, '0' as feat, '0' as vars, '' as feature_codes, '' as variables, uniqueid as detail_id, 'O' as direction from vicidial_log $vicidial_log_SQL and user not in ('VDAD') UNION select call_date, phone_number, campaign_id, 0 as ivr, sec_to_time(round(queue_seconds)) as wait, sec_to_time(round(if(comments='EMAIL', length_in_sec, length_in_sec-queue_seconds))) as duration, queue_position, CAST(term_reason AS CHAR) as term_reason, user, '1' as attempts, status, uniqueid, 0 as moh_events, '00:00:00' as moh_duration, '' as ivr_duration, '' as ivr_path, '' as did, '' as url, '' as tag, '0' as feat, '0' as vars, '' as feature_codes, '' as variables, CONCAT(uniqueid, '|', closecallid) as detail_id, 'I' as direction from vicidial_closer_log $vicidial_closer_log_SQL and (user!='VDCL') $sort_clause";

if ($DB) {$HTML_output.="<B>$stmt</B>";}

$rslt=mysql_to_mysqli($stmt, $link);
$total_pages=ceil(mysqli_num_rows($rslt)/20);

$HTML_output.="<BR><h2 class='rpt_header' id='sad_anchor'>"._QXZ("Answered Call Details").":</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
$HTML_output.="<table width='100%' id='details_table'>";
$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' align='left'><B><a name='page_anchor' class='header_link' href='".$PHP_SELF."?sort_answered_details=".$sort_answered_details."&ans_page_no=1#page_anchor' alt='First page of results'>|<</a>&nbsp;&nbsp;&nbsp;&nbsp;<a class='header_link' href='".$PHP_SELF."?sort_answered_details=".$sort_answered_details."&ans_page_no=".($ans_page_no-1)."#page_anchor' alt='Prev page of results'><<</a></B></td>";
$HTML_output.="<td class='export_row_cell' align='left'>";
$HTML_output.="<input type='button' class='download_button' onClick=\"javascript:this.form.action+='#sad_anchor'; GoToDetailsPage('CALLS', '1', 'ans_page_no')\" title=\"First page\" value='|<'>&nbsp;&nbsp;&nbsp;&nbsp;";
$HTML_output.="<input type='button' class='download_button' onClick=\"javascript:this.form.action+='#sad_anchor'; GoToDetailsPage('CALLS', '".($ans_page_no-1)."', 'ans_page_no')\" title=\"Prev page\" value='<<'>";
$HTML_output.="</td>";
$HTML_output.="<td class='export_row_cell' align='center' colspan='".(count($selected_columns)-2)."'>&nbsp;</td>";
# $HTML_output.="<td class='export_row_cell' align='right'><B><a class='header_link' href='".$PHP_SELF."?sort_answered_details=".$sort_answered_details."&ans_page_no=".($ans_page_no+1)."#page_anchor' alt='Next page of results'>>></a>&nbsp;&nbsp;&nbsp;&nbsp;<a class='header_link' href='".$PHP_SELF."?sort_answered_details=".$sort_answered_details."&ans_page_no=".$total_pages."#page_anchor' alt='Last page of results'>>|</a></B></td>";
$HTML_output.="<td class='export_row_cell' align='right'>";
$HTML_output.="<input type='button' class='download_button' onClick=\"javascript:this.form.action+='#sad_anchor'; GoToDetailsPage('CALLS', '".($ans_page_no<$total_pages ? $ans_page_no+1 : $total_pages)."', 'ans_page_no')\" title=\"Next page\" value='>>'>&nbsp;&nbsp;&nbsp;&nbsp;";
$HTML_output.="<input type='button' class='download_button' onClick=\"javascript:this.form.action+='#sad_anchor'; GoToDetailsPage('CALLS', '".($total_pages)."', 'ans_page_no')\" title=\"Last page\" value='>|'>";
$HTML_output.="</td>";
$HTML_output.="</tr>";
$HTML_output.="<tr>";
$CSV_output["answer_details"]="";
foreach ($selected_columns as $display_name => $column_name)
	{
	if ($display_name!="uniqueid")
		{
		$CSV_output["answer_details"].="\"$display_name\",";
		# $HTML_output.="<th><a class='header_link' name='call_detail_sort_".$column_name."' id='call_detail_sort_".$column_name."' href='".$PHP_SELF."?sort_answered_details=".($column_name==$sort_answered_details_preg ? "$reverse_link" : "$column_name")."&ans_page_no=".$ans_page_no."#call_detail_sort_".$column_name."'>".$display_name.($column_name==$sort_answered_details_preg ? " $sort_char" : "")."</a></th>";

		$HTML_output.="<th><input type='button' class='sort_button' value='".$display_name.($column_name==$sort_answered_details_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_answered_details').value='".($column_name==$sort_answered_details_preg ? "$reverse_link" : "$column_name")."'; this.form.action+='#sad_anchor'; this.form.submit()\"></th>\n";
		}
	}
$CSV_output["answer_details"].="\n";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="</tr>";

$output_array=array();
$i=0;
while ($row=mysqli_fetch_array($rslt))
	{
	$row["caller_code"]="";
	$row["ivr"]="00:00";
	$row["ivr_duration"]="00:00";
	$row["ivr_path"]="";
	$row["did"]="";

	$uniqueid=$row["uniqueid"];
	$row["campaign_id"]=$queue_names["$row[campaign_id]"];
	$row["status"]=($status_names["$row[status]"] ? $status_names["$row[status]"] : $row["status"]);
	$row["user"]=$fullname_info["$row[user]"];


	$row["caller_code"]=GetCallerCode($row["uniqueid"]);
	if ($row["direction"]=="I")
		{
		$ivr_info_array=GetIVRInfo($row["uniqueid"], $row["call_date"]);
		$row["ivr"]=$ivr_info_array[0];
		$row["ivr_duration"]=$ivr_info_array[1];
		$row["ivr_path"]=$ivr_info_array[2];
		$row["call_date"]=$ivr_info_array[3];
		$row["did"]=GetDID($row["uniqueid"]);
		}

	if ($i>=$ans_ll && $i<$ans_ul || $download_rpt)
		{
		$current_row=array();
		foreach ($selected_columns as $display_name => $column_name)
			{
			$current_row["$column_name"]=$row["$column_name"];
			}

		array_push($output_array, $current_row);
		}
	$i++;
	}

$sort_array_holder=array();
if ($sort_index)
	{
	#$sort_index=8;
	# print "***** $sort_index ********\n";
	# array_multisort($output_array[$sort_index], SORT_ASC, SORT_STRING);

	foreach ($output_array as $sorting_array) {
        $sort_array_holder[] = $sorting_array["$sort_index"];
    }
	if (preg_match('/ desc$/', $sort_answered_details))
		{
		array_multisort($sort_array_holder,SORT_DESC, SORT_STRING,$output_array);
		}
	else
		{
		array_multisort($sort_array_holder,SORT_ASC, SORT_STRING,$output_array);
		}

	#usort($output_array, function($a, $b){
    #        return strcmp($a[$sort_index], $b[$sort_index]);
	#});
	}

foreach ($output_array as $data_row)
	{
	$HTML_output.="<tr>";
	foreach ($data_row as $key => $value)
		{
		if ($key=="call_date")
			{
			$myDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $value);
			$value = $myDateTime->format('m/d - H:i:s');
			}
		if ($key!="detail_id")
			{
			$HTML_output.="<td>".$value."</td>";
			$CSV_output["answer_details"].="\"".$value."\",";
			}
		else
			{
			$HTML_output.="<td><a onClick=\"ShowCallDetails('$value', 'CALLS')\"><svg width='20' height='20' viewBox='0 0 20 20' data-svg='search'><circle fill='none' stroke='#000' stroke-width='1.1' cx='9' cy='9' r='7'></circle><path fill='none' stroke='#000' stroke-width='1.1' d='M14,14 L18,18 L14,14 Z'></path></svg></a></td>";
			# $HTML_output.="<td><IMG SRC=\"images/glass.png\" onClick=\"ShowCallDetails('$value')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></td>";
			}
		}
	$CSV_output["answer_details"].="\n";

	$HTML_output.="</tr>";
	}
$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' align='left'><B><a class='header_link' href='".$PHP_SELF."?sort_answered_details=".$sort_answered_details."&ans_page_no=1#page_anchor' alt='First page of results'>|<</a>&nbsp;&nbsp;&nbsp;&nbsp;<a class='header_link' href='".$PHP_SELF."?sort_answered_details=".$sort_answered_details."&ans_page_no=".($ans_page_no-1)."#page_anchor' alt='Prev page of results'><<</a></B></td>";
$HTML_output.="<td class='export_row_cell' align='left'>";
$HTML_output.="<input type='button' class='download_button' onClick=\"javascript:this.form.action+='#sad_anchor'; GoToDetailsPage('CALLS', '1', 'ans_page_no')\" title=\"First page\" value='|<'>&nbsp;&nbsp;&nbsp;&nbsp;";
$HTML_output.="<input type='button' class='download_button' onClick=\"javascript:this.form.action+='#sad_anchor'; GoToDetailsPage('CALLS', '".($ans_page_no-1)."', 'ans_page_no')\" title=\"Prev page\" value='<<'>";
$HTML_output.="</td>";
# $HTML_output.="<td class='export_row_cell' align='center' colspan='".(count($selected_columns)-2)."'>"._QXZ("Export as")."... &nbsp;&nbsp;&nbsp;&nbsp;<a  href=\"".$PHP_SELF."?download_rpt=answer_details&sort_answered_details=".$sort_answered_details."\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a>&nbsp;&nbsp;&nbsp;&nbsp;Current page: $ans_page_no / $total_pages</td>";
$HTML_output.="<td colspan='".(count($selected_columns)-2)."' align='center' class='export_row_cell'>"._QXZ("Export as")."... &nbsp;&nbsp;&nbsp;&nbsp;<input type='button' class='download_button' onClick=\"DownloadReport('CALLS', 'answer_details')\" title=\"Export as a CSV file\" value='CSV'>&nbsp;&nbsp;&nbsp;&nbsp;"._QXZ("Current page").": $ans_page_no / $total_pages</td>";

# $HTML_output.="<td class='export_row_cell' align='right'><B><a class='header_link' href='".$PHP_SELF."?sort_answered_details=".$sort_answered_details."&ans_page_no=".($ans_page_no+1)."#page_anchor' alt='Next page of results'>>></a>&nbsp;&nbsp;&nbsp;&nbsp;<a class='header_link' href='".$PHP_SELF."?sort_answered_details=".$sort_answered_details."&ans_page_no=".($total_pages-1)."#page_anchor' alt='Last page of results'>>|</a></B></td>";
$HTML_output.="<td class='export_row_cell' align='right'>";
$HTML_output.="<input type='button' class='download_button' onClick=\"javascript:this.form.action+='#sad_anchor'; GoToDetailsPage('CALLS', '".($ans_page_no<$total_pages ? $ans_page_no+1 : $total_pages)."', 'ans_page_no')\" title=\"Next page\" value='>>'>&nbsp;&nbsp;&nbsp;&nbsp;";
$HTML_output.="<input type='button' class='download_button' onClick=\"javascript:this.form.action+='#sad_anchor'; GoToDetailsPage('CALLS', '".($total_pages)."', 'ans_page_no')\" title=\"Last page\" value='>|'>";
$HTML_output.="</td>";

$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="<input type=hidden name=ans_page_no id=ans_page_no value='$ans_page_no'>";
$HTML_output.="<input type='hidden' name='sort_answered_details' id='sort_answered_details' value='$sort_answered_details'>";
############################################

$HTML_output.="<BR><BR><BR>";

########## UNANSWERED DETAILS ##############
$selected_columns=array(
_QXZ("Date") => "call_date",
_QXZ("Agent") => "user",
_QXZ("Caller") => "phone_number",
_QXZ("Queue") => "campaign_id",
_QXZ("Disconnection") => "term_reason",
_QXZ("Position") => "queue_position",
_QXZ("IVR") => "ivr_duration",
_QXZ("Wait") => "wait",
_QXZ("Attempts") => "attempts",
_QXZ("Code") => "status",
_QXZ("Key") => "key",
"uniqueid" => "detail_id"
);

$sort_char="";
$sort_index="";

$sort_clause=" order by call_date";
$sort_index=preg_replace('/ desc/', '', $sort_unanswered_details);
if (preg_match('/ desc$/', $sort_unanswered_details)) 
	{
	$sort_char="&#8595;"; 
	$reverse_link=preg_replace('/ desc$/', '', $sort_unanswered_details);
	} 
else 
	{
	$sort_char="&#8593;"; 
	$reverse_link=$sort_unanswered_details." desc";
	}
$sort_unanswered_details_preg=preg_replace('/ desc$/', '', $sort_unanswered_details);

$stmt="select call_date, phone_number, campaign_id, 0 as ivr, '0:00' as wait, sec_to_time(round(length_in_sec)) as duration, '1' as queue_position, CAST(term_reason AS CHAR) as term_reason, user, '1' as attempts, status, uniqueid, 0 as moh_events, '00:00:00' as moh_duration, '' as ivr_duration, '' as ivr_path, '' as did, '' as url, '' as tag, '0' as feat, '0' as vars, '' as feature_codes, '' as variables, uniqueid as detail_id, 'O' as direction from vicidial_log $vicidial_log_SQL and user in ('VDAD') UNION select call_date, phone_number, campaign_id, 0 as ivr, sec_to_time(round(queue_seconds)) as wait, sec_to_time(round(if(comments='EMAIL', length_in_sec, length_in_sec-queue_seconds))) as duration, queue_position, CAST(term_reason AS CHAR) as term_reason, user, '1' as attempts, status, uniqueid, 0 as moh_events, '00:00:00' as moh_duration, '' as ivr_duration, '' as ivr_path, '' as did, '' as url, '' as tag, '0' as feat, '0' as vars, '' as feature_codes, '' as variables, CONCAT(uniqueid, '|', closecallid) as detail_id, 'I' as direction from vicidial_closer_log $vicidial_closer_log_SQL and user in ('VDCL') $and_NANQUE_clause $sort_clause";

if ($DB) {$HTML_output.="<B>$stmt</B>";}

$rslt=mysql_to_mysqli($stmt, $link);
$total_pages=ceil(mysqli_num_rows($rslt)/20);

$HTML_output.="<BR><h2 class='rpt_header' id='sud_anchor'>"._QXZ("Unanswered Call Details").":</h2>";
$HTML_output.="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'><a name='make_it_work' id='make_it_work'>";
$HTML_output.="<table width='100%' id='details_table'>";
$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' align='left'><B><a name='page_anchor' class='header_link' href='".$PHP_SELF."?sort_unanswered_details=".$sort_unanswered_details."&unans_page_no=1#page_anchor' alt='First page of results'>|<</a>&nbsp;&nbsp;&nbsp;&nbsp;<a class='header_link' href='".$PHP_SELF."?sort_unanswered_details=".$sort_unanswered_details."&unans_page_no=".($unans_page_no-1)."#page_anchor' alt='Prev page of results'><<</a></B></td>";
$HTML_output.="<td class='export_row_cell' align='left'>";
$HTML_output.="<input type='button' class='download_button' onClick=\"javascript:this.form.action+='#sud_anchor'; GoToDetailsPage('CALLS', '1', 'unans_page_no')\" title=\"First page\" value='|<'>&nbsp;&nbsp;&nbsp;&nbsp;";
$HTML_output.="<input type='button' class='download_button' onClick=\"javascript:this.form.action+='#sud_anchor'; GoToDetailsPage('CALLS', '".($unans_page_no-1)."', 'unans_page_no')\" title=\"Prev page\" value='<<'>";
$HTML_output.="</td>";
$HTML_output.="<td class='export_row_cell' align='center' colspan='".(count($selected_columns)-2)."'>&nbsp;</td>";
# $HTML_output.="<td class='export_row_cell' align='right'><B><a class='header_link' href='".$PHP_SELF."?sort_unanswered_details=".$sort_unanswered_details."&unans_page_no=".($unans_page_no+1)."#page_anchor' alt='Next page of results'>>></a>&nbsp;&nbsp;&nbsp;&nbsp;<a class='header_link' href='".$PHP_SELF."?sort_unanswered_details=".$sort_unanswered_details."&unans_page_no=".$total_pages."#page_anchor' alt='Last page of results'>>|</a></B></td>";
$HTML_output.="<td class='export_row_cell' align='right'>";
$HTML_output.="<input type='button' class='download_button' onClick=\"javascript:this.form.action+='#sud_anchor'; GoToDetailsPage('CALLS', '".($unans_page_no<$total_pages ? $unans_page_no+1 : $total_pages)."', 'unans_page_no')\" title=\"Next page\" value='>>'>&nbsp;&nbsp;&nbsp;&nbsp;";
$HTML_output.="<input type='button' class='download_button' onClick=\"javascript:this.form.action+='#sud_anchor'; GoToDetailsPage('CALLS', '".($total_pages)."', 'unans_page_no')\" title=\"Last page\" value='>|'>";
$HTML_output.="</td>";
$HTML_output.="</tr>";
$HTML_output.="<tr>";
$CSV_output["unanswered_details"]="";
foreach ($selected_columns as $display_name => $column_name)
	{
	if ($display_name!="uniqueid")
		{
		$CSV_output["unanswered_details"].="\"$display_name\",";
		# $HTML_output.="<th><a class='header_link' name='call_detail_sort_".$column_name."' id='call_detail_sort_".$column_name."' href='".$PHP_SELF."?sort_unanswered_details=".($column_name==$sort_unanswered_details_preg ? "$reverse_link" : "$column_name")."&unans_page_no=".$unans_page_no."#call_detail_sort_".$column_name."'>".$display_name.($column_name==$sort_unanswered_details_preg ? " $sort_char" : "")."</a></th>";

		$HTML_output.="<th><input type='button' class='sort_button' value='".$display_name.($column_name==$sort_unanswered_details_preg ? " $sort_char" : "")."' onClick=\"javascript:document.getElementById('sort_unanswered_details').value='".($column_name==$sort_unanswered_details_preg ? "$reverse_link" : "$column_name")."'; this.form.action+='#sud_anchor'; this.form.submit()\"></th>\n";
		}
	}
$CSV_output["unanswered_details"].="\n";
$HTML_output.="<th>&nbsp;</th>";
$HTML_output.="</tr>";

$output_array=array();
$i=0;
while ($row=mysqli_fetch_array($rslt))
	{
	$row["caller_code"]="";
	$row["ivr"]="00:00";
	$row["ivr_duration"]="00:00";
	$row["ivr_path"]="";
	$row["did"]="";

	$uniqueid=$row["uniqueid"];
	$row["campaign_id"]=$queue_names["$row[campaign_id]"];
	$row["status"]=($status_names["$row[status]"] ? $status_names["$row[status]"] : $row["status"]);
	$row["user"]=$fullname_info["$row[user]"];

	$row["key"]=GetKeyPress($row["uniqueid"]);

	$row["caller_code"]=GetCallerCode($row["uniqueid"]);
	if ($row["direction"]=="I")
		{
		$ivr_info_array=GetIVRInfo($row["uniqueid"], $row["call_date"]);
		$row["ivr"]=$ivr_info_array[0];
		$row["ivr_duration"]=$ivr_info_array[1];
		$row["ivr_path"]=$ivr_info_array[2];
		$row["call_date"]=$ivr_info_array[3];
		$row["did"]=GetDID($row["uniqueid"]);
		}

	if ($i>=$unans_ll && $i<$unans_ul || $download_rpt)
		{
		$current_row=array();
		foreach ($selected_columns as $display_name => $column_name)
			{
			$current_row["$column_name"]=$row["$column_name"];
			}

		array_push($output_array, $current_row);
		}
	$i++;
	}

$sort_array_holder=array();
if ($sort_index)
	{
	#$sort_index=8;
	# print "***** $sort_index ********\n";
	# array_multisort($output_array[$sort_index], SORT_ASC, SORT_STRING);

	foreach ($output_array as $sorting_array) 
		{
		$sort_array_holder[] = $sorting_array["$sort_index"];
		}
	if (preg_match('/ desc$/', $sort_unanswered_details))
		{
		array_multisort($sort_array_holder,SORT_DESC, SORT_STRING,$output_array);
		}
	else
		{
		array_multisort($sort_array_holder,SORT_ASC, SORT_STRING,$output_array);
		}

	#usort($output_array, function($a, $b){
    #        return strcmp($a[$sort_index], $b[$sort_index]);
	#});
	}

foreach ($output_array as $data_row)
	{
	$HTML_output.="<tr>";
	foreach ($data_row as $key => $value)
		{
		if ($key=="call_date")
			{
			$myDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $value);
			$value = $myDateTime->format('m/d - H:i:s');
			}
		if ($key!="detail_id")
			{
			$HTML_output.="<td>".$value."</td>";
			$CSV_output["unanswered_details"].="\"".$value."\",";
			}
		else
			{
			$HTML_output.="<td><a onClick=\"ShowCallDetails('$value', 'answered')\"><svg width='20' height='20' viewBox='0 0 20 20' data-svg='search'><circle fill='none' stroke='#000' stroke-width='1.1' cx='9' cy='9' r='7'></circle><path fill='none' stroke='#000' stroke-width='1.1' d='M14,14 L18,18 L14,14 Z'></path></svg></a></td>";
			# $HTML_output.="<td><IMG SRC=\"images/glass.png\" onClick=\"ShowCallDetails('$value')\" WIDTH=20 HEIGHT=20 BORDER=0 ALT=\"HELP\" ALIGN=TOP></td>";
			}
		}
	$CSV_output["unanswered_details"].="\n";

	$HTML_output.="</tr>";
	}
$HTML_output.="<tr class='export_row'>";
# $HTML_output.="<td class='export_row_cell' align='left'><B><a class='header_link' href='".$PHP_SELF."?sort_unanswered_details=".$sort_unanswered_details."&unans_page_no=1#page_anchor' alt='First page of results'>|<</a>&nbsp;&nbsp;&nbsp;&nbsp;<a class='header_link' href='".$PHP_SELF."?sort_unanswered_details=".$sort_unanswered_details."&unans_page_no=".($unans_page_no-1)."#page_anchor' alt='Prev page of results'><<</a></B></td>";
$HTML_output.="<td class='export_row_cell' align='left'>";
$HTML_output.="<input type='button' class='download_button' onClick=\"javascript:this.form.action+='#sud_anchor'; GoToDetailsPage('CALLS', '1', 'unans_page_no')\" title=\"First page\" value='|<'>&nbsp;&nbsp;&nbsp;&nbsp;";
$HTML_output.="<input type='button' class='download_button' onClick=\"javascript:this.form.action+='#sud_anchor'; GoToDetailsPage('CALLS', '".($unans_page_no-1)."', 'unans_page_no')\" title=\"Prev page\" value='<<'>";
$HTML_output.="</td>";
# $HTML_output.="<td class='export_row_cell' align='center' colspan='".(count($selected_columns)-2)."'>"._QXZ("Export as")."... &nbsp;&nbsp;&nbsp;&nbsp;<a  href=\"".$PHP_SELF."?download_rpt=answer_details&sort_unanswered_details=".$sort_unanswered_details."\" title=\"Export as a CSV file\" class=\"uk-icon\">CSV</a>&nbsp;&nbsp;&nbsp;&nbsp;Current page: $unans_page_no / $total_pages</td>";
$HTML_output.="<td class='export_row_cell' align='center' colspan='".(count($selected_columns)-2)."'>"._QXZ("Export as")."...<input type='button' class='download_button' onClick=\"DownloadReport('CALLS', 'unanswered_details')\" title=\"Export as a CSV file\" value='CSV'>&nbsp;&nbsp;&nbsp;&nbsp;Current page: $unans_page_no / $total_pages</td>";
# $HTML_output.="<td class='export_row_cell' align='right'><B><a class='header_link' href='".$PHP_SELF."?sort_unanswered_details=".$sort_unanswered_details."&unans_page_no=".($unans_page_no+1)."#page_anchor' alt='Next page of results'>>></a>&nbsp;&nbsp;&nbsp;&nbsp;<a class='header_link' href='".$PHP_SELF."?sort_unanswered_details=".$sort_unanswered_details."&unans_page_no=".($total_pages-1)."#page_anchor' alt='Last page of results'>>|</a></B></td>";
$HTML_output.="<td class='export_row_cell' align='right'>";
$HTML_output.="<input type='button' class='download_button' onClick=\"javascript:this.form.action+='#sud_anchor'; GoToDetailsPage('CALLS', '".($unans_page_no<$total_pages ? $unans_page_no+1 : $total_pages)."', 'unans_page_no')\" title=\"Next page\" value='>>'>&nbsp;&nbsp;&nbsp;&nbsp;";
$HTML_output.="<input type='button' class='download_button' onClick=\"javascript:this.form.action+='#sud_anchor'; GoToDetailsPage('CALLS', '".($total_pages)."', 'unans_page_no')\" title=\"Last page\" value='>|'>";
$HTML_output.="</td>";

$HTML_output.="</tr>";
$HTML_output.="</table>";
$HTML_output.="<input type=hidden name=unans_page_no id=unans_page_no value='$unans_page_no'>";
$HTML_output.="<input type=hidden name=sort_unanswered_details id=sort_unanswered_details value='$sort_unanswered_details'>";
############################################

if ($download_rpt)
	{
	$data_to_download=$CSV_output["$download_rpt"];

	$FILE_TIME = date("Ymd-His");
	$CSVfilename = "CALLS_RPT_".$download_rpt."_".$FILE_TIME.".csv";
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
function GetCallerCode($uniqueid)
	{
	global $link, $DB;
	$caller_code="";
	$cc_stmt="select caller_code from vicidial_log_extended where uniqueid='$uniqueid'";
	$cc_rslt=mysqli_query($link, $cc_stmt);
	$cc_row=mysqli_fetch_array($cc_rslt);
	$caller_code=$cc_row[0];
	return $caller_code;
	}

function GetKeyPress($uniqueid)
	{
	global $link, $DB;
	$keypress="";
	$keypress_stmt="select extension, max(start_time) From live_inbound_log where uniqueid='$uniqueid' and extension not in ('s', 'i', 't') and comment_a='CALLMENU' group by extension";
	$keypress_rslt=mysql_to_mysqli($keypress_stmt, $link);
	$keypress_row=mysqli_fetch_array($keypress_rslt);
	$keypress=$keypress_row[0];
	return $keypress;
	}

function GetIVRInfo($uniqueid, $actual_start_time)
	{
	global $link, $DB;

	$ivr_path="";
	$ivr_length=0;
	$ivr_duration=0;

	$ivr_stmt="select extension,start_time,comment_a,comment_b,comment_d,UNIX_TIMESTAMP(start_time),phone_ext from live_inbound_log where uniqueid='$uniqueid' and comment_a IN('CALLMENU') order by start_time";
	$ivr_rslt=mysqli_query($link, $ivr_stmt);
	$ivr_paths=array(); # 0 - total calls, 1 - total time, 2 - min time, 3 - max time
	$ivr_counts=array();
	while ($ivr_row=mysqli_fetch_array($ivr_rslt))
		{
		if(!$prev_time) {$prev_time=$ivr_row[5];}
		if(!$ivr_start_time) 
			{
			$actual_start_time=$ivr_row[1];
			$ivr_start_time=$ivr_row[5];
			}
		$ivrpath.=$ivr_row["comment_b"];

		$ivr_duration=$ivr_row[5]-$ivr_start_time;
		$ivr_length+=$ivr_duration;
		$prev_time=$ivr_row[5];
		}

	$ivr_duration_fmt=($ivr_duration>=3600 ? floor($ivr_duration/3600).":" : "").gmdate("i:s", $ivr_duration);
	$ivr_length_fmt=($ivr_length>=3600 ? floor($ivr_length/3600).":" : "").gmdate("i:s", $ivr_length);

	return array("$ivr_length_fmt", "$ivr_duration_fmt", "$ivr_path", "$actual_start_time");
	}

function GetDID($uniqueid)
	{
	global $link, $DB, $did_id_info, $did_pattern_info;

	$did_str="";

	$did_stmt="select extension, did_id from vicidial_did_log where uniqueid in ('$uniqueid')";
	$did_rslt=mysqli_query($link, $did_stmt);
	$did_row=mysqli_fetch_array($did_rslt);
	$did_str=$did_row["extension"]." - ".$did_id_info["$did_row[extension]"];

	return $did_str;
	}
?>
