<?php 
# this script allows the user to appoint a user as a moderator or administrator in a room
# taking the roomID, the id of the user to appoint, the appointing user's sessionID, and 
# the moderation rank (0 for mods, 1 for admins, null to set someone back to a regular user),
# checking first if the user has permission to do this
$ajaxResult = array();

# get connection to mysql database
include "dbAccess.php";
$db = get_db_connection();

if ($db) {
	$ajaxResult["sqlConnectSuccess"] = true;

	# read parameters
	$_POST = json_decode(file_get_contents('php://input'), true);
	$sessionID = $db->real_escape_string($_POST["sessionID"]);
	$moderatorID = $db->real_escape_string($_POST["moderatorID"]);
	$rank = $db->real_escape_string($_POST["rank"]);
	$roomID = $db->real_escape_string($_POST["roomID"]);

	# get user ID
	$queryResult = $db->query("select getSessionUser('$sessionID')");
	if (!$queryResult) {
		# handle errors
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["errorCode"] = $db->errno;
		exit(json_encode($ajaxResult));
	}
	$userID = $queryResult->fetch_row()[0];
	free_all_results($db);

	# check if new moderator is registered
	$queryResult = $db->query("select isRegistered('$moderatorID')");
	if (!$queryResult) {
		# handle errors
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["errorCode"] = $db->errno;
		exit(json_encode($ajaxResult));
	}
	if (!$queryResult->fetch_row()[0]) {
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["failReason"] = "No registered user with ID $moderatorID";
		exit(json_encode($ajaxResult));
	}
	free_all_results($db);

	# check permissions
	$queryResult = $db->query("select hasRoomPermission('$userID', '$roomID', 10)");
	if (!$queryResult) {
		# handle errors
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["errorCode"] = $db->errno;
		exit(json_encode($ajaxResult));
	}
	$canAppointModerators = $queryResult->fetch_row()[0];
	free_all_results($db);

	$queryResult = $db->query("select hasRoomPermission('$userID', '$roomID', 11)");
	if (!$queryResult) {
		# handle errors
		$ajaxResult["querySuccess"] = false;
		$ajaxResult["errorCode"] = $db->errno;
		exit(json_encode($ajaxResult));
	}
	$canAppointAdministrators = $queryResult->fetch_row()[0];
	free_all_results($db);

	# removals
	if ($rank == null || $rank == "null") {
		$queryResult = $db->query("select getModerationRank('$userID', '$roomID')");
		if (!$queryResult) {
			# handle errors
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["errorCode"] = $db->error;
			exit(json_encode($ajaxResult));
		}
		$currentRank = $queryResult->fetch_row()[0];

		if ($currentRank == 0 && !$canAppointModerators) {
			# removing mods
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["failReason"] = "Permission to remove moderator not granted";
			exit(json_encode($ajaxResult));
		}

		if ($currentRank == 1 && !$canAppointAdministrators) {
			# removing admins
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["failReason"] = "Permission to remove administrator not granted";
			exit(json_encode($ajaxResult));
		}

		if ($currentRank != null) {
			$queryResult = $db->query("call removeModerator('$moderatorID', '$roomID')");
			if (!$queryResult) {
				# handle errors
				$ajaxResult["querySuccess"] = false;
				$ajaxResult["errorCode"] = $db->error;
				exit(json_encode($ajaxResult));
			}
		}

		$ajaxResult["querySuccess"] = true;
		exit(json_encode($ajaxResult));
	}

	# mod appointments
	if ($rank == 0) {
		if (!$canAppointModerators) {
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["failReason"] = "Permission to appoint moderator not granted";
			exit(json_encode($ajaxResult));
		}
		$queryResult = $db->query("call addModerator('$moderatorID', '$roomID')");
		if (!$queryResult) {
			# handle errors
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["errorCode"] = $db->error;
			exit(json_encode($ajaxResult));
		}
		$ajaxResult["querySuccess"] = true;
		exit(json_encode($ajaxResult));
	}

	# admin appointments
	if ($rank == 1) {
		if (!$canAppointAdministrators) {
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["failReason"] = "Permission to appoint administrator not granted";
			exit(json_encode($ajaxResult));
		}
		$queryResult = $db->query("call addAdministrator('$moderatorID', '$roomID')");
		if (!$queryResult) {
			# handle errors
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["errorCode"] = $db->error;
			exit(json_encode($ajaxResult));
		}
		$ajaxResult["querySuccess"] = true;
		exit(json_encode($ajaxResult));
	}
}
else {
	$ajaxResult["sqlConnectSuccess"] = false;
}

# return data
echo json_encode($ajaxResult);
?>