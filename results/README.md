# Results — V1 vulnerability reproduction (Member B)

Sanitized evidence for the three V1 attacks. One file per scenario, each using
the record format the project requires: scenario + target version, attacker /
victim, input, observed security impact, defence applied, outcome, and a
legitimate-function check.

**Test environment (Member B):** Ubuntu 22.04.3 LTS (WSL2), Apache 2.4.52,
PHP 8.1.2, SQLite. (Member A's baseline: Ubuntu 26.04 / Apache 2.4.66 / PHP 8.5.)
Version difference did not change any V1 result.

**Repeatable harness:** [`tests/rce_test.sh`](../tests/rce_test.sh) and
[`tests/idor_test.sh`](../tests/idor_test.sh). XSS is verified manually (browser).
All take `[version] [base_url]`, default `v1` / `http://127.0.0.1:8080`, and exit
`0` = vulnerable, `1` = blocked/not implemented, `2` = harness error.

**Never commit:** session cookies, the SQLite DB, or uploaded runtime files.
Live cookie values in screenshots are redacted here.

| Scenario | Target | Outcome |
| --- | --- | --- |
| [RCE](rce-v1.md)  | v1 | VULNERABLE — server command execution as `www-data` |
| [XSS](xss-v1.md)  | v1 | VULNERABLE — cross-victim stored XSS; session cookie not `HttpOnly` |
| [IDOR](idor-v1.md) | v1 | VULNERABLE — authenticated user reads another user's private file |
