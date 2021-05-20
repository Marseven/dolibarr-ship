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
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/lib/bticket.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';

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

$object = new Bticket($db);
$object_passenger = new Passenger($db);

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

$createbarcode = empty($conf->barcode->enabled) ? 0 : 1;
if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->creer_advance)) $createbarcode = 0;

$parameters = array('id'=>$id, 'ref'=>$ref, 'objcanvas'=>$objcanvas);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

$shiprecords = [];
$sql_ship = "SELECT s.rowid, s.ref, s.label, s.labelshort,  s.nbre_place, s.nbre_vip, s.nbre_aff, s.nbre_eco,";
$sql_ship .= " s.entity";
$sql_ship .= " FROM ".MAIN_DB_PREFIX."bookticket_ship as s";
$sql_ship .= ' WHERE s.entity IN ('.getEntity('ship').')';

$resql_ship =$db->query($sql_ship);
if ($resql_ship)
{
	$num = $db->num_rows($resql_ship);
	$i = 0;
	if ($num)
	{
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql_ship);
			if ($obj)
			{
				$shiprecords[$i] = $obj;
			}
			$i++;
		}
	}
}

$classerecords = [];
$sql_classe = 'SELECT c.rowid, c.label, c.labelshort, c.entity,';
$sql_classe .= ' c.date_creation, c.tms as date_update';
$sql_classe .= ' FROM '.MAIN_DB_PREFIX.'bookticket_classe as c';
$sql_classe .= ' WHERE c.entity IN ('.getEntity('classe').')';
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
$sql_travel = 'SELECT t.rowid, t.ref, t.jour, t.heure, t.entity,';
$sql_travel .= ' t.date_creation, t.tms as date_update';
$sql_travel .= ' FROM '.MAIN_DB_PREFIX.'bookticket_travel as t';
$sql_travel .= ' WHERE t.entity IN ('.getEntity('travel').')';
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


