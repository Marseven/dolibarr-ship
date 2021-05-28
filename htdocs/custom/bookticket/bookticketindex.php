
<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2021      Mebodo Aristide	<mebodoraistide@gmail.com>
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
 *	\file       bookticket/bookticketindex.php
 *	\ingroup    bookticket
 *	\brief      Home page of bookticket top menu
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";

// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";

// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

// Load translation files required by the page
$langs->loadLangs(array("bookticket@bookticket"));

$action = GETPOST('action', 'aZ09');


// Security check
//if (! $user->rights->bookticket->myobject->read) accessforbidden();

$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0)
{
	$action = '';
	$socid = $user->socid;
}

$max = 5;
$now = dol_now();

$user->rights->bookticket->read = true;

/*
 * Actions
 */

// Check if company name is defined (first install)
if (GETPOST('addbox'))	// Add box (when submit is done from a form when ajax disabled)
{
	require_once DOL_DOCUMENT_ROOT.'/core/class/infobox.class.php';
	$zone = GETPOST('areacode', 'aZ09');
	$userid = GETPOST('userid', 'int');
	$boxorder = GETPOST('boxorder', 'aZ09');
	$boxorder .= GETPOST('boxcombo', 'aZ09');

	$result = InfoBox::saveboxorder($db, $zone, $boxorder, $userid);
	if ($result > 0) setEventMessages($langs->trans("BoxAdded"), null);
}


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("BookTicketArea"));

print load_fiche_titre($langs->trans("BookTicketArea"), '', 'bookticket.png@bookticket');

print '<div class="fichecenter"><div class="fichethirdleft">';


/*
 * Dashboard Dolibarr states (statistics)
 * Hidden for external users
 */

$boxstatItems = array();

// Load translation files required by page
$langs->loadLangs(array('bookticket'));

// Load global statistics of objects
if ($user->rights->bookticket->bticket->read)
{
	$object = new stdClass();


	// Cle array returned by the method load_state_board for each line
	$keys = array(
		'users',
		'travel',
		'ship',
		'passenger',
		'classe',
		'city',
		'agence',
		'bticket'
	);

	// Condition to be checked for each display line dashboard
	$conditions = array(
		'users' => $user->rights->user->user->lire,
		'travel' => $user->rights->bookticket->travel->read,
		'ship' => $user->rights->bookticket->ship->read,
		'passenger' => $user->rights->bookticket->passenger->read,
		'classe' => $user->rights->bookticket->classe->read,
		'city' => $user->rights->bookticket->city->read,
		'agence' => $user->rights->bookticket->agence->read,
		'bticket' => $user->rights->bookticket->bticket->read
	);
	// Class file containing the method load_state_board for each line
	$includes = array(
		'users' => DOL_DOCUMENT_ROOT."/user/class/user.class.php",
		'travel' => DOL_DOCUMENT_ROOT."/custom/bookticket/class/travel.class.php",
		'ship' => DOL_DOCUMENT_ROOT."/custom/bookticket/class/ship.class.php",
		'passenger' => DOL_DOCUMENT_ROOT."/custom/bookticket/class/passenger.class.php",
		'classe' => DOL_DOCUMENT_ROOT."/custom/bookticket/class/classe.class.php",
		'city' => DOL_DOCUMENT_ROOT."/custom/bookticket/class/city.class.php",
		'agence' => DOL_DOCUMENT_ROOT."/custom/bookticket/class/agence.class.php",
		'bticket' => DOL_DOCUMENT_ROOT."/custom/bookticket/class/bticket.class.php"
	);
	// Name class containing the method load_state_board for each line
	$classes = array(
		'users' => 'User',
		'travel' => 'Travel',
		'ship' => 'Ship',
		'passenger' => 'Passenger',
		'classe' => 'Classe',
		'city' => 'City',
		'agence' => 'Agence',
		'bticket' => 'Bticket'
	);
	// Translation keyword
	$titres = array(
		'users' => "User",
		'travel' => 'Travel',
		'ship' => 'Ship',
		'passenger' => 'Passenger',
		'classe' => 'Classe',
		'city' => 'City',
		'agence' => "Agence",
		'bticket' => 'Bticket'
	);
	// Dashboard Link lines
	$links = array(
		'users' => DOL_URL_ROOT.'/user/list.php',
		'travel' => DOL_URL_ROOT.'/custom/bookticket/travelindex.php',
		'ship' => DOL_URL_ROOT.'/custom/bookticket/shipindex.php',
		'passenger' => DOL_URL_ROOT.'/custom/bookticket/passengerindex.php',
		'classe' => DOL_URL_ROOT.'/custom/bookticket/classeindex.php',
		'city' => DOL_URL_ROOT.'/custom/bookticket/city_list.php',
		'agence' => DOL_URL_ROOT.'/custom/bookticket/agence_list.php',
		'bticket' => DOL_URL_ROOT.'/custom/bookticket/bticketindex.php'
	);
	// Translation lang files
	$langfile = array(
		'bookticket' => "bookticket"
	);

	// Loop and displays each line of table
	foreach ($keys as $val)
	{
		if ($conditions[$val])
		{
			$boxstatItem = '';
			$class = $classes[$val];
			include_once $includes[$val]; // Loading a class cost around 1Mb

			$board = new $class($db);
			$board->load_state_board();
			$boardloaded[$class] = $board;


			$langs->load('bookticket');

			$text = $langs->trans($titres[$val]);
			$boxstatItem .= '<a href="'.$links[$val].'" class="boxstatsindicator thumbstat nobold nounderline">';
			$boxstatItem .= '<div class="boxstats">';
			$boxstatItem .= '<span class="boxstatstext" title="'.dol_escape_htmltag($text).'">'.$text.'</span><br>';
			$boxstatItem .= '<span class="boxstatsindicator">'.img_object("", $board->picto, 'class="inline-block"').' '.($board->nb[$val] ? $board->nb[$val] : 0).'</span>';
			$boxstatItem .= '</div>';
			$boxstatItem .= '</a>';
			$boxstatItems[$val] = $boxstatItem;
		}
	}
}

