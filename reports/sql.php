<?php
$PAGE_FIELD_SQL=array();
$PAGE_FIELD_SQL['farm-process-records']='SELECT * FROM erp_farm_process;';
$PAGE_FIELD_SQL['worker-records']="SELECT id, concat(surname,' ', other_names) AS name FROM erp_workers;";
$PAGE_FIELD_SQL['product-records']='SELECT * FROM erp_product;';
$PAGE_FIELD_SQL['production-records']='SELECT * FROM erp_product;';
$PAGE_FIELD_SQL['plant-animal-process-records']='SELECT * FROM erp_grow_product;';
$PAGE_FIELD_SQL['destroyed-product-records']="SELECT erp_destroyed_products_record.id
        , erp_product.name AS name, erp_grow_product.name AS grow_product_name
    JOIN erp_product ON erp_destroyed_products_record.product_id=erp_product.id 
    JOIN erp_grow_product ON erp_destroyed_products_record.grow_product_id=erp_grow_product.id";
$PAGE_FIELD_SQL['product-quantity-records']='SELECT product.name, product.id FROM erp_product_quantity_records records
JOIN erp_product_quantity_current current ON current.id=records.product_quantity_current_id
JOIN erp_product product ON product.id=current.product_id;';
$PAGE_FIELD_SQL['retail-sales']='SELECT * FROM erp_retail_unit;';
$PAGE_FIELD_SQL['deliveries-sent']='SELECT * FROM erp_retail_unit;';
$PAGE_FIELD_SQL['deliveries-received']='SELECT * FROM erp_retail_unit;';

$DATE_TABLE=array();
$DATE_TABLE['worker-records']='erp_farm_process_record';
$DATE_TABLE['plant-animal-process-records']='erp_farm_process_record';
$DATE_TABLE['retail-sales']='sale';
$DATE_TABLE['deliveries-sent']='sent_record';
$DATE_TABLE['deliveries-received']='received_record';
$DATE_TABLE['bank-account-transactions']='transac';
$DATE_TABLE['product-quantity-records']='records';
$DATE_TABLE['product-records']='erp_farm_process_record';




$PAGE_RESULT_SQL=array();
$PAGE_RESULT_SQL['farm-process-records']="SELECT erp_farm_process_record.id 
        , erp_farm_process.name, erp_farm_process_record.datetime_recorded, erp_farm_process_record.datetime_processed
        , erp_farm_process_record.notes
        , /*to_json*/(erp_farm_process_record.requirements) AS requirements
        , array_to_string(array_agg(h.name), ', ') AS plants_animals
    FROM erp_farm_process_record
    LEFT JOIN erp_farm_process ON erp_farm_process_record.farm_process_id=erp_farm_process.id
    LEFT JOIN erp_farm_process_grow_product_record  g ON erp_farm_process_record.farm_process_id=g.farm_process_record_id
    LEFT JOIN erp_grow_product h
        ON g.grow_product_id = h.id
    WHERE erp_farm_process.id =ANY($1) ";

$PAGE_RESULT_SQL['bank-account-transactions']="SELECT transac.id,account.name AS \"Bank Account Name\",
    retail.name AS \"Retail Name\",transac.datetime_deposited,transac.transaction_code, transac.total_amount,transac.added_by,transac.notes
    FROM erp_bank_account_transactions transac
    JOIN erp_retail_unit retail ON retail.id=transac.retail_id
    JOIN erp_bank_account account ON transac.bank_account_id=account.id";

$PAGE_RESULT_SQL['plant-animal-process-records']="SELECT erp_farm_process_record.id AS Process_Record_Id
        , erp_farm_process.name AS Process_Name, erp_farm_process_record.datetime_recorded, erp_farm_process_record.datetime_processed
        , erp_farm_process_record.notes
        , array_to_string(array_agg(h.name), ', ') AS plants_animals
    FROM erp_farm_process_record
    LEFT JOIN erp_farm_process ON erp_farm_process_record.farm_process_id=erp_farm_process.id
    LEFT JOIN erp_farm_process_grow_product_record  g ON erp_farm_process_record.id=g.farm_process_record_id
    LEFT JOIN erp_grow_product h
        ON g.grow_product_id = h.id
    WHERE g.grow_product_id =ANY($1) ";
