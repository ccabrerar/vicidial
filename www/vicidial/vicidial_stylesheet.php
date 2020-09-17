<?php
# vicidial_stylesheet.php
# 
# Copyright (C) 2020  Matt Florell <vicidial@gmail.com>, Joe Johnson <freewermadmin@gmail.com>    LICENSE: AGPLv2
#
# CSS/PHP file that uses the system-defined screen colors to display report elements
#
# 170830-2123 - First build
# 180501-0045 - New elements added
# 190129-1303 - New mobile display elements added
# 200309-1819 - Modifications for display formatting
#

require("dbconnect_mysqli.php");
require("functions.php");
header("Content-type: text/css");

require("screen_colors.php");
?>
/* TEXT / DISPLAY STYLES */
.vertical-text {
	-ms-writing-mode: tb-rl;
	-webkit-writing-mode: vertical-rl;
	-moz-writing-mode: vertical-rl;
	-ms-writing-mode: vertical-rl;
	writing-mode: vertical-rl;
	white-space:nowrap;
	display:block;
	top:0;
	width:20px;
	height:20px;
}
div.scrolling {
	height: 77px;
	width: 320px;
	overflow: auto;
	border: 1px solid #666;
	background-color: #FFF;
	padding: 2px;
}
redalert {font-size: 18px; font-weight:bold; font-family: Arial, Sans-Serif; color: white ; background: #FF0000}
.sm_shadow {box-shadow: 2px 2px 2px #000000;}
.round_corners {    
	-moz-border-radius: 5px;
	-webkit-border-radius: 5px;
	border-radius: 5px;
	}
.embossed {
	text-shadow: -1px -1px 1px #fff, 1px 1px 1px #000;
	opacity: 1.0;
}
.embossed_bold {
	font-weight:bold;
	text-shadow: -1px -1px 1px #fff, 1px 1px 1px #000;
	opacity: 1.0;
}
.help_bold {
	font-weight:bold;
	font-size: 12pt;
	opacity: 1.0;
}
.bold {
	font-weight:bold;
}
.green {color: black; background-color: #99FF99}
.red {color: black; background-color: #FF9999}
.orange {color: black; background-color: #FFCC99}
.white_text {color: #FFF;}

/* Basic fonts */
.small_standard {  font-family: Arial, Helvetica, sans-serif; font-size: 8pt}
.small_standard_bold {  font-family: Arial, Helvetica, sans-serif; font-size: 8pt; font-weight: bold}
.standard {  font-family: Arial, Helvetica, sans-serif; font-size: 10pt}
.standard_bold {  font-family: Arial, Helvetica, sans-serif; font-size: 10pt; font-weight: bold}

.std_row1 {background-color: <?php echo $SSstd_row1_background; ?>}
.std_row2 {background-color: <?php echo $SSstd_row2_background; ?>}
.std_row3 {background-color: <?php echo $SSstd_row3_background; ?>}
.std_row4 {background-color: <?php echo $SSstd_row4_background; ?>}
.std_row5 {background-color: <?php echo $SSstd_row5_background; ?>}
.alt_row1 {background-color: <?php echo $SSalt_row1_background; ?>}
.alt_row2 {background-color: <?php echo $SSalt_row2_background; ?>}
.alt_row3 {background-color: <?php echo $SSalt_row3_background; ?>}
.std_btn  {background-color: <?php echo $SSbutton_background; ?>;}


.border2px {border:solid 2px #<?php echo $SSmenu_background; ?>}

.android_standard {
	font-family: Arial, Sans-Serif;
	FONT-SIZE: calc(8px + 1vw);
}
.android_medium {
	font-family: Arial, Sans-Serif;
	FONT-SIZE: calc(9px + 1vw);
}
.android_large {
	font-family: Arial, Sans-Serif;
	FONT-SIZE: calc(10px + 1vw);
	font-weight: bold;
}
.android_auto {
	font-family: Arial, Helvetica, sans-serif;
	font-weight: bold;
	font-size: calc(14px + (26 - 14) * ((100vw - 300px) / (1600 - 300)));
	line-height: calc(1.3em + (1.5 - 1.2) * ((100vw - 300px)/(1600 - 300)));
}
.android_small {
	font-family: Arial, Sans-Serif;
	FONT-SIZE: calc(7px + .5vw);
}
.android_auto_small {
	font-family: Arial, Helvetica, sans-serif;
	font-size: calc(8px + (18 - 8) * ((100vw - 300px) / (1600 - 300)));
	line-height: calc(1.3em + (1.5 - 1.2) * ((100vw - 300px)/(1600 - 300)));
}
.android_whiteboard_small {
	font-family: Arial, Helvetica, sans-serif;
	FONT-SIZE: calc(8px + .5vw);
	line-height: calc(1.3em + (1.5 - 1.2) * ((100vw - 300px)/(1600 - 300)));
}
.android_campaign_header {
	FONT-FAMILY: Courier;
	FONT-WEIGHT: bold;
	FONT-SIZE: calc(4px + (18 - 4) * ((100vw - 300px) / (1600 - 300)));
}
.android_auto_percent {
	font-family: Arial, Helvetica, sans-serif;
	font-weight: bold;
	width: 24vw;
	font-size: calc(14px + (26 - 14) * ((100vw - 300px) / (1600 - 300)));
	line-height: calc(1.3em + (1.5 - 1.2) * ((100vw - 300px)/(1600 - 300)));
}
.mobile_whiteboard_td_lg {width: 40vw; max-width: 400px;}
.mobile_whiteboard_td_sm {width: 30vw; max-width: 300px;}
.mobile_whiteboard_select {width: 27vw; max-width: 270px;}
.mobile_whiteboard_button {width: 15vw; max-width: 150px;}
.mobile_whiteboard_display {width: 80vw; max-width:800px; height:70vh; max-height:800px;}

.autosize_10 {width: 10vw; max-width: 160px;}
.autosize_12 {width: 12vw; max-width: 180px;}

/* 
	LEVEL ONE MENU
*/
ul.dropdown_android                    { position: relative;}
ul.dropdown_android li                 { font-weight: bold; float: left; }
ul.dropdown_android a:hover	{ color: #000; }
ul.dropdown_android a:active   { color: #ffa500; }
ul.dropdown_android li a               { display: block; border-right: 1px solid #333; color: #222; }
ul.dropdown_android li:last-child a         { border-right: none; } /* Doesn't work in IE */
ul.dropdown_android li.hover,
ul.dropdown_android li:hover      { position: relative; }


/* 
	LEVEL TWO MENU
*/
ul.dropdown_android ul 		{ width: calc(25px + (150 - 25) * ((100vw - 300px) / (1600 - 300))); visibility: hidden; position: absolute; top: 100%; left: -40;}
ul.dropdown_android ul li 		{ font-weight: normal; float: none; top: 100%; margin-top:-4;}

/* IE 6 & 7 Needs Inline Block */
ul.dropdown_android ul li a		{ border-right: none; width: 100%; display: inline-block; } 

/* 
	LEVEL THREE MENU
*/
ul.dropdown_android ul ul 		{ left: 100%; top: 0; }
ul.dropdown_android li:hover > ul 	{ visibility: visible; }


.records_list_x{
	background-color: #<?php echo $SSstd_row2_background; ?>;
}
.records_list_x:hover{background-color: #E6E6E6;}
.records_list_y{
	background-color: #<?php echo $SSstd_row1_background; ?>;
}
.records_list_y:hover{background-color: #E6E6E6;}
.insetshadow {
    color: #202020;
    letter-spacing: .1em;
    text-shadow: 
      -1px -1px 1px #111, 
      2px 2px 1px #363636;
 }
.elegantshadow {
    color: #131313;
    background-color: #e7e5e4;
    letter-spacing: .15em;
    text-shadow: 
      1px -1px 0 #767676, 
      -1px 2px 1px #737272, 
      -2px 4px 1px #767474, 
      -3px 6px 1px #787777, 
      -4px 8px 1px #7b7a7a, 
      -5px 10px 1px #7f7d7d, 
      -6px 12px 1px #828181, 
      -7px 14px 1px #868585, 
      -8px 16px 1px #8b8a89, 
      -9px 18px 1px #8f8e8d, 
      -10px 20px 1px #949392, 
      -11px 22px 1px #999897, 
      -12px 24px 1px #9e9c9c, 
      -13px 26px 1px #a3a1a1, 
      -14px 28px 1px #a8a6a6, 
      -15px 30px 1px #adabab, 
      -16px 32px 1px #b2b1b0, 
      -17px 34px 1px #b7b6b5, 
      -18px 36px 1px #bcbbba, 
      -19px 38px 1px #c1bfbf, 
      -20px 40px 1px #c6c4c4, 
      -21px 42px 1px #cbc9c8, 
      -22px 44px 1px #cfcdcd, 
      -23px 46px 1px #d4d2d1, 
      -24px 48px 1px #d8d6d5, 
      -25px 50px 1px #dbdad9, 
      -26px 52px 1px #dfdddc, 
      -27px 54px 1px #e2e0df, 
      -28px 56px 1px #e4e3e2;
  }

/* FORM ELEMENTS */
.form_field {
	font-family: Arial, Sans-Serif;
	font-size: 10px;
	margin-bottom: 3px;
	background-color: #<?php echo $SSalt_row3_background; ?>;
	padding: 2px;
	border: solid 1px #<?php echo $SSmenu_background; ?>;
}
.form_field_android {
	font-family: Arial, Sans-Serif;
	FONT-SIZE: calc(8px + 1vw);
}
.form_field_android_small {
	font-family: Arial, Sans-Serif;
	FONT-SIZE: calc(6px + 0.5vw);
}
.form_field_whiteboard_android {
	font-family: Arial, Sans-Serif;
	font-size: calc(8px + 1vw);
	margin-bottom: 3px;
	background-color: #<?php echo $SSalt_row3_background; ?>;
	border: solid 1px #<?php echo $SSmenu_background; ?>;
}
.required_field {
	font-family: Arial, Sans-Serif;
	font-size: 10px;
	margin-bottom: 3px;
	background-color: #FFCCCC;
	padding: 2px;
	border: groove 2px #990000;
	background-position: top;
}
.required_field_whiteboard_android {
	font-family: Arial, Sans-Serif;
	font-size: calc(8px + 1vw);
	margin-bottom: 3px;
	background-color: #FFCCCC;
	border: groove 1px #990000;
	background-position: top;
}
textarea.chat_box {
	 font-family: Arial, Sans-Serif;
	 font-size: 10px;
	 margin-bottom: 3px;
	 padding: 2px;
	 border: solid 1px #000066;
}
textarea.chat_box_ended {
	 font-family: Arial, Sans-Serif;
	 font-size: 10px;
	 margin-bottom: 3px;
	 padding: 2px;
	 border: solid 1px #000066;
	 background-color:#999999;
}
.cust_form { 
	font-family: Sans-Serif; 
	font-size: 10px; 
	overflow: hidden; 
}

input.red_btn{
	font-family: Arial, Sans-Serif;
	font-size: 12px;
	color:#FFFFFF;
	font-weight:bold;
	background-color:#990000;
	border:2px solid;
	border-top-color:#FFCCCC;
	border-left-color:#FFCCCC;
	border-right-color:#330000;
	border-bottom-color:#330000;
}
input.red_btn_mobile{
	font-family: Arial, Sans-Serif;
	FONT-SIZE: calc(12px + .5vw);
	width: 36vw;
	max-width:600px;
	color:#FFFFFF;
	font-weight:bold;
	background-color:#990000;
	border:2px solid;
	border-top-color:#FFCCCC;
	border-left-color:#FFCCCC;
	border-right-color:#330000;
	border-bottom-color:#330000;
}
input.red_btn_mobile_lg{
	font-family: Arial, Sans-Serif;
	FONT-SIZE: calc(14px + 1vw);
	width: 48vw;
	max-width:800px;
	color:#FFFFFF;
	font-weight:bold;
	background-color:#990000;
	border:2px solid;
	border-top-color:#FFCCCC;
	border-left-color:#FFCCCC;
	border-right-color:#330000;
	border-bottom-color:#330000;
}
input.red_btn_mobile_sm{
	font-family: Arial, Sans-Serif;
	FONT-SIZE: calc(12px + .2vw);
	width: 30vw;
	max-width:400px;
	color:#FFFFFF;
	font-weight:bold;
	background-color:#990000;
	border:2px solid;
	border-top-color:#FFCCCC;
	border-left-color:#FFCCCC;
	border-right-color:#330000;
	border-bottom-color:#330000;
}
input.red_btn_anywidth{
	font-family: Arial, Sans-Serif;
	FONT-SIZE: calc(12px + .5vw);
	color:#FFFFFF;
	font-weight:bold;
	background-color:#990000;
	border:2px solid;
	border-top-color:#FFCCCC;
	border-left-color:#FFCCCC;
	border-right-color:#330000;
	border-bottom-color:#330000;
}

input.green_btn{
	font-family: Arial, Sans-Serif;
	font-size: 12px;
	color:#FFFFFF;
	font-weight:bold;
	background-color:#009900;
	border:2px solid;
	border-top-color:#CCFFCC;
	border-left-color:#CCFFCC;
	border-right-color:#003300;
	border-bottom-color:#003300;
}
input.green_btn_mobile{
	font-family: Arial, Sans-Serif;
	FONT-SIZE: calc(12px + .5vw);
	width: 11vw;
	max-width:250px;
	color:#FFFFFF;
	font-weight:bold;
	background-color:#009900;
	border:2px solid;
	border-top-color:#CCFFCC;
	border-left-color:#CCFFCC;
	border-right-color:#003300;
	border-bottom-color:#003300;
}
input.green_btn_mobile_lg{
	font-family: Arial, Sans-Serif;
	FONT-SIZE: calc(14px + 1vw);
	width: 15vw;
	max-width:200px;
	color:#FFFFFF;
	font-weight:bold;
	background-color:#009900;
	border:2px solid;
	border-top-color:#CCFFCC;
	border-left-color:#CCFFCC;
	border-right-color:#003300;
	border-bottom-color:#003300;
}
input.green_btn_anywidth{
	font-family: Arial, Sans-Serif;
	FONT-SIZE: calc(12px + .5vw);
	color:#FFFFFF;
	font-weight:bold;
	background-color:#009900;
	border:2px solid;
	border-top-color:#CCFFCC;
	border-left-color:#CCFFCC;
	border-right-color:#003300;
	border-bottom-color:#003300;
}
input.green_btn_anywidth_lg{
	font-family: Arial, Sans-Serif;
	FONT-SIZE: calc(14px + 1vw);
	color:#FFFFFF;
	font-weight:bold;
	background-color:#009900;
	border:2px solid;
	border-top-color:#CCFFCC;
	border-left-color:#CCFFCC;
	border-right-color:#003300;
	border-bottom-color:#003300;
}
input.blue_btn{
	font-family: Arial, Sans-Serif;
	font-size: 12px;
	color:#FFFFFF;
	font-weight:bold;
	background-color:#000099;
	border:2px solid;
	border-top-color:#CCCCFF;
	border-left-color:#CCCCFF;
	border-right-color:#000033;
	border-bottom-color:#000033;
}
input.blue_btn_mobile{
	font-family: Arial, Sans-Serif;
	FONT-SIZE: calc(12px + .5vw);
	color:#FFFFFF;
	font-weight:bold;
	background-color:#000099;
	border:2px solid;
	border-top-color:#CCCCFF;
	border-left-color:#CCCCFF;
	border-right-color:#000033;
	border-bottom-color:#000033;
}
input.tiny_red_btn{
	color:#FFFFFF;
	font-size:9px;
	font-weight:bold;
	background-color:#993333;
	border:1px solid;
	border-top-color:#FFCCCC;
	border-left-color:#FFCCCC;
	border-right-color:#660000;
	border-bottom-color:#660000;
	filter:progid:DXImageTransform.Microsoft.Gradient
		(GradientType=0,StartColorStr='#00ffffff',EndColorStr='#ff660000');}
input.tiny_blue_btn{
	color:#FFFFFF;
	font-size:9px;
	font-weight:bold;
	background-color:#3333FF;
	border:1px solid;
	border-top-color:#CCCCFF;
	border-left-color:#CCCCFF;
	border-right-color:#000066;
	border-bottom-color:#000066;
	filter:progid:DXImageTransform.Microsoft.Gradient
		(GradientType=0,StartColorStr='#00ffffff',EndColorStr='#ff000066');}
input.tiny_yellow_btn{
	color:#000000;
	font-size:9px;
	font-weight:bold;
	background-color:#FFFF00;
	border:1px solid;
	border-top-color:#FFFFCC;
	border-left-color:#FFFFCC;
	border-right-color:#333300;
	border-bottom-color:#333300;
	filter:progid:DXImageTransform.Microsoft.Gradient
		(GradientType=0,StartColorStr='#00ffffff',EndColorStr='#ffFFFF00');}
input.tiny_green_btn{
	color:#FFFFFF;
	font-size:9px;
	font-weight:bold;
	background-color:#009900;
	border:1px solid;
	border-top-color:#CCFFCC;
	border-left-color:#CCFFCC;
	border-right-color:#003300;
	border-bottom-color:#003300;
	filter:progid:DXImageTransform.Microsoft.Gradient
		(GradientType=0,StartColorStr='#00ffffff',EndColorStr='#FF003300');}

/* TABLE ELEMENTS */
TABLE.question_td {
	-moz-border-radius: 5px 5px 5px 5px;
	-webkit-border-radius: 5px 5px 5px 5px;
	border-radius: 5px 5px 5px 5px;
	box-shadow: 5px 5px 12px #000000;
	padding: 5px;
	font-family: Arial, Helvetica, sans-serif; 
	color: black; 
	font-size: 12pt; 
	font-weight: bold;
	background: #<?php echo $SSframe_background; ?>;
	color: #000000;
	vertical-align: top;
	border:solid 2px #<?php echo $SSmenu_background; ?>
}
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
TABLE.panel_td {
	padding: 5px;
	font-family: Arial, Helvetica, sans-serif; 
	color: black; 
	font-size: 12pt; 
	font-weight: bold;
	background: #<?php echo $SSframe_background; ?>;
	color: #000000;
	vertical-align: top;
}

TD.panel_td {
	padding: 5px;
	font-family: Arial, Helvetica, sans-serif; 
	color: black; 
	font-size: 12pt; 
	font-weight: bold;
	background: #<?php echo $SSframe_background; ?>;
	color: #000000;
	vertical-align: top;
}
TD.search_td {
	-moz-border-radius: 5px 5px 5px 5px;
	-webkit-border-radius: 5px 5px 5px 5px;
	border-radius: 5px 5px 5px 5px;
	padding: 5px;
	font-family: Arial, Helvetica, sans-serif; 
	color: black; 
	font-size: 8pt; 
	font-weight: bold;
	background: #FB9BB9;
	color: #000000;
	vertical-align: top;
	border:solid 2px #600
}
TD.bbottom {
	border-bottom:1pt solid black;
}
TD.btop {
	border-top:1pt solid black;
}
TD.bsides {
	border-left:1pt solid black;
	border-right:1pt solid black;
}
TD.text_overflow {
	text-overflow: ellipis; 
	overflow: hidden;
}

/* DIV ELEMENTS */
div.shadowbox
{
box-shadow: 6px 6px 3px #888888;
border:2px solid;
border-radius:5px;
FONT-WEIGHT: bold;
FONT-SIZE: 12pt;
FONT-FAMILY: Courier;
padding-left:25px;
}
div.shadowbox_1st
{
background-color:#66FF66;
box-shadow: 6px 6px 3px #888888;
border:2px solid;
border-radius:5px;
padding-left:10px;
FONT-WEIGHT: bold;
FONT-SIZE: 12pt;
FONT-FAMILY: Courier;
}
div.shadowbox_2nd
{
background-color:#FFFF66;
box-shadow: 6px 6px 3px #888888;
border:2px solid;
border-radius:5px;
FONT-WEIGHT: bold;
FONT-SIZE: 12pt;
FONT-FAMILY: Courier;
padding-left:10px;
}
div.shadowbox_3rd
{
background-color:#66FFFF;
box-shadow: 6px 6px 3px #888888;
border:2px solid;
border-radius:5px;
FONT-WEIGHT: bold;
FONT-SIZE: 12pt;
FONT-FAMILY: Courier;
padding-left:10px;
}

div.shadowbox_4th
{
background-color:#FF6666;
box-shadow: 6px 6px 3px #888888;
border:2px solid;
border-radius:5px;
FONT-WEIGHT: bold;
FONT-SIZE: 12pt;
FONT-FAMILY: Courier;
padding-left:10px;
}

div.android_switchbutton
{
background-color:#FFCCCC;
box-shadow: 3px 3px 1px #000000;
border:2px solid;
WIDTH: calc(25px + (200 - 25) * ((100vw - 300px) / (1600 - 300)));
FONT-WEIGHT: bold;
FONT-SIZE: calc(6px + (24 - 6) * ((100vw - 300px) / (1600 - 300)));
FONT-FAMILY: Courier;
padding-left:3px;
padding-right:3px;
}
div.android_switchbutton_blue
{
background-color:#99CCFF;
box-shadow: 3px 3px 1px #000000;
border:2px solid;
WIDTH: calc(25px + (200 - 25) * ((100vw - 300px) / (1600 - 300)));
FONT-WEIGHT: bold;
FONT-SIZE: calc(6px + (24 - 6) * ((100vw - 300px) / (1600 - 300)));
FONT-FAMILY: Courier;
padding-left:3px;
padding-right:3px;
}
div.android_offbutton
{
background-color:#CCCCCC;
box-shadow: 3px 3px 1px #000000;
border:2px solid;
WIDTH: calc(25px + (200 - 25) * ((100vw - 300px) / (1600 - 300)));
FONT-WEIGHT: bold;
font-color: white;
FONT-SIZE: calc(6px + (24 - 6) * ((100vw - 300px) / (1600 - 300)));
FONT-FAMILY: Courier;
padding-left:3px;
padding-right:3px;
}
div.android_onbutton
{
background-color:#FFFFFF;
box-shadow: 3px 3px 1px #000000;
border:2px solid;
WIDTH: calc(25px + (200 - 25) * ((100vw - 300px) / (1600 - 300)));
FONT-WEIGHT: bold;
FONT-SIZE: calc(6px + (24 - 6) * ((100vw - 300px) / (1600 - 300)));
FONT-FAMILY: Courier;
padding-left:3px;
padding-right:3px;
}
div.android_offbutton_noshadow
{
background-color:#CCCCCC;
border:2px solid;
WIDTH: calc(25px + (200 - 25) * ((100vw - 300px) / (1600 - 300)));
FONT-WEIGHT: bold;
font-color: white;
FONT-SIZE: calc(6px + (24 - 6) * ((100vw - 300px) / (1600 - 300)));
FONT-FAMILY: Courier;
padding-left:3px;
padding-right:3px;
}
div.android_onbutton_noshadow
{
background-color:#FFFFFF;
border:2px solid;
WIDTH: calc(25px + (200 - 25) * ((100vw - 300px) / (1600 - 300)));
FONT-WEIGHT: bold;
FONT-SIZE: calc(6px + (24 - 6) * ((100vw - 300px) / (1600 - 300)));
FONT-FAMILY: Courier;
padding-left:3px;
padding-right:3px;
}
div.dropdown_android_button
{
background-color:#CCCCCC;
box-shadow: 3px 3px 1px #000000;
border:2px solid;
WIDTH: calc(25px + (200 - 25) * ((100vw - 300px) / (1600 - 300)));
FONT-WEIGHT: bold;
FONT-SIZE: calc(6px + (24 - 6) * ((100vw - 300px) / (1600 - 300)));
FONT-FAMILY: Courier;
padding-left:3px;
padding-right:3px;
}
div.dropdown_android_button:hover{
background-color: #F3D673; 
color: black;
}
div.android_offbutton_large
{
background-color:#999999;
box-shadow: 3px 3px 1px #000000;
border:2px solid;
WIDTH: calc(50px + (320 - 50) * ((100vw - 300px) / (1600 - 300)));
FONT-WEIGHT: bold;
font-color: white;
FONT-SIZE: calc(6px + (24 - 6) * ((100vw - 300px) / (1600 - 300)));
FONT-FAMILY: Courier;
padding-left:3px;
padding-right:3px;
}
div.android_onbutton_large
{
background-color:#FFFFFF;
box-shadow: 3px 3px 1px #000000;
border:2px solid;
WIDTH: calc(60px + (320 - 60) * ((100vw - 300px) / (1600 - 300)));
FONT-WEIGHT: bold;
FONT-SIZE: calc(6px + (24 - 6) * ((100vw - 300px) / (1600 - 300)));
FONT-FAMILY: Courier;
padding-left:3px;
padding-right:3px;
}
div.android_table
{
WIDTH: 100vw;
border:5px solid;
border-radius:5px;
padding-left:3px;
border-color:#<?php echo $SSframe_background; ?>;
background-color:#<?php echo $SSframe_background; ?>;
}
div.android_settings_table
{
WIDTH: 94vw;
border:5px solid;
border-radius:5px;
padding-left:3px;
border-color:#<?php echo $SSframe_background; ?>;
background-color:#<?php echo $SSframe_background; ?>;
}
div.help_info {position:absolute; top:0; left:0; display:none;}

span.android_offbutton
{
background-color:#999999;
box-shadow: 3px 3px 1px #000000;
border:2px solid;
border-radius:5px;
FONT-WEIGHT: bold;
font-color: white;
FONT-SIZE: calc(8px + (14 - 8) * ((100vw - 300px) / (1600 - 300)));
FONT-FAMILY: Courier;
padding-left:3px;
padding-right:3px;
}
span.android_onbutton
{
background-color:#FFFFFF;
box-shadow: 3px 3px 1px #000000;
border:2px solid;
border-radius:5px;
FONT-WEIGHT: bold;
FONT-SIZE: calc(8px + (14 - 8) * ((100vw - 300px) / (1600 - 300)));
FONT-FAMILY: Courier;
padding-left:3px;
padding-right:3px;
}

.button_active {
    background-color: #4CAF50; /* Green */
	border: 1px solid green;
	border-radius: 2px;
    color: white;
    padding: 10px 22px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 16px;
	}

.button_inactive {
    background-color: #4CAF50; /* Green */
    border: 1px solid green;
	border-radius: 2px;
    color: white;
    padding: 10px 22px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 16px;
	opacity: 0.6;
    cursor: not-allowed;
	}
