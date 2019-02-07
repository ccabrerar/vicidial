#!/usr/bin/perl
#
# sheet2tab.pl - Convert spreadsheet to tab-delimited text file   version 2.12
#
# Copyright (C) 2016  Matt Florell & Michael Cargile <vicidial@gmail.com>    LICENSE: AGPLv2
#
# Lead file conversion and scrubbing script.  This is the first stage in the lead loading process.
#
# IMPORTANT - make sure the XLSX folder that was created in your Perl directory when you installed Spreadsheet::XLSX
# has executable privileges or the Spreadsheet::XLSX compile will fail and this script WILL NOT WORK.
# For some reason it is installed with read privileges only, unlike other Spreadsheet modules which have
# read and executable for all.
# 
# *Stage 1 - Convert file to a tab delimited format (DONE)
#  Stage 2 - Prompt the user for the field mapping (TBD)
#  Stage 3 - Schedule a List Load by the command line list loader (TBD)
#
# This particular script converts csv xls xlsx ods sxc files to a tab delimited file. In the
# process it scrubs out ' " ; ` \ characters which could cause problems with db insertion of
# lead data.  It also replaces pipes, tabs, carrage returns, and line feeds with spaces to prevent
# stage 3 from miscounting the number of fields on a line.
#
# ARG1 = File to Convert
# ARG2 = Name of the output file
#
# This file requires the Spreadsheet::Read and Spreadsheet::XLSX perl modules from CPAN
#
# cpan> install Spreadsheet::Read
# cpan> install Spreadsheet::XLSX
#
# CHANGES
# 100706-0833 - Initial build <mikec>
# 100706-1244 - Reformat and add comments
# 110927-1750 - Fixed issue with improperly CSV files locking up servers <mikec>
# 130619-2310 - Fixed missing XLSX perl module declaration
# 150312-1547 - Allow for single quotes in vicidial_list data fields
# 160102-0958 - Fix for special characters, issue #792
#

# disable when not debugging
#use strict;
#use warnings;

use Spreadsheet::Read;
use Spreadsheet::XLSX;
use File::Basename;
use open ':std', ':encoding(UTF-8)';


sub scrub_lead_field 
	{
	my $lead_field = $_[0];

	# remove bad characters
	$lead_field	=~ s/\\|\"|;|\`|\224//gi;
	
	# replace tabs and newlines with spaces
	$lead_field	=~ s/\n|\r|\t|\174/ /gi;

	return $lead_field;
	}

my $infile;
my $outfile;

my $error_log = "sheet2tab_error.txt";

my $csv_chuck_size = 500;

my $crap_loop_time = 5;

