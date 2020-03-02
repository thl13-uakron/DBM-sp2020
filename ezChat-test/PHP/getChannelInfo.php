<?php
# This script takes the ID of a channel and returns detailed information about it, including the name,
# description, ID of the room it's in, and date and time of creation

# initialize result array
$ajaxResult = array();

# get connection to mysql database
include "dbAccess.php";
$db = get_db_connection();

if ($db) {
	$ajaxResult["sqlConnectSuccess"] = true;

	# read parameters
	$channelID = $_POST["channelID"];

	# execute query
	$queryResult = $db->query("call getChannelInfo('$channelID')");
	if ($queryResult) {
		$channelInfo = $queryResult->fetch_assoc();

		# record data
		$ajaxResult["querySuccess"] = true;
		$ajaxResult["channelInfo"] = json_encode($channelInfo);
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