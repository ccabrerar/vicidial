<?php
# graphcanvas.inc
# 
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>, Joe Johnson <freewermadmin@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
#
# 160731-1019 - First build
# 171012-2015 - Fixed javascript/apache errors with graphs by removing "echo" lines
#

$graphCanvas="\n\n<form>";
$graphCanvas.="<table cellspacing=\"1\" cellpadding=\"1\" summary=\""._QXZ("$graph_title")."\" class=\"horizontalgraph\">\n";
$graphCanvas.="  <caption align=\"top\">"._QXZ("$graph_title")."</caption>\n";
if ($graph_count>1) {
	$graphCanvas.="	<tr>\n";
	for ($q=0; $q<$graph_count; $q++) {
		$graph_info=explode("|", $graph_array[$q]);
		$link_name=$graph_info[2];
		$graphCanvas.="<th width='".($q==0 ? '*' : floor(100/$graph_count).'%')."' class='thgraph ".($q==0 ? 'selected_grey_graph_cell' : 'grey_graph_cell')."' id='LinkID".$graph_id."_".$q."'><a href='#' onClick=\"ToggleSpan('".$graph_id."', '".$q."', '".$graph_count."'); return false;\">"._QXZ("$link_name")."</a></th>";
	}
	$graphCanvas.="	</tr>\n";	
}

