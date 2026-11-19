# Results — Versioned vulnerability reproduction

Sanitized evidence for the three scenarios across V1, V2 and V3. One file per
scenario and version uses
the record format the project requires: scenario + target version, attacker /
victim, input, observed security impact, defence applied, outcome, and a
legitimate-function check.

**Test environment (Member B):** Ubuntu 22.04.3 LTS (WSL2), Apache 2.4.52,
PHP 8.1.2, SQLite. (Member A's baseline: Ubuntu 26.04 / Apache 2.4.66 / PHP 8.5.)
Version difference did not change any V1 result.

**Repeatable harness:** [`tests/rce_test.sh`](../tests/rce_test.sh) and
[`tests/idor_test.sh`](../tests/idor_test.sh). XSS is verified manually
(browser). The RCE and IDOR harnesses take `[version] [base_url]`, default
`v1` / `http://127.0.0.1:8080`. Exit `0` means the tested violation occurred,
`1` means the violation was blocked or not observed, and `2` means a harness
error.

**Never commit:** session cookies, the SQLite DB, or uploaded runtime files.
Live cookie values in screenshots are redacted here.

| Scenario | Target | Outcome |
| --- | --- | --- |
| [RCE](rce-v1.md)  | v1 | VULNERABLE — server command execution as `www-data` |
| [XSS](xss-v1.md)  | v1 | VULNERABLE — cross-victim stored XSS; session cookie not `HttpOnly` |
| [IDOR](idor-v1.md) | v1 | VULNERABLE — authenticated user reads another user's private file |
| [RCE](rce-v2.md) | v2 | BLOCKED — PHP upload rejected by extension/MIME validation |
| [XSS](xss-v2.md) | v2 | BLOCKED — active SVG delivered as an attachment |
| [IDOR](idor-v2.md) | v2 | VULNERABLE BY DESIGN — opaque token without ownership authorization |
| [RCE](rce-v3.md) | v3 | BLOCKED — PHP stored privately and served as data |
| [XSS](xss-v3.md) | v3 | BLOCKED — CSP prevents SVG script execution |
| [IDOR](idor-v3.md) | v3 | BLOCKED — ownership authorization denies unauthorized access |
| [Combined](patched.md) | patched | IMPLEMENTED / PENDING LIVE TESTS — RCE, XSS, and IDOR protections are combined; XSS remains manual and browser evidence is not yet claimed |
