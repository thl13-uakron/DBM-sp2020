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
		overflow-wrap: break-word;
		background-color: black;
		color: darkgray;
		font-family: arial;
		font-size: 15px;
		line-height: 1.2;
		margin:0;
	}

	button:hover:enabled{
		background-color:gray;
		cursor:pointer;
	}

	button:disabled {
		background-color:darkgray;
	}

	textarea {
		font-family: arial;
	}

	.message:hover {
		background-color: #E8E8E8;
	}

	.clickableListing:hover {
		background-color:#202020;
		cursor:pointer;
	}

	.selectedListing {
		background-color:#303030;
		font-weight: bold;
	}

	.smallText {
		font-size: smaller;
	}

	.boldText {
		font-weight: bold;
	}

	.normalText {
		font-weight: normal;
	}

	.leftFlexAlign {
		align-self: flex-end;
	}

	.region {
		display: flex;
		flex-direction: column;
		flex-wrap: nowrap;
		flex-grow: 1;
	}

	.columnFlex {
		display: flex; 
		flex-direction: column;
	}

	.rowFlex {
		flex-grow: 1;
		display: flex;
		flex-direction: row;
	}

	.padded {
		padding: 5px;
	}

	.darkGrayText {
		color: darkgray;
	}

	.lightGrayText {
		color: lightgray;
	}

	.silverText {
		color: silver;
	}

	.grayText {
		color: gray;
	}

	.dimGrayText {
		color: dimgray;
	}

	.bottomMargin {
		margin-bottom: 5px;
	}

	#messageStream, #roomList, #userList {
		overflow-y: auto;
		box-sizing: border-box;
	}

	#messageStream {
		height: 80%;
		bottom: 20%;
		font-size: 14px;
	}

	#roomCreationPanel, #messageStream {
		position:fixed;
		overflow-y: auto;
		width: 60%;
	}

	#chatBox {
		position:fixed;
		box-sizing: border-box;
		width: 60%;
		bottom: 0;
		display: flex;
		background-color: #C0C0C0;
		max-height: 20%;
		height: 20%;
		min-height: 20%;
	}

	#centerRegion {
		max-height:100%;
		width: 60%;
		background-color: #F0F0F0;
		color: black;
	}

	#leftRegion, #rightRegion {
		width: 20%;
		bottom: 0%;
	}

	#leftScrollbox, #rightScrollbox {
		width: 20%;
		height: 100%;
		position:fixed;
		box-sizing: border-box;
		overflow-y: auto;
	}
</style>

<body>
	<div id="leftRegion" class="region">
		<div id="leftScrollbox">
			<div id="roomInfo" class="padded bottomMargin">
			</div>

			<div id="roomList" class="padded">
			</div>
		</div>
	</div>

	<div id="centerRegion" class="region">
		
	</div>

	<div id="rightRegion" class="region">
		<div id="rightScrollbox">
			<div id="accountInfo" class="padded bottomMargin">
			</div>

			<div id="userList" class="padded" style="flex-grow: 2">
			</div>
		</div>
	</div>
</body>

