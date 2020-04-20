<?php
# This script finds and returns the ID of the last channel the user visited that
# they still have access to, either within a specified room or across the whole site

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
		$roomID = $db->real_escape_string($_POST["roomID"]);
	}

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

	# get channel ID
	$queryResult = $db->query("select getLastVisitedChannel('$userID', '$roomID')");
	if (!$queryResult) {
		# handle errors
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["errorCode"] = $db->errno;
		exit(json_encode($ajaxResult));
	}
	$ajaxResult["channelID"] = $queryResult->fetch_row()[0];
	$ajaxResult["querySuccess"] = true;
}
else {
	$ajaxResult["sqlConnectSuccess"] = false;
}

# return data
echo json_encode($ajaxResult);

?>