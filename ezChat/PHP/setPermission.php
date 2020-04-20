<?php 
# this script allows the user to modify the permission settings for a room or channel
# taking the permissionID, the user's sessionID, the roomID or channelID, and the value
# to set the permission to, checking first if the user has permission to do this
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
	$roomID = null;
	if (array_key_exists("roomID", $_POST)) {
		$roomID = $_POST["roomID"];
	}
	$channelID = null;
	if (array_key_exists("channelID", $_POST)) {
		$channelID = $_POST["channelID"];
	}
	$permissionID = $db->real_escape_string($_POST["permissionID"]);
	$permissionValue = $db->real_escape_string($_POST["permissionValue"]);

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

	# channel permission settings
	if ($channelID) {
		# check if user has permission to set permission
		$queryResult = $db->query("select canEditChannelInfo('$userID', '$channelID')");
		if (!$queryResult) {
			# handle errors
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["errorCode"] = $db->errno;
			exit(json_encode($ajaxResult));
		}
		if (!$queryResult->fetch_row()[0]) {
			# indicate if permission not granted
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["failReason"] = "Permission to edit channel settings not granted";
			exit(json_encode($ajaxResult));
		}
		free_all_results($db);

		# set permission
		$queryResult = $db->query("call setChannelPermission('$channelID', '$permissionID', '$permissionValue')");
		if (!$queryResult) {
			# handle errors
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["errorCode"] = $db->errno;
			exit(json_encode($ajaxResult));
		}
		$ajaxResult["querySuccess"] = true;
		exit(json_encode($ajaxResult));
	}

	# room permission settings
	if ($roomID) {
		# check if user has permission to set permission
		$queryResult = $db->query("select canEditRoomInfo('$userID', '$roomID')");
		if (!$queryResult) {
			# handle errors
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["errorCode"] = $db->error;
			exit(json_encode($ajaxResult));
		}
		if (!$queryResult->fetch_row()[0]) {
			# indicate if permission not granted
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["failReason"] = "Permission to edit room settings not granted";
			exit(json_encode($ajaxResult));
		}
		free_all_results($db);

		# set permission
		$queryResult = $db->query("call setRoomPermission('$roomID', '$permissionID', '$permissionValue')");
		if (!$queryResult) {
			# handle errors
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["errorCode"] = $db->error;
			exit(json_encode($ajaxResult));
		}
		$ajaxResult["querySuccess"] = true;
		exit(json_encode($ajaxResult));
	}
}
else {
	$ajaxResult["sqlConnectSuccess"] = false;
}

# return data
echo json_encode($ajaxResult);
?>