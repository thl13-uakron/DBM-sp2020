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
	$_POST = json_decode(file_get_contents('php://input'), true);
	$channelID = $db->real_escape_string($_POST["channelID"]);

	# execute query
	$queryResult = $db->query("call getChannelInfo('$channelID')");
	if ($queryResult) {
		$channelInfo = $queryResult->fetch_assoc();

		# record data
		$ajaxResult["querySuccess"] = true;
		$ajaxResult["channelInfo"] = $channelInfo;

		free_all_results($db);
		$queryResult = $db->query("call getChannelPermissionSettings('$channelID')");
		if (!$queryResult) {
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["errorCode"] = $db->error;
			exit(json_encode($ajaxResult));
		}
		$permissionSettings = $queryResult->fetch_all(MYSQLI_ASSOC);
		$count = count($permissionSettings);
		$ajaxResult["permissionSettings"] = array();
		for ($i = 0; $i < $count; ++$i) {
			$ajaxResult["permissionSettings"][$permissionSettings[$i]["permissionName"]] = $permissionSettings[$i];
		}
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
$db->close();

?>