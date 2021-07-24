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
 *	\file       bookticket/travelindex.php
 *	\ingroup    bookticket
 *	\brief      Home page of travel left menu
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
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/city.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/ship.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/lib/travel.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

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

// by default 'alphanohtml' (better security); hidden conf MAIN_SECURITY_ALLOW_UNSECURED_LABELS_WITH_HTML allows basic html
$label_security_check = empty($conf->global->MAIN_SECURITY_ALLOW_UNSECURED_LABELS_WITH_HTML) ? 'alphanohtml' : 'restricthtml';

if (!empty($user->socid)) $socid = $user->socid;

$object = new Travel($db);

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
	$objcanvas->getCanvas('travel', 'card', $canvas);
}

// Security check
//$result = restrictedArea($user, 'produit|service', $fieldvalue, 'travel&travel', '', '', $fieldtype);

/*
 * Actions
 */

if ($cancel) $action = '';

$usercanread = $user->rights->bookticket->travel->read;
$usercancreate = $user->rights->bookticket->travel->write;
$usercandelete = $user->rights->bookticket->travel->delete;

$parameters = array('id'=>$id, 'ref'=>$ref, 'objcanvas'=>$objcanvas);

$shiprecords = [];
$sql_ship = "SELECT s.rowid, s.ref, s.label, s.labelshort,  s.nbre_place, s.nbre_vip, s.nbre_aff, s.nbre_eco,";
$sql_ship .= " s.entity";
$sql_ship .= " FROM ".MAIN_DB_PREFIX."bookticket_ship as s";
$sql_ship .= ' WHERE s.entity IN ('.getEntity('ship').')';
$sql_ship .= ' AND s.status = 2';

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

$cityrecords = [];
$sql_city = 'SELECT c.rowid, c.label, c.labelshort, c.entity,';
$sql_city .= ' c.date_creation, c.tms as date_update';
$sql_city .= ' FROM '.MAIN_DB_PREFIX.'bookticket_city as c';
$sql_city .= ' WHERE c.entity IN ('.getEntity('city').')';
$sql_city .= ' AND c.status = 2';
$resql_city =$db->query($sql_city);
if ($resql_city)
{
	$num = $db->num_rows($resql_city);
	$i = 0;
	if ($num)
	{
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql_city);
			if ($obj)
			{
				$cityrecords[$i] = $obj;
			}
			$i++;
		}
	}
}

// Actions to build doc
$upload_dir = $conf->travel->dir_output;
$permissiontoadd = $usercancreate;
include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';


