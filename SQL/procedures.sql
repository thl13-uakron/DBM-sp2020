use ezChat;

delimiter //
create procedure addUser(in p_screenName varchar(32), inout p_userID integer)
	begin
		set p_userID = (select max(userID) from Users) + 1;
		insert into Users(userID, screenName) values (p_userID, p_screenName);
	end //
    
create procedure addAccount(in p_userName varchar(24), in p_password varchar(64), inout p_userID integer)
	begin
		if p_userName not in (select accountName from Accounts) then
			call addUser(p_userName, p_userID);
			insert into Accounts
            (userID, accountName, password, registrationDate) values
				(p_userID, p_userName, p_password, NOW());
		end if;
    end //
    
create function login(p_userName varchar(24), p_password varchar(64))
	returns integer
    begin
    return (select userID from Accounts where accountName = p_userName and password = p_password);
    end //
    
create function getScreenName(p_userID integer)
	returns varchar(32)
    begin
    return (select screenName from Users where userID = p_userID);
    end //
    
create procedure updateScreenName(p_userID integer, p_screenName varchar(32))
	begin
    update Users set screenName = p_screenName where userID = p_userID;
    end //
    
create function isRegistered(p_userID integer)
	returns boolean
    begin
    return (p_userID in (select userID from Accounts));
    end //
    
create procedure getAccountInfo(in p_userID integer)
	begin
    select accountName, password, email, registrationDate from Accounts where userID = p_userID;
    end //
        
create procedure updateAccountInfo(in p_userID integer, in p_userName varchar(24), in p_password varchar(64), in p_email varchar(64))
	begin
    update Accounts set userName = p_userName, password = p_password, email = p_email
    where userID = p_userID;
    end //

create procedure addChannel(in p_channelName varchar(24), in p_description varchar(64), in p_roomID integer, in creatorID integer, inout p_channelID integer)
	begin
    set p_channelID = (select max(channelID) from Channels) + 1;
    insert into Channels(channelID, channelName, description, roomID, creatorID, creationTime)
	values (p_channelID, p_channelName, p_description, p_roomID, p_creatorID, NOW());
    end //
    
create procedure addRoom(in p_roomName varchar(32), in p_description varchar(128), in p_browsable boolean, in p_public boolean, 
	in p_password varchar(64), in p_creatorID integer, inout p_roomID integer)
	begin
    set p_roomID = (select max(roomID) from Rooms) + 1;
    insert into Rooms(roomID, roomName, description, browsable, public, password, creatorID, creationTime)
    values (p_roomID, p_roomName, p_description, p_browsable, p_public, p_password, p_creatorID, NOW());
    call addChannel('main', p_roomID, p_creatorID, @p_channelID);
    end //

create procedure getChannelInfo(in p_channelID integer)
	begin
    select channelName, description, creatorID, getScreenName(creatorID) as creatorName, creationTime from Channels 
    where channelID = p_channelID;
    end //

create procedure getChannelsByRoom(in p_roomID integer)
	begin
    select channelID, channelName from Channels where roomID = p_roomID;
    end //
    
create procedure getRoomInfo(in p_roomID integer)
	begin
    select roomName, description, creatorID, getScreenName(creatorID) as creatorName, creationTime 
    from Rooms where roomID = p_roomID;
    end //

create procedure getRoomList()
	begin
    select roomID, roomName, public from Rooms where browsable = true;
    end //
    
create procedure updateChannelInfo(in p_channelID integer, in p_channelName varchar(64), in p_description varchar(64))
	begin
    update Channels set channelName = p_channelName, description = p_description where channelID = p_channelID;
    end //
    
create procedure updateRoomInfo(in p_roomID integer, in p_roomName varchar(32), in p_description varchar(128), in p_browsable boolean,
	in p_public boolean, in p_password varchar(64))
    begin
    update Rooms set roomName = p_roomName, description = p_description, browsable = p_browsable, public = p_public, password = p_password 
    where roomID = p_roomID;
    end //
    
create procedure deleteRoom(in p_roomID integer) 
	begin
    delete from Messages where channelID in (select channelID from Channels where roomID = p_roomID);
    delete from Channels where roomID = p_roomID;
    delete from Rooms where roomID = p_roomID;
    end //
    
create procedure deleteChannel(in p_channelID integer)
	begin
    delete from Messages where channelID = p_channelID;
    delete from Channels where channelID = p_channelID;
    end //
    
create procedure postMessage(in p_userID integer, in p_channelID integer, in p_content varchar(512), inout p_messageID integer)
	begin
    set p_messageID = (select max(messageID) from Messages) + 1;
    insert into Messages(messageID, userID, channelID, content, sendTime) values (p_messageID, p_userID, p_channelID, p_content, NOW());
    end //
    
create procedure getMessages(in p_channelID integer)
	begin
    select messageID, userID, screenName, content, sendTime from 
    (select messageID, userID, content, sendTime from Messages where channelID = p_channelID)M
    left join (select screenName from Users)U on M.userID = U.userID;
    end //
    
create procedure deleteMessage(in p_messageID integer)
	begin
    delete from Messages where messageID = p_messageID;
    end //

delimiter ;
