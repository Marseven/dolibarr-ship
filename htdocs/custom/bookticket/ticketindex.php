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


require_once DOL_DOCUMENT_ROOT.'/custom/bookticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_parser.class.php';

// Security check
//$result = restrictedArea($user, 'produit|service|expedition');

// Load translation files required by the page
$langs->loadLangs('ticket');


$ticket_static = new Ticket($db);


/*
 * View
 */

$transAreaType = $langs->trans("TicketArea");

$helpurl = '';
$transAreaType = $langs->trans("TicketArea");
$helpurl = 'EN:Module_Ticket|FR:Module_Ticket|ES:M&oacute;dulo_Ticket';

$user->rights->ticket->lire = true;

llxHeader("", $langs->trans("Ticket"), $helpurl);

$linkback = "";
print load_fiche_titre($transAreaType, $linkback, 'ticket');


print '<div class="fichecenter"><div class="fichethirdleft">';


if (!empty($conf->global->MAIN_SEARCH_FORM_ON_HOME_AREAS))     // This is useless due to the global search combo
{
	// Search contract
	if ($user->rights->ticket->lire)
	{
		$listofsearchfields['search_ticket'] = array('text'=>'Ticket');
	}

	if (count($listofsearchfields))
	{
		print '<form method="post" action="'.DOL_URL_ROOT.'/core/search.php">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder nohover centpercent">';
		$i = 0;
		foreach ($listofsearchfields as $key => $value)
		{
			if ($i == 0) print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("Search").'</td></tr>';
			print '<tr class="oddeven">';
			print '<td class="nowrap"><label for="'.$key.'">'.$langs->trans($value["text"]).'</label></td><td><input type="text" class="flat inputsearch" name="'.$key.'" id="'.$key.'" size="18"></td>';
			if ($i == 0) print '<td rowspan="'.count($listofsearchfields).'"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td>';
			print '</tr>';
			$i++;
		}
		print '</table>';
		print '</div>';
		print '</form>';
		print '<br>';
	}
}

/*
 * Number of Ticket
 */
if ($user->rights->ticket->lire)
{
	$prodser = array();
	$prodser[0][0] = $prodser[0][1] = $prodser[0][2] = $prodser[0][3] = 0;
	$prodser[1][0] = $prodser[1][1] = $prodser[1][2] = $prodser[1][3] = 0;

	$sql = "SELECT COUNT(t.rowid) as total";
	$sql .= " FROM ".MAIN_DB_PREFIX."ticket as t";
	//$sql .= ' WHERE t.entity IN ('.getEntity($ticket_static->element, 1).')';
	// Add where from hooks
	$parameters = array();


	if ($conf->use_javascript_ajax)
	{
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><th>'.$langs->trans("Statistics").'</th></tr>';
		print '<tr><td class="center nopaddingleftimp nopaddingrightimp">';

		/*$SommeA = $prodser[0]['sell'];
		$SommeB = $prodser[0]['buy'];
		$SommeC = $prodser[0]['none'];
		$SommeD = $prodser[1]['sell'];
		$SommeE = $prodser[1]['buy'];
		$SommeF = $prodser[1]['none'];
		$total = 0;
		$dataval = array();
		$datalabels = array();
		$i = 0;

		$total = $SommeA + $SommeB + $SommeC + $SommeD + $SommeE + $SommeF;
		$dataseries = array();
		if (!empty($conf->product->enabled))
		{
			$dataseries[] = array($langs->transnoentitiesnoconv("ProductsOnSale"), round($SommeA));
			$dataseries[] = array($langs->transnoentitiesnoconv("ProductsOnPurchase"), round($SommeB));
			$dataseries[] = array($langs->transnoentitiesnoconv("ProductsNotOnSell"), round($SommeC));
		}
		if (!empty($conf->service->enabled))
		{
			$dataseries[] = array($langs->transnoentitiesnoconv("ServicesOnSale"), round($SommeD));
			$dataseries[] = array($langs->transnoentitiesnoconv("ServicesOnPurchase"), round($SommeE));
			$dataseries[] = array($langs->transnoentitiesnoconv("ServicesNotOnSell"), round($SommeF));
		}*/
		$dataseries = [];
		include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
		$dolgraph = new DolGraph();
		$dolgraph->SetData($dataseries);
		$dolgraph->setShowLegend(2);
		$dolgraph->setShowPercent(0);
		$dolgraph->SetType(array('pie'));
		$dolgraph->setHeight('200');
		$dolgraph->draw('idgraphstatus');
		print $dolgraph->show($total ? 0 : 1);

		print '</td></tr>';
		print '</table>';
		print '</div>';
	}
}


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


/*
 * Latest modified ticket
 */
if (!empty($conf->product->enabled) && $user->rights->ticket->lire)
{
	$max = 15;
	$sql = "SELECT t.rowid, t.ref, s.label as ship, p.nom as nom,  c.label as classe, tr.ref as voyage,";
	$sql .= " t.entity,";
	$sql .= " t.tms as datem";
	$sql .= " FROM ".MAIN_DB_PREFIX."bookticket_ticket as t";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_ship as s ON t.fk_ship = s.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_passenger as p ON t.fk_passenger = p.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_classe as c ON t.fk_classe = c.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."bookticket_travel as tr ON t.fk_ship = tr.rowid";
	//$sql .= " WHERE p.entity IN (".getEntity($ticket_static->element, 1).")";
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
			if (empty($conf->global->PRODUIT_MULTIPRICES)) $colnb++;

			print '<tr class="liste_titre"><th colspan="'.$colnb.'">'.$transRecordedType.'</th>';
			print '<th class="right" colspan="3"><a href="'.DOL_URL_ROOT.'/custom/bookticket/ticket_list.php?sortfield=t.tms&sortorder=DESC">'.$langs->trans("FullList").'</td>';
			print '</tr>';

			while ($i < $num)
			{
				$objp = $db->fetch_object($result);

				$ticket_static->id = $objp->rowid;
				$ticket_static->ref = $objp->ref;
				$ticket_static->ship = $objp->ship;
				$ticket_static->passenger = $objp->passenger;
				$ticket_static->travel = $objp->travel;
				$ticket_static->classe = $objp->classe;
				$ticket_static->entity = $objp->entity;

				//Multilangs
				if (!empty($conf->global->MAIN_MULTILANGS))
				{
					$sql = "SELECT label";
					$sql .= " FROM ".MAIN_DB_PREFIX."ticket_lang";
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
				print $ticket_static->getNomUrl(1, '', 16);
				print "</td>\n";
				print '<td>'.dol_trunc($objp->travel, 32).'</td>';
				print "<td>";
				print dol_print_date($db->jdate($objp->datem), 'day');
				print "</td>";

				print '<td class="right nowrap width25"><span class="statusrefsell">';
				print $ticket_static->LibStatut($objp->passenger, 3, 0);
				print "</span></td>";
				print '<td class="right nowrap width25"><span class="statusrefbuy">';
				print $ticket_static->LibStatut($objp->classe, 3, 1);
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

print '</div></div></div>';

// End of page
llxFooter();
$db->close();
