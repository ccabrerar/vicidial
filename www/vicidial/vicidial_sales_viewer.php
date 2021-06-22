<?php header("Pragma: no-cache"); 
#
# vicidial_sales_viewer.php - VICIDIAL administration page
# 
# 
# Copyright (C) 2017  Joe Johnson,Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 80310-1500 - first build
# 90310-2135 - Added admin header
# 90508-0644 - Changed to PHP long tags
# 130610-1127 - Finalized changing of all ereg instances to preg
# 130615-2357 - Added filtering of input to prevent SQL injection attacks and new user auth
# 130901-0832 - Changed to mysqli PHP functions
# 141007-2147 - Finalized adding QXZ translation to all admin files
# 170217-1213 - Fixed non-latin auth issue #995
# 170526-2044 - Fixed unfilter variables issue #1015
#

if (isset($_GET["dcampaign"]))					{$dcampaign=$_GET["dcampaign"];}
	elseif (isset($_POST["dcampaign"]))			{$dcampaign=$_POST["dcampaign"];}
if (isset($_GET["submit_report"]))				{$submit_report=$_GET["submit_report"];}
	elseif (isset($_POST["submit_report"]))		{$submit_report=$_POST["submit_report"];}
if (isset($_GET["list_ids"]))					{$list_ids=$_GET["list_ids"];}
	elseif (isset($_POST["list_ids"]))			{$list_ids=$_POST["list_ids"];}
if (isset($_GET["sales_number"]))				{$sales_number=$_GET["sales_number"];}
	elseif (isset($_POST["sales_number"]))		{$sales_number=$_POST["sales_number"];}
if (isset($_GET["sales_time_frame"]))			{$sales_time_frame=$_GET["sales_time_frame"];}
	elseif (isset($_POST["sales_time_frame"]))	{$sales_time_frame=$_POST["sales_time_frame"];}
if (isset($_GET["forc"]))						{$forc=$_GET["forc"];}
	elseif (isset($_POST["forc"]))				{$forc=$_POST["forc"];}
if (isset($_GET["DB"]))							{$DB=$_GET["DB"];}
	elseif (isset($_POST["DB"]))				{$DB=$_POST["DB"];}

include("dbconnect_mysqli.php");
include("functions.php");

$PHP_AUTH_USER=$_SERVER['PHP_AUTH_USER'];
$PHP_AUTH_PW=$_SERVER['PHP_AUTH_PW'];
$PHP_SELF=$_SERVER['PHP_SELF'];
$PHP_SELF = preg_replace('/\.php.*/i','.php',$PHP_SELF);

#############################################
##### START SYSTEM_SETTINGS LOOKUP #####
$stmt = "SELECT use_non_latin,webroot_writable,outbound_autodial_active,enable_languages,language_method FROM system_settings;";
$rslt=mysql_to_mysqli($stmt, $link);
if ($DB) {echo "$stmt\n";}
$qm_conf_ct = mysqli_num_rows($rslt);
if ($qm_conf_ct > 0)
	{
	$row=mysqli_fetch_row($rslt);
	$non_latin =					$row[0];
	$webroot_writable =				$row[1];
	$SSoutbound_autodial_active =	$row[2];
	$SSenable_languages =			$row[3];
	$SSlanguage_method =			$row[4];
	}
##### END SETTINGS LOOKUP #####
###########################################

if ($non_latin < 1)
	{
	$PHP_AUTH_USER = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_USER);
	$PHP_AUTH_PW = preg_replace('/[^-_0-9a-zA-Z]/', '', $PHP_AUTH_PW);
	}
else
	{
	$PHP_AUTH_PW = preg_replace("/'|\"|\\\\|;/","",$PHP_AUTH_PW);
	$PHP_AUTH_USER = preg_replace("/'|\"|\\\\|;/","",$PHP_AUTH_USER);
	}
$dcampaign = preg_replace('/[^0-9a-zA-Z]/', '', $dcampaign);
$list_ids = preg_replace('/[^\,0-9a-zA-Z]/', '', $list_ids);
$forc = preg_replace('/[^0-9a-zA-Z]/', '', $forc);

$auth=0;
$reports_auth=0;
$admin_auth=0;
$auth_message = user_authorization($PHP_AUTH_USER,$PHP_AUTH_PW,'REPORTS',1,0);
if ($auth_message == 'GOOD')
	{$auth=1;}

