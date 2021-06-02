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
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/plugins/bticket/bticket.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/plugins/manifeste/manifeste.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/plugins/qrcode/qrcode.class.php';


/*
 * Actions
 */

$date = date('d/m/Y');
$expire = date('d/m/Y H:m:s', strtotime('+3 month'));

$id = GETPOST('id', 'int');
$type = GETPOST('type', 'alpha');
$socid = GETPOST('socid', 'int');
if (!empty($user->socid)) $socid = $user->socid;

$usercancreate = $user->rights->bookticket->bticket->write;

if($usercancreate && $type == 'bticket'){

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

	$pdf = new PDF_Bticket( 'P', 'mm', 'A4' );
	$pdf->AddPage();
	$pdf->Image('img/DVM.jpg', 10, 10, 28, 28);
	$pdf->addSociete( $mysoc->name, $mysoc->getFullAddress());
	$pdf->addAgence( $obj->agence );
	$userCreate = new User($db);
	$userCreate->fetch($object->fk_user_creat);
	$pdf->addAgent( $userCreate->lastname." ".$userCreate->firstname );


	$pdf->fact_dev( "Billet ", $obj->ref );

	$pdf->addClientAdresse( $object_passenger->nom." ".$object_passenger->prenom, $object_passenger->adresse."\n".$object_passenger->telephone."\n".$object_passenger->email);

	$pdf->addReglement("Airtel Money");

	$pdf->addAchat($date);

	$pdf->addExpiration($expire);

	$pdf->addNote("Lorem ipsum dolor sit amet, consectetur adipiscing elit.
	Mauris sed nibh at mi blandit imperdiet. Nullam hendrerit sollicitudin ante sit amet commodo.
	Quisque at arcu lobortis purus pulvinar pulvinar in in turpis.");

	$cols=array( "REF"    	=> 20,
				"Date"  	=> 20,
				"Heure"  	=> 20,
				"De"     	=> 30,
				"Vers"     => 30,
				"Classe" 	=> 15,
				"Details"  => 35,
				"Bg"   => 10,
				"St"   => 10 );
	$pdf->addCols( $cols);
	$cols=array( "REF"   	 => "C",
				"Date"  	 => "C",
				"Heure"     => "C",
				"De"     	 => "C",
				"Vers" 	 => "C",
				"Classe"    => "C",
				"Details"   => "C",
				"Bg"    	 => "C",
				"St"    	 => "C" );
	$pdf->addLineFormat($cols);
	$pdf->addLineFormat($cols);

	$y    = 129;
	$line = array(  "REF"   	=> $obj->ref,
					"Date"  	=> $obj->jour,
					"Heure"     => $obj->heure,
					"De"     	=> $obj->de,
					"Vers" 	 	=> $obj->vers,
					"Classe"    => $obj->classe,
					"Details"   => $object_passenger->accompagne == "on" ? " 1 Adulte avec enfant" : "1 Adulte",
					"Bg"   	    => $obj->kilo." Kg",
					"St"    	=> $object->status == 2 ? "C" : "R"  );
	$size = $pdf->addLine( $y, $line );
	$y   += $size + 2;

	$pdf->addCadrePrice();

	$pdf->addPrice($obj->prix, 0, 0, $obj->prix);

	$pdf->addCondition("Lorem ipsum dolor sit amet, consectetur adipiscing elit.
	Mauris sed nibh at mi blandit imperdiet. Nullam hendrerit sollicitudin ante sit amet commodo.
	Quisque at arcu lobortis purus pulvinar pulvinar in in turpis. Vivamus quis nisi massa.
	Donec lacinia metus diam, non scelerisque orci hendrerit et. Nulla purus nibh, finibus et turpis et.
	Sed venenatis lacinia efficitur. Nunc in justo nec diam ultrices bibendum. Duis pharetra sagittis dui.
	Proin in odio molestie, tristique elit non, faucibus augue. Aenean eget augue sed nisl convallis elementum.
	ras tristique leo ac metus tincidunt, sollicitudin elementum lacus dictum.");

	$qrcode = new QRcode('Billet N° '.$obj->ref.' valide pour Douya Voyage Maritime', 'H');

	$qrcode->displayFPDF($pdf, 150, 17, 20);

	$file_pdf = $obj->ref."_".date("dmYHis").".pdf";

	$pdf->Output('D', $file_pdf, true);

}

if($usercancreate && $type == 'travel'){

	$object = new Travel($db);
	$result_travel = $object->fetch($id);
	if ($result_travel <= 0) dol_print_error('', $object->error);


	// This sample program uses two distinct templates
	$file_tpl = DOL_DOCUMENT_ROOT.'/custom/bookticket/plugins/manifeste/template.tpl';

	// This sample program uses data fetched from a CSV file

	$btickets = [];
	$sql_t = 'SELECT DISTINCT t.rowid, t.ref, t.barcode, p.telephone as telephone, p.nom as nom, p.prenom as prenom,  c.labelshort as classe, p.nationalite as nationalite, p.age as age, c.prix_standard as prix, tr.ref as travel, t.entity';
	$sql_t .= ' FROM '.MAIN_DB_PREFIX.'bookticket_bticket as t';
	$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_ship as s ON t.fk_ship = s.rowid";
	$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_passenger as p ON t.fk_passenger = p.rowid";
	$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_classe as c ON t.fk_classe = c.rowid";
	$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_travel as tr ON t.fk_travel = tr.rowid";
	$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_agence as a ON t.fk_agence = a.rowid";
	$sql_t .= ' WHERE t.entity IN ('.getEntity('bticket').')';
	$sql_t .= ' AND tr.rowid IN ('.$object->id.')';
	$resql_t = $db->query($sql_t);
	if ($resql_t)
	{
		$num = $db->num_rows($resql_t);
		$i = 0;
		if ($num)
		{
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql_t);
				if ($obj)
				{
					$btickets[$i] = $obj;
				}
				$i++;
			}
		}
	}

	$pdf = new Manifeste();
	$pdf->AliasNbPages ("{nb}");			// For page numbering


	// ====================================================
	//   First page contains the table for all employees
	// ====================================================

	$pdf->AddPage("P");

	// Template #2 is used for the part which builds a table containing all employees
	$template = $pdf->LoadTemplate($file_tpl);
	if ($template <= 0) {
		die ("  ** Error couldn't load template file '$file_tpl'");
	}
	$pdf->IncludeTemplate ($template);
	$pdf->Image('img/DVM.jpg', 10, 5, 15, 15);

	$society = "DOUYA  VOYAGE MARITIME";
	$society1 =	"D.V.M  S.A";
	$society2 =	"Siège social- Libreville\n";
	$society3 =	"B.P : 14050 Libreville-  Gabon – Email : douya.voyagemaritime@ gmail.com";
	$society4 =	"Libreville  Tél : ( +241)  07 52 56 05 – 04 18 67 36-  06 03 29 85";
	$society5 =	"Port-Gentil Tél : (+241 ) 06 35 90 35- 05 34 54 88- 07 44 85 19";

	$pdf->ApplyTextProp("SOCIETY", utf8_decode($society));
	$pdf->ApplyTextProp("SOCIETY1", utf8_decode($society1));
	$pdf->ApplyTextProp("SOCIETY2", utf8_decode($society2));
	$pdf->ApplyTextProp("SOCIETY3", utf8_decode($society3));
	$pdf->ApplyTextProp("SOCIETY4", utf8_decode($society4));
	$pdf->ApplyTextProp("SOCIETY5", utf8_decode($society5));

	$pdf->ApplyTextProp ("FOOTRNB2", "1 / {nb}");   //  Add a footer with page number
	$pdf->ApplyTextProp ("TITLE", utf8_decode("Manifeste du Voyage N° ").$btickets[0]->travel);   //  Add a footer with page number
	$pdf->ApplyTextProp ("FOOTTITLE", utf8_decode("Manifeste du Voyage N° ").$btickets[0]->travel);   //  Add a footer with page number

	// In the table of the first page, take into account only a subset of fields of CSV file; say fields #0,#2,#3,#5,#6,#7
	$nn = count ($btickets);

	// Get collumns widths with an anchor ID
	$pcol = $pdf->GetColls ("COLSWDTH", "");
	// Get Text properties of headers
	$ptxp = $pdf->ApplyTextProp ("ROW0COL0", "");

	// Column interspace is 1
	$pdf->SetX ($pdf->GetX() + 1);
	$pdf->Cell ($pcol [0], $ptxp ['iy'], "No", 1, 0, "C", true);
	$pdf->SetX ($pdf->GetX() + 1);
	$pdf->Cell ($pcol [1], $ptxp ['iy'], "Nom", 1, 0, "C", true);
	$pdf->SetX ($pdf->GetX() + 1);
	$pdf->Cell ($pcol [2], $ptxp ['iy'], "Prenom", 1, 0, "C", true);
	$pdf->SetX ($pdf->GetX() + 1);
	$pdf->Cell ($pcol [3], $ptxp ['iy'], "Nationalite", 1, 0, "C", true);
	$pdf->SetX ($pdf->GetX() + 1);
	$pdf->Cell ($pcol [4], $ptxp ['iy'], "No Billet", 1, 0, "C", true);
	$pdf->SetX ($pdf->GetX() + 1);
	$pdf->Cell ($pcol [5], $ptxp ['iy'], "Telephone", 1, 0, "C", true);


	$pdf->SetFillColor (240, 240, 240);		// for "zebra" effect
	// Get Text properties of data cell
	$ptxp = $pdf->ApplyTextProp ("ROW1COL0", "");
	$py = $ptxp ['py'];			// Initial Y position for data rows
	for ($jj = 0; $jj < $nn; $jj ++) {
		$pdf->SetXY ($ptxp ['px'], $py);

		// Column interspace is 1
		$pdf->SetX ($pdf->GetX() + 1);
		// Last fill boolean parameter switches from false to true to achieve a "zebra" effect
		$pdf->Cell ($pcol [0], $ptxp ['iy'], $jj , "", 0, "L", $jj & 1);
		// Column interspace is 1
		$pdf->SetX ($pdf->GetX() + 1);
		// Last fill boolean parameter switches from false to true to achieve a "zebra" effect
		$pdf->Cell ($pcol [1], $ptxp ['iy'], $btickets[$jj]->nom, "", 0, "L", $jj & 1);
		// Column interspace is 1
		$pdf->SetX ($pdf->GetX() + 1);
		// Last fill boolean parameter switches from false to true to achieve a "zebra" effect
		$pdf->Cell ($pcol [2], $ptxp ['iy'], $btickets[$jj]->prenom, "", 0, "L", $jj & 1);
		// Column interspace is 1
		$pdf->SetX ($pdf->GetX() + 1);
		// Last fill boolean parameter switches from false to true to achieve a "zebra" effect
		$pdf->Cell ($pcol [3], $ptxp ['iy'], $btickets[$jj]->nationalite, "", 0, "L", $jj & 1);
		// Column interspace is 1
		$pdf->SetX ($pdf->GetX() + 1);
		// Last fill boolean parameter switches from false to true to achieve a "zebra" effect
		$pdf->Cell ($pcol [4], $ptxp ['iy'], $btickets[$jj]->ref, "", 0, "L", $jj & 1);
		// Column interspace is 1
		$pdf->SetX ($pdf->GetX() + 1);
		// Last fill boolean parameter switches from false to true to achieve a "zebra" effect
		$pdf->Cell ($pcol [5], $ptxp ['iy'], $btickets[$jj]->telephone, "", 0, "L", $jj & 1);
		// Column interspace is 1
		$pdf->SetX ($pdf->GetX() + 1);
		// Last fill boolean parameter switches from false to true to achieve a "zebra" effect
		$pdf->Cell ($pcol [6], $ptxp ['iy'], "", "", 0, "L", $jj & 1);

		$py += $ptxp ['iy'];		// for row interspace
	}

	$file_pdf = $btickets[0]->travel."_".date("dmYHis").".pdf";
	$pdf->Output("D", $file_pdf, true);
}
