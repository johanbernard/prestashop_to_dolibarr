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

class PrestashopToDolibarrPro extends Module
{
    public $debug_mode = true;
    /**temps maximum en seconde pour exporter*/
    public $nbr_max_sec_export = 24;
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

    public function __construct()
    {
        $this->name = 'prestashoptodolibarrpro';
        $this->tab = 'migration_tools';
        $this->version = '1.7.3';
        $this->author = 'PJ Conseil';
        $this->module_key = 'a9616fc7465750635d2cc4293269cb83';
        $this->need_instance = 0;

        parent::__construct();

        $this->displayName = $this->l('Prestashop to Dolibarr PRO');
        $this->description = $this->l('Import and link in real time Prestashop to Dolibarr');
        $this->initConfig();
    }

    public function initConfig()
    {
        require_once(_PS_MODULE_DIR_.'prestashoptodolibarrpro/nusoap/lib/p2dWebservices.php');
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
                'DOLIBARR_VERSION'
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
            $this->logInFile("-requete d'installation : ".$query);
            if ($query) {
                if (!Db::getInstance()->execute(trim($query))) {
                    return false;
                }
            }
        }

        if (!parent::install() || !$this->registerHook('createAccount') || !$this->registerHook('orderConfirmation')) {
            return false;
        }
        if (!$this->registerHook('updateproduct') || !$this->registerHook('addproduct') ||
            !$this->registerHook('updateOrderStatus')) {
            return false;
        }
        if (!$this->registerHook('categoryAddition') || !$this->registerHook('categoryUpdate') ||
            !$this->registerHook('categoryDeletion')) {
            return false;
        }
        $this->logInFile('install OK');
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

        if ((!$this->deleteTables()) || !parent::uninstall()) {
            return false;
        }

