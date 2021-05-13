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
 *	\file       bookticket/shipindex.php
 *	\ingroup    bookticket
 *	\brief      Home page of ship left menu
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

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/ship.class.php';

if (!empty($conf->categorie->enabled))
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

// Load translation files required by the page
$langs->loadLangs(array('products', 'stocks', 'suppliers', 'companies', 'margins'));
if (!empty($conf->productbatch->enabled)) $langs->load("productbatch");

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');

$sall = trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$search_ref = GETPOST("search_ref", 'alpha');
$search_barcode = GETPOST("search_barcode", 'alpha');
$search_label = GETPOST("search_label", 'alpha');
$search_type = GETPOST("search_type", 'int');
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
if (!$sortfield) $sortfield = "s.ref";
if (!$sortorder) $sortorder = "ASC";

// Initialize context for list
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'productservicelist';
if ((string) $type == '1') { $contextpage = 'servicelist'; if ($search_type == '') $search_type = '1'; }
if ((string) $type == '0') { $contextpage = 'productlist'; if ($search_type == '') $search_type = '0'; }

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array of hooks
$object = new Product($db);
$hookmanager->initHooks(array('productservicelist'));
$extrafields = new ExtraFields($db);
$form = new Form($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

if (empty($action)) $action = 'list';

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$canvas = GETPOST("canvas");
$objcanvas = null;
if (!empty($canvas))
{
	require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('ship', 'list', $canvas);
}

// Security check
//if ($search_type == '0') $result = restrictedArea($user, 'produit', '', '', '', '', '', 0);
//elseif ($search_type == '1') $result = restrictedArea($user, 'service', '', '', '', '', '', 0);
//else $result = restrictedArea($user, 'produit|service', '', '', '', '', '', 0);

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	's.ref'=>"Ref",
	'p.label'=>"ShipLabel",
	'p.labelshort'=>"ShipLabelShort",

);

// multilang
if (!empty($conf->global->MAIN_MULTILANGS))
{
	$fieldstosearchall['pl.label'] = 'ProductLabelTranslated';
	$fieldstosearchall['pl.description'] = 'ProductDescriptionTranslated';
	$fieldstosearchall['pl.note'] = 'ProductNoteTranslated';
}

if (!empty($conf->barcode->enabled)) {
	$fieldstosearchall['p.barcode'] = 'Gencod';
	$fieldstosearchall['pfp.barcode'] = 'GencodBuyPrice';
}

$isInEEC = isInEEC($mysoc);

// Definition of fields for lists
$arrayfields = array(
	'p.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	'p.label'=>array('label'=>$langs->trans("Label"), 'checked'=>1, 'position'=>10),
	'p.barcode'=>array('label'=>$langs->trans("Gencod"), 'checked'=>1, 'enabled'=>(!empty($conf->barcode->enabled)), 'position'=>12),
	'p.weight'=>array('label'=>$langs->trans('Weight'), 'checked'=>0, 'enabled'=>(!empty($conf->product->enabled) && $type != '1'), 'position'=>20),
	'p.stock'=>array('label'=>$langs->trans("PhysicalStock"), 'checked'=>1, 'enabled'=>(!empty($conf->stock->enabled) && $user->rights->stock->lire && $contextpage != 'service'), 'position'=>52),
	'p.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
	'p.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
);


// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_array_fields.tpl.php';

$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');



/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

$rightskey = 'ship';

if (empty($reshook))
{
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
	{
		$sall = "";
		$search_ref = "";
		$search_label = "";
		$search_barcode = "";
		$searchCategoryProductOperator = 0;
		$searchCategoryProductList = array();
		$search_tosell = "";
		$search_tobuy = "";
		$search_tobatch = '';
		$search_country = "";
		$search_state = "";
		$search_vatrate = "";
		$search_finished = '';
		//$search_type='';						// There is 2 types of list: a list of product and a list of services. No list with both. So when we clear search criteria, we must keep the filter on type.

		$show_childproducts = '';
		$search_accountancy_code_sell = '';
		$search_accountancy_code_sell_intra = '';
		$search_accountancy_code_sell_export = '';
		$search_accountancy_code_buy = '';
		$search_accountancy_code_buy_intra = '';
		$search_accountancy_code_buy_export = '';
		$search_array_options = array();
	}

	// Mass actions
	$objectclass = 'Ship';

	$permissiontoread = $user->rights->{$rightskey}->lire;
	$permissiontodelete = $user->rights->{$rightskey}->supprimer;
	$uploaddir = $conf->product->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}


/*
 * View
 */

$title = $langs->trans("Ships");

$texte = $langs->trans("Ships");


