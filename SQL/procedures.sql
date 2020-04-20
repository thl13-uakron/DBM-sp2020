use ezChat;

delimiter //
create procedure addUser(in p_screenName varchar(32), inout p_userID integer)
	begin
		set p_userID = (select max(userID) from Users) + 1;
        if p_userID is null then set p_userID = 1;
        end if;
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
    update Users set screenName = p_screenName, lastNameChange = NOW() where userID = p_userID;
    end //
    
create function isRegistered(p_userID integer)
	returns boolean
    begin
    return (p_userID in (select userID from Accounts));
    end //
    
create procedure getAccountInfo(in p_userID integer)
	begin
    select accountName, email, registrationDate from Accounts where userID = p_userID;
    end //
        
create procedure updateAccountInfo(in p_userID integer, in p_userName varchar(24), in p_password varchar(64), in p_email varchar(64))
	begin
    update Accounts set accountName = p_userName, password = p_password, email = p_email
    where userID = p_userID;
    end //

create function canAddChannel(p_userID integer, p_roomID integer)
	returns boolean
	begin
    return hasRoomPermission(p_userID, p_roomID, 7);
    end //

create procedure addChannel(in p_channelName varchar(24), in p_description varchar(64), in p_roomID integer, in p_creatorID integer, inout p_channelID integer)
	begin
    set p_channelID = (select max(channelID) from Channels) + 1;
    if p_channelID is null then set p_channelID = 1;
        end if;
    insert into Channels(channelID, channelName, description, creatorID, roomID, creationDate)
		values (p_channelID, p_channelName, p_description, p_creatorID, p_roomID, NOW());
    end //
    
create procedure addRoom(in p_roomName varchar(32), in p_description varchar(128), in p_browsable boolean, in p_public boolean, 
	in p_password varchar(64), in p_creatorID integer, inout p_roomID integer)
	begin
    set p_roomID = (select max(roomID) from Rooms) + 1;
    if p_roomID is null then set p_roomID = 1;
		end if;
    insert into Rooms(roomID, roomName, description, browsable, public, password, creatorID, creationDate)
		values (p_roomID, p_roomName, p_description, p_browsable, p_public, p_password, p_creatorID, NOW());
    call addChannel('main', '', p_roomID, p_creatorID, @p_channelID);
    call addAdministrator(p_creatorID, p_roomID);
    end //

create procedure getChannelInfo(in p_channelID integer)
	begin
    select channelName, description, roomID, creationDate from Channels 
    where channelID = p_channelID;
    end //

create procedure getChannelsByRoom(in p_roomID integer)
	begin
    select channelID, channelName, description, creatorID from Channels where roomID = p_roomID;
    end //
    
create procedure getRoomInfo(in p_roomID integer)
	begin
    select roomName, description, creatorID, getScreenName(creatorID) as creatorName, creationDate, browsable 
    from Rooms where roomID = p_roomID;
    end //
    
create function checkRoomInfoUpdates(p_roomID integer, lastUpdateTime dateTime)
	returns boolean
    begin
    return (select (select editTime from Rooms where roomID = p_roomID) > lastUpdateTime);
    end //
    
create procedure getNewChannels(in p_roomID integer, in lastUpdateTime dateTime)
	begin
    select channelID, channelName, description from Channels where roomID = p_roomID and (editTime > lastUpdateTime or creationDate > lastUpdateTime);
    end //

create procedure getRoomList()
	begin
    select roomID, roomName, public from Rooms where browsable = true;
    end //
    
create function canEditChannelInfo(p_userID integer, p_channelID integer)
	returns boolean
    begin
    return (p_userID = (select creatorID from Channels where channelID = p_channelID))
		or hasRoomPermission(p_userID, (select roomID from Channels where channelID = p_channelID), 9);
    end //
    
create function canDeleteChannel(p_userID integer, p_channelID integer)
	returns boolean
    begin
    return (p_userID = (select creatorID from Channels where channelID = p_channelID))
		or hasRoomPermission(p_userID, (select roomID from Channels where channelID = p_channelID), 8);
    end //
    
create procedure updateChannelInfo(in p_channelID integer, in p_channelName varchar(64), in p_description varchar(64))
	begin
    update Channels set channelName = p_channelName, description = p_description, editTime = NOW() where channelID = p_channelID;
    end //
    