// Add a travel
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
		$ref = explode('-',$ref);
		if(GETPOST('lieu_depart') == 'Port-Gentil'){
			$ref = $ref[0].'-POG/LBV-'.$ref[2];
		}else{
			$ref = $ref[0].'-LBV/POG-'.$ref[2];
		}

		if(GETPOST('lieu_depart') == GETPOST('lieu_arrive')){
			setEventMessages($langs->trans('LieuIdentique'), null, 'errors');
			$action = "create";
			exit;
		}

		$object->ref                   = $ref;
		$object->jour            = GETPOST('jour');
		$object->heure            = GETPOST('heure');
		$object->fk_ship               = GETPOST('fk_ship');
		$object->lieu_depart           = GETPOST('lieu_depart');
		$object->lieu_arrive           = GETPOST('lieu_arrive');

		$object_ship = new Ship($db);
		$result = $object_ship->fetch($object->fk_ship);

		$object->nbre_place             = $object_ship->nbre_place;
		$object->nbre_vip             	 = $object_ship->nbre_vip;
		$object->nbre_aff             	 = $object_ship->nbre_aff;
		$object->nbre_eco             	 = $object_ship->nbre_eco;


		// Fill array 'array_options' with data from add form
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
			$object->jour             = GETPOST('jour');
			$object->heure             = GETPOST('heure');
			$object->fk_ship                = GETPOST('fk_ship');
			$object->lieu_depart            = GETPOST('lieu_depart');
			$object->lieu_arrive            = GETPOST('lieu_arrive');

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
	if ($object->status == Travel::STATUS_APPROVED)
	{
		$object->status = Travel::STATUS_LOCK;

		$db->begin();

		$verif = $object->lock($user);
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

/* Action clone object
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

					if ($object->error == 'ErrorTravelAlreadyExists')
					{
						$db->rollback();

						$refalreadyexists++;
						$action = "";

						$mesg = $langs->trans("ErrorTravelAlreadyExists", $object->ref);
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
}*/

// Delete a travel
//if ($action == 'confirm_delete' && $confirm != 'yes') { $action = ''; }
if ($action == 'delete' && $usercandelete)
{
	$result = $object->delete($user);

	if ($result > 0)
	{
		header('Location: '.DOL_URL_ROOT.'/custom/bookticket/travel_list.php?deltravel='.urlencode($object->ref));
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

$title = $langs->trans('travelCard');
$helpurl = '';
$shortlabel = dol_trunc($object->ref, 16);
$title = $langs->trans('travel')." ".$shortlabel." - ".$langs->trans('Card');
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
		$object = new Travel($db);
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


		$picto = 'travel';
		$title = $langs->trans("NewTravel");

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
		$tmpref = "DVM-TRAJET-".$code;
		print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Ref").'</td><td colspan="3"><input name="ref" class="maxwidth200" maxlength="128" value="'.dol_escape_htmltag($tmpref).'"></td></tr>';

		print '</td></tr>';

		// Jour
		print '<tr><td class="fieldrequired">'.$langs->trans("Jour").'</td><td colspan="3">';
        print '<input name="jour" type="date" class="minwidth300 maxwidth400onsmartphone" maxlength="255" value="'.dol_escape_htmltag($object->jour).'">';
		print '</td></tr>';

		// Heure
		print '<tr><td class="fieldrequired">'.$langs->trans("Heure").'</td><td colspan="3">';
        print '<input name="heure" type="time" class="minwidth300 maxwidth400onsmartphone" maxlength="255" value="'.dol_escape_htmltag($object->heure).'">';
		print '</td></tr>';


		// Ship
		print '<tr><td class="titlefieldcreate">'.$langs->trans("Ship").'</td>';

		$ship = '<td><select class="flat" name="fk_ship">';
		if (empty($shiprecords))
		{
			$ship .= '<option value="0">'.($langs->trans("AucuneEntree")).'</option>';
		}else{
			foreach ($shiprecords as $line)
			{
				$ship .= '<option value="';
				$ship .= $line->rowid;
				$ship .= '"';
				$ship .= '>';
				$ship .= $line->label;
				$ship .= '</option>';
			}
		}

		$ship .= '</select>';

		print $ship;

		print '</td>';

		print '</tr>';

		print '</table>';

		print '<hr>';

		print '<table class="border centpercent">';

			// lieu_depart
			print '<tr><td class="titlefieldcreate">'.$langs->trans("LieuDepart").'</td>';

			$city_depart = '<td><select class="flat" name="lieu_depart">';
			if (empty($cityrecords))
			{
				$city_depart .= '<option value="0">'.($langs->trans("AucuneEntree")).'</option>';
			}else{
				foreach ($cityrecords as $lines)
				{
					$city_depart .= '<option value="';
					$city_depart .= $lines->label;
					$city_depart .= '"';
					$city_depart .= '>';
					$city_depart .= $lines->label;
					$city_depart .= '</option>';
				}
			}

			$city_depart .= '</select>';

			print $city_depart;

			print '</td></tr>';

			// lieu_arrive
			print '<tr><td class="titlefieldcreate">'.$langs->trans("LieuArrive").'</td>';

			$city_arrive = '<td><select class="flat" name="lieu_arrive">';
			if (empty($cityrecords))
			{
				$city_arrive .= '<option value="0">'.($langs->trans("AucuneEntree")).'</option>';
			}else{
				foreach ($cityrecords as $lines)
				{
					$city_arrive .= '<option value="';
					$city_arrive .= $lines->label;
					$city_arrive .= '"';
					$city_arrive .= '>';
					$city_arrive .= $lines->label;
					$city_arrive .= '</option>';
				}
			}

			$city_arrive .= '</select>';

			print $city_arrive;

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
         * travel card
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


			$type = $langs->trans('travel');

			// Main official, simple, and not duplicated code
			print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="POST" name="formprod">'."\n";
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
			print '<input type="hidden" name="canvas" value="'.$object->canvas.'">';

			$head = travel_prepare_head($object);
			$titre = $langs->trans("Cardtravel".$object->type);
			$picto =  'travel';
			print dol_get_fiche_head($head, 'card', $titre, 0, $picto);


			print '<table class="border allwidth">';

			// Ref
			print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Ref").'</td><td colspan="3"><input name="ref" class="maxwidth200" maxlength="128" value="'.dol_escape_htmltag($object->ref).'" disabled></td></tr>';

			// Jour
			print '<tr><td class="fieldrequired">'.$langs->trans("Jour").'</td><td colspan="3"><input name="jour" type="date" class="minwidth300 maxwidth400onsmartphone" maxlength="255" value="'.dol_escape_htmltag($object->jour).'"></td></tr>';

			// heure
			print '<tr><td class="fieldrequired">'.$langs->trans("Heure").'</td><td colspan="3"><input name="heure" type="time" class="minwidth300 maxwidth400onsmartphone" maxlength="255" value="'.dol_escape_htmltag($object->heure).'"></td></tr>';

			// Ship
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

			// lieu_depart
			print '<tr><td class="titlefieldcreate">'.$langs->trans("LieuDepart").'</td>';

			$city_depart = '<td><select class="flat" name="lieu_depart">';
			if (empty($cityrecords))
			{
				$city_depart .= '<option value="0">'.($langs->trans("AucuneEntree")).'</option>';
			}else{
				foreach ($cityrecords as $lines)
				{
					$city_depart .= '<option value="';
					$city_depart .= $lines->rowid;
					$city_depart .= '"';
					$city_depart .= '>';
					$city_depart .= $langs->trans($lines->label);
					$city_depart .= '</option>';
				}
			}

			$city_depart .= '</select>';

			print $city_depart;

			print '</td></tr>';

			// lieu_arrive
			print '<tr><td class="titlefieldcreate">'.$langs->trans("LieuArrive").'</td>';

			$city_arrive = '<td><select class="flat" name="lieu_arrive">';
			if (empty($cityrecords))
			{
				$city_arrive .= '<option value="0">'.($langs->trans("AucuneEntree")).'</option>';
			}else{
				foreach ($cityrecords as $lines)
				{
					$city_arrive .= '<option value="';
					$city_arrive .= $lines->rowid;
					$city_arrive .= '"';
					$city_arrive .= '>';
					$city_arrive .= $langs->trans($lines->label);
					$city_arrive .= '</option>';
				}
			}

			$city_arrive .= '</select>';

			print $city_arrive;

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
			$object_ship = new Ship($db);
			$object_ship->fetch($object->fk_ship);

			$date1 = date('Y-m-d');
			$date2 = date('Y-m-d', $object->jour);

			if(($date2 < $date1) && $object->status != Travel::STATUS_LOCK){
				$object->status = Travel::STATUS_LOCK;
				$object->update($user);
			}

			$somme_billet = 0;
			$somme_penalite = 0;
			$somme_total = 0;

			$sql_t = 'SELECT DISTINCT t.rowid, t.ref, p.telephone as telephone, p.nom as nom, p.prenom as prenom, tr.ref as travel, tr.lieu_depart as lieu_depart, tr.lieu_arrive as lieu_arrive, tr.jour as depart, s.label as ship, s.ref as refship, ct.label as country, pn.prix_da, pn.prix_db, pn.prix_n, pn.prix_bp, pn.prix_c, pn.prix_ce, t.entity';
			$sql_t .= ' FROM '.MAIN_DB_PREFIX.'bookticket_bticket as t';
			$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_ship as s ON t.fk_ship = s.rowid";
			$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_passenger as p ON t.fk_passenger = p.rowid";
			$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_classe as c ON t.fk_classe = c.rowid";
			$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_travel as tr ON t.fk_travel = tr.rowid";
			$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_agence as a ON t.fk_agence = a.rowid";
			$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_penalite as pn ON t.rowid = pn.fk_bticket";
			$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as ct ON p.nationalite = ct.rowid";
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
							$somme_billet += $obj->prix;
							$somme_penalite += $obj->prix_c+$obj->prix_n+$obj->prix_bp+$obj->prix_ce+$obj->prix_da+$obj->prix_db;
						}
						$i++;
					}
				}
			}

			$somme_total = $somme_billet + $somme_penalite;

			$somme_j_billet = 0;
			$somme_j_penalite = 0;
			$somme_jour = 0;

			$sql_t = 'SELECT DISTINCT t.rowid, t.ref, p.telephone as telephone, p.nom as nom, p.prenom as prenom, tr.ref as travel, tr.lieu_depart as lieu_depart, tr.lieu_arrive as lieu_arrive, tr.jour as depart, s.label as ship, s.ref as refship, ct.label as country, pn.prix_da, pn.prix_db, pn.prix_n, pn.prix_bp, pn.prix_c, pn.prix_ce, t.entity';
			$sql_t .= ' FROM '.MAIN_DB_PREFIX.'bookticket_bticket as t';
			$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_ship as s ON t.fk_ship = s.rowid";
			$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_passenger as p ON t.fk_passenger = p.rowid";
			$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_classe as c ON t.fk_classe = c.rowid";
			$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_travel as tr ON t.fk_travel = tr.rowid";
			$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_agence as a ON t.fk_agence = a.rowid";
			$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_penalite as pn ON t.rowid = pn.fk_bticket";
			$sql_t .= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as ct ON p.nationalite = ct.rowid";
			$sql_t .= ' WHERE t.entity IN ('.getEntity('bticket').')';
			$sql_t .= ' AND tr.rowid IN ('.$object->id.')';
			$sql_t .= " AND DAY(tr.date_creation) = ( SELECT DAY( NOW() ) )";
			$sql_t .= " AND MONTH(tr.date_creation) = ( SELECT MONTH(NOW() ) )";
			$sql_t .= " AND YEAR(tr.date_creation) = ( SELECT YEAR(NOW()))";
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
							$somme_j_billet += $obj->prix;
							$somme_j_penalite += $obj->prix_c+$obj->prix_n+$obj->prix_bp+$obj->prix_ce+$obj->prix_da+$obj->prix_db;
						}
						$i++;
					}
				}
			}

			$somme_jour = $somme_j_billet + $somme_j_penalite;

			$head = travel_prepare_head($object);
			$titre = $langs->trans("CardTravel");
			$picto = 'travel';

			print dol_get_fiche_head($head, 'card', $titre, -1, $picto);

			$linkback = '<a href="'.DOL_URL_ROOT.'/custom/bookticket/travel_list.php?restore_lastsearch_values=1&type=">'.$langs->trans("BackToList").'</a>';

			$shownav = 1;
			if ($user->socid && !in_array('travel', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav = 0;

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
				print dol_print_date($object->jour, 'day', 'tzuser');
				print '</td></tr>';

				// heure
				print '<tr>';
				print '<td class="titlefield">'.$langs->trans("Heure").'</td>';
				print '<td>';
				print $object->heure;
				print '</td></tr>';


				// LieuDepart
				print '<tr>';
				print '<td class="titlefield">'.$langs->trans("LieuDepart").'</td>';
				print '<td>';
				print $object->lieu_depart;
				print '</td></tr>';

				// LieuArrive
				print '<tr>';
				print '<td class="titlefield">'.$langs->trans("LieuArrive").'</td>';
				print '<td>';
				print $object->lieu_arrive;
				print '</td></tr>';



				// NbrePlace
				print '<tr>';
				print '<td>';
				$htmlhelp = $langs->trans('NbrePlaceHelp');
				$htmlhelp .= '<br>'.$langs->trans("NbrePlaceHelp");
				print $form->textwithpicto($langs->trans('NbrePlaceDispo'), $htmlhelp);
				print '</td>';
				print '<td>';
				print $object->nbre_place." / ".$object_ship->nbre_place;
				print '</td>';
				print '</tr>';

				// NbreVip
				print '<tr>';
				print '<td>';
				$htmlhelp = $langs->trans('NbreVipHelp');
				$htmlhelp .= '<br>'.$langs->trans("NbreVipHelp");
				print $form->textwithpicto($langs->trans('NbreVipDispo'), $htmlhelp);
				print '</td>';
				print '<td>';
				print $object->nbre_vip." / ".$object_ship->nbre_vip;
				print '</td>';
				print '</tr>';

				// NbreAff
				print '<tr>';
				print '<td>';
				$htmlhelp = $langs->trans('NbreAffHelp');
				$htmlhelp .= '<br>'.$langs->trans("NbreAffHelp");
				print $form->textwithpicto($langs->trans('NbreAffDispo'), $htmlhelp);
				print '</td>';
				print '<td>';
				print $object->nbre_aff." / ".$object_ship->nbre_aff;
				print '</td>';
				print '</tr>';

				// NbreEco
				print '<tr>';
				print '<td>';
				$htmlhelp = $langs->trans('NbreEcoHelp');
				$htmlhelp .= '<br>'.$langs->trans("NbreEcoHelp");
				print $form->textwithpicto($langs->trans('NbreEcoDispo'), $htmlhelp);
				print '</td>';
				print '<td>';
				print $object->nbre_eco." / ".$object_ship->nbre_eco;
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

				print '<tr>';
				print '<td>'.$langs->trans('VenteGlobaleVoyage').'</td>';
				print '<td> Billets : '.$somme_billet.' XAF</td>';
				print '<td> Pénalités : '.$somme_penalite.' XAF</td>';
				print '<td> Total : '.$somme_total.' XAF</td>';
				print '</tr>';

				print '<tr>';
				print '<td>'.$langs->trans('VenteJour').'</td>';
				print '<td> Billets : '.$somme_j_billet.' XAF</td>';
				print '<td> Pénlités : '.$somme_j_penalite.' XAF</td>';
				print '<td> Total : '.$somme_jour.' XAF</td>';
				print '</tr>';

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


		if ($usercancreate &&  $object->status == Travel::STATUS_APPROVED)
		{
			if (!isset($object->no_button_edit) || $object->no_button_edit <> 1) print '<a class="butAction" href="'.DOL_URL_ROOT.'/custom/bookticket/bticket_card.php?action=create&travel='.$object->id.'">'.$langs->trans('NewBTicket').'</a>';

			if (!isset($object->no_button_edit) || $object->no_button_edit <> 1) print '<a class="butAction" href="'.DOL_URL_ROOT.'/custom/bookticket/reservation_card.php?action=create&travel='.$object->id.'">'.$langs->trans('NewReservation').'</a>';

		}

		if ($usercancreate && $object->status != Travel::STATUS_LOCK)
		{
			if (!isset($object->no_button_edit) || $object->no_button_edit <> 1) print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&amp;id='.$object->id.'">'.$langs->trans("Modify").'</a>';
		}

		if ($usercancreate && $object->status == Travel::STATUS_DRAFT)		// If draft
		{
			print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=valid" class="butAction">'.$langs->trans("Approve").'</a>';
		}

		if ($usercancreate &&  ($object->status == Travel::STATUS_APPROVED || $object->status == Travel::STATUS_LOCK))		// If draft
		{
			print '<a href="document.php?id='.$object->id.'&type=travel" class="butAction">'.$langs->trans("PRINT").'</a>';
		}

		if ($usercancreate && $object->status == Travel::STATUS_APPROVED)		// If draft
		{
			print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=lock" class="butAction">'.$langs->trans("CLOTURER").'</a>';
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