$sql = 'SELECT DISTINCT p.rowid, p.ref, p.label, p.fk_product_type, p.barcode, p.price, p.tva_tx, p.price_ttc, p.price_base_type, p.entity,';
$sql .= ' p.fk_product_type, p.duration, p.finished, p.tosell, p.tobuy, p.seuil_stock_alerte, p.desiredstock,';
$sql .= ' p.tobatch, p.accountancy_code_sell, p.accountancy_code_sell_intra, p.accountancy_code_sell_export,';
$sql .= ' p.accountancy_code_buy, p.accountancy_code_buy_intra, p.accountancy_code_buy_export,';
$sql .= ' p.datec as date_creation, p.tms as date_update, p.pmp, p.stock, p.cost_price,';
$sql .= ' p.weight, p.weight_units, p.length, p.length_units, p.width, p.width_units, p.height, p.height_units, p.surface, p.surface_units, p.volume, p.volume_units, fk_country, fk_state,';


// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= ' FROM '.MAIN_DB_PREFIX.'ship as s';

$sql .= ' WHERE p.entity IN ('.getEntity('ship').')';

if ($search_ref)     $sql .= natural_search('p.ref', $search_ref);
if ($search_label)   $sql .= natural_search('p.label', $search_label);
if ($search_barcode) $sql .= natural_search('p.barcode', $search_barcode);

// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " GROUP BY p.rowid, p.ref, p.label, p.barcode, p.price, p.tva_tx, p.price_ttc, p.price_base_type,";
$sql .= " p.fk_product_type, p.duration, p.finished, p.tosell, p.tobuy, p.seuil_stock_alerte, p.desiredstock,";
$sql .= ' p.datec, p.tms, p.entity, p.tobatch, p.accountancy_code_sell, p.accountancy_code_sell_intra, p.accountancy_code_sell_export,';
$sql .= ' p.accountancy_code_buy, p.accountancy_code_buy_intra, p.accountancy_code_buy_export, p.pmp, p.cost_price, p.stock,';
$sql .= ' p.weight, p.weight_units, p.length, p.length_units, p.width, p.width_units, p.height, p.height_units, p.surface, p.surface_units, p.volume, p.volume_units, p.fk_country, p.fk_state';

// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
//if (GETPOST("toolowstock")) $sql.= " HAVING SUM(s.reel) < p.seuil_stock_alerte";    // Not used yet
$sql .= $db->order($sortfield, $sortorder);

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
		header("Location: ".DOL_URL_ROOT.'/custom/bookticket/ship_card.php?id='.$id);
		exit;
	}

	$helpurl = '';
	$helpurl = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';


    llxHeader('', $title, $helpurl, '', 0, 0, "", "", $paramsCat);


	// Displays ship removal confirmation
	if (GETPOST('delprod')) {
		setEventMessages($langs->trans("ProductDeleted", GETPOST('delprod')), null, 'mesgs');
	}

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
	if ($sall) $param .= "&sall=".urlencode($sall);
	if ($searchCategoryProductOperator == 1) $param .= "&search_category_product_operator=".urlencode($searchCategoryProductOperator);
	foreach ($searchCategoryProductList as $searchCategoryProduct) {
		$param .= "&search_category_product_list[]=".urlencode($searchCategoryProduct);
	}
	if ($search_ref) $param = "&search_ref=".urlencode($search_ref);
	if ($search_ref_supplier) $param = "&search_ref_supplier=".urlencode($search_ref_supplier);
	if ($search_barcode) $param .= ($search_barcode ? "&search_barcode=".urlencode($search_barcode) : "");
	if ($search_label) $param .= "&search_label=".urlencode($search_label);
	if ($search_finished) $param = "&search_finished=".urlencode($search_finished);

	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

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
	$perm = $user->rights->ship->creer;
	$params = array();
	$params['forcenohideoftext'] = 1;
	$newcardbutton .= dolGetButtonTitle($langs->trans('NewProduct'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/custom/bookticket/ship_card.php?action=create&type=0', '', $perm, $params);
	$label = 'NewShip';

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formulaire">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	//print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="type" value="'.$type.'">';

	$picto = 'ship';

	print_barre_liste($texte, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, $picto, 0, $newcardbutton, '', $limit, 0, 0, 1);

	$topicmail = "Information";
	$modelmail = "ship";
	$objecttmp = new Ship($db);
	$trackid = 'ship'.$object->id;
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
	if (!empty($arrayfields['p.ref']['checked']))
	{
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" name="search_ref" size="8" value="'.dol_escape_htmltag($search_ref).'">';
		print '</td>';
	}
	if (!empty($arrayfields['pfp.ref_fourn']['checked']))
	{
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" name="search_ref_supplier" size="8" value="'.dol_escape_htmltag($search_ref_supplier).'">';
		print '</td>';
	}
	if (!empty($arrayfields['p.label']['checked']))
	{
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" name="search_label" size="12" value="'.dol_escape_htmltag($search_label).'">';
		print '</td>';
	}

	// Barcode
	if (!empty($arrayfields['p.barcode']['checked']))
	{
		print '<td class="liste_titre">';
		print '<input class="flat" type="text" name="search_barcode" size="6" value="'.dol_escape_htmltag($search_barcode).'">';
		print '</td>';
	}
	// Duration
	if (!empty($arrayfields['p.duration']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}

	// Finished
	if (!empty($arrayfields['p.finished']['checked']))
	{
		print '<td class="liste_titre">';
		print $formproduct->selectProductNature('search_finished', $search_finished);
		print '</td>';
	}
	// Weight
	if (!empty($arrayfields['p.weight']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (!empty($arrayfields['p.datec']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Date modification
	if (!empty($arrayfields['p.tms']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	if (!empty($arrayfields['p.tosell']['checked']))
	{
		print '<td class="liste_titre right">';
		print $form->selectarray('search_tosell', array('0'=>$langs->trans('ProductStatusNotOnSellShort'), '1'=>$langs->trans('ProductStatusOnSellShort')), $search_tosell, 1);
		print '</td >';
	}
	if (!empty($arrayfields['p.tobuy']['checked']))
	{
		print '<td class="liste_titre right">';
		print $form->selectarray('search_tobuy', array('0'=>$langs->trans('ProductStatusNotOnBuyShort'), '1'=>$langs->trans('ProductStatusOnBuyShort')), $search_tobuy, 1);
		print '</td>';
	}
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';

	print '</tr>';

	print '<tr class="liste_titre">';
	if (!empty($arrayfields['p.ref']['checked'])) {
		print_liste_field_titre($arrayfields['p.ref']['label'], $_SERVER["PHP_SELF"], "p.ref", "", $param, "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['p.label']['checked'])) {
		print_liste_field_titre($arrayfields['p.label']['label'], $_SERVER["PHP_SELF"], "p.label", "", $param, "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['p.barcode']['checked'])) {
		print_liste_field_titre($arrayfields['p.barcode']['label'], $_SERVER["PHP_SELF"], "p.barcode", "", $param, "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['p.duration']['checked'])) {
		print_liste_field_titre($arrayfields['p.duration']['label'], $_SERVER["PHP_SELF"], "p.duration", "", $param, '', $sortfield, $sortorder, 'center ');
	}
	if (!empty($arrayfields['p.finished']['checked'])) {
		print_liste_field_titre($arrayfields['p.finished']['label'], $_SERVER["PHP_SELF"], "p.finished", "", $param, '', $sortfield, $sortorder, 'center ');
	}

	if (!empty($arrayfields['p.weight']['checked']))  		print_liste_field_titre($arrayfields['p.weight']['label'], $_SERVER['PHP_SELF'], 'p.weight', '', $param, '', $sortfield, $sortorder, 'center ');
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	// Hook fields
	$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (!empty($arrayfields['p.datec']['checked'])) {
		print_liste_field_titre($arrayfields['p.datec']['label'], $_SERVER["PHP_SELF"], "p.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	}
	if (!empty($arrayfields['p.tms']['checked'])) {
		print_liste_field_titre($arrayfields['p.tms']['label'], $_SERVER["PHP_SELF"], "p.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	}
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	print "</tr>\n";


	$ship_static = new Ship($db);

	$i = 0;
	$totalarray = array();
	while ($i < min($num, $limit))
	{
		$obj = $db->fetch_object($resql);

		// Multilangs
		if (!empty($conf->global->MAIN_MULTILANGS))  // If multilang is enabled
		{
			$sql = "SELECT label";
			$sql .= " FROM ".MAIN_DB_PREFIX."product_lang";
			$sql .= " WHERE fk_product=".$obj->rowid;
			$sql .= " AND lang='".$db->escape($langs->getDefaultLang())."'";
			$sql .= " LIMIT 1";

			$result = $db->query($sql);
			if ($result)
			{
				$objtp = $db->fetch_object($result);
				if (!empty($objtp->label)) $obj->label = $objtp->label;
			}
		}

		$product_static->id = $obj->rowid;
		$product_static->ref = $obj->ref;
		$product_static->label = $obj->label;
		print '<tr class="oddeven">';

		// Ref
		if (!empty($arrayfields['p.ref']['checked']))
		{
			print '<td class="tdoverflowmax200">';
			print $product_static->getNomUrl(1);
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}

		// Label
		if (!empty($arrayfields['p.label']['checked']))
		{
			print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($obj->label).'">'.$obj->label.'</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Barcode
		if (!empty($arrayfields['p.barcode']['checked']))
		{
			print '<td>'.$obj->barcode.'</td>';
			if (!$i) $totalarray['nbfield']++;
		}



		// Finished
		if (!empty($arrayfields['p.finished']['checked']))
		{
			print '<td class="center">';
			print $product_static->getLibFinished();
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
		// Fields from hook
		$parameters = array('arrayfields'=>$arrayfields, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
		$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Date creation
		if (!empty($arrayfields['p.datec']['checked']))
		{
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Date modification
		if (!empty($arrayfields['p.tms']['checked']))
		{
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->date_update), 'dayhour', 'tzuser');
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