create function canEditRoomInfo(p_userID integer, p_roomID integer)
	returns boolean
	begin
    return hasRoomPermission(p_userID, p_roomID, 12);
    end //
    
create function canDeleteRoom(p_userID integer, p_roomID integer)
	returns boolean
    begin
    return hasRoomPermission(p_userID, p_roomID, 13);
    end //
    
create procedure updateRoomInfo(in p_roomID integer, in p_roomName varchar(32), in p_description varchar(128), in p_browsable boolean,
	in p_public boolean, in p_password varchar(64))
    begin
    update Rooms set roomName = p_roomName, description = p_description, browsable = p_browsable, public = p_public, password = p_password, editTime = NOW() 
    where roomID = p_roomID;
    end //
    
create procedure deleteRoom(in p_roomID integer) 
	begin
    insert into DeletedRooms(roomID, deleteTime) values (p_roomID, NOW());
    delete from Messages where channelID in (select channelID from Channels where roomID = p_roomID);
    delete from Channels where roomID = p_roomID;
    delete from Rooms where roomID = p_roomID;
    end //
    
create procedure deleteChannel(in p_channelID integer)
	begin
    if (select count(channelID) from Channels where roomID = (select roomID from Channels where channelID = p_channelID)) > 1
    then
		insert into DeletedChannels(channelID, deleteTime) values (p_channelID, NOW());
		delete from Messages where channelID = p_channelID;
		delete from Channels where channelID = p_channelID;
    end if;
    end //
    
create procedure postMessage(in p_userID integer, in p_channelID integer, in p_content varchar(512), inout p_messageID integer)
	begin
    set p_messageID = (select max(messageID) from Messages) + 1;
    if p_messageID is null then set p_messageID = 1;
        end if;
    insert into Messages(messageID, userID, channelID, content, sendTime) values (p_messageID, p_userID, p_channelID, p_content, NOW());
    end //
    
create procedure getMessages(in p_channelID integer)
	begin
    select messageID, M.userID as userID, screenName, content, sendTime, editTime from 
    (select messageID, userID, content, sendTime,editTime from Messages where channelID = p_channelID)M
    left join (select screenName, userID from Users)U on M.userID = U.userID;
    end //
    
create function canDeleteMessage(p_userID integer, p_messageID integer)
	returns boolean
	begin
    declare p_roomID integer;
    declare p_channelID integer;
    declare senderID integer;
    set p_channelID = (select channelID from Messages where messageID = p_messageID);
    set p_roomID = (select roomID from Channels where channelID = p_channelID);
    set senderID = (select userID from Messages where messageID = p_messageID);
    return ((p_userID = senderID) 
			and hasChannelPermission(p_userID, p_channelID, 4))
		or (hasChannelPermission(p_userID, p_channelID, senderID, 5) 
			and not isModerator(senderID, p_roomID) and not isAdministrator(senderID, p_roomID));
    end //
    
create procedure deleteMessage(in p_userID integer, in p_messageID integer)
	begin
    if (select canDeleteMessage(p_userID, p_messageID))
    then
		insert into DeletedMessages(messageID, channelID, senderID, content, sendTime, deleterID, deleteTime)
			values (p_messageID, (select channelID from Messages where messageID = p_messageID), 
            (select userID from Messages where messageID = p_messageID), 
            (select content from Messages where messageID = p_messageID), 
            (select sendTime from Messages where messageID = p_messageID), p_userID, NOW());
		delete from Messages where messageID = p_messageID;
	end if;
    end //
    
create procedure getMessageDeletions(in p_channelID integer, in lastUpdateTime dateTime)
	begin
    select messageID, sendTime, deleteTime from DeletedMessages where channelID = p_channelID and deleteTime > lastUpdateTime;
    end //
    
create procedure getChannelDeletions(in p_roomID integer, in lastUpdateTime dateTime)
	begin
    select channelID from DeletedChannels where deleteTime > lastUpdateTime and channelID not in (select channelID from Channels where roomID = p_roomID);
    end //
    
create procedure getRoomDeletions(in lastUpdateTime dateTime)
	begin
    select roomID from DeletedRooms where deleteTime > lastUpdateTime and roomID not in (select roomID from Rooms);
    end //
    
