#!/usr/bin/env python3
"""
Extract text from PDF and map to structured JSON schema.

Pipeline:
  1. Extract text from PDF: PyMuPDF -> pdfplumber -> pdfminer -> pypdf
  2. Detect sections (FORMATION, EXPÉRIENCE, COMPÉTENCES, LANGUES, etc.)
  3. Regex for easy fields: email, phone, LinkedIn, dates
  4. Rule-based parse: education, experience, skills (keywords)
  5. Optional AI/NLP (LLM): job title, summary, education/experience entries, skills
  6. Normalize into JSON: { "text", "source", "structured", "parsed" }

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


def extract_text_pymupdf(path: str) -> str:
    """Direct text extraction via PyMuPDF (fitz). Fast and clean for text PDFs."""
    try:
        import fitz
    except ImportError:
        return ""
    try:
        doc = fitz.open(path)
        parts = []
        for page in doc:
            parts.append(page.get_text())
        doc.close()
        return "\n\n".join(parts).strip()
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
        return "\n\n".join(parts).strip()
    except Exception:
        return ""


def extract_text_pdfminer(path: str) -> str:
    """Fallback: pdfminer.six."""
    try:
        from pdfminer.high_level import extract_text as pdfminer_extract
        return (pdfminer_extract(path) or "").strip()
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
        return "\n".join((p.extract_text() or "" for p in reader.pages)).strip()
    except ImportError:
        pass
    except Exception:
        pass
    return ""


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--path", required=True, help="Path to PDF file")
    args = parser.parse_args()
    path = args.path.strip().strip("'\"")
    source = "none"
    text = ""

    try:
        text = extract_text_pymupdf(path)
        if text:
            source = "pymupdf"
        if not text:
            text = extract_text_pdfplumber(path)
            if text:
                source = "pdfplumber"
        if not text:
            text = extract_text_pdfminer(path)
            if text:
                source = "pdfminer"
        if not text:
            text = extract_text_pypdf(path)
            if text:
                source = "pypdf"

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
