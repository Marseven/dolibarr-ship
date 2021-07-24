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
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/penalite.class.php';
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

	if($object_passenger->accompagne == "on"){
		$object_accompgneur = new Passenger($db);
		$object_accompgneur->fetch($object->fk_passenger_acc);
	}


	$sql_t = 'SELECT DISTINCT t.rowid, t.ref, t.categorie, s.label as ship, tr.lieu_depart as de, tr.lieu_arrive as vers,  c.labelshort as classe, c.kilo_bagage as kilo, t.prix as prix, c.prix_standard as prix_standard, c.prix_enf_por as prix_enf_por, c.prix_enf_acc as prix_enf_acc, c.prix_enf_dvm as prix_enf_dvm, tr.jour as jour, tr.heure as heure, tr.ref as travel, a.label as agence, t.entity, t.date_creation';
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

	$sql_p = "SELECT sum(b.prix_da) as da, sum(b.prix_db) as db, sum(b.prix_n) as n, sum(b.prix_bp) as bp, sum(b.prix_c) as c, sum(b.prix_ce) as ce";
	$sql_p .= " FROM ".MAIN_DB_PREFIX."bookticket_penalite as b";
	$sql_p .= " WHERE b.status > 0";
	$sql_p .= " AND b.entity IN (".getEntity('penalite').")";
	$sql_p .= " AND b.fk_bticket IN (".$object->id.")";
	$resql_p = $db->query($sql_p);
	$obj_p = $db->fetch_object($resql_p);

	$penalites = $obj_p->da + $obj_p->db + $obj_p->n + $obj_p->bp + $obj_p->c + $obj_p->ce;

	$date = dol_print_date($obj->date_creation, 'day', 'tzuser');
	$date1 = new DateTime($obj->date_creation);
	$date1 = $date1->modify("+ 3 months");
	$expire = $date1->format('d/m/Y');

	$heure1 = new DateTime($obj->heure);
	$heure1 = $heure1->modify("- 2 hour");
	$heuresave = $heure1->format('H:i');

	$heure2 = new DateTime($obj->heure);
	$heure2 = $heure2->modify("- 30 minutes");
	$heuresave1 = $heure2->format('H:i');

	$heure = new DateTime($obj->heure);
	$heure = $heure->format('H:i');

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

	$pdf->addReglement(utf8_decode($obj->mode_paiement));

	$pdf->addAchat($date);

	$pdf->addExpiration($expire);

	$pdf->addNote(utf8_decode("La convocation est prevue a ".$heuresave." et l'enregistrement termine a ".$heuresave1."."));

	$cols=array( "REF"    	=> 20,
				"Date"  	=> 23,
				"Heure"  	=> 20,
				"De"     	=> 30,
				"Vers"     => 30,
				"Classe" 	=> 15,
				"Categorie"  => 35,
				"Bg"   => 15 );
	$pdf->addCols( $cols);
	$cols=array( "REF"   	 => "C",
				"Date"  	 => "C",
				"Heure"     => "C",
				"De"     	 => "C",
				"Vers" 	 => "C",
				"Classe"    => "C",
				"Categorie"   => "C",
				"Bg"    	 => "C");
	$pdf->addLineFormat($cols);
	$pdf->addLineFormat($cols);

	$y    = 119;
	$line = array(  "REF"   	=> $obj->ref,
					"Date"  	=> dol_print_date($obj->jour, 'day', 'tzuser'),
					"Heure"     => $heure,
					"De"     	=> $obj->de,
					"Vers" 	 	=> $obj->vers,
					"Classe"    => $obj->classe,
					"Categorie" => $obj->categorie,
					"Bg"   	    => $obj->kilo." Kg" );
	$size = $pdf->addLine( $y, $line );
	$y   += $size + 2;

	$pdf->addCadrePrice();
	$prix = 0;

	$firstDate  = new DateTime(date('Y-m-d'));
	$secondDate = new DateTime(date('Y-m-d', $object_passenger->date_naissance));
	$age = $firstDate->diff($secondDate);

	if($age->y >= 15 && $obj->categorie == 'A'){
		$prix = $obj->prix_standard;
	}elseif(($age->y <= 5 && $age->y >= 0) && $obj->categorie == 'B'){
		$prix = $obj->prix_enf_por;
	}elseif(($age->y < 15 && $age->y >= 6) && $obj->categorie == 'C'){
		$prix = $obj->prix_enf_acc;
	}elseif(($age->y < 15 && $age->y >= 6) && $obj->categorie == 'D'){
		$prix = $obj->prix_enf_dvm;
	}

	$pdf->addPrice($prix, 0, $penalites, $obj->prix);

	$pdf->addCondition(utf8_decode("CONDITIONS GÉNÉRALES DE TRANSPORT

	Validité du billet:
	Le transporteur se réserve le droit de modifier l'itinéraire, les horaires de départ ou annuler le voyage.
	Il n'est responsable des horaires à l'arrivée en fonction des marées ou du mauvais temps.
	Le billet paye a une validité 03 mois à compter de sa date d'émission.Le billet est non remboursable. sauf en cas
	de non exécution du trajet par la compagnie. Le billet est annulé, si le passager n'a pas notifié le changement de
	la date de son voyage au moins 24 h avant le départ. Si le passager a déjà sa carte d'embarquement et qu'il vienne
	après le départ du bateau, son billet devient nul et sans remboursement.

	Heure limite d'enregistrement :
	La convocation est prévue 2h avant l'heure du départ et l'enregistrement termine 30 min avant l'heure du départ.
	Passé ce délai, compagnie se réserve le droit de disposer des passagers qui ni se serait pas présenter à temps.

	Confirmation :
	Si vous n'utilisez pas une place réservée, pensez à avertir la compagnie 24h avant I’heure de départ,
	qui Pourra ainsi l’attribuer à un autre passager en liste d'attente.

	Sécurité :
	Il est strictement	interdit de placer dans les bagages	certains objets dangereux (produits inflammables,
	toxiques, corrosifs, armes blanches ou pompes, munitions, drogues, etc)

	Renseignez-vous auprès de votre agence commerciales."));

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
	$sql_t = 'SELECT DISTINCT t.rowid, t.ref, p.telephone as telephone, p.nom as nom, p.prenom as prenom, tr.ref as travel, tr.lieu_depart as lieu_depart, tr.lieu_arrive as lieu_arrive, tr.jour as depart, s.label as ship, s.ref as refship, ct.label as country, t.entity';
	$sql_t .= ' FROM '.MAIN_DB_PREFIX.'bookticket_bticket as t';
	$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_ship as s ON t.fk_ship = s.rowid";
	$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_passenger as p ON t.fk_passenger = p.rowid";
	$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_classe as c ON t.fk_classe = c.rowid";
	$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_travel as tr ON t.fk_travel = tr.rowid";
	$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_agence as a ON t.fk_agence = a.rowid";
	$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as ct ON p.nationalite = ct.rowid";
	$sql_t .= ' WHERE t.entity IN ('.getEntity('bticket').')';
	$sql_t .= ' AND tr.rowid IN ('.$object->id.')';
	$sql_t .= " ORDER BY p.nom ASC";
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

	$pdf->AddPage("L");

	// Template #2 is used for the part which builds a table containing all employees
	$template = $pdf->LoadTemplate($file_tpl);
	if ($template <= 0) {
		die ("  ** Error couldn't load template file '$file_tpl'");
	}
	$pdf->IncludeTemplate ($template);
	$pdf->Image('img/DVM.jpg', 10, 5, 15, 15);

	$society = "DOUYA VOYAGE MARITIME";
	$society1 =	"D.V.M S.A";
	$society2 =	"Siège social - Libreville";
	$society3 =	"B.P : 14050 Libreville - Gabon - Email : douya.voyagemaritime@gmail.com";
	$society4 =	"Libreville  Tél : (+241) 07 52 56 05 - 04 18 67 36 -  06 03 29 85";
	$society5 =	"Port-Gentil Tél : (+241 ) 06 35 90 35 - 05 34 54 88 - 07 44 85 19";

	$pdf->ApplyTextProp("SOCIETY", utf8_decode($society));
	$pdf->ApplyTextProp("SOCIETY1", utf8_decode($society1));
	$pdf->ApplyTextProp("SOCIETY2", utf8_decode($society2));
	$pdf->ApplyTextProp("SOCIETY3", utf8_decode($society3));
	$pdf->ApplyTextProp("SOCIETY4", utf8_decode($society4));
	$pdf->ApplyTextProp("SOCIETY5", utf8_decode($society5));

	$pdf->ApplyTextProp("SHIP", utf8_decode("Nom : ".$btickets[0]->ship." - N° Immatriculation : ".$btickets[0]->refship));
	$pdf->ApplyTextProp("DEPART", utf8_decode("Date du : ".dol_print_date($btickets[0]->depart, 'day', 'tzuser')));
	$pdf->ApplyTextProp("TRAJET", utf8_decode("De : ".$btickets[0]->lieu_depart." à ".$btickets[0]->lieu_arrive));

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
	$py = $ptxp ['py'];
	$n = 0;		// Initial Y position for data rows
	for ($jj = 0; $jj < $nn; $jj ++) {
		$pdf->SetXY ($ptxp ['px'], $py);
		$n++;
		// Column interspace is 1
		$pdf->SetX ($pdf->GetX() + 1);
		// Last fill boolean parameter switches from false to true to achieve a "zebra" effect
		$pdf->Cell ($pcol [0], $ptxp ['iy'], $n , "", 0, "L", $jj & 1);
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
		$pdf->Cell ($pcol [3], $ptxp ['iy'], $btickets[$jj]->country, "", 0, "L", $jj & 1);
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

if($usercancreate && $type == 'sell'){

	$object = new Travel($db);
	$result_travel = $object->fetch($id);
	if ($result_travel <= 0) dol_print_error('', $object->error);


	// This sample program uses two distinct templates
	$file_tpl = DOL_DOCUMENT_ROOT.'/custom/bookticket/plugins/manifeste/template.tpl';

	// This sample program uses data fetched from a CSV file

	$btickets = [];
	$sql_t = 'SELECT DISTINCT t.rowid, t.ref, t.prix, p.nom as nom, p.prenom as prenom, tr.ref as travel, tr.lieu_depart as lieu_depart, tr.lieu_arrive as lieu_arrive, tr.jour as depart, s.label as ship, s.ref as refship, pn.prix_da, pn.prix_db, pn.prix_n, pn.prix_bp, pn.prix_c, pn.prix_ce, t.entity';
	$sql_t .= ' FROM '.MAIN_DB_PREFIX.'bookticket_bticket as t';
	$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_ship as s ON t.fk_ship = s.rowid";
	$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_passenger as p ON t.fk_passenger = p.rowid";
	$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_classe as c ON t.fk_classe = c.rowid";
	$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_travel as tr ON t.fk_travel = tr.rowid";
	$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_agence as a ON t.fk_agence = a.rowid";
	$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_penalite as pn ON t.rowid = pn.fk_bticket";
	$sql_t .= ' WHERE t.entity IN ('.getEntity('bticket').')';
	$sql_t .= ' AND t.fk_travel IN ('.$object->id.')';
	$sql_t .= " ORDER BY p.nom ASC";
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

	$pdf->AddPage("L");

	// Template #2 is used for the part which builds a table containing all employees
	$template = $pdf->LoadTemplate($file_tpl);
	if ($template <= 0) {
		die ("  ** Error couldn't load template file '$file_tpl'");
	}
	$pdf->IncludeTemplate ($template);
	$pdf->Image('img/DVM.jpg', 10, 5, 15, 15);

	$society = "DOUYA VOYAGE MARITIME";
	$society1 =	"D.V.M S.A";
	$society2 =	"Siège social - Libreville";
	$society3 =	"B.P : 14050 Libreville - Gabon - Email : douya.voyagemaritime@gmail.com";
	$society4 =	"Libreville  Tél : (+241) 07 52 56 05 - 04 18 67 36 -  06 03 29 85";
	$society5 =	"Port-Gentil Tél : (+241 ) 06 35 90 35 - 05 34 54 88 - 07 44 85 19";

	$pdf->ApplyTextProp("SOCIETY", utf8_decode($society));
	$pdf->ApplyTextProp("SOCIETY1", utf8_decode($society1));
	$pdf->ApplyTextProp("SOCIETY2", utf8_decode($society2));
	$pdf->ApplyTextProp("SOCIETY3", utf8_decode($society3));
	$pdf->ApplyTextProp("SOCIETY4", utf8_decode($society4));
	$pdf->ApplyTextProp("SOCIETY5", utf8_decode($society5));

	$pdf->ApplyTextProp("SHIP", utf8_decode("Nom : ".$btickets[0]->ship." - N° Immatriculation : ".$btickets[0]->refship));
	$pdf->ApplyTextProp("DEPART", utf8_decode("Date du : ".dol_print_date($btickets[0]->depart, 'day', 'tzuser')));
	$pdf->ApplyTextProp("TRAJET", utf8_decode("De : ".$btickets[0]->lieu_depart." à ".$btickets[0]->lieu_arrive));

	$pdf->ApplyTextProp ("FOOTRNB2", "1 / {nb}");   //  Add a footer with page number
	$pdf->ApplyTextProp ("TITLE", utf8_decode("Manifeste de Vente du Voyage N° ").$btickets[0]->travel);   //  Add a footer with page number
	$pdf->ApplyTextProp ("FOOTTITLE", utf8_decode("Manifeste de Vente du Voyage N° ").$btickets[0]->travel);   //  Add a footer with page number

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
	$pdf->Cell ($pcol [3], $ptxp ['iy'], "Prix", 1, 0, "C", true);
	$pdf->SetX ($pdf->GetX() + 1);
	$pdf->Cell ($pcol [4], $ptxp ['iy'], utf8_decode("Pénalité"), 1, 0, "C", true);
	$pdf->SetX ($pdf->GetX() + 1);
	$pdf->Cell ($pcol [5], $ptxp ['iy'], "Total", 1, 0, "C", true);


	$pdf->SetFillColor (240, 240, 240);		// for "zebra" effect
	// Get Text properties of data cell
	$ptxp = $pdf->ApplyTextProp ("ROW1COL0", "");
	$py = $ptxp ['py'];
	$n = 0;		// Initial Y position for data rows
	$offset = 0;
	$somme = 0;
	for ($jj = 0; $jj < $nn; $jj ++) {
		$pdf->SetXY ($ptxp ['px'], $py);
		$n++;
		$penalite = $btickets[$jj]->prix_da + $btickets[$jj]->prix_db + $btickets[$jj]->prix_c + $btickets[$jj]->prix_ce + $btickets[$jj]->prix_n + $btickets[$jj]->prix_bp;
		// Column interspace is 1
		$pdf->SetX ($pdf->GetX() + 1);
		// Last fill boolean parameter switches from false to true to achieve a "zebra" effect
		$pdf->Cell ($pcol [0], $ptxp ['iy'], $n , "", 0, "L", $jj & 1);
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
		$pdf->Cell ($pcol [3], $ptxp ['iy'], $btickets[$jj]->prix, "", 0, "L", $jj & 1);
		// Column interspace is 1
		$pdf->SetX ($pdf->GetX() + 1);
		// Last fill boolean parameter switches from false to true to achieve a "zebra" effect
		$pdf->Cell ($pcol [4], $ptxp ['iy'], $penalite.' XAF', "", 0, "L", $jj & 1);
		// Column interspace is 1
		$pdf->SetX ($pdf->GetX() + 1);
		// Last fill boolean parameter switches from false to true to achieve a "zebra" effect
		$pdf->Cell ($pcol [5], $ptxp ['iy'], $btickets[$jj]->prix+$penalite.' XAF', "", 0, "L", $jj & 1);
		// Column interspace is 1
		$pdf->SetX ($pdf->GetX() + 1);
		// Last fill boolean parameter switches from false to true to achieve a "zebra" effect
		$pdf->Cell ($pcol [6], $ptxp ['iy'], "", "", 0, "L", $jj & 1);
		$py += $ptxp ['iy'];		// for row interspace
		$somme += $btickets[$jj]->prix+$penalite;
		$offset = $jj;
	}

	$pdf->SetXY ($ptxp ['px'], $py);
	// Column interspace is 1
	$pdf->SetX ($pdf->GetX() + 1);
	// Last fill boolean parameter switches from false to true to achieve a "zebra" effect
	$pdf->Cell ($pcol [0], $ptxp ['iy'], "" , "", 0, "L", $offset & 1);
	// Column interspace is 1
	$pdf->SetX ($pdf->GetX() + 1);
	// Last fill boolean parameter switches from false to true to achieve a "zebra" effect
	$pdf->Cell ($pcol [1], $ptxp ['iy'], "", "", 0, "L", $offset & 1);
	// Column interspace is 1
	$pdf->SetX ($pdf->GetX() + 1);
	// Last fill boolean parameter switches from false to true to achieve a "zebra" effect
	$pdf->Cell ($pcol [2], $ptxp ['iy'], "", "", 0, "L", $offset & 1);
	// Column interspace is 1
	$pdf->SetX ($pdf->GetX() + 1);
	// Last fill boolean parameter switches from false to true to achieve a "zebra" effect
	$pdf->Cell ($pcol [3], $ptxp ['iy'], "", "", 0, "L", $offset & 1);
	// Column interspace is 1
	$pdf->SetX ($pdf->GetX() + 1);
	// Last fill boolean parameter switches from false to true to achieve a "zebra" effect
	$pdf->Cell ($pcol [4], $ptxp ['iy'], "", "", 0, "L", $offset & 1);
	// Column interspace is 1
	$pdf->SetX ($pdf->GetX() + 1);
	// Last fill boolean parameter switches from false to true to achieve a "zebra" effect
	$pdf->Cell ($pcol [5], $ptxp ['iy'], $somme.' XAF', "", 0, "L", $offset & 1);
	// Column interspace is 1
	$pdf->SetX ($pdf->GetX() + 1);
	// Last fill boolean parameter switches from false to true to achieve a "zebra" effect
	$pdf->Cell ($pcol [6], $ptxp ['iy'], "", "", 0, "L", $offset & 1);

	$py += $ptxp ['iy'];		// for row interspace

	$file_pdf = $btickets[0]->travel."_".date("dmYHis").".pdf";
	$pdf->Output("D", $file_pdf, true);
}
