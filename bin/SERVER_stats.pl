#!/usr/bin/perl
#
# SERVER_stats.pl version 2.14
#
# This script gathers several system metrics and stores them in the MySQL database
#
# Copyright (C) 2024  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# CHANGES
# 240217-2226 - First build
#

$start_epoch = time();

# constants
$DB=0;  # Debug flag, set to 0 for no debug messages, lots of output
$DBX=0;
$run_check=1; # concurrency check
$iodelay=60;

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
		print "  [--test] = test\n";
		print "  [--debug] = verbose debug messages\n";
		print "  [--debugX] = Extra-verbose debug messages\n";
		print "  [--help] = this screen\n";
		print "\n";
		exit;
		}
	else
		{
		if ($args =~ /--debug/i)
			{
			$DB=1; # Debug flag
			print "\n----- DEBUGGING ENABLED -----\n\n";
			}
		if ($args =~ /--debugX/i)
			{
			$DBX=1;
			print "\n----- SUPER-DUPER DEBUGGING -----\n\n";
			}
		if ($args =~ /--test/i)
			{
			$TEST=1;
			$T=1;
			}
		}
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
	if ($line =~ /^PATHhome/)	{$PATHhome = $line;   $PATHhome =~ s/.*=//gi;}
	if ($line =~ /^PATHlogs/)	{$PATHlogs = $line;   $PATHlogs =~ s/.*=//gi;}
	if ($line =~ /^PATHagi/)	{$PATHagi = $line;   $PATHagi =~ s/.*=//gi;}
	if ($line =~ /^PATHweb/)	{$PATHweb = $line;   $PATHweb =~ s/.*=//gi;}
	if ($line =~ /^PATHsounds/)	{$PATHsounds = $line;   $PATHsounds =~ s/.*=//gi;}
	if ($line =~ /^PATHmonitor/)	{$PATHmonitor = $line;   $PATHmonitor =~ s/.*=//gi;}
	if ($line =~ /^VARserver_ip/)	{$VARserver_ip = $line;   $VARserver_ip =~ s/.*=//gi;}
	if ($line =~ /^VARDB_server/)	{$VARDB_server = $line;   $VARDB_server =~ s/.*=//gi;}
	if ($line =~ /^VARDB_database/)	{$VARDB_database = $line;   $VARDB_database =~ s/.*=//gi;}
	if ($line =~ /^VARDB_user/)	{$VARDB_user = $line;   $VARDB_user =~ s/.*=//gi;}
	if ($line =~ /^VARDB_pass/)	{$VARDB_pass = $line;   $VARDB_pass =~ s/.*=//gi;}
	if ($line =~ /^VARDB_custom_user/)	{$VARDB_custom_user = $line;   $VARDB_custom_user =~ s/.*=//gi;}
	if ($line =~ /^VARDB_custom_pass/)	{$VARDB_custom_pass = $line;   $VARDB_custom_pass =~ s/.*=//gi;}
	if ($line =~ /^VARDB_port/)	{$VARDB_port = $line;   $VARDB_port =~ s/.*=//gi;}
	$i++;
	}

# Customized Variables
$server_ip = $VARserver_ip;		# Asterisk server IP


### find df binary (partition usage)
$dfbin = '';
if ( -e ('/bin/df')) {$dfbin = '/bin/df';}
else
	{
	if ( -e ('/usr/bin/df')) {$dfbin = '/usr/bin/df';}
	else
		{
		if ( -e ('/usr/local/bin/df')) {$dfbin = '/usr/local/bin/df';}
		else
			{
			print "Can't find df binary! Exiting...\n";
			exit;
			}
		}
	}

### find iostat binary (device IO usage)
$iostatbin = '';
if ( -e ('/bin/iostat')) {$iostatbin = '/bin/iostat';}
else
	{
	if ( -e ('/usr/bin/iostat')) {$iostatbin = '/usr/bin/iostat';}
	else
		{
		if ( -e ('/usr/local/bin/iostat')) {$iostatbin = '/usr/local/bin/iostat';}
		else
			{
			print "Can't find iostat binary! Exiting...\n";
			exit;
			}
		}
	}

### find nproc binary (CPU count)
$nprocbin = '';
if ( -e ('/bin/nproc')) {$nprocbin = '/bin/nproc';}
else
	{
	if ( -e ('/usr/bin/nproc')) {$nprocbin = '/usr/bin/nproc';}
	else
		{
		if ( -e ('/usr/local/bin/nproc')) {$nprocbin = '/usr/local/bin/nproc';}
		else
			{
			print "Can't find nproc binary! Exiting...\n";
			exit;
			}
		}
	}

### find uptime binary
if ( -e ('/bin/uptime')) {$uptimebin = '/bin/uptime';}
else 
	{
	if ( -e ('/usr/bin/uptime')) {$uptimebin = '/usr/bin/uptime';}
	else 
		{
		if ( -e ('/usr/local/bin/uptime')) {$uptimebin = '/usr/local/bin/uptime';}
		else
			{
			print "Can't find uptime binary!\n";
			}
		}
	}

### find hostname binary (line and word count)
$hostnamebin = '';
if ( -e ('/bin/hostname')) {$hostnamebin = '/bin/hostname';}
else
	{
	if ( -e ('/usr/bin/hostname')) {$hostnamebin = '/usr/bin/hostname';}
	else
		{
		if ( -e ('/usr/local/bin/hostname')) {$hostnamebin = '/usr/local/bin/hostname';}
		else
			{
			if ($DB > 0) {print "Can't find hostname binary, not going to gather database info.\n";}
			}
		}
	}

### find mysqladmin binary (database admin)
$mysqladminbin = '';
if ( -e ('/bin/mysqladmin')) {$mysqladminbin = '/bin/mysqladmin';}
else
	{
	if ( -e ('/usr/bin/mysqladmin')) {$mysqladminbin = '/usr/bin/mysqladmin';}
	else
		{
		if ( -e ('/usr/local/bin/mysqladmin')) {$mysqladminbin = '/usr/local/bin/mysqladmin';}
		else
			{
			if ($DB > 0) {print "Can't find mysqladmin binary, not going to gather database info.\n";}
			}
		}
	}

### find wc binary (line and word count)
$wcbin = '';
if ( -e ('/bin/wc')) {$wcbin = '/bin/wc';}
else
	{
	if ( -e ('/usr/bin/wc')) {$wcbin = '/usr/bin/wc';}
	else
		{
		if ( -e ('/usr/local/bin/wc')) {$wcbin = '/usr/local/bin/wc';}
		else
			{
			if ($DB > 0) {print "Can't find wc binary, not going to gather database info.\n";}
			}
		}
	}


if (!$VARDB_port) {$VARDB_port='3306';}


&get_time_now;

#use lib './lib', '../lib';
use Time::HiRes ('gettimeofday','usleep','sleep');  # necessary to have perl sleep command of less than one second
use DBI;
use Switch;
use POSIX;
use Scalar::Util qw(looks_like_number);


$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
or die "Couldn't connect to database: " . DBI->errstr;

### concurrency check (SCREEN uses script path, so check for more than 2 entries)
if ($run_check > 0)
	{
	my $grepout = `/bin/ps ax | grep $0 | grep -v grep | grep -v '/bin/sh'`;
	my $grepnum=0;
	$grepnum++ while ($grepout =~ m/\n/g);
	if ($grepnum > 2) 
		{
		if ($DB) {print "I am not alone! Another $0 is running! Exiting...\n";}
		$event_string = "I am not alone! Another $0 is running! Exiting...";
		&event_logger($SYSLOG,$event_string);
		exit;
		}
	}


$event_string="STARTING NEW STATS GRAB";
event_logger($SYSLOG,$event_string);

# insert/update server_live_stats record in the DB
$stmtA = "INSERT INTO server_live_stats set update_time=NOW(), server_ip='$server_ip' ON DUPLICATE KEY UPDATE update_time=NOW();";
if($DB){print STDERR "\n$stmtA\n";}
$dbhA->do($stmtA);
$affected_rows = $dbhA->do($stmtA);


# gather server stats
$MYSQLuptime=0;   $MYSQLconnections=0;   $MYSQLqueries=0;

$hostname = get_hostname();

$cpu_count = get_cpu_count();

( $loadavg_1, $loadavg_5, $loadavg_15 ) = get_cpu_load();

( $mem_total, $mem_used, $mem_free ) = get_mem_usage();

$num_processes = get_num_processes();

$system_uptime = get_uptime();

(	$cpu_user_percent,
	$cpu_idle_percent,
	$cpu_iowait_percent,
	$cpu_sys_percent,
	$cpu_vm_percent,
	$user_diff,
	$nice_diff,
	$system_diff,
	$idle_diff,
	$iowait_diff,
	$irq_diff,
	$softirq_diff,
	$steal_diff,
	$guest_diff,
	$guest_nice_diff ) = get_cpu_percent();

( $reads, $writes ) = get_disk_rw();

my ( $asterisk_channels_total, $asterisk_agents_total ) = get_asterisk_data($dbhA,$server_ip);

if (length($mysqladminbin) > 2) 
	{
	$mysql_data_begin = time();
	( $MYSQLuptime, $MYSQLconnections, $MYSQLqueries ) = get_mysqlstatus();
	$MYSQLqueriesOLD = $MYSQLqueries;
	}


# insert/update server_live_stats record in the DB
$stmtA = "UPDATE server_live_stats set update_time=NOW(),server_name='$hostname',cpu_count='$cpu_count',loadavg_1='$loadavg_1',loadavg_5='$loadavg_5',loadavg_15='$loadavg_15',freeram='$mem_free',usedram='$mem_used',processes='$num_processes',system_uptime='$system_uptime',asterisk_channels_total='$asterisk_channels_total',asterisk_agents_total='$asterisk_agents_total',mysql_uptime='$MYSQLuptime',mysql_connections='$MYSQLconnections' WHERE server_ip='$server_ip';";
if($DB){print STDERR "\n$stmtA\n";}
$dbhA->do($stmtA);
$affected_rows = $dbhA->do($stmtA);


my $disk_usage = get_disk_space($dbhA,$server_ip);

my $storage_devices = get_disk_io($dbhA,$server_ip);

( $reads, $writes ) = get_disk_rw();

(	$cpu_user_percent,
	$cpu_idle_percent,
	$cpu_iowait_percent,
	$cpu_sys_percent,
	$cpu_vm_percent,
	$user_diff,
	$nice_diff,
	$system_diff,
	$idle_diff,
	$iowait_diff,
	$irq_diff,
	$softirq_diff,
	$steal_diff,
	$guest_diff,
	$guest_nice_diff ) = get_cpu_percent();
$read_write_cpu_SQL = "disk_reads='$reads',disk_writes='$writes',cpu_user_percent='$cpu_user_percent',cpu_sys_percent='$cpu_sys_percent',cpu_idle_percent='$cpu_idle_percent',cpu_iowait_percent='$cpu_iowait_percent',cpu_vm_percent='$cpu_vm_percent'";

if (length($mysqladminbin) > 2) 
	{
	$mysql_data_end = time();
	( $MYSQLuptime, $MYSQLconnections, $MYSQLqueries ) = get_mysqlstatus();

	$MYSQLqueriesTEMP = ($MYSQLqueries - $MYSQLqueriesOLD);
	$mysql_data_sec = ($mysql_data_end - $mysql_data_begin);
	$mysql_queries_per_second = sprintf("%.0f", ($MYSQLqueriesTEMP / $mysql_data_sec));

	if($DB){print STDERR "\n$MYSQLqueriesTEMP = ($MYSQLqueries - $MYSQLqueriesOLD)\n";}

	# update server_live_stats record in the DB
	$stmtA = "UPDATE server_live_stats set update_time=NOW(),mysql_uptime='$MYSQLuptime',mysql_connections='$MYSQLconnections',mysql_queries_per_second='$mysql_queries_per_second',$read_write_cpu_SQL WHERE server_ip='$server_ip';";
	if($DB){print STDERR "\n$stmtA\n";}
	$dbhA->do($stmtA);
	$affected_rows = $dbhA->do($stmtA);
	}
else 
	{
	# update server_live_stats record in the DB
	$stmtA = "UPDATE server_live_stats set update_time=NOW(),$read_write_cpu_SQL WHERE server_ip='$server_ip';";
	if($DB){print STDERR "\n$stmtA\n";}
	$dbhA->do($stmtA);
	$affected_rows = $dbhA->do($stmtA);
	}



$event_string='FINISHING UP|';
event_logger($SYSLOG,$event_string);


$sthA->finish();
$dbhA->disconnect();

$end_epoch = time();
$run_time = ($end_epoch - $start_epoch);

if($DB){print "DONE... Exiting... Goodbye... See you later!     runtime: $run_time seconds \n";}


exit;







#################### SUBROUTINES #########################

sub event_logger 
	{
	my ($SYSLOG,$event_string) = @_;
	if ($SYSLOG)
		{
		### open the log file for writing ###
		open(Lout, ">>$PATHlogs/server_stats.$action_log_date")
				|| die "Can't open $PATHlogs/server_stats.$action_log_date: $!\n";
		print Lout "$now_date|$event_string|\n";
		close(Lout);
		}
	$event_string='';
	}

sub get_time_now	#get the current date and time and epoch for logging call lengths and datetimes
	{
	($sec,$min,$hour,$mday,$mon,$year,$wday,$yday,$isdst) = localtime(time);
	$year = ($year + 1900);
	$mon++;
	if ($mon < 10) {$mon = "0$mon";}
	if ($mday < 10) {$mday = "0$mday";}
	if ($hour < 10) {$hour = "0$hour";}
	if ($min < 10) {$min = "0$min";}
	if ($sec < 10) {$sec = "0$sec";}

	$now_date_epoch = time();
	$now_date = "$year-$mon-$mday $hour:$min:$sec";
	$action_log_date = "$year-$mon-$mday";
	}



sub print_now 
	{
	my $string = shift;

	# get the current time in microseconds
	my ( $now_sec, $now_micro_sec ) = gettimeofday();

	# figure out how many micro seconds since epoch
	my $now_micro_epoch = $now_sec * 1000000;
	$now_micro_epoch = $now_micro_epoch + $now_micro_sec;

	print "\n$string|$now_micro_epoch\n\n";
	}


sub get_cpu_percent
	{
	# stat file in proc
	$stat = '/proc/stat';
	
	# read it
	open( FH , "<$stat" );
	@lines = <FH>;
	close( FH );

	# get the various times spent doing stuff ( $cpu is useless )
	($cpu, $user, $nice, $system, $idle, $iowait, $irq, $softirq, $steal, $guest, $guest_nice ) = split( ' ', $lines[0] );

	# total the different catagories of time
	$cpu_user = $user + $nice;
	$cpu_idle = $idle;
	$cpu_iowait = $iowait;
	$cpu_sys = $system + $irq + $softirq;
	$cpu_vm = $steal + $guest + $guest_nice;

	# check if we have previous values
	if ( defined ( $prev_user ) ) 
		{
		# get the differential from last time
		$cpu_user_diff = $cpu_user - $prev_cpu_user;
		$cpu_idle_diff = $cpu_idle - $prev_cpu_idle;
		$cpu_iowait_diff = $cpu_iowait - $prev_cpu_iowait;
		$cpu_sys_diff = $cpu_sys - $prev_cpu_sys;
		$cpu_vm_diff = $cpu_vm - $prev_cpu_vm;

		$user_diff = $user - $prev_user;
		$nice_diff = $nice - $prev_nice;
		$system_diff = $system - $prev_system;
		$idle_diff = $idle - $prev_idle;
		$iowait_diff = $iowait - $prev_iowait;
		$irq_diff = $irq - $prev_irq;
		$softirq_diff = $softirq - $prev_softirq;
		$steal_diff = $steal - $prev_steal;
		$guest_diff = $guest - $prev_guest;
		$guest_nice_diff = $guest_nice - $prev_guest_nice;

		# total the differentials
		$cpu_total_diff = $cpu_user_diff + $cpu_idle_diff + $cpu_sys_diff + $cpu_vm_diff + $cpu_iowait_diff;

		$cpu_user_percent  = sprintf("%.2f", (( $cpu_user_diff / $cpu_total_diff ) * 100 ));
		$cpu_idle_percent  = sprintf("%.2f", (( $cpu_idle_diff / $cpu_total_diff ) * 100 ));
		$cpu_iowait_percent  = sprintf("%.2f", (( $cpu_iowait_diff / $cpu_total_diff ) * 100 ));
		$cpu_sys_percent   = sprintf("%.2f", (( $cpu_sys_diff / $cpu_total_diff ) * 100 ));
		$cpu_vm_percent    = sprintf("%.2f", (( $cpu_vm_diff / $cpu_total_diff ) * 100 ));
		}
	else 
		{
		# first time runnings so there are no previous values.
		# all we can do is base it off the system totals since boot.
		$cpu_total = $cpu_user + $cpu_idle + $cpu_sys + $cpu_vm;

		$cpu_user_percent  = sprintf("%.2f", (( $cpu_user / $cpu_total ) * 100 ));
		$cpu_idle_percent  = sprintf("%.2f", (( $cpu_idle / $cpu_total ) * 100 ));
		$cpu_iowait_percent  = sprintf("%.2f", (( $cpu_iowait / $cpu_total ) * 100 ));
		$cpu_sys_percent   = sprintf("%.2f", (( $cpu_sys / $cpu_total ) * 100 ));
		$cpu_vm_percent    = sprintf("%.2f", (( $cpu_vm / $cpu_total ) * 100 ));		
		}
	
	# store the values for the next call
	$prev_user = $user;
	$prev_nice = $nice;
	$prev_system = $system;
	$prev_idle = $idle;
	$prev_iowait = $iowait;
	$prev_irq = $irq;
	$prev_softirq = $softirq;
	$prev_steal = $steal;
	$prev_guest = $guest;
	$prev_guest_nice = $guest_nice;
	$prev_cpu_user = $cpu_user;
	$prev_cpu_idle = $cpu_idle;
	$prev_cpu_iowait = $cpu_iowait;
	$prev_cpu_sys = $cpu_sys;
	$prev_cpu_vm = $cpu_vm;

	return ( 
		$cpu_user_percent, 
		$cpu_idle_percent, 
		$cpu_iowait_percent, 
		$cpu_sys_percent, 
		$cpu_vm_percent, 
		$user_diff, 
		$nice_diff, 
		$system_diff, 
		$idle_diff, 
		$iowait_diff, 
		$irq_diff, 
		$softirq_diff, 
		$steal_diff, 
		$guest_diff, 
		$guest_nice_diff 
	);
	}


sub get_cpu_load
	{
	$lavg = '/proc/loadavg';
	
	open( FH , "<$lavg" );
	@lines = <FH>;
	close( FH );

	@loadavg_data = split(' ',$lines[0]);
	$loadavg_1 = $loadavg_data[0];
	$loadavg_5 = $loadavg_data[1];
	$loadavg_15 = $loadavg_data[2];
	
	return ( $loadavg_1, $loadavg_5, $loadavg_15 );
	}


sub get_mem_usage
	{
	$meminfo = '/proc/meminfo';
	
	open( FH , "<$meminfo" );
	@lines = <FH>;
	close( FH );
	
	$mem_total = $lines[0];
	$mem_total =~ s/MemTotal: *//g;
	
	$mem_free = $lines[1];
	$mem_free =~ s/MemFree: *//g;

	$mem_used = $mem_total - $mem_free;

	$mem_used = floor( $mem_used / 1024 );
	$mem_total = floor( $mem_total / 1024 );
	$mem_free = floor( $mem_free / 1023 );

	return ( $mem_total, $mem_used, $mem_free );
	}


sub get_disk_space
	{
	my ($dbhA,$server_ip) = @_;

	# df  -B 1048576 -x nfs -x cifs -x sshfs -x ftpfs
	#Filesystem     1M-blocks   Used Available Use% Mounted on
	#devtmpfs               4      0         4   0% /dev
	#tmpfs              64395      1     64395   1% /dev/shm
	#tmpfs              25758     42     25717   1% /run
	#tmpfs                  4      0         4   0% /sys/fs/cgroup
	#/dev/md0          933742  99229    787010  12% /
	#tmpfs               6144      0      6144   0% /var/spool/asterisk/monitor
	#/dev/md2          937801 363804    531254  41% /srv/mysql
	#tmpfs              12879      0     12879   0% /run/user/0

	@serverDISK = `$dfbin -B 1048576 -x nfs -x cifs -x sshfs -x ftpfs`;
	$ct=0;
	$ct_PCT=0;
	$disk_usage = '';
	foreach(@serverDISK)
		{
		if ($serverDISK[$ct] =~ /(\d+\%)/)
			{
			$usage = $1;
			$usage =~ s/\%//gi;
			$disk_usage .= "$ct_PCT $usage|";
			$partition_path = $serverDISK[$ct];
			$partition_path =~ s/.*\% |\r|\n|\t//gi;
			$partition_filesystem = $serverDISK[$ct];
			$partition_filesystem =~ s/ .*|\r|\n|\t//gi;
			$disk_data = $serverDISK[$ct];
			$disk_data =~ s/\s+/ /gi;
			@disk_dataARY = split(/ /,$disk_data);
			$mb_used =		$disk_dataARY[2];
			$mb_available = $disk_dataARY[3];

			# insert/update server_live_drives record in the DB
			$stmtA = "INSERT INTO server_live_partitions set server_ip='$server_ip',partition_path='$partition_path',update_time=NOW(),partition_order='$ct_PCT',partition_filesystem='$partition_filesystem',use_pct='$usage',mb_used='$mb_used',mb_available='$mb_available' ON DUPLICATE KEY UPDATE update_time=NOW(),partition_order='$ct_PCT',partition_filesystem='$partition_filesystem',use_pct='$usage',mb_used='$mb_used',mb_available='$mb_available';";
			if($DB){print STDERR "\n$stmtA\n";}
			$dbhA->do($stmtA);
			$affected_rows = $dbhA->do($stmtA);

			$ct_PCT++;
			}
		$ct++;
		}

	return $disk_usage;
	}


sub get_disk_io
	{
	my ($dbhA,$server_ip) = @_;

	$ct_DEVICES = -1;
	# Device            r/s     w/s     rkB/s     wkB/s   rrqm/s   wrqm/s  %rrqm  %wrqm r_await w_await aqu-sz rareq-sz wareq-sz  svctm  %util
	# nvme0n1          2.22   23.56     10.10    129.77     0.00     7.24   0.00  23.50    2.13    0.68   0.03     4.55     5.51   0.95   2.45
	# @serverDISKio = `$iostatbin -yx 59 1`;
	@serverDISKioVERSION = `$iostatbin -V`;
	$IOSTATversion = $serverDISKioVERSION[0];
	$IOSTATversion =~ s/.*version |\..*//gi;
	$IOSTATversion =~ s/\D//gi;
	if ($IOSTATversion >= 11)
		{
		@serverDISKio = `$iostatbin -yx $iodelay 1`;
		$ct=0;
		$devices_trigger=0;
		$ct_DEVICES=0;
		$disk_io = '';
		foreach(@serverDISKio)
			{
			if ($serverDISKio[$ct] =~ /^Device/)
				{$devices_trigger++;}
			else
				{
				if ( ($devices_trigger > 0) && (length($serverDISKio[$ct]) > 14) )
					{
					$drive_order[$ct_DEVICES] =		$ct_DEVICES;
					$drive_device[$ct_DEVICES] =	substr($serverDISKio[$ct],0,13);
					$read_sec[$ct_DEVICES] =		substr($serverDISKio[$ct],13,8);
					$write_sec[$ct_DEVICES] =		substr($serverDISKio[$ct],21,8);
					$kb_read_sec[$ct_DEVICES] =		substr($serverDISKio[$ct],29,10);
					$kb_write_sec[$ct_DEVICES] =	substr($serverDISKio[$ct],39,10);
					$util_pct[$ct_DEVICES] =		substr($serverDISKio[$ct],129,7);

					$drive_device[$ct_DEVICES] =~ s/\s//gi;
					$read_sec[$ct_DEVICES] =~ s/\s//gi;
					$write_sec[$ct_DEVICES] =~ s/\s//gi;
					$kb_read_sec[$ct_DEVICES] =~ s/\s//gi;
					$kb_write_sec[$ct_DEVICES] =~ s/\s//gi;
					$util_pct[$ct_DEVICES] =~ s/\s//gi;

					# insert/update server_live_drives record in the DB
					$stmtA = "INSERT INTO server_live_drives set server_ip='$server_ip',drive_device='$drive_device[$ct_DEVICES]',update_time=NOW(),drive_order='$drive_order[$ct_DEVICES]',read_sec='$read_sec[$ct_DEVICES]',write_sec='$write_sec[$ct_DEVICES]',kb_read_sec='$kb_read_sec[$ct_DEVICES]',kb_write_sec='$kb_write_sec[$ct_DEVICES]',util_pct='$util_pct[$ct_DEVICES]' ON DUPLICATE KEY UPDATE update_time=NOW(),drive_order='$drive_order[$ct_DEVICES]',read_sec='$read_sec[$ct_DEVICES]',write_sec='$write_sec[$ct_DEVICES]',kb_read_sec='$kb_read_sec[$ct_DEVICES]',kb_write_sec='$kb_write_sec[$ct_DEVICES]',util_pct='$util_pct[$ct_DEVICES]';";
					if($DB){print STDERR "\n$stmtA\n";}
					$dbhA->do($stmtA);
					$affected_rows = $dbhA->do($stmtA);

					$ct_DEVICES++;
					}
				}
			$ct++;
			}
		}

	return $ct_DEVICES;
	}


sub get_cpu_count
	{
	@serverNPROC = `$nprocbin`;
	$ct_CPUS = $serverNPROC[0];
	$ct_CPUS =~ s/\D//gi;

	return $ct_CPUS;
	}


sub get_hostname
	{
	@serverHOSTNAME = `$hostnamebin`;
	$hostname = $serverHOSTNAME[0];
	$hostname =~ s/\n|\r|\t//gi;

	return $hostname;
	}


sub get_uptime
	{
	@sysuptime = `$uptimebin`;
	 # 18:26:14 up 153 days, 23:59,  4 users,  load average: 0.01, 0.01, 0.00
	 # 10:03am  up 123 days  0:10,  1 user,  load average: 0.11, 0.05, 0.01
	 # 10:03am  up   3:17,  2 users,  load average: 0.41, 0.28, 0.21

	if ($DBX) {print "Uptime debug 1: |$sysuptime[0]|\n";}
	$sysuptime[0] =~ s/ days, / days /gi;
	$sysuptime[0] =~ s/,.*//gi;
	$sysuptime[0] =~ s/  / /gi;
	$sysuptime[0] =~ s/  / /gi;
	$sysuptime[0] =~ s/\r|\n|\t//gi;
	@uptimedata = split(/ up /,$sysuptime[0]);
	$system_uptime = $uptimedata[1];

	return $system_uptime;
	}


sub get_num_processes
	{
	$num_processes = 0;

	opendir( DH, '/proc');
	while ( readdir( DH ) )
		{
		if ( looks_like_number( $_ ) ) { $num_processes++; }
		}
	closedir( DH );

	return ($num_processes);
	}


sub get_disk_rw
	{
	$total_reads = 0;
	$total_writes = 0;

	$diskstats = "/proc/diskstats";

	open( FH , "<$diskstats" );
	@lines = <FH>;
	close( FH );

	foreach $line ( @lines )
		{
		# remove excess white space
		$line =~ s/\h+/ /g;
		$line =~ s/^\s+|\s+$//g;

		# split the line
		(
			$major,
			$minor,
			$device,
			$reads_completed,
			$reads_merged,
			$sectors_read,
			$time_reading,
			$writes_completed,
			$writes_merged,
			$sectors_written,
			$time_writing,
			$current_ios,
			$ios_time,
			$weighted_ios_time
		) =   split( ' ', $line );

		# check if device is a drive or partition or raid
		if ( $device !~ /\d/ )
			{
			$total_reads += $sectors_read;
			$total_writes += $sectors_written;
			}
		}

	# check if we previously got the total reads
	if ( defined ( $prev_total_reads ) )
		{
		$reads = $total_reads - $prev_total_reads;
		$writes = $total_writes - $prev_total_writes;
		}
	else
		{
		# cannot figure out the difference
		# if we don't know what it was before
		$reads = 0;
		$writes = 0;
		}

	$prev_total_reads = $total_reads;
	$prev_total_writes = $total_writes;

	return ( $reads, $writes );
	}


sub get_asterisk_data
	{
	my ($dbhA,$server_ip) = @_;

	$live_channels=0;
	$stmtA = "SELECT count(*) FROM live_channels where server_ip='$server_ip';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	@aryA = $sthA->fetchrow_array;
	$live_channels = $aryA[0];
	if($DB){print STDERR "\n|$live_channels|$stmtA|\n";}

	$live_sip_channels=0;
	$stmtA = "SELECT count(*) FROM live_sip_channels where server_ip='$server_ip';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	@aryA = $sthA->fetchrow_array;
	$live_sip_channels = $aryA[0];
	if($DB){print STDERR "\n|$live_sip_channels|$stmtA|\n";}
	$total_channels = ($live_channels + $live_sip_channels);

	$live_agents=0;
	$stmtA = "SELECT count(*) FROM vicidial_live_agents where server_ip='$server_ip';";
	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	@aryA = $sthA->fetchrow_array;
	$live_agents = $aryA[0];
	if($DB){print STDERR "\n|$live_agents|$stmtA|\n";}

	return ( $total_channels, $live_agents );
	}


sub get_mysqlstatus
	{
	# Uptime: 3848845  Threads: 1  Questions: 231036439  Slow queries: 46  Opens: 54008  Flush tables: 2  Open tables: 64  Queries per second avg: 60.027
	@serverMYSQL = `$mysqladminbin status`;
	$MYSQLuptime=0;
	$MYSQLthreads=0;
	$MYSQLqueries=0;
	$MYSQLconnections=0;
	@mysql_status_data = split('  ',$serverMYSQL[0]);
	$MYSQLuptime = $mysql_status_data[0];
	$MYSQLuptime =~ s/\D//gi;
	$MYSQLthreads = $mysql_status_data[1];
	$MYSQLthreads =~ s/\D//gi;
	$MYSQLqueries = $mysql_status_data[2];
	$MYSQLqueries =~ s/\D//gi;

	if($DB){print STDERR "\n $mysqladminbin processlist | $wcbin \n";}

	@serverMYSQLconnections = `$mysqladminbin processlist | $wcbin`;
	$connection_data = $serverMYSQLconnections[0];
	$connection_data =~ s/\s+/ /gi;
	@serverMYSQLconnectionsARY = split(/ /,$connection_data);
	$MYSQLconnections =		($serverMYSQLconnectionsARY[1] - 4);

	return ( $MYSQLuptime, $MYSQLconnections, $MYSQLqueries );
	}