print '<div class="clearboth"></div>';

print '<div class="fichecenter fichecenterbis">';


/*
 * Show widgets (boxes)
 */

if (empty($user->socid) && empty($conf->global->MAIN_DISABLE_GLOBAL_BOXSTATS))
{
	// Remove allready present info in new dash board
	if (!empty($conf->global->MAIN_INCLUDE_GLOBAL_STATS_IN_OPENED_DASHBOARD) && is_array($boxstatItems) && count($boxstatItems) > 0) {
		foreach ($boxstatItems as $boxstatItemKey => $boxstatItemHtml) {
			if (in_array($boxstatItemKey, $globalStatInTopOpenedDashBoard)) {
				unset($boxstatItems[$boxstatItemKey]);
			}
		}
	}

	if (!empty($boxstatFromHook) || !empty($boxstatItems)) {
		$boxstat .= '<!-- Database statistics -->'."\n";
		$boxstat .= '<div class="box">';
		$boxstat .= '<table summary="'.dol_escape_htmltag($langs->trans("DolibarrStateBoard")).'" class="noborder boxtable boxtablenobottom nohover widgetstats" width="100%">';
		$boxstat .= '<tr class="liste_titre box_titre">';
		$boxstat .= '<td>';
		$boxstat .= '<div class="inline-block valignmiddle">'.$langs->trans("DolibarrStateBoard").'</div>';
		$boxstat .= '</td>';
		$boxstat .= '</tr>';
		$boxstat .= '<tr class="nobottom nohover"><td class="tdboxstats nohover flexcontainer">';

		$boxstat .= $boxstatFromHook;

		if (is_array($boxstatItems) && count($boxstatItems) > 0)
		{
			$boxstat .= implode('', $boxstatItems);
		}

		$boxstat .= '<a class="boxstatsindicator thumbstat nobold nounderline"><div class="boxstatsempty"></div></a>';
		$boxstat .= '<a class="boxstatsindicator thumbstat nobold nounderline"><div class="boxstatsempty"></div></a>';
		$boxstat .= '<a class="boxstatsindicator thumbstat nobold nounderline"><div class="boxstatsempty"></div></a>';
		$boxstat .= '<a class="boxstatsindicator thumbstat nobold nounderline"><div class="boxstatsempty"></div></a>';
		$boxstat .= '<a class="boxstatsindicator thumbstat nobold nounderline"><div class="boxstatsempty"></div></a>';
		$boxstat .= '<a class="boxstatsindicator thumbstat nobold nounderline"><div class="boxstatsempty"></div></a>';
		$boxstat .= '<a class="boxstatsindicator thumbstat nobold nounderline"><div class="boxstatsempty"></div></a>';
		$boxstat .= '<a class="boxstatsindicator thumbstat nobold nounderline"><div class="boxstatsempty"></div></a>';

		$boxstat .= '</td></tr>';
		$boxstat .= '</table>';
		$boxstat .= '</div>';
	}
}

