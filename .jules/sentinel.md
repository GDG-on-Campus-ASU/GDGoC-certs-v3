## 2024-05-23 - OIDC Email Verification Bypass
**Vulnerability:** The OIDC callback controller was trusting the email address returned by the identity provider without checking if the email was actually verified by that provider.
**Learning:** OIDC providers (like Google, Authentik) often return an `email_verified` claim. If this is `false`, it means the user on the other end hasn't proved they own that email. An attacker could create an account on the IdP with `victim@company.com`, not verify it, and then log in to our application. Since we link accounts by email (`link_existing_users`), this allows account takeover.
**Prevention:** Always check the `email_verified` claim in the OIDC `UserInfo` response. If it exists and is `false`, reject the login.
## 2024-05-23 - OIDC Email Verification Gap
**Vulnerability:** The OIDC authentication flow trusts the `email` claim from the provider without verifying `email_verified`. This allows an attacker to register `admin@target.com` on a permissive OIDC provider and takeover the admin account if linking is enabled.
**Learning:** "Deprioritized" security fixes can leave critical holes. Always verify `email_verified` when linking accounts by email.
**Prevention:** Explicitly check `$userInfo['email_verified']` before linking or creating users from OIDC.
