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
$pdf->Image('img/DVM.jpg', 10, 10, 20, 20);
$pdf->addSociete( "MaSociete",
				"MonAdresse\n" .
				"75000 PARIS\n".
				"R.C.S. PARIS B 000 000 007\n" );


$pdf->fact_dev( "Billet ", "REF" );

//$pdf->temporaire( "Devis temporaire" );

$pdf->addDate( "03/12/2003");

//$pdf->addClient("CL01");

//$pdf->addPageNumber("1");

$pdf->addClientAdresse("Ste\nM. XXXX\n3ème étage\n33, rue d'ailleurs\n75000 PARIS");

$pdf->addReglement("Chèque à réception de facture");

$pdf->addEcheance("03/12/2003");

//$pdf->addNumTVA("FR888777666");

$pdf->addReference("Devis ... du ....");

$cols=array( "REFERENCE"    => 23,
			"DESIGNATION"  => 78,
			"QUANTITE"     => 22,
			"P.U. HT"      => 26,
			"MONTANT H.T." => 30,
			"TVA"          => 11 );
$pdf->addCols( $cols);
$cols=array( "REFERENCE"    => "L",
			"DESIGNATION"  => "L",
			"QUANTITE"     => "C",
			"P.U. HT"      => "R",
			"MONTANT H.T." => "R",
			"TVA"          => "C" );
$pdf->addLineFormat( $cols);
$pdf->addLineFormat($cols);

$y    = 109;
$line = array( "REFERENCE"    => "REF1",
			"DESIGNATION"  => "Carte Mère MSI 6378\n" .
								"Processeur AMD 1Ghz\n" .
								"128Mo SDRAM, 30 Go Disque, CD-ROM, Floppy, Carte vidéo",
			"QUANTITE"     => "1",
			"P.U. HT"      => "600.00",
			"MONTANT H.T." => "600.00",
			"TVA"          => "1" );
$size = $pdf->addLine( $y, $line );
$y   += $size + 2;

$line = array( "REFERENCE"    => "REF2",
			"DESIGNATION"  => "Câble RS232",
			"QUANTITE"     => "1",
			"P.U. HT"      => "10.00",
			"MONTANT H.T." => "60.00",
			"TVA"          => "1" );
$size = $pdf->addLine( $y, $line );
$y   += $size + 2;

$pdf->addCadreTVAs();

$tot_prods = array( array ( "px_unit" => 600, "qte" => 1, "tva" => 1 ),
					array ( "px_unit" =>  10, "qte" => 1, "tva" => 1 ));
$tab_tva = array( "1"       => 19.6,
				"2"       => 5.5);
$params  = array( "RemiseGlobale" => 1,
					"remise_tva"     => 1,       // {la remise s'applique sur ce code TVA}
					"remise"         => 0,       // {montant de la remise}
					"remise_percent" => 10,      // {pourcentage de remise sur ce montant de TVA}
				"FraisPort"     => 1,
					"portTTC"        => 10,      // montant des frais de ports TTC
												// par defaut la TVA = 19.6 %
					"portHT"         => 0,       // montant des frais de ports HT
					"portTVA"        => 19.6,    // valeur de la TVA a appliquer sur le montant HT
				"AccompteExige" => 1,
					"accompte"         => 0,     // montant de l'acompte (TTC)
					"accompte_percent" => 15,    // pourcentage d'acompte (TTC)
				"Remarque" => "Avec un acompte, svp..." );

$pdf->addTVAs( $params, $tab_tva, $tot_prods);

$pdf->addReference("Devis ... du ....");

//$pdf->addCadreEurosFrancs();
$pdf->Output('D', 'test.pdf', true);

