# 006 — Operator and release runbook

Application implementation is approved; production release is not. No development/production migration, admin account provisioning or production data operation was performed during this implementation. Apply incremental migrations and provision accounts only in the intended environment after review and deployment authorization. Never use migrate:fresh, database reset, Redis flush or volume deletion.

## Setup after application review

Feature 005 must be installed first. Reuse its FAQ/question tables, Redis connection, identity HMAC secret and explicit proxy configuration. Apply the normal incremental migration that adds default-false `users.is_chatbot_admin`; no existing user is granted automatically. Verify the existing users/sessions migrations are installed. Use database-backed Laravel sessions so account revocation/password reset can delete target sessions. Do not deploy with another session driver without revising and testing session invalidation.

Production requires HTTPS and appropriately configured Secure, HttpOnly and SameSite session cookies. Verify externally visible origin/scheme and explicit trusted proxy/header settings for both public and admin paths; never use wildcard proxy trust. The anonymous public chatbot remains stateless and keeps its existing Origin, 16 KiB and Redis contracts. Admin mutations are CSRF/session-authenticated, capped at 32 KiB, and use independent login/user quota domains. Redis or missing identity-key configuration fails closed.

Use individually authenticated restricted server/container console access. `CHATBOT_OPERATOR_USERS` is an allowlist of actual OS identities, not web users; app environment or a caller-supplied name is not authentication. The existing audit channel stores metadata only and needs restricted permissions, attribution, rotation and approved retention.

Authorized local Sail command examples (these were not run against development data):

```sh
./vendor/bin/sail artisan chatbot:admin:create
./vendor/bin/sail artisan chatbot:admin:grant <existing-user-id>
./vendor/bin/sail artisan chatbot:admin:revoke <existing-user-id>
./vendor/bin/sail artisan chatbot:admin:password-reset <existing-user-id>
```

Create/reset use protected password prompts and explicit confirmation. Never put passwords or FAQ content in shell arguments. The new account has no default password. Grant/revoke apply to existing records; revoke/reset remove database sessions. Browser registration, browser user management, password-recovery mail, SSO and MFA are outside approved scope. Obtain the security owner's authentication-scope approval before release. Sign in at `/admin/login`; administration is at `/admin/chatbot`.

## Prepared answer workflow

Review the oldest retained visitor questions, optionally filter by original classification, then select **Create FAQ draft**. Remove personal/sensitive content and provide a verified plain-text answer. Save produces an inactive FAQ. Open its detail page and explicitly review/activate it. Existing Unicode normalization and uniqueness include inactive FAQs; search text is a separate substring lookup, not chatbot matching.

Editing actual content deactivates the FAQ; unchanged content preserves state. Changes by another web admin or Artisan operator return a conflict. Preserve/copy the local unsaved draft if needed, then explicitly reload; there is no automatic overwrite or mutation retry. A network timeout can occur after a write commits: check the record/list before resubmitting. Public question classification remains original, while replay reply_source reflects today's answer.

FAQ deletion requires confirmation and a password confirmed through the protected endpoint. Collected rows remain, with deleted FAQ references cleared and original classification preserved. Question deletion is single-row, confirmed, password-verified and hard; it also removes that row's deduplication record. There is no export, mutable review flag, notes or bulk delete.

## Restoration records and retention

Production question deletion is disabled until `CHATBOT_ADMIN_RESTORATION_DIRECTORY` names an absolute private writable directory outside public/. In local/testing the default is `storage/app/private/chatbot-deletions`. Directory mode is 0700 and files 0600. The file writer flushes/fsyncs before deleting the DB row; write failure returns 503 and retains the question. Records contain only internal question ID, original UTC creation time and deletion time, never content/hash/request identifiers. No production storage durability is inferred from a local file test.

The deployment owner must provide persistent storage independent of database restores, restrict/read-audit access and protect it from web serving/telemetry. Keep instructions for the backup lifetime (005: <=30 days). The restricted `chatbot:admin:expire-restoration` command removes at most 500 expired metadata files per call and is scheduled daily without overlap. Monitor completion/backlog and run additional authorized batches when needed; do not let deletion records accumulate indefinitely. The existing question purge still uses a 90-day UTC cutoff, daily bounded batches and the normally-within-91-days live-data policy.

Before exposing a restored database: obtain the current protected deletion records from independent storage, reapply deletion only where internal ID and original creation timestamp both match, reapply the 005 age purge, validate restored FAQ activation states/access credentials and verify obsolete sessions cannot regain admin access. A restoration instruction can exist when a later DB transaction fails; treat it conservatively as the already-authorized deletion intent. Record the restore drill without copied question text. Never offer these records through an admin/public endpoint.

## Required release evidence

Record owner/date/configuration and actual observations for: incremental migration and provisioning; database-session revocation/reset; authentication scope; TLS/cookies; ingress/PHP limits and edge errors; proxy/header/direct-access topology; private MySQL/Redis; log/proxy/APM request redaction; audit/restoration persistence and permissions; scheduler alerts/expiry backlog; <=30-day backups and restore/deletion drill. Privacy approval must cover restricted web review, admin identity/session metadata retention, public notice and backup/deletion wording.

Browser review remains pending: login and CSRF network/session behavior, screenshots at 320×568/1440×900 and 200% zoom, keyboard and real screen-reader interaction, mobile menu/modal focus, wrapping/reduced motion, public chatbot appearance/avatars/speaker names/lifecycle. Vitest proves component contracts, not real visual layout or spoken output. Production/privacy and prior 005 manual gaps remain pending even when all automated checks pass.
