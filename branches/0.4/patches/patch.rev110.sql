/*
    DROP PREVIOUS KEY COLUMNS
*/

alter table nuclear_userkey drop column pass;
alter table nuclear_api_auth drop column auth_key;
