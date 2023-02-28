<?php
# VERM_stylesheet.php - Vicidial Enhanced Reporting stylesheet
#
# CSS/PHP file that uses the system-defined screen colors to display report elements
#
# Copyright (C) 2022  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
# 
# CHANGELOG:
# 220825-1603 - First build
# 230123-1825 - Changed require statements to local files
#

require("dbconnect_mysqli.php");
require("functions.php");
header("Content-type: text/css");

require("screen_colors.php");
?>
div.help_info {position:absolute; top:0; left:0; display:none;}

TABLE.help_td {
        -moz-border-radius: 5px 5px 5px 5px;
        -webkit-border-radius: 5px 5px 5px 5px;
        border-radius: 5px 5px 5px 5px;
        box-shadow: 5px 5px 12px #000000;
        padding: 5px;
        font-family: Arial, Helvetica, sans-serif;
        color: black;
        font-size: 10pt;
        background: #<?php echo $SSframe_background; ?>;
        color: #000000;
        vertical-align: top;
        border:solid 4px #<?php echo $SSmenu_background; ?>
}

.help_bold {
        font-weight:bold;
        font-size: 12pt;
        opacity: 1.0;
}

.panel_td {
	padding: 5px;
	font-family: Arial, Helvetica, sans-serif; 
	color: black; 
	font-size: 12pt; 
	font-weight: bold;
	background: #<?php echo $SSframe_background; ?>;
	color: #000000;
	vertical-align: top;
}

.standard_font_small {
	font-family: "Segoe UI";
	font-size: 9pt; 
}

.standard_font {
	font-family: "Segoe UI";
	font-size: 12pt; 
}
.standard_font_lg {
	font-family: "Segoe UI"; 
	font-size: 14pt; 
	font-weight: bold;
}

h2.rpt_header {
	font-family: "Segoe UI"; 
	font-size: 18pt; 
}

h2.admin_sub_header {
	font-family: "Arial"; 
	font-size: 12pt; 
	color: #<?php echo $SSmenu_background; ?>;
}

h2.admin_header {
	font-family: "Arial"; 
	font-size: 18pt; 
}


#rpt_table {
	font-family: "Segoe UI";
	font-size: 12pt; 
	border-collapse: collapse;
	width: 100%;
}

#rpt_table td, #rpt_table th {
	border: 1px solid #ddd;
	padding: 8px;
}

#rpt_table tr:nth-child(even){background-color: #f9f9f9;}

#rpt_table tr:hover {background-color: #ddf;}

#rpt_table tr.export_row 
	{
	background-color: #fff;
	padding-top: 12px;
	padding-bottom: 12px;
	background-color: #FFF;
	color: black;
	text-align: right;
	}

#rpt_table th {
  padding-top: 12px;
  padding-bottom: 12px;
  background-color: #FFF;
  color: black;
}

#rpt_table {
	font-family: "Segoe UI";
	font-size: 12pt; 
	border-collapse: collapse;
	width: 100%;
}

#rpt_table td, #rpt_table th {
	border: 1px solid #ddd;
	padding: 8px;
}
#rpt_table td.export_row_cell
	{
	border: 0px;
	background-color: #FFF;
	}
#rpt_table td.bold_cell
	{
	font-weight: bold;
	}
#rpt_table td.queue_cell
	{
	font-family: "Segoe UI";
	font-size: 8pt; 
	}
#rpt_table td.small_text
	{
	font-family: "Segoe UI";
	font-size: 10pt; 
	}


#details_table {
	font-family: "Segoe UI";
	font-size: 12pt; 
	border-collapse: collapse;
	width: 100%;
}

#details_table td, #details_table th {
	border: 1px solid #ddd;
	padding: 4px;
}

#details_table tr:hover {background-color: #ddf;}

#details_table tr.export_row 
	{
	background-color: #fff;
	padding-top: 6px;
	padding-bottom: 6px;
	background-color: #FFF;
	color: black;
	text-align: right;
	}
#details_table td.export_row_cell
	{
	border: 0px;
	}

#details_table th {
  padding-top: 12px;
  padding-bottom: 12px;
  background-color: #FFF;
  color: black;
}
#details_table td.bold_cell
	{
	font-weight: bold;
	}
#details_table td.queue_cell
	{
	font-family: "Segoe UI";
	font-size: 8pt; 
	}
#details_table td.small_text
	{
	font-family: "Segoe UI";
	font-size: 10pt; 
	}



#admin_table {
	font-family: "Arial";
	font-size: 12pt; 
	border-collapse: collapse;
	width: 100%;
}

