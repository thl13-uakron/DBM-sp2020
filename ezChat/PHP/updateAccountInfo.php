<?php
# this script allows a user to update their screen name, account name, password, or email,
# taking the new values for these fields as parameters along with the user's session ID

# initialize result array
$ajaxResult = array();

# get connection to mysql database
include "dbAccess.php";
$db = get_db_connection();

if ($db) {
	$ajaxResult["sqlConnectSuccess"] = true;

	# read parameters
	$_POST = json_decode(file_get_contents('php://input'), true);
	$sessionID = $db->real_escape_string($_POST["sessionID"]);
	$newScreenName = $db->real_escape_string($_POST["newScreenName"]);
	$newAccountName = $db->real_escape_string($_POST["newAccountName"]);
	$currentPassword = $db->real_escape_string($_POST["currentPassword"]);
	$newPassword = $db->real_escape_string($_POST["newPassword"]);

	# get userID from session ID
	$queryResult = $db->query("select getSessionUser('$sessionID')");
	if (!$queryResult) {
		# handle errors
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["errorCode"] = $db->errno;
		$ajaxResult["failReason"] = $db->error;
		exit(json_encode($ajaxResult));
	}
	$userID = $queryResult->fetch_row()[0];
	free_all_results($db);

	if (!$userID) {
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["failReason"] = "Failed to validate user session";
		exit(json_encode($ajaxResult));
	}

	$queryResult = $db->query("select validateUser('$userID', '$currentPassword', false)");
	if (!$queryResult) {
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["errorCode"] = $db->errno;
		$ajaxResult["failReason"] = $db->error;
		exit(json_encode($ajaxResult));
	}
	if (!$queryResult->fetch_row()[0]) {
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["failReason"] = "Invalid password";
		exit(json_encode($ajaxResult));
	}
	free_all_results($db);

	if (!$newScreenName) {
		$queryResult = $db->query("select getScreenName('$userID')");
		if (!$queryResult) {
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["errorCode"] = $db->errno;
			$ajaxResult["failReason"] = $db->error;
			exit(json_encode($ajaxResult));
		}
		$newScreenName = $queryResult->fetch_row()[0];
		free_all_results($db);
	}

	$queryResult = $db->query("call getAccountInfo('$userID')");
	if (!$queryResult) {
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["errorCode"] = $db->errno;
		$ajaxResult["failReason"] = $db->error;
		exit(json_encode($ajaxResult));
	}
	$accountInfo = $queryResult->fetch_assoc();
	free_all_results($db);

	if (!$newAccountName) {
		$newAccountName = $accountInfo["accountName"];
	}

	$newEmail = $accountInfo["email"];

	if (!$newPassword) {
		$newPassword = $currentPassword;
	}

	$queryResult = $db->query("call updateAccountInfo('$userID', '$newAccountName', '$newPassword', '$newEmail')");
	if (!$queryResult) {
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["errorCode"] = $db->errno;
		if ($ajaxResult["errorCode"] == 1062) {
			$ajaxResult["failReason"] = "Specified account name has already been taken";
		}
		exit(json_encode($ajaxResult));
	}
	free_all_results($db);

	$queryResult = $db->query("call updateScreenName('$userID', '$newScreenName')");
	if (!$queryResult) {
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["errorCode"] = $db->errno;
		$ajaxResult["failReason"] = $db->error;
	}

	$ajaxResult["querySuccess"] = true;
}
else {
	$ajaxResult["sqlConnectSuccess"] = false;
	$ajaxResult["errorCode"] = $db->errno;
	$ajaxResult["failReason"] = $db->error;
}

# return data
echo json_encode($ajaxResult);

?>