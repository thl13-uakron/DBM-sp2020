use ezChat;

create table Users(
	userID integer primary key,
	screenName varchar(32) not null
);
create table Accounts(
	userID integer primary key,
	accountName varchar(24) unique not null,
	password varchar(64) not null,
	Email varchar(64),
	registrationDate dateTime not null,
    
	foreign key (userID) references Users(userID)
);
# Users includes all people posting on the site, including guests, while Accounts comprises the subset of Users who are registered

create table Rooms(
	roomID integer primary key,
	roomName varchar(32) not null,
	description varchar(128) not null,
	browsable boolean not null,
	public boolean not null,
	password varchar(64),
	creatorID int not null,
	creationDate dateTime not null,

	Foreign key (creatorID) references Users(userID)
);
# browsable determines whether the room appears as part of the public list or if it can only be accessed by URL
# public determines whether the room can be entered by people who have not been granted explicit permission
# The password, if not set to null, can be used to provide the user with access to a private room

create table Channels(
	channelID integer primary key,
	channelName varchar(24) not null,
	description varchar(64) not null,
	roomID integer,
	creatorID int not null,
	creationDate dateTime not null,
	
	Foreign key (roomID) references Rooms(roomID),
	Foreign key (creatorID) references Users(userID)
);
# The roomID is set to null for Direct Message channels, which contain posts intended to be viewed by only a specific user or group of users

create table DMs(
	channelID integer not null,
	userID integer not null,

	Foreign key (channelID) references Channels(channelID),
	Foreign key (userID) references Users(userID)
);
# Like in Discord, direct message channels follow the same format as regular channels, but they’re independent of rooms and can only be accessed by users who’ve been added to them, as is tracked in the DMs table

create table Messages(
	messageID integer primary key,
	channelID integer not null,
	userID integer not null,
	content varchar(512) not null,
	sendTime dateTime not null,

	Foreign key (channelID) references Channels(channelID),
	Foreign key (userID) references Users(UserID)
);

# Everything below this comment hasn't been added to the db yet

create table PunishmentIndex(
	punishmentID integer primary key,
	punishmentName varchar(16) not null
);
create table Punishments(
	giverID integer not null,
	receiverID integer not null,
	punishmentID integer not null,
	channelID integer,
	roomID integer,
	timeGiven dateTime not null,
	length integer,
	reason varchar(64),

	Foreign key (giverID) references Users(userID),
	Foreign key (receiverID) references Users(userID),
	Foreign key (punishmentID) references Punishments(punishmentID),
	Foreign key (channelID) references Channels(channelID),
	Foreign key (roomID) references Rooms(roomID),
	Check (length > 0)
);

create table UserLabelIndex(
	userLabelID integer primary key,
	labelName varchar(16) not null,
	implicit boolean not null
);
create table UserLabels(
	userID integer not null,
	userLabelID integer not null,
	roomID integer,

	Foreign key (userID) references Users(userID),
	Foreign key (userLabelID) references UserLabelIndex(userLabelID),
	Foreign key (roomID) references Rooms(roomID)
);

create table PermissionIndex(
	permissionID integer primary key,
	permissionName varchar(16) not null
);
create table Permissions(
	roomID integer,
	channelID integer,
	userLabelID integer,
	userID integer,
	permissionID integer not null,
	permissionValue integer not null,

	Foreign key (roomID) references Rooms(roomID),
	Foreign key (channelID) references Channels(channelID),
	Foreign key (userLabelID) references UserLabelIndex(userLabelID),
	Foreign key (userID) references Users(userID),
	Foreign key (permissionID) references PermissionIndex(permissionID),

	Unique(roomID, permissionID),
	Unique(channelID, permissionID),
	Unique(userLabelID, permissionID),
	Unique(userID, permissionID)
);
