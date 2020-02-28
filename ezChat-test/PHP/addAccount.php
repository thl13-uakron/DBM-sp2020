<?php
# This script takes the username and password specified by someone signing up for the site
# and attempts to create a new account and return the ID of the new account,
# returning NULL if the procedure doesn't work (e.g. username is already taken)

# initialize result array
$ajaxResult = array();

# get connection to mysql database
include "dbAccess.php";
$db = get_db_connection();

# read parameters
$username = $_POST["username"];
$password = $_POST["password"];

# execute query
$db->query("call addAccount('$username', '$password', @p_userID)");
$queryResult = $db->query("select @p_userID");

# parse query results
$id = $queryResult->fetch_row()[0];

# return data
$ajaxResult["id"] = $id;
echo json_encode($ajaxResult);

?>