<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');

$time = date('r');
// If the connection closes, retry in 1 second
echo "retry: {$_GET['refresh_interval']}\n";
echo "data: The server time is: {$time}\n\n";
flush();