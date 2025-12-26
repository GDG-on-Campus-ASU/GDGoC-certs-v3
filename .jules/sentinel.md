## 2024-05-23 - OIDC Email Verification Gap
**Vulnerability:** The OIDC authentication flow trusts the `email` claim from the provider without verifying `email_verified`. This allows an attacker to register `admin@target.com` on a permissive OIDC provider and takeover the admin account if linking is enabled.
**Learning:** "Deprioritized" security fixes can leave critical holes. Always verify `email_verified` when linking accounts by email.
**Prevention:** Explicitly check `$userInfo['email_verified']` before linking or creating users from OIDC.
