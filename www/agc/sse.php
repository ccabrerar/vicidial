<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');

$time = date('r');

$refresh_interval=(array_key_exists('refresh_interval', $_GET) ? $_GET['refresh_interval'] : "-1");

// If the connection closes, retry in 1 second
echo "retry: {$refresh_interval}\n";
echo "data: The server time is: {$time}\n\n";
flush();
?>