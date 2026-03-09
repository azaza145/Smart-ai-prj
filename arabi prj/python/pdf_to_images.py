#!/usr/bin/env python3
"""
Convert PDF pages to images (PNG/JPEG)
"""
import sys
import json
import os
from pathlib import Path

try:
    from pdf2image import convert_from_path
    PDF2IMAGE_AVAILABLE = True
except ImportError:
    PDF2IMAGE_AVAILABLE = False

try:
    from PIL import Image
    PIL_AVAILABLE = True
except ImportError:
    PIL_AVAILABLE = False

def pdf_to_images(pdf_path, output_dir, dpi=150, fmt='png'):
    """
    Convert PDF pages to images
    
    Args:
        pdf_path: Path to PDF file
        output_dir: Directory to save images
        dpi: Resolution (default 150)
        fmt: Format ('png' or 'jpeg')
    
    Returns:
        List of image file paths
    """
    if not os.path.exists(pdf_path):
        return {'error': f'PDF file not found: {pdf_path}'}
    
    if not PDF2IMAGE_AVAILABLE:
        return {'error': 'pdf2image library not available. Install with: pip install pdf2image'}
    
    try:
        os.makedirs(output_dir, exist_ok=True)
        
        # Convert PDF to images
        images = convert_from_path(pdf_path, dpi=dpi, fmt=fmt)
        
        image_paths = []
        base_name = Path(pdf_path).stem
        
        for i, image in enumerate(images):
            image_filename = f'{base_name}_page_{i+1}.{fmt}'
            image_path = os.path.join(output_dir, image_filename)
            image.save(image_path, fmt.upper())
            image_paths.append(image_path)
        
        return {
            'success': True,
            'images': image_paths,
            'count': len(image_paths)
        }
    except Exception as e:
        return {'error': str(e)}

if __name__ == '__main__':
    if len(sys.argv) < 3:
        print(json.dumps({'error': 'Usage: python pdf_to_images.py <pdf_path> <output_dir> [dpi] [format]'}))
        sys.exit(1)
    
    pdf_path = sys.argv[1]
    output_dir = sys.argv[2]
    dpi = int(sys.argv[3]) if len(sys.argv) > 3 else 150
    fmt = sys.argv[4] if len(sys.argv) > 4 else 'png'
    
    result = pdf_to_images(pdf_path, output_dir, dpi, fmt)
    print(json.dumps(result))
