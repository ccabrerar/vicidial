<?php
# VERM_wallboard_stylesheet.php - Vicidial Enhanced Reporting stylesheet for the wallboards report
#
# CSS/PHP file that uses the system-defined screen colors to display report elements
#
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGELOG:
# 220825-1602 - First build
# 230123-1830 - Changed require statements to use local files, not relative
#

require("dbconnect_mysqli.php");
require("functions.php");
header("Content-type: text/css");

require("screen_colors.php");
?>

/*
.play_pause_button {
  box-sizing: border-box;
  height: 20px;
  
  border-color: transparent transparent transparent #202020;
  transition: 100ms all ease;
  will-change: border-width;
  cursor: pointer;

  // play state
  border-style: solid;
  border-width: 10px 0 10px 16px;

  // paused state
  //&.pause {
  //  border-style: double;
  //  border-width: 0px 0 0px 16px;
  //}
}
*/

input.play_button {
	width: 25px;
	height: 25px;
	border-radius: 8px;	
	color:white;
	display: inline-flex;
	align-items: center; 
	background-color: green;
}
input.stop_button {
	width: 25px;
	height: 25px;
	border-radius: 8px;	
	color:white;
	display: inline-flex;
	align-items: center; 
	background-color: red;
}

.topalign
	{
	vertical-align: top;
	}
div.top_center
	{
	vertical-align: top;
	align: center;
	}
div.total_center
	{
	vertical-align: middle;
	align: center;
	}

td.widget_cell
	{
	border: 1px solid white;
	vertical-align: top;
	// border-radius: 2px;
	// cellspacing: 5px;
	}
td.widget_cell:hover
	{
	border: 1px solid black;
	}

table.widget_contents
	{
	border-collapse: collapse; border-spacing: 0;
	border-radius: 4px;
	}

table.shaded
	{
	background-color: #<? echo $SSalt_row2_background; ?>;
	}

tr.widget_cell_title_bar
	{
	font-family: "Segoe UI";
	font-size: 12pt; 
	color: #444;
	background-color: #FFF;
	}

tr.widget_cell_title_bar:hover 
	{
	color: #FFF;
	background-color: #444;
	}

tr.widget_cell_text_type
	{
	font-family: "Segoe UI";
	font-size: 36pt; 
	}


tr.wallboard_title_row
	{
	font-family: "Segoe UI";
	font-size: 16pt; 
	color: #999;
	background-color: #444;
	height: 50px;
	}

td.widget_settings_cell
	{
	font-family: "Segoe UI";
	font-size: 12pt; 
	color: #CCC;
	overflow: hidden;
	text-overflow: ellipsis;
	}

th.widget_settings_title_cell
	{
	font-family: "Segoe UI";
	font-size: 12pt; 
	color: #CCC;
	overflow: hidden;
	text-overflow: ellipsis;
	}


input.widget_settings_form_field {
	font-family: "Segoe UI";
	font-size: 10px;
	margin-bottom: 1px;
	margin-bottom: 1px;
	border: 0px;
}


div.widget_settings {
	position:fixed; 
	background-color: #444;
	top: 0vh, 
	left:0vw; 
	display:none; 
	overflow-x: auto; 
	overflow-y: auto;
	box-shadow: 3px 3px 3px 3px #666;
	border-radius: 3px;
	border:3px solid;
}


#widget_table {
	font-family: "Segoe UI";
	font-size: 12pt; 
	border-collapse: collapse;
	width: 100%;
	vertical-align: top;
}

#widget_table tr.widget_table_header {
	background-color: #666;
	color: #FFF;
}

#widget_table td, #widget_table th {
	border: 1px solid #ddd;
}

#widget_table tr:nth-child(even){background-color:#<? echo $SSalt_row3_background; ?>;}

.view_button {
	padding: 6px;
	border-radius: 50px;
	display: inline-flex;
	cursor: pointer;
	transition: background .2s ease;
	margin: 8px 0;
	-webkit-tap-highlight-color: transparent;
	vertical-align: middle;
}

.widget_edit_button {
	border: none;
	color: white;
	background-color: transparent;
	align-items: center; 
}
.widget_edit_button:hover {
	color: #0FF;
	align-items: center; 
}
input.inbound_marker_button 
	{
	width: 30px;
	height: 30px;
	font-family: "Segoe UI";
	font-size: 18px;
	border: none;
	background-color: #FFFF99;
	align-items: center; 
	}
input.outbound_marker_button 
	{
	width: 30px;
	height: 30px;
	font-family: "Segoe UI";
	font-size: 18px;
	border: none;
	background-color: #99FF99;
	align-items: center; 
	}
input.paused_marker_button 
	{
	width: 30px;
	height: 30px;
	font-family: "Segoe UI";
	font-size: 18px;
	border: none;
	background-color: #FF9999;
	align-items: center; 
	}
input.ready_marker_button 
	{
	width: 30px;
	height: 30px;
	font-family: "Segoe UI";
	font-size: 18px;
	border: none;
	background-color: #9999FF;
	align-items: center; 
	}


input.widget_form_button{
	font-family: "Segoe UI";
	font-size: 12px;
	color:#000;
	border: none;
	padding: 5px 5px;
	width: 100px;
	text-align: center;
	text-decoration: none;
	display: inline-block;	
	background-color:#<?php echo $SSbutton_color; ?>;
}

input.color_swatch_button {
	border: none;
	padding: 1px 1px;
	width: 30px;
	height: 10px;
	text-align: center;
	text-decoration: none;
	display: inline-block;	
}

input.color_swatch_button:hover {
  color: rgba(255, 255, 255, 1);
  box-shadow: 0px 0px 3px 3px rgba(255, 255, 255, .4);
}

.radio-wrap{
display: inline-block;
width: 30px;
height: 30px; 
border: 2px solid red;
border-radius: 15px;
position: relative;
box-shadow: 0px 0px 4px rgba(0, 0, 0, .50);
margin: 0.25rem;
}

.radio-wrap:before {
content: "";
display: flex;
justify-content: center;
align-items: center;
background: lime;
height: 10px;
width: 10px;
border-radius: 5px;
position: absolute;
top: calc(50% - 5px);
left: calc(50% - 5px);
box-shadow: 0px 0px 8px rgba(0, 0, 0, .70);
}

.wallboard_tiny_text {
	font-family: "Segoe UI";
	FONT-SIZE: calc(5px + 0.3vw);
}
.wallboard_small_text {
	font-family: "Segoe UI";
	FONT-SIZE: calc(6px + 0.5vw);
}
.wallboard_medium_text {
	font-family: "Segoe UI";
	FONT-SIZE: calc(12px + 0.6vw);
}
.wallboard_large_text {
	font-family: "Segoe UI";
	FONT-SIZE: calc(18px + 0.8vw);
}
.wallboard_extra_large_text {
	font-family: "Segoe UI";
	FONT-SIZE: calc(24px + 1vw);
}
.bold {
	font-weight:bold;
}
.italics {
	font-style: italic;
}
.centered_text {
	text-align: center;
}

a.header_link:link {
	color: #FFF;
	text-decoration: none;
}

a.header_link:visited {
	color: #FFF;
	text-decoration: none;
}

a.header_link:hover {
	color: #FFF;
	text-decoration: underline;
	cursor: pointer;
}

a.header_link:active {
	color: #FFF;
	text-decoration: underline;
}
