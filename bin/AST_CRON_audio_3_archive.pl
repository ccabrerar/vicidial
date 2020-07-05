#!/usr/bin/perl
#
# AST_CRON_audio_3_archive.pl
#
# Step 3 program to create a local archive in /home/archive
# This was needed since without it everything sits in /var/spool/asterisk/monitorDONE with no sorting
#
# Copyright (C) 2020  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
# Written by James Pearson <jamesp@vicidial.com>
#
#
# changes:
# 200702-2318 - First Build
#

# Global vars/flags/etc
$DB = 0;
$DBX = 0;
$VERBOSE = 1;
$TEST = 0;
$PATHconf = '/etc/astguiclient.conf';
$PATHarchive = '/home/archive/RECORDINGS';
$PATHmonitor = '/var/spool/asterisk/monitorDONE';
$PATHlogs = '/var/log/astguiclient';
$URLArchive = '/archive/RECORDINGS';
$ServerIP = '127.0.0.1';
$DoMove = 0;
$DeleteAfter = 0;
$CompareSizes = 1;
$FileLimit = 10000;
$MP3 = 1; # Default to MP3 cause reasons
$OGG = 0;
$GSM = 0;
$WAV = 0;
$FTPmove = 1; # Copy processed files to FTP directory so other scripts can do things too
my $DBhost = 'localhost';
my $DBname = 'asterisk';
my $DBuser = 'cron';
my $DBpass = '1234';
my $DBport = '3306';
my $CLIDB_host = 0;
my $CLIDB_name = 0;
my $CLIDB_user = 0;
my $CLIDB_pass = 0;
my $CLIDB_port = 0;
my $CLIPATHmonitor = 0;
my $CLIPATHarchive = 0;
my $CLIURLArchive = 0;
my $CLIServerIP = 0;
my $CLIPATHconf = 0;

# Load our modules
use warnings;
use DBI;
use File::Basename;
use Scalar::Util qw(looks_like_number);
use File::Copy qw(move copy);
$| = 1; # flush buffer after each print so things dont look like they've hung

# Subs
sub trim {
	# Perl trim function to remove whitespace from the start and end of the string, stolen from google cause i'm lazy
	my $string = shift;
	$string =~ s/^\s+//;
	$string =~ s/\s+$//;
	return $string;
}
sub getviciarg {
	my $return='0'; # Default to boolean false on return
	my $value=''; # return value
	my $arg = shift; # Get variable to look for
	my $argtype='flag'; # By default just check if the argument exists and return boolean true
	if ( @_ ) { if ( lc($_[0]) eq 'value' ) { $argtype = 'value'; } } # If we are returning a value, set that option here
	
	if ( @ARGV && length($ARGV[0])>1 ) {
		foreach my $a(@ARGV) {
			if ( $a =~ /$arg/i ) {
				my @CLIarg = split(/$arg=/,$a);
				if ( $CLIarg[1] ) {
					$value=$CLIarg[1];
					$value =~ s/\/$| |\r|\n|\t//gi; # Remove whitespace and special sauce
					$return=2; # Has a value set
				} else {
					$return=1; # Only a flag, no value set
				}
			}
		}
	}
	
	if ( $argtype eq 'value' ) {
		return $value; # If value requested, return the value of the variable
	} else {
		return $return; # returns 0 for boolean false, 1 = flag, 2 = has value
	}
}

# Gives debug output
sub debugoutput {
	my $debugline = shift;
	my $debugdie = 0;
	if ( @_ ) { # If we have a second parameter, it's the debugdie option so grab it
		$debugdie = shift;
		if (! looks_like_number($debugdie)) { $debugdie = 0; } # 0 = don't exit, 1 = exit, everything else = garbage
	}
	# Do the actual output and functionality
	if ($DB==1 and $debugdie==0) {
		print "$debugline\n";
		} elsif ($DB==1 and $debugdie==1) {
			die("$debugline\n"); # Evidently it was a critical error, so we die on output
	}
}
sub debugxoutput { if ($DBX==1) { debugoutput(@_); }} # Same as above, but for DebugX

sub verboseoutput { # Gives verbose output if it's on
	my $verboseline = shift;
	my $newLine = 1; # Add a newline by default
	if ( @_ ) { # If we have a second parameter, it's the newline option
		$newLine = shift;
	}
	if ($VERBOSE == 1) {
		if ($newLine == 1) { print "$verboseline\n"; } else { print "$verboseline"; }
	}
}

