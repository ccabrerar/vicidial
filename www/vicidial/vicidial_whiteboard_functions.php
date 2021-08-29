<?php
header('Content-Type: application/javascript');
require("functions.php");

if (isset($_GET["mobile"]))				{$mobile=$_GET["mobile"];}
	elseif (isset($_POST["mobile"]))		{$mobile=$_POST["mobile"];}

$mobile=preg_replace('/[^0-9\p{L}]/u','',$mobile);
?>
// vicidial_whiteboard_functions.php
// 
// Copyright (C) 2020  Matt Florell <vicidial@gmail.com>, Joe Johnson <freewermadmin@gmail.com>    LICENSE: AGPLv2
//
// Javascript file used in AST_rt_whiteboard_reports.php and AST_rt_whiteboard_report_mobile.php
//
// 171027-2352 - First build
// 190226-1645 - Added mobile variable for mobile versions of reports
// 190307-0142 - Change auto-resize on load if device is mobile, removed debug
// 200309-1819 - Modifications for display formatting
//

var main_canvas = document.getElementById("MainReportCanvas");
var size = {
  width: window.innerWidth || document.body.clientWidth,
  height: window.innerHeight || document.body.clientHeight
}
<?php if (!$mobile) { ?>
main_canvas.width=size.width-220;
main_canvas.height=size.height-220;
<?php } else { ?>
main_canvas.width=size.width-220;
main_canvas.height=size.height-300;
<?php } ?>

// global chart names
var MainGraph=null;
var ChartsHidden=null;
var LabelArray_holder=new Array();
var CallCountArray_holder=new Array();
var SaleCountArray_holder=new Array();
var TotalCallCountArray_holder=new Array();
var TotalSaleCountArray_holder=new Array();
var TimeArray_holder=new Array();
var ConvRateArray_holder=new Array();
var CPHArray_holder=new Array();
var SPHArray_holder=new Array();
var TPAArray_holder=new Array();
var TGArray_holder=new Array();

function StartRefresh(refresh_rate) {
		document.getElementById("loading_display").style.display="block";
		if (!refresh_rate) {refresh_rate=30; RefreshReportWindow();} 
		document.getElementById("query_date2").value=document.getElementById("query_date").value;
		// document.getElementById("end_date2").value=document.getElementById("end_date").value;
		document.getElementById("query_time2").value=document.getElementById("query_time").value;
		// document.getElementById("end_time2").value=document.getElementById("end_time").value;
		document.getElementById("hourly_display2").value=document.getElementById("hourly_display").value;
		ToggleVisibility('report_control_panel');
		ToggleVisibility('report_display_panel');
		ChartsHidden=null;
		rInt=window.setInterval(function() {RefreshReportWindow()}, (refresh_rate*1000));
}
function StopRefresh() {
		MainGraph.clear();
		document.getElementById("parameters_span").innerHTML='';
		ToggleVisibility('report_control_panel');
		ToggleVisibility('report_display_panel');
		clearInterval(rInt);
}

function ToggleVisibility(span_name) {
	var span_vis = document.getElementById(span_name).style;
	if (span_vis.display=='none') { span_vis.display = 'block'; } else { span_vis.display = 'none'; }
}

function sortFunction(a, b) {
	if (a[0] === b[0]) 
		{
		return 0;
		} 
	else 
		{
		return (a[0] > b[0]) ? -1 : 1;
		}
}

