<?php
class AdminProductAiSettingsController extends ModuleAdminController {
    public function __construct() {
        parent::__construct();
        $this->bootstrap = true;
        $this->module = Module::getInstanceByName('productai');
    }

    public function initContent() {
        parent::initContent();
        $this->context->smarty->assign('module', $this->module);
        
        $helper = $this->initForm();
        $this->content = $helper->generateForm([$this->getFormStructure()]);
        $this->context->smarty->assign('content', $this->content);
        $this->setTemplate('config.tpl');
    }

    protected function initForm() {
        $helper = new HelperForm();
        $helper->module = $this->module;
        $helper->name_controller = $this->controller_name;
        $helper->token = Tools::getAdminTokenLite('AdminProductAiSettings');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->module->name;
        $helper->title = $this->module->displayName;
        $helper->submit_action = 'submitSettings';
        $helper->fields_value = $this->getConfigValues();
        return $helper;
    }

    protected function getFormStructure() {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->l('API Settings'),
                    'icon' => 'icon-cogs'
                ],
                'input' => [
                    [
                        'type' => 'password',
                        'label' => $this->l('Hugging Face Token'),
                        'name' => 'PRODUCTAI_HF_KEY',
                        'required' => true,
                        'desc' => $this->l('Get from huggingface.co/settings/tokens')
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->l('Max Response Length'),
                        'name' => 'PRODUCTAI_MAX_TOKENS',
                        'required' => true,
                        'desc' => $this->l('300-700 recommended'),
                        'validate' => 'isUnsignedInt'
                    ]
                ],
                'submit' => [
                    'title' => $this->l('Save')
                ]
            ]
        ];
    }

    protected function getConfigValues() {
        return [
            'PRODUCTAI_HF_KEY' => Configuration::get('PRODUCTAI_HF_KEY'),
            'PRODUCTAI_MAX_TOKENS' => Configuration::get('PRODUCTAI_MAX_TOKENS')
        ];
    }

    public function postProcess() {
        if (Tools::isSubmit('submitSettings')) {
            $this->saveSettings();
        }
        parent::postProcess();
    }

    protected function saveSettings() {
        $apiKey = Tools::getValue('PRODUCTAI_HF_KEY');
        $maxTokens = (int)Tools::getValue('PRODUCTAI_MAX_TOKENS');

        if (empty($apiKey)) {
            $this->errors[] = $this->l('API key required');
        }

        if ($maxTokens < 100 || $maxTokens > 1000) {
            $this->errors[] = $this->l('Max tokens must be 100-1000');
        }

        if (empty($this->errors)) {
            $encryptedKey = openssl_encrypt(
                $apiKey,
                'AES-256-CBC',
                _COOKIE_KEY_,
                0,
                substr(_COOKIE_KEY_, 0, 16)
            );
            
            Configuration::updateValue('PRODUCTAI_HF_KEY', $encryptedKey);
            Configuration::updateValue('PRODUCTAI_MAX_TOKENS', $maxTokens);
            $this->confirmations[] = $this->l('Settings saved');
        }
    }
}