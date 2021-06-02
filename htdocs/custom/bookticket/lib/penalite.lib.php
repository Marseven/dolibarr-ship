<?php
/* Copyright (C) 2006-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2007       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2009-2010  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015-2016	Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2021		Mebodo Aristide			<mebodoaristide@gmail.com>
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
 * or see https://www.gnu.org/
 */

/**
 *	\file       htdocs/core/lib/bticket.lib.php
 *	\brief      Ensemble de fonctions de base pour le module produit et service
 * 	\ingroup	bticket
 */

/**
 * Prepare array with list of tabs
 *
 * @param  Penalite	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function penalite_prepare_head($object)
{
	global $db, $langs, $conf, $user;
	$langs->load("bookticket");

	$label = $langs->trans('Penalite');

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/custom/bookticket/penalite_card.php?id=".$object->id;
	$head[$h][1] = $label;
	$head[$h][2] = 'card';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'Penalite');

	// Notes
	if (empty($conf->global->MAIN_DISABLE_NOTES_TAB))
	{
		$nbNote = 0;
		if (!empty($object->note_private)) $nbNote++;
		if (!empty($object->note_public)) $nbNote++;
		$head[$h][0] = DOL_URL_ROOT.'/custom/bookticket/panlite_note.php?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		$head[$h][2] = 'note';
		$h++;
	}

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'Penalite', 'remove');

	/* Log
	$head[$h][0] = DOL_URL_ROOT.'/custom/bookticket/ticket_agenda.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Events");
	if (!empty($conf->agenda->enabled) && (!empty($user->rights->agenda->myactions->read) || !empty($user->rights->agenda->allactions->read)))
	{
		$head[$h][1] .= '/';
		$head[$h][1] .= $langs->trans("Agenda");
	}
	$head[$h][2] = 'agenda';
	$h++;*/

	return $head;
}


/**
*  Return array head with list of tabs to view object informations.
*
*  @return	array   	        head array with tabs
*/
function penalite_admin_prepare_head()
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/custom/bookticket/admin/penalite.php";
	$head[$h][1] = $langs->trans('Parameters');
	$head[$h][2] = 'general';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'penalite_admin');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'penalite_admin', 'remove');

	return $head;
}


/**
 * Show stats for company
 *
 * @param	Ticket		$ticket	ticket object
 * @param 	int			$socid		Thirdparty id
 * @return	integer					NB of lines shown into array
 */
function show_stats_for_company($ticket, $socid)
{
	global $conf, $langs, $user, $db;
	$form = new Form($db);

	$nblines = 0;

	print '<tr class="liste_titre">';
	print '<td class="left" width="25%">'.$langs->trans("Referers").'</td>';
	print '<td class="right" width="25%">'.$langs->trans("NbOfThirdParties").'</td>';
	print '<td class="right" width="25%">'.$langs->trans("NbOfObjectReferers").'</td>';
	print '<td class="right" width="25%">'.$langs->trans("TotalQuantity").'</td>';
	print '</tr>';

	return $nblines++;
}
