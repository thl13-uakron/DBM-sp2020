<?php
# This script adds a new user to the database, taking the specified screen name for the user
# and returning the ID of the new user

# initialize result array
$ajaxResult = array();

# get connection to mysql database
include "dbAccess.php";
$db = get_db_connection();

# read parameters
$screenName = $_POST["screenName"];

# execute query
$db->query("call addUser('$screenName', @p_userID)");
$queryResult = $db->query("select @p_userID");

# parse query results
$id = $queryResult->fetch_row()[0];

# return data
$ajaxResult["id"] = $id;
echo json_encode($ajaxResult);

?>