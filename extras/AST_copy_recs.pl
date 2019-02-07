#!/usr/bin/perl
#
# AST_move_recs.pl version 2.10
#
# DESCRIPTION:
# Copy recordings from /var/spool/asterisk/monitorDONE/ to an external source.
# The files are put into a dated directory. If the process gets interupted,
# the last recording id transferred will be noted in a file. That recording
# id can then be passed as an argument and the script will pick up from there.
#
# Copyright (C) 2015  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#
# 150121-1624 - Initial Build <mikec>
#


$PATHconf =	     '/etc/astguiclient.conf';

open(conf, "$PATHconf") || die "can't open $PATHconf: $!\n";
@conf = <conf>;
close(conf);
$i=0;
foreach(@conf)
	{
	$line = $conf[$i];
	$line =~ s/ |>|\n|\r|\t|\#.*|;.*//gi;
	if ( ($line =~ /^VARserver_ip/) && ($CLIserver_ip < 1) )
		{$VAR_server_ip = $line;   $VAR_server_ip =~ s/.*=//gi;}
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

############################################

$DB=0;
$recording_id=0;

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
		print "allowed run time options(must stay in this order):\n";
		print "  [--debug] = debug\n";
		print "  [--copy-folder=XXX] = directory to copy recordings into\n";
		print "  [--start-recording-id=XXX] = recording_id to start the processing at\n";
		print "\n";
		exit;
		}
	else
		{
		if ($args =~ /--debug/i) # Debug flag
			{
			$DB=1;
			}
		if ($args =~ /--copy-folder=/i)
			{
			@copy_folder = split(/--copy-folder=/,$args);
			@copy_folder = split(/ /,$copy_folder[1]);
			if (length($copy_folder[0]) > 2)
				{
				$folder = $copy_folder[0];
				$folder =~ s/\/$| |\r|\n|\t//gi;
				}
			}
		if ($args =~ /--start-recording-id=/i)
			{
			@start_recording_id = split(/--start-recording-id=/,$args);
			@start_recording_id = split(/ /,$start_recording_id[1]);
			if (length($start_recording_id[0])>1)
				{
				$recording_id = $start_recording_id[0];
				$recording_id =~ s/\/$| |\r|\n|\t//gi;
				}
			}
		}
	}
else
	{
	print "no command line options set\n";
	}

## end parsing run-time options ###

if ($DB) { print "$folder\n" }

use DBI;
use File::Copy;

$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
	or die "Couldn't connect to database: " . DBI->errstr;

$rec_id_file = "/root/AST-copy-rec-id";

$loop = 1;
while ( $loop ) 
	{
	$stmtA = "select recording_id, location, start_time from recording_log where recording_id > $recording_id and server_ip = '$VAR_server_ip' and location IS NOT NULL order by recording_id limit 1000";
	if ($DB) { print "$stmtA\n"; }

	$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
	$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
	$sthArows=$sthA->rows;

	if ( $sthArows == 0 ) 
		{
		$loop = 0;
		break;
		}

	$num_rows=0;

	while ($num_rows < $sthArows)
		{
		@aryA = $sthA->fetchrow_array;

		$rec_id = $aryA[0];
		$location = $aryA[1];
		$start_time = $aryA[2];

		@path = split('/',$location);
		$filename = $path[-1];

		@file_parts = split('\.',$filename);
		$file_type = $file_parts[-1];

		if ( $file_type eq 'mp3' ) 
			{
			@date = split(' ',$start_time);
			$date = @date[0];

			$sub_folder = "$folder/$date";

			if ($DB) { print "|$rec_id|$date|$filename|$sub_folder|\n"; }

			if (!( -d $sub_folder )) 
				{
				if ($DB) { print "making subdirectory $sub_folder\n"; }
				mkdir( $sub_folder );
				}

			$source_file_path = "/var/spool/asterisk/monitorDONE/MP3/$filename";
			$dest_file_path = "$sub_folder/$filename";

			if ($DB) { print "copying file $filename\n"; }
			copy($source_file_path,$dest_file_path) or die "Copy failed: $!";

			}
		else 
			{
			if ($DB) { print "$filename| $file_type is not an mp3 skipping\n"; }
			}

		open(my $fh, '>', $rec_id_file) or die "Could not open file '$rec_id_file' $!";
		print $fh "$rec_id";
		close($fh);

		$recording_id = $rec_id;

		$num_rows++;
		}
	$sthA->finish();
	}

exit;
