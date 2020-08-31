#!/usr/bin/perl
#
# VICIDIAL_international_DNC_parser_2.pl
#
# This is the second part of a two-step program in the DNC importing process
#
# It requires that a system settings container exists in your system settings 
# named "INTERNATIONAL_DNC_IMPORT".  Two run-time variables must be defined 
# in said container - "--file-dir" and "--file-destination"
#
# It also requires a second settings container named "DNC_IMPORT_FORMATS", 
# which it will use to determine how to parse the data in the file.
#
# This should run every minute to check the DNC queue table for files 
# marked as PENDING, which it will then try to process based on the import
# format defined through the web interface.
#
# As the file is being processed, the web interface will reflect the progress
# of this script.  Only one file will be processed at any time.
# 
# Put an entry into the cron of of your asterisk machine to run this script 
# every minute or however often you desire
#
# ### International DNC importing scripts
##* * * * * /usr/share/astguiclient/VICIDIAL_international_DNC_parser_2.pl
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
	if ( ($line =~ /^VARDB_custom_user/) && ($CLIDB_custom_user < 1) )
		{$VARDB_custom_user = $line;   $VARDB_custom_user =~ s/.*=//gi;}
	if ( ($line =~ /^VARDB_custom_pass/) && ($CLIDB_custom_pass < 1) )
		{$VARDB_custom_pass = $line;   $VARDB_custom_pass =~ s/.*=//gi;}
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
$dbhC = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_custom_user", "$VARDB_custom_pass")
    or die "Couldn't connect to database: " . DBI->errstr;

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
			}
		}
	$rslt->finish();

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
	# Get file layouts
	$layout_stmt="select container_entry from vicidial_settings_containers where container_id='DNC_IMPORT_FORMATS'";
	if ($DBX) {print "$layout_stmt\n\n";}
	$layout_rslt=$dbhB->prepare($layout_stmt);
	$layout_rslt->execute();
	if ($layout_rslt->rows==0) {die "No import formats container found in settings containers";}
	@layout_row=$layout_rslt->fetchrow_array;
	@layout_array=split(/\n/, $layout_row[0]);
	%layouts=();
	for ($i=0; $i<scalar(@layout_array); $i++) 
		{
		$entry=$layout_array[$i];
		$entry=~s/^\s+//;
		if ($entry!~/^#/ && $entry=~/\=\>/) 
			{
			@current_layout=split(/\=\>/, $entry);
			$current_layout[0]=~s/^\s+|\s+$//;
			$current_layout[1]=~s/^\s+|\s+$//;
			if (length($current_layout[0])) 
				{
				$layouts{"$current_layout[0]"}=$current_layout[1];
				}
			}
		}
	$layout_rslt->finish;
	
	# select from database where file is marked as ready to queue and country code is set
	$process_stmt="select filename, file_layout, country_code, file_action, dnc_file_id from vicidial_country_dnc_queue where file_status='PENDING' and country_code is not null and file_layout is not null order by date_uploaded asc limit 1";
	if ($DBX) {print "$process_stmt\n\n";}
	$process_rslt=$dbhA->prepare($process_stmt);
	$process_rslt->execute();
	if ($process_rslt->rows==0) {exit;}

	while(@process_row=$process_rslt->fetchrow_array)
		{
		$filename=$process_row[0];
		$file_layout=$process_row[1];
		$country_code=$process_row[2];
		$file_action=$process_row[3];
		$dnc_file_id=$process_row[4];
		$process_count=0;
		$records_processed=0;
		$records_inserted=0;

		if (!exists $layouts{"$file_layout"})
			{
			$upd_stmt="update vicidial_country_dnc_queue set file_status='INVALID LAYOUT' where dnc_file_id='$dnc_file_id'";
			if ($DB) {print "Can't find file layout ($file_layout) - $upd_stmt\n\n";}
			$upd_rslt=$dbhB->prepare($upd_stmt);
			$upd_rslt->execute();
			$upd_rslt->finish;
			exit;
			}
		else
			{
			$create_stmt="CREATE TABLE IF NOT EXISTS vicidial_dnc_".$country_code."(phone_number varchar(20) primary key)";
			if ($DB) {print "$create_stmt\n\n";}
			$create_rslt=$dbhC->prepare($create_stmt);
			$create_rslt->execute();
			$create_rslt->finish;

			if ($file_action eq "PURGE") 
				{
				$delete_stmt="delete from vicidial_dnc_".$country_code;
				if ($DBX) {print "$delete_stmt\n\n";}
				$delete_rslt=$dbhB->prepare($delete_stmt);
				$delete_rslt->execute();
				$delete_rslt->finish;

				$opt_stmt="optimize table vicidial_dnc_".$country_code;
				if ($DBX) {print "$opt_stmt\n\n";}
				$opt_rslt=$dbhB->prepare($opt_stmt);
				$opt_rslt->execute();
				$opt_rslt->finish;
				}
			

			@layout_stats=split(/,/, $layouts{"$file_layout"});
			if ($DBX) {print $layouts{"$file_layout"}."\n";}
			$txt_format=$layout_stats[0];
			$delimiter=$layout_stats[1];
			if ($filename=~/csv$/i) 
				{
				$delimiter="comma";
				}

			$delimiter=~s/.*comma.*/,/gi;
			$delimiter=~s/.*pipe.*/\\\|/gi;
			$delimiter=~s/.*tab.*/\t/gi;

			if ($txt_format ne "" && $DB) {print "Format: $txt_format\n\n";}
			if ($delimiter ne "" && $DB) {print "Delimiter: $delimiter\n\n";}

			@phone_fields=();
			for($i=2; $i<scalar(@layout_stats); $i++)
				{
				push(@phone_fields, $layout_stats[$i]);
				}
			if (scalar(@phone_fields)==0 || ($txt_format=~/delimit/i && $delimiter eq "") || ($txt_format=~/fixed/i && $delimiter ne "")) 
				{
				$upd_stmt="update vicidial_country_dnc_queue set file_status='INVALID LAYOUT' where dnc_file_id='$dnc_file_id'";
				if ($DB) {print "No phone fields defined ($file_layout) - $upd_stmt\n\n";}
				$upd_rslt=$dbhB->prepare($upd_stmt);
				$upd_rslt->execute();
				$upd_rslt->finish;
				exit;
				}
			}

		if (!$skip_ftp) # File is NOT local, so attempt to download.
			{
			use Net::Ping;
			use Net::FTP;

			$ftp = Net::FTP->new("$ftp_host", Port => $ftp_port, Passive => $ftp_passive, Debug => $FTPdb);
			$ftp->login("$ftp_user","$ftp_pwd");
			if ($file_dir) {$ftp->cwd("$file_dir");}
			$ftp->get("$filename", "/tmp/$filename");
			$ftp->quit;
			$full_filename="/tmp/$filename";
			}
		else
			{
			$full_filename="$file_dir/$filename";
			}

		if ($filename=~/xlsx?$/) 
			{
			use Spreadsheet::Read;
			use Spreadsheet::XLSX;
			use File::Basename;
			use open ':std', ':encoding(UTF-8)';
			
			# Gotta account for the fact that spreadsheets don't start with 0 for indices
			for($k=0; $k<scalar(@phone_fields); $k++) {$phone_fields[$k]++;}

			$datafile=Spreadsheet::Read->new("$full_filename");
			$sheets=$datafile->[0]{sheets};
			for ($i=1; $i<=$sheets; $i++) 
				{
				$current_sheet=$datafile->[$i];
				$sheet_rows=$datafile->[$i]{maxrow};
				$sheet_cols=$datafile->[$i]{maxcol};

				$upd_stmt="update vicidial_country_dnc_queue set file_status='PROCESSING', total_records='$sheet_rows' where dnc_file_id='$dnc_file_id'";
				if ($DB) {print "$upd_stmt\n\n";}
				$upd_rslt=$dbhB->prepare($upd_stmt);
				$upd_rslt->execute();
				$upd_rslt->finish;

				for ($j=1; $j<=$sheet_rows; $j++) 
					{
					$records_processed++;
					$phone_number="";
					for($k=0; $k<scalar(@phone_fields); $k++)
						{
						$phone_number.=$datafile->[$i]{cell}[$phone_fields[$k]][$j];
						}
					InsertPhoneNumber($records_processed, $records_inserted, $phone_number, $dnc_file_id);
					}
				}				
			}
		else
			{
			$total_records_str=`wc -l $full_filename`;
			@tr_str=split(/\s/, $total_records_str);
			$total_records=$tr_str[0];

			$upd_stmt="update vicidial_country_dnc_queue set file_status='PROCESSING', total_records='$total_records' where dnc_file_id='$dnc_file_id'";
			if ($DB) {print "$upd_stmt\n\n";}
			$upd_rslt=$dbhB->prepare($upd_stmt);
			$upd_rslt->execute();
			$upd_rslt->finish;

			open(PHONE_FILE, "$full_filename");
			while($buffer=<PHONE_FILE>)
				{
				$records_processed++;
				$phone_number="";
				if ($delimiter ne "")
					{
					@data=split(/$delimiter/, $buffer);
					for($i=0; $i<scalar(@phone_fields); $i++)
						{
						$phone_number.=$data[$phone_fields[$i]];
						}
					}
				else
					{
					for($i=0; $i<scalar(@phone_fields); $i++)
						{
						@data=split(/\|/, $phone_fields[$i]);
						$phone_number.=substr($buffer, $data[0], $data[1]);
						}
					}
				if ($records_processed%1000==0) {print "*$phone_number*\n";}
				InsertPhoneNumber($records_processed, $records_inserted, $phone_number, $dnc_file_id);

				}
			}
		close(PHONE_FILE);
		$upd_stmt="update vicidial_country_dnc_queue set records_processed='$records_processed', records_inserted='$records_inserted', date_processed=now(), file_status='FINISHED' where dnc_file_id='$dnc_file_id'";
		if ($DBX) {print "$upd_stmt\n\n";}
		$upd_rslt=$dbhB->prepare($upd_stmt);
		$upd_rslt->execute();
		$upd_rslt->finish;

		if (!$skip_ftp) # File is NOT local, so attempt to download.
			{
			use Net::Ping;
			use Net::FTP;

			$ftp = Net::FTP->new("$ftp_host", Port => $ftp_port, Passive => $ftp_passive, Debug => $FTPdb);
			$ftp->login("$ftp_user","$ftp_pwd");
			if ($file_dir) {$ftp->cwd("$file_dir");}
#			$ftp->get("$filename", "/tmp/$filename");
#			$ftp->rename("$file_dir/$filename", "$file_destination/$filename");
#			$ftp->quit;
#			$full_filename="/tmp/$filename";
			}
		else
			{
			`mv $file_dir/$filename $file_destination/$filename`;
			}


		}
	$dbhA->disconnect();
	$dbhB->disconnect();
	$dbhC->disconnect();
	}
else
	{
	$dbhA->disconnect();
	$dbhB->disconnect();
	$dbhC->disconnect();
	exit;
	}


sub InsertPhoneNumber
	{
	my $rp=$_[0];
	my $ri=$_[1];
	my $phone=$_[2];
	my $dfid=$_[3];

	$phone=~s/[^0-9]//gi;

	if (length($phone)>=5 && length($phone)<=18) 
		{
		$ins_stmt="insert ignore into vicidial_dnc_".$country_code." values('$phone')";
		$ins_rslt=$dbhB->prepare($ins_stmt);
		$ins_rslt->execute();
		if ($ins_rslt->rows>0) {$records_inserted++; $ri++;}
		$ins_rslt->finish;
		}

	if ($rp%1000==0) 
		{
		$upd_stmt="update vicidial_country_dnc_queue set records_processed='$rp', records_inserted='$ri' where dnc_file_id='$dfid'";
		if ($DBX) {print "$ins_stmt\n$upd_stmt\n";}
		$upd_rslt=$dbhB->prepare($upd_stmt);
		$upd_rslt->execute();
		$upd_rslt->finish;
		}

	}
