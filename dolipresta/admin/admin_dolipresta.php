<?php
/*
* 2015 PJ CONSEIL
*
* NOTICE OF LICENSE
*
* This source file is subject to License
* You may not distribute this module even for free
*
* @author PJ CONSEIL
* @version RC2
*/

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once(DOL_DOCUMENT_ROOT.'/dolipresta/lib/PSWebServiceLibrary.php');

$langs->load("admin");
$langs->load("dolipresta@dolipresta");

if (! $user->admin)
	accessforbidden();


//sauvegarde case a cocher
if ($_GET[upd] == 1) {
	$SYNCH_STOCK = GETPOST("SYNCH_STOCK");
	$SYNCH_ORDER = GETPOST("SYNCH_ORDER");
	$db->begin();
	dolibarr_set_const($db,'SYNCH_STOCK',GETPOST("SYNCH_STOCK"),'chaine',0,'',$conf->entity);
	dolibarr_set_const($db,'SYNCH_ORDER',GETPOST("SYNCH_ORDER"),'chaine',0,'',$conf->entity);
	$db->commit();	
} else {
	$SYNCH_STOCK=$conf->global->SYNCH_STOCK;
	$SYNCH_ORDER=$conf->global->SYNCH_ORDER;
}

$IS_SYNCH_STOCK_CHECKED	= "";
$IS_SYNCH_ORDER_CHECKED	= "";
if ($SYNCH_STOCK == 'on')
	$IS_SYNCH_STOCK_CHECKED = "checked";
if ($SYNCH_ORDER == 'on')
	$IS_SYNCH_ORDER_CHECKED = "checked";

//gestion de l'affichage de la table presta
$IS_TABLE_PRESTA_VISIBLE = 'none';
if (($SYNCH_STOCK == 'on') || ($SYNCH_ORDER == 'on')) {
	$IS_TABLE_PRESTA_VISIBLE = 'on';
}




// Sauvegardes parametres
$actionsave=GETPOST("save");
if ($actionsave)
{
    $j=0;

    $db->begin();

    $j+=dolibarr_set_const($db,'WEBSERVICES_KEY',trim(GETPOST("WEBSERVICES_KEY")),'chaine',0,'',$conf->entity);
		
    if ($j >= 1)
    {
        $db->commit();
        setEventMessage($langs->trans("SetupSaved"));
    } else {
        $db->rollback();
        setEventMessage($langs->trans("Error"), 'errors');
    }
	
	$querys = "SELECT rowid FROM ".MAIN_DB_PREFIX."dolipresta_wsurl";
	$resqls=$db->query($querys);
	$nb=0;
	if ($resqls)
	{
		$num = $db->num_rows($resqls);
		$i = 0;
		if ($num)
		{
			while ($i < $num)
			{
				$obj = $db->fetch_object($resqls);
				if ($obj)
				{
					$checkbox = GETPOST($obj->rowid."checkbox");
					$url = GETPOST($obj->rowid."url");
					$wskey = GETPOST($obj->rowid."wskey");
					$trigram = GETPOST($obj->rowid."trigram");
					
					if ($checkbox) { //on supprime
						$queryd = "DELETE FROM ".MAIN_DB_PREFIX."dolipresta_wsurl WHERE rowid = $obj->rowid";
						$resqld=$db->query($queryd);
					}
					else { //on UPDATE
						$queryu = "UPDATE ".MAIN_DB_PREFIX."dolipresta_wsurl SET url='$url', wskey='$wskey', trigram='$trigram' WHERE rowid = $obj->rowid;";
						$resqlu=$db->query($queryu);
					}
				}
				$i++;
			}
			$db->commit();
		}
	}
	
	$newurl = GETPOST("newurl");
	$newwskey = GETPOST("newwskey");
	$newtrigram = GETPOST("newtrigram");
	if (($newurl<>"") && ($newwskey<>"")) {  //nouvelle ligne renseignée => INSERT
		$queryi = "INSERT INTO ".MAIN_DB_PREFIX."dolipresta_wsurl (url, wskey, trigram) VALUES ('$newurl', '$newwskey', '$newtrigram');";
		$resqli=$db->query($queryi);
		$db->commit();
	}
	elseif (($newurl<>"") ||($newwskey<>"")) setEventMessage($langs->trans("dolibarr@error"), 'errors');
	
	
	//sauvegarde des statuts de commande
	if ($SYNCH_ORDER == 'on') {
		$query = 'SELECT  * FROM '.MAIN_DB_PREFIX.'dolipresta_dolibarr_statut';
		$resql = $db->query($query);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $db->fetch_object($resql);
					if ($obj)
					{
						$id = GETPOST('select_'.$obj->id);
						dol_syslog('test recup post selected :'.$id);
						$query = 'UPDATE '.MAIN_DB_PREFIX.'dolipresta_dolibarr_statut SET id_prestashop_statut = '.$id.' WHERE id = '.$obj->id.';';
						$resqlu = $db->query($query);
						$db->commit();
						//print 'test recup post '.$obj->id.' : '.$test.'<br>';
					}
					$i++;
				}

			}
		}
	}
}