$boxlist .= $boxstat;
$boxlist .= $resultboxes['boxlistb'];
$boxlist .= "\n";
print $boxlist;

print '</div>';

print '<div class="fichecenter fichecenterbis">';

$bticket_static = new Bticket($db);

$stats = $bticket_static->load_stats();

// Billets
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

$colnb = 2;

print '<tr class="liste_titre"><th>'.$langs->trans("StatBillet").'</th>';
print '<th class="right" ><a href="'.DOL_URL_ROOT.'/custom/bookticket/bticket_list.php?sortfield=t.tms&sortorder=DESC">'.$langs->trans("FullList").'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td class="nowrap">';
print $langs->trans("NbreBilletJour");
print "</td>\n";
print '<td>';
print $stats['nbj'];
print "</td>";
print "</tr>\n";

print '<tr class="oddeven">';
print '<td class="nowrap">';
print $langs->trans("NbreBilletSemaine");
print "</td>\n";
print '<td>';
print $stats['nbh'];
print "</td>";
print "</tr>\n";

print '<tr class="oddeven">';
print '<td class="nowrap">';
print $langs->trans("NbreBilletMois");
print "</td>\n";
print '<td>';
print $stats['nbm'];
print "</td>";
print "</tr>\n";

print '<tr class="oddeven">';
print '<td class="nowrap">';
print $langs->trans("NbreBilletAnne");
print "</td>\n";
print '<td>';
print $stats['nba'];
print "</td>";
print "</tr>\n";

print "</table>";
print '</div>';
print '<br>';

// CA
print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

$colnb = 2;

print '<tr class="liste_titre"><th>'.$langs->trans("ChiffreAffaire").'</th>';
print '<th class="right" ><a href="'.DOL_URL_ROOT.'/custom/bookticket/bticket_list.php?sortfield=t.tms&sortorder=DESC">'.$langs->trans("FullList").'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td class="nowrap">';
print $langs->trans("NbreBilletJour");
print "</td>\n";
print '<td>';
print $stats['caj'] == NULL ? "0" : $stats['caj'];
print "</td>";
print "</tr>\n";

print '<tr class="oddeven">';
print '<td class="nowrap">';
print $langs->trans("NbreBilletSemaine");
print "</td>\n";
print '<td>';
print $stats['cah']== NULL ? "0" : $stats['cah'];
print "</td>";
print "</tr>\n";

print '<tr class="oddeven">';
print '<td class="nowrap">';
print $langs->trans("NbreBilletMois");
print "</td>\n";
print '<td>';
print $stats['cam']== NULL ? "0" : $stats['cam'];
print "</td>";
print "</tr>\n";

print '<tr class="oddeven">';
print '<td class="nowrap">';
print $langs->trans("NbreBilletAnne");
print "</td>\n";
print '<td>';
print $stats['caa']== NULL ? "0" : $stats['caj'];
print "</td>";
print "</tr>\n";

print "</table>";
print '</div>';
print '<br>';

//$stats2 = $bticket_static->load_stats_by_agence();
//$stats1 = $bticket_static->load_stats_by_classe();

print '</div>';

print '</div></div>';

print '<div class="fichetwothirdright">';


/*
 * Latest modified ticket
 */
