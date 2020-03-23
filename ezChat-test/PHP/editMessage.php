<?php
# This script allows a user to edit the contents of a message they posted, checking first if they have
# permission to do so and taking the user's session ID, the ID of the message they want to edit, and the 
# revised contents of the message as parameters

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
	$messageID = $db->real_escape_string($_POST["messageID"]);
	$newContent = $db->real_escape_string($_POST["newContent"]);

	# get userID from session ID
	$queryResult = $db->query("select getSessionUser('$sessionID')");
	if (!$queryResult) {
		# handle errors
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["errorCode"] = $db->errno;
		exit(json_encode($ajaxResult));
	}
	$userID = $queryResult->fetch_row()[0];
	free_all_results($db);

	# check if userID is valid
	if (!$userID) {
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["failReason"] = "Failed to validate user";
		exit(json_encode($ajaxResult));
	}

	# check if user has permission to edit message
	$queryResult = $db->query("select canEditMessage('$userID', '$messageID')");
	if (!$queryResult) {
		# handle errors
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["errorCode"] = $db->errno;
		exit(json_encode($ajaxResult));
	}
	if (!$queryResult->fetch_row()[0]) {
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["failReason"] = "Permission to edit message not granted";
		exit(json_encode($ajaxResult));
	}

	# edit message
	$queryResult = $db->query("call editMessage('$userID', '$messageID', '$newContent')");
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
	$ajaxResult["errorCode"] = $db->errno;
}

# return data
echo json_encode($ajaxResult);

?>