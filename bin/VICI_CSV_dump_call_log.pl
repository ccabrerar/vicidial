#!/usr/bin/perl

# VICI_CSV_dump_call_log.pl

# DESCRIPTION:
# adjusts the auto_dial_level for vicidial adaptive-predictive campaigns. 
# gather call stats for campaigns and in-groups
#
# Copyright (C) 2016  Joe Johnson, Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGELOG
# 60823-1302 - First build
#

$secX = time();
$time = $secX;
	($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
$year = ($year + 1900);
$mon++;
if ($mon < 10) {$mon = "0$mon";}
if ($mday < 10) {$mday = "0$mday";}
if ($hour < 10) {$hour = "0$hour";}
if ($min < 10) {$min = "0$min";}
if ($sec < 10) {$sec = "0$sec";}
$timestamp = "$year-$mon-$mday $hour:$min:$sec";
$start_date = "$year-$mon-$mday";
$filedate="$year$mon$mday";
$dayofweek=$wday;
$datestamp = "$year/$mon/$mday $hour:$min";


# default path to astguiclient configuration file:
if (length($PATHconf) < 5)
	{$PATHconf =		'/etc/astguiclient.conf';}

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

# DEFAULT VALUES
$file_type="csv";
$delimiter=",";
$output_file="Vicidial_CSV_dump";
$subdir="/";

for ($q=0; $q<scalar(@ARGV); $q++) {
	if ($ARGV[$q]=~/--timestamp/) {
		$ARGV[$q]=~s/\-\-timestamp=//;
		if ($ARGV[$q]=~/day/i) {
			$file_suffix="_$filedate";
		} elsif ($ARGV[$q]=~/week|wk/i) {
			$file_suffix="_$dayofweek";
		} else {
			$file_suffix="";
		}
	}
	if ($ARGV[$q]=~/--file_type/) {
		$ARGV[$q]=~s/\-\-file_type=//;
		$file_type=$ARGV[$q];
		$file_type=~s/[^a-z]//gi;
	}
	if ($ARGV[$q]=~/--delimiter/) {
		$ARGV[$q]=~s/\-\-delimiter=//;
		$delimiter=$ARGV[$q];
	}
	if ($ARGV[$q]=~/--output_file/) {
		$ARGV[$q]=~s/\-\-output_file=//;
		$output_file=$ARGV[$q];
	}
	if ($ARGV[$q]=~/--subdir/) {
		$ARGV[$q]=~s/\-\-subdir=//;
		$subdir=$ARGV[$q];
		if ($subdir!~/^\//) {
			$subdir="/$subdir";
		}
	}

	if ($ARGV[$q]=~/--debug/) {
		$DB=1;
	}
	if ($ARGV[$q]=~/--debugX/) {
		$DBX=1;
	}

	if ($ARGV[$q]=~/--help/) {
		print "Run time options:\n";
		print "  [--timestamp] = set to 'day' to put today's date as part of the outfile name, or 'week' or 'wk' for the numeric day of the week\n";
		print "  [--output_file] = customize output file name - do not put a file type as part of the name.  Default is 'Vicidial_CSV_dump'\n";
		print "  [--file_type] = determines file type, which must be either txt or csv\n";
		print "  [--delimiter] = If file_type is txt, this character will be the delimiter (remember to include backslashes if needed). Default is ',' for txt files and quote-comma for csv files\n";
		print "  [--subdir] = specify sub-directory in the web directory to write file to. Default is web path: $PATHweb/\n";
		print "  [--debug or --debugX] = Debug options.  debug will show output parameters and statuses queried for, debugX will show the SQL queries as well\n";
		print "\n";
		exit;
	}
}
$output_file.=$file_suffix.".".$file_type;
$output_dir="$PATHweb$subdir";


# Check that file is txt or csv.  If csv set delimiter to quote-comma.
if ($file_type!~/txt|csv/i) {
	die "\nInvalid output file type\n";
} elsif ($file_type=~/csv/i) {
	$delimiter="\",\"";
}

if ($DB || $DBX) {
	print "\nOutput parameters\n";
	print "-------------------\n";
	print "Output filename  : $output_file\n";
	print "Output directory : $output_dir\n";
	print "Timestamp        : $file_suffix\n";
	print "File type        : $file_type\n";
	print "Delimiter        : $delimiter\n\n";
	sleep(3);
}

# Check if directory exists, then open file to write to if it does.
if (-d $output_dir) {
	open(STORAGE, "> $output_dir/$output_file");
} else {
	die "\nOutput directory $PATHweb$subdir does not exist or is not a directory\n";
}

# Customized Variables
$server_ip = $VARserver_ip;		# Asterisk server IP

$start_time = time();
$servers=0;

use DBI;

$dbh = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
 or die "Couldn't connect to database: " . DBI->errstr;

# $user_stmt="SELECT distinct '' as full_name, user from ".$agent_log_table." where event_time >= '$start_date 00:00:00' and event_time <= '$start_date 23:59:59' order by user asc"; 

$status_stmt="select distinct status from vicidial_campaign_statuses where customer_contact='Y' UNION select distinct status from vicidial_statuses where customer_contact='Y' order by status";
if ($DBX) {print "$status_stmt\n";}
$status_rslt=$dbh->prepare($status_stmt);
$status_rslt->execute();
@status_array=();
while (@row=$status_rslt->fetchrow_array) {
	$status=$row[0];
	if (!grep(/^$status$/, @status_array)) {push(@status_array, "$status");}
}

$status_str=join("','", @status_array);

if ($DB || $DBX) {print "Status list: '$status_str'\n";}

$agent_log_stmt="select event_time, vu.full_name, vu.user, talk_sec, v.phone_number from vicidial_list v, vicidial_agent_log val, vicidial_users vu where event_time >= '$start_date 00:00:00' and event_time <= '$start_date 23:59:59' and val.status in ('".$status_str."') and val.user=vu.user and val.lead_id=v.lead_id order by event_time asc";
if ($DBX) {print "$agent_log_stmt\n";}
$agent_log_rslt=$dbh->prepare($agent_log_stmt);
$agent_log_rslt->execute();
while (@row=$agent_log_rslt->fetchrow_array) {
	for ($i=0; $i<scalar(@row); $i++) {
		if ($delimiter eq "\",\"") {
			$row[$i]=~s/\"/\\\"/gi;
		} else {
			$row[$i]=~s/$delimiter/\\$delimiter/gi;
		}
	}
	if ($delimiter eq "\\t") {
		$output_str=join("\t", @row);
	} else {
		$output_str=join("$delimiter", @row);
	}
	if ($delimiter eq "\",\"") {
		$output_str="\"$output_str\"";
	}
	$output_str.="\n";
	print STORAGE $output_str;
}
close(STORAGE);

$end_time = time();

print "Elapsed time: ".($end_time-$start_time)." sec\n";