#admin_table td, #admin_table th {
	border: 0px;
	padding: 4px;
}

#admin_table tr.export_row 
	{
	background-color: #fff;
	padding-top: 6px;
	padding-bottom: 6px;
	background-color: #FFF;
	color: black;
	text-align: right;
	}

#admin_table th {
  padding-top: 12px;
  padding-bottom: 12px;
  background-color: #FFF;
  color: black;
}

#admin_table font.rpt_header {
  font-weight: bold;
  color: red;
}


#nav_table
	{
    width:100%;
    padding:0px;
    margin:0px;
	}

#nav_table td.nav_header
	{
	border-bottom: 2px solid black;
	border-top: 2px solid black;
	background-color: #CCC;
	}

#nav_table td.title_cell
	{
	font-family: "Segoe UI";
	font-size: 12px;
	}

input.actButton{
	font-family: "Segoe UI";
	font-size: 12px;
	color:#FFFFFF;
	border: none;
	padding: 0px 0px;
	width: 150px;
	height: 36px;
	text-align: center;
	text-decoration: none;
	display: inline-block;	
	background-color:#<?php echo $SSmenu_background; ?>;
}
input.refreshButton{
	font-family: "Segoe UI";
	font-size: 24px;
	color:#FFFFFF;
	border: none;
	padding: 0px 0px;
	width: 50px;
	height: 36px;
	text-align: center;
	text-decoration: none;
	display: inline-block;	
	background-color:#<?php echo $SSmenu_background; ?>;
}

a.header_link:link {
	color: #900;
	text-decoration: none;
}

a.header_link:visited {
	color: #900;
	text-decoration: none;
}

a.header_link:hover {
	color: #66F;
	text-decoration: underline;
	cursor: pointer;
}

a.header_link:active {
	color: #66F;
	text-decoration: underline;
}

a.report_link:link {
	color: #009;
	text-decoration: none;
}

a.report_link {
	color: #009;
	text-decoration: none;
}

a.report_link:visited {
	color: #909;
	text-decoration: none;
}

a.report_link:hover {
	color: #66F;
	text-decoration: underline;
	cursor: pointer;
}

a.report_link:active {
	color: #66F;
	text-decoration: underline;
}


a.popup_link:link {
	color: #900;
	text-decoration: none;
}

a.popup_link {
	color: #900;
	text-decoration: none;
}

a.popup_link:visited {
	color: #900;
	text-decoration: none;
}

a.popup_link:hover {
	color: #F66;
	text-decoration: underline;
	cursor: pointer;
}

a.popup_link:active {
	color: #F66;
	text-decoration: underline;
}

div.details_info {
	position:fixed; 
	background-color: white;
	top: 5vh, 
	left:5vw; 
	display:none; 
	overflow-x: hidden; 
	overflow-y: auto;
	box-shadow: 10px 10px 10px 10px #666;
	border:3px solid;
}

.VERM_form_field {
	font-family: "Segoe UI";
	font-size: 12px;
	margin-bottom: 2px;
	margin-bottom: 2px;
	background-color: #<?php echo $SSalt_row1_background; ?>;
	padding: 5px;
	border: solid 3px #<?php echo $SSmenu_background; ?>;
}

input.VERM_numeric_field
	{
	width:80px
	}

input.transparent_button
	{
	background: none;
	border: 0px;
	padding: 10px 5px;
	display: inline-block;
	}

input.download_button
	{
	background: none;
	border: 0px;
	font-family: "Segoe UI";
	font-size: 12pt; 
	}

input.sort_button
	{
	background: none;
	border: 0px;
	font-family: "Segoe UI";
	font-size: 12pt; 
	font-weight: bold;
	color: #900;
	}

input.download_button:hover 
	{
	background: #F99;
	}

input.sort_button:hover 
	{
	background: #99F;
	}

input.current_report
	{
	font-family: "Arial black";
	font-weight: bold;
	color: #F00;
	}

ul.navigation_list 
	{
	font-family: "Arial";
	font-size: 10pt; 
	float: left; /* float all of this to the right */
	padding-inline: 10px 20px;  /* An absolute length */
	}

ul.navigation_list li
	{
	  display: inline-block;
	  padding: 15px;
	}

ul.navigation_list li.current_report
	{
	font-family: "Arial black";
	font-weight: bold;
	color: #F00;
	}

button {
  width: 30px;
  height: 38px;
  position: relative;
  left: -5px;
  border: 1px solid #DDE1E4;
  border-left: none;
  background-color: #11E8EA;
  cursor: pointer;
}

datalist {
  display: none;
}
