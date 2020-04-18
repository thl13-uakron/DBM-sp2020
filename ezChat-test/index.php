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
		flex-grow: 0.8;
		resize: none;
	}

	textarea:disabled {
		background-color: rgb(235, 235, 235);
	}

	input {
		flex-grow: 0.5;
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

	.rightFlexAlign {
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

	.centerRegionElement {
		position:fixed;
		overflow-y: auto;
		box-sizing: border-box;
		width: 60%;
		height: 100%;
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
	var lastVisitedChannelByRoom = {};
	var messageDrafts = {};
	var channelPermissions = {};

	var channelUpdateController = new AbortController();
	var roomInfoController = new AbortController();
	var roomInfoUpdateController = new AbortController();
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

	function getContextualTime(dateTime, currentTime) {
		var difference = (Date.parse(currentTime) - Date.parse(dateTime)) / 1000;
		if (difference < 60) {
			return "less than a minute ago";
		}
		if (difference < 120) {
			return "about a minute ago";
		}
		if (difference < 3600) {
			return "about " + Math.floor(difference / 60) + " minutes ago";
		}
		if (difference < 7200) {
			return "about an hour ago";
		}
		if (difference < 86400) {
			return "about " + Math.floor(difference / 3600) + " hours ago";
		}
		if (difference < 172800) {
			return "over a day ago";
		}
		return "about " + Math.floor(difference / 86400) + " days ago";
	}

	// functions for retrieving data from backend and dynamically generating page content
	function isModerator(userID) {
		if (!userID) {
			var userID = document.getElementById("accountInfo").dataset.userID;
		}
		for (i in cachedRoomInfo[roomID]["moderators"]) {
			if (cachedRoomInfo[roomID]["moderators"][i]["userID"] == userID) return true;
		}
		return false;
	}
	function isAdministrator(userID) {
		if (!userID) {
			var userID = document.getElementById("accountInfo").dataset.userID;
		}
		for (i in cachedRoomInfo[roomID]["administrators"]) {
			if (cachedRoomInfo[roomID]["administrators"][i]["userID"] == userID) return true;
		}
		return false;
	}
	function getPermissionValue(permissionName) {
		permissionValue = null;
		if (channelPermissions[channelID][permissionName]) {
			permissionValue = channelPermissions[channelID][permissionName]["permissionValue"];
		}
		if (permissionValue == null) {
			permissionValue = cachedRoomInfo[roomID]["permissionSettings"][permissionName]["permissionValue"];
		}
		return permissionValue;
	}
	function hasPermission(permissionName) {
		permissionValue = getPermissionValue(permissionName);

		if (permissionValue == 1) {
			return true;
		}
		if (permissionValue == 2) {
			return document.getElementById("accountInfo").dataset.isRegistered == "true";
		}
		if (permissionValue == 3) {
			return isModerator() || isAdministrator();
		}
		if (permissionValue == 4) {
			return isAdministrator();
		}
		return false;
	}
	var entryAttempts = 0; // see if other channels in room can be entered
	// used for indicating to users why they don't have permission to do something
	var permissionValueDescriptors = {
		2: "Only registered users",
		3: "Only moderators or administrators of this room",
		4: "Only administrators of this room"
	}
	function handlePermissions() {
		if (cachedRoomInfo[roomID]["permissionSettings"]) {
			if (!hasPermission("canEnter")) {
				if (entryAttempts == 0) {
					alert(permissionValueDescriptors[getPermissionValue("canEnter")] + " can enter this channel");
					lastVisitedChannelByRoom[roomID] = null;
					if (cachedRoomInfo[roomID]["channelList"].length > 1) {
						++entryAttempts;
						enterRoom(roomID);
					}
					else {
						enterRoom(null);
					}
					return;
				}
				else {
					entryAttempts = 0;
					enterRoom(null);
				}
			}
			else {
				entryAttempts = 0;
			}

			var chatInput = document.getElementById("chatboxInput");
			if (chatInput) {
				if (!hasPermission("canPostMessages")) {
					chatInput.disabled= true;
					chatInput.value = permissionValueDescriptors[getPermissionValue("canPostMessages")] + " can post messages in this channel";
				}
				else {
					if (chatInput.disabled) {
						chatInput.disabled = false;
						chatInput.value = "";
					}
				}
			}

			var roomOptionsElement = document.getElementById("roomOptionsElement");
			if ((hasPermission("canEditRoomSettings"))) { // || hasPermission("canDeleteRoom"))) {
				if (!document.getElementById("editRoomButton")) {
					var editRoomButton = document.createElement("button");
					editRoomButton.innerHTML = "Change Room Settings";
					editRoomButton.className = "smallText bottomMargin";
					editRoomButton.id = "editRoomButton";
					editRoomButton.addEventListener("click", function() {
						showRoomEditing(roomID);
					})
					roomOptionsElement.appendChild(editRoomButton);
					roomOptionsElement.className = "padded bottomMargin";
				}
			}
			else {
				var editRoomButton = document.getElementById("editRoomButton");
				if (editRoomButton) editRoomButton.remove();
				roomOptionsElement.className = "";
				if (document.getElementById("roomEditPanel")) {
					enterChannel(channelID);
					return;
				}
			}

			var createChannelOption = document.getElementById("createChannelOption")
			if (hasPermission("canAddChannel")) {
				if (!document.getElementById("addChannelButton")) {
					var addChannelButton = document.createElement("button");
					addChannelButton.innerHTML = "Add Channel";
					addChannelButton.className = "smallText";
					addChannelButton.id = "addChannelButton";
					addChannelButton.addEventListener("click", function() {
						showChannelCreation();
					})
					createChannelOption.appendChild(addChannelButton);
					createChannelOption.className = "padded bottomMargin";
				}
			}
			else {
				var addChannelButton = document.getElementById("addChannelButton");
				if (addChannelButton) addChannelButton.remove();
				createChannelOption.className = "";
				if (document.getElementById("channelCreationPanel")) {
					enterChannel(channelID);
					return;
				}
			}

			var channelEditOption = document.getElementById("channel" + channelID + "options");
			var channelEditButton = document.getElementById("channelEditButton");
			if (channelEditButton) channelEditButton.remove();
			channelEditOption.className = "";
			var userID = document.getElementById("accountInfo").dataset.userID;
			if (userID == document.getElementById("channel" + channelID).dataset.creatorID || hasPermission("canEditChannelSettings")) { // || hasPermission("canDeleteChannels")) {
				var channelEditButton = document.createElement("button");
				channelEditButton.innerHTML = "Edit";
				channelEditButton.className = "smallText";
				channelEditButton.id = "channelEditButton";
				channelEditButton.addEventListener("click", function() {
					showChannelEditing(channelID);
				});
				channelEditOption.appendChild(channelEditButton);
				channelEditOption.className = "padded bottomMargin";
			}
			else {
				var channelEditButton = document.getElementById("channelEditButton");
				if (channelEditButton) channelEditButton.remove();
				channelEditOption.className = "";
				if (document.getElementById("channelEditPanel")) {
					enterChannel(channelID);
					return;
				}
			}

			if (hasPermission("canAppointModerators") || hasPermission("canAppointAdministrators")) {
				//
			}
			else {
				if (document.getElementById("addAdministratorButton") && !hasPermission("canAppointAdministrators")) document.getElementById("addAdministratorButton").remove();
				if (document.getElementById("addModeratorButton") && !hasPermission("canModeratorAdministrators")) document.getElementById("addModeratorButton").remove();
			}
		}

		return;
	}

	function getListedChannelElement(channelInfo, roomID) {
		var element = document.createElement("div");
		var elementID = "channel" + channelInfo["channelID"];
		element.id = elementID;
		element.className = "channelListing padded";
		element.dataset.channelID = channelInfo["channelID"];
		element.dataset.creatorID = channelInfo["creatorID"];

		element.innerHTML = `
		<span id="`+elementID+`channelName" class="lightGrayText">` + escapeHTML(channelInfo["channelName"]) + `</span> 
		<span class="normalText">(channelID: <span id="`+elementID+`channelID">` + channelInfo["channelID"] + `</span>)</span>
		<span id="`+elementID+`options" class="smallText normalText"> </span>
		`;

		if (channelInfo["channelID"] == channelID) {
			element.className += " selectedListing";
		}
		else {
			element.className += " clickableListing";
			
		}

		if (channelInfo["description"]) {
			element.innerHTML += `
			<br />
			<span class="normalText smallText" id="`+elementID+`description">
			`+channelInfo["description"]+`
			</span>
			`
		}

		element.addEventListener("click", function() {
			if (element.dataset.channelID != channelID) {
				channelID = channelInfo["channelID"];
				enterChannel(roomID, channelID);
			}
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
					channelElement.innerHTML = getListedChannelElement(channelList[i], roomInfo, channelID).innerHTML;
				}
			}
		}
	}

	function getRoomInfoUpdates(roomID, lastUpdateTime) {
		parent = document.getElementById("roomInfo");
		if (parent.dataset.roomID == roomID) {
			fetch("PHP/getRoomInfoUpdates.php", {
				method: "POST",
				body: JSON.stringify({
					sessionID: sessionID,
					roomID: roomID,
					lastUpdateTime: lastUpdateTime
				}),
				signal: roomInfoUpdateController.signal
			})
			.then(response => response.text())
			.then(data => {
				console.log(data);
				data = JSON.parse(data);

				updateTime = lastUpdateTime;
				roomInfo = data["roomInfo"];
				newChannels = data["newChannels"];
				deletedChannels = data["deletedChannels"];
				permissionSettings = data["permissionSettings"];
				moderators = data["moderators"];
				administrators = data["administrators"];
				userList = data["userList"];

				if (roomInfo) {
					updateTime = data["updateTime"];
					cachedRoomInfo[roomID]["roomInfo"] = roomInfo;
					if (parent.dataset.roomID == roomID) {
						var roomNameElement = document.getElementById("roomNameElement");
						roomNameElement.innerHTML = roomInfo["roomName"];

						var roomDescriptionElement = document.getElementById("roomDescriptionElement");
						roomDescriptionElement.innerHTML = roomInfo["description"];

						if (roomInfo["description"]) {
							roomDescriptionElement.className = "padded bottomMargin";
						}
						else {
							roomDescriptionElement.className = "";
						}
					}
					else {
						return;
					}
				}

				if (newChannels) {
					updateTime = data["updateTime"];
					cachedChannelList = cachedRoomInfo[roomID]["channelList"];
					for (i in newChannels) {
						if (newChannels[i]["channelID"] > cachedChannelList[cachedChannelList.length - 1]["channelID"]) {
							cachedRoomInfo[roomID]["channelList"].push(newChannels[i]);
						}
						else {
							for (j in cachedChannelList) {
								if (newChannels[i]["channelID"] == cachedChannelList[j]["channelID"]) {
									cachedRoomInfo[roomID]["channelList"][j] = newChannels[i];
									break;
								}
							}
						}
					}
					showChannels(newChannels, roomID);
				}

				if (deletedChannels) {
					updateTime = data["updateTime"];
					cachedChannelList = cachedRoomInfo[roomID]["channelList"];
					for (i in deletedChannels) {
						document.getElementById("channel" + deletedChannels[i]["channelID"]).remove();
						for (j in cachedChannelList) {
							if (deletedChannels[i]["channelID"] == cachedChannelList[j]["channelID"]) {
								cachedChannelList.splice(j, 1);
								cachedRoomInfo[roomID]["channelList"].splice(j, 1);
								break;
							}
						}
					}
				}

				if (permissionSettings && permissionSettings.length > 0) {
					updateTime = data["updateTime"];
					for (i in permissionSettings) {
						cachedRoomInfo[roomID]["permissionSettings"][i] = permissionSettings[i];
						/*if (document.getElementById("roomEditPanel")) {
							document.getElementById("permission" + permissionSettings[i]["permissionID"]).innerHTML = getPermissionSettingElement(permissionSettings[i]);
						}*/
					}
				}

				if (moderators) {
					updateTime = data["updateTime"];
					cachedRoomInfo[roomID]["moderators"] = moderators;
				}
				if (administrators) {
					updateTime = data["updateTime"];
					cachedRoomInfo[roomID]["administrators"] = administrators;
				}

				if (userList) {
					updateTime = data["updateTime"];
					cachedRoomInfo[roomID]["userList"] = userList;
				}
				showUserLists(cachedRoomInfo[roomID]["userList"]);

				cachedRoomInfo[roomID]["updateTime"] = updateTime;
				
				setTimeout(() => getRoomInfoUpdates(roomID, updateTime), 1200);
				/*if ((permissionSettings && permissionSettings.length > 0) || moderators || administrators) {
					handlePermissions();
				}*/
				handlePermissions();
			})
			.catch(error => {
				console.log(error);
				if (error.name != "AbortError") {
					// resend request if it timed out but not if it was aborted
					setTimeout(() => getRoomInfoUpdates(roomID, lastUpdateTime), 1200);
				}
			})
		}
	}
	var permissionSettingValues = {
		0: "(Use room settings)",
		1: "Everyone",
		2: "Registered users only",
		3: "Moderators and administrators only",
		4: "Administrators only"
	}
	function showPermissionSettingElement(permissionInfo) {
		var element = document.createElement("div");
		var elementID = "permission" + permissionInfo["permissionID"];
		element.id = elementID;
		element.dataset.value = permissionInfo["permissionValue"];

		var isChannelPermission = (document.getElementById("channelEditPanel") ? true : false);
		if (isChannelPermission) {
			var parent = document.getElementById("channelPermissionSettings");
			parent.appendChild(element);
		}
		else {
			var parent = document.getElementById("roomPermissionSettings");
			parent.appendChild(element);
		}

		element.className = "padded bottomMargin";
		element.innerHTML = `
		Who can `+permissionInfo["descriptor"] + (isChannelPermission ? " this channel" : (Number(permissionInfo["channelSpecific"]) ? " this room" : "")) +`?
		<br />
		`;

		for (i in permissionSettingValues) {
			if ((i == 0 && isChannelPermission) || (i <= permissionInfo["maxVal"] && i >= permissionInfo["minVal"])) {
				/*var permissionOptionElement = document.createElement("input");
				element.appendChild(permissionOptionElement);
				permissionOptionElement.type = "radio";
				permissionOptionElement.name = elementID + "value";
				permissionOptionElement.value = i;
				if (permissionOptionElement.value == (element.dataset.value != "null" ? element.dataset.value : 0)) {
					permissionOptionElement.checked = true;
				}
				permissionOptionElement.addEventListener("click", function() {
					element = document.getElementById(elementID);
					document.getElementById(elementID + "saveButton").disabled = false;
					element.dataset.value = this.value;
				});*/
				element.innerHTML += `
				<input type="radio" name="`+elementID+`option" value=`+i+` id="`+elementID+"option"+i+`">
				`;
				element.innerHTML += permissionSettingValues[i];
				element.innerHTML += "<br />";
			}
		}

		for (i in permissionSettingValues) {
			var permissionOptionElement = document.getElementById(elementID + "option" + i);
			if (permissionOptionElement) {
				permissionOptionElement.value = i;
				if (permissionOptionElement.value == (element.dataset.value != "null" ? element.dataset.value : 0)) {
					permissionOptionElement.checked = true;
				}
				permissionOptionElement.addEventListener("click", function() {
					element = document.getElementById(elementID);
					document.getElementById(elementID + "saveButton").innerHTML = "Save";
					document.getElementById(elementID + "saveButton").disabled = false;
					element.dataset.value = this.value;
				});
			}
		}

		permissionSaveButton = document.createElement("button");
		permissionSaveButton.innerHTML = "Saved";
		permissionSaveButton.id = elementID + "saveButton";
		permissionSaveButton.disabled = true;
		permissionSaveButton.addEventListener("click", function() {
			permissionSaveButton = this;
			permissionSaveButton.disabled = true;
			permissionSaveButton.innerHTML = "Saving...";
			fetch("PHP/setPermission.php", {
				method: "POST",
				body: JSON.stringify({
					channelID: (isChannelPermission ? channelID : null),
					roomID: (isChannelPermission ? null : roomID),
					sessionID: sessionID,
					permissionID: permissionInfo["permissionID"],
					permissionValue: (element.dataset.value != 0 ? element.dataset.value : null)
				}),
				signal: channelUpdateController.signal
			})
			.then(response => response.text())
			.then(data => {
				console.log(data);
				data = JSON.parse(data);
				if (data["querySuccess"]) {
					permissionSaveButton.innerHTML = "Saved";
				}
				else {
					alert("Unable to set permission" + (data["failReason"] ? ": " + data["failReason"] : ""))
					permissionSaveButton.innerHTML = "Save";
					permissionSaveButton.disabled = false;
				}
			})
			.catch(error => console.log(error))
		})
		element.appendChild(permissionSaveButton);
	}

	function showChannelEditing(channelID) {
		var parent = document.getElementById("centerRegion");
		channelUpdateController.abort();
		channelUpdateController = new AbortController;
		parent.innerHTML = `
		<div id="channelEditPanel" class="padded centerRegionElement" style="overflow-y: auto">
			<div class="padded boldText">
				Settings For Channel ` + channelID + `
			</div>
			<div class="padded">
				Channel Name (1-24 characters): <span class="rowFlex"> <input id="channelNameInput" maxlength=24> </input> </span>
			</div>
			<div class="padded">
				Channel Description (max 64 characters, optional): 
				<br />
				<div class="rowFlex">
				<textarea id="channelDescriptionInput" maxlength=64></textarea>
				</div>
			</div>
			<div class="padded">
				<button id="channelEditGoButton"> Save Changes </button>
			</div>
			<div class="padded" id="channelEditStatus">
			</div>
			<div id="channelPermissionSettings">
			</div>
			<div class="padded" id="channelDeleteOption">
			</div>
			<div class="padded">
				<button id="channelEditBackButton"> Go Back </button>
			</div>
		</div>
		`;

		document.getElementById("channelEditPanel").dataset.channelID = channelID;

		var channelEditController = new AbortController();

		var channelEditBackButton = document.getElementById("channelEditBackButton");
		channelEditBackButton.addEventListener("click", function() {
			channelEditController.abort();
			enterChannel(roomID, channelID);
		})
		var channelEditGoButton = document.getElementById("channelEditGoButton");
		channelEditGoButton.disabled = true;

		var channelNameInput = document.getElementById("channelNameInput");
		channelNameInput.value = document.getElementById("channel" + channelID + "channelName").innerText;

		var channelDescriptionInput = document.getElementById("channelDescriptionInput");
		if (document.getElementById("channel" + channelID + "description")) {
			channelDescriptionInput.value = document.getElementById("channel" + channelID + "description").innerText;
		}

		channelEditGoButton.addEventListener("click", function() {
			channelEditStatus.innerHTML = "Saving Changes...";
			fetch("PHP/updateChannelInfo.php", {
				method: "POST",
				body: JSON.stringify({
					sessionID: sessionID,
					channelID: channelID,
					newChannelName: channelNameInput.value,
					newChannelDescription: channelDescriptionInput.value
				}),
				signal: channelEditController.signal
			})
			.then(response => response.text())
			.then(data => {
				console.log(data);
				data = JSON.parse(data);

				if (data["querySuccess"]) {
					channelEditStatus.innerHTML = "Changes Saved.";
					channelEditGoButton.disabled = true;
				}
				else {
					channelEditStatus.innerHTML = "Failed to make changes";
					if (data["failReason"]) {
						channelEditStatus.innerHTML = ": " + data["failReason"];
					}
				}
			})
		})

		for (i in channelPermissions[channelID]) {
			showPermissionSettingElement(channelPermissions[channelID][i]);
		}

		if (document.getElementById("channel" + channelID).dataset.creatorID == userID || hasPermission("canDeleteChannels")) {
			var deleteChannelButton = document.createElement("button");
			deleteChannelButton.innerHTML = "Delete";
			deleteChannelButton.addEventListener("click", function() {
				if (window.confirm("Are you sure you want to this channel? It can't be recovered.")) {
					fetch("PHP/deleteChannel.php", {
						method: "POST",
						body: JSON.stringify({
							sessionID: sessionID,
							channelID: channelID
						}),
						signal: channelEditController.signal
					})
					.then(response => response.text())
					.then(data => {
						console.log(data);
						enterRoom(roomID);
					})
					.catch(error => console.log(error))
				}
			})
			var channelDeleteOption = document.getElementById("channelDeleteOption");
			channelDeleteOption.innerHTML = "Delete Channel? ";
			channelDeleteOption.appendChild(deleteChannelButton);
			channelDeleteOption.className += " bottomMargin";
		}

		parent.addEventListener("keyup", function() {
			if (channelNameInput.value.length > 0) {
				channelEditGoButton.disabled = false;
			}
			else {
				channelEditGoButton.disabled = true;
			}
		})
	}

	function showChannelCreation() {
		var parent = document.getElementById("centerRegion");
		channelUpdateController.abort();
		channelUpdateController = new AbortController;
		parent.innerHTML = `
		<div id="channelCreationPanel" class="padded centerRegionElement">
			<div class="padded boldText">
				Create a New Channel
			</div>
			<div class="padded">
				Channel Name (1-24 characters): <span class="rowFlex"> <input id="channelNameInput" maxlength=24> </input> </span>
			</div>
			<div class="padded">
				Channel Description (max 64 characters, optional): 
				<br />
				<div class="rowFlex">
					<textarea id="channelDescriptionInput" maxlength=64></textarea>
				</div>
			</div>
			<div class="padded">
				<button id="channelCreationBackButton"> Go Back </button>
				<button id="channelCreationGoButton"> Create Channel </button>
			</div>
			<div class="padded" id="channelCreationStatus">
			</div>
		</div>
		`;

		var channelCreationController = new AbortController();

		var channelCreationBackButton = document.getElementById("channelCreationBackButton");
		channelCreationBackButton.addEventListener("click", function() {
			channelCreationController.abort();
			enterChannel(roomID, channelID);
		})
		var channelCreationGoButton = document.getElementById("channelCreationGoButton");
		channelCreationGoButton.disabled = true;

		var channelNameInput = document.getElementById("channelNameInput");
		var channelDescriptionInput = document.getElementById("channelDescriptionInput");

		var channelCreationStatus = document.getElementById("channelCreationStatus");

		parent.addEventListener("keyup", function() {
			channelCreationStatus.innerHTML = "";
			if (channelNameInput.value.length > 0) {
				channelCreationGoButton.disabled = false;
			}
			else {
				channelCreationGoButton.disabled = true;
			}
		});

		channelCreationGoButton.addEventListener("click", function() {
			fetch("PHP/addChannel.php", {
				method: "POST",
				body: JSON.stringify({
					sessionID: sessionID,
					roomID: roomID,
					channelName: channelNameInput.value,
					description: channelDescriptionInput.value
				}),
				signal: channelCreationController.signal
			})
			.then(response => response.text())
			.then(data => {
				console.log(data);
				data = JSON.parse(data);

				if (data["querySuccess"]) {
					channelID = data["channelID"];
					enterChannel(roomID, channelID);
				}
				else {
					channelCreationStatus.innerHTML = "Failed to create channel";
					if (data["failReason"]) {
						channelCreationStatus.innerHTML += ": " + data["failReason"];
					}
				}
			})
			.then(error => console.log(error))
		})
	}

	function showRoomEditing(roomID) {
		var parent = document.getElementById("centerRegion");
		channelUpdateController.abort();
		channelUpdateController = new AbortController;
		parent.innerHTML = `
		<div id="roomEditPanel" class="padded centerRegionElement">
			<div class="padded boldText">
				Settings for Room ` + roomID + `
			</div>
			<div class="padded">
				Room Name (1-32 characters): <span class="rowFlex"> <input id="roomNameInput" maxlength=32> </input> </span>
			</div>
			<div class="padded">
				Room Description (max 128 characters, optional): 
				<br />
				<div class="rowFlex">
					<textarea id="roomDescriptionInput" maxlength=128></textarea>
				</div>
			</div> 
			<div class="padded">
				<input type="radio" name="roomAccess" id="roomIsVisible"> Room appears in public list </input>
				<br />
				<input type="radio" name="roomAccess" id="roomIsNotVisible"> Room is only accessible by URL </input>
			</div>
			<div class="padded">
				<button id="roomEditGoButton"> Save Changes </button>
			</div>
			<div class="padded" id="roomEditStatus"> 
			</div>
			<div id="roomPermissionSettings">
			</div>
			<div class="padded" id="roomDeleteOption">
			</div>
			<div class="padded">
				<button id="roomEditBackButton"> Go Back </button>
			</div>
		</div>
		`;

		var roomEditController = new AbortController();

		var roomEditBackButton = document.getElementById("roomEditBackButton");
		roomEditBackButton.addEventListener("click", function() {
			roomEditController.abort();
			enterChannel(roomID, channelID);
		})
		var roomEditGoButton = document.getElementById("roomEditGoButton");
		roomEditGoButton.disabled = true;

		var roomNameInput = document.getElementById("roomNameInput");
		roomNameInput.value = cachedRoomInfo[roomID]["roomInfo"]["roomName"];

		var roomDescriptionInput = document.getElementById("roomDescriptionInput");
		roomDescriptionInput.value = cachedRoomInfo[roomID]["roomInfo"]["description"];

		var roomIsVisible = document.getElementById("roomIsVisible");
		var roomIsNotVisible = document.getElementById("roomIsNotVisible");
		if (Number(cachedRoomInfo[roomID]["roomInfo"]["browsable"])) {
			roomIsVisible.checked = true;
		}
		else {
			roomIsNotVisible.checked = true;
		}

		var roomEditStatus = document.getElementById("roomEditStatus");

		function checkSaveButton() {
			roomEditStatus.innerHTML = "";
			if (roomNameInput.value.length > 0) {
				roomEditGoButton.disabled = false;
			}
			else {
				roomEditGoButton.disabled = true;
			}
		}

		parent.addEventListener("keyup", checkSaveButton);
		roomIsVisible.addEventListener("click", checkSaveButton);
		roomIsNotVisible.addEventListener("click", checkSaveButton);

		roomEditGoButton.addEventListener("click", function() {
			roomEditStatus.innerHTML = "Saving Changes...";
			fetch("PHP/updateRoomInfo.php", {
				method: "POST",
				body: JSON.stringify({
					sessionID: sessionID,
					roomID: roomID,
					newRoomName: roomNameInput.value,
					newRoomDescription: roomDescriptionInput.value,
					roomIsVisible: roomIsVisible.checked
				}),
				signal: roomEditController.signal
			})
			.then(response => response.text())
			.then(data => {
				console.log(data);
				data = JSON.parse(data);

				if (data["querySuccess"]) {
					roomEditStatus.innerHTML = "Changes Saved.";
					roomEditGoButton.disabled = true;
				}
				else {
					roomEditStatus.innerHTML = "Failed to make changes";
					if (data["failReason"]) {
						roomEditStatus.innerHTML = ": " + data["failReason"];
					}
				}
			})
			.catch(error => console.log(error));
		})

		for (i in cachedRoomInfo[roomID]["permissionSettings"]) {
			showPermissionSettingElement(cachedRoomInfo[roomID]["permissionSettings"][i]);
		}

		if (hasPermission("canDeleteRoom")) {
			var deleteRoomButton = document.createElement("button");
			deleteRoomButton.innerHTML = "Delete";
			deleteRoomButton.addEventListener("click", function() {
				if (window.confirm("Are you sure you want to this room? It can't be recovered.")) {
					fetch("PHP/deleteRoom.php", {
						method: "POST",
						body: JSON.stringify({
							sessionID: sessionID,
							roomID: roomID
						}),
						signal: roomEditController.signal
					})
					.then(response => response.text())
					.then(data => {
						console.log(data);
						enterRoom(null);
					})
					.catch(error => console.log(error))
				}
			})
			var roomDeleteOption = document.getElementById("roomDeleteOption");
			roomDeleteOption.innerHTML = "Delete Room? ";
			roomDeleteOption.appendChild(deleteRoomButton);
			roomDeleteOption.className += " bottomMargin";
		}
	}

	function showRoomInfo_helper(roomID, roomInfo, userPermissions, channelList, updateTime) {
		parent = document.getElementById("roomInfo");

		if (parent.dataset.roomID == roomID) {
			parent.innerHTML = `
			<div id="roomInfoHeader" class="padded">
				<span class="lightGrayText"><b> Current Room: <span id="roomNameElement"></span></b> </span> 
				(roomID: <span id="roomIDElement"> </span>)
				<br />
				<i><span class="smallText grayText"> Created by <span id="creatorNameElement"></span> (userID: <span id="creatorIDElement"></span>) 
				on <span id="creationDateElement"></span> </span></i>
			</div>
			<div id="roomDescriptionElement"> </div>
			<div id="roomOptionsElement"> </div>
			<div id="channelListElement"> 
				<div id="channelListHeader" class="padded silverText"> 
					<b> Channels in this Room </b> 
				</div>
				<div id="channelListBody" class="bottomMargin"> </div>
				<div id="createChannelOption"> </div>
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
				roomDescriptionElement.innerHTML = escapeHTML(roomInfo["description"]);
			}

			showChannels(channelList, roomID);

			// poll for future updates
			getRoomInfoUpdates(roomID, updateTime);
		}
	}

	function showRoomInfo(channelID) {
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
			// console.log(data);
			data = JSON.parse(data);

			channelInfo = data["channelInfo"];
			channelPermissions[channelID] = data["permissionSettings"];
			if (channelInfo) {
				roomID = channelInfo["roomID"];
				if (roomID) {
					// update room List
					var previousRoomListing = document.getElementsByClassName("roomListing selectedListing")[0];
					var roomListing = document.getElementById("room" + roomID);
					if (previousRoomListing && previousRoomListing.dataset.roomID != roomID) {
						previousRoomListing.className = previousRoomListing.className.replace('selectedListing', "clickableListing");
						if (roomListing) {
							roomListing.className = roomListing.className.replace("clickableListing", "selectedListing");
						}
					}
					else if (roomListing && !roomListing.className.match("selectedListing")) {
						roomListing.className += " selectedListing";
					}

					// log channel
					lastVisitedChannelByRoom[roomID] = channelID;

					// display room info if not already displayed
					if (parent.dataset.roomID != roomID) {
						parent.dataset.roomID = roomID;
						if (!cachedRoomInfo[roomID]) {
							roomInfoUpdateController.abort();
							roomInfoUpdateController = new AbortController();

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
								// console.log(data);
								data = JSON.parse(data);

								roomInfo = data["roomInfo"];
								updateTime = data["updateTime"];
								userPermissions = data["userPermissions"];
								permissionSettings = data["permissionSettings"];
								administrators = data["administrators"];
								moderators = data["moderators"];
								channelList = data["channelList"];
								userList = data["userList"];

								cachedRoomInfo[roomID] = {
									"roomInfo": roomInfo, 
									"updateTime": updateTime, 
									"userPermissions": userPermissions,
									"channelList": channelList,
									"permissionSettings": permissionSettings,
									"administrators": administrators,
									"moderators": moderators,
									"userList": userList
								};

								showRoomInfo_helper(roomID, roomInfo, userPermissions, channelList, updateTime);
								showUserLists(userList);
								handlePermissions();
							})
							.catch(error => console.log(error));
						}
						else {
							// get room info from memory if already stored there
							roomInfo = cachedRoomInfo[roomID]["roomInfo"];
							channelList = cachedRoomInfo[roomID]["channelList"];
							updateTime = cachedRoomInfo[roomID]["updateTime"];
							userPermissions = cachedRoomInfo[roomID]["userPermissions"];

							showRoomInfo_helper(roomID, roomInfo, userPermissions, channelList, updateTime);
							showUserLists(cachedRoomInfo[roomID]["userList"]);
							handlePermissions();
						}
					}
					else {
						// update channel list if already in room
						var previousChannelListing = document.getElementsByClassName("channelListing selectedListing")[0];
						if (previousChannelListing && previousChannelListing.dataset.channelID != channelID) {
							previousChannelListing.className = previousChannelListing.className.replace('selectedListing', "clickableListing");
							if (document.getElementById("channelEditButton")) document.getElementById("channelEditButton").remove();
						}
						var channelListing = document.getElementById("channel" + channelID);
						if (channelListing) {
							channelListing.className = channelListing.className.replace("clickableListing", "selectedListing");
						}
						handlePermissions();
					}
				}
				else {
					// handle DM channels that are independent of rooms
				}
			}
			else {
				lastVisitedChannelByRoom[roomID] = null;
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
		<div id="roomCreationPanel" class="padded centerRegionElement">
			<div class="padded boldText">
				Create a New Room
			</div>
			<div class="padded">
				Room Name (1-32 characters): <span class="rowFlex"> <input id="newRoomName" maxlength=32> </input> </span>
			</div>
			<div class="padded">
				Room Description (max 128 characters, optional): 
				<br />
				<div class="rowFlex">
					<textarea id="newRoomDescription" maxlength=128></textarea>
				</div>
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
				var roomListing = document.getElementById("room" + rooms[i]["roomID"])
				if (!roomListing) {
					allRoomsBody.appendChild(getListedRoomElement(rooms[i]));
				}
				else {
					roomListing.innerHTML = getListedRoomElement(rooms[i]).innerHTML;
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
					if (updatedRooms[i]["browsable"] != "0") {
						if (roomListing) {
							roomListing.hidden = false;
							roomListing.innerHTML = getListedRoomElement(updatedRooms[i]).innerHTML;
						}
						else {
							document.getElementById("allRoomsBody").appendChild(getListedRoomElement(updatedRooms[i]));
						}
					}
					else {
						if (roomListing && roomListing.parentElement.id == "allRoomsBody") {
							roomListing.hidden = true;
						}
					}
				}
			}

			var deletedRooms = data["deletedRooms"];
			if (deletedRooms) {
				for (i in deletedRooms) {
					document.getElementById("room" + deletedRooms[i]["roomID"]).remove();
					cachedRoomInfo[deletedRooms[i]["roomID"]] = null;
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

		// showPage(roomID, channelID);
		location.reload();
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
		<div> Username (1-24 characters) <br /> <input id='usernameInput' class="bottomMargin" maxlength="24"> </input> </div>
		<div> Password (1-64 characters) <br /> <input id='passwordInput' type="password" class='bottomMargin' maxlength="64"> </input> </div>
		<div> Confirm Password <br /> <input id='confirmPassword' type="password" class='bottomMargin' maxlength="64"> </input> </div>
		<div class="bottomMargin"> <button id='signupCancelButton'> Cancel </button> <button id='signupGoButton'> Go </button> </div>
		<div id='signupStatus'> </div>
		`;

		var signupCancelButton = document.getElementById("signupCancelButton");
		var signupGoButton = document.getElementById("signupGoButton");
		var usernameInput = document.getElementById("usernameInput");
		var passwordInput = document.getElementById("passwordInput");
		var confirmPassword = document.getElementById("confirmPassword");
		var signupStatus = document.getElementById("signupStatus");

		// var restrictedPasswordRegex = new RegExp(".*[\. ].*");

		signupCancelButton.addEventListener("click", showGuestAccountOptions);
		signupGoButton.disabled = true;
		signupGoButton.addEventListener("click", function() {
			if (usernameInput.value.length > 0 && passwordInput.value.length > 0 && confirmPassword.value == passwordInput.value) {
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
			if (usernameInput.value.length > 0 && passwordInput.value.length > 0 && confirmPassword.value == passwordInput.value) {
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

	function showAccountEditing(accountInfo) {
		parent = document.getElementById("accountOptions");
		parent.innerHTML = `
		<div class="bottomMargin lightGrayText" id="accountEditHeader"> Edit Account Details: </div>
		<div class="bottomMargin">
			Screen Name (1-32 characters) <br /> 
			<input id="screenNameInput" value="` + accountInfo["screenName"] + `"> </input>
			<br />
			<span class="smallText grayText"> This is the name that other users see. It doesn't need to be unique. </span>
		</div>
		<div class="bottomMargin">
			Account Name (1-24 characters) <br /> 
			<input id="accountNameInput" value="` + accountInfo["accountName"] + `"> </input>
			<br />
			<span class="smallText grayText"> This is the name you use for logging in. It has to be unique. </span>
		</div>
		<div class="bottomMargin">
			Current Password <br /> 
			<input id="currentPasswordInput" type="password"> </input>
		</div>
		<div class="bottomMargin">
			New Password <br /> 
			<input id="newPasswordInput" type="password"> </input>
			<br />
			<span class="smallText grayText"> Leave this blank to keep your current password </span>
		</div>
		<div class="bottomMargin"> 
			<button id='editAccountCancelButton'> Cancel </button> <button id='editAccountGoButton'> Save Changes </button> 
		</div>
		<div class="bottomMargin" id="editAccountStatus"> </div>
		`;

		var accountEditController = new AbortController();

		var editAccountCancelButton = document.getElementById("editAccountCancelButton");
		editAccountCancelButton.addEventListener("click", function() {
			accountEditController.abort();
			showRegisteredAccountOptions(accountInfo);
		});
		var editAccountGoButton = document.getElementById("editAccountGoButton");
		editAccountGoButton.disabled = true;
		var editAccountStatus = document.getElementById("editAccountStatus");

		var currentPasswordInput = document.getElementById("currentPasswordInput");
		var screenNameInput = document.getElementById("screenNameInput");
		var accountNameInput = document.getElementById("accountNameInput");

		parent.addEventListener("keyup", function() {
			editAccountStatus.innerHTML = "";
			if (currentPasswordInput.value.length > 0 && screenNameInput.value.length > 0 && accountNameInput.value.length > 0) {
				editAccountGoButton.disabled = false;
			}
			else {
				editAccountGoButton.disabled = true;
			}
		});

		editAccountGoButton.addEventListener("click", function() {
			editAccountStatus.innerHTML = "Saving...";
			fetch("PHP/updateAccountInfo.php", {
				method: "POST",
				body: JSON.stringify({
					sessionID: sessionID,
					newScreenName: screenNameInput.value,
					newAccountName: accountNameInput.value,
					currentPassword: currentPasswordInput.value,
					newPassword: newPasswordInput.value
				}),
				signal: accountEditController.signal
			})
			.then(response => response.text())
			.then(data => {
				console.log(data);
				data = JSON.parse(data);

				if (!data["querySuccess"]) {
					editAccountStatus.innerHTML = "Failed to make changes";
					if (data["failReason"]) {
						editAccountStatus.innerHTML += ": " + data["failReason"];
					}
				}
				else {
					showAccountInfo();
				}
			})
			.then(error => console.log(error));
		});
	}

	function showRegisteredAccountOptions(accountInfo) {
		parent = document.getElementById("accountOptions");
		parent.innerHTML = `
		<button id="signoutButton"> Sign Out </button>
		<button id="editAccountButton"> Edit Account Info </button>
		`;

		var signoutButton = document.getElementById("signoutButton");
		signoutButton.addEventListener("click", showSignout);

		var editAccountButton = document.getElementById("editAccountButton");
		editAccountButton.addEventListener("click", function() {
			showAccountEditing(accountInfo);
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
			parent.dataset.userID = userID;

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
				parent.dataset.isRegistered = true;
				showRegisteredAccountOptions({
					screenName: data["screenName"], 
					accountName: data["accountInfo"]["accountName"]
				});
			}
			else {
				// options for guests
				showGuestAccountOptions();
			}
		})
		.catch(error => console.log(error));
	}

	function showMessageEditing(messageID) {
		var parent = document.getElementById("message" + messageID + "body");
		var messageContent = parent.innerHTML;

		parent.innerHTML = `
		<div class="rowFlex">
			<textarea id="message` + messageID + `editInput"></textarea>
		</div>
		<div>
			<button id="message` + messageID + `editCancel"> Cancel (ESC) </button>
			<button id="message` + messageID + `editSave"> Save (ENTER) </button>
		</div>
		`;

		var editInput = document.getElementById("message" + messageID + "editInput");
		editInput.style.flexGrow = 1;
		editInput.style.resize = "none";
		editInput.rows = 5;
		editInput.value = messageContent;
		editInput.focus();
		
		var editSaveButton = document.getElementById("message" + messageID + "editSave");
		editSaveButton.className = "smallText";

		var editCancelButton = document.getElementById("message" + messageID + "editCancel");
		editCancelButton.className = "smallText";

		editCancelButton.addEventListener("click", function() {
			parent.innerHTML = messageContent;
		})

		editSaveButton.addEventListener("click", function() {
			fetch("PHP/editMessage.php", {
				method: "POST",
				body: JSON.stringify({
					sessionID: sessionID,
					messageID: messageID,
					newContent: editInput.value
				})
			})
			.then(response => response.text())
			.then(data => {
				console.log(data);
				if (data["querySuccess"] == true) {
					document.getElementById("chatboxInput").focus();
				}
			})
			.catch(error => console.log(error));
		})

		editInput.addEventListener("keyup", function(e) {
			if (editInput.value.length > 0) {
				editSaveButton.disabled = false;
			}
			else {
				editSaveButton.disabled = true;
			}

			if (e.keyCode == 13 && !e.shiftKey) {
				editSaveButton.click();
			}
			else if (e.keyCode == 27) {
				editCancelButton.click();
			}
		})
	}

	function showMessageDeletion(messageID) {
		var parent = document.getElementById("message" + messageID + "options");
		parent.innerHTML = `<span class="grayText"> Delete Message?&nbsp </span>`;

		var deleteMessageGoButton = document.createElement("button");
		deleteMessageGoButton.className = "smallText";
		deleteMessageGoButton.innerHTML = "Yes";
		deleteMessageGoButton.addEventListener("click", function() {
			fetch("PHP/deleteMessage.php", {
				method: "POST",
				body: JSON.stringify({
					sessionID: sessionID,
					messageID: messageID
				})
			})
			.then(response => response.text())
			.then(data => {
				console.log(data)
			})
			.catch(error => console.log(error));
		});
		parent.appendChild(deleteMessageGoButton);

		var deleteMessageCancelButton = document.createElement("button");
		deleteMessageCancelButton.className = "smallText";
		deleteMessageCancelButton.innerHTML = "No";
		deleteMessageCancelButton.addEventListener("click", function() {
			showMessageOptions(messageID);
		})
		parent.appendChild(deleteMessageCancelButton);
	}

	function showMessageOptions(messageID) {
		var parent = document.getElementById("message" + messageID + "options");
		parent.innerHTML = "";

		var senderID = document.getElementById("message" + messageID).dataset.userID;
		var userID = document.getElementById("accountInfo").dataset.userID;

		// show option to edit message if applicable
		if (userID == senderID && hasPermission("canEditMessages")) {
			var editButton = document.createElement("button");
			editButton.innerHTML = "Edit";
			editButton.className = "smallText";
			editButton.addEventListener("click", function() {
				showMessageEditing(messageID);
			});
			parent.appendChild(editButton);
		}

		// show option to delete message if applicable
		if ((userID == senderID && hasPermission("canDeleteOwnMessages")) 
			|| (hasPermission("canDeleteUserMessages") && !isModerator(senderID) && !isAdministrator(senderID))) {
			var deleteButton = document.createElement("button");
			deleteButton.innerHTML = "Delete";
			deleteButton.className = "smallText";
			deleteButton.addEventListener("click", function() {
				showMessageDeletion(messageID);
			});
			parent.appendChild(deleteButton);
		}
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
		element.dataset.userID = messageInfo["userID"];

		var messageHeader = document.createElement("div");
		element.appendChild(messageHeader);
		messageHeader.className = "rowFlex smallText";
		messageHeader.innerHTML = `
		<div style="flex-grow: 1" class="bottomMargin">
			<span id="`+ elementID + `screenName" class="boldText">` + escapeHTML(messageInfo["screenName"]) + `</span>  
			(userID <span id="`+ elementID + `userID">` + messageInfo["userID"] + `</span>)
			<span class="grayText" id="`+ elementID + `sendTime"> ` + convertDateTime(messageInfo["sendTime"]) + ` </span>
			<span class="grayText" id="`+ elementID + `editTime"> ` + (messageInfo["editTime"] ? "<i>(edited " + convertDateTime(messageInfo["editTime"]) + ")</i>" : "") +  ` </span>
		</div>
		<div id="` + elementID + `options" class="rowFlex" style="justify-content: flex-end"> </div>
		`;

		var messageBody = document.createElement("div");
		element.appendChild(messageBody);
		messageBody.className = "bottomMargin";
		messageBody.id = elementID + "body";
		messageBody.innerHTML = formatMessage(messageInfo["content"]);

		var messageFooter = document.createElement("div");
		element.appendChild(messageFooter);
		messageFooter.className = "smallText";
		// messageFooter.hidden = true;
		// messageFooter.innerHTML = `messageID ` + messageInfo["messageID"];

		element.addEventListener("mouseenter", function() {
			if (!element.dataset.hover || element.dataset.hover == "false") {
				showMessageOptions(messageInfo["messageID"]);
				element.dataset.hover = true;
			}
		})
		element.addEventListener("mouseleave", function() {
			element.dataset.hover = false;
			document.getElementById(elementID + "options").innerHTML = "";
		})

		return element;
	}

	function showMessages(messageList, channelID) {
		if (!cachedMessages[channelID]) {
			cachedMessages[channelID] = {"messageList": messageList};
		}

		parent = document.getElementById("messageStream");
		for (i in messageList) {			// exit if no longer viewing current channel
			if (!parent || parent.dataset.channelID != channelID) return;
			if (messageList[i]["channelID"] && messageList[i]["channelID"] != channelID) continue;

			// add message to stream
			messageElement = document.getElementById("message" + messageList[i]["messageID"]);
			if (!messageElement) {
				parent.appendChild(getMessageElement(messageList[i]));
				cachedMessageList = cachedMessages[channelID]["messageList"];
				if (cachedMessageList.length == 0 || Number(messageList[i]["messageID"]) > Number(cachedMessageList[cachedMessageList.length - 1]["messageID"])) {
					cachedMessages[channelID]["messageList"].push(messageList[i]);
				}
			}
			else {
				messageElement.innerHTML = getMessageElement(messageList[i]).innerHTML;
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

			// leave channel if it gets deleted
			if (data["channelWasDeleted"]) {
				cachedMessages[channelID] = null;
				messageDrafts[channelID] = "";
				channelPermissions[channelID] = null;
				enterChannel(roomID, channelID);
			}
			else {
				parent = document.getElementById("messageStream");
				if (!parent || parent.dataset.channelID != channelID) return;

				var scrolledToBottom = parent.scrollHeight - parent.clientHeight <= parent.scrollTop + 1;

				updateTime = lastUpdateTime;

				// display new messages
				newMessages = data["newMessages"];
				if (newMessages) {
					showMessages(newMessages, channelID);
					cachedMessages[channelID]["updateTime"] = data["updateTime"];
					updateTime = data["updateTime"];
				}

				// handle name changes
				nameChanges = data["nameChanges"];
				if (nameChanges) {
					for (i in cachedMessages[channelID]["messageList"]) {
						var senderID = cachedMessages[channelID]["messageList"][i]["userID"];
						if (nameChanges[senderID]) {
							document.getElementById("message" + cachedMessages[channelID]["messageList"][i]["messageID"] + "screenName").innerHTML = escapeHTML(nameChanges[senderID]);
						}
					}
					updateTime = data["updateTime"];
					if (roomID) {
						showUserLists(cachedRoomInfo[roomID]["userList"]);
					}
				}

				// handle deleted messages
				deletedMessages = data["deletedMessages"];
				if (deletedMessages) {
					for (i in cachedMessages[channelID]["messageList"]) {
						var messageListing = cachedMessages[channelID]["messageList"][i];
						if (deletedMessages[messageListing["messageID"]] 
							&& Date.parse(deletedMessages[messageListing["messageID"]]["deleteTime"]) > Date.parse(messageListing["sendTime"])) {
							cachedMessages[channelID]["messageList"].splice(i, 1);
							document.getElementById("message" + messageListing["messageID"]).remove();
						}
					}
					updateTime = data["updateTime"];
				}

				// handle permission changes
				permissionSettings = data["permissionSettings"];
				if (permissionSettings && permissionSettings.length > 0) {
					updateTime = data["updateTime"];
					for (i in permissionSettings) {
						cachedRoomInfo[roomID]["permissionSettings"][i] = permissionSettings[i];
					}
				}

				// keep message stream scrolled to bottom if currently scrolled to bottom
				parent = document.getElementById("messageStream");
				if (!parent || parent.dataset.channelID != channelID) return;
				if (scrolledToBottom) {
					parent.scrollTop = parent.scrollHeight - parent.clientHeight;
				}

				// fetch additional updates
				setTimeout(() => { getChatUpdates(channelID, updateTime); }, 1000);
				if (permissionSettings && permissionSettings.length > 0) {
					handlePermissions();
				}
			}
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
			<div id="messageStream" class="padded centerRegionElement">
			</div>
			<div id="chatBox" class="padded columnFlex centerRegionElement">
				<textarea style="flex-grow: 1" id="chatboxInput" class="bottomMargin"></textarea>
				<div id="chatboxFooter" style="flex-grow:0" class="rowFlex"> 
					<span id="chatboxInfo" class="smallText" style="flex-grow:1"> <span id="characterCount"></span> / <span id="maxMessageLength"></span> characters.  Press ENTER to send. </span>
					<button id="sendButton" class="rightFlexAlign" style="flex-grow:0"> Send Message </button>
				</div>
			</div>
		</div>
		`;

		var messageStream = document.getElementById("messageStream");
		messageStream.dataset.channelID = channelID;
		var chatInput = document.getElementById("chatboxInput");
		var sendButton = document.getElementById("sendButton");

		var characterCount = document.getElementById("characterCount");
		var maxMessageLength = document.getElementById("maxMessageLength");

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
		chatInput.maxLength = 512;
		chatInput.style.resize = "none";
		chatInput.style.rows = 4;
		chatInput.placeholder = "Type your message here";

		maxMessageLength.innerHTML = chatInput.maxLength;

		if (messageDrafts[channelID]) {
			chatInput.value = messageDrafts[channelID];
		}
		chatInput.focus();

		characterCount.innerHTML = chatInput.value.length;

		// set event handlers
		if (chatInput.value.length == 0) {
			sendButton.disabled = true;
		}
		chatInput.addEventListener("keyup", function(e) {
			characterCount.innerHTML = chatInput.value.length;
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
				// save current message contents
				messageDrafts[channelID] = chatInput.value;
			}
		});
		chatInput.addEventListener("change", function() {
			characterCount.innerHTML = chatInput.value.length;
		})
		sendButton.addEventListener("click", function() {
			if (chatInput.value.length > 0) {
				var messageContent = chatInput.value;

				chatInput.readonly = true;
				chatInput.value = "Sending...";
				messageDrafts[channelID] = "";
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
					characterCount.innerHTML = chatInput.value.length;
				})
				.catch(error => console.log(error));
			}
			else {
				sendButton.disabled = true;
			}
		})

		if (cachedRoomInfo[roomID]) {
			handlePermissions();
		}
	}

	function getUserElement(userInfo) {
		userElement = document.createElement("div");
		elementID = "user" + userInfo["userID"];
		userElement.id = elementID;
		userElement.dataset.userID = userInfo["userID"];
		userElement.dataset.screenName = userInfo["screenName"];
		userElement.className = "bottomMargin " + (userInfo["userID"] == userID ? "selectedListing" : "clickableListing");
		userElement.innerHTML = `
		<span id="`+elementID+`screenName" class="lightGrayText">`+escapeHTML(userInfo["screenName"])+`</span>
		<span class="normalText darkGrayText"> (userID: <span id="`+elementID+`userID">`+userInfo["userID"]+`</span>) </span>
		<span id="`+elementID+`options"></span>
		`;

		if (userInfo["updateTime"]) {
			userElement.innerHTML += `
			<br />
			<span class="smallText normalText grayText">Visited this room `+ getContextualTime(userInfo["updateTime"], cachedRoomInfo[roomID]["updateTime"]) + `</span>
			`;
		}
		else {
			userElement.innerHTML += `
			<br />
			<span class="smallText normalText grayText">No recent activity in this room</span>
			`;
		}

		userElement.addEventListener("mouseenter", function() {
			var userElement = this;
			if (!userElement.dataset.hover || userElement.dataset.hover == "false") {
				userElement.dataset.hover = true;
				if (userElement.dataset.userID != userID && 
					(isModerator(userElement.dataset.userID) && hasPermission("canAppointModerators"))
					|| (isAdministrator(userElement.dataset.userID) && hasPermission("canAppointAdministrators") && cachedRoomInfo[roomID]["administrators"].length > 1) ) {
					removeModeratorButton = document.createElement("button");
					removeModeratorButton.className = "smallText";
					removeModeratorButton.innerHTML = "Remove";
					removeModeratorButton.addEventListener("click", function() {
						if (window.confirm("Remove " + userElement.dataset.screenName + " from the moderation team?")) {
							fetch("PHP/addModerator.php", {
								method: "POST",
								body: JSON.stringify({
									sessionID: sessionID,
									roomID: roomID,
									moderatorID: userElement.dataset.userID,
									rank: null
								})
							})
							.then(response => response.text())
							.then(data => {
								console.log(data);
							})
							.catch(error => console.log(error))
						}
					})
					document.getElementById("user" + userElement.dataset.userID + "options").appendChild(removeModeratorButton);
				}
			}
		})
		userElement.addEventListener("mouseleave", function() {
			var userElement = this;
			userElement.dataset.hover = false;
			document.getElementById("user" + userElement.dataset.userID + "options").innerHTML = "";
		})

		return userElement;
	}

	function showUserLists(userList) {
		var parent = document.getElementById("userList");
		parent.innerHTML =
		`
		<div id="administratorsHeader" class="padded boldText silverText"> Room Administrators </div>
		<div id="administratorsBody" class="padded bottomMargin"></div>
		<div id="moderatorsHeader" class="padded boldText silverText"> Room Moderators </div>
		<div id="moderatorsBody" class="padded bottomMargin"></div>
		<div id="usersHeader" class="padded boldText silverText"> Other Recently Online Users </div>
		<div id="usersBody" class="padded bottomMargin"></div>
		`;

		for (i in userList) {
			if (!document.getElementById("user" + userList[i]["userID"])) {
				userElement = getUserElement(userList[i]);
				if (isAdministrator(userElement.dataset.userID)) {
					document.getElementById("administratorsBody").appendChild(userElement);
				}
				else if (isModerator(userElement.dataset.userID)) {
					if (!document.getElementById("user" + userList[i]["userID"])) {
						document.getElementById("moderatorsBody").appendChild(userElement);
					}
				}
				if (!document.getElementById("user" + userElement.dataset.userID)) {
					if (!isModerator(userElement.dataset.userID)) {
						document.getElementById("usersBody").appendChild(userElement);
					}
					else {
						document.getElementById("moderatorsBody").appendChild(userElement);
					}
				}
			}
		}

		var moderators = cachedRoomInfo[roomID]["moderators"];
		for (i in moderators) {
			if (!document.getElementById("user" + moderators[i]["userID"])) {
				document.getElementById("moderatorsBody").appendChild(getUserElement(moderators[i]));
			}
		}

		var administrators = cachedRoomInfo[roomID]["administrators"];
		for (i in administrators) {
			if (!document.getElementById("user" + administrators[i]["userID"])) {
				document.getElementById("administratorsBody").appendChild(getUserElement(administrators[i]));
			}
		}

		if (hasPermission("canAppointModerators")) {
			addModeratorButton = document.createElement("button");
			addModeratorButton.innerHTML = "Add Moderator";
			addModeratorButton.className = "smallText bottomMargin";
			addModeratorButton.id = "addModeratorButton";
			addModeratorButton.addEventListener("click", function() {
				var moderatorID = window.prompt("Enter the ID of the user to add as a moderator (registered users only)");
				moderatorID = (moderatorID != null && moderatorID != "" ? Number(moderatorID) : null);
				if (moderatorID != null && moderatorID != NaN) {
					fetch("PHP/addModerator.php", {
						method: "POST",
						body: JSON.stringify({
							sessionID: sessionID,
							roomID: roomID,
							moderatorID: moderatorID,
							rank: 0
						})
					})
					.then(response => response.text())
					.then(data => {
						console.log(data);
						data = JSON.parse(data);
						if (!data["querySuccess"]) {
							if (data["failReason"]) alert("Error: " + data["failReason"]);
						}
					})
					.catch(error => console.log(error))
				}
			})
			document.getElementById("moderatorsBody").appendChild(addModeratorButton);
		}

		if (hasPermission("canAppointAdministrators")) {
			addAdministratorButton = document.createElement("button");
			addAdministratorButton.innerHTML = "Add Administrator";
			addAdministratorButton.className = "smallText bottomMargin";
			addAdministratorButton.id = "addAdministratorButton";
			addAdministratorButton.addEventListener("click", function() {
				var moderatorID = window.prompt("Enter the ID of the user to add as an administrator (registered users only)");
				moderatorID = (moderatorID != null && moderatorID != "" ? Number(moderatorID) : null);
				if (moderatorID != null && moderatorID != NaN) {
					fetch("PHP/addModerator.php", {
						method: "POST",
						body: JSON.stringify({
							sessionID: sessionID,
							roomID: roomID,
							moderatorID: moderatorID,
							rank: 1
						})
					})
					.then(response => response.text())
					.then(data => {
						console.log(data);
						data = JSON.parse(data);
						if (!data["querySuccess"]) {
							if (data["failReason"]) alert("Error: " + data["failReason"]);
						}
					})
					.catch(error => console.log(error))
				}
			})
			document.getElementById("administratorsBody").appendChild(addAdministratorButton);
		}
	}

	function enterLastChannel() {
		// go to most recently-visited channel that 
	}

	function enterRoom_helper(roomID) {
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
				// enter first listed channel
				channelID = data["channelList"][0]["channelID"];
				enterChannel(roomID, channelID);
			}
			else {
				enterRoom(defaultRoomID);
			}
		}).catch(error => {
			console.log(error);
			enterRoom(defaultRoomID);
		});
	}

	function enterRoom(roomID) {
		abortChannelFetches();

		// enter most recently-visted channel in room
		if (lastVisitedChannelByRoom[roomID]) {
			channelID = lastVisitedChannelByRoom[roomID];
			enterChannel(roomID, channelID);
		}
		else {
			// get list of channels associated with room if room not visited before
			fetch("PHP/getLastValidChannel.php", {
				method: "POST",
				body: JSON.stringify({
					roomID: roomID,
					sessionID: sessionID 
				})
			})
			.then(response => response.text())
			.then(data => {
				console.log(data);
				data = JSON.parse(data);

				if (data["channelID"]) {
					channelID = data["channelID"];
					enterChannel(roomID, channelID);
				}
				else {
					enterRoom_helper(roomID);
				}
			})
			.catch(error => {
				console.log(error);
				enterRoom_helper(roomID);
			});
		}
	}

	function enterChannel(p_roomID, p_channelID) {
		abortChannelFetches();

		roomID = p_roomID;
		channelID = p_channelID;

		if (channelID == 0) channelID = 1;

		if (channelID) {
			// record channelID and display channel information and chat stream
			document.cookie = "channelID=" + channelID;
			history.replaceState({channelID: channelID}, "", "?channelID=" + channelID);

			showRoomInfo(channelID);
			showChat(channelID);
			// showUserLists(roomID, channelID);
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
		$queryResult = $db->query("select getSessionUser('$sessionID')");
		$userID = null;
		if (!$queryResult || !$queryResult->fetch_row()[0]) {
			$sessionID = null;
		}
		else if ($queryResult->fetch_row()) {
			$userID = $queryResult->fetch_row()[0];
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
		if (!$channelID) {
			$queryResult = $db->query("select getLastVisitedChannel('$userID', null)");
			if ($queryResult) {
				$channelID = $queryResult->fetch_row()[0];
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
			showPage(roomID, channelID);
		})
		.catch((error) => console.log(error))
	}
	else {
		// render page content otherwise
		showPage(roomID, channelID);
	}

</script>
