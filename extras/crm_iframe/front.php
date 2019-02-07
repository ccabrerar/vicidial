<?php
# front.php - first page for CRM example
# 
# Copyright (C) 2015  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGELOG
# 151229-1503 - First Build 
#

require_once("crm_settings.php");

if (isset($_GET["DB"]))						    {$DB=$_GET["DB"];}
        elseif (isset($_POST["DB"]))            {$DB=$_POST["DB"];}
if (isset($_GET["phone_login"]))                {$phone_login=$_GET["phone_login"];}
        elseif (isset($_POST["phone_login"]))   {$phone_login=$_POST["phone_login"];}
if (isset($_GET["phone_pass"]))					{$phone_pass=$_GET["phone_pass"];}
        elseif (isset($_POST["phone_pass"]))    {$phone_pass=$_POST["phone_pass"];}
if (isset($_GET["VD_login"]))					{$VD_login=$_GET["VD_login"];}
        elseif (isset($_POST["VD_login"]))      {$VD_login=$_POST["VD_login"];}
if (isset($_GET["VD_pass"]))					{$VD_pass=$_GET["VD_pass"];}
        elseif (isset($_POST["VD_pass"]))       {$VD_pass=$_POST["VD_pass"];}
if (isset($_GET["VD_campaign"]))                {$VD_campaign=$_GET["VD_campaign"];}
        elseif (isset($_POST["VD_campaign"]))   {$VD_campaign=$_POST["VD_campaign"];}
if (isset($_GET["relogin"]))					{$relogin=$_GET["relogin"];}
        elseif (isset($_POST["relogin"]))       {$relogin=$_POST["relogin"];}

if (!isset($phone_login)) 
	{
	if (isset($_GET["pl"]))                {$phone_login=$_GET["pl"];}
			elseif (isset($_POST["pl"]))   {$phone_login=$_POST["pl"];}
	}
if (!isset($phone_pass))
	{
	if (isset($_GET["pp"]))                {$phone_pass=$_GET["pp"];}
			elseif (isset($_POST["pp"]))   {$phone_pass=$_POST["pp"];}
	}

### security strip all non-alphanumeric characters out of the variables ###
$DB=preg_replace("/[^0-9a-z]/","",$DB);
$phone_login=preg_replace("/[^\,0-9a-zA-Z]/","",$phone_login);
$phone_pass=preg_replace("/[^-_0-9a-zA-Z]/","",$phone_pass);
$VD_login=preg_replace("/\'|\"|\\\\|;| /","",$VD_login);
$VD_pass=preg_replace("/\'|\"|\\\\|;| /","",$VD_pass);
$VD_campaign = preg_replace("/[^-_0-9a-zA-Z]/","",$VD_campaign);

$StarTtimE = date("U");
$NOW_TIME = date("Y-m-d H:i:s");

$PHP_SELF=$_SERVER['PHP_SELF'];


echo "<HTML><BODY BGCOLOR=white><HEAD>\n";
echo "<TITLE>CRM Example Agent Screen</TITLE>\n";
echo "<META HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=utf-8\">\n";


