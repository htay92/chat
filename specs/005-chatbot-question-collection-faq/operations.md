# Feature 005 operator and release runbook

Application implementation is approved. This runbook does not authorize production deployment or assert infrastructure/privacy acceptance.

## Configuration and deployment prerequisites

Review the new migrations and apply them through the authorized deployment process only after release approval. They have been exercised in test-only MySQL; no live database was migrated during implementation. Production uses MySQL, not the .env.example SQLite default. Configure the exact externally visible CHATBOT_ORIGIN, a dedicated securely provisioned CHATBOT_HMAC_KEY of at least 32 bytes, and the explicit chatbot Redis connection. Do not derive Origin from incoming Host headers. Empty Origin/key causes requests to fail safely. No AI provider/credentials exist.

Only set CHATBOT_TRUSTED_PROXIES to verified ingress IPs/CIDRs, with masks within 1–32 for IPv4 or 1–128 for IPv6; no wildcard or /0. The expected forwarded headers are For, Proto and Port, not Host. Verify header overwrite/sanitization and blocked direct backend access. Verify HTTPS, route-specific ingress caps, streamed-body behavior and any edge 413 formats. Application limit is 16,384 bytes read before parsing; PHP/ingress may reject first. Production compatibility remains unverified.

Supply CHATBOT_OPERATOR_USERS only for individually authenticated, authorized OS accounts. Shared sail/root accounts are inappropriate production identities. Console/container-exec permission must be restricted by OS/SSH/IAM policy; a string in a CLI argument is never authentication. The default production allowlist is empty. Local developers may explicitly authorize sail. Audit files use mode 0600; verify attributable platform/OS identity, owner, permissions, rotation and access controls in the actual deployment. API traffic does not create web sessions, but existing website session collection is outside this feature's storage policy.

## FAQ preparation and review

Invoke these through Sail locally or the authenticated authorized deployment console. Do not expose commands through HTTP.

```sh
./vendor/bin/sail artisan chatbot:faq:create
./vendor/bin/sail artisan chatbot:faq:update <faq-id>
./vendor/bin/sail artisan chatbot:faq:activate <faq-id>
./vendor/bin/sail artisan chatbot:faq:deactivate <faq-id>
./vendor/bin/sail artisan chatbot:faq:list
./vendor/bin/sail artisan chatbot:faq:show <faq-id>
./vendor/bin/sail artisan chatbot:questions:review --limit=50
./vendor/bin/sail artisan chatbot:questions:review --limit=50 --cursor=<returned-cursor>
```

Create/update prompt for content, preview it literally, and confirm. New FAQs and actual content edits are inactive until explicitly activated. Conflicting revisions/duplicate keys fail without overwrite. Repeated activation/deactivation or unchanged content is harmless. Listing is bounded to the first 50 FAQ entries; show/update work by ID. No FAQ deletion command is provided.

For multiline content, create/update supports --stdin with a protected JSON object containing only question and answer (at most 32 KiB total). Redirect a restricted local file or protected input stream; do not interpolate content into shell command text. Confirmation comes from the controlling /dev/tty, separate from piped content; without a controlling terminal the operation fails without a write. Actual terminal behavior must still be checked manually on the deployment host. Remove any temporary content file using the operator's approved secure handling process; never add it to Git/logs.

Review is read-only, oldest-first original misses, 50 rows maximum and an explicit next cursor. It displays internal row ID, UTC time and escaped text only. Review/FAQ creation never marks collected rows answered/reviewed or extends age. Avoid copying personal information into FAQs. A new active FAQ can answer a replay whose original status remains unanswered; use reply_source for current response semantics. Originally answered rows replay only their recorded FAQ reference.

The optional ChatbotDemoFaqSeeder is never included in DatabaseSeeder and never automatically run in production. It creates inactive demo content, preserving existing keys. Operators must review/activate it explicitly if used.

## Retention and early deletion

The daily UTC schedule invokes chatbot:questions:purge with overlap prevention, deleting cutoff-eligible rows in batches of 500. Application scheduling/log contracts are tested; actual cron/service execution is a production gate. Monitor last successful chatbot_purge_completed time/count and alert on missing daily success or chatbot_purge_failed/chatbot_scheduled_purge_failed. Healthy scheduling deletes within 91 days; failed runs can extend that interval and must be addressed.

chatbot:questions:delete-session accepts a UUID through hidden protected input and an explicit confirmation; no UUID argument or public deletion API exists. It deletes only that session's HMAC matches and removes deduplication. It does not authenticate a visitor or promise a public request procedure. Maintain restricted restore-time deletion instructions outside application/access/audit logs; do not store raw tokens in ordinary logs. Verify backup expiration within 30 days and reapply those instructions plus age purge before restored service is accessible. Operator confirmation, access controls, logging/APM redaction and a restore drill remain production gates.

For HMAC rotation, pause collection and keep the previous dedicated key available under restricted maintenance access until its retained records/backups have been handled. Coordinate tab-ID expiry and early-deletion/replay behavior; do not simply replace the key while retained old hashes still need lookup. A multi-key lookup protocol is not implemented by this feature.

## Isolated verification

```sh
SAIL_FILES=compose.chatbot-tests.yaml APP_SERVICE=chatbot-test-runner ./vendor/bin/sail up -d
SAIL_FILES=compose.chatbot-tests.yaml APP_SERVICE=chatbot-test-runner ./vendor/bin/sail artisan test
./vendor/bin/sail npm run test:frontend
./vendor/bin/sail npm run typecheck
./vendor/bin/sail npm run build
./vendor/bin/sail php vendor/bin/pint --test
```

The test runner uses a separate internal network, dedicated tmpfs MySQL/Redis services, no published ports/development data volumes and test-only credentials. Every run creates a guarded random schema/key namespace. The least-privilege MySQL account has no DROP/GRANT privileges. Inherited socket, URL, host, driver, port, credentials, split-connection and cached-config overrides fail closed before connections. Test rows roll back or are deleted only within the owned schema; no Redis flush or broad shared cleanup occurs. Old test schemas are temporary tmpfs data, not persistent development volumes. Do not use --remove-orphans, down -v, migrate:fresh, FLUSHALL/FLUSHDB or shared cache clearing. The existing development Compose services are never removed by these commands.

Plain Sail artisan test outside the isolated runner skips real-service chatbot tests; it is not equivalent acceptance evidence. The full isolated run includes a real ~60-second Redis reset observation and forked separate-connection MySQL/Redis concurrency tests.

## Remaining release gates

Browser measurements and screenshots for required viewports/zoom, mobile keyboard/safe areas, actual screen-reader announcements and deployed Network/Storage checks remain pending. CSS, avatar geometry and speaker structure are preserved in code; DOM tests do not establish visual or spoken acceptance.

Production owner must verify authenticated operator access/audit, MySQL/Redis/TLS access, real Origin/proxy/ingress behavior, sanitized logs/APM, daily scheduler/alerts and backup expiry/restore deletion. Privacy owner must approve the full collection/retention/deletion policy and publication of the notice. No live question collection release is claimed until these gates are satisfied.