sub loadconfig { # Parse the config file if it's preasent, skipping options from above
	debugxoutput("--- loadconfig START");
	my $configFile = shift;
	if ( -e $configFile ) {
		open(CONF, "$configFile");
		@conf = <CONF>;
		close(CONF);
		$i = 0;
		foreach(@conf) {
			$line = $conf[$i];
			$line =~ s/ |>|\n|\r|\t|\#.*|;.*//gi;
			if ( ($line =~ /^VARDB_server/) && ($CLIDB_host < 1) )
				{ $DBhost = $line;   $DBhost =~ s/.*=//gi; }
			if ( ($line =~ /^VARDB_database/) && ($CLIDB_name < 1) )
				{ $DBname = $line;   $DBname =~ s/.*=//gi; }
			if ( ($line =~ /^VARDB_user/) && ($CLIDB_user < 1) )
				{ $DBuser = $line;   $DBuser =~ s/.*=//gi; }
			if ( ($line =~ /^VARDB_pass/) && ($CLIDB_pass < 1) )
				{ $DBpass = $line;   $DBpass =~ s/.*=//gi; }
			if ( ($line =~ /^VARDB_port/) && ($CLIDB_port < 1) )
				{ $DBport = $line;   $DBport =~ s/.*=//gi; }
			if ( ($line =~ /^PATHDONEmonitor/) && ($CLIPATHmonitor < 1) )
				{ $PATHmonitor = $line;   $PATHmonitor =~ s/.*=//gi; }
			if ( ($line =~ /^PATHlogs/) )
				{ $PATHlogs = $line;   $PATHlogs =~ s/.*=//gi; }
			if ( ($line =~ /^VARserver_ip/) )
				{ $ServerIP = $line;   $ServerIP =~ s/.*=//gi; }
			$i++;
		}
	} else { print "Config file not found at: $configFile\n";}
	debugxoutput("--- loadconfig STOP");
}

sub findRecordingID { # Find the recording_id for the file and return it
	debugxoutput("--- findRecordingID START");
	my $filename = shift;
	my $recordingID = '-1';
	my $startTime = '-1';
	my $sth = $dbhVD->prepare('SELECT recording_id, date(start_time) FROM recording_log WHERE filename = ?') or die "Recording ID prepare failed: $dbh->errstr()";
	$sth->execute($filename) or die "Recording ID execution failed: $dbh->errstr()";
	my $sthRows = $sth->rows;
	if ( $sthRows >= 1 ) {
		my @row = $sth->fetchrow_array;
		$recordingID = $row[0];
		$startTime = $row[1];
	}
	return ($recordingID, $startTime);
	debugxoutput("--- findRecordingID STOP");
}

sub updateRecordingID {
	debugxoutput("--- updateRecordingID START");
	my $recordingID=shift;
	my $recordingURL=shift;
	my $sql = 'update recording_log set location=? where recording_id=?;';
	$dbhVD->do($sql, undef, $recordingURL, $recordingID);
	return 0;
	debugxoutput("--- updateRecordingID STOP");
}


# Parse CLI args
$args = "";
if (length($ARGV[0])>1) {
	$i=0;
	while ($#ARGV >= $i) {
		$args = "$args $ARGV[$i]";
		$i++;
	}
	if ($args =~ /--help/i) {
		loadconfig($PATHconf);
		print "\n\nViciDial Local Archive\n\n";
		print "allowed run time options:\n";
		print "  [--debug] = debug\n";
		print "  [--debugX] = debug extended\n";
		print "  [--quiet] = Be quiet, default is to be verbose\n";
		print "  [--domove] = Move files instead of copying to archive\n";
		print "  [--deleteafter] = Delete file after copying\n";
		print "  [--dontcompare] = Don't compare file size after copy/move\n";
		print "  [--mp3] = Process MP3 recordings, DEFAULT\n";
		print "  [--ogg] = Process OGG recordings\n";
		print "  [--wav] = Process WAV recordings\n";
		print "  [--gsm] = Process GSM recordings\n";
		print "  [--filelimit=$FileLimit] = Max number of files to process\n";
		print "  [--noftpmove] = Do not copy files to FTP directory\n";
		print "  [--astconffile=$PATHconf] = ViciDial configuration file\n";
		print "  [--monitordir=$PATHmonitor] = Directory of ViciDial recordings\n";
		print "  [--archivedir=$PATHarchive] = Directory for the local archive\n";
		print "  [--archiveurl=$URLArchive] = URL to add to end of local server IP\n";
		print "  [--archivehost=$ServerIP] = Host/IP to use in URL creation\n";
		print "  [--dbhost=$DBhost] = Database Host\n";
		print "  [--dbname=$DBname] = Database Name\n";
		print "  [--dbuser=$DBuser] = Database User\n";
		print "  [--dbpass=$DBpass] = Database Pass\n";
		print "  [--dbport=$DBport] = Database Port\n";
		exit;
	} else {
		# Process arguments
		if (getviciarg('--debugX')) {
			$DBX = 1;
			$DB = 1;
			$VERBOSE = 1;
			print "\n\nViciDial Local Archive\n\n";
			print "\n----- DEBUG Extended -----\n\n";
		}
		if ($DB == 0 && getviciarg('--debug')) {
			$DB=1;
			$VERBOSE = 1;
			print "\n\nViciDial Local Archive\n\n";
			print "\n----- DEBUG Normal -----\n\n";
		}
		if (getviciarg('--domove')) {
			$DoMove = 1;
			debugoutput(" CLI Transfer Method  :      Move");
		}
		if ($VERBOSE == 1 && $DB == 0 && getviciarg('--quiet')) {
			$VERBOSE = 0;
			debugoutput(" CLI Verbose          :      Yes");
		}
		if ($DoMove == 0 && getviciarg('--deleteafter')) {
			$DeleteAfter = 1;
			debugoutput(" CLI Delete After     :      Yes");
		}
		if (getviciarg('--dontcompare')) {
			$CompareSizes = 0;
			debugoutput(" CLI Compare Sizes    :      No");
		}
		if (getviciarg('--noftpmove')) {
			$FTPmove = 0;
			debugoutput(" CLI Move to FTP dir  :      No");
		}
		if (getviciarg('--astconffile') == 2 ) {
			$PATHconf=getviciarg('--astconffile','value');
			$CLIPATHconf=1;
			debugoutput(" CLI Vici conf file   :      $PATHconf");
		}
		if (getviciarg('--monitordir') == 2 ) {
			$PATHmonitor=getviciarg('--monitordir','value');
			$CLIPATHmonitor=1;
			debugoutput(" CLI monitor dir      :      $PATHmonitor");
		}
		if (getviciarg('--archivedir') == 2 ) {
			$PATHarchive=getviciarg('--archivedir','value');
			$CLIPATHarchive=1;
			debugoutput(" CLI archive dir      :      $PATHarchive");
		}
		if (getviciarg('--archiveurl') == 2 ) {
			$URLArchive=getviciarg('--archiveurl','value');
			$CLIURLArchive=1;
			debugoutput(" CLI archive dir      :      $PATHarchive");
		}
		if (getviciarg('--archivehost') == 2 ) {
			$ServerIP=getviciarg('--archivehost','value');
			$CLIServerIP=1;
			debugoutput(" CLI archive dir      :      $PATHarchive");
		}
		if (getviciarg('--filelimit') == 2 ) {
			$FileLimit=getviciarg('--filelimit','value');
			$CLIPATHarchive=1;
			debugoutput(" CLI file limit       :      $FileLimit");
		}
		# Only one file type is supported per run, so they just clobber each other below
		# Also, MP3 is default so no need to check for it
		if (getviciarg('--wav')) {
			$WAV=1;
			$MP3=0;
			$GSM=0;
			$OGG=0;
		}
		if (getviciarg('--ogg')) {
			$WAV=0;
			$MP3=0;
			$GSM=0;
			$OGG=1;
		}
		if (getviciarg('--gsm')) {
			$WAV=0;
			$MP3=0;
			$GSM=1;
			$OGG=0;
		}

		# Database stuff
		if (getviciarg('--dbhost') == 2 ) {
			$DBhost=getviciarg('--dbhost','value');
			$CLIDB_host=1;
			debugoutput(" CLI Database Host    :      $DBhost");
		}
		if (getviciarg('--dbname') == 2 ) {
			$DBname=getviciarg('--dbname','value');
			$CLIDB_name=1;
			debugoutput(" CLI Database Name    :      $DBname");
		}
		if (getviciarg('--dbuser') == 2 ) {
			$DBuser=getviciarg('--dbuser','value');
			$CLIDB_user=1;
			debugoutput(" CLI Database User    :      $DBuser");
		}
		if (getviciarg('--dbpass') == 2 ) {
			$DBpass=getviciarg('--dbpass','value');
			$CLIDB_pass=1;
			debugoutput(" CLI Database Password:      $DBpass");
		}
		if (getviciarg('--dbport') == 2 ) {
			my $DBPORTtmp=getviciarg('--dbport','value');
			if (looks_like_number($DBPORTtmp)) {
				if ($DBPORTtmp < 65535 && $DBPORTtmp > 1024) {
					$DBport = $DBPORTtmp;
					$CLIDB_port = 1;
					debugoutput(" CLI Database Port    :      $DBport");
				}
			}
		}
	}
}
loadconfig($PATHconf);

# Give runtime output on debug mode
if ($DB==1) {
	if ($MP3 == 1) { debugoutput(" MP3 processing       :      Yes"); } else { debugoutput(" MP3 processing       :      No"); }
	if ($WAV == 1) { debugoutput(" WAV processing       :      Yes"); } else { debugoutput(" WAV processing       :      No"); }
	if ($OGG == 1) { debugoutput(" OGG processing       :      Yes"); } else { debugoutput(" OGG processing       :      No"); }
	if ($GSM == 1) { debugoutput(" GSM processing       :      Yes"); } else { debugoutput(" GSM processing       :      No"); }
	if ($CLIPATHconf == 0) { debugoutput(" ViciDIal Conf file   :      $PATHconf"); }
	if ($CLIPATHmonitor == 0) { debugoutput(" Monitor Dir          :      $PATHmonitor"); }
	if ($CLIPATHarchive == 0) { debugoutput(" Archive Dir          :      $PATHarchive"); }
	if ($CLIURLArchive == 0) { debugoutput(" Archive URL          :      $URLArchive"); }
	if ($CLIServerIP == 0) { debugoutput(" Archive Host         :      $ServerIP"); }
	if ($CLIDB_host == 0) { debugoutput(" Database Host        :      $DBhost"); }
	if ($CLIDB_name == 0) { debugoutput(" Database Name        :      $DBname"); }
	if ($CLIDB_user == 0) { debugoutput(" Database User        :      $DBuser"); }
	if ($CLIDB_pass == 0) { debugoutput(" Database Pass        :      $DBpass"); }
	if ($CLIDB_port == 0) { debugoutput(" Database Port        :      $DBport"); }
	if ($DoMove == 0) {
		debugoutput(" Transfer Method      :      Copy");
		if ($DeleteAfter == 0) { debugoutput(" Delete After         :      No"); }
		if ($FTPmove == 1) { debugoutput(" Move to FTP dir      :      Yes"); }
	}
	if ($CompareSizes == 1) { debugoutput(" Compare File Sizes  :      No"); }
}
verboseoutput("ViciDial Local Archive");

# concurrency checking so we don't loop ourselves
$RUNNING_FILE=basename($0);
my $grepout = `/bin/ps ax | grep $RUNNING_FILE | grep -v grep | grep -v /bin/sh`;
my $grepnum=0;
$grepnum++ while ($grepout =~ m/\n/g);
if ($grepnum > 1) { die("\nI am not alone! Another $0 is running! Exiting...\n"); }

# Write to a log file if not in verbose or debug mode, really only captures glaring errors
if ($DB<1 && $VERBOSE<1) {
	use IO::Handle;
	open OUTPUT, '>>', "$PATHlogs/audio_3_archive.log" or die $!;
	open ERROR, '>>', "$PATHlogs/audio_3_archive.log" or die $!;
	STDOUT->fdopen( \*OUTPUT, 'w' ) or die $!;
	STDERR->fdopen( \*ERROR,  'w' ) or die $!;
}

# Connect to vicidial database
$dbhVD = DBI->connect("DBI:mysql:$DBname:$DBhost:$DBport", "$DBuser", "$DBpass") or die "Couldn't connect to database: " . DBI->errstr;

# Make sure the archive directory exists
if ( ! -d $PATHarchive ) { die "Archive directory not found at $PATHarchive\n"; }


# get and parse directory listing
$dir2 = "$PATHmonitor";
$FTPdir = "$PATHmonitor/FTP";
if ($MP3 > 0) { $dir2 = "$PATHmonitor/MP3"; }
if ($GSM > 0) { $dir2 = "$PATHmonitor/GSM"; }
if ($OGG > 0) { $dir2 = "$PATHmonitor/OGG"; }
verboseoutput("Processing $dir2...",0);
$CTfile = 0;
#@files;
opendir(DIR, "$dir2") or die "Can't open $dir2: $!";

# Generate initial file list and store in array
while ( defined( my $file = readdir DIR ) && $CTfile < $FileLimit) {
	next if ($file =~ m/^\./); # Don't bother with files beginning with a .
	$files[$CTfile][0] = $file;
	$files[$CTfile][1] = -s "$dir2/$file"; # Store filesize so we can check it later for writes
	$CTfile++;
}
closedir(DIR);
verboseoutput("  found $CTfile files.");
sleep 3; # Wait so we can check for writes later

# Process the file list
$fileCount = 0;
while ( $fileCount < $CTfile ) {
	debugoutput("Processing file $fileCount: $dir2/$files[$fileCount][0]");
	my $file = $files[$fileCount][0];
	my $filePreSize = $files[$fileCount][1]; # Size of file when directory was listed
	my $fileSize = -s "$dir2/$file"; # Size of file as it exists now
	
	# Split file into it's parts so it can be looked up in the database
	my ($filename, $path, $suffix) = fileparse("$dir2/$file", '\.[^\.]*');
	my $basename = $filename;
	$basename =~ s/-all//;

	# Do DB lookup and check if file has been written to
	my ($recordingID, $recordingDate) = findRecordingID($basename);
	if ( $recordingID == '-1' ) {
		verboseoutput("  File $file not found in database");
		$fileCount++;
		next; # Force loop
	} elsif ($filePreSize == $fileSize) { # File sizes match, so it's not been written to
		verboseoutput("  File $fileCount: $file, Size: $filePreSize, ID: $recordingID... ",0);
	} else {
		verboseoutput("  File $fileCount: $file has changed size from $filePreSize to $fileSize, skipping.");
		$fileCount++;
		next; # Force loop
	}

	# Setup for move/copy
	if ( ! -d "$PATHarchive/$recordingDate" ) { mkdir "$PATHarchive/$recordingDate"; }
	# Make sure we have the source file
	if ( -f "$dir2/$file") {
		# Do the move/copy
		if ( $DoMove == 1 ) { 
			move("$dir2/$file", "$PATHarchive/$recordingDate/$file");
			verboseoutput("moved...",0);
		} else {
			copy("$dir2/$file", "$PATHarchive/$recordingDate/$file");
			verboseoutput("copied...",0);
			# If the FTP directory exists, move the file there
			if ($FTPmove == 1 && -d $FTPdir) { move("$dir2/$file", "$FTPdir/$file"); } else {
				debugoutput("  FTP directory doesn't exist at $FTPdir!?");
			}
		}

		# Consistency check, if file not found  or size doesn't match then error out, otherwise process delete after if flagged
		$fileSize = -s "$PATHarchive/$recordingDate/$file";
		if ( ! -f "$PATHarchive/$recordingDate/$file" ) {
			die(" ERROR! File $file was not found at $PATHarchive/$recordingDate as expected! Drive full?\n");
		} elsif ( $CompareSizes == 1 && $filePreSize != $fileSize ) {
			die("File $file was not the same size after copying/moving! Drive full?\n");
		} elsif ( $DoMove == 0 && $DeleteAfter==1 ) { 
			unlink("$dir2/$file");
		}
	} else {
		verboseoutput("File $dir2/$file not found! skipping...");
		$fileCount++;
		next; # Force loop
	}

	verboseoutput(" updating DB... ",0);
	my $newURL = "http://$ServerIP/$URLArchive/$recordingDate/$file";
	updateRecordingID($recordingID, $newURL);
	verboseoutput("done.");
	$fileCount++;
}

