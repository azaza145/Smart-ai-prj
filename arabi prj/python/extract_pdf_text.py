#!/usr/bin/env python3
"""
Extract text from PDF and map to structured JSON schema.

Pipeline:
  1. Extract text from PDF: PyMuPDF -> pdfplumber -> pdfminer -> pypdf -> OCR
  2. Text quality check: try next extractor if result is empty or garbled
  3. Detect sections (FORMATION, EXPÉRIENCE, COMPÉTENCES, LANGUES, etc.)
  4. Regex for easy fields: email, phone, LinkedIn, dates
  5. Rule-based parse: education, experience, skills (keywords)
  6. Optional AI/NLP (LLM): job title, summary, education/experience entries, skills
  7. Normalize into JSON: { "text", "source", "structured", "parsed" }

  - structured: full schema (personal_info, experience, education, skills, languages, hobbies)
  - parsed: flat dict for PHP Candidate::fillEmptyFromParsedCv

  Env for AI step: CV_LLM_API_URL, CV_LLM_API_KEY (optional), CV_LLM_MODEL, CV_AI_ENABLED=1
"""
import argparse
import json
import os
import sys

_script_dir = os.path.dirname(os.path.abspath(__file__))
if _script_dir not in sys.path:
    sys.path.insert(0, _script_dir)

try:
    from cv_parser import parse as parse_structured
    from cv_parser import to_flat_parsed
except ImportError:
    parse_structured = None
    to_flat_parsed = None

try:
    from cv_ai_extract import extract_with_ai, merge_structured
except ImportError:
    extract_with_ai = None
    merge_structured = None


def is_valid_text(text: str) -> bool:
    """Check if extracted text has meaningful content."""
    text = text.strip()
    if len(text) < 100:
        return False
    words = text.split()
    if len(words) < 10:
        return False
    # Check for garbled text (high ratio of non-alphanumeric chars)
    alpha_count = sum(1 for c in text if c.isalpha())
    if alpha_count / max(len(text), 1) < 0.3:
        return False
    return True


def extract_text_pymupdf(path: str) -> str:
    """Direct text extraction via PyMuPDF (fitz). Fast and clean for text PDFs. Handles multi-column."""
    try:
        import fitz
    except ImportError:
        return ""
    try:
        doc = fitz.open(path)
        parts = []
        for page in doc:
            # sort=True gives reading-order text (handles columns)
            text = page.get_text("text", sort=True)
            if text:
                parts.append(text.strip())
        doc.close()
        result = "\n\n".join(parts).strip()
        return result if is_valid_text(result) else ""
    except Exception:
        return ""


def extract_text_pdfplumber(path: str) -> str:
    """Direct text extraction via pdfplumber. Good layout preservation."""
    try:
        import pdfplumber
    except ImportError:
        return ""
    try:
        parts = []
        with pdfplumber.open(path) as pdf:
            for page in pdf.pages:
                t = page.extract_text()
                if t:
                    parts.append(t)
        result = "\n\n".join(parts).strip()
        return result if is_valid_text(result) else ""
    except Exception:
        return ""


def extract_text_pdfminer(path: str) -> str:
    """Fallback: pdfminer.six."""
    try:
        from pdfminer.high_level import extract_text as pdfminer_extract
        result = (pdfminer_extract(path) or "").strip()
        return result if is_valid_text(result) else ""
    except ImportError:
        pass
    except Exception:
        pass
    return ""


def extract_text_pypdf(path: str) -> str:
    """Fallback: pypdf."""
    try:
        from pypdf import PdfReader
        reader = PdfReader(path)
        result = "\n".join((p.extract_text() or "" for p in reader.pages)).strip()
        return result if is_valid_text(result) else ""
    except ImportError:
        pass
    except Exception:
        pass
    return ""


def extract_text_ocr_fallback(path: str) -> str:
    """OCR fallback for image-only/scanned PDFs using pytesseract + pdf2image."""
    try:
        from pdf2image import convert_from_path
        import pytesseract
        pages = convert_from_path(path, dpi=200)
        texts = []
        for page_img in pages:
            text = pytesseract.image_to_string(page_img, lang='fra+eng')
            if text.strip():
                texts.append(text.strip())
        result = "\n\n".join(texts).strip()
        return result if is_valid_text(result) else ""
    except Exception:
        return ""


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--path", required=True, help="Path to PDF file")
    args = parser.parse_args()
    path = args.path.strip().strip("'\"")
    source = "none"
    text = ""

    extractors = [
        ("pymupdf", extract_text_pymupdf),
        ("pdfplumber", extract_text_pdfplumber),
        ("pdfminer", extract_text_pdfminer),
        ("pypdf", extract_text_pypdf),
        ("ocr", extract_text_ocr_fallback),
    ]

    try:
        for src_name, extractor in extractors:
            try:
                text = extractor(path)
                if text and is_valid_text(text):
                    source = src_name
                    break
            except Exception:
                continue

        text = (text or "").strip()
        out = {"text": text or "", "source": source}

        if parse_structured and to_flat_parsed and text:
            try:
                structured = parse_structured(text)
                # Optional AI/NLP step: refine job title, summary, education, experience, skills
                if extract_with_ai and merge_structured:
                    ai_result = extract_with_ai(text)
                    if ai_result:
                        structured = merge_structured(structured, ai_result)
                out["structured"] = structured
                out["parsed"] = to_flat_parsed(structured)
            except Exception:
                out["structured"] = {}
                out["parsed"] = {}
        else:
            out["structured"] = {}
            out["parsed"] = {}

    except Exception as e:
        out = {"text": "", "source": "none", "structured": {}, "parsed": {}, "error": str(e)}

    print(json.dumps(out, ensure_ascii=False))


if __name__ == "__main__":
    main()