$PAGE_RESULT_SQL['worker-records']="SELECT erp_farm_process_record.id  AS Process_Record_Id
        , erp_workers.surname, erp_workers.other_names,erp_farm_process.name AS Process_Name
        , erp_farm_process_record.datetime_recorded, erp_farm_process_record.datetime_processed
    FROM erp_farm_process_worker_record
    LEFT JOIN erp_farm_process_record ON erp_farm_process_record.id=erp_farm_process_worker_record.farm_process_record_id
    LEFT JOIN erp_farm_process ON erp_farm_process_record.farm_process_id=erp_farm_process.id
    LEFT JOIN erp_workers ON erp_farm_process_worker_record.worker_id=erp_workers.id
    WHERE erp_workers.id =ANY($1) ";
$PAGE_RESULT_SQL['product-records']="SELECT erp_farm_process_record.id AS Process_Record_Id
        , erp_product.name AS Product_Name, erp_farm_process.name AS Process_Name, erp_farm_process_record.datetime_recorded
        , erp_farm_process_record.datetime_processed, erp_farm_process_product_record.quantity 
    FROM erp_farm_process_product_record
    LEFT JOIN erp_farm_process_record ON erp_farm_process_record.id=erp_farm_process_product_record.farm_process_record_id
    LEFT JOIN erp_farm_process ON erp_farm_process_record.farm_process_id=erp_farm_process.id
    LEFT JOIN erp_product ON erp_farm_process_product_record.product_id=erp_product.id
    WHERE erp_farm_process_product_record.product_id =ANY($1) ";
$PAGE_RESULT_SQL['production-records']="SELECT erp_production_record.id
        , erp_product.name, erp_production_record.datetime_produced AS \"Date\\ Time Recorded\", erp_production_record.datetime_recorded AS \"Date\\ Time Recorded\"
        , erp_production_record.quantity, erp_production_record.notes
    FROM erp_production_record
    JOIN erp_product ON erp_production_record.product_id=erp_product.id
    WHERE erp_product.id =ANY($1) ";

$PAGE_RESULT_SQL['destroyed-product-records']="SELECT erp_destroyed_products_record.id
        , erp_product.name,erp_grow_product.name, erp_destroyed_products_record.date
        , erp_destroyed_products_record.quantity, erp_farm_process_record.requirements 
        , erp_farm_process_record.notes
    FROM erp_farm_process_product_record
    JOIN erp_grow_product ON erp_destroyed_products_record.grow_product_id=erp_grow_product.id
    JOIN erp_product ON erp_destroyed_products_record.product_id=erp_product.id
    WHERE erp_farm_process_worker_record.id =ANY($1) ";
$PAGE_RESULT_SQL['product-quantity-records']="SELECT records.id, product.name, records.datetime_recorded, records.transaction_type, records.quantity 
    FROM erp_product_quantity_records records
    LEFT JOIN erp_product_quantity_current quantity ON quantity.id= records.product_quantity_current_id
    LEFT JOIN erp_product product ON product.id = quantity.product_id AND product.grows=false
    LEFT JOIN erp_grow_product  grow ON grow.product_quantity_current_id=quantity.id
    WHERE product.id =ANY($1) ";

$PAGE_RESULT_SQL['products']="SELECT p.id, p.name, u.name AS Unit_of_measure,p.purchase,p.grows,p.consumable,p.produced,p.active, p.added_by,p.notes from erp_product p
    JOIN erp_unit_of_measure u ON u.id = p.unit_of_measure_id ";

$PAGE_RESULT_SQL['purchases']="SELECT erp_purchase.id, u.name,erp_purchase.quantity, erp_purchase.cost_per_unit, erp_purchase.amount as \"Total Cost\",erp_purchase.date as \"Date\\ Time\",erp_purchase.added_by,erp_purchase.notes FROM erp_purchase
    JOIN erp_product u ON u.id = erp_purchase.product_id ";


