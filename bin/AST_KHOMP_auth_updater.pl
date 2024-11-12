#!/usr/bin/perl

# default path to astguiclient configuration file:
$PATHconf = '/etc/astguiclient.conf';

open(conf, "$PATHconf") || die "can't open $PATHconf: $!\n";
@conf = <conf>;
close(conf);
$i=0;
foreach(@conf)
	{
	$line = $conf[$i];
	$line =~ s/ |>|\n|\r|\t|\#.*|;.*//gi;
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
	$i++;
		}

if (!$VARDB_port)	   {$VARDB_port='3306';}

$CLIcontainer='KHOMPSETTINGS';

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
                print "  [--khomp-container=XXX] = the settings container with the khomp settings to use\n";
                print "\n";
                exit;
                }
        else
                {
                if ($args =~ /--khomp-container=/i) # CLI defined container
                        {
                        @CLIvarARY = split(/--khomp-container=/,$args);
                        @CLIvarARX = split(/ /,$CLIvarARY[1]);
                        if (length($CLIvarARX[0])>2)
                                {
                                $CLIcontainer = $CLIvarARX[0];
                                $CLIcontainer =~ s/\/$| |\r|\n|\t//gi;
                                }
                        else
                                {$CLIcontainer = 'KHOMPSETTINGS';}
                        @CLIvarARY=@MT;   @CLIvarARY=@MT;
                        }
		}
	}


use DBI;
$dbhA = DBI->connect("DBI:mysql:$VARDB_database:$VARDB_server:$VARDB_port", "$VARDB_user", "$VARDB_pass")
	or die "Couldn't connect to database: " . DBI->errstr;


use Time::Local;
use Time::HiRes ('tv_interval','gettimeofday','usleep','sleep');  # necessary to have perl sleep command of less than one second
($START_s_hires, $START_usec) = gettimeofday();

# Needed for Khomp Integration
 use JSON::PP qw(encode_json decode_json);


### find curl binary for KHOMP
$curlbin = '';
$khomp_enabled = 1;
if ( -e ('/bin/curl')) {$curlbin = '/bin/curl';}
else
	{
	if ( -e ('/usr/bin/curl')) {$curlbin = '/usr/bin/curl';}
	else
		{
		if ( -e ('/usr/local/bin/curl')) {$curlbin = '/usr/local/bin/curl';}
		else
			{
			if ($AGILOG) {$agi_string = "ERROR: curl binary not found, KHOMP disabled";   &agi_output;}
			$khomp_enabled = 0;
			}
		}
	}

$khomp_api_url =			'';
$khomp_api_proxied =		'false';
$khomp_api_login_url =		'';
$khomp_api_user =			'';
$khomp_api_pass =			'';
$khomp_api_token =			'';
$khomp_api_check_ssl =		'';
$khomp_api_token_expire =	0;

