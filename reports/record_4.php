<?php
checkpermissions(array(2,3));
$param=array();
$param['farm']="SELECT quantity.id, product.name,quantity.quantity  FROM erp_product_quantity_current quantity LEFT JOIN erp_product product ON  product.id=quantity.product_id ORDER BY id;";
$param['retail']="SELECT quantity.id,retail.name AS \"Retail Name\" , product.name as \"Product Name\",quantity.quantity   FROM erp_retail_product_quantity_current quantity JOIN erp_retail_unit retail ON retail.id=quantity.retail_id LEFT JOIN erp_product product ON  product.id=quantity.product_id ORDER BY retail.id, product.id;";
$param=$param[$_GET['param']];
$result_stmt=pg_query($link, $param);
$result_array=pg_fetch_all($result_stmt);
$table_headers=array_keys($result_array[0]);
ob_end_clean();
$pdf = new Report_3PDF('L');
$pdf->AddPage();
$pdf->SetAuthor($_SESSION['username']);
$pdf->SetTitle($TITLE_ARRAY[$page]." ".date("Y-m-d H:i"));
$pdf->SetHeaderData("logo.png", 45, "Jungelbook".' 048', "Yengas FarmERP");
ob_end_clean();

$pdf->writeHTML( $pdf->BasicTable($table_headers,$result_array, $TITLE_ARRAY[$page]));
ob_end_clean();
$pdf->Output($TITLE_ARRAY[$page]." ".date("Y-m-d H:i"));
/*
?>
<!--
<h2>Get <?=$TITLE_ARRAY[$page]?> Report</h2>
<p>Please fill this form.</p>

<form method='post' target="_blank">
<h3><?=$TITLE_ARRAY[$page]?> State:</h3>

    <!--<div class='form-row'>-->
    <div class='form-row'>
        <div class='col-sm-12'><h3>Check all that apply</h3></div>
        <div class="form-check form-check-inline mb-4">
            <input class="form-check-input  <?= (!empty($purchase_err)) ? 'is_invalid' : ''; ?>" type="checkbox" value="true" id="purchase" name="purchase"<?= ($purchase=='true') ? 'checked' : ''; ?>>
            <label class="form-check-label" for="purchase">
                Can Be Purchased
            </label>
            <span class="invalid-feedback"><?= $purchase_err; ?></span>

        </div>
        <div class="form-check form-check-inline mb-4">
            <input class="form-check-input <?= (!empty($sold_err)) ? 'is-invalid' : ''; ?>" type="checkbox" value="true" id="sold" name="sold" <?= ($sold=='true') ? 'checked' : ''; ?>>
            <label class="form-check-label" for="sold">
                Can Be Sold
            </label>
            <span class="invalid-feedback"><?= $sold_err; ?></span>

        </div>
        <div class="form-check form-check-inline mb-4">
            <input class="form-check-input <?= (!empty($grows_err)) ? 'is-invalid' : ''; ?>" type="checkbox" value="true" id="grows" name="grows" <?= ($grows=='true') ? 'checked' : ''; ?> >
            <label class="form-check-label" for="grows">
                Grows
            </label>
            <span class="invalid-feedback"><?= $grows_err; ?></span>

        </div>   
        <div class="form-check form-check-inline mb-4">
            <input class="form-check-input <?= (!empty($consumable_err) || !empty($consumable_grow_err)) ? 'is-invalid' : ''; ?>" type="checkbox" value="true" id="consumable" name="consumable" <?= ($consumable=='true') ? 'checked' : ''; ?>>
            <label class="form-check-label" for="consumable">
                Consumable
            </label>
            <span class="invalid-feedback"><?= $consumable_err; echo $consumable_grow_err ?></span>

        </div>
        <div class="form-check form-check-inline mb-4">
            <input class="form-check-input <?= (!empty($non_consumable_err) ) ? 'is-invalid' : ''; ?>" type="checkbox" value="true" id="non-consumable" name="non-consumable" <?= ($non_consumable=='true') ? 'checked' : ''; ?>>
            <label class="form-check-label" for="non-consumable">
                Non-Consumable
            </label>
            <span class="invalid-feedback"><?= $non_consumable_err ?></span>

        </div>
        <div class="form-check form-check-inline mb-4">
            <input class="form-check-input <?= (!empty($produced_err) || !empty($produced_grow_err)) ? 'is-invalid' : ''; ?>" type="checkbox" value="true" id="produced" name="produced" <?= ($produced=='true') ? 'checked' : ''; ?>>
            <label class="form-check-label" for="produced">
                Can Be Produced
            </label>
            <span class="invalid-feedback"><?= $produced_err;?></span>

        </div>
        <input type="hidden" name="page" value="<?= $page ?>">
    <!--</div>-->
    <div class="form-group">
        <input type="submit" class="btn btn-primary" value="Submit">
        <input type="reset" class="btn btn-default" value="Reset">
    </div>

</form>
*/