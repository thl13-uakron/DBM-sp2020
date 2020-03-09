<?php
# This script returns the list of new messages, along with edited and deleted and any changes to the channel settings,
# posted to a channel since the previous update, taking the ID of the channel and the time of the previous update

# initialize result array
$ajaxResult = array();

# get connection to mysql database
include "dbAccess.php";
$db = get_db_connection();

if ($db) {
	$ajaxResult["sqlConnectSuccess"] = true;

	# read parameters
	$_POST = json_decode(file_get_contents('php://input'), true);
	$channelID = $db->real_escape_string($_POST["channelID"]);
	$lastUpdateTime = $db->real_escape_string($_POST["lastUpdateTime"]);

	$ajaxResult["channelID"] = $channelID;

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

		# check changes in permissions

		# check new messages
		$queryResult = $db->query("call getNewMessages('$channelID', '$lastUpdateTime')");
		if ($queryResult) {
			$newMessages = $queryResult->fetch_all(MYSQLI_ASSOC);

			# record data and break loop if updates are detected
			if (count($newMessages) > 0) {
				$ajaxResult["querySuccess"] = true;
				$ajaxResult["newMessages"] = $newMessages;
				$updatesDetected = true;
			}
		}
		else {
			# indicate if errors occur
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["errorCode"] = $db->errno;
			break;
		}

		# check edited messages

		# check deleted messages

		# check name changes

		# free results
		if (!$updatesDetected) {
			sleep(0.7);
			free_all_results($db);
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