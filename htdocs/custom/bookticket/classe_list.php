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

require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/classe.class.php';

// Load translation files required by the page
$langs->loadLangs('bookticket');

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');

$sall = trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$search_ref = GETPOST("search_ref", 'alpha');
$search_label = GETPOST("search_label", 'alpha');
$search_labelshort = GETPOST("search_labelshort", 'alpha');
$search_finished = GETPOST("search_finished", 'int');
$optioncss = GETPOST('optioncss', 'alpha');
$type = GETPOST("type", "int");

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) { $page = 0; }     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = "c.ref";
if (!$sortorder) $sortorder = "ASC";

// Initialize context for list
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'classelist';

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array of hooks
$object = new Classe($db);
$form = new Form($db);

if (empty($action)) $action = 'list';

// Security check
//$result = restrictedArea($user, 'classe', '', '', '', '', '', 0);

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'c.ref'=>"Ref",
	'c.label'=>"ClasseLabel",
	'c.labelshort'=>"ClasseLabelShort",
);

//$isInEEC = isInEEC($mysoc);

// Definition of fields for lists
$arrayfields = array(
	'c.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	'c.label'=>array('label'=>$langs->trans("Label"), 'checked'=>1, 'position'=>10),
	'c.labelshort'=>array('label'=>$langs->trans("LabelShort"), 'checked'=>1, 'position'=>20),
	'c.prix_standard'=>array('label'=>$langs->trans('PrixStandard'), 'checked'=>1,  'position'=>30),
	'c.prix_enf_por'=>array('label'=>$langs->trans("PrixEnfPor"), 'checked'=>1,  'position'=>52),
	'c.prix_enf_acc'=>array('label'=>$langs->trans("PrixEnfAcc"), 'checked'=>1,  'position'=>53),
	'c.prix_enf_devn'=>array('label'=>$langs->trans("PrixEnfDVM"), 'checked'=>1,  'position'=>53),
	'c.kilo_bagage'=>array('label'=>$langs->trans("KiloBagage"), 'checked'=>1,  'position'=>54),
	'c.date_creation'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),

);


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }

$parameters = array();

$rightskey = 'classe';

// Selection of new fields
include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
	$sall = "";
	$search_ref = "";
	$search_label = "";
	$search_labelshort = "";
	$search_finished = ''; // There is 2 types of list: a list of product and a list of services. No list with both. So when we clear search criteria, we must keep the filter on type.

	$search_array_options = array();
}

// Mass actions
$objectclass = 'Classe';

$permissiontoread = $user->rights->bookticket->{$rightskey}->read;
$permissiontodelete = $user->rights->bookticket->{$rightskey}->delete;
$uploaddir = $conf->bookticket->dir_output;
include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';



/*
 * View
 */

$title = $langs->trans("Classes");

$texte = $langs->trans("Classes");


$sql = 'SELECT DISTINCT c.rowid, c.ref, c.label, c.labelshort, c.prix_standard, c.prix_enf_por, c.prix_enf_acc, c.prix_enf_dvm, c.kilo_bagage, c.entity,';
$sql .= ' c.date_creation, c.tms as date_update';

// Add fields from hooks
$parameters = array();
$sql .= ' FROM '.MAIN_DB_PREFIX.'bookticket_classe as c';
$sql .= ' WHERE c.entity IN ('.getEntity('classe').')';

if ($search_ref)     $sql .= natural_search('c.ref', $search_ref);
if ($search_label)   $sql .= natural_search('c.label', $search_label);
if ($search_labelshort) $sql .= natural_search('c.labelshort', $search_labelshort);

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);
$resql = $db->query($sql);

