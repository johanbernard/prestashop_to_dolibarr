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
if (! defined("NOCSRFCHECK"))    define("NOCSRFCHECK",'1');

set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

// Load Dolibarr environment
$res=0;
// Try master.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/master.inc.php");
// Try master.inc.php into web root detected using web root caluclated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/master.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/master.inc.php");
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/master.inc.php")) $res=@include(dirname(substr($tmp, 0, ($i+1)))."/master.inc.php");
// Try master.inc.php using relative path
if (! $res && file_exists("../../master.inc.php")) $res=@include("../../master.inc.php");
if (! $res && file_exists("../../../master.inc.php")) $res=@include("../../../master.inc.php");
if (! $res) die("Include of master fails");
require_once NUSOAP_PATH.'/nusoap.php';        // Include SOAP
require_once DOL_DOCUMENT_ROOT.'/core/lib/ws.lib.php';

require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';




dol_syslog("Call Dolibarr webservices interfaces");

$langs->load("main");

// Enable and test if module web services is enabled
if (empty($conf->global->MAIN_MODULE_WEBSERVICES))
{
	$langs->load("admin");
	dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled");
	print $langs->trans("WarningModuleNotActive",'WebServices').'.<br><br>';
	print $langs->trans("ToActivateModule");
	exit;
}

// Create the soap Object
$server = new nusoap_server();
$server->soap_defencoding='UTF-8';
$server->decode_utf8=false;
$ns='http://www.dolibarr.org/ns/';
$server->configureWSDL('WebServicesDolibarrOrder',$ns);
$server->wsdl->schemaTargetNamespace=$ns;


// Define WSDL Authentication object
$server->wsdl->addComplexType(
		'authentication',
		'complexType',
		'struct',
		'all',
		'',
		array(
				'dolibarrkey' => array('name'=>'dolibarrkey','type'=>'xsd:string'),
				'sourceapplication' => array('name'=>'sourceapplication','type'=>'xsd:string'),
				'login' => array('name'=>'login','type'=>'xsd:string'),
				'password' => array('name'=>'password','type'=>'xsd:string'),
				'entity' => array('name'=>'entity','type'=>'xsd:string')
		)
);
// Define WSDL Return object
$server->wsdl->addComplexType(
		'result',
		'complexType',
		'struct',
		'all',
		'',
		array(
				'result_code' => array('name'=>'result_code','type'=>'xsd:string'),
				'result_label' => array('name'=>'result_label','type'=>'xsd:string'),
		)
);

// Define other specific objects
$server->wsdl->addComplexType(
		'line',
		'complexType',
		'struct',
		'all',
		'',
		array(
				'id' => array('name'=>'id','type'=>'xsd:string'),
		        'type' => array('name'=>'type','type'=>'xsd:int'),
				'fk_commande' => array('name'=>'fk_commande','type'=>'xsd:int'),
				'fk_parent_line' => array('name'=>'fk_parent_line','type'=>'xsd:int'),
				'desc' => array('name'=>'desc','type'=>'xsd:string'),
				'qty' => array('name'=>'qty','type'=>'xsd:double'),
				'price' => array('name'=>'price','type'=>'xsd:double'),
				'unitprice' => array('name'=>'unitprice','type'=>'xsd:double'),
				'vat_rate' => array('name'=>'vat_rate','type'=>'xsd:double'),

				'remise' => array('name'=>'remise','type'=>'xsd:double'),
				'remise_percent' => array('name'=>'remise_percent','type'=>'xsd:double'),

				'total_net' => array('name'=>'total_net','type'=>'xsd:double'),
				'total_vat' => array('name'=>'total_vat','type'=>'xsd:double'),
				'total' => array('name'=>'total','type'=>'xsd:double'),

				'date_start' => array('name'=>'date_start','type'=>'xsd:date'),
				'date_end' => array('name'=>'date_end','type'=>'xsd:date'),

				// From product
		        'product_id' => array('name'=>'product_id','type'=>'xsd:int'),
				'product_ref' => array('name'=>'product_ref','type'=>'xsd:string'),
				'product_label' => array('name'=>'product_label','type'=>'xsd:string'),
				'product_desc' => array('name'=>'product_desc','type'=>'xsd:string')
		)
);

