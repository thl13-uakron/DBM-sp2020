<?php
# This script retrieves a publicly browsable list of rooms

# initialize result array
$ajaxResult = array();

# get connection to mysql database
include "dbAccess.php";
$db = get_db_connection();

if ($db) {
	$ajaxResult["sqlConnectSuccess"] = true;

	# read parameters
	$_POST = json_decode(file_get_contents('php://input'), true);

	# execute query
	$ajaxResult["updateTime"] = $db->query("select NOW()")->fetch_row()[0];

	$queryResult = $db->query("call getRoomList()");
	if ($queryResult) {
		$roomList = $queryResult->fetch_all(MYSQLI_ASSOC);

		# record data
		$ajaxResult["querySuccess"] = true;
		$ajaxResult["roomList"] = $roomList;
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