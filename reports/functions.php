<?php
//require('fpdf/fpdf.php');
require_once('tcpdf/examples/tcpdf_include.php');
require_once('tcpdf/tcpdf.php');



/*if (@file_exists(dirname(__FILE__).'/tcpdf/examples/lang/eng.php')) {
	require_once(dirname(__FILE__).'/tcpdf/examples/lang/eng.php');
	$pdf->setLanguageArray($l);
}*/


class Report_3PDF extends TCPDF{

	function BasicTable($headers, $data, $title=null){
		global $tablehtml;
		$tablehtml.="<style>
		#myTable {
			font-family: 'Trebuchet MS', Arial, Helvetica, sans-serif;
			border-collapse: collapse;
			width: 100%;
		}		  
		  #myTable td, table th {
			border: 1px solid #ddd;
			padding: 8px;
		  }		  
		  #myTable tr:nth-child(even){background-color: #f2f2f2;}
		  
		  #myTable tr:hover {background-color: #ddd;}
		  
		  #myTable th {
			padding-top: 12px;
			padding-bottom: 12px;
			text-align: left;
			background-color: #4CAF50;
			color: white;
		  }
		  h1,h2,h3{
			text-align: center;

		  }
		</style>";
		$tablehtml.="<h1 style='text-align:center'>Yengas FarmERP</h1>";
		$tablehtml.="<h2 style='text-align:center'>$title Report</h2>";
		$tablehtml.="<h3>".date("Y-m-d H:i")."</h3>";
		$tablehtml.='<table  id="myTable" border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse"><tr>';
		foreach($headers as $col){
			$col=str_replace('_', ' ', $col);
			$col=ucwords($col);
				$tablehtml.='<th style="padding-top: 12px;
			padding-bottom: 12px;
			text-align: left;
			background-color: #4CAF50;
			color: white">'.($col)."</th>";	
		}
		$tablehtml.="</tr>";	
		foreach($data as $row){
			$tablehtml.="<tr>";
			foreach($row as $col){
				$col=str_replace('{', '', $col);
				$col=str_replace('}', '', $col);
				$col=str_replace(',', '<br />', $col);
		
				$col=($col==='t')?"True":$col;
				$col=($col==='f')?"False":$col;
	
				$tablehtml.="<td>$col</td>";
			}
			$tablehtml.="</tr>";
	
		}
	
	
		
	
		$tablehtml.="</table>";
	
		return $tablehtml;
	
	}
	function CashbookTable($headers, $data, $title=null, $date_sql){

		global $link;
		$period_cr=pg_fetch_assoc(pg_query($link, "SELECT SUM(amount) FROM erp_cashbook WHERE transaction_type='Cr' ".$date_sql))['sum'];
		$period_dr=pg_fetch_assoc(pg_query($link, "SELECT SUM(amount) FROM erp_cashbook WHERE transaction_type='Dr' ".$date_sql))['sum'];
		$total_cr=pg_fetch_assoc(pg_query($link, "SELECT SUM(amount) FROM erp_cashbook WHERE transaction_type='Cr' "))['sum'];
		$total_dr=pg_fetch_assoc(pg_query($link, "SELECT SUM(amount) FROM erp_cashbook WHERE transaction_type='Dr' "))['sum'];
		global $tablehtml;
		$tablehtml.="<style>
		#myTable {
			font-family: 'Trebuchet MS', Arial, Helvetica, sans-serif;
			border-collapse: collapse;
			width: 100%;
		}		  
		  #myTable td, table th {
			border: 1px solid #ddd;
			padding: 8px;
		  }		  
		  #myTable tr:nth-child(even){background-color: #f2f2f2;}
		  
		  #myTable tr:hover {background-color: #ddd;}
		  
		  #myTable th {
			padding-top: 12px;
			padding-bottom: 12px;
			text-align: left;
			background-color: #4CAF50;
			color: white;
		  }
		  h1,h2,h3{
			text-align: center;

		  }
		</style>";
		$tablehtml.="<h1 style='text-align:center'>Yengas FarmERP</h1>";
		$tablehtml.="<h2 style='text-align:center'>$title Report</h2>";
		$tablehtml.="<h3>".date("Y-m-d H:i")."</h3>";
		$tablehtml.='<table  id="myTable" border="1" cellspacing="0" cellpadding="4" style="border-collapse:collapse"><tr>';
		foreach($headers as $col){
			$col=str_replace('_', ' ', $col);
			$col=ucwords($col);
			if ($col=="Amount"){continue;}
			$tablehtml.=(strtolower($col)=='transaction type')?'<th style="padding-top: 12px;
			padding-bottom: 12px;
			text-align: left;
			background-color: #4CAF50;
			color: white;text-align:center" colspan="2" >Transaction Type</th>':'<th style="padding-top: 12px;
			padding-bottom: 12px;
			text-align: left;
			background-color: #4CAF50;
			color: white">'.($col)."</th>";	
		}
		$tablehtml.="
		</tr>
		<tr>
			<td colspan=\"5\"> </td>
			<td style=\"text-align:center\">Dr</td>
			<td style=\"text-align:center\">Cr</td>
		</tr>";	
		$x=0;
		foreach($data as $row){
			$tablehtml.=($x%2==0)?'<tr style="background-color: #f2f2f2;">':"<tr>";
			
			foreach($row as $col){
				if(array_search($col, $row)=='amount'){continue;
				}
				elseif(array_search($col, $row)=='transaction_type')
				{
					if ($row['transaction_type']=='Dr'){
						$tablehtml.="<td style=\"text-align:center\">$row[amount]</td><td> </td>";
					}elseif($row['transaction_type']=='Cr'){
						$tablehtml.="<td></td><td style=\"text-align:center\">$row[amount]</td>";				

					}

				}else{
					$tablehtml.="<td>$col</td>";
				}

			}
			$x++;
			$tablehtml.="</tr>";
	
		}
		$tablehtml.="<tr><td colspan=\"5\">Period Total</td><td>$period_dr</td><td>$period_cr</td></tr><tr><td colspan=\"5\">Period Balance</td>";
		$dif=(($period_dr+0)-($period_cr+0));
		if($dif>0){

			$tablehtml.="<td style=\"text-align:center\">$dif</td><td> </td>";
		}else{
			$tablehtml.="<td style=\"text-align:center\"></td><td>".abs($dif)."</td>";
		}
	
	
		
	
		$tablehtml.="</tr></table>";
	
		return $tablehtml;
	

	}

}
?>
