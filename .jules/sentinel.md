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
## 2024-05-24 - Queue Worker State Leak via Config::set
**Vulnerability:** Modification of global configuration (e.g., `Config::set('mail.from.address', ...)`) within a queue job persists across subsequent jobs processed by the same worker process. Additionally, the Mail Manager caches mailer instances, so updating `mail.mailers.config` is insufficient without purging the cached instance.
**Learning:** Laravel queue workers boot the application once and process jobs in a loop. Global state changes like `Config::set` are not automatically reset between jobs. This can lead to information leaks or spoofing (e.g., one tenant's SMTP "From" address being used by another tenant, or credentials being reused).
**Prevention:** Avoid `Config::set` for per-job configuration where possible. Use instance-specific configuration (e.g., `$mailable->from()`). When modifying mailer config, always call `Mail::purge('mailer_name')` to ensure the new configuration is applied.