create procedure getRoomsToHide(in lastUpdateTime dateTime)
	begin
    select roomID from Rooms where browsable = false and editTime > lastUpdateTime;
    end // 
    
create function roomWasDeleted(p_roomID integer, lastUpdateTime dateTime)
	returns boolean
    begin
	return p_roomID not in (select roomID from Rooms);
		# or p_roomID in (select roomID from DeletedRooms where deleteTime > lastUpdateTime);
    end //

create function channelWasDeleted(p_channelID integer, lastUpdateTime dateTime)
	returns boolean
	begin
    return p_channelID not in (select channelID from Channels) ;
		#or p_channelID in (select channelID from DeletedChannels where deleteTime > lastUpdateTime);
    end //
    
create function canEnterChannel(p_userID integer, p_channelID integer)
	returns boolean
	begin
    return hasChannelPermission(p_userID, p_channelID, 1);
    end //
    
create function getLastVisitedChannel(p_userID integer, p_roomID integer)
	returns integer
    begin
    return (select channelID from ChannelVisits
        where userID = p_userID 
        and updateTime = (select max(updateTime) from ChannelVisits 
               where userID = p_userID 
               AND (p_roomID is null 
                    or p_roomID not in (select roomID from Rooms)
                    or not hasRoomPermission(p_userID, p_roomID, 1)
                    or p_roomID = (select roomID from Channels where channelID = ChannelVisits.channelID))) );
    end //
    
create function validateUser(p_userID integer, p_password varchar(64), allowGuests boolean)
	returns boolean
	begin
    if isRegistered(p_userID) 
		then return p_password = (select password from Accounts where userID = p_userID);
	else 
		if p_userID in (select userID from Users)
			then return allowGuests;
		end if;
		return false;
    end if;
    end //
    
create procedure getNewMessages(p_channelID integer, lastUpdateTime dateTime)
	begin
    select messageID, M.userID as userID, screenName, content, sendTime, editTime, channelID from 
    (select messageID, userID, content, sendTime, editTime, channelID from Messages where channelID = p_channelID and sendTime > lastUpdateTime)M
    left join (select screenName, userID from Users)U on M.userID = U.userID;
    end //
    
create procedure getRoomListUpdates(in lastUpdateTime dateTime)
	begin
    select roomID, roomName, public, browsable from Rooms where (creationDate > lastUpdateTime or editTime > lastUpdateTime);
    end //
    
create procedure getChannelListUpdates(in p_roomID integer, in lastUpdateTime dateTime)
	begin
    select channelID, channelName from Channels where roomID = p_roomID and (creationDate > lastUpdateTime or editTime > lastUpdateTime);
    end //
    
create procedure getNameChanges(in p_channelID integer, in lastUpdateTime dateTime)
	begin
    select R1.userID as userID, screenName from 
		((select distinct userID from Messages where channelID = p_channelID)R1
		inner join 
        (select userID, screenName from Users where lastNameChange > lastUpdateTime)R2
        on R1.userID = R2.userID);
    end //
    
create function canEditMessage(p_userID integer, p_messageID integer)
	returns boolean
    begin
    return p_userID = (select userID from Messages where messageID = p_messageID) 
		and hasChannelPermission(p_userID, (select channelID from Messages where messageID = p_messageID));
	end //
    
create procedure editMessage(in p_userID integer, in p_messageID integer, in newContent varchar(512))
	begin
    if canEditMessage(p_userID, p_messageID)
    then
    update Messages set content = newContent, editTime = NOW() where messageID = p_messageID;
    end if;
    end //

create procedure setSession(in p_userID integer, inout p_sessionID integer)
	begin
    set p_sessionID = (select max(sessionID) from Sessions) + 1;
    if p_sessionID is null then set p_sessionID = 1;
        end if;
	insert into Sessions(sessionID, userID) values (p_sessionID, p_userID);
    end //
    
create procedure clearSession(in p_sessionID integer)
	begin
    delete from Sessions where sessionID = p_sessionID;
    end //
    
create function getSessionUser(p_sessionID integer)
	returns integer
	begin
    return (select userID from Sessions where sessionID = p_sessionID);
    end //
    
