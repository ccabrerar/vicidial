#!/usr/bin/perl
#
# nanpa_type_populate.pl version 2.10
#
# DESCRIPTION:
# This script is designed to filter the leads in a list by phone number and 
# put the lead into a different list if it is a cellphone or invalid
#
# It is recommended that you run this program on the local Asterisk machine
#
# Copyright (C) 2014  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 130915-1345 - first build
# 130928-0747 - Force overwrite of unzipped files, more standard debug output
# 140702-2319 - Added additional logging to admin log --adminlog
#

$PATHconf =			'/etc/astguiclient.conf';
$PATHconfPREFIX =	'/etc/vicidial_prefix.conf';

$|=1;
$start_time=time();

open(conf, "$PATHconf") || die "can't open $PATHconf: $!\n";
@conf = <conf>;
close(conf);
$i=0;
foreach(@conf)
	{
	$line = $conf[$i];
	$line =~ s/ |>|\n|\r|\t|\#.*|;.*//gi;
	if ( ($line =~ /^PATHlogs/) && ($CLIlogs < 1) )
		{$PATHlogs = $line;   $PATHlogs =~ s/.*=//gi;}
	if ( ($line =~ /^VARfastagi_log_min_servers/) && ($CLIVARfastagi_log_min_servers < 1) )
		{$VARfastagi_log_min_servers = $line;   $VARfastagi_log_min_servers =~ s/.*=//gi;}
	if ( ($line =~ /^VARfastagi_log_max_servers/) && ($CLIVARfastagi_log_max_servers < 1) )
		{$VARfastagi_log_max_servers = $line;   $VARfastagi_log_max_servers =~ s/.*=//gi;}
	if ( ($line =~ /^VARfastagi_log_min_spare_servers/) && ($CLIVARfastagi_log_min_spare_servers < 1) )
		{$VARfastagi_log_min_spare_servers = $line;   $VARfastagi_log_min_spare_servers =~ s/.*=//gi;}
	if ( ($line =~ /^VARfastagi_log_max_spare_servers/) && ($CLIVARfastagi_log_max_spare_servers < 1) )
		{$VARfastagi_log_max_spare_servers = $line;   $VARfastagi_log_max_spare_servers =~ s/.*=//gi;}
	if ( ($line =~ /^VARfastagi_log_max_requests/) && ($CLIVARfastagi_log_max_requests < 1) )
		{$VARfastagi_log_max_requests = $line;   $VARfastagi_log_max_requests =~ s/.*=//gi;}
	if ( ($line =~ /^VARfastagi_log_checkfordead/) && ($CLIVARfastagi_log_checkfordead < 1) )
		{$VARfastagi_log_checkfordead = $line;   $VARfastagi_log_checkfordead =~ s/.*=//gi;}
	if ( ($line =~ /^VARfastagi_log_checkforwait/) && ($CLIVARfastagi_log_checkforwait < 1) )
		{$VARfastagi_log_checkforwait = $line;   $VARfastagi_log_checkforwait =~ s/.*=//gi;}
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

open(viciprefix, "$PATHconfPREFIX") || die "can't open $PATHconfPREFIX: $!\n";
@viciprefix = <viciprefix>;
close(viciprefix);
$i=0;
foreach(@viciprefix)
	{
	$line = $viciprefix[$i];
	$line =~ s/ |>|\n|\r|\t|\#.*|;.*//gi;
	if ( ($line =~ /^PREFIX_DB_server/) && ($CLIDB_server < 1) )
		{$PREFIX_DB_server = $line;   $PREFIX_DB_server =~ s/.*=//gi;}
	if ( ($line =~ /^PREFIX_DB_database/) && ($CLIDB_database < 1) )
		{$PREFIX_DB_database = $line;   $PREFIX_DB_database =~ s/.*=//gi;}
	if ( ($line =~ /^PREFIX_DB_user/) && ($CLIDB_user < 1) )
		{$PREFIX_DB_user = $line;   $PREFIX_DB_user =~ s/.*=//gi;}
	if ( ($line =~ /^PREFIX_DB_pass/) && ($CLIDB_pass < 1) )
		{$PREFIX_DB_pass = $line;   $PREFIX_DB_pass =~ s/.*=//gi;}
	if ( ($line =~ /^PREFIX_DB_port/) && ($CLIDB_port < 1) )
		{$PREFIX_DB_port = $line;   $PREFIX_DB_port =~ s/.*=//gi;}
	if ( ($line =~ /^PREFIX_SFTP_server/) && ($CLI_PREFIX_SFTP_server < 1) )
		{$PREFIX_SFTP_server = $line;   $PREFIX_SFTP_server =~ s/.*=//gi;}
	if ( ($line =~ /^PREFIX_SFTP_user/) && ($CLI_PREFIX_SFTP_user < 1) )
		{$PREFIX_SFTP_user = $line;   $PREFIX_SFTP_user =~ s/.*=//gi;}
	if ( ($line =~ /^PREFIX_SFTP_pass/) && ($CLI_PREFIX_SFTP_pass < 1) )
		{$PREFIX_SFTP_pass = $line;   $PREFIX_SFTP_pass =~ s/.*=//gi;}
	if ( ($line =~ /^PREFIX_SFTP_port/) && ($CLI_PREFIX_SFTP_port < 1) )
		{$PREFIX_SFTP_port = $line;   $PREFIX_SFTP_port =~ s/.*=//gi;}
	$i++;
	}


$secX = time();
($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
$MT[0]='';
$DBX=0;

$list_id='';
$cellphone_list_id='';
$landline_list_id='';
$invalid_list_id='';
$invalid=0;
$cellphone=0;
$landline=0;
$admin_log=0;

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
		print "  [--skip-download] = skip download of files\n";
		print "  [--test] = test\n";
		print "  [--quiet] = supress all normal output\n";
		print "  [--debug] = verbose debug messages\n";
		print "  [--debugX] = extra verbose debug messages\n";
		print "  [--adminlog] = log to vicidial_admin_log\n";
		print "\n";
		exit;
		}
	else
		{
		if ($args =~ /--quiet/i)
			{
			$Q=1;
			}
		if ($args =~ /--debug/i)
			{
			$DB=1;
			}
		if ($args =~ /--debugX/i)
			{
			$DBX=1;
			}
		if ($args =~ /--test/i)
			{
			$TEST=1;
			}
		if ($args =~ /--skip-download/i)
			{
			$skip_download=1;
			}
		if ($args =~ /--adminlog/i)
			{
			$admin_log=1;
			if ($DB) {print "ADMIN LOG ENABLED\n"}
			}
		}
	}
else
	{
	print "no command line options set\n";
	exit;
	}
### end parsing run-time options ###


if ($skip_download < 1)
	{
	##### BEGIN gather files from SFTP server and unzip #####
	use Net::SFTP::Foreign;

	if (!$Q) {print "Logging in to SFTP server: $outfile\n";}

	$sftp_more='-q';
	if ($DB > 0) {$sftp_more='-v';}

	if ($DB > 0) {print "\n  getting neustar.zip\n";}
	$sftp = Net::SFTP::Foreign->new($PREFIX_SFTP_user . '@' . $PREFIX_SFTP_server, password => "$PREFIX_SFTP_pass", port => "$PREFIX_SFTP_port", more => "$sftp_more");
	if ($DB > 0) 
		{
		$sftp->get("/$PREFIX_SFTP_user/_Databases/Neustar/neustar.zip", '/tmp/neustar.zip', callback => \&readcallback) or die "file transfer failed: " . $sftp->error;
		}
	else
		{
		$sftp->get("/$PREFIX_SFTP_user/_Databases/Neustar/neustar.zip", '/tmp/neustar.zip') or die "file transfer failed: " . $sftp->error;
		}

	sleep(1);

	if ($DB > 0) {print "\n  getting PrefixMasterList.zip\n";}
	$sftpX = Net::SFTP::Foreign->new($PREFIX_SFTP_user . '@' . $PREFIX_SFTP_server, password => "$PREFIX_SFTP_pass", port => "$PREFIX_SFTP_port", more => "$sftp_more");
	if ($DB > 0) 
		{
		$sftpX->get("/$PREFIX_SFTP_user/_Databases/WirelessPrefix/PrefixMasterList.zip", '/tmp/PrefixMasterList.zip', callback => \&readcallback) or die "file transfer failed: " . $sftpX->error;
		}
	else
		{
		$sftpX->get("/$PREFIX_SFTP_user/_Databases/WirelessPrefix/PrefixMasterList.zip", '/tmp/PrefixMasterList.zip') or die "file transfer failed: " . $sftpX->error;
		}

	if ($DB > 0) {print "\n unzipping files...  PrefixMasterList.zip\n";}
	`/usr/bin/unzip -uo /tmp/PrefixMasterList.zip -d /tmp`;
	if ($DB > 0) {print " unzipping files...  neustar.zip\n";}
	`/usr/bin/unzip -uo /tmp/neustar.zip -d /tmp`;

	##### END gather files from SFTP server and unzip #####
	}


use DBI;

### connect to phone type prefix DB
$dbhN = DBI->connect("DBI:mysql:$PREFIX_DB_database:$PREFIX_DB_server:$PREFIX_DB_port;mysql_local_infile=1", "$PREFIX_DB_user", "$PREFIX_DB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

$stmtN = "SELECT count(*) FROM nanpa_wired_to_wireless;";
$sthN = $dbhN->prepare($stmtN) or die "preparing: ",$dbhN->errstr;
$sthN->execute or die "executing: $stmtN ", $dbhN->errstr;
$sthNrows=$sthN->rows;
if ($sthNrows > 0)
	{
	@aryN = $sthN->fetchrow_array;
	$nanpa_wired_to_wireless_count =	$aryN[0];
	$sthN->finish();
	if ($DBX) {print "   $sthNrows|$stmtN|$nanpa_wired_to_wireless_count|\n";}
	}

$stmtN = "SELECT count(*) FROM nanpa_wireless_to_wired;";
$sthN = $dbhN->prepare($stmtN) or die "preparing: ",$dbhN->errstr;
$sthN->execute or die "executing: $stmtN ", $dbhN->errstr;
$sthNrows=$sthN->rows;
if ($sthNrows > 0)
	{
	@aryN = $sthN->fetchrow_array;
	$nanpa_wireless_to_wired_count =	$aryN[0];
	$sthN->finish();
	if ($DBX) {print "   $sthNrows|$stmtN|$nanpa_wireless_to_wired_count|\n";}
	}

$stmtN = "SELECT count(*) FROM nanpa_prefix_exchanges_master;";
$sthN = $dbhN->prepare($stmtN) or die "preparing: ",$dbhN->errstr;
$sthN->execute or die "executing: $stmtN ", $dbhN->errstr;
$sthNrows=$sthN->rows;
if ($sthNrows > 0)
	{
	@aryN = $sthN->fetchrow_array;
	$nanpa_prefix_exchanges_master_count =	$aryN[0];
	$sthN->finish();
	if ($DBX) {print "   $sthNrows|$stmtN|$nanpa_prefix_exchanges_master_count|\n";}
	}

$stmtN = "SELECT count(*) FROM nanpa_prefix_exchanges_fast;";
$sthN = $dbhN->prepare($stmtN) or die "preparing: ",$dbhN->errstr;
$sthN->execute or die "executing: $stmtN ", $dbhN->errstr;
$sthNrows=$sthN->rows;
if ($sthNrows > 0)
	{
	@aryN = $sthN->fetchrow_array;
	$nanpa_prefix_exchanges_fast_count =	$aryN[0];
	$sthN->finish();
	if ($DBX) {print "   $sthNrows|$stmtN|$nanpa_prefix_exchanges_fast_count|\n";}
	}


if ($nanpa_wireless_to_wired_count > 0)
	{
	$stmtN = "DELETE from nanpa_wireless_to_wired;";
	$affected_rowsWRLSWIRE = $dbhN->do($stmtN);
	if($DB){print STDERR "|$affected_rowsWRLSWIRE records deleted| nanpa_wireless_to_wired\n";}
	}

$stmtN = "LOAD DATA LOCAL INFILE '/tmp/WIRELESS-TO-WIRELINE-NORANGE.TXT' into table nanpa_wireless_to_wired LINES terminated by '\n' (phone);";
$affected_rowsINwrlswire = $dbhN->do($stmtN);
if($DB){print STDERR "|$affected_rowsINwrlswire records inserted| nanpa_wireless_to_wired\n";}

if ($nanpa_wired_to_wireless_count > 0)
	{
	$stmtN = "DELETE from nanpa_wired_to_wireless;";
	$affected_rowsWIREWRLS = $dbhN->do($stmtN);
	if($DB){print STDERR "|$affected_rowsWIREWRLS records deleted| nanpa_wired_to_wireless\n";}
	}

$stmtN = "LOAD DATA LOCAL INFILE '/tmp/WIRELINE-TO-WIRELESS-NORANGE.TXT' into table nanpa_wired_to_wireless LINES terminated by '\n' (phone);";
$affected_rowsINwirewrls = $dbhN->do($stmtN);
if($DB){print STDERR "|$affected_rowsINwirewrls records inserted| nanpa_wired_to_wireless\n";}

if ($nanpa_prefix_exchanges_master_count > 0)
	{
	$stmtN = "DELETE from nanpa_prefix_exchanges_master;";
	$affected_rowsPRFXMSTR = $dbhN->do($stmtN);
	if($DB){print STDERR "|$affected_rowsPRFXMSTR records deleted| nanpa_prefix_exchanges_master\n";}
	}

$stmtN = "LOAD DATA LOCAL INFILE '/tmp/PrefixMasterList.csv' into table nanpa_prefix_exchanges_master FIELDS TERMINATED BY ',' LINES terminated by '\r\n' (areacode,prefix,source,type,tier,postal_code,new_areacode,tzcode,region);";
$affected_rowsINprfxmstr = $dbhN->do($stmtN);
if($DB){print STDERR "|$affected_rowsINprfxmstr records inserted| nanpa_prefix_exchanges_master\n";}

if ($nanpa_prefix_exchanges_fast_count > 0)
	{
	$stmtN = "DELETE from nanpa_prefix_exchanges_fast;";
	$affected_rowsPRFXFAST = $dbhN->do($stmtN);
	if($DB){print STDERR "|$affected_rowsPRFXFAST records deleted| nanpa_prefix_exchanges_fast\n";}
	}

$stmtN = "INSERT INTO nanpa_prefix_exchanges_fast (ac_prefix,type) SELECT CONCAT(areacode,prefix),type from nanpa_prefix_exchanges_master;";
$affected_rowsINprfxfast = $dbhN->do($stmtN);
if($DB){print STDERR "|$affected_rowsINprfxfast records inserted| nanpa_prefix_exchanges_fast\n";}


$end_time=time();

if ($admin_log > 0)
	{
	$stmtA="INSERT INTO vicidial_admin_log set event_date=NOW(), user='VDAD', ip_address='1.1.1.1', event_section='SERVERS', event_type='OTHER', record_id='$VARserver_ip', event_code='NANPA populate data', event_sql='', event_notes='WIRED2WIRELESS($nanpa_wired_to_wireless_count / $affected_rowsWIREWRLS / $affected_rowsINwirewrls)  WIRELESS2WIRED($nanpa_wireless_to_wired_count / $affected_rowsWRLSWIRE / $affected_rowsINwrlswire)   PREFIX($nanpa_prefix_exchanges_master_count / $nanpa_prefix_exchanges_fast_count / $affected_rowsPRFXMSTR / $affected_rowsINprfxmstr / $affected_rowsPRFXFAST / $affected_rowsINprfxfast)   |$args| TOTAL Elapsed time: ".($end_time-$start_time)." sec';";
	$Iaffected_rows = $dbhN->do($stmtA);
	if ($DB) {print "Admin logged: |$Iaffected_rows|$stmtA|\n";}
	}

if (!$Q) {print "TOTAL Elapsed time: ".($end_time-$start_time)." sec\n\n";}


### sftp callback subroutines
sub writecallback 
	{
	my($sftp, $data, $offset, $size) = @_;
	if ($DB > 0) 
		{print STDERR "Wrote $offset / $size bytes\r";}
	}

sub readcallback 
	{
	my($sftp, $data, $offset, $size) = @_;
	if ($DB > 0) 
		{print STDERR "Read $offset / $size bytes\r";}
	}