if ($resql)
{
	$num = $db->num_rows($resql);

	$arrayofselected = is_array($toselect) ? $toselect : array();

	if ($num == 1 && !empty($conf->global->MAIN_SEARCH_DIRECT_OPEN_IF_ONLY_ONE) && $sall)
	{
		$obj = $db->fetch_object($resql);
		$id = $obj->rowid;
		header("Location: ".DOL_URL_ROOT.'/custom/bookticket/classe_card.php?id='.$id);
		exit;
	}

	$helpurl = '';
	$helpurl = 'EN:Module_Bookticket|FR:Module_Bookticket|ES:M&oacute;dulo_Bookticket';

    llxHeader('', $title, $helpurl, '', 0, 0, "", "", $paramsCat);


	// Displays classe removal confirmation
	if (GETPOST('delclasse')) {
		setEventMessages($langs->trans("ClasseDeleted", GETPOST('delclasse')), null, 'mesgs');
	}

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
	if ($sall) $param .= "&sall=".urlencode($sall);
	if ($search_ref) $param = "&search_ref=".urlencode($search_ref);
	if ($search_label) $param .= "&search_label=".urlencode($search_label);
	if ($search_labelshort) $param .= "&search_labelshort=".urlencode($search_labelshort);

	// List of mass actions available
	$arrayofmassactions = array(
		'generate_doc'=>$langs->trans("ReGeneratePDF"),
		//'builddoc'=>$langs->trans("PDFMerge"),
		//'presend'=>$langs->trans("SendByMail"),
	);
	if ($user->rights->{$rightskey}->supprimer) $arrayofmassactions['predelete'] = "<span class='fa fa-trash paddingrightonly'></span>".$langs->trans("Delete");
	if (in_array($massaction, array('presend', 'predelete'))) $arrayofmassactions = array();
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	$newcardbutton = '';
	$perm = $user->rights->bookticket->classe->write;
	$params = array();
	$params['forcenohideoftext'] = 1;
	$newcardbutton .= dolGetButtonTitle($langs->trans('NewClasse'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/custom/bookticket/classe_card.php?action=create&type=0', '', $perm, $params);
	$label = 'NewClasse';

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formulaire">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	//print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="type" value="'.$type.'">';

	$picto = 'classe';

	print_barre_liste($texte, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, $picto, 0, $newcardbutton, '', $limit, 0, 0, 1);

	$topicmail = "Information";
	$modelmail = "classe";
	$objecttmp = new Classe($db);
	$trackid = 'classe'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';


	if ($sall)
	{
		foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall).join(', ', $fieldstosearchall).'</div>';
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
	if ($massactionbutton) $selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	// Lines with input filters
	print '<tr class="liste_titre_filter">';
	if (!empty($arrayfields['c.ref']['checked']))
	{
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" name="search_ref" size="8" value="'.dol_escape_htmltag($search_ref).'">';
		print '</td>';
	}
	if (!empty($arrayfields['c.label']['checked']))
	{
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" name="search_label" size="12" value="'.dol_escape_htmltag($search_label).'">';
		print '</td>';
	}

	if (!empty($arrayfields['c.labelshort']['checked']))
	{
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" name="search_labelshort" size="12" value="'.dol_escape_htmltag($search_labelshort).'">';
		print '</td>';
	}



	// prix_standard
	if (!empty($arrayfields['c.prix_standard']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}

	// prix_enfant
	if (!empty($arrayfields['c.prix_enf_por']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}

	// prix_enf_stand
	if (!empty($arrayfields['c.prix_enf_acc']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}

	// prix_enf_stand
	if (!empty($arrayfields['c.prix_enf_dvm']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}

	// kilo_bagage
	if (!empty($arrayfields['c.kilo_bagage']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}

	// Date creation
	if (!empty($arrayfields['c.date_creation']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}

	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';

	print '</tr>';

	print '<tr class="liste_titre">';
	if (!empty($arrayfields['c.ref']['checked'])) {
		print_liste_field_titre($arrayfields['c.ref']['label'], $_SERVER["PHP_SELF"], "c.ref", "", $param, "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['c.label']['checked'])) {
		print_liste_field_titre($arrayfields['c.label']['label'], $_SERVER["PHP_SELF"], "c.label", "", $param, "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['c.labelshort']['checked'])) {
		print_liste_field_titre($arrayfields['c.labelshort']['label'], $_SERVER["PHP_SELF"], "c.labelshort", "", $param, "", $sortfield, $sortorder);
	}

	if (!empty($arrayfields['c.prix_standard']['checked']))  print_liste_field_titre($arrayfields['c.prix_standard']['label'], $_SERVER['PHP_SELF'], 'c.prix_standard', '', $param, '', $sortfield, $sortorder, 'center ');
	if (!empty($arrayfields['c.prix_enf_por']['checked']))  print_liste_field_titre($arrayfields['c.prix_enf_por']['label'], $_SERVER['PHP_SELF'], 'c.prix_enf_por', '', $param, '', $sortfield, $sortorder, 'center ');
	if (!empty($arrayfields['c.prix_enf_acc']['checked']))  print_liste_field_titre($arrayfields['c.prix_enf_acc']['label'], $_SERVER['PHP_SELF'], 'c.prix_enf_acc', '', $param, '', $sortfield, $sortorder, 'center ');
	if (!empty($arrayfields['c.prix_enf_dvm']['checked']))  print_liste_field_titre($arrayfields['c.prix_enf_dvm']['label'], $_SERVER['PHP_SELF'], 'c.prix_enf_dvm', '', $param, '', $sortfield, $sortorder, 'center ');
	if (!empty($arrayfields['c.kilo_bagage']['checked']))  print_liste_field_titre($arrayfields['c.kilo_bagage']['label'], $_SERVER['PHP_SELF'], 'c.kilo_bagage', '', $param, '', $sortfield, $sortorder, 'center ');

	if (!empty($arrayfields['c.date_creation']['checked'])) {
		print_liste_field_titre($arrayfields['c.date_creation']['label'], $_SERVER["PHP_SELF"], "c.date_creation", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	}
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	print "</tr>\n";


	$classe_static = new Classe($db);

	$i = 0;
	$totalarray = array();
	while ($i < min($num, $limit))
	{
		$obj = $db->fetch_object($resql);

		$classe_static->id = $obj->rowid;
		$classe_static->ref = $obj->ref;
		$proclasse_staticduct_static->label = $obj->label;
		print '<tr class="oddeven">';

		// Ref
		if (!empty($arrayfields['c.ref']['checked']))
		{
			print '<td class="tdoverflowmax200">';
			print $classe_static->getNomUrl(1);
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}

		// Label
		if (!empty($arrayfields['c.label']['checked']))
		{
			print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($obj->label).'">'.$obj->label.'</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Labelshort
		if (!empty($arrayfields['c.labelshort']['checked']))
		{
			print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($obj->labelshort).'">'.$obj->labelshort.'</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// prix_standard
		if (!empty($arrayfields['c.prix_standard']['checked']))
		{
			print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($obj->prix_standard).'">'.$obj->prix_standard.'</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// prix_enf_por
		if (!empty($arrayfields['c.prix_enf_por']['checked']))
		{
			print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($obj->prix_enf_por).'">'.$obj->prix_enf_por.'</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// prix_enf_acc
		if (!empty($arrayfields['c.prix_enf_acc']['checked']))
		{
			print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($obj->prix_enf_acc).'">'.$obj->prix_enf_acc.'</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// prix_enf_dvm
		if (!empty($arrayfields['c.prix_enf_dvm']['checked']))
		{
			print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($obj->prix_enf_dvm).'">'.$obj->prix_enf_dvm.'</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// kilo_bagage
		if (!empty($arrayfields['c.kilo_bagage']['checked']))
		{
			print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($obj->kilo_bagage).'">'.$obj->kilo_bagage.'</td>';
			if (!$i) $totalarray['nbfield']++;
		}



		// Date creation
		if (!empty($arrayfields['c.date_creation']['checked']))
		{
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Action
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
		{
			$selected = 0;
			if (in_array($obj->rowid, $arrayofselected)) $selected = 1;
			print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
		if (!$i) $totalarray['nbfield']++;

		print "</tr>\n";
		$i++;
	}

	$db->free($resql);

	print "</table>";
	print "</div>";
	print '</form>';
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
