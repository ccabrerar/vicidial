var IE = document.all?true:false
// If NS -- that is, !IE -- then set up for mouse capture
if (!IE) document.captureEvents(Event.MOUSEMOVE)

// LINK BUTTON FUNCTION
function GenerateLink(e) {
	document.getElementById("DisplayLinkDiv").innerHTML="";

	var form_elements = document.forms[0].elements;
	var link_str=window.location.href+"?";
	for (i=0; i<form_elements.length; i++) {
		if (form_elements[i].type!="button" && form_elements[i].type!="submit" && form_elements[i].value!="")
			{
			link_str+=form_elements[i].name+"="+encodeURIComponent(form_elements[i].value)+"&";
			}
	}
	link_str = link_str.substring(0, link_str.length - 1);

	var LinkText="<table style='width:25vw' id='details_table' align='center' valign='middle'>";
	LinkText+="<tr><td align='right' class='export_row_cell'><a onClick='javascript:document.getElementById(\"DisplayLinkDiv\").style.display=\"none\"'><h2 class='rpt_header'>[X]</h2></a></td></tr>";
	LinkText+="<tr class='export_row'><td align='left' class='export_row_cell'><B>Link to this page:</B><BR><BR>";
	// LinkText+="<hr style='height:2px;border-width:0;color:#ddd;background-color:#ddd;margin-bottom: 2em;'>";
	LinkText+="<a href='"+link_str+"'>Right click and select 'Copy link address'</a>";
	LinkText+="</td></tr>";
	LinkText+="</table>";

	document.getElementById("DisplayLinkDiv").innerHTML=LinkText;

	if (IE) { // grab the x-y pos.s if browser is IE
		tempX = event.clientX + document.body.scrollLeft+250
		tempY = event.clientY + document.body.scrollTop
	} else {  // grab the x-y pos.s if browser is NS
		tempX = e.pageX
		tempY = e.pageY
	}  
	// catch possible negative values in NS4
	if (tempX < 0){tempX = 0}
	if (tempY < 0){tempY = 0}  
	// show the position values in the form named Show
	// in the text fields named MouseX and MouseY

	tempX+=20;

	document.getElementById("DisplayLinkDiv").style.display="block";
	document.getElementById("DisplayLinkDiv").style.left = "35vw";
	document.getElementById("DisplayLinkDiv").style.top = "35vh";
	document.getElementById("DisplayLinkDiv").style.width = "30vw";
	document.getElementById("DisplayLinkDiv").style.height = "30vh";
}

// CALL DETAILS FUNCTIONS
function HideCallDetails() {
	document.getElementById("CallDetailsDiv").innerHTML="";
	document.getElementById("CallDetailsDiv").style.display="none";
}
function ShowCallDetails(detail_id, call_type, width_override, height_override) {
	document.getElementById("CallDetailsDiv").style.display="block";
	document.getElementById("AgentDetailsDiv").style.left = "5vw";
	document.getElementById("AgentDetailsDiv").style.top = "5vh";
	document.getElementById("AgentDetailsDiv").style.width = "90vw";
	document.getElementById("AgentDetailsDiv").style.height = "80vh";

	var helptext_query="";
	if (width_override) 
		{
		var new_width=parseInt(width_override);
		left_override=Math.round((100-new_width)/2);
		document.getElementById("CallDetailsDiv").style.width = new_width+"vw";
		document.getElementById("CallDetailsDiv").style.left = left_override+"vw";
		}
	if (height_override) 
		{
		var new_height=parseInt(height_override);
		top_override=Math.round((100-new_height)/2);
		document.getElementById("CallDetailsDiv").style.height = new_height+"vh";
		document.getElementById("CallDetailsDiv").style.top = top_override+"vh";
		helptext_query+="&detail_span_height="+new_height;
		}

	var CallDetails = null;

	var xmlhttp=false;
	/*@cc_on @*/
	/*@if (@_jscript_version >= 5)
	// JScript gives us Conditional compilation, we can cope with old IE versions.
	// and security blocked creation of the objects.
	 try {
	  xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	 } catch (e) {
	  try {
	   xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	  } catch (E) {
	   xmlhttp = false;
	  }
	 }
	@end @*/
	if (!xmlhttp && typeof XMLHttpRequest!='undefined')
		{
		xmlhttp = new XMLHttpRequest();
		}
	if (xmlhttp)
		{
		helptext_query += "&detail_id=" + detail_id + "&call_type=" + call_type;
		xmlhttp.open('POST', 'display_call_details.php');
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(helptext_query);
		xmlhttp.onreadystatechange = function()
			{
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
				{
				CallDetails = xmlhttp.responseText;
				document.getElementById("CallDetailsDiv").innerHTML=CallDetails;
				}
			}
		delete xmlhttp;
		}
}

