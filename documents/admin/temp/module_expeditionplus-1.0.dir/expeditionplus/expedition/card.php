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
 * \file    htdocs/expeditionplus/expedition/card.php
 * \ingroup expeditionplus
 * \brief   PHP file for module expeditionplus.
 */


// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
if (! empty($conf->expedition->enabled)) require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php'; else exit;
if (! empty($conf->commande->enabled)) require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php'; else exit;
if (! empty($conf->product->enabled) || ! empty($conf->service->enabled))  require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php'; else exit;

$action = GETPOST('action','alpha');
$mainmenu = intval(GETPOST('mainmenu','alpha'));

$commandes_expediables = [];
$sql = "SELECT rowid as id";
$sql.= " FROM ".MAIN_DB_PREFIX."commande";
$sql.= " WHERE fk_statut IN (1,2)";
$sql.= " ORDER BY rowid ASC";

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	if ($num)
	{
		$i = 0;
		$products = [];
		while ($i < $num)
		{
			$notshippable=0;
			$obj = $db->fetch_object($resql);
			$generic_commande = null;
			$generic_commande = new Commande($db);
			$generic_commande->id = $obj->id;
			$generic_commande->fetch_lines(1);
				
			$numlines = count($generic_commande->lines); // Loop on each line of order
			for ($lig=0; $lig < $numlines; $lig++)
			{
				$generic_product = null;
				$generic_product = new Product($db);
				$generic_product->id = $generic_commande->lines[$lig]->fk_product;
				$generic_product->load_stock('nobatch');
				
				if(!empty($products[$generic_product->id]['stock_needed'])) $stock_needed = intval($products[$generic_product->id]['stock_needed']);
				else $stock_needed = 0;
				
				if ($generic_commande->lines[$lig]->qty > ($generic_product->stock_reel - $stock_needed)) $notshippable++;

				if($generic_product->stock_reel>0 && $notshippable == 0){
					if(!array_key_exists($generic_product->id,$products))
						$products[$generic_product->id] = [
							'stock_needed' => $generic_commande->lines[$lig]->qty,
							'stock_reel' => $generic_product->stock_reel
						];
					else $products[$generic_product->id]['stock_needed'] += $generic_commande->lines[$lig]->qty;
					if($products[$generic_product->id]['stock_needed']>$generic_product->stock_reel) $notshippable++;
				}
			}
			
			if($notshippable == 0){
				$commandes_expediables[] = $obj->id;
			}
			$i++;
		}
	}
}

$expeditions_validees = [];
$sql = "SELECT rowid as id";
$sql.= " FROM ".MAIN_DB_PREFIX."expedition";
$sql.= " WHERE ";
$sql.= "fk_statut = 1";
$sql.= " ORDER BY rowid ASC";

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	if ($num)
	{
		$i = 0;
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);
			$expeditions_validees[] = $obj->id;
			$i++;
		}
	}
}