        return true;
        //return parent::uninstall();
    }

    private function deleteTables()
    {
        if (!file_exists(dirname(__FILE__).'/'.self::UNINSTALL_SQL_FILE)) {
            return (false);
        } elseif (!$sql = Tools::file_get_contents(dirname(__FILE__).'/'.self::UNINSTALL_SQL_FILE)) {
            return (false);
        }
        $sql = str_replace(array('PREFIX_', 'ENGINE_TYPE'), array(_DB_PREFIX_, _MYSQL_ENGINE_), $sql);
        $sql = preg_split("/;\s*[\r\n]+/", $sql);
        foreach ($sql as $query) {
            $this->logInFile('-requete de desinstallation : '.$query);
            if ($query) {
                if (!Db::getInstance()->execute(trim($query))) {
                    return false;
                }
            }
        }

        return true;
    }


    public function logInFile($texte, $type = 'DEBUG')
    {
        if ($this->debug_mode == true) {
            $fichier_a_ecrire = '../modules/prestashoptodolibarrpro/prestashopdolibarr.log';
            if (file_exists('./modules/prestashoptodolibarrpro/prestashopdolibarr.log')) {
                $fichier_a_ecrire = './modules/prestashoptodolibarrpro/prestashopdolibarr.log';
            }
            $in_file = fopen($fichier_a_ecrire, 'a+');
            fputs($in_file, "LOG[$type][".date('Ymd.H:i')."]: $texte \n");
            fclose($in_file);
        }
    }

    public function getContent()
    {
       
        $this->_html = '<h2>'.$this->displayName.'</h2>';

        $this->postValidation();
        if (!count($this->post_errors)) {
            $this->postProcess();
        } else {
            foreach ($this->post_errors as $err) {
                $this->_html .= '<div class = "alert error">'.$err.'</div>';
            }
        }

        $this->initConfig();

        // get order state for order syncronization
        $configurations = Configuration::getMultiple(array('PS_LANG_DEFAULT'));
        $default_language = (int)$configurations['PS_LANG_DEFAULT'];
        $order_states = $this->getOrderStates($default_language);

        $order_states_options = array(
            '0' => '',
            '1' => $this->l('Draft'),
            '2' => $this->l('Validated'),
            '3' => $this->l('Shipment in process'),
            '4' => $this->l('Delivered'),
            '5' => $this->l('Processed'),
            '6' => $this->l('Canceled')
        );
        //$this->logInFile('orderstates : '.print_r($order_states,true));
        $var = array(
        'ws_adress_value' => htmlentities(Tools::getValue('adress', $this->ws_adress_value), ENT_COMPAT, 'UTF-8'),
        'ws_key_value' => htmlentities(Tools::getValue('WSkey', $this->ws_key_value), ENT_COMPAT, 'UTF-8'),
        'ws_login_value' => htmlentities(Tools::getValue('login', $this->ws_login_value), ENT_COMPAT, 'UTF-8'),
        'ws_passwd_value' => htmlentities(Tools::getValue('password', $this->ws_passwd_value), ENT_COMPAT, 'UTF-8'),
        'ws_trigram_value' => htmlentities($this->ws_trigram_value, ENT_COMPAT, 'UTF-8'),
        'is_checked_synch_customer' => $this->is_checked_synch_customer,
        'is_checked_synch_product' => $this->is_checked_synch_product,
        'is_checked_synch_stock' => $this->is_checked_synch_stock,
        'ws_warehouse_value' => htmlentities(
            Tools::getValue('warehouse', $this->ws_warehouse_value),
            ENT_COMPAT,
            'UTF-8'
        ),
        'is_checked_synch_invoice' => $this->is_checked_synch_invoice,
        'is_checked_synch_order' => $this->is_checked_synch_order,
        'is_checked_synch_category' => $this->is_checked_synch_category,
        'is_checked_synch_status' => $this->is_checked_synch_status,
        'ws_accesss_ok' => $this->ws_accesss_ok,
        'order_states' => $order_states,
        'order_states_options' => $order_states_options);
        $this->context->smarty->assign('varMain', $var);

        $this->_html .= $this->display(__FILE__, 'views/templates/admin/main.tpl');

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
        //verification pour le bouton de configuration
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
                // trigramme par defaut si vide
                $this->_wstrigram_value = 'PTS';
            } elseif (Tools::strlen($trigram_action) <> 3) {
                $this->post_errors[] = $this->l('"Trigram" must have 3 characters');
            }
        }
    }

    public function postProcess()
    {
        //$configurations = Configuration::getMultiple(array('PS_LANG_DEFAULT', 'PS_CURRENCY_DEFAULT'));

        $btn_submit_acces_ws = Tools::getValue('btnSubmitAccesWS');
        $adress_action = Tools::getValue('adress');
        $wskey_action = Tools::getValue('WSkey');
        $login_action = Tools::getValue('login');
        $password_action = Tools::getValue('password');
        $trigram_action = Tools::getValue('trigram');
        //update WS settings
        if ($btn_submit_acces_ws) {
            Configuration::updateValue('DOLIBARR_WS_ADRESS', $adress_action);
            Configuration::updateValue('DOLIBARR_WS_KEY', $wskey_action);
            Configuration::updateValue('DOLIBARR_WS_LOGIN', $login_action);
            Configuration::updateValue('DOLIBARR_WS_PASSWD', $password_action);
            // trigramme par defaut si vide
            if (!($trigram_action)) {
                $trigram_action = 'PTS';
            }
            Configuration::updateValue('DOLIBARR_WS_TRIGRAM', $trigram_action);
            $this->ws_trigram_value = $trigram_action;
            $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "'.
            $this->l('OK').'" /> '.
            $this->l('Settings updated, you can test the connection by clicking on the "Test Webservice Acess" button')
            .'</div>';
        }

        //Test WS access
        $btn_test_acces_ws = Tools::getValue('btnTestAccesWS');
        if ($btn_test_acces_ws) {
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
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "'.
                $this->l('OK').'" /> '
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
                $m = 'Dolibarr doesn\'t respond, please verify that Dolibarr\'s ';
                $m .= 'version is equal or upper than 3.6.0 and verify the Dolibarr\'s url';
                $this->_html .= '<div class = "alert error">'.
                $this->l($m).
                '</div>';
            }
        }

        /**
        * Export clients
        */
        $btn_submit_export_client = Tools::getValue('btnSubmitExportClient');
        if ($btn_submit_export_client) {
            $tmsp_start = time();
            $import_client = $this->importClients(0, $tmsp_start);

            if ($import_client['result'] == 'OK') {
                if ($import_client['nbrMaxClient']) { //pas totalement importé
                    $this->_html .= '<div class = "conf warn"><img src= "../modules/prestashoptodolibarrpro/warning.png" alt = "OK" /> '.
                    $this->l('You have successfully exported').
                    ' '.$import_client['nbClientImported'].' '.$this->l('customer(s) on').' '.
                    $import_client['nbClientTotal'].
                    $this->l(', press Start again for exporting next customers').'</div>';
                } else {
                    $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "OK" /> '.
                    $import_client['nbClientImported'].' '.
                    $this->l('customer(s) on').' '.$import_client['nbClientTotal'].' '.$this->l('exported').'</div>';
                }
            } else {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ko.gif" alt = "KO" /> '.
                $this->l('something went wrong').' : '.
                $import_client['reason'].'</div>';
            }
        }

        /**
        * Reset export client
        */
        $btn_reset_export_client = Tools::getValue('btnResetExportClient');
        if ($btn_reset_export_client) {
            $reset_customers = $this->resetCustomers();

            if ($reset_customers) {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "OK" /> '.
                $this->l('Reset on customers done').'</div>';
            } else {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ko.gif" alt = "KO" /> '.
                $this->l('something went wrong').'</div>';
            }
        }

        /**
        * Export produits
        */
        $btn_submit_export_product = Tools::getValue('btnSubmitExportProduct');
        if ($btn_submit_export_product) {
            $tmsp_start = time();
            $result_product = $this->importProduits(0, $tmsp_start);

            if ($result_product['result'] == 'OK') {
                if ($result_product['nbrMaxProduct']) {//pas totalement importé
                    $this->_html .= '<div class = "conf warn"><img src= "../modules/prestashoptodolibarrpro/warning.png" alt = "OK" /> '.
                    $this->l('You have successfully exported').
                    ' '.$result_product['nbProductImported'].' '.$this->l('product(s) on').' '.
                    $result_product['nbProductTotal'].
                    $this->l(', press Start again for exporting next products').'</div>';
                } else {
                    $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "OK" /> '.
                    $result_product['nbProductImported'].
                    ' '.$this->l('product(s) on').' '.$result_product['nbProductTotal'].' '.
                    $this->l('exported').'</div>';
                }
            } else {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ko.gif" alt = "KO" /> '.
                $this->l('something went wrong').
                ' : '.$result_product['reason'].'</div>';
            }
        }

        /**
        * Reset export produits
        */
        $btn_reset_export_product = Tools::getValue('btnResetExportProduct');
        if ($btn_reset_export_product) {
            $reset_products = $this->resetProducts();

            if ($reset_products) {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "OK" /> '.
                $this->l('Reset on products done').'</div>';
            } else {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ko.gif" alt = "KO" /> '.
                $this->l('something went wrong').'</div>';
            }
        }

        /**
        * Export factures
        */
        $btn_submit_import_invoice = Tools::getValue('btnSubmitImportInvoice');
        if ($btn_submit_import_invoice) {
            $this->logInFile('--Export invoices--');
            $tmsp_start = time();

            $result_invoice = $this->importFacturesOrCommandes(0, $tmsp_start, false, true);
            $this->logInFile('FIN export factures : '.print_r($result_invoice, true));
            if ($result_invoice['result'] == 'OK') {
                if ($result_invoice['nbrMaxOrder']) {//pas totalement importé
                    $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "'.
                    $this->l('Ok').'" /> '.
                    $this->l('You have successfully exported').' '.$result_invoice['nbOrderOk'].' '.
                    $this->l('invoice(s) on').' '.$result_invoice['nbOrderTotal'].
                    $this->l(', press Start again for exporting next invoices').'</div>';
                } else {
                    $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "'.
                    $this->l('Ok').'" /> '.$result_invoice['nbOrderOk'].' '.
                    $this->l('invoice(s) on').' '.$result_invoice['nbOrderTotal'].' '.$this->l('exported').'</div>';
                }
            } else {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ko.gif" alt = "KO" /> '.
                $this->l('something went wrong').' : '.
                $result_invoice['reason'].'</div>';
            }
        }

        /**
        * Reset export factures
        */
        $btnreset_export_invoice = Tools::getValue('btnResetExportInvoice');
        if ($btnreset_export_invoice) {
            $resetinvoices = $this->resetInvoices();

            if ($resetinvoices) {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "OK" /> '.
                $this->l('Reset on invoices done').'</div>';
            } else {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ko.gif" alt = "KO" /> '.
                $this->l('something went wrong').'</div>';
            }
        }

        /**
        * Export commandes
        */
        $btn_submit_import_order = Tools::getValue('btnSubmitImportOrder');
        if ($btn_submit_import_order) {
            $this->logInFile('--Export orders--');
            $tmsp_start = time();

            $result_order = $this->importFacturesOrCommandes(0, $tmsp_start, true, false);
            $this->logInFile('retour export commandes : '.print_r($result_order, true));
            if ($result_order['result'] == 'OK') {
                if ($result_order['nbrMaxOrder']) {//pas totalement importé
                    $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "'.
                    $this->l('Ok').'" /> '.
                    $this->l('You have successfully exported').' '.$result_order['nbOrderOk'].' '.
                    $this->l('order(s) on').' '.$result_order['nbOrderTotal'].
                    $this->l(', press Start again for exporting next orders').'</div>';
                } else {
                    $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "'.
                    $this->l('Ok').'" /> '.$result_order['nbOrderOk'].' '.
                    $this->l('order(s) on').' '.$result_order['nbOrderTotal'].' '.
                    $this->l('exported').'</div>';
                }
            } else {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ko.gif" alt = "KO" /> '.
                $this->l('something went wrong').' : '.
                $result_order['reason'].'</div>';
            }
        }

        /**
        * Reset export commandes
        */
        $btn_reset_export_order = Tools::getValue('btnResetExportOrder');
        if ($btn_reset_export_order) {
            $resetorders = $this->resetOrders();
            if ($resetorders) {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "OK" /> '.
                $this->l('Reset on orders done').'</div>';
            } else {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ko.gif" alt = "KO" /> '.
                $this->l('something went wrong').'</div>';
            }
        }

        /**
        * Export categories
        */
        $btn_submit_import_category = Tools::getValue('btnSubmitImportCategory');
        if ($btn_submit_import_category) {
            $this->logInFile('--Export categories--');
            $result_category = $this->importCategories();
            if ($result_category['result']['result_code'] == 'OK') {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "'.
                $this->l('Ok').'" /> '.
                $this->l('You have successfully exported your categories').'</div>';
            } else {
                $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ko.gif" alt = "KO" /> '.
                $this->l('something went wrong').' : '.
                $result_category['result']['result_label'].'</div>';
            }
        }

        /**
        * checking des synchronisation
        */
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
            
            
            $configurations = Configuration::getMultiple(array('PS_LANG_DEFAULT'));
            $default_language = (int)$configurations['PS_LANG_DEFAULT'];
            $order_states = $this->getOrderStates($default_language);
            $this->logInFile('check synchro order states'.print_r($order_states, true));
            foreach ($order_states as $x => $order_state) {
                $get_id = Tools::getValue('select_'.$order_state['id_order_state']);
                //update orderstate
                $query = 'UPDATE '._DB_PREFIX_.'order_state  
                        SET id_order_state_doli = '.(int)$get_id.'
                        WHERE id_order_state = '.(int)$order_state['id_order_state'];
                if (!Db::getInstance()->execute($query)) {
                    $this->logInFile('erreur de maj id_order_state dans la base order_state');
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
            

            if ($this->is_checked_synch_customer || $this->is_checked_synch_product ||
                $this->is_checked_synch_invoice) {
                if ($this->is_checked_synch_order || $this->is_checked_synch_category) {
                    $this->_html .= '<div class = "conf confirm"><img src= "../modules/prestashoptodolibarrpro/ok.gif" alt = "" /> ';

                    if ($this->is_checked_synch_customer) {
                        $this->_html .= '<br>'.$this->l('Customers are synchronised with Dolibarr');
                    }
                    if ($this->is_checked_synch_product) {
                        $this->_html .= '<br>'.$this->l('Products are synchronised with Dolibarr');
                    }
                    if ($this->is_checked_synch_invoice) {
                        $this->_html .= '<br>'.$this->l('Invoices are synchronised with Dolibarr');
                    }
                    if ($this->is_checked_synch_order) {
                        $this->_html .= '<br>'.$this->l('Orders are synchronised with Dolibarr');
                    }
                    if ($this->is_checked_synch_category) {
                        $this->_html .= '<br>'.$this->l('Categories are synchronised with Dolibarr');
                    }
                    if ($this->is_checked_synch_status) {
                        $this->_html .= '<br>'.$this->l('Status are synchronised with Dolibarr');
                    }

                    $this->_html .= '</div>';
                }
            } else {
                $this->_html .= '<div class = "alert warning">'.$this->l('You have nothing checked').'</div>';
            }
        }
    }

    /**
    *
    * methodes d'exportation categories
    *
    **/
    public function importCategories()
    {
        $this->logInFile('--importCategories--');
        $configurations = Configuration::getMultiple(array('PS_LANG_DEFAULT', 'PS_CURRENCY_DEFAULT'));
        $default_language = (int)$configurations['PS_LANG_DEFAULT'];
        $this->logInFile('cat language : '.$default_language);
        $result_get_order_tab = array('result'=>array('result_code' => '', 'result_label' => ''));
        $category_tab = Category::getCategories($default_language, false);
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

        $soapclient1 = new p2dWebservices_client($ws_dol_url_category);
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
        $this->logInFile('reponse du WS in tab : '.print_r($result_get_order_tab, true));
        return $result_get_order_tab;
    }

    /**
    *
    * methodes d'exportation clients
    *
    **/
    public function importClients($customer_ec = 0, $tmsp_start = 0)
    {
        $this->logInFile('--importClients--');
        $this->logInFile('importClients : '.print_r($customer_ec, true));
        $configurations = Configuration::getMultiple(array('PS_LANG_DEFAULT', 'PS_CURRENCY_DEFAULT'));
        $default_language = (int)$configurations['PS_LANG_DEFAULT'];

        $result = 'OK';
        $nb_client_total        = 0;
        $nb_client_imported    = 0;
        $nbr_max_client        = false;

        if ($customer_ec == 0) {      //Catching up
            $obj = new Customer();
            $customers = $obj->getCustomers();
            $customers = array_reverse($customers);  //Recuperation des customers
        } else {
            $customers = $customer_ec;
        }

        $nb_client_total = count($customers);

        $wsretourbis = array();
        $wsretourbis['result']['result_label'] = '';
        foreach ($customers as $customer) {
            $c_id          = $customer['id_customer'];
            $ref          = $this->ws_trigram_value.$this->format($c_id, 10);
            $orders       = Order::getCustomerOrders((int)$c_id, true);
            $this->logInFile('\customer en cours before new, '.(int)$c_id.' : '.print_r($customer, true));
            $customer     = new Customer($c_id);
            $this->logInFile('\customer en cours after new, '.(int)$c_id.' : '.print_r($customer, true));
            $customer_info = $customer->getFields();
            $nbr_commandes = count($orders);
            $addresses = $customer->getAddresses($default_language);

            //récupération des ref internes
            $customer_ref = $this->getCustomerRef($c_id);

            $this->logInFile('\customer ref interne:'.print_r($customer_ref, true));
            $this->logInFile('\addresses : '.print_r($addresses, true));
            $this->logInFile($customer->date_upd.' - '.$customer->date_add);

            $max_upd = 0;
            $max_add = 0;
            //on cherche un maj adresse eventuelle
            foreach ($addresses as $adress_c) {
                if ($adress_c['date_add'] > $max_add) {
                    $max_add = $adress_c['date_add'];
                }
                if ($adress_c['date_upd'] > $max_upd) {
                    $max_upd = $adress_c['date_upd'];
                }
            }

            //gestion anti re-import
            if ($customer_ref['date_export_doli'] >= max($customer->date_upd, $customer->date_add)) {
                if ($customer_ref['date_export_doli'] >= max($max_upd, $max_add)) {
                    $this->logInFile('--->Client ref : '.$c_id.' deja importe en date de maj, au suivant');
                    $nb_client_imported++;
                    continue;
                }
            }

            $code_postal = '00000';
            $telephone = '0000000000';
            $private_note = $customer->note;
            $url = $customer->website;

            if (count($addresses)) {
                $adress = $addresses[0]['company'].' '.$addresses[0]['firstname'].' '.$addresses[0]['lastname'].', '.
                $addresses[0]['address1'].', '.
                $addresses[0]['address2'].', '.$addresses[0]['postcode'].', '.$addresses[0]['city'].', '.
                $addresses[0]['country'];
                $code_postal = $addresses[0]['postcode'];
                $ville = $addresses[0]['city'];
                $pays = $addresses[0]['country'];
                $obj_country = new Country();
                $id_country = $obj_country->getIdByName(null, $pays);
                $country_code_iso = $obj_country->getIsoById($id_country);

                $adress_phone = $addresses[0]['phone'];
                $adress_phone_mobile = $addresses[0]['phone_mobile'];
                if ($adress_phone != '') {
                    $telephone = $adress_phone;
                }
                if ($adress_phone_mobile != '') {
                    $telephone = $adress_phone_mobile;
                }
            }

            $c_name        = $customer_info['lastname'];
            $c_firstname   = $customer_info['firstname'];
            $email        = $customer_info['email'];

            if ($nbr_commandes > 0) {
                // || $is_clientForcer == 1)   //  A REVOIR
                $is_client = 1;
            } else {
                $is_client = 2;
            }

            $code_retourbis = 'KO';
            if ($customer_ref['id_ext_doli']) {   //update customer
                $enrich_customer = $this->enrichCustomers(
                    $customer_ref['id_ext_doli'],
                    $c_name,
                    $c_firstname,
                    $ref,
                    $is_client,
                    $private_note,
                    $adress,
                    $code_postal,
                    $ville,
                    $country_code_iso,
                    $telephone,
                    $email,
                    $url,
                    $addresses
                );
                $wsretourbis = $this->WSModCustomer($enrich_customer);
                $code_retourbis = $wsretourbis['result']['result_code'];
            }
            if ((!$customer_ref['id_ext_doli']) || ($code_retourbis == 'NOT_FOUND')) {
                // add customer si inexistant ou update a échoué
                // faire un get pr s'assurer qu'il n'existe pas deja (en cas de reinstallation) et récupérer son id
                if ($code_retourbis != 'NOT_FOUND') {
                    $wsretour = $this->WSGetCustomer($ref);
                    $code_retour = $wsretour['result']['result_code'];
                } else {
                    $code_retour = 'NOT_FOUND';
                }

                if ($code_retour == 'OK') { //update
                    $enrich_customer = $this->enrichCustomers(
                        $wsretour['thirdparty']['id'],
                        $c_name,
                        $c_firstname,
                        $ref,
                        $is_client,
                        $private_note,
                        $adress,
                        $code_postal,
                        $ville,
                        $country_code_iso,
                        $telephone,
                        $email,
                        $url,
                        $addresses
                    );
                    $wsretourbis = $this->WSModCustomer($enrich_customer);
                    $code_retourbis = $wsretourbis['result']['result_code'];
                } elseif ($code_retour == 'NOT_FOUND') {
                    //create
                    $enrich_customer = $this->enrichCustomers(
                        '',
                        $c_name,
                        $c_firstname,
                        $ref,
                        $is_client,
                        $private_note,
                        $adress,
                        $code_postal,
                        $ville,
                        $country_code_iso,
                        $telephone,
                        $email,
                        $url,
                        $addresses
                    );
                    $wsretourbis = $this->WSAddCustomer($enrich_customer);
                    $code_retourbis = $wsretourbis['result']['result_code'];
                }
            }

            if ($code_retourbis == 'OK') {
                $this->setCustomerRef($c_id, $wsretourbis['id']);
                $nb_client_imported++;
                $customer_ref['id_ext_doli'] = $wsretourbis['id'];
            } else {
                //pb de communication
                $result = 'KO';
                break;
            }

            //nbr de client max atteint
            $tmsp_now = time();
            $this->logInFile('--->tmsp Start :'.$tmsp_start, 'ERROR');
            $this->logInFile('--->tmsp Now :'.$tmsp_now, 'ERROR');
            $this->logInFile('--->tmsp diff :'.($tmsp_now - $tmsp_start), 'ERROR');
            if ((($tmsp_now - $tmsp_start) >= $this->nbr_max_sec_export) && ($nb_client_imported != $nb_client_total)) {
                $nbr_max_client = true;
                $this->logInFile("---> time limit d'export atteint, break");
                break;
            }
        }

        return array('result'=>$result, 'nbClientImported'=>$nb_client_imported,
        'nbClientTotal'=>$nb_client_total, 'nbrMaxClient'=>$nbr_max_client,
        'id_ext_doli'=>$customer_ref['id_ext_doli'], 'reason'=>$wsretourbis['result']['result_label']);
    }

    /*
    * fonction enrichissement customers
    */
    public function enrichCustomers(
        $id,
        $nom,
        $prenom,
        $client_ref,
        $is_client,
        $private_note,
        $address,
        $code_postal,
        $ville,
        $country_code_iso,
        $telephone,
        $email,
        $url,
        $addresses
    ) {
        $this->logInFile('--enrichCustomers--');
        $nom = $this->noSpecialCharacterV3($nom);
        $prenom = $this->noSpecialCharacterV3($prenom);
        $client_ref = $this->noSpecialCharacterV3($client_ref);
        $private_note = $this->noSpecialCharacterV3($private_note);
        $address = $this->noSpecialCharacterV3($address);
        $ville = $this->noSpecialCharacterV3($ville);
        $telephone = $this->noSpecialCharacterV3($telephone);
        $email = $this->noSpecialCharacterV3($email);
        $url = $this->noSpecialCharacterV3($url);
        $enrich = array();

        $enrich['id'] = $id;
        $enrich['ref'] = $nom.' '.$prenom;
        $enrich['ref_ext'] = $client_ref;
        $enrich['status'] = '1'; //0 = clos // 1 = actif
        $enrich['client'] = $is_client;
        $enrich['supplier'] = '0';
        $enrich['customer_code'] = '-1';
        $enrich['supplier_code'] = '';
        $enrich['customer_code_accountancy'] = '';
        $enrich['supplier_code_accountancy'] = '';
        $enrich['note_public'] = 'Imported from Prestashop';
        $enrich['note_private'] = $private_note;
        $enrich['address'] = $address;
        $enrich['zip'] = $code_postal;
        $enrich['town'] = $ville;
        $enrich['province_id'] = '';
        $enrich['country_id'] = '';
        $enrich['country_code'] = $country_code_iso;
        $enrich['phone'] = $telephone;
        $enrich['fax'] = '';
        $enrich['email'] = $email;
        $enrich['url'] = $url;
        $enrich['profid1'] = '';
        $enrich['profid2'] = '';
        $enrich['profid3'] = '';
        $enrich['profid4'] = '';
        $enrich['profid5'] = '';
        $enrich['profid6'] = '';
        $enrich['capital'] = '';
        $enrich['barcode'] = '';
        $enrich['vat_used'] = '';
        $enrich['vat_number'] = '';
        $enrich['canvas'] = '';
        $enrich['individual'] = '';

        foreach ($addresses as $x => $adresse_n) {
            if ($adresse_n['postcode'] != '') {
                $adress = $adresse_n['address1'];
                if ($adresse_n['address2'] != '') {
                    $adress .= ' '.$adresse_n['address2'].',';
                }

                $nom        = $adresse_n['lastname'];
                if ($adresse_n['company'] != '') {
                    $nom = $adresse_n['company'].', '.$nom;
                }

                $prenom        = $adresse_n['firstname'];
                $code_postal = $adresse_n['postcode'];
                $ville         = $adresse_n['city'];
                $pays         = $adresse_n['country'];
                $obj_country = new Country();
                $id_country     = $obj_country->getIdByName(null, $pays);
                $country_code_iso = $obj_country->getIsoById($id_country);

                $adress_phone = $adresse_n['phone'];
                $adress_phone_mobile = $adresse_n['phone_mobile'];
                if ($adress_phone != '') {
                    $telephone = $adress_phone;
                }
                if ($adress_phone_mobile != '') {
                    $telephone = $adress_phone_mobile;
                }

                $adress     = $this->noSpecialCharacterV3($adress);
                $ville         = $this->noSpecialCharacterV3($ville);
                $telephone     = $this->noSpecialCharacterV3($telephone);

                $enrich['addressesClient']['addresseClient'][$x]['nom'] = $nom;
                $enrich['addressesClient']['addresseClient'][$x]['prenom'] = $prenom;
                $enrich['addressesClient']['addresseClient'][$x]['adresse'] = $adress;
                $enrich['addressesClient']['addresseClient'][$x]['zip'] = $code_postal;
                $enrich['addressesClient']['addresseClient'][$x]['town'] = $ville;
                $enrich['addressesClient']['addresseClient'][$x]['country_code'] = $country_code_iso;
                $enrich['addressesClient']['addresseClient'][$x]['phone'] = $telephone;
                $enrich['addressesClient']['addresseClient'][$x]['import_key'] =
                $this->ws_trigram_value.$this->format($adresse_n['id_address'], 10);
            }
        }

        return $enrich;
    }

    /**
    *
    * methodes importation produits
    *
    **/
    public function importProduits($product_ec = 0, $tmsp_start = 0)
    {
        $this->logInFile('--importProduits--');
        $configurations     = Configuration::getMultiple(array('PS_LANG_DEFAULT', 'PS_CURRENCY_DEFAULT'));
        $default_language     = (int)$configurations['PS_LANG_DEFAULT'];
        $result             = 'OK';
        $nb_product_total        = 0;
        $nb_product_imported    = 0;
        $nbr_max_product        = false;

        $this->logInFile('--IMPORT DES PRODUITS--');

        if ($this->dolibarr_ref_ind == 0) {
            $this->dolibarr_ref_ind = 1;
        }
        $this->logInFile('variable incrementation ref produit: '.$this->dolibarr_ref_ind);
        //get des id product dont la référence externe <> référence externe, par ordre croissant
        $idsrefdoliproduct = $this->getRefdoliEmpty('product');
        $this->logInFile('liste des id product dont ref doli <> : '.print_r($idsrefdoliproduct, true));
        if ($idsrefdoliproduct) {
            foreach ($idsrefdoliproduct as $product) {
                if ($product['reference'] == '') {  //reference vide => on la créée
                    $refdoli = $this->ws_trigram_value.$this->dolibarr_ref_ind;
                    $this->dolibarr_ref_ind ++;
                    Configuration::updateValue('DOLIBARR_REF_IND', $this->dolibarr_ref_ind);
                } else { // reference renseignée
                    //Est-elle unique ?
                    $is_unique_id = $this->isRefUnique($product['id_product'], $product['reference'], 'product');
                    //test sur les id inférieurs de ref identiques
                    if ($is_unique_id == 0) {
                        //elle est unique
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

        //get des id product_attribute dont la référence externe <> référence interne, par ordre croissant
        // Limite : pas de MAJ de la réf attribut quand celle-ci est vide en interne
        //=> le chgt d'une référence d'un produit pere sera inchangé
        $idsrefdoliproduct = $this->getRefdoliEmpty('product_attribute');
        $this->logInFile('liste des id product_attribute dont ref <> : '.print_r($idsrefdoliproduct, true));
        $ind = 1;
        if ($idsrefdoliproduct) {
            $id_product = -1;
            foreach ($idsrefdoliproduct as $product) {
                if ($product['id_product'] <> $id_product) {  //chgt de produit => réinit incrément
                    $ind = 1;
                    $id_product = $product['id_product'];
                }
                if ($product['reference'] == '') {  //reference vide => on la créée
                    $refdoli = $product['product_reference'].'-d'.$ind;
                    $ind ++;
                } else { // reference renseignée
                    //Est-elle unique ?
                    $is_unique_id = $this->isRefUnique(
                        $product['id_product_attribute'],
                        $product['reference'],
                        'product_attribute'
                    );
                    if ($is_unique_id == 0) { //elle est unique
                        $refdoli = $product['reference'];
                    } else {
                        $refdoli = $product['product_reference'].'-d'.$ind;
                        $ind ++;
                    }
                }
                $this->insertRefDoli($product['id_product_attribute'], $refdoli, 'product_attribute');
            }
        }

        //prise des id des produits pere
        if ($product_ec == 0) {
            //set_time_limit(600);
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

        //boucle de decompte de tout les produits
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
        //parcours des produits pere
        foreach ($products as $product) {
            $this->logInFile('->parcours du produit id: '.$product['id_product'].' / '.print_r($product, true));

            // Recuperation des declinaisons
            if (_PS_VERSION_ < '1.5') {
                $product_attributes_ids = $this->getProductAttributesIds($product['id_product'], 0);
            } else {
                $product_attributes_ids = Product::getProductAttributesIds($product['id_product'], false);
            }

            //le produit n'a pas d'attribut : on creee un fictif = '0'
            if (!$product_attributes_ids) {
                $product_attributes_ids = array(array('id_product_attribute'=>0));
            }

            //Boucle sur les declinaisons
            foreach ($product_attributes_ids as $product_attribute_id) {
                $product_ref = $this->ws_trigram_value.$this->format($product['id_product'], 10).
                $this->format($product_attribute_id['id_product_attribute'], 10);
                //$product_presta_ref = $product['reference'];

                //Recup references interne
                $product_ref_interne = $this->getProductRef(
                    $product['id_product'],
                    $product_attribute_id['id_product_attribute']
                );
                $this->logInFile(
                    '-->references produits internes recuperees ('.$product['id_product'].', '.
                    $product_attribute_id['id_product_attribute'].
                    ') : '.print_r($product_ref_interne, true)
                );
                $this->logInFile(
                    'date interne : '.$product_ref_interne['date_export_doli'].' - date upd : '.
                    $product['date_upd'].' - date add : '.
                    $product['date_add']
                );

                if ($product_attribute_id['id_product_attribute'] != 0) {
                    //$product_attribute_ref = $product_ref_interne['reference'];
                    $ean13 = $product_ref_interne['ean13'];
                    $upc = $product_ref_interne['upc'];
                } else {
                    //$product_attribute_ref = '';
                    $ean13 = $product['ean13'];
                    $upc = $product['upc'];
                }
                $product_presta_ref = $product_ref_interne['ref_doli'];
                $this->logInFile('-->parcours de lattribut ref : '.$product_ref.' / '.$product_presta_ref);

                //correction bug sur id attribute en commandes et factures
                if (array_key_exists('id_product_attribute', $product) == true) {
                    if ($product['id_product_attribute'] == $product_attribute_id['id_product_attribute']) {
                        $id_product_doli_ec = $product_ref_interne['id_ext_doli'];
                    }
                }

                //gestion anti report
                if ($product_ref_interne['date_export_doli'] >= max($product['date_upd'], $product['date_add'])) {
                    $this->logInFile('--->attribut ref : '.$product_ref.' deja importe en date de maj, au suivant');
                    $nb_product_imported++;
                    continue;
                }

                $code_retour = '';
                if ($product_ref_interne['id_ext_doli']) {   //update product
                    $this->logInFile('--- MAJ PRODUIT ---');
                    $enrich = $this->enrichProducts(
                        $product_ref_interne['id_ext_doli'],
                        $product_ref,
                        $product['id_product'],
                        $product_attribute_id['id_product_attribute'],
                        $product['description_short'],
                        $product['active'],
                        $default_language,
                        $product_presta_ref,
                        $ean13,
                        $upc
                    );
                    $wsretour = $this->WSModProduct($enrich);
                    $code_retour = $wsretour['result']['result_code'];
                }
                if ((!$product_ref_interne['id_ext_doli']) || ($code_retour == 'NOT_FOUND')) {
                    // add product si inexistant ou update a échoué
                    // faire un get pr s'assurer qu'il n'existe pas deja
                    //(en cas de reinstallation) et récupérer son id
                    if ($code_retour != 'NOT_FOUND') {
                        $wsretour = $this->WSGetProduct($product_ref);
                        $code_retour = $wsretour['result']['result_code'];
                    }
                    if ($code_retour == 'OK') { //update product
                        $this->logInFile('--- MAJ PRODUIT ---');
                        $enrich = $this->enrichProducts(
                            $wsretour['product']['id'],
                            $product_ref,
                            $product['id_product'],
                            $product_attribute_id['id_product_attribute'],
                            $product['description_short'],
                            $product['active'],
                            $default_language,
                            $product_presta_ref,
                            $ean13,
                            $upc
                        );
                        $wsretour = $this->WSModProduct($enrich);
                        $code_retour = $wsretour['result']['result_code'];
                    } elseif ($code_retour == 'NOT_FOUND') {  //create product
                        $this->logInFile('--- INSERT PRODUIT ---');
                        $enrich = $this->enrichProducts(
                            '',
                            $product_ref,
                            $product['id_product'],
                            $product_attribute_id['id_product_attribute'],
                            $product['description_short'],
                            $product['active'],
                            $default_language,
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
                        $wsretour['id']
                    );
                    $nb_product_imported++;
                    $product_ref_interne['id_ext_doli'] = $wsretour['id'];
                } else {       //pb de communication
                    $this->logInFile("--->plantage lors de l'update de lattribut : $product_ref", 'ERROR');
                    $result = 'KO';
                    break;
                }

                //nbr de produit max atteint
                $tmsp_now = time();
                $this->logInFile('--->tmsp Start :'.$tmsp_start, 'ERROR');
                $this->logInFile('--->tmsp Now :'.$tmsp_now, 'ERROR');
                $this->logInFile('--->tmsp diff :'.($tmsp_now - $tmsp_start), 'ERROR');
                if ((($tmsp_now - $tmsp_start) >= $this->nbr_max_sec_export) &&
                ($nb_product_imported != $nb_product_total)) {
                    $nbr_max_product = true;
                    $this->logInFile('---> nombre max de produits exportés atteint, break');
                    break;
                }

                //correction bug sur id attribute en commandes et factures
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
    * fonction enrichissement produits
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

        //recuperation de l'image
        $image_product = Image::getImages($default_language, $id_product, $id_product_attribute);
        if (array_key_exists(0, $image_product)) {
            $image_id = $image_product[0]['id_image'];
        } else {
            $image_id = $this->getIdImage($id_product);
        }

        if ($image_id != '') {
            $image_path = $image_id.'/';
            $imageType = '-'.ImageType::getFormatedName('home');
            $image_path_hd = $image_path.$image_id.$imageType.'.jpg';
            $image_name = $image_id.$imageType.'.jpg';
            $soapclient = new p2dWebservices_client('test');

            $image_path_hd2 = str_replace('\\', '/', _PS_PROD_IMG_DIR_.$image_path_hd);
            if (($image_path_hd2 != '') && (file_exists($image_path_hd2))) {
                $image_b64 = $soapclient->formatIg(Tools::file_get_contents($image_path_hd2));
            } else {
                $image_b64 = '';
            }

            $this->logInFile(
                "->image : \nfor product name : ".
                $product_name."\n image disque dur = $image_path_hd2 \n & image name : ".$image_name
            );
        } else {
            $this->logInFile("->image : Pas d'image pour ce produit");
        }

        $product_ref = $this->noSpecialCharacterV3($product_ref);
        $description_short = $this->noSpecialCharacterV3($description_short);
        $id_ext_doli = $this->noSpecialCharacterV3($id_ext_doli);
        $product_name = $this->noSpecialCharacterV3($product_name);

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
        $enrich['note'] = 'imported from Prestashop';
        // pas cette notion dans prestashop => taguee comme importé de presta
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
        $enrich['images']['image']['photo'] = $image_b64;
        $enrich['images']['image']['photo_vignette'] = $image_name;
        $enrich['images']['image']['imgWidth'] = '250';
        $enrich['images']['image']['imgHeight'] = '250';

        //récupération des catégories du produits
        if ($this->is_checked_synch_category == 'true') {
            $category_obj_list = Product::getProductCategories($id_product);
            $this->logInFile('->getProductCategories '.$id_product.' : '.print_r($category_obj_list, true));
            foreach ($category_obj_list as $id => $cat) {
                $category_obj_list[$id] = $this->ws_trigram_value.$this->format($cat, 10);
            }
            $this->logInFile(
                '->getProductCategories apres transformation '.$id_product.' : '.
                print_r($category_obj_list, true)
            );
            $enrich['category_list'] = $category_obj_list;
        }
        return $enrich;
    }

    /**
    *
    * methodes importation factures et/ou commandes
    *
    **/
    public function importFacturesOrCommandes($facture_ec = 0, $tmsp_start = 0, $is_commande = 0, $is_facture = 0)
    {
        $this->logInFile('--importFacturesOrCommandes--');
        $nb_order_ok = 0;
        $result = 'OK';
        $nb_order_total = 0;
        $nbr_max_order = false;
        $obj = new Customer();

        if ((is_int($facture_ec) == true) && ($facture_ec == 0)) {   //export de masse
            //on part des utilisateurs
            $customers = $obj->getCustomers();
            //recup nombre total de factures présentes
            foreach ($customers as $customer) {
                $c_id = $customer['id_customer'];
                $orders = Order::getCustomerOrders((int)$c_id, true);
                $nb_order_total += count($orders);
            }
            //parcours des clients
            foreach ($customers as $customer) {
                $c_id = $customer['id_customer'];
                $orders = Order::getCustomerOrders((int)$c_id, true);  // on recupère les commandes de l'utilisateur
                $orders = array_reverse($orders);

                $this->logInFile('customer n°'.$c_id);
                foreach ($orders as $row) {
                    $order = new Order($row['id_order']);

                    $enrich_retour = $this->enrichOrderAndSend($order, $tmsp_start, $is_commande, $is_facture);
                    $this->logInFile('---> retour enrichissement order : '.print_r($enrich_retour, true));
                    $code_retour = $enrich_retour['code'];
                    if (($code_retour == 'OK') || ($code_retour == 'DBL')) {
                        $nb_order_ok++;
                    } else {
                        //pb de communication
                        $this->logInFile('test break');
                        $result = 'KO';
                        break;
                    }

                    //temps limite depassé
                    $tmsp_now = time();
                    $this->logInFile('--->tmsp Start :'.$tmsp_start, 'ERROR');
                    $this->logInFile('--->tmsp Now :'.$tmsp_now, 'ERROR');
                    $this->logInFile('--->tmsp diff :'.($tmsp_now - $tmsp_start), 'ERROR');
                    if ((($tmsp_now - $tmsp_start) >= $this->nbr_max_sec_export) && ($nb_order_ok != $nb_order_total)) {
                        $nbr_max_order = true;
                        $this->logInFile("---> temps limite depasse dans l'export de commandes/factures, break");
                        break;
                    }
                }

                //break de temps limite depassé
                if ($nbr_max_order) {
                    break;
                }
            }
        } else {    //hookorderconfirmation
            $this->logInFile('---> enrichOrderAndSend ');
            $enrich_retour = $this->enrichOrderAndSend($facture_ec, $tmsp_start, $is_commande, $is_facture);
            $this->logInFile('---> retour enrichissement order 2 : '.print_r($enrich_retour, true));
            $code_retour = $enrich_retour['code'];
            if (($code_retour == 'OK') || ($code_retour == 'DBL')) {
                $nb_order_ok++;
            } else {
                $result = 'KO';
            }

            //temps limite depassé
            $tmsp_now = time();
            $this->logInFile('--->tmsp Start :'.$tmsp_start, 'ERROR');
            $this->logInFile('--->tmsp Now :'.$tmsp_now, 'ERROR');
            $this->logInFile('--->tmsp diff :'.($tmsp_now - $tmsp_start), 'ERROR');
            if ((($tmsp_now - $tmsp_start) >= $this->nbr_max_sec_export) && ($nb_order_ok != $nb_order_total)) {
                $nbr_max_order = true;
                $this->logInFile("---> temps limite depasse dans l'export de commandes/factures");
            }
        }
        return array('result'=>$result, 'nbOrderOk'=>$nb_order_ok, 'nbrMaxOrder'=>$nbr_max_order,
        'nbOrderTotal'=>$nb_order_total,
        'reason'=>$enrich_retour['reason']);
    }

    /** Methode enrichissement et envoi des commandes et des factures */
    public function enrichOrderAndSend($facture_ec, $tmsp_start, $is_commande, $is_facture)
    {
        $this->logInFile('--enrichOrderAndSend--');
        $configurations = Configuration::getMultiple(array('PS_LANG_DEFAULT', 'PS_CURRENCY_DEFAULT'));
        $id_order = $facture_ec->id;
        $export_order = $is_commande;
        $export_invoice = $is_facture;
        $reductions = $facture_ec->total_discounts_tax_incl;
        $id_address_delivery = $this->ws_trigram_value.$this->format($facture_ec->id_address_delivery, 10);
        $id_address_invoice = $this->ws_trigram_value.$this->format($facture_ec->id_address_invoice, 10);
        $this->logInFile('commande en cours : '.print_r($facture_ec, true));

        //prise du nom du transporteur
        $id_lang = (int)Context::getContext()->language->id;
        if ($id_lang == '') {
            $id_lang     = (int)$configurations['PS_LANG_DEFAULT'];
        }
        $id_transporteur = $facture_ec->id_carrier;
        $obj_carrier = new Carrier($id_transporteur, $id_lang);
        $name_transporteur = $obj_carrier->name;
        $this->logInFile('transporteur choisi : '.$name_transporteur);

        //gestion anti-report
        $order_ref = $this->getOrderRef($id_order);
        $invoice_ref = $this->getInvoiceRef($id_order);
        if (array_key_exists('date_export_order_doli', $order_ref)) {
            if ($order_ref['date_export_order_doli'] >= max($facture_ec->date_upd, $facture_ec->date_add)) {
                $export_order = false;
            }
        }
        if (array_key_exists('date_export_invoice_doli', $invoice_ref)) {
            if ($invoice_ref['date_export_invoice_doli'] >= max($facture_ec->date_upd, $facture_ec->date_add)) {
                $export_invoice = false;
            }
        }
        if (!$export_order && !$export_invoice) {
            return array('code'=>'OK', 'reason'=>'');
        }

        // on est dans un cas d'export
        //$idCustomer = $facture_ec->id_customer;
        $date_facture = $facture_ec->date_add;
        $products = $facture_ec->getProducts();   // on recupère les produits de chaque commande
        $module_reglement = $facture_ec->module;
        $mode_reglement = $facture_ec->payment;
        $this->logInFile('mode de reglement : '.print_r($mode_reglement, true));
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
        $obj_customer = new customer($facture_ec->id_customer);
        //creation de l'array du customer de la facture :
        $customer = array(
        'id_customer'=>$obj_customer->id,
        'email'=>$obj_customer->email,
        'firstname'=>$obj_customer->firstname,
        'lastname'=>$obj_customer->lastname
        );

        $this->logInFile('Produits de la commande '.$id_order.' : '.print_r($products, true));

        $product_tab = array();
        unset($product_tab);

        $i = 0;
        foreach ($products as $product) {
            $product_tab[$i]['product_id'] = $product['product_id'];
            $product_tab[$i]['product_attribute_id'] = $product['product_attribute_id'];
            $product_tab[$i]['product_quantity'] = $product['product_quantity'];
            $product_tab[$i]['tax_rate'] = $product['tax_rate'];
            //BOG
            if (_PS_VERSION_ < '1.5') {
                //finalbug $product_tab[$i]['total_price_tax_incl'] = $product['total_price'];
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
            $this->logInFile('retour WSAddOrder : '.$code_retour);
            if ($code_retour == 'OK') {
                $this->setOrderRef($id_order, $wsretour['id']);
            }
        }

        if ($export_invoice && (($code_retour = 'OK') || ($code_retour = 'DBL'))) {
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
                $invoice_ref['id_ext_invoice_doli']
            );
            $code_retour = $wsretour['result']['result_code'];
            if ($code_retour == 'OK') {
                $this->setInvoiceRef($id_order, $wsretour['id']);
            }
        }
        if (array_key_exists('result', $wsretour) && array_key_exists('result_label', $wsretour['result'])) {
            $reason = $wsretour['result']['result_label'];
        } else {
            $reason = '';
        }
        return array('code'=>$code_retour, 'reason'=>$reason);
    }

    /**
    *
    * methodes de communication Webservices
    *
    **/

    /** Methodes test  */
    public function WSVersion($wsurl)
    {
        $this->logInFile('--WSVersion--');
        $ws_dol_url = $wsurl.$this->ws_adress_dolibarr.'/pj_ws_version.php';
        $ws_method  = 'getVersions';
        $ns = 'http://www.Dolibarr.org/ns/';
        //$versionToReturn = '0';

        $soapclient = new p2dWebservices_client($ws_dol_url);
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
        $this->logInFile('Appel du getVersions : '.$ws_dol_url.' - '.print_r($parameters, true));
        $this->logInFile('retour du getVersions : '.print_r($result, true));

        return $result;
    }

    /** Methodes Clients */
    public function WSGetCustomer($client_ref)
    {
        $this->logInFile('--WSGetCustomer--');
        $wsurl = $this->ws_adress_value;
        $ws_dol_url = $wsurl.$this->ws_adress_dolibarr.'/pj_ws_clients.php';
        $ws_method  = 'getThirdParty';
        $ns = 'http://www.Dolibarr.org/ns/';

        $soapclient = new p2dWebservices_client($ws_dol_url);
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

        $this->logInFile('echange avec le '.$ws_method.' ('.$ws_dol_url.') ');
        $this->logInFile('requete:'.$soapclient->request);
        $this->logInFile('reponse du WS in tab:'.print_r($result_tab, true));

        return $result_tab;
    }

    public function WSModCustomer($thirdparty)
    {
        $this->logInFile('--WSModCustomer--');
        $wsurl = $this->ws_adress_value;
        $ws_dol_url = $wsurl.$this->ws_adress_dolibarr.'/pj_ws_clients.php';
        $ws_method  = 'updateThirdParty';
        $ns = 'http://www.Dolibarr.org/ns/';

        $soapclient = new p2dWebservices_client($ws_dol_url);
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

        $this->logInFile('echange avec le '.$ws_method.' ('.$ws_dol_url.') ');
        $this->logInFile('requete:'.$soapclient->request);
        $this->logInFile('reponse du WS mod_client in tab:'.print_r($result_tab, true));

        return $result_tab;
    }

    public function WSAddCustomer($enrich_customer)
    {
        $this->logInFile('--WSAddCustomer--');
        $wsurl = $this->ws_adress_value;
        $ws_dol_url = $wsurl.$this->ws_adress_dolibarr.'/pj_ws_clients.php';
        $ws_method  = 'createThirdParty';
        $ns = 'http://www.Dolibarr.org/ns/';

        $soapclient = new p2dWebservices_client($ws_dol_url);
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
        $this->logInFile('requete:'.$soapclient->request);
        $this->logInFile('reponse du WS in tab:'.print_r($result_tab, true));

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
        
        $soapclient = new p2dWebservices_client($ws_dol_url);
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
        $this->logInFile('requete:'.$soapclient->request);
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

        $soapclient = new p2dWebservices_client($ws_dol_url);
        if ($soapclient) {
            $soapclient->soap_defencoding = 'UTF-8';
            $soapclient->decodeUTF8(false);
        }
        $authentication = array('dolibarrkey'=>$this->ws_key_value, 'sourceapplication'=>'PRESTASHOP',
        'login'=>$this->ws_login_value, 'password'=>$this->ws_passwd_value, 'entity'=>'');

        $parameters = array('authentication'=>$authentication, 'product'=>$product);
        $result_tab = $soapclient->call($ws_method, $parameters, $ns, '');
        $this->logInFile('echange avec le '.$ws_method.' ('.$ws_dol_url.') ');
        $this->logInFile('requete:'.$soapclient->request);
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

        $soapclient = new p2dWebservices_client($ws_dol_url);
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
        $this->logInFile('requete:'.$soapclient->request);
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
        $doli_id
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

        //on fait un get invoice pour voir si l'on met pas un doublon
        $soapclient1 = new p2dWebservices_client($ws_dol_url_invoice);
        if ($soapclient1) {
            $soapclient1->soap_defencoding = 'UTF-8';
            $soapclient1->decodeUTF8(false);
        }
        $parameters1 = array('authentication'=>$authentication, 'id'=>$doli_id, 'ref'=>'', 'ref_ext'=>'');
        $result_get_order_tab = $soapclient1->call($ws_method_get_invoice, $parameters1, $ns, '');
        $result_get_order = $result_get_order_tab['result']['result_code'];

        $this->logInFile('echange avec le '.$ws_method_get_invoice.' ('.$ws_dol_url_invoice.') ');
        $this->logInFile('requete:'.$soapclient1->request);
        $this->logInFile('reponse du WS in tab:'.print_r($result_get_order_tab, true));

        if ($result_get_order == 'OK') {
            return $retour_dbl;
        } elseif ($result_get_order == 'KO') {
            return $retour_ko;
        }

        //si ce nest pas un doublon alors on chope l'id du client
        //dans Dolibarr et on le met à jour (si quelque chose a été modifié)
        //(il passe de prospect à client)
        $customers_to_set = array('0' =>$customer);

        //import client
        $result_client = $this->importClients($customers_to_set, $tmsp_start);
        if ($result_client['result'] != 'OK') {
            return $retour_ko;
        }

        $thirdparty_id = $result_client['id_ext_doli'];
        $this->logInFile('Client de la commande : '.$thirdparty_id);

        //si on a recup l'id du client on chope l'id des produits
        $lines = array();
        $line = array();
        unset($lines);
        //for ($i = 0; $product_tab[$i]['product_id'] != ''; $i++)
        for ($i = 0; array_key_exists($i, $product_tab) && $product_tab[$i]['product_id'] != ''; $i++) {
            $ref_product = $this->ws_trigram_value.$this->format($product_tab[$i]['product_id'], 10).
            $this->format($product_tab[$i]['product_attribute_id'], 10);

            //on exporte le produit
            $this->logInFile('produit dans invoice a setter ref : '.$ref_product.' : '.print_r($product_tab[$i], true));

            $default_language = (int)Configuration::get('PS_LANG_DEFAULT');
            $default_language = (int)Configuration::get('PS_LANG_DEFAULT');
            $testproduct = new product($product_tab[$i]['product_id'], true, $default_language, null, null);

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
            $this->logInFile('Produit de la commande : '.$id_product);

            //Prepration de l'objet WS pr ce produit
            $line['type'] = '0';       // Type of line (0=product, 1=service)
            $line['desc'] = 'Product';    // Description de la ligne
            //$line['fk_product'] = '';  //lien vers le produit, doublon avec product_id qui l'ecrase
            $line['unitprice'] = round($product_tab[$i]['unit_price_tax_excl'], 2);   //prix HT pour un seul produit
            $line['total_net'] = $product_tab[$i]['total_price_tax_excl'];  //prix HT tous produits
            $line['total_vat'] = ($product_tab[$i]['total_price_tax_incl']
            - $product_tab[$i]['total_price_tax_excl']); //total taxe tous produits
            $line['total'] = $product_tab[$i]['total_price_tax_incl']; //total TTC tous produits
            //$line['vat_rate'] = $resultGetProductTab['product']['vat_rate'];   //taux tva
            $line['vat_rate'] = $product_tab[$i]['tax_rate'];

            $line['qty'] = $product_tab[$i]['product_quantity'];   //nb de produits du même type
            $line['product_id'] = $id_product;   //lien vers le produit, id de dolibarr
            $lines[$i] = $line;
        }

        //Ajout du cout eventuel du transport
        if ($total_shipping_tax_incl != 0) {
            $line['type'] = '1';    // Type of line (0=product, 1=service)
            $line['desc'] = 'delivery : '.$name_transporteur;    // Description de la ligne
            $line['unitprice'] = $total_shipping_tax_excl;
            //prix HT pour un seul produit (arrondi à 2 chiffres après la virgule)
            $line['total_net'] = $total_shipping_tax_excl;  //prix HT tous produits
            $line['total_vat'] = $total_shipping_tax_incl - $total_shipping_tax_excl; //total taxe tous produits
            $line['total'] = $total_shipping_tax_incl; //total TTC tous produits
            $line['vat_rate'] = $carrier_tax_rate;   //taux tva
            $line['qty'] = '1';   //nb de produits du même type
            $line['product_id'] = '';   //lien vers le produit, id de dolibarr
            $lines[$i] = $line;
        }

        //Ajout des reductions
        if ($reductions != 0) {
            $i++;
            $reductions_ht = $reductions / (1 + $carrier_tax_rate / 100);
            $reductions_ht = round($reductions_ht * -1, 2);
            $reductions = $reductions * -1;
            $line['type'] = '1';    // Type of line (0=product, 1=service)
            $line['desc'] = 'Discount';    // Description de la ligne
            $line['unitprice'] = $reductions_ht;
            //prix HT pour un seul produit (arrondi à 2 chiffres après la virgule)
            $line['total_net'] = $reductions_ht;  //prix HT tous produits
            $line['total_vat'] = $reductions - $reductions_ht; //total taxe tous produits
            $line['total'] = $reductions; //total TTC tous produits
            $line['vat_rate'] = $carrier_tax_rate;   //taux tva
            $line['qty'] = '1';   //nb de produits du même type
            $line['product_id'] = '';   //lien vers le produit, id de dolibarr
            $lines[$i] = $line;
        }

        $this->logInFile('lines : '.print_r($lines, true));

        //Finalisation de l'objet WS invoice
        $invoice = array(
            'ref_ext'=>$ref_order,
            'thirdparty_id'=>$thirdparty_id,  //id de dolibarr
            'date'=>$date_facture,
            'type'=>'0',
            //0=Standard invoice, 1=Replacement invoice, 2=Credit note invoice, 3=Deposit invoice, 4=Proforma invoice
            'note_private'=>'imported by Prestashop',
            'note_public'=>'',
            'status'=>'1',        //! 0=draft, 1=validated (need to be paid), 2=classified paid partially
            //! 3=classified abandoned and no payment done (close_code = 'badcustomer', 'abandon' || 'replaced')
            'project_id'=>'', //c'est quoi ?
            'id_address_delivery' => $id_address_delivery,
            'id_address_invoice' => $id_address_invoice,
            'lines'=>$lines
        );

        $soapclient4 = new p2dWebservices_client($ws_dol_url_invoice);
        if ($soapclient4) {
            $soapclient4->soap_defencoding = 'UTF-8';
            $soapclient4->decodeUTF8(false);
        }
        $parameters4 = array('authentication'=>$authentication, 'invoice'=>$invoice);
        $result_create_invoice_tab = $soapclient4->call($ws_method_create_invoice, $parameters4, $ns, '');

        $this->logInFile('echange avec le '.$ws_method_create_invoice);
        $this->logInFile('requete:'.$soapclient4->request);
        $this->logInFile('reponse du WS in tab:'.print_r($result_create_invoice_tab['result'], true));

        return $result_create_invoice_tab;
    }

    /*******************
    // Methodes Commandes
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
        $doli_id = 0
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

        //on fait un get order pour voir si l'on met pas un doublon
        $soapclient1 = new p2dWebservices_client($ws_dol_url_order);
        if ($soapclient1) {
            $soapclient1->soap_defencoding = 'UTF-8';
            $soapclient1->decodeUTF8(false);
        }
        $parameters1 = array('authentication'=>$authentication, 'id'=>$doli_id, 'ref'=>'', 'ref_ext'=>'');
        $result_get_order_tab = $soapclient1->call($ws_method_get_order, $parameters1, $ns, '');
        $result_get_order = $result_get_order_tab['result']['result_code'];

        $this->logInFile('echange avec le '.$ws_method_get_order.' ('.$ws_dol_url_order.') ');
        $this->logInFile('requete:'.$soapclient1->request);
        $this->logInFile('reponse du WS in tab:'.print_r($result_get_order_tab, true));

        $result_create_order_tab = array();
        $result_update_order_statut_tab = array();
        if ($result_get_order == 'OK') {
            // validation de la commande si pas deja validée
            $parameters_status = array('authentication'=>$authentication, 'id'=>$doli_id, 'status'=>$statut);
            $result_update_order_statut_tab = $soapclient1->call(
                $ws_method_update_order_statut,
                $parameters_status,
                $ns,
                ''
            );
            $this->logInFile('echange avec le '.$ws_method_update_order_statut.' ('.$ws_dol_url_order.') ');
            $this->logInFile('requete:'.$soapclient1->request);
            $this->logInFile('reponse du WS in tab:'.print_r($result_update_order_statut_tab, true));
            if ($result_update_order_statut_tab['result']['result_code'] == 'KO') {
                return $retour_ko;
            }
            return $retour_dbl;
        } elseif ($result_get_order == 'KO') {
            return $retour_ko;
        } else {
            $customers_to_set = array('0' =>$customer);

            //import client
            $result_client = $this->importClients($customers_to_set, $tmsp_start);
            if ($result_client['result'] != 'OK') {
                return $retour_ko;
            }

            $thirdparty_id = $result_client['id_ext_doli'];
            $this->logInFile('Client de la commande : '.$thirdparty_id);

            //si on a recup l'id du client on chope l'id des produits
            $lines = array();
            unset($lines);
            $line = array();
            //for ($i = 0; $product_tab[$i]['product_id'] != ''; $i++)
            for ($i = 0; array_key_exists($i, $product_tab) && $product_tab[$i]['product_id'] != ''; $i++) {
                $ref_product = $this->ws_trigram_value.$this->format($product_tab[$i]['product_id'], 10).
                $this->format($product_tab[$i]['product_attribute_id'], 10);

                //on exporte le produit
                $this->logInFile(
                    'produit dans invoice a setter ref : '.$ref_product.' : '.print_r($product_tab[$i], true)
                );

                $default_language = (int)Configuration::get('PS_LANG_DEFAULT');
                $testproduct = new product($product_tab[$i]['product_id'], true, $default_language, null, null);
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
                $this->logInFile('Produit de la commande : '.$id_product);

                $line = array();
                $line['type'] = '0';       // Type of line (0=product, 1=service)
                $line['desc'] = 'Product';    // Description de la ligne
                //$line['fk_product'] = '';  //lien vers le produit, doublon avec product_id qui l'ecrase
                $line['unitprice'] = round($product_tab[$i]['unit_price_tax_excl'], 2); //prix HT pour un seul produit
                $line['total_net'] = $product_tab[$i]['total_price_tax_excl'];  //prix HT tous produits
                $line['total_vat'] = ($product_tab[$i]['total_price_tax_incl'] -
                $product_tab[$i]['total_price_tax_excl']); //total taxe tous produits
                $line['total'] = $product_tab[$i]['total_price_tax_incl']; //total TTC tous produits
                //$line['vat_rate'] = $resultGetProductTab['product']['vat_rate'];   //taux tva
                $line['vat_rate'] = $product_tab[$i]['tax_rate'];
                $line['qty'] = $product_tab[$i]['product_quantity'];   //nb de produits du même type
                $line['product_id'] = $id_product;   //lien vers le produit, id de dolibarr
                $lines[$i] = $line;
            }

            //Ajout du cout eventuel du transport
            if ($total_shipping_tax_incl != 0) {
                $line['type'] = '1';       // Type of line (0=product, 1=service)
                $line['desc'] = 'delivery : '.$name_transporteur;    // Description de la ligne
                $line['unitprice'] = $total_shipping_tax_excl;
                //prix HT pour un seul produit (arrondi à 2 chiffres après la virgule)
                $line['total_net'] = $total_shipping_tax_excl;  //prix HT tous produits
                $line['total_vat'] = $total_shipping_tax_incl - $total_shipping_tax_excl; //total taxe tous produits
                $line['total'] = $total_shipping_tax_incl; //total TTC tous produits
                $line['vat_rate'] = $carrier_tax_rate;   //taux tva
                $line['qty'] = '1';   //nb de produits du même type
                $line['product_id'] = '';   //lien vers le produit, id de dolibarr
                $lines[$i] = $line;
            }

            //Ajout des reductions
            if ($reductions != 0) {
                $i++;
                $reductions_ht = $reductions / (1 + $carrier_tax_rate / 100);
                $reductions_ht = round($reductions_ht * -1, 2);
                $reductions = $reductions * -1;
                $line['type'] = '1';       // Type of line (0=product, 1=service)
                $line['desc'] = 'Discount';    // Description de la ligne
                $line['unitprice'] = $reductions_ht;
                //prix HT pour un seul produit (arrondi à 2 chiffres après la virgule)
                $line['total_net'] = $reductions_ht;  //prix HT tous produits
                $line['total_vat'] = $reductions - $reductions_ht; //total taxe tous produits
                $line['total'] = $reductions; //total TTC tous produits
                $line['vat_rate'] = $carrier_tax_rate;   //taux tva
                $line['qty'] = '1';   //nb de produits du même type
                $line['product_id'] = '';   //lien vers le produit, id de dolibarr
                $lines[$i] = $line;
            }

            $this->logInFile('lines : '.print_r($lines, true));
            //on envoie la creation de facture
            $module_reglement = $this->noSpecialCharacterV3($module_reglement);
            $mode_reglement = $this->noSpecialCharacterV3($mode_reglement);

            $module_reglement_code = Tools::substr($module_reglement, 0, 6);
            
            $order = array(
            'ref_ext'=>$ref_order,
            'thirdparty_id'=>$thirdparty_id,  //id de dolibarr
            'date'=>$date_facture,
            'type'=>'0',
            //0=Standard invoice, 1=Replacement invoice, 2=Credit note invoice, 3=Deposit invoice, 4=Proforma invoice
            'note_private'=>'imported by Prestashop',
            'note_public'=> '',
            'status'=>$statut,
            'project_id'=> '', //c'est quoi ?
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

            $soapclient4 = new p2dWebservices_client($ws_dol_url_order);
            if ($soapclient4) {
                $soapclient4->soap_defencoding = 'UTF-8';
                $soapclient4->decodeUTF8(false);
            }
            $parameters4 = array('authentication'=>$authentication, 'order'=>$order);
            $result_create_order_tab = $soapclient4->call($ws_method_create_order, $parameters4, $ns, '');

            $this->logInFile('echange avec le '.$ws_method_create_order." : $ws_dol_url_order");
            $this->logInFile('requete:'.$soapclient4->request);
            $this->logInFile('reponse du WS in tab:'.print_r($result_create_order_tab['result'], true));
        }
        return $result_create_order_tab;
    }

    private function noSpecialCharacterV2($chaine)
    {
        $this->logInFile('--noSpecialCharacterV2--');
        //decode en utf8
        $chaine = utf8_decode($chaine);

        //  les caracètres speciaux (aures que lettres et chiffres en fait)
        $chaine = str_replace('<b>', '', $chaine);
        $chaine = str_replace('</b>', '', $chaine);
        $chaine = str_replace('<i>', '', $chaine);
        $chaine = str_replace('</i>', '', $chaine);
        $chaine = str_replace('<u>', '', $chaine);
        $chaine = str_replace('</u>', '', $chaine);
        $chaine = str_replace('<li>', '', $chaine);
        $chaine = str_replace('</li>', '', $chaine);
        $chaine = str_replace('<ul>', '', $chaine);
        $chaine = str_replace('</ul>', '', $chaine);
        $chaine = str_replace('<p>', '', $chaine);
        $chaine = str_replace('</p>', '', $chaine);
        $chaine = str_replace('<br />', '. ', $chaine);

        //  les accents
        $chaine = trim($chaine);
        $chaine_a = 'ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊ';
        $chaine_a .= 'ËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ';
        $chaine_b = 'aaaaaaaaaaaaooooooooooooeeeeeeeecciiiiiiiiuuuuuuuuynn';
        $chaine = strtr($chaine, $chaine_a, $chaine_b);
        //$chaine = preg_replace('/([^.a-z0-9]+)/i', ' ', $chaine);
        //$chaine = strtolower($chaine);
        $chaine = utf8_encode($chaine);
        return $chaine;
    }

    private function noSpecialCharacterV3($str_entree)
    {
        $this->logInFile('--noSpecialCharacterV3--');
        //  les caracètres speciaux (aures que lettres et chiffres en fait)
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
    *  METHODES DE HOOK
    *
    */

    /**
    * hook categories
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
    * hook clients
    */
    public function hookCreateAccount($params)
    {
        $this->logInFile('--hookCreateAccount--');
        $tmsp_start = time();
        if ($this->is_checked_synch_customer == 'true') {
            //création de l'array du customer à créer :
            $customer = array('0' => array(
            'id_customer'=>$params['newCustomer']->id,
            'email'=>$params['newCustomer']->email,
            'firstname'=>$params['newCustomer']->firstname,
            'lastname'=>$params['newCustomer']->lastname
            ));

            //import client
            $result = $this->importClients($customer, $tmsp_start);

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
    * hook commandes
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
                $this->logInFile('retour du hookupdateorderstatus : '.print_r($result, true));
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
    * hook confirmation commande pour facturation
    */

    public function hookOrderConfirmation($params)
    {
        $this->logInFile('--hookOrderConfirmation--');
        $tmsp_start = time();
        $order_id = $params['objOrder']->id;
        $this->logInFile('hookOrderConfirmation : '.print_r($params['objOrder'], true));
        if (($this->is_checked_synch_invoice == 'true') || ($this->is_checked_synch_order == 'true')) {
            $result = $this->importFacturesOrCommandes(
                $params['objOrder'],
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
    * hook produits
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
            $default_language = (int)Configuration::get('PS_LANG_DEFAULT');
            $testproduct = new product($product_id, true, $default_language, null, null);
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
            $default_language = (int)Configuration::get('PS_LANG_DEFAULT');
            $testproduct = new product($product_id, true, $default_language, null, null);
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

    /**Fonctions non gerees pour les versions de prestashop < 1.5*/

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
            //pas de description ds la langue par défaut => on prend la premiere qui vient
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

    public function setProductRef($id_product, $id_product_attribute = null, $id_ext_doli = 0)
    {
        $this->logInFile('--setProductRef--');
        if (($id_product_attribute) && ($id_product_attribute != 0)) {
            $query = '
            UPDATE        '._DB_PREFIX_.'product_attribute
            SET 
                date_export_doli = CURRENT_TIMESTAMP,
                id_ext_doli = '.(int)$id_ext_doli.' 
            WHERE 
                id_product_attribute = '.(int)$id_product_attribute;
        } else {
            $query = '
            UPDATE 
                    '._DB_PREFIX_.'product
            SET 
                date_export_doli = CURRENT_TIMESTAMP,
                id_ext_doli = '.(int)$id_ext_doli.' 
            WHERE 
                id_product = '.(int)$id_product;
        }

        $result = Db::getInstance()->execute($query);

        return $result[0];
    }

    public function getCustomerRef($id_customer)
    {
        $query = '
            SELECT     date_export_doli, id_ext_doli
            FROM 
                    '._DB_PREFIX_.'customer as c
            WHERE 
                c.id_customer = '.(int)$id_customer;

        $result = Db::getInstance()->executeS($query);

        return $result[0];
    }

    public function getOrderRef($id_order)
    {
        $query = '
            SELECT     date_export_order_doli, id_ext_order_doli
            FROM 
                    '._DB_PREFIX_.'orders as o
            WHERE 
                o.id_order = '.(int)$id_order;

        $result = Db::getInstance()->executeS($query);

        return $result[0];
    }

    public function getInvoiceRef($id_order)
    {
        $query = '
            SELECT     date_export_invoice_doli, id_ext_invoice_doli
            FROM 
                    '._DB_PREFIX_.'orders as o
            WHERE 
                o.id_order = '.(int)$id_order;

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
        $this->logInFile('-requete set order : '.$query);
        $this->logInFile('result requete : '.print_r($result, true));

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
        $this->logInFile('-requete set invoice : '.$query);
        $this->logInFile('result requete : '.print_r($result, true));

        return $result[0];
    }

    public function resetCustomers()
    {
        $query = '
        UPDATE        '._DB_PREFIX_.'customer
        SET 
            date_export_doli = NULL
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
        $this->logInFile('-requete reset product : '.$query);
        $this->logInFile('result requete : '.print_r($result, true));

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
            date_export_order_doli = NULL
        ';

        $result = Db::getInstance()->execute($query);
        return $result;
    }

    public function resetInvoices()
    {
        $query = '
        UPDATE        '._DB_PREFIX_.'orders
        SET 
            date_export_invoice_doli = NULL
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
        return $result[0]['id_image'];
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
    private function cleanCategory($cat_tab, $result = '')
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

    /**function qui retourne le nombre d'id inférieurs avec une ref identique*/
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

    /**function maj ref_doli*/
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

    /** function get statutdolib */
    private function getStatutDolibarr($statut)
    {
        $query = '
        SELECT id_order_state_doli FROM '._DB_PREFIX_.'order_state
            WHERE id_order_state = '.(int)$statut;
        $this->logInFile('->sql : '.$query);
        $result = Db::getInstance()->executeS($query);
        $this->logInFile('->result getStatutDolibarr : '.print_r($result, true));
        if (array_key_exists(0, $result)) {
            return $result[0]['id_order_state_doli'];
        }
        return 0;
    }

    /**
    * Get all available order statuses
    *
    * @param integer $id_lang Language id for status name
    * @return array Order statuses
    */
    public static function getOrderStates($id_lang)
    {
        $result = Db::getInstance()->executeS('
        SELECT *
        FROM `'._DB_PREFIX_.'order_state` os
        LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state` 
        AND osl.`id_lang` = '.(int)$id_lang.')
        WHERE deleted = 0
        ORDER BY `name` ASC');
        return $result;
    }
}
