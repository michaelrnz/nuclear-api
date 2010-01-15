/* RESTRUCTURE KEYS */

alter table nu_packet_hash modify column hash BINARY(20) NOT NULL;
alter table nuclear_userkey drop column verify, add column auth BINARY(20) NOT NULL;
alter table nuclear_api_auth add column auth BINARY(20) NOT NULL AFTER auth_key;
alter table nuclear_verify modify column hash VARCHAR(255) NOT NULL;
alter table nuclear_verify add column auth VARCHAR(255) NOT NULL, drop column pass;
alter table nuclear_password_reset modify column hash VARCHAR(255) NOT NULL;
alter table nuclear_account_destroy modify column hash VARCHAR(255) NOT NULL;
alter table nuclear_change_email modify column hash VARCHAR(255) NOT NULL;
