<?php
	/*
	$db_host = "sql200.main-hosting.eu";
	$db_name = "u668413793_ezChat";
	$db_user = "u668413793_ezChatDev";
	$db_password = "ezChatDev";

	$db = new mysqli($db_host, $db_user, $db_password, $db_name);
	*/

	include "PHP/dbAccess.php";
	$db = get_db_connection();
?>

<title>
	Welcome to ezChat!
</title>

<style>
	body {
		display: flex;
		flex-direction: row;
		background-color: black;
		color: darkgray;
		font-family: arial;
		line-height: 1.5;
		margin:0;
	}

	.region {
		display: flex;
		flex-direction: column;
		flex-grow: 1;
	}

	.padded {
		padding: 2%;
	}

	.bottomMargin {
		margin-bottom: 2%;
	}

	#messageStream, #roomList, #userList {
		overflow-y: auto;
	}

	#chatBox {
		display: flex;
		background-color: darkgray;
	}

	#centerRegion {
		flex-grow: 5;
		flex-shrink: 4;
		background-color: lightgray;
		color: black;
	}
</style>

<body>
	<div id="leftRegion" class="region">
		<div id="roomInfo" class="padded">
		</div>

		<div id="roomList" class="padded">
		</div>
	</div>

	<div id="centerRegion" class="region">
		
	</div>

	<div id="rightRegion" class="region">
		<div id="accountInfo" class="padded">
		</div>

		<div id="userList" class="padded" style="flex-grow: 2">
		</div>
	</div>
</body>

<script>
	// functions for retrieving data from backend

	// functions for dynamically generating page content
	function showRoomInfo(roomID) {
		var parent = document.getElementById("roomInfo");
		parent.innerHTML = 
		`
		`;
	}

	function showDMs(userID, password) {
		var parent = document.getElementById("dmList");
	}
	function showRecentRooms(userID, password) {
		var parent = document.getElementById("recentRooms");
	}
	function showPublicRooms(userID, password) {
		var parent = document.getElementById("allRooms");
		fetch("PHP/getRoomList.php", {
			method: "POST",
		})
		.then();
	}
	function showRoomLists(userID, password) {
		var parent = document.getElementById("roomList");
		parent.innerHTML = 
		`
		<div id="dmList">
		</div>
		<div id="recentRooms">
		</div>
		<div id="allRooms">
		</div>
		`;
		showDMs(userID, password);
		showRecentRooms(userID, password);
		showPublicRooms(userID, password);
	}

	function showAccountInfo(userID, password) {
		var parent = document.getElementById("accountInfo");
		/*
		fetch ("PHP/", {
			method: "POST", 
			body: JSON.stringify({})
		})
		.then()
		*/
	}

	function showChat(userID, password, channelID) {
		var parent = document.getElementById("centerRegion");
		parent.innerHTML = 
		`
		<div id="chatRegion" class="region">
			<div id="messageStream" class="padded" style="flex-grow: 5">
			</div>
			<div id="chatBox" class="padded" style="flex-grow: 1; display: flex">
				<textarea style="flex-grow: 1"></textarea>
			</div>
		</div>
		`;
	}

	function showUserLists(userID, password) {
		var parent = document.getElementById("userList");
		parent.innerHTML =
		`
		`;
	}

	function showPage(userID, password, channelID, roomID) {
		showRoomInfo(userID, password, roomID);
		showRoomLists(userID, password);
		showChat(userID, password, channelID);
		// showAccountInfo(username, password);
		showUserLists(userID, password);
	}

	function showLogin() {
		parent = document.getElementById("loginPanel");
		parent.innerHTML = `
		<div> <b> Login </b> </div>
		<div> Username: <input id='usernameInput'> </input> </div>
		<div> Password: <input id='passwordInput'> </input> </div>
		<div> <button id='loginCancelButton' value='Cancel' /> <button id='loginButton' value='Go' /> </div>
		<div id='loginStatus'> </div>
		`;
	}

	// get client parameters
	var userID = `<?php
		$userID = null;
		# get userID from cookie if present
		if (array_key_exists("userID", $_COOKIE)) {
			$userID = $_COOKIE["userID"];
		}
		echo $userID;
	?>`;

	var password = `<?php
		$password = null;
		# get password from cookie if present
		if (array_key_exists("password", $_COOKIE)) {
			$password = $_COOKIE["password"];
		}
		echo $password;
	?>`;

	var channelID = `<?php
		function isValidChannel($db, $channelID) {
			$queryResult = $db->query("call getChannelInfo('$channelID')");
			return count($queryResult->fetch_all()) > 0;
		}
		$channelID = "null";
		# get channel ID from GET parameter
		if (array_key_exists("channelID", $_GET)) {
			$channelID = $_GET["channelID"];
			# check if ID is valid
			if (!isValidChannel($db, $channelID)) {
				$channelID = "null";
			}
		}
		# get channel ID from cookie if GET parameter not present or invalid
		if (!$channelID && (array_key_exists("channelID", $_COOKIE))) {
			# reset db connection
			$db->close();
			$db = get_db_connection();

			$channelID = $_COOKIE["channelID"];
			if (!isValidChannel($db, $channelID)) {
				$channelID = "null";
			}
		}
		echo $channelID;
	?>`;

	var roomID = `<?php
		function isValidRoom($db, $roomID) {
			$queryResult = $db->query("call getRoomInfo('$roomID')");
			return count($queryResult->fetch_all()) > 0;
		}
		# reset db connection
		$db->close();
		$db = get_db_connection();

		$roomID = "null";
		# get room ID from channel ID is present
		if ($channelID) {
			$queryResult = $db->query("call getChannelInfo('$channelID')");
			if ($queryResult) {
	 			$roomID = $queryResult->fetch_assoc()["roomID"];
			}
		}
		else {
			# get from GET parameter otherwise (leave null if no room ID associated with channel)
			if (array_key_exists("roomID", $_GET)) {
				$roomID = $_GET["roomID"];
				# check if ID is valid
				if (!isValidRoom($db, $roomID)) {
					$channelID = "null";
				}
			}
			# set to default room if both roomID and channelID are null
			else {
				$roomID = 2;
			}
		}

		# reset db connection
		$db->close();
		$db = get_db_connection();

		echo $roomID;
	?>`;

	// assign guest ID if current ID/password not valid
	if (!Number(`<?php echo ($userID != null && $db->query("select validateUser('$userID', '$password', true)")->fetch_array()[0]) ?>`)) {
		fetch("PHP/addUser.php", {
			method: "POST",
			headers: {
				"Content-Type": "application/json",
				"Accept": "application/json"
			},
			body: JSON.stringify({
				"screenName": "guest"
			})
		})
		.then((response) => response.json())
		.then((data) => {
			userID = data["userID"];
			document.cookie = "userID=" + userID;
			showPage(userID, password, channelID, roomID);
		})
		.catch((error) => console.log(error))
	}
	else {
		// render page content otherwise
		showPage(userID, password, channelID, roomID);
	}

</script>
