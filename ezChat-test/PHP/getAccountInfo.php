<?php
# This script takes the userID and password of a user and returns the information associated with their
# account, as well as their screen name and whether or not they're registered

# initialize result array
$ajaxResult = array();

# get connection to mysql database
include "dbAccess.php";
$db = get_db_connection();

if ($db) {
	$ajaxResult["sqlConnectSuccess"] = true;

	# read parameters
	$_POST = json_decode(file_get_contents('php://input'), true);
	$userID = $db->real_escape_string($_POST["userID"]);
	$password = $db->real_escape_string($_POST["password"]);

	# validate user ID
	$queryResult = $db->query("select validateUser('$userID', '$password', true)");
	if ($queryResult) {
		if ($userID != null && $queryResult->fetch_row()[0]) {
			# get account info
			$queryResult = $db->query("call getAccountInfo('$userID', '$password')");
			if ($queryResult) {
				# record data
				$ajaxResult["querySuccess"] = true;
				$ajaxResult["accountInfo"] = $queryResult->fetch_assoc();

				# determine if user is registered
				if (!$ajaxResult["accountInfo"]) {
					$ajaxResult["isRegistered"] = false;
				}
				else {
					$ajaxResult["isRegistered"] = true;
				}

				# get screen name
				free_all_results($db);
				$queryResult = $db->query("select getScreenName('$userID')");
				if ($queryResult) {
					$ajaxResult["screenName"] = $queryResult->fetch_row()[0];
				}
				else {
					$ajaxResult["querySuccess"] = false;
					$ajaxResult["errorCode"] = $db->errno;
				}
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
#$db->close();

?>