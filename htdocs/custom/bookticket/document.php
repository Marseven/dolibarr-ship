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

$date = date('d/m/Y');
$expire = date('d/m/Y',strtotime('+3 month',strtotime($date)));

$id = GETPOST('id', 'int');
$socid = GETPOST('socid', 'int');

if (!empty($user->socid)) $socid = $user->socid;

$object = new Bticket($db);
$result_bticket = $object->fetch($id);
if ($result <= 0) dol_print_error('', $object->error);
$object_passenger = new Passenger($db);
$object_passenger->fetch($object->fk_passenger);

$sql_t = 'SELECT DISTINCT t.rowid, t.ref, t.barcode, s.label as ship, tr.lieu_depart as de, tr.lieu_arrive as vers,  c.labelshort as classe, c.kilo_bagage as kilo, c.prix_standard as prix, tr.jour as jour, tr.heure as heure, tr.ref as travel, a.label as agence, t.entity';
$sql_t .= ' FROM '.MAIN_DB_PREFIX.'bookticket_bticket as t';
$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_ship as s ON t.fk_ship = s.rowid";
$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_passenger as p ON t.fk_passenger = p.rowid";
$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_classe as c ON t.fk_classe = c.rowid";
$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_travel as tr ON t.fk_travel = tr.rowid";
$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_agence as a ON t.fk_agence = a.rowid";
$sql_t .= ' WHERE t.entity IN ('.getEntity('bticket').')';
$sql_t .= ' AND t.rowid IN ('.$object->id.')';
$resql_t = $db->query($sql_t);
$obj = $db->fetch_object($resql_t);

$mysoc->getFullAddress();

$pdf = new PDF_Invoice( 'P', 'mm', 'A4' );
$pdf->AddPage();
$pdf->Image('img/DVM.jpg', 10, 10, 28, 28);
$pdf->addSociete( $mysoc->name, $mysoc->getFullAddress()."\n".$obj->agence );


$pdf->fact_dev( "Billet ", $obj->ref );

$pdf->addDate($date);

$pdf->addClientAdresse( $object_passenger->nom." ".$object_passenger->prenom." \n".$object_passenger->adresse."\n".$object_passenger->telephone."\n".$object_passenger->email);

$pdf->addReglement("Airtel Money");

$pdf->addEcheance($expire);

$pdf->addNote("Lorem ipsum dolor sit amet, consectetur adipiscing elit.
Mauris sed nibh at mi blandit imperdiet. Nullam hendrerit sollicitudin ante sit amet commodo.
Quisque at arcu lobortis purus pulvinar pulvinar in in turpis.");

$cols=array( "REF"    	=> 20,
			 "Date"  	=> 20,
			 "Heure"  	=> 20,
			 "De"     	=> 30,
			 "Vers"     => 30,
			 "Classe" 	=> 15,
			 "Détails"  => 35,
			 "Bg"   => 10,
			 "St"   => 10 );
$pdf->addCols( $cols);
$cols=array( "REF"   	 => "C",
			 "Date"  	 => "C",
			 "Heure"     => "C",
			 "De"     	 => "C",
			 "Vers" 	 => "C",
			 "Classe"    => "C",
			 "Détails"   => "C",
			 "Bg"    	 => "C",
			 "St"    	 => "C" );
$pdf->addLineFormat($cols);
$pdf->addLineFormat($cols);

$y    = 109;
$line = array(  "REF"   	=> $obj->ref,
				"Date"  	=> $obj->jour,
				"Heure"     => $obj->heure,
				"De"     	=> $obj->de,
				"Vers" 	 	=> $obj->vers,
				"Classe"    => $obj->classe,
				"Détails"   => $object_passenger->accompagne == "on" ? " 1 Adulte avec enfant" : "1 Adulte",
				"Bg"   	    => $obj->kilo,
				"St"    	=> $object->status == 2 ? "C" : "R"  );
$size = $pdf->addLine( $y, $line );
$y   += $size + 2;

$pdf->addCadrePrice();

$pdf->addPrice();

$pdf->addCondition("Lorem ipsum dolor sit amet, consectetur adipiscing elit.
Mauris sed nibh at mi blandit imperdiet. Nullam hendrerit sollicitudin ante sit amet commodo.
Quisque at arcu lobortis purus pulvinar pulvinar in in turpis. Vivamus quis nisi massa.
Donec lacinia metus diam, non scelerisque orci hendrerit et. Nulla purus nibh, finibus et turpis et, scelerisque tincidunt tortor.
Sed venenatis lacinia efficitur. Nunc in justo nec diam ultrices bibendum. Duis pharetra sagittis dui. Donec vehicula laoreet finibus.
Proin in odio molestie, tristique elit non, faucibus augue. Aenean eget augue sed nisl convallis elementum. Maecenas sit amet rutrum nibh. C
ras tristique leo ac metus tincidunt, sollicitudin elementum lacus dictum.");

$pdf->Output('D', 'test.pdf', true);

