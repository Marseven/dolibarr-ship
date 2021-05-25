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

/**
 * Prepare admin pages header
 *
 * @return array
 */
function bookticketAdminPrepareHead()
{
	global $langs, $conf;

	$langs->load("bookticket@bookticket");

	$h = 0;
	$head = array();

	$head[$h][0] = dol_buildpath("/bookticket/admin/setup.php", 1);
	$head[$h][1] = $langs->trans("Settings");
	$head[$h][2] = 'settings';
	$h++;

	/*
	$head[$h][0] = dol_buildpath("/bookticket/admin/myobject_extrafields.php", 1);
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'myobject_extrafields';
	$h++;
	*/

	$head[$h][0] = dol_buildpath("/bookticket/admin/about.php", 1);
	$head[$h][1] = $langs->trans("About");
	$head[$h][2] = 'about';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	//$this->tabs = array(
	//	'entity:+tabname:Title:@bookticket:/bookticket/mypage.php?id=__ID__'
	//); // to add new tab
	//$this->tabs = array(
	//	'entity:-tabname:Title:@bookticket:/bookticket/mypage.php?id=__ID__'
	//); // to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'bookticket');

	return $head;
}

/*function bticketPrint(){


	require DOL_DOCUMENT_ROOT.'/custom/bookticket/plugins/invoice/invoice.php';

	$pdf = new PDF_Invoice( 'P', 'mm', 'A4' );
	$pdf->AddPage();
	$pdf->addSociete( "MaSociete",
					"MonAdresse\n" .
					"75000 PARIS\n".
					"R.C.S. PARIS B 000 000 007\n" .
					"Capital : 18000 " . EURO );
	$pdf->fact_dev( "Devis ", "TEMPO" );
	$pdf->temporaire( "Devis temporaire" );
	$pdf->addDate( "03/12/2003");
	$pdf->addClient("CL01");

	$pdf->addPageNumber("1");
	$pdf->addClientAdresse("Ste\nM. XXXX\n3ème étage\n33, rue d'ailleurs\n75000 PARIS");
	$pdf->addReglement("Chèque à réception de facture");
	$pdf->addEcheance("03/12/2003");
	$pdf->addNumTVA("FR888777666");
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
	$pdf->addCadreEurosFrancs();
	$pdf->Output('D', DOL_DOCUMENT_ROOT.'/custom/bookticket/doc/output/test.pdf');
}*/