if ( (strlen($phone_login) > 1) and (strlen($phone_pass) > 1) and (strlen($VD_login) > 1) and (strlen($VD_pass) > 1) and (strlen($VD_campaign) > 1) )
	{
	?>

	<script language="Javascript">

	var MTvar;

	// functions to hide and show different DIVs
	function showDiv(divvar) 
		{
		if (document.getElementById(divvar))
			{
			divref = document.getElementById(divvar).style;
			divref.visibility = 'visible';
			}
		}
	function hideDiv(divvar)
		{
		if (document.getElementById(divvar))
			{
			divref = document.getElementById(divvar).style;
			divref.visibility = 'hidden';
			}
		}
	function start_page()
		{
		hideDiv('agentscreencontent');
		}
	function AgentScreenOpen(Slocation,Soperation,Ylocation)
		{
		if (Soperation=='show')
			{
			document.getElementById("agentscreenlink").innerHTML = "<font size=1><a href=\"#\" onclick=\"AgentScreenOpen('agentscreencontent','hide','CRMSpan');return false;\">Hide Full Agent Screen</a>: &nbsp; -</font>";
			showDiv(Slocation);
			hideDiv(Ylocation);
			}
		else
			{
			document.getElementById("agentscreenlink").innerHTML = "<font size=1><a href=\"#\" onclick=\"AgentScreenOpen('agentscreencontent','show','CRMSpan');return false;\">Show Full Agent Screen</a>: &nbsp; +</font>";
			hideDiv(Slocation);
			showDiv(Ylocation);
			}
		}

	</script>


	<?php
	echo "</HEAD>\n";
	echo "<BODY BGCOLOR=#FFFFFF marginheight=0 marginwidth=0 leftmargin=0 topmargin=0 onload=\"start_page();\">\n";

	$crm_screen_content = "<iframe src=\"$crm_url&VD_login=$VD_login\" style=\"width:" . $crm_screen_width . "px;height:" . $crm_screen_height . "px;background-color:transparent;z-index:10;\" scrolling=\"auto\" frameborder=\"0\" allowtransparency=\"true\" id=\"" . $frame_id . "\" name=\"" . $frame_id . "\" width=\"" . $crm_screen_width . "px\" height=\"" . $crm_screen_height . "px\"> </iframe>";

	echo "<span style=\"position:absolute;left:0px;top:25px;height:" . $crm_screen_height . "px;overflow:scroll;z-index:11;background-color:white;\" id=\"CRMSpan\"><table cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td width=\"5px\" rowspan=\"2\">&nbsp;</td><td align=\"center\"><font class=\"body_text\">
	CRM Screen: &nbsp; </font></td></tr><tr><td align=\"center\"><span id=\"crmscreencontent\">$crm_screen_content</span></td></tr></table></span>\n";

	
	$agentURL = "$agent_screen_url?phone_login=$phone_login&phone_pass=$phone_pass&DB=$DB&VD_login=$VD_login&VD_pass=$VD_pass&VD_campaign=$VD_campaign&relogin=$relogin";

	$agent_screen_content = "<iframe src=\"$agentURL\" style=\"width:" . $agent_screen_width . "px;height:" . $agent_screen_height . "px;background-color:transparent;z-index:22;\" scrolling=\"auto\" frameborder=\"0\" allowtransparency=\"true\" id=\"agent_screen\" name=\"agent_screen\" width=\"" . $agent_screen_width . "px\" height=\"" . $agent_screen_height . "px\"> </iframe>";

	echo "<span style=\"position:absolute;left:0px;top:5px;height:" . $agent_screen_height . "px;overflow:hidden;z-index:9;background-color:white;\" id=\"AgentSpan\"><table cellpadding=\"0\" cellspacing=\"0\" border=\"0\"><tr><td width=\"5px\" rowspan=\"2\">&nbsp;</td><td align=\"center\">
	<span id=\"agentscreenlink\"><font size=1><a href=\"#\" onclick=\"AgentScreenOpen('agentscreencontent','show','CRMSpan');return false;\">Show Full Agent Screen</a>: &nbsp; +</font></span></td></tr><tr><td align=\"center\"></td></tr></table></span>\n";
	echo "<span style=\"position:absolute;left:0px;top:25px;height:" . $agent_screen_height . "px;width:" . $agent_screen_width . "px;overflow:hidden;z-index:21;background-color:white;\" id=\"agentscreencontent\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">$agent_screen_content</span>\n";
	}
else
	{
	echo "</HEAD>\n";
	echo "<BODY BGCOLOR=#FFFFFF marginheight=0 marginwidth=0 leftmargin=0 topmargin=0>\n";

    echo "<form name=\"crm_form\" id=\"crm_form\" action=\"$PHP_SELF\" method=\"post\">\n";
    echo "<input type=\"hidden\" name=\"DB\" id=\"DB\" value=\"$DB\" />\n";
    echo "<br /><br /><br /><center><table width=\"460px\" cellpadding=\"0\" cellspacing=\"0\" bgcolor=\"#E6E6E6\"><tr bgcolor=\"white\">";
    echo "<td align=\"left\" valign=\"bottom\">CRM LOGIN</td>";
    echo "<td align=\"center\" valign=\"middle\"> &nbsp; </td>";
    echo "</tr>\n";
    echo "<tr><td align=\"left\" colspan=\"2\"><font size=\"1\"> &nbsp; </font></td></tr>\n";
    echo "<tr><td align=\"right\">Phone Login: </td>";
    echo "<td align=\"left\"><input type=\"text\" name=\"phone_login\" size=\"10\" maxlength=\"20\" value=\"$phone_login\" /></td></tr>\n";
    echo "<tr><td align=\"right\">Phone Password:  </td>";
    echo "<td align=\"left\"><input type=\"password\" name=\"phone_pass\" size=\"10\" maxlength=\"20\" value=\"$phone_pass\" /></td></tr>\n";
    echo "<tr><td align=\"right\">User Login:  </td>";
    echo "<td align=\"left\"><input type=\"text\" name=\"VD_login\" size=\"10\" maxlength=\"20\" value=\"$VD_login\" /></td></tr>\n";
    echo "<tr><td align=\"right\">User Password:  </td>";
    echo "<td align=\"left\"><input type=\"password\" name=\"VD_pass\" size=\"10\" maxlength=\"20\" value=\"$VD_pass\" /></td></tr>\n";
    echo "<tr><td align=\"right\">Campaign:  </td>";
    echo "<td align=\"left\"><input type=\"VD_campaign\" name=\"VD_campaign\" size=\"10\" maxlength=\"8\" value=\"$VD_campaign\" /></td></tr>\n";
    echo "<tr><td align=\"center\" colspan=\"2\"><input type=\"submit\" name=\"SUBMIT\" value=\"SUBMIT\" /> &nbsp; </td></tr>\n";
    echo "</table></center>\n";
    echo "</form>\n\n";

	}

echo "</BODY>\n";
echo "</HTML>\n";

exit;

?>
