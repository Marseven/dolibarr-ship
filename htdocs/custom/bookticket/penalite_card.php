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
 *	\file       bookticket/penaliteindex.php
 *	\ingroup    bookticket
 *	\brief      Home page of penalite left menu
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
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/penalite.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/lib/penalite.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/modules_bticket.php';

// Load translation files required by the page
$langs->loadLangs(array('bookticket', 'other'));

$mesg = ''; $error = 0; $errors = array();

$refalreadyexists = 0;

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = (GETPOST('action', 'alpha') ? GETPOST('action', 'alpha') : 'view');
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$socid = GETPOST('socid', 'int');

if (!empty($user->socid)) $socid = $user->socid;

$object = new Penalite($db);

$object_passenger = new Passenger($db);
$object_bticket = new Bticket($db);

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

$usercanread = $user->rights->bookticket->penalite->read;
$usercancreate = $user->rights->bookticket->penalite->write;
$usercandelete = $user->rights->bookticket->penalite->delete;


$parameters = array('id'=>$id, 'ref'=>$ref, 'objcanvas'=>$objcanvas);


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

$bticketrecords = [];
$sql_bticket = 'SELECT b.rowid, b.ref, b.entity,';
$sql_bticket .= ' b.date_creation, b.tms as date_update';
$sql_bticket .= ' FROM '.MAIN_DB_PREFIX.'bookticket_bticket as b';
$sql_bticket .= ' WHERE b.entity IN ('.getEntity('bticket').')';
$sql_bticket .= ' AND b.status = 2';
$resql_bticket =$db->query($sql_bticket);
if ($resql_bticket)
{
	$num = $db->num_rows($resql_bticket);
	$i = 0;
	if ($num)
	{
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql_bticket);
			if ($obj)
			{
				$bticketrecords[$i] = $obj;
			}
			$i++;
		}
	}
}

$travelrecords = [];
$sql_travel = 'SELECT t.rowid, t.ref, t.jour, t.heure, t.entity,';
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



// Actions to build doc
$upload_dir = $conf->bookticket->dir_output;
$permissiontoadd = $usercancreate;
include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';




