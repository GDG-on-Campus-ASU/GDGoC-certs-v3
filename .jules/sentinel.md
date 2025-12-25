# Sentinel Journal
## 2025-12-25 - [Account Takeover via OIDC Linking]
**Vulnerability:** The application was automatically linking OIDC identities to local accounts based on matching email addresses without verifying if the email was actually verified by the Identity Provider.
**Learning:** Even if an email matches, it cannot be trusted for account linking unless the IDP explicitly vouches for its ownership via . Many IDPs allow account creation without immediate email verification.
**Prevention:** Always check  claim before linking existing accounts. Default to safe behavior (no link) if the claim is missing.
## 2025-12-25 - [Account Takeover via OIDC Linking]
**Vulnerability:** The application was automatically linking OIDC identities to local accounts based on matching email addresses without verifying if the email was actually verified by the Identity Provider.
**Learning:** Even if an email matches, it cannot be trusted for account linking unless the IDP explicitly vouches for its ownership via `email_verified: true`. Many IDPs allow account creation without immediate email verification.
**Prevention:** Always check `email_verified` claim before linking existing accounts. Default to safe behavior (no link) if the claim is missing.
## 2025-12-25 - [Account Takeover via OIDC Linking]
**Vulnerability:** The application was automatically linking OIDC identities to local accounts based on matching email addresses without verifying if the email was actually verified by the Identity Provider.
**Learning:** Even if an email matches, it cannot be trusted for account linking unless the IDP explicitly vouches for its ownership via `email_verified: true`. Many IDPs allow account creation without immediate email verification.
**Prevention:** Always check `email_verified` claim before linking existing accounts. Default to safe behavior (no link) if the claim is missing.
