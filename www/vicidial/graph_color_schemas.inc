<?php

switch ($graph_color_schema) {
	case "99999999": # RED/PALE RED
		$backgroundColor=array("#FF9999", "#FFCCCC");
		$hoverBackgroundColor=array("#FF6666", "#FF9999");
		$hoverBorderColor=array("#FF0000", "#FF3333");
		$graphConstants ="\t\t\t\thoverBorderWidth: 2,\n";
		$graphConstants.="\t\t\t\ttension: 0,\n";
		$graphConstants.="\t\t\t\tfillColor: \"rgba(255,102,102,0.5)\",\n";
		# $graphConstants.="\t\t\t\tbackgroundColor: \"#f99\",\n";
		$graphConstants.="\t\t\t\tborderColor: \"rgba(102,0,0,0.8)\",\n";
		$graphConstants.="\t\t\t\tpointBorderColor: \"rgba(255,0,0,1)\",\n";
		$graphConstants.="\t\t\t\tpointBackgroundColor: \"#f99\",\n";
		$graphConstants.="\t\t\t\tpointHoverBackgroundColor: \"rgba(255,204,204,0.75)\",\n";
		$graphConstants.="\t\t\t\tpointHoverBorderColor: \"rgba(255,102,102,1)\"\n";
		break;
	case "88888888": # BLUE
		$backgroundColor=array("#9999FF");
		$hoverBackgroundColor=array("#6666FF");
		$hoverBorderColor=array("#0000FF");
		$graphConstants ="\t\t\t\thoverBorderWidth: 2,\n";
		$graphConstants.="\t\t\t\ttension: 0,\n";
		$graphConstants.="\t\t\t\tfillColor: \"rgba(102,102,255,0.5)\",\n";
		# $graphConstants.="\t\t\t\tbackgroundColor: \"#99f\",\n";
		$graphConstants.="\t\t\t\tborderColor: \"rgba(0,0,102,0.8)\",\n";
		$graphConstants.="\t\t\t\tpointBorderColor: \"rgba(0,0,255,1)\",\n";
		$graphConstants.="\t\t\t\tpointBackgroundColor: \"#99f\",\n";
		$graphConstants.="\t\t\t\tpointHoverBackgroundColor: \"rgba(204,204,255,0.75)\",\n";
		$graphConstants.="\t\t\t\tpointHoverBorderColor: \"rgba(102,102,255,1)\"\n";	
		break;
	case "7777777": # GREEN
		$backgroundColor=array("#99FF99");
		$hoverBackgroundColor=array("#66FF66");
		$hoverBorderColor=array("#00FF00");
		$graphConstants ="\t\t\t\thoverBorderWidth: 2,\n";
		$graphConstants.="\t\t\t\ttension: 0,\n";
		$graphConstants.="\t\t\t\tfillColor: \"rgba(102,255,102,0.5)\",\n";
		# $graphConstants.="\t\t\t\tbackgroundColor: \"#9f9\",\n";
		$graphConstants.="\t\t\t\tborderColor: \"rgba(0,102,0,0.8)\",\n";
		$graphConstants.="\t\t\t\tpointBorderColor: \"rgba(0,255,0,1)\",\n";
		$graphConstants.="\t\t\t\tpointBackgroundColor: \"#9f9\",\n";
		$graphConstants.="\t\t\t\tpointHoverBackgroundColor: \"rgba(204,255,204,0.75)\",\n";
		$graphConstants.="\t\t\t\tpointHoverBorderColor: \"rgba(102,255,102,1)\"\n";	
		break;
	case "66666666": # RED
		$backgroundColor=array("#FF9999");
		$hoverBackgroundColor=array("#FF6666");
		$hoverBorderColor=array("#FF0000");
		$graphConstants ="\t\t\t\thoverBorderWidth: 2,\n";
		$graphConstants.="\t\t\t\ttension: 0,\n";
		$graphConstants.="\t\t\t\tfillColor: \"rgba(255,102,102,0.5)\",\n";
		# $graphConstants.="\t\t\t\tbackgroundColor: \"#f99\",\n";
		$graphConstants.="\t\t\t\tborderColor: \"rgba(102,0,0,0.8)\",\n";
		$graphConstants.="\t\t\t\tpointBorderColor: \"rgba(255,0,0,1)\",\n";
		$graphConstants.="\t\t\t\tpointBackgroundColor: \"#f99\",\n";
		$graphConstants.="\t\t\t\tpointHoverBackgroundColor: \"rgba(255,204,204,0.75)\",\n";
		$graphConstants.="\t\t\t\tpointHoverBorderColor: \"rgba(255,102,102,1)\"\n";
	default:
		$color_stmt="select menu_background, frame_background, std_row1_background, std_row2_background, std_row3_background, std_row4_background, std_row5_background from system_settings s, vicidial_screen_colors v where s.admin_screen_colors=v.colors_id and length(frame_background)=6 and length(menu_background)=6 limit 1;";
		$color_rslt=mysql_to_mysqli($color_stmt, $link);
		if(mysqli_num_rows($color_rslt) > 0) {
			$color_row=mysqli_fetch_array($color_rslt);

			$backgroundColor=array("$color_row[std_row1_background]", "$color_row[std_row2_background]", "$color_row[std_row3_background]", "$color_row[std_row4_background]", "$color_row[std_row5_background]");
			array_filter($backgroundColor);
			for ($ci=0; $ci<count($backgroundColor); $ci++) {
				$backgroundColor[$ci]="#".$backgroundColor[$ci];
			}

			$rgbmenu=hex2rgb("#$color_row[menu_background]");
			$rgbframe=hex2rgb("#$color_row[frame_background]");

			$hoverBackgroundColor=$backgroundColor;
			$hoverBorderColor=array("#$color_row[menu_background]");
			$graphConstants ="\t\t\t\thoverBorderWidth: 2,\n";
			$graphConstants.="\t\t\t\ttension: 0,\n";
			$graphConstants.="\t\t\t\tfillColor: \"rgba(".implode(",", $rgbframe).",0.5)\",\n";
			$graphConstants.="\t\t\t\tborderColor: \"rgba(".implode(",", $rgbmenu).",0.8)\",\n";
			$graphConstants.="\t\t\t\tpointBorderColor: \"rgba(".implode(",", $rgbmenu).",1)\",\n";
			$graphConstants.="\t\t\t\tpointBackgroundColor: \"".$backgroundColor[0]."\",\n";
			$graphConstants.="\t\t\t\tpointHoverBackgroundColor: \"rgba(".implode(",", $rgbmenu).",0.75)\",\n";
			$graphConstants.="\t\t\t\tpointHoverBorderColor: \"rgba(".implode(",", $rgbframe).",1)\"\n";	
		} else {
			$backgroundColor=array("#9999FF");
			$hoverBackgroundColor=array("#6666FF");
			$hoverBorderColor=array("#0000FF");
			$graphConstants ="\t\t\t\thoverBorderWidth: 2,\n";
			$graphConstants.="\t\t\t\ttension: 0,\n";
			$graphConstants.="\t\t\t\tfillColor: \"rgba(102,102,255,0.5)\",\n";
			# $graphConstants.="\t\t\t\tbackgroundColor: \"#99f\",\n";
			$graphConstants.="\t\t\t\tborderColor: \"rgba(0,0,102,0.8)\",\n";
			$graphConstants.="\t\t\t\tpointBorderColor: \"rgba(0,0,255,1)\",\n";
			$graphConstants.="\t\t\t\tpointBackgroundColor: \"#99f\",\n";
			$graphConstants.="\t\t\t\tpointHoverBackgroundColor: \"rgba(204,204,255,0.75)\",\n";
			$graphConstants.="\t\t\t\tpointHoverBorderColor: \"rgba(102,102,255,1)\"\n";	
		}
		break;

} 
?>
