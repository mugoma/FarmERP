BEGIN;
/* Create  an email field*/

CREATE EXTENSION citext;
CREATE DOMAIN email AS TEXT CHECK ( value ~ '^[a-zA-Z0-9.!#$%&''*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$' AND length(value)<=254);
CREATE TYPE delegation AS ENUM ('Manager', 'Admin', 'Employee');

/* Create the necessary tables*/

/* AUTH TABLES */
CREATE TABLE IF NOT EXISTS auth_users( 
    id serial PRIMARY KEY,
    username email UNIQUE NOT NULL,
    password varchar(254) NOT NULL,
    delegation delegation default 'Employee' NOT NULL,
    database_name TEXT ,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    is_superuser BOOLEAN NOT NULL DEFAULT FALSE,
    added_by text,
    last_pwd_change date NOT NULL default CURRENT_DATE 
);

CREATE TABLE IF NOT EXISTS auth_users_log( 
    id serial PRIMARY KEY,
    user_id int NOT NULL references auth_users,
    date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    remote_ip inet NOT NULL,
    remote_address varchar NOT NULL 
    
);


CREATE TABLE IF NOT EXISTS auth_permission(
    id serial PRIMARY KEY,
    name varchar(255) NOT NULL,
    code_name varchar(100) NOT NULL UNIQUE

);
CREATE TABLE IF NOT EXISTS auth_failed_login(
    id serial PRIMARY KEY,
    remote_username text NOT NULL,
    remote_ip inet NOT NULL,
    remote_address varchar NOT NULL ,
    date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    user_agent varchar NOT NULL

);
CREATE TABLE IF NOT EXISTS auth_db_records(
    id SERIAL PRIMARY KEY,
    date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    user_id int NOT NULL REFERENCES auth_users,
    db_transaction text NOT NULL

);

/*CREATE TABLE IF NOT EXISTS auth_user_user_permissions(
    id serial PRIMARY KEY,
    user_id int NOT NULL REFERENCES auth_users,
    permission_id int NOT NULL REFERENCES auth_permission
);*/
CREATE TABLE IF NOT EXISTS auth_groups(
    id serial PRIMARY KEY,
    description text,
    name text NOT NULL
);
INSERT INTO auth_groups (id,name, description) VALUES 
    (1,'Admin', 'Can access ALL pages in the system(reserved for system admin ONLY)'),
    (2,'Manager', 'Can generate all reports, add and edit products, processes and workers. Can add users.'),
    (3,'Supervisor', 'Can generate all reports , enter records, add products, processes and workers.'),
    (4,'Entry Clerk', 'Can only enter records'),
    (5,'Retail Employee', 'Can only enter retail-related records');
CREATE TABLE IF NOT EXISTS auth_user_groups(
    id serial PRIMARY KEY,
    user_id int NOT NULL REFERENCES auth_users,
    group_id int NOT NULL REFERENCES auth_groups
);
/* ERP TABLES */
CREATE TABLE IF NOT EXISTS erp_unit_of_measure(
    id serial PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    symbol varchar(10) NOT NULL UNIQUE,
    code text UNIQUE,
    active BOOLEAN NOT NULL DEFAULT 'true',
    added_by text,
    notes TEXT

);
INSERT INTO erp_unit_of_measure (name, symbol)  VALUES('Unit', 'Unit' );
INSERT INTO erp_unit_of_measure (name, symbol)  VALUES('Kilogram', 'Kg' );

CREATE TABLE IF NOT EXISTS erp_product(
    id serial PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    sale BOOLEAN NOT NULL,
    purchase BOOLEAN NOT NULL,
    unit_of_measure_id int NOT NULL references erp_unit_of_measure DEFAULT 1,
    grows BOOLEAN NOT NULL DEFAULT 'false',
    consumable BOOLEAN NOT NULL DEFAULT 'true',
    produced BOOLEAN NOT NULL DEFAULT 'true',
    active BOOLEAN NOT NULL DEFAULT 'true',
    can_sell_below_retail BOOLEAN NOT NULL DEFAULT 'false',
    added_by text,
    retail_price numeric CHECK (retail_price>=0),
    notes TEXT

    --CONSTRAINT grow_consumable_not_equal CHECK(grows!=consumable AND grows == TRUE),
    --CONSTRAINT consumable_non_consumable_not_equal CHECK(non_consumable!=consumable AND consumable == TRUE)

);

