#!/usr/bin/perl
#
# AST_DB_DNC_filter.pl version 2.12
#
# DESCRIPTION:
# OPTIONAL!!!
# - runs several filters on numbers in the DNC lists and reports results with an
#    option to delete filtered numbers, like few digits, too many digits, 
#	 invalid phone numbers, etc...
#
# It is recommended that you run this program on the local Asterisk machine
#
# Copyright (C) 2015  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 151004-0826 - first build
# 151004-2225 - Added --remove-nondigits and --protect-areacodes options
#

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
		print "  [--without-campaign-dnc] = will skip analyzing the campaign dnc lists\n";
		print "  [--campaign-dnc-only=X] = will only run on one campaign DNC list\n";
		print "  [--remove-x-or-less=X] = remove phone numbers in the dnc list that are X digits or less <=\n";
		print "  [--remove-x-or-more=X] = remove phone numbers in the dnc list that are X digits or more >=\n";
		print "  [--remove-nondigits] = remove phone numbers that have non-digits in them\n";
		print "  [--protect-areacodes] = protect areacode entries with wildcards from being deleted\n";
		print "  [--test] = test\n";
		print "  [--quiet] = quiet\n";
		print "  [--debug] = verbose debug messages\n";
		print "  [--debugX] = extra verbose debug messages\n\n";
		exit;
		}
	else
		{
		if ($args =~ /--quiet/i)
			{
			$Q=1;
			}
		if ($args =~ /--without-campaign-dnc/i)
			{
			$without_campaign_dnc=1;
			}
		if ($args =~ /--campaign-dnc-only=/i)
			{
			#	print "\n|$ARGS|\n\n";
			@data_in = split(/--campaign-dnc-only=/,$args);
			$CLIcampaign = $data_in[1];
			$CLIcampaign =~ s/ .*$//gi;
			if ($CLIcampaign =~ /---/)
				{
				$CLIcampaign =~ s/---/','/gi;
				}
			if ($DB > 0) {print "\n-----CAMPAIGN DNC ONLY: $CLIcampaign -----\n\n";}
			}
		else
			{$CLIcampaign = '';}
		if ($args =~ /--remove-x-or-less=/i)
			{
			#	print "\n|$ARGS|\n\n";
			@data_in = split(/--remove-x-or-less=/,$args);
			$CLIremoveless = $data_in[1];
			$CLIremoveless =~ s/ .*$//gi;
			$CLIremoveless =~ s/\D//gi;
			if ($DB > 0) {print "\n-----REMOVE X DIGITS OR LESS: $CLIremoveless -----\n\n";}
			}
		else
			{$CLIremoveless = '';}
		if ($args =~ /--remove-x-or-more=/i)
			{
			#	print "\n|$ARGS|\n\n";
			@data_in = split(/--remove-x-or-more=/,$args);
			$CLIremovemore = $data_in[1];
			$CLIremovemore =~ s/ .*$//gi;
			$CLIremovemore =~ s/\D//gi;
			if ($DB > 0) {print "\n-----REMOVE X DIGITS OR MORE: $CLIremovemore -----\n\n";}
			}
		else
			{$CLIremovemore = '';}
		if ($args =~ /--remove-nondigits/i)
			{
			$remove_nondigits=1;
			}
		if ($args =~ /--protect-areacodes/i)
			{
			$protect_areacodes=1;
			}
		if ($args =~ /--debug/i)
			{
			$DB=1; # Debug flag, set to 0 for no debug messages, On an active system this will generate hundreds of lines of output per minute
			}
		if ($args =~ /--debugX/i)
			{
			$DBX=1; # Debug flag, set to 0 for no debug messages, On an active system this will generate hundreds of lines of output per minute
			}
		if ($args =~ /--test/i)
			{
			$TEST=1;
			$T=1;
			}
		}
	}
else
	{
	print "no command line options set, exiting...\n";
	exit;
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

use DBI;	  

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);


$deleted=0;
$MT[0]='';

### find stats on dnc lists
$stmtA = "SELECT count(*) from vicidial_dnc;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArowsDNC=$sthA->rows;
if ($sthArowsDNC > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$DNCcount = 	$aryA[0];
	}
$sthA->finish();
if($DB){print "\nTotal System Internal DNC entries: |$DNCcount|\n";}
if($DB){print "\n";}
if($DB){print "Total Campaign DNC entries:\n";}

$stmtA = "SELECT count(*),campaign_id from vicidial_campaign_dnc group by campaign_id order by campaign_id;";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArowsCDNC=$sthA->rows;
$rec_count=0;
while ($sthArowsCDNC > $rec_count)
	{
	@aryA = $sthA->fetchrow_array;
	$aryA[0] = sprintf("%10s", $aryA[0]);
	$aryA[1] = sprintf("%10s", $aryA[1]);
	if($DB){print "$aryA[1]: $aryA[0]\n";}
	$rec_count++;
	}
$sthA->finish();



