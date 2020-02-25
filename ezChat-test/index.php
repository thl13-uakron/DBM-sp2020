<!--
<?php 
$ip = $_SERVER["SERVER_ADDR"];
echo "Server address: $ip";
?>
-->

<style>
	body {
		display: flex;
		flex-direction: row;
	}
</style>

<body>
	<div id="leftRegion" class="region sideRegion">
		<div id="dmList">
			DMs
		</div>
		<div id="roomList">
			<div id="recentRooms">
				Recent Rooms
			</div>
			<div id="allRooms">
				Public Rooms
			</div>
		</div>
	</div>

	<div id="centerRegion" class="region centerRegion">
		<div id="chatRegion">
			<div id="messageStream">
				Messages
			</div>
			<div id="chatBox">
				Message Box
			</div>
		</div>
	</div>

	<div id="rightRegion" class="region sideRegion">
		<div id="userList">
			Users
		</div>
	</div>
</body>