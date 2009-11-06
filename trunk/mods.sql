create table nu_packet_proxy (id int unsigned not null, publisher int unsigned not null, primary key (id));
create table nu_packet_queue (id int unsigned not null, publisher int unsigned not null, mode enum('publish','unpublish','republish') default 'publish', data blob, primary key(id));
