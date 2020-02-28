<?php
# This script takes a set of login credentials and returns the ID of the user that their associated with
# or NULL if the credentials don't work

# initialize result array
$ajaxResult = array();

# get connection to mysql database
include "dbAccess.php";
$db = get_db_connection();

# read parameters
$username = $_POST["username"];
$password = $_POST["password"];

# execute query
$queryResult = $db->query("select login('$username', '$password')");

# parse query results
$id = $queryResult->fetch_row()[0];

# return data
$ajaxResult["id"] = $id;
echo json_encode($ajaxResult);

?>