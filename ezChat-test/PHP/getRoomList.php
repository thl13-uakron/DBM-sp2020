<?php
# This script retrieves a publicly browsable list of rooms

# initialize result array
$ajaxResult = array();

# get connection to mysql database
include "dbAccess.php";
$db = get_db_connection();

if ($db) {
	$ajaxResult["sqlConnectSuccess"] = true;

	# execute query
	$queryResult = $db->query("call getRoomList()");
	if ($queryResult) {
		$roomList = $queryResult->fetch_all(MYSQLI_ASSOC);

		# record data
		$ajaxResult["querySuccess"] = true;
		$ajaxResult["roomList"] = json_encode($roomList);
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