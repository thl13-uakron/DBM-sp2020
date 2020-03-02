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
	$roomID = $_POST["roomID"];

	# execute query
	$queryResult = $db->query("call getChannelsByRoom('$roomID')");
	if ($queryResult) {
		$roomInfo = $queryResult->fetch_all(MYSQLI_ASSOC);

		# record data
		$ajaxResult["querySuccess"] = true;
		$ajaxResult["roomInfo"] = json_encode($roomInfo);
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