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

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

require_once("../master.inc.php");
require_once(NUSOAP_PATH.'/nusoap.php');		// Include SOAP
require_once DOL_DOCUMENT_ROOT.'/core/lib/ws.lib.php';
require_once(DOL_DOCUMENT_ROOT."/categories/class/categorie.class.php");


dol_syslog("Call Dolibarr webservices interfaces");

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
$server->configureWSDL('WebServicesDolibarrCategorie',$ns);
$server->wsdl->schemaTargetNamespace=$ns;

// Define WSDL content
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
		'entity' => array('name'=>'entity','type'=>'xsd:string'),
	)
);

/*
 * Une catégorie
 */
$server->wsdl->addComplexType(
	'categorie',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'id' => array('name'=>'id','type'=>'xsd:string'),
		'id_mere' => array('name'=>'id_mere','type'=>'xsd:string'),
		'label' => array('name'=>'label','type'=>'xsd:string'),
		'description' => array('name'=>'description','type'=>'xsd:string'),
		'socid' => array('name'=>'socid','type'=>'xsd:string'),
		'type' => array('name'=>'type','type'=>'xsd:string'),
		'visible' => array('name'=>'visible','type'=>'xsd:string'),
		'dir'=> array('name'=>'dir','type'=>'xsd:string'),
		'photos' => array('name'=>'photos','type'=>'tns:PhotosArray'),
		'filles' => array('name'=>'filles','type'=>'tns:FillesArray')
	)
);

/*
 * Une categorie prestashop
 */
$server->wsdl->addComplexType(
	'pcategorie',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'id_category' => array('name'=>'id_category','type'=>'xsd:string'),
		'id_parent' => array('name'=>'id_parent','type'=>'xsd:string'),
		'name' => array('name'=>'name','type'=>'xsd:string'),
		'description' => array('name'=>'description','type'=>'xsd:string')
	)
);

/*
 * liste de categorie
 */
$server->wsdl->addComplexType(
	'CategoriesArray',
	'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:pcategorie[]')
    ),
    'tns:pcategorie'
);


/*
 * Les catégories filles, sous tableau de la catégorie
 */
 $server->wsdl->addComplexType(
    'FillesArray',
    'complexType',
    'array',
    '',
    'SOAP-ENC:Array',
    array(),
    array(
        array('ref'=>'SOAP-ENC:arrayType','wsdl:arrayType'=>'tns:categorie[]')
    ),
    'tns:categorie'
);

 /*
  * Image of product
 */
 $server->wsdl->addComplexType(
 		'PhotosArray',
 		'complexType',
 		'array',
 		'sequence',
 		'',
 		array(
 				'image' => array(
 						'name' => 'image',
 						'type' => 'tns:image',
 						'minOccurs' => '0',
 						'maxOccurs' => 'unbounded'
 				)
 		)
 );
 
 /*
  * An image
 */
 $server->wsdl->addComplexType(
 		'image',
 		'complexType',
 		'struct',
 		'all',
 		'',
 		array(
 				'photo' => array('name'=>'photo','type'=>'xsd:string'),
 				'photo_vignette' => array('name'=>'photo_vignette','type'=>'xsd:string'),
 				'imgWidth' => array('name'=>'imgWidth','type'=>'xsd:string'),
 				'imgHeight' => array('name'=>'imgHeight','type'=>'xsd:string')
 		)
 );

/*
 * Retour
 */
