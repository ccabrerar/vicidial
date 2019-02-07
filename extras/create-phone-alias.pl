#!/usr/bin/perl
#use warnings;

# Our little random password generator sub
sub randomPassword {
my $password;
my $_rand;

my $password_length = $_[0];
        if (!$password_length) {
                $password_length = 10;
        }

my @chars = split(" ",
        "0 1 2 3 4 5 6 7 8 9 a b c d e
        f g h i j k l m n o p q r s t u
        v w x y z 0 1 2 3 4 5 6 7 8 9 A
        B C D E F G H I J K L M N O P Q
        R S T U V W X Y Z 0 1 2 3 4 5 6
        7 8 9");

srand;

for (my $i=0; $i <= $password_length ;$i++) {
        $_rand = int(rand 41);
        $password .= $chars[$_rand];
}
return $password;
}

# Used for number to letter substitution in loops
@abcnumsub = split(" ", "a b c d e f g h i j k l m n o p q r s t u v w x y z");

# default path to astguiclient configuration file:
$PATHconf =	'/etc/astguiclient.conf';

open(conf, "$PATHconf") || die "can't open $PATHconf: $!\n";
@conf = <conf>;
close(conf);
$i=0;
foreach(@conf)
	{
	$line = $conf[$i];
	$line =~ s/ |>|\n|\r|\t|\#.*|;.*//gi;
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


### begin parsing run-time options ### Shamelessly stolen from Matt's code
if (length($ARGV[0])>1)
	{
	$i=0;
	while ($#ARGV >= $i)
		{
		$args = "$args $ARGV[$i]";
		$i++;
		}
	print "\n\nPhone Alias Creation Tool\n\n";
	if ($args =~ /--help/i)
		{
		print "allowed run time options:\n";
		print "  [--start-phone=XXXX] = Phone to start from\n";
		print "  [--phone-count=XXX] = Number of sequential phones to make\n";
		print "  [--password=X] = Password to set all phones to, or RND if random\n";
		print "  [--pass-length=X] = Length to set password to if random (Defaults to 10)\n";
		print "  [--protocol=SIP/IAX] = Create SIP or IAX entries (required)\n";
		print "  [--gmt-offset=-/+X] = GMT Offset of phone, I.E. -5.00 for EST, etc\n";
		print "  [--server=server_id] = Don't create alias, just set up phones on a single server\n";
		print "  [--debug] = debug\n";
		exit;
		}
	else
		{
		if ($args =~ /--debug/i)
			{
			$DB=1;
			print "\n----- DEBUG -----\n\n";
			}
		}
		if ($args =~ /--start-phone=/i) # starting phone extension
			{
			@startphoneARY = split(/--start-phone=/,$args);
			@startphoneARX = split(/ /,$startphoneARY[1]);
			if (length($startphoneARX[0])>2)
				{
				$startphone = $startphoneARX[0];
				$startphone =~ s/\/$| |\r|\n|\t//gi;
				print "  Start Phone :      $startphone\n";
				} else {
					die "Need a starting phone position\n";
				}
		}
		
		if ($args =~ /--phone-count=/i) # number of sequential phones to make
			{
			@phonecountARY = split(/--phone-count=/,$args);
			@phonecountARX = split(/ /,$phonecountARY[1]);
			if (length($phonecountARX[0])>1)
				{
				$phonecount = $phonecountARX[0];
				$phonecount =~ s/\/$| |\r|\n|\t//gi;
				print "  Phone Count :      $phonecount\n";
				} else {
					die "Need to be creating at least 10 phones to use this script!\n";
				}
		}
		
		if ($args =~ /--password=/i) # static password to use
			{
			@passwordARY = split(/--password=/,$args);
			@passwordARX = split(/ /,$passwordARY[1]);
			if (length($passwordARX[0])>2)
				{
				$password = $passwordARX[0];
				$password =~ s/\/$| |\r|\n|\t//gi;
				print "  Password    :      $password\n";
				} else {
					die "No password option given\n";
				}
		}
		
		if ($args =~ /--pass-length=/i) # password length to use if random
			{
			@passlengthARY = split(/--pass-length=/,$args);
			@passlengthARX = split(/ /,$passlengthARY[1]);
			$passlength = $passlengthARX[0];
			$passlength =~ s/\/$| |\r|\n|\t//gi;
			if ($password=='RND')
				{
				print "  Pass Length :      $passlength\n";
				$passlength = $passlength - 1 ;
				}
		}
		
		if ($args =~ /--protocol=/i) # Protocol to use
			{
			@protocolARY = split(/--protocol=/,$args);
			@protocolARX = split(/ /,$protocolARY[1]);
			if (length($protocolARX[0])>2)
				{
				$protocol = $protocolARX[0];
				$protocol =~ s/\/$| |\r|\n|\t//gi;
				print "  Protocol    :      $protocol\n";
				} else {
					die "Need to specify SIP or IAX protocol\n";
				}
		}
		
		if ($args =~ /--server=/i) # Protocol to use
			{
			@serverARY = split(/--server=/,$args);
			@serverARX = split(/ /,$serverARY[1]);
			if (length($serverARX[0])>2)
				{
				$server = $serverARX[0];
				$server =~ s/\/$| |\r|\n|\t//gi;
				print "  server      :      $server\n";
				}
		}
		
		if ($args =~ /--gmt-offset=/i) # Protocol to use
			{
			@gmtoffsetARY = split(/--gmt-offset=/,$args);
			@gmtoffsetARX = split(/ /,$gmtoffsetARY[1]);
			if (length($gmtoffsetARX[0])>3)
				{
				$gmtoffset = $gmtoffsetARX[0];
				$gmtoffset =~ s/\/$| |\r|\n|\t//gi;
				print "  GMT offset  :      $gmtoffset\n";
				} else {
					die "GMT Offset not set\n";
				}
		}

		
	}
else
	{
	die "no command line options set\n";
	}

# Some other simple data validation
if (length($passlength)>0 and $password!='RND') {
	die "Can not use randomn password without a password length";
}

# Set-up our database connection strings, die if we cant connect to either DB
use DBI;
$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass") or die "Couldn't connect to database: " . DBI->errstr;
 
# Find our servers
if (length($server)>2) {
	$stmtSERVERS = "select server_ip from servers where server_id='$server' and active_agent_login_server='Y' and active='Y';";
	if ($DB==1) {print "Server Selection SQL: $stmtSERVERS\n"; }
	} else {
		$stmtSERVERS = "select server_ip from servers where active_agent_login_server='Y' and active='Y';";
		if ($DB==1) {print "Server Selection SQL: $stmtSERVERS\n"; }
}
$sthSERVERS = $dbhA->prepare($stmtSERVERS) or die "Preparing sthSERVERS: ",$dbhA->errstr;
$sthSERVERS->execute or die "Executing sthSERVERS: ",$dbhA->errstr;
$sthSERVERSrows = $sthSERVERS->rows;

# Now loop through our servers, creating an array of their IP's
if ($sthSERVERSrows>=1) {
	$i=0;
	while ($i < $sthSERVERSrows) {
		@arySERVERS = $sthSERVERS->fetchrow_array;
		$server_ip = $arySERVERS[0];
		if ($i==0) {
			$server_ip_list = $server_ip;
			} else {
				$server_ip_list = $server_ip_list . " " . $server_ip;
		}
		
		$i++;
	}
	if ($DB==1) { print "Server IP List: $server_ip_list\n"; }
	@serverip = split(" ", $server_ip_list);
	
	} else {
		die "That server does not exist\n";
}
	
	$i=0;
	# Now do some inserts
	while ($i < $phonecount) {
		$n=0;
		$currphone = $startphone + $i;
		$aliaslist = '';
		$phonepass = $password;
		if ($password=='RND') {
			$phonepass = randomPassword($passlength);
		}
		if ($DB==1) { print "Password for phone $currphone: $phonepass \n"; }
		while ($n < $sthSERVERSrows) {
			if ($sthSERVERSrows>1) { $currphonealias = $currphone . $abcnumsub[$n]; } else { $currphonealias = $currphone; }
			
			if ($n == 0) {
				$aliaslist = $currphonealias;
				} else {
					$aliaslist = $aliaslist . "," . $currphonealias;
			}
			
			$server_ip = $serverip[$n];
			$stmtPHONE = "INSERT INTO phones (extension,dialplan_number,voicemail_id,server_ip,login,pass,status,active,phone_type,fullname,protocol,local_gmt,outbound_cid) values('$currphonealias','$currphone','$currphone','$server_ip','$currphonealias','$phonepass','ACTIVE','Y','Agent $currphone','station $currphone','$protocol','$gmtoffset','0000000000');";
			if ($DB==1) { print "Phone insert $n for phone $currphone: $stmtPHONE \n"; }
			$sthPHONE = $dbhCDR->prepare($stmtPHONE) or die "Preparing stmtPHONE: ",$dbhA->errstr;
			$sthPHONE->execute or die "Executing sthPHONE: ",$dbhA->errstr;
			$sthPHONE->finish;
			
		$n++;
		}
		$aliaslogin = $currphone . 'x';
		
		if ($sthSERVERSrows>1) {
			$stmtALIAS = "insert into phones_alias (alias_id, alias_name, logins_list) values ('$aliaslogin', '$aliaslogin', '$aliaslist');";
			if ($DB==1) { print "Phone alias insert for phone $currphone: $stmtALIAS \n"; }
			$sthALIAS = $dbhCDR->prepare($stmtALIAS) or die "Preparing stmtALIAS: ",$dbhA->errstr;
			$sthALIAS->execute or die "Executing sthALIAS: ",$dbhA->errstr;
			$sthALIAS->finish;
		}
	$i++;
	}

print "\n\nAlias list done\n";