/*$server->wsdl->addComplexType(
		'LinesArray',
		'complexType',
		'array',
		'',
		'SOAP-ENC:Array',
		array(),
		array(
				array(
						'ref'=>'SOAP-ENC:arrayType',
						'wsdl:arrayType'=>'tns:line[]'
				)
		),
		'tns:line'
);*/
$server->wsdl->addComplexType(
		'LinesArray2',
		'complexType',
		'array',
		'sequence',
		'',
		array(
				'line' => array(
						'name' => 'line',
						'type' => 'tns:line',
						'minOccurs' => '0',
						'maxOccurs' => 'unbounded'
				)
		)
);

$server->wsdl->addComplexType(
		'order',
		'complexType',
		'struct',
		'all',
		'',
		array(
				'id' => array('name'=>'id','type'=>'xsd:string'),
				'ref' => array('name'=>'ref','type'=>'xsd:string'),
				'ref_client' => array('name'=>'ref_client','type'=>'xsd:string'),
				'ref_ext' => array('name'=>'ref_ext','type'=>'xsd:string'),
				'ref_int' => array('name'=>'ref_int','type'=>'xsd:string'),
				'thirdparty_id' => array('name'=>'thirdparty_id','type'=>'xsd:int'),
				'status' => array('name'=>'status','type'=>'xsd:int'),
				'facturee' => array('name'=>'facturee','type'=>'xsd:string'),
				'total_net' => array('name'=>'total_net','type'=>'xsd:double'),
				'total_vat' => array('name'=>'total_vat','type'=>'xsd:double'),
				'total_localtax1' => array('name'=>'total_localtax1','type'=>'xsd:double'),
				'total_localtax2' => array('name'=>'total_localtax2','type'=>'xsd:double'),
				'total' => array('name'=>'total','type'=>'xsd:double'),
				'date' => array('name'=>'date','type'=>'xsd:date'),
		        'date_creation' => array('name'=>'date_creation','type'=>'xsd:dateTime'),
		        'date_validation' => array('name'=>'date_validation','type'=>'xsd:dateTime'),
	    	    'date_modification' => array('name'=>'date_modification','type'=>'xsd:dateTime'),
				'remise' => array('name'=>'remise','type'=>'xsd:string'),
				'remise_percent' => array('name'=>'remise_percent','type'=>'xsd:string'),
				'remise_absolue' => array('name'=>'remise_absolue','type'=>'xsd:string'),
				'source' => array('name'=>'source','type'=>'xsd:string'),
				'note_private' => array('name'=>'note_private','type'=>'xsd:string'),
				'note_public' => array('name'=>'note_public','type'=>'xsd:string'),
				'project_id' => array('name'=>'project_id','type'=>'xsd:string'),

				'mode_reglement_id' => array('name'=>'mode_reglement_id','type'=>'xsd:string'),
				'mode_reglement_code' => array('name'=>'mode_reglement_code','type'=>'xsd:string'),
				'mode_reglement' => array('name'=>'mode_reglement','type'=>'xsd:string'),
				'cond_reglement_id' => array('name'=>'cond_reglement_id','type'=>'xsd:string'),
				'cond_reglement_code' => array('name'=>'cond_reglement_code','type'=>'xsd:string'),
				'cond_reglement' => array('name'=>'cond_reglement','type'=>'xsd:string'),
				'cond_reglement_doc' => array('name'=>'cond_reglement_doc','type'=>'xsd:string'),

				'date_livraison' => array('name'=>'date_livraison','type'=>'xsd:date'),
				'fk_delivery_address' => array('name'=>'fk_delivery_address','type'=>'xsd:int'),
				'demand_reason_id' => array('name'=>'demand_reason_id','type'=>'xsd:string'),

				'lines' => array('name'=>'lines','type'=>'tns:LinesArray2')
		)
);
/*
$server->wsdl->addComplexType(
		'OrdersArray',
		'complexType',
		'array',
		'',
		'SOAP-ENC:Array',
		array(),
		array(
				array(
						'ref'=>'SOAP-ENC:arrayType',
						'wsdl:arrayType'=>'tns:order[]'
				)
		),
		'tns:order'
);*/
$server->wsdl->addComplexType(
		'OrdersArray2',
		'complexType',
		'array',
		'sequence',
		'',
		array(
				'order' => array(
						'name' => 'order',
						'type' => 'tns:order',
						'minOccurs' => '0',
						'maxOccurs' => 'unbounded'
				)
		)
);



