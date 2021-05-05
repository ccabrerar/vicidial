#!/usr/bin/perl
#
# KHOMP_updater.pl
# 
# Copyright (C) 2019  Matt Florell <vicidial@gmail.com>, Joe Johnson <freewermadmin@gmail.com>    LICENSE: AGPLv2
#
# Script designed to be used as a cron job, where the KHOMP log table in the database will 
# be queried for any new KHOMP codes that aren't currently stored in the dialer.
#
# 191105-1355 - First build
#

use DBI;
use Time::Local;

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
	if ( ($line =~ /^VARREPORT_host/) && ($CLIREPORT_host < 1) )
		{$VARREPORT_host = $line;   $VARREPORT_host =~ s/.*=//gi;}
	if ( ($line =~ /^VARREPORT_user/) && ($CLIREPORT_user < 1) )
		{$VARREPORT_user = $line;   $VARREPORT_user =~ s/.*=//gi;}
	if ( ($line =~ /^VARREPORT_pass/) && ($CLIREPORT_pass < 1) )
		{$VARREPORT_pass = $line;   $VARREPORT_pass =~ s/.*=//gi;}
	if ( ($line =~ /^VARREPORT_port/) && ($CLIREPORT_port < 1) )
		{$VARREPORT_port = $line;   $VARREPORT_port =~ s/.*=//gi;}
	if ( ($line =~ /^VARREPORT_dir/) && ($CLIREPORT_dir < 1) )
		{$VARREPORT_dir = $line;   $VARREPORT_dir =~ s/.*=//gi;}
	$i++;
	}

$dbh = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
    or die "Couldn't connect to database: " . DBI->errstr;
$dbhB = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
    or die "Couldn't connect to database: " . DBI->errstr;

$secX = time();
	($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
$year = ($year + 1900);
$mon++;
$rowdate = "$mon/$mday/$year";
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
if ($min < 10) {$min = "0$min";}
if ($hour < 10) {$hour = "0$hour";}
if ($sec < 10) {$sec = "0$sec";}
$run_time="$year-$mon-$mday $hour:$min:$sec";


$time = $secX;
	($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time-14400);
$year = ($year + 1900);
$mon++;
$rowdate = "$mon/$mday/$year";
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
if ($min < 10) {$min = "0$min";}
if ($hour < 10) {$hour = "0$hour";}
if ($sec < 10) {$sec = "0$sec";}

$shipdate = "$year-$mon-$mday";
$start_date = "$year$mon$mday";
$datestamp = "$year/$mon/$mday $hour:$min";
$four_hours_ago = "$year-$mon-$mday $hour:$min:$sec";
$FILEDATE = "$year$mon$mday$hour$min$sec";

$time = $secX;
	($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time-86400*30);
$year = ($year + 1900);
$mon++;
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
if ($min < 10) {$min = "0$min";}
if ($hour < 10) {$hour = "0$hour";}
if ($sec < 10) {$sec = "0$sec";}
$one_month_ago = "$year-$mon-$mday $hour:$min:$sec";

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
			print "  [--month-check]             = Check the entire KHOMP log table for any missing phone patterns from the past month.\n";
			print "  [--full-check]              = Check the entire KHOMP log table for any missing phone patterns - will only execute\n";
			print "                                if no agents are logged in - otherwise the 4-hour window is used.\n";
			print "  [--custom-check=yyyy-mm-dd] = Check the entire KHOMP log table for any missing phone patterns from the date defined.\n";
			print "                                Date must be in yyyy-mm-dd format or default 4-hour window is used.\n";
			print "                                Use caution if running while agents are dialing to prevent database locking.\n";
			print "  [-debug]                    = Verbose debug messages\n";
			print "  [-debugX]                   = Extra verbose debug messages\n";
			print "\n";
			exit;
			}

		if ($args =~ /--month-check/i)
			{
			$month_check=1;
			}
		if ($args =~ /--full-check/i)
			{
			$full_check=1;
			}
		if ($args =~ /--custom-check/i)
			{
			$custom_check=1;
			@cdate_array = split(/--custom-check=/,$args);
			$custom_date=$cdate_array[1];
			$custom_date=~s/ .*//;
			$custom_date=~s/[^0-9\-]//g;
			}
		if ($args =~ /--debug/i)
			{
			$DB=1;
			}
		if ($args =~ /--debugX/i)
			{
			$DB=1;
			$DBX=1;
			}

	}

$run_check=1;
### concurrency check -mandatory per previous line
if ($run_check > 0)
	{
	my $grepout = `/bin/ps ax | grep $0 | grep -v grep | grep -v '/bin/sh'`;
	my $grepnum=0;
	$grepnum++ while ($grepout =~ m/\n/g);
	if ($grepnum > 1)
		{
		if ($DB) {print "I am not alone! Another $0 is running! Exiting...\n";}
		exit;
		}
	}

$stmt="select * from vicidial_settings_containers where container_id='KHOMPSTATUSMAP'";
$rslt=$dbh->prepare($stmt);
$rslt->execute();
@row=$rslt->fetchrow_array();
$rslt->finish;
$container_entry=$row[4];
@container_array=split(/\n/, $container_entry);
@khomps_array=();
@new_khomps=();
for ($i=0; $i<scalar(@container_array); $i++) {
	@khomp_side=split(/\=\>/, $container_array[$i]);
	$khomp_side[0]=~s/^\s+|\s+$//g;
	push @khomps_array, $khomp_side[0];
}

$start_date=$four_hours_ago;
$action="4HR CHECK";
if ($custom_check>0) {
	if ($custom_date=~/[0-9]{4}\-[0-9]{2}\-[0-9]{2}/) {
		$start_date="$custom_date 00:00:00";
		$action="CUSTOM DATE CHECK";
	} else {
		print "\n\nWARNING: custom date is not in valid yyyy-mm-dd format - reverting to default start time of $four_hours_ago\n\n";
	}
} elsif ($month_check>0) {
	$start_date=$one_month_ago;
	$action="MONTH CHECK";
} elsif ($full_check) {
	$lc_stmt="select count(*) From live_channels";
	if ($DB) {print "$lc_stmt\n";}
	$lc_rslt=$dbh->prepare($lc_stmt);
	$lc_rslt->execute();
	@lc_row=$lc_rslt->fetchrow_array;
	$live_channels=$lc_row[0];
	$lc_rslt->finish;

	$vla_stmt="select count(*) From vicidial_live_agents";
	if ($DB) {print "$vla_stmt\n";}
	$vla_rslt=$dbh->prepare($vla_stmt);
	$vla_rslt->execute();
	@vla_row=$vla_rslt->fetchrow_array;
	$live_agents=$vla_row[0];
	$vla_rslt->finish;

	if ($live_channels==0 && $live_agents==0) {
		$start_date="2010-01-01";
		$action="FULL CHECK";
	}
}

$khomp_stmt="select conclusion, pattern from vicidial_khomp_log where start_date>='$start_date'";
if ($DB) {print $khomp_stmt."\n";}
$khomp_rslt=$dbh->prepare($khomp_stmt);
$khomp_rslt->execute();
while(@khomp_row=$khomp_rslt->fetchrow_array) {
	if (length($khomp_row[0])>0) {
		$khomp_key="\"$khomp_row[0]\"";
		if (length($khomp_row[1])>0) {
			$khomp_key.=".\"$khomp_row[1]\"";
		}
		if ( !grep( /^$khomp_key$/, @khomps_array ) ) {
			push @new_khomps, $khomp_key;
			push @khomps_array, $khomp_key;
		}
	}
}
$khomp_rslt->finish();

if ($DBX) {foreach (@khomps_array) {print "$_\n";}}

my $new_khomps_str = join ' => \r\n', @new_khomps;
if ($new_khomps_str!~/\s\=\>\s$/) {$new_khomps_str.=" => ";}
$container_entry.="\r\n$new_khomps_str";
$container_entry=~s/\\r\\n/\r\n/gi;

if (scalar(@new_khomps)>0) {
	$upd_stmt="update vicidial_settings_containers set container_entry='".$container_entry."' where container_id='KHOMPSTATUSMAP'";
	if ($DB) {print $upd_stmt."\n";}
	$upd_rslt=$dbh->prepare($upd_stmt);
	$upd_rslt->execute();
	$upd_rslt->finish;
}

$dbh->disconnect;
$dbhB->disconnect;