if ( $#ARGV == 1 ) 
	{
	$infile = $ARGV[0]; 
	$outfile = $ARGV[1];
	} 
else
	{
	print STDERR "Incorrect number of arguments\n";
	exit(1);
	}

open( OUTFILE , ">$outfile" ) or die $!;

my $debug = 0;

my $out_delim = "\t";

my $count = 0;

my $colPos = 0;
my $rowPos = 0;

my $time = time();

my $tempfile = "sheet2tab_temp_file_$time.csv";

# file types we are looking for
my @exts = qw(.csv);

# dont screw up the actual infile variable
my $exten_file = $infile;
chomp $exten_file;

# break the file up into its parts
my ($dir, $name, $ext) = fileparse($exten_file, @exts);

# if we are a csv file

if ($ext eq '.csv') 
	{
	open( IN, $infile ) or die "can't open $infile: $!\n";
	open( TMPFILE , ">$tempfile" ) or die $!;

	my $cur_loop_time = time();
	my $old_loop_time = time();
	my $loop_time = 0;
	my $loop_sleep = 0;

	my $loop_count = 0;

	# loop through the file and process it in chunks
	while( <IN> )
		{
		$loop_count++;

		print TMPFILE $_;
		if ($debug) { print STDERR "csv line = '$_'\n"; };

		# break
		if ( $loop_count % $csv_chuck_size == 0 )
			{

			# close the temp file
			close( TMPFILE );

			# process the temp file
			my $parser = ReadData ( "$tempfile" );

			my $maxCol = $parser->[1]{maxcol};
			my $maxRow = $parser->[1]{maxrow};

			# Something is not right if they have 0 or over 100 columns in their lead file.
			if (( $maxCol >= 100 ) || ( $maxCol == 0 ))
				{
				print STDERR "ERROR: Improperly formatted lead file.\n";
				print OUTFILE "BAD_LEAD_FILE\tBAD_LEAD_FILE\tBAD_LEAD_FILE\tBAD_LEAD_FILE\tBAD_LEAD_FILE\tBAD_LEAD_FILE\tBAD_LEAD_FILE\t\n";
				exit;
				}

			if ($debug) { print STDERR "maxCol = '$maxCol'\n"; };
			if ($debug) { print STDERR "maxRow = '$maxRow'\n"; };

			# loop through the rows
			for ( $rowPos = 1; $rowPos <= $maxRow; $rowPos++  )
				{
				# loop through the cols
				for ( $colPos = 1; $colPos <= $maxCol; $colPos++  )
					{
					my $cell = cr2cell( $colPos, $rowPos );

					if ($debug) { print STDERR "cell = '$cell'\n"; };

					my $field;

					# make sure the field has a value
					if ( $parser->[1]{$cell} )
						{
						$field = $parser->[1]{$cell};
						}
					else
						{
						$field = "";
						}

					if ($debug) { print STDERR "field = '$field'\n"; };

					$field = scrub_lead_field( $field );

					print OUTFILE $field;

					if ( $colPos < $maxCol )
						{
						print OUTFILE $out_delim;
						}
					else
						{
						print OUTFILE "\n";
						}
					}
				}


			# delete the TMPFILE
			unlink( $tempfile ) or die $!;

			# repopn the TMPFILE
			open( TMPFILE , ">$tempfile" ) or die $!;
				

			# figure out how long it took to loop
			$old_loop_time = $cur_loop_time;
			$cur_loop_time = time();

			$loop_time = $cur_loop_time - $old_loop_time;

			#print STDERR "loop_count = '$loop_count' $loop_time $loop_sleep\n";

			if ( $loop_time > $crap_loop_time ) 
				{
				# this should not take this long to run through a loop.
				# sleep for a bit to let the CPU recover.
				$loop_sleep = $loop_sleep + $loop_time;

				# they have waited 60 seconds lets just kill this and get it over with
				if ( $loop_sleep > 60 ) 
					{
					close( TMPFILE );
					close( IN );

					# delete the TMPFILE
					unlink( $tempfile ) or die $!;

					open( ERRFILE, ">>$error_log" );

					print ERRFILE "$cur_loop_time: Sheet2tab.pl aborting. Penalized them long enough for their junk leads in $infile \n\n";

					close( ERRFILE );

					exit;
					}

				sleep($loop_sleep);

				open( ERRFILE, ">>$error_log" );

				print ERRFILE "$cur_loop_time: Sheet2tab.pl took $loop_time to process $csv_chuck_size leads from the $infile lead file. Making them sleep $loop_sleep so we can recover.\n\n";

				close( ERRFILE );

				# do not penalize the next loop through because we forced them to sleep
				$cur_loop_time = time();
				}
			}
		}
		
	# close the temp and In files
	close( TMPFILE );
	close( IN );

	# see if we have any left overs
	my $temp_file_size = -s $tempfile;

	# if not exit
	if ( $temp_file_size == 0 ) 
		{
		# delete the TMPFILE
		unlink( $tempfile ) or die $!;
		exit;
		}

	# other wise parse the last temp file
	my $parser = ReadData ( "$tempfile" );

	my $maxCol = $parser->[1]{maxcol};
	my $maxRow = $parser->[1]{maxrow};

	if (( $maxCol >= 100 ) || ( $maxCol == 0 ))
		{
		print STDERR "ERROR: Improperly formatted lead file.\n";
		print OUTFILE "BAD_LEAD_FILE\tBAD_LEAD_FILE\tBAD_LEAD_FILE\tBAD_LEAD_FILE\tBAD_LEAD_FILE\tBAD_LEAD_FILE\tBAD_LEAD_FILE\t\n";
		exit;
		}

	if ($debug) { print STDERR "maxCol = '$maxCol'\n"; };
	if ($debug) { print STDERR "maxRow = '$maxRow'\n"; };

	# loop through the rows
	for ( $rowPos = 1; $rowPos <= $maxRow; $rowPos++  )
		{
		# loop through the cols
		for ( $colPos = 1; $colPos <= $maxCol; $colPos++  )
			{
			my $cell = cr2cell( $colPos, $rowPos );

			if ($debug) { print STDERR "cell = '$cell'\n"; };

			my $field;

			# make sure the field has a value
			if ( $parser->[1]{$cell} )
				{
				$field = $parser->[1]{$cell};
				}
			else
				{
				$field = "";
				}

			if ($debug) { print STDERR "field = '$field'\n"; };

			$field = scrub_lead_field( $field );

			print OUTFILE $field;

			if ( $colPos < $maxCol )
				{
				print OUTFILE $out_delim;
				}
			else
				{
				print OUTFILE "\n";
				}
			}
		}

	# delete the TMPFILE
	unlink( $tempfile ) or die $!;

	}
else
	# not a CSV file
	{
	# parse the file
	my $parser = ReadData ( "$infile" );
	
	my $maxCol = $parser->[1]{maxcol};
	my $maxRow = $parser->[1]{maxrow};


	if ($debug) { print STDERR "maxCol = '$maxCol'\n"; };
	if ($debug) { print STDERR "maxRow = '$maxRow'\n"; };

	# loop through the rows
	for ( $rowPos = 1; $rowPos <= $maxRow; $rowPos++  ) 
		{
		# loop through the cols
		for ( $colPos = 1; $colPos <= $maxCol; $colPos++  ) 
			{
			my $cell = cr2cell( $colPos, $rowPos );
	
			if ($debug) { print STDERR "cell = '$cell'\n"; };

			my $field;

			# make sure the field has a value
			if ( $parser->[1]{$cell} ) 
				{
				$field = $parser->[1]{$cell};
				} 
			else 
				{
				$field = "";
				}

			if ($debug) { print STDERR "field = '$field'\n"; };

			$field = scrub_lead_field( $field );

			print OUTFILE $field;

			if ( $colPos < $maxCol ) 
				{
				print OUTFILE $out_delim;
				} 
			else 
				{
				print OUTFILE "\n";
				}
			}
		}
	}
exit;
