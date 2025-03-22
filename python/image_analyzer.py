import sys
import json
import logging
from pathlib import Path
from PIL import Image
from clip_interrogator import Config, Interrogator

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)

def analyze_image(image_path):
    try:
        if not Path(image_path).exists():
            raise FileNotFoundError(f"Image not found: {image_path}")

        config = Config()
        config.clip_model_name = "ViT-L-14/openai"
        ci = Interrogator(config)
        
        image = Image.open(image_path).convert('RGB')
        description = ci.interrogate(image)
        
        return {
            "status": "success",
            "result": description,
            "model": config.clip_model_name
        }
    except Exception as e:
        logging.error(f"Analysis failed: {str(e)}")
        return {"status": "error", "message": str(e)}

if __name__ == "__main__":
    try:
        if len(sys.argv) != 2:
            print(json.dumps({"status": "error", "message": "Usage: python image_analyzer.py <image_path>"}))
            sys.exit(1)
            
        result = analyze_image(sys.argv[1])
        print(json.dumps(result))
        
    except Exception as e:
        print(json.dumps({"status": "error", "message": f"Critical failure: {str(e)}"}))