if($action == "sendingscreate" && $user->rights->expedition->creer){
	foreach($commandes_expediables as $CommandeID){
		
		$Commande = null;
		$Commande = new Commande($db);
		if ($Commande->fetch($CommandeID))
        {
			$error=0;
			$predef='';

			$db->begin();
			$object = null;
			$object = new Expedition($db);
			$object->note				= "";
			$object->origin				= "commande";
			$object->origin_id			= $CommandeID;
			$object->fk_project         = 0;
			$object->weight				= "NULL";
			$object->sizeH				= "NULL";
			$object->sizeW				= "NULL";
			$object->sizeS				= "NULL";
			$object->size_units			= 0;
			$object->weight_units		= 0;
			
			$object->socid					= $Commande->socid;
			$object->ref_customer			= $Commande->ref_client;
			$object->model_pdf				= $conf->global->EXPEDITION_ADDON_PDF;
			$object->date_delivery			= $Commande->date_livraison;	    // Date delivery planed
			$object->fk_delivery_address	= $Commande->fk_delivery_address;
			$object->shipping_method_id		= $Commande->shipping_method_id;
			$object->tracking_number		= "";
			$object->ref_int				= $Commande->ref_int;
			$object->note_private			= $Commande->note_private;
			$object->note_public			= $Commande->note_public;
			$object->fk_incoterms 			= (!empty($Commande->fk_incoterms) ? $Commande->fk_incoterms : '');
			$object->location_incoterms 	= (!empty($Commande->location_incoterms)?$Commande->location_incoterms:'');

			$batch_line = array();
			$stockLine = array();
			$array_options=array();

			$numAsked=count($Commande->lines);
			$Commande->loadExpeditions();
			#################################################################################
			$indiceAsked = 0;
			$qtyasked = [];
			$qtydelivered = [];
			$idL = [];
			$qtyL = [];
			$batchL = [];
			$entL = [];
            while ($indiceAsked < $numAsked)
            {
                $product = new Product($db);
                $line = $Commande->lines[$indiceAsked];

                // Show product and description
                $type=$line->product_type?$line->product_type:$line->fk_product_type;
                // Try to enhance type detection using date_start and date_end for free lines where type
                // was not saved.
                if (! empty($line->date_start)) $type=1;
                if (! empty($line->date_end)) $type=1;

                // Product label
                if ($line->fk_product > 0)  // If predefined product
                {
                    $product->fetch($line->fk_product);
                    $product->load_stock('warehouseopen');
                }

                // Qty
				$qtyasked[$indiceAsked] = $line->qty;
                $qtyProdCom=$line->qty;

                // Qty already shipped
                $quantityDelivered = $Commande->expeditions[$line->id];
				$qtydelivered[$indiceAsked] = $quantityDelivered;

                // Qty to ship
                $quantityAsked = $line->qty;
				if ($line->product_type == 1 && empty($conf->global->STOCK_SUPPORTS_SERVICES))
				{
					$quantityToBeDelivered = 0;
				}
				else
				{
					$quantityToBeDelivered = $quantityAsked - $quantityDelivered;
				}
								
                $warehouse_id = 0;
				if ($warehouse_id > 0 || ! ($line->fk_product > 0) || empty($conf->stock->enabled))     // If warehouse was already selected or if product is not a predefined, we go into this part with no multiwarehouse selection
				{
					//ship from preselected location
					$stock = + $product->stock_warehouse[$warehouse_id]->real; // Convert to number
					$deliverableQty=min($quantityToBeDelivered, $stock);
					if ($deliverableQty < 0) $deliverableQty = 0;
					if (empty($conf->productbatch->enabled) || ! $product->hasbatch())
					{
						// Quantity to send
						if ($line->product_type == Product::TYPE_PRODUCT || ! empty($conf->global->STOCK_SUPPORTS_SERVICES))
						{
							$idL[$indiceAsked] = $line->id;
							$qtyL[$indiceAsked] = $deliverableQty;
						}
					}
					else
					{
						$subj=0;
						
						if (is_object($product->stock_warehouse[$warehouse_id]) && count($product->stock_warehouse[$warehouse_id]->detail_batch))
						{
							foreach ($product->stock_warehouse[$warehouse_id]->detail_batch as $dbatch)	// $dbatch is instance of Productbatch
							{
								$batchStock = + $dbatch->qty;		// To get a numeric
								$deliverableQty = min($quantityToBeDelivered,$batchStock);
								
								$qtyL[$indiceAsked][$subj] = $deliverableQty;
								
								$batchL[$indiceAsked][$subj] = $dbatch->id;

								$quantityToBeDelivered -= $deliverableQty;
								if ($quantityToBeDelivered < 0)
								{
									$quantityToBeDelivered = 0;
								}
								$subj++;
							}
						}
						else
						{
						    $qtyL[$indiceAsked][$subj] = 0;
						}
					}
				}
				else
				{
					// ship from multiple locations
					if (empty($conf->productbatch->enabled) || ! $product->hasbatch())
					{
					    $idL[$indiceAsked] = $line->id;
						
						$subj=0;

						foreach ($product->stock_warehouse as $warehouse_id=>$stock_warehouse)    // $stock_warehouse is product_stock
						{
							if ($stock_warehouse->real > 0)
							{
								$stock = + $stock_warehouse->real; // Convert it to number
								$deliverableQty = min($quantityToBeDelivered,$stock);
								$deliverableQty = max(0, $deliverableQty);
								// Quantity to send
								if ($line->product_type == Product::TYPE_PRODUCT || ! empty($conf->global->STOCK_SUPPORTS_SERVICES))
								{
									$qtyL[$indiceAsked][$subj] = $deliverableQty;
									$entL[$indiceAsked][$subj] = $warehouse_id;
								}
								
								$quantityToBeDelivered -= $deliverableQty;
								if ($quantityToBeDelivered < 0)
								{
									$quantityToBeDelivered = 0;
								}
								$subj++;
							}
						}
					}
					else
					{
						$subj=0;
						$idL[$indiceAsked] = $line->id;

						foreach ($product->stock_warehouse as $warehouse_id=>$stock_warehouse)
						{
							if (($stock_warehouse->real > 0) && (count($stock_warehouse->detail_batch))) {
						        foreach ($stock_warehouse->detail_batch as $dbatch)
								{
									$batchStock = + $dbatch->qty;		// To get a numeric
									$deliverableQty = min($quantityToBeDelivered,$batchStock);
									if ($deliverableQty < 0) $deliverableQty = 0;
									
									$qtyL[$indiceAsked][$subj] = $deliverableQty;
									$batchL[$indiceAsked][$subj] = $dbatch->id;

									$quantityToBeDelivered -= $deliverableQty;
									if ($quantityToBeDelivered < 0)
									{
										$quantityToBeDelivered = 0;
									}
									$subj++;
								}
							}
						}
					}
					if ($subj == 0) // Line not shown yet, we show it
					{
						if ($line->product_type == Product::TYPE_PRODUCT || ! empty($conf->global->STOCK_SUPPORTS_SERVICES))
						{
						    $disabled=0;
					        if (! empty($conf->productbatch->enabled) && $product->hasbatch())
					        {
                                $disabled=1;
						    }
							if($disabled==0) $qtyL[$indiceAsked][$subj] = 0;
						}
					}
				}
                $indiceAsked++;
            }
			
			
			################################################################################
			$totalqty=0;
			for ($i = 0; $i < $numAsked; $i++)
			{
				$idl=$idL[$i];

				$sub_qty=array();
				$subtotalqty=0;

				$j=0;
				$stockLocation=$entL[$i][0];
				$qty = $qtyL[$i];

				if ($Commande->lines[$i]->product_tobatch)      // If product need a batch number
				{
					if (isset($batchL[$i][0]))
					{
						//shipment line with batch-enable product
						while (isset($qtyL[$i][$j]))
						{
							// save line of detail into sub_qty
							$sub_qty[$j]['q']=$qtyL[$i][$j];				// the qty we want to move for this stock record
							$sub_qty[$j]['id_batch']=$batchL[$i][$j];		// the id into llx_product_batch of stock record to move
							$subtotalqty+=$sub_qty[$j]['q'];

							$j++;
						}

						$batch_line[$i]['detail']=$sub_qty;		// array of details
						$batch_line[$i]['qty']=$subtotalqty;
						$batch_line[$i]['ix_l']=$idl;

						$totalqty+=$subtotalqty;
					}
					else
					{
						// No detail were provided for lots
						if (! empty($qtyL[$i]))
						{
							setEventMessages($langs->trans("StockIsRequiredToChooseWhichLotToUse"), null, 'errors');
						}
					}
				}
				else if (isset($entL[$i][0]))
				{
					//shipment line from multiple stock locations
					$qty = $qtyL[$i][$j];
					while (isset($entL[$i][$j]))
					{
						// save sub line of warehouse
						$stockLine[$i][$j]['qty']=$qtyL[$i][$j];
						$stockLine[$i][$j]['warehouse_id']=$entL[$i][$j];
						$stockLine[$i][$j]['ix_l']=$idl;

						$totalqty+=$qty;

						$j++;
					}
				}
				else
				{
					//var_dump(GETPOST($qty,'int')); var_dump($_POST); var_dump($batch);exit;
					//shipment line for product with no batch management and no multiple stock location
					if ($qty > 0) $totalqty+=$qty;
				}
			}

			if ($totalqty > 0)		// There is at least one thing to ship
			{
				for ($i = 0; $i < $numAsked; $i++)
				{
					$qty = $qtyL[$i];
					if (! isset($batch_line[$i]))
					{
						// not batch mode
						if (isset($stockLine[$i]))
						{
							//shipment from multiple stock locations
							$nbstockline = count($stockLine[$i]);
							for($j = 0; $j < $nbstockline; $j++)
							{
								if ($stockLine[$i][$j]['qty']>0)
								{
									$ret=$object->addline($stockLine[$i][$j]['warehouse_id'], $stockLine[$i][$j]['ix_l'], $stockLine[$i][$j]['qty']);
									if ($ret < 0)
									{
										setEventMessages($object->error, $object->errors, 'errors');
										$error++;
									}
								}
							}
						}
						else
						{
							if ($qtyL[$i] > 0 || ($qtyL[$i] == 0 && $conf->global->SHIPMENT_GETS_ALL_ORDER_PRODUCTS))
							{
								$entrepot_id = is_numeric($entL[$i])?$entL[$i]:'';
								if ($entrepot_id < 0) $entrepot_id='';
								if (! ($Commande->lines[$i]->fk_product > 0)) $entrepot_id = 0;

								$ret=$object->addline($entrepot_id, $idL[$i], $qtyL[$i]);
								if ($ret < 0)
								{
									setEventMessages($object->error, $object->errors, 'errors');
									$error++;
								}
							}
						}
					}
					else
					{
						// batch mode
						if ($batch_line[$i]['qty']>0)
						{
							$ret=$object->addline_batch($batch_line[$i]);
							if ($ret < 0)
							{
								setEventMessages($object->error, $object->errors, 'errors');
								$error++;
							}
						}
					}
				}
				// Fill array 'array_options' with data from add form
				$extrafields = null;
				$extrafields = new ExtraFields($db);
				$ret = $extrafields->setOptionalsFromPost($extralabels, $object);
				if ($ret < 0) $error++;

				if (! $error)
				{
					$ret=$object->create($user);
					if ($ret <= 0)
					{
						setEventMessages($object->error, $object->errors, 'errors');
						$error++;
					} else {
						$object = null;
						$object = new Expedition($db);
						$object->fetch($ret);
						$object->valid($user);
						$object->generateDocument($object->modelpdf, $langs);
						$commandes_expediables = array_diff($commandes_expediables,[$CommandeID]);
					}
				}
			}
			else
			{
				$error++;
			}

			if (! $error)
			{
				$db->commit();
			}
			else
			{
				$db->rollback();
			}
		}
	}
}

