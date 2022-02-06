<?php
checkpermissions(array(2,3));

$product_state=array('all', 'true', 'false');
$selected_product_state="";


$product_state_err="";

if ($_SERVER["REQUEST_METHOD"] == "POST"){

    if (
        (isset($_REQUEST['product_state']) && !in_array(test_input($_REQUEST['product_state']), $product_state)))
        {
            $product_state_err='The selected state doesn\'t exist';

    }elseif(isset($_REQUEST['product_state']) && empty($_REQUEST['product_state'])){
    }else{
        $selected_product_state=test_input($_REQUEST['product_state']);

    }
    if (empty($product_state_err)){
        $result_stmt="";
        $result_array=array();
        if($selected_product_state=='all'){
            $result_stmt=pg_query($link, $PAGE_RESULT_SQL[$page]??"SELECT * FROM $table ORDER BY id ASC;");
        }else{
            pg_prepare($link,'stmt_select',"SELECT * FROM $table WHERE active=$1 ORDER BY id ASC;");

            $result_stmt=pg_execute($link, 'stmt_select', array($selected_product_state));
        }

        if ($result_stmt && pg_num_rows($result_stmt)>0){
            $result_array=pg_fetch_all($result_stmt);
            $table_headers=array_keys($result_array[0]);
            ob_end_clean();
            $pdf = new Report_3PDF('L');
            //$pdf->SetFont('Arial','',14);
            $pdf->AddPage();
            $pdf->SetAuthor($_SESSION['username']);
            $pdf->SetTitle($TITLE_ARRAY[$page]." ".date("Y-m-d H:i"));
            $pdf->SetSubject('TCPDF Tutorial');
            $pdf->SetKeywords('TCPDF, PDF, example, test, guide');
            $pdf->SetHeaderData("logo.png", 45, "Jungelbook".' 048', "Yengas FarmERP");

            $pdf->writeHTML( $pdf->BasicTable($table_headers,$result_array, $TITLE_ARRAY[$page]));
            $pdf->Output($TITLE_ARRAY[$page]." ".date("Y-m-d H:i"));


        }else{
            $product_state_err="There are no $TITLE_ARRAY[$page] in the selected state";
        }



    }
}
?>
<h2>Get <?=$TITLE_ARRAY[$page]?> Report</h2>
<p>Please fill this form.</p>

<form method='post' target="_blank">
<h3><?=$TITLE_ARRAY[$page]?> State:</h3>

    <!--<div class='form-row'>-->
            <div class="form-check form-check-inline">
                <input class="form-check-input <?= (!empty($product_state_err)) ? 'is-invalid' : ''; ?>" type="radio" name="product_state" id="all-product" value="all" checked>
                <label class="form-check-label" for="exampleRadios1">
                    All
                </label>
                <span class="invalid-feedback"><?= $product_state_err ?></span>

            </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="product_state" id="acive-product" value="true">
            <label class="form-check-label" for="exampleRadios2">
                Active
            </label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="product_state" id="inactive-product" value="false" >
            <label class="form-check-label" for="exampleRadios3">
                Inactive(Deleted)
            </label>
        </div>
        <input type="hidden" name="page" value="<?= $page ?>">
    <!--</div>-->
    <div class="form-group">
        <input type="submit" class="btn btn-primary" value="Submit">
        <input type="reset" class="btn btn-default" value="Reset">
    </div>

</form>