$PAGE_RESULT_SQL['retail-sales']="SELECT sale.id ,
    retail.name AS retail_name,
    product.name AS product_name, sale.quantity,sale.cost_per_unit,sale.amount AS total_amount,date_trunc( 'milliseconds',sale.datetime_recorded) as Datetime_recorded, sale.notes, sale.added_by
    FROM erp_retail_sales sale
    JOIN erp_product product ON product.id=sale.product_id 
    JOIN erp_retail_unit retail ON retail.id=sale.retail_id 

    WHERE sale.retail_id =ANY($1) ";

$PAGE_RESULT_SQL['sent-deliveries']="SELECT sent.id ,retail.name,
    date_trunc('milliseconds', sent.datetime_sent) AS Datetime_Sent,
    date_trunc('milliseconds', sent.datetime_recorded) AS Datetime_recorded,
    sent.status ,array_to_string(array_agg(product.name),\",\"
    FROM erp_retail_sales sale
    JOIN erp_product product ON product.id=sale.product_id 
    JOIN erp_retail_unit retail ON retail.id=sale.retail_id 

    WHERE sale.retail_id =ANY($1) ";

$PAGE_RESULT_SQL['deliveries-sent']="SELECT delivery_product_name.delivery_sent_record_id AS id,
    retail_unit.name AS Retail_unit_name,sent_record.datetime_sent,
    (array_agg(CONCAT(delivery_product_name.name,' : ',delivery_product_name.quantity) )) as products,
    sent_record.status, sent_record.added_by, sent_record.notes 
    FROM (SELECT DISTINCT JP1.name, SP1.delivery_sent_record_id, SP1.quantity
         FROM erp_product AS JP1, erp_delivery_sent_product_record AS SP1
        WHERE NOT EXISTS
           (SELECT *
              FROM erp_product AS JP2
             WHERE JP2.id = JP1.id
               AND JP2.id
                   NOT IN (SELECT SP2.product_id
                             FROM erp_delivery_sent_product_record AS SP2
                            WHERE SP2.delivery_sent_record_id = SP1.delivery_sent_record_id))
                            ) AS delivery_product_name
    JOIN erp_delivery_sent_record sent_record ON sent_record.id=delivery_sent_record_id 
    JOIN erp_retail_unit retail_unit ON sent_record.receiving_retail_id=retail_unit.id  
    WHERE retail_unit.id=ANY($1) ";

$PAGE_RESULT_SQL['deliveries-received']="SELECT delivery_product_name.delivery_received_record_id AS id,
    retail_unit.name AS Retail_unit_name,received_record.datetime_received,
    (array_agg(CONCAT(delivery_product_name.name,' : ',delivery_product_name.quantity) )) as products,
     received_record.added_by, received_record.notes 
    FROM (SELECT DISTINCT JP1.name, SP1.delivery_received_record_id, SP1.quantity
         FROM erp_product AS JP1, erp_delivery_received_product_record AS SP1
        WHERE NOT EXISTS
           (SELECT *
              FROM erp_product AS JP2
             WHERE JP2.id = JP1.id
               AND JP2.id
                   NOT IN (SELECT SP2.product_id
                             FROM erp_delivery_received_product_record AS SP2
                            WHERE SP2.delivery_received_record_id = SP1.delivery_received_record_id))
                            ) AS delivery_product_name
    JOIN erp_delivery_received_record received_record ON received_record.id=delivery_received_record_id 
    JOIN erp_retail_unit retail_unit ON received_record.receiving_retail_id=retail_unit.id  
    WHERE retail_unit.id=ANY($1) ";

$GROUP_BY=array();
$GROUP_BY['farm-process-records']=" GROUP BY erp_farm_process.name, erp_farm_process_record.id ";
$GROUP_BY['plant-animal-process-records']=" GROUP BY erp_farm_process_record.id, erp_farm_process_record.id, erp_farm_process.name ";
$GROUP_BY['deliveries-sent']=" GROUP BY delivery_product_name.delivery_sent_record_id,sent_record.datetime_sent,sent_record.status,sent_record.datetime_sent,retail_unit.name, sent_record.added_by, sent_record.notes ";
$GROUP_BY['deliveries-received']=" GROUP BY delivery_product_name.delivery_received_record_id,received_record.datetime_received,received_record.datetime_received,retail_unit.name, received_record.added_by, received_record.notes ";
?>