create procedure logChannelVisit(in p_userID integer, in p_channelID integer)
	begin
    if (p_userID, p_channelID) not in (select userID, channelID from ChannelVisits)
    then
		insert into ChannelVisits(userID, channelID, updateTime) values (p_userID, p_channelID, NOW());
	else
		update ChannelVisits set updateTime = NOW() where userID = p_userID and channelID = p_channelID;
    end if;
    end //
    
create procedure getRecentRooms(in p_userID integer, in numRooms integer)
	begin
    select distinct R3.roomID as roomID, roomName, public from 
		((select roomID, updateTime from
			((select channelID, userID, updateTime from ChannelVisits where userID = p_userID)R1
			inner join
			(select channelID, roomID from Channels)R2
			on R1.channelID = R2.channelID))R3
        inner join
        (select roomID, roomName, public from Rooms)R4
        on R3.roomID = R4.roomID)
    order by updateTime desc 
    limit numRooms;
    end //

create procedure addModerator(in p_userID integer, in p_roomID integer)
	begin
    if (p_userID, p_roomID) not in (select userID, roomID from Moderators)
	then
		insert into Moderators(userID, roomID, rank, appointmentTime)
        values (p_userID, p_roomID, 0, NOW());
	else if (select rank from Moderators where userID = p_userID and roomID = p_roomID) is null
	then
		update Moderators set appointmentTime = NOW(), rank = 0
        where userID = p_userID and roomID = p_roomID;
        end if;
	end if;
    end //
    
create procedure addAdministrator(in p_userID integer, in p_roomID integer)
	begin
    if (p_userID, p_roomID) not in (select userID, roomID from Moderators)
	then
		insert into Moderators(userID, roomID, rank, appointmentTime)
        values (p_userID, p_roomID, 1, NOW());
	else
		update Moderators set appointmentTime = NOW(), rank = 1
        where userID = p_userID and roomID = p_roomID;
	end if;
    end //
    
create procedure removeModerator(in p_userID integer, in p_roomID integer)
	begin
	if not (isAdministrator(p_userID, p_roomID)
		and (select count(userID) from Moderators where roomID = p_roomID and rank = 1) = 1)
	then 
		update Moderators set appointmentTime = NOW(), rank = null
        where userID = p_userID and roomID = p_roomID;
	end if;
    end //
    
create function isModerator(p_userID integer, p_roomID integer)
	returns boolean
	begin 
    return (select rank from Moderators where userID = p_userID and roomID = p_roomID) = 0;
    end //
    
create function isAdministrator(p_userID integer, p_roomID integer)
	returns boolean
	begin
    return (select rank from Moderators where userID = p_userID and roomID = p_roomID) = 1;
    end //
    
create procedure getModerators(in p_roomID integer)
	begin
    select M.userID as userID, screenName from 
		(select userID from Moderators where roomID = p_roomID and rank = 0)M
        inner join Users
        on M.userID = Users.userID;
    end //
    
create procedure getAdministrators(in p_roomID integer)
	begin
    select M.userID as userID, screenName from 
		(select userID from Moderators where roomID = p_roomID and rank = 1)M
        inner join Users
        on M.userID = Users.userID;
    end //
    
create procedure getModerationChanges(in p_roomID integer, in lastUpdateTime dateTime)
	begin
    select M.userID as userID, screenName, rank from 
		(select userID, rank from Moderators where roomID = p_roomID and appointmentTime > lastUpdateTime)M
        inner join Users
        on M.userID = Users.userID;
    end //
    
create function getModerationRank(p_userID integer, p_roomID integer)
	returns integer
    begin 
    return (select rank from Moderators where userID = p_userID and roomID = p_roomID);
    end //
    
create procedure setRoomPermission(in p_roomID integer, in p_permissionID integer, in p_permissionValue integer)
	begin
    if (p_roomID, p_permissionID) in (select roomID, permissionID from PermissionSettings)
    then
		update PermissionSettings set permissionValue = p_permissionValue, setTime = NOW()
        where roomID = p_roomID and permissionID = p_permissionID;
	else
		insert into PermissionSettings(roomID, permissionID, permissionValue, setTime) values
        (p_roomID, p_permissionID, p_permissionValue, NOW());
	end if;
    end //
    
