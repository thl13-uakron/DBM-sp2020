<?php
# This script takes the ID of a room and returns detailed information about it, including the name,
# description, ID and name of the creator, and date and time of creation, as well as what permissions
# the user has

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

	$ajaxResult["updateTime"] = $db->query("select NOW()")->fetch_row()[0];

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
		$ajaxResult["failReason"] = "Failed to validate user session";
		exit(json_encode($ajaxResult));
	}

	# execute query
	$queryResult = $db->query("call getRoomInfo('$roomID')");
	if ($queryResult) {
		$roomInfo = $queryResult->fetch_assoc();

		# record data
		$ajaxResult["querySuccess"] = true;
		$ajaxResult["roomInfo"] = $roomInfo;

		# get channel list
		free_all_results($db);
		$queryResult = $db->query("call getChannelsByRoom('$roomID')");
		if (!$queryResult) {
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["errorCode"] = $db->errno;
			exit(json_encode($ajaxResult));
		}
		$channelList = $queryResult->fetch_all(MYSQLI_ASSOC);
		$ajaxResult["channelList"] = $channelList;

		# get permissions
		$ajaxResult["roomPermissions"] = array();
		free_all_results($db);
		$queryResult = $db->query("select canAddChannel('$userID', '$roomID')");
		if (!$queryResult) {
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["errorCode"] = $db->errno;
			exit(json_encode($ajaxResult));
		}
		$ajaxResult["roomPermissions"]["canAddChannels"] = $queryResult->fetch_row()[0];

		free_all_results($db);
		$queryResult = $db->query("select canEditRoomInfo('$userID', '$roomID')");
		if (!$queryResult) {
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["errorCode"] = $db->errno;
			exit(json_encode($ajaxResult));
		}
		$ajaxResult["roomPermissions"]["canEditRoomInfo"] = $queryResult->fetch_row()[0];
	}
	else {
		# indicate if errors occur
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["errorCode"] = $db->errno;
	}
}
else {
	$ajaxResult["sqlConnectSuccess"] = false;
	$ajaxResult["errorCode"] = $db->errno;
}

# return data
echo json_encode($ajaxResult);
#$db->close();

?>