if ($user->rights->bookticket->bticket->read)
{

	$max = 15;
	$sql = "SELECT t.rowid, t.ref, s.label as ship, p.nom as nom,  c.label as classe, tr.ref as travel,";
	$sql .= " t.entity, t.status,";
	$sql .= " t.tms as datem";
	$sql .= " FROM ".MAIN_DB_PREFIX."bookticket_bticket as t";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_ship as s ON t.fk_ship = s.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_passenger as p ON t.fk_passenger = p.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_classe as c ON t.fk_classe = c.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_travel as tr ON t.fk_ship = tr.rowid";
	//$sql .= " WHERE p.entity IN (".getEntity($bticket_static->element, 1).")";
	// Add where from hooks

	$parameters = array();
	$sql .= $db->order("t.tms", "DESC");
	$sql .= $db->plimit($max, 0);

	//print $sql;
	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);

		$i = 0;

		if ($num > 0)
		{
			$transRecordedType = $langs->trans("LastModifiedTicket", $max);
			$transRecordedType = $langs->trans("LastRecordedTicket", $max);

			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder centpercent">';

			$colnb = 2;

			print '<tr class="liste_titre"><th colspan="'.$colnb.'">'.$transRecordedType.'</th>';
			print '<th class="right" colspan="3"><a href="'.DOL_URL_ROOT.'/custom/bookticket/bticket_list.php?sortfield=t.tms&sortorder=DESC">'.$langs->trans("FullList").'</td>';
			print '</tr>';

			while ($i < $num)
			{
				$objp = $db->fetch_object($result);

				$bticket_static->id = $objp->rowid;
				$bticket_static->ref = $objp->ref;
				$bticket_static->ship = $objp->ship;
				$bticket_static->passenger = $objp->nom;
				$bticket_static->travel = $objp->travel;
				$bticket_static->classe = $objp->classe;
				$bticket_static->entity = $objp->entity;


				print '<tr class="oddeven">';
				print '<td class="nowrap">';
				print $bticket_static->getNomUrl(1, '', 16);
				print "</td>\n";
				print '<td>'.dol_trunc($objp->travel, 32).'</td>';
				print "<td>";
				print dol_print_date($db->jdate($objp->datem), 'day');
				print "</td>";
				print '<td>';
				print $bticket_static->passenger;
				print "</td>";

				print '<td class="right nowrap width25"><span class="statusrefbuy">';
				print $bticket_static->LibStatut($objp->status, 3, 0);
				print "</span></td>";
				print "</tr>\n";
				$i++;
			}

			$db->free($result);

			print "</table>";
			print '</div>';
			print '<br>';
		}
	} else {
		dol_print_error($db);
	}
}

print '</div>';

print '<div class="fichetwothirdright">';


/*
 * Latest modified travel
 */
if ($user->rights->bookticket->travel->read)
{
	$travel_static = new Travel($db);

	$max = 15;
	$sql = "SELECT t.rowid, t.ref, t.jour, t.heure, t.lieu_depart,  t.lieu_arrive, s.label as ship,";
	$sql .= " t.entity, t.status,";
	$sql .= " t.tms as datem";
	$sql .= " FROM ".MAIN_DB_PREFIX."bookticket_travel as t";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_ship as s ON t.fk_ship = s.rowid";
	//$sql .= " WHERE p.entity IN (".getEntity($travel_static->element, 1).")";

	$parameters = array();
	$sql .= $db->order("t.tms", "DESC");
	$sql .= $db->plimit($max, 0);

	//print $sql;
	$result = $db->query($sql);
	if ($result)
	{
		$num = $db->num_rows($result);

		$i = 0;

		if ($num > 0)
		{
			$transRecordedType = $langs->trans("LastModifiedTravel", $max);
			$transRecordedType = $langs->trans("LastRecordedTravel", $max);

			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder centpercent">';

			$colnb = 2;
			if (empty($conf->global->PRODUIT_MULTIPRICES)) $colnb++;

			print '<tr class="liste_titre"><th colspan="'.$colnb.'">'.$transRecordedType.'</th>';
			print '<th class="right" colspan="3"><a href="'.DOL_URL_ROOT.'/custom/bookticket/travel_list.php?sortfield=t.tms&sortorder=DESC">'.$langs->trans("FullList").'</td>';
			print '</tr>';

			while ($i < $num)
			{
				$objp = $db->fetch_object($result);

				$travel_static->id = $objp->rowid;
				$travel_static->ref = $objp->ref;
				$travel_static->jour_heure = $objp->label;
				$travel_static->lieu_depart = $objp->labelshort;
				$travel_static->lieu_arrive = $objp->nbre_place;
				$travel_static->ship = $objp->ship;
				$travel_static->entity = $objp->entity;

				//Multilangs
				if (!empty($conf->global->MAIN_MULTILANGS))
				{
					$sql = "SELECT label";
					$sql .= " FROM ".MAIN_DB_PREFIX."travel_lang";
					$sql .= " WHERE fk_product=".$objp->rowid;
					$sql .= " AND lang='".$db->escape($langs->getDefaultLang())."'";

					$resultd = $db->query($sql);
					if ($resultd)
					{
						$objtp = $db->fetch_object($resultd);
						if ($objtp && $objtp->label != '') $objp->label = $objtp->label;
					}
				}


				print '<tr class="oddeven">';
				print '<td class="nowrap">';
				print $travel_static->getNomUrl(1, '', 16);
				print "</td>\n";
				print '<td>'.dol_trunc($objp->ship, 32).'</td>';
				print "<td>";
				print dol_print_date($db->jdate($objp->jour), 'day');
				print "</td>";
				print '<td>';
				print $objp->heure;
				print "</td>";

				print '<td class="right nowrap width25"><span class="statusrefbuy">';
				print $travel_static->LibStatut($objp->status, 3, 1);
				print "</span></td>";
				print "</tr>\n";
				$i++;
			}

			$db->free($result);

			print "</table>";
			print '</div>';
			print '<br>';
		}
	} else {
		dol_print_error($db);
	}
}

