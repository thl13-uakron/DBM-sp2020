<?php
# This script takes the username and password specified by someone signing up for the site
# and attempts to create a new account and return the ID of the new account,
# returning NULL if the procedure doesn't work (e.g. username is already taken)

# get connection to mysql database
include "dbAccess.php";
$db = get_db_connection();

# read parameters

?>