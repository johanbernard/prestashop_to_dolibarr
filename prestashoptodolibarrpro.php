<?php
/**
* 2019 PJ CONSEIL
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

class PrestashopToDolibarrPro extends Module
{
	/* === set this options as you need === */
    public $debug_mode = true; /* if true then the $this->logInFile("bla bla bla"); really write messages on prestashopdolibarr.log */
    public $debug_file = 'prestashopdolibarr.log'; /* on the current firectory of this module */
    public $nbr_max_sec_export = 10; //24; /** maximum time in seconds to export */
    public $preferred_address = 'invoice'; // 'invoice' vs. 'delivery', what to choose to take as address of the customer in Dolibarr?

    /* === don't touch this === */
    private $id_customer = 0;
    private $firstname_customer = '';
    private $lastname_customer = '';


    const INSTALL_SQL_FILE = 'install.sql';
    const UNINSTALL_SQL_FILE = 'uninstall.sql';

    private $html = '';
    private $post_errors = array();

    private $ws_adress_value = '';
    private $ws_adress_dolibarr = 'dolipresta';
    private $ws_key_value = '';
    private $ws_login_value = '';
    private $ws_passwd_value = '';
    private $ws_trigram_value = '';
    private $ws_accesss_ok = false;

    public $is_checked_synch_customer = '';
    public $is_checked_synch_product = '';
    public $is_checked_synch_invoice = '';
    public $is_checked_synch_stock = '';
    public $is_checked_synch_order = '';
    public $is_checked_synch_category = '';
    public $is_checked_synch_status = '';
    private $ws_warehouse_value = '';
    private $dolibarr_ref_ind = 0;
    private $dolibarr_version = 0;

    // cache for make faster the loop processes
	private $countries = array();
    private $order_states = array();
	private $default_lang = ''; // default language
	private $default_curr = ''; // default currency
	private $already_synced_customers = array();

    public function __construct()
    {
        $this->name = 'prestashoptodolibarrpro';
        $this->tab = 'migration_tools';
        $this->version = '2.0';
        $this->author = 'PJ Conseil';
        $this->module_key = 'a9616fc7465750635d2cc4293269cb83';
        $this->need_instance = 0;
        //$this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Prestashop to Dolibarr PRO');
        $this->description = $this->l('Import and link in real time Prestashop to Dolibarr');
        $this->initConfig();
    }

    public function initConfig()
    {
        require_once(_PS_MODULE_DIR_.'prestashoptodolibarrpro/nusoap/lib/nusoap.php');
        $config = Configuration::getMultiple(
            array(
                'DOLIBARR_WS_ADRESS',
                'DOLIBARR_WS_KEY',
                'DOLIBARR_WS_LOGIN',
                'DOLIBARR_WS_PASSWD',
                'DOLIBARR_WS_WAREHOUSE',
                'DOLIBARR_WS_TRIGRAM',
                'DOLIBARR_WS_ACCESS_OK',
                'DOLIBARR_IS_SYNCH_CUSTOMER',
                'DOLIBARR_IS_SYNCH_PRODUCT',
                'DOLIBARR_IS_SYNCH_INVOICE',
                'DOLIBARR_IS_SYNCH_STOCK',
                'DOLIBARR_IS_SYNCH_ORDER',
                'DOLIBARR_IS_SYNCH_CATEGORY',
                'DOLIBARR_IS_SYNCH_STATUS',
                'DOLIBARR_REF_IND',
                'DOLIBARR_VERSION',
                'PS_LANG_DEFAULT',
                'PS_CURRENCY_DEFAULT'
            )
        );
        if ($config['DOLIBARR_WS_ADRESS']) {
            $this->ws_adress_value = $config['DOLIBARR_WS_ADRESS'];
        }
        if ($config['DOLIBARR_WS_KEY']) {
            $this->ws_key_value = $config['DOLIBARR_WS_KEY'];
        }
        if ($config['DOLIBARR_WS_LOGIN']) {
            $this->ws_login_value = $config['DOLIBARR_WS_LOGIN'];
        }
        if ($config['DOLIBARR_WS_PASSWD']) {
            $this->ws_passwd_value = $config['DOLIBARR_WS_PASSWD'];
        }
        if ($config['DOLIBARR_WS_WAREHOUSE']) {
            $this->ws_warehouse_value = $config['DOLIBARR_WS_WAREHOUSE'];
        }
        if ($config['DOLIBARR_WS_TRIGRAM']) {
            $this->ws_trigram_value = $config['DOLIBARR_WS_TRIGRAM'];
        }
        if ($config['DOLIBARR_WS_ACCESS_OK']) {
            $this->ws_accesss_ok = $config['DOLIBARR_WS_ACCESS_OK'];
        }
        if ($config['DOLIBARR_IS_SYNCH_CUSTOMER']) {
            $this->is_checked_synch_customer = $config['DOLIBARR_IS_SYNCH_CUSTOMER'];
        }
        if ($config['DOLIBARR_IS_SYNCH_PRODUCT']) {
            $this->is_checked_synch_product = $config['DOLIBARR_IS_SYNCH_PRODUCT'];
        }
        if ($config['DOLIBARR_IS_SYNCH_INVOICE']) {
            $this->is_checked_synch_invoice = $config['DOLIBARR_IS_SYNCH_INVOICE'];
        }
        if ($config['DOLIBARR_IS_SYNCH_STOCK']) {
            $this->is_checked_synch_stock = $config['DOLIBARR_IS_SYNCH_STOCK'];
        }
        if ($config['DOLIBARR_IS_SYNCH_ORDER']) {
            $this->is_checked_synch_order = $config['DOLIBARR_IS_SYNCH_ORDER'];
        }
        if ($config['DOLIBARR_IS_SYNCH_CATEGORY']) {
            $this->is_checked_synch_category = $config['DOLIBARR_IS_SYNCH_CATEGORY'];
        }
        if ($config['DOLIBARR_IS_SYNCH_STATUS']) {
            $this->is_checked_synch_status = $config['DOLIBARR_IS_SYNCH_STATUS'];
        }
        if ($config['DOLIBARR_REF_IND']) {
            $this->dolibarr_ref_ind = $config['DOLIBARR_REF_IND'];
        }
        if ($config['DOLIBARR_VERSION']) {
            $this->dolibarr_version = $config['DOLIBARR_VERSION'];
        }

        $this->default_lang = (int)$config['PS_LANG_DEFAULT'];
        $this->default_curr = (int)$config['PS_CURRENCY_DEFAULT'];

        // prestashop order states
        $this->loadOrderStates();

    }

    public function install()
    {
        if (!file_exists(dirname(__FILE__).'/'.self::INSTALL_SQL_FILE)) {
            return (false);
        } elseif (!$sql = Tools::file_get_contents(dirname(__FILE__).'/'.self::INSTALL_SQL_FILE)) {
            return (false);
        }
        $sql = str_replace(array('PREFIX_', 'ENGINE_TYPE'), array(_DB_PREFIX_, _MYSQL_ENGINE_), $sql);
        $sql = preg_split("/;\s*[\r\n]+/", $sql);
        foreach ($sql as $query) {
            $this->logInFile('- installation query : '.$query);
            if ($query) {
                if (!Db::getInstance()->execute(trim($query))) {
                    return false;
                }
            }
        }

        if (!parent::install()) {
				$this->logInFile('Install failed on: parent::install().');
				return false;
        }
        if (!$this->registerHook('createAccount') || !$this->registerHook('actionValidateOrder')) {
				$this->logInFile('Install failed on: detecting hooks createAccount or actionValidateOrder.');
				return false;
        }
        if (!$this->registerHook('updateproduct') || !$this->registerHook('addproduct') ||
            !$this->registerHook('updateOrderStatus')) {
				$this->logInFile('Install failed on: detecting hooks updateproduct, addproduct or updateOrderStatus.');
				return false;
        }
        if (!$this->registerHook('categoryAddition') || !$this->registerHook('categoryUpdate') ||
            !$this->registerHook('categoryDeletion')) {
				$this->logInFile('Install failed on: detecting hooks categoryAddition, categoryUpdate or categoryDeletion.');
				return false;
        }
        $this->logInFile('Install OK');
        return true;
    }

    public function uninstall()
    {
        Configuration::deleteByName('DOLIBARR_WS_ADRESS');
        Configuration::deleteByName('DOLIBARR_WS_KEY');
        Configuration::deleteByName('DOLIBARR_WS_LOGIN');
        Configuration::deleteByName('DOLIBARR_WS_PASSWD');
        Configuration::deleteByName('DOLIBARR_WS_WAREHOUSE');
        Configuration::deleteByName('DOLIBARR_WS_TRIGRAM');
        Configuration::deleteByName('DOLIBARR_WS_ACCESS_OK');
        Configuration::deleteByName('DOLIBARR_IS_SYNCH_CUSTOMER');
        Configuration::deleteByName('DOLIBARR_IS_SYNCH_PRODUCT');
        Configuration::deleteByName('DOLIBARR_IS_SYNCH_INVOICE');
        Configuration::deleteByName('DOLIBARR_IS_SYNCH_STOCK');
        Configuration::deleteByName('DOLIBARR_IS_SYNCH_ORDER');
        Configuration::deleteByName('DOLIBARR_IS_SYNCH_CATEGORY');
        Configuration::deleteByName('DOLIBARR_IS_SYNCH_STATUS');
        Configuration::deleteByName('DOLIBARR_REF_IND');
        Configuration::deleteByName('DOLIBARR_VERSION');

        if (!$this->deleteTables()) {
			$this->logInFile('Uninstall failed on: deleting tables.');
            return false;
        }
        if (!parent::uninstall()) {
			$this->logInFile('Uninstall failed on: parent::uninstall()');
            return false;
        }

		$this->logInFile('Uninstall OK');

        return true;
    }

    private function deleteTables()
    {
        if (!file_exists(dirname(__FILE__).'/'.self::UNINSTALL_SQL_FILE)) {
			$this->logInFile('Error: missing uninstall file '.dirname(__FILE__).'/'.self::UNINSTALL_SQL_FILE);
            return (false);
        } elseif (!$sql = Tools::file_get_contents(dirname(__FILE__).'/'.self::UNINSTALL_SQL_FILE)) {
			$this->logInFile('Error: retrieving content of file '.dirname(__FILE__).'/'.self::UNINSTALL_SQL_FILE);
            return (false);
        }
        $sql = str_replace(array('PREFIX_', 'ENGINE_TYPE'), array(_DB_PREFIX_, _MYSQL_ENGINE_), $sql);
        $sql = preg_split("/;\s*[\r\n]+/", $sql);
        foreach ($sql as $query) {
            $this->logInFile('- uninstall query : '.$query);
            if ($query) {
                if (!Db::getInstance()->execute(trim($query))) {
                    return false;
                }
            }
        }

        return true;
    }


    public function logInFile($text, $type = 'DEBUG')
    {
        if ($this->debug_mode != true) return;

		$file_path = dirname(__FILE__).'/'.$this->debug_file;
		$file_handle = fopen($file_path, 'a+');
		fputs($file_handle, "LOG[$type][".date('Ymd.H:i:s')."]: $text \n");
		fclose($file_handle);
    }

    public function getContent()
    {

		// check if it's an AJAX call
		if (!empty($_GET['ajax'])){
			switch ($_GET['ajax']){
				case 'getHowMany': die( json_encode($this->ajax_getHowMany()) );
			}
		}

        $this->_html = '<h2>'.$this->displayName.'</h2>';

		// check if has been posted new WS settings
		// if not or yes but without errors then we process the submitted request, if any
        $this->postValidation();
        if (!count($this->post_errors)) {
            $this->postProcess();
        } else {
            foreach ($this->post_errors as $err) {
                $this->_html .= '<div class = "alert error">'.$err.'</div>';
            }
        }
        $this->initConfig();

        // typical dolibarr states
        $order_states_options = array(
									'0' => '',
									'1' => $this->l('Draft'),
									'2' => $this->l('Validated'),
									'3' => $this->l('Shipment in process'),
									'4' => $this->l('Delivered'),
									'5' => $this->l('Processed'),
									'6' => $this->l('Canceled')
								);

        // prepare array of settings to be rendered on the forms at main.tpl
        $var = array(
					'ws_adress_value' => htmlentities(Tools::getValue('adress', $this->ws_adress_value), ENT_COMPAT, 'UTF-8'),
					'ws_key_value' => htmlentities(Tools::getValue('WSkey', $this->ws_key_value), ENT_COMPAT, 'UTF-8'),
					'ws_login_value' => htmlentities(Tools::getValue('login', $this->ws_login_value), ENT_COMPAT, 'UTF-8'),
					'ws_passwd_value' => htmlentities(Tools::getValue('password', $this->ws_passwd_value), ENT_COMPAT, 'UTF-8'),
					'ws_trigram_value' => htmlentities($this->ws_trigram_value, ENT_COMPAT, 'UTF-8'),
					'is_checked_synch_customer' => $this->is_checked_synch_customer,
					'is_checked_synch_product' => $this->is_checked_synch_product,
					'is_checked_synch_stock' => $this->is_checked_synch_stock,
					'ws_warehouse_value' => htmlentities(Tools::getValue('warehouse', $this->ws_warehouse_value),ENT_COMPAT,'UTF-8'),
					'is_checked_synch_invoice' => $this->is_checked_synch_invoice,
					'is_checked_synch_order' => $this->is_checked_synch_order,
					'is_checked_synch_category' => $this->is_checked_synch_category,
					'is_checked_synch_status' => $this->is_checked_synch_status,
					'ws_accesss_ok' => $this->ws_accesss_ok,
					'order_states' => $this->order_states,
					'order_states_options' => $order_states_options
				);
        $this->context->smarty->assign('varMain', $var);

        // render view at main.tpl
        $this->_html .= $this->display(__FILE__, 'views/templates/admin/main.tpl');

        // use javascript to show only the requested tab, if none then the first one is showed
        $id_tab_action = Tools::getValue('id_tab');
        if ($id_tab_action) {
            $this->_html .= '<script type = "text/javascript">
								  $(".menuTabButton.selected").removeClass("selected");
								  $("#menuTab'.Tools::safeOutput($id_tab_action).'").addClass("selected");
								  $(".tabItem.selected").removeClass("selected");
								  $("#menuTab'.Tools::safeOutput($id_tab_action).'Sheet").addClass("selected");
							</script>';
        }
        return $this->_html;

    }

    public function postValidation()
    {
        // check for configuration button
        $btn_submit_acces_ws = Tools::getValue('btnSubmitAccesWS');
        if ($btn_submit_acces_ws) {
            $adress_action = Tools::getValue('adress');
            $wskey_action = Tools::getValue('WSkey');
            $login_action = Tools::getValue('login');
            $password_action = Tools::getValue('password');
            $trigram_action = Tools::getValue('trigram');

            if (!$adress_action) {
                $this->post_errors[] = $this->l('"Dolibarr url" is required.');
            } elseif (!($wskey_action)) {
                $this->post_errors[] = $this->l('"Webservice key" is required.');
            } elseif (!($login_action)) {
                $this->post_errors[] = $this->l('"Dolibarr login" is required.');
            } elseif (!($password_action)) {
                $this->post_errors[] = $this->l('"Dolibarr password" is required.');
            } elseif (!($trigram_action)) {
                $this->_wstrigram_value = 'PTS'; // default trigram if empty
            } elseif (Tools::strlen($trigram_action) <> 3) {
                $this->post_errors[] = $this->l('"Trigram" must have 3 characters');
            }
        }
    }

	/*
	 * we check if it has been submited (POST) any of the forms on main.tpl
	 */
    public function postProcess()
    {

        // == update WS settings

        $btn_submit_acces_ws = Tools::getValue('btnSubmitAccesWS');
        if ($btn_submit_acces_ws) {
			$adress_action = Tools::getValue('adress');
			$wskey_action = Tools::getValue('WSkey');
			$login_action = Tools::getValue('login');
			$password_action = Tools::getValue('password');
			$trigram_action = Tools::getValue('trigram');
            if (!($trigram_action)) {
                $trigram_action = 'PTS'; // default trigram if empty
            }
            Configuration::updateValue('DOLIBARR_WS_ADRESS', $adress_action);
            Configuration::updateValue('DOLIBARR_WS_KEY', $wskey_action);
            Configuration::updateValue('DOLIBARR_WS_LOGIN', $login_action);
            Configuration::updateValue('DOLIBARR_WS_PASSWD', $password_action);
            Configuration::updateValue('DOLIBARR_WS_TRIGRAM', $trigram_action);
            $this->ws_trigram_value = $trigram_action;
            $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "'
							.$this->l('OK').'" /> '
							.$this->l('Settings updated, you can test the connection by clicking on the "Test Webservice Acess" button')
							.'</div>';
			}

        // == Webservice access test

        $btn_test_acces_ws = Tools::getValue('btnTestAccesWS');
        if ($btn_test_acces_ws) {
			$adress_action = Tools::getValue('adress');
			$wskey_action = Tools::getValue('WSkey');
			$login_action = Tools::getValue('login');
			$password_action = Tools::getValue('password');
			$trigram_action = Tools::getValue('trigram');
            if ($adress_action && $wskey_action && $login_action && $password_action) {
                Configuration::updateValue('DOLIBARR_WS_ADRESS', $adress_action);
                Configuration::updateValue('DOLIBARR_WS_KEY', $wskey_action);
                Configuration::updateValue('DOLIBARR_WS_LOGIN', $login_action);
                Configuration::updateValue('DOLIBARR_WS_PASSWD', $password_action);
                Configuration::updateValue('DOLIBARR_WS_TRIGRAM', $trigram_action);
                $this->ws_adress_value = $adress_action;
                $this->ws_key_value = $wskey_action;
                $this->ws_login_value = $login_action;
                $this->ws_passwd_value = $password_action;
                $this->ws_trigram_value = $trigram_action;
            }
            if ($this->endsWith($this->ws_adress_value, '/') == false) {
                $this->ws_adress_value = $this->ws_adress_value.'/';
            }
            $version_n = $this->WSVersion($this->ws_adress_value);

            $versionDoli = str_replace(".", "", $version_n['dolibarr']);
            if ($versionDoli >= 360) {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "'.$this->l('OK').'" /> '
								.$this->l('Prestashop is linked with Dolibarr! ').'</div>';
                Configuration::updateValue('DOLIBARR_WS_ADRESS', $this->ws_adress_value);
                Configuration::updateValue('DOLIBARR_WS_ACCESS_OK', 'OK');
                $this->dolibarr_version = $versionDoli;
                Configuration::updateValue('DOLIBARR_VERSION', $versionDoli);
                $this->ws_accesss_ok = 'OK';
            } elseif ($version_n['result']['result_code'] == 'BAD_CREDENTIALS') {
                $this->_html .= '<div class = "alert error">'.$this->l('Bad credantials').'</div>';
            } elseif ($version_n['result']['result_code'] == 'BAD_VALUE_FOR_SECURITY_KEY') {
                $this->_html .= '<div class = "alert error">'.$this->l('Bad value for security key').'</div>';
            } else {
                $m = 'Dolibarr doesn\'t respond, please check your Dolibarr\'s URL and the comunication keys.'
					.'Then check if Soap is enable in the PHP configuration of your dolibarr\'s server';
                $this->_html .= '<div class = "alert error">'.$this->l($m).'</div>';
            }
        }

        // == Export customers

        $btn_submit_export_client = Tools::getValue('btnSubmitExportClient');
        if ($btn_submit_export_client) {
            $tmsp_start = time();
            $import_client = $this->importClients(0, $tmsp_start);

            if ($import_client['result'] == 'OK') {
                if ($import_client['nbrMaxClient']) { //pas totalement importé
                    $this->_html .= '<div class = "conf warn"><img src= "../modules/prestashoptodolibarrpro/warning.png" alt = "OK" /> '
									.$this->l('You have successfully exported').' '.$import_client['nbClientImported'].' '
									.$this->l('customer(s) on').' '.$import_client['nbClientTotal']
									.$this->l(', press Start again for exporting next customers').'</div>';
                } else {
                    $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "OK" /> '
									.$import_client['nbClientImported'].' '.$this->l('customer(s) on').' '
									.$import_client['nbClientTotal'].' '.$this->l('exported').'</div>';
                }
            } else {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ko.gif" alt = "KO" /> '
								.$this->l('something went wrong').' : '.$import_client['reason'].'</div>';
            }
        }

        // == Reset export customer

        $btn_reset_export_client = Tools::getValue('btnResetExportClient');
        if ($btn_reset_export_client) {
            $reset_customers = $this->resetCustomers();

            if ($reset_customers) {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "OK" /> '
								.$this->l('Reset on customers done').'</div>';
            } else {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ko.gif" alt = "KO" /> '
								.$this->l('something went wrong').'</div>';
            }
        }

        // == Export products

        $btn_submit_export_product = Tools::getValue('btnSubmitExportProduct');
        if ($btn_submit_export_product) {
            $tmsp_start = time();
            $result_product = $this->importProduits(0, $tmsp_start);

            if ($result_product['result'] == 'OK') {
                if ($result_product['nbrMaxProduct']) { // not fully imported
                    $this->_html .= '<div class = "conf warn"><img src= "../modules/prestashoptodolibarrpro/warning.png" alt = "OK" /> '
									.$this->l('You have successfully exported').' '.$result_product['nbProductImported'].' '
									.$this->l('product(s) on').' '.$result_product['nbProductTotal']
									.$this->l(', press Start again for exporting next products').'</div>';
                } else {
                    $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "OK" /> '
									.$result_product['nbProductImported'].' '.$this->l('product(s) on').' '
									.$result_product['nbProductTotal'].' '.$this->l('exported').'</div>';
                }
            } else {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ko.gif" alt = "KO" /> '
								.$this->l('something went wrong').' : '.$result_product['reason'].'</div>';
            }
        }

        // == Reset export products

        $btn_reset_export_product = Tools::getValue('btnResetExportProduct');
        if ($btn_reset_export_product) {
            $reset_products = $this->resetProducts();

            if ($reset_products) {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "OK" /> '
								.$this->l('Reset on products done').'</div>';
            } else {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ko.gif" alt = "KO" /> '
								.$this->l('something went wrong').'</div>';
            }
        }

        // == Export invoices

        $btn_submit_import_invoice = Tools::getValue('btnSubmitImportInvoice');
        if ($btn_submit_import_invoice) {
            $this->logInFile('--Export invoices--');
            $tmsp_start = time();

            $result_invoice = $this->importFacturesOrCommandes(0, $tmsp_start, false, true);
            $this->logInFile('FIN export factures : '.print_r($result_invoice, true));
            if ($result_invoice['result'] == 'OK') {
                if ($result_invoice['nbrMaxOrder']) { // not fully imported
                    $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "'.$this->l('Ok').'" /> '
									.$this->l('You have successfully exported').' '.$result_invoice['nbOrderOk'].' '
									.$this->l('invoice(s) on').' '.$result_invoice['nbOrderTotal']
									.$this->l(', press Start again for exporting next invoices').'</div>';
                } else {
                    $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "'
									.$this->l('Ok').'" /> '.$result_invoice['nbOrderOk'].' '.$this->l('invoice(s) on').' '
									.$result_invoice['nbOrderTotal'].' '.$this->l('exported').'</div>';
                }
            } else {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ko.gif" alt = "KO" /> '
								.$this->l('something went wrong').' : '.(is_array($result_invoice['reason']) ? print_r($result_invoice['reason'],true) : $result_invoice['reason']).'</div>';
            }
        }

        // == Reset export invoices

        $btnreset_export_invoice = Tools::getValue('btnResetExportInvoice');
        if ($btnreset_export_invoice) {
            $resetinvoices = $this->resetInvoices();

            if ($resetinvoices) {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "OK" /> '
								.$this->l('Reset on invoices done').'</div>';
            } else {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ko.gif" alt = "KO" /> '
								.$this->l('something went wrong').'</div>';
            }
        }

        // == Export orders

        $btn_submit_import_order = Tools::getValue('btnSubmitImportOrder');
        if ($btn_submit_import_order) {
            $this->logInFile('--Export orders--');
            $tmsp_start = time();

            $result_order = $this->importFacturesOrCommandes(0, $tmsp_start, true, false);
            $this->logInFile('export orders/invoices result : '.print_r($result_order, true));
            if ($result_order['result'] == 'OK') {
                if ($result_order['nbrMaxOrder']) { // not fully imported
                    $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "'.$this->l('Ok').'" /> '
									.$this->l('You have successfully exported').' '.$result_order['nbOrderOk'].' '.$this->l('order(s) on').' '
									.$result_order['nbOrderTotal'].$this->l(', press Start again for exporting next orders').'</div>';
                } else {
                    $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "'
									.$this->l('Ok').'" /> '.$result_order['nbOrderOk'].' '
									.$this->l('order(s) on').' '.$result_order['nbOrderTotal'].' '
									.$this->l('exported').'</div>';
                }
            } else {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ko.gif" alt = "KO" /> '
								.$this->l('something went wrong').': '.$result_order['reason'].'</div>';
            }
        }

        // == Reset export orders

        $btn_reset_export_order = Tools::getValue('btnResetExportOrder');
        if ($btn_reset_export_order) {
            $resetorders = $this->resetOrders();
            if ($resetorders) {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "OK" /> '
								.$this->l('Reset on orders done').'</div>';
            } else {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ko.gif" alt = "KO" /> '
								.$this->l('something went wrong').'</div>';
            }
        }

        // == Export categories

        $btn_submit_import_category = Tools::getValue('btnSubmitImportCategory');
        if ($btn_submit_import_category) {
            $this->logInFile('--Export categories--');
            $result_category = $this->importCategories();
            if ($result_category['result']['result_code'] == 'OK') {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "'.$this->l('Ok').'" /> '
								.$this->l('You have successfully exported your categories').'</div>';
            } else {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ko.gif" alt = "KO" /> '
								.$this->l('something went wrong').' : '.$result_category['result']['result_label'].'</div>';
            }
        }

        // == checking synchronization

        $btn_submit_synchro = Tools::getValue('btnSubmitSynchro');
        if ($btn_submit_synchro) {
            $warehouse = Tools::getValue('warehouse');
            if ($warehouse) {
                Configuration::updateValue('DOLIBARR_WS_WAREHOUSE', $warehouse);
                $this->ws_warehouse_value = $warehouse;
            }
            $check_synch_customer = Tools::getValue('checkSynchCustomer');
            $check_synch_products = Tools::getValue('checkSynchProducts');
            $check_synch_invoice = Tools::getValue('checkSynchInvoice');
            $check_synch_stock = Tools::getValue('checkSynchStock');
            $check_synch_order = Tools::getValue('checkSynchOrder');
            $check_synch_category = Tools::getValue('checkSynchCategory');
            $check_synch_status = Tools::getValue('checkSynchStatus');

            $this->loadOrderStates();

            $this->logInFile('check synchro, prestashop order states: '.print_r($this->order_states, true));

            foreach ($this->order_states as $x => $order_state) {
                $get_id = Tools::getValue('select_'.$order_state['id_order_state']);
                //update orderstate
                $query = 'UPDATE '._DB_PREFIX_.'order_state
                        SET id_order_state_doli = '.(int)$get_id.'
                        WHERE id_order_state = '.(int)$order_state['id_order_state'];
                if (!Db::getInstance()->execute($query)) {
                    $this->logInFile('db error updating id_order_state_doli (='.$get_id.') in the order_state table for id_order_state='.$order_state['id_order_state']);
                }
                $this->order_states[$x]['id_order_state_doli'] = $get_id;
            }

            Configuration::updateValue('DOLIBARR_IS_SYNCH_CUSTOMER', $check_synch_customer);
            Configuration::updateValue('DOLIBARR_IS_SYNCH_PRODUCT', $check_synch_products);
            Configuration::updateValue('DOLIBARR_IS_SYNCH_INVOICE', $check_synch_invoice);
            Configuration::updateValue('DOLIBARR_IS_SYNCH_STOCK', $check_synch_stock);
            Configuration::updateValue('DOLIBARR_IS_SYNCH_ORDER', $check_synch_order);
            Configuration::updateValue('DOLIBARR_IS_SYNCH_CATEGORY', $check_synch_category);
            Configuration::updateValue('DOLIBARR_IS_SYNCH_STATUS', $check_synch_status);

            $this->is_checked_synch_customer = $check_synch_customer;
            $this->is_checked_synch_product = $check_synch_products;
            $this->is_checked_synch_invoice = $check_synch_invoice;
            $this->is_checked_synch_stock = $check_synch_stock;
            $this->is_checked_synch_order = $check_synch_order;
            $this->is_checked_synch_category = $check_synch_category;
            $this->is_checked_synch_status = $check_synch_status;

            if ($this->is_checked_synch_customer || $this->is_checked_synch_product || $this->is_checked_synch_invoice
				|| $this->is_checked_synch_order || $this->is_checked_synch_category || $this->is_checked_synch_status) {

                    $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "" /> ';

                    if ($this->is_checked_synch_customer) $this->_html .= '<br>'.$this->l('Customers are synchronised with Dolibarr');
                    if ($this->is_checked_synch_product)  $this->_html .= '<br>'.$this->l('Products are synchronised with Dolibarr');
                    if ($this->is_checked_synch_stock)    $this->_html .= '<br>'.$this->l('Products stocks are synchronised with Dolibarr');

                    if ($this->is_checked_synch_invoice)  $this->_html .= '<br>'.$this->l('Invoices are synchronised with Dolibarr');
                    if ($this->is_checked_synch_order)    $this->_html .= '<br>'.$this->l('Orders are synchronised with Dolibarr');
                    if ($this->is_checked_synch_category) $this->_html .= '<br>'.$this->l('Categories are synchronised with Dolibarr');
                    if ($this->is_checked_synch_status)   $this->_html .= '<br>'.$this->l('Status are synchronised with Dolibarr');

                    $this->_html .= '</div>';

            } else {
                $this->_html .= '<div class = "alert warning">'.$this->l('You have nothing checked').'</div>';
            }
        }
    }

    /**
    *
    * methods to export categories
    *
    **/
    public function importCategories()
    {
        $this->logInFile('--importCategories--');
        $this->logInFile('cat language : '.$this->default_lang);
        $result_get_order_tab = array('result'=>array('result_code' => '', 'result_label' => ''));
        $category_tab = Category::getCategories($this->default_lang, false);
        $this->logInFile('->getCategoriesTab : '.print_r($category_tab, true));
        $clean_cat_tab = $this->cleanCategory($category_tab);
        $this->logInFile('->CLEAN getCategoriesTab : '.print_r($clean_cat_tab, true));
        $wsurl = $this->ws_adress_value;
        $ws_dol_url_category = $wsurl.$this->ws_adress_dolibarr.'/pj_ws_categories.php';
        $ws_method_put_category  = 'putCategory';
        $ns = 'http://www.Dolibarr.org/ns/';
        $authentication = array(
            'dolibarrkey'=>$this->ws_key_value,
            'sourceapplication'=>'PRESTASHOP',
            'login'=>$this->ws_login_value,
            'password'=>$this->ws_passwd_value,
            'entity'=>''
        );

        $soapclient1 = new nusoap_client($ws_dol_url_category);
        if ($soapclient1) {
            $soapclient1->soap_defencoding = 'UTF-8';
            $soapclient1->decodeUTF8(false);
        }
        $parameters1 = array('authentication'=>$authentication, 'cat_list'=>$clean_cat_tab);
        $this->logInFile('->parameters1 : '.print_r($parameters1, true));
        $result_get_order_tab = $soapclient1->call($ws_method_put_category, $parameters1, $ns, '');
        $this->logInFile('->resultGetOrderTab : '.print_r($result_get_order_tab, true));
        //$result_get_order = $result_get_order_tab['result']['result_code'];

        $this->logInFile('echange avec le '.$ws_method_put_category.' ('.$ws_dol_url_category.') ');
        $this->logInFile('requete : '.$soapclient1->request);
        $this->logInFile('WS response : '.print_r($result_get_order_tab, true));
        return $result_get_order_tab;
    }

    /**
    *
    * customers export
    *
    **/
    public function importClients($customer_ec = 0, $tmsp_start = 0)
    {
        $this->logInFile('--importClients--');
        $this->logInFile(' passed customers : '.print_r($customer_ec, true));

        $result = 'OK';
        $nb_imported_customers    = 0;
        $nb_max_customer        = false;

        // Catching up: all the customers at db
        if ($customer_ec == 0) {

            $customers = $this->getCustomers();

        // the customers passed
        } else {
            $customers = $customer_ec;
        }

        $nb_customers = count($customers);
        $wsretourbis = array('result'=>array('result_label'=>''));

        foreach ($customers as $customer) {

            $c_id          = $customer['id_customer'];
            $this->logInFile('\customer id='.(int)$c_id.' : '.print_r($customer, true));
            $customer_ref = $this->getCustomerRef($c_id); // return array with: date_add, date_upd ,date_export_doli, id_ext_doli
            $id_ext_doli = $customer_ref['id_ext_doli']; // dolibarr_id of the customer

            // addresses
            $customer_obj = new Customer($c_id);
            $addresses_temp    = $customer_obj->getAddresses($this->default_lang);
            $nb_addresses = is_array($addresses_temp) ? count($addresses_temp) : 0;
            $addresses = array();
            $last_addr_update = '';
            if ($nb_addresses>0) {
				foreach ($addresses_temp as $addr){
					if (!empty($addr['city'])) $addresses[$addr['id_address']] = $addr;
					$last_addr_update = max($last_addr_update,$addr['date_upd']);
				}
			}
            $this->logInFile('\addresses ('.$nb_addresses.') (last update '.$last_addr_update.'): '.print_r($addresses, true));

            // check if the customer was updated since the last export
            if (!empty($id_ext_doli) && !empty($customer_ref['date_export_doli'])){
				if ($customer_ref['date_export_doli'] > max($last_addr_update,$customer_ref['date_upd'])){
					continue; // we don't need to export nothing :-) so we skip to the next customer on the list
				}
			}

            // we must choose only one address
            $pref_address = array();
            $last_order = $this->getCustomerLastOrder($c_id);
            $this->logInFile('\last order (of customer id='.$c_id.'): '.print_r($last_order,true));

            if ($nb_addresses==1){ // we take the unique address available

				$pref_address = reset($addresses);

			}else{ // we take the preferred (invoice/delivery) of the last order, if any

				$last_addr = array('invoice'=> '', 'delivery'=>'');
				if ($last_order && empty($last_addr['invoice'])  && !empty($last_order['id_address_invoice']))
						$last_addr['invoice']  = $last_order['id_address_invoice'];
				if ($last_order && empty($last_addr['delivery']) && !empty($last_order['id_address_delivery']))
						$last_addr['delivery'] = $last_order['id_address_delivery'];

				// preferred prestashop customer address (invoice / delivery)
				$pref = $this->preferred_address; // invoice/delivery
				if (!empty($last_addr[$pref]) && isset($addresses[$last_addr[$pref]])){
					$pref_address = $addresses[$last_addr[$pref]];
				}else{
					$other_pref = $pref=='invoice' ? 'delivery' : 'invoice';
					if (!empty($last_addr[$other_pref]) && isset($addresses[$last_addr[$other_pref]])){
						$pref_address = $addresses[$last_addr[$other_pref]];
					}
				}
			}
            $this->logInFile('\preferred addr: '.print_r($pref_address, true));


			// other customer data
            $private_note = $customer_obj->note;
            $url = $customer_obj->website;

			if (!is_array($customer) || !isset($customer['firstname']) || !isset($customer['lastname']) || !isset($customer['email'])){
				$customer_info = $customer_obj->getFields();
				$c_firstname   = $customer_info['firstname'];
				$c_name        = $customer_info['lastname'];
				$email         = $customer_info['email'];
			}else{
				$c_firstname   = $customer['firstname'];
				$c_name        = $customer['lastname'];
				$email         = $customer['email'];
			}

			//$is_client = $nb_orders > 0 || $is_clientForcer == 1 ? 1 : 2;
			$is_client = $last_order ? 1 : 2;

			// try to get the customer by ref
			$ref = $this->ws_trigram_value.$this->format($c_id, 10);
            if (empty($id_ext_doli)){
				$wsretour = $this->WSGetCustomer($ref);
				if (!empty($wsretour['thirdparty']['id'])){
					$id_ext_doli = $wsretour['thirdparty']['id'];
				}
            }

            // if we have the id_ext_doli then we update the customer on dolibarr
            if (!empty($id_ext_doli)){

                $enrich_customer = $this->enrichCustomer(
											$id_ext_doli,
											$c_name,
											$c_firstname,
											$ref,
											$is_client,
											$private_note,
											$email,
											$url,
											$pref_address
										);
                $wsretourbis = $this->WSModCustomer($enrich_customer);
                $code_retourbis = $wsretourbis['result']['result_code'];


            // we add a new customer on dolibarr
            }else {

				$enrich_customer = $this->enrichCustomer(
											'',
											$c_name,
											$c_firstname,
											$ref,
											$is_client,
											$private_note,
											$email,
											$url,
											$pref_address
										);
				$wsretourbis = $this->WSAddCustomer($enrich_customer);
				$code_retourbis = $wsretourbis['result']['result_code'];

            }

            if ($code_retourbis == 'OK') {
                $this->setCustomerRef($c_id, $wsretourbis['id']);
                $nb_imported_customers++;
                $id_ext_doli = $wsretourbis['id'];
            } else {
                // communication problem
                $result = 'KO';
                break;
            }

            // max client number reached
            $tmsp_now = time();
            $this->logInFile('--->tmsp Start :'.$tmsp_start, 'ERROR');
            $this->logInFile('--->tmsp Now :'.$tmsp_now, 'ERROR');
            $this->logInFile('--->tmsp diff :'.($tmsp_now - $tmsp_start), 'ERROR');
            if ((($tmsp_now - $tmsp_start) >= $this->nbr_max_sec_export) && ($nb_imported_customers != $nb_customers)) {
                $nb_max_customer = true;
                $this->logInFile("---> export time limit reached, break");
                break;
            }
        }

		$this->logInFile("-- exiting of importClients (".(time() - $tmsp_start)." seconds)");

        return array('result'=>$result, 'nbClientImported'=>$nb_imported_customers,
					 'nbClientTotal'=>$nb_customers, 'nbrMaxClient'=>$nb_max_customer,
					 'id_ext_doli'=>$id_ext_doli, 'reason'=>$wsretourbis['result']['result_label']);
    }

    /*
    * enrichment customers
    */
    public function enrichCustomer($id,$nom,$prenom,$client_ref,$is_client,$private_note,$email,$url,$pref_address)
    {
        $enrich = array(
					'id' => $id,
					'ref' => trim($this->noSpecialCharacterV3($nom.' '.$prenom)),
					'ref_ext' => $this->noSpecialCharacterV3($client_ref),
					'status' => '1', //0 = clos // 1 = actif
					'client' => $is_client,
					'supplier' => '0',
					'customer_code' => '-1',
					'supplier_code' => '',
					'customer_code_accountancy' => '',
					'supplier_code_accountancy' => '',
					'note_public' => 'Imported from Prestashop',
					'note_private' => $this->noSpecialCharacterV3($private_note),
					'province_id' => '',
					'country_id' => '',
					'fax' => '',
					'email' => $this->noSpecialCharacterV3($email),
					'url' => $this->noSpecialCharacterV3($url),
					'profid1' => '',
					'profid2' => '',
					'profid3' => '',
					'profid4' => '',
					'profid5' => '',
					'profid6' => '',
					'capital' => '',
					'barcode' => '',
					'vat_used' => '',
					'vat_number' => '',
					'canvas' => '',
					'individual' => ''
				);

		// address
		if (!empty($pref_address['id_address'])){

				$addr = $this->enrichAddress($pref_address);
				foreach ($addr as $f=>$v) {
					$enrich[$f] = $this->noSpecialCharacterV3($v);
				}
		}

		if (empty($enrich['zip']))   $enrich['zip'] = '00000';
		if (empty($enrich['phone'])) $enrich['phone'] = '0000000000';

        return $enrich;
    }

    private function enrichAddress($arr){

		if (!isset($this->countries[$arr['country']])){
			$obj_country = new Country();
			$id_country  = $obj_country->getIdByName(null, $arr['country']);
			$this->countries[$arr['country']] = $obj_country->getIsoById($id_country);
		}

		$adr = array(
					'address'      => trim((!empty($arr['address1'])?$arr['address1']:'') . (!empty($arr['address2']) ? ', '.$arr['address2'] : '')),
					'zip'          => (!empty($arr['postcode']) ? $arr['postcode'] : ''),
					'town'         => (!empty($arr['city']) ? $arr['city'] : ''),
					'country_code' => $this->countries[$arr['country']],
					'phone'        => (!empty($arr['phone_mobile']) ? $arr['phone_mobile'] : (!empty($arr['phone'])? $arr['phone'] : '')),
					'import_key'   => ($this->ws_trigram_value.$this->format($arr['id_address'], 10))
				);

		return $adr;
	}

    /**
    *
    * product import methods
    *
    **/
    public function importProduits($product_ec = 0, $tmsp_start = 0)
    {
        $this->logInFile('--importProduits--');
        $result              = 'OK';
        $nb_product_total    = 0;
        $nb_product_imported = 0;
        $nbr_max_product     = false;

        $this->logInFile('--IMPORTING THE PRODUCTS--');

        if ($this->dolibarr_ref_ind == 0) {
            $this->dolibarr_ref_ind = 1;
        }
        $this->logInFile('variable incrementation product ref: '.$this->dolibarr_ref_ind);

        // Get product ids including external reference <> external reference, in ascending order
        $idsrefdoliproduct = $this->getRefdoliEmpty('product');
        $this->logInFile('list of product ids including doli ref <> : '.print_r($idsrefdoliproduct, true));
        if ($idsrefdoliproduct) {
            foreach ($idsrefdoliproduct as $product) {
                if ($product['reference'] == '') {  // empty reference => we created it
                    $refdoli = $this->ws_trigram_value.$this->dolibarr_ref_ind;
                    $this->dolibarr_ref_ind++;
                    Configuration::updateValue('DOLIBARR_REF_IND', $this->dolibarr_ref_ind);
                    $this->updateRefEmpty($product['id_product'], $refdoli, 'product');
                } else { // reference completed
                    // Is it unique?
                    $is_unique_id = $this->isRefUnique($product['id_product'], $product['reference'], 'product');
                    // Test on lower ids of identical ref
                    if ($is_unique_id == 0) {
                        // It is unique
                        $refdoli = $product['reference'];
                    } else {
                        $refdoli = $product['reference'].'-p'.$this->dolibarr_ref_ind;
                        $this->dolibarr_ref_ind ++;
                        Configuration::updateValue('DOLIBARR_REF_IND', $this->dolibarr_ref_ind);
                    }
                }
                $this->insertRefDoli($product['id_product'], $refdoli, 'product');
            }
        }

        // Get product_attribute ids including external reference <> internal reference, in ascending order
        // Limit: no update of the attribute ref when it is empty internally
        //=> the chgt of a reference of a father product will be unchanged
        $idsrefdoliproduct = $this->getRefdoliEmpty('product_attribute');
        $this->logInFile('list of product_attribute ids including ref <> : '.print_r($idsrefdoliproduct, true));
        $ind = 1;
        if ($idsrefdoliproduct) {
            $id_product = -1;
            foreach ($idsrefdoliproduct as $product) {
                if ($product['id_product'] <> $id_product) {  // chgt de produit => reset increment
                    $ind = 1;
                    $id_product = $product['id_product'];
                }
                if ($product['reference'] == '') {  // empty reference => we created it
                    $refdoli = $product['product_reference'].'-d'.$ind;
                    $ind ++;
                } else { // reference completed
                    // Is it unique ?
                    $is_unique_id = $this->isRefUnique(
                        $product['id_product_attribute'],
                        $product['reference'],
                        'product_attribute'
                    );
                    if ($is_unique_id == 0) { // it's unique
                        $refdoli = $product['reference'];
                    } else {
                        $refdoli = $product['product_reference'].'-d'.$ind;
                        $ind ++;
                    }
                }
                $this->insertRefDoli($product['id_product_attribute'], $refdoli, 'product_attribute');
            }
        }

        // Taking father product ids
        if ($product_ec == 0) {
            // set_time_limit(600);
            $products = Product::getProducts(
                $this->context->cookie->id_lang,
                0,
                1000000,
                'id_product',
                'asc',
                false,
                false
            );
        } else {
            $products = array($product_ec);
        }

        // Account loop for all products
        foreach ($products as $product) {
             if (_PS_VERSION_ < '1.5') {
                $product_attributes_ids = $this->getProductAttributesIds($product['id_product'], 0);
            } else {
                $product_attributes_ids = Product::getProductAttributesIds($product['id_product'], false);
            }

            if (!$product_attributes_ids) {
                $product_attributes_ids = array (array('id_product_attribute'=>0));
            }
            foreach ($product_attributes_ids as $product_attribute_id) {
                $nb_product_total++;
            }
        }

        $id_product_doli_ec = '';
        $wsretour = array();
        $wsretour['result']['result_label'] = '';
        // father product path
        foreach ($products as $product) {
            $this->logInFile('->product path id: '.$product['id_product'].' / '.print_r($product, true));

            // Recovery of variations
            if (_PS_VERSION_ < '1.5') {
                $product_attributes_ids = $this->getProductAttributesIds($product['id_product'], 0);
            } else {
                $product_attributes_ids = Product::getProductAttributesIds($product['id_product'], false);
            }

            // The product has no attribute: we create a fictitious = '0'
            if (!$product_attributes_ids) {
                $product_attributes_ids = array(array('id_product_attribute'=>0));
            }

            // Loop on variations
            foreach ($product_attributes_ids as $product_attribute_id) {
                $product_ref = $this->ws_trigram_value.$this->format($product['id_product'], 10);
                if($product_attribute_id['id_product_attribute']>0) {
                	$this->format($product_attribute_id['id_product_attribute'], 10);
                }
                //$product_presta_ref = $product['reference'];

                // Internal references recuperation
                $product_ref_interne = $this->getProductRef(
                    $product['id_product'],
                    $product_attribute_id['id_product_attribute']
                );
                $this->logInFile(
                    '--> internal product references recovered ('.$product['id_product'].', '
                    .$product_attribute_id['id_product_attribute'].') : '.print_r($product_ref_interne, true));
                $this->logInFile(
                    'internal date : '.$product_ref_interne['date_export_doli'].' - date upd : '
                    .$product['date_upd'].' - date add : '.$product['date_add']);

                if ($product_attribute_id['id_product_attribute'] != 0) {
                    // $product_attribute_ref = $product_ref_interne['reference'];
                    $ean13 = $product_ref_interne['ean13'];
                    $upc = $product_ref_interne['upc'];
                } else {
                    // $product_attribute_ref = '';
                    $ean13 = $product['ean13'];
                    $upc = $product['upc'];
                }
                $product_presta_ref = $product_ref_interne['ref_doli'];
                $this->logInFile('-->ref attribute path : '.$product_ref.' / '.$product_presta_ref);

                // Bug correction on id attribute in orders and invoices
                if (array_key_exists('id_product_attribute', $product) == true) {
                    if ($product['id_product_attribute'] == $product_attribute_id['id_product_attribute']) {
                        $id_product_doli_ec = $product_ref_interne['id_ext_doli'];
                    }
                }

                // Anti deferral management
                if ($product_ref_interne['date_export_doli'] >= max($product['date_upd'], $product['date_add'])) {
                    $this->logInFile('--->attribut ref : '.$product_ref.' already imports as of maj, next');
                    $nb_product_imported++;
                    continue;
                }

                $code_retour = '';
                if ($product_ref_interne['id_ext_doli']) {   // update product
                    $this->logInFile('--- MAJ PRODUIT1 ---'."\n\n  +++product = ".print_r($product,true));
                    $enrich = $this->enrichProducts(
                        $product_ref_interne['id_ext_doli'],
                        $product_ref,
                        $product['id_product'],
                        $product_attribute_id['id_product_attribute'],
                        $product['description_short'],
                        $product['active'],
                        $this->default_lang,
                        $product_presta_ref,
                        $ean13,
                        $upc
                    );
                    $wsretour = $this->WSModProduct($enrich);
                    $code_retour = $wsretour['result']['result_code'];
                }
                if ((!$product_ref_interne['id_ext_doli']) || ($code_retour == 'NOT_FOUND')) {
                    // add product if non-existent or update failed
                    // make a get to make sure it doesn't already exist
                    // (in case of reinstallation) and recover its id
                    if ($code_retour != 'NOT_FOUND') {
                        $wsretour = $this->WSGetProduct($product_ref);
                        $code_retour = $wsretour['result']['result_code'];
                    }
                    if ($code_retour == 'OK') { // update product
                        $this->logInFile('--- MAJ PRODUIT2 ---');
                        $enrich = $this->enrichProducts(
                            $wsretour['product']['id'],
                            $product_ref,
                            $product['id_product'],
                            $product_attribute_id['id_product_attribute'],
                            $product['description_short'],
                            $product['active'],
                            $this->default_lang,
                            $product_presta_ref,
                            $ean13,
                            $upc
                        );
                        $wsretour = $this->WSModProduct($enrich);
                        $code_retour = $wsretour['result']['result_code'];
                    } elseif ($code_retour == 'NOT_FOUND') {  // create product
                        $this->logInFile('--- ADD PRODUCT ---');
                        $enrich = $this->enrichProducts(
                            '',
                            $product_ref,
                            $product['id_product'],
                            $product_attribute_id['id_product_attribute'],
                            $product['description_short'],
                            $product['active'],
                            $this->default_lang,
                            $product_presta_ref,
                            $ean13,
                            $upc
                        );
                        $wsretour = $this->WSAddProduct($enrich);
                        $code_retour = $wsretour['result']['result_code'];
                    }
                }

                if ($code_retour == 'OK') {
                    $this->setProductRef(
                        $product['id_product'],
                        $product_attribute_id['id_product_attribute'],
                        $wsretour['id'],
                        $wsretour['ref']
                    );
                    $nb_product_imported++;
                    $product_ref_interne['id_ext_doli'] = $wsretour['id'];
                    $product_ref_interne['date_export_doli'] = date('Y-m-d H:i:s');
                } else {       // pb de communication
                    $this->logInFile("---> crash during attribute update : $product_ref", 'ERROR');
                    $result = 'KO';
                    break;
                }

                // number of max product reached
                $tmsp_now = time();
                $this->logInFile('--->tmsp Start :'.$tmsp_start, 'ERROR');
                $this->logInFile('--->tmsp Now :'.$tmsp_now, 'ERROR');
                $this->logInFile('--->tmsp diff :'.($tmsp_now - $tmsp_start), 'ERROR');
                if ((($tmsp_now - $tmsp_start) >= $this->nbr_max_sec_export) &&
                ($nb_product_imported != $nb_product_total)) {
                    $nbr_max_product = true;
                    $this->logInFile('---> max number of exported products reached, break');
                    break;
                }

                // correction bug on id attribute in orders and invoices
                if (array_key_exists('id_product_attribute', $product) == true) {
                    if ($product['id_product_attribute'] == $product_attribute_id['id_product_attribute']) {
                        $id_product_doli_ec = $product_ref_interne['id_ext_doli'];
                    }
                }
            }
            if ($result == 'KO' || $nbr_max_product) {
                break;
            }
        }

        return array('result'=>$result, 'nbProductImported'=>$nb_product_imported,
        'nbProductTotal'=>$nb_product_total, 'nbrMaxProduct'=>$nbr_max_product,
        'id_ext_doli'=>$id_product_doli_ec, 'reason'=>$wsretour['result']['result_label']);
    }

    /*
    * product enrichment
    */
    public function enrichProducts(
        $id_ext_doli,
        $product_ref,
        $id_product,
        $id_product_attribute,
        $description_short,
        $active,
        $default_language,
        $product_presta_ref,
        $ean13,
        $upc
    ) {
        $this->logInFile('--enrichProducts--');
        $price = Product::getPriceStatic(
            $id_product,
            true,
            $id_product_attribute,
            6,
            null,
            false,
            true,
            1,
            false,
            null,
            0
        );
        $quantity = Product::getQuantity($id_product, $id_product_attribute);

        if (_PS_VERSION_ < '1.5') {
            $product_name = $this->getProductName($id_product, $id_product_attribute, $default_language);
            $get_tva_tx = $this->getTaxesInformations($id_product);
        } else {
            $product_name = Product::getProductName($id_product, $id_product_attribute, $default_language);
            $get_tva_tx = Product::getTaxesInformations(array('id_product'=>$id_product));
        }
        $tva_tx = $get_tva_tx['rate'];

        $product_ref = $this->noSpecialCharacterV3($product_ref);
        $description_short = $this->noSpecialCharacterV3(strip_tags($description_short));
        $id_ext_doli = $this->noSpecialCharacterV3($id_ext_doli);
        $product_name = $this->noSpecialCharacterV3(strip_tags($product_name));

        $enrich = array();
        $enrich['id'] = $id_ext_doli;
        $enrich['ref'] = $product_presta_ref;
        $enrich['ref_ext'] = $product_ref;
        $enrich['label'] = $product_name;
        $enrich['type'] = '0';
        if ($ean13 != '') {
            $enrich['barcode'] = $ean13;
            $enrich['barcode_type'] = 2;
        } elseif ($upc != '') {
            $enrich['barcode'] = $upc;
            $enrich['barcode_type'] = 3;
        } else {
            $enrich['barcode'] = '';
            $enrich['barcode_type'] = '';
        }
        $enrich['description'] = $description_short;
        $enrich['note'] = 'imported from Prestashop'; // not this notion in prestashop => tagged as imported from presta
        $enrich['status_tosell'] = $active;
        $enrich['status_tobuy'] = '0';
        $enrich['country_id'] = '';
        $enrich['country_code'] = '';
        $enrich['custom_code'] = '';
        $enrich['price_net'] = '';
        $enrich['price'] = $price;
        $enrich['vat_rate'] = $tva_tx;
        $enrich['price_base_type'] = 'TTC';
        $enrich['stock_alert'] = '';
        if ($this->is_checked_synch_stock) {
            $enrich['stock_real'] = $quantity;
            $enrich['warehouse_ref'] = $this->ws_warehouse_value;
        }
        $enrich['pmp'] = '';
        $enrich['canvas'] = '';

		// ToDo: Multi Image
		// by @wdammak

		// image recovery
        $image_product = Image::getImages($default_language, $id_product, $id_product_attribute);

		// ToDo: Add all images to Dolibarr
		// by @wdammak

		// prepare array
		if (!array_key_exists(0, $image_product))
		{
			$image_id = $this->getIdImage($id_product);
			$image_product = array('id_image'=> $image_id);
		}

		// multi image
		foreach($image_product as $key => $curimage)
		{
			//if ($image_id != '') /* [caos30] */
			if (is_array($curimage) && !empty($curimage['id_image']))
			{
				$image_id = $curimage['id_image'];
				if ($image_id < 10)
					$image_path = $image_id.'/';
				else if ($image_id >= 10 && $image_id < 100)
					$image_path = $image_id[0].'/'.$image_id[1].'/';
				else if ($image_id >= 100 && $image_id < 1000)
					$image_path = $image_id[0].'/'.$image_id[1].'/'.$image_id[2].'/';
				else if ($image_id >= 1000 && $image_id < 10000)
					$image_path = $image_id[0].'/'.$image_id[1].'/'.$image_id[2].'/'.$image_id[3].'/';

				$imageType = '-'.ImageType::getFormatedName('home');
				$image_path_hd = $image_path.$image_id.$imageType.'.jpg';

				$image_name = $image_id.$imageType.'.jpg';
				$soapclient = new nusoap_client('test');

				$image_path_hd2 = str_replace('\\', '/', _PS_PROD_IMG_DIR_.$image_path_hd);
				if (($image_path_hd2 != '') && (file_exists($image_path_hd2))) {
					$image_b64 = base64_encode(Tools::file_get_contents($image_path_hd2));
				} else {
					$image_b64 = '';
				}

				$this->logInFile("->image : \nfor product name : ".$product_name."\n hard drive image = $image_path_hd2 \n & image name : ".$image_name);

				$enrich['images']['image'][] = array(
												'id_image'=> $image_id,
												'photo'=> $image_b64,
												'photo_vignette'=> $image_name,
												'imgWidth'=> '250',
												'imgHeight'=> '250',
												);
			} else {
				$this->logInFile("->image : No image for this product ".(is_array($curimage)?"|n  curimage = ".var_export($curimage,true):''));
			}
		}

        // retrieving product categories
        if ($this->is_checked_synch_category == 'true') {
            $category_obj_list = Product::getProductCategories($id_product);
            $this->logInFile('->getProductCategories '.$id_product.' : '.print_r($category_obj_list, true));
            foreach ($category_obj_list as $id => $cat) {
                $category_obj_list[$id] = $this->ws_trigram_value.$this->format($cat, 10);
            }
            $this->logInFile('-> getProductCategories after transformation '.$id_product.' : '.print_r($category_obj_list, true));
            $enrich['category_list'] = $category_obj_list;
        }
        return $enrich;
    }

    /**
    *
    * export one/all invoices/orders from prestashop to dolibarr
    *
    **/
    public function importFacturesOrCommandes($facture_ec = 0, $tmsp_start = 0, $is_commande = 0, $is_facture = 0)
    {
        $this->logInFile('--importFacturesOrCommandes--');
        $nb_order_ok = 0;
        $result = 'OK';
        $nb_order_total = 0;
        $nbr_max_order = false;

        // == mass export of all invoices/orders

        if ((is_int($facture_ec) == true) && ($facture_ec == 0)) {

            // we get the orders of all customers
			$obj_customer = new Customer();
            $customers = $obj_customer->getCustomers();
            foreach ($customers as $customer) {
                $c_id = $customer['id_customer'];
                $orders = Order::getCustomerOrders((int)$c_id, true);
                if (is_array($orders)) $nb_order_total += count($orders);
            }

            // customer loop
            foreach ($customers as $customer) {
                $c_id = $customer['id_customer'];
                $this->logInFile('customer id = '.$c_id.' customer = '.print_r($customer,true));

                $orders = Order::getCustomerOrders((int)$c_id, true);  // we recover the customer orders
                $orders = array_reverse($orders);

                foreach ($orders as $row) {
                    $order = new Order($row['id_order']);
                    $enrich_retour = $this->enrichOrderAndSend($order, $tmsp_start, $is_commande, $is_facture);
                    $this->logInFile('---> return of enrichOrderAndSend : '.print_r($enrich_retour, true));
                    $code_retour = is_array($enrich_retour) && !empty($enrich_retour['code']) ? $enrich_retour['code'] : 'KO';
                    if ($code_retour == 'OK' || $code_retour == 'DBL') {
                        $nb_order_ok++;
                    } else {
                        $this->logInFile('export object failed, so stopped synchronization', 'ERROR');
                        $result = 'KO';
                        if (!is_array($enrich_retour)) $enrich_retour = array();
                        if (!isset($enrich_retour['reason'])) $enrich_retour['reason'] = print_r($enrich_retour,true);
                        break;
                    }

                    // time limit exceeded ?
                    $tmsp_now = time();
                    $this->logInFile('---> tmsp Start :'.$tmsp_start, 'DEBUG');
                    $this->logInFile('---> tmsp Now :'.$tmsp_now, 'DEBUG');
                    $this->logInFile('---> tmsp diff :'.($tmsp_now - $tmsp_start), 'DEBUG');
                    if ((($tmsp_now - $tmsp_start) >= $this->nbr_max_sec_export) && ($nb_order_ok != $nb_order_total)) {
                        $nbr_max_order = true;
                        $this->logInFile('---> time limit exceeded in the export of orders/invoices, break','ERROR');
                        break;
                    }
                }

				// export through WS failed
				if ($result == 'KO') break;

                // break time limit exceeded
                if ($nbr_max_order) break;

            }

		// == export of one invoice/order

        } else {

			$nb_order_total = 1;
            $this->logInFile('---> enrichOrderAndSend ');
            $enrich_retour = $this->enrichOrderAndSend($facture_ec, $tmsp_start, $is_commande, $is_facture);
            $this->logInFile('---> return of enrichment order & send 2 (a unique invoice/order) : '.print_r($enrich_retour, true));
            if ($enrich_retour['code'] == 'OK' || $enrich_retour['code'] == 'DBL') {
                $nb_order_ok = 1;
            } else {
                $result = 'KO';
            }

            // time limit exceeded ?
            $tmsp_now = time();
            $this->logInFile('--->tmsp Start :'.$tmsp_start, 'ERROR');
            $this->logInFile('--->tmsp Now :'.$tmsp_now, 'ERROR');
            $this->logInFile('--->tmsp diff :'.($tmsp_now - $tmsp_start), 'ERROR');
            if (($tmsp_now - $tmsp_start) >= $this->nbr_max_sec_export && $nb_order_ok != $nb_order_total) {
                $nbr_max_order = true;
                $this->logInFile("---> time limit exceeded in the export of orders/invoices, break");
            }
        }

        return array('result'=>$result, 'nbOrderOk'=>$nb_order_ok, 'nbrMaxOrder'=>$nbr_max_order,
					 'nbOrderTotal'=>$nb_order_total,'reason'=>$enrich_retour['reason']);
    }

    /**
     * enrichment of ONE invoice/order and sending to dolibarr
     */
    public function enrichOrderAndSend($facture_ec, $tmsp_start, $export_order, $export_invoice)
    {
        $this->logInFile('--enrichOrderAndSend--');
        $id_order = $facture_ec->id;
        $reductions = $facture_ec->total_discounts_tax_incl;
        $id_address_delivery = $this->ws_trigram_value.$this->format($facture_ec->id_address_delivery, 10);
        $id_address_invoice = $this->ws_trigram_value.$this->format($facture_ec->id_address_invoice, 10);
        $this->logInFile('invoice/order : '.print_r($facture_ec, true));

        // taking the name of the carrier
        $id_lang = (int)Context::getContext()->language->id;
        if ($id_lang == '') {
            $id_lang     = $this->default_lang;
        }
        $id_transporteur = $facture_ec->id_carrier;
        $obj_carrier = new Carrier($id_transporteur, $id_lang);
        $name_transporteur = $obj_carrier->name;
        $this->logInFile('carrier name : '.$name_transporteur);

        // avoid to export again if it there is no change since last export date
        $order_ref = $this->getOrderRef($id_order);
        if ($export_order){
			if (array_key_exists('date_export_order_doli', $order_ref)) {
				if ($order_ref['date_export_order_doli'] >= max($facture_ec->date_upd, $facture_ec->date_add)) {
					$export_order = false;
				}
			}
		}
        if ($export_invoice){
			if (array_key_exists('date_export_invoice_doli', $order_ref)) {
				if ($order_ref['date_export_invoice_doli'] >= max($facture_ec->date_upd, $facture_ec->date_add)) {
					$export_invoice = false;
				}
			}
		}
		if (!$export_order && !$export_invoice) return array('code'=>'OK', 'reason'=>'Not changes since last synchronization.');

        // prepare data to be exported
        $date_facture = $facture_ec->date_add;
        $module_reglement = $facture_ec->module;
        $mode_reglement = $facture_ec->payment;
        $statut = $this->getStatutDolibarr($facture_ec->current_state);

        if (_PS_VERSION_ < '1.5') {
            $total_shipping_tax_incl = $facture_ec->total_shipping;
            $carrier_tax_rate = $this->getCarrierTaxes($id_order);
            $total_shipping_tax_excl = round($total_shipping_tax_incl / (1 + abs($carrier_tax_rate) / 100), 2);
        } else {
            $total_shipping_tax_incl = $facture_ec->total_shipping_tax_incl;
            $total_shipping_tax_excl = $facture_ec->total_shipping_tax_excl;
            $carrier_tax_rate = $facture_ec->carrier_tax_rate;
        }

        // creation of the customer's array of the invoice:
        $obj_customer = new customer($facture_ec->id_customer);
        $customer = array(
						'id_customer'=>$obj_customer->id,
						'email'=>$obj_customer->email,
						'firstname'=>$obj_customer->firstname,
						'lastname'=>$obj_customer->lastname
					);

		// products
        $product_tab = array();
        $products = $facture_ec->getProducts();
        $this->logInFile('Products of the order '.$id_order.' : '.print_r($products, true));
        $i = 0;
        foreach ($products as $product) {
            $product_tab[$i]['product_id'] = $product['product_id'];
            $product_tab[$i]['product_attribute_id'] = $product['product_attribute_id'];
            $product_tab[$i]['product_quantity'] = $product['product_quantity'];
            $product_tab[$i]['tax_rate'] = $product['tax_rate'];
            // BOG
            if (_PS_VERSION_ < '1.5') {
                // finalbug $product_tab[$i]['total_price_tax_incl'] = $product['total_price'];
                $product_tab[$i]['total_price_tax_incl'] = $product['total_wt'];
                if ($product['total_wt'] = $product['total_price']) {
                    $product_tab[$i]['total_price_tax_excl'] =
                    $product['product_quantity'] * round($product['product_price'], 2);
                } else {
                    $product_tab[$i]['total_price_tax_excl'] = round($product['total_price'], 2);
                }
                $product_tab[$i]['unit_price_tax_excl'] = round($product['product_price'], 2);
                $product_tab[$i]['active'] = 1;
            } else {
                $product_tab[$i]['total_price_tax_incl'] = $product['total_price_tax_incl'];
                $product_tab[$i]['total_price_tax_excl'] = $product['total_price_tax_excl'];
                $product_tab[$i]['unit_price_tax_excl'] = $product['unit_price_tax_excl'];
                $product_tab[$i]['active'] = $product['active'];
            }
            $i++;
        }

		$code_retour = 'OK';
        $wsretour = array();
        if ($export_order) {
            $wsretour = $this->WSAddOrder(
										$tmsp_start,
										$id_order,
										$customer,
										$date_facture,
										$product_tab,
										$total_shipping_tax_incl,
										$total_shipping_tax_excl,
										$carrier_tax_rate,
										$statut,
										$module_reglement,
										$mode_reglement,
										$reductions,
										$name_transporteur,
										$id_address_delivery,
										$id_address_invoice,
										$order_ref['id_ext_order_doli']
									);
            $code_retour = $wsretour['result']['result_code'];
            $this->logInFile('returned by WSAddOrder : '.$code_retour);
            if ($code_retour == 'OK') {
                $this->setOrderRef($id_order, $wsretour['id']);
            }
        }

        if ($export_invoice && ($code_retour == 'OK' || $code_retour == 'DBL')) {
            $wsretour = $this->WSAddInvoice(
										$tmsp_start,
										$id_order,
										$customer,
										$date_facture,
										$product_tab,
										$total_shipping_tax_incl,
										$total_shipping_tax_excl,
										$carrier_tax_rate,
										$reductions,
										$name_transporteur,
										$id_address_delivery,
										$id_address_invoice,
										$order_ref['id_ext_invoice_doli'],
										$order_ref['id_ext_order_doli'],
										$facture_ec->current_state
									);
            $code_retour = $wsretour['result']['result_code'];
            if ($code_retour == 'OK') {
                $this->setInvoiceRef($id_order, $wsretour['id']);
            }
        }

        if (is_array($wsretour) && !empty($wsretour['result']) && !empty($wsretour['result']['result_label'])) {
            $reason = $wsretour['result']['result_label'];
        } else {
            $reason = '';
        }

        return array('code'=>$code_retour, 'reason'=>$reason);
    }

    /**
    *
    * Webservice communication methods
    *
    **/

    /** Test methods  */
    public function WSVersion($wsurl)
    {
        $this->logInFile('--WSVersion--');
        $ws_dol_url = $wsurl.$this->ws_adress_dolibarr.'/pj_ws_version.php';
        $ws_method  = 'getVersions';
        $ns = 'http://www.Dolibarr.org/ns/';
        //$versionToReturn = '0';

        $soapclient = new nusoap_client($ws_dol_url);
        if ($soapclient) {
            $soapclient->soap_defencoding = 'UTF-8';
            $soapclient->decodeUTF8(false);
        }
        $authentication = array(
            'dolibarrkey'=>$this->ws_key_value,
            'sourceapplication'=>'PRESTASHOP',
            'login'=>$this->ws_login_value,
            'password'=>$this->ws_passwd_value,
            'entity'=>''
        );

        $parameters = array('authentication'=>$authentication);
        $result = $soapclient->call($ws_method, $parameters, $ns, '');
        $this->logInFile('getVersions call : '.$ws_dol_url.' - '.print_r($parameters, true));
        $this->logInFile('getVersions response : '.print_r($result, true));

        return $result;
    }

    /** Customer Methods */
    public function WSGetCustomer($client_ref)
    {
        $this->logInFile('--WSGetCustomer--');
        $wsurl = $this->ws_adress_value;
        $ws_dol_url = $wsurl.$this->ws_adress_dolibarr.'/pj_ws_clients.php';
        $ws_method  = 'getThirdParty';
        $ns = 'http://www.Dolibarr.org/ns/';

        $soapclient = new nusoap_client($ws_dol_url);
        if ($soapclient) {
            $soapclient->soap_defencoding = 'UTF-8';
            $soapclient->decodeUTF8(false);
        }
        $authentication = array(
            'dolibarrkey'=>$this->ws_key_value,
            'sourceapplication'=>'PRESTASHOP',
            'login'=>$this->ws_login_value,
            'password'=>$this->ws_passwd_value,
            'entity'=>''
        );

        $parameters = array('authentication'=>$authentication, 'id'=>'', 'ref'=>'', 'ref_ext'=>$client_ref);

        $result_tab = $soapclient->call($ws_method, $parameters, $ns, '');

        $this->logInFile('exchange with '.$ws_method.' method ('.$ws_dol_url.') ');
        $this->logInFile('request:'.$soapclient->request);
        $this->logInFile('WS response:'.print_r($result_tab, true));

        return $result_tab;
    }

    public function WSModCustomer($thirdparty)
    {
        $this->logInFile('--WSModCustomer--');
        $wsurl = $this->ws_adress_value;
        $ws_dol_url = $wsurl.$this->ws_adress_dolibarr.'/pj_ws_clients.php';
        $ws_method  = 'updateThirdParty';
        $ns = 'http://www.Dolibarr.org/ns/';

        $soapclient = new nusoap_client($ws_dol_url);
        if ($soapclient) {
            $soapclient->soap_defencoding = 'UTF-8';
            $soapclient->decodeUTF8(false);
        }
        $authentication = array(
            'dolibarrkey'=>$this->ws_key_value,
            'sourceapplication'=>'PRESTASHOP',
            'login'=>$this->ws_login_value,
            'password'=>$this->ws_passwd_value,
            'entity'=>''
        );

        $parameters = array('authentication'=>$authentication, 'thirdparty'=>$thirdparty);
        $result_tab = $soapclient->call($ws_method, $parameters, $ns, '');

        $this->logInFile('exchange with the '.$ws_method.' method ('.$ws_dol_url.') ');
        $this->logInFile('request:'.$soapclient->request);
        $this->logInFile('reponse of WS mod_client:'.print_r($result_tab, true));

        return $result_tab;
    }

    public function WSAddCustomer($enrich_customer)
    {
        $this->logInFile('--WSAddCustomer--');
        $wsurl = $this->ws_adress_value;
        $ws_dol_url = $wsurl.$this->ws_adress_dolibarr.'/pj_ws_clients.php';
        $ws_method  = 'createThirdParty';
        $ns = 'http://www.Dolibarr.org/ns/';

        $soapclient = new nusoap_client($ws_dol_url);
        if ($soapclient) {
            $soapclient->soap_defencoding = 'UTF-8';
            $soapclient->decodeUTF8(false);
        }
        $authentication = array(
            'dolibarrkey'=>$this->ws_key_value,
            'sourceapplication'=>'PRESTASHOP',
            'login'=>$this->ws_login_value,
            'password'=>$this->ws_passwd_value,
            'entity'=>''
        );

        $parameters = array('authentication'=>$authentication, 'thirdparty'=>$enrich_customer);

        $result_tab = $soapclient->call($ws_method, $parameters, $ns, '');

        $this->logInFile('echange avec le '.$ws_method.' ('.$ws_dol_url.') ');
        $this->logInFile('request:'.$soapclient->request);
        $this->logInFile('WS response:'.print_r($result_tab, true));

        return $result_tab;
    }

    /** Methodes Products */
    public function WSAddProduct($product)
    {
        $this->logInFile('--WSAddProduct--');
        $wsurl = $this->ws_adress_value;

        if ($this->dolibarr_version == 380 || $this->dolibarr_version == 381) {
            $wsProductAdresse = "pj_ws_products3801.php";
        } else {
            $wsProductAdresse = "pj_ws_products.php";
        }
        $ws_dol_url = $wsurl.$this->ws_adress_dolibarr.'/'.$wsProductAdresse;

        $ws_method  = 'createProductOrService';
        $ns = 'http://www.Dolibarr.org/ns/';

        $soapclient = new nusoap_client($ws_dol_url);
        if ($soapclient) {
            $soapclient->soap_defencoding = 'UTF-8';
            $soapclient->decodeUTF8(false);
        }
        $authentication = array(
            'dolibarrkey'=>$this->ws_key_value,
            'sourceapplication'=>'PRESTASHOP',
            'login'=>$this->ws_login_value,
            'password'=>$this->ws_passwd_value,
            'entity'=>''
        );

        $parameters = array('authentication'=>$authentication, 'product'=>$product);
        $result_tab = $soapclient->call($ws_method, $parameters, $ns, '');

        $this->logInFile('echange avec le '.$ws_method.' ('.$ws_dol_url.') ');
        $this->logInFile('request:'.$soapclient->request);
        $this->logInFile('reponse du WS add_product in tab:'.print_r($result_tab, true));

        return $result_tab;
    }

    public function WSModProduct($product)
    {
        $this->logInFile('--WSModProduct--');
        $wsurl = $this->ws_adress_value;

        if ($this->dolibarr_version == 380 || $this->dolibarr_version == 381) {
            $wsProductAdresse = "pj_ws_products3801.php";
        } else {
            $wsProductAdresse = "pj_ws_products.php";
        }
        $ws_dol_url = $wsurl.$this->ws_adress_dolibarr.'/'.$wsProductAdresse;

        $ws_method  = 'updateProductOrService';
        $ns = 'http://www.Dolibarr.org/ns/';

        $soapclient = new nusoap_client($ws_dol_url);
        if ($soapclient) {
            $soapclient->soap_defencoding = 'UTF-8';
            $soapclient->decodeUTF8(false);
        }
        $authentication = array('dolibarrkey'=>$this->ws_key_value, 'sourceapplication'=>'PRESTASHOP',
        'login'=>$this->ws_login_value, 'password'=>$this->ws_passwd_value, 'entity'=>'');

        $parameters = array('authentication'=>$authentication, 'product'=>$product);
        $result_tab = $soapclient->call($ws_method, $parameters, $ns, '');
        $this->logInFile('echange avec le '.$ws_method.' ('.$ws_dol_url.') ');
        $this->logInFile('request:'.$soapclient->request);
        $this->logInFile('reponse du WS mod_product in tab:'.print_r($result_tab, true));

        return $result_tab;
    }

    public function WSGetProduct($ref)
    {
        $this->logInFile('--WSGetProduct--');
        $wsurl = $this->ws_adress_value;

        $this->logInFile('--Version Dolibarr = '.$this->dolibarr_version);
        if ($this->dolibarr_version == 380 || $this->dolibarr_version == 381) {
            $wsProductAdresse = "pj_ws_products3801.php";
        } else {
            $wsProductAdresse = "pj_ws_products.php";
        }
        $ws_dol_url = $wsurl.$this->ws_adress_dolibarr.'/'.$wsProductAdresse;

        $ws_method  = 'getProductOrService';
        $ns = 'http://www.Dolibarr.org/ns/';

        $soapclient = new nusoap_client($ws_dol_url);
        if ($soapclient) {
            $soapclient->soap_defencoding = 'UTF-8';
            $soapclient->decodeUTF8(false);
        }
        $authentication = array('dolibarrkey'=>$this->ws_key_value, 'sourceapplication'=>'PRESTASHOP',
        'login'=>$this->ws_login_value, 'password'=>$this->ws_passwd_value, 'entity'=>'');
        $parameters = array('authentication'=>$authentication, 'id'=>'', 'ref'=>'', 'ref_ext'=>$ref);

        //call
        $result_tab = $soapclient->call($ws_method, $parameters, $ns, '');

        $this->logInFile('echange avec le '.$ws_method.' ('.$ws_dol_url.') ');
        $this->logInFile('request:'.$soapclient->request);
        $this->logInFile('reponse du WS getproduct in tab:'.print_r($result_tab, true));

        return $result_tab;
    }

    /*******************
    // Methodes Factures
    //*****************/
    public function WSAddInvoice(
								$tmsp_start,
								$id_order,
								$customer,
								$date_facture,
								$product_tab,
								$total_shipping_tax_incl,
								$total_shipping_tax_excl,
								$carrier_tax_rate,
								$reductions,
								$name_transporteur,
								$id_address_delivery,
								$id_address_invoice,
								$id_ext_invoice_doli,
								$id_ext_order_doli,
								$current_state
							) {
        $this->logInFile('--WSAddInvoice--');
        $ref_order = $this->ws_trigram_value.$this->format($id_order, 10);
        //$refCustomer = $this->ws_trigram_value.$this->format($customer['id_customer'], 10);

        $wsurl = $this->ws_adress_value;
        $ws_dol_url_invoice = $wsurl.$this->ws_adress_dolibarr.'/pj_ws_factures.php';
        $ws_method_get_invoice  = 'getInvoice';
        $ws_method_create_invoice = 'createInvoice';
        $ns = 'http://www.Dolibarr.org/ns/';
        $authentication = array(
            'dolibarrkey'=>$this->ws_key_value,
            'sourceapplication'=>'PRESTASHOP',
            'login'=>$this->ws_login_value,
            'password'=>$this->ws_passwd_value,
            'entity'=>''
        );

        $retour_ko = array();
        $retour_ko['result']['result_code'] = 'KO';
        $retour_dbl = array();
        $retour_dbl['result']['result_code'] = 'DBL';

        // we do a get invoice to avoid to create a duplicate
        $soapclient1 = new nusoap_client($ws_dol_url_invoice);
        if ($soapclient1) {
            $soapclient1->soap_defencoding = 'UTF-8';
            $soapclient1->decodeUTF8(false);
        }
        $parameters1 = array('authentication'=>$authentication, 'id'=>$id_ext_invoice_doli, 'ref'=>'', 'ref_ext'=>'');
        $result_get_order_tab = $soapclient1->call($ws_method_get_invoice, $parameters1, $ns, '');
        $result_get_order = $result_get_order_tab['result']['result_code'];

        $this->logInFile('exchange with the '.$ws_method_get_invoice.' method ('.$ws_dol_url_invoice.') ');
        $this->logInFile('request:'.$soapclient1->request);
        $this->logInFile('WS response:'.print_r($result_get_order_tab, true));

        if ($result_get_order == 'OK') {
            return $retour_dbl;
        } elseif ($result_get_order == 'KO') {
            return $retour_ko;
        }

		// synced customer
		if (isset($this->already_synced_customers[$customer['id_customer']])){
			$synced_customer = $this->already_synced_customers[$customer['id_customer']];
		}else{
			$synced_customer = $this->importClients(array('0' =>$customer), $tmsp_start);
			$this->logInFile('\result of export customer of the order: '.print_r($synced_customer,true));
			if ($synced_customer['result'] != 'OK') {
				return $retour_ko;
			}
		}
		$this->logInFile('\customer of the invoice: id_ext_doli='.$synced_customer['id_ext_doli']);

        // if we got the customer_id we save the product_id
        $lines = array();
        $line = array();
        unset($lines);
        //for ($i = 0; $product_tab[$i]['product_id'] != ''; $i++)
        for ($i = 0; array_key_exists($i, $product_tab) && $product_tab[$i]['product_id'] != ''; $i++) {
            $ref_product = $this->ws_trigram_value.$this->format($product_tab[$i]['product_id'], 10).
            $this->format($product_tab[$i]['product_attribute_id'], 10);

            // we export the product
            $this->logInFile('product in invoice : '.$ref_product.' : '.print_r($product_tab[$i], true));

            $testproduct = new product($product_tab[$i]['product_id'], true, $this->default_lang, null, null);

            $description_short = $this->noSpecialCharacterV3($testproduct->description_short);

            $product_to_set = array(
								'id_product'=>$product_tab[$i]['product_id'],
								'description_short'=>$description_short,
								'active'=>$testproduct->active,
								'date_upd'=>$testproduct->date_upd,
								'date_add'=>$testproduct->date_add,
								'reference'=>$testproduct->reference,
								'ean13'=>$testproduct->ean13,
								'upc'=>$testproduct->upc,
								'id_product_attribute'=>$product_tab[$i]['product_attribute_id']
							);
            $result_product = $this->importProduits($product_to_set, $tmsp_start);
            if ($result_product['result'] != 'OK') {
                return $retour_ko;
            }
            $id_product = $result_product['id_ext_doli'];
            $this->logInFile('Product of the order : '.$id_product);

            // prepare the product for WS
            $lines[$i] = array(
							'type' => '0',       // 0=product, 1=service
							'desc' => 'Product',
							//'fk_product' => '',
							'unitprice' => round($product_tab[$i]['unit_price_tax_excl'], 2),
							'total_net' => $product_tab[$i]['total_price_tax_excl'],
							'total_vat' => ($product_tab[$i]['total_price_tax_incl'] - $product_tab[$i]['total_price_tax_excl']),
							'total' => $product_tab[$i]['total_price_tax_incl'],
							//'vat_rate'] = $resultGetProductTab['product']['vat_rate'],
							'vat_rate' => $product_tab[$i]['tax_rate'],
							'qty' => $product_tab[$i]['product_quantity'],
							'product_id' => $id_product   // product_id on dolibarr
						);
        }

        // add hipping cost as "service" on the invoice, if needed
        if ($total_shipping_tax_incl != 0) {
            $lines[$i] = array(
							'type' => '1',    // 0=product, 1=service
							'desc' => 'delivery : '.$name_transporteur,
							'unitprice' => $total_shipping_tax_excl,
							'total_net' => $total_shipping_tax_excl,
							'total_vat' => ($total_shipping_tax_incl - $total_shipping_tax_excl),
							'total' => $total_shipping_tax_incl,
							'vat_rate' => $carrier_tax_rate,
							'qty' => '1',
							'product_id' => '', // product_id on dolibarr
						);
        }

        // add discounts, if any
        if ($reductions != 0) {
			$reductions = $reductions * -1;
            $reductions_ht = round($reductions / (1 + $carrier_tax_rate / 100) , 2);
            $i++;
            $lines[$i] = array(
							'type' => '1',    // 0=product, 1=service
							'desc' => 'Discount',
							'unitprice' => $reductions_ht,
							'total_net' => $reductions_ht,
							'total_vat' => ($reductions - $reductions_ht),
							'total' => $reductions,
							'vat_rate' => $carrier_tax_rate,
							'qty' => '1',
							'product_id' => '', // product_id on dolibarr
						);
        }

        $this->logInFile('lines : '.print_r($lines, true));

        // dolibarr status of the invoice
		// 		0=draft, 1=validated (need to be paid), 2=classified paid partially
		// 		3=classified abandoned and no payment done (close_code = 'badcustomer', 'abandon' || 'replaced')
		$status = in_array($current_state,array('2','11')) ? '1' : '0'; // validated only if on prestashop is "payment accepted" (2) or "remote payment accepted" (11)

        // prepare invoice object for webservice
        $invoice = array(
						'ref_ext' => $ref_order,
						'thirdparty_id' => $synced_customer['id_ext_doli'],  // id on dolibarr
						'date' => $date_facture,
						'type' => '0', // 0=Standard invoice, 1=Replacement invoice, 2=Credit note invoice, 3=Deposit invoice, 4=Proforma invoice
						'note_private' => 'Imported by Prestashop on '.date('d/m/Y H:i:s'),
						'note_public' => '',
						'status' => $status,
						'project_id' => '',
						'id_address_delivery' => $id_address_delivery,
						'id_address_invoice' => $id_address_invoice,
						'lines' => $lines,
						'id_ext_order_doli' => $id_ext_order_doli
					);

        $soapclient4 = new nusoap_client($ws_dol_url_invoice);
        if ($soapclient4) {
            $soapclient4->soap_defencoding = 'UTF-8';
            $soapclient4->decodeUTF8(false);
        }
        $parameters4 = array('authentication'=>$authentication, 'invoice'=>$invoice);
        $result_create_invoice_tab = $soapclient4->call($ws_method_create_invoice, $parameters4, $ns, '');

        $this->logInFile('exchange with the '.$ws_method_create_invoice.' method');
        $this->logInFile('request:'.$soapclient4->request);
        $this->logInFile('WS response:'.print_r($result_create_invoice_tab['result'], true));

        return $result_create_invoice_tab;
    }

    /*******************
    // Orders
    //*****************/
    public function WSAddOrder(
								$tmsp_start,
								$id_order,
								$customer,
								$date_facture,
								$product_tab,
								$total_shipping_tax_incl,
								$total_shipping_tax_excl,
								$carrier_tax_rate,
								$statut = 0,
								$module_reglement = 0,
								$mode_reglement = 0,
								$reductions = 0,
								$name_transporteur = 0,
								$id_address_delivery = 0,
								$id_address_invoice = 0,
								$id_ext_order_doli = 0
							) {
        $this->logInFile('--WSAddOrder--');
        $ref_order = $this->ws_trigram_value.$this->format($id_order, 10);
        //$refCustomer = $this->ws_trigram_value.$this->format($customer['id_customer'], 10);

        $wsurl = $this->ws_adress_value;
        $ws_dol_url_order = $wsurl.$this->ws_adress_dolibarr.'/pj_ws_commandes.php';
        $ws_method_get_order  = 'getOrder';
        $ws_method_create_order = 'createOrder';
        $ws_method_update_order_statut = 'updateOrderStatus';
        $ns = 'http://www.Dolibarr.org/ns/';
        $authentication = array('dolibarrkey'=>$this->ws_key_value, 'sourceapplication'=>'PRESTASHOP',
        'login'=>$this->ws_login_value,
        'password'=>$this->ws_passwd_value, 'entity'=>'');

        $retour_ko = array();
        $retour_ko['result']['result_code'] = 'KO';
        $retour_dbl = array();
        $retour_dbl['result']['result_code'] = 'DBL';

        // we make a get order to see if we don't put a duplicate
        if ($id_ext_order_doli!=0){
			$soapclient1 = new nusoap_client($ws_dol_url_order);
			if ($soapclient1) {
				$soapclient1->soap_defencoding = 'UTF-8';
				$soapclient1->decodeUTF8(false);
			}
			$parameters1 = array('authentication'=>$authentication, 'id'=>$id_ext_order_doli, 'ref'=>'', 'ref_ext'=>'');
			$result_get_order_tab = $soapclient1->call($ws_method_get_order, $parameters1, $ns, '');
			$result_get_order = $result_get_order_tab['result']['result_code'];

			$this->logInFile('echange avec le '.$ws_method_get_order.' ('.$ws_dol_url_order.') ');
			$this->logInFile('parameters: '.print_r($parameters1,true));
			$this->logInFile('request:'.$soapclient1->request);
			$this->logInFile('WS response after getOrder:'.print_r($result_get_order_tab, true));

			if ($result_get_order == 'OK') {
				// validation of the order if not already validated
				$parameters_status = array('authentication'=>$authentication, 'id'=>$id_ext_order_doli, 'status'=>$statut);
				$result_update_order_statut_tab = $soapclient1->call($ws_method_update_order_statut,$parameters_status,$ns,'');

				$this->logInFile('exchange with the '.$ws_method_update_order_statut.' method ('.$ws_dol_url_order.') ');
				$this->logInFile('request:'.$soapclient1->request);
				$this->logInFile('WS response after updateOrder:'.print_r($result_update_order_statut_tab, true));

				if ($result_update_order_statut_tab['result']['result_code'] == 'KO') {
					return $retour_ko;
				}
				return $retour_dbl;
			} elseif ($result_get_order == 'KO') {
				return $retour_ko;
			}
		}

        $result_create_order_tab = array();
        $result_update_order_statut_tab = array();

		// synced customer
		if (isset($this->already_synced_customers[$customer['id_customer']])){
			$synced_customer = $this->already_synced_customers[$customer['id_customer']];
		}else{
			$synced_customer = $this->importClients(array('0' =>$customer), $tmsp_start);
			$this->logInFile('\result of export customer of the order: '.print_r($synced_customer,true));
			if ($synced_customer['result'] != 'OK') {
				return $retour_ko;
			}
		}
		$this->logInFile('\customer of the order: id_ext_dloi='.$synced_customer['id_ext_doli']);

		// if we got the customer id we grab the product id
		$lines = array();
		unset($lines);
		$line = array();
		//for ($i = 0; $product_tab[$i]['product_id'] != ''; $i++)
		for ($i = 0; array_key_exists($i, $product_tab) && $product_tab[$i]['product_id'] != ''; $i++) {
			$ref_product = $this->ws_trigram_value.$this->format($product_tab[$i]['product_id'], 10).
			$this->format($product_tab[$i]['product_attribute_id'], 10);

			// we export the product
			$this->logInFile('Product in invoice, ref : '.$ref_product.' -> '.print_r($product_tab[$i], true));
			$testproduct = new product($product_tab[$i]['product_id'], true, $this->default_lang, null, null);
			$description_short = $this->noSpecialCharacterV3($testproduct->description_short);
			$product_to_set = array(
				'id_product'=>$product_tab[$i]['product_id'],
				'description_short'=>$description_short,
				'active'=>$testproduct->active,
				'date_upd'=>$testproduct->date_upd,
				'date_add'=>$testproduct->date_add,
				'reference'=>$testproduct->reference,
				'ean13'=>$testproduct->ean13,
				'upc'=>$testproduct->upc,
				'id_product_attribute'=>$product_tab[$i]['product_attribute_id']
			);
			$result_product = $this->importProduits($product_to_set, $tmsp_start);
			if ($result_product['result'] != 'OK') {
				return $retour_ko;
			}
			$id_product = $result_product['id_ext_doli'];
			$this->logInFile('Product of the order : '.$id_product);

			$line = array();
			$line['type'] = '0';       // Type of line (0=product, 1=service)
			$line['desc'] = 'Product';    // Description of the line
			//$line['fk_product'] = '';  // link to product, duplicate with product_id which overwrites it
			$line['unitprice'] = round($product_tab[$i]['unit_price_tax_excl'], 2); // price excluding tax for a single product
			$line['total_net'] = $product_tab[$i]['total_price_tax_excl'];  // price all products, exlcuded tax
			$line['total_vat'] = ($product_tab[$i]['total_price_tax_incl'] - $product_tab[$i]['total_price_tax_excl']); // total tax all products
			$line['total'] = $product_tab[$i]['total_price_tax_incl']; // total price including tax of all products
			//$line['vat_rate'] = $resultGetProductTab['product']['vat_rate'];
			$line['vat_rate'] = $product_tab[$i]['tax_rate'];
			$line['qty'] = $product_tab[$i]['product_quantity'];   // number of products of the same type
			$line['product_id'] = $id_product;   // link to product, dolibarr id
			$lines[$i] = $line;
		}

		// Addition of the possible cost of transport
		if ($total_shipping_tax_incl != 0) {
			$line['type'] = '1';       // Type of line (0=product, 1=service)
			$line['desc'] = 'delivery : '.$name_transporteur;    // Description of the line
			$line['unitprice'] = $total_shipping_tax_excl; // price excluding VAT for a single product (rounded to 2 digits after the decimal point)
			$line['total_net'] = $total_shipping_tax_excl;  // price excluding VAT for all products
			$line['total_vat'] = $total_shipping_tax_incl - $total_shipping_tax_excl; // total ox VAT of all products
			$line['total'] = $total_shipping_tax_incl; // total of all products price including VAT
			$line['vat_rate'] = $carrier_tax_rate;
			$line['qty'] = '1';   // number of products of the same type
			$line['product_id'] = '';   // link to product, dolibarr id
			$lines[$i] = $line;
		}

		// Addition of reductions
		if ($reductions != 0) {
			$i++;
			$reductions_ht = $reductions / (1 + $carrier_tax_rate / 100);
			$reductions_ht = round($reductions_ht * -1, 2);
			$reductions = $reductions * -1;
			$line['type'] = '1';       // Type of line (0=product, 1=service)
			$line['desc'] = 'Discount';    // Description of the line
			$line['unitprice'] = $reductions_ht; // price excluding VAT for a single product (rounded to 2 digits after the decimal point)
			$line['total_net'] = $reductions_ht;  // price excluding VAT for all products
			$line['total_vat'] = $reductions - $reductions_ht; // total ox VAT of all products
			$line['total'] = $reductions; // total of all products price including VAT
			$line['vat_rate'] = $carrier_tax_rate;
			$line['qty'] = '1';   // number of products of the same type
			$line['product_id'] = '';   // link to product, dolibarr id
			$lines[$i] = $line;
		}
		$this->logInFile('lines : '.print_r($lines, true));

		// we send the invoice
		$module_reglement = $this->noSpecialCharacterV3($module_reglement);
		$mode_reglement = $this->noSpecialCharacterV3($mode_reglement);
		$module_reglement_code = Tools::substr($module_reglement, 0, 6);

		$order = array(
					'ref_ext'=>$ref_order,
					'thirdparty_id'=>$synced_customer['id_ext_doli'],  // dolibarr id
					'date'=>$date_facture,
					'type'=>'0', //0=Standard invoice, 1=Replacement invoice, 2=Credit note invoice, 3=Deposit invoice, 4=Proforma invoice
					'note_private'=>'Imported by Prestashop '.date('d/m/Y H:i:s'),
					'note_public'=> '',
					'status'=>$statut,
					'project_id'=> '',
					'remise' => '',
					'remise_percent' => '',
					'remise_absolue' => '',
					'source' => '',
					'mode_reglement_id' => '',
					'mode_reglement_code' => $module_reglement_code,
					'mode_reglement' => $mode_reglement,
					'cond_reglement_id' => '',
					'cond_reglement_code' => '',
					'cond_reglement' => '',
					'cond_reglement_doc' => '',
					'date_livraison' => '',
					'fk_delivery_address' => '',
					'demand_reason_id' => '',
					'id_address_delivery' => $id_address_delivery,
					'id_address_invoice' => $id_address_invoice,
					'lines'=>$lines
				);

		$soapclient4 = new nusoap_client($ws_dol_url_order);
		if ($soapclient4) {
			$soapclient4->soap_defencoding = 'UTF-8';
			$soapclient4->decodeUTF8(false);
		}
		$parameters4 = array('authentication'=>$authentication, 'order'=>$order);
		$result_create_order_tab = $soapclient4->call($ws_method_create_order, $parameters4, $ns, '');

		$this->logInFile('exchange with the '.$ws_method_create_order.' method :'.$ws_dol_url_order);
		$this->logInFile('parameters4: '.print_r($parameters4,true));
		$this->logInFile('request:'.$soapclient4->request);
		$this->logInFile('WS response after createOrder:'.print_r($result_create_order_tab, true));

        return $result_create_order_tab;
    }

    private function noSpecialCharacterV3($str_entree)
    {
        $this->logInFile('--noSpecialCharacterV3--');
        //  special characters (in fact only letters and numbers)
        $str_entree = str_replace('<b>', '', $str_entree);
        $str_entree = str_replace('</b>', '', $str_entree);
        $str_entree = str_replace('<i>', '', $str_entree);
        $str_entree = str_replace('</i>', '', $str_entree);
        $str_entree = str_replace('<u>', '', $str_entree);
        $str_entree = str_replace('</u>', '', $str_entree);
        $str_entree = str_replace('<li>', '', $str_entree);
        $str_entree = str_replace('</li>', '', $str_entree);
        $str_entree = str_replace('<ul>', '', $str_entree);
        $str_entree = str_replace('</ul>', '', $str_entree);
        $str_entree = str_replace('<p>', '', $str_entree);
        $str_entree = str_replace('</p>', '', $str_entree);
        $str_entree = str_replace('<br />', '. ', $str_entree);

        $encoding = 'utf-8';
        $str = htmlentities($str_entree, ENT_NOQUOTES, $encoding);

        if (Tools::strlen($str) == 0) {
            $encoding = 'ISO-8859-1';
            $str = htmlentities($str_entree, ENT_NOQUOTES, $encoding);
        }
        $str = preg_replace('#&([A-za-z])(?:acute|grave|cedil|circ|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
        $str = preg_replace('#&[^;]+;#', '', $str);
        return $str;
    }

    private function format($id, $nb)
    {
        $this->logInFile('--format--');
        $chaine = '';
        for ($i = Tools::strlen($id); $i < $nb; $i++) {
            $chaine = $chaine.'0';
        }
        $chaine = $chaine.$id;

        return $chaine;
    }

    /**
    *
    *  HOOK methods
    *
    */

    /**
    * categories hook
    */
    public function hookCategoryAddition()
    {
        $this->logInFile('--hookCategoryAddition--');
        if ($this->is_checked_synch_category == 'true') {
            $this->importCategories();
        }
    }

    public function hookCategoryUpdate()
    {
        $this->logInFile('hookCategoryUpdate');
        if ($this->is_checked_synch_category == 'true') {
            $this->importCategories();
        }
    }

    public function hookCategoryDeletion()
    {
        $this->logInFile('--hookCategoryDeletion--');
        if ($this->is_checked_synch_category == 'true') {
            $this->importCategories();
        }
    }

    /**
    * customers hook
    */
    public function hookCreateAccount($params)
    {
        $this->logInFile('--hookCreateAccount--');
        $tmsp_start = time();
        if ($this->is_checked_synch_customer == 'true') {
            // customer's array to create
            $customers = array('0' => array(
										'id_customer'=>$params['newCustomer']->id,
										'email'=>$params['newCustomer']->email,
										'firstname'=>$params['newCustomer']->firstname,
										'lastname'=>$params['newCustomer']->lastname
									));

            // import customer
            $result = $this->importClients($customers, $tmsp_start);

            if ($result['nbrMaxClient'] == true) {
                Logger::addLog(
                    'prestashopdolibarr module customer phase shift',
                    1,
                    null,
                    'customer',
                    $params['newCustomer']->id,
                    false
                );
            }
        }
    }

    /**
    * orders hook
    */

    public function hookUpdateOrderStatus($params)
    {
        if ($this->is_checked_synch_status == 'true') {
            $this->logInFile('--hookUpdateOrderStatus--');
            $tmsp_start = time();
            $order_id = $params['id_order'];
            $order = new Order($order_id);
            $this->logInFile('test id status'.$params['newOrderStatus']->id);
            $order->current_state = $params['newOrderStatus']->id;
            $this->logInFile('hookUpdateOrderStatus : '.print_r($params, true));
            if (($this->is_checked_synch_invoice == 'true') || ($this->is_checked_synch_order == 'true')) {
                $result = $this->importFacturesOrCommandes(
									$order,
									$tmsp_start,
									$this->is_checked_synch_order,
									$this->is_checked_synch_invoice
								);
                $this->logInFile('hookupdateorderstatus response: '.print_r($result, true));
                if ($result['nbrMaxOrder'] == true) {
                    Logger::addLog('prestashopdolibarr module order phase shift', 1, null, 'order', $order_id, false);
                }
            }
        }

    }

    /**
    public function hookNewOrder($params)
    {
        $this->logInFile("hookNewOrder : ".print_r($params['objOrder'], true));
        if ($this->is_checked_synch_order == 'true')
            $this->_importCommandes($params['objOrder']);
    }*/

    /**
    * order confirmation for invoicing hook
    */
    public function hookActionValidateOrder($params)
    {
        $this->logInFile('--hookActionValidateOrder--');
        $tmsp_start = time();
        $order_id = $params['order']->id;
        $this->logInFile('hookActionValidateOrder : '.print_r($params['order'], true));
        if (($this->is_checked_synch_invoice == 'true') || ($this->is_checked_synch_order == 'true')) {
            $result = $this->importFacturesOrCommandes(
														$params['order'],
														$tmsp_start,
														$this->is_checked_synch_order,
														$this->is_checked_synch_invoice
													);
            if ($result['nbrMaxOrder'] == true) {
                Logger::addLog('prestashopdolibarr module order phase shift', 1, null, 'order', $order_id, false);
            }
        }
    }

    /**
    * products hook
    */
    public function hookUpdateProduct($params)
    {
        $this->logInFile('--hookUpdateProduct--');
        $tmsp_start = time();
        $product_id = (int)$params['product']->id;
        if ($product_id == 0) {
            $product_id = $params['id_product'];
        }
        $this->logInFile('hookUpdateProduct: '.$product_id);

        if ($this->is_checked_synch_product == 'true' || $this->is_checked_synch_stock == 'true') {
            $testproduct = new product($product_id, true, $this->default_lang, null, null);
            $product = array(
							'id_product'=>$product_id,
							'description_short'=>$testproduct->description_short,
							'active'=>$testproduct->active,
							'date_upd'=>$testproduct->date_upd,
							'date_add'=>$testproduct->date_add,
							'reference'=>$testproduct->reference,
							'ean13'=>$testproduct->ean13,
							'upc'=>$testproduct->upc
							);

            $result = $this->importProduits($product, $tmsp_start);
            if ($result['nbrMaxProduct'] == true) {
                Logger::addLog('prestashopdolibarr module product phase shift', 1, null, 'product', $product_id, false);
            }
        }

    }

    public function hookAddproduct($params)
    {
        $this->logInFile('--hookAddproduct--');
        $tmsp_start = time();
        $product_id = (int)$params['product']->id;
        if ($product_id == 0) {
            $product_id = $params['id_product'];
        }
        $this->logInFile('hookAddproduct: ');

        if ($this->is_checked_synch_product == 'true') {
            $testproduct = new product($product_id, true, $this->default_lang, null, null);
            $product = array(
							'id_product'=>$product_id,
							'description_short'=>$testproduct->description_short,
							'active'=>$testproduct->active,
							'date_upd'=>$testproduct->date_upd,
							'date_add'=>$testproduct->date_add,
							'reference'=>$testproduct->reference,
							'ean13'=>$testproduct->ean13,
							'upc'=>$testproduct->upc
							);

            $result = $this->importProduits($product, $tmsp_start);
            if ($result['nbrMaxProduct'] == true) {
                Logger::addLog('prestashopdolibarr module product phase shift', 1, null, 'product', $product_id, false);
            }
        }
    }

    /** Unmanaged functions for versions of prestashop < 1.5 */

    public function getProductAttributesIds($id_product, $shop_only = false)
    {
        $this->logInFile('--getProductAttributesIds--');
        return Db::getInstance()->executeS(
            'SELECT pa.id_product_attribute
            FROM `'._DB_PREFIX_.'product_attribute` pa'.
            ($shop_only ? Shop::addSqlAssociation('product_attribute', 'pa') : '').'
            WHERE pa.`id_product` = '.(int)$id_product
        );
    }

    public function getProductName($id_product, $id_product_attribute = null, $id_lang = null)
    {
        $this->logInFile('--getProductName--');
        // use the lang in the context if $id_lang is not defined
        if (!$id_lang) {
            $id_lang = (int)Context::getContext()->language->id;
        }

        // selects different names, if it is a combination
        if ($id_product_attribute) {
            $query = "select IFNULL(CONCAT(pl.name, ' : ', GROUP_CONCAT(DISTINCT agl.`name`, ' - ',
            al.name SEPARATOR ', '))";
            $query .= ',pl.name) as name FROM `'._DB_PREFIX_.'product_attribute` pa
            INNER JOIN `'._DB_PREFIX_.'product_lang` pl on pl.id_product = pa.id_product AND pl.id_lang = '.
            (int)$id_lang.'
            LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` `pac` on
            pac.id_product_attribute = pa.id_product_attribute
            LEFT JOIN `'._DB_PREFIX_.'attribute` `atr` on atr.id_attribute = pac.id_attribute
            LEFT JOIN `'._DB_PREFIX_.'attribute_lang` `al` on al.id_attribute = atr.id_attribute AND al.id_lang = '.
            (int)$id_lang.'
            LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` `agl` on agl.id_attribute_group = atr.id_attribute_group
             AND agl.id_lang = '.(int)$id_lang.'
            where pa.id_product = '.(int)$id_product.' AND pa.id_product_attribute = '.(int)$id_product_attribute;
        } else {
            // || just adds a 'where' clause for a simple product
            $query = 'select DISTINCT pl.name as name ';
            $query = $query.'FROM '._DB_PREFIX_.'product_lang pl';
            $query = $query.' where pl.id_product = '.(int)$id_product;
            $query = $query.' and pl.id_lang = '.(int)$id_lang;//.Shop::addSqlRestrictionOnLang('pl');
        }
        $result = Db::getInstance()->executeS($query);

        if ((!$result) || ($result[0]['name'] == '')) {
            // no description in the default language => we take the first one that comes
            if ($id_product_attribute) {
                $query = "select IFNULL(CONCAT(pl.name, ' : ', GROUP_CONCAT(DISTINCT agl.`name`, ' - ',
                al.name SEPARATOR ', ')),pl.name) as name";
                $query .= ' FROM `'._DB_PREFIX_.'product_attribute` pa
                INNER JOIN `'._DB_PREFIX_.'product_lang` pl on pl.id_product = pa.id_product
                LEFT JOIN `'._DB_PREFIX_.'product_attribute_combination` `pac`
                on pac.id_product_attribute = pa.id_product_attribute
                LEFT JOIN `'._DB_PREFIX_.'attribute` `atr` on atr.id_attribute = pac.id_attribute
                LEFT JOIN `'._DB_PREFIX_.'attribute_lang` `al` on al.id_attribute = atr.id_attribute
                LEFT JOIN `'._DB_PREFIX_.'attribute_group_lang` `agl`
                on agl.id_attribute_group = atr.id_attribute_group
                where pa.id_product = '.(int)$id_product.' AND pa.id_product_attribute = '.(int)$id_product_attribute;
            } else {
                // || just adds a 'where' clause for a simple product
                $query = 'select DISTINCT pl.name as name ';
                $query = $query.'FROM '._DB_PREFIX_.'product_lang pl';
                $query = $query.' where pl.id_product = '.(int)$id_product;
            }
            $result = Db::getInstance()->executeS($query);
        }
        return $result[0]['name'];
    }

    public function getTaxesInformations($id_product)
    {
        $this->logInFile('--getTaxesInformations--');
        if (_PS_VERSION_ < '1.4.0.5') {
            $query = 'SELECT rate FROM '._DB_PREFIX_.'tax t, '._DB_PREFIX_.'product p
            where t.id_tax=p.id_tax AND id_product = '.(int)$id_product;
        } else {
            $id_country = Country::getDefaultCountryId();
            $query = '
            SELECT     tax.rate as rate
            FROM
                    '._DB_PREFIX_.'product product, '._DB_PREFIX_.'tax tax, '._DB_PREFIX_.'tax_rule taxrule
            WHERE
                product.id_product = '.(int)$id_product.'
                AND product.id_tax_rules_group = taxrule.id_tax_rules_group
                AND taxrule.id_country = '.(int)$id_country.'
                AND taxrule.id_state = 0
                AND taxrule.id_tax = tax.id_tax
            ';
        }

        $result = Db::getInstance()->executeS($query);
        $rate = $result[0];

        return $rate;
    }

    public function getCarrierTaxes($id_order)
    {
        $this->logInFile('--getCarrierTaxes--');
        if (_PS_VERSION_ < '1.4.0.5') {
            $query = 'SELECT rate FROM '._DB_PREFIX_.'tax t, '._DB_PREFIX_.'carrier c, '._DB_PREFIX_.'orders o
            where o.id_order = '.(int)$id_order.'
            AND o.id_carrier = c.id_carrier
            AND c.id_tax = t.id_tax';
        } else {
            $id_country = Country::getDefaultCountryId();
            $this->logInFile('tax id country carrier : '.$id_country);
            $query = '
            SELECT
                    tax.rate as rate
            FROM
            '._DB_PREFIX_.'tax tax, '._DB_PREFIX_.'carrier carrier, '._DB_PREFIX_.'orders orders, '._DB_PREFIX_.
            'tax_rule taxrule
            WHERE orders.id_order = '.(int)$id_order.'
                AND orders.id_carrier = carrier.id_carrier
                AND carrier.id_tax_rules_group = taxrule.id_tax_rules_group
                AND taxrule.id_country = '.(int)$id_country.'
                AND taxrule.id_state = 0
                AND taxrule.id_tax = tax.id_tax
            ';
        }

        $this->logInFile('get carrier taxes query : '.$query);
        $result = Db::getInstance()->executeS($query);
        $this->logInFile('get carrier taxes : '.print_r($result, true));

        $rate = $result[0]['rate'];

        return $rate;
    }

    public function getProductRef($id_product, $id_product_attribute = null)
    {
        $this->logInFile('--getProductRef--');
        if ($id_product_attribute) {
            $query = '
            SELECT     date_export_doli, id_ext_doli, reference, ean13, upc, ref_doli
            FROM
                    '._DB_PREFIX_.'product_attribute as pa
            WHERE
                pa.id_product_attribute = '.(int)$id_product_attribute;
        } else {
            $query = '
            SELECT     date_export_doli, id_ext_doli, ref_doli
            FROM
                    '._DB_PREFIX_.'product as p
            WHERE
                p.id_product = '.(int)$id_product;
        }
        $result = Db::getInstance()->executeS($query);
        return $result[0];
    }

    public function setProductRef($id_product, $id_product_attribute = null, $id_ext_doli = 0, $ref_doli = '')
    {
        $this->logInFile('--setProductRef--');
        if (($id_product_attribute) && ($id_product_attribute != 0)) {
            $query = '
            UPDATE        '._DB_PREFIX_.'product_attribute
            SET 
                date_export_doli = CURRENT_TIMESTAMP,
                id_ext_doli = '.(int)$id_ext_doli.',
                ref_doli = \''.$ref_doli.'\' 
            WHERE 
                id_product_attribute = '.(int)$id_product_attribute;
        } else {
            $query = '
            UPDATE 
                    '._DB_PREFIX_.'product
            SET 
                date_export_doli = CURRENT_TIMESTAMP,
                id_ext_doli = '.(int)$id_ext_doli.',
                ref_doli = \''.$ref_doli.'\'  
            WHERE 
                id_product = '.(int)$id_product;
        }

        $result = Db::getInstance()->execute($query);

        return $result[0];
    }

	/* this is like the native method $customer_obj->getCustomers() but selecting also the dolibarr fields ;-) */
	private function getCustomers($onlyActive = null){
        $result = Db::getInstance()->executeS(
            'SELECT `id_customer`, `email`, `firstname`, `lastname`, `date_export_doli`, `id_ext_doli`
            FROM `' . _DB_PREFIX_ . 'customer`
            WHERE 1 ' . Shop::addSqlRestriction(Shop::SHARE_CUSTOMER)
            .($onlyActive ? ' AND `active` = 1' : '')
        );
        $customers = array();
        if ($result && is_array($result)){
			foreach($result as $arr){
				$customers[$arr['id_customer']] = $arr;
			}
		}
        return $customers;
	}

    public function getCustomerRef($id_customer)
    {
        $query = '
            SELECT     date_add, date_upd ,date_export_doli, id_ext_doli
            FROM
                    '._DB_PREFIX_.'customer as c
            WHERE
                c.id_customer = '.(int)$id_customer;

        $result = Db::getInstance()->executeS($query);

        return $result[0];
    }

	private function getCustomerLastOrder($customer_id){
        $query = '
            SELECT     *
            FROM
                    '._DB_PREFIX_.'orders
            WHERE
                id_customer = '.(int)$customer_id.
			' ORDER BY `id_order` DESC LIMIT 0,1';

        $result = Db::getInstance()->executeS($query);

        return $result && isset($result[0]) ? $result[0] : null;
	}

    public function getOrderRef($id_order)
    {
        $query = '
            SELECT     date_export_order_doli, id_ext_order_doli, date_export_invoice_doli, id_ext_invoice_doli
            FROM
                    '._DB_PREFIX_.'orders
            WHERE
                id_order = '.(int)$id_order;

        $result = Db::getInstance()->executeS($query);

        return $result[0];
    }

    public function setCustomerRef($id_customer, $id_ext_doli)
    {
        $query = '
        UPDATE        '._DB_PREFIX_.'customer
        SET
            date_export_doli = CURRENT_TIMESTAMP,
            id_ext_doli = '.(int)$id_ext_doli.'
        WHERE
            id_customer = '.(int)$id_customer;

        $result = Db::getInstance()->execute($query);

        return $result[0];
    }

    public function setOrderRef($id_order, $id_ext_doli)
    {
        $query = '
        UPDATE        '._DB_PREFIX_.'orders
        SET
            date_export_order_doli = CURRENT_TIMESTAMP,
            id_ext_order_doli = '.(int)$id_ext_doli.'
        WHERE
            id_order = '.(int)$id_order;

        $result = Db::getInstance()->execute($query);
        $this->logInFile('- SQL query to set order doli_id : '.$query);
        $this->logInFile('- result of SQL query : '.print_r($result, true));

        return $result[0];
    }

    public function setInvoiceRef($id_order, $id_ext_doli)
    {
        $query = '
        UPDATE        '._DB_PREFIX_.'orders
        SET
            date_export_invoice_doli = CURRENT_TIMESTAMP,
            id_ext_invoice_doli = '.(int)$id_ext_doli.'
        WHERE
            id_order = '.(int)$id_order;

        $result = Db::getInstance()->execute($query);
        $this->logInFile('- SQL query to set invoice doli_id : '.$query);
        $this->logInFile('- result of SQL query : '.print_r($result, true));

        return $result[0];
    }

    public function resetCustomers()
    {
        $query = '
        UPDATE        '._DB_PREFIX_.'customer
        SET
            date_export_doli = NULL,
            id_ext_doli = NULL
        ';

        $result = Db::getInstance()->execute($query);
        return $result;
    }

    public function resetProducts()
    {
        $query = '
        UPDATE        '._DB_PREFIX_.'product
        SET
            date_export_doli = NULL,
            id_ext_doli = NULL
        ';

        $result = Db::getInstance()->execute($query);
        $this->logInFile('- SQL query to reset products : '.$query);
        $this->logInFile('- result of SQL query : '.print_r($result, true));

        if ($result) {
            $query = '
            UPDATE        '._DB_PREFIX_.'product_attribute
            SET
                date_export_doli = NULL,
                id_ext_doli = NULL
            ';

            $result = Db::getInstance()->execute($query);
        }
        return $result;
    }

    public function resetOrders()
    {
        $query = '
        UPDATE        '._DB_PREFIX_.'orders
        SET
            date_export_order_doli = NULL,
            id_ext_order_doli = NULL
        ';

        $result = Db::getInstance()->execute($query);
        return $result;
    }

    public function resetInvoices()
    {
        $query = '
        UPDATE        '._DB_PREFIX_.'orders
        SET
            date_export_invoice_doli = NULL,
            id_ext_invoice_doli = NULL
        ';

        $result = Db::getInstance()->execute($query);
        return $result;
    }

    public function getIdImage($id_product)
    {
        $query = '
        SELECT id_image FROM '._DB_PREFIX_.'image
        WHERE
            id_product = '.(int)$id_product;

        $result = Db::getInstance()->executeS($query);
        return isset($result[0]['id_image'])?$result[0]['id_image']:0;
    }

    /**fonctions utiles*/
    private function startsWith($haystack, $needle)
    {
        return $needle === '' || strrpos($haystack, $needle, -Tools::strlen($haystack)) !== false;
    }

    private function endsWith($haystack, $needle)
    {
        return $needle === '' || (($temp = Tools::strlen($haystack) - Tools::strlen($needle)) >= 0 &&
        strpos($haystack, $needle, $temp) !== false);
    }

    /**nettoyage array categories*/
    private function cleanCategory($cat_tab, $result = array())
    {

        //if ($cat_tab['infos'])
        if (array_key_exists('infos', $cat_tab) == true) {
            $id_cat = $cat_tab['infos']['id_category'];
            $result[$id_cat]['id_category'] = $this->ws_trigram_value.$this->format(
                $cat_tab['infos']['id_category'],
                10
            );
            $result[$id_cat]['id_parent'] = $this->ws_trigram_value.$this->format($cat_tab['infos']['id_parent'], 10);
            $result[$id_cat]['name'] = $cat_tab['infos']['name'];
            $result[$id_cat]['description'] = $cat_tab['infos']['description'];
        } else {
            foreach ($cat_tab as $cat) {
                $result = $this->cleanCategory($cat, $result);
            }
        }
        return $result;
    }

    /**function qui retourne les id  des references externes dolibarr déphasées avec la références internes*/
    private function getRefdoliEmpty($table)
    {
        if ($table == 'product') {
            $query = '
            SELECT id_product, reference FROM '._DB_PREFIX_.'product
                WHERE reference <> reference_old_doli
                ORDER BY id_product';
        } elseif ($table == 'product_attribute') {
            $query = '
            SELECT pa.id_product, pa.id_product_attribute, pa.reference, p.ref_doli as product_reference
            FROM '._DB_PREFIX_.'product as p, '._DB_PREFIX_.'product_attribute as pa
                WHERE p.id_product = pa.id_product
                  AND pa.reference <> pa.reference_old_doli
                ORDER BY pa.id_product, pa.id_product_attribute';
        }
        $this->logInFile('->sql : '.$query);
        $result = Db::getInstance()->executeS($query);
        $this->logInFile('->result : '.print_r($result, true));
        return $result;
    }

    /** function which returns the number of lower ids with an identical ref */
    private function isRefUnique($id, $reference, $table)
    {
        if ($table == 'product') {
            $query = '
            SELECT COUNT(*) as cpt FROM '._DB_PREFIX_."product
                WHERE reference = '".pSQL($reference)."'
                 AND id_product < ".(int)$id;
        } elseif ($table == 'product_attribute') {
            $query = '
            SELECT COUNT(*) as cpt FROM (
            SELECT p.id_product as id FROM '._DB_PREFIX_."product p
                WHERE p.reference = '".pSQL($reference)."'
            UNION
            SELECT pa.id_product_attribute as id FROM "._DB_PREFIX_."product_attribute pa
                WHERE pa.reference = '".pSQL($reference)."'
                 AND pa.id_product_attribute < ".(int)$id.'
            ) abc';
        }
        $this->logInFile('->sql : '.$query);
        $result = Db::getInstance()->executeS($query);
        $this->logInFile('->result : '.print_r($result, true));
        return $result[0]['cpt'];
    }

    private function insertRefDoli($id, $refdoli, $table = 'product')
    {
        $query = '
        UPDATE '._DB_PREFIX_.pSQL($table)."
            SET ref_doli = '".pSQL($refdoli)."',
                reference_old_doli = reference
            WHERE id_".pSQL($table).' = '.(int)$id;
        $this->logInFile('->sql : '.$query);
        $result = Db::getInstance()->execute($query);
        $this->logInFile('->result : '.print_r($result, true));
        return $result;
    }

    private function updateRefEmpty($id, $refdoli, $table = 'product')
    {
     	$query = '
     		UPDATE '._DB_PREFIX_.pSQL($table)."  
       		SET reference = '".pSQL($refdoli)."' 
         	WHERE id_".pSQL($table).' = '.(int)$id;
       	$this->logInFile('->sql : '.$query);
       	$result = Db::getInstance()->execute($query);
       	$this->logInFile('->result : '.print_r($result, true));
      	return $result;
    }

    private function getStatutDolibarr($state_id)
    {
        if (count($this->order_states)==0) $this->loadOrderStates();

        $state_id = (int)$state_id;

        if (!isset($this->order_states[$state_id])) return 0;
        if (!isset($this->order_states[$state_id]['id_order_state_doli'])) return 0;

        return  (int)$this->order_states[$state_id]['id_order_state_doli'];

    }

    /*
     * build an array of the prestashop order_states, indexed by the id_order_state, and with the names in the default language
     * some of the order_states are defined by core and other defined by installed modules)
     */
	private function loadOrderStates(){

        $order_states = Db::getInstance()->executeS('
							SELECT *
							FROM `'._DB_PREFIX_.'order_state` os
							LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state`
							AND osl.`id_lang` = '.$this->default_lang.')
							WHERE deleted = 0
							ORDER BY `name` ASC');

		$this->order_states = array();
		foreach ($order_states as $arr) $this->order_states[$arr['id_order_state']] = $arr;

        return;
	}

	/*
	 * get number of synced objects vs not synced objects, looking on database for dolibarr_id
	 */
	private function ajax_getHowMany(){
		// check the requested table
			if (empty($_GET['table']) || !in_array($_GET['table'],array('products','customers','orders','invoices'))){
				return array('msg'=>'Error. Not valid table: '.$_GET['table']);
			}
			$table = $_GET['table'];

		// get info from database
			$f = array(
						'products'  => array('product','id_product','id_ext_doli',''),
						'customers' => array('customer','id_customer','id_ext_doli','active=1'),
						'orders' => array('orders','id_order','id_ext_order_doli',''),
						'invoices' => array('orders','id_order','id_ext_invoice_doli',''),
					);

			$all_objects = Db::getInstance()->executeS('
								SELECT count('.$f[$table][1].') as n
								FROM `'._DB_PREFIX_.$f[$table][0].'` '
								.(!empty($f[$table][3]) ? 'WHERE '.$f[$table][3] : '')
								.';');

			$exported_objects = Db::getInstance()->executeS('
								SELECT count('.$f[$table][1].') as n
								FROM `'._DB_PREFIX_.$f[$table][0].'`
								WHERE '.(!empty($f[$table][3]) ? $f[$table][3].' AND ':'')
								.' '.$f[$table][2].' IS NOT NULL; ');

			$msg = mb_strtoupper($table).":"
					."\n\nNumber of TOTAL objects: ".$all_objects[0]['n']
					."\n\nNumber of EXPORTED objects: ".$exported_objects[0]['n']
					.( $exported_objects[0]['n'] != $all_objects[0]['n'] ? " !!!":"")
					."\n\n";

		return array('msg'=>$msg);
	}

}
