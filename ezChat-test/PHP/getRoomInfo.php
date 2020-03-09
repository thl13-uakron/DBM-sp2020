<?php
# This script takes the ID of a room and returns detailed information about it, including the name,
# description, ID and name of the creator, and date and time of creation

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

	$ajaxResult["updateTime"] = $db->query("select NOW()")->fetch_row()[0];

	# execute query
	$queryResult = $db->query("call getRoomInfo('$roomID')");
	if ($queryResult) {
		$roomInfo = $queryResult->fetch_assoc();

		# record data
		$ajaxResult["querySuccess"] = true;
		$ajaxResult["roomInfo"] = $roomInfo;
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