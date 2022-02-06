/* Create a new database
DROP DATABASE IF EXISTS farmerp;*/
CREATE DATABASE farmerp;



/* Create a new user and grants priviledges*/

/*
Ensure that the role executing the grant commands has sufficient authority.
Otherwise the priviledges granted to the new user will be capped at the executing 
user's priviledges
*/
/* Create  an email field*/
CREATE EXTENSION citext;
CREATE DOMAIN email AS TEXT CHECK ( value ~ '^[a-zA-Z0-9.!#$%&''*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$' AND length(value)<=254);
CREATE TYPE delegation AS ENUM ('Manager', 'Admin', 'Employee');

/* Create the necessary tables*/
CREATE TABLE IF NOT EXISTS auth_users( 
    id serial PRIMARY KEY,
    username email UNIQUE NOT NULL,
    password varchar(254) NOT NULL,
    delegation delegation default 'Employee' NOT NULL,
    last_pwd_change date NOT NULL default CURRENT_DATE 
);

CREATE TABLE IF NOT EXISTS auth_users_log( 
    id serial PRIMARY KEY,
    username int NOT NULL references auth_users,
    date timestamp NOT NULL,
    remote_ip inet NOT NULL,
    remote_address varchar NOT NULL 
    
);



CREATE TABLE IF NOT EXISTS vaccines(
    id serial PRIMARY KEY,
    type_of_vaccine varchar(254) NOT NULL,
    purchase_date timestamptz NOT NULL,
    expiry_date timestamptz NOT NULL,
    dosage_amount int NOT NULL,
    age_dosage int NOT NULL
);

CREATE TABLE IF NOT EXISTS meat_sale(
    id serial PRIMARY KEY,
    date_sale timestamptz NOT NULL,
    type int NOT NULL,
    bird_number int NOT NULL,
    weight int NOT NULL,
    price_per_kg int NOT NULL

);

CREATE TABLE IF NOT EXISTS transactions(
    id serial PRIMARY KEY,
    transaction_ref varchar(110) NOT NULL,
    date date NOT NULL,
    item_sold int NOT NULL,
    price_per_piece int NOT NULL,
    no_sold int NOT NULL
);

CREATE TABLE IF NOT EXISTS layers(
    id serial PRIMARY KEY, 
    daily_schedule date NOT NULL,
    age int NOT NULL
    /* there are som ore columns in here that i will check later*/
);

CREATE TABLE IF NOT EXISTS indigenous(
    /* there are som ore columns in here that i will check later*/

);

CREATE TABLE IF NOT EXISTS incubator_hatchery(

    /* there are som ore columns in here that i will check later*/

);

CREATE TABLE IF NOT EXISTS egg_production(
    id serial PRIMARY KEY,
    date_collected date NOT NULL,
    no_of_eggs int NOT NULL,
    broken int NOT NULL,
    CONSTRAINT broken_egg CHECK (no_of_eggs >= broken)

);

CREATE TABLE IF NOT EXISTS coop_maintenance(
    id serial PRIMARY KEY,
    daily_schedule date NOT NULL,
    cleaned BOOLEAN NOT NULL,
    pesticide_application BOOLEAN NOT NULL,
    maintenance_fee int NOT NULL

);
CREATE TABLE IF NOT EXISTS chicken_initial_price(
    id serial PRIMARY KEY,
    chicken_id date NOT NULL

);
CREATE TABLE IF NOT EXISTS auth_permission(
    id serial PRIMARY KEY,
    name varchar(255) NOT NULL,
    code_name varchar(100) NOT NULL UNIQUE

);

GRANT USAGE ON ALL SEQUENCES IN SCHEMA public TO yengas2_root

DROP ROLE IF EXISTS user_basic;
CREATE ROLE user_basic WITH PASSWORD 'carMango2' LOGIN;
GRANT ALL PRIVILEGES ON SCHEMA public TO user_basic;