function HighlightRelatedFields(report_type) {

	switch(report_type) {
		case "agent_performance_total":
		case "agent_performance_rates":
		case "team_performance_total":
		case "team_performance_rates":
			var related_fields = ["campaigns", "user_groups", "users", "groups", "target_per_agent", "query_date", "query_time", "status_flags"];
			break;
		case "floor_performance_total":
		case "floor_performance_rates":
			var related_fields = ["campaigns", "target_gross", "query_date", "query_time", "hourly_display", "status_flags"];
			break;
		case "ingroup_performance_total":
		case "ingroup_performance_rates":
			var related_fields = ["campaigns", "groups", "target_per_agent", "query_date", "query_time", "status_flags"];
			break;
		case "did_performance_total":
		case "did_performance_rates":
			var related_fields = ["dids", "groups", "target_per_agent", "query_date", "query_time", "status_flags"];
			break;
		case "status_performance_total":
			var related_fields = ["campaigns", "groups", "users", "query_date", "query_time", "status_flags"];
			break;
	}

	var form_elements = document.getElementById("vicidial_report").elements;
	for (var i=0, element; element=form_elements[i++];) {
		if (element.type=="text" || element.type=="select-one" || element.type=="select-multiple") {
			element.className='form_field sm_shadow round_corners';
			for (var j=0; j<related_fields.length; j++) {
				if (related_fields[j]==element.id) {
					element.className='required_field sm_shadow round_corners';
				}
			}
		}
	}
}

<?php if (!$mobile) { ?>
document.getElementById("goto_reports").onclick = function() {
	location.href = "./admin.php?ADD=999999";
};
<?php } ?>
document.getElementById("adjust_report").onclick = function() {
	document.getElementById("query_date").value=document.getElementById("query_date2").value;
	// document.getElementById("end_date").value=document.getElementById("end_date2").value;
	document.getElementById("query_time").value=document.getElementById("query_time2").value;
	// document.getElementById("end_time").value=document.getElementById("end_time2").value;
	document.getElementById("hourly_display").value=document.getElementById("hourly_display2").value;
	clearInterval(rInt);
	var new_refresh_rate=document.getElementById("refresh_rate").value;
	rInt=window.setInterval(function() {RefreshReportWindow()}, (new_refresh_rate*1000));
};


