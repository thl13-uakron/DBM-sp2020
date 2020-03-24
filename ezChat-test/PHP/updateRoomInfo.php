<?php
# This script allows a user to change the name and description of a room, taking the roomID,
# the user's sessionID, and the updated information as parameters

# initialize result array
$ajaxResult = array();

# get connection to mysql database
include "dbAccess.php";
$db = get_db_connection();

if ($db) {
	$ajaxResult["sqlConnectSuccess"] = true;

	# read parameters
	$_POST = json_decode(file_get_contents('php://input'), true);
	$roomID = $db->real_escape_string($_POST["roomID"]);
	$sessionID = $db->real_escape_string($_POST["sessionID"]);
	$newRoomName = $db->real_escape_string($_POST["newRoomName"]);
	$newRoomDescription = $db->real_escape_string($_POST["newRoomDescription"]);
}
else {
	$ajaxResult["sqlConnectSuccess"] = false;
	$ajaxResult["errorCode"] = $db->errno;
}

# return data
echo json_encode($ajaxResult);
#$db->close();

?>