if ($auth > 0)
	{
	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 7 and view_reports='1';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$admin_auth=$row[0];

	$stmt="SELECT count(*) from vicidial_users where user='$PHP_AUTH_USER' and user_level > 6 and view_reports='1';";
	if ($DB) {echo "|$stmt|\n";}
	$rslt=mysql_to_mysqli($stmt, $link);
	$row=mysqli_fetch_row($rslt);
	$reports_auth=$row[0];

	if ($reports_auth < 1)
		{
		$VDdisplayMESSAGE = _QXZ("You are not allowed to view reports");
		Header ("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
		}
	if ( ($reports_auth > 0) and ($admin_auth < 1) )
		{
		$ADD=999999;
		$reports_only_user=1;
		}
	}
else
	{
	$VDdisplayMESSAGE = _QXZ("Login incorrect, please try again");
	if ($auth_message == 'LOCK')
		{
		$VDdisplayMESSAGE = _QXZ("Too many login attempts, try again in 15 minutes");
		Header ("Content-type: text/html; charset=utf-8");
		echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$auth_message|\n";
		exit;
		}
	Header("WWW-Authenticate: Basic realm=\"CONTACT-CENTER-ADMIN\"");
	Header("HTTP/1.0 401 Unauthorized");
	echo "$VDdisplayMESSAGE: |$PHP_AUTH_USER|$PHP_AUTH_PW|$auth_message|\n";
	exit;
	}

?>
<html>
<head>
<title>Recent Sales Lookup</title>
</head>
<?php
#include("dbconnect_mysqli.php");
#include("/home/www/phpsubs/stylesheet.inc");
?>
<script language="JavaScript1.2">
function GatherListIDs() {
		var ListIDstr="";
		var ListIDstr2="";
	    for (var i=0; i<document.forms[0].list_id.options.length; i++) {
			ListIDstr2+=document.forms[0].list_id.options[i].value;
			ListIDstr2+=",";
			if (document.forms[0].list_id.options[i].selected) {
				ListIDstr+=document.forms[0].list_id.options[i].value;
				ListIDstr+=",";
			}
		}
		if (ListIDstr.length>0) {
			document.forms[0].list_ids.value=ListIDstr;
		} else {
			document.forms[0].list_ids.value=ListIDstr2;
		}
		return true;
}
</script>
<body bgcolor=white marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>

<?php
	$short_header=1;

	require("admin_header.php");

echo "<TABLE CELLPADDING=4 CELLSPACING=0><TR><TD>";
?>

<form action="<?php echo $PHP_SELF ?>" method=post onSubmit="return GatherListIDs()">
<input type="hidden" name="list_ids">
<input type="hidden" name="DB" value="<?php echo $DB ?>">
<table border=0 cellpadding=5 cellspacing=0 align=center width=600>
<tr>
	<th colspan=3><font class="standard_bold"><?php echo _QXZ("OUTBOUND recent sales report"); ?> &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; </font><font class="standard"><a href="admin.php?ADD=999999"><?php echo _QXZ("Back to Admin"); ?></a></font>
	<BR></th>
</tr>
<tr bgcolor='#CCCCCC'>
	<td colspan=3>
		<table width=100%>
			<td align=right width=200 nowrap><font class='standard_bold'><?php echo _QXZ("Select a campaign"); ?>:</td>
			<td align=left><select name="dcampaign" onChange="this.form.submit();">
			<?php 
			if ($dcampaign) {
				$stmt="select campaign_id, campaign_name from vicidial_campaigns where campaign_id='$dcampaign'";
				$rslt=mysql_to_mysqli($stmt, $link);
				while ($row=mysqli_fetch_array($rslt)) {
					print "\t\t<option value='$row[campaign_id]' selected>$row[campaign_id] - $row[campaign_name]</option>\n";
				}
			} 
			?>
			<option value=''>---------------------------------</option>
			<?php
				$stmt="select distinct vc.campaign_id, vc.campaign_name from vicidial_campaigns vc, vicidial_lists vl where vc.campaign_id=vl.campaign_id order by vc.campaign_name asc";
				$rslt=mysql_to_mysqli($stmt, $link);
				while ($row=mysqli_fetch_array($rslt)) {
					print "\t\t<option value='$row[campaign_id]'>$row[campaign_id] - $row[campaign_name]</option>\n";
				}
			?>
			</select></td>
			</tr>
			<?php
			if ($dcampaign) {
			?>
			<tr bgcolor='#CCCCCC'>
				<td align=right width=200 nowrap><font class='standard_bold'><?php echo _QXZ("Select list ID(s) # (optional)"); ?>:</td>
				<td align=left><select name="list_id" multiple size="4">
				<?php
					$stmt="select list_id, list_name from vicidial_lists where campaign_id='$dcampaign' order by list_id asc";
					$rslt=mysql_to_mysqli($stmt, $link);
					while ($row=mysqli_fetch_array($rslt)) {
						print "\t\t<option value='$row[list_id]'>$row[list_id] - $row[list_name]</option>\n";
					}
				?>
				</select></td>
			</tr>
			<?php
				}
			?>
		</table>
	</td>
</tr>
<tr bgcolor='#EEEEEE'>
	<th align=left width=350><font class='standard_bold'><?php echo _QXZ("View sales made within the last"); ?> <select name="sales_time_frame">
	<option value=''>----------</option>
	<option value='15'>15 <?php echo _QXZ("minutes"); ?></option>
	<option value='30'>30 <?php echo _QXZ("minutes"); ?></option>
	<option value='45'>45 <?php echo _QXZ("minutes"); ?></option>
	<option value='60'>1 <?php echo _QXZ("hour"); ?></option>
	<option value='120'>2 <?php echo _QXZ("hours"); ?></option>
	<option value='180'>3 <?php echo _QXZ("hours"); ?></option>
	<option value='240'>4 <?php echo _QXZ("hours"); ?></option>
	<option value='360'>6 <?php echo _QXZ("hours"); ?></option>
	<option value='480'>8 <?php echo _QXZ("hours"); ?></option>
	</select></th>
	<th width=50><?php echo _QXZ("OR"); ?>...</th>
	<th align=right width=200><font class='standard_bold'><?php echo _QXZ("View the last"); ?> <input type=text size=5 maxlength=5 name="sales_number"> <?php echo _QXZ("sales"); ?>**</font></th>
</tr>
<tr bgcolor='#EEEEEE'><th colspan=3><font class="small_standard">(<?php echo _QXZ("If you enter values in both fields, the results will be limited by the first criteria met"); ?>)</font></th></tr>
<tr bgcolor='#CCCCCC'>
	<td align=right><font class="standard_bold"><?php echo _QXZ("Campaign is"); ?>:&nbsp;&nbsp;&nbsp;&nbsp;<input type=radio name="forc" value="F"><?php echo _QXZ("Transfer"); ?></font></td>
	<td colspan=2 align=left><font class="standard_bold">&nbsp;&nbsp;<input type=radio name="forc" value="C" checked><?php echo _QXZ("Non-transfer"); ?></font></td>
</tr>
<tr><th colspan=3><input style='background-color:#<?php echo "$SSbutton_color"; ?>' type=submit name="submit_report" value="<?php echo _QXZ("SUBMIT"); ?>"></th></tr>
<!-- <tr><th colspan=3><input type=checkbox name="weekly_report" value="WEEKLY_REPORT"><font class="small_standard">Generate weekly report</font></th></tr> //-->
<tr><td colspan=3 align=center><font class="small_standard">** - <?php echo _QXZ("sorted by call date"); ?></font></td></tr>
</table>
</form>
<?php
if ($submit_report && $list_ids) {

	$now=date("YmdHis");
	$list_id_clause="and v.list_id in (";
	$lists=explode(",", $list_ids);
	for ($i=0; $i<count($lists); $i++) {
		if (strlen($lists[$i]>0)) {	$list_id_clause.="$lists[$i], "; }
	}
	$list_id_clause=substr($list_id_clause, 0, -2);
	$list_id_clause.=")";

	if ($sales_number && $sales_number>0) {
		$sales_number=preg_replace('/[^0-9]/i', '', $sales_number);
		$limit_clause="limit $sales_number";
	} else {
		$sales_number=0;
		$limit_clause="";
	}
	if ($sales_time_frame && $sales_time_frame>0) {
		$hours=$sales_time_frame/60;
		$timestamp=date("YmdHis", mktime(date("H"),(date("i")-$sales_time_frame),date("s"),date("m"),date("d"),date("Y")));
	} else {
		$timestamp=date("YmdHis", mktime(date("H"),date("i"),date("s"),date("m"),(date("d")-1),date("Y")));
		$hours=24;
	}
	print "<HR>";
	print "<table border=0 cellpadding=5 cellspacing=0 align=center>";
	$i=0;

	$dfile=fopen("discover_stmts.txt", "w");
	if ($forc=="C") {
		$stmt="select v.first_name, v.last_name, v.phone_number, vl.call_date, v.lead_id, u.full_name from vicidial_users u, vicidial_list v, vicidial_log vl where vl.call_date>='$timestamp' and vl.lead_id=v.lead_id and v.status='SALE' $list_id_clause and vl.user=u.user order by call_date desc $limit_clause";
	} else {
		$stmt="select v.first_name, v.last_name, v.phone_number, vl.call_date, v.lead_id, vl.user, vl.closer from vicidial_list v, vicidial_xfer_log vl where vl.call_date>='$timestamp' and vl.lead_id=v.lead_id and v.status='SALE' $list_id_clause order by call_date desc $limit_clause";
	}
	if ( ($webroot_writable > 0) and ($DB>0) )
		{fwrite($dfile, "$stmt\n");}
	$rslt=mysql_to_mysqli($stmt, $link);
	$q=0;
	print "<tr bgcolor='#000000'><th colspan=8><font class='standard_bold' color='white'>Last ".mysqli_num_rows($rslt)." "._QXZ("sales made")."</font></th></tr>\n";
	print "<tr bgcolor='#000000'>\n";
	print "\t<th><font class='standard_bold' color='white'>"._QXZ("Sales Rep(s)")."</font></th>\n";
	print "\t<th><font class='standard_bold' color='white'>"._QXZ("Customer Name")."</font></th>\n";
	print "\t<th><font class='standard_bold' color='white'>"._QXZ("Phone")."</font></th>\n";
	print "\t<th><font class='standard_bold' color='white'>"._QXZ("Recording ID")."</font></th>\n";
	print "\t<th><font class='standard_bold' color='white'>"._QXZ("Timestamp")."</font></th>\n";
	print "</tr>\n";
	while ($row=mysqli_fetch_row($rslt)) {
		$rec_stmt="select max(recording_id) from recording_log where lead_id='$row[4]'";
		$rec_rslt=mysql_to_mysqli($rec_stmt, $link);
		$rec_row=mysqli_fetch_row($rec_rslt);

		if ($forc=="F") {
			$rep_stmt="select full_name from vicidial_users where user='$row[5]'";
			$rep_rslt=mysql_to_mysqli($rep_stmt, $link);
			$fr_row=mysqli_fetch_array($rep_rslt);

			$rep_stmt="select full_name from vicidial_users where user='$row[6]'";
			$rep_rslt=mysql_to_mysqli($rep_stmt, $link);
			$cl_row=mysqli_fetch_array($rep_rslt);

			$rep_name="$fr_row[full_name]/$cl_row[full_name]";
		} else {
			$rep_name=$row[5];
		}

		if ($i%2==0) {$bgcolor="#999999";} else {$bgcolor="#CCCCCC";}
		print "<tr bgcolor='$bgcolor'>\n";
		print "\t<th><font class='standard_bold'>$rep_name</font></th>\n";
		print "\t<th><font class='standard_bold'>$row[0] $row[1]</font></th>\n";
		print "\t<th><font class='standard_bold'>$row[2]</font></th>\n";
		print "\t<th><font class='standard_bold'>$rec_row[0]</font></th>\n";
		print "\t<th><font class='standard_bold'>$row[3]</font></th>\n";
		print "</tr>\n";
		flush();
	}
	print "</table>";
	passthru("$WeBServeRRooT/vicidial/spreadsheet_sales_viewer.pl $list_ids $sales_number $timestamp $forc $now $dcampaign");
#	print "\n\n<BR>$WeBServeRRooT/vicidial/spreadsheet_sales_viewer.pl $list_ids $sales_number $timestamp $forc $now $dcampaign<BR>\n";
	flush();
	print "<table align=center border=0 cellpadding=3 cellspacing=5 width=700><tr bgcolor='#CCCCCC'>";
	if ($forc=="F") {
		print "<th width='50%'><font class='standard_bold'><a href='vicidial_fronter_report_$now.xls'>"._QXZ("View complete Excel fronter report for this shift")."</a></font></th>";
	}
	print "<th width='50%'><font class='standard_bold'><a href='vicidial_closer_report_$now.xls'>"._QXZ("View complete Excel sales report for this shift")."</a></font></th>";
	print "</tr></table>";
}
?>

</TD></TR></TABLE>

</body>
</html>

