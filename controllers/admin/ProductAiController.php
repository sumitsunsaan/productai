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

            // Validate product ID
            $productId = (int)Tools::getValue('productId');
            if ($productId <= 0) {
                throw new Exception('Invalid product ID');
            }

            // Validate language ID
            $langId = (int)Tools::getValue('langId');
            if (!Language::getLanguage($langId)) {
                throw new Exception('Invalid language ID');
            }

            // Get product images
            $product = new Product($productId);
            $images = $product->getImages($this->context->language->id);
            if (empty($images)) {
                throw new Exception('No images found for this product');
            }

            // Process first image
            $image = new Image($images[0]['id_image']);
            $imagePath = _PS_PROD_IMG_DIR_.$image->getExistingImgPath().'.jpg';
            
            if (!file_exists($imagePath)) {
                throw new Exception('Image file not found');
            }

            // Analyze image
            $pythonScript = escapeshellarg(dirname(__FILE__).'/../../python/image_analyzer.py');
            $output = shell_exec("python3 $pythonScript ".escapeshellarg($imagePath)." 2>&1");
            $analysis = json_decode($output, true);

            if (!$analysis || $analysis['status'] !== 'success') {
                throw new Exception('Image analysis failed: '.($analysis['message'] ?? 'Unknown error'));
            }

            // Generate description
            $description = $this->generateDescription(
                $analysis['result'],
                $langId
            );

            // Save to database
            Db::getInstance()->insert('productai_data', [
                'id_product' => $productId,
                'content' => $description,
                'lang' => $langId,
                'date_add' => date('Y-m-d H:i:s')
            ]);

            die(json_encode(['content' => $description]));

        } catch (Exception $e) {
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

        if (empty($apiKey)) {
            throw new Exception('API key not configured');
        }

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
                    "max_length" => (int)Configuration::get('PRODUCTAI_MAX_TOKENS'),
                    "temperature" => 0.7,
                    "top_p" => 0.9
                ]
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            throw new Exception("API connection failed: $error");
        }

        if ($httpCode !== 200) {
            throw new Exception("API returned error code $httpCode");
        }

        $data = json_decode($response, true);
        if (!isset($data[0]['generated_text'])) {
            throw new Exception('Invalid API response format');
        }

        return strip_tags($data[0]['generated_text']);
    }
}