<?php
##### CALCULATE COMPLETE LEADS #####
function complete_leads($DB,$link,$dial_statuses,$camp_lists,$call_count_limit,$single_status,$campaign_id)
{
if (isset($camp_lists))
	{
	if (strlen($camp_lists)>1)
		{
		if (strlen($dial_statuses)>2)
			{
			$dial_statuses = preg_replace("/ -$/","",$dial_statuses);
			$Dstatuses = explode(" ", $dial_statuses);
			$Ds_to_print = (count($Dstatuses) - 0);
			$Dsql = '';
			$o=0;
			while ($Ds_to_print > $o) 
				{
				$o++;
				$Dsql .= "'$Dstatuses[$o]',";
				}
			$Dsql = preg_replace("/,$/","",$Dsql);
			if (strlen($Dsql) < 2) {$Dsql = "''";}

			$CCLsql = "(called_count < 0) or";
			if ($call_count_limit > 0)
				{$CCLsql = "(called_count >= $call_count_limit) or";}

			$complete_statuses='';
			$stmt="SELECT status from vicidial_statuses where completed='Y';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$statuses_to_print = mysqli_num_rows($rslt);
			$q=0;
			while ($statuses_to_print > $q) 
				{
				$rowx=mysqli_fetch_row($rslt);
				$complete_statuses.="'$rowx[0]',";
				$q++;
				}
			$stmt="SELECT status from vicidial_campaign_statuses where completed='Y';";
			$rslt=mysql_to_mysqli($stmt, $link);
			$statuses_to_print = mysqli_num_rows($rslt);
			$q=0;
			while ($statuses_to_print > $q) 
				{
				$rowx=mysqli_fetch_row($rslt);
				$complete_statuses.="'$rowx[0]',";
				$q++;
				}
			$complete_statuses = preg_replace("/,$/","",$complete_statuses);
			if (strlen($complete_statuses) < 2) {$complete_statuses = "''";}
			$CSsql = "status IN($complete_statuses)";

			$stmt="SELECT count(*) FROM vicidial_list where ( (status IN($Dsql)) and (list_id IN($camp_lists)) and ( $CCLsql ($CSsql) ) );";
			#$DB=1;
			if ($DB) {$HTML_header.="$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$rslt_rows = mysqli_num_rows($rslt);
			if ($rslt_rows)
				{
				$rowx=mysqli_fetch_row($rslt);
				$complete_leads = $rowx[0];
				}
			else {$complete_leads = '0';}

			if ($DB > 0) {$HTML_header.="|$DB|\n";}
			if ($single_status > 0)
				{return $complete_leads;}
			else
				{$HTML_header.=_QXZ("There are")." $complete_leads "._QXZ("completed leads in those lists")."\n";}
			}
		else
			{
			$HTML_header.=_QXZ("no dial statuses selected for this campaign")."\n";
			}
		}
	else
		{
		$HTML_header.=_QXZ("no active lists selected for this campaign")."\n";
		}
	}
else
	{
	$HTML_header.=_QXZ("no active lists selected for this campaign")."\n";
	}
}


##### CALCULATE DIALABLE LEADS #####
function dialable_leads($DB,$link,$local_call_time,$dial_statuses,$camp_lists,$drop_lockout_time,$call_count_limit,$single_status,$fSQL)
{

global $full_dialable_SQL;

##### BEGIN calculate what gmt_offset_now values are within the allowed local_call_time setting ###
if (isset($camp_lists))
	{
	if (strlen($camp_lists)>1)
		{
		if (strlen($dial_statuses)>2)
			{
			$g=0;
			$p='13';
			$GMT_gmt[0] = '';
			$GMT_hour[0] = '';
			$GMT_day[0] = '';
			while ($p > -13)
				{
				$pzone=3600 * $p;
				$pmin=(gmdate("i", time() + $pzone));
				$phour=( (gmdate("G", time() + $pzone)) * 100);
				$pday=gmdate("w", time() + $pzone);
				$tz = sprintf("%.2f", $p);	
				$GMT_gmt[$g] = "$tz";
				$GMT_day[$g] = "$pday";
				$GMT_hour[$g] = ($phour + $pmin);
				$p = ($p - 0.25);
				$g++;
				}

			$stmt="SELECT call_time_id,call_time_name,call_time_comments,ct_default_start,ct_default_stop,ct_sunday_start,ct_sunday_stop,ct_monday_start,ct_monday_stop,ct_tuesday_start,ct_tuesday_stop,ct_wednesday_start,ct_wednesday_stop,ct_thursday_start,ct_thursday_stop,ct_friday_start,ct_friday_stop,ct_saturday_start,ct_saturday_stop,ct_state_call_times FROM vicidial_call_times where call_time_id='$local_call_time';";
			if ($DB) {$HTML_header.="$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$rowx=mysqli_fetch_row($rslt);
			$Gct_default_start =	$rowx[3];
			$Gct_default_stop =	$rowx[4];
			$Gct_sunday_start =	$rowx[5];
			$Gct_sunday_stop =	$rowx[6];
			$Gct_monday_start =	$rowx[7];
			$Gct_monday_stop =	$rowx[8];
			$Gct_tuesday_start =	$rowx[9];
			$Gct_tuesday_stop =	$rowx[10];
			$Gct_wednesday_start =	$rowx[11];
			$Gct_wednesday_stop =	$rowx[12];
			$Gct_thursday_start =	$rowx[13];
			$Gct_thursday_stop =	$rowx[14];
			$Gct_friday_start =	$rowx[15];
			$Gct_friday_stop =	$rowx[16];
			$Gct_saturday_start =	$rowx[17];
			$Gct_saturday_stop =	$rowx[18];
			$Gct_state_call_times = $rowx[19];

			$ct_states = '';
			$ct_state_gmt_SQL = '';
			$ct_srs=0;
			$b=0;
			if (strlen($Gct_state_call_times)>2)
				{
				$state_rules = explode('|',$Gct_state_call_times);
				$ct_srs = ((count($state_rules)) - 2);
				}
			while($ct_srs >= $b)
				{
				if (strlen($state_rules[$b])>1)
					{
					$stmt="SELECT state_call_time_id,state_call_time_state,state_call_time_name,state_call_time_comments,sct_default_start,sct_default_stop,sct_sunday_start,sct_sunday_stop,sct_monday_start,sct_monday_stop,sct_tuesday_start,sct_tuesday_stop,sct_wednesday_start,sct_wednesday_stop,sct_thursday_start,sct_thursday_stop,sct_friday_start,sct_friday_stop,sct_saturday_start,sct_saturday_stop from vicidial_state_call_times where state_call_time_id='$state_rules[$b]';";
					$rslt=mysql_to_mysqli($stmt, $link);
					$row=mysqli_fetch_row($rslt);
					$Gstate_call_time_id =		$row[0];
					$Gstate_call_time_state =	$row[1];
					$Gsct_default_start =		$row[4];
					$Gsct_default_stop =		$row[5];
					$Gsct_sunday_start =		$row[6];
					$Gsct_sunday_stop =		$row[7];
					$Gsct_monday_start =		$row[8];
					$Gsct_monday_stop =		$row[9];
					$Gsct_tuesday_start =		$row[10];
					$Gsct_tuesday_stop =		$row[11];
					$Gsct_wednesday_start =		$row[12];
					$Gsct_wednesday_stop =		$row[13];
					$Gsct_thursday_start =		$row[14];
					$Gsct_thursday_stop =		$row[15];
					$Gsct_friday_start =		$row[16];
					$Gsct_friday_stop =		$row[17];
					$Gsct_saturday_start =		$row[18];
					$Gsct_saturday_stop =		$row[19];

					$ct_states .="'$Gstate_call_time_state',";

					$r=0;
					$state_gmt='';
					while($r < $g)
						{
						if ($GMT_day[$r]==0)	#### Sunday local time
							{
							if (($Gsct_sunday_start==0) and ($Gsct_sunday_stop==0))
								{
								if ( ($GMT_hour[$r]>=$Gsct_default_start) and ($GMT_hour[$r]<$Gsct_default_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							else
								{
								if ( ($GMT_hour[$r]>=$Gsct_sunday_start) and ($GMT_hour[$r]<$Gsct_sunday_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							}
						if ($GMT_day[$r]==1)	#### Monday local time
							{
							if (($Gsct_monday_start==0) and ($Gsct_monday_stop==0))
								{
								if ( ($GMT_hour[$r]>=$Gsct_default_start) and ($GMT_hour[$r]<$Gsct_default_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							else
								{
								if ( ($GMT_hour[$r]>=$Gsct_monday_start) and ($GMT_hour[$r]<$Gsct_monday_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							}
						if ($GMT_day[$r]==2)	#### Tuesday local time
							{
							if (($Gsct_tuesday_start==0) and ($Gsct_tuesday_stop==0))
								{
								if ( ($GMT_hour[$r]>=$Gsct_default_start) and ($GMT_hour[$r]<$Gsct_default_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							else
								{
								if ( ($GMT_hour[$r]>=$Gsct_tuesday_start) and ($GMT_hour[$r]<$Gsct_tuesday_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							}
						if ($GMT_day[$r]==3)	#### Wednesday local time
							{
							if (($Gsct_wednesday_start==0) and ($Gsct_wednesday_stop==0))
								{
								if ( ($GMT_hour[$r]>=$Gsct_default_start) and ($GMT_hour[$r]<$Gsct_default_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							else
								{
								if ( ($GMT_hour[$r]>=$Gsct_wednesday_start) and ($GMT_hour[$r]<$Gsct_wednesday_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							}
						if ($GMT_day[$r]==4)	#### Thursday local time
							{
							if (($Gsct_thursday_start==0) and ($Gsct_thursday_stop==0))
								{
								if ( ($GMT_hour[$r]>=$Gsct_default_start) and ($GMT_hour[$r]<$Gsct_default_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							else
								{
								if ( ($GMT_hour[$r]>=$Gsct_thursday_start) and ($GMT_hour[$r]<$Gsct_thursday_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							}
						if ($GMT_day[$r]==5)	#### Friday local time
							{
							if (($Gsct_friday_start==0) and ($Gsct_friday_stop==0))
								{
								if ( ($GMT_hour[$r]>=$Gsct_default_start) and ($GMT_hour[$r]<$Gsct_default_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							else
								{
								if ( ($GMT_hour[$r]>=$Gsct_friday_start) and ($GMT_hour[$r]<$Gsct_friday_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							}
						if ($GMT_day[$r]==6)	#### Saturday local time
							{
							if (($Gsct_saturday_start==0) and ($Gsct_saturday_stop==0))
								{
								if ( ($GMT_hour[$r]>=$Gsct_default_start) and ($GMT_hour[$r]<$Gsct_default_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							else
								{
								if ( ($GMT_hour[$r]>=$Gsct_saturday_start) and ($GMT_hour[$r]<$Gsct_saturday_stop) )
									{$state_gmt.="'$GMT_gmt[$r]',";}
								}
							}
						$r++;
						}
					$state_gmt = "$state_gmt'99'";
					$ct_state_gmt_SQL .= "or (state='$Gstate_call_time_state' and gmt_offset_now IN($state_gmt)) ";
					}

				$b++;
				}
			if (strlen($ct_states)>2)
				{
				$ct_states = eregi_replace(",$",'',$ct_states);
				$ct_statesSQL = "and state NOT IN($ct_states)";
				}
			else
				{
				$ct_statesSQL = "";
				}

			$r=0;
			$default_gmt='';
			while($r < $g)
				{
				if ($GMT_day[$r]==0)	#### Sunday local time
					{
					if (($Gct_sunday_start==0) and ($Gct_sunday_stop==0))
						{
						if ( ($GMT_hour[$r]>=$Gct_default_start) and ($GMT_hour[$r]<$Gct_default_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					else
						{
						if ( ($GMT_hour[$r]>=$Gct_sunday_start) and ($GMT_hour[$r]<$Gct_sunday_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					}
				if ($GMT_day[$r]==1)	#### Monday local time
					{
					if (($Gct_monday_start==0) and ($Gct_monday_stop==0))
						{
						if ( ($GMT_hour[$r]>=$Gct_default_start) and ($GMT_hour[$r]<$Gct_default_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					else
						{
						if ( ($GMT_hour[$r]>=$Gct_monday_start) and ($GMT_hour[$r]<$Gct_monday_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					}
				if ($GMT_day[$r]==2)	#### Tuesday local time
					{
					if (($Gct_tuesday_start==0) and ($Gct_tuesday_stop==0))
						{
						if ( ($GMT_hour[$r]>=$Gct_default_start) and ($GMT_hour[$r]<$Gct_default_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					else
						{
						if ( ($GMT_hour[$r]>=$Gct_tuesday_start) and ($GMT_hour[$r]<$Gct_tuesday_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					}
				if ($GMT_day[$r]==3)	#### Wednesday local time
					{
					if (($Gct_wednesday_start==0) and ($Gct_wednesday_stop==0))
						{
						if ( ($GMT_hour[$r]>=$Gct_default_start) and ($GMT_hour[$r]<$Gct_default_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					else
						{
						if ( ($GMT_hour[$r]>=$Gct_wednesday_start) and ($GMT_hour[$r]<$Gct_wednesday_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					}
				if ($GMT_day[$r]==4)	#### Thursday local time
					{
					if (($Gct_thursday_start==0) and ($Gct_thursday_stop==0))
						{
						if ( ($GMT_hour[$r]>=$Gct_default_start) and ($GMT_hour[$r]<$Gct_default_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					else
						{
						if ( ($GMT_hour[$r]>=$Gct_thursday_start) and ($GMT_hour[$r]<$Gct_thursday_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					}
				if ($GMT_day[$r]==5)	#### Friday local time
					{
					if (($Gct_friday_start==0) and ($Gct_friday_stop==0))
						{
						if ( ($GMT_hour[$r]>=$Gct_default_start) and ($GMT_hour[$r]<$Gct_default_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					else
						{
						if ( ($GMT_hour[$r]>=$Gct_friday_start) and ($GMT_hour[$r]<$Gct_friday_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					}
				if ($GMT_day[$r]==6)	#### Saturday local time
					{
					if (($Gct_saturday_start==0) and ($Gct_saturday_stop==0))
						{
						if ( ($GMT_hour[$r]>=$Gct_default_start) and ($GMT_hour[$r]<$Gct_default_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					else
						{
						if ( ($GMT_hour[$r]>=$Gct_saturday_start) and ($GMT_hour[$r]<$Gct_saturday_stop) )
							{$default_gmt.="'$GMT_gmt[$r]',";}
						}
					}
				$r++;
				}

			$default_gmt = "$default_gmt'99'";
			$all_gmtSQL = "(gmt_offset_now IN($default_gmt) $ct_statesSQL) $ct_state_gmt_SQL";

			$dial_statuses = preg_replace("/ -$/","",$dial_statuses);
			$Dstatuses = explode(" ", $dial_statuses);
			$Ds_to_print = (count($Dstatuses) - 0);
			$Dsql = '';
			$o=0;
			while ($Ds_to_print > $o) 
				{
				$o++;
				$Dsql .= "'$Dstatuses[$o]',";
				}
			$Dsql = preg_replace("/,$/","",$Dsql);
			if (strlen($Dsql) < 2) {$Dsql = "''";}

			$DLTsql='';
			if ($drop_lockout_time > 0)
				{
				$DLseconds = ($drop_lockout_time * 3600);
				$DLseconds = floor($DLseconds);
				$DLseconds = intval("$DLseconds");
				$DLTsql = "and ( ( (status IN('DROP','XDROP')) and (last_local_call_time < CONCAT(DATE_ADD(NOW(), INTERVAL -$DLseconds SECOND),' ',CURTIME()) ) ) or (status NOT IN('DROP','XDROP')) )";
				}

			$CCLsql='';
			if ($call_count_limit > 0)
				{
				$CCLsql = "and (called_count < $call_count_limit)";
				}

			$stmt="SELECT count(*) FROM vicidial_list where status IN($Dsql) and list_id IN($camp_lists) and ($all_gmtSQL) $CCLsql $DLTsql $fSQL";
			$full_dialable_SQL="status IN($Dsql) and list_id IN($camp_lists) and ($all_gmtSQL) $CCLsql $DLTsql $fSQL";
			#$DB=1;
			if ($DB) {$HTML_header.="$stmt\n";}
			$rslt=mysql_to_mysqli($stmt, $link);
			$rslt_rows = mysqli_num_rows($rslt);
			if ($rslt_rows)
				{
				$rowx=mysqli_fetch_row($rslt);
				$active_leads = "$rowx[0]";
				}
			else {$active_leads = '0';}

			if ($DB > 0) {$HTML_header.="|$DB|\n";}
			if ($single_status > 0)
				{return $active_leads;}
			else
				{$HTML_header.=_QXZ("This campaign has $active_leads leads to be dialed in those lists")."\n";}
			}
		else
			{
			$HTML_header.=_QXZ("no dial statuses selected for this campaign")."\n";
			}
		}
	else
		{
		$HTML_header.=_QXZ("no active lists selected for this campaign")."\n";
		}
	}
else
	{
	$HTML_header.=_QXZ("no active lists selected for this campaign")."\n";
	}
##### END calculate what gmt_offset_now values are within the allowed local_call_time setting ###
}
?>
