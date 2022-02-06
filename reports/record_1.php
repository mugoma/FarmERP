<?php
checkpermissions(array(2,3));

$from_date=$to_date="";
$from_date_err=$to_date_err=$param_id_err="";
$param_id=array();


$param_query=pg_query($link, $PAGE_FIELD_SQL[$page]);
$param_list=pg_fetch_all($param_query);


if($_SERVER["REQUEST_METHOD"] == "POST"){

    if (isset($_REQUEST['from_date']) && !empty($_REQUEST['from_date']) && !test_date($_REQUEST['from_date'])){
        $from_date_err='Invalid date submitted';

    }else{
        $from_date=test_input($_REQUEST['from_date']);
    }

    if (isset($_REQUEST['to_date']) && !empty($_REQUEST['to_date'])&& !test_date($_REQUEST['to_date'])){
        $to_date_err='Invalid date submitted';

    }else{
        $to_date=test_input($_REQUEST['to_date']);
    }
    if(isset($_REQUEST['param_id']) && !is_array($_REQUEST['param_id'])){
        $param_id_err="Please select a valid $title";

    }elseif(empty($_REQUEST['param_id'])){
        $param_id=array_column($param_list, 'id');

    }else{
        if (count(array_intersect($_REQUEST['param_id'],array_column($param_list, 'id'))) != count($_REQUEST['param_id'])) {
            $param_id_err="One of the selected $title does not exist.";
            } else{
                $param_id=$_REQUEST['param_id'];
            }
    }

if(empty($from_date_err) && empty($to_date_err) && empty($param_id_err)){
    $sql=$PAGE_RESULT_SQL[$page];
    $result_stmt="";
    //$i=1;
    $param_array_id="{";
    for ($i=0; $i < count($param_id); $i++) { 
        $param_array_id.=$param_id[$i];
        $param_array_id.=($i!=count($param_id)-1)?",":"";

        
    }
    $param_array_id.="}";
    


    if (empty($from_date) && empty($to_date)){
        $sql.=$GROUP_BY[$page]??"";
        $sql.=";";
        var_dump($sql);
        $result_stmt=pg_execute($link, $sql);
        //;


    }else{
        $date_table_name=$DATE_TABLE[$page]??$table;
        $sql.=(!empty($from_date))? " AND date_trunc('day',".$date_table_name.".datetime_recorded) >= $".(2):"";
        if (!empty($to_date) && !empty($from_date)){
            $sql.=" AND date_trunc('day',".$date_table_name." .datetime_recorded) <= $".(3);
        }elseif(!empty($to_date) && empty($from_date)){
            $sql.=" AND  date_trunc('day',".$date_table_name.".datetime_recorded) <= $".(2);
        }
        $sql.=$GROUP_BY[$page]??"";

        $sql.=";";
        ;
        if(pg_prepare($link, 'stmt_select', $sql)){
            $param_array=array($param_array_id);
            if (!empty($to_date) && !empty($from_date)){
                array_push($param_array, $from_date, $to_date);
            }else{
            array_push($param_array, $cars=(!empty($from_date))?$from_date:$to_date/*,(!empty($to_date))?$to_date:""*/);
            }


            $result_stmt=pg_execute($link, 'stmt_select', $param_array);
        }
    }
    echo($sql);
    if ($result_stmt && pg_num_rows($result_stmt)>0){
        $result_array=pg_fetch_all($result_stmt);
        $table_headers=array_keys($result_array[0]);
        ob_end_clean();
        $pdf = new Report_3PDF('L');
        //$pdf->SetFont('Arial','',14);
        $pdf->AddPage();
        $pdf->SetAuthor($_SESSION['username']);
        $pdf->SetTitle($TITLE_ARRAY[$page]." ".date("Y-m-d H:i"));
        $pdf->SetHeaderData("logo.png", 45, "Jungelbook".' 048', "Yengas FarmERP");

        $pdf->writeHTML( $pdf->BasicTable($table_headers,$result_array, $TITLE_ARRAY[$page]));
        $pdf->Output($TITLE_ARRAY[$page]." ".date("Y-m-d H:i"));
    }else{
        $from_date_err='No rows fetched for selected period!';
        $to_date_err='No rows fetched for selected period!';
    }
        
    

}
}
$param_name_array=array();
$param_name_array['retail-sales']='Retail Unit';
$param_name_array['deliveries-sent']='Retail Unit';
$param_name_array['deliveries-received']='Retail Unit';
$param_name_array['retail-sales']='Retail Unit';
?>
<h2>Get <?=$TITLE_ARRAY[$page]?> Report</h2>
<p>Please fill this form.</p>
<form method='post' target="_blank">
    <!--<div class='form-row'>-->
        <div class="form-group">
            <label for='param'><?= $param_name_array[$page]??$TITLE_ARRAY[$page] ?>:</label>
            <select multiple class="form-control select_multiple  <?= (!empty($param_id_err)) ? 'is-invalid' : ''; ?>" id="param" name="param_id[]" >

                <?php
                    foreach($param_list as $param_int){
                        if (($param_int['id']==$param_id)) {
                            echo "<option value='".$param_int['id']."' selected >".$param_int['name']??$param_int['grow_product_name']."</option>";
                        }
                        else {
                            echo "<option value='".$param_int['id']."'>".$param_int['name']??$param_int['grow_product_name']."</option>";
                        }
                        
                    }
                ?>
            </select>
            <span class="invalid-feedback"><?= $param_id_err ?></span>
        </div>

    <!--</div>-->
    <div class='form-row'>
        <div class='col'>
            <div class="form-group">
                <label for="from-date">From: </label>
                <input type="date" class="form-control <?= (!empty($from_date_err))?'is-invalid':"";?>" id="from-date"name="from_date" >
                <span class="invalid-feedback"><?= $from_date_err ?></span>
            
            </div>
        </div>
        <div class='col'>
            <div class="form-group">
                <label for="to-date">To: </label>
                <input type="date" class="form-control <?= (!empty($to_date_err))?'is-invalid':"";?>" id="to-date"name="to_date" value="<?= date("Y-m-d")?>">
                <span class="invalid-feedback"><?= $to_date_err ?></span>

            </div>
        </div>
    </div>
    <input type="hidden" value="<?= $page ?>" name="page">
    <div class="form-group">
        <input type="submit" class="btn btn-primary" value="Submit">
        <input type="reset" class="btn btn-default" value="Reset">
    </div>
</form>