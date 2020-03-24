<?php
# this script returns any changes to the name or description of the room as well as any changes made
# to the user's permissions and any channels that have been added, edited, or deleted

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
	$lastUpdateTime = $db->real_escape_string($_POST["lastUpdateTime"]);

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

	# validate userID
	if (!$userID) {
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["failReason"] = "Failed to validate user";
		exit(json_encode($ajaxResult));
	}

	$updatesDetected = false;

	# set_time_limit(30);
	while (!$updatesDetected) {
		# record current time
		$queryResult = $db->query("select NOW()");
		if (!$queryResult) {
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["errorCode"] = $db->errno;
			break;
		}
		$ajaxResult["updateTime"] = $queryResult->fetch_array()[0];
		free_all_results($db);

		# get changes to room info
		$queryResult = $db->query("select checkRoomInfoUpdates('$roomID', '$lastUpdateTime')");
		if (!$queryResult) {
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["errorCode"] = $db->errno;
			break;
		}
		if ($queryResult->fetch_row()[0]) {
			$updatesDetected = true;
			free_all_results($db);
			$queryResult = $db->query("call getRoomInfo('$roomID')");
			if (!$queryResult) {
				$ajaxResult["querySuccess"] = false;
				$ajaxResult["errorCode"] = $db->errno;
				break;
			}
			$roomInfo = $queryResult->fetch_assoc();

			$ajaxResult["querySuccess"] = true;
			$ajaxResult["roomInfo"] = $roomInfo;
		}
		free_all_results($db);

		# get changes to room permissions

		# get new or edited channels
		$queryResult = $db->query("call getNewChannels('$roomID', '$lastUpdateTime')");
		if (!$queryResult) {
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["errorCode"] = $db->errno;
			break;
		}
		$newChannels = $queryResult->fetch_all(MYSQLI_ASSOC);
		if (count($newChannels) > 0) {
			$ajaxResult["querySuccess"] = true;
			$ajaxResult["newChannels"] = $newChannels;
			$updatesDetected = true;
		}

		# get deleted channels

		# wait before iterating again
		if (!$updatesDetected) {
			free_all_results($db);
			if (connection_aborted()) exit();
			sleep(1);
		}
	}
}
else {
	$ajaxResult["sqlConnectSuccess"] = false;
	$ajaxResult["errorCode"] = $db->errno;
}

# return data
echo json_encode($ajaxResult);

?>