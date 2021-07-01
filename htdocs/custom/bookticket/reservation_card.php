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
 *	\file       bookticket/reservation_card.php
 *	\ingroup    bookticket
 *	\brief      Home page of reservation left menu
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
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/travel.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/reservation.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/lib/reservation.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('bookticket', 'other'));

$mesg = ''; $error = 0; $errors = array();

$refalreadyexists = 0;

$id = GETPOST('id', 'int');
$travel = GETPOST('travel', 'int');
$ref = GETPOST('ref', 'alpha');
$action = (GETPOST('action', 'alpha') ? GETPOST('action', 'alpha') : 'view');
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');
$confirm = GETPOST('confirm', 'alpha');
$socid = GETPOST('socid', 'int');

// by default 'alphanohtml' (better security); hidden conf MAIN_SECURITY_ALLOW_UNSECURED_LABELS_WITH_HTML allows basic html
$label_security_check = empty($conf->global->MAIN_SECURITY_ALLOW_UNSECURED_LABELS_WITH_HTML) ? 'alphanohtml' : 'restricthtml';

if (!empty($user->socid)) $socid = $user->socid;

$object = new Reservation($db);

if ($id > 0 || !empty($ref))
{
	$result = $object->fetch($id, $ref);

	if (!empty($conf->travel->enabled)) $upload_dir = $conf->travel->multidir_output[$object->entity].'/'.get_exdir(0, 0, 0, 0, $object, 'travel').dol_sanitizeFileName($object->ref);
	elseif (!empty($conf->service->enabled)) $upload_dir = $conf->service->multidir_output[$object->entity].'/'.get_exdir(0, 0, 0, 0, $object, 'travel').dol_sanitizeFileName($object->ref);

	if (!empty($conf->global->travel_USE_OLD_PATH_FOR_PHOTO))    // For backward compatiblity, we scan also old dirs
	{
		if (!empty($conf->travel->enabled)) $upload_dirold = $conf->travel->multidir_output[$object->entity].'/'.substr(substr("000".$object->id, -2), 1, 1).'/'.substr(substr("000".$object->id, -2), 0, 1).'/'.$object->id."/photos";
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
	$objcanvas->getCanvas('reservation', 'card', $canvas);
}

// Security check
//$result = restrictedArea($user, 'produit|service', $fieldvalue, 'travel&travel', '', '', $fieldtype);

/*
 * Actions
 */

if ($cancel) $action = '';

$usercanread = $user->rights->bookticket->reservation->read;
$usercancreate = $user->rights->bookticket->reservation->write;
$usercandelete = $user->rights->bookticket->reservation->delete;

$parameters = array('id'=>$id, 'ref'=>$ref, 'objcanvas'=>$objcanvas);

$travelrecords = [];
$sql_travel = 'SELECT t.rowid, t.ref, t.jour, t.heure, t.lieu_depart, t.lieu_arrive, t.nbre_place, t.nbre_eco, t.nbre_vip, t.nbre_aff, t.entity,';
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

// Actions to build doc
$upload_dir = $conf->reservation->dir_output;
$permissiontoadd = $usercancreate;
include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';


// Add a reservation
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

		$object->ref                   = $ref;
		$object->fk_travel               = GETPOST('fk_travel');

		$object_travel = new Travel($db);
		$object_travel->fetch($object->fk_travel);

		if(GETPOST('nbre_vip') <= $object_travel->nbre_vip) $object->nbre_vip = GETPOST('nbre_vip');
		if(GETPOST('nbre_aff') <= $object_travel->nbre_aff) $object->nbre_aff = GETPOST('nbre_aff');
		if(GETPOST('nbre_eco') <= $object_travel->nbre_eco) $object->nbre_eco = GETPOST('nbre_eco');
		$object->nbre_place = $object->nbre_vip + $object->nbre_eco + $object->nbre_aff;


		// Fill array 'array_options' with data from add form
		if (!$error)
		{
			$id = $object->create($user);

			$object_travel->fetch($object->travel);


			$object_travel->nbre_vip = $object_travel->nbre_vip - $object->nbre_vip;
			$object_travel->nbre_eco = $object_travel->nbre_eco - $object->nbre_eco;
			$object_travel->nbre_aff = $object_travel->nbre_aff - $object->nbre_aff;
			$object_travel->nbre_place = $object_travel->nbre_place - $object->nbre_place;

			$object_travel->update($user);
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

// Update a travel
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
			$object->fk_travel               = GETPOST('fk_travel');

			$object_travel = new Travel($db);
			$object_travel->fetch($object->fk_travel);

			$object_travel->nbre_vip = $object_travel->nbre_vip + $object->nbre_vip;
			$object_travel->nbre_eco = $object_travel->nbre_eco + $object->nbre_eco;
			$object_travel->nbre_aff = $object_travel->nbre_aff + $object->nbre_aff;
			$object_travel->nbre_place = $object_travel->nbre_place + $object->nbre_place;

			$object_travel->update($user);

			if(GETPOST('nbre_vip') <= $object_travel->nbre_vip) $object->nbre_vip = GETPOST('nbre_vip');
			if(GETPOST('nbre_aff') <= $object_travel->nbre_aff) $object->nbre_aff = GETPOST('nbre_aff');
			if(GETPOST('nbre_eco') <= $object_travel->nbre_eco) $object->nbre_eco = GETPOST('nbre_eco');
			$object->nbre_place = $object->nbre_vip + $object->nbre_eco + $object->nbre_aff;

			if (!$error && $object->check())
			{
				if ($object->update($user) > 0)
				{
					$object_travel->nbre_vip = $object_travel->nbre_vip - $object->nbre_vip;
					$object_travel->nbre_eco = $object_travel->nbre_eco - $object->nbre_eco;
					$object_travel->nbre_aff = $object_travel->nbre_aff - $object->nbre_aff;
					$object_travel->nbre_place = $object_travel->nbre_place - $object->nbre_place;

					$object_travel->update($user);
					$action = 'view';
				} else {
					if (count($object->errors)) setEventMessages($object->error, $object->errors, 'errors');
					else setEventMessages($langs->trans($object->error), null, 'errors');
					$action = 'edit';
				}
			} else {
				if (count($object->errors)) setEventMessages($object->error, $object->errors, 'errors');
				else setEventMessages($langs->trans("ErrortravelBadRefOrLabel"), null, 'errors');
				$action = 'edit';
			}
		}
	}
}

