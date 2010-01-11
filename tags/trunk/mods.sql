alter table nu_relation drop primary key, add primary key(user,party), add index(user,model);
