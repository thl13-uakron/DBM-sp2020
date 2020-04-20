<?php
# This script takes the username and password specified by someone signing up for the site
# and attempts to create a new account and return the ID of the new account,
# returning NULL if the procedure doesn't work (e.g. username is already taken)

# initialize result array
$ajaxResult = array();

# get connection to mysql database
include "dbAccess.php";
$db = get_db_connection();

if ($db) {
	$ajaxResult["sqlConnectSuccess"] = true;

	# read parameters
	$_POST = json_decode(file_get_contents('php://input'), true);
	$username = $db->real_escape_string($_POST["username"]);
	$password = $db->real_escape_string($_POST["password"]);

	# execute query
	if ($db->query("call addAccount('$username', '$password', @p_userID)")) {
		$queryResult = $db->query("select @p_userID");

		# parse query results
		$userID = $queryResult->fetch_row()[0];

		# record data
		$ajaxResult["userID"] = $userID;
		$ajaxResult["querySuccess"] = true;

		free_all_results($db);
		if ($db->query("call setSession('$userID', @p_sessionID)")) {
			$queryResult = $db->query("select @p_sessionID");

			$sessionID = $queryResult->fetch_row()[0];
			$ajaxResult["sessionID"] = $sessionID;
		}
		else {
			$ajaxResult["querySuccess"] = false;
			$ajaxResult["errorCode"] = $db->errno;
		}
	}
	else {
		# indicate if errors occur
		$ajaxResult["querySuccess"] = false;
	}
}
else {
	$ajaxResult["sqlConnectSuccess"] = false;
}

// Validate data types for ajaxResult
function validateData(el) {
    if (gettype(el["userID"]) != string) {
        return false;
    }
    else if (gettype["querySuccess"] != true){
        return false;
    }
    else {
        return true; 
    }     
}

# return data if data types are valid
if (validateData(ajaxResult)){
    echo json_encode($ajaxResult);
}

?>