/*
 *	View
 */
llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre("Prestashop to Dolibarr",$linkback,'setup');


print $langs->trans("dolipresta@title")."
		
		<br>
		<br>

		<form name='agendasetupform' action='".$_SERVER["PHP_SELF"]."' method='post'>
		
			<input type='hidden' name='token' value='".$_SESSION['newtoken']."'>			
			
			<img src='../img/dolibarr.png' width='70px'>
			<table class='noborder' style='width: 900px !important; '>
				<tr class='liste_titre' style='background-color: rgb(63, 156, 239) !important; font-size: 13px !important;'>
					<td colspan='3'>".$langs->trans("dolipresta@KeyForWebServicesAccess")."</td>
				</tr>
				<tr class='liste_titre' style='background-color: rgb(63, 156, 239) !important; font-size: 13px !important;'>
					<td width='50%'>".$langs->trans("Parameter")."</td>
					<td>".$langs->trans("Value")."</td>
					<td>&nbsp;</td>
				</tr>
				<tr class='impair'>
					<td class='fieldrequired' style='font-size: 13px !important;'>".$langs->trans("dolipresta@KeyDolibarr")."</td>
					<td><input type='text' class='flat' id='WEBSERVICES_KEY' name='WEBSERVICES_KEY' value='".(GETPOST('WEBSERVICES_KEY')?GETPOST('WEBSERVICES_KEY'):(! empty($conf->global->WEBSERVICES_KEY)?$conf->global->WEBSERVICES_KEY:''))."' size='40'></td>
					<td><input type='submit' name='save' class='button' value='".$langs->trans("Validate")."'></td>
				</tr>
			</table>

			<br><br><br><br>

			<img src='../img/prestashop.png' width='70px'>
			<br>";
			
	//case a cocher
	print  "<input type='checkbox' onclick=\"document.forms['agendasetupform'].action='".$_SERVER["PHP_SELF"]."?upd=1'; document.forms['agendasetupform'].submit();\" name='SYNCH_STOCK' id='SYNCH_STOCK' $IS_SYNCH_STOCK_CHECKED> ".$langs->trans("dolipresta@synchronizeStock")."
			<br><br>
			<input type='checkbox' onclick=\"document.forms['agendasetupform'].action='".$_SERVER["PHP_SELF"]."?upd=1'; document.forms['agendasetupform'].submit();\" name='SYNCH_ORDER' id='SYNCH_ORDER' $IS_SYNCH_ORDER_CHECKED> ".$langs->trans("dolipresta@synchronizeOrder")."
			<br><br>";

	
	//tableau de recup de l'url des WS presta		
	print	"<br>
			<table class='noborder' id='prestatable' style='width: 900px !important; display:$IS_TABLE_PRESTA_VISIBLE;'>
				<tr class='liste_titre' style='background-color: rgb(66, 81, 95) !important; font-size: 13px !important;'>
					<td colspan='5'>".$langs->trans("dolipresta@KeyOfPrestashopWebServicesAccess")."</td>
				</tr>
				<tr class='liste_titre' style='background-color: rgb(222, 46, 133) !important; font-size: 13px !important;'>
					<td width='40%'>url Prestashop*</td>
					<td>".$langs->trans("dolipresta@Key")."*</td>
					<td>".$langs->trans("dolipresta@Trigram")."</td>
					<td> </td>
					<td> </td>
				</tr>";

			
	//récupération des données WS
	$confWsDone = false;
	$query = "SELECT * FROM ".MAIN_DB_PREFIX."dolipresta_wsurl";
	$resql = $db->query($query);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num)
		{
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				if ($obj)
				{
					//count on dolipresta_prestashop_states
					$nb_states = getNbStates($db);
					if ($nb_states < 2) {
						getPrestashopOrderStates($obj->url, $obj->wskey, $db, $langs);
					}
					print "	<tr class='impair'>
								<td><input type='text' class='flat' id='".$obj->rowid."url' name='".$obj->rowid."url'         value='".$obj->url."' size='40'></td>
								<td><input type='text' class='flat' id='".$obj->rowid."wskey' name='".$obj->rowid."wskey'     value='".$obj->wskey."' size='40'></td>
								<td><input type='text' class='flat' id='".$obj->rowid."trigram' name='".$obj->rowid."trigram' value='".$obj->trigram."' size='3'></td>
								<td>
									<input type='checkbox' class='flat' id='".$obj->rowid."checkbox' name='".$obj->rowid."checkbox'> ".$langs->trans("Delete")." 
									<input type='submit' name='save' class='button' value='".$langs->trans("Update")."'>
								</td>
							</tr>";
					$confWsDone = true;
				}
				$i++;
			}
		}
		
		print "	<tr class='impair'>
			<td><input type='text' class='flat' id='newurl' name='newurl'         placeholder='exemple:http://www.mon_magasin.com/' size='40'></td>
			<td><input type='text' class='flat' id='newwskey' name='newwskey'     placeholder='prestashop web service key' size='40'></td>
			<td><input type='text' class='flat' id='newtrigram' name='newtrigram' placeholder='PTS' size='3'></td>
			<td><input type='submit' name='save' class='button' value='".$langs->trans("Add")."'></td>
		</tr>";
		
	} else {
		
		print "	<tr class='impair'>
			<td><input type='text' class='flat' id='newurl' name='newurl'         placeholder='exemple:http://www.mon_magasin.com/' size='40'></td>
			<td><input type='text' class='flat' id='newwskey' name='newwskey'     placeholder='prestashop web service key' size='40'></td>
			<td> </td>
			<td><input type='submit' name='save' class='button' value='".$langs->trans("Add")."'></td>
		</tr>";
	}	
	print "</table>";


	/**
	* affichage des statut
	*/
	if ($SYNCH_ORDER == 'on' && $confWsDone) {
		//récup des statuts Prestashop
		$query = 'SELECT  * FROM '.MAIN_DB_PREFIX.'dolipresta_prestashop_statut';
		$resql = $db->query($query);
		$statut_prestashop = array();
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			if ($num)
			{
				//$db->begin();
				while ($i < $num)
				{
					$obj = $db->fetch_object($resql);
					if ($obj)
					{
						$statut_prestashop[$obj->id] = $obj->libelle;
					}
					$i++;
				}
			}
		}
		
		// parcours des statuts dolib
		$query = 'SELECT  * FROM '.MAIN_DB_PREFIX.'dolipresta_dolibarr_statut';
		$resql = $db->query($query);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			if ($num)
			{
				print	"<br>
			<table class='noborder' id='prestatable' style='width: 450px !important; display:$IS_TABLE_PRESTA_VISIBLE;'>
				<tr class='liste_titre' style='background-color: rgb(66, 81, 95) !important; font-size: 13px !important;'>
					<td colspan='5'>".$langs->trans("dolipresta@PrestashopStatusTabTitle")."</td>
				</tr>
				<tr class='liste_titre' style='background-color: rgb(222, 46, 133) !important; font-size: 13px !important;'>
					<td align='right'>".$langs->trans("dolipresta@DolibarrStatus")."*</td>
					<td> &nbsp; &nbsp; ".$langs->trans("dolipresta@MatchingStatus")."</td>
				</tr>";
				
				//$db->begin();
				while ($i < $num)
				{
					$obj = $db->fetch_object($resql);
					if ($obj)
					{
						print '<tr>	
									<td align="right" style="font-size: 13px !important;">'.$langs->trans($obj->libelle).' : </td>
									<td> &nbsp; &nbsp; <select name="select_'.$obj->id.'">';
						foreach ($statut_prestashop as $id => $statut) {
							$toPrint = '<option value="'.$id.'"';
							if ($obj->id_prestashop_statut == $id) 
								$toPrint .= ' selected';
							$toPrint .= '>'.$statut."</option>";
							print $toPrint;
						}
						print '</select></td></tr>';
					}
					$i++;
				}
				print "<tr><td colspan='2' align='center'><br><input type='submit' name='save' class='button' value='".$langs->trans("Validate")."'></td></tr>";
				print '</table>';
			}
		}
	}
	
	print "</form>";