$graphCanvas.="	<tr><td colspan='".$graph_count."'>\n";
for ($q=0; $q<$graph_count; $q++) {
	
	# SET GRAPH size FOR READABILITY	
	if (file_exists('options.php'))
		{
		require('options.php');
		$init_height=$graph_canvas_size;  # Chart has a minimum height/width of 600 by default
		}
	else
		{
		$init_height=600;
		}

	$entry_width=($init_width<20 ? 20 : ($init_width>50 ? 50 : $init_width));
	$init_width=(($graph_count*$entry_width)<$init_height ? $init_height : ($graph_count*$entry_width));
	
	if ($override_height) {$init_height=$override_height;}
	if ($override_width) {$init_width=$override_width;}

	$graph_info=explode("|", $graph_array[$q]);
	$dataset_name=$graph_info[0];
	$dataset_type=$graph_info[3];

	switch($dataset_type) {
		case "time":
			$chart_options="options: {tooltips: {callbacks: {label: function(tooltipItem, data) {var value = Math.round(data.datasets[0].data[tooltipItem.index]); return value.toHHMMSS();}}}, legend: { display: false }},";
			break;
		case "percent":
			$chart_options="options: {tooltips: {callbacks: {label: function(tooltipItem, data) {var value = data.datasets[0].data[tooltipItem.index]; return value + '%';}}}, legend: { display: false }},";
			break;
		default:
			$chart_options="options: { legend: { display: false }},";
			break;
	}

	
	$graphCanvas.="<span id='SpanID".$graph_id."_".$q."' style='display:".($q==0 ? 'block' : 'none').";'>";
	$graphCanvas.="<table width='$init_width'>";
	$graphCanvas.=$graph_totals_array[$q];
	$graphCanvas.="<tr>\n";
/*
	$graphCanvas.="<td align='center'><input type='button' value='PIE' onClick=\"if(GraphID".$graph_id."_".$q."!=null){GraphID".$graph_id."_".$q.".destroy();}; prepChart('pie', $graph_id, $q, $dataset_name); var GraphID".$graph_id."_".$q." = new Chart(main_ctx, $dataset_name);\"></td>\n";
 	$graphCanvas.="<td align='center'><input type='button' value='LINE' onClick=\"if(GraphID".$graph_id."_".$q."!=null){GraphID".$graph_id."_".$q.".destroy();}; prepChart('line', $graph_id, $q, $dataset_name); var GraphID".$graph_id."_".$q." = new Chart(main_ctx, $dataset_name);\"></td>\n";
	$graphCanvas.="<td align='center'><input type='button' value='BAR'  onClick=\"if(GraphID".$graph_id."_".$q."!=null){GraphID".$graph_id."_".$q.".destroy();}; prepChart('bar', $graph_id, $q, $dataset_name); var GraphID".$graph_id."_".$q." = new Chart(main_ctx, $dataset_name);\"></td>\n";
	$graphCanvas.="<td align='center'><input type='button' value='POLAR' onClick=\"if(GraphID".$graph_id."_".$q."!=null){GraphID".$graph_id."_".$q.".destroy();}; prepChart('polarArea', $graph_id, $q, $dataset_name); var GraphID".$graph_id."_".$q." = new Chart(main_ctx, $dataset_name);\"></td>\n";

var h = Math.floor(d / 3600); var m = Math.floor(d % 3600 / 60); var s = Math.floor(d % 3600 % 60); return ((h > 0 ? h + ":" + (m < 10 ? "0" : "") : "") + m + ":" + (s < 10 ? "0" : "") + s);

      type: 'pie', options: {tooltips: {callbacks: {label: function(tooltipItem, data) {var value = data.datasets[0].data[tooltipItem.index]; var label = data.labels[tooltipItem.index]; var percentage = Math.round(value / totalSessions * 100); return label + ' ' + percentage + '%';}}},}, data: topBrowsers
 */
	$graphCanvas.="<td align='center' width='20%'><input type='button' class='ChartJSButton' value='"._QXZ("PIE")."'   onClick=\"if(GraphID".$graph_id."_".$q."!=null){GraphID".$graph_id."_".$q.".destroy();}; var main_ctx = document.getElementById('CanvasID".$graph_id."_".$q."'); GraphID".$graph_id."_".$q." = new Chart(main_ctx, {type: 'pie', options: {tooltips: {callbacks: {label: function(tooltipItem, data) {var value = data.datasets[0].data[tooltipItem.index]; var label = data.labels[tooltipItem.index]; var percentage = Math.round(value / ".$graph_totals_rawdata[$q]." * 10000)/100; return label + ' ' + percentage + '%';}}}, legend: { display: false }}, data: $dataset_name});\"></td>\n";
	$graphCanvas.="<td align='center' width='20%'><input type='button' class='ChartJSButton' value='"._QXZ("LINE")."'  onClick=\"if(GraphID".$graph_id."_".$q."!=null){GraphID".$graph_id."_".$q.".destroy(); var main_ctx = document.getElementById('CanvasID".$graph_id."_".$q."'); GraphID".$graph_id."_".$q." = new Chart(main_ctx, {type: 'line', $chart_options data: $dataset_name});}\"></td>\n";
	$graphCanvas.="<td align='center' width='20%'><input type='button' class='ChartJSButton' value='"._QXZ("HORIZ. BAR")."'   onClick=\"if(GraphID".$graph_id."_".$q."!=null){GraphID".$graph_id."_".$q.".destroy();}; var main_ctx = document.getElementById('CanvasID".$graph_id."_".$q."'); GraphID".$graph_id."_".$q." = new Chart(main_ctx, {type: 'horizontalBar', $chart_options data: $dataset_name});\"></td>\n";
	$graphCanvas.="<td align='center' width='20%'><input type='button' class='ChartJSButton' value='"._QXZ("VERT. BAR")."'   onClick=\"if(GraphID".$graph_id."_".$q."!=null){GraphID".$graph_id."_".$q.".destroy();}; var main_ctx = document.getElementById('CanvasID".$graph_id."_".$q."'); GraphID".$graph_id."_".$q." = new Chart(main_ctx, {type: 'bar', $chart_options data: $dataset_name});\"></td>\n";
	$graphCanvas.="<td align='center' width='20%'><input type='button' class='ChartJSButton' value='"._QXZ("POLAR")."' onClick=\"if(GraphID".$graph_id."_".$q."!=null){GraphID".$graph_id."_".$q.".destroy();}; var main_ctx = document.getElementById('CanvasID".$graph_id."_".$q."'); GraphID".$graph_id."_".$q." = new Chart(main_ctx, {type: 'polarArea', $chart_options data: $dataset_name});\"></td>\n";

	$graphCanvas.="</tr>\n";
	$graphCanvas.="<tr height='$init_height'>";
	$graphCanvas.="<td id='chartContainer' colspan=5>";
	$graphCanvas.="<canvas id=\"CanvasID".$graph_id."_".$q."\" width=\"".$init_width."\" height=\"".$init_height."\"></canvas></td>";
	$graphCanvas.="<td width='100' id='LegendID".$graph_id."_".$q."' rowspan=2></td>";
	$graphCanvas.="</tr>";
	$graphCanvas.="</table>";
	$graphCanvas.="</span>";
}
$graphCanvas.="</td></tr>\n";
$graphCanvas.="</table>\n";
$graphCanvas.="</form>\n\n";

$HTML_graph_head="<script language=\"Javascript\">\n";
$HTML_graph_head.="// global chart names\n";
for ($q=0; $q<$graph_count; $q++) {
        $HTML_graph_head.="var GraphID".$graph_id."_".$q."=null;\n";
}
$HTML_graph_head.="</script>\n";

$allowed_graph="ALL"; 
$graph_count=1;
?>
