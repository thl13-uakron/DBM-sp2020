<?php
# This script adds a new room to the database, taking the name and description, along with 
# the userID and password of the creator, indicators for whether the room is publicly accessible and 
# whether it should appear in the list, returning the ID of the new room and the status of the operation

# initialize result array
$ajaxResult = array();

# get connection to mysql database
include "dbAccess.php";
$db = get_db_connection();

if ($db) {
	$ajaxResult["sqlConnectSuccess"] = true;

	# read parameters
	$_POST = json_decode(file_get_contents('php://input'), true);
	$roomName = $_POST["roomName"];
	$description = $_POST["description"];
	$browsable = $_POST["browsable"];
	$public = $_POST["public"];
	$creatorID = $_POST["creatorID"];
	$creatorPassword = $_POST["creatorPassword"];
	$roomPassword = $_POST["roomPassword"];

	# validate ID of creator
	$queryResult = $db->query("select validateUser('$creatorID', '$creatorPassword', false)");
	if ($queryResult && ) {
		# execute main query
		if ($queryResult->fetch_row()[0]) {
			$queryResult = $db->query("call addRoom('$roomName', '$description', '$browsable', '$public', '$roomPassword', '$creatorID', @p_roomID)");
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