/*
CREATE TABLE IF NOT EXISTS erp_product_quantity_current(
    id serial PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    quantity numeric NOT NULL,
    product_id INT REFERENCES erp_product

);


CREATE TABLE IF NOT EXISTS erp_product_quantity_records(
    id serial PRIMARY KEY,
    date date NOT NULL DEFAULT CURRENT_DATE,
    name VARCHAR(100) NOT NULL ,
    quantity numeric NOT NULL,
    added_by text ,
    transaction_type VARCHAR(100) NOT NULL 


);

*/
CREATE TABLE IF NOT EXISTS erp_farm_building(
    id SERIAL PRIMARY KEY,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    added_by text,
    name text

);
CREATE TABLE IF NOT EXISTS erp_product_quantity_current(
    id serial PRIMARY KEY,
    quantity numeric NOT NULL CHECK  (  quantity>=0 ),
    product_id INT REFERENCES erp_product,
    grows BOOLEAN NOT NULL DEFAULT 'false'
    --CONSTRAINT product_id_grow_product_id_not_null_together CHECK(product_id IS NOT NULL OR grow_product_id IS NOT NULL)

);
CREATE TABLE IF NOT EXISTS erp_grow_product(
    id serial primary KEY,
    name text NOT NULL,
    product_id int REFERENCES erp_product,
    product_quantity_current_id int REFERENCES erp_product_quantity_current,
    datetime_purchased timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    building_id int REFERENCES erp_farm_building,
    added_by text,
    quantity NUMERIC NOT NULL  CHECK (quantity>=0)
);
CREATE TABLE IF NOT EXISTS erp_product_quantity_records(
    id serial PRIMARY KEY,
    datetime_recorded timestamp DEFAULT CURRENT_TIMESTAMP,
    --name text NOT NULL,
    product_quantity_current_id int NOT NULL 
        REFERENCES erp_product_quantity_current,
    previous_quantity numeric NOT NULL check ( previous_quantity>=0),
    quantity numeric NOT NULL CHECK  (  quantity>=0 ),
    added_by text ,
    transaction_type VARCHAR(100) NOT NULL 

);
CREATE TABLE IF NOT EXISTS erp_workers(
    id serial PRIMARY KEY,
    surname char(50) NOT NULL,
    active BOOLEAN  NOT NULL DEFAULT 'true',
    added_by text,
    other_names char(50) NOT NULL
);

CREATE TABLE IF NOT EXISTS erp_purchase(
    id serial PRIMARY KEY,
    product_id  int REFERENCES erp_product,
    amount numeric NOT NULL CHECK  ( amount>=0),
    cost_per_unit numeric NOT NULL  CHECK  (  cost_per_unit>=0 ),
    quantity numeric NOT NULL  CHECK  (  quantity>=0 ),
    datetime_purchased timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,   
    datetime_recorded timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    added_by text,
    notes TEXT
);


CREATE TABLE IF NOT EXISTS erp_sales(
    id serial PRIMARY KEY,
    product_id  int REFERENCES erp_product,
    amount numeric NOT NULL  CHECK  (  amount>=0 ),
    cost_per_unit numeric NOT NULL  CHECK  (  cost_per_unit>=0 ),
    quantity numeric NOT NULL  CHECK  (  quantity>=0 ),
    datetime_purchased timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    added_by text,
    notes TEXT/*,
    CONSTRAINT sale_product_id_not_null 
        CHECK(product_id IS NOT NULL OR grow_product_id IS NOT NULL)*/
);

CREATE TABLE IF NOT EXISTS erp_farm_process(
    id serial PRIMARY KEY,
    name  varchar(100) NOT NULL,
    requirements text [],
    active BOOLEAN NOT NULL DEFAULT 'true',
    added_by text,
    notes TEXT
);

CREATE TABLE IF NOT EXISTS erp_farm_process_product(
    farm_process_id int REFERENCES erp_farm_process,
    product_id int REFERENCES erp_product,
    CONSTRAINT farm_process_product_pk PRIMARY KEY (farm_process_id, product_id)
);

CREATE TABLE IF NOT EXISTS erp_farm_process_worker(
    farm_process_id int REFERENCES erp_farm_process,
    worker_id int REFERENCES erp_workers,
    CONSTRAINT farm_process_worker_pk PRIMARY KEY (farm_process_id, worker_id)
);
CREATE TABLE IF NOT EXISTS erp_farm_process_non_consumable(
    farm_process_id int REFERENCES erp_farm_process,
    product_id int REFERENCES erp_product,
    CONSTRAINT farm_process_non_consumable_pk PRIMARY KEY (farm_process_id, product_id)
);


