<?php
# vicidial_stylesheet.php
# 
# Copyright (C) 2017  Matt Florell <vicidial@gmail.com>, Joe Johnson <freewermadmin@gmail.com>    LICENSE: AGPLv2
#
# CSS/PHP file that uses the system-defined screen colors to display report elements
#
# 170830-2123 - First build
# 180501-0045 - New elements added

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
.green {color: black; background-color: #99FF99}
.red {color: black; background-color: #FF9999}
.orange {color: black; background-color: #FFCC99}

.std_row1 {background-color: <?php echo $SSstd_row1_background; ?>}
.std_row2 {background-color: <?php echo $SSstd_row2_background; ?>}
.std_row3 {background-color: <?php echo $SSstd_row3_background; ?>}
.std_row4 {background-color: <?php echo $SSstd_row4_background; ?>}
.std_row5 {background-color: <?php echo $SSstd_row5_background; ?>}
.alt_row1 {background-color: <?php echo $SSalt_row1_background; ?>}
.alt_row2 {background-color: <?php echo $SSalt_row2_background; ?>}
.alt_row3 {background-color: <?php echo $SSalt_row3_background; ?>}

.border2px {border:solid 2px #<?php echo $SSmenu_background; ?>}



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
.required_field {
	font-family: Arial, Sans-Serif;
	font-size: 10px;
	margin-bottom: 3px;
	background-color: #FFCCCC;
	padding: 2px;
	border: groove 2px #990000;
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

div.help_info {position:absolute; top:0; left:0; display:none;}

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
