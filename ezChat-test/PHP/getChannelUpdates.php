<?php
# This script returns the list of new messages, along with edited and deleted and any changes to the channel settings,
# posted to a channel since the previous update, taking the ID of the channel and the time of the previous update

# ignore_user_abort(true);

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
	$sessionID = $db->real_escape_string($_POST["sessionID"]);

	$ajaxResult["channelID"] = $channelID;

	$queryResult = $db->query("select getSessionUser('$sessionID')");
	$userID = $queryResult->fetch_row()[0];
	free_all_results($db);

	# log user presence
	$queryResult = $db->query("call logChannelVisit('$userID', '$channelID')");
	free_all_results($db);

	# poll for updates
	$updatesDetected = false;
	# $iterations = 0;
	# set_time_limit(30);
	while (!$updatesDetected) {
		echo '';
		ob_flush();
		flush();
		if (connection_aborted() || connection_status() != CONNECTION_NORMAL) {
			$db->close();
			exit();
		}

		# record current time
		$queryResult = $db->query("select NOW()");
		if (!$queryResult) {
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["errorCode"] = $db->errno;
			break;
		}
		$ajaxResult["updateTime"] = $queryResult->fetch_array()[0];

		# check changes in permissions

		# check new and updated messages 
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

		# check deleted messages
		free_all_results($db);
		$queryResult = $db->query("call getMessageDeletions('$channelID', '$lastUpdateTime')");
		if ($queryResult) {
			$deletedMessages = $queryResult->fetch_all(MYSQLI_ASSOC);

			# record data and break loop if updates are detected
			$count = count($deletedMessages);
			if ($count > 0) {
				$ajaxResult["querySuccess"] = true;
				$ajaxResult["deletedMessages"] = array();
				for ($i = 0; $i < $count; ++$i) {
					$ajaxResult["deletedMessages"][$deletedMessages[$i]["messageID"]] = ["deleteTime" => $deletedMessages[$i]["deleteTime"]];
				}
				$updatesDetected = true;
			}
		}
		else {
			# indicate if errors occur
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["errorCode"] = $db->errno;
			break;
		}

		# check name changes
		free_all_results($db);
		if (connection_aborted()) {
			$db->close();
			exit();
		}
		$queryResult = $db->query("call getNameChanges('$channelID', '$lastUpdateTime')");
		if ($queryResult) {
			$nameChanges = $queryResult->fetch_all(MYSQLI_ASSOC);

			$count = count($nameChanges);
			if ($count > 0) {
				$ajaxResult["nameChanges"] = array();
				for ($i = 0; $i < $count; ++$i) {
					$ajaxResult["nameChanges"][$nameChanges[$i]["userID"]] = $nameChanges[$i]["screenName"];
				}
				$updatesDetected = true;
			}
		}
		else {
			# indicate if errors occur
			$ajaxResult["errorCode"] = $db->errno;
		}

		# free results
		if (!$updatesDetected) {
			echo '';
			ob_flush();
			flush();
			free_all_results($db);
			if (connection_aborted()) {
				$db->close();
				exit();
			}
			sleep(0.7);
		}
		# ++$iterations;
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