create procedure setChannelPermission(in p_channelID integer, in p_permissionID integer, in p_permissionValue integer)
	begin
    if (p_channelID, p_permissionID) in (select channelID, permissionID from PermissionSettings)
    then
		update PermissionSettings set permissionValue = p_permissionValue, setTime = NOW()
        where channelID = p_channelID and permissionID = p_permissionID;
	else
		insert into PermissionSettings(channelID, permissionID, permissionValue, setTime) values
        (p_channelID, p_permissionID, p_permissionValue, NOW());
	end if;
    end //
    
create function getRoomPermissionValue(p_roomID integer, p_permissionID integer)
	returns integer
	begin
    declare p_permissionValue integer;
    set p_permissionValue = (select permissionValue from PermissionSettings where roomID = p_roomID and permissionID = p_permissionID);
    if p_permissionValue is not null
    then
		return p_permissionValue;
	else
		return (select defaultVal from PermissionIndex where permissionID = p_permissionID);
	end if;
    end //
    
create function getChannelPermissionValue(p_channelID integer, p_permissionID integer)
	returns integer
	begin
    return (select permissionValue from PermissionSettings where channelID = p_channelID and permissionID = p_permissionID);
    end //
    
create function hasRoomPermission(p_userID integer, p_roomID integer, p_permissionID integer)
	returns boolean
	begin
    declare permissionValue integer;
    set permissionValue = getRoomPermissionValue(p_roomID, p_permissionID);
    if permissionValue = 1 then
		return true;
	end if;
    if permissionValue = 2 then
		return isRegistered(p_userID);
	end if;
    if permissionValue = 3 then
		return isModerator(p_userID, p_roomID) or isAdministrator(p_userID, p_roomID);
	end if;
    return isAdministrator(p_userID, p_roomID);
    end //
    
create function hasChannelPermission(p_userID integer, p_channelID integer, p_permissionID integer)
	returns boolean
	begin
    declare p_roomID integer;
    declare permissionValue integer;
    set p_roomID = (select roomID from Channels where channelID = p_channelID);
    set permissionValue = getChannelPermissionValue(p_channelID, p_permissionID);
    if permissionValue is null then
		return hasRoomPermission(p_userID, p_roomID, p_permissionID);
	end if;
    if permissionValue = 1 then
		return true;
	end if;
    if permissionValue = 2 then
		return isRegistered(p_userID);
	end if;
    if permissionValue = 3 then
		return isModerator(p_userID, p_roomID) or isAdministrator(p_userID, p_roomID);
	end if;
    return isAdministrator(p_userID, p_roomID);
    end //
    
create procedure getRoomPermissionSettings(in p_roomID integer)
	begin
    select permissionID, permissionName, descriptor, minVal, maxVal, channelSpecific, getRoomPermissionValue(p_roomID, permissionID) as permissionValue
    from PermissionIndex;
    end //
    
create procedure getChannelPermissionSettings(in p_channelID integer)
	begin
    select permissionID, permissionName, descriptor, minVal, maxVal, getChannelPermissionValue(p_channelID, permissionID) as permissionValue
    from PermissionIndex where channelSpecific = true;
    end //
    
create procedure getRoomPermissionChanges(in p_roomID integer, in lastUpdateTime dateTime)
	begin
    select permissionID, permissionValue from PermissionSettings where roomID = p_roomID and setTime > lastUpdateTime;
    end //
    
create procedure getChannelPermissionChanges(in p_channelID integer, in lastUpdateTime dateTime)
	begin
    select permissionID, permissionValue from PermissionSettings where channelID = p_channelID and setTime > lastUpdateTime;
    end //
    
create procedure getRecentUsers(in p_channelID integer, in minTime dateTime)
	begin
    select V.userID as userID, screenName, updateTime from
    (select userID, updateTime from ChannelVisits where channelID = p_channelID and (minTime is null or updateTime > minTime))V
    inner join (select userID, screenName from Users)U
    on V.userID = U.userID
    order by updateTime desc;
    end //
    
create procedure getRecentRoomUsers(in p_roomID integer, in minTime dateTime)
	begin
    select V.userID as userID, screenName, updateTime, channelID from
    (select userID, updateTime, channelID from ChannelVisits where (select roomID from Channels where channelID = ChannelVisits.channelID) = p_roomID and (minTime is null or updateTime > minTime))V
    inner join (select userID, screenName from Users)U
    on V.userID = U.userID
    order by updateTime desc;
    end //
    
delimiter ;
