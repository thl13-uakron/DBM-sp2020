<?php
# This script adds a new user to the database, taking the specified screen name for the user
# and returning the ID of the new user

# initialize result array
$ajaxResult = array();

# get connection to mysql database
include "dbAccess.php";
$db = get_db_connection();

if ($db) {
	$ajaxResult["sqlConnectSuccess"] = true;

	# read parameters
	$_POST = json_decode(file_get_contents('php://input'), true);
	$screenName = $_POST["screenName"];

	# execute query
	if ($db->query("call addUser('$screenName', @p_userID)")) {
		$queryResult = $db->query("select @p_userID");

		# parse query results
		$id = $queryResult->fetch_row()[0];

		# record data
		$ajaxResult["userID"] = $id;
		$ajaxResult["querySuccess"] = true;
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