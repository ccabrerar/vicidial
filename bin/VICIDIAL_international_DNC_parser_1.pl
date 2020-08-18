#!/usr/bin/perl
#
# VICIDIAL_international_DNC_parser_1.pl
#
# This is the first part of a two-step program in the DNC importing process
#
# It requires that a system settings container exists in your system 
# settings named "INTERNATIONAL_DNC_IMPORT".  Two run-time variables 
# must be defined in said container - "--file-dir" and "--file-destination"
#
# This should run every minute to check the directory defined in the 
# "--file-dir" variable for any files to post and to insert into the DNC 
# queue table in the database.  It will also purge any CANCELLED files from
# both the queue table and the file folder.
# 
# Put an entry into the cron of of your asterisk machine to run this script 
# every minute or however often you desire
#
# ### International DNC importing scripts
##* * * * * /usr/share/astguiclient/VICIDIAL_international_DNC_parser_1.pl
#
# Copyright (C) 2020  Matt Florell <vicidial@gmail.com>, Joe Johnson <joej@vicidial.com>    LICENSE: AGPLv2
#
# CHANGE LOG:
# 200813-1230 - First Build
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

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
    or die "Couldn't connect to database: " . DBI->errstr;
$dbhB = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
    or die "Couldn't connect to database: " . DBI->errstr;
$dbhC = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
    or die "Couldn't connect to database: " . DBI->errstr;
$dbhD = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
    or die "Couldn't connect to database: " . DBI->errstr;
$dbhE = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
    or die "Couldn't connect to database: " . DBI->errstr;

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
		print "  [--help] = this screen\n";
		print "  [--debug] = debug\n";
		print "  [--debugX] = super debug\n";
		print "  [--FTPdb] = show FTP debug output\n";
		print "  [--test] = will execute script and transfer files, but not delete lists from dialer\n";
		print "\n";
		exit;
		}
	else
		{
		if ($args =~ /--debug/i)
			{
			$DB=1;
			print "\n----- DEBUG -----\n\n";
			}
		if ($args =~ /--debugX/i)
			{
			$DB=1;
			$DBX=1;
			print "\n----- SUPER DEBUG -----\n\n";
			}
		if ($args =~ /--FTPdb/i)
			{
			$FTPdb=1;
			print "\n----- FTP DEBUG -----\n\n";
			}
		if ($args =~ /--test/i)
			{
			$T=1;
			print "\n----- TEST EXECUTION -----\n\n";
			}
		if ($args =~ /--force-dnc-scrub/i)
			{
			$force_dnc_scrub = "Y";
			if ($DBX) {print "\n----- FORCING DNC SCRUB -----\n\n";}
			}
		}
	}

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

$stmt="SELECT enable_international_dncs FROM system_settings;";
$rslt=$dbhA->prepare($stmt);
if ($DB) {print "$stmt\n";}
$rslt->execute();
if ($rslt->rows>0)
	{
	@row=$rslt->fetchrow_array;
	if ($row[0]==0 || !$row[0])
		{
		if ($DB) {print "Feature not enabled.\n"; exit;}
		}
	}
else
	{
	if ($DB) {print "Database error!\n"; exit;}
	}


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
$dnc_scrub_status="DNCI";

