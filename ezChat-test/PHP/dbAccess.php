<?php
# This script contains a function to connect to the site's database server

function get_db_connection() {
	$db_host = "sql302.epizy.com";
	$db_name = "epiz_25254983_ezChat";
	$db_user = "epiz_25254983";
	$db_password = "ezChatDev";
	return new mysqli($db_host, $db_user, $db_password, $db_name);
}

?>