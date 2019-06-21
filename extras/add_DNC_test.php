<?php
### add_DNC_test.php - add numbers to the DNC list using the admin.php form
#
# 
# CHANGES:
# 190617-1057 - First Build
#

$DB=1; 

$phone_numbers = "9998885555\n9998885556\n9998885557";

$HTTPuser = '6666';
$HTTPpass = '1234';

$url = "https://cpdtest.vicihost.com/vicidial/admin.php";
$fields = "ADD=121&campaign_id=SYSTEM_INTERNAL&stage=add&phone_numbers=$phone_numbers";

# use cURL to call the copy custom fields code
$curl = curl_init();

# Set some options - we are passing in a useragent too here
curl_setopt_array($curl, array(
	CURLOPT_RETURNTRANSFER => 1,
	CURLOPT_URL => $url,
	CURLOPT_POST => 4,
	CURLOPT_POSTFIELDS => $fields,
	CURLOPT_USERPWD => "$HTTPuser:$HTTPpass",
	CURLOPT_USERAGENT => 'Test-DNC-adder'
));

# Send the request & save response to $resp
$resp = curl_exec($curl);

# Close request to clear up some resources
curl_close($curl);

if ($DB > 0)
	{
	echo "URL: $url\n";
	}

echo "$resp\n";

?>