function RefreshReportWindow() {
	var query_date=document.getElementById("query_date").value;
	// var end_date=document.getElementById("end_date").value;
	var query_time=document.getElementById("query_time").value;
	// var end_time=document.getElementById("end_time").value;
	var hourly_display=document.getElementById("hourly_display").value;
	var target_gross=document.getElementById("target_gross").value;
	var target_per_agent=document.getElementById("target_per_agent").value;
	var rpt_field = document.getElementById("report_type");
	var report_type = rpt_field.options[rpt_field.selectedIndex].value;
	var report_type_text = rpt_field.options[rpt_field.selectedIndex].text;

	if (!target_per_agent) {target_per_agent=0;}
	if (!target_gross) {target_gross=0;}
	var size = {
	  width: window.innerWidth || document.body.clientWidth,
	  height: window.innerHeight || document.body.clientHeight
	}
	// main_canvas.width=size.width-40;
	// main_canvas.height=size.height-60;


	var xmlhttp=false;
	if (!xmlhttp && typeof XMLHttpRequest!='undefined')
		{
		xmlhttp = new XMLHttpRequest();
		}
	if (xmlhttp) 
		{ 
		var rpt_query = "&rpt_type="+report_type+"&query_date="+query_date+"&query_time="+query_time+"&target_gross="+target_gross+"&target_per_agent="+target_per_agent+"&hourly_display="+hourly_display;

        var InvForm = document.forms[0];
        var x = 0;
		var query_date_str="<?php echo _QXZ("Start date"); ?>: "+query_date+" "+query_time;
		if (hourly_display && hourly_display>0)
			{
			query_date_str="<?php echo _QXZ("Displaying last"); ?> "+hourly_display+" <?php echo _QXZ("hours"); ?>";
			}

		var campaign_parameters_str="";
        for (x=0;x<InvForm.campaigns.length;x++)
			{
			if (InvForm.campaigns[x].selected)
				{
				rpt_query +="&campaigns[]="+InvForm.campaigns[x].value;
				campaign_parameters_str+=InvForm.campaigns[x].value+", ";
				}
			}

		var user_groups_parameters_str="";
        for (x=0;x<InvForm.user_groups.length;x++)
			{
			if (InvForm.user_groups[x].selected)
				{
				rpt_query +="&user_groups[]="+InvForm.user_groups[x].value;
				user_groups_parameters_str+=InvForm.user_groups[x].value+", ";
				}
			}

		var users_parameters_str="";
        for (x=0;x<InvForm.users.length;x++)
			{
			if (InvForm.users[x].selected)
				{
				rpt_query +="&users[]="+InvForm.users[x].value;
				users_parameters_str+=InvForm.users[x].value+", ";
				}
			}

		var groups_parameters_str="";
        for (x=0;x<InvForm.groups.length;x++)
			{
			if (InvForm.groups[x].selected)
				{
				rpt_query +="&groups[]="+InvForm.groups[x].value;
				groups_parameters_str+=InvForm.groups[x].value+", ";
				}
			}

		var did_parameters_str="";
        for (x=0;x<InvForm.dids.length;x++)
			{
			if (InvForm.dids[x].selected)
				{
				rpt_query +="&dids[]="+InvForm.dids[x].value;
				did_parameters_str+=InvForm.dids[x].text+", ";
				}
			}

		var status_flags_parameters_str="";
        for (x=0;x<InvForm.status_flags.length;x++)
			{
			if (InvForm.status_flags[x].selected)
				{
				rpt_query +="&status_flags[]="+InvForm.status_flags[x].value;
				status_flags_parameters_str+=InvForm.status_flags[x].text+", ";
				}
			}

		var regRPTtype = new RegExp("floor_performance","g");
		var regRPTtotal = new RegExp("total","g");
		var regRPTrates = new RegExp("rates","g");
		var regRPTcomma = new RegExp(", $","g");
		var regRPTpipe = new RegExp("|$","g");
		var regRPTdash = new RegExp("-","g");
		var regRPTall = new RegExp("--ALL--","g");
		var regRPTflag = new RegExp("ALL FLAGS","g");

		var CPstr=campaign_parameters_str.replace(regRPTcomma, '');
		var UGPstr=user_groups_parameters_str.replace(regRPTcomma, '');
		var USPstr=users_parameters_str.replace(regRPTcomma, '');
		var GPstr=groups_parameters_str.replace(regRPTcomma, '');
		var DPstr=did_parameters_str.replace(regRPTcomma, '');
		if (status_flags_parameters_str.match(regRPTflag)) {status_flags_parameters_str="<?php echo "ALL FLAGS"; ?>";}
		var SFstr=status_flags_parameters_str.replace(regRPTcomma, '');

		var CPstr=CPstr.replace(regRPTall, '<?php echo _QXZ("ALL CAMPAIGNS"); ?>');
		var UGPstr=UGPstr.replace(regRPTall, '<?php echo _QXZ("ALL USER GROUPS"); ?>');
		var USPstr=USPstr.replace(regRPTall, '<?php echo _QXZ("ALL USERS"); ?>');
		var GPstr=GPstr.replace(regRPTall, '<?php echo _QXZ("ALL IN-GROUPS"); ?>');
		var DPstr=DPstr.replace(regRPTdash, '');
		
		
		var report_parameters="<div class='embossed border2px round_corners sm_shadow alt_row1'>";
		report_parameters+="<?php echo _QXZ("Report Type"); ?>: "+report_type_text+"<BR>\n";
		report_parameters+=query_date_str+"<BR>\n";
		if (campaign_parameters_str.length>0) {report_parameters+="<?php echo _QXZ("Campaigns"); ?>: "+CPstr+"<BR>\n";}
		if (user_groups_parameters_str.length>0) {report_parameters+="<?php echo _QXZ("User groups"); ?>: "+UGPstr+"<BR>\n";}
		if (users_parameters_str.length>0) {report_parameters+="<?php echo _QXZ("Users"); ?>: "+USPstr+"<BR>\n";}
		if (groups_parameters_str.length>0) {report_parameters+="<?php echo _QXZ("In-groups"); ?>: "+GPstr+"<BR>\n";}
		if (did_parameters_str.length>0) {report_parameters+="<?php echo _QXZ("DIDs"); ?>: "+DPstr+"<BR>\n";}
		if (status_flags_parameters_str.length>0) {report_parameters+="<?php echo _QXZ("Status flags"); ?>: "+SFstr+"<BR>\n";}
		report_parameters+="</div>\n";

		document.getElementById("parameters_span").innerHTML=report_parameters;

		xmlhttp.open('POST', 'whiteboard_reports.php'); 
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(rpt_query); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				response_txt = null;
				response_txt = xmlhttp.responseText;
				// document.getElementById("report_display_panel").innerHTML=report_type+" - "+response_txt; return 0;

				var report_result_array=response_txt.split("\n");
				var arrayLength = report_result_array.length;

				// var main_canvas = document.getElementById("MainReportCanvas");

				// Define all arrays here - don't care if it's long - easier to deal with for now.
				var LabelArray=new Array();
				var CallCountArray=new Array();
				var SaleCountArray=new Array();
				var TotalCallCountArray=new Array();
				var TotalSaleCountArray=new Array();
				var TimeArray=new Array();
				var ConvRateArray=new Array();
				var CPHArray=new Array();
				var Top10Array=new Array();
				var SPHArray=new Array();
				var TPAArray=new Array(); // Target per agent
				var TGArray=new Array(); // Target per agent

				var TotalCallCount=0;
				var TotalSaleCount=0;
				var TotalTime=0;
				var TotalCPH=0;
				var TotalSPH=0;
				var Top10Entries=0;
				var PrevTop10Value=0;
				var Top10Limit="off";
				var alert_text="";

				for (var i = 0; i < arrayLength; i++) 
					{
					var agent_array=report_result_array[i].split("|");
					var agent_array_length=agent_array.length;
					if (report_type.match(regRPTtype))
						{
						LabelArray[i]=agent_array[0];
						}
					else
						{
						var agent_label=agent_array[0];
						if (typeof agent_array[1] !== "undefined") {agent_label+=" - "+agent_array[1];}
						LabelArray[i]=agent_label;
						}
					
					CallCountArray[i]=agent_array[2]; // TotalCallCount+=parseInt(agent_array[2]);
					SaleCountArray[i]=agent_array[3]; // TotalSaleCount+=parseInt(agent_array[3]);

					if (report_type=="status_performance_total")
						{
						Top10Array[i]=[parseInt(agent_array[2]),agent_array[0],agent_array[1]];
						}
					else if (report_type.match(regRPTrates))
						{
						Top10Array[i]=[agent_array[5],agent_array[0],agent_array[1]];
						}
					else
						{
						Top10Array[i]=[parseInt(agent_array[3]),agent_array[0],agent_array[1]];
						}

					TimeArray[i]=agent_array[4]; 
					if (report_type.match(regRPTtype) || report_type=="status_performance_total") // If the cumulative floor report, use the last entries from the TotalCall and TotalSale above (m
						{
						TotalTime=parseInt(agent_array[4]);
						}
					else 
						{
						TotalTime+=parseInt(agent_array[4]);
						}
					ConvRateArray[i]=agent_array[5];
					CPHArray[i]=agent_array[6];
					SPHArray[i]=agent_array[7];
					TotalCallCountArray[i]=agent_array[8]; TotalCallCount=agent_array[8];
					TotalSaleCountArray[i]=agent_array[9]; TotalSaleCount=agent_array[9];
					alert_text=agent_array[10];
					TPAArray[i]=target_per_agent;
					TGArray[i]=target_gross;
					}

					Top10Array.sort(sortFunction);
				}

				var Top10HTMLChart="<div class='embossed border2px round_corners sm_shadow alt_row1' align='center'><table cellpadding='8' valign='top'>";
				Top10HTMLChart+="<tr>";
				Top10HTMLChart+="<th colspan='3'><font size='+2'><?php echo _QXZ("TOP 10 PERFORMERS"); ?></font></th>";
				Top10HTMLChart+="<td align='right'><input type='button' class='red_btn sm_shadow' style='width:20px;height:20px' value=' X' onClick=\"ToggleVisibility('top_10_display'); ToggleVisibility('graph_display');\"></td>";
				Top10HTMLChart+="</tr>";
				Top10HTMLChart+="<tr>";
				Top10HTMLChart+="<th><?php echo _QXZ("RANK"); ?></th>";
				Top10HTMLChart+="<th><?php echo _QXZ("NAME"); ?></th>";
				Top10HTMLChart+="<th></th>";
				Top10HTMLChart+="</tr>";

				var breakloop="no";
				var prev_ranking=0;
				for (var i = 0; i < arrayLength; i++) 
					{
					var trClass="std_row"+((i%2)+1);
					if (i>0 && Top10Array[i][0]==Top10Array[(i-1)][0])
					{
						var ranking=prev_ranking+" (tie)";
					}
					else
					{
						var ranking=(i+1);
						prev_ranking=ranking;
						if (i>=10) {breakloop="yes";}
					}
					if (breakloop=="no")
						{
						Top10HTMLChart+="<tr class='"+trClass+"'>";
						Top10HTMLChart+="<td align='right'><B>"+ranking+".</B></td>";
						Top10HTMLChart+="<td align='left'><B>"+Top10Array[i][1];
						if (typeof Top10Array[i][2] !== "undefined" && report_type!="status_performance_total") {Top10HTMLChart+=" - "+Top10Array[i][2];}
						Top10HTMLChart+="</B></td>";
						Top10HTMLChart+="<td align='center' colspan='2'><B>"+Top10Array[i][0];
						if (report_type.match(regRPTrates)) {Top10HTMLChart+=" %";} // Irritating, but necessary.
						Top10HTMLChart+="</B></td>";
						Top10HTMLChart+="</tr>";
						}
					else 
						{
						i=arrayLength;
						}
					}
				Top10HTMLChart+="<tr>";
				Top10HTMLChart+="<th colspan='4'><input type='button' class='green_btn sm_shadow round_corners' style='width:250px' value='<?php echo _QXZ("SHOW PERFORMANCE CHART"); ?>' onClick=\"ToggleVisibility('top_10_display'); ToggleVisibility('graph_display');\"></th>";
				Top10HTMLChart+="</tr>";
				Top10HTMLChart+="</table><BR><BR></div>";
				document.getElementById("top_10_display").innerHTML=Top10HTMLChart;

				var TotalCPH=TotalCallCount/(TotalTime/3600);
				var TotalSPH=TotalSaleCount/(TotalTime/3600);
				var TotalConvRate=(TotalSaleCount/TotalCallCount)*100;

				var TotalHours = Math.floor(TotalTime / 3600);
				TotalTime = TotalTime - TotalHours * 3600;
				var TotalMinutes = Math.floor(TotalTime / 60);
				var TotalSeconds = TotalTime - TotalMinutes * 60;
				TotalMinutes="0"+TotalMinutes;
				TotalSeconds="0"+TotalSeconds;

				if (response_txt && response_txt=="<?php echo _QXZ("REPORT RETURNED NO RESULTS"); ?>") {
					document.getElementById("total_calls_div").innerHTML="** <?php echo _QXZ("NO RESULTS"); ?> **";
					document.getElementById("total_sales_div").innerHTML="** <?php echo _QXZ("NO RESULTS"); ?> **";
					document.getElementById("total_time_div").innerHTML="** <?php echo _QXZ("NO RESULTS"); ?> **";
					document.getElementById("total_cph_div").innerHTML="** <?php echo _QXZ("NO RESULTS"); ?> **";
					document.getElementById("total_sph_div").innerHTML="** <?php echo _QXZ("NO RESULTS"); ?> **";
					document.getElementById("total_conv_div").innerHTML="** <?php echo _QXZ("NO RESULTS"); ?> **";
				} else {
					document.getElementById("total_calls_div").innerHTML=TotalCallCount;
					document.getElementById("total_sales_div").innerHTML=TotalSaleCount;
					document.getElementById("total_time_div").innerHTML=TotalHours+":"+TotalMinutes.slice(-2)+":"+TotalSeconds.slice(-2);
					document.getElementById("total_cph_div").innerHTML=Math.round(TotalCPH*100)/100;
					document.getElementById("total_sph_div").innerHTML=Math.round(TotalSPH*100)/100;
					document.getElementById("total_conv_div").innerHTML=Math.round(TotalConvRate*100)/100+"%";
				}
				// document.getElementById("totals_span").innerHTML='';
				// document.getElementById("parameters_span").innerHTML=response_txt;


				var label_title=query_date+" "+query_time;
				var hide_total="true";
				var hide_rates="true";
				var graph_type='bar';
				
				if (LabelArray) { // Seems to return 3 times above.  Dunno why
					
					if (report_type.match(regRPTtotal)) {hide_total="false";} 
					if (report_type.match(regRPTrates)) {hide_rates="false";}
					if (report_type.match(regRPTtype)) {graph_type='line';}
					if (LabelArray.length!=LabelArray_holder.length) {
						if (MainGraph) 
							{							
							MainGraph.destroy();
							}

						if (report_type.match(regRPTtype))
							{
							MainGraph=new Chart(main_canvas, {
								type: graph_type,
								// options: { elements: { point: { radius: 0 } } }, // no dots
								data: {
									datasets: [
										{label: '<?php echo _QXZ("Total calls"); ?>', data: TotalCallCountArray, backgroundColor: "rgba(153,153,255,0.5)", hidden: hide_total}, // dataset 0, see below
										{label: '<?php echo _QXZ("Total sales"); ?>', data: TotalSaleCountArray, backgroundColor: "rgba(255,153,153,0.5)", hidden: hide_total}, // dataset 1, see below
										{label: '<?php echo _QXZ("Calls per hour"); ?>', data: CPHArray, backgroundColor: "rgba(255,255,0,0.5)", hidden: hide_rates}, // dataset 2, see below
										{label: '<?php echo _QXZ("Sales per hour"); ?>', data: SPHArray, backgroundColor: "rgba(153,255,153,0.5)", hidden: hide_rates}, // dataset 3, see below
										{label: '<?php echo _QXZ("Conversion rate %"); ?>', data: ConvRateArray, backgroundColor: "rgba(255,153,255,0.5)", hidden: hide_rates}, // dataset 4, see below
										{label: '<?php echo _QXZ("Target gross"); ?>', data: TGArray, type: 'line', pointRadius: 0, backgroundColor: "rgba(153,255,255,0.5)", hidden: hide_total} // dataset 5, see below
									],
									labels: LabelArray
								}
							});
							if (report_type.match(regRPTtotal)) 
								{
								if (!ChartsHidden)
									{
									ChartsHidden="false|false|true|true|true|false";
									}
								var hidden_array=ChartsHidden.split("|");
								MainGraph.data.datasets[0].hidden=(hidden_array[0] == 'true');
								MainGraph.data.datasets[1].hidden=(hidden_array[1] == 'true');
								MainGraph.data.datasets[2].hidden=(hidden_array[2] == 'true');
								MainGraph.data.datasets[3].hidden=(hidden_array[3] == 'true');
								MainGraph.data.datasets[4].hidden=(hidden_array[4] == 'true');
								MainGraph.data.datasets[5].hidden=(hidden_array[5] == 'true');
	
								MainGraph.update();
								} 
							if (report_type.match(regRPTrates)) 
								{
								if (!ChartsHidden)
									{
									ChartsHidden="true|true|false|false|false|true";
									}
								var hidden_array=ChartsHidden.split("|");
								MainGraph.data.datasets[0].hidden=(hidden_array[0] == 'true');
								MainGraph.data.datasets[1].hidden=(hidden_array[1] == 'true');
								MainGraph.data.datasets[2].hidden=(hidden_array[2] == 'true');
								MainGraph.data.datasets[3].hidden=(hidden_array[3] == 'true');
								MainGraph.data.datasets[4].hidden=(hidden_array[4] == 'true');
								MainGraph.data.datasets[5].hidden=(hidden_array[5] == 'true');
								
								MainGraph.update();
								}
							}
						else
							{
							MainGraph=new Chart(main_canvas, {
								type: 'bar',
								// options: { elements: { point: { radius: 0 } } }, // no dots
								data: {
									datasets: [
										{label: '<?php echo _QXZ("Total calls"); ?>', data: CallCountArray, backgroundColor: "rgba(153,153,255,0.5)", hidden: false}, // dataset 0, see below
										{label: '<?php echo _QXZ("Total sales"); ?>', data: SaleCountArray, backgroundColor: "rgba(255,153,153,0.5)", hidden: false}, // dataset 1, see below
										{label: '<?php echo _QXZ("Sales per hour"); ?>', data: SPHArray, backgroundColor: "rgba(153,255,153,0.5)", hidden: false}, // dataset 2, see below
										{label: '<?php echo _QXZ("Conversion rate %"); ?>', data: ConvRateArray, backgroundColor: "rgba(255,255,0,0.5)", hidden: false}, // dataset 3, see below
										{label: '<?php echo _QXZ("Target per unit"); ?>', data: TPAArray, type: 'line', pointRadius: 0, backgroundColor: "rgba(153,255,255,0.5)", hidden: false} // dataset 4, see below
									],
									labels: LabelArray
								},
								options: {
									scales:{
										xAxes: [{
											stacked: true
										}],
									}
								}
							});
							if (report_type.match(regRPTtotal)) 
								{
								if (!ChartsHidden)
									{
									ChartsHidden="false|false|true|true|false";
									}
								var hidden_array=ChartsHidden.split("|");
								MainGraph.data.datasets[0].hidden=(hidden_array[0] == 'true');
								MainGraph.data.datasets[1].hidden=(hidden_array[1] == 'true');
								MainGraph.data.datasets[2].hidden=(hidden_array[2] == 'true');
								MainGraph.data.datasets[3].hidden=(hidden_array[3] == 'true');
								MainGraph.data.datasets[4].hidden=(hidden_array[4] == 'true');
					
								MainGraph.update();
								} 
							if (report_type.match(regRPTrates)) 
								{
								if (!ChartsHidden)
									{
									ChartsHidden="true|true|false|false|true";
									}
								var hidden_array=ChartsHidden.split("|");
								MainGraph.data.datasets[0].hidden=(hidden_array[0] == 'true');
								MainGraph.data.datasets[1].hidden=(hidden_array[1] == 'true');
								MainGraph.data.datasets[2].hidden=(hidden_array[2] == 'true');
								MainGraph.data.datasets[3].hidden=(hidden_array[3] == 'true');
								MainGraph.data.datasets[4].hidden=(hidden_array[4] == 'true');
					
								MainGraph.update();
								}
							}
					} else {
						var update_graph_flag=0;
						for (var j=0; j<LabelArray.length; j++) {
							if (report_type.match(regRPTtype))
								{
								if (TotalCallCountArray[j]!=TotalCallCountArray_holder[j]) 
									{
									MainGraph.data.datasets[0].data[j]=TotalCallCountArray[j];
									update_graph_flag++;
									}
								if (TotalSaleCountArray[j]!=TotalSaleCountArray_holder[j]) 
									{
									MainGraph.data.datasets[1].data[j]=TotalSaleCountArray[j];
									update_graph_flag++;
									}
								if (CPHArray[j]!=CPHArray_holder[j]) 
									{
									MainGraph.data.datasets[2].data[j]=CPHArray[j];
									update_graph_flag++;
									}
								if (SPHArray[j]!=SPHArray_holder[j]) 
									{
									MainGraph.data.datasets[3].data[j]=SPHArray[j];
									update_graph_flag++;
									}
								if (ConvRateArray[j]!=ConvRateArray_holder[j]) 
									{
									MainGraph.data.datasets[4].data[j]=ConvRateArray[j];
									update_graph_flag++;
									}
								if (TGArray[j]!=TGArray_holder[j]) 
									{
									MainGraph.data.datasets[5].data[j]=TGArray[j];
									update_graph_flag++;
									}
								}
							else 
								{
								if (CallCountArray[j]!=CallCountArray_holder[j]) 
									{
									MainGraph.data.datasets[0].data[j]=CallCountArray[j];
									update_graph_flag++;
									}
								if (SaleCountArray[j]!=SaleCountArray_holder[j]) 
									{
									MainGraph.data.datasets[1].data[j]=SaleCountArray[j];
									update_graph_flag++;
									}
								if (SPHArray[j]!=SPHArray_holder[j]) 
									{
									MainGraph.data.datasets[2].data[j]=SPHArray[j];
									update_graph_flag++;
									}
								if (ConvRateArray[j]!=ConvRateArray_holder[j]) 
									{
									MainGraph.data.datasets[3].data[j]=ConvRateArray[j];
									update_graph_flag++;
									}
								if (TPAArray[j]!=TPAArray_holder[j]) 
									{
									MainGraph.data.datasets[4].data[j]=TPAArray[j];
									update_graph_flag++;
									}
								}
						}
						
						if (update_graph_flag>0) 
							{
							MainGraph.update();
							}
					}
					var hidden_charts="";
					for (var j=0; j<MainGraph.data.datasets.length; j++) 
						{
						hidden_charts+=(!MainGraph.isDatasetVisible(j))+"|";
						}
					ChartsHidden=hidden_charts.replace(regRPTpipe, '');
					document.getElementById("hidden_display").value=ChartsHidden;

					LabelArray_holder=LabelArray;
					CallCountArray_holder=CallCountArray;
					SaleCountArray_holder=SaleCountArray;
					TotalCallCountArray_holder=TotalCallCountArray;
					TotalSaleCountArray_holder=TotalSaleCountArray;
					TimeArray_holder=TimeArray;
					ConvRateArray_holder=ConvRateArray;
					CPHArray_holder=CPHArray;
					SPHArray_holder=SPHArray;
					TPAArray_holder=TPAArray;
					TGArray_holder=TGArray;
					// document.getElementById("canvas_message").innerHTML="URL: "+rpt_query+"<BR>\n";
					// document.getElementById("canvas_message").innerHTML+="Hide total: "+hide_total+"<BR>\n";
					// document.getElementById("canvas_message").innerHTML+="Hide rates: "+hide_rates+"<BR>\n";
					// document.getElementById("canvas_message").innerHTML+="Graph type: "+graph_type+"<BR>\n";
					// document.getElementById("canvas_message").innerHTML+="Target per agent: "+graph_type+"<BR>\n";
					// document.getElementById("canvas_message").innerHTML+="Result: "+response_txt+"<BR>\n";
				}
				// MainGraph.update();
			}

	document.getElementById("loading_display").style.display="none";


/*
myLineChart.destroy();
myLineChart.data.datasets[0].data[2] = 50; // Would update the first dataset's value of 'March' to be 50
myLineChart.update(); // Calling update now animates the position of March from 90 to 50.

var mixedChart = new Chart(ctx, {
  type: 'bar',
  data: {
    datasets: [{
          label: 'Bar Dataset',
          data: [10, 20, 30, 40]
        }, {
          label: 'Line Dataset',
          data: [50, 50, 50, 50],

          // Changes this dataset to become a line
          type: 'line'
        }],
    labels: ['January', 'February', 'March', 'April']
  },
  options: options
});
*/
		delete xmlhttp;
		}
}