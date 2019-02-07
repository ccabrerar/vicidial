#!/usr/bin/perl
#
# AST_phone_update.pl version 2.14
#
# DESCRIPTION:
# checks the registered IP address of the phone and updates the phones table
#
# Copyright (C) 2018  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# 70521-1529 - first build
# 100625-1220 - Added waitfors after logout to fix broken pipe errors in asterisk <MikeC>
# 130625-0947 - Added --agent-lookup option for agent phone_ip population
# 131210-1305 - Added Asterisk 1.8 compatibility
# 150610-1200 - Added support for AMI version 1.3
# 180403-1545 - Updated telnet login process to work with newer Asterisk versions
#

# constants
$DB=0;  # Debug flag, set to 0 for no debug messages per minute
$US='__';
$MT[0]='';
$agent_lookup=0;

### begin parsing run-time options ###
if (length($ARGV[0])>1)
	{
	$i=0;
	while ($#ARGV >= $i)
		{
		$args = "$args $ARGV[$i]";
		$i++;
		}

	if ($args =~ /--help/i)
		{
		print "allowed run time options:\n";
		print "  [-t] = test\n";
		print "  [--debug] = verbose debug messages\n";
		print "  [--debugX] = extra verbose debug messages\n";
		print "  [--agent-lookup] = looks up the agent phone_ip only\n";
		print "\n";
		exit;
		}
	else
		{
		if ($args =~ /-t/i)
			{
			$TEST=1;
			$T=1;
			}
		if ($args =~ /--debug/i)
			{
			$DB=1; # Debug flag
			}
		if ($args =~ /--debugX/i)
			{
			$DBX=1; # Extra debug flag
			}
		if ($args =~ /--agent-lookup/i)
			{
			$agent_lookup=1; # agent lookup only flag
			}
		}
	}
else
	{
	#	print "no command line options set\n";
	}
### end parsing run-time options ###

# default path to astguiclient configuration file:
$PATHconf =		'/etc/astguiclient.conf';

open(conf, "$PATHconf") || die "can't open $PATHconf: $!\n";
@conf = <conf>;
close(conf);
$i=0;
foreach(@conf)
	{
	$line = $conf[$i];
	$line =~ s/ |>|\n|\r|\t|\#.*|;.*//gi;
	if ( ($line =~ /^PATHhome/) && ($CLIhome < 1) )
		{$PATHhome = $line;   $PATHhome =~ s/.*=//gi;}
	if ( ($line =~ /^PATHlogs/) && ($CLIlogs < 1) )
		{$PATHlogs = $line;   $PATHlogs =~ s/.*=//gi;}
	if ( ($line =~ /^PATHagi/) && ($CLIagi < 1) )
		{$PATHagi = $line;   $PATHagi =~ s/.*=//gi;}
	if ( ($line =~ /^PATHweb/) && ($CLIweb < 1) )
		{$PATHweb = $line;   $PATHweb =~ s/.*=//gi;}
	if ( ($line =~ /^PATHsounds/) && ($CLIsounds < 1) )
		{$PATHsounds = $line;   $PATHsounds =~ s/.*=//gi;}
	if ( ($line =~ /^PATHmonitor/) && ($CLImonitor < 1) )
		{$PATHmonitor = $line;   $PATHmonitor =~ s/.*=//gi;}
	if ( ($line =~ /^VARserver_ip/) && ($CLIserver_ip < 1) )
		{$VARserver_ip = $line;   $VARserver_ip =~ s/.*=//gi;}
	if ( ($line =~ /^VARDB_server/) && ($CLIDB_server < 1) )
		{$VARDB_server = $line;   $VARDB_server =~ s/.*=//gi;}
	if ( ($line =~ /^VARDB_database/) && ($CLIDB_database < 1) )
		{$VARDB_database = $line;   $VARDB_database =~ s/.*=//gi;}
	if ( ($line =~ /^VARDB_user/) && ($CLIDB_user < 1) )
		{$VARDB_user = $line;   $VARDB_user =~ s/.*=//gi;}
	if ( ($line =~ /^VARDB_pass/) && ($CLIDB_pass < 1) )
		{$VARDB_pass = $line;   $VARDB_pass =~ s/.*=//gi;}
	if ( ($line =~ /^VARDB_port/) && ($CLIDB_port < 1) )
		{$VARDB_port = $line;   $VARDB_port =~ s/.*=//gi;}
	$i++;
	}

# Customized Variables
$server_ip = $VARserver_ip;		# Asterisk server IP

if (!$VARDB_port) {$VARDB_port='3306';}

use Time::HiRes ('gettimeofday','usleep','sleep');  # necessary to have perl sleep command of less than one second
use DBI;
use Net::Telnet ();
	  
 $dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;
 
### Grab Server values from the database
$stmtA = "SELECT telnet_host,telnet_port,ASTmgrUSERNAME,ASTmgrSECRET,ASTmgrUSERNAMEupdate,ASTmgrUSERNAMElisten,ASTmgrUSERNAMEsend,max_vicidial_trunks,answer_transfer_agent,local_gmt,ext_context,asterisk_version FROM servers where server_ip = '$server_ip';";

$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
if ($DB) {print "|$stmtA|\n";}
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
$rec_count=0;
if ($sthArows > 0)
    {
	@aryA = $sthA->fetchrow_array;
	$DBtelnet_host	=			$aryA[0];
	$DBtelnet_port	=			$aryA[1];
	$DBASTmgrUSERNAME	=		$aryA[2];
	$DBASTmgrSECRET	=			$aryA[3];
	$DBASTmgrUSERNAMEupdate	=	$aryA[4];
	$DBASTmgrUSERNAMElisten	=	$aryA[5];
	$DBASTmgrUSERNAMEsend	=	$aryA[6];
	$DBmax_vicidial_trunks	=	$aryA[7];
	$DBanswer_transfer_agent=	$aryA[8];
	$DBSERVER_GMT		=		$aryA[9];
	$DBext_context	=			$aryA[10];
	$asterisk_version =			$aryA[11];
	if ($DBtelnet_host)				{$telnet_host = $DBtelnet_host;}
	if ($DBtelnet_port)				{$telnet_port = $DBtelnet_port;}
	if ($DBASTmgrUSERNAME)			{$ASTmgrUSERNAME = $DBASTmgrUSERNAME;}
	if ($DBASTmgrSECRET)			{$ASTmgrSECRET = $DBASTmgrSECRET;}
	if ($DBASTmgrUSERNAMEupdate)	{$ASTmgrUSERNAMEupdate = $DBASTmgrUSERNAMEupdate;}
	if ($DBASTmgrUSERNAMElisten)	{$ASTmgrUSERNAMElisten = $DBASTmgrUSERNAMElisten;}
	if ($DBASTmgrUSERNAMEsend)		{$ASTmgrUSERNAMEsend = $DBASTmgrUSERNAMEsend;}
	if ($DBmax_vicidial_trunks)		{$max_vicidial_trunks = $DBmax_vicidial_trunks;}
	if ($DBanswer_transfer_agent)	{$answer_transfer_agent = $DBanswer_transfer_agent;}
	if ($DBSERVER_GMT)				{$SERVER_GMT = $DBSERVER_GMT;}
	if ($DBext_context)				{$ext_context = $DBext_context;}
	}
$sthA->finish(); 
	
$secX = time();

$BDtarget = ($secX - 86400);
($Bsec,$Bmin,$Bhour,$Bmday,$Bmon,$Byear,$Bwday,$Byday,$Bisdst) = localtime($BDtarget);
$Byear = ($Byear + 1900);
$Bmon++;
if ($Bmon < 10) {$Bmon = "0$Bmon";}
if ($Bmday < 10) {$Bmday = "0$Bmday";}
if ($Bhour < 10) {$Bhour = "0$Bhour";}
if ($Bmin < 10) {$Bmin = "0$Bmin";}
if ($Bsec < 10) {$Bsec = "0$Bsec";}
$BDtsSQLdate = "$Byear$Bmon$Bmday$Bhour$Bmin$Bsec";

$phone_listSQL='';
$sip_count=0;
##### BEGIN sip agent phone_ip lookup #####
if ($agent_lookup > 0)
	{
	$stmtA = "SELECT distinct server_phone from vicidial_user_log where server_ip='$server_ip' and phone_ip='LOOKUP' and event_date > \"$BDtsSQLdate\" and extension LIKE \"SIP%\";";
	if ($DB) {print "|$stmtA|\n";}
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	while ($sthArows > $sip_count)
		{
		@aryA = $sthA->fetchrow_array;
		$phone_list .= "'$aryA[0]',";
		$sip_count++;
		}
	$sthA->finish(); 
	chop($phone_list);
	if (length($phone_list) > 2)
		{$phone_listSQL = "and login IN($phone_list)";}
	}
##### END sip agent phone_ip lookup #####


############# BEGIN SIP SECTION ################################################
if($DB){print "\n\nSIP EXTENSIONS:\n";}

if ( ($sip_count > 0) || ($agent_lookup < 1) )
	{
	@PTextensions=@MT; @PTphone_ips=@MT; @PTlogins=@MT;
	$stmtA = "SELECT extension,phone_ip,login from phones where server_ip='$server_ip' and protocol='SIP' $phone_listSQL;";
	if ($DB) {print "|$stmtA|\n";}
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	$rec_count=0;
	while ($sthArows > $rec_count)
		{
		@aryA = $sthA->fetchrow_array;
		$PTextensions[$rec_count] =		$aryA[0];
		$PTphone_ips[$rec_count] =		$aryA[1];
		$PTlogins[$rec_count] =			$aryA[2];
		$rec_count++;
		}
	$sthA->finish(); 


	### connect to asterisk manager through telnet
	$t = new Net::Telnet (Port => 5038,
						  Prompt => '/\r\n/',
						  Output_record_separator => '',);

	##### uncomment both lines below for telnet log
	#        $LItelnetlog = "$PATHlogs/phone_telnet_log.txt";
	#        $fh = $t->dump_log("$LItelnetlog");

	if (length($ASTmgrUSERNAMEsend) > 3) {$telnet_login = $ASTmgrUSERNAMEsend;}
	else {$telnet_login = $ASTmgrUSERNAME;}
	$t->open("$telnet_host");
	$t->waitfor('/Asterisk Call Manager\//');

	# get the AMI version number
	$ami_version = $t->getline(Errmode => Return, Timeout => 1,);
	$ami_version =~ s/\n//gi;
	if ($DB) {print "----- AMI Version $ami_version -----\n";}

	# Login
	$t->print("Action: Login\nUsername: $telnet_login\nSecret: $ASTmgrSECRET\n\n");
	$t->waitfor('/Authentication accepted/');              # waitfor auth accepted

	$t->buffer_empty;

	$i=0;
	foreach(@PTextensions)
		{
		@list_channels=@MT;
		$t->buffer_empty;

		%ast_ver_str = parse_asterisk_version($asterisk_version);
		if (( $ast_ver_str{major} = 1 ) && ($ast_ver_str{minor} < 6))
			{
			@list_channels = $t->cmd(String => "Action: Command\nCommand: sip show peer $PTextensions[$i]\n\nAction: Ping\n\n", Prompt => '/Response: Pong.*/'); 
			}
		else
			{
			@list_channels = $t->cmd(String => "Action: Command\nCommand: sip show peer $PTextensions[$i]\n\nAction: Ping\n\n", Prompt => '/Response: Success\nPing: Pong.*/'); 
			}

		$j=0;
		foreach(@list_channels)
			{		#  Addr->IP     : 10.10.14.25 Port 5060
			if ($list_channels[$j] =~ /  Addr->IP     : /)
				{
				$NEW_IPaddr[$i] = "$list_channels[$j]";
				$NEW_IPaddr[$i] =~ s/  Addr->IP     : | Port .*|\n|\r|\t//g;
				}

			$j++;
			}

		if($DB){print "Phone IP- $PTextensions[$i]    NEW:|$NEW_IPaddr[$i]|  OLD:|$PTphone_ips[$i]|    ";}
		if ( ($NEW_IPaddr[$i] =~ /$PTphone_ips[$i]/) && (length($NEW_IPaddr[$i]) eq length($PTphone_ips[$i])) )
			{
			if($DB){print "PHONE IP UNCHANGED, DOING NOTHING FOR THIS EXTENSION\n";}
			}
		else
			{
			if ($NEW_IPaddr[$i] =~ /Unspecified/) 
				{
				if($DB){print "PHONE IP UNSPECIFIED, DOING NOTHING FOR THIS EXTENSION\n";}
				}
			else
				{
				$stmtA = "UPDATE phones set phone_ip='$NEW_IPaddr[$i]' where server_ip='$server_ip' and extension='$PTextensions[$i]';";
				$affected_rows = $dbhA->do($stmtA); #  or die  "Couldn't execute query:|$stmtA|\n";
				if($DB){print STDERR "\n|$affected_rows|$stmtA|\n";}
				}
			}
		if ($agent_lookup > 0)
			{
			$stmtA = "UPDATE vicidial_user_log set phone_ip='$NEW_IPaddr[$i]' where server_phone='$PTlogins[$i]' and server_ip='$server_ip' and phone_ip='LOOKUP' and event_date > \"$BDtsSQLdate\" and extension LIKE \"SIP%\";";
			$affected_rows = $dbhA->do($stmtA); #  or die  "Couldn't execute query:|$stmtA|\n";
			if($DB){print STDERR "\n|$affected_rows|$stmtA|\n";}
			}

		$i++;
		### sleep for 10 hundredths of a second
		usleep(1*100*1000);
		}

	$t->buffer_empty;
	@hangup = $t->cmd(String => "Action: Logoff\n\n", Prompt => "/.*/"); 
	$t->buffer_empty;
	$t->waitfor(Match => '/Message:.*\n\n/', Timeout => 10);
	$ok = $t->close;
	}
############# END SIP SECTION ################################################



$phone_listSQL='';
$iax_count=0;
##### BEGIN sip agent phone_ip lookup #####
if ($agent_lookup > 0)
	{
	$stmtA = "SELECT distinct server_phone from vicidial_user_log where server_ip='$server_ip' and phone_ip='LOOKUP' and event_date > \"$BDtsSQLdate\" and extension LIKE \"IAX%\";";
	if ($DB) {print "|$stmtA|\n";}
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	while ($sthArows > $iax_count)
		{
		@aryA = $sthA->fetchrow_array;
		$phone_list .= "'$aryA[0]',";
		$iax_count++;
		}
	$sthA->finish(); 
	chop($phone_list);
	if (length($phone_list) > 2)
		{$phone_listSQL = "and login IN($phone_list)";}
	}
##### END sip agent phone_ip lookup #####


############# BEGIN IAX2 SECTION ################################################
if($DB){print "\n\niax2 EXTENSIONS:\n";}

if ( ($iax_count > 0) || ($agent_lookup < 1) )
	{
	@PTextensions=@MT; @PTphone_ips=@MT; @PTlogins=@MT;
	$stmtA = "SELECT extension,phone_ip,login from phones where server_ip='$server_ip' and protocol='IAX2'  $phone_listSQL;";
	if ($DB) {print "|$stmtA|\n";}
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;
	$rec_count=0;
	while ($sthArows > $rec_count)
		{
		@aryA = $sthA->fetchrow_array;
		$PTextensions[$rec_count] =		$aryA[0];
		$PTphone_ips[$rec_count] =		$aryA[1];
		$PTlogins[$rec_count] =			$aryA[2];
		$rec_count++;
		}
	$sthA->finish(); 


	### connect to asterisk manager through telnet
	$t = new Net::Telnet (Port => 5038,
						  Prompt => '/\r\n/',
						  Output_record_separator => '',);

	##### uncomment both lines below for telnet log
	#        $LItelnetlog = "$PATHlogs/phone_telnet_log.txt";
	#        $fh = $t->dump_log("$LItelnetlog");

	if (length($ASTmgrUSERNAMEsend) > 3) {$telnet_login = $ASTmgrUSERNAMEsend;}
	else {$telnet_login = $ASTmgrUSERNAME;}
	$t->open("$telnet_host");
	$t->waitfor('/Asterisk Call Manager\//');

	# get the AMI version number
	$ami_version = $t->getline(Errmode => Return, Timeout => 1,);
	$ami_version =~ s/\n//gi;
	if ($DB) {print "----- AMI Version $ami_version -----\n";}

	# Login
	$t->print("Action: Login\nUsername: $telnet_login\nSecret: $ASTmgrSECRET\n\n");
	$t->waitfor('/Authentication accepted/');              # waitfor auth accepted

	$t->buffer_empty;

	$i=0;
	foreach(@PTextensions)
		{
		@list_channels=@MT;
		$t->buffer_empty;
		%ast_ver_str = parse_asterisk_version($asterisk_version);
		if (( $ast_ver_str{major} = 1 ) && ($ast_ver_str{minor} < 6))
			{
			@list_channels = $t->cmd(String => "Action: Command\nCommand: iax2 show peer $PTextensions[$i]\n\nAction: Ping\n\n", Prompt => '/Response: Pong.*/'); 
			}
		else
			{
			@list_channels = $t->cmd(String => "Action: Command\nCommand: iax2 show peer $PTextensions[$i]\n\nAction: Ping\n\n", Prompt => '/Response: Success\nPing: Pong.*/'); 
			}

		$j=0;
		foreach(@list_channels)
			{		#  Addr->IP     : 10.10.14.25 Port 5060
			if ($list_channels[$j] =~ /  Addr->IP     : /)
				{
				$NEW_IPaddr[$i] = "$list_channels[$j]";
				$NEW_IPaddr[$i] =~ s/  Addr->IP     : | Port .*|\n|\r|\t//g;
				}

			$j++;
			}

		if($DB){print "Phone IP- $PTextensions[$i]    NEW:|$NEW_IPaddr[$i]|  OLD:|$PTphone_ips[$i]|    ";}
		if ( ($NEW_IPaddr[$i] =~ /$PTphone_ips[$i]/) && (length($NEW_IPaddr[$i]) eq length($PTphone_ips[$i])) )
			{
			if($DB){print "PHONE IP UNCHANGED, DOING NOTHING FOR THIS EXTENSION\n";}
			}
		else
			{
			if ($NEW_IPaddr[$i] =~ /Unspecified/) 
				{
				if($DB){print "PHONE IP UNSPECIFIED, DOING NOTHING FOR THIS EXTENSION\n";}
				}
			else
				{
				$stmtA = "UPDATE phones set phone_ip='$NEW_IPaddr[$i]' where server_ip='$server_ip' and extension='$PTextensions[$i]';";
				$affected_rows = $dbhA->do($stmtA); #  or die  "Couldn't execute query:|$stmtA|\n";
				if($DB){print STDERR "\n|$affected_rows|$stmtA|\n";}
				}
			}
		if ($agent_lookup > 0)
			{
			$stmtA = "UPDATE vicidial_user_log set phone_ip='$NEW_IPaddr[$i]' where server_phone='$PTlogins[$i]' and server_ip='$server_ip' and phone_ip='LOOKUP' and event_date > \"$BDtsSQLdate\" and extension LIKE \"IAX%\";";
			$affected_rows = $dbhA->do($stmtA); #  or die  "Couldn't execute query:|$stmtA|\n";
			if($DB){print STDERR "\n|$affected_rows|$stmtA|\n";}
			}

		$i++;
		### sleep for 10 hundredths of a second
		usleep(1*100*1000);
		}

	$t->buffer_empty;
	@hangup = $t->cmd(String => "Action: Logoff\n\n", Prompt => "/.*/"); 
	$t->buffer_empty;
	$t->waitfor(Match => '/Message:.*\n\n/', Timeout => 10);
	$ok = $t->close;
	}
############# END IAX2 SECTION ################################################





$dbhA->disconnect();

if($DB){print "DONE... Exiting...\n";}

exit;


# subroutine to parse the asterisk version
# and return a hash with the various part
sub parse_asterisk_version
{
	# grab the arguments
	my $ast_ver_str = $_[0];

	# get everything after the - and put it in $ast_ver_postfix
	my @hyphen_parts = split( /-/ , $ast_ver_str );

	my $ast_ver_postfix = $hyphen_parts[1];

	# now split everything before the - up by the .
	my @dot_parts = split( /\./ , $hyphen_parts[0] );

	my %ast_ver_hash;

	if ( $dot_parts[0] <= 1 )
		{
			%ast_ver_hash = (
				"major" => $dot_parts[0],
				"minor" => $dot_parts[1],
				"build" => $dot_parts[2],
				"revision" => $dot_parts[3],
				"postfix" => $ast_ver_postfix
			);
		}

	# digium dropped the 1 from asterisk 10 but we still need it
	if ( $dot_parts[0] > 1 )
		{
			%ast_ver_hash = (
				"major" => 1,
				"minor" => $dot_parts[0],
				"build" => $dot_parts[1],
				"revision" => $dot_parts[2],
				"postfix" => $ast_ver_postfix
			);
		}

	return ( %ast_ver_hash );
}
