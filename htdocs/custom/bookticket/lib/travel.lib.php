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
 *	\file       htdocs/core/lib/travel.lib.php
 *	\brief      Ensemble de fonctions de base pour le module produit et service
 * 	\ingroup	travel
 */

/**
 * Prepare array with list of tabs
 *
 * @param  Travel	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function travel_prepare_head($object)
{
	global $db, $langs, $conf, $user;
	$langs->load("bookticket");

	$label = $langs->trans('travel');

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/custom/bookticket/travel_card.php?id=".$object->id;
	$head[$h][1] = $label;
	$head[$h][2] = 'card';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'travel');

	/* Notes
	if (empty($conf->global->MAIN_DISABLE_NOTES_TAB))
	{
		$nbNote = 0;
		if (!empty($object->note_private)) $nbNote++;
		if (!empty($object->note_public)) $nbNote++;
		$head[$h][0] = DOL_URL_ROOT.'/travel/note.php?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		$head[$h][2] = 'note';
		$h++;
	}*/

	// Attachments
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->service->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	if (!empty($conf->global->TRAVEL_USE_OLD_PATH_FOR_PHOTO)) {
		$upload_dir = $conf->service->multidir_output[$object->entity].'/'.get_exdir($object->id, 2, 0, 0, $object, 'travel').$object->id.'/photos';
		$nbFiles += count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	}
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/custom/bookticket/travel_document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	$head[$h][2] = 'documents';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'travel', 'remove');

	/* Log
	$head[$h][0] = DOL_URL_ROOT.'/custom/bookticket/travel_agenda.php?id='.$object->id;
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
function travel_admin_prepare_head()
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/custom/bookticket/admin/travel.php";
	$head[$h][1] = $langs->trans('Parameters');
	$head[$h][2] = 'general';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'travel_admin');

	complete_head_from_modules($conf, $langs, null, $head, $h, 'travel_admin', 'remove');

	return $head;
}


/**
 * Show stats for company
 *
 * @param	Travel		$travel	travel object
 * @param 	int			$socid		Thirdparty id
 * @return	integer					NB of lines shown into array
 */
function show_stats_for_company($travel, $socid)
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


	// travel
	if (!empty($conf->contrat->enabled) && $user->rights->contrat->lire)
	{
		$nblines++;
		$ret = $travel->load_stats_contrat($socid);
		if ($ret < 0) dol_print_error($db);
		$langs->load("contracts");
		print '<tr><td>';
		print '<a href="contrat.php?id='.$travel->id.'">'.img_object('', 'contract', 'class="paddingright"').$langs->trans("Contracts").'</a>';
		print '</td><td class="right">';
		print $travel->stats_contrat['customers'];
		print '</td><td class="right">';
		print $travel->stats_contrat['nb'];
		print '</td><td class="right">';
		print $travel->stats_contrat['qty'];
		print '</td>';
		print '</tr>';
	}

	return $nblines++;
}
