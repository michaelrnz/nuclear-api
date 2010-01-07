create table nu_preference (id int unsigned not null, label varchar(32) not null, int_store int, blob_store blob, primary key(id,label)) engine=innodb;