CREATE TABLE IF NOT EXISTS erp_farm_process_record(
    id serial  PRIMARY KEY,
    datetime_recorded timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    datetime_processed timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    farm_process_id int NOT NULL REFERENCES erp_farm_process,
    notes text,
    added_by text,
    requirements text
    
);


CREATE TABLE IF NOT EXISTS erp_farm_process_product_record(
    farm_process_record_id int REFERENCES erp_farm_process_record,
    product_id int REFERENCES erp_product,
    --datetime date NOT NULL DEFAULT CURRENT_DATE,
    quantity numeric NOT NULL DEFAULT 0 CHECK  (  quantity>=0 ),
    CONSTRAINT erp_farm_process_product_record_pk 
        PRIMARY KEY (farm_process_record_id, product_id)
);
/*CREATE TABLE IF NOT EXISTS erp_farm_process_non_consumable_record(
    farm_process_record_id int REFERENCES erp_farm_process_record,
    product_id int REFERENCES erp_product,
    date date NOT NULL DEFAULT CURRENT_DATE,
    quantity numeric NOT NULL DEFAULT 0 CHECK quantity>=0,
    CONSTRAINT erp_farm_process_non_consumable_record_pk 
        PRIMARY KEY (farm_process_record_id,date, product_id)
);*/
CREATE TABLE IF NOT EXISTS erp_farm_process_worker_record(
    farm_process_record_id int REFERENCES erp_farm_process_record,
    worker_id int REFERENCES erp_workers,
    CONSTRAINT erp_farm_process_worker_record_pk 
        PRIMARY KEY (farm_process_record_id, worker_id)
    
);

CREATE TABLE IF NOT EXISTS erp_farm_process_grow_product_record(
    farm_process_record_id int REFERENCES erp_farm_process_record,
    grow_product_id int REFERENCES erp_grow_product,
    CONSTRAINT erp_farm_process_grow_product_record_pk 
        PRIMARY KEY (farm_process_record_id, grow_product_id)
    

);

CREATE TABLE IF NOT EXISTS erp_destroyed_products_record(
    id serial primary KEY,
    product_id int REFERENCES erp_product,
    grow_product_id int REFERENCES erp_grow_product,
    datetime_recorded timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    datetime_destroyed timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    quantity NUMERIC NOT NULL CHECK  (  quantity>=0 ),
    added_by text,
    notes text,
    CONSTRAINT destroyed_product_product_id_not_null 
        CHECK(product_id IS NOT NULL OR grow_product_id IS NOT NULL)
);
CREATE TABLE IF NOT EXISTS erp_product_change_record(
    id serial primary KEY,
    --product_to_id int REFERENCES erp_product,
    --grow_product_to_id int  REFERENCES erp_grow_product,
    --product_from_id int REFERENCES erp_product,
    --grow_product_from_id int NOT NULL REFERENCES erp_grow_product,
    product_from text NOT NULL,
    product_to text NOT NULL,
    quantity NUMERIC NOT NULL CHECK  (  quantity>=0 ),
    datetime_recorded timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    datetime_changed timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    added_by text,
    notes text
    --CONSTRAINT product_change_product_from_not_null 
        --CHECK(product_from_id IS NOT NULL OR grow_product_from_id IS NOT NULL),
    --CONSTRAINT product_change_product_to_not_null 
        --CHECK(product_to_id IS NOT NULL OR grow_product_to_id IS NOT NULL)


);


CREATE TABLE IF NOT EXISTS erp_cashbook(
    id serial PRIMARY KEY, 
    folio text NOT NULL,
    date date NOT NULL DEFAULT LOCALTIMESTAMP,
    time time NOT NULL DEFAULT CURRENT_TIME,
    amount numeric NOT NULL  CHECK  (  amount>=0 ), 
    added_by text,
    transaction_type text NOT NULL
);

