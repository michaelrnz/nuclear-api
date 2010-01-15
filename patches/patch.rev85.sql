/* REVISION 85 Patch */

/* no optimization in view selecting by name */

alter table nu_user drop key `domain`, add unique key(`name`,`domain`);


/* NuclearAuthorized view for grabbing user data on login */

create view NuclearAuthorized as (
 select NuclearUser.*, U.email, S.level, S.verified, (S.level+0) as level_id 
 from NuclearUser 
 left join nuclear_user U on U.id=NuclearUser.id 
 left join nuclear_system S on S.id=NuclearUser.id
);
