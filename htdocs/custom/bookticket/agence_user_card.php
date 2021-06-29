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
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/agence_user.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/travel.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/passenger.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/classe.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/agence.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/lib/agence_user.lib.php';
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

$object = new AgenceUser($db);
$object_passenger = new Passenger($db);
$object_travel = new Travel($db);


// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$canvas = !empty($object->canvas) ? $object->canvas : GETPOST("canvas");
$objcanvas = null;
if (!empty($canvas))
{
	require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('agence_user', 'card', $canvas);
}

// Security check
//$result = restrictedArea($user, 'agence_user');

/*
 * Actions
 */

if ($cancel) $action = '';

$usercanread = $user->rights->bookticket->agence_user->read;
$usercancreate = $user->rights->bookticket->agence_user->write;
$usercandelete = $user->rights->bookticket->agence_user->delete;

$agencerecords = [];
$sql_agence = 'SELECT a.rowid, a.label, a.labelshort, a.ville, a.entity,';
$sql_agence .= ' a.date_creation, a.tms as date_update';
$sql_agence .= ' FROM '.MAIN_DB_PREFIX.'bookticket_agence as a';
$sql_agence .= ' WHERE a.entity IN ('.getEntity('agence').')';
$sql_agence .= ' AND a.status = 2';
$resql_agence =$db->query($sql_agence);
if ($resql_agence)
{
	$num = $db->num_rows($resql_agence);
	$i = 0;
	if ($num)
	{
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql_agence);
			if ($obj)
			{
				$agencerecords[$i] = $obj;
			}
			$i++;
		}
	}
}

$userrecords = [];
$sql_passenger = 'SELECT u.rowid, u.lastname, u.firstname';
$sql_passenger .= ' FROM '.MAIN_DB_PREFIX.'user as u';
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
				$userrecords[$i] = $obj;
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


