<?php
# This script contains a function to connect to the site's database server

function get_db_connection() {
	$db_host = "p:sql200.main-hosting.eu";
	$db_name = "u668413793_ezChat";
	$db_user = "u668413793_ezChatDev";
	$db_password = "ezChatDev";
	return new mysqli($db_host, $db_user, $db_password, $db_name);
}

function free_all_results($db)
{
    do {
        if ($res = $db->store_result()) {
            $res->fetch_all(MYSQLI_ASSOC);
            $res->free();
        }
    } while ($db->more_results() && $db->next_result());

}
?>