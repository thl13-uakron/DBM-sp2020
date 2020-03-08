<?php
# This script adds a new channel to the database, taking the name, a description and the 
# ID of the room the channel is in, returning the ID of the new channel

# initialize result array
$ajaxResult = array();

# get connection to mysql database
include "dbAccess.php";
$db = get_db_connection();

if ($db) {
	$ajaxResult["sqlConnectSuccess"] = true;

	# read parameters
	$_POST = json_decode(file_get_contents('php://input'), true);
	$channelName = $db->real_escape_string($_POST["channelName"]);
	$description = $db->real_escape_string($_POST["description"]);
	$roomID = $db->real_escape_string($_POST["roomID"]);

	# execute query
	$queryResult = $db->query("call addChannel('$channelName', '$description', '$roomID', @p_channelID)");
	if ($queryResult) {
		$queryResult = $db->query("select @p_channelID");

		# parse query results
		$channelID = $queryResult->fetch_row()[0];

		# record data
		$ajaxResult["querySuccess"] = true;
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