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

<script>
	// functions for dynamically generating page content

	function showAccountInfo() {
		var parent = document.getElementById("accountInfo");
		parent.innerHTML = "Account Info";
	}

</script>

<body>
	<div id="leftRegion" class="region padded">
		<div id="roomInfo">

		</div>
		<div id="roomList">
			<div id="dmList">
				Your DMs
			</div>
			<div id="recentRooms">
				Your Recent Rooms
			</div>
			<div id="allRooms">
				Public Rooms
			</div>
		</div>
	</div>

	<div id="centerRegion" class="region">
		<div id="chatRegion" class="region">
			<div id="messageStream" class="padded" style="flex-grow: 5">
				Messages
			</div>
			<div id="chatBox" class="padded" style="flex-grow: 1; display: flex">
				<textarea style="flex-grow: 1"></textarea>
			</div>
		</div>
	</div>

	<div id="rightRegion" class="region padded">
		<div id="accountInfo">
			<script> showAccountInfo(); </script>
		</div>
		<div id="userList" style="flex-grow: 2">
			Other Users
		</div>
	</div>
</body>