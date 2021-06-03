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
 *	\file       bookticket/classeindex.php
 *	\ingroup    bookticket
 *	\brief      Home page of classe left menu
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
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/lib/classe.lib.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/classe.class.php';
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

// by default 'alphanohtml' (better security); hidden conf MAIN_SECURITY_ALLOW_UNSECURED_LABELS_WITH_HTML allows basic html
$label_security_check = empty($conf->global->MAIN_SECURITY_ALLOW_UNSECURED_LABELS_WITH_HTML) ? 'alphanohtml' : 'restricthtml';

if (!empty($user->socid)) $socid = $user->socid;

$object = new Classe($db);

if ($id > 0 || !empty($ref))
{
	$result = $object->fetch($id, $ref);

	if (!empty($conf->classe->enabled)) $upload_dir = $conf->classe->multidir_output[$object->entity].'/'.get_exdir(0, 0, 0, 0, $object, 'classe').dol_sanitizeFileName($object->ref);
	elseif (!empty($conf->service->enabled)) $upload_dir = $conf->service->multidir_output[$object->entity].'/'.get_exdir(0, 0, 0, 0, $object, 'classe').dol_sanitizeFileName($object->ref);

	if (!empty($conf->global->Classe_USE_OLD_PATH_FOR_PHOTO))    // For backward compatiblity, we scan also old dirs
	{
		if (!empty($conf->classe->enabled)) $upload_dirold = $conf->classe->multidir_output[$object->entity].'/'.substr(substr("000".$object->id, -2), 1, 1).'/'.substr(substr("000".$object->id, -2), 0, 1).'/'.$object->id."/photos";
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
	$objcanvas->getCanvas('classe', 'card', $canvas);
}

// Security check
//$result = restrictedArea($user, 'classe');

/*
 * Actions
 */

if ($cancel) $action = '';

$usercanread = $user->rights->bookticket->classe->read;
$usercancreate = $user->rights->bookticket->classe->write;
$usercandelete = $user->rights->bookticket->classe->delete;

$parameters = array('id'=>$id, 'ref'=>$ref, 'objcanvas'=>$objcanvas);

// Actions to build doc
$upload_dir = $conf->bookticket->dir_output;
$permissiontoadd = $usercancreate;
include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';


// Add a classe
if ($action == 'add' && $usercancreate)
{
	$error = 0;

	if (!GETPOST('label', $label_security_check))
	{
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Label')), null, 'errors');
		$action = "create";
		$error++;
	}
	if (empty($ref))
	{
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentities('Ref')), null, 'errors');
		$action = "create";
		$error++;
	}

	if (!$error)
	{
		$object->ref                   = $ref;
		$object->label                 = GETPOST('label', $label_security_check);
		$object->labelshort            = GETPOST('labelshort');
		$object->prix_standard         = GETPOST('prix_standard');
		$object->prix_enfant           = GETPOST('prix_enfant');
		$object->prix_enf_stand        = GETPOST('prix_enf_stand');
		$object->kilo_bagage           = GETPOST('kilo_bagage');


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

// Update a classe
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
			$object->label                  = GETPOST('label', $label_security_check);
			$object->labelshort             = GETPOST('labelshort');
			$object->prix_standard          = GETPOST('prix_standard');
			$object->prix_enfant            = GETPOST('prix_enfant');
			$object->prix_enf_stand         = GETPOST('prix_enf_stand');
			$object->kilo_bagage            = GETPOST('kilo_bagage');

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
				else setEventMessages($langs->trans("ErrorClasseBadRefOrLabel"), null, 'errors');
				$action = 'edit';
			}
		}
	}
}

