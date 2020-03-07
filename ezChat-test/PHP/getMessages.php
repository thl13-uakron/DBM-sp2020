<?php
# This script returns the list of messages posted to a channel, taking the ID of that channel

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

	# execute query
	$ajaxResult["updateTime"] = $db->query("select NOW()")->fetch_row()[0];
	$queryResult = $db->query("call getMessages('$channelID')");
	if ($queryResult) {
		$messageList = $queryResult->fetch_all(MYSQLI_ASSOC);

		# record data
		$ajaxResult["querySuccess"] = true;
		$ajaxResult["messageList"] = json_encode($messageList);
		$ajaxResult["channelID"] = $channelID;
	}
	else {
		# indicate if errors occur
		$ajaxResult["querySuccess"] = false;
	}
}
else {
	$ajaxResult["sqlConnectSuccess"] = false;
}

# return data
echo json_encode($ajaxResult);

?>