print '</div>';


// End of page
llxFooter();
$db->close();

/**
 *  Show weather logo. Logo to show depends on $totallate and values for
 *  $conf->global->MAIN_METEO_LEVELx
 *
 *  @param      int     $totallate      Nb of element late
 *  @param      string  $text           Text to show on logo
 *  @param      string  $options        More parameters on img tag
 *  @param      string  $morecss        More CSS
 *  @return     string                  Return img tag of weather
 */
function showWeather($totallate, $text, $options, $morecss = '')
{
	global $conf;

	$weather = getWeatherStatus($totallate);
	return img_weather($text, $weather->picto, $options, 0, $morecss);
}


/**
 *  get weather level
 *  $conf->global->MAIN_METEO_LEVELx
 *
 *  @param      int     $totallate      Nb of element late
 *  @return     string                  Return img tag of weather
 */
function getWeatherStatus($totallate)
{
	global $conf;

	$weather = new stdClass();
	$weather->picto = '';

	$offset = 0;
	$factor = 10; // By default

	$used_conf = !empty($conf->global->MAIN_USE_METEO_WITH_PERCENTAGE) ? 'MAIN_METEO_PERCENTAGE_LEVEL' : 'MAIN_METEO_LEVEL';

	$level0 = $offset;
	$weather->level = 0;
	if (!empty($conf->global->{$used_conf.'0'})) {
		$level0 = $conf->global->{$used_conf.'0'};
	}
	$level1 = $offset + 1 * $factor;
	if (!empty($conf->global->{$used_conf.'1'})) {
		$level1 = $conf->global->{$used_conf.'1'};
	}
	$level2 = $offset + 2 * $factor;
	if (!empty($conf->global->{$used_conf.'2'})) {
		$level2 = $conf->global->{$used_conf.'2'};
	}
	$level3 = $offset + 3 * $factor;
	if (!empty($conf->global->{$used_conf.'3'})) {
		$level3 = $conf->global->{$used_conf.'3'};
	}

	if ($totallate <= $level0) {
		$weather->picto = 'weather-clear.png';
		$weather->level = 0;
	}
	elseif ($totallate <= $level1) {
		$weather->picto = 'weather-few-clouds.png';
		$weather->level = 1;
	}
	elseif ($totallate <= $level2) {
		$weather->picto = 'weather-clouds.png';
		$weather->level = 2;
	}
	elseif ($totallate <= $level3) {
		$weather->picto = 'weather-many-clouds.png';
		$weather->level = 3;
	}
	else {
		$weather->picto = 'weather-storm.png';
		$weather->level = 4;
	}

	return $weather;
}