//Approve
if($action == 'valid' && $usercancreate){

	$object->fetch($id);

	// If status is waiting approval and approver is also user
	if ($object->status == Classe::STATUS_DRAFT)
	{
		$object->status = Classe::STATUS_APPROVED;

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

					if ($object->error == 'ErrorClasseAlreadyExists')
					{
						$db->rollback();

						$refalreadyexists++;
						$action = "";

						$mesg = $langs->trans("ErrorClasseAlreadyExists", $object->ref);
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

// Delete a classe
//if ($action == 'confirm_delete' && $confirm != 'yes') { $action = ''; }
if ($action == 'delete' && $usercandelete)
{
	$result = $object->delete($user);

	if ($result > 0)
	{
		header('Location: '.DOL_URL_ROOT.'/custom/bookticket/classe_list.php?delclasse='.urlencode($object->ref));
		exit;
	} else {
		setEventMessages($langs->trans($object->error), null, 'errors');
		$reload = 0;
		$action = '';
	}
}


// Add classe into object
if ($object->id > 0 && $action == 'addin')
{
	$thirpdartyid = 0;
}



/*
 * View
 */

$title = $langs->trans('ClasseCard');
$helpurl = '';
$shortlabel = dol_trunc($object->label, 16);
$title = $langs->trans('Classe')." ".$shortlabel." - ".$langs->trans('Card');
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
		$object = new Classe($db);
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


		$picto = 'classe';
		$title = $langs->trans("NewClasse");

		$linkback = "";
		print load_fiche_titre($title, $linkback, $picto);

		print dol_get_fiche_head('');

		print '<table class="border centpercent">';

		print '<tr>';
		$tmpcode = '';

		if ($refalreadyexists)
		{
			print $langs->trans("RefAlreadyExists");
		}else{
			// Ref
			$number = "0123456789";
			$code = substr(str_shuffle(str_repeat($number, 6)), 0, 6);
			$tmpref = "DVM-VG-".$code;
			print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Ref").'</td><td colspan="3"><input name="ref" class="maxwidth200" maxlength="128" value="'.dol_escape_htmltag($tmpref).'"></td></tr>';
		}
		print '</td></tr>';

		// Label
		print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td colspan="3"><input name="label" class="minwidth300 maxwidth400onsmartphone" maxlength="255" value="'.dol_escape_htmltag(GETPOST('label', $label_security_check)).'"></td></tr>';

		// Labelshort
		print '<tr><td class="fieldrequired">'.$langs->trans("Labelshort").'</td><td colspan="3"><input name="labelshort" class="minwidth300 maxwidth400onsmartphone" maxlength="255" value="'.dol_escape_htmltag(GETPOST('labelshort')).'"></td></tr>';

		print '</table>';

		print '<hr>';

		print '<table class="border centpercent">';

			// prix_standard
			print '<tr><td class="titlefieldcreate">'.$langs->trans("PrixStandard").'</td>';
			print '<td><input name="prix_standard" class="maxwidth50" value="'.$object->prix_standard.'"> FCFA';
			print '</td></tr>';

			// prix_enfant
			print '<tr><td class="titlefieldcreate">'.$langs->trans("PrixEnfant").'</td>';
			print '<td><input name="prix_enfant" class="maxwidth50" value="'.$object->prix_enfant.'"> FCFA';
			print '</td></tr>';

			// prix_enf_stand
			print '<tr><td class="titlefieldcreate">'.$langs->trans("PrixEnfStand").'</td>';
			print '<td><input name="prix_enf_stand" class="maxwidth50" value="'.$object->prix_enf_stand.'"> FCFA';
			print '</td></tr>';

			// kilo_bagage
			print '<tr><td class="titlefieldcreate">'.$langs->trans("KiloBagage").'</td>';
			print '<td><input name="kilo_bagage" class="maxwidth50" value="'.$object->kilo_bagage.'"> Kg';
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
         * classe card
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


			$type = $langs->trans('classe');

			// Main official, simple, and not duplicated code
			print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="POST" name="formprod">'."\n";
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
			print '<input type="hidden" name="canvas" value="'.$object->canvas.'">';

			$head = classe_prepare_head($object);
			$titre = $langs->trans("CardClasse".$object->type);
			$picto =  'classe';
			print dol_get_fiche_head($head, 'card', $titre, 0, $picto);


			print '<table class="border allwidth">';

			// Ref
			print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Ref").'</td><td colspan="3"><input name="ref" class="maxwidth200" maxlength="128" value="'.dol_escape_htmltag($object->ref).'"></td></tr>';

			// Label
			print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td colspan="3"><input name="label" class="minwidth300 maxwidth400onsmartphone" maxlength="255" value="'.dol_escape_htmltag($object->label).'"></td></tr>';

			// Labelshort
			print '<tr><td class="fieldrequired">'.$langs->trans("Labelshort").'</td><td colspan="3"><input name="labelshort" class="minwidth300 maxwidth400onsmartphone" maxlength="255" value="'.dol_escape_htmltag($object->labelshort).'"></td></tr>';

			// prix_standard
			print '<tr><td class="titlefieldcreate">'.$langs->trans("PrixStandard").'</td>';
			print '<td><input name="prix_standard" class="maxwidth50" value="'.$object->prix_standard.'"> FCFA';
			print '</td></tr>';

			// prix_enfant
			print '<tr><td class="titlefieldcreate">'.$langs->trans("PrixEnfant").'</td>';
			print '<td><input name="prix_enfant" class="maxwidth50" value="'.$object->prix_enfant.'"> FCFA';
			print '</td></tr>';

			// prix_enf_stand
			print '<tr><td class="titlefieldcreate">'.$langs->trans("PrixEnfStand").'</td>';
			print '<td><input name="prix_enf_stand" class="maxwidth50" value="'.$object->prix_enf_stand.'"> FCFA';
			print '</td></tr>';

			// kilo_bagage
			print '<tr><td class="titlefieldcreate">'.$langs->trans("KiloBagage").'</td>';
			print '<td><input name="kilo_bagage" class="maxwidth50" value="'.$object->kilo_bagage.'"> Kg';
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
			$head = classe_prepare_head($object);
			$titre = $langs->trans("CardClasse");
			$picto = 'classe';

			print dol_get_fiche_head($head, 'card', $titre, -1, $picto);

			$linkback = '<a href="'.DOL_URL_ROOT.'/custom/bookticket/classe_list.php?restore_lastsearch_values=1&type=">'.$langs->trans("BackToList").'</a>';

			$shownav = 1;
			if ($user->socid && !in_array('classe', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav = 0;

			dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref');


			print '<div class="fichecenter">';
				print '<div class="fichehalfleft">';
				print '<div class="underbanner clearboth"></div>';

				print '<table class="border tableforfield centpercent">';
				print '<tbody>';

				// Label
				print '<tr>';
				print '<td class="titlefield">'.$langs->trans("Label").'</td>';
				print '<td>';
				print $object->label;
				print '</td></tr>';

				// Labelshort
				print '<tr>';
				print '<td class="titlefield">'.$langs->trans("Labelshort").'</td>';
				print '<td>';
				print $object->labelshort;
				print '</td></tr>';



				// prix_standard
				print '<tr>';
				print '<td>';
				$htmlhelp = $langs->trans('PrixStandardHelp');
				print $form->textwithpicto($langs->trans('PrixStandard'), $htmlhelp);
				print '</td>';
				print '<td>';
				print $object->prix_standard." FCFA";
				print '</td>';
				print '</tr>';

				// prix_enfant
				print '<tr>';
				print '<td>';
				$htmlhelp = $langs->trans('PrixEnfantHelp');
				print $form->textwithpicto($langs->trans('PrixEnfant'), $htmlhelp);
				print '</td>';
				print '<td>';
				print $object->prix_enfant." FCFA";
				print '</td>';
				print '</tr>';

				// prix_enf_stand
				print '<tr>';
				print '<td>';
				$htmlhelp = $langs->trans('PrixEnfStandHelp');
				print $form->textwithpicto($langs->trans('PrixEnfStand'), $htmlhelp);
				print '</td>';
				print '<td>';
				print $object->prix_enf_stand." FCFA";
				print '</td>';
				print '</tr>';

				// kilo_bagage
				print '<tr>';
				print '<td>';
				$htmlhelp = $langs->trans('KiloBagageHelp');
				print $form->textwithpicto($langs->trans('KiloBagage'), $htmlhelp);
				print '</td>';
				print '<td>';
				print $object->kilo_bagage." Kg";
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


/*$formconfirm = '';

// Confirm delete classe
if (($action == 'delete' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile)))	// Output when action = clone if jmobile or no js
	|| (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile)))							// Always output when not jmobile nor js
{
	$formconfirm = $form->formconfirm("card.php?id=".$object->id, $langs->trans("DeleteClasse"), $langs->trans("ConfirmDeleteClasse"), "confirm_delete", '', 0, "action-delete");
}

// Clone confirmation
if (($action == 'clone' && (empty($conf->use_javascript_ajax) || !empty($conf->dol_use_jmobile)))		// Output when action = clone if jmobile or no js
	|| (!empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile)))							// Always output when not jmobile nor js
{
	// Define confirmation messages
	$formquestionclone = array(
		'text' => $langs->trans("ConfirmClone"),
		array('type' => 'text', 'name' => 'clone_ref', 'label' => $langs->trans("NewRefForClone"), 'value' => empty($tmpcode) ? $langs->trans("CopyOf").' '.$object->ref : $tmpcode, 'size'=>24),
		array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneContentClasse"), 'value' => 1),
	);


	$formconfirm .= $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneClasse', $object->ref), 'confirm_clone', $formquestionclone, 'yes', 'action-clone', 350, 600);
}
;
// Print form confirm
print $formconfirm;*/

/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */
if ($action != 'create' && $action != 'edit')
{
	print "\n".'<div class="tabsAction">'."\n";

	$parameters = array();


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

		if ($usercancreate && $object->status == Classe::STATUS_DRAFT)		// If draft
		{
			print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=valid" class="butAction">'.$langs->trans("Approve").'</a>';
		}

		if ($usercandelete)
		{
			if (!isset($object->no_button_delete) || $object->no_button_delete <> 1)
			{
				print '<a class="butActionDelete" onclick="return confirm(\'Voulez-vous vraiment supprimer cette classe ! \');" href="'.$_SERVER["PHP_SELF"].'?action=delete&amp;token='.newToken().'&amp;id='.$object->id.'">'.$langs->trans("Delete").'</a>';
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("ClasseIsUsed").'">'.$langs->trans("Delete").'</a>';
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