$server->wsdl->addComplexType(
	'result',
	'complexType',
	'struct',
	'all',
	'',
	array(
		'result_code' => array('name'=>'result_code','type'=>'xsd:string'),
		'result_label' => array('name'=>'result_label','type'=>'xsd:string')
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
	'getCategory',
	// Entry values
	array('authentication'=>'tns:authentication','id'=>'xsd:string'),
	// Exit values
	array('result'=>'tns:result','end'=>'xsd:string'),
	$ns,
    $ns.'#getCategory',
    $styledoc,
    $styleuse,
    'WS to get category'
);

// Register WSDL
$server->register(
	'putCategory',
	// Entry values
	array('authentication'=>'tns:authentication','cat_list'=>'tns:CategoriesArray'),
	// Exit values
	array('result'=>'tns:result','pcategorie'=>'tns:pcategorie'),
	$ns,
    $ns.'#putCategory',
    $styledoc,
    $styleuse,
    'WS to put category'
);

/**
 * Get category infos and children
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$id					Id of object
 * @return	mixed
 */
function getCategory($authentication,$id)
{
	global $db,$conf,$langs;

	dol_syslog("Function: getCategory login=".$authentication['login']." id=".$id);

	if ($authentication['entity']) $conf->entity=$authentication['entity'];

	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;
	$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);

	if (! $error && !$id)
	{
		$error++;
		$errorcode='BAD_PARAMETERS'; $errorlabel="Parameter id must be provided.";
	}

	if (! $error)
	{
		$fuser->getrights();

		if ($fuser->rights->categorie->lire)
		{
			$categorie=new Categorie($db);
			$result=$categorie->fetch($id);
			if ($result > 0)
			{
					$dir = (!empty($conf->categorie->dir_output)?$conf->categorie->dir_output:$conf->service->dir_output);
					$pdir = get_exdir($categorie->id,2) . $categorie->id ."/photos/";
					$dir = $dir . '/'. $pdir;

					$cat = array(
						'id' => $categorie->id,
						'id_mere' => $categorie->id_mere,
						'label' => $categorie->label,
						'description' => $categorie->description,
						'socid' => $categorie->socid,
						//'visible'=>$categorie->visible,
						'type' => $categorie->type,
						'dir' => $pdir,
						'photos' => $categorie->liste_photos($dir,$nbmax=10)
			    	);

					$cats = $categorie->get_filles();
					if (count($cats) > 0)
					{
					 	foreach($cats as $fille)
						{
							$dir = (!empty($conf->categorie->dir_output)?$conf->categorie->dir_output:$conf->service->dir_output);
							$pdir = get_exdir($fille->id,2) . $fille->id ."/photos/";
							$dir = $dir . '/'. $pdir;
							$cat['filles'][] = array(
								'id'=>$fille->id,
								'id_mere' => $categorie->id_mere,
								'label'=>$fille->label,
								'description'=>$fille->description,
								'socid'=>$fille->socid,
								//'visible'=>$fille->visible,
								'type'=>$fille->type,
								'dir' => $pdir,
								'photos' => $fille->liste_photos($dir,$nbmax=10)
							);

						}

					}

			    // Create
			    $objectresp = array(
					'result'=>array('result_code'=>'OK', 'result_label'=>''),
					'categorie'=> $cat
			   );
			}
			else
			{
				$error++;
				$errorcode='NOT_FOUND'; $errorlabel='Object not found for id='.$id;
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
 * Put category infos
 *
 * @param	array		$authentication		Array of authentication information
 * @param	int			$cat_list			List of categories with info
 * @return	mixed
 */
function putCategory($authentication, $cat_list)
{
	global $db,$conf,$langs;

	dol_syslog("Function: putCategory login=".$authentication['login']);
	
	if ($authentication['entity']) $conf->entity=$authentication['entity'];

	$objectresp=array();
	$errorcode='';$errorlabel='';
	$error=0;
	
	$fuser=check_authentication($authentication,$error,$errorcode,$errorlabel);
	
	if (! $error)
	{
		$categorie=new Categorie($db);
		$db->begin();
		$tofetch = true;

		foreach ($cat_list AS $id => $cat) {
			dol_syslog("WS_categories gestion de la cat : ".print_r($cat, true));
			if ($tofetch == true){
				$delete_list = fetchCategories(substr($cat['id_category'],0,3));
				$tofetch = false;
			}
			//suppression de la categorie en cours dans la delete list
			unset($delete_list[array_search($cat['id_category'], $delete_list)]);

			$categorie->label = $cat['name'];
			$categorie->description = $cat['description'];
			$fk_parent = getImportedCateg($cat['id_parent']);
			dol_syslog("WS_categories fk_parent : ".print_r($fk_parent, true));
			if ($fk_parent <> -1)
				$categorie->fk_parent = $fk_parent;
			else
				$categorie->fk_parent = '';
			$categorie->type = 0;  //product
			$categorie->import_key = $cat['id_category'];
			
			//fetch sur import_key
			$already_exists = getImportedCateg($cat['id_category']);
			dol_syslog("WS_categories already_exists : ".print_r($already_exists, true));			
			if ($already_exists < 0 || $already_exists == ""){ //create
				dol_syslog("WS_categories create de cat : ".print_r($categorie, true));
				$result = $categorie->create($fuser);
				dol_syslog("WS_categories result create : ".print_r($result, true));
			}
			else { //update
				$categorie->id = $already_exists;
				$result = $categorie->update($fuser);
			}
			if ($result <0){  //error
				$error++;
			}
		}
	}


	//suppression des categories restantes
	if (!$error && $delete_list && $delete_list <> -1){
		foreach ($delete_list AS $id => $cat) {
			$categorie->fetch(getImportedCateg($cat));
			$result = $categorie->delete($fuser);
			if ($result <0){  //error
				$error++;
			}
		}
	}
	
	if (!$error){
		$db->commit();
		$objectresp = array('result'=>array('result_code' => "OK", 'result_label' => ''));
	}
	else{
		$db->rollback();
		$errorcode='KO';
		$errorlabel=$categorie->error;
		$objectresp = array('result'=>array('result_code' => $errorcode, 'result_label' => $errorlabel));
	}
	dol_syslog("return of WS_categories".print_r($objectresp, true));
	return $objectresp;
}


// function getCateg  retourne la categorie importée
function getImportedCateg($import_key)
{
	$objs = array();
	global $db;
	dol_syslog("Call getImportedCateg in WS_categories");

	$sql = "SELECT c.rowid";
	$sql.= " FROM ".MAIN_DB_PREFIX."categorie as c";
	$sql.= " WHERE c.import_key = '".$import_key."'";
	$sql.= " AND c.type = 0";
	
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

// function fetchCategories  retourne toutes les categories importées
function fetchCategories($PS_Trigram)
{
	$objs = array();
	$catArray = array();
	global $db;
	dol_syslog("Call fetchCategories in WS_categories");
	
	if (!$PS_Trigram)
		return -1;

	$sql = "SELECT c.import_key";
	$sql.= " FROM ".MAIN_DB_PREFIX."categorie as c";
	$sql.= " WHERE c.import_key LIKE '".$PS_Trigram."%'";
	$sql.= " AND c.type = 0";
	
	$resultSql = $db->query($sql);

	if ($resultSql)
	{
		$num=$db->num_rows($resultSql);

		$i=0;
		while ($i < $num)
		{
			$obj=$db->fetch_object($resultSql);
			$catArray[]=$obj->import_key;
			$i++;
		}
		return $catArray;
	}
	else
	{
		$this->error=$db->error().' sql='.$sql;
		return -1;
	}
}

// Return the results.
$server->service($HTTP_RAW_POST_DATA);

