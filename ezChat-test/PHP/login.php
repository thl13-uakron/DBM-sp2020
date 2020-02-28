<?php
# This script takes a set of login credentials and returns the ID of the user that their associated with
# or NULL if the credentials don't work

# get connection to mysql database
include "dbAccess.php";
$db = get_db_connection();

# read parameters
$username = $_POST["username"];
$password = $_POST["password"];

# execute query
$id = $db->query("select login('$username', '$password')");

# return data
echo $id;

?>