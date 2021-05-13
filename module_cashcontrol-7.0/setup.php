<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2017 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2018-2018 Andreu Bisquerra		<jove@bisquerra.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/cashdesk/admin/cashdesk.php
 *	\ingroup    cashdesk
 *	\brief      Setup page for cashdesk module
 */

$res=@include("../../main.inc.php");
if (! $res) $res=@include("../../../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';

// Security check
if (!$user->admin)
accessforbidden();

$langs->load("admin");
$langs->load("cashdesk");


/*
 * Actions
 */
if (GETPOST('action','alpha') == 'set')
{
	$db->begin();

	if (GETPOST('socid','int') < 0) $_POST["socid"]='';

	$res = dolibarr_set_const($db,"CASHCONTROL_CASH",(GETPOST('CASHCONTROL_CASH','alpha') > 0 ? GETPOST('CASHCONTROL_CASH','alpha') : ''),'chaine',0,'',$conf->entity);
	$res = dolibarr_set_const($db,"CASHCONTROL_BANK",(GETPOST('CASHCONTROL_BANK','alpha') > 0 ? GETPOST('CASHCONTROL_BANK','alpha') : ''),'chaine',0,'',$conf->entity);

	dol_syslog("admin/cashdesk: level ".GETPOST('level','alpha'));

	if (! $res > 0) $error++;

 	if (! $error)
    {
        $db->commit();
	    setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
    }
    else
    {
        $db->rollback();
	    setEventMessages($langs->trans("Error"), null, 'errors');
    }
}

/*
 * View
 */

$form=new Form($db);
$formproduct=new FormProduct($db);

llxHeader('',$langs->trans("CashControlSetup"));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("CashControlSetup"),$linkback,'title_setup');
print '<br>';


// Mode
$var=true;
print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td><td>'.$langs->trans("Value").'</td>';
print "</tr>\n";

print '<tr class="oddeven"><td>'.$langs->trans("CashDeskBankAccountForSell").'</td>';
print '<td colspan="2">';
$form->select_comptes($conf->global->CASHCONTROL_CASH,'CASHCONTROL_CASH',0,"courant=2",1);
print '</td></tr>';

	
print '<tr class="oddeven"><td>'.$langs->trans("CashDeskBankAccountForCB").'</td>';
print '<td colspan="2">';
$form->select_comptes($conf->global->CASHCONTROL_BANK,'CASHCONTROL_BANK',0,"courant=1",1);
print '</td></tr>';


print '</table>';
print '<br>';

print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Save").'"></div>';

print "</form>\n";

llxFooter();
$db->close();
