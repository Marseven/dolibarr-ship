<?php
/* Copyright (C) 2021      Mebodo Aristide	<mebodoaristide@gmail.com>
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
 */

/**
 *	\file       bookticket/ticketindex.php
 *	\ingroup    bookticket
 *	\brief      Home page of ticket left menu
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
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/agence_caisse.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/agence_user.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/reservation.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/lib/bticket.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/modules_bticket.php';

require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/paymentvarious.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';

// Load translation files required by the page
$langs->loadLangs(array('bookticket', 'other'));

$mesg = ''; $error = 0; $errors = array();

$refalreadyexists = 0;

$id = GETPOST('id', 'int');
$travel = GETPOST('travel', 'int');
$reservation = GETPOST('reservation', 'int');
$ref = GETPOST('ref', 'alpha');
$action = (GETPOST('action', 'alpha') ? GETPOST('action', 'alpha') : 'view');
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$socid = GETPOST('socid', 'int');

if (!empty($user->socid)) $socid = $user->socid;

$object = new Bticket($db);
$object_passenger = new Passenger($db);
$object_travel = new Travel($db);
$object_reservation = new Reservation($db);

if ($id > 0 || !empty($ref))
{
	$result = $object->fetch($id, $ref);

	if (!empty($conf->bticket->enabled)) $upload_dir = $conf->bticket->multidir_output[$object->entity].'/'.get_exdir(0, 0, 0, 0, $object, 'bticket').dol_sanitizeFileName($object->ref);
	elseif (!empty($conf->service->enabled)) $upload_dir = $conf->service->multidir_output[$object->entity].'/'.get_exdir(0, 0, 0, 0, $object, 'bticket').dol_sanitizeFileName($object->ref);

	if (!empty($conf->global->BTICKET_USE_OLD_PATH_FOR_PHOTO))    // For backward compatiblity, we scan also old dirs
	{
		if (!empty($conf->bticket->enabled)) $upload_dirold = $conf->bticket->multidir_output[$object->entity].'/'.substr(substr("000".$object->id, -2), 1, 1).'/'.substr(substr("000".$object->id, -2), 0, 1).'/'.$object->id."/photos";
		else $upload_dirold = $conf->service->multidir_output[$object->entity].'/'.substr(substr("000".$object->id, -2), 1, 1).'/'.substr(substr("000".$object->id, -2), 0, 1).'/'.$object->id."/photos";
	}
}


// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$canvas = !empty($object->canvas) ? $object->canvas : GETPOST("canvas");
$objcanvas = null;
if (!empty($canvas))
{
	require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('bticket', 'card', $canvas);
}

// Security check
//$result = restrictedArea($user, 'bticket');

/*
 * Actions
 */

if ($cancel) $action = '';

$usercanread = $user->rights->bookticket->bticket->read;
$usercancreate = $user->rights->bookticket->bticket->write;
$usercandelete = $user->rights->bookticket->bticket->delete;

$parameters = array('id'=>$id, 'ref'=>$ref, 'objcanvas'=>$objcanvas);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

$classerecords = [];
$sql_classe = 'SELECT c.rowid, c.label, c.labelshort, c.entity,';
$sql_classe .= ' c.date_creation, c.tms as date_update';
$sql_classe .= ' FROM '.MAIN_DB_PREFIX.'bookticket_classe as c';
$sql_classe .= ' WHERE c.entity IN ('.getEntity('classe').')';
$sql_classe .= ' AND c.status = 2';
$resql_classe =$db->query($sql_classe);
if ($resql_classe)
{
	$num = $db->num_rows($resql_classe);
	$i = 0;
	if ($num)
	{
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql_classe);
			if ($obj)
			{
				$classerecords[$i] = $obj;
			}
			$i++;
		}
	}
}


$travelrecords = [];
$sql_travel = 'SELECT t.rowid, t.ref, t.jour, t.heure, t.lieu_depart, t.lieu_arrive, t.fk_ship, t.entity,';
$sql_travel .= ' t.date_creation, t.tms as date_update';
$sql_travel .= ' FROM '.MAIN_DB_PREFIX.'bookticket_travel as t';
$sql_travel .= ' WHERE t.entity IN ('.getEntity('travel').')';
$sql_travel .= ' AND t.status = 2';
$resql_travel =$db->query($sql_travel);
if ($resql_travel)
{
	$num = $db->num_rows($resql_travel);
	$i = 0;
	if ($num)
	{
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql_travel);
			if ($obj)
			{
				$travelrecords[$i] = $obj;
			}
			$i++;
		}
	}
}

$passengerrecords = [];
$sql_passenger = 'SELECT p.rowid, p.nom, p.prenom, p.entity,';
$sql_passenger .= ' p.date_creation, p.tms as date_update';
$sql_passenger .= ' FROM '.MAIN_DB_PREFIX.'bookticket_passenger as p';
$sql_passenger .= ' WHERE p.entity IN ('.getEntity('passenger').')';
$resql_passenger =$db->query($sql_passenger);
if ($resql_passenger)
{
	$num = $db->num_rows($resql_passenger);
	$i = 0;
	if ($num)
	{
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql_passenger);
			if ($obj)
			{
				$passengerrecords[$i] = $obj;
			}
			$i++;
		}
	}
}