/**
* get order states
*/
function getPrestashopOrderStates($urlBoutique, $cleWS, $db, $langs) {
	try {
		$webService = new PrestaShopWebservice($urlBoutique, $cleWS, false);
		//$url = $urlBoutique.'/API/order_states?display=%5Bid%2C%20name%5D';
		$url = $urlBoutique.'webservice/dispatcher.php?url=order_states&display=%5Bid%2C%20name%5D';
		$xmlProd = $webService->get(array('url' => $url))->order_states->order_state;
		
		foreach ($xmlProd as $order_state) {
			$query = 'INSERT INTO '.MAIN_DB_PREFIX.'dolipresta_prestashop_statut'.
			' (id, libelle) VALUES ('.$order_state->id.", '".$order_state->name->language[0]."')";
			$resql=$db->query($query);
		}
		$db->commit();
		return 0;
	} catch (PrestaShopWebserviceException $ex) {
		$trace = $ex->getTrace(); 
		$errorCode = $trace[0]['args'][0];
		if ($errorCode == 401)
			setEventMessage($langs->trans('Bad auth key').' : '.$cleWS);
		elseif ($errorCode == 302)
			setEventMessage($langs->trans('BAD URL').' : '.$urlBoutique);
		else
			setEventMessage($langs->trans('Other error').' : '.'<br />'.$ex->getMessage());
	}
}

function getNbStates($db) {
	$query = "SELECT Count(*) as nb_states FROM ".MAIN_DB_PREFIX."dolipresta_prestashop_statut";
	$resql = $db->query($query);
	if ($resql) {
		$num = $db->num_rows($resql);
		$i = 0;
		if ($num)
		{
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				if ($obj)
				{
					return $obj->nb_states;
				}
				$i++;
			}
		}
	}
	return 0;
}

llxFooter();
$db->close();
