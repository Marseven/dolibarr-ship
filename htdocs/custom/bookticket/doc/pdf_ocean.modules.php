<?php
/* Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2008      Raphael Bertrand     <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2015 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
 * Copyright (C) 2012      Cedric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2017-2018 Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2018-2020 Frédéric France      <frederic.france@netlogic.fr>
 * Copyright (C) 2019      Pierre Ardoin      	<mapiolca@me.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *	\file       htdocs/custom/bookticket/doc/pdf_ocean.modules.php
 *	\ingroup    Btickete
 *	\brief      File of Class to generate PDF Bticket with Ocean template
 */
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/modules_bticket.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/bticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/plugins/invoice/invoice.php';


function write_pdf(){
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
	$pdf->addClientAdresse("Ste\nM. XXXX\n3�me �tage\n33, rue d'ailleurs\n75000 PARIS");
	$pdf->addReglement("Ch�que � r�ception de facture");
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
				"DESIGNATION"  => "Carte M�re MSI 6378\n" .
									"Processeur AMD 1Ghz\n" .
									"128Mo SDRAM, 30 Go Disque, CD-ROM, Floppy, Carte vid�o",
				"QUANTITE"     => "1",
				"P.U. HT"      => "600.00",
				"MONTANT H.T." => "600.00",
				"TVA"          => "1" );
	$size = $pdf->addLine( $y, $line );
	$y   += $size + 2;

	$line = array( "REFERENCE"    => "REF2",
				"DESIGNATION"  => "C�ble RS232",
				"QUANTITE"     => "1",
				"P.U. HT"      => "10.00",
				"MONTANT H.T." => "60.00",
				"TVA"          => "1" );
	$size = $pdf->addLine( $y, $line );
	$y   += $size + 2;

	$pdf->addCadreTVAs();

	// invoice = array( "px_unit" => value,
	//                  "qte"     => qte,
	//                  "tva"     => code_tva );
	// tab_tva = array( "1"       => 19.6,
	//                  "2"       => 5.5, ... );
	// params  = array( "RemiseGlobale" => [0|1],
	//                      "remise_tva"     => [1|2...],  // {la remise s'applique sur ce code TVA}
	//                      "remise"         => value,     // {montant de la remise}
	//                      "remise_percent" => percent,   // {pourcentage de remise sur ce montant de TVA}
	//                  "FraisPort"     => [0|1],
	//                      "portTTC"        => value,     // montant des frais de ports TTC
	//                                                     // par defaut la TVA = 19.6 %
	//                      "portHT"         => value,     // montant des frais de ports HT
	//                      "portTVA"        => tva_value, // valeur de la TVA a appliquer sur le montant HT
	//                  "AccompteExige" => [0|1],
	//                      "accompte"         => value    // montant de l'acompte (TTC)
	//                      "accompte_percent" => percent  // pourcentage d'acompte (TTC)
	//                  "Remarque" => "texte"              // texte
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
	$pdf->Output();
}