// Add a agence_user
if ($action == 'add' && $usercancreate)
{
	$error = 0;

	if (!$error)
	{

		$object->fk_agence             	 = GETPOST('fk_agence');
		$object->fk_user             	 = GETPOST('fk_user');

		$object->status = AgenceUser::STATUS_APPROVED;

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

// Update a agence_user
if ($action == 'update' && $usercancreate)
{
	if (GETPOST('cancel', 'alpha'))
	{
		$action = '';
	} else {
		if ($object->id > 0)
		{
			$object->oldcopy = clone $object;

			$$object->fk_agence             	 = GETPOST('fk_agence');
			$object->fk_user             	 = GETPOST('fk_user');

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
				else setEventMessages($langs->trans("ErrorAgenceUserBadRefOrLabel"), null, 'errors');
				$action = 'edit';
			}
		}
	}
}

// Delete a agence_user
//if ($action == 'confirm_delete' && $confirm != 'yes') { $action = ''; }
if ($action == 'delete' && $usercandelete)
{
	$result = $object->delete($user);

	if ($result > 0)
	{
		header('Location: '.DOL_URL_ROOT.'/custom/bookticket/agence_user_list.php?type='.$object->type.'&delagence_user='.urlencode($object->ref));
		exit;
	} else {
		setEventMessages($langs->trans($object->error), null, 'errors');
		$reload = 0;
		$action = '';
	}
}


// Add agence_user into object
if ($object->id > 0 && $action == 'addin')
{
	$thirpdartyid = 0;
}


/*
 * View
 */

$title = $langs->trans('AffectationCard');
$helpurl = '';
$shortlabel = dol_trunc($object->label, 16);
$title = $langs->trans('Affectation')." ".$shortlabel." - ".$langs->trans('Card');
$helpurl = 'EN:Module_Ticket|FR:Module_Ticket|ES:M&oacute;dulo_Ticket';

llxHeader('', $title, $helpurl);

$form = new Form($db);
$formfile = new FormFile($db);
$formcompany = new FormCompany($db);

// Load object modBarCodeTicket
$res = 0;


if (is_object($objcanvas) && $objcanvas->displayCanvasExists($action)) {
	// -----------------------------------------
	// When used with CANVAS
	// -----------------------------------------
	if (empty($object->error) && $id)
	{
		$object = new AgenceUser($db);
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
		if (!empty($modCodeAgenceUser->code_auto))
			print '<input type="hidden" name="code_auto" value="1">';
		if (!empty($modBarCodeAgenceUser->code_auto))
			print '<input type="hidden" name="barcode_auto" value="1">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';


		$picto = 'agence_user';
		$title = $langs->trans("NewAffectation");

		$linkback = "";
		print load_fiche_titre($title, $linkback, $picto);

		print dol_get_fiche_head('');

		print '<table class="border centpercent">';



			// user
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Users").'</td>';

			$user = '<td><select class="flat" name="fk_user">';
			if (empty($userrecords))
			{
				$user .= '<option value="0">'.($langs->trans("AucuneEntree")).'</option>';
			}else{
				foreach ($userrecords as $lines)
				{
					$user .= '<option value="';
					$user .= $lines->rowid;
					$user .= '"';
					$user .= '>';
					$user .= $langs->trans($lines->lastname).' '.$langs->trans($lines->firstname);
					$user .= '</option>';
				}
			}

			$user .= '</select>';

			print $user;

			print '</td></tr>';

			// agence
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Agence").'</td>';

			$agence = '<td><select class="flat" name="fk_agence">';
			if (empty($agencerecords))
			{
				$agence .= '<option value="0">'.($langs->trans("AucuneEntree")).'</option>';
			}else{
				foreach ($agencerecords as $lines)
				{
					$agence .= '<option value="';
					$agence .= $lines->rowid;
					$agence .= '"';
					$agence .= '>';
					$agence .= $langs->trans($lines->label);
					$agence .= '</option>';
				}
			}

			$agence .= '</select>';

			print $agence;

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
         * agence_user card
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

			$head = agence_user_prepare_head($object);
			$titre = $langs->trans("CardAffectation");
			$picto =  'agence_user';

			print dol_get_fiche_head($head, 'card', $titre, 0, $picto);


			print '<table class="border allwidth">';

			// user
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Users").'</td>';

			$user = '<td><select class="flat" name="fk_user">';
			if (empty($userrecords))
			{
				$user .= '<option value="0">'.($langs->trans("AucuneEntree")).'</option>';
			}else{
				foreach ($userrecords as $lines)
				{
					$user .= '<option value="';
					$user .= $lines->rowid;
					$user .= '"';
					$user .= '>';
					$user .= $langs->trans($lines->lastname).' '.$langs->trans($lines->firstname);
					$user .= '</option>';
				}
			}

			$user .= '</select>';

			print $user;

			print '</td></tr>';

			// agence
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Agence").'</td>';

			$agence = '<td><select class="flat" name="fk_agence">';
			if (empty($agencerecords))
			{
				$agence .= '<option value="0">'.($langs->trans("AucuneEntree")).'</option>';
			}else{
				foreach ($agencerecords as $lines)
				{
					$agence .= '<option value="';
					$agence .= $lines->rowid;
					$agence .= '"';
					$agence .= '>';
					$agence .= $langs->trans($lines->label);
					$agence .= '</option>';
				}
			}

			$agence .= '</select>';

			print $agence;

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

			$sql_a = 'SELECT DISTINCT au.rowid, u.lastname, u.firstname,  a.label';
			$sql_a .= ' FROM '.MAIN_DB_PREFIX.'bookticket_agence_user as au';
			$sql_a .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_agence as a ON au.fk_agence = a.rowid";
			$sql_a .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON au.fk_user = u.rowid";
			$sql_a .= ' WHERE a.rowid IN ('.$object->fk_agence.')';
			$sql_a .= ' AND u.rowid IN ('.$object->fk_user.')';
			$resql_a = $db->query($sql_a);
			$obj = $db->fetch_object($resql_a);



			$head = agence_user_prepare_head($object);
			$titre = $langs->trans("CardAffectation");
			$picto = 'agence_user';

			print dol_get_fiche_head($head, 'card', $titre, -1, $picto);

			$linkback = '<a href="'.DOL_URL_ROOT.'/custom/bookticket/agence_user_list.php?restore_lastsearch_values=1&type=">'.$langs->trans("BackToList").'</a>';

			$shownav = 1;
			if ($user->socid && !in_array('agence_user', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav = 0;

			dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref');

			print '<div class="fichecenter">';
			print '<div class="fichehalfleft">';
			print '<div class="underbanner clearboth"></div>';

			print '<table class="border tableforfield centpercent">';
			print '<tbody>';

			// User
			print '<tr>';
			print '<td class="titlefield">'.$langs->trans("User").'</td>';
			print '<td>';
			print $obj->lastname.' '.$obj->firstname;
			print '</td></tr>';





			// Agence
			print '<tr>';
			print '<td>';
			print $form->textwithpicto($langs->trans('Agence'), $htmlhelp);
			print '</td>';
			print '<td>';
			print $obj->label;
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
