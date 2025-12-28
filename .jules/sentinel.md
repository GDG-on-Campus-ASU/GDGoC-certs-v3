## 2024-05-23 - Content Security Policy (CSP) Implementation
**Vulnerability:** Missing `Content-Security-Policy` header allowing potential loading of malicious scripts/styles.
**Learning:** Implementing CSP in an existing Laravel application using Alpine.js and Tailwind CSS requires careful configuration. Strict CSP (disallowing `unsafe-inline` and `unsafe-eval`) breaks Alpine.js functionality as it relies on `eval()`-like behavior for reactivity and inline `x-data` attributes.
**Prevention:**
1.  Implement a CSP header in `App\Http\Middleware\SecurityHeaders`.
2.  Allow `unsafe-inline` and `unsafe-eval` for `script-src` to support Alpine.js.
3.  Allow `unsafe-inline` for `style-src` (common for Tailwind/Alpine).
4.  Whitelist external fonts (e.g., `fonts.bunny.net`) and images (e.g., `gravatar.com`).
5.  Use a test case (`tests/Feature/Security/SecurityHeadersTest.php`) to enforce the presence of the CSP header.
## 2024-05-23 - OIDC Email Verification Gap
**Vulnerability:** The OIDC authentication flow trusts the `email` claim from the provider without verifying `email_verified`. This allows an attacker to register `admin@target.com` on a permissive OIDC provider and takeover the admin account if linking is enabled.
**Learning:** "Deprioritized" security fixes can leave critical holes. Always verify `email_verified` when linking accounts by email.
**Prevention:** Explicitly check `$userInfo['email_verified']` before linking or creating users from OIDC.
