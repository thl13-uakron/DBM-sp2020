
delimiter //
create procedure addUser(in p_screenName varchar(32), inout p_userID integer)
	begin
		set p_userID = (select max(userID) from Users) + 1;
		insert into Users(userID, screenName) values (p_userID, p_screenName);
	end //
    
create procedure addAccount(in p_userName varchar(24), in p_password varchar(64), inout p_userID integer)
	begin
		call addUser(p_userName, p_userID);
        insert into Accounts(userID, accountName, password, registrationDate) values
			(p_userID, p_userName, p_password, NOW());
    end //
    
create function login(p_userName varchar(24), p_password varchar(64))
	returns integer
    return (select userID from Accounts where accountName = p_userName and password = p_password);
    end //
    
    
delimiter ;
