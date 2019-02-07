<?php
# audit_comments.php
# 
# Copyright (C) 2014  poundteam.com,vicidial.org    LICENSE: AGPLv2
#
# This script is designed to display QC audit comments, contributed by poundteam.com
#
# changes:
# 121116-1322 - First build, added to vicidial codebase
# 130802-0957 - Changed to PHP mysqli functions
# 140304-2154 - Enabled special characters in comments
# 141120-1053 - Added user name and date and flag for admin user for comment log display, code cleanup
#

require_once("functions.php");

function audit_comments($lead_id,$list_id,$format,$user,$mel,$NOW_TIME,$link,$server_ip,$session_name,$one_mysql_log,$campaign) {
    $audit_comments_active=audit_comments_active($list_id,$format,$user,$mel,$NOW_TIME,$link,$server_ip,$session_name,$one_mysql_log);
    if ($audit_comments_active) 
		{
        //Get comment from list
        $stmt="SELECT comments from vicidial_list where lead_id='$lead_id' limit 1;";
        if ($format=='debug') 
			{echo "\n<!-- $stmt -->";}
        $rslt=mysql_to_mysqli($stmt, $link);
        if ($mel > 0) 
			{mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'00142-AuditComments2',$user,$server_ip,$session_name,$one_mysql_log);}
        $row=mysqli_fetch_row($rslt);
        if (strlen($row[0]) > 0) 
			{
            $comment=$row[0];
            //Put comment in comment table
            $stmt="INSERT INTO vicidial_comments (lead_id,user_id,list_id,campaign_id,comment) VALUES ('$lead_id','$user','$list_id','$campaign','".mysqli_real_escape_string($link, $comment)."');";
            if ($format=='debug') 
				{echo "\n<!-- $stmt -->";}
            $rslt=mysql_to_mysqli($stmt, $link);
            if ($mel > 0) 
				{mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'00142-AuditComments3',$user,$server_ip,$session_name,$one_mysql_log);}
            $affected=mysqli_affected_rows($link);
            if($affected>0) 
				{
                $stmt="UPDATE vicidial_list set comments='' where lead_id='$lead_id';";
                if ($format=='debug') 
					{echo "\n<!-- $stmt -->";}
                $rslt=mysql_to_mysqli($stmt, $link);
                if ($mel > 0) 
					{mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'00142-AuditComments4',$user,$server_ip,$session_name,$one_mysql_log);}
				} 
			else 
				{
                mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'00142-AuditCommentsERROR-Comment not moved',$user,$server_ip,$session_name,$one_mysql_log);
                echo "\n<!-- 00142-AuditCommentsERROR-Comment not moved -->";
				}
			}
		}
	}


function audit_comments_active($list_id,$format,$user,$mel,$NOW_TIME,$link,$server_ip,$session_name,$one_mysql_log)
	{
    $stmt="SELECT count(audit_comments) from vicidial_lists_custom where list_id='$list_id' and audit_comments='1' limit 1;";
    if ($format=='debug') 
		{echo "\n<!-- $stmt -->";}
    $rslt=mysql_to_mysqli($stmt, $link);
    if ($mel > 0) 
		{mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'00142-AuditComments5',$user,$server_ip,$session_name,$one_mysql_log);}
    $row=mysqli_fetch_row($rslt);
    if ($row[0] == '1') 
		{
        return true;
		}
	else 
		{
        return false;
		}
	}


function get_audited_comments($lead_id,$format,$user,$mel,$NOW_TIME,$link,$server_ip,$session_name,$one_mysql_log) 
	{
    global $ACcount;
    global $ACcomments;
    $stmt="SELECT user_id,comment,timestamp from vicidial_comments where lead_id='$lead_id' order by timestamp desc;";
    $rslt=mysql_to_mysqli($stmt, $link);
    if ($mel > 0) 
		{mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'00142-69-AuditComments',$user,$server_ip,$session_name,$one_mysql_log);}
    $ACcount=mysqli_num_rows($rslt);
    if($ACcount>0) 
		{
        $i=0;
        while ($i < $ACcount) 
			{
            $row=mysqli_fetch_row($rslt);
			$C_user[$i] =		$row[0];
			$C_comment[$i] =	$row[1];
			$C_date[$i] =		$row[2];
            $i++;
			}

		$i=0;
        while ($i < $ACcount) 
			{
			$C_name[$i]='Unknown';
			$C_level[$i]='1';
			$FCS=''; $FCE='';
			$stmt="SELECT full_name,user_level from vicidial_users where user='$C_user[$i]';";
			$rslt=mysql_to_mysqli($stmt, $link);
			if ($mel > 0) 
				{mysql_error_logging($NOW_TIME,$link,$mel,$stmt,'00142-70-AuditCommentsUser',$user,$server_ip,$session_name,$one_mysql_log);}
			$Ucount=mysqli_num_rows($rslt);
			if($Ucount>0) 
				{
				$row=mysqli_fetch_row($rslt);
				$C_name[$i] =	$row[0];
				$C_level[$i] =	$row[1];
				}
			if ($C_level[$i] > 7)
				{$FCS='--------ADMINFONTBEGIN--------'; $FCE='--------ADMINFONTEND--------';}
			$ACcomments .=	_QXZ("UserID: $FCS%1s - %2s$FCE,  Date: %3s",0,'',$C_user[$i],$C_name[$i],$C_date[$i])."\n";
            $ACcomments .=	$C_comment[$i];
            $ACcomments .=	"\n----------------------------------\n";
            $i++;
			}
        return true;
		} 
	else 
		{
        return false;
		}
	}
?>
