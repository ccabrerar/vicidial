<STYLE type="text/css">
<?php
$color_stmt="select menu_background, frame_background, std_row1_background, std_row2_background, std_row3_background, std_row4_background, std_row5_background from system_settings s, vicidial_screen_colors v where s.admin_screen_colors=v.colors_id and length(frame_background)=6 and length(menu_background)=6 limit 1;";
$color_rslt=mysql_to_mysqli($color_stmt, $link);

if (file_exists('options.php'))
	{
	require('options.php');
	$init_height=$graph_canvas_size;  # Chart has a minimum height/width of 600 by default
	}
if (strlen($init_height)<2)
	{$init_height=600;}

if(mysqli_num_rows($color_rslt) > 0) {
	$color_row=mysqli_fetch_array($color_rslt);

	$rgbbutton=hex2rgb("#$color_row[std_row1_background]");
	$bcolor="#FFFFFF";
	$scolor="#000000";
	for ($cs=0; $cs<count($rgbbutton); $cs++) {
		if ($rgbbutton[$cs]>=204) {
			$bcolor="#000000";
			$scolor="#FFFFFF";
		}
	}

	echo ".ChartJSButton {\n";
	echo "\tbackground-color:#".$color_row["std_row1_background"].";\n";
	echo "\t-moz-border-radius:14px;\n";
	echo "\t-webkit-border-radius:14px;\n";
	echo "\tborder-radius:14px;\n";
	echo "\tborder:4px solid #".$color_row["std_row2_background"].";\n";
	echo "\tdisplay:inline-block;\n";
	echo "\tcursor:pointer;\n";
	echo "\tcolor:".$bcolor.";\n";
	echo "\tfont-family:Arial;\n";
	echo "\tfont-size:10px;\n";
	echo "\tfont-weight:bold;\n";
	echo "\tpadding:8px;\n";
	echo "\twidth:".floor($init_height/5)."px;\n";
	echo "\ttext-decoration:none;\n";
	echo "\ttext-shadow:1px 1px 0px ".$scolor.";\n";
	echo "}\n";

	echo ".ChartJSButton:hover {\n";
	echo "\tbackground-color:#".$color_row["std_row3_background"].";\n";
	echo "}\n";

}
?>
</STYLE>