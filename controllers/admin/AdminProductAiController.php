<?php
class AdminProductAiController extends ModuleAdminController {
    public function __construct() {
        parent::__construct();
        $this->bootstrap = true;
        $this->module = Module::getInstanceByName('productai');
    }

    public function ajaxProcessGenerateContent() {
        header('Content-Type: application/json');
        
        try {
            // Validate security token
            if (!Tools::getValue('secure_key') || 
                Tools::getValue('secure_key') !== Configuration::get('PRODUCTAI_SECURE_KEY')) {
                throw new Exception('Invalid security token');
            }

            $productId = (int)Tools::getValue('productId');
            $product = new Product($productId);
            if (!Validate::isLoadedObject($product)) {
                throw new Exception('Invalid product ID');
            }

            // Get first image
            $images = $product->getImages($this->context->language->id);
            if (empty($images)) {
                throw new Exception('No images found');
            }

            $image = new Image($images[0]['id_image']);
            $imagePath = _PS_PROD_IMG_DIR_.$image->getExistingImgPath().'.jpg';
            
            // Analyze image
            $output = shell_exec('python3 '.__DIR__.'/../../python/image_analyzer.py '.escapeshellarg($imagePath));
            $this->module->log("Image analysis: ".$output);
            $analysis = json_decode($output, true);
            
            if (!$analysis || $analysis['status'] !== 'success') {
                throw new Exception('Image analysis failed: '.($analysis['message'] ?? 'Unknown error'));
            }

            // Generate description
            $description = $this->generateDescription(
                $analysis['result'], 
                (int)Tools::getValue('langId')
            );

            // Save to database
            Db::getInstance()->insert('productai_data', [
                'id_product' => $productId,
                'content' => $description,
                'lang' => (int)Tools::getValue('langId'),
                'date_add' => date('Y-m-d H:i:s')
            ]);

            die(json_encode(['content' => $description]));

        } catch(Exception $e) {
            $this->module->log("Error: ".$e->getMessage());
            http_response_code(400);
            die(json_encode(['error' => $e->getMessage()]));
        }
    }

    private function generateDescription($prompt, $langId) {
        $apiKey = openssl_decrypt(
            Configuration::get('PRODUCTAI_HF_KEY'),
            'AES-256-CBC',
            _COOKIE_KEY_,
            0,
            substr(_COOKIE_KEY_, 0, 16)
        );

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => "https://api-inference.huggingface.co/models/google/flan-t5-xxl",
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $apiKey",
                "Content-Type: application/json"
            ],
            CURLOPT_POSTFIELDS => json_encode([
                "inputs" => "Generate product description in ".Language::getIsoById($langId)." about: $prompt",
                "parameters" => [
                    "max_length" => Configuration::get('PRODUCTAI_MAX_TOKENS'),
                    "temperature" => 0.7
                ]
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $data = json_decode($response, true);
        
        return $data[0]['generated_text'] ?? '';
    }
}