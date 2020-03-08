<?php
	/*
	$db_host = "p:sql200.main-hosting.eu";
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
		flex-wrap: nowrap;
		overflow-wrap: all;
		background-color: black;
		color: darkgray;
		font-family: arial;
		line-height: 1.1;
		margin:0;
	}

	button:hover:enabled{
		background-color:gray;
	}

	button:disabled {
		background-color:darkgray;
	}

	.smallText {
		font-size: 0.5em;
	}

	.region {
		display: flex;
		flex-direction: column;
		flex-wrap: nowrap;
		flex-grow: 1;
	}

	.padded {
		padding: 2%;
	}

	.darkGrayText {
		color: darkgray;
	}

	.lightGrayText {
		color: lightgray;
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
	window.addEventListener("unload", 
		function() {
			fetch("PHP/closeConnection.php", {
				method: "POST"
			})
		});

	// functions for retrieving data from backend

	// functions for dynamically generating page content
	function showRoomInfo(roomID) {
		var parent = document.getElementById("roomInfo");
		parent.innerHTML = 
		`
		`;
	}

	function getListedRoomElement(roomInfo) {
		//
	}

	function showDMs(userID, password) {
		var parent = document.getElementById("dmList");
	}
	function showRecentRooms(userID, password) {
		var parent = document.getElementById("recentRooms");
	}
	function showPublicRooms(userID, password) {
		var parent = document.getElementById("allRooms");
		parent.innerHTML = "Loading Room List...";
		fetch("PHP/getRoomList.php", {
			method: "POST",
		})
		.then((response) => response.text())
		.then((data) => {
			console.log(data);
			data = JSON.parse(data);
			
			rooms = data["roomList"];
			parent.innerHTML = `
			<div id="allRoomsHeader" class="padded">
				<b> Public Rooms </b>
			</div>
			<div id="allRoomsBody">
			</div>
			`;

		})
		.catch((error) => console.log(error));
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

	function login(p_userID, p_password) {
		userID = p_userID;
		password = p_password;

		document.cookie = "userID=" + p_userID + "; samesite=strict";
		document.cookie = "password=" + encodeURIComponent(p_password) + "; samesite=strict";

		showPage(userID, password, roomID, channelID);
	}

	function showLogin() {
		parent = document.getElementById("accountOptions");
		parent.innerHTML = `
		<div class="bottomMargin lightGrayText"> Log In: </div>
		<div class="bottomMargin"> Username: <input id='usernameInput' maxlength="24"> </input> </div>
		<div class="bottomMargin"> Password: <input id='passwordInput' type="password" maxlength="64"> </input> </div>
		<div> <button id='loginCancelButton'> Cancel </button> <button id='loginGoButton'> Go </button> </div>
		<div id='loginStatus'> </div>
		`;

		var loginCancelButton = document.getElementById("loginCancelButton");
		var loginGoButton = document.getElementById("loginGoButton");
		var usernameInput = document.getElementById("usernameInput");
		var passwordInput = document.getElementById("passwordInput");
		var loginStatus = document.getElementById("loginStatus");

		loginCancelButton.addEventListener("click", showGuestAccountOptions);
		loginGoButton.disabled = true;
		loginGoButton.addEventListener("click", function() {
			if (usernameInput.value.length > 0 && passwordInput.value.length > 0) {
				p_username = usernameInput.value;
				p_password = passwordInput.value;

				loginStatus.innerHTML = "Logging In...";
				fetch("PHP/login.php", {
					method: "POST",
					body: JSON.stringify({
						username: p_username,
						password: p_password
					})
				})
				.then(response => response.text())
				.then(data => {
					console.log(data);
					data = JSON.parse(data);
					if (data["userID"]) {
						login(data["userID"], p_password);
					}
					else {
						loginStatus.innerHTML = "Invalid username or password";
					}
				})
			}
		})
		parent.addEventListener("keyup", function() {
			if (usernameInput.value.length > 0 && passwordInput.value.length > 0) {
				loginGoButton.disabled = false;
			}
			else {
				loginGoButton.disabled = true;
			}
		})
	}

	function showAccountCreation() {
		parent = document.getElementById("accountOptions");
		parent.innerHTML = `
		<div class="bottomMargin lightGrayText"> Create New Account: </div>
		<div> Username (1-24 characters)
		<br />
		<input id='usernameInput' class="bottomMargin" maxlength="24"> </input> </div>
		<div> Password (1-64 characters, restricted chars: ' ', '.')
		<br />
		<input id='passwordInput' type="password" class='bottomMargin' maxlength="64"> </input> </div>
		<div> Confirm Password
		<br />
		<input id='confirmPassword' type="password" class='bottomMargin' maxlength="64"> </input> </div>
		<div class="bottomMargin"> <button id='signupCancelButton'> Cancel </button> <button id='signupGoButton'> Go </button> </div>
		<div id='signupStatus'> </div>
		`;

		var signupCancelButton = document.getElementById("signupCancelButton");
		var signupGoButton = document.getElementById("signupGoButton");
		var usernameInput = document.getElementById("usernameInput");
		var passwordInput = document.getElementById("passwordInput");
		var confirmPassword = document.getElementById("confirmPassword");
		var signupStatus = document.getElementById("signupStatus");

		var restrictedPasswordRegex = new RegExp(".*[\. ].*");

		signupCancelButton.addEventListener("click", showGuestAccountOptions);
		signupGoButton.disabled = true;
		signupGoButton.addEventListener("click", function() {
			if (usernameInput.value.length > 0 && passwordInput.value.length > 0 && !passwordInput.value.match(restrictedPasswordRegex) && confirmPassword.value == passwordInput.value) {
				var newPassword = passwordInput.value;
				var newUsername = usernameInput.value;

				// attempt to create account
				signupStatus.innerHTML = "Creating Account...";
				fetch("PHP/addAccount.php", {
					method: "POST",
					body: JSON.stringify({
						username: newUsername,
						password: newPassword
					})
				})
				.then(response => response.text())
				.then(data => {
					console.log(data);
					data = JSON.parse(data);

					// assign new account credentials if account successfully created
					if (data["userID"]) {
						login(data["userID"], newPassword);
					}
					else {
						signupStatus.innerHTML = "Unable to create account. Your specified username may have already been taken.";
					}
				})
				.catch(error => console.log(error));
			}
		});
		parent.addEventListener("keyup", function() {
			// check if input fields are valid
			if (usernameInput.value.length > 0 && passwordInput.value.length > 0 && !passwordInput.value.match(restrictedPasswordRegex) && confirmPassword.value == passwordInput.value) {
				signupGoButton.disabled = false;
				signupStatus.innerHTML = "";
			}
			else {
				signupGoButton.disabled = true;
			}
		})
	}

	function showGuestAccountOptions() {
		parent = document.getElementById("accountOptions");
		parent.innerHTML = `
		<button id="createAccountButton"> Create an Account </button>
		<button id="loginButton"> Sign In </button>
		`;

		var createAccountButton = document.getElementById("createAccountButton");
		createAccountButton.addEventListener("click", showAccountCreation);

		var loginButton = document.getElementById("loginButton");
		loginButton.addEventListener("click", showLogin);
	}

	function logout() {
		userID = null;
		password = null;

		document.cookie = "userID=;";
		document.cookie = "password=;";

		location.reload();
	}

	function showSignout() {
		parent = document.getElementById("accountOptions");
		parent.innerHTML = `
		Are you sure you want to log out? 
		<button id="signoutCancelButton"> No </button>
		<button id="signoutGoButton"> Yes </button>
		`;

		signoutGoButton = document.getElementById("signoutGoButton");
		signoutGoButton.addEventListener("click", logout);
		signoutCancelButton = document.getElementById("signoutCancelButton");
		signoutCancelButton.addEventListener("click", function () {
			showRegisteredAccountOptions(userID, password);
		});
	}

	function showRegisteredAccountOptions(userID, password) {
		parent = document.getElementById("accountOptions");
		parent.innerHTML = `
		<button id="signoutButton"> Sign Out </button>
		`;

		var signoutButton = document.getElementById("signoutButton");
		signoutButton.addEventListener("click", showSignout);
	}

	function showAccountInfo(userID, password) {
		var parent = document.getElementById("accountInfo");
		parent.innerHTML = "Loading User Info...";
		fetch ("PHP/getAccountInfo.php", {
			method: "POST", 
			body: JSON.stringify({
				"userID": userID,
				"password": password
			})
		})
		.then(response => response.text())
		.then(data => {
			console.log(data);
			data = JSON.parse(data);

			parent.innerHTML = `
			<div id="accountInfoHeader" class="padded">
				<span class="lightGrayText"> Welcome to ezChat, <b><span id="screenName"> </span></b> </span>
				<br/> 
				Your userID: <span id="userID"> </span>
			</div>
			<div id="accountOptions" class="padded">
			</div>
			`;

			document.getElementById("screenName").innerHTML = data["screenName"];
			document.getElementById("userID").innerHTML = userID;

			if (data["isRegistered"]) {
				// options for registered users
				showRegisteredAccountOptions(userID, password);
			}
			else {
				// options for guests
				showGuestAccountOptions();
			}
		})
		.catch(error => console.log(error));
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
		showRoomInfo(userID, password, roomID, channelID);
		showRoomLists(userID, password);
		showChat(userID, password, channelID);
		showAccountInfo(userID, password);
		showUserLists(userID, password);
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
	password = decodeURIComponent(password);

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
			$free_all_results($db);

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
		free_all_results($db);

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
		free_all_results($db);

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
		.then((response) => response.text())
		.then((data) => {
			console.log(data);
			data = JSON.parse(data);

			userID = data["userID"];
			document.cookie = "userID=" + userID + ";samesite=strict";
			showPage(userID, password, channelID, roomID);
		})
		.catch((error) => console.log(error))
	}
	else {
		// render page content otherwise
		showPage(userID, password, channelID, roomID);
	}

</script>
