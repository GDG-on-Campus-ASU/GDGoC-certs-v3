## 2025-02-14 - PDF Injection via Certificate Data
**Vulnerability:** User-controlled data (e.g., recipient names) was directly substituted into HTML templates used for PDF generation without sanitization. This allowed attackers to inject HTML/JS tags (XSS) or potentially exploit the PDF renderer (SSRF/LFI).
**Learning:** `str_replace` is naive and does not offer context-aware escaping. When generating HTML for PDFs, all dynamic data must be treated as untrusted and escaped.
**Prevention:** Used `htmlspecialchars` to escape all replacement values before substitution. Added a test case `CertificatePdfInjectionTest` to verify that injected script tags are escaped in the HTML passed to the PDF generator.