// 5 styles: RPC/encoded, RPC/literal, Document/encoded (not WS-I compliant), Document/literal, Document/literal wrapped
// Style merely dictates how to translate a WSDL binding to a SOAP message. Nothing more. You can use either style with any programming model.
// http://www.ibm.com/developerworks/webservices/library/ws-whichwsdl/
$styledoc='rpc';       // rpc/document (document is an extend into SOAP 1.0 to support unstructured messages)
$styleuse='encoded';   // encoded/literal/literal wrapped
// Better choice is document/literal wrapped but literal wrapped not supported by nusoap.

// Register WSDL
$server->register(
		'getOrder',
		array('authentication'=>'tns:authentication','id'=>'xsd:string','ref'=>'xsd:string','ref_ext'=>'xsd:string'), // Entry values
		array('result'=>'tns:result','order'=>'tns:order'),	// Exit values
		$ns,
		$ns.'#getOrder',
		$styledoc,
		$styleuse,
		'WS to get a particular invoice'
);

$server->register(
		'getOrdersForThirdParty',
		array('authentication'=>'tns:authentication','idthirdparty'=>'xsd:string'),	// Entry values
		array('result'=>'tns:result','orders'=>'tns:OrdersArray2'),	// Exit values
		$ns,
		$ns.'#getOrdersForThirdParty',
		$styledoc,
		$styleuse,
		'WS to get all orders of a third party'
);

$server->register(
		'createOrder',
		array('authentication'=>'tns:authentication','order'=>'tns:order'),	// Entry values
		array('result'=>'tns:result','id'=>'xsd:string','ref'=>'xsd:string'),	// Exit values
		$ns,
		$ns.'#createOrder',
		$styledoc,
		$styleuse,
		'WS to create an order'
);


// Register WSDL
$server->register(
		'validOrder',
		array('authentication'=>'tns:authentication','id'=>'xsd:string'),	// Entry values
		array('result'=>'tns:result','id'=>'xsd:string','ref'=>'xsd:string'),	// Exit values
		$ns,
		$ns.'#validOrder',
		$styledoc,
		$styleuse,
		'WS to valid an order'
);

// Register WSDL
$server->register(
		'updateOrderStatus',
		array('authentication'=>'tns:authentication','id'=>'xsd:string','status'=>'xsd:string'),	// Entry values
		array('result'=>'tns:result','id'=>'xsd:string','ref'=>'xsd:string'),	// Exit values
		$ns,
		$ns.'#updateOrderStatus',
		$styledoc,
		$styleuse,
		'WS to update an order status'
);

/**
 * Get order from id, ref or ref_ext.
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$id					Id
 * @param	string		$ref				Ref
 * @param	string		$ref_ext			Ref_ext
 * @return	array							Array result
 */
