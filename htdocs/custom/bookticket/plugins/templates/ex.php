<?php 
require_once ('templates_fpdf.php');

// This sample program uses two distinct templates
$file_tpl1 = "template1.tpl";
$file_tpl2 = "template2.tpl";

// This sample program uses data fetched from a CSV file
$file_csv  = "data.csv";
$lines     = file ($file_csv);
$headers   = explode (";", $lines [0]);	// Headers for columns for the first page
$rows      = array_slice ($lines, 1);	// Data doesn't include the first row which contains headers 
$nb_rows   = count ($rows);

$pdf = new Templates_FPDF();
$pdf->AliasNbPages ("{nb}");			// For page numbering

// Template #1 is used for the part which builds one page per employee
$template1 = $pdf->LoadTemplate ($file_tpl1);
if ($template1 <= 0) {
	die ("  ** Error couldn't load template file '$file_tpl1'");
	}

// ====================================================
//   First page contains the table for all employees 
// ====================================================
	
$pdf->AddPage("L");

// Template #2 is used for the part which builds a table containing all employees
$template2 = $pdf->LoadTemplate ($file_tpl2);
if ($template2 <= 0) {
	die ("  ** Error couldn't load template file '$file_tpl2'");
	}
$pdf->IncludeTemplate ($template2);		
$pdf->ApplyTextProp ("FOOTRNB2", "1 / {nb}");   //  Add a footer with page number

// In the table of the first page, take into account only a subset of fields of CSV file; say fields #0,#2,#3,#5,#6,#7
$subset_csv = array (0, 2, 3, 5, 6, 7);
$nn = count ($subset_csv);

// Get collumns widths with an anchor ID
$pcol = $pdf->GetColls ("COLSWDTH", "");
// Get Text properties of headers
$ptxp = $pdf->ApplyTextProp ("ROW0COL0", "");

for ($ii = 0; $ii < $nn; $ii ++) {
	$data = $headers [$subset_csv [$ii]];
// Column interspace is 1
	$pdf->SetX ($pdf->GetX() + 1);
	$pdf->Cell ($pcol [$ii], $ptxp ['iy'], $data, 1, 0, "C", true);
	}

$pdf->SetFillColor (240, 240, 240);		// for "zebra" effect
// Get Text properties of data cell
$ptxp = $pdf->ApplyTextProp ("ROW1COL0", "");
$py = $ptxp ['py'];			// Initial Y position for data rows		
for ($jj = 0; $jj < $nb_rows; $jj ++) {
	$pdf->SetXY ($ptxp ['px'], $py);
	$fields = explode (";", $rows[$jj]);
	for ($ii = 0; $ii < $nn; $ii ++) {
		$data = trim ($fields [$subset_csv [$ii]]);
// Column interspace is 1
		$pdf->SetX ($pdf->GetX() + 1);
// Last fill boolean parameter switches from false to true to achieve a "zebra" effect
		$pdf->Cell ($pcol [$ii], $ptxp ['iy'], $data, "", 0, "L", $jj & 1);
		}
	$py += $ptxp ['iy'];		// for row interspace
	}
	
// ==================================================
//   Subsequent pages contain one page per employee 
// ==================================================
	
for ($ii = 0; $ii < $nb_rows; $ii ++) {
	$pdf->AddPage("P");
	$pdf->IncludeTemplate ($template1);
	$page_nb = $pdf->PageNo(); 
	$pdf->ApplyTextProp ("FOOTRNB1", "$page_nb / {nb}");   //  Add a footer with page number

	$fields = explode (";", $rows[$ii]);
	foreach ($fields as $kk => $field) {
		$anchor_id = "FIELD" . sprintf ("%03d", $kk);
		$pdf->ApplyTextProp ($anchor_id, trim ($field));
		}
	}

$nb_pages = $pdf->PageNo();
$file_pdf = "ex.pdf";	
$pdf->Output("F", $file_pdf);
print ("  >> File '$file_pdf' generated:  " . "$nb_pages pages  -  " . filesize($file_pdf) . " bytes\n");
?>
