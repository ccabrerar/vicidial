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


?>