if($action == "mergebl" && $user->rights->expedition->lire){
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
	
	$Expedition = new Expedition($db);
	
	$listofobjectid=array();
    $listofobjectthirdparties=array();
    $listofobjectref=array();
	
	if(count($expeditions_validees)>0){
		foreach($expeditions_validees as $expeditionid){
			$Expedition = new Expedition($db);
			$result=$Expedition->fetch($expeditionid);
			if ($result > 0)
			{
				$Expedition->generateDocument($Expedition->modelpdf, $langs);
				$listofobjectid[$expeditionid]=$expeditionid;
				$thirdpartyid=$Expedition->fk_soc?$Expedition->fk_soc:$Expedition->socid;
				$listofobjectthirdparties[$thirdpartyid]=$thirdpartyid;
				$listofobjectref[$expeditionid]=$Expedition->ref;
			}
		}
        
		$uploaddir = $conf->expedition->dir_output."/sending";
		
		$arrayofinclusion=array();
		foreach($listofobjectref as $tmppdf) $arrayofinclusion[]='^'.preg_quote(dol_sanitizeFileName($tmppdf),'/').'\.pdf$';
		foreach($listofobjectref as $tmppdf) $arrayofinclusion[]='^'.preg_quote(dol_sanitizeFileName($tmppdf),'/').'_[a-zA-Z0-9-_]+\.pdf$';
		$listoffiles = dol_dir_list($uploaddir,'all',1,implode('|',$arrayofinclusion),'\.meta$|\.png','date',SORT_DESC,0,true);
		
		// build list of files with full path
		$files = array();
		
		foreach($listofobjectref as $basename)
		{
			$basename = dol_sanitizeFileName($basename);
			foreach($listoffiles as $filefound)
			{
				if (strstr($filefound["name"],$basename))
				{
					$files[] = $uploaddir.'/'.$basename.'/'.$filefound["name"];
					break;
				}
			}
		}
		
		// Define output language
		$outputlangs = $langs;
		$newlang='';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','aZ09')) $newlang=GETPOST('lang_id','aZ09');
		if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$Expedition->thirdparty->default_lang;
		if (! empty($newlang))
		{
			$outputlangs = new Translate("",$conf);
			$outputlangs->setDefaultLang($newlang);
		}
		
		// Create empty PDF
        $formatarray=pdf_getFormat();
        $page_largeur = $formatarray['width'];
        $page_hauteur = $formatarray['height'];
        $format = array($page_largeur,$page_hauteur);

        $pdf=pdf_getInstance($format);

        if (class_exists('TCPDF'))
        {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }
        $pdf->SetFont(pdf_getPDFFont($outputlangs));

        if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

        // Add all others
        foreach($files as $file)
        {
            // Charge un document PDF depuis un fichier.
            $pagecount = $pdf->setSourceFile($file);
            for ($i = 1; $i <= $pagecount; $i++)
            {
                $tplidx = $pdf->importPage($i);
                $s = $pdf->getTemplatesize($tplidx);
                $pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
                $pdf->useTemplate($tplidx);
            }
        }

        // Defined name of merged file
        $filename=strtolower(dol_sanitizeFileName($langs->transnoentities('Shipments')));
        $filename=preg_replace('/\s/','_',$filename);

        // Save merged file
        if ($pagecount)
        {
            $now=dol_now();
            $file=$filename.'_'.dol_print_date($now,'dayhourlog').'.pdf';
            $pdf->Output($file,'D');

			$langs->load(array("expedition_ext@expedition_ext"));
			setEventMessages($langs->trans('FileSuccessfullyBuilt',$filename.'_'.dol_print_date($now,'dayhourlog')), null, 'mesgs');
        }
        else
        {
            setEventMessages($langs->trans('NoPDFAvailableForDocGenAmongChecked'), null, 'errors');
        }
	}		
}

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
$langs->loadLangs(array("expedition_ext@expedition_ext"));

