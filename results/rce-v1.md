# RCE — V1 (vulnerable baseline)

- **Scenario / target version:** RCE / v1
- **Date:** 2026-11-05
- **Attacker:** arcie (any authenticated user)
- **Victim:** the server (Apache/PHP process)
- **Input:** [`exploits/rce/v1.php`](../exploits/rce/v1.php) — prints a marker and runs `whoami`
- **Method:** `tests/rce_test.sh v1`

## Observed security impact

The uploaded `.php` file was **executed by the server** when opened, not served
as text:

```
LAB_RCE_MARKER_EXECUTED
Command: whoami
Result: www-data
```

`www-data` is the Apache/PHP process user, so an authenticated attacker achieves
arbitrary server-side command execution.

## Root cause

- `public/common/upload_handler.php` keeps the original extension and stores the
  file under `public/scenarios/rce/v1/uploads/` — inside the web root.
- Apache (`setup/cs4239-lab.conf`, `mod_php`) executes any `.php` under the
  document root, so the upload becomes a live script at a predictable URL.
- No file-type/extension validation, and uploads are not kept out of the
  executable web root.

## Defence applied

None (vulnerable baseline). Planned: V2 file-type validation, V3 non-executable
storage outside the web root.

## Outcome

**VULNERABLE.** `tests/rce_test.sh v1` exited `0`.

## Legitimate-function check

Uploading a benign file (`exploits/legitimate.txt`) and opening it still returns
its contents normally — ordinary file use is unaffected.