$stmtA = "SELECT container_entry FROM vicidial_settings_containers WHERE container_id = '$CLIcontainer';";
$sthA = $dbhA->prepare($stmtA) or die "preparing: ",$dbhA->errstr;
$sthA->execute or die "executing: $stmtA ", $dbhA->errstr;
$sthArows=$sthA->rows;
if ($sthArows > 0)
	{
	@aryA = $sthA->fetchrow_array;
	$container_entry = $aryA[0];

	my @lines = split ( /\n/, $container_entry );
	foreach my $line (@lines)
		{
		# remove comments and blank lines
		$line =~ /^\s*$/ and next; # skip blank lines
		$line =~ /^\s*#/ and next; # skip line that begin with #
		if ( $line =~ /#/ ) # check for comments midway through the line
			{
			# remove them
			@split_line = split( /#/ , $line );
			$line = $split_line[0];
			}

		if ( $line =~ /=>/ )
			{
			@setting = split( /=>/ , $line );
			$key = $setting[0];
			$key =~ s/^\s+|\s+$//g;
			$value = $setting[1];
			$value =~ s/^\s+|\s+$//g;

			if ( $key eq 'khomp_api_url' )				{ $khomp_api_url = $value; }
			if ( $key eq 'khomp_api_proxied' )			{ $khomp_api_proxied = $value; }
			if ( $key eq 'khomp_api_user' )				{ $khomp_api_user = $value; }
			if ( $key eq 'khomp_api_pass' )				{ $khomp_api_pass = $value; }
			if ( $key eq 'khomp_api_check_ssl' )                         { $khomp_api_check_ssl = $value; }
			if ( $key eq 'khomp_api_login_url' )		{ $khomp_api_login_url = $value; }
			if ( $key eq 'khomp_api_token' )			{ $khomp_api_token = $value; }
			if ( $key eq 'khomp_api_token_expire' )		{ $khomp_api_token_expire = $value; }
			}

		}
	print "Current KHOMP Settings: url-$khomp_api_url\nproxied-$khomp_api_proxied\nlogin_url-$khomp_api_login_url\nuser-$khomp_api_user\npass-$khomp_api_pass\nheader-$khomp_header\nssl-check-$khomp_api_check_ssl\nformat-$khomp_id_format\ntoken-$khomp_api_token\ntoken expire-$khomp_api_token_expire\n\n";
	}
else
	{
	print "No KHOMP Settings Container!!! Exiting\n";
	exit();
	}

my $api_auth_time = 0;
if ( ( !( $khomp_api_token_expire ) ) and ( $khomp_api_token_expire != 0 ) ) { $khomp_api_token_expire = 0; }

if ($AGILOG) {$agi_string = "--	KHOMP API Token $khomp_api_token has expired at $khomp_api_token_expire";   &agi_output;}

# get a new API token
my $new_khomp_api_token = '';
if ( $khomp_api_proxied eq 'false' )
	{
	($new_khomp_api_token, $api_auth_time) = khomp_api_login( $khomp_api_login_url, $khomp_api_user, $khomp_api_pass, $khomp_api_check_ssl );
	}

if ( $new_khomp_api_token eq '' )
	{
	print "ERROR!!! No Token! Exit!\n\n";
	exit;
	}

# update the settings container
my $old_token_string = "khomp_api_token => $khomp_api_token";
my $new_token_string = "khomp_api_token => $new_khomp_api_token";
my $new_token_expire_time = time() + 3600;
my $old_token_expire_string = "khomp_api_token_expire => $khomp_api_token_expire";
my $new_token_expire_string = "khomp_api_token_expire => $new_token_expire_time";

# LOCK vicidial_settings_containers
$stmtA = "LOCK TABLES vicidial_settings_containers WRITE";
$dbhA->do($stmtA);

# UPDATE the Token
$stmtToken = "UPDATE vicidial_settings_containers SET container_entry = REGEXP_REPLACE(container_entry, '$old_token_string', '$new_token_string') WHERE container_id = '$CLIcontainer';";
$affected_rows = $dbhA->do($stmtToken);

# UPDATE the Expire time
$stmtExpire = "UPDATE vicidial_settings_containers SET container_entry = REGEXP_REPLACE(container_entry, '$old_token_expire_string', '$new_token_expire_string') WHERE container_id = '$CLIcontainer';";
$affected_rows = $dbhA->do($stmtExpire);

# Unlock vicidial_settings_containers
$stmtA = "UNLOCK TABLES";
$dbhA->do($stmtA);

print "--	KHOMP SC TOKEN UPDATE|$affected_rows|$stmtToken|\n";
print "--	KHOMP SC TOKEN EXPIRE UPDATE|$affected_rows|$stmtExpire|\n";
print "--	KHOMP Auth API Call took $api_auth_time seconds\n";
	


### code for logging into KHOMP api
sub khomp_api_login
	{
	(
	$khomp_api_login_url,
	$khomp_api_user,
	$khomp_api_pass,
	$khomp_api_check_ssl
	) = @_;

	my $token = 'login';
	my $auth_start_sec;
	my $auth_start_usec;
	my $auth_end_sec;
	my $auth_end_usec;
	my $auth_sec;
	my $auth_usec;
	my $api_auth_time;

	($auth_start_sec, $auth_start_usec) = gettimeofday();

	# build JSON object
	my $login_json = {
		'id' => 0,
		'method' => 'SessionLogin',
		'params' => {
			'username' => "$khomp_api_user",
			'password' => "$khomp_api_pass",
		},
		'jsonrpc' => '2.0',
	};

	# encode it as JSON
	$login_json = encode_json( $login_json );

        if ($khomp_api_check_ssl == 0) { $curlbin = "$curlbin --insecure"; }

	my $curl_cmd = "$curlbin -sS --data \'$login_json\' $khomp_api_login_url";

	print "--	KHOMP CURL COMMAND: $curl_cmd\n";

	$message = `$curl_cmd`;

	print "--	KHOMP RESULT: $message\n";

	chomp($message);

	print "--	KHOMP LOGIN URL: |$khomp_api_login_url|\n";
	print "--	KHOMP LOGIN JSON : |$login_json|\n";
	print "--	KHOMP LOGIN RESPONSE JSON: |$message|\n";

	$json = JSON::PP->new->ascii->pretty->allow_nonref;
	$result = $json->decode($message);

	$token = $result->{'result'}->{'token'};

	($auth_end_sec, $auth_end_usec) = gettimeofday();

	$api_auth_time = tv_interval ( [$auth_start_sec, $auth_start_usec], [$auth_end_sec, $auth_end_usec]);

	return ($token, $api_auth_time);
	}

