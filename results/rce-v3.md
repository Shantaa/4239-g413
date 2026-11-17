# RCE — V3 (non-executable private storage)

- **Scenario / target version:** RCE / v3
- **Date:** 2026-11-17
- **Attacker:** arcie (authenticated user)
- **Victim:** the server (Apache/PHP process)
- **Input:** [`exploits/rce/v3.php`](../exploits/rce/v3.php) — the same fixed PHP marker payload used for the other RCE versions
- **Method:** [`tests/rce_test.sh`](../tests/rce_test.sh) `v3`

## Observed security impact

The PHP upload was accepted and stored outside Apache's public document root.
Opening the generated download link returned the PHP source as data:

```php
<?php
echo "LAB_RCE_MARKER_EXECUTED\n";
```

The source was not executed. No `whoami` output was produced, and the
response did not begin with the execution marker as a server-generated line.

The test reported:

```
RESULT: no RCE — the file was served as data, not executed.
```

## Root cause addressed

- V3 stores uploads under `data/private/rce/v3/`, outside the Apache document
  root.
- The uploaded filename is not used as a directly executable public URL.
- [`public/scenarios/rce/v3/download.php`](../public/scenarios/rce/v3/download.php)
  reads the private file and serves it with a neutral content type and
  attachment disposition.
- Apache therefore cannot dispatch the uploaded `.php` file to the PHP
  interpreter as a public script.

The same payload is used as the V1 and V2 comparison input so that the result
measures storage and delivery isolation rather than a changed attack.

## Defence applied

V3 applies non-executable private storage and controlled file delivery:

```text
data/private/rce/v3/
```

Files are returned with `Content-Disposition: attachment`,
`application/octet-stream`, and `X-Content-Type-Options: nosniff`.

## Outcome

**PROTECTED AGAINST THIS RCE PATH.** `tests/rce_test.sh v3` completed without
server-side execution and returned the non-execution result.

The PHP source appearing in the `curl` response is the downloaded file
content, not evidence of PHP execution.

## Legitimate-function check

A legitimate file remains retrievable through the V3 download endpoint.
Ordinary file access works, while uploaded PHP content is delivered as data
and is not executed by Apache.
