<?php
# This script adds a new channel to the database, taking the name, a description and the 
# ID of the room the channel is in, returning the ID of the new channel

# initialize result array
$ajaxResult = array();

# get connection to mysql database
include "dbAccess.php";
$db = get_db_connection();

if ($db) {
	$ajaxResult["sqlConnectSuccess"] = true;

	# read parameters
	$_POST = json_decode(file_get_contents('php://input'), true);
	$channelName = $db->real_escape_string($_POST["channelName"]);
	$description = $db->real_escape_string($_POST["description"]);
	$roomID = $db->real_escape_string($_POST["roomID"]);
	$sessionID = $db->real_escape_string($_POST["sessionID"]);

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

	if (!$userID) {
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["failReason"] = "Failed to validate user session";
		exit(json_encode($ajaxResult));
	}

	# check if user is allowed to create channel
	$queryResult = $db->query("select canAddChannel('$userID', '$roomID')");
	if (!$queryResult) {
		# handle errors
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["errorCode"] = $db->errno;
		exit(json_encode($ajaxResult));
	}
	if (!$queryResult->fetch_row()[0]) {
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["failReason"] = "Permission to add channels to this room not granted";
		exit(json_encode($ajaxResult));
	}

	# execute query
	$queryResult = $db->query("call addChannel('$channelName', '$description', '$roomID', @p_channelID)");
	if ($queryResult) {
		$queryResult = $db->query("select @p_channelID");

		# parse query results
		$channelID = $queryResult->fetch_row()[0];

		# record data
		$ajaxResult["querySuccess"] = true;
		$ajaxResult["channelID"] = $channelID;
	}
	else {
		# indicate if errors occur
		$ajaxResult["querySuccess"] = false;
	}
}
else {
	$ajaxResult["sqlConnectSuccess"] = false;
}

# return data
echo json_encode($ajaxResult);

?>