/* For Scheduler */
create table nu_queue (
  id int unsigned not null auto_increment primary key, 
  label varchar(32), data blob) default character set=utf8;