// Add a bticket
if ($action == 'add' && $usercancreate)
{
	$error = 0;

	if (empty($ref))
	{
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Ref')), null, 'errors');
		$action = "create";
		$error++;
	}

	if (empty(GETPOST('nom')) || GETPOST('nom') == '')
	{
		setEventMessages($langs->trans('NomObligatoire', $langs->transnoentities('Ref')), null, 'errors');
		$action = "create";
		$error++;
	}

	if (empty(GETPOST('telephone')) || GETPOST('telephone') == '')
	{
		setEventMessages($langs->trans('TelephoneObligatoire', $langs->transnoentities('Ref')), null, 'errors');
		$action = "create";
		$error++;
	}

	if (empty(GETPOST('date_naissance')) || GETPOST('date_naissance') == '')
	{
		setEventMessages($langs->trans('DatenaissanceObligatoire', $langs->transnoentities('Ref')), null, 'errors');
		$action = "create";
		$error++;
	}

	$firstDate  = new DateTime(date('Y-m-d'));
	$secondDate = new DateTime(GETPOST('date_naissance'));
	$age = $firstDate->diff($secondDate);

	if($age->y >= 15 && $object->categorie == 'A'){
		die('1');
	}elseif(($age->y <= 5 && $age->y >= 0) && $object->categorie == 'B'){
		die('2');
	}elseif(($age->y < 15 && $age->y > 5) && $object->categorie == 'C'){
		die('3');
	}elseif(($age->y < 15 && $age->y > 5) && $object->categorie == 'D'){
		die('4');
	}else{

		$error++;
		$mesg = "L'âge du passager renseigné invalide pour la catégorie choisie.";
		var_dump($mesg);
		setEventMessages($mesg, null, 'errors');
		die();
		$action = "create";
	}

	if (!$error)
	{

		$object->ref   = $ref;

		$object->fk_travel             	 = GETPOST('fk_travel');
		$object_travel->fetch($object->fk_travel);
		$object->fk_ship             	 = $object_travel->fk_ship;
		$object->fk_classe             	 = GETPOST('fk_classe');
		$object->categorie             	 = GETPOST('categorie');

		$sql_a = 'SELECT DISTINCT au.rowid, au.fk_agence';
		$sql_a .= ' FROM '.MAIN_DB_PREFIX.'bookticket_agence_user as au';
		$sql_a .= ' WHERE au.fk_user IN ('.$user->id.')';
		$resql_a = $db->query($sql_a);
		$obj = $db->fetch_object($resql_a);

		$object->fk_agence = $obj->fk_agence;

		if(GETPOST('new_passenger') != 'on'){
			$object->fk_passenger = GETPOST('fk_passenger');
		}else{
			$object_passenger->ref             	 = GETPOST('pref');
			$object_passenger->civility          = GETPOST('civilite');
			$object_passenger->type_piece        = GETPOST('type_piece');
			$object_passenger->nom             	 = GETPOST('nom');
			$object_passenger->prenom            = GETPOST('prenom');
			$object_passenger->nationalite       = GETPOST('nationalite');
			$object_passenger->date_naissance    = GETPOST('date_naissance');
			$object_passenger->adresse           = GETPOST('adresse');
			$object_passenger->telephone         = GETPOST('telephone');
			$object_passenger->email             = GETPOST('email');
			$object_passenger->accompagne        = GETPOST('accompagne');
			$object_passenger->status = Passenger::STATUS_VALIDATED;


			$customer = new Societe($db);

			$customer->particulier	= 1;
			$customer->status	= 1;

			$customer->name = dolGetFirstLastname(GETPOST('prenom', 'alphanohtml'), GETPOST('nom', 'alphanohtml'));
			$customer->civility_id	= GETPOST('civilite', 'alphanohtml'); // Note: civility id is a code, not an int

			// Add non official properties
			$customer->name_bis = GETPOST('nom', 'alphanohtml');
			$customer->firstname = GETPOST('prenom', 'alphanohtml');

			$customer->entity	  = (GETPOSTISSET('entity') ? GETPOST('entity', 'int') : $conf->entity);
			$customer->address	  = GETPOST('adresse', 'alphanohtml');
			$customer->country_id = GETPOST('nationalite', 'int');

			$customer->phone = GETPOST('telephone', 'alpha');
			$customer->email = trim(GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL));
			$customer->code_client	=GETPOST('pref');
			$customer->typent_code	= 'TE_PRIVATE';

			$customer->client = '';
			$customer->commercial_id = $user->id;

			if (empty($customer->client))      $customer->code_client = '';

			$result = $customer->create($user);

			$object_passenger->fk_socid = $result;

			$id_passenger = $object_passenger->create($user);

			$object->fk_passenger = $id_passenger;
		}

		$sql_prix = 'SELECT c.rowid, c.labelshort, c.prix_standard, c.prix_enf_por, c.prix_enf_acc,c.prix_enf_dvm, c.entity,';
		$sql_prix .= ' c.date_creation, c.tms as date_update';
		$sql_prix .= ' FROM '.MAIN_DB_PREFIX.'bookticket_classe as c';
		$sql_prix .= ' WHERE c.entity IN ('.getEntity('classe').')';
		$sql_prix .= ' AND c.rowid IN ('.$object->fk_classe.')';
		$resql_prix =$db->query($sql_prix);
		$obj_prix = $db->fetch_object($resql_prix);

		$firstDate  = new DateTime(date('Y-m-d'));
		$secondDate = new DateTime(GETPOST('date_naissance'));
		$age = $firstDate->diff($secondDate);


		if($object->categorie == 'A'){
			$object->prix = $obj_prix->prix_standard;
		}elseif($object->categorie == 'B'){
			$object->prix = $obj_prix->prix_enf_por;
		}elseif($object->categorie == 'C'){
			$object->prix = $obj_prix->prix_enf_acc;
		}elseif($object->categorie == 'D'){
			$object->prix = $obj_prix->prix_enf_dvm;
		}

		/*if(GETPOST('accompagne') == 'on'){

			if($age->y >= 15){
				$error++;
				$mesg = "L'âge du passager renseigné est supérieur au maximum requis pour être accompagné.";
				setEventMessages($mesg.$stdobject->error, $mesg.$stdobject->errors, 'errors');
			}elseif($object->categorie == 'D'){
				$error++;
				$mesg = "La catégorie choisie ne correspond pas. Un billet pour enfant confié à DVM ne peut pas avoir d'accompagnateur.";
				setEventMessages($mesg.$stdobject->error, $mesg.$stdobject->errors, 'errors');
			}else{
				$object->fk_passenger_acc = GETPOST('fk_passenger_acc');
			}
		}*/

		$object->status = Bticket::STATUS_APPROVED;

		if (!$error)
		{
			$id = $object->create($user);

			if($reservation){
				$object_reservation->fetch($reservation);

				if($obj_prix->labelshort == "VIP"){
					$object_reservation->nbre_vip--;
				}elseif($obj_prix->labelshort == "ECO"){
					$object_reservation->nbre_eco--;
				}else{
					$object_reservation->nbre_aff--;
				}

				$object_reservation->nbre_place--;

				if($object_reservation->nbre_place == 0 && $object_reservation->status == Reservation::STATUS_APPROVED){
					$object_reservation->status = Reservation::STATUS_LOCK;
				}

				$object_reservation->update($user);
			}else{
				$object_travel->fetch($object->fk_travel);

				if($obj_prix->labelshort == "VIP"){
					$object_travel->nbre_vip--;
				}elseif($obj_prix->labelshort == "ECO"){
					$object_travel->nbre_eco--;
				}else{
					$object_travel->nbre_aff--;
				}

				$object_travel->nbre_place--;

				if($object_travel->nbre_place == 0 && $object_travel->status == Travel::STATUS_APPROVED){
					$object_travel->status = Travel::STATUS_LOCK;
				}

				$object_travel->update($user);
			}


			$object_payment = new PaymentVarious($db);

			$sql_ac = 'SELECT ac.rowid, ac.fk_caisse';
			$sql_ac .= ' FROM '.MAIN_DB_PREFIX.'bookticket_agence_caisse as ac';
			$sql_ac .= ' WHERE ac.fk_agence IN ('.$object->fk_agence.')';
			$resql_ac = $db->query($sql_ac);
			$object_caisse = $db->fetch_object($resql_ac);

			$datep = dol_mktime(12, 0, 0, date('m'), date('d'), date('Y'));
			$datev = dol_mktime(12, 0, 0, date('m'), date('d'), date('Y'));

			if (empty($datev)) $datev = $datep;

			$object_payment->ref = '';
			$object_payment->fk_account = $object_caisse->fk_caisse;
			$object_payment->accountid = $object_caisse->fk_caisse;
			$object_payment->datev = $datev;
			$object_payment->datep = $datep;
			$object_payment->amount = price2num($object->prix);
			$object_payment->label = 'Vente du Billet N° '.$object->ref;
			$object_payment->type_payment = dol_getIdFromCode($db, 'LIQ', 'c_paiement', 'code', 'id', 1);
			$object_payment->fk_user_author = $user->id;
			$object_payment->sens = 1;
			$object_payment->accountancy_code = "DVM-BL";
			$object_payment->subledger_account = $object->ref;
			$db->begin();
			$ret = $object_payment->create($user);
			$db->commit();
		}

		if ($id > 0)
		{
			if (!empty($backtopage))
			{
				$backtopage = preg_replace('/--IDFORBACKTOPAGE--/', $object->id, $backtopage); // New method to autoselect project after a New on another form object creation
				if (preg_match('/\?/', $backtopage)) $backtopage .= '&socid='.$object->id; // Old method
				header("Location: ".$backtopage."?action=create&travel=".$travel);
				exit;
			} else {
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
				exit;
			}
		} else {
			if (count($object->errors)) setEventMessages($object->error, $object->errors, 'errors');
			else setEventMessages($langs->trans($object->error), null, 'errors');
			$action = "create";
		}
	}
}

