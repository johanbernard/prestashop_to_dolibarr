<?php
/**
* 2015 PJ CONSEIL
*
* NOTICE OF LICENSE
*
* This source file is subject to License
* You may not distribute this module even for free
*
* @author    PJ CONSEIL
* @copyright 2014 PJ CONSEIL
* @license   NoLicence
* @version   RC2
*/

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
dol_include_once("dolipresta/lib/PSWebServiceLibrary.php");

/**
 *  Class of triggers for dolipresta module
 */
class InterfaceDolipresta //extends DolibarrTriggers
{

	public $family = 'dolipresta';
	public $picto = 'technic';
	public $description = "Triggers stock modification and orders status modification";
	public $version = '3.8.1';

    /**
     * @var DoliDB Database handler.
     */
    public $db;

    /**
     *   Constructor
     *
     *   @param DoliDB $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
        
        $this->name = preg_replace('/^Interface/i', '', get_class($this));
	    $this->family = 'dolipresta';
        $this->description = "Triggers of the module Dolipresta";
        $this->version = 'dolibarr'; // 'development', 'experimental', 'dolibarr' or version
        $this->picto = 'dolipresta@dolipresta';
    }

	/**
     * Function called when a Dolibarrr business event is done.
	 * All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
     *
     * @param string		$action		Event action code
     * @param Object		$object     Object concerned. Some context information may also be provided into array property object->context.
     * @param User		    $user       Object user
     * @param Translate 	$langs      Object langs
     * @param conf		    $conf       Object conf
     * @return int         				<0 if KO, 0 if no triggered ran, >0 if OK
     */
    //public function run_trigger($action, $object, User $user, Translate $langs, Conf $conf)
    public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
		// Put here code you want to execute when a Dolibarr business events occurs.
        // Data and type of action are stored into $object and $action
	    dol_syslog('triggers dolipresta-action : '.$action);
	    switch ($action) {
		    /*
		    // Users
		    case 'USER_CREATE':
		    case 'USER_MODIFY':
		    case 'USER_NEW_PASSWORD':
		    case 'USER_ENABLEDISABLE':
		    case 'USER_DELETE':
		    case 'USER_SETINGROUP':
		    case 'USER_REMOVEFROMGROUP':
				
		    case 'USER_LOGIN':
		    case 'USER_LOGIN_FAILED':
		    case 'USER_LOGOUT':

		    // Actions
		    case 'ACTION_MODIFY':
		    case 'ACTION_CREATE':
		    case 'ACTION_DELETE':

		    // Groups
		    case 'GROUP_CREATE':
		    case 'GROUP_MODIFY':
		    case 'GROUP_DELETE':

			// Companies
		    case 'COMPANY_CREATE':
		    case 'COMPANY_MODIFY':
		    case 'COMPANY_DELETE':

			// Contacts
		    case 'CONTACT_CREATE':
		    case 'CONTACT_MODIFY':
		    case 'CONTACT_DELETE':
		    case 'CONTACT_ENABLEDISABLE':

			// Products
		    case 'PRODUCT_CREATE':
		    case 'PRODUCT_MODIFY':
		    case 'PRODUCT_DELETE':
		    case 'PRODUCT_PRICE_MODIFY':
*/
			//Stock mouvement
		    case 'STOCK_MOVEMENT':
				$this->synchStock($object);
				break;
			/*	
			//MYECMDIR
		    case 'MYECMDIR_DELETE':
		    case 'MYECMDIR_CREATE':
		    case 'MYECMDIR_MODIFY':
*/
			// Customer orders
			//case 'ORDER_SENTBYMAIL':
			//case 'ORDER_DELETE':
			//case 'ORDER_CLONE':
		    case 'ORDER_CREATE':
				$this->addModeReglement($object);
				break;

		    case 'ORDER_VALIDATE':				// Validation
			case 'ORDER_REOPEN':				// Validation bis
		    case 'ORDER_CANCEL':				// Annulation
		    case 'ORDER_CLASSIFY_BILLED':		// Traitée
			case 'ORDER_CLOSE':					// Délivrée
			case 'SHIPPING_CREATE':				// En cours de traitement
				if (!defined("IS_A_WEBSERVICE_CALL_FROM_PRESTASHOP")){
					$this->synchOrderStatus($action, $object);
				}
				break;
		    //case 'LINEORDER_INSERT':
		    //case 'LINEORDER_UPDATE':
		    //case 'LINEORDER_DELETE':
/*
			// Supplier orders
		    case 'ORDER_SUPPLIER_CREATE':
		    case 'ORDER_SUPPLIER_CLONE':
		    case 'ORDER_SUPPLIER_VALIDATE':
		    case 'ORDER_SUPPLIER_DELETE':
		    case 'ORDER_SUPPLIER_APPROVE':
		    case 'ORDER_SUPPLIER_REFUSE':
		    case 'ORDER_SUPPLIER_CANCEL':
		    case 'ORDER_SUPPLIER_SENTBYMAIL':
            case 'ORDER_SUPPLIER_DISPATCH':
		    case 'LINEORDER_SUPPLIER_DISPATCH':
		    case 'LINEORDER_SUPPLIER_CREATE':
		    case 'LINEORDER_SUPPLIER_UPDATE':

			// Proposals
		    case 'PROPAL_CREATE':
		    case 'PROPAL_CLONE':
		    case 'PROPAL_MODIFY':
		    case 'PROPAL_VALIDATE':
		    case 'PROPAL_SENTBYMAIL':
		    case 'PROPAL_CLOSE_SIGNED':
		    case 'PROPAL_CLOSE_REFUSED':
		    case 'PROPAL_DELETE':
		    case 'LINEPROPAL_INSERT':
		    case 'LINEPROPAL_UPDATE':
		    case 'LINEPROPAL_DELETE':

			// Askpricesupplier
		    case 'ASKPRICESUPPLIER_CREATE':
		    case 'ASKPRICESUPPLIER_CLONE':
		    case 'ASKPRICESUPPLIER_MODIFY':
		    case 'ASKPRICESUPPLIER_VALIDATE':
		    case 'ASKPRICESUPPLIER_SENTBYMAIL':
		    case 'ASKPRICESUPPLIER_CLOSE_SIGNED':
		    case 'ASKPRICESUPPLIER_CLOSE_REFUSED':
		    case 'ASKPRICESUPPLIER_DELETE':
		    case 'LINEASKPRICESUPPLIER_INSERT':
		    case 'LINEASKPRICESUPPLIER_UPDATE':
		    case 'LINEASKPRICESUPPLIER_DELETE':
		    
			// Contracts
		    case 'CONTRACT_CREATE':
		    case 'CONTRACT_ACTIVATE':
		    case 'CONTRACT_CANCEL':
		    case 'CONTRACT_CLOSE':
		    case 'CONTRACT_DELETE':
		    case 'LINECONTRACT_CREATE':
		    case 'LINECONTRACT_UPDATE':
		    case 'LINECONTRACT_DELETE':

			// Bills
		    case 'BILL_CREATE':
		    case 'BILL_CLONE':
		    case 'BILL_MODIFY':
		    case 'BILL_VALIDATE':
		    case 'BILL_UNVALIDATE':
		    case 'BILL_SENTBYMAIL':
		    case 'BILL_CANCEL':
		    case 'BILL_DELETE':
		    case 'BILL_PAYED':
		    case 'LINEBILL_INSERT':
		    case 'LINEBILL_UPDATE':
		    case 'LINEBILL_DELETE':

			//Supplier Bill
		    case 'BILL_SUPPLIER_CREATE':
		    case 'BILL_SUPPLIER_UPDATE':
		    case 'BILL_SUPPLIER_DELETE':
		    case 'BILL_SUPPLIER_PAYED':
		    case 'BILL_SUPPLIER_UNPAYED':
		    case 'BILL_SUPPLIER_VALIDATE':
		    case 'BILL_SUPPLIER_UNVALIDATE':
		    case 'LINEBILL_SUPPLIER_CREATE':
		    case 'LINEBILL_SUPPLIER_UPDATE':
		    case 'LINEBILL_SUPPLIER_DELETE':

			// Payments
		    case 'PAYMENT_CUSTOMER_CREATE':
		    case 'PAYMENT_SUPPLIER_CREATE':
		    case 'PAYMENT_ADD_TO_BANK':
		    case 'PAYMENT_DELETE':
		    
		    // Online  
		    case 'PAYMENT_PAYBOX_OK':
		    case 'PAYMENT_PAYPAL_OK':
		    
			// Donation
		    case 'DON_CREATE':
		    case 'DON_UPDATE':
		    case 'DON_DELETE':

			// Interventions
		    case 'FICHINTER_CREATE':
		    case 'FICHINTER_MODIFY':
		    case 'FICHINTER_VALIDATE':
		    case 'FICHINTER_DELETE':
		    case 'LINEFICHINTER_CREATE':
		    case 'LINEFICHINTER_UPDATE':
		    case 'LINEFICHINTER_DELETE':
			case 'FICHINTER_CLASSIFY_BILLED':

			// Members
		    case 'MEMBER_CREATE':
		    case 'MEMBER_VALIDATE':
		    case 'MEMBER_SUBSCRIPTION':
		    case 'MEMBER_MODIFY':
		    case 'MEMBER_NEW_PASSWORD':
		    case 'MEMBER_RESILIATE':
		    case 'MEMBER_DELETE':

			// Categories
		    case 'CATEGORY_CREATE':
		    case 'CATEGORY_MODIFY':
		    case 'CATEGORY_DELETE':
		    */
		    case 'CATEGORY_MODIFY':
				$this->syncCategory($object);
				break;

			/*
			// Projects
		    case 'PROJECT_CREATE':
		    case 'PROJECT_MODIFY':
		    case 'PROJECT_DELETE':

			// Project tasks
		    case 'TASK_CREATE':
		    case 'TASK_MODIFY':
		    case 'TASK_DELETE':

			// Task time spent
		    case 'TASK_TIMESPENT_CREATE':
		    case 'TASK_TIMESPENT_MODIFY':
		    case 'TASK_TIMESPENT_DELETE':

			// Shipping
		    case 'SHIPPING_CREATE':
		    case 'SHIPPING_MODIFY':
		    case 'SHIPPING_VALIDATE':
		    case 'SHIPPING_SENTBYMAIL':
		    case 'SHIPPING_DELETE':
			*/
	    }

