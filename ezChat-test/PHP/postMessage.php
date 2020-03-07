<?php
# This script adds a new message to the database, taking the userID and password of the user posting the message, the
# ID of the channel the message is being posted to, and the content of the message

# initialize result array
$ajaxResult = array();

# get connection to mysql database
include "dbAccess.php";
$db = get_db_connection();

if ($db) {
	$ajaxResult["sqlConnectSuccess"] = true;

	# read parameters
	$_POST = json_decode(file_get_contents('php://input'), true);
	$userID = $_POST["userID"];
	$password = $_POST["password"];
	$channelID = $_POST["channelID"];
	$content = $_POST["content"];

	# validate user ID
	$queryResult = $db->query("select validateUser('$userID', '$password', true)");
	if ($queryResult) {
		if ($queryResult->fetch_row()[0]) {
			# execute main query
			$queryResult = $db->query("call postMessage('$userID', '$channelID', '$content', @p_messageID)");
			if ($queryResult) {
				# record data
				$ajaxResult["querySuccess"] = true;
			}
			else {
				# indicate if errors occur
				$ajaxResult["querySuccess"] = false;
				$ajaxResult["errorCode"] = $db->errno;
			}
		}
		else {
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["errorCode"] = $db->errno;
			$ajaxResult["failReason"] = "failed to validate user";
		}
	}
	else {
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

?>