if (empty($reshook))
{

	// Actions to build doc
	$upload_dir = $conf->bookticket->dir_output;
	$permissiontoadd = $usercancreate;
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Barcode type
	if ($action == 'setfk_barcode_type' && $createbarcode)
	{
		$result = $object->setValueFrom('fk_barcode_type', GETPOST('fk_barcode_type'), '', null, 'text', '', $user, 'BTICKET_MODIFY');
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
		exit;
	}

	// Barcode value
	if ($action == 'setbarcode' && $createbarcode)
	{
		$result = $object->check_barcode(GETPOST('barcode'), GETPOST('barcode_type_code'));

		if ($result >= 0)
		{
			$result = $object->setValueFrom('barcode', GETPOST('barcode'), '', null, 'text', '', $user, 'BTICKET_MODIFY');
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		} else {
			$langs->load("errors");
			if ($result == -1) $errors[] = 'ErrorBadBarCodeSyntax';
			elseif ($result == -2) $errors[] = 'ErrorBarCodeRequired';
			elseif ($result == -3) $errors[] = 'ErrorBarCodeAlreadyUsed';
			else $errors[] = 'FailedToValidateBarCode';

			$error++;
			setEventMessages($errors, null, 'errors');
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

		if (!$error)
		{
			$object->ref                   = $ref;

			$object->barcode_type          = GETPOST('fk_barcode_type');
			$object->barcode = GETPOST('barcode');
			// Set barcode_type_xxx from barcode_type id
			$stdobject = new GenericObject($db);
			$stdobject->element = 'bticket';
			$stdobject->barcode_type = GETPOST('fk_barcode_type');
			$result = $stdobject->fetch_barcode();
			if ($result < 0)
			{
				$error++;
				$mesg = 'Failed to get bar code type information ';
				setEventMessages($mesg.$stdobject->error, $mesg.$stdobject->errors, 'errors');
			}
			$object->barcode_type_code      = $stdobject->barcode_type_code;
			$object->barcode_type_coder     = $stdobject->barcode_type_coder;
			$object->barcode_type_label     = $stdobject->barcode_type_label;

			$object->fk_travel             	 = GETPOST('fk_travel');
			$object->fk_ship             	 = GETPOST('fk_ship');
			$object->fk_classe             	 = GETPOST('fk_classe');

			if(GETPOST('new_passenger') == 'off'){
				$object->fk_passenger            = GETPOST('fk_passenger');
			}else{
				$object_passenger->nom             	 = GETPOST('nom');
				$object_passenger->prenom            = GETPOST('prenom');
				$object_passenger->age             	 = GETPOST('age');
				$object_passenger->adresse           = GETPOST('adresse');
				$object_passenger->telephone         = GETPOST('telephone');
				$object_passenger->email             = GETPOST('email');
				$object_passenger->accompagne        = GETPOST('accompagne');
				$object_passenger->nom_enfant        = GETPOST('nom_enfant');
				$object_passenger->age_enfant        = GETPOST('age_enfant');

				$id_passenger = $object_passenger->create($user);

				$object->fk_passenger = $id_passenger;
			}



			if (!$error)
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

				$object->ref                    = $ref;
				$object->fk_travel             	 = GETPOST('fk_travel');
				$object->fk_ship             	 = GETPOST('fk_ship');
				$object->fk_classe             	 = GETPOST('fk_classe');
				$object->fk_passenger             	 = GETPOST('fk_passenger');

				$object_passenger->nom             	 = GETPOST('nom');
				$object_passenger->prenom             	 = GETPOST('prenom');
				$object_passenger->age             	 = GETPOST('age');
				$object_passenger->adresse             	 = GETPOST('adresse');
				$object_passenger->telephone             	 = GETPOST('telephone');
				$object_passenger->email             	 = GETPOST('email');
				$object_passenger->accompagne             	 = GETPOST('accompagne');
				$object_passenger->nom_enfant             	 = GETPOST('nom_enfant');
				$object_passenger->age_enfant             	 = GETPOST('age_enfant');


				$object->barcode_type = GETPOST('fk_barcode_type');
				$object->barcode = GETPOST('barcode');
				// Set barcode_type_xxx from barcode_type id
				$stdobject = new GenericObject($db);
				$stdobject->element = 'bticket';
				$stdobject->barcode_type = GETPOST('fk_barcode_type');
				$result = $stdobject->fetch_barcode();
				if ($result < 0)
				{
					$error++;
					$mesg = 'Failed to get bar code type information ';
					setEventMessages($mesg.$stdobject->error, $mesg.$stdobject->errors, 'errors');
				}
				$object->barcode_type_code      = $stdobject->barcode_type_code;
				$object->barcode_type_coder     = $stdobject->barcode_type_coder;
				$object->barcode_type_label     = $stdobject->barcode_type_label;

				if (!$error && $object->check())
				{
					if ($object->update($object->id, $user) > 0 && $object_passenger->update($object_passenger->id, $user) > 0)
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

	// Action clone object
	if ($action == 'confirm_clone' && $confirm != 'yes') { $action = ''; }
	if ($action == 'confirm_clone' && $confirm == 'yes' && $usercancreate)
	{
		if (!GETPOST('clone_content') && !GETPOST('clone_prices'))
		{
			setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
		} else {
			$db->begin();

			$originalId = $id;
			if ($object->id > 0)
			{
				$object->ref = GETPOST('clone_ref', 'alphanohtml');
				$object->id = null;
				$object->barcode = -1;

				if ($object->check())
				{
					$object->context['createfromclone'] = 'createfromclone';
					$id = $object->create($user);
					if ($id > 0)
					{

						$db->commit();
						$db->close();

						header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
						exit;
					} else {
						$id = $originalId;

						if ($object->error == 'ErrorBticketAlreadyExists')
						{
							$db->rollback();

							$refalreadyexists++;
							$action = "";

							$mesg = $langs->trans("ErrorBticketAlreadyExists", $object->ref);
							$mesg .= ' <a href="'.$_SERVER["PHP_SELF"].'?ref='.$object->ref.'">'.$langs->trans("ShowCardHere").'</a>.';
							setEventMessages($mesg, null, 'errors');
							$object->fetch($id);
						} else {
							$db->rollback();
							if (count($object->errors))
							{
								setEventMessages($object->error, $object->errors, 'errors');
								dol_print_error($db, $object->errors);
							} else {
								setEventMessages($langs->trans($object->error), null, 'errors');
								dol_print_error($db, $object->error);
							}
						}
					}

					unset($object->context['createfromclone']);
				}
			} else {
				$db->rollback();
				dol_print_error($db, $object->error);
			}
		}
	}

	// Delete a bticket
	if ($action == 'confirm_delete' && $confirm != 'yes') { $action = ''; }
	if ($action == 'confirm_delete' && $confirm == 'yes' && $usercandelete)
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

// Load object modBarCodeTicket
$res = 0;
if (!empty($conf->barcode->enabled) && !empty($conf->global->BARCODE_BTICKET_ADDON_NUM))
{
	$module = strtolower($conf->global->BARCODE_BTICKET_ADDON_NUM);
	$dirbarcode = array_merge(array('/core/modules/barcode/'), $conf->modules_parts['barcode']);
	foreach ($dirbarcode as $dirroot)
	{
		$res = dol_include_once($dirroot.$module.'.php');
		if ($res) break;
	}
	if ($res > 0)
	{
			$modBarCodeTicket = new $module();
	}
}


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
		$title = $langs->trans("NewTicket");

		$linkback = "";
		print load_fiche_titre($title, $linkback, $picto);

		print dol_get_fiche_head('');

		print '<table class="border centpercent">';

			print '<tr>';
			$tmpcode = '';
			if (!empty($modCodeBticket->code_auto)) $tmpcode = $modCodeBticket->getNextValue($object, $type);
			print '<td class="titlefieldcreate fieldrequired">'.$langs->trans("Ref").'</td><td colspan="3"><input id="ref" name="ref" class="maxwidth200" maxlength="128" value="'.dol_escape_htmltag(GETPOSTISSET('ref') ? GETPOST('ref', 'alphanohtml') : $tmpcode).'">';
			if ($refalreadyexists)
			{
				print $langs->trans("RefAlreadyExists");
			}
			print '</td></tr>';

			$showbarcode = empty($conf->barcode->enabled) ? 0 : 1;
			if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->lire_advance)) $showbarcode = 0;

			if ($showbarcode)
			{
				print '<tr><td>'.$langs->trans('BarcodeType').'</td><td>';
				if (GETPOSTISSET('fk_barcode_type')) {
					$fk_barcode_type = GETPOST('fk_barcode_type');
				} else {
					if (empty($fk_barcode_type) && !empty($conf->global->PRODUIT_DEFAULT_BARCODE_TYPE)) $fk_barcode_type = $conf->global->PRODUIT_DEFAULT_BARCODE_TYPE;
				}
				require_once DOL_DOCUMENT_ROOT.'/core/class/html.formbarcode.class.php';
				$formbarcode = new FormBarCode($db);
				print $formbarcode->selectBarcodeType($fk_barcode_type, 'fk_barcode_type', 1);
				print '</td>';
				if ($conf->browser->layout == 'phone') print '</tr><tr>';
				print '<td>'.$langs->trans("BarcodeValue").'</td><td>';
				$tmpcode = GETPOSTISSET('barcode') ? GETPOST('barcode') : $object->barcode;
				if (empty($tmpcode) && !empty($modBarCodeBticket->code_auto)) $tmpcode = $modBarCodeBticket->getNextValue($object, $type);
				print '<input class="maxwidth100" type="text" name="barcode" value="'.dol_escape_htmltag($tmpcode).'">';
				print '</td></tr>';
			}

			print "</td></tr>";

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

			// ship
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Ship").'</td>';

			$ship = '<td><select class="flat" name="fk_ship">';
			if (empty($shiprecords))
			{
				$ship .= '<option value="0">'.($langs->trans("AucuneEntree")).'</option>';
			}else{
				foreach ($shiprecords as $lines)
				{
					$ship .= '<option value="';
					$ship .= $lines->rowid;
					$ship .= '"';
					$ship .= '>';
					$ship .= $langs->trans($lines->label);
					$ship .= '</option>';
				}
			}

			$ship .= '</select>';

			print $ship;

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

		print '</table>';

		print '<hr>';

		print '<table class="border centpercent">';

			print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("InformationPassager").'</td></tr>';

			// passenger
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Passenger").'</td>';

			$passenger = '<td><select class="flat" name="fk_passenger">';
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

			// new_passenger
			print '<tr><td class="titlefieldcreate">'.$langs->trans("NouveauPassager").'</td>';
			print '<td><input type="checkbox" name="new_passenger" >';
			print '</td></tr>';

			// nom
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Nom").'</td>';
			print '<td><input name="nom" class="maxwidth300" value="'.$object_passenger->nom.'">';
			print '</td></tr>';

			// prenom
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Prenom").'</td>';
			print '<td><input name="prenom" class="maxwidth300" value="'.$object_passenger->prenom.'">';
			print '</td></tr>';

			// age
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Age").'</td>';
			print '<td><input name="age" type="number" class="maxwidth50" value="'.$object_passenger->age.'"> ANS';
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

			// accompagne
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Accompagne").'</td>';
			print '<td><input type="checkbox" name="accompagne"'.($object_passenger->accompagne == 1 ? 'checked="checked"' : '').'>';
			print '</td></tr>';

			// nom_enfant
			print '<tr><td class="titlefieldcreate">'.$langs->trans("NomEnfant").'</td>';
			print '<td><input name="nom_enfant" class="maxwidth300" value="'.$object_passenger->nom_enfant.'">';
			print '</td></tr>';

			// age_enfant
			print '<tr><td class="titlefieldcreate">'.$langs->trans("AgeEnfant").'</td>';
			print '<td><input name="age_enfant" type="number" class="maxwidth50" value="'.$object_passenger->age_enfant.'"> ANS';
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
			$titre = $langs->trans("CardTicket".$object->type);
			$picto =  'bticket';
			print dol_get_fiche_head($head, 'card', $titre, 0, $picto);


			print '<table class="border allwidth">';

			// Ref
			print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Ref").'</td><td colspan="3"><input name="ref" class="maxwidth200" maxlength="128" value="'.dol_escape_htmltag($object->ref).'"></td></tr>';

			// Barcode
			$showbarcode = empty($conf->barcode->enabled) ? 0 : 1;
			if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->lire_advance)) $showbarcode = 0;

			if ($showbarcode)
			{
				print '<tr><td>'.$langs->trans('BarcodeType').'</td><td>';
				if (GETPOSTISSET('fk_barcode_type')) {
				 	$fk_barcode_type = GETPOST('fk_barcode_type');
				} else {
					$fk_barcode_type = $object->barcode_type;
					if (empty($fk_barcode_type) && !empty($conf->global->PRODUIT_DEFAULT_BARCODE_TYPE)) $fk_barcode_type = $conf->global->PRODUIT_DEFAULT_BARCODE_TYPE;
				}
				require_once DOL_DOCUMENT_ROOT.'/core/class/html.formbarcode.class.php';
				$formbarcode = new FormBarCode($db);
				print $formbarcode->selectBarcodeType($fk_barcode_type, 'fk_barcode_type', 1);
				print '</td><td>'.$langs->trans("BarcodeValue").'</td><td>';
				$tmpcode = GETPOSTISSET('barcode') ? GETPOST('barcode') : $object->barcode;
				if (empty($tmpcode) && !empty($modBarCodeTicket->code_auto)) $tmpcode = $modBarCodeTicket->getNextValue($object, $type);
				print '<input size="40" class="maxwidthonsmartphone" type="text" name="barcode" value="'.dol_escape_htmltag($tmpcode).'">';
				print '</td></tr>';
			}

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

			// ship
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Ship").'</td>';
			$ship = '<td><select class="flat" name="fk_ship">';
			if (empty($shiprecords))
			{
				$ship .= '<option value="0">'.($langs->trans("AucuneEntree")).'</option>';
			}else{
				foreach ($shiprecords as $lines)
				{
					$ship .= '<option value="';
					$ship .= $lines->rowid;
					$ship .= '"';
					$ship .= '>';
					$ship .= $langs->trans($lines->label);
					$ship .= '</option>';
				}
			}

			$ship .= '</select>';

			print $ship;

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

			// fk_passenger
			print '<td><input name="fk_passenger" type="hidden" value="'.$object_passenger->id.'">';

			// nom
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Nom").'</td>';
			print '<td><input name="nom" class="maxwidth300" value="'.$object_passenger->nom.'">';
			print '</td></tr>';

			// prenom
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Prenom").'</td>';
			print '<td><input name="prenom" class="maxwidth300" value="'.$object_passenger->prenom.'">';
			print '</td></tr>';

			// age
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Age").'</td>';
			print '<td><input name="age" type="number" class="maxwidth50" value="'.$object_passenger->age.'"> ANS';
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

			// accompagne
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Accompagne").'</td>';
			print '<td><input type="checkbox" name="accompagne"'.($object_passenger->accompagne == 1 ? 'checked="checked"' : '').'>';
			print '</td></tr>';

			// nom_enfant
			print '<tr><td class="titlefieldcreate">'.$langs->trans("NomEnfant").'</td>';
			print '<td><input name="nom_enfant" class="maxwidth300" value="'.$object_passenger->nom_enfant.'">';
			print '</td></tr>';

			// age_enfant
			print '<tr><td class="titlefieldcreate">'.$langs->trans("AgeEnfant").'</td>';
			print '<td><input name="age_enfant" type="number" class="maxwidth50" value="'.$object_passenger->age_enfant.'"> ANS';
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
			$showbarcode = empty($conf->barcode->enabled) ? 0 : 1;

			if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->lire_advance)) $showbarcode = 0;

			$sql_t = 'SELECT DISTINCT t.rowid, t.ref, t.barcode, s.label as ship, p.nom as passenger,  c.label as classe, c.prix_standard as prix, tr.ref as travel, t.entity';
			$sql_t .= ' FROM '.MAIN_DB_PREFIX.'bookticket_bticket as t';
			$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_ship as s ON t.fk_ship = s.rowid";
			$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_passenger as p ON t.fk_passenger = p.rowid";
			$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_classe as c ON t.fk_classe = c.rowid";
			$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_travel as tr ON t.fk_travel = tr.rowid";
			$sql_t .= ' WHERE t.entity IN ('.getEntity('bticket').')';
			$sql_t .= ' AND t.rowid IN ('.$object->id.')';
			$resql_t = $db->query($sql_t);
			$obj = $db->fetch_object($resql_t);

			var_dump($obj); die;

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

			if ($showbarcode)
			{
				// Barcode type
				print '<tr><td class="nowrap">';
				print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
				print $langs->trans("BarcodeType");
				print '</td>';
				if (($action != 'editbarcodetype') && $usercancreate && $createbarcode) print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editbarcodetype&amp;id='.$object->id.'">'.img_edit($langs->trans('Edit'), 1).'</a></td>';
				print '</tr></table>';
				print '</td><td colspan="2">';
				if ($action == 'editbarcodetype' || $action == 'editbarcode')
				{
					require_once DOL_DOCUMENT_ROOT.'/core/class/html.formbarcode.class.php';
					$formbarcode = new FormBarCode($db);
				}
				if ($action == 'editbarcodetype')
				{
					print $formbarcode->formBarcodeType($_SERVER['PHP_SELF'].'?id='.$object->id, $object->barcode_type, 'fk_barcode_type');
				} else {
					$object->fetch_barcode();
					print $object->barcode_type_label ? $object->barcode_type_label : ($object->barcode ? '<div class="warning">'.$langs->trans("SetDefaultBarcodeType").'<div>' : '');
				}
				print '</td></tr>'."\n";

				// Barcode value
				print '<tr><td class="nowrap">';
				print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
				print $langs->trans("BarcodeValue");
				print '</td>';
				if (($action != 'editbarcode') && $usercancreate && $createbarcode) print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editbarcode&amp;id='.$object->id.'">'.img_edit($langs->trans('Edit'), 1).'</a></td>';
				print '</tr></table>';
				print '</td><td colspan="2">';
				if ($action == 'editbarcode')
				{
					$tmpcode = GETPOSTISSET('barcode') ? GETPOST('barcode') : $object->barcode;
					if (empty($tmpcode) && !empty($modBarCodeTicket->code_auto)) $tmpcode = $modBarCodeTicket->getNextValue($object);

					print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
					print '<input type="hidden" name="token" value="'.newToken().'">';
					print '<input type="hidden" name="action" value="setbarcode">';
					print '<input type="hidden" name="barcode_type_code" value="'.$object->barcode_type_code.'">';
					print '<input size="40" class="maxwidthonsmartphone" type="text" name="barcode" value="'.$tmpcode.'">';
					print '&nbsp;<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
					print '</form>';
				} else {
					print $object->barcode;
				}
				print '</td></tr>'."\n";
			}

			// Ref
			print '<tr>';
			print '<td class="titlefield">'.$langs->trans("Ref").'</td>';
			print '<td>';
			print $object->ref;
			print '</td></tr>';

			// Passenger
			print '<tr>';
			print '<td class="titlefield">'.$langs->trans("Passenger").'</td>';
			print '<td>';
			print $object->passenger;
			print '</td></tr>';



			// Travel
			print '<tr>';
			print '<td>';
			$htmlhelp = $langs->trans('TravelHelp');
			print $form->textwithpicto($langs->trans('Travel'), $htmlhelp);
			print '</td>';
			print '<td>';
			print $object->travel;
			print '</td>';
			print '</tr>';

			// Ship
			print '<tr>';
			print '<td>';
			$htmlhelp = $langs->trans('ShipHelp');
			print $form->textwithpicto($langs->trans('Ship'), $htmlhelp);
			print '</td>';
			print '<td>';
			print $object->ship;
			print '</td>';
			print '</tr>';

			// Classe
			print '<tr>';
			print '<td>';
			$htmlhelp = $langs->trans('ClasseHelp');
			print $form->textwithpicto($langs->trans('Classe'), $htmlhelp);
			print '</td>';
			print '<td>';
			print $object->classe;
			print '</td>';
			print '</tr>';

			// Prix
			print '<tr>';
			print '<td>';
			$htmlhelp = $langs->trans('PrixHelp');
			print $form->textwithpicto($langs->trans('Prix'), $htmlhelp);
			print '</td>';
			print '<td>';
			print $object->price.' FCFA';
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