// Add a penalite
if ($action == 'add' && $usercancreate)
{
	$error = 0;

	if (empty($ref))
	{
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Ref')), null, 'errors');
		$action = "create";
		$error++;
	}

	if (!$error)
	{
		$idbticket = GETPOST('fk_bticket');
		$object_bticket->fetch($idbticket);
		$idpassenger = $object_bticket->fk_passenger;
		$object_passenger->fetch($idpassenger);

		$object->ref   = $ref;
		$object->fk_bticket = GETPOST('fk_bticket');
		$object->fk_bticket = $idpassenger;
		$object->datea = GETPOST('datea');
		$object->dateb = GETPOST('dateb');
		$object->nom = GETPOST('nomprenom');
		$object->billet_perdu = GETPOST('billet_perdu');
		$object->classe = GETPOST('classe');
		$object->classe_enfant = GETPOST('classe_enfant');
		$object->prix_c = GETPOST('prix_c');
		$object->prix_ce = GETPOST('prix_ce');
		$object->prix_n = 8000;
		$object->prix_bp = 8000;
		$object->prix_da = 5000;
		$object->prix_db = 8000;

		if(GETPOST('datea') == 'on'){
			$object_bticket->fk_travel = GETPOST('fk_travel');
			$object_bticket->prix  += $object->prix_da;
		}

		if(GETPOST('dateb') == 'on'){
			$object_bticket->fk_travel  = GETPOST('fk_travel');
			$object_bticket->prix  += $object->prix_db;
		}

		if(GETPOST('nomprenom') == 'on'){
			$object_passenger->nom   = GETPOST('nom');
			$object_passenger->prenom  = GETPOST('prenom');
			$object_bticket->prix  += $object->prix_n;
		}

		if(GETPOST('billet_perdu') == 'on'){
			$object_bticket->prix  += $object->prix_bp;
		}

		if(GETPOST('classe') == 'on'){
			$object_bticket->fk_classe  = GETPOST('fk_classe');
			$object_bticket->prix  += $object->prix_c;
		}

		if(GETPOST('classe_enfant') == 'on'){
			$object_bticket->fk_classe = GETPOST('fk_classe');
			$object_bticket->prix  += $object->prix_ce;
		}

		$id_passenger = $object_passenger->update($user);
		$id_bticket = $object_bticket->update($user);

		$object->status = Penalite::STATUS_APPROVED;
		$object->fk_valideur = $user->fk_user;

		if (!$error && $id_passenger > 0 && $id_bticket > 0)
		{
			$id = $object->create($user);
		}

		if ($id > 0)
		{
			if (!empty($backtopage))
			{
				$backtopage = preg_replace('/--IDFORBACKTOPAGE--/', $object->id, $backtopage); // New method to autoselect project after a New on another form object creation
				if (preg_match('/\?/', $backtopage)) $backtopage .= '&socid='.$object->id; // Old method
				header("Location: ".$backtopage);
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

			$idbticket = GETPOST('fk_bticket');
			$object_bticket->fetch($idbticket);
			$idpassenger = $object_bticket->fk_passenger;
			$object_passenger->fetch($idpassenger);


			$object->ref   = $ref;
			$object->fk_bticket = GETPOST('fk_bticket');
			$object->fk_bticket = $idpassenger;
			$object->datea = GETPOST('datea');
			$object->dateb = GETPOST('dateb');
			$object->nom = GETPOST('nomprenom');
			$object->billet_perdu = GETPOST('billet_perdu');
			$object->classe = GETPOST('classe');
			$object->classe_enfant = GETPOST('classe_enfant');
			$object->classe = GETPOST('prix_c');
			$object->classe_enfant = GETPOST('prix_ce');

			if(GETPOST('datea') == 'on'){
				$object_bticket->fk_travel = GETPOST('fk_travel');
				$object_bticket->prix  += 5000;
			}

			if(GETPOST('dateb') == 'on'){
				$object_bticket->fk_travel  = GETPOST('fk_travel');
				$object_bticket->prix  += 8000;
			}

			if(GETPOST('nomprenom') == 'on'){
				$object_passenger->nom   = GETPOST('nom');
				$object_passenger->prenom  = GETPOST('prenom');
				$object_bticket->prix  += 8000;
			}

			if(GETPOST('billet_perdu') == 'on'){
				$object_bticket->prix  += 8000;
			}

			if(GETPOST('classe') == 'on'){
				$object_bticket->fk_classe  = GETPOST('fk_classe');
				$object_bticket->prix  += GETPOST('prix_c');
			}

			if(GETPOST('classe_enfant') == 'on'){
				$object_bticket->fk_classe = GETPOST('fk_classe');
				$object_bticket->prix  += GETPOST('prix_ce');
			}

			$object->fk_valideur = $user->fk_user;

			$object->status = Penalite::STATUS_VALIDATED;


			if (!$error && $object->check())
			{
				if ($object->update($user) > 0 && $object_passenger->update($user) > 0 && $object_bticket->update($user) > 0)
				{
					$action = 'view';
				} else {
					if (count($object->errors)) setEventMessages($object->error, $object->errors, 'errors');
					else setEventMessages($langs->trans($object->error), null, 'errors');
					$action = 'edit';
				}
			} else {
				if (count($object->errors)) setEventMessages($object->error, $object->errors, 'errors');
				else setEventMessages($langs->trans("ErrorBticketBadRefOrLabel"), null, 'errors');
				$action = 'edit';
			}
		}
	}
}

//Approve
if($action == 'valid' && $usercancreate){

	$object->fetch($id);

	// If status is waiting approval and approver is also user
	if ($object->status == Penalite::STATUS_VALIDATED && $user->id == $object->fk_valideur)
	{
		$object->status = Penalite::STATUS_APPROVED;

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

	// If status is waiting approval and approver is also user
	if ($object->status == Penalite::STATUS_VALIDATED && $user->id == $object->fk_valideur)
	{
		$object->status = Penalite::STATUS_REFUSED;

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
		header('Location: '.DOL_URL_ROOT.'/custom/bookticket/penalite_list.php?type='.$object->type.'&delbticket='.urlencode($object->ref));
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
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';


		$picto = 'bticket';
		$title = $langs->trans("NewTicket");

		$linkback = "";
		print load_fiche_titre($title, $linkback, $picto);

		print dol_get_fiche_head('');

		print '<table class="border centpercent">';

			// Ref
			print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Ref").'</td><td colspan="3"><input name="ref" class="maxwidth200" maxlength="128" value="'.dol_escape_htmltag($object->ref).'"></td></tr>';

			// bticket
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Bticket").'</td>';

			$bticket = '<td><select class="flat" name="fk_bticket">';
			if (empty($bticketrecords))
			{
				$bticket .= '<option value="0">'.($langs->trans("AucuneEntree")).'</option>';
			}else{
				foreach ($bticketrecords as $lines)
				{
					$bticket .= '<option value="';
					$bticket .= $lines->rowid;
					$bticket .= '"';
					$bticket .= '>';
					$bticket .= $langs->trans($lines->ref);
					$shbticketip .= '</option>';
				}
			}

			$bticket .= '</select>';

			print $bticket;

			print '</td></tr>';

			// datea
			print '<tr><td class="titlefieldcreate">'.$langs->trans("DateA").'</td>';
			print '<td><input type="checkbox" name="datea" >';
			print '</td></tr>';

			// datea
			print '<tr><td class="titlefieldcreate">'.$langs->trans("DateB").'</td>';
			print '<td><input type="checkbox" name="dateb" >';
			print '</td></tr>';

			print '<tr>';

			// travel
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Travel").'</td>';

			$travel = '<td><select class="flat" name="fk_travel">';
			if (empty($travelrecords))
			{
				$travel .= '<option value="0">'.($langs->trans("AucuneEntree")).'</option>';
			}else{
				foreach ($travelrecords as $lines)
				{
					$travel .= '<option value="';
					$travel .= $lines->rowid;
					$travel .= '"';
					$travel .= '>';
					$travel .= $langs->trans($lines->ref);
					$travel .= '</option>';
				}
			}

			$travel .= '</select>';

			print $travel;

			print '</td></tr>';

			// nom
			print '<tr><td class="titlefieldcreate">'.$langs->trans("NomPrenom").'</td>';
			print '<td><input type="checkbox" name="nomprenom" >';
			print '</td></tr>';

			// nom
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Nom").'</td>';
			print '<td><input name="nom" class="maxwidth300" value="'.$object_passenger->nom.'">';
			print '</td></tr>';

			// prenom
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Prenom").'</td>';
			print '<td><input name="prenom" class="maxwidth300" value="'.$object_passenger->prenom.'">';
			print '</td></tr>';


			// classe
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Classe").'</td>';
			print '<td><input type="checkbox" name="classe" >';
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

			print '<tr><td class="titlefieldcreate">'.$langs->trans("PenaliteClasse").'</td>';
			print '<td><input type="number" name="prix_c" >';
			print '</td></tr>';

			// classe_enfant
			print '<tr><td class="titlefieldcreate">'.$langs->trans("ClasseEnfant").'</td>';
			print '<td><input type="checkbox" name="classe_enfant" >';
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

			print '<tr><td class="titlefieldcreate">'.$langs->trans("PenaliteClasseEnfant").'</td>';
			print '<td><input type="number" name="prix_ce" >';
			print '</td></tr>';

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
		// Fiche en mode edition
		if ($action == 'edit' && $usercancreate)
		{
			$sql_p = 'SELECT DISTINCT p.rowid, p.nom, p.prenom, p.age, p.adresse,  p.telephone, p.email, p.accompagne,  p.nom_enfant,  p.age_enfant, p.entity';
			$sql_p .= ' FROM '.MAIN_DB_PREFIX.'bookticket_passenger as p';
			$sql_p .= ' WHERE p.entity IN ('.getEntity('passenger').')';
			$sql_p .= ' AND p.rowid IN ('.$object->fk_passenger.')';
			$resql_p = $db->query($sql_p);
			$obj_p = $db->fetch_object($resql_p);

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


			$type = $langs->trans('Penalite');

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
			print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Ref").'</td><td colspan="3"><input name="ref" class="maxwidth200" maxlength="128" value="'.dol_escape_htmltag($object->ref).'"></td></tr>';

			// bticket
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Bticket").'</td>';

			$bticket = '<td><select class="flat" name="fk_bticket">';
			if (empty($bticketrecords))
			{
				$bticket .= '<option value="0">'.($langs->trans("AucuneEntree")).'</option>';
			}else{
				foreach ($bticketrecords as $lines)
				{
					$bticket .= '<option value="';
					$bticket .= $lines->rowid;
					$bticket .= '"';
					$bticket .= '>';
					$bticket .= $langs->trans($lines->ref);
					$shbticketip .= '</option>';
				}
			}

			$bticket .= '</select>';

			print $bticket;

			print '</td></tr>';

			// datea
			print '<tr><td class="titlefieldcreate">'.$langs->trans("DateA").'</td>';
			print '<td><input type="checkbox" name="datea" >';
			print '</td></tr>';

			// datea
			print '<tr><td class="titlefieldcreate">'.$langs->trans("DateB").'</td>';
			print '<td><input type="checkbox" name="dateb" >';
			print '</td></tr>';

			print '<tr>';

			// travel
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Travel").'</td>';

			$travel = '<td><select class="flat" name="fk_travel">';
			if (empty($travelrecords))
			{
				$travel .= '<option value="0">'.($langs->trans("AucuneEntree")).'</option>';
			}else{
				foreach ($travelrecords as $lines)
				{
					$travel .= '<option value="';
					$travel .= $lines->rowid;
					$travel .= '"';
					$travel .= '>';
					$travel .= $langs->trans($lines->ref);
					$travel .= '</option>';
				}
			}

			$travel .= '</select>';

			print $travel;

			print '</td></tr>';

			// nom
			print '<tr><td class="titlefieldcreate">'.$langs->trans("NouveauPassager").'</td>';
			print '<td><input type="checkbox" name="nomprenom" ><input name="fk_passenger" class="maxwidth200" maxlength="128" value="'.dol_escape_htmltag($object_passenger->id).'">';
			print '</td></tr>';

			// nom
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Nom").'</td>';
			print '<td><input name="nom" class="maxwidth300" value="'.$object_passenger->nom.'">';
			print '</td></tr>';

			// prenom
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Prenom").'</td>';
			print '<td><input name="prenom" class="maxwidth300" value="'.$object_passenger->prenom.'">';
			print '</td></tr>';


			// classe
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Classe").'</td>';
			print '<td><input type="checkbox" name="classe" >';
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

			print '<tr><td class="titlefieldcreate">'.$langs->trans("PenaliteClasse").'</td>';
			print '<td><input type="number" name="prix_c" >';
			print '</td></tr>';

			// classe_enfant
			print '<tr><td class="titlefieldcreate">'.$langs->trans("ClasseEnfant").'</td>';
			print '<td><input type="checkbox" name="classe_enfant" >';
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

			print '<tr><td class="titlefieldcreate">'.$langs->trans("PenaliteClasseEnfant").'</td>';
			print '<td><input type="number" name="prix_ce" >';
			print '</td></tr>';

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

			$sql_t = 'SELECT DISTINCT t.rowid, t.ref, s.label as ship, p.nom as nom, p.prenom as prenom,  c.label as classe, c.prix_standard as prix, tr.ref as travel, a.label as agence, t.entity';
			$sql_t .= ' FROM '.MAIN_DB_PREFIX.'bookticket_bticket as t';
			$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_ship as s ON t.fk_ship = s.rowid";
			$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_passenger as p ON t.fk_passenger = p.rowid";
			$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_classe as c ON t.fk_classe = c.rowid";
			$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_travel as tr ON t.fk_travel = tr.rowid";
			$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_agence as a ON t.fk_agence = a.rowid";
			$sql_t .= ' WHERE t.entity IN ('.getEntity('bticket').')';
			$sql_t .= ' AND t.rowid IN ('.$object->fk_bticket.')';
			$resql_t = $db->query($sql_t);
			$obj = $db->fetch_object($resql_t);

			$head = bticket_prepare_head($object);
			$titre = $langs->trans("CardPenalite");
			$picto = 'penalite';

			print dol_get_fiche_head($head, 'card', $titre, -1, $picto);

			$linkback = '<a href="'.DOL_URL_ROOT.'/custom/bookticket/bticket_list.php?restore_lastsearch_values=1&type=">'.$langs->trans("BackToList").'</a>';

			$shownav = 1;
			if ($user->socid && !in_array('penalite', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav = 0;

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

			// Bticket
			print '<tr>';
			print '<td>';
			$htmlhelp = $langs->trans('BticketHelp');
			print $form->textwithpicto($langs->trans('Travel'), $htmlhelp);
			print '</td>';
			print '<td>';
			print $obj->travel;
			print '</td>';
			print '</tr>';

			// Passenger
			print '<tr>';
			print '<td class="titlefield">'.$langs->trans("Passenger").'</td>';
			print '<td>';
			print $obj->nom.' '.$obj->prenom;
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

			// Prix
			print '<tr>';
			print '<td>';
			$htmlhelp = $langs->trans('PrixHelp');
			print $form->textwithpicto($langs->trans('PrixBillet'), $htmlhelp);
			print '</td>';
			print '<td>';
			print $obj->prix.' FCFA';
			print '</td>';
			print '</tr>';

			// Penalite
			print '<tr>';
			print '<td>';
			$htmlhelp = $langs->trans('AgenceHelp');
			print $form->textwithpicto($langs->trans('Penalites'), $htmlhelp);
			print '</td>';
			print '</tr>';

			// datea
			print '<tr>';
			print '<td>';
			$htmlhelp = $langs->trans('PenaliteHelp');
			print $form->textwithpicto($langs->trans('PenaliteDateA'), $htmlhelp);
			print '</td>';
			print '<td>';
			print $object->datea == "on" ? $object->prix_da." FCFA" : "Non";
			print '</td>';
			print '</tr>';

			// dateb
			print '<tr>';
			print '<td>';
			$htmlhelp = $langs->trans('PenaliteHelp');
			print $form->textwithpicto($langs->trans('PenaliteDateB'), $htmlhelp);
			print '</td>';
			print '<td>';
			print $object->dateb == "on" ? $object->prix_db." FCFA" : "Non";
			print '</td>';
			print '</tr>';

			// nom
			print '<tr>';
			print '<td>';
			$htmlhelp = $langs->trans('PenaliteHelp');
			print $form->textwithpicto($langs->trans('PenaliteNom'), $htmlhelp);
			print '</td>';
			print '<td>';
			print $object->nom == "on" ? $object->prix_n." FCFA" : "Non";
			print '</td>';
			print '</tr>';

			// billet_perdu
			print '<tr>';
			print '<td>';
			$htmlhelp = $langs->trans('PenaliteHelp');
			print $form->textwithpicto($langs->trans('PenaliteBilletPerdu'), $htmlhelp);
			print '</td>';
			print '<td>';
			print $object->billet_perdu == "on" ? $object->prix_bp." FCFA" : "Non";
			print '</td>';
			print '</tr>';

			// classe
			print '<tr>';
			print '<td>';
			$htmlhelp = $langs->trans('PenaliteHelp');
			print $form->textwithpicto($langs->trans('PenaliteClasse'), $htmlhelp);
			print '</td>';
			print '<td>';
			print $object->classe == "on" ? $object->prix_c." FCFA" : "Non";
			print '</td>';
			print '</tr>';

			// classe_enfant
			print '<tr>';
			print '<td>';
			$htmlhelp = $langs->trans('PenaliteHelp');
			print $form->textwithpicto($langs->trans('PenaliteClasseEnfant'), $htmlhelp);
			print '</td>';
			print '<td>';
			print $object->classe_enfant == "on" ? $object->prix_ce." FCFA" : "Non";
			print '</td>';
			print '</tr>';

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
				if ($object->status == Penalite::STATUS_APPROVED || $object->status == Penalite::STATUS_CANCELED) print $langs->trans('ApprovedBy');
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

/*$formconfirm = '';

// Confirm delete bticket
if (($action == 'delete' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile)))	// Output when action = clone if jmobile or no js
	|| (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile)))							// Always output when not jmobile nor js
{
	$formconfirm = $form->formconfirm("card.php?id=".$object->id, $langs->trans("DeleteBTicket"), $langs->trans("ConfirmDeleteTicket"), "confirm_delete", '', 0, "action-delete");
}

// Clone confirmation
if (($action == 'clone' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile)))		// Output when action = clone if jmobile or no js
	|| (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile)))							// Always output when not jmobile nor js
{
	// Define confirmation messages
	$formquestionclone = array(
		'text' => $langs->trans("ConfirmClone"),
		array('type' => 'text', 'name' => 'clone_ref', 'label' => $langs->trans("NewRefForClone"), 'value' => empty($tmpcode) ? $langs->trans("CopyOf").' '.$object->ref : $tmpcode, 'size'=>24),
		array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneContentTicket"), 'value' => 1),
	);


	$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneTicket', $object->ref), 'confirm_clone', $formquestionclone, 'yes', 'action-clone', 350, 600);
}


// Print form confirm
print $formconfirm;*/

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
		if ($usercancreate)
		{
			if (!isset($object->no_button_edit) || $object->no_button_edit <> 1) print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&amp;id='.$object->id.'">'.$langs->trans("Modify").'</a>';

			/*if (!isset($object->no_button_copy) || $object->no_button_copy <> 1)
			{
				if (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))
				{
					print '<span id="action-clone" class="butAction">'.$langs->trans('ToClone').'</span>'."\n";
				} else {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=clone&amp;id='.$object->id.'">'.$langs->trans("ToClone").'</a>';
				}
			}*/
		}

		if ($usercancreate && $object->status == Bticket::STATUS_APPROVED)		// If draft
		{
			print '<a href="document.php?id='.$object->id.'&type=bticket" class="butAction">'.$langs->trans("PRINT").'</a>';
		}

		if ($object->status == Bticket::STATUS_VALIDATED)	// If validated
		{
			if ($user->id == $object->fk_valideur)
			{
				print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=valid" class="butAction">'.$langs->trans("Approve").'</a>';
				print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=refuse" class="butAction">'.$langs->trans("ActionRefuseCP").'</a>';
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
