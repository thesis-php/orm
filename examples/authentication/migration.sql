create table identity (
    id uuid primary key,
    password_hash text not null,
    version smallint default 1 not null
);
