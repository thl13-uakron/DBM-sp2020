<?php
# This script takes the ID of a room and returns a list of the names and IDs of the 
# channels associated with it

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

	# execute query
	$queryResult = $db->query("call getChannelsByRoom('$roomID')");
	if ($queryResult) {
		$channelList = $queryResult->fetch_all(MYSQLI_ASSOC);

		# record data
		$ajaxResult["querySuccess"] = true;
		$ajaxResult["channelList"] = $channelList;
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