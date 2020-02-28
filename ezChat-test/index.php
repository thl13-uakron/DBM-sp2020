<?php
	$db_host = "sql200.main-hosting.eu";
	$db_name = "u668413793_ezChat";
	$db_user = "u668413793_ezChatDev";
	$db_password = "ezChatDev";

	$db = new mysqli($db_host, $db_user, $db_password, $db_name);
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

	.sideRegion {
		flex-shrink: 0.5;
		padding: 2%;
	}

	.padded {
		padding: 2%;
	}

	#centerRegion {
		flex-grow: 5;
		flex-shrink: 4;
		background-color: lightgray;
		color: black;
	}
</style>

<body>
	<div id="leftRegion" class="region padded">
		<div id="roomInfo">
		</div>

		<div id="roomList">
		</div>
	</div>

	<div id="centerRegion" class="region">
		
	</div>

	<div id="rightRegion" class="region padded">
		<div id="accountInfo">
		</div>

		<div id="userList" style="flex-grow: 2">
		</div>
	</div>
</body>

<script>
	// client parameters
	var userID;

	// functions for dynamically generating page content
	function showRoomInfo() {
		var parent = document.getElementById("roomInfo");
		parent.innerHTML = 
		`
		
		`;
	}
	function showRoomList() {
		var parent = document.getElementById("roomList");
		parent.innerHTML = 
		`
		<div id="dmList">
			Your DMs
		</div>
		<div id="recentRooms">
			Your Recent Rooms
		</div>
		<div id="allRooms">
			Public Rooms
		</div>
		`;
	}
	function showAccountInfo() {
		var parent = document.getElementById("accountInfo");
		parent.innerHTML = 
		`
		Account Info
		`;
	}
	function showChat() {
		var parent = document.getElementById("centerRegion");
		parent.innerHTML = 
		`
		<div id="chatRegion" class="region">
			<div id="messageStream" class="padded" style="flex-grow: 5">
				Messages
			</div>
			<div id="chatBox" class="padded" style="flex-grow: 1; display: flex">
				<textarea style="flex-grow: 1"></textarea>
			</div>
		</div>
		`;
	}
	function showUserList() {
		var parent = document.getElementById("userList");
		parent.innerHTML =
		`
		Other Users
		`;
	}

	function showPage() {
		showRoomInfo();
		showRoomList();
		showChat();
		showAccountInfo();
		showUserList();
	}

	// functions for retrieving data from backend

	// render content
	showPage();
	
</script>