// Update a bticket
if ($action == 'update' && $usercancreate)
{
	if (GETPOST('cancel', 'alpha'))
	{
		$action = '';
	} else {
		if ($object->id > 0)
		{
			$object->oldcopy = clone $object;

			$object->ref                    = $ref;

			$firstDate  = new DateTime(date('Y-m-d'));
			$secondDate = new DateTime(GETPOST('date_naissance'));
			$age = $firstDate->diff($secondDate);

			if($age->y >= 15 && $object->categorie == 'A'){

			}elseif(($age->y <= 5 && $age->y >= 0) && $object->categorie == 'B'){

			}elseif(($age->y < 15 && $age->y > 5) && $object->categorie == 'C'){

			}elseif(($age->y < 15 && $age->y > 5) && $object->categorie == 'D'){

			}else{
				$error++;
				$mesg = "L'âge du passager renseigné invalide pour la catégorie choisie.";
				setEventMessages($mesg, null, 'errors');
				$action = "create";
			}

			if (empty(GETPOST('nom')) || GETPOST('nom') == '')
			{
				setEventMessages($langs->trans('NomObligatoire', $langs->transnoentities('Ref')), null, 'errors');
				$action = "create";
				$error++;
			}

			if (empty(GETPOST('telephone')) || GETPOST('telephone') == '')
			{
				setEventMessages($langs->trans('TelephoneObligatoire', $langs->transnoentities('Ref')), null, 'errors');
				$action = "create";
				$error++;
			}

			if (empty(GETPOST('date_naissance')) || GETPOST('date_naissance') == '')
			{
				setEventMessages($langs->trans('DatenaissanceObligatoire', $langs->transnoentities('Ref')), null, 'errors');
				$action = "create";
				$error++;
			}


			$object->fk_travel             	 = GETPOST('fk_travel');
			$object_travel->fetch($object->fk_travel);
			$object->fk_ship             	 = $object_travel->fk_ship;
			$object->fk_classe             	 = GETPOST('fk_classe');

			$object->categorie             	 = GETPOST('categorie');

			$object->fk_passenger             	 = GETPOST('fk_passenger');

			$object_passenger->fetch($object->fk_passenger);

			$object_passenger->ref             	 = GETPOST('pref');
			$object_passenger->civility          = GETPOST('civilite');
			$object_passenger->nom             	 = GETPOST('nom');
			$object_passenger->prenom            = GETPOST('prenom');
			$object_passenger->nationalite       = GETPOST('nationalite');
			$object_passenger->date_naissance    = GETPOST('date_naissance');
			$object_passenger->adresse           = GETPOST('adresse');
			$object_passenger->telephone         = GETPOST('telephone');
			$object_passenger->email             = GETPOST('email');
			$object_passenger->accompagne        = GETPOST('accompagne');
			$object_passenger->status 			 = Passenger::STATUS_VALIDATED;

			$sql_prix = 'SELECT c.rowid, c.labelshort, c.prix_standard, c.prix_enf_por, c.prix_enf_acc,c.prix_enf_dvm, c.entity,';
			$sql_prix .= ' c.date_creation, c.tms as date_update';
			$sql_prix .= ' FROM '.MAIN_DB_PREFIX.'bookticket_classe as c';
			$sql_prix .= ' WHERE c.entity IN ('.getEntity('classe').')';
			$sql_prix .= ' AND c.rowid IN ('.$object->fk_classe.')';
			$resql_prix =$db->query($sql_prix);
			$obj_prix = $db->fetch_object($resql_prix);

			$firstDate  = new DateTime(date('Y-m-d'));
			$secondDate = new DateTime(GETPOST('date_naissance'));
			$age = $firstDate->diff($secondDate);

			if($object->categorie == 'A'){
				$object->prix = $obj_prix->prix_standard;
			}elseif($object->categorie == 'B'){
				$object->prix = $obj_prix->prix_enf_por;
			}elseif($object->categorie == 'C'){
				$object->prix = $obj_prix->prix_enf_acc;
			}elseif($object->categorie == 'D'){
				$object->prix = $obj_prix->prix_enf_dvm;
			}

			/*if(GETPOST('accompagne') == 'on'){
				if($age->y >= 15){
					$error++;
					$mesg = "L'âge du passager renseigné est supérieur au maximum requis.";
					setEventMessages($mesg.$stdobject->error, $mesg.$stdobject->errors, 'errors');
				}elseif($object->categorie == 'D'){
					$error++;
					$mesg = "La catégorie choisie ne correspond pas. Un billet pour enfant confié à DVM ne peut pas avoir d'accompagnateur.";
					setEventMessages($mesg.$stdobject->error, $mesg.$stdobject->errors, 'errors');
				}else{
					$object->fk_passenger_acc = GETPOST('fk_passenger_acc');
				}
			}*/

			$object->fk_valideur = $user->fk_user;

			$object->status = Bticket::STATUS_VALIDATED;

			if (!$error && $object->check())
			{
				if ($object->update($user) > 0 && $object_passenger->update($user) > 0)
				{
					$action = 'view';
				} else {
					if (count($object->errors)) setEventMessages($object->error, $object->errors, 'errors');
					else setEventMessages($langs->trans($object->error), null, 'errors');
					$action = 'edit';
				}
			} else {
				if (count($object->errors)) setEventMessages($object->error, $object->errors, 'errors');
				else setEventMessages($langs->trans("ErrorFieldRequired"), null, 'errors');
				$action = 'edit';
			}
		}
	}
}

