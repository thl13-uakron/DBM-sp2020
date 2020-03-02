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
	$username = $_POST["username"];
	$password = $_POST["password"];

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
	}
}
else {
	$ajaxResult["sqlConnectSuccess"] = false;
}

# return results
echo json_encode($ajaxResult);

?>