$form = new Form($db);

$formfile = new FormFile($db);
llxHeader("",$langs->trans("ExpeditionsPlus"));

print load_fiche_titre($langs->trans("ExpeditionsPlus"),'','title_products.png');

echo '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
echo '<table class="noborder" width="100%">';
echo '<tr class="liste_titre"><td>'.$langs->trans("ExpeditionsEnMasse").'</td><td></td><td></td></tr>';
if($user->rights->expedition->creer){
	echo '<tr class="oddeven"><td class="titlefield"><b>'.$langs->trans("CommandesExpediables").'</b></td>';
	echo '<td>'.$langs->trans("Thereis").'&nbsp;'.count($commandes_expediables).'&nbsp;'.$langs->trans("CommandesExpediables").'</td>';
	echo '<td><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=sendingscreate">'.$langs->trans("SendingsCreate").'</a></td>';
	echo '</tr>';
}
echo '<tr class="oddeven"><td class="titlefield"><b>'.$langs->trans("MergeBL").'</b></td>';
echo '<td>'.$langs->trans("Thereis").'&nbsp;'.count($expeditions_validees).'&nbsp;'.$langs->trans("BL").$langs->trans("ReadyToPrint").'</td>';
echo '<td><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=mergebl">'.$langs->trans("PDFMerge").'</a></td>';
echo '</tr>';
echo '</table><br>'."\n";
echo '</form>';
/*
echo '<table class="noborder" width="100%">';
echo '<tr class="liste_titre"><td>'.$langs->trans("Support").'</td><td></td><td></td></tr>';
echo '<tr class="oddeven"><td class="titlefield"><b>Github</b></td>';
echo '<td>'.$langs->trans("EP_GITHUBDESC").'</td>';
echo '<td><a class="butAction" href="#" target="_blank">Github</a></td>';
echo '</tr>';
echo '</table><br>'."\n";*/

llxFooter();
$db->close();
?>