function getOrder($authentication,$id='',$ref='',$ref_ext='')
{
	global $db,$conf,$langs;

	dol_syslog("Function: getOrder login=".$authentication['login']." id=".$id." ref=".$ref." ref_ext=".$ref_ext);

	if ($authentication['entity']) $conf->entity=$authentication['entity'];

	// Init and check authentication
	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;

	$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

	if ($fuser->societe_id) $socid=$fuser->societe_id;

	// Check parameters
	if (! $error && (($id && $ref) || ($id && $ref_ext) || ($ref && $ref_ext)))
	{
		$error++;
		$errorcode='BAD_PARAMETERS'; $errorlabel="Parameter id, ref and ref_ext can't be both provided. You must choose one or other but not both.";
	}

	if (! $error)
	{
		$fuser->getrights();

		if ($fuser->rights->commande->lire)
		{
			$order=new Commande($db);
			$result=$order->fetch($id,$ref,$ref_ext);
			if ($result > 0)
			{
				// Security for external user
				if( $socid && ( $socid != $order->socid) )
				{
					$error++;
					$errorcode='PERMISSION_DENIED'; $errorlabel=$order->socid.'User does not have permission for this request';
				}

				if(!$error)
				{

					$linesresp=array();
					$i=0;
					foreach($order->lines as $line)
					{
						//var_dump($line); exit;
						$linesresp[]=array(
						'id'=>$line->rowid,
						'fk_commande'=>$line->fk_commande,
						'fk_parent_line'=>$line->fk_parent_line,
						'desc'=>$line->desc,
						'qty'=>$line->qty,
						'price'=>$line->price,
						'unitprice'=>$line->subprice,
						'vat_rate'=>$line->tva_tx,
						'remise'=>$line->remise,
						'remise_percent'=>$line->remise_percent,
						'product_id'=>$line->fk_product,
						'product_type'=>$line->product_type,
						'total_net'=>$line->total_ht,
						'total_vat'=>$line->total_tva,
						'total'=>$line->total_ttc,
						'date_start'=>$line->date_start,
						'date_end'=>$line->date_end,
						'product_ref'=>$line->product_ref,
						'product_label'=>$line->product_label,
						'product_desc'=>$line->product_desc
						);
						$i++;
					}

					// Create order
					$objectresp = array(
					'result'=>array('result_code'=>'OK', 'result_label'=>''),
					'order'=>array(
					'id' => $order->id,
					'ref' => $order->ref,
					'ref_client' => $order->ref_client,
					'ref_ext' => $order->ref_ext,
					'ref_int' => $order->ref_int,
					'thirdparty_id' => $order->socid,
					'status' => $order->statut,

					'total_net' => $order->total_ht,
					'total_vat' => $order->total_tva,
					'total_localtax1' => $order->total_localtax1,
					'total_localtax2' => $order->total_localtax2,
					'total' => $order->total_ttc,
					'project_id' => $order->fk_project,

					'date' => $order->date_commande?dol_print_date($order->date_commande,'dayrfc'):'',
					'date_creation' => $invoice->date_creation?dol_print_date($invoice->date_creation,'dayhourrfc'):'',
					'date_validation' => $invoice->date_validation?dol_print_date($invoice->date_creation,'dayhourrfc'):'',
					'date_modification' => $invoice->datem?dol_print_date($invoice->datem,'dayhourrfc'):'',

					'remise' => $order->remise,
					'remise_percent' => $order->remise_percent,
					'remise_absolue' => $order->remise_absolue,

					'source' => $order->source,
					'facturee' => $order->facturee,
					'note_private' => $order->note_private,
					'note_public' => $order->note_public,
					'cond_reglement_id' => $order->cond_reglement_id,
					'cond_reglement_code' => $order->cond_reglement_code,
					'cond_reglement' => $order->cond_reglement,
					'mode_reglement_id' => $order->mode_reglement_id,
					'mode_reglement_code' => $order->mode_reglement_code,
					'mode_reglement' => $order->mode_reglement,

					'date_livraison' => $order->date_livraison,
					'fk_delivery_address' => $order->fk_delivery_address,

					'demand_reason_id' => $order->demand_reason_id,
					'demand_reason_code' => $order->demand_reason_code,

					'lines' => $linesresp
					));
				}
			}
			else
			{
				$error++;
				$errorcode='NOT_FOUND'; $errorlabel='Object not found for id='.$id.' nor ref='.$ref.' nor ref_ext='.$ref_ext;
			}
		}
		else
		{
			$error++;
			$errorcode='PERMISSION_DENIED'; $errorlabel='User does not have permission for this request';
		}
	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}


/**
 * Get list of orders for third party
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$idthirdparty		Id of thirdparty
 * @return	array							Array result
 */
function getOrdersForThirdParty($authentication,$idthirdparty)
{
	global $db,$conf,$langs;

	dol_syslog("Function: getOrdersForThirdParty login=".$authentication['login']." idthirdparty=".$idthirdparty);

	if ($authentication['entity']) $conf->entity=$authentication['entity'];

	// Init and check authentication
	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;
	$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

	if ($fuser->societe_id) $socid=$fuser->societe_id;

	// Check parameters
	if (! $error && empty($idthirdparty))
	{
		$error++;
		$errorcode='BAD_PARAMETERS'; $errorlabel='Parameter id is not provided';
	}

	if (! $error)
	{
		$linesorders=array();

		$sql.='SELECT c.rowid as orderid';
		$sql.=' FROM '.MAIN_DB_PREFIX.'commande as c';
		$sql.=" WHERE c.entity = ".$conf->entity;
		if ($idthirdparty != 'all' ) $sql.=" AND c.fk_soc = ".$db->escape($idthirdparty);


		$resql=$db->query($sql);
		if ($resql)
		{
			$num=$db->num_rows($resql);
			$i=0;
			while ($i < $num)
			{
				// En attendant remplissage par boucle
				$obj=$db->fetch_object($resql);

				$order=new Commande($db);
				$order->fetch($obj->orderid);

				// Sécurité pour utilisateur externe
				if( $socid && ( $socid != $order->socid) )
				{
					$error++;
					$errorcode='PERMISSION_DENIED'; $errorlabel=$order->socid.' User does not have permission for this request';
				}

				if(!$error)
				{

					// Define lines of invoice
					$linesresp=array();
					foreach($order->lines as $line)
					{
						$linesresp[]=array(
						'id'=>$line->rowid,
						'type'=>$line->product_type,
						'fk_commande'=>$line->fk_commande,
						'fk_parent_line'=>$line->fk_parent_line,
						'desc'=>$line->desc,
						'qty'=>$line->qty,
						'price'=>$line->price,
						'unitprice'=>$line->subprice,
						'tva_tx'=>$line->tva_tx,
						'remise'=>$line->remise,
						'remise_percent'=>$line->remise_percent,
						'total_net'=>$line->total_ht,
						'total_vat'=>$line->total_tva,
						'total'=>$line->total_ttc,
						'date_start'=>$line->date_start,
						'date_end'=>$line->date_end,
						'product_id'=>$line->fk_product,
						'product_ref'=>$line->product_ref,
						'product_label'=>$line->product_label,
						'product_desc'=>$line->product_desc
						);
					}

					// Now define invoice
					$linesorders[]=array(
					'id' => $order->id,
					'ref' => $order->ref,
					'ref_client' => $order->ref_client,
					'ref_ext' => $order->ref_ext,
					'ref_int' => $order->ref_int,
					'socid' => $order->socid,
					'status' => $order->statut,

					'total_net' => $order->total_ht,
					'total_vat' => $order->total_tva,
					'total_localtax1' => $order->total_localtax1,
					'total_localtax2' => $order->total_localtax2,
					'total' => $order->total_ttc,
					'project_id' => $order->fk_project,

					'date' => $order->date_commande?dol_print_date($order->date_commande,'dayrfc'):'',

					'remise' => $order->remise,
					'remise_percent' => $order->remise_percent,
					'remise_absolue' => $order->remise_absolue,

					'source' => $order->source,
					'facturee' => $order->facturee,
					'note_private' => $order->note_private,
					'note_public' => $order->note_public,
					'cond_reglement_id' => $order->cond_reglement_id,
					'cond_reglement' => $order->cond_reglement,
					'cond_reglement_doc' => $order->cond_reglement_doc,
					'cond_reglement_code' => $order->cond_reglement_code,
					'mode_reglement_id' => $order->mode_reglement_id,
					'mode_reglement' => $order->mode_reglement,
					'mode_reglement_code' => $order->mode_reglement_code,

					'date_livraison' => $order->date_livraison,

					'demand_reason_id' => $order->demand_reason_id,
					'demand_reason_code' => $order->demand_reason_code,

					'lines' => $linesresp
					);
				}
				$i++;
			}

			$objectresp=array(
			'result'=>array('result_code'=>'OK', 'result_label'=>''),
			'orders'=>$linesorders

			);
		}
		else
		{
			$error++;
			$errorcode=$db->lasterrno(); $errorlabel=$db->lasterror();
		}
	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}

	return $objectresp;
}


/**
 * Create order
 *
 * @param	array		$authentication		Array of authentication information
 * @param	array		$order				Order info
 * @return	int								Id of new order
 */
function createOrder($authentication,$order)
{
	global $db,$conf,$langs;

	require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

	$now=dol_now();

	dol_syslog("Function: createOrder login=".$authentication['login']." socid :".$order['socid']);

	if ($authentication['entity']) $conf->entity=$authentication['entity'];

	// Init and check authentication
	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;
	$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

	// Check parameters


	if (! $error)
	{
		$newobject=new Commande($db);
		$newobject->socid=$order['thirdparty_id'];
		$newobject->type=$order['type'];
		$newobject->ref_ext=$order['ref_ext'];
		$newobject->date=dol_stringtotime($order['date'],'dayrfc');
		$newobject->date_lim_reglement=dol_stringtotime($order['date_due'],'dayrfc');
		$newobject->note_private=$order['note_private'];
		$newobject->note_public=$order['note_public'];
		$newobject->statut=0;	// We start with status draft
		$newobject->facturee=$order['facturee'];
		$newobject->fk_project=$order['project_id'];
		$newobject->cond_reglement_id=$order['cond_reglement_id'];
		//PJ ajout mode de reglt
		$newobject->mode_reglement = $order['mode_reglement'];
		$newobject->mode_reglement_code = $order['mode_reglement_code'];
		//PJ fin
		$newobject->demand_reason_id=$order['demand_reason_id'];
		$newobject->date_creation=$now;

		// Trick because nusoap does not store data with same structure if there is one or several lines
		$arrayoflines=array();
		if (isset($order['lines']['line'][0])) $arrayoflines=$order['lines']['line'];
		else $arrayoflines=$order['lines'];

		foreach($arrayoflines as $key => $line)
		{
			// $key can be 'line' or '0','1',...
			$newline=new OrderLine($db);

			$newline->type=$line['type'];
			$newline->desc=$line['desc'];
			$newline->fk_product=$line['product_id'];
			$newline->tva_tx=$line['vat_rate'];
			$newline->qty=$line['qty'];
			$newline->price=$line['price'];
			$newline->subprice=$line['unitprice'];
			$newline->total_ht=$line['total_net'];
			$newline->total_tva=$line['total_vat'];
			$newline->total_ttc=$line['total'];
			$newobject->lines[]=$newline;
		}


		$db->begin();
		dol_syslog("Webservice server_order:: order creation start", LOG_DEBUG);
		$result=$newobject->create($fuser);
		dol_syslog('Webservice server_order:: order creation done with $result='.$result, LOG_DEBUG);
		if ($result < 0)
		{
			dol_syslog("Webservice server_order:: order creation failed", LOG_ERR);
			$error++;

		}
		else {
			dol_syslog('tentative de up le statut : '.$order['status']);
			if ($order['status'] == '2') { //Validation
				$result=$order->valid($fuser);
				dol_syslog('result valid order : '.print_r($result, true));
				if ($result >= 0)
				{
					// Define output language
					$outputlangs = $langs;
					commande_pdf_create($db, $order, $order->modelpdf, $outputlangs, 0, 0, 0);
					dol_syslog('pdf created');
				}
			} elseif($order['status'] == '3'){ //En cours de traitement
				dol_syslog('statut 3 !');
				$result=$order->setStatut(2);
				dol_syslog('result "En cours de traitement" order : '.print_r($result, true));
			} elseif($order['status'] == '4'){ //délivrée
				$result=$order->cloture($fuser);
				dol_syslog('result cloture order : '.print_r($result, true));
			} elseif($order['status'] == '5'){ //traitée
				$result=$order->classifyBilled();
				dol_syslog('result classifyBilled order : '.print_r($result, true));
			} elseif($status == '6'){ //annulée
					$result=$order->cancel();
					dol_syslog('result cancel order : '.print_r($result, true));
				}
			if ($result < 0) {
				$db->rollback();
				$error++;
				$errorcode='KO';
				$errorlabel=$newobject->error;
			}
		}
		
		//gestion des adresses factu et livraison
		if (!$error) {
			//delivery address
			if ($order['id_address_delivery']) {
				$resultg = getImportedSocpeople($order['id_address_delivery']);
				if ($resultg >=0) {
					//link adresse delivery
					$newobject->add_contact($resultg, 102, $source='external',$notrigger=0);
				}
			}
			
			//invoice address
			if ($order['id_address_invoice']) {
				$resultg = getImportedSocpeople($order['id_address_invoice']);
				if ($resultg >=0) {
					//link adresse invoice
					$newobject->add_contact($resultg, 100, $source='external',$notrigger=0);
				}
			}
		}

		if ($result >= 0)
		{
			dol_syslog("Webservice server_order:: order creation & validation succeeded, commit", LOG_DEBUG);
			$db->commit();
			$objectresp=array('result'=>array('result_code'=>'OK', 'result_label'=>''),'id'=>$newobject->id,'ref'=>$newobject->ref);
		}
		else
		{
			dol_syslog("Webservice server_order:: order creation or validation failed, rollback", LOG_ERR);
			$db->rollback();
			$error++;
			$errorcode='KO';
			$errorlabel=$newobject->error;
		}

	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}
	return $objectresp;
}


/**
 * Valid an order
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$id					Id of order to validate
 * @return	array							Array result
 */
function validOrder($authentication,$id='')
{
	global $db,$conf,$langs;

	dol_syslog("Function: validOrder login=".$authentication['login']." id=".$id." ref=".$ref." ref_ext=".$ref_ext);

	// Init and check authentication
	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;
	if ($authentication['entity']) $conf->entity=$authentication['entity'];
	$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

	if (! $error)
	{
		$fuser->getrights();

		if ($fuser->rights->commande->lire)
		{
			$order=new Commande($db);
			$result=$order->fetch($id,$ref,$ref_ext);

			$order->fetch_thirdparty();
			$db->begin();
			if ($result > 0)
			{
				$result=$order->valid($fuser);
				dol_syslog('result valid order : '.print_r($result, true));
				if ($result	>= 0)
				{
					// Define output language
					$outputlangs = $langs;
					commande_pdf_create($db, $order, $order->modelpdf, $outputlangs, 0, 0, 0);
					dol_syslog('pdf created');
				}
				else
				{
					$db->rollback();
					$error++;
					$errorcode='KO';
					$errorlabel=$newobject->error;
				}
			}
			else
			{
				$db->rollback();
				$error++;
				$errorcode='KO';
				$errorlabel=$newobject->error;
			}

		}
		else
		{
			$db->rollback();
			$error++;
			$errorcode='KO';
			$errorlabel=$newobject->error;
		}

	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}
	else
	{
		$db->commit();
		$objectresp= array('result'=>array('result_code'=>'OK', 'result_label'=>''));
	}
	dol_syslog('validOrder result : '.print_r($objectresp, true));
	return $objectresp;
}


/**
 * Update an order status
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$id					Id of order to updatre status
 * @param	int			$status				status:
 * @param	'0' => '',
 * @param   '1' => 'Brouillon',
 * @param   '2' => 'Validée',
 * @param   '3' => 'En cours de traitement',
 * @param   '4' => 'Délivrée',
 * @param   '5' => 'Traitée',
 * @param   '6' => 'Annulée'
 * @return	array							Array result
 */
function updateOrderStatus($authentication,$id='',$status='1')
{
	global $db,$conf,$langs;

	dol_syslog("Function: updateOrderStatus login=".$authentication['login']." id=".$id." status=".$status);

	// Init and check authentication
	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;
	if ($authentication['entity']) $conf->entity=$authentication['entity'];
	$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

	if (! $error)
	{
		$fuser->getrights();

		if ($fuser->rights->commande->lire)
		{
			$order=new Commande($db);
			$result=$order->fetch($id,$ref,$ref_ext);

			$order->fetch_thirdparty();
			$db->begin();
			if ($result > 0)
			{
				if ($status =='1') { // Brouillon
					$result=$order->set_draft($fuser);
					dol_syslog('result draft order : '.print_r($result, true));
				} elseif ($status == '2') { //Validation
					$result=$order->valid($fuser);
					dol_syslog('result valid order : '.print_r($result, true));
					if ($result >= 0)
					{
						// Define output language
						$outputlangs = $langs;
						commande_pdf_create($db, $order, $order->modelpdf, $outputlangs, 0, 0, 0);
						dol_syslog('pdf created');
					}
				} elseif($order['status'] == '3'){ //En cours de traitement
					$result=$order->setStatut(2);
					dol_syslog('result "En cours de traitement" order : '.print_r($result, true));
				} elseif($status == '4'){ //délivrée
					$result=$order->cloture($fuser);
					dol_syslog('result cloture order : '.print_r($result, true));
				} elseif($status == '5'){ //traitée
					$result=$order->classifyBilled();
					dol_syslog('result classifyBilled order : '.print_r($result, true));
				} elseif($status == '6'){ //annulée
					$result=$order->cancel();
					dol_syslog('result cancel order : '.print_r($result, true));
				}
				if ($result < 0) {
					$db->rollback();
					$error++;
					$errorcode='KO';
					$errorlabel=$newobject->error;
				}
			}
			else
			{
				$db->rollback();
				$error++;
				$errorcode='KO';
				$errorlabel=$newobject->error;
			}

		}
		else
		{
			$db->rollback();
			$error++;
			$errorcode='KO';
			$errorlabel=$newobject->error;
		}

	}

	if ($error)
	{
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}
	else
	{
		$db->commit();
		$objectresp= array('result'=>array('result_code'=>'OK', 'result_label'=>''));
	}
	dol_syslog('updateOrderStatus result : '.print_r($objectresp, true));
	return $objectresp;
}

// function getImportedSocpeople  retourne la socpeople importée
function getImportedSocpeople($import_key)
{
	global $db;

	$sql = "SELECT s.rowid";
	$sql.= " FROM ".MAIN_DB_PREFIX."socpeople as s";
	$sql.= " WHERE s.import_key = '".$import_key."'";
	
	$resql = $db->query($sql);

	if ($resql)
	{
		$rec = $db->fetch_array($resql);
		return $rec['rowid'];
	}
	else
	{
		$this->error=$db->error().' sql='.$sql;
		return -1;
	}
}

// Return the results.
$server->service((isset($HTTP_RAW_POST_DATA)?$HTTP_RAW_POST_DATA:''));

