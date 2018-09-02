<?php

/**
* 2018 PJ CONSEIL
*
*
*
* @author    PJ CONSEIL
* @copyright 2018 PJ CONSEIL
* @license   NoLicence
* @version   RC3
*/


include_once DOL_DOCUMENT_ROOT .'/core/modules/DolibarrModules.class.php'; //ici

/**
 *  Description and activation class for module dolipresta
 */
class moddolipresta extends DolibarrModules
{
	function __construct($db)
	{
        global $langs,$conf;
        $this->db = $db;
		
		$this->numero = 184251;
		$this->rights_class = 'dolipresta';
		$this->family = "other";
		$this->name = preg_replace('/^mod/i','',get_class($this));
		$this->description = "dolipresta link prestashop with dolibarr in real time";
		$this->version = '7.0.1';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->special = 1;
		$this->picto='generic';

		$this->module_parts = array(
			'triggers' => 1
		);

		// Data directories to create when module is enabled.
		// Example: this->dirs = array("/dolipresta/temp");
		$this->dirs = array();

		// Config pages. Put here list of php page, stored into dolipresta/admin directory, to use to setup module.
		$this->config_page_url = array("admin_dolipresta.php@dolipresta");

		// Dependencies
		$this->hidden = false;			// A condition to hide module
		$this->depends = array("modWebServices", "modBarcode", "modWorkflow", "modCategorie", "modVariants", "modStock", "modFacture", "modFournisseur", "modSociete", "modCommande", "modExpedition", );		// List of modules id that must be enabled if this module is enabled
		$this->requiredby = array();	// List of modules id to disable if this one is disabled
		$this->conflictwith = array("modCyberoffice");	// List of modules id this module is in conflict with
		$this->phpmin = array(5,0);					// Minimum version of PHP required by module
		$this->need_dolibarr_version = array(3,5);	// Minimum version of Dolibarr required by module
		$this->langfiles = array("dolipresta@dolipresta");
		
		// Constants
		$this->const = array();
        $this->tabs = array();

        // Dictionaries
	    if (! isset($conf->dolipresta->enabled))
        {
			$conf->dolipresta=(object) array();
        	$conf->dolipresta->enabled=0;
        }
		$this->dictionaries=array();

		

        // Boxes
		// Add here list of php file(s) stored in core/boxes that contains class to show a box.
        $this->boxes = array();			// List of boxes
	
		// Permissions
		$this->rights = array();		// Permission array used by this module
		$r=0;

		// Main menu entries
		$this->menu = array();			// List of menus to addl
		$r=1;
	}

	/**
	 *		Function called when module is enabled.
	 *		The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *		It also creates data directories
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function init($options='')
	{
		$sql = array();
		$result=$this->_load_tables('/dolipresta/sql/');
		//patch stock hook
		/*if(!file_exists(DOL_DOCUMENT_ROOT .'/product/stock/class/mouvementstock.class.old')) {
			rename (DOL_DOCUMENT_ROOT .'/product/stock/class/mouvementstock.class.php', DOL_DOCUMENT_ROOT .'/product/stock/class/mouvementstock.class.old');
			$version = str_replace(".", "", DOL_VERSION);
			if ($version >= 380) {
				copy(DOL_DOCUMENT_ROOT .'/dolipresta/lib/mouvementstock.class38.php', DOL_DOCUMENT_ROOT .'/product/stock/class/mouvementstock.class.php');
			} else {
				copy(DOL_DOCUMENT_ROOT .'/dolipresta/lib/mouvementstock.class37.php', DOL_DOCUMENT_ROOT .'/product/stock/class/mouvementstock.class.php');
			}
		}*/
		return $this->_init($sql, $options);
	}

	/**
	 *		Function called when module is disabled.
	 *      Remove from database constants, boxes and permissions from Dolibarr database.
	 *		Data directories are not deleted
	 *
     *      @param      string	$options    Options when enabling module ('', 'noboxes')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	function remove($options='')
	{
		$sql = array();
		return $this->_remove($sql, $options);
	}

}

