<?php
# This script adds a new room to the database, taking the name and description, along with 
# indicators for whether the room is public and should appear in the list, and returns the
# ID of the new room and the status of the operation

# initialize result array
$ajaxResult = array();

# get connection to mysql database
include "dbAccess.php";
$db = get_db_connection();

if ($db) {
	$ajaxResult["sqlConnectSuccess"] = true;

	# read parameters
	$roomName = $_POST["roomName"];
	$description = $_POST["description"];
	$browsable = $_POST["browsable"];
	$public = $_POST["public"];
	$creatorID = $_POST["roomID"];
	$password = $_POST["password"];

	# execute query
	$queryResult = $db->query("call addRoom('$roomName', '$description', '$browsable', '$public', '$password', '$creatorID', @p_roomID)");
	if ($queryResult) {
		$queryResult = $db->query("select @p_roomID");

		# parse query results
		$roomID = $queryResult->fetch_row()[0];

		# record data
		$ajaxResult["querySuccess"] = true;
		$ajaxResult["roomID"] = $roomID;
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