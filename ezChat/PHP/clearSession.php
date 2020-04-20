<?php

# this script removes a user's sessionID from the database after they log out

# get connection to mysql database
include "dbAccess.php";
$db = get_db_connection();

# get parameters
$_POST = json_decode(file_get_contents('php://input'), true);
$sessionID = $db->real_escape_string($_POST["sessionID"]);

$db->query("call clearSession('$sessionID')");

?>