        return 0;
	}

	function synchStock($object) {
		global $conf;
		
		$SYNCH_STOCK = $conf->global->SYNCH_STOCK;
		
		$error = 0; // Error counter
		$id = "";
		// MAJ du stock
		$get_ref = $this->getProductRefExterne($object->product_id);
		$ref_ext = $get_ref['ref_ext'];
		$reel = $get_ref['reel'];
		$id_product = intval(substr($ref_ext,3,10));
		$id_attribut = intval(substr($ref_ext,13,10));
		$trigram = substr($ref_ext,0,3);
		$query = "SELECT url, wskey FROM ".MAIN_DB_PREFIX."dolipresta_wsurl WHERE trigram = '$trigram' OR trigram = '';";
		$resql=$this->db->query($query);
		$nb=0;
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num)
			{
				$this->db->begin();
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					if ($obj)
					{
						$cleWS = $obj->wskey;
						$urlBoutique = $obj->url;
						if ($SYNCH_STOCK == 'on')
							$this->moveStock($urlBoutique, $cleWS, $id_product, $id_attribut, $reel);
					}
					$i++;
				}
				$this->db->commit();
			}
		}
		
		//gestion des erreurs
		if (! $error)
		{
			$this->results = array('myreturn' => $myvalue);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		}
		else
		{
			$this->errors[] = 'Error message';
			return -1;
		}
	}

	/**
	* move de x le stock d'un produit ou d'un attribut
	*/
	function moveStock($urlBoutique, $cleWS, $id_product, $id_attribut = 0, $nbr = 0) {
		
		try {
			$webService = new PrestaShopWebservice($urlBoutique, $cleWS, false);

			$optP['resource'] = 'products';
			$optP['id'] = $id_product;
			$xmlProd = $webService->get($optP)->product->associations->stock_availables->stock_availables;
			if(empty($xmlProd)){
				$xmlProd = $webService->get($optP)->product->associations->stock_availables->stock_available;
			}
			for($nbrStock = 0; $xmlProd[$nbrStock]; $nbrStock++) {
				$id_stock_available = $xmlProd[$nbrStock]->id;
				$id_product_attribute = $xmlProd[$nbrStock]->id_product_attribute;
				
				$optS['resource'] = 'stock_availables';
				$optS['id'] = $id_stock_available;
				$xmlStock = $webService->get($optS);
				if (($id_attribut == "" || $id_attribut == 0) && $id_product_attribute == 0) {
					$xmlStock->stock_available->quantity = $nbr;
					$optS['putXml'] = $xmlStock->asXML();
					$webService->edit($optS);
					return 1;
				} else if (($id_attribut != "" && $id_attribut != 0) && $id_product_attribute == $id_attribut) {
					$xmlStock->stock_available->quantity = $nbr;
					$optS['putXml'] = $xmlStock->asXML();
					$webService->edit($optS);
					return 1;
				}
			}
			return 0;
		} catch (PrestaShopWebserviceException $ex) {
			$trace = $ex->getTrace(); 
			$errorCode = $trace[0]['args'][0];
			if ($errorCode == 401)
				dol_syslog('dolipresta - Bad auth key : '.$cleWS);
			elseif ($errorCode == 302)
				dol_syslog('dolipresta - BAD URL : '.$urlBoutique);
			else
				dol_syslog('dolipresta - Other error : <br />'.$ex->getMessage());
		}
	}
	
	public function getProductRefExterne($rowid){
		$ref_ext = "";
		$reel = 0;
		$query = "SELECT p.ref_ext, sum(ps.reel) as reel FROM ".MAIN_DB_PREFIX."product p, ".MAIN_DB_PREFIX."product_stock ps where p.rowid = $rowid AND ps.fk_product = p.rowid";
		$resql=$this->db->query($query);
		
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					if ($obj)
					{
						// You can use here results
						$ref_ext = $obj->ref_ext;
						$reel = $obj->reel;
					}
					$i++;
				}
			}
			else {	
				$ref_ext = "";
				$reel = 0;
			}
		}
		
		return Array('ref_ext'=>$ref_ext, 'reel'=>$reel);
	}
	
	/*
	 * Synchronize a category after be modified on Dolibarr
	 */
	function syncCategory($object) {
		global $conf;
		
		$import_key = $this->getCategoryImportKey($object->id);
		if (empty($import_key)) return 0;
		
		$presta_category_id = intval(substr($import_key,3)); // quit the prefix: SHP00000003 -> 3
		if (!is_numeric($presta_category_id) || $presta_category_id == 0) return 0;
		
		$trigram = substr($import_key,0,3); // SHP00000003 -> SHP
		$error = 0; // Error counter
		
		$query = "SELECT url, wskey FROM ".MAIN_DB_PREFIX."dolipresta_wsurl WHERE trigram = '$trigram' OR trigram = '';";
		$resql=$this->db->query($query);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num)
			{
				$this->db->begin();
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					if ($obj)
					{
						$cleWS = $obj->wskey;
						$urlBoutique = $obj->url;
						$this->wsUpdateCategory($urlBoutique, $cleWS, $presta_category_id, $object->label);
					}
					$i++;
				}
				$this->db->commit();
			}
		}
		
		//gestion des erreurs
		if (! $error)
		{
			$this->results = array('myreturn' => $myvalue);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		}
		else
		{
			$this->errors[] = 'Error message';
			return -1;
		}
	}	

	public function getCategoryImportKey($rowid){
		$import_key = "";
		$resql = $this->db->query("SELECT import_key FROM ".MAIN_DB_PREFIX."categorie where rowid = $rowid");
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);
				if ($obj) $import_key = $obj->import_key;
			}
		}
		return $import_key;
	}
	

	function wsUpdateCategory($urlBoutique, $cleWS, $presta_category_id, $label) {
		
		try {
			$webService = new PrestaShopWebservice($urlBoutique, $cleWS, false);

			// check that the category exists on Prestashop
			$options = array('resource' => 'categories', 'id' => $presta_category_id);
			$xml = $webService->get($options);
			$xmlCat = $xml->children()->children();
			dol_syslog('------> return of WS UpdateCategory : '.print_r($xmlCat,true));
			if(empty($xmlCat->id)) return 1;
			
			// modify object to send
			$ii=0;
			foreach($xmlCat->name->language as $foo){
				$xmlCat->name->language[$ii] = $label;
				$ii++;
			}
			
			// send the updated XML object through webservice
			dol_syslog('------> $xmlCat : '.print_r($xmlCat,true));
			$options['putXml'] = $xmlCat ;//->asXML();
			dol_syslog('------> sending to WS UpdateCategory put : '.print_r($options,true));
			$res = $webService->edit($options);
			dol_syslog('------> returned from WS UpdateCategory put : '.print_r($res,true));
			
		} catch (PrestaShopWebserviceException $ex) {
			$trace = $ex->getTrace(); 
			$errorCode = $trace[0]['args'][0];
			if ($errorCode == 401)
				dol_syslog('dolipresta - Bad auth key : '.$cleWS);
			elseif ($errorCode == 302)
				dol_syslog('dolipresta - BAD URL : '.$urlBoutique);
			else
				dol_syslog('dolipresta - Other error : <br />'.$ex->getMessage());
		}
	}
		
	/*
	 * Synchroniza change on order status
	 */
	public function synchOrderStatus($action, $object) {
		global $conf;
		$SYNCH_ORDER = $conf->global->SYNCH_ORDER;
		dol_syslog('synchOrderStatus');
		if ($SYNCH_ORDER == 'on') {
			dol_syslog('synchOrder action : '.print_r($action,true));
			$ref_ext = $object->ref_ext;
			switch ($action) {
				case 'ORDER_VALIDATE':				// Validation
				case 'ORDER_REOPEN':
					$statut = 2;
					break;
				case 'ORDER_CANCEL':				// Annulation
					$statut = 6;
					break;
				case 'ORDER_CLASSIFY_BILLED':		// Traitée
					$statut = 5;
					break;
				case 'ORDER_CLOSE':					// Délivrée
					$statut = 4;
					break;
				case 'SHIPPING_CREATE':	// Envoi en cours
					$statut = 3;
					$order = new Commande($this->db);
					$result=$order->fetch($object->origin_id);
					$ref_ext = $order->ref_ext;
					break;
			}
			dol_syslog('synchOrder ref ext : '.$ref_ext);
			$id_order_prestashop = intval(substr($ref_ext,3,10));
			//get prestashop status
			$query = 'SELECT id_prestashop_statut FROM '.MAIN_DB_PREFIX.'dolipresta_dolibarr_statut WHERE id = '.$statut.';';
			$resql=$this->db->query($query);
			if ($resql)
			{
				$num = $this->db->num_rows($resql);
				$i = 0;
				if ($num)
				{
					while ($i < $num)
					{
						$obj = $this->db->fetch_object($resql);
						if ($obj)
						{
							// You can use here results
							$prestashop_statut = $obj->id_prestashop_statut;
						}
						$i++;
					}
				}
				else {	
					$prestashop_statut = 0;
				}
			}
			//call WS update order status
			if ($prestashop_statut > 0) {
				$query = "SELECT url, wskey FROM ".MAIN_DB_PREFIX."dolipresta_wsurl WHERE trigram = '$trigram' OR trigram = '';";
				$resql=$this->db->query($query);
				$nb=0;
				if ($resql)
				{
					$num = $this->db->num_rows($resql);
					$i = 0;
					if ($num)
					{
						$this->db->begin();
						while ($i < $num)
						{
							$obj = $this->db->fetch_object($resql);
							if ($obj)
							{
								$cleWS = $obj->wskey;
								$urlBoutique = $obj->url;
								$this->updatePrestashopOrderStatus($urlBoutique, $cleWS, $id_order_prestashop, $prestashop_statut);
							}
							$i++;
						}
						$this->db->commit();
					}
				}
			}
		}
	}
	
	public function updatePrestashopOrderStatus($urlBoutique, $cleWS, $id_order_prestashop, $prestashop_statut) {
		dol_syslog('dolipresta - updatePrestashopOrderStatus');
		try {
			$webService = new PrestaShopWebservice($urlBoutique, $cleWS, false);
			$optPost['resource'] = 'order_histories';
			$optPost['postXml'] = '<?xml version="1.0" encoding="UTF-8"?>
							<prestashop xmlns:xlink="http://www.w3.org/1999/xlink">
							<order_history>
								<id/>
								<id_order_state>'.$prestashop_statut.'</id_order_state>
								<id_order>'.$id_order_prestashop.'</id_order>
								<id_employee/>
								<date_add/>
							</order_history>
							</prestashop>';
			dol_syslog('objet post : '.print_r($optPost,true));
			$xmlretour = $webService->add($optPost);
			dol_syslog('retour post order_histories : '.print_r($optPost,true));
			return 1;
			
		} catch (PrestaShopWebserviceException $ex) {
			$trace = $ex->getTrace(); 
			$errorCode = $trace[0]['args'][0];
			if ($errorCode == 401)
				dol_syslog('dolipresta - Bad auth key : '.$cleWS);
			elseif ($errorCode == 302)
				dol_syslog('dolipresta - BAD URL : '.$urlBoutique);
			else
				dol_syslog('dolipresta - Other error : <br />'.$ex->getMessage());
		}
	
		return 0;
	}
	
	public function addModeReglement($object){
		dol_syslog('dolipresta - ass mode reglement : '.print_r($object,true));
		$mode_reglement = $object->mode_reglement;
		$mode_reglement_code = $object->mode_reglement_code;
		if (isset($mode_reglement)) {  // on récup l'id du mode de reglement et on le créée si inexistant
			$orderid = $object->id;
			$id = $this->selectModeReglement($mode_reglement_code);
			if($id == "") {
				$query = "INSERT INTO ".MAIN_DB_PREFIX."c_paiement (id, code, libelle, type, active, module) SELECT (IFNULL(max(id), 0)+1), '$mode_reglement_code', '$mode_reglement', 0, 1, '$mode_reglement_code' FROM ".MAIN_DB_PREFIX."c_paiement";
				$this->db->begin();   // Debut transaction
				$this->db->query($query);
				$this->db->commit();       // Valide
				$id = $this->selectModeReglement($mode_reglement_code);
			}
			
			if($id != "") {
				//update en base orders sur ordersid avec l'id du mode de reglement
				$query = "UPDATE ".MAIN_DB_PREFIX."commande SET fk_mode_reglement = $id where rowid = $orderid";
				$this->db->begin();
				$resql=$this->db->query($query);
				$this->db->commit();
			}
		}
	}
	
	public function selectModeReglement($mode_reglement_code){
		$id = "";
		$query = "SELECT id FROM ".MAIN_DB_PREFIX."c_paiement where module = '$mode_reglement_code'";
		$resql=$this->db->query($query);
		
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			if ($num)
			{
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					if ($obj)
					{
						$id = $obj->id;
					}
					$i++;
				}
			}
			else {	
				$id = "";
			}
		}
		
		return $id;
	}
}

class xmlStateOrder {
	public $id;
	public $id_order_state;
	public $id_order;
	public $id_employee;
	public $date_add;
}