CREATE TABLE IF NOT EXISTS erp_production_record(
    id SERIAL PRIMARY KEY,
    product_id int REFERENCES erp_product,
    grow_product_id int REFERENCES erp_grow_product,
    farm_building_id int REFERENCES erp_farm_building,
    quantity numeric CHECK  (  quantity>=0 ),
    datetime_produced timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    datetime_recorded timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    added_by text,
    notes text
);
CREATE TABLE IF NOT EXISTS erp_retail_unit(
    id serial PRIMARY KEY,
    name text ,
    town text NOT NULL,
    location text NOT NULL,
    active BOOLEAN NOT NULL DEFAULT 'true',
    added_by text,
    notes text
);
CREATE TABLE IF NOT EXISTS erp_retail_user(
    retail_id int NOT NULL REFERENCES erp_retail_unit,
    user_id int NOT NULL REFERENCES auth_users,
    CONSTRAINT erp_retail_user_pk 
        PRIMARY KEY (retail_id, user_id)
);
CREATE TABLE IF NOT EXISTS erp_retail_product(
    retail_id int NOT NULL REFERENCES erp_retail_unit,
    product_id int NOT NULL REFERENCES erp_product,
    CONSTRAINT erp_retail_product_pk 
        PRIMARY KEY (retail_id, product_id)
);
CREATE TABLE IF NOT EXISTS erp_bank_account(
    id serial PRIMARY KEY,
    name text UNIQUE,
    institution text NOT NULL,
    account_number text COLLATE "C" NOT NULL UNIQUE,
    active BOOLEAN NOT NULL DEFAULT TRUE,
    added_by text,
    notes text

);
CREATE TABLE IF NOT EXISTS erp_delivery_sent_record(
    id SERIAL PRIMARY KEY,
    receiving_retail_id int NOT NULL REFERENCES erp_retail_unit,
    datetime_sent timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    datetime_recorded timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status text DEFAULT 'Not Received',
    added_by text,
    notes text
    

);
CREATE TABLE IF NOT EXISTS erp_delivery_sent_product_record(
    delivery_sent_record_id int NOT NULL REFERENCES erp_delivery_sent_record,
    product_id int NOT NULL REFERENCES erp_product,
    quantity numeric NOT NULL CHECK  (  quantity>=0 ),
    CONSTRAINT erp_delivery_sent_product_record_pk 
        PRIMARY KEY (delivery_sent_record_id, product_id)

);
CREATE TABLE IF NOT EXISTS erp_delivery_received_record(
    id SERIAL PRIMARY KEY,
    receiving_retail_id int NOT NULL REFERENCES erp_retail_unit,
    datetime_received timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    datetime_recorded timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    added_by text,
    notes text
    

);
CREATE TABLE IF NOT EXISTS erp_delivery_received_product_record(
    delivery_received_record_id int NOT NULL REFERENCES erp_delivery_received_record,
    product_id int NOT NULL REFERENCES erp_product,
    quantity numeric NOT NULL CHECK  (  quantity>=0 ),
    CONSTRAINT erp_delivery_received_product_record_pk 
        PRIMARY KEY (delivery_received_record_id, product_id)

);
CREATE TABLE IF NOT EXISTS erp_delivery_lost_product_record(
    delivery_received_record_id int NOT NULL REFERENCES erp_delivery_received_record,
    delivery_sent_record_id int NOT NULL REFERENCES erp_delivery_sent_record,
    product_id int NOT NULL REFERENCES erp_product,
    quantity numeric NOT NULL CHECK  (  quantity>=0 ),
    CONSTRAINT erp_delivery_lost_product_record_pk 
        PRIMARY KEY (delivery_received_record_id,delivery_sent_record_id)

);
CREATE TABLE IF NOT EXISTS erp_retail_product_quantity_current(
    id serial PRIMARY KEY,
    quantity numeric NOT NULL CHECK  (  quantity>=0 ),
    product_id INT REFERENCES erp_product,
    grows BOOLEAN NOT NULL DEFAULT 'false',
    date_added timestamp DEFAULT CURRENT_TIMESTAMP,
    active BOOLEAN DEFAULT TRUE,
    retail_id INT REFERENCES erp_retail_unit

);
CREATE TABLE IF NOT EXISTS erp_retail_product_quantity_records(
    id serial PRIMARY KEY,
    date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    product_quantity_current_id int NOT NULL 
        REFERENCES erp_retail_product_quantity_current,
    previous_quantity numeric NOT NULL CHECK  (  previous_quantity>=0 ),
    quantity numeric NOT NULL CHECK  (  quantity>=0 ),
    added_by text ,
    retail_id INT REFERENCES erp_retail_unit,
    transaction_type VARCHAR(100) NOT NULL 

);
CREATE TABLE IF NOT EXISTS erp_retail_sales(
    id serial PRIMARY KEY,
    retail_id int REFERENCES erp_retail_unit,
    product_id  int REFERENCES erp_product,
    amount numeric NOT NULL CHECK  (  amount>=0 ),
    cost_per_unit numeric NOT NULL  CHECK  (  cost_per_unit>=0 ),
    quantity numeric NOT NULL CHECK  (  quantity>=0 ),
    datetime_recorded timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    added_by text,
    notes TEXT
);
CREATE TABLE IF NOT EXISTS erp_bank_account_transactions(
    id serial PRIMARY KEY,
    bank_account_id int NOT NULL REFERENCES erp_bank_account,
    retail_id int NOT NULL REFERENCES erp_retail_unit,
    total_amount numeric NOT NULL  CHECK  (  total_amount>=0 ),
    datetime_deposited timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    datetime_recorded timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,  
    notes text,  
    added_by text,
    transaction_code text 

);
CREATE TABLE IF NOT EXISTS erp_farm_building_occupancy_record(
    farm_building_id int NOT NULL REFERENCES erp_farm_building,
    grow_product_id int NOT NULL REFERENCES erp_grow_product,
    CONSTRAINT erp_farm_building_occupancy_record_pk 
        PRIMARY KEY (farm_building_id,grow_product_id)
);
COMMIT;