<script>

	function closeConnection() {
		fetch("PHP/closeConnection.php", {
			method: "POST"
		})
	}

	window.addEventListener("unload", closeConnection);

	window.addEventListener("reload", closeConnection);

	var defaultRoomID = 2;
	var cachedRoomInfo = {};
	var cachedMessages = {};

	var channelUpdateController = new AbortController();
	var roomInfoController = new AbortController();
	var roomListUpdateController = new AbortController();

	function abortChannelFetches() {
		channelUpdateController.abort();
		channelUpdateController = new AbortController();

		roomInfoController.abort();
		roomInfoController = new AbortController();
	}

	// helper function for preventing script injections, copied from https://stackoverflow.com/questions/6234773/can-i-escape-html-special-chars-in-javascript
	function escapeHTML(unsafe) {
	    return unsafe
	         .replace(/&/g, "&amp;")
	         .replace(/</g, "&lt;")
	         .replace(/>/g, "&gt;")
	         .replace(/"/g, "&quot;")
	         .replace(/'/g, "&#039;");
	 }

	// helper function to convert mySQL timestamps to the client's local time, copied from https://stackoverflow.com/questions/3075577/convert-mysql-datetime-stamp-into-javascripts-date-format
	function convertDateTime(dateTime) {
		var t = dateTime.split(/[- :]/);

		// Apply each element to the Date function
		var d = new Date(Date.UTC(t[0], t[1]-1, t[2], t[3], t[4], t[5]));

		return d.toDateString() + " at " + d.toLocaleTimeString();
	}

	// functions for retrieving data from backend and dynamically generating page content
	function getListedChannelElement(channelInfo, roomID, currentChannelID) {
		var element = document.createElement("div");
		var elementID = "channel" + channelInfo["channelID"];
		element.id = elementID;
		element.className = "channelListing padded";
		element.dataset.channelID = channelInfo["channelID"];

		element.innerHTML = `
		<span id="`+elementID+`"channelName" class="lightGrayText">` + escapeHTML(channelInfo["channelName"]) + `</span> 
		<span class="normalText">(channelID: <span id="`+elementID+`channelID">` + channelInfo["channelID"] + `</span>)</span>
		`;

		if (channelInfo["channelID"] == currentChannelID) {
			element.className += " selectedListing";
		}
		else {
			element.className += " clickableListing";
			
		}

		if (channelInfo["description"]) {
			element.innerHTML += `
			<br />
			<span class="normalText smallText" id="`+elementID+`"description">
			`+channelInfo["description"]+`
			</span>
			`
		}

		element.addEventListener("click", function() {
			channelID = channelInfo["channelID"];
			enterChannel(roomID, channelID);
		});

		return element;
	}

	function showChannels(channelList, roomID) {
		if (document.getElementById("roomInfo").dataset.roomID == roomID) {
			var channelListBody = document.getElementById("channelListBody");
			for (i in channelList) {
				var channelElement = document.getElementById("channel" + channelList[i]["channelID"]);
				if (!channelElement) {
					channelListBody.appendChild(getListedChannelElement(channelList[i], roomInfo, channelID));
				}
				else {
					channelElement = getListedChannelElement(channelList[i], roomInfo, channelID);
				}
			}
		}
	}

	function getRoomInfoUpdates(roomID, channelID, lastUpdateTime) {
		//
	}

	function showRoomInfo(roomID, channelID) {
		var parent = document.getElementById("roomInfo");
		roomInfoController = new AbortController();
		roomInfoSignal = roomInfoController.signal;
		fetch("PHP/getChannelInfo.php", {
			method: "POST",
			body: JSON.stringify({
				//userID: userID,
				//password: password,
				sessionID: sessionID,
				channelID: channelID,
			}),
			signal: roomInfoSignal
		})
		.then(response => response.text())
		.then(data => {
			console.log(data);
			data = JSON.parse(data);

			channelInfo = data["channelInfo"];
			if (channelInfo) {
				roomID = channelInfo["roomID"];
				if (roomID) {
					// update room List
					var previousRoomListing = document.getElementsByClassName("roomListing selectedListing")[0];
					if (previousRoomListing && previousRoomListing.dataset.roomID != roomID) {
						previousRoomListing.className = previousRoomListing.className.replace('selectedListing', "clickableListing");
						var roomListing = document.getElementById("room" + roomID);
						if (roomListing) {
							roomListing.className = roomListing.className.replace("clickableListing", "selectedListing");
						}
					}

					// display room info if not already displayed
					if (parent.dataset.roomID != roomID) {
						parent.dataset.roomID = roomID;
						if (!cachedRoomInfo[roomID]) {
							fetch("PHP/getRoomInfo.php", {
								method: "POST",
								body: JSON.stringify({
									// userID: userID,
									// password: password,
									sessionID: sessionID,
									roomID: roomID 
								}),
								signal: roomInfoSignal
							})
							.then(response => response.text())
							.then(data => {
								console.log(data);
								data = JSON.parse(data);

								roomInfo = data["roomInfo"];
								updateTime = data["updateTime"];
								cachedRoomInfo[roomID] = {"roomInfo": roomInfo, "updateTime": updateTime};
								parent.innerHTML = `
								<div id="roomInfoHeader" class="padded">
									<span class="lightGrayText"><b> Current Room: <span id="roomNameElement"></span></b> </span> 
									(roomID: <span id="roomIDElement"> </span>)
									<br />
									<i><span class="smallText grayText"> Created by <span id="creatorNameElement"></span> (userID: <span id="creatorIDElement"></span>) 
									on <span id="creationDateElement"></span> </span></i>
								</div>
								<div id="roomDescriptionElement"> </div>
								<div id="channelListElement"> 
									<div id="channelListHeader" class="padded silverText"> <b> Channels in this Room </b> </div>
									<div id="channelListBody" class="bottomMargin"> </div>
								</div>
								`;

								document.getElementById("roomNameElement").innerHTML = escapeHTML(roomInfo["roomName"]);
								document.getElementById("roomIDElement").innerHTML = roomID;
								document.getElementById("creatorNameElement").innerHTML = escapeHTML(roomInfo["creatorName"]);
								document.getElementById("creatorIDElement").innerHTML = roomInfo["creatorID"];
								document.getElementById("creationDateElement").innerHTML = convertDateTime(roomInfo["creationDate"]);

								if (roomInfo["description"]) {
									var roomDescriptionElement = document.getElementById("roomDescriptionElement");
									roomDescriptionElement.className = "padded bottomMargin";
									roomDescriptionElement.innerHTML = escapeHTML(roomInfo["description"])
								}

								fetch("PHP/getChannelsByRoom.php", {
									method: "POST",
									body: JSON.stringify({
										roomID: roomID 
									})
								})
								.then(response => response.text())
								.then(data => {
									console.log(data);
									data = JSON.parse(data);

									channelList = data["channelList"];
									cachedRoomInfo[roomID]["channelList"] = channelList;
									showChannels(channelList, roomID);

									// poll for future updates
									getRoomInfoUpdates(roomID, channelID, updateTime);
								})
								.catch(error => console.log(error));

							})
							.catch(error => console.log(error));
						}
						else {
							// get room info from memory if already stored there
							roomInfo = cachedRoomInfo[roomID]["roomInfo"];
							channelList = cachedRoomInfo[roomID]["channelList"];
							updateTime = cachedRoomInfo[roomID]["updateTime"];

							parent.innerHTML = `
							<div id="roomInfoHeader" class="padded">
								<span class="lightGrayText"><b> Current Room: <span id="roomNameElement"></span></b> </span> 
								(roomID: <span id="roomIDElement"> </span>)
								<br />
								<i><span class="smallText grayText"> Created by <span id="creatorNameElement"></span> (userID: <span id="creatorIDElement"></span>) 
								on <span id="creationDateElement"></span> </span></i>
							</div>
							<div id="roomDescriptionElement"> </div>
							<div id="channelListElement"> 
								<div id="channelListHeader" class="padded silverText"> <b> Channels in this Room </b> </div>
								<div id="channelListBody" class="bottomMargin"> </div>
							</div>
							`;

							document.getElementById("roomNameElement").innerHTML = escapeHTML(roomInfo["roomName"]);
							document.getElementById("roomIDElement").innerHTML = roomID;
							document.getElementById("creatorNameElement").innerHTML = escapeHTML(roomInfo["creatorName"]);
							document.getElementById("creatorIDElement").innerHTML = roomInfo["creatorID"];
							document.getElementById("creationDateElement").innerHTML = convertDateTime(roomInfo["creationDate"]);

							if (roomInfo["description"]) {
								var roomDescriptionElement = document.getElementById("roomDescriptionElement");
								roomDescriptionElement.className = "padded bottomMargin";
								roomDescriptionElement.innerHTML = escapeHTML(roomInfo["description"])
							}

							showChannels(channelList, roomID);

							// poll for future updates
							getRoomInfoUpdates(roomID, channelID, updateTime);
						}
					}
					else {
						// update channel list if already in room
						var previousChannelListing = document.getElementsByClassName("channelListing selectedListing")[0];
						if (previousChannelListing && previousChannelListing.dataset.channelID != channelID) {
							previousChannelListing.className = previousChannelListing.className.replace('selectedListing', "clickableListing");
							var channelListing = document.getElementById("channel" + channelID);
							if (channelListing) {
								channelListing.className = channelListing.className.replace("clickableListing", "selectedListing");
							}
						}
					}
				}
				else {
					// handle DM channels that are independent of rooms
				}
			}
			else {
				if (!roomID) roomID = defaultRoomID;
				enterRoom(roomID);
			}
		})
	}

	function showRoomCreation() {
		var parent = document.getElementById("centerRegion");
		channelUpdateController.abort();
		channelUpdateController = new AbortController;
		parent.innerHTML = `
		<div id="roomCreationPanel" class="padded" style="overflow-y: auto">
			<div class="padded boldText">
				Create a New Room
			</div>
			<div class="padded">
				Room Name (1-32 characters): <input id="newRoomName" maxlength=32> </input>
			</div>
			<div class="padded">
				Room Description (max 128 characters, optional): 
				<br />
				<textarea id="newRoomDescription" maxlength=128></textarea>
			</div>
			<div class="padded">
				<input type="radio" name="newRoomAccess" id="roomIsBrowsable" checked> Room appears in public list </input>
				<br />
				<input type="radio" name="newRoomAccess" id="roomIsNotBrowsable"> Room is only accessible by URL </input>
			</div>
			<div class="padded">
				<button id="roomCreationBackButton"> Go Back </button>
				<button id="roomCreationGoButton"> Create Room </button>
			</div>
			<div class="padded" id="roomCreationStatus">
			</div>
		</div>
		`;

		var roomCreationController = new AbortController();

		var roomCreationBackButton = document.getElementById("roomCreationBackButton");
		roomCreationBackButton.addEventListener("click", function() {
			roomCreationController.abort();
			enterChannel(roomID, channelID);
		})
		var roomCreationGoButton = document.getElementById("roomCreationGoButton");
		roomCreationGoButton.disabled = true;

		var roomCreationStatus = document.getElementById("roomCreationStatus");
		var roomNameInput = document.getElementById("newRoomName");
		var roomDescriptionInput = document.getElementById("newRoomDescription");

		roomNameInput.addEventListener("keyup", function() {
			if (roomNameInput.value.length > 0) {
				roomCreationGoButton.disabled = false;
			}
			else {
				roomCreationGoButton.disabled = true;
			}
		});

		roomCreationGoButton.addEventListener("click", function() {
			if (roomNameInput.value.length > 0) {
				roomCreationStatus.innerHTML = "Creating...";
				fetch("PHP/addRoom.php", {
					method: "POST",
					body: JSON.stringify({
						roomName: roomNameInput.value,
						description: roomDescriptionInput.value,
						browsable: document.getElementById("roomIsBrowsable").checked,
						public: true,
						//creatorID: userID,
						//creatorPassword: password,
						sessionID: sessionID,
						roomPassword: null,
					}),
					signal: roomCreationController.signal
				})
				.then(response => response.text()) 
				.then(data => {
					console.log(data);
					data = JSON.parse(data);

					if (data["roomID"]) {
						roomID = data["roomID"];
						enterRoom(roomID);
					}
					else {
						roomCreationStatus.innerHTML = "Failed to create room. Check the console log for details."
					}
				})
				.catch(error => console.log(error));
			}
			else {
				roomCreationGoButton.disabled = true;
			}
		})
	}

	function getListedRoomElement(roomInfo) {
		var element = document.createElement("div");
		var elementID = "room" + roomInfo["roomID"];
		element.id = elementID;
		element.className = "roomListing padded";
		element.dataset.roomID = roomInfo["roomID"];

		element.innerHTML = `
		<span id="`+elementID+`"roomName" class="lightGrayText">` + escapeHTML(roomInfo["roomName"]) + `</span> 
		<span class="normalText">(roomID: <span id="`+elementID+`roomID">` + roomInfo["roomID"] + `</span>)</span>
		`;

		if (roomInfo["roomID"] == roomID) {
			element.className += " selectedListing";
		}
		else {
			element.className += " clickableListing";

		}
		element.addEventListener("click", function() {
			/*currentRoomElement = document.getElementById("room" + roomID);
			if (currentRoomElement) {
				currentRoomElement.className = currentRoomElement.className.replace("selectedListing", "clickableListing");
			}
			element.className = element.className.replace("clickableListing", "selectedListing");*/
			roomID = roomInfo["roomID"];
			enterRoom(roomID);
		});
		
		return element;
	}

	function showDMs() {
		var parent = document.getElementById("dmList");
	}
	function showRecentRooms() {
		var parent = document.getElementById("recentRooms");
	}
	function showPublicRooms() {
		var parent = document.getElementById("allRooms");
		parent.innerHTML = `
		<div id="allRoomsHeader" class="padded silverText">
			<b> Public Rooms </b>
			<div id="createRoomOption"> </div>
		</div>
		<div id="allRoomsBody">
			<span class="padded"> Loading... </span>
		</div>
		`;

		fetch("PHP/getRoomList.php", {
			method: "POST",
			body: JSON.stringify({
				sessionID: sessionID
			})
		})
		.then((response) => response.text())
		.then((data) => {
			console.log(data);
			data = JSON.parse(data);

			rooms = data["roomList"];

			var allRoomsBody = document.getElementById("allRoomsBody");
			allRoomsBody.innerHTML = '';

			for (i in rooms) {
				if (!document.getElementById("room" + rooms[i]["roomID"])) {
					allRoomsBody.appendChild(getListedRoomElement(rooms[i]));
				}
			}
				
			// poll for updates
			getRoomListUpdates(data["updateTime"]);
		})
		.catch((error) => console.log(error));
	}
	function getRoomListUpdates(lastUpdateTime) {
		roomListUpdateController.abort();
		roomListUpdateController = new AbortController();
		fetch("PHP/getRoomListUpdates.php", {
			method: "POST",
			body: JSON.stringify({
				lastUpdateTime: lastUpdateTime,
				sessionID: sessionID
			}),
			signal: roomListUpdateController.signal
		})
		.then(response => response.text())
		.then(data => {
			console.log(data);
			data = JSON.parse(data);

			var updatedRooms = data["updatedRooms"];
			// check list of new or updated rooms
			if (updatedRooms) {
				for (i in updatedRooms) {
					var roomListing = document.getElementById("room" + updatedRooms[i]["roomID"]);
					if (roomListing) {
						roomListing = getListedRoomElement(updatedRooms[i]);
					}
					else {
						document.getElementById("allRoomsBody").appendChild(getListedRoomElement(updatedRooms[i]));
					}
				}
			}

			// get additional updates
			setTimeout(() => getRoomListUpdates(data["updateTime"]), 1200);
		})
		.catch(error => {
			console.log(error);
			if (error.name != "AbortError") {
				// resend request if it timed out but not if it was aborted
				setTimeout(() => getRoomListUpdates(lastUpdateTime), 1200);
			}
		})
	}
	function showRoomLists() { 
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
		showDMs();
		showRecentRooms();
		showPublicRooms();
	}

	function login(p_userID, p_password, p_sessionID) {
		// userID = p_userID;
		// password = p_password;
		sessionID = p_sessionID;

		// document.cookie = "userID=" + p_userID + "; samesite=strict";
		// document.cookie = "password=" + encodeURIComponent(p_password) + "; samesite=strict";
		document.cookie = "sessionID=" + p_sessionID + "; samesite=strict";

		showPage(roomID, channelID);
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
						login(data["userID"], p_password, data["sessionID"]);
					}
					else {
						loginStatus.innerHTML = "Invalid username or password";
					}
				})
			}
		})
		parent.addEventListener("keyup", function(e) {
			if (usernameInput.value.length > 0 && passwordInput.value.length > 0) {
				loginGoButton.disabled = false;
				loginStatus.innerHTML = "";
				if (e.keyCode == 13) {
					loginGoButton.click();
				}
			}
			else {
				loginGoButton.disabled = true;
			}
		})
	}

	function showAccountCreation() {
		parent = document.getElementById("accountOptions");
		parent.innerHTML = `
		<div class="bottomMargin lightGrayText" id="accountCreationHeader"> Create New Account: </div>
		<div> Username (1-24 characters) 
		<input id='usernameInput' class="bottomMargin" maxlength="24"> </input> </div>
		<div> Password (1-64 characters, restricted chars: ' ', '.') 
		<input id='passwordInput' type="password" class='bottomMargin' maxlength="64"> </input> </div>
		<div> Confirm Password 
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
						login(data["userID"], newPassword, data["sessionID"]);
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
				if (e.keyCode == 13) {
					signupGoButton.click();
				}
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

		document.getElementById("createRoomOption").innerHTML = `
		<span class="smallText darkGrayText"> Register or log in to create your own rooms </span>
		`;
	}

	function logout() {
		fetch("PHP/clearSession.php", {
			method: "POST",
			body: JSON.stringify({
				sessionID: sessionID
			})
		})
		.catch(error => console.log(error));

		// userID = null;
		// password = null;
		sessionID = null;

		// document.cookie = "userID=;";
		// document.cookie = "password=;";
		document.cookie = "sessionID=;";

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

	function showAccountEditing() {
		parent = document.getElementById("accountOptions");
		parent.innerHTML = `
		<div class="bottomMargin lightGrayText" id="accountEditHeader"> Edit Account Details: </div>
		<div class="bottomMargin">
			Screen Name (1-32 characters) <input id="screenNameInput"> </input>
			<br />
			<span class="smallText grayText"> This is the name that other users see. It doesn't need to be unique. </span>
		</div>
		<div class="bottomMargin">
			Account Name (1-24 characters) <input id="accountNameInput"> </input>
			<br />
			<span class="smallText grayText"> This is the name you use for logging in. It has to be unique. </span>
		</div>
		<div class="bottomMargin">
		</div>
		<div class="bottomMargin"> 
			<button id='editAccountCancelButton'> Cancel </button> <button id='editAccountGoButton'> Save Changes </button> 
		</div>
		`;

		var editAccountCancelButton = document.getElementById("editAccountCancelButton");
		editAccountCancelButton.addEventListener("click", function() {
			showRegisteredAccountOptions();
		});
		var editAccountGoButton = document.getElementById("editAccountGoButton");
		editAccountGoButton.disabled = true;
	}

	function showRegisteredAccountOptions() {
		parent = document.getElementById("accountOptions");
		parent.innerHTML = `
		<button id="signoutButton"> Sign Out </button>
		<button id="editAccountButton"> Edit Account Info </button>
		`;

		var signoutButton = document.getElementById("signoutButton");
		signoutButton.addEventListener("click", showSignout);

		var editAccountButton = document.getElementById("editAccountButton");
		editAccountButton.addEventListener("click", function() {
			showAccountEditing();
		})

		document.getElementById("createRoomOption").innerHTML = `
		<button id="createRoomButton" style="margin-top:3px"> Create New Room </button>
		`;

		document.getElementById("createRoomButton").addEventListener("click", function () {
			showRoomCreation();
		});
	}

	function showAccountInfo() {
		var parent = document.getElementById("accountInfo");
		parent.innerHTML = "Loading User Info...";
		fetch ("PHP/getAccountInfo.php", {
			method: "POST", 
			body: JSON.stringify({
				// "userID": userID,
				// "password": password,
				"sessionID": sessionID
			})
		})
		.then(response => response.text())
		.then(data => {
			console.log(data);
			data = JSON.parse(data);

			userID = data["userID"];

			parent.innerHTML = `
			<div id="accountInfoHeader" class="padded">
				<span class="lightGrayText"> <b> Welcome to ezChat, <span id="screenName"> </span></b> </span>
				<br/> 
				Your userID: <span id="userID"> </span>
			</div>
			<div id="accountOptions" class="padded">
			</div>
			`;

			document.getElementById("screenName").innerHTML = escapeHTML(data["screenName"]);
			document.getElementById("userID").innerHTML = userID;

			if (data["isRegistered"]) {
				// options for registered users
				showRegisteredAccountOptions();
			}
			else {
				// options for guests
				showGuestAccountOptions();
			}
		})
		.catch(error => console.log(error));
	}

	function formatMessage(messageContent) {
		messageContent = escapeHTML(messageContent);
		// messageContent.replace(/\n/g, "<br />");
		return messageContent;
	}

	function getMessageElement(messageInfo) {
		var parent = document.getElementById("messageStream");

		var element = document.createElement("div");
		var elementID = "message" + messageInfo["messageID"];
		element.id = elementID;
		element.className = "message columnFlex padded bottomMargin";
		element.dataset.messageID = messageInfo["messageID"];

		var messageHeader = document.createElement("div");
		element.appendChild(messageHeader);
		messageHeader.className = "bottomMargin rowFlex";
		messageHeader.innerHTML = `
		<div>
			<span id="`+ elementID + `screenName" class="boldText">` + escapeHTML(messageInfo["screenName"]) + `</span>  
			(userID <span id="`+ elementID + `userID">` + messageInfo["userID"] + `</span>)
			<span class="grayText smallText" id="`+ elementID + `sendTime"> ` + convertDateTime(messageInfo["sendTime"]) + ` </span>
			<span class="grayText smallText" id="`+ elementID + `editTime"></span>
		</div>
		`;

		var messageBody = document.createElement("div");
		element.appendChild(messageBody);
		messageBody.className = "bottomMargin";
		messageBody.innerHTML = formatMessage(messageInfo["content"]);

		/*
		var messageFooter = document.createElement("div");
		element.appendChild(messageFooter);
		messageFooter.className = "grayText smallText bottomMargin";
		messageFooter.innerHTML = `messageID ` + messageInfo["messageID"];
		*/

		return element;
	}

	function showMessages(messageList, channelID) {
		if (!cachedMessages[channelID]) {
			cachedMessages[channelID] = {"messageList": messageList};
		}

		parent = document.getElementById("messageStream");
		for (i in messageList) {
			// exit if no longer viewing current channel
			if (!parent || parent.dataset.channelID != channelID) return;

			messageElement = document.getElementById("message" + messageList[i]["messageID"]);
			if (!messageElement) {
				parent.appendChild(getMessageElement(messageList[i]));
				cachedMessages[channelID]["messageList"].push(messageList[i]);
			}
			else {
				messageElement = getMessageElement(messageList[i]);
				for (j in cachedMessages[channelID]["messageList"]) {
					if (cachedMessages[channelID]["messageList"][j]["messageID"] == messageList[i]["messageID"]) {
						cachedMessages[channelID]["messageList"][j] = messageList[i];
						break;
					}
				}
			}
		}
	}

	function getChatUpdates(channelID, lastUpdateTime) {
		var parent = document.getElementById("messageStream");
		if (!parent || parent.dataset.channelID != channelID) return;
		channelUpdateController.abort();
		channelUpdateController = new AbortController();
		fetch("PHP/getChannelUpdates.php", {
			method: "POST",
			body: JSON.stringify({
				//userID: userID,
				//password: password,
				sessionID: sessionID,
				channelID: channelID,
				lastUpdateTime: lastUpdateTime
			}),
			signal: channelUpdateController.signal
		})
		.then(response => response.text())
		.then(data => {
			console.log(data);
			data = JSON.parse(data);

			parent = document.getElementById("messageStream");
			if (!parent || parent.dataset.channelID != channelID) return;

			var scrolledToBottom = parent.scrollHeight - parent.clientHeight <= parent.scrollTop + 1;

			// display new messages
			newMessages = data["newMessages"];
			if (newMessages) {
				showMessages(newMessages, channelID);
				cachedMessages[channelID]["updateTime"] = data["updateTime"];
			}

			// keep message stream scrolled to bottom if currently scrolled to bottom
			parent = document.getElementById("messageStream");
			if (!parent || parent.dataset.channelID != channelID) return;
			if (scrolledToBottom) {
				parent.scrollTop = parent.scrollHeight - parent.clientHeight;
			}

			// fetch additional updates
			setTimeout(() => { getChatUpdates(channelID, data["updateTime"]); }, 1000);
		})
		.catch(error => {
			console.log(error);
			if (error.name != "AbortError") {
				setTimeout(() => { getChatUpdates(channelID, lastUpdateTime); }, 1000);
			}
		})
	}

	function showChat(channelID) {
		var parent = document.getElementById("centerRegion");
		parent.innerHTML = 
		`
		<div id="chatRegion" class="region">
			<div id="messageStream" class="padded">
			</div>
			<div id="chatBox" class="padded columnFlex">
				<textarea style="flex-grow: 1" id="chatboxInput" class="bottomMargin"></textarea>
				<div id="chatboxFooter" style="flex-grow:0" class="rowFlex"> 
					<span id="chatboxInfo" class="smallText" style="flex-grow:1"> ENTER to send, SHIFT+ENTER to create line break </span>
					<button id="sendButton" class="leftFlexAlign" style="flex-grow:0"> Send Message </button>
				</div>
			</div>
		</div>
		`;

		var messageStream = document.getElementById("messageStream");
		messageStream.dataset.channelID = channelID;
		var chatInput = document.getElementById("chatboxInput");
		var sendButton = document.getElementById("sendButton");

		messageStream.innerHTML = "Loading Messages...";
		if (!cachedMessages[channelID]) {
			fetch("PHP/getMessages.php", {
				method: "POST",
				body: JSON.stringify({
					//userID: userID,
					//password: password,
					sessionID: sessionID,
					channelID: channelID 
				}),
				signal: channelUpdateController.signal
			})
			.then(response => response.text())
			.then(data => {
				console.log(data);
				data = JSON.parse(data);

				// display existing messages
				messageStream.innerHTML = `<div class="padded bottomMargin"> Start of message stream for Channel ` + channelID + ` </div>`;
				var messageList = data["messageList"];
				showMessages(messageList, channelID);
				cachedMessages[channelID]["updateTime"] = data["updateTime"];

				// scroll to bottom of message stream
				messageStream.scrollTop = messageStream.scrollHeight - messageStream.clientHeight;

				// check for future updates
				getChatUpdates(channelID, data["updateTime"]);
			})
			.catch(error => console.log(error));
		}
		else {
			// show messages already stored in memory
			messageStream.innerHTML = `<div class="padded bottomMargin"> Start of message stream for Channel ` + channelID + ` </div>`;
			showMessages(cachedMessages[channelID]["messageList"], channelID);

			// get additional updates
			getChatUpdates(channelID, cachedMessages[channelID]["updateTime"]);

			// scroll to bottom of message stream
			messageStream.scrollTop = messageStream.scrollHeight - messageStream.clientHeight;
		}

		// set styling for input box
		chatInput.maxlength = 512;
		chatInput.style.resize = "none";
		chatInput.style.rows = 4;
		chatInput.placeholder = "Type your message here";

		// set event handlers
		sendButton.disabled = true;
		chatInput.addEventListener("keyup", function(e) {
			if (e.keyCode == 13 && !e.shiftKey) {
				// send message if enter pressed
				sendButton.click();
			}
			else {
				// enable send button if message length is greater than 0
				if (chatInput.value.length > 0) {
					sendButton.disabled = false;
				}
				else {
					sendButton.disabled = true;
				}
			}
		});
		sendButton.addEventListener("click", function() {
			if (chatInput.value.length > 0) {
				var messageContent = chatInput.value;

				chatInput.readonly = true;
				chatInput.value = "Sending...";
				sendButton.disabled = true;

				fetch("PHP/postMessage.php", {
					method: "POST",
					body: JSON.stringify({
						//userID: userID,
						//password: password,
						sessionID: sessionID,
						channelID: channelID,
						content: messageContent
					})
				})
				.then(response => response.text())
				.then(data => {
					console.log(data);

					chatInput.readonly = false;
					chatInput.value = "";
				})
				.catch(error => console.log(error));
			}
			else {
				sendButton.disabled = true;
			}
		})
	}

	function showUserLists(roomID, channelID) {
		var parent = document.getElementById("userList");
		parent.innerHTML =
		`
		`;
	}

	function enterRoom(roomID) {
		// get list of channels associated with room
		fetch("PHP/getChannelsByRoom.php", {
			method: "POST",
			body: JSON.stringify({
				roomID: roomID 
			})
		})
		.then(response => response.text())
		.then(data => {
			console.log(data);
			data = JSON.parse(data);

			if (data["channelList"]) {
				channelID = data["channelList"][0]["channelID"];
				enterChannel(roomID, channelID);
			}
			else {
				enterRoom(defaultRoomID);
			}
		})
		.catch(error => console.log(error));
	}

	function enterChannel(roomID, channelID) {
		abortChannelFetches();

		if (channelID) {
			// record channelID and display channel information and chat stream
			document.cookie = "channelID=" + channelID;
			history.replaceState({channelID: channelID}, "", "?channelID=" + channelID);

			showRoomInfo(roomID, channelID);
			showChat(channelID);
			showUserLists(roomID, channelID);
		}
		else {
			// get channelID from roomID if channelID is null
			// move to default room if both roomID and channelID are null
			if (!roomID) roomID = defaultRoomID;
			enterRoom(roomID);
		}
	}

	function showPage(roomID, channelID) {
		showRoomLists();
		showAccountInfo();
		enterChannel(roomID, channelID);
	}

	// get client parameters
	/*var userID = `<?php
		$userID = null;
		# get userID from cookie if present
		if (array_key_exists("userID", $_COOKIE)) {
			$userID = $_COOKIE["userID"];
		}
		if (!$userID && array_key_exists("guestID", $_COOKIE)) {
			$userID = $_COOKIE["guestID"];
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
	password = decodeURIComponent(password);*/

	var sessionID = `<?php
		$sessionID = null;
		# get userID from cookie if present
		if (array_key_exists("sessionID", $_COOKIE)) {
			$sessionID = $_COOKIE["sessionID"];
		}
		if (!$sessionID && array_key_exists("guestSessionID", $_COOKIE)) {
			$sessionID = $_COOKIE["guestSessionID"];
		}
		echo $sessionID;
	?>`;

	var channelID = `<?php
		function isValidChannel($db, $channelID) {
			$queryResult = $db->query("call getChannelInfo('$channelID')");
			return count($queryResult->fetch_all()) > 0;
		}
		$channelID = null;
		# get channel ID from GET parameter
		if (array_key_exists("channelID", $_GET)) {
			$channelID = $_GET["channelID"];
			# check if ID is valid
			if (!isValidChannel($db, $channelID)) {
				$channelID = null;
			}
		}
		# get channel ID from cookie if GET parameter not present or invalid
		if (!$channelID && (array_key_exists("channelID", $_COOKIE)) && !(array_key_exists("roomID", $_GET))) {
			# reset db connection
			free_all_results($db);

			$channelID = $_COOKIE["channelID"];
			if (!isValidChannel($db, $channelID)) {
				$channelID = null;
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

		$roomID = null;
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
					$channelID = null;
				}
			}
		}

		# reset db connection
		free_all_results($db);

		echo $roomID;
	?>`;

	// set roomID to default room if both roomID and channelID are null
	if (!roomID && !channelID) {
		roomID = defaultRoomID;
	}

	// assign guest ID if current ID/password not valid
	if (!sessionID) { // Number(`<?php echo ($userID != null && $db->query("select validateUser('$userID', '$password', true)")->fetch_array()[0]) ?>`)) {
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

			/*userID = data["userID"];
			document.cookie = "userID=" + userID + ";samesite=strict";
			document.cookie = "guestID=" + userID + ";samesite=strict";*/

			sessionID = data["sessionID"];
			document.cookie = "sessionID=" + sessionID + ";samesite=strict";
			document.cookie = "guestSessionID=" + sessionID + ";samesite=strict";
			showPage(channelID, roomID);
		})
		.catch((error) => console.log(error))
	}
	else {
		// render page content otherwise
		showPage(roomID, channelID);
	}

</script>
