// help.js 
//
// Copyright (C) 2018  Matt Florell <vicidial@gmail.com>, Joe Johnson <freewermadmin@gmail.com>    LICENSE: AGPLv2
//
// Javascript functions for displaying/hiding/moving/populating the help span with text
//
// CHANGELOG:
// 180501-0045 - First build

// Detect if the browser is IE or not.
// If it is not IE, we assume that the browser is NS.
var IE = document.all?true:false
// If NS -- that is, !IE -- then set up for mouse capture
if (!IE) document.captureEvents(Event.MOUSEMOVE)


function ClearAndHideHelpDiv() {
	document.getElementById("HelpDisplayDiv").innerHTML="";
	document.getElementById("HelpDisplayDiv").style.display="none";
}

function FillAndShowHelpDiv(e, help_id) 
	{
	var help_text_id=help_id.replace("#", "");
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


	var HelpVerbiage = null;

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
		helptext_query = "action=grab_help_text&help_id=" + help_text_id;
		xmlhttp.open('POST', 'display_help.php');
		xmlhttp.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
		xmlhttp.send(helptext_query); 
		xmlhttp.onreadystatechange = function() 
			{ 
			if (xmlhttp.readyState == 4 && xmlhttp.status == 200) 
				{
				HelpVerbiage = xmlhttp.responseText;
				document.getElementById("HelpDisplayDiv").innerHTML=HelpVerbiage;
				}
			}
		delete xmlhttp;
		}
	}
