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

require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/bticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/passenger.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/ship.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/travel.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/classe.class.php';
require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/agence.class.php';

// Load translation files required by the page
$langs->loadLangs('bookticket');

$action = GETPOST('action', 'aZ09');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');

$sall = trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$search_ref = GETPOST("search_ref", 'alpha');
$search_passenger = GETPOST("search_passenger", 'alpha');
$search_type = GETPOST("search_type", 'int');
$search_finished = GETPOST("search_finished", 'int');
$optioncss = GETPOST('optioncss', 'alpha');

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');

if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) { $page = 0; }     // If $page is not defined, or '' or -1 or if we click on clear filters or if we select empty mass action
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = "t.ref";
if (!$sortorder) $sortorder = "ASC";

// Initialize context for list
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'ticketlist';

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array of hooks
$object = new Bticket($db);
$form = new Form($db);

if (empty($action)) $action = 'list';

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$canvas = GETPOST("canvas");
$objcanvas = null;
if (!empty($canvas))
{
	require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('bticket', 'list', $canvas);
}

// Security check
//$result = restrictedArea($user, 'ticket', '', '', '', '', '', 0);

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	't.ref'=>"Ref",
	'passenger'=>"Passenger",
);


//$isInEEC = isInEEC($mysoc);

// Definition of fields for lists
$arrayfields = array(
	't.ref'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
	't.travel'=>array('label'=>$langs->trans('Travel'), 'checked'=>1, 'position'=>20),
	't.ship'=>array('label'=>$langs->trans("Ship"), 'checked'=>1, 'position'=>52),
	't.classe'=>array('label'=>$langs->trans('Classe'), 'checked'=>1, 'position'=>20),
	'passenger'=>array('label'=>$langs->trans("Passenger"), 'checked'=>1, 'position'=>52),
	't.date_creation'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
	't.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
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

$rightskey = 'bticket';

// Selection of new fields
include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
	$sall = "";
	$search_ref = "";
	$search_passenger = "";
	$search_finished = '';
	$search_array_options = array();
}

// Mass actions
$objectclass = 'Bticket';

$permissiontoread = $user->rights->bookticket->{$rightskey}->read;
$permissiontodelete = $user->rights->bookticket->{$rightskey}->delete;
$uploaddir = $conf->bookticket->dir_output;
include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';


/*
 * View
 */

$title = $langs->trans("BTickets");

$texte = $langs->trans("BTickets");


$sql = 'SELECT DISTINCT t.rowid, t.ref, s.label as ship, p.nom as passenger, p.prenom as prenom,  c.label as classe, tr.ref as travel, t.entity, fk_passenger';
$sql .= ' FROM '.MAIN_DB_PREFIX.'bookticket_bticket as t';
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_ship as s ON t.fk_ship = s.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_passenger as p ON t.fk_passenger = p.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_classe as c ON t.fk_classe = c.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_travel as tr ON t.fk_travel = tr.rowid";
$sql .= ' WHERE t.entity IN ('.getEntity('ticket').')';

if ($search_ref)     $sql .= natural_search('t.ref', $search_ref);
if ($search_passenger)   $sql .= natural_search('p.nom', $search_passenger);

$sql .= " ORDER BY t.date_creation DESC";

