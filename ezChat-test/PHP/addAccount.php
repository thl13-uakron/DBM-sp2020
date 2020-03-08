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