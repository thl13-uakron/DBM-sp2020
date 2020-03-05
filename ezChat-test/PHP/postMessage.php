<?php
# This script adds a new message to the database, taking the username and password of the user posting the message, the
# ID of the channel the message is being posted to, and the content of the message

# initialize result array
$ajaxResult = array();

# get connection to mysql database
include "dbAccess.php";
$db = get_db_connection();

if ($db) {
	$ajaxResult["sqlConnectSuccess"] = true;

	# read parameters
	$username = $_POST["username"];
	$password = $_POST["password"];
	$channelID = $_POST["channelID"];
	$content = $_POST["content"];

	# get user ID
	$queryResult = $db->query("select login('$username', '$password')");
	if ($queryResult) {
		$userID = $queryResult->fetch_row()[0];
		# execute main query
		$queryResult = $db->query("call postMessage('$userID', '$channelID', '$content', @p_messageID)");
		if ($queryResult) {
			# record data
			$ajaxResult["querySuccess"] = true;
		}
		else {
			# indicate if errors occur
			$ajaxResult["querySuccess"] = false;
		}
	}
	else {
		$ajaxResult["querySuccess"] = false;
	}
}
else {
	$ajaxResult["sqlConnectSuccess"] = false;
}

# return data
echo json_encode($ajaxResult);

?>