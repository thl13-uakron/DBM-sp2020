use ezChat;

create table Users(
	userID integer primary key,
	screenName varchar(32) not null,
	lastNameChange dateTime default null,

	check (userID > 0),
	check (length(screenName) > 0)
);
create table Accounts(
	userID integer primary key,
	accountName varchar(24) unique not null,
	password varchar(64) not null,
	Email varchar(64),
	registrationDate dateTime not null,
    
	foreign key (userID) references Users(userID) on delete cascade,
	check (length(accountName) > 0),
	check (length(password) > 0)
);
# Users includes all people posting on the site, including guests, while Accounts comprises the subset of Users who are registered

create table Rooms(
	roomID integer primary key,
	roomName varchar(32) not null,
	description varchar(128) not null,
	browsable boolean not null,
	public boolean not null,
	password varchar(64),
	creatorID int,
	creationDate dateTime not null,
	editTime dateTime default null,

	Foreign key (creatorID) references Users(userID) on delete set null,
	check (roomID > 0),
	check (length(roomName) > 0)
);
# browsable determines whether the room appears as part of the public list or if it can only be accessed by URL
# public determines whether the room can be entered by people who have not been granted explicit permission
# The password, if not set to null, can be used to provide the user with access to a private room

create table Channels(
	channelID integer primary key,
	channelName varchar(24) not null,
	description varchar(64) not null,
	roomID integer,
	creatorID int,
	creationDate dateTime not null,
	editTime dateTime default null,
	
	Foreign key (roomID) references Rooms(roomID) on delete cascade,
	Check (channelID > 0),
	Check (length(channelName) > 0)
	Foreign key (creatorID) references Users(userID) on delete set null
);
# The roomID is set to null for Direct Message channels, which contain posts intended to be viewed by only a specific user or group of users

create table DMs(
	channelID integer not null,
	userID integer not null,

	Foreign key (channelID) references Channels(channelID) on delete cascade,
	Foreign key (userID) references Users(userID) on delete cascade
);
# Like in Discord, direct message channels follow the same format as regular channels, but they’re independent of rooms and can only be accessed by users who’ve been added to them, as is tracked in the DMs table

create table Messages(
	messageID integer primary key,
	channelID integer not null,
	userID integer,
	content varchar(512) not null,
	sendTime dateTime not null,
	editTime dateTime default null,

	Foreign key (channelID) references Channels(channelID) on delete cascade,
	Foreign key (userID) references Users(UserID) on delete set null,
	check (messageID > 0),
	check (length(content) > 0),
	check (editTime > sendTime or editTime is null)
);

create table Sessions(
	sessionID integer primary key,
	userID integer not null,

	foreign key (userID) references Users(userID) on delete cascade
);

create table ChannelVisits(
	userID integer not null,
	channelID integer not null,
	updateTime dateTime not null,

	foreign key (userID) references Users(userID) on delete cascade,
	foreign key (channelID) references Channels(channelID) on delete cascade,
	primary key (userID, channelID)
);

create table DeletedMessages(
	messageID integer not null,
	channelID integer references Channels(channelID) on delete cascade,
	senderID integer references Users(userID) on delete set null,
	deleterID integer references Users(userID) on delete set null,
	content varchar(512) not null,
	sendTime dateTime not null,
	deleteTime dateTime not null
);

create table DeletedRooms(
	roomID integer not null,
	deleteTime dateTime not null
);

create table DeletedChannels(
	channelID integer not null,
	roomID integer,
	deleteTime dateTime not null
);

create table Moderators(
	userID integer not null,
	roomID integer not null,
	rank integer,
	appointmentTime dateTime not null,

	foreign key (userID) references Users(userID) on delete cascade,
	foreign key (roomID) references Rooms(roomID) on delete cascade, 
	primary key(userID, roomID)
);

create table PermissionValues(
	value integer primary key,
	description varchar(40),

	check (`value` > 0)
);
insert into PermissionValues(value, description) values 
	(1, 'Everyone'),
	(2, 'Registered users only'),
	(3, 'Moderators and administrators only'),
	(4, 'Administrators only');

create table PermissionIndex(
	permissionID integer primary key,
	permissionName varchar(24) unique not null,
	descriptor varchar(80) not null,
	defaultVal integer references PermissionValues(value),
	minVal integer references PermissionValues(value),
	maxVal integer references PermissionValues(value),
	channelSpecific boolean not null,

	check (permissionID > 0)
);
insert into PermissionIndex(permissionID, permissionName, descriptor, defaultVal, minVal, maxVal, channelSpecific) values
	(1, 'canEnter', 'can enter', 1, 1, 3, 1),
	(2, 'canPostMessages', 'can post messages in', 1, 1, 4, 1),
	(3, 'canEditMessages', 'can edit their own messages in', 2, 1, 4, 1),
	(4, 'canDeleteOwnMessages', 'can delete their own messages in', 2, 1, 4, 1),
	(5, 'canDeleteUserMessages', 'can delete messages posted by any non-moderator in', 3, 2, 4, 1),
	(6, 'canMuteUsers', 'can mute and unmute users in', 3, 2, 4, 1),
	(7, 'canAddChannel', 'can create and configure new channels', 3, 2, 4, 0),
	(8, 'canDeleteChannels', 'can delete channels created by other users', 4, 2, 4, 0),
	(9, 'canEditChannelSettings', 'can configure settings and permissions for channels created by other users', 4, 2, 4, 0),
	(10, 'canAppointModerators', 'can appoint and remove users as moderators', 4, 3, 4, 0),
	(11, 'canAppointAdministrators', 'can appoint and remove users as administrators', 4, 4, 4, 0),
	(12, 'canEditRoomSettings', 'can configure settings and permissions for this room', 4, 3, 4, 0),
	(13, 'canDeleteRoom', 'can delete this room', 4, 4, 4, 0);

create table PermissionSettings(
	permissionID integer not null,
	roomID integer,
	channelID integer,
	permissionValue integer,
	setTime dateTime,

	foreign key (permissionID) references PermissionIndex(permissionID) on delete cascade,
	foreign key (roomID) references Rooms(roomID) on delete cascade,
	foreign key (channelID) references Channels(channelID) on delete cascade,
	foreign key (permissionValue) references PermissionValues(value) on delete cascade,

	unique key roomSetting (permissionID, roomID),
	unique key channelSetting (permissionID, channelID),
	check(roomID is null or channelID is null)
);