SELECT erp_product.id,erp_product.name, erp_product.unit_of_measure_id, erp_unit_of_measure.symbol  FROM erp_product  JOIN  erp_farm_process_product ON erp_product.id=erp_farm_process_product.product_id WHERE (erp_farm_process_product.farm_process_id=) JOIN erp_unit_of_measure ON erp_unit_of_measure.id=erp_product.unit_of_measure_id;
SELECT erp_workers.id,erp_workers.surname, erp_workers.other_names  FROM erp_workers JOIN  erp_farm_process_worker ON erp_workers.id=erp_farm_process_worker.worker_id WHERE (erp_workers.active='true' AND erp_farm_process_worker.farm_process_id==1;
SELECT erp_farm_process.id, erp_farm_process.name,erp_farm_process.requirements FROM erp_farm_process;
SELECT (name, quantity) FROM erp_product_quantity_current WHERE(lower(name) IN '');
SELECT erp_grow_product.id,erp_grow_product.name FROM erp_grow_product WHERE(erp_grow_product.quantity <0);

INSERT INTO erp_farm_process_record(date, time, farm_process, requirements) VALUES RETURNING id;
INSERT INTO erp_farm_process_product_record(farm_process_record_id, product_id, date, amount) VALUES;
INSERT INTO erp_farm_process_worker_record(farm_process_record_id, worker_id) VALUES;
INSERT INTO erp_product_quantity_records(name, quantity, transaction_type) VALUES;

UPDATE  erp_product_quantity_current SET (quantity=quantity-$1) WHERE lower(name) = $2;

jobpart=product
sup_part=delivery_sent_report
part_numb=commom_column
SELECT DISTINCT JP1.name, SP1.sent_record_id
  FROM erp_product AS JP1, erp_delivery_sent_product_record AS SP1
 WHERE NOT EXISTS
        (SELECT *
           FROM erp_product AS JP2
          WHERE JP2.id = JP1.product_id
            AND JP2.id
                NOT IN (SELECT SP2.product_id
                          FROM erp_delivery_sent_product_record AS SP2
                         WHERE SP2.sent_record_id = SP1.sent_record_id));
SELECT DISTINCT JP1.name, SP1.delivery_sent_record_id
  FROM erp_product AS JP1, erp_delivery_sent_product_record AS SP1
 WHERE NOT EXISTS
        (SELECT *
           FROM erp_product AS JP2
          WHERE JP2.id = JP1.id
            AND JP2.id
                NOT IN (SELECT SP2.product_id
                          FROM erp_delivery_sent_product_record AS SP2
                         WHERE SP2.delivery_sent_record_id = SP1.delivery_sent_record_id))
						 AND SP1.delivery_sent_record_id=1;
SELECT  array_agg(foo.name), foo.delivery_sent_record_id FROM (	SELECT DISTINCT JP1.name, SP1.delivery_sent_record_id
	  FROM erp_product AS JP1, erp_delivery_sent_product_record AS SP1
	 WHERE NOT EXISTS
			(SELECT *
			   FROM erp_product AS JP2
			  WHERE JP2.id = JP1.id
				AND JP2.id
					NOT IN (SELECT SP2.product_id
							  FROM erp_delivery_sent_product_record AS SP2
							 WHERE SP2.delivery_sent_record_id = SP1.delivery_sent_record_id))
							 AND SP1.delivery_sent_record_id=1) AS delivery_product
							 GROUP BY foo.delivery_sent_record_id;

GRANT USAGE ON ALL SEQUENCES IN SCHEMA public TO yengas2_root;
GRANT ALL PRIVILEGES ON SCHEMA public TO yengas2_root;
GRANT ALL PRIVILEGES ON auth_users TO yengas2_root;
GRANT USAGE ON ALL SEQUENCES IN SCHEMA foo TO mygrp;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA foo TO staff;

COMMIT;
