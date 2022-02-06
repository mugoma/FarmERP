<?php
checkpermissions(array(2,3));

$from_date=$to_date="";
$from_date_err=$to_date_err="";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    if (isset($_REQUEST['from_date']) && !empty($_REQUEST['from_date']) && !test_date($_REQUEST['from_date'])){
        $from_date_err='Invalid date submitted';

    }else{
        $from_date=test_input($_REQUEST['from_date']);
    }

    if (isset($_REQUEST['to_date'])  && !empty($_REQUEST['to_date'])&& !test_date($_REQUEST['to_date'])){
        $to_date_err='Invalid date submitted';

    }else{
        $to_date=test_input($_REQUEST['to_date']);
    }
    $result_array=array();
    $date_sql="";

    if(empty($from_date_err) && empty($to_date_err)){
        $sql=$PAGE_RESULT_SQL[$page]??"SELECT * FROM $table ";
        $result_stmt="";

        if (empty($from_date) && empty($to_date)){
            $sql.=";";
            $result_stmt=pg_query($link, $sql);

        }else{
            $date_table=$DATE_TABLE[$page]??$table;
            $date_column=($page=='bank-account-transactions')?'datetime_recorded':'date';
            $sql.=(!empty($from_date))? " WHERE (date_trunc('day',$date_table.$date_column)) >= $1 ":"";
            $date_sql.=(!empty($from_date))? " date >= '$from_date' ":"";
            if (!empty($to_date) && !empty($from_date)){
                $sql.="AND date_trunc('day',$date_table.$date_column)<= $2";
                $date_sql.="AND date<= '$to_date'";
            }elseif(!empty($to_date) && empty($from_date)){
                $sql.=" WHERE(date_trunc('day',$date_table.$date_column) <= $1";
                $date_sql.=" date <= '$to_date'";
            }
            $sql.=");";
            //$sql.="WHERE ".$date_sql;
            if(pg_prepare($link, 'stmt_select', $sql)){
                $param_array=array();
                if (!empty($to_date) && !empty($from_date)){
                    array_push($param_array, $from_date, $to_date);
                }else{
                    array_push($param_array, (!empty($from_date))?$from_date:$to_date);
                }


                $result_stmt=pg_execute($link, 'stmt_select', $param_array);
            }
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
            $pdf->SetHeaderData("logo.png", 45, "Jungelbook".' 048', "Yengas FarmERP");
            if ($page=='cashbook'){
                $pdf->writeHTML( $pdf->CashbookTable($table_headers,$result_array, $TITLE_ARRAY[$page], " AND ".$date_sql));
            }else{
                $pdf->writeHTML( $pdf->BasicTable($table_headers,$result_array, $TITLE_ARRAY[$page]));
            }
            //echo($pdf->CashbookTable($table_headers,$result_array, $TITLE_ARRAY[$page], " AND ".$date_sql));
            $pdf->Output($TITLE_ARRAY[$page]." ".date("Y-m-d H:i"));
        }else{
            $from_date_err='No rows fetched for selected period!';
            $to_date_err='No rows fetched for selected period!';
        }

    }
}
?>
<h2>Get <?=$TITLE_ARRAY[$page]?> Report</h2>
<p>Please fill this form.</p>
<form method='post' target="_blank">
    <h3>Period</h3>

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
