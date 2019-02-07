#!/usr/bin/perl

# QXZanalyzer.pl version 2.10
#
# Copyright (C) 2014  Matt Florell <vicidial@gmail.com>    LICENSE: AGPLv2
#

# CHANGES
# 141117-1734 - First build
# 141118-1326 - Added more counts and fixed parsing issues
#

############################################
# QXZanalyzer.pl - parses a PHP file to find all instances of QXZ function
# inside of it and further analysis
#

$secX = time();

# constants
$DB=0;  # Debug flag, set to 0 for no debug messages, lots of output
$US='_';
$MT[0]='';
$QXZlines=0;
$QXZvalues=0;
$QXZvaronly=0;
$QXZlengthonly=0;
$QXZplaceonly=0;
$PATHconf='';

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
		print "QXZanalyzer.pl - analyze php scripts for QXZ functions and contents\n";
		print "\n";
		print "options:\n";
		print "  [--help] = this help screen\n";
		print "  [--debug] = verbose debug messages\n";
		print "  [--QXZlines] = print full lines that include QXZ functions\n";
		print "  [--QXZvalues] = print only QXZ function values\n";
		print "  [--QXZvaronly] = print only QXZ function values with PHP variables in them\n";
		print "  [--QXZlengthonly] = print only QXZ function values that include a length spec\n";
		print "  [--QXZplaceonly] = print only QXZ function values with placeholder variables in them\n";
		print "  [--file=/path/from/root] = define PHP file to analyze\n";
		print "\n";

		exit;
		}
	else
		{
		if ($args =~ /--debug/i) # Debug flag
			{$DB=1;}
		if ($args =~ /--QXZlines/i) # QXZlines flag
			{$QXZlines=1;}
		if ($args =~ /--QXZvalues/i) # QXZvalues flag
			{$QXZvalues=1;}
		if ($args =~ /--QXZvaronly/i) # QXZvaronly flag
			{$QXZvaronly=1;}
		if ($args =~ /--QXZlengthonly/i) # QXZlengthonly flag
			{$QXZlengthonly=1;}
		if ($args =~ /--QXZplaceonly/i) # QXZplaceonly flag
			{$QXZplaceonly=1;}
		if ($args =~ /--file=/i) # CLI defined file path
			{
			@CLIconffileARY = split(/--file=/,$args);
			@CLIconffileARX = split(/ /,$CLIconffileARY[1]);
			if (length($CLIconffileARX[0])>2)
				{
				$PATHconf = $CLIconffileARX[0];
				$PATHconf =~ s/\/$| |\r|\n|\t//gi;
				print "  CLI defined file path:  $PATHconf\n";
				}
			}
		}
	}
else
	{
	print "no command line options set, exiting\n";
	exit;
	}
if (length($PATHconf)<2) 
	{
	print "no file to analyze($PATHconf), exiting\n";
	exit;
	}
### end parsing run-time options ###


$QXZlinecount=0;
$QXZcount=0;
$QXZcount_length=0;
$QXZcount_var=0;
$QXZcount_place=0;
$QXZcount_var_ct=0;
$QXZcount_place_ct=0;

if (-e "$PATHconf") 
	{
	print "file found at: $PATHconf\n";
	print "\n";
	open(conf, "$PATHconf") || die "can't open $PATHconf: $!\n";
	@conf = <conf>;
	close(conf);
	$i=0;
	foreach(@conf)
		{
		$line = $conf[$i];
		$line =~ s/\n|\r|\t//gi;

		if ($line =~ /\_QXZ\(/)
			{
			$QXZlinecount++;
			$QXZ_in_line = () = $line =~ /\_QXZ\(/g;
			$QXZcount = ($QXZcount + $QXZ_in_line);
			@eachQXZ = split(/\_QXZ\(/,$line);
			$each_ct=0;
			while ($each_ct <= $#eachQXZ) 
				{
				if ($each_ct > 0) 
					{
					@eachQXZvalue = split(/\)/,$eachQXZ[$each_ct]);
					if ( ($eachQXZvalue[0] =~ /\(/) && (length($eachQXZvalue[1])>0) )
						{
						$temp_QXZval_str = $eachQXZvalue[0].")".$eachQXZvalue[1];
						if ( ($eachQXZvalue[1] =~ /\(/) && (length($eachQXZvalue[2])>0) )
							{
							$temp_QXZval_str .= ")".$eachQXZvalue[2];
							if ( ($eachQXZvalue[2] =~ /\(/) && (length($eachQXZvalue[3])>0) )
								{
								$temp_QXZval_str .= ")".$eachQXZvalue[3];
								}
							}
						}
					else
						{
						$temp_QXZval_str = $eachQXZvalue[0];
						}
					if ( ($QXZvalues > 0) && ($QXZvaronly < 1) && ($QXZlengthonly < 1) && ($QXZplaceonly < 1) )
						{print "$temp_QXZval_str\n";}
					if ($temp_QXZval_str =~ /,\d|, \d/) 
						{
						$QXZcount_length++;
						if ($QXZlengthonly > 0)
							{print "$temp_QXZval_str\n";}
						}
					if ($temp_QXZval_str =~ /\$/) 
						{
						$QXZcount_var++;
						$var_ct_in_line = () = $temp_QXZval_str =~ /\$/g;
						$QXZcount_var_ct = ($QXZcount_var_ct + $var_ct_in_line);
						if ($QXZvaronly > 0)
							{print "$temp_QXZval_str\n";}
						}
					if ($temp_QXZval_str =~ /%\ds/) 
						{
						$QXZcount_place++;
						$place_ct_in_line = () = $temp_QXZval_str =~ /\$/g;
						$QXZcount_place_ct = ($QXZcount_place_ct + $place_ct_in_line);
						if ($QXZplaceonly > 0)
							{print "$temp_QXZval_str\n";}
						}
					}

				$each_ct++;
				}


			if ($QXZlines > 0)
				{print "$i|$QXZlinecount|$QXZ_in_line|$line\n";}


			}

		$i++;
		}
	}
else
	{
	print "no file to analyze($PATHconf), exiting\n";
	exit;
	}


print "\n";
print "ANALYSIS FINISHED!\n";
print "File path:    $PATHconf\n";
print "Lines in file:           $i\n";
print "QXZ line count:          $QXZlinecount\n";
print "QXZ count:               $QXZcount\n";
print "QXZ with length set:     $QXZcount_length\n";
print "QXZ with PHP vars:       $QXZcount_var\n";
print "QXZ with var place set:  $QXZcount_place\n";
print "QXZ with PHP vars inst:  $QXZcount_var_ct\n";
print "QXZ with var place inst: $QXZcount_place_ct\n";
print "\n";

$secy = time();		$secz = ($secy - $secX);		$minz = ($secz/60);		# calculate script runtime so far
print "\n     - process runtime      ($secz sec) ($minz minutes)\n";


exit;