$stmt="select * from vicidial_settings_containers where container_id='INTERNATIONAL_DNC_IMPORT' and container_type='PERL_CLI'";
if ($DBX) {print "\n$stmt\n\n";}
$rslt=$dbhA->prepare($stmt);
$rslt->execute();
if ($rslt->rows>0) 
	{
	@row=$rslt->fetchrow_array;
	$row[4]=~s/\r//g;
	@container_settings=split(/\n/, $row[4]);
	for ($i=0; $i<scalar(@container_settings); $i++) 
		{
		$args=$container_settings[$i];
		$args=~s/^\s+|\s+$//;
		if ($args!~/^#/) 
			{
			if ($args =~ /--ftp-host/i)
				{
				my @data_in = split(/--ftp-host=/,$args);
				$ftp_host = $data_in[1];
				$ftp_host =~ s/ .*//gi;
				if ($DBX) {print "\n----- FTP HOST: $ftp_host -----\n\n";}
				}
			if ($args =~ /--ftp-user/i)
				{
				my @data_in = split(/--ftp-user=/,$args);
				$ftp_user = $data_in[1];
				$ftp_user =~ s/ .*//gi;
				if ($DBX) {print "\n----- FTP USER: $ftp_user -----\n\n";}
				}
			if ($args =~ /--ftp-pwd/i)
				{
				my @data_in = split(/--ftp-pwd=/,$args);
				$ftp_pwd = $data_in[1];
				$ftp_pwd =~ s/ .*//gi;
				if ($DBX) {print "\n----- FTP PASSWORD: $ftp_pwd -----\n\n";}
				}
			if ($args =~ /--ftp-port/i)
				{
				my @data_in = split(/--ftp-port=/,$args);
				$ftp_port = $data_in[1];
				$ftp_port =~ s/ .*//gi;
				if ($DBX) {print "\n----- FTP PORT: $ftp_port -----\n\n";}
				}
			if ($args =~ /--ftp-passive/i)
				{
				my @data_in = split(/--ftp-passive=/,$args);
				$ftp_passive = $data_in[1];
				$ftp_passive =~ s/ .*//gi;
				if ($DBX) {print "\n----- FTP PASSIVE: $ftp_passive -----\n\n";}
				}
			if ($args =~ /--file-dir/i)
				{
				my @data_in = split(/--file-dir=/,$args);
				$file_dir = $data_in[1];
				$file_dir =~ s/ .*//gi;
				if ($DBX) {print "\n----- FTP DIRECTORY: $file_dir -----\n\n";}
				}
			if ($args =~ /--file-destination/i)
				{
				my @data_in = split(/--file-destination=/,$args);
				$file_destination = $data_in[1];
				$file_destination =~ s/ .*//gi;
				if ($DBX) {print "\n----- FTP DESTINATION: $file_destination -----\n\n";}
				}
			if ($args =~ /--dnc-status-override/i)
				{
				my @data_in = split(/--dnc-status-override=/,$args);
				$dnc_scrub_status = $data_in[1];
				$dnc_scrub_status =~ s/ .*//gi;
				}
			}
		}
	$rslt->finish();

	if ($DBX) {print "\n----- DNC STATUS: $dnc_scrub_status -----\n\n";}

	if ($hour<1 || $force_dnc_scrub) {
		$scrub_stmt="select container_entry from vicidial_settings_containers where container_id='DNC_CURRENT_BLOCKED_LISTS' and container_entry!=''";
		if ($DBX) {print "$scrub_stmt\n";}
		$scrub_rslt=$dbhB->prepare($scrub_stmt);
		$scrub_rslt->execute();
		if ($scrub_rslt->rows>0) 
			{
			@scrub_row=$scrub_rslt->fetchrow_array;
			$scrub_rslt->finish;


			$dnc_entries=$scrub_row[0];

			@dnc_entries=split(/\n/, $scrub_row[0]);
			for ($i=0; $i<scalar(@dnc_entries); $i++) 
				{
				@current_scrub=split(/\=\>/, $dnc_entries[$i]);
				$scrub_list_id=$current_scrub[0];
				$scrub_country=$current_scrub[1];
				$scrub_list_id=~s/^\s+|\s+$//;
				$scrub_country=~s/^\s+|\s+$//;
				if (length($scrub_list_id)>0 && length($scrub_country)>0) 
					{
					$check_table_stmt="show tables like 'vicidial_dnc_".$scrub_country."'";
					$check_table_rslt=$dbhB->prepare($check_table_stmt);
					$check_table_rslt->execute();
					if ($check_table_rslt->rows>0)
						{
						$list_stmt="select phone_number from vicidial_list where list_id='$scrub_list_id'";
						$list_rslt=$dbhC->prepare($list_stmt);
						$list_rslt->execute();
						while (@list_row=$list_rslt->fetchrow_array) 
							{
							$dnc_check_stmt="select count(*) from vicidial_dnc_".$scrub_country." where phone_number='$list_row[0]'";
							$dnc_check_rslt=$dbhD->prepare($dnc_check_stmt);
							$dnc_check_rslt->execute();
							@count_row=$dnc_check_rslt->fetchrow_array;
							if ($count_row[0]>0) 
								{
								$upd_dnc_stmt="update vicidial_list set status='$dnc_scrub_status' where list_id='$scrub_list_id' and phone_number='$list_row[0]'";
								$upd_dnc_rslt=$dbhE->prepare($upd_dnc_stmt);
								$upd_dnc_rslt->execute();
								$upd_dnc_rslt->finish;
								}
							$dnc_check_rslt->finish;
							}
						$list_rslt->finish;
						}
					$check_table_rslt->finish;
					}
				}
			}
		else 
			{
			$scrub_rslt->finish;
			}
		$upd_stmt="update vicidial_settings_containers set container_entry='' where container_id='DNC_CURRENT_BLOCKED_LISTS'";
		$upd_rslt=$dbhB->prepare($upd_stmt);
		$upd_rslt->execute();
		$upd_rslt->finish;
	}

	$skip_ftp=0;
	if (!$ftp_host) 
		{
		if ($DB) {print "No FTP host given.\n";}
		$skip_ftp++;
		# exit;
		}
	if (!$ftp_user) 
		{
		if ($DB) {print "No FTP user given.\n";}
		$skip_ftp++;
		# exit;
		}
	if (!$ftp_pwd) 
		{
		if ($DB) {print "No FTP password given.\n";}
		$skip_ftp++;
		# exit;
		}
	if (!$ftp_port) 
		{
		$ftp_port=21;
		}
	if (!$ftp_passive) 
		{
		$ftp_passive=0;
		}
	if (!$file_dir) 
		{
		print "Script failed - no directory to look up files.\n";
		exit;
		}
	if (!-e $file_dir || (-e $file_dir && !-d $file_dir)) 
		{
		print "Script failed - file directory does not exist or is not a directory.\n";
		exit;
		}
	if (!$file_destination) 
		{
		print "Script failed - no destination to move completed files to.\n";
		exit;
		}
	if (!-e $file_destination || (-e $file_destination && !-d $file_destination)) 
		{
		print "Script failed - file destination directory does not exist or is not a directory.\n";
		exit;
		}
	if ($file_dir eq $file_destination) 
		{
		print "Script failed - please choose a destination for the completed files that is different from where they're already located.\n";
		exit;
		}

	$delete_stmt="select filename from vicidial_country_dnc_queue where file_status='CANCELLED'";
	if ($DBX) {print "$delete_stmt\n";}
	$delete_rslt=$dbhA->prepare($delete_stmt);
	$delete_rslt->execute();
	while (@delete_row=$delete_rslt->fetchrow_array) 
		{
		if (-e "$file_dir/$delete_row[0]") 
			{
			`rm $file_dir/$delete_row[0]`;
			}
		}
	$delete_rslt->finish();

	# Purge queue - current default 24 hours
	$delete_stmt="delete from vicidial_country_dnc_queue where file_status='CANCELLED' or (file_status='FINISHED' and date_processed<=now()-INTERVAL 1 WEEK)";
	if ($DBX) {print "$delete_stmt\n";}
	$delete_rslt=$dbhA->prepare($delete_stmt);
	$delete_rslt->execute();
	$delete_rslt->finish();

	# Get list of files already ready to be queued
	$stmt="select filename from vicidial_country_dnc_queue where file_status in ('READY', 'PENDING', 'PROCESSING', 'INVALID LAYOUT')";
	$rslt=$dbhA->prepare($stmt);
	$rslt->execute();
	@files_array=();
	while (@filename_row=$rslt->fetchrow_array) 
		{
		push(@files_array, "$filename_row[0]");
		}
	%active_files = map { $_ => 1 } @files_array;
	$rslt->finish();


	if (!$skip_ftp) 
		{
		# Get directory, insert and ignore
		use Net::Ping;
		use Net::FTP;

		$p = Net::Ping->new();
		$ping_good = $p->ping("$ftp_host");
		if (!$ping_good)
			{
			$p = Net::Ping->new("icmp");
			$ping_good = $p->ping("$ftp_host");
			}

		if ($ping_good)
			{	
			$ftp = Net::FTP->new("$ftp_host", Port => $ftp_port, Passive => $ftp_passive, Debug => $FTPdb);
			$ftp->login("$ftp_user","$ftp_pwd");
			if ($file_dir) {$ftp->cwd("$file_dir");}

			@FILES = $ftp->dir(".") or die "Can't get a list of files in $file_dir: $!";
			}
		else
			{
			if($DB){print "ERROR: Could not ping server $ftp_host\n";}
			exit;
			}
		$ftp->quit;	
		}
	else
		{
		opendir(FILE, "$file_dir/");
		@FILES = readdir(FILE);
		}

	$i=0;
	$filename_str="";
	
	foreach(@FILES)
		{
		$FILEsize1[$i] = 0;
		if($FILES[$i]=~/(csv|xlsx?|txt)$/i && (length($FILES[$i]) > 4) )
			{
			$FILEsize1[$i] = (-s "$file_dir/$FILES[$i]");
			if ($DBX) {print "$file_dir/$FILES[$i] $FILEsize1[$i]\n";}
			}
		$i++;
		}
	sleep(5);

	$i=0;
	foreach(@FILES)
		{
		$FILEsize2[$i] = 0;
		if($FILES[$i]=~/(csv|xlsx?|txt)$/i && (length($FILES[$i]) > 4) )
			{
			$FILEsize2[$i] = (-s "$file_dir/$FILES[$i]");
			if ($DBX) {print "$file_dir/$FILES[$i] $FILEsize2[$i]\n";}
			if ($FILEsize1[$i] ne $FILEsize2[$i]) 
				{
				# do nothing
				}
			elsif(!exists($active_files{$FILES[$i]}))
				{
				$ins_stmt="insert into vicidial_country_dnc_queue(filename, date_uploaded, file_status) VALUES('$FILES[$i]', now(), 'READY')";
				if ($DBX) {print "$ins_stmt\n";}
				$ins_rslt=$dbhA->prepare($ins_stmt);
				$ins_rslt->execute();
				$ins_rslt->finish;
				$filename_str.="'$FILES[$i]',";
				}
			}
		$i++;
		}
	
	if (length($filename_str)>0) 
		{
		# Clear records from queue table where the file is no longer found in the expected place
		$filename_str=~s/,$//;
		$delete_stmt="delete from vicidial_country_dnc_queue where file_status!='FINISHED' and filename not in ($filename_str)";
		if ($DBX) {print "$delete_stmt\n";}
		$delete_rslt=$dbhA->prepare($delete_stmt);
		$delete_rslt->execute();
		$delete_rslt->finish();
		}

	$dbhA->disconnect();
	$dbhB->disconnect();
	$dbhC->disconnect();
	$dbhD->disconnect();
	$dbhE->disconnect();
	}
else
	{
	$dbhA->disconnect();
	$dbhB->disconnect();
	$dbhC->disconnect();
	$dbhD->disconnect();
	$dbhE->disconnect();
	exit;
	}
#### end 1st script	