//Approve
if($action == 'valid' && $usercancreate){

	$object->fetch($id);

	// If status is waiting approval and approver is also user
	if ($object->status == Bticket::STATUS_VALIDATED && $user->id == $object->fk_valideur)
	{
		$object->status = Bticket::STATUS_APPROVED;

		$db->begin();

		$verif = $object->approve($user);
		if ($verif <= 0)
		{
			setEventMessages($object->error, $object->errors, 'errors');
			$error++;
		}

		if (!$error)
		{
			$db->commit();

			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		} else {
			$db->rollback();
			$action = '';
		}
	}
}

//Refuse
if($action == 'refuse' && $usercancreate){

	$object->fetch($id);

	// If status is waiting refuse and refuser is also user
	if ($object->status == Bticket::STATUS_VALIDATED && $user->id == $object->fk_valideur)
	{
		$object->status = Bticket::STATUS_REFUSED;

		$db->begin();

		$verif = $object->refuse($user);
		if ($verif <= 0)
		{
			setEventMessages($object->error, $object->errors, 'errors');
			$error++;
		}

		if (!$error)
		{
			$db->commit();

			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		} else {
			$db->rollback();
			$action = '';
		}
	}
}

// Delete a bticket
//if ($action == 'confirm_delete' && $confirm != 'yes') { $action = ''; }
if ($action == 'delete' && $usercandelete)
{
	$result = $object->delete($user);

	if ($result > 0)
	{
		header('Location: '.DOL_URL_ROOT.'/custom/bookticket/bticket_list.php?type='.$object->type.'&delbticket='.urlencode($object->ref));
		exit;
	} else {
		setEventMessages($langs->trans($object->error), null, 'errors');
		$reload = 0;
		$action = '';
	}
}

// Add bticket into object
if ($object->id > 0 && $action == 'addin')
{
	$thirpdartyid = 0;
}

/*
 * View
 */

$title = $langs->trans('bticketCard');
$helpurl = '';
$shortlabel = dol_trunc($object->label, 16);
$title = $langs->trans('bticket')." ".$shortlabel." - ".$langs->trans('Card');
$helpurl = 'EN:Module_Ticket|FR:Module_Ticket|ES:M&oacute;dulo_Ticket';

llxHeader('', $title, $helpurl);

$form = new Form($db);
$formfile = new FormFile($db);
$formcompany = new FormCompany($db);

