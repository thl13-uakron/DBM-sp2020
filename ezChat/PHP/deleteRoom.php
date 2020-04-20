<?php
# This script 

# initialize result array
$ajaxResult = array();

# get connection to mysql database
include "dbAccess.php";
$db = get_db_connection();

if ($db) {
	$ajaxResult["sqlConnectSuccess"] = true;

	# read parameters
	$_POST = json_decode(file_get_contents('php://input'), true);
	$sessionID = $db->real_escape_string($_POST["sessionID"]);
	$roomID = $db->real_escape_string($_POST["roomID"]);

	# get user ID
	$queryResult = $db->query("select getSessionUser('$sessionID')");
	if (!$queryResult) {
		# handle errors
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["errorCode"] = $db->errno;
		exit(json_encode($ajaxResult));
	}
	$userID = $queryResult->fetch_row()[0];
	free_all_results($db);

	# check permissions
	$queryResult = $db->query("select canDeleteRoom('$userID', '$roomID')");
	if (!$queryResult) {
		# handle errors
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["errorCode"] = $db->errno;
		exit(json_encode($ajaxResult));
	}
	if (!$queryResult->fetch_row()[0]) {
		# indicate if permission not granted
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["failReason"] = "Permission to delete room not granted";
		exit(json_encode($ajaxResult));
	}

	# make deletion
	$queryResult = $db->query("call deleteRoom('$roomID')");
	if (!$queryResult) {
		# handle errors
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["errorCode"] = $db->errno;
		exit(json_encode($ajaxResult));
	}
	$ajaxResult["querySuccess"] = true;
}
else {
	$ajaxResult["sqlConnectSuccess"] = false;
}

# return data
echo json_encode($ajaxResult);

?>