// AGENT DETAILS FUNCTIONS
function HideAgentDetails() {
	document.getElementById("AgentDetailsDiv").innerHTML="";
	document.getElementById("AgentDetailsDiv").style.display="none";
}
function ShowAgentDetails(user, start_datetime, end_datetime, total_duration_His, total_pause_His, total_all_billable_His, total_billable_His) {
	document.getElementById("AgentDetailsDiv").style.display="block";
	document.getElementById("AgentDetailsDiv").style.left = "5vw";
	document.getElementById("AgentDetailsDiv").style.top = "5vh";
	document.getElementById("AgentDetailsDiv").style.width = "90vw";
	document.getElementById("AgentDetailsDiv").style.height = "80vh";
	
	var form_elements = document.forms[0].elements;
	var agent_request_query="user="+user+"&start_datetime="+start_datetime+"&end_datetime="+end_datetime+"&total_duration_His="+total_duration_His+"&total_pause_His="+total_pause_His+"&total_all_billable_His="+total_all_billable_His+"&total_billable_His="+total_billable_His;

	for (i=0; i<form_elements.length; i++) 
		{
		if (form_elements[i].type!="button" && form_elements[i].type!="submit" && form_elements[i].value!="")
			{
			agent_request_query+="&"+form_elements[i].name+"="+encodeURIComponent(form_elements[i].value);
			}
		}
	
	var xmlhttp=false;
	/*@cc_on @*/
	/*@if (@_jscript_version >= 5)
	// JScript gives us Conditional compilation, we can cope with old IE versions.
	// and security blocked creation of the objects.
	 try {
	  xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	 } catch (e) {
	  try {
	   xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	  } catch (E) {
	   xmlhttp = false;
	  }
	 }
	@end @*/
	if (!xmlhttp && typeof XMLHttpRequest!='undefined')
		{
		xmlhttp = new XMLHttpRequest();
		}
	if (xmlhttp)
		{
		xmlhttp.open('POST', 'display_agent_details.php');
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(agent_request_query);
		xmlhttp.onreadystatechange = function()
			{
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
				{
				AgentDetails = xmlhttp.responseText;
				document.getElementById("AgentDetailsDiv").innerHTML=AgentDetails;
				}
			}
		delete xmlhttp;
		}

}

// OUTCOMES DETAILS FUNCTIONS
function HideOutcomesDetails() {
	document.getElementById("OutcomesDetailsDiv").innerHTML="";
	document.getElementById("OutcomesDetailsDiv").style.display="none";
}
function ShowOutcomesDetails(dispo, start_datetime, end_datetime, user, campaign_id, status_name, teams) {
	document.getElementById("OutcomesDetailsDiv").style.display="block";
	document.getElementById("OutcomesDetailsDiv").style.left = "5vw";
	document.getElementById("OutcomesDetailsDiv").style.top = "5vh";
	document.getElementById("OutcomesDetailsDiv").style.width = "90vw";
	document.getElementById("OutcomesDetailsDiv").style.height = "80vh";

	var vicidial_queue_groups=document.getElementById("vicidial_queue_groups").value;
	var users=document.getElementById("users").value;

	outcome_query = "&start_datetime=" + start_datetime + "&end_datetime=" + end_datetime + "&vicidial_queue_groups=" + vicidial_queue_groups;
	if (users && users!="")
		{
		outcome_query += "&users=" + users;
		}
	if (teams && teams!="")
		{
		outcome_query += "&teams=" + teams;
		}
	if (dispo)
		{
		outcome_query += "&status=" + dispo;
		}
	if (user)
		{
		outcome_query += "&user=" + user;
		}
	if (campaign_id)
		{
		outcome_query += "&campaign_id=" + campaign_id;
		}
	if (status_name) // From OUTCOMES page
		{
		outcome_query += "&status_name=" + status_name;
		}
	
	// alert(outcome_query);

	var OutcomesDetails = null;

	var xmlhttp=false;
	/*@cc_on @*/
	/*@if (@_jscript_version >= 5)
	// JScript gives us Conditional compilation, we can cope with old IE versions.
	// and security blocked creation of the objects.
	 try {
	  xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
	 } catch (e) {
	  try {
	   xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	  } catch (E) {
	   xmlhttp = false;
	  }
	 }
	@end @*/
	if (!xmlhttp && typeof XMLHttpRequest!='undefined')
		{
		xmlhttp = new XMLHttpRequest();
		}
	if (xmlhttp)
		{
		xmlhttp.open('POST', 'display_outcomes_details.php');
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(outcome_query);
		xmlhttp.onreadystatechange = function()
			{
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200)
				{
				OutcomesDetails = xmlhttp.responseText;
				document.getElementById("OutcomesDetailsDiv").innerHTML=OutcomesDetails;
				}
			}
		delete xmlhttp;
		}
}

// BASIC SPAN VISIBILITY HANDLER FUNCTION
function ToggleVisibility(span_name, display_type) {
	var span_vis = document.getElementById(span_name).style;
	if (!display_type)
		{
		if (span_vis.display=='none') { span_vis.display = 'block'; } else { span_vis.display = 'none'; }
		}
	else
		{
		span_vis.display = display_type;
		}
}

function AutoDownloadWarning(e, report_name, limit) 
	{
	document.getElementById("HelpDisplayDiv").innerHTML="";

	if (IE) { // grab the x-y pos.s if browser is IE
		tempX = event.clientX + document.body.scrollLeft+250
		tempY = event.clientY + document.body.scrollTop
	} else {  // grab the x-y pos.s if browser is NS
		tempX = e.pageX
		tempY = e.pageY
	}  
	// catch possible negative values in NS4
	if (tempX < 0){tempX = 0}
	if (tempY < 0){tempY = 0}  
	// show the position values in the form named Show
	// in the text fields named MouseX and MouseY

	tempX+=20;

	document.getElementById("HelpDisplayDiv").style.display="block";
	document.getElementById("HelpDisplayDiv").style.left = tempX + "px";
	document.getElementById("HelpDisplayDiv").style.top = tempY + "px";

	document.getElementById("HelpDisplayDiv").innerHTML="<TABLE CELLPADDING=2 CELLSPACING=0 border='0' class='help_td' width='300'><TR><TD VALIGN='TOP' width='280'><FONT class='help_bold'>NOTICE:</B></font></td><TD VALIGN='TOP' align='right' width='20'>&nbsp;</td></tr><TR><TD VALIGN='TOP' colspan='2'>"+report_name+" report is over "+limit+" records, and will auto-download as a CSV file rather than attempt to display to your screen.</td></tr></TABLE>";
	}