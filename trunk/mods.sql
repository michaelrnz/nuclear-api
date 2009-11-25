alter table nu_federated_publisher_auth add key(token);
alter table nu_federated_subscriber_auth add key(token);

drop table nu_federated_publisher_domain;
drop table nu_federated_subscriber_domain;

/*
create table nu_packet_proxy (id int unsigned not null, publisher int unsigned not null, primary key (id));
create table nu_packet_queue (id int unsigned not null, publisher int unsigned not null, mode enum('publish','unpublish','republish') default 'publish', data blob, primary key(id));

create table nu_publisher_packet_index (publisher int unsigned not null, id int unsigned not null auto_increment, primary key(publisher,id));
alter table nu_packet_index add column global_id int unsigned not null after publisher;

alter table nu_packet_queue add column global_id int unsigned not null after publisher;


alter table nu_publisher_packet_index add column packet int unsigned not null;

insert into nu_publisher_packet_index (select publisher, NULL,id from nu_packet_index);

update nu_packet_index as I left join nu_publisher_packet_index as P on P.publisher=I.publisher && P.packet=I.id set I.global_id=P.id;

*/
