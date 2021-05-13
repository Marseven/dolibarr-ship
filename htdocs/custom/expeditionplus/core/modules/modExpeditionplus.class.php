<?php
/* Copyright (C) 2018,2019 Cédric Ancelin <cedric@c3do.fr>
 *
 * Donations https://www.paypal.me/cancelin
 *
 * This program (Expedition plus) is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
/**
 * 	\defgroup   expeditionplus     Module expeditionplus
 *  \brief      Extension du module Expedition.
 *
 *  \file       htdocs/expeditionplus/core/modules/modExpeditionplus
 *  \brief      Description and activation file for module expeditionplus
 */
include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php';

class modExpeditionplus extends DolibarrModules
{
	public function __construct($db)
	{
        global $langs,$conf;

        $this->db = $db;
		$this->numero = 840103;
		
		$this->rights_class = 'expeditionplus';
		$this->family = "crm";
		$this->module_position = '40';
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "Extension pour le module expedition";
		$this->descriptionlong = "Permet, entre autres, la création des Expeditions en masse, et la Fusion des BL en un seul PDF";

		$this->editor_name = 'Cédric Ancelin';
		$this->editor_url = '';
		
		$this->version = '1.0';
		
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->picto='sending';
		
		$this->module_parts = array('triggers' => 0,'login' => 0,'substitutions' => 0,'menus' => 0,'theme' => 0,'tpl' => 0,'barcode' => 0,'models' => 0,
									'css' => array(),
	 								'js' => array(),
									'hooks' => array());

		$this->config_page_url = array();

		$this->hidden = false;
		$this->depends = array("modExpedition");
		$this->requiredby = array();
		$this->conflictwith = array();
		$this->langfiles = array("expeditionplus@expeditionplus");
		$this->phpmin = array(5,4);
		$this->need_dolibarr_version = array(9,0);
		$this->warnings_activation = array();
		$this->warnings_activation_ext = array();

		$this->const = array();

		if (! isset($conf->expeditionplus) || ! isset($conf->expeditionplus->enabled))
		{
			$conf->expeditionplus=new stdClass();
			$conf->expeditionplus->enabled=0;
		}

        $this->tabs = array();
		$this->dictionaries=array();		
        $this->boxes = array();
		$this->cronjobs = array();
		
		$this->rights = array();
		$r = 0;
		$this->rights[$r][0] = 8401031;
		$this->rights[$r][1] = 'ExpeditionPlus';
		$this->rights[$r][3] = 0;
		$this->rights[$r][4] = 'main';
		$this->rights[$r][5] = 'active';
		$r ++;
		
		$this->menu = array();
		$r=0;
		$this->menu[$r++]=array('fk_menu'=>'fk_mainmenu=products,fk_leftmenu=sendings',		// Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode of parent menu
								'type'=>'left',							// This is a Left menu entry
								'titre'=>'ExpeditionsPlus',
								'mainmenu'=>'products',
								'leftmenu'=>'create_sendings',
								'url'=>'/expeditionplus/expedition/card.php',
								'langs'=>'expeditionplus@expeditionplus',			// Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
								'position'=>$r,
								'enabled'=>'$user->rights->expeditionplus->main->active',	// Define condition to show or hide menu entry. Use '$conf->monmodule->enabled' if entry must be visible if module is enabled.
								'perms'=>'$user->rights->expeditionplus->main->active',							// Use 'perms'=>'$user->rights->monmodule->level1->level2' if you want your menu with a permission rules
								'target'=>'',
								'user'=>2);
	}

	public function init($options='')
	{
		$sql = array();
		return $this->_init($sql, $options);
	}

	public function remove($options = '')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}
}
