create table nu_federated_auth (
    publisher int unsigned not null, 
    subscriber int unsigned not null, 
    token varchar(255) not null, 
    secret varchar(255), 
    ts timestamp not null default CURRENT_TIMESTAMP, 
    primary key(publisher, subscriber),
    key(token)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;

insert ignore into nu_federated_auth (
  select user, federated_user, token, secret, ts 
  from nu_federated_subscriber_auth
);

insert ignore into nu_federated_auth (
  select federated_user, user, token, secret, ts 
  from nu_federated_publisher_auth
);
