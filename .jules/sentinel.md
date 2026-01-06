## 2024-05-23 - Content Security Policy (CSP) Implementation
**Vulnerability:** Missing `Content-Security-Policy` header allowing potential loading of malicious scripts/styles.
**Learning:** Implementing CSP in an existing Laravel application using Alpine.js and Tailwind CSS requires careful configuration. Strict CSP (disallowing `unsafe-inline` and `unsafe-eval`) breaks Alpine.js functionality as it relies on `eval()`-like behavior for reactivity and inline `x-data` attributes.
**Prevention:**
1.  Implement a CSP header in `App\Http\Middleware\SecurityHeaders`.
2.  Allow `unsafe-inline` and `unsafe-eval` for `script-src` to support Alpine.js.
3.  Allow `unsafe-inline` for `style-src` (common for Tailwind/Alpine).
4.  Whitelist external fonts (e.g., `fonts.bunny.net`) and images (e.g., `gravatar.com`).
5.  Use a test case (`tests/Feature/Security/SecurityHeadersTest.php`) to enforce the presence of the CSP header.
## 2024-05-23 - OIDC Email Verification Bypass
**Vulnerability:** The OIDC callback controller was trusting the email address returned by the identity provider without checking if the email was actually verified by that provider.
**Learning:** OIDC providers (like Google, Authentik) often return an `email_verified` claim. If this is `false`, it means the user on the other end hasn't proved they own that email. An attacker could create an account on the IdP with `victim@company.com`, not verify it, and then log in to our application. Since we link accounts by email (`link_existing_users`), this allows account takeover.
**Prevention:** Always check the `email_verified` claim in the OIDC `UserInfo` response. If it exists and is `false`, reject the login.
## 2024-05-23 - OIDC Email Verification Gap
**Vulnerability:** The OIDC authentication flow trusts the `email` claim from the provider without verifying `email_verified`. This allows an attacker to register `admin@target.com` on a permissive OIDC provider and takeover the admin account if linking is enabled.
**Learning:** "Deprioritized" security fixes can leave critical holes. Always verify `email_verified` when linking accounts by email.
**Prevention:** Explicitly check `$userInfo['email_verified']` before linking or creating users from OIDC.
## 2025-01-01 - User Mass Assignment Protection
**Vulnerability:** The `User` model included sensitive fields (`role`, `status`, `oauth_provider`, `oauth_id`) in the `$fillable` array.
**Learning:** Relying on controller-level validation (or lack of "all()" usage) to prevent Mass Assignment is fragile. If a developer accidentally uses `User::create($request->all())` or similar in a new feature, users could elevate their privileges.
**Prevention:** Remove sensitive fields from `$fillable`. Assign them manually in controllers or use `forceFill()`/`forceCreate()` where necessary (e.g., Admin user creation).
