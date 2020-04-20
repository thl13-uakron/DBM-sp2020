<?php
# This script returns a list of any rooms that have been deleted, modified, or created since the last time this script
# returned results

# initialize result array
$ajaxResult = array();

# get connection to mysql database
include "dbAccess.php";
$db = get_db_connection();

if ($db) {
	$ajaxResult["sqlConnectSuccess"] = true;

	# read parameters
	$_POST = json_decode(file_get_contents('php://input'), true);
	$lastUpdateTime = $db->real_escape_string($_POST["lastUpdateTime"]);

	# poll for updates
	$updatesDetected = false;
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

		# check new and updated rooms
		$queryResult = $db->query("call getRoomListUpdates('$lastUpdateTime')");
		if ($queryResult) {
			$updatedRooms = $queryResult->fetch_all(MYSQLI_ASSOC);

			# record data and break loop if updates are detected
			if (count($updatedRooms) > 0) {
				$ajaxResult["querySuccess"] = true;
				$ajaxResult["updatedRooms"] = $updatedRooms;
				$updatesDetected = true;
			}
		}
		else {
			# indicate if errors occur
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["errorCode"] = $db->errno;
			break;
		}
		free_all_results($db);

		# check deleted rooms
		$queryResult = $db->query("call getRoomDeletions('$lastUpdateTime')");
		if (!$queryResult) {
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["errorCode"] = $db->errno;
			break;
		}
		$deletedRooms = $queryResult->fetch_all(MYSQLI_ASSOC);
		if (count($deletedRooms) > 0) {
			$ajaxResult["querySuccess"] = true;
			$ajaxResult["deletedRooms"] = $deletedRooms;
			$updatesDetected = true;
		}

		# free results
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
#$db->close();

?>