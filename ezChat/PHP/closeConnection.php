<?php
# get connection to mysql database
include "dbAccess.php";
$db = get_db_connection();

# close connection
$db->close();
?>