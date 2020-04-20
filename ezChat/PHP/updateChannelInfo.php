<?php 
# This script allows the user to change the name of a description of a channel, taking their
# sessionID, the channelID, and the new name and description, and checking whether the user has
# permission to make the change
$ajaxResult = array();

# get connection to mysql database
include "dbAccess.php";
$db = get_db_connection();

if ($db) {
	$ajaxResult["sqlConnectSuccess"] = true;

	# read parameters
	$_POST = json_decode(file_get_contents('php://input'), true);
	$sessionID = $db->real_escape_string($_POST["sessionID"]);
	$channelID = $db->real_escape_string($_POST["channelID"]);
	$newChannelName = $db->real_escape_string($_POST["newChannelName"]);
	$newChannelDescription = $db->real_escape_string($_POST["newChannelDescription"]);

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
	$queryResult = $db->query("select canEditChannelInfo('$userID', '$channelID')");
	if (!$queryResult) {
		# handle errors
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["errorCode"] = $db->error;
		exit(json_encode($ajaxResult));
	}
	if (!$queryResult->fetch_row()[0]) {
		# indicate if permission not granted
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["failReason"] = "Permission to edit channel settings not granted";
		exit(json_encode($ajaxResult));
	}

	# make changes
	$queryResult = $db->query("call updateChannelInfo('$channelID', '$newChannelName', '$newChannelDescription')");
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
}

# return data
echo json_encode($ajaxResult);
?>