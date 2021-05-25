<?php
/* Copyright (C) 2021 SuperAdmin
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    bookticket/lib/bookticket.lib.php
 * \ingroup bookticket
 * \brief   Library files with common functions for BookTicket
 */


// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");

// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php");

// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/bticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/ship.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/travel.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/passenger.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/classe.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/agence.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/lib/bticket.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/modules_bticket.php';
require DOL_DOCUMENT_ROOT.'/custom/bookticket/plugins/invoice/invoice.php';


/*
 * Actions
 */

$pdf = new PDF_Invoice( 'P', 'mm', 'A4' );
$pdf->AddPage();
$pdf->Image('img/DVM.jpg', 10, 10, 28, 28);
$pdf->addSociete( "MaSociete",
				"MonAdresse\n" .
				"75000 PARIS\n".
				"R.C.S. PARIS B 000 000 007\n" );


$pdf->fact_dev( "Billet ", "REF" );

//$pdf->temporaire( "Devis temporaire" );

$pdf->addDate( "03/12/2021");

//$pdf->addClient("CL01");

//$pdf->addPageNumber("1");

$pdf->addClientAdresse("Ste\nM. XXXX\n3ème étage\n33, rue d'ailleurs\n75000 PARIS");

$pdf->addReglement("Chèque à réception de facture");

$pdf->addEcheance("03/12/2021");

//$pdf->addNumTVA("FR888777666");

$pdf->addNote("Devis ... du ....");

$cols=array( "REF"    	=> 20,
			 "Date"  	=> 20,
			 "Heure"  	=> 20,
			 "De"     	=> 30,
			 "Vers"     => 30,
			 "Classe" 	=> 15,
			 "Détails"  => 35,
			 "Bg"   => 10,
			 "St"   => 20 );
$pdf->addCols( $cols);
$cols=array( "REF"   	 => "L",
			 "Date"  	 => "L",
			 "Heure"     => "C",
			 "De"     	 => "R",
			 "Vers" 	 => "R",
			 "Classe"    => "C",
			 "Détails"   => "C",
			 "Bg"    => "C",
			 "St"    => "C" );
//$pdf->addLineFormat( $cols);
$pdf->addLineFormat($cols);

$y    = 109;
$line = array(  "REF"   	=> "L",
				"Date"  	=> "L",
				"Heure"     => "C",
				"De"     	=> "R",
				"Vers" 	 	=> "R",
				"Classe"    => "C",
				"Détails"   => "C",
				"Bg"    => "C",
				"St"    => "C"  );
$size = $pdf->addLine( $y, $line );
$y   += $size + 2;

$pdf->addCadrePrice();

$pdf->addPrice( $params, $tab_tva, $tot_prods);

$pdf->addCondition("Devis ... du ....");

$pdf->Output('D', 'test.pdf', true);