$tmpcode = '';

$formconfirm = '';

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
print $formconfirm;

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

			if (!isset($object->no_button_copy) || $object->no_button_copy <> 1)
			{
				if (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))
				{
					print '<span id="action-clone" class="butAction">'.$langs->trans('ToClone').'</span>'."\n";
				} else {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=clone&amp;id='.$object->id.'">'.$langs->trans("ToClone").'</a>';
				}
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
					print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=delete&amp;token='.newToken().'&amp;id='.$object->id.'">'.$langs->trans("Delete").'</a>';
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


/*
 * Documents generes
 */

if ($action != 'create' && $action != 'edit' && $action != 'delete')
{
	print '<div class="fichecenter"><div class="fichehalfleft">';
	print '<a name="builddoc"></a>'; // ancre

	// Documents
	$objectref = dol_sanitizeFileName($object->ref);
	$relativepath = $comref.'/'.$objectref.'.pdf';
	if (!empty($conf->bticket->multidir_output[$object->entity])) {
		$filedir = $conf->bticket->multidir_output[$object->entity].'/'.$objectref; //Check repertories of current entities
	} else {
		$filedir = $conf->bticket->dir_output.'/'.$objectref;
	}
	$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
	$genallowed = $usercanread;
	$delallowed = $usercancreate;

	$modulepart = "bookticket";

	//print $formfile->showdocuments($modulepart, $object->ref, $filedir, $urlsource, $genallowed, $delallowed, '', 0, 0, 0, 28, 0, '', 0, '', $object->default_lang, '', $object);
	$somethingshown = $formfile->numoffiles;

	print '</div><div class="fichehalfright"><div class="ficheaddleft">';

	$MAXEVENT = 10;

	$morehtmlright = '<a href="'.DOL_URL_ROOT.'/custom/bookticket/bticket_agenda.php?id='.$object->id.'">';
	$morehtmlright .= $langs->trans("SeeAll");
	$morehtmlright .= '</a>';

	// List of actions on element
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	$formactions = new FormActions($db);
	$somethingshown = $formactions->showactions($object, 'bticket', 0, 1, '', $MAXEVENT, '', $morehtmlright); // Show all action for ticket

	print '</div></div></div>';
}

// End of page
llxFooter();
$db->close();
