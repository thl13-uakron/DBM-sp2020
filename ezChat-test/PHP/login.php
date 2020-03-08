<?php
# This script takes a set of login credentials and returns the ID of the user that their associated with
# or NULL if the credentials don't work

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
	$queryResult = $db->query("select login('$username', '$password')"); 
	if ($queryResult) {
		# parse query results
		$id = $queryResult->fetch_row()[0];

		# record data
		$ajaxResult["querySuccess"] = true;
		$ajaxResult["userID"] = $id;
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

# return results
echo json_encode($ajaxResult);
#$db->close();

?>