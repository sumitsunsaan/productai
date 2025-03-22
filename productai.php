<?php
if (!defined('_PS_VERSION_')) exit;

class ProductAI extends Module {
    public $secure_key;

    public function __construct() {
        $this->name = 'productai';
        $this->tab = 'administration';
        $this->version = '3.1.0';
        $this->author = 'Sumit Dahal | Hastakala Nepal';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->secure_key = Tools::encrypt($this->name);

        parent::__construct();

        $this->displayName = $this->l('AI Product Generator');
        $this->description = $this->l('Automatically generate product descriptions from images');
    }

    public function install() {
        return parent::install() 
            && $this->registerHook('displayAdminProductsExtra')
            && $this->installDB()
            && Configuration::updateValue('PRODUCTAI_HF_KEY', '')
            && Configuration::updateValue('PRODUCTAI_MAX_TOKENS', 500)
            && Configuration::updateValue('PRODUCTAI_SECURE_KEY', Tools::passwdGen(16));
    }

    private function installDB() {
        return Db::getInstance()->execute('
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'productai_data` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `id_product` INT NOT NULL,
                `content` TEXT NOT NULL,
                `lang` INT NOT NULL,
                `date_add` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                INDEX `product_lang` (`id_product`, `lang`)
            ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8;
        ');
    }
    public function getContent() {
        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminProductAiSettings')
        );
    }
    public function hookDisplayAdminProductsExtra($params) {
        $product = new Product((int)$params['id_product']);
        $this->context->smarty->assign([
            'product_id' => $product->id,
            'languages' => Language::getLanguages(false),
            'secure_key' => Configuration::get('PRODUCTAI_SECURE_KEY'),
            'link' => $this->context->link
        ]);
        return $this->display(__FILE__, 'views/templates/admin/product_tab.tpl');
    }
}