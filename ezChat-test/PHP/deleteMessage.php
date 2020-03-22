<?php
# This script allows a user to delete a message they posted or was posted in a room they moderate, checking
# first if they have permission to do so and taking the user's sessionID and the ID of the message they want to
# delete as parameters

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

	if (!$userID) {
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["failReason"] = "Failed to validate user";
		exit(json_encode($ajaxResult));
	}
}
else {
	$ajaxResult["sqlConnectSuccess"] = false;
	$ajaxResult["errorCode"] = $db->errno;
}

# return data
echo json_encode($ajaxResult);

?>