if ( (length($CLIremoveless)>0) or (length($CLIremovemore)>0) or ($remove_nondigits > 0) ) 
	{
	$removelessSQL='';
	$removemoreSQL='';
	$remove_nondigitsSQL='';
	$protect_areacodesSQL='';
	if (length($CLIremoveless)>0) 
		{$removelessSQL="(char_length(phone_number) <= $CLIremoveless)";}
	if (length($CLIremovemore)>0)
		{
		if (length($removelessSQL) > 1) 
			{$removemoreSQL = 'or ';}
		$removemoreSQL .= "(char_length(phone_number) >= $CLIremovemore)";
		}
	if ($remove_nondigits > 0)
		{
		if ( (length($removelessSQL) > 1) or (length($removemoreSQL) > 1) )
			{$remove_nondigitsSQL = 'or ';}
		$remove_nondigitsSQL .= "( (phone_number LIKE \"% %\") or (phone_number LIKE \"%*%\") or (phone_number LIKE \"%#%\") or (phone_number LIKE \"%a%\") or (phone_number LIKE \"%b%\") or (phone_number LIKE \"%c%\") or (phone_number LIKE \"%d%\") or (phone_number LIKE \"%e%\") or (phone_number LIKE \"%f%\") or (phone_number LIKE \"%g%\") or (phone_number LIKE \"%h%\") or (phone_number LIKE \"%i%\") or (phone_number LIKE \"%j%\") or (phone_number LIKE \"%k%\") or (phone_number LIKE \"%l%\") or (phone_number LIKE \"%m%\") or (phone_number LIKE \"%n%\") or (phone_number LIKE \"%o%\") or (phone_number LIKE \"%p%\") or (phone_number LIKE \"%q%\") or (phone_number LIKE \"%r%\") or (phone_number LIKE \"%s%\") or (phone_number LIKE \"%t%\") or (phone_number LIKE \"%u%\") or (phone_number LIKE \"%v%\") or (phone_number LIKE \"%q%\") or (phone_number LIKE \"%x%\") or (phone_number LIKE \"%y%\") or (phone_number LIKE \"%z%\") or (phone_number LIKE \"%.%\") or (phone_number LIKE \"%,%\") )";
		}
	if ($protect_areacodes > 0) 
		{
		if ( (length($removelessSQL) > 1) or (length($removemoreSQL) > 1) or (length($remove_nondigitsSQL) > 1) )
			{$protect_areacodesSQL = "and (phone_number NOT LIKE \"%XXXXXXX\")";}
		}
	if (length($CLIcampaign)<1) 
		{
		##### BEGIN system internal DNC filter #####
		$stmtA = "SELECT phone_number from vicidial_dnc where $removelessSQL $removemoreSQL $remove_nondigitsSQL $protect_areacodesSQL limit 100000000;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArowsCT=$sthA->rows;
		if($DB){print "|$sthArowsCT|$stmtA|\n";}
		$rec_count=0;
		while ($sthArowsCT > $rec_count)
			{
			@aryA = $sthA->fetchrow_array;

			$phone_numbers[$rec_count] = 	$aryA[0];

			$rec_count++;
			}
		$sthA->finish();

		$rec_count=0;
		while ($sthArowsCT > $rec_count)
			{
			$affected_rows=0;
			$affected_rowsA=0;
			$stmtA = "DELETE from vicidial_dnc where phone_number='$phone_numbers[$rec_count]';";
			if (!$T) {$affected_rows = $dbhA->do($stmtA);}
			if($DBX){print STDERR "\n|$affected_rows deleted|   |$stmtA|\n";}
			$deleted++;
			if ($affected_rows > 0)
				{
				$stmtA="INSERT INTO vicidial_dnc_log SET phone_number='$phone_numbers[$rec_count]', campaign_id='-SYSINT-', action='delete', action_date=NOW(), user='CLIFLTR';";
				if (!$T) {$affected_rowsA = $dbhA->do($stmtA);}
				if($DBX){print STDERR "\n|$affected_rowsA log|   |$stmtA|\n";}
				}
			$rec_count++;
			}
		##### END system internal DNC filter #####
		}




	if ( (length($CLIcampaign) > 0) or ($without_campaign_dnc < 1) )
		{
		$CLIcampaignSQL='';
		if (length($CLIcampaign) > 0) 
			{$CLIcampaignSQL = "and campaign_id IN('$CLIcampaign')";}
		##### BEGIN campaign DNC filter #####
		$stmtA = "SELECT phone_number,campaign_id from vicidial_campaign_dnc where $removelessSQL $removemoreSQL $remove_nondigitsSQL $protect_areacodesSQL $CLIcampaignSQL limit 100000000;";
		$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
		$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
		$sthArowsCT=$sthA->rows;
		if($DB){print "|$sthArowsCT|$stmtA|\n";}
		$rec_count=0;
		while ($sthArowsCT > $rec_count)
			{
			@aryA = $sthA->fetchrow_array;

			$Cphone_numbers[$rec_count] = 	$aryA[0];
			$Ccampaigns[$rec_count] = 		$aryA[1];

			$rec_count++;
			}
		$sthA->finish();

		$rec_count=0;
		while ($sthArowsCT > $rec_count)
			{
			$affected_rows=0;
			$affected_rowsA=0;
			$stmtA = "DELETE from vicidial_campaign_dnc where phone_number='$Cphone_numbers[$rec_count]' and campaign_id='$Ccampaigns[$rec_count]';";
			if (!$T) {$affected_rows = $dbhA->do($stmtA);}
			if($DBX){print STDERR "\n|$affected_rows deleted|   |$stmtA|\n";}
			$deleted++;
			if ($affected_rows > 0)
				{
				$stmtA="INSERT INTO vicidial_dnc_log SET phone_number='$Cphone_numbers[$rec_count]', campaign_id='$Ccampaigns[$rec_count]', action='delete', action_date=NOW(), user='CLIFLTR';";
				if (!$T) {$affected_rowsA = $dbhA->do($stmtA);}
				if($DBX){print STDERR "\n|$affected_rowsA log|   |$stmtA|\n";}
				}
			$rec_count++;
			}
		##### END campaign DNC filter #####
		}
	}
else
	{
	if($DB>0){print "\n no dnc filter set: |$CLIremoveless|$CLIremovemore|\n\n";}
	}

exit;
