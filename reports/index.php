<?php
ob_start();

$table_headers="";
require_once (realpath(dirname(__FILE__) . '/..') ."/"."config.php");
redirecttologin($_SERVER['REQUEST_URI']);



$TABLE_NAME=array();
$TABLE_NAME['farm-process-records']="erp_farm_process_record";
//$TABLE_NAME['farm-process-product-records']="erp_farm_process_product_record";
//$TABLE_NAME['farm-process-worker-records']="erp_farm_process_worker_record";
$TABLE_NAME['worker-records']="erp_farm_process_worker_record";
$TABLE_NAME['product-records']="erp_farm_process_product_record";
$TABLE_NAME['production-records']="erp_production_record";
$TABLE_NAME['destroyed-product-records']="erp_destroyed_products_record";
$TABLE_NAME['product-quantity-records']="erp_product_quantity_records";
$TABLE_NAME['plant-animal-process-records']="erp_farm_process_grow_product_record";
$TABLE_NAME['retail-sales']="erp_retail_sales";
$TABLE_NAME['deliveries-sent']="erp_delivery_sent_record";
$TABLE_NAME['deliveries-received']="erp_delivery_received_record";

$TABLE_NAME['cashbook']="erp_cashbook";
$TABLE_NAME['purchases']="erp_purchase";
$TABLE_NAME['sales']="erp_sales";
$TABLE_NAME['product-change-records']="erp_product_change_record";


$TABLE_NAME['products']="erp_product";
$TABLE_NAME['process']="erp_farm_process";
$TABLE_NAME['workers']="erp_workers";
$TABLE_NAME['retail-unit']="erp_retail_unit";
$TABLE_NAME['product-quantity']="erp_product_quantity_current";
$TABLE_NAME['bank-account-transactions']="erp_bank_account_transactions";



$PAGE_NAME=array();

//record-1: there is the aspect, from date and to date
$PAGE_NAME['farm-process-records']="record_1";
//$PAGE_NAME['farm-process-product-records']="record_1";
//$PAGE_NAME['farm-process-worker-records']="record_1";
$PAGE_NAME['worker-records']="record_1";
$PAGE_NAME['product-records']="record_1";
$PAGE_NAME['production-records']="record_1";
$PAGE_NAME['destroyed-products-records']='record_1';
$PAGE_NAME['product-quantity-records']='record_1';
$PAGE_NAME['plant-animal-process-records']='record_1';
$PAGE_NAME['retail-sales']='record_1';
$PAGE_NAME['deliveries-sent']='record_1';
$PAGE_NAME['deliveries-received']='record_1';


//record-2: there is only from date and to date
$PAGE_NAME['cashbook']="record_2";
$PAGE_NAME['purchases']="record_2";
$PAGE_NAME['sales']="record_2";
$PAGE_NAME['product-change-records']='record_2';
$PAGE_NAME['bank-account-transactions']='record_2';


//record-3: there is only whether active or not active
$PAGE_NAME['products']="record_3";
$PAGE_NAME['process']="record_3";
$PAGE_NAME['workers']="record_3";
$PAGE_NAME['retail-unit']="record_3";

//record-4: there is only aspect
$PAGE_NAME['product-quantity-current']='record_4';


require_once("sql.php");

$TITLE_ARRAY=array('products'=>'Products ', 'purchases'=>'Purchases', 'sales'=>'Sales');
$TITLE_ARRAY['cashbook']='Cashbook';
$TITLE_ARRAY['process']='Process';
$TITLE_ARRAY['workers']='Workers';
$TITLE_ARRAY['retail-unit']='Retail Unit';
$TITLE_ARRAY['dashboard']='Dashboard';
$TITLE_ARRAY['farm-process-records']='Farm Process Records';
$TITLE_ARRAY['worker-records']='Worker Records';
$TITLE_ARRAY['product-records']='Product(from process) Records';
$TITLE_ARRAY['production-records']='Production Records';
$TITLE_ARRAY['destroyed-product-records']='Destroyed Products Records';
$TITLE_ARRAY['product-quantity-records']="Product Quantity Records";
$TITLE_ARRAY['plant-animal-process-records']="Plant/Animal Process Records";
$TITLE_ARRAY['product-quantity-current']="Current Product Quantities";
$TITLE_ARRAY['retail-sales']="Retail Sales";
$TITLE_ARRAY['deliveries-sent']="Sent Deliveries";
$TITLE_ARRAY['deliveries-received']="Received Deliveries";
$TITLE_ARRAY['bank-account-transactions']="Bank Account Transactions";
$TITLE_ARRAY['product-change-records']="Product Change Records";


$page=(isset($_REQUEST['page']))?$_REQUEST['page']:"dashboard";
$table=(isset($_REQUEST['page']))?$TABLE_NAME[$_REQUEST['page']]:"";
$table_headers="";

require_once('functions.php');
?>

<!DOCTYPE html>
<html>
    <head>
        <title><?= $TITLE_ARRAY[$page]." | Reports | ".SOFTWARE_NAME ?> </title>
        <?php require_once(realpath(dirname(__FILE__) . '/..') ."/"."include/header.php")?>

    </head>
    <body>
        <?php require_once (realpath(dirname(__FILE__) . '/..') ."/"."nav.php");?>

        <main>
            <div class='container'>
                <?php include_once((($PAGE_NAME[$page])? $PAGE_NAME[$page]:"dashboard").".php")?>
            </div>
        </main>
        <?php require_once(realpath(dirname(__FILE__) . '/..') ."/"."include/footer.php")?>
    </body>
</html>
