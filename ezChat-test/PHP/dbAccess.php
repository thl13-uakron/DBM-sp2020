<?php
# This script contains a function to connect to the site's database server

function get_db_connection() {
	$db_host = "sql200.main-hosting.eu";
	$db_name = "u668413793_ezChat";
	$db_user = "u668413793_ezChatDev";
	$db_password = "ezChatDev";
	return new mysqli($db_host, $db_user, $db_password, $db_name);
}

?>