//$sql .= $db->order($sortfield, $sortorder);

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
		header("Location: ".DOL_URL_ROOT.'/custom/bookticket/bticket_card.php?id='.$id);
		exit;
	}

	$helpurl = '';
	$helpurl = 'EN:Module_BTicket|FR:Module_BTickets|ES:M&oacute;dulo_BTickets';


    llxHeader('', $title, $helpurl, '', 0, 0, "", "");

	// Displays ship removal confirmation
	if (GETPOST('delTicket')) {
		setEventMessages($langs->trans("TicketDeleted", GETPOST('delTicket')), null, 'mesgs');
	}

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.urlencode($limit);
	if ($sall) $param .= "&sall=".urlencode($sall);
	if ($search_ref) $param = "&search_ref=".urlencode($search_ref);
	if ($search_passenger) $param .= "&search_passenger=".urlencode($search_passenger);
	if ($search_finished) $param = "&search_finished=".urlencode($search_finished);

	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions = array(
		'generate_doc'=>$langs->trans("ReGeneratePDF"),
		//'builddoc'=>$langs->trans("PDFMerge"),
		//'presend'=>$langs->trans("SendByMail"),
	);
	if ($user->rights->bookticket->{$rightskey}->delete) $arrayofmassactions['predelete'] = "<span class='fa fa-trash paddingrightonly'></span>".$langs->trans("Delete");
	if (in_array($massaction, array('presend', 'predelete'))) $arrayofmassactions = array();
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	$newcardbutton = '';
	$perm = $user->rights->bookticket->bticket->write;
	$params = array();
	$params['forcenohideoftext'] = 1;
	//$newcardbutton .= dolGetButtonTitle($langs->trans('NewBTicket'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/custom/bookticket/bticket_card.php?action=create&type=0', '', $perm, $params);
	$label = 'NewBTicket';

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formulaire">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	//print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="type" value="'.$type.'">';

	$picto = 'bticket';

	print_barre_liste($texte, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, $picto, 0, $newcardbutton, '', $limit, 0, 0, 1);

	$topicmail = "Information";
	$modelmail = "bticket";
	$objecttmp = new Bticket($db);
	$trackid = 'bticket'.$object->id;
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
	if (!empty($arrayfields['t.ref']['checked']))
	{
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" name="search_ref" size="8" value="'.dol_escape_htmltag($search_ref).'">';
		print '</td>';
	}
	if (!empty($arrayfields['passenger']['checked']))
	{
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" name="search_passenger" size="12" value="'.dol_escape_htmltag($search_passenger).'">';
		print '</td>';
	}

	// travel
	if (!empty($arrayfields['t.travel']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}

	// ship
	if (!empty($arrayfields['t.ship']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}

	// classe
	if (!empty($arrayfields['t.classe']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}

	// Date creation
	if (!empty($arrayfields['t.date_creation']['checked']))
	{
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Date modification
	if (!empty($arrayfields['t.tms']['checked']))
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
	if (!empty($arrayfields['t.ref']['checked'])) {
		print_liste_field_titre($arrayfields['t.ref']['label'], $_SERVER["PHP_SELF"], "t.ref", "", $param, "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['passenger']['checked'])) {
		print_liste_field_titre($arrayfields['passenger']['label'], $_SERVER["PHP_SELF"], "passenger", "", $param, "", $sortfield, $sortorder);
	}

	if (!empty($arrayfields['t.travel']['checked'])) {
		print_liste_field_titre($arrayfields['t.travel']['label'], $_SERVER["PHP_SELF"], "t.travel", "", $param, '', $sortfield, $sortorder, 'center ');
	}
	if (!empty($arrayfields['t.ship']['checked'])) {
		print_liste_field_titre($arrayfields['t.ship']['label'], $_SERVER["PHP_SELF"], "t.ship", "", $param, '', $sortfield, $sortorder, 'center ');
	}

	if (!empty($arrayfields['t.classe']['checked']))  		print_liste_field_titre($arrayfields['t.classe']['label'], $_SERVER['PHP_SELF'], 't.classe', '', $param, '', $sortfield, $sortorder, 'center ');

	if (!empty($arrayfields['t.date_creation']['checked'])) {
		print_liste_field_titre($arrayfields['t.date_creation']['label'], $_SERVER["PHP_SELF"], "t.date_creation", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	}
	if (!empty($arrayfields['t.tms']['checked'])) {
		print_liste_field_titre($arrayfields['t.tms']['label'], $_SERVER["PHP_SELF"], "t.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	}
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	print "</tr>\n";


	$ticket_static = new Bticket($db);

	$i = 0;
	$totalarray = array();
	while ($i < min($num, $limit))
	{
		$obj = $db->fetch_object($resql);

		$ticket_static->id = $obj->rowid;
		$ticket_static->ref = $obj->ref;
		$ticket_static->passenger = $obj->passenger;
		print '<tr class="oddeven">';

		// Ref
		if (!empty($arrayfields['t.ref']['checked']))
		{
			print '<td class="tdoverflowmax200">';
			print $ticket_static->getNomUrl(1);
			print "</td>\n";
			if (!$i) $totalarray['nbfield']++;
		}

		// Passenger
		if (!empty($arrayfields['passenger']['checked']))
		{
			print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($obj->passenger).'"><a href="'.dol_buildpath('/bookticket/passenger_card.php', 1).'?id='.$obj->fk_passenger.'">'.$obj->passenger.' '.$obj->prenom.'</a></td>';
			if (!$i) $totalarray['nbfield']++;
		}



		// travel
		if (!empty($arrayfields['t.travel']['checked']))
		{
			print '<td class="center">';
			print $obj->travel;
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// ship
		if (!empty($arrayfields['t.ship']['checked']))
		{
			print '<td class="center">';
			print $obj->ship;
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// classe
		if (!empty($arrayfields['t.classe']['checked']))
		{
			print '<td class="center">';
			print $obj->classe;
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}

		// Date creation
		if (!empty($arrayfields['t.date_creation']['checked']))
		{
			print '<td class="center nowraponall">';
			print dol_print_date($db->jdate($obj->date_creation), 'dayhour', 'tzuser');
			print '</td>';
			if (!$i) $totalarray['nbfield']++;
		}
		// Date modification
		if (!empty($arrayfields['t.tms']['checked']))
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
