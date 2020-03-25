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

	$roomIsBrowsable = $db->query("select browsable from Rooms where roomID = '$roomID'")->fetch_row()[0];
	$roomIsPublic = true;
	$newRoomPassword = null;

	# get userID from session ID
	$queryResult = $db->query("select getSessionUser('$sessionID')");
	if (!$queryResult) {
		# handle errors
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["errorCode"] = $db->errno;
		$ajaxResult["failReason"] = $db->error;
		exit(json_encode($ajaxResult));
	}
	$userID = $queryResult->fetch_row()[0];
	free_all_results($db);

	# check permissions
	$queryResult = $db->query("select canEditRoomInfo('$userID', '$roomID')");
	if (!$queryResult) {
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["errorCode"] = $db->errno;
		$ajaxResult["failReason"] = $db->error;
		exit(json_encode($ajaxResult));
	}
	if (!$queryResult->fetch_row()[0]) {
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["failReason"] = "Permission to change room settings not granted";
		exit(json_encode($ajaxResult));
	}
	free_all_results($db);

	# make changes
	$queryResult = $db->query("call updateRoomInfo('$roomID', '$newRoomName', '$newRoomDescription', '$roomIsBrowsable', '$roomIsPublic', '$newRoomPassword')");
	if (!$queryResult) {
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["errorCode"] = $db->errno;
		$ajaxResult["failReason"] = $db->error;
		exit(json_encode($ajaxResult));
	}
	$ajaxResult["querySuccess"] = true;
}
else {
	$ajaxResult["sqlConnectSuccess"] = false;
	$ajaxResult["errorCode"] = $db->errno;
	$ajaxResult["failReason"] = $db->error;
}

# return data
echo json_encode($ajaxResult);
#$db->close();

?>