//Approve
if($action == 'valid' && $usercancreate){

	$object->fetch($id);

	// If status is waiting approval and approver is also user
	if ($object->status == Travel::STATUS_DRAFT)
	{
		$object->status = Travel::STATUS_APPROVED;

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

//Lock
if($action == 'lock' && $usercancreate){

	$object->fetch($id);

	// If status is waiting lock and
	if ($object->status == Reservation::STATUS_APPROVED)
	{
		$object->status = Reservation::STATUS_LOCK;

		$object_travel->update($user);

		if (!$error && $object->check())
		{
			if ($object->update($user) > 0)
			{
				$action = 'view';
			} else {
				if (count($object->errors)) setEventMessages($object->error, $object->errors, 'errors');
				else setEventMessages($langs->trans($object->error), null, 'errors');
				$action = 'edit';
			}
		} else {
			if (count($object->errors)) setEventMessages($object->error, $object->errors, 'errors');
			else setEventMessages($langs->trans("ErrortravelBadRefOrLabel"), null, 'errors');
			$action = 'edit';
		}
	}
}

//Lock
if($action == 'cancel' && $usercancreate){

	$object->fetch($id);

	// If status is waiting lock and
	if ($object->status == Reservation::STATUS_APPROVED)
	{
		$object->status = Reservation::STATUS_CANCELED;

		$object_travel = new Travel($db);
		$object_travel->fetch($object->fk_travel);

		$object_travel->nbre_vip = $object_travel->nbre_vip + $object->nbre_vip;
		$object_travel->nbre_eco = $object_travel->nbre_eco + $object->nbre_eco;
		$object_travel->nbre_aff = $object_travel->nbre_aff + $object->nbre_aff;
		$object_travel->nbre_place = $object_travel->nbre_place + $object->nbre_place;

		$object_travel->update($user);

		if (!$error && $object->check())
		{
			if ($object->update($user) > 0)
			{
				$action = 'view';
			} else {
				if (count($object->errors)) setEventMessages($object->error, $object->errors, 'errors');
				else setEventMessages($langs->trans($object->error), null, 'errors');
				$action = 'edit';
			}
		} else {
			if (count($object->errors)) setEventMessages($object->error, $object->errors, 'errors');
			else setEventMessages($langs->trans("ErrortravelBadRefOrLabel"), null, 'errors');
			$action = 'edit';
		}
	}
}

// Delete a Reservation
//if ($action == 'confirm_delete' && $confirm != 'yes') { $action = ''; }
if ($action == 'delete' && $usercandelete)
{
	$result = $object->delete($user);

	if ($result > 0)
	{
		header('Location: '.DOL_URL_ROOT.'/custom/bookticket/reservation_list.php?delreservation='.urlencode($object->ref));
		exit;
	} else {
		setEventMessages($langs->trans($object->error), null, 'errors');
		$reload = 0;
		$action = '';
	}
}


// Add travel into object
if ($object->id > 0 && $action == 'addin')
{
	$thirpdartyid = 0;
}


/*
 * View
 */

$title = $langs->trans('ReservationCard');
$helpurl = '';
$shortlabel = dol_trunc($object->ref, 16);
$title = $langs->trans('Reservation')." ".$shortlabel." - ".$langs->trans('Card');
$helpurl = 'EN:Module_Bookticket|FR:Module_Bookticket|ES:M&oacute;dulo_Bookticket';

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
		$object = new Reservation($db);
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
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';


		$picto = 'reservation';
		$title = $langs->trans("NewReservation");

		$linkback = "";
		print load_fiche_titre($title, $linkback, $picto);

		print dol_get_fiche_head('');

		print '<table class="border centpercent">';

		print '<tr>';

		if ($refalreadyexists)
		{
			print $langs->trans("RefAlreadyExists");
		}
		// Ref
		$number = "0123456789";
		$code = substr(str_shuffle(str_repeat($number, 6)), 0, 6);
		$tmpref = "DVM-RSV-".$code;
		print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Ref").'</td><td colspan="3"><input name="ref" class="maxwidth200" maxlength="128" value="'.dol_escape_htmltag($tmpref).'"></td></tr>';

		print '</td></tr>';

		// travel
		print '<tr>';
		print '<td><input name="fk_travel" type="hidden" value="'.$travel.'"> Place(s)';
		print '</td></tr>';

		// Nbre_vip
		print '<tr><td class="titlefieldcreate">'.$langs->trans("NbreVip").'</td>';
		print '<td><input name="nbre_vip" class="maxwidth50" value="'.$object->nbre_vip.'"> Place(s)';
		print '</td></tr>';

		// Nbre_aff
		print '<tr><td class="titlefieldcreate">'.$langs->trans("NbreAff").'</td>';
		print '<td><input name="nbre_aff" class="maxwidth50" value="'.$object->nbre_aff.'"> Place(s)';
		print '</td></tr>';

		// Nbre_eco
		print '<tr><td class="titlefieldcreate">'.$langs->trans("NbreEco").'</td>';
		print '<td><input name="nbre_eco" class="maxwidth50" value="'.$object->nbre_eco.'"> Place(s)';
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
         * reservation card
         */
		// Fiche en mode edition
		if ($action == 'edit' && $usercancreate)
		{
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


			$type = $langs->trans('Reservation');

			// Main official, simple, and not duplicated code
			print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="POST" name="formprod">'."\n";
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
			print '<input type="hidden" name="canvas" value="'.$object->canvas.'">';

			$head = reservation_prepare_head($object);
			$titre = $langs->trans("CardReservation".$object->type);
			$picto =  'reservation';
			print dol_get_fiche_head($head, 'card', $titre, 0, $picto);


			print '<table class="border allwidth">';

			// Ref
			print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Ref").'</td><td colspan="3"><input name="ref" class="maxwidth200" maxlength="128" value="'.dol_escape_htmltag($object->ref).'" disabled></td></tr>';

			// travel
			print '<tr>';
			print '<td><input name="fk_travel" type="hidden" value="'.$object->fk_travel.'"> Place(s)';
			print '</td></tr>';

			// Nbre_vip
			print '<tr><td class="titlefieldcreate">'.$langs->trans("NbreVip").'</td>';
			print '<td><input name="nbre_vip" class="maxwidth50" value="'.$object->nbre_vip.'"> Place(s)';
			print '</td></tr>';

			// Nbre_aff
			print '<tr><td class="titlefieldcreate">'.$langs->trans("NbreAff").'</td>';
			print '<td><input name="nbre_aff" class="maxwidth50" value="'.$object->nbre_aff.'"> Place(s)';
			print '</td></tr>';

			// Nbre_eco
			print '<tr><td class="titlefieldcreate">'.$langs->trans("NbreEco").'</td>';
			print '<td><input name="nbre_eco" class="maxwidth50" value="'.$object->nbre_eco.'"> Place(s)';
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
			$object_travel = new Travel($db);
			$object_travel->fetch($object->fk_travel);

			$head = reservation_prepare_head($object);
			$titre = $langs->trans("CardReservation");
			$picto = 'reservation';

			print dol_get_fiche_head($head, 'card', $titre, -1, $picto);

			$linkback = '<a href="'.DOL_URL_ROOT.'/custom/bookticket/reservation_list.php?restore_lastsearch_values=1&type=">'.$langs->trans("BackToList").'</a>';

			$shownav = 1;
			if ($user->socid && !in_array('reservation', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav = 0;

			dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref');


			print '<div class="fichecenter">';
				print '<div class="fichehalfleft">';
				print '<div class="underbanner clearboth"></div>';

				print '<table class="border tableforfield centpercent">';
				print '<tbody>';

				// Ref
				print '<tr>';
				print '<td class="titlefield">'.$langs->trans("Label").'</td>';
				print '<td>';
				print $object->ref;
				print '</td></tr>';

				// Jour
				print '<tr>';
				print '<td class="titlefield">'.$langs->trans("Jour").'</td>';
				print '<td>';
				print dol_print_date($object_travel->jour, 'day', 'tzuser');
				print '</td></tr>';

				// heure
				print '<tr>';
				print '<td class="titlefield">'.$langs->trans("Heure").'</td>';
				print '<td>';
				print $object_travel->heure;
				print '</td></tr>';


				// LieuDepart
				print '<tr>';
				print '<td class="titlefield">'.$langs->trans("LieuDepart").'</td>';
				print '<td>';
				print $object_travel->lieu_depart;
				print '</td></tr>';

				// LieuArrive
				print '<tr>';
				print '<td class="titlefield">'.$langs->trans("LieuArrive").'</td>';
				print '<td>';
				print $object_travel->lieu_arrive;
				print '</td></tr>';



				// NbrePlace
				print '<tr>';
				print '<td>';
				$htmlhelp = $langs->trans('NbrePlaceHelp');
				$htmlhelp .= '<br>'.$langs->trans("NbrePlaceHelp");
				print $form->textwithpicto($langs->trans('NbrePlaceReserve'), $htmlhelp);
				print '</td>';
				print '<td>';
				print $object->nbre_place." / ".($object_travel->nbre_place + $object->nbre_place);
				print '</td>';
				print '</tr>';

				// NbreVip
				print '<tr>';
				print '<td>';
				$htmlhelp = $langs->trans('NbreVipHelp');
				$htmlhelp .= '<br>'.$langs->trans("NbreVipHelp");
				print $form->textwithpicto($langs->trans('NbreVipReserve'), $htmlhelp);
				print '</td>';
				print '<td>';
				print $object->nbre_vip." / ".($object_travel->nbre_vip + $object->nbre_vip);
				print '</td>';
				print '</tr>';

				// NbreAff
				print '<tr>';
				print '<td>';
				$htmlhelp = $langs->trans('NbreAffHelp');
				$htmlhelp .= '<br>'.$langs->trans("NbreAffHelp");
				print $form->textwithpicto($langs->trans('NbreAffReserve'), $htmlhelp);
				print '</td>';
				print '<td>';
				print $object->nbre_aff." / ".($object_travel->nbre_aff+$object->nbre_aff);
				print '</td>';
				print '</tr>';

				// NbreEco
				print '<tr>';
				print '<td>';
				$htmlhelp = $langs->trans('NbreEcoHelp');
				$htmlhelp .= '<br>'.$langs->trans("NbreEcoHelp");
				print $form->textwithpicto($langs->trans('NbreEcoReserve'), $htmlhelp);
				print '</td>';
				print '<td>';
				print $object->nbre_eco." / ".($object_travel->nbre_eco+$object->nbre_eco);
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

/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */
if ($action != 'create' && $action != 'edit')
{
	print "\n".'<div class="tabsAction">'."\n";

	$parameters = array();


		if ($usercancreate && ($object->status != Reservation::STATUS_LOCK && $object->status != Reservation::STATUS_CANCELED))
		{
			if (!isset($object->no_button_edit) || $object->no_button_edit <> 1) print '<a class="butAction" href="'.DOL_URL_ROOT.'/custom/bookticket/bticket_card.php?action=create&travel='.$object->fk_travel.'&reservation='.$object->id.'">'.$langs->trans('NewBTicket').'</a>';

			if (!isset($object->no_button_edit) || $object->no_button_edit <> 1) print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&amp;id='.$object->id.'">'.$langs->trans("Modify").'</a>';
		}

		if ($usercancreate && $object->status == Reservation::STATUS_DRAFT)		// If draft
		{
			print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=valid" class="butAction">'.$langs->trans("Approve").'</a>';
		}

		if ($usercancreate && $object->status == Reservation::STATUS_APPROVED)		// If draft
		{
			print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=cancel" class="butAction">'.$langs->trans("CancelReserve").'</a>';
		}

		if ($usercancreate && $object->status == Reservation::STATUS_APPROVED)		// If draft
		{
			print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=lock" class="butAction">'.$langs->trans("ArchiveReserve").'</a>';
		}

		if ($usercandelete)
		{
			if (!isset($object->no_button_delete) || $object->no_button_delete <> 1)
			{
				print '<a class="butActionDelete" onclick="return confirm(\'Voulez-vous vraiment supprimer ce voyage ! \');" href="'.$_SERVER["PHP_SELF"].'?action=delete&amp;token='.newToken().'&amp;id='.$object->id.'">'.$langs->trans("Delete").'</a>';

			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("TravelIsUsed").'">'.$langs->trans("Delete").'</a>';
			}
		} else {
			print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("Delete").'</a>';
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
