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
	$channelID = $_POST["channelID"];
	$lastUpdateTime = $_POST["lastUpdateTime"];

	$ajaxResult["channelID"] = $channelID;

	# poll for updates
	while (1) {
		$ajaxResult["updateTime"] = $db->query("select NOW()")->fetch_array()[0];

		# new messages
		$queryResult = $db->query("call getMessages('$channelID')");
		if ($queryResult) {
			$newMessages = $queryResult->fetch_all(MYSQLI_ASSOC);

			# record data and break loop if updates are detected
			if (count($newMessages) > 0) {
				$ajaxResult["querySuccess"] = true;
				$ajaxResult["newMessages"] = json_encode($newMessages);
				break;
			}
			
		}
		else {
			# indicate if errors occur
			$ajaxResult["querySuccess"] = false;
			break;
		}
	}
}
else {
	$ajaxResult["sqlConnectSuccess"] = false;
}

# return data
echo json_encode($ajaxResult);

?>