if (is_object($objcanvas) && $objcanvas->displayCanvasExists($action)) {
	// -----------------------------------------
	// When used with CANVAS
	// -----------------------------------------
	if (empty($object->error) && $id)
	{
		$object = new Bticket($db);
		$result = $object->fetch($id);
		if ($result <= 0) dol_print_error('', $object->error);
	}
	$objcanvas->assign_values($action, $object->id, $object->ref); // Set value for templates
	$objcanvas->display_canvas($action); // Show template
} else {
	// -----------------------------------------
	// When used in standard mode
	// -----------------------------------------
	if ($action == 'create' && $usercancreate) {
		//WYSIWYG Editor
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

		print '<script type="text/javascript">';
				print '$(document).ready(function () {
                        $("#selectcountry_id").change(function() {
                        	document.formprod.action.value="create";
                        	document.formprod.submit();
                        });
                     });';
				print '</script>'."\n";

		dol_set_focus('input[name="ref"]');

		print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" name="formprod">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="type" value="'.$type.'">'."\n";
		if (!empty($modCodeBticket->code_auto))
			print '<input type="hidden" name="code_auto" value="1">';
		if (!empty($modBarCodeBticket->code_auto))
			print '<input type="hidden" name="barcode_auto" value="1">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';


		$picto = 'bticket';
		$title = $langs->trans("NewBTicket");

		$linkback = "";
		print load_fiche_titre($title, $linkback, $picto);

		print dol_get_fiche_head('');

		print '<table class="border centpercent">';

			print '<tr>';
			$number = "0123456789";
			$code = substr(str_shuffle(str_repeat($number, 6)), 0, 6);
			$tmpref = "DVM-BL-".$code;
			print '<td colspan="3"><input id="ref" type="hidden" name="ref" class="maxwidth200" maxlength="128" value="'.dol_escape_htmltag(GETPOSTISSET('ref') ? GETPOST('ref', 'alphanohtml') : $tmpref).'">';
			if ($refalreadyexists)
			{
				print $langs->trans("RefAlreadyExists");
			}
			print '</td></tr>';

			//travel
			print '<tr><input type="hidden" name="fk_travel" value="'.$travel.'"></tr>';


			// categorie
			print '<tr><td class="titlefieldcreate">'.$langs->trans("CatégorieBillet").'</td>';

			print '<td><select class="flat" name="categorie">';
			print '<option value="A">'.($langs->trans("BilletNormal")).'</option>';
			print '<option value="B">'.($langs->trans("BilletEnfantBasAge")).'</option>';
			print '<option value="C">'.($langs->trans("BilletMineur")).'</option>';
			print '<option value="D">'.($langs->trans("BilletMineurConfie")).'</option>';
			print '</select>';

			print '</td></tr>';

			// classe
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Classe").'</td>';

			if($reservation){
				$classe = '<td><select class="flat" name="fk_classe">';
				if (empty($classerecords))
				{
					$classe .= '<option value="0">'.($langs->trans("AucuneEntree")).'</option>';
				}else{
					$object_reservation->fetch($reservation);
					foreach ($classerecords as $lines)
					{
						if($lines->labelshort == "VIP" && $object_reservation->nbre_vip > 0){
							$classe .= '<option value="';
							$classe .= $lines->rowid;
							$classe .= '"';
							$classe .= '>';
							$classe .= $langs->trans($lines->label);
							$classe .= '</option>';
						}elseif($lines->labelshort == "ECO" && $object_reservation->nbre_eco > 0){
							$classe .= '<option value="';
							$classe .= $lines->rowid;
							$classe .= '"';
							$classe .= '>';
							$classe .= $langs->trans($lines->label);
							$classe .= '</option>';
						}elseif($lines->labelshort == "AFF" && $object_reservation->nbre_aff > 0){
							$classe .= '<option value="';
							$classe .= $lines->rowid;
							$classe .= '"';
							$classe .= '>';
							$classe .= $langs->trans($lines->label);
							$classe .= '</option>';
						}

					}
				}
			}else{
				$classe = '<td><select class="flat" name="fk_classe">';
				if (empty($classerecords))
				{
					$classe .= '<option value="0">'.($langs->trans("AucuneEntree")).'</option>';
				}else{
					$object_travel->fetch($travel);
					foreach ($classerecords as $lines)
					{
						if($lines->labelshort == "VIP" && $object_travel->nbre_vip > 0){
							$classe .= '<option value="';
							$classe .= $lines->rowid;
							$classe .= '"';
							$classe .= '>';
							$classe .= $langs->trans($lines->label);
							$classe .= '</option>';
						}elseif($lines->labelshort == "ECO" && $object_travel->nbre_eco > 0){
							$classe .= '<option value="';
							$classe .= $lines->rowid;
							$classe .= '"';
							$classe .= '>';
							$classe .= $langs->trans($lines->label);
							$classe .= '</option>';
						}elseif($lines->labelshort == "AFF" && $object_travel->nbre_aff > 0){
							$classe .= '<option value="';
							$classe .= $lines->rowid;
							$classe .= '"';
							$classe .= '>';
							$classe .= $langs->trans($lines->label);
							$classe .= '</option>';
						}

					}
				}
			}


			$classe .= '</select>';

			print $classe;

			print '</td></tr>';

		print '</table>';

		print '<hr>';

		print '<table class="border centpercent">';

			print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("InformationPassager").'</td></tr>';

			// passenger
			if (empty($conf->global->MAIN_USE_JQUERY_MULTISELECT) && !defined('REQUIRE_JQUERY_MULTISELECT')) return '';

			print '<tr><td class="titlefieldcreate">'.$langs->trans("Passenger").'</td>';

			$passenger = '<td><select class="fk_passenger flat" name="fk_passenger" id="fk_passenger">';
			if (empty($passengerrecords))
			{
				$passenger .= '<option value="0">'.($langs->trans("AucuneEntree")).'</option>';
			}else{
				foreach ($passengerrecords as $lines)
				{
					$passenger .= '<option value="';
					$passenger .= $lines->rowid;
					$passenger .= '"';
					$passenger .= '>';
					$passenger .= $langs->trans($lines->nom).' '. $langs->trans($lines->prenom);
					$passenger .= '</option>';
				}
			}

			$passenger .= '</select>';

			print $passenger;

			print '</td></tr>';

			print "<script>
						$(document).ready(function() {
							$('.fk_passenger').select2();
						});
				</script>";
			// new_passenger
			print '<tr><td class="titlefieldcreate">'.$langs->trans("NewPassenger").'</td>';
			print '<td><input type="checkbox" name="new_passenger" >';
			print '</td></tr>';

			// type de piece
			print '<tr><td class="titlefieldcreate">'.$langs->trans("TypePiece").'</td>';

			$piece = '<td><select class="flat" name="type_piece">';
			$piece .= '<option value="Aucun">'.($langs->trans("Aucun")).'</option>';
			$piece .= '<option value="CNI">'.($langs->trans("CNI")).'</option>';
			$piece .= '<option value="Passeport">'.($langs->trans("Passeport")).'</option>';
			$piece .= '<option value="Carte de Séjour">'.($langs->trans("CarteSéjour")).'</option>';
			$piece .= '<option value="Permis de Conduire">'.($langs->trans("PermisConduire")).'</option>';
			$piece .= '</select>';

			print $piece;

			print '</td></tr>';

			// Ref
			print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("PieceIdentite").'</td><td colspan="3"><input name="pref" class="maxwidth200" maxlength="128" value="'.dol_escape_htmltag($object_passenger->ref).'"></td></tr>';

			// civilite
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Civilite").'</td>';

			$civilite = '<td><select class="flat" name="civilite">';
			$civilite .= '<option value="MR">'.($langs->trans("MR")).'</option>';
			$civilite .= '<option value="MME">'.($langs->trans("MME")).'</option>';
			$civilite .= '</select>';

			print $civilite;

			print '</td></tr>';

			// nom
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Nom").'</td>';
			print '<td><input name="nom" class="maxwidth300" value="'.$object_passenger->nom.'">';
			print '</td></tr>';

			// prenom
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Prenom").'</td>';
			print '<td><input name="prenom" class="maxwidth300" value="'.$object_passenger->prenom.'">';
			print '</td></tr>';

			// nationalite
			print '<tr><td>'.$form->editfieldkey('Country', 'selectnationalite', '', $object, 0).'</td><td colspan="3" class="maxwidthonsmartphone">';
			print img_picto('', 'globe-americas', 'class="paddingrightonly"');
			print $form->select_country((GETPOSTISSET('nationalite') ? 16 : $object->nationalite), 'nationalite', '', 0, 'minwidth100 maxwidth150 widthcentpercentminusx');
			print '</td></tr>';

			// age
			print '<tr><td class="titlefieldcreate">'.$langs->trans("DateNaissance").'</td>';
			print '<td><input name="date_naissance" type="date" class="maxwidth300" value="'.$object_passenger->date_naissance.'">';
			print '</td></tr>';

			// adresse
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Adresse").'</td>';
			print '<td><input name="adresse" class="maxwidth300" value="'.$object_passenger->adresse.'">';
			print '</td></tr>';

			// telephone
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Telephone").'</td>';
			print '<td><input name="telephone" class="maxwidth300" value="'.$object_passenger->telephone.'">';
			print '</td></tr>';

			// email
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Email").'</td>';
			print '<td><input name="email" type="email" class="maxwidth300" value="'.$object_passenger->email.'">';
			print '</td></tr>';

			/* accompagne
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Accompagne").'</td>';
			print '<td><input type="checkbox" name="accompagne"'.($object_passenger->accompagne == 1 ? 'checked="checked"' : '').'>';
			print '</td></tr>';

			// passenger
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Par").'</td>';
			$passenger = '<td><select class="fk_passenger flat" name="fk_passenger_acc" id="fk_passenger_acc">';
			if (empty($passengerrecords))
			{
				$passenger .= '<option value="0">'.($langs->trans("AucuneEntree")).'</option>';
			}else{
				foreach ($passengerrecords as $lines)
				{
					$passenger .= '<option value="';
					$passenger .= $lines->rowid;
					$passenger .= '"';
					$passenger .= '>';
					$passenger .= $langs->trans($lines->nom).' '. $langs->trans($lines->prenom);
					$passenger .= '</option>';
				}
			}

			$passenger .= '</select>';

			print $passenger;

			print '</td></tr>';*/

			print "<script>
						$(document).ready(function() {
							$('.fk_passenger').select2();
						});
				</script>";


		print '</table>';

		print dol_get_fiche_end();

		print '<div class="center">';
		print '<input type="submit" class="button" value="'.$langs->trans("Create").'">';
		print ' &nbsp; &nbsp; ';
		print '<input type="button" class="button button-cancel" value="'.$langs->trans("Cancel").'" onClick="javascript:history.go(-1)">';
		print '</div>';

		print '</form>';
	} elseif ($object->id > 0) {
		/*
         * bticket card
         */

		$date = dol_print_date($obj->date_creation, 'day', 'tzuser');
		$date1 = new DateTime($obj->date_creation);
		$date1 = $date1->modify("+ 3 months");
		$expire = $date1->format('d/m/Y');
		$today = date('d/m/Y');

		// Fiche en mode edition
		if ($action == 'edit' && $usercancreate)
		{
			$sql_p = 'SELECT DISTINCT p.rowid, p.ref, p.type_piece, p.civility, p.nom, p.prenom, p.date_naissance, p.adresse,  p.telephone, p.email, p.accompagne, p.entity';
			$sql_p .= ' FROM '.MAIN_DB_PREFIX.'bookticket_passenger as p';
			$sql_p .= ' WHERE p.entity IN ('.getEntity('passenger').')';
			$sql_p .= ' AND p.rowid IN ('.$object->fk_passenger.')';
			$resql_p = $db->query($sql_p);
			$obj_p = $db->fetch_object($resql_p);

			//var_dump($object); die;

			//WYSIWYG Editor
			require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

			print '<script type="text/javascript">';
				print '$(document).ready(function () {
                        $("#selectcountry_id").change(function () {
                        	document.formprod.action.value="edit";
                        	document.formprod.submit();
                        });
				});';
				print '</script>'."\n";


			$type = $langs->trans('Ticket');

			// Main official, simple, and not duplicated code
			print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="POST" name="formprod">'."\n";
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
			print '<input type="hidden" name="canvas" value="'.$object->canvas.'">';

			$head = bticket_prepare_head($object);
			$titre = $langs->trans("CardTicket");
			$picto =  'bticket';

			print dol_get_fiche_head($head, 'card', $titre, 0, $picto);


			print '<table class="border allwidth">';

			// Ref
			print '<tr><td colspan="3"><input name="ref" type="hidden" class="maxwidth200" maxlength="128" value="'.dol_escape_htmltag($object->ref).'"></td></tr>';

			//travel
			print '<tr><input type="hidden" name="fk_travel" value="'.$object->fk_travel.'"></tr>';

			// categorie
			print '<tr><td class="titlefieldcreate">'.$langs->trans("CategorieBillet").'</td>';

			print '<td><select class="flat" name="categorie">';
			$passenger .= '<option value="0">'.($langs->trans("AucuneEntree")).'</option>';
			print '<option value="A">'.($langs->trans("BilletNormal")).'</option>';
			print '<option value="B">'.($langs->trans("BilletEnfantBasAge")).'</option>';
			print '<option value="C">'.($langs->trans("BilletMineur")).'</option>';
			print '<option value="D">'.($langs->trans("BilletMineurConfie")).'</option>';
			print '</select>';

			print '</td></tr>';

			// classe
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Classe").'</td>';

			$classe = '<td><select class="flat" name="fk_classe">';
			if (empty($classerecords))
			{
				$classe .= '<option value="0">'.($langs->trans("AucuneEntree")).'</option>';
			}else{
				foreach ($classerecords as $lines)
				{
					$classe .= '<option value="';
					$classe .= $lines->rowid;
					$classe .= '"';
					$classe .= '>';
					$classe .= $langs->trans($lines->label);
					$classe .= '</option>';
				}
			}

			$classe .= '</select>';

			print $classe;

			print '</td></tr>';

			print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("InformationPassager").'</td></tr>';

			print '<tr><td class="titlefieldcreate">'.$langs->trans("TypePiece").'</td>';

			$piece = '<td><select class="flat" name="type_piece">';
			$piece .= '<option value="'.$obj_p->type_piece.'">'.($langs->trans($obj_p->type_piece)).'</option>';
			$piece .= '<option value="Aucun">'.($langs->trans("Aucun")).'</option>';
			$piece .= '<option value="CNI">'.($langs->trans("CNI")).'</option>';
			$piece .= '<option value="Passeport">'.($langs->trans("Passeport")).'</option>';
			$piece .= '<option value="Carte de Séjour">'.($langs->trans("CarteSéjour")).'</option>';
			$piece .= '<option value="Permis de Conduire">'.($langs->trans("PermisConduire")).'</option>';
			$piece .= '</select>';

			print $piece;

			print '</td></tr>';

			// Ref
			print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("PieceIdentite").'</td><td colspan="3"><input name="pref" class="maxwidth200" maxlength="128" value="'.dol_escape_htmltag($obj_p->ref).'"></td></tr>';

			// civilite
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Civilite").'</td>';

			$civilite = '<td><select class="flat" name="civilite">';
			$civilite .= '<option value="'.$obj_p->civility.'">'.($langs->trans($obj_p->civility)).'</option>';
			$civilite .= '<option value="MR">'.($langs->trans("MR")).'</option>';
			$civilite .= '<option value="MME">'.($langs->trans("MME")).'</option>';
			$civilite .= '</select>';

			print $civilite;

			print '</td></tr>';

			// nom
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Nom").'</td>';
			print '<td><input name="nom" class="maxwidth300" value="'.$obj_p->nom.'">';
			print '</td></tr>';

			// prenom
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Prenom").'</td>';
			print '<td><input name="prenom" class="maxwidth300" value="'.$obj_p->prenom.'">';
			print '</td></tr>';

			// nationalite
			print '<tr><td>'.$form->editfieldkey('Country', 'selectnationalite', '', $object, 0).'</td><td colspan="3" class="maxwidthonsmartphone">';
			print img_picto('', 'globe-americas', 'class="paddingrightonly"');
			print $form->select_country($object->nationalite, 'nationalite', '', 0, 'minwidth100 maxwidth150 widthcentpercentminusx');
			print '</td></tr>';

			// age
			print '<tr><td class="titlefieldcreate">'.$langs->trans("DateNaissance").'</td>';
			print '<td><input name="date_naissance" type="date" class="maxwidth300" value="'.$obj_p->date_naissance.'" >';
			print '</td></tr>';

			// adresse
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Adresse").'</td>';
			print '<td><input name="adresse" class="maxwidth300" value="'.$obj_p->adresse.'">';
			print '</td></tr>';

			// telephone
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Telephone").'</td>';
			print '<td><input name="telephone" class="maxwidth300" value="'.$obj_p->telephone.'" >';
			print '</td></tr>';

			// email
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Email").'</td>';
			print '<td><input name="email" type="email" class="maxwidth300" value="'.$obj_p->email.'">';
			print '</td></tr>';

			/* accompagne
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Accompagne").'</td>';
			print '<td><input type="checkbox" name="accompagne"'.($obj_p->accompagne == "on" ? 'checked' : '').'>';
			print '</td></tr>';

			// passenger
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Par").'</td>';
			$passenger = '<td><select class="fk_passenger flat" name="fk_passenger_acc" id="fk_passenger_acc">';
			if (empty($passengerrecords))
			{
				$passenger .= '<option value="0">'.($langs->trans("AucuneEntree")).'</option>';
			}else{
				foreach ($passengerrecords as $lines)
				{
					$passenger .= '<option value="';
					$passenger .= $lines->rowid;
					$passenger .= '"';
					$passenger .= '>';
					$passenger .= $langs->trans($lines->nom).' '. $langs->trans($lines->prenom);
					$passenger .= '</option>';
				}
			}

			$passenger .= '</select>';

			print $passenger;

			print '</td></tr>';

			print "<script>
					$(document).ready(function() {
						$('.fk_passenger').select2();
					});
			</script>";

			// fk_passenger
			print '<td><input name="fk_passenger" type="hidden" value="'.$object->fk_passenger.'">';*/

			print '</table>';

			print '<br>';

			print dol_get_fiche_end();

			print '<div class="center">';
			print '<input type="submit" class="button button-save" value="'.$langs->trans("Save").'">';
			print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
			print '</div>';

			print '</form>';
		} else {
			// Fiche en mode visu

			$sql_t = 'SELECT DISTINCT t.rowid, t.ref, t.categorie, s.label as ship, p.nom as nom, p.prenom as prenom, c.label as classe, t.prix, tr.ref as travel, a.label as agence, t.entity, p.accompagne, t.fk_passenger_acc';
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
			if($obj->accompagne == 'on'){
				$object_passenger->fetch($obj->fk_passenger_acc);
			}


			$head = bticket_prepare_head($object);
			$titre = $langs->trans("CardTicket");
			$picto = 'bticket';

			print dol_get_fiche_head($head, 'card', $titre, -1, $picto);

			$linkback = '<a href="'.DOL_URL_ROOT.'/custom/bookticket/bticket_list.php?restore_lastsearch_values=1&type=">'.$langs->trans("BackToList").'</a>';

			$shownav = 1;
			if ($user->socid && !in_array('bticket', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav = 0;

			dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref');

			print '<div class="fichecenter">';
			print '<div class="fichehalfleft">';
			print '<div class="underbanner clearboth"></div>';

			print '<table class="border tableforfield centpercent">';
			print '<tbody>';

			// Ref
			print '<tr>';
			print '<td class="titlefield">'.$langs->trans("Ref").'</td>';
			print '<td>';
			print $obj->ref;
			print '</td></tr>';

			// Passenger
			print '<tr>';
			print '<td class="titlefield">'.$langs->trans("Passenger").'</td>';
			print '<td>';
			print $obj->nom.' '.$obj->prenom;
			print '</td></tr>';

			// categorie
			print '<tr>';
			print '<td class="titlefield">'.$langs->trans("CategorieBillet").'</td>';
			print '<td>';
			print $obj->categorie;
			print '</td></tr>';



			// Travel
			print '<tr>';
			print '<td>';
			$htmlhelp = $langs->trans('TravelHelp');
			print $form->textwithpicto($langs->trans('Travel'), $htmlhelp);
			print '</td>';
			print '<td>';
			print $obj->travel;
			print '</td>';
			print '</tr>';

			// Ship
			print '<tr>';
			print '<td>';
			$htmlhelp = $langs->trans('ShipHelp');
			print $form->textwithpicto($langs->trans('Ship'), $htmlhelp);
			print '</td>';
			print '<td>';
			print $obj->ship;
			print '</td>';
			print '</tr>';

			// Classe
			print '<tr>';
			print '<td>';
			$htmlhelp = $langs->trans('ClasseHelp');
			print $form->textwithpicto($langs->trans('Classe'), $htmlhelp);
			print '</td>';
			print '<td>';
			print $obj->classe;
			print '</td>';
			print '</tr>';

			// Agence
			print '<tr>';
			print '<td>';
			$htmlhelp = $langs->trans('AgenceHelp');
			print $form->textwithpicto($langs->trans('Agence'), $htmlhelp);
			print '</td>';
			print '<td>';
			print $obj->agence;
			print '</td>';
			print '</tr>';

			// Prix
			print '<tr>';
			print '<td>';
			$htmlhelp = $langs->trans('PrixHelp');
			print $form->textwithpicto($langs->trans('Prix'), $htmlhelp);
			print '</td>';
			print '<td>';
			print $obj->prix.' FCFA';
			print '</td>';
			print '</tr>';

			/* Prix
			print '<tr>';
			print '<td>';
			$htmlhelp = $langs->trans('AccompagneHelp');
			print $form->textwithpicto($langs->trans('Accompagne'), $htmlhelp);
			print '</td>';
			print '<td>';
			print $obj->accompagne == 'on'  ? "OUI" : "NON";
			print '</td>';
			print '</tr>';

			if($obj->accompagne == 'on'){
				print '<tr>';
				print '<td>';

				print $langs->trans('Par');
				print '</td>';
				print '<td>';
				print $object_passenger->nom.' '.$object_passenger->prenom;
				print '</td>';
				print '</tr>';
			}*/

			// Other attributes
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

			print '</tbody>';
			print '</table>'."\n";

			print '</div>';
			print '<div class="fichehalfright">';
			print '<div class="ficheaddleft">';

			print '<div class="underbanner clearboth"></div>';

			// Info workflow
			print '<table class="border tableforfield centpercent">'."\n";
			print '<tbody>';

			if (!empty($object->fk_user_creat))
			{
				$userCreate = new User($db);
				$userCreate->fetch($object->fk_user_creat);
				print '<tr>';
				print '<td class="titlefield">'.$langs->trans('UserCreat').'</td>';
				print '<td>'.$userCreate->getNomUrl(-1).'</td>';
				print '</tr>';
			}

			// Approver
			if (!empty($object->fk_valideur)) {
				$userValidate = new User($db);
				$userValidate->fetch($object->fk_valideur);
				print '<tr>';
				print '<td class="titlefield">';
				if ($object->status == Bticket::STATUS_APPROVED || $object->status == Bticket::STATUS_CANCELED) print $langs->trans('ApprovedBy');
				else print $langs->trans('ReviewedByCP');
				print '</td>';
				print '<td>';
				print $userValidate->getNomUrl(-1);
				print '</td>';
				print '</tr>';
			}

			print '<tr>';
			print '<td>'.$langs->trans('DateCreation').'</td>';
			print '<td>'.dol_print_date($object->date_creation, 'dayhour', 'tzuser').'</td>';
			print '</tr>';

			print '</tbody>';
			print '</table>';



			print '</div>';
			print '</div>';
			print '</div>';

			print '<div class="clearboth"></div>';

			print dol_get_fiche_end();
		}
	} elseif ($action != 'create')
	{
		exit;
	}
}

$tmpcode = '';

// Si validation de la demande
if ($action == 'valid')
{
	print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("TitleValidCP"), $langs->trans("ConfirmValidCP"), "confirm_valid", '', 1, 1);
}

/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */
if ($action != 'create' && $action != 'edit')
{
	print "\n".'<div class="tabsAction">'."\n";

	$parameters = array();
	$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook))
	{
		if ($usercancreate  && $object->status == Bticket::STATUS_DRAFT)
		{
			if (!isset($object->no_button_edit) || $object->no_button_edit <> 1) print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&amp;id='.$object->id.'">'.$langs->trans("Modify").'</a>';
		}

		if ($usercancreate && $object->status == Bticket::STATUS_APPROVED)		// If draft
		{
			if (!isset($object->no_button_edit) || $object->no_button_edit <> 1) print '<a class="butAction" href="'.DOL_URL_ROOT.'/custom/bookticket/penalite_card.php?action=create&bticket='.$object->id.'">'.$langs->trans("NewPenalite").'</a>';

			if($expire >= $today) print '<a href="document.php?id='.$object->id.'&type=bticket" class="butAction">'.$langs->trans("PRINT").'</a>';
		}

		if ($object->status == Bticket::STATUS_VALIDATED)	// If validated
		{
			if ($user->id == $object->fk_valideur)
			{
				if (!isset($object->no_button_edit) || $object->no_button_edit <> 1) print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&amp;id='.$object->id.'">'.$langs->trans("Modify").'</a>';
				print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=valid" class="butAction">'.$langs->trans("Approve").'</a>';
				print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=refuse" class="butAction">'.$langs->trans("ActionRefuse").'</a>';
			}elseif($object->fk_valideur){
				if (!isset($object->no_button_edit) || $object->no_button_edit <> 1) print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&amp;id='.$object->id.'">'.$langs->trans("Modify").'</a>';
			}
		}

		if ($usercandelete)
		{
			if (!isset($object->no_button_delete) || $object->no_button_delete <> 1)
			{
				if (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))
				{
					print '<span id="action-delete" class="butActionDelete">'.$langs->trans('Delete').'</span>'."\n";
				} else {
					print '<a class="butActionDelete" onclick="return confirm(\'Voulez-vous vraiment supprimer cet Billet de voyage ! \');" href="'.$_SERVER["PHP_SELF"].'?action=delete&amp;token='.newToken().'&amp;id='.$object->id.'">'.$langs->trans("Delete").'</a>';
				}
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("TicketIsUsed").'">'.$langs->trans("Delete").'</a>';
			}
		} else {
			print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("Delete").'</a>';
		}
	}

	print "\n</div>\n";
}

/*
 * All the "Add to" areas
 */

if ($object->id && ($action == '' || $action == 'view') && $object->status)
{
	//Variable used to check if any text is going to be printed
	$html = '';
	//print '<div class="fichecenter"><div class="fichehalfleft">';

	//If any text is going to be printed, then we show the table
	if (!empty($html))
	{
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="addin">';

		print load_fiche_titre($langs->trans("AddToDraft"), '', '');

		print dol_get_fiche_head('');

		$html .= '<tr><td class="nowrap">'.$langs->trans("Quantity").' ';
		$html .= '<input type="text" class="flat" name="qty" size="1" value="1"></td>';
		$html .= '<td class="nowrap">'.$langs->trans("ReductionShort").'(%) ';
		$html .= '<input type="text" class="flat" name="remise_percent" size="1" value="0">';
		$html .= '</td></tr>';

		print '<table width="100%" class="border">';
		print $html;
		print '</table>';

		print '<div class="center">';
		print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
		print '</div>';

		print dol_get_fiche_end();

		print '</form>';
	}
}


// End of page
llxFooter();
$db->close();
