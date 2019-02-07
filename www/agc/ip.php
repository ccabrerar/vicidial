<?php

$ip = getenv("REMOTE_ADDR");
$ip_class_c = preg_replace('~\.\d+(?!.*\.\d+)~', '.x', $ip);

echo "Your IP Address: $ip<br>\n";
#echo "Your IP Address class C: $ip_class_c\n";

?>