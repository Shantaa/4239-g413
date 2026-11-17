# RCE — V2 (extension and MIME validation)

- **Scenario / target version:** RCE / v2
- **Date:** 2026-11-17
- **Attacker:** arcie (authenticated user)
- **Victim:** the server (Apache/PHP process)
- **Input:** [`exploits/rce/v2.php`](../exploits/rce/v2.php) — the same fixed PHP marker payload used for the other RCE versions
- **Method:** [`tests/rce_test.sh`](../tests/rce_test.sh) `v2`

## Observed security impact

The upload was rejected with HTTP `403` before the file was stored:

```
Upload returned HTTP 403 (version likely not implemented / rejected).
RESULT: no RCE (upload not accepted for v2).
```

The test harness exited with code `1`, indicating that no server-side
execution was observed. The `LAB_RCE_MARKER_EXECUTED` marker did not appear,
and no uploaded PHP file was available to open.

For this payload, V2 rejected the file based on its `.php` extension. The
server-side MIME check is an additional defence in the same handler, but it was
not reached because extension validation rejected the file first.

## Root cause addressed

V2 adds validation in
[`public/common/upload_handler.php`](../public/common/upload_handler.php):

- The original filename extension is checked against an allowlist of
  `txt`, `png`, `jpg`, `jpeg`, and `pdf`.
- The uploaded content is inspected with PHP `finfo`.
- Only `text/plain`, `image/png`, `image/jpeg`, and `application/pdf` MIME types
  are accepted.
- A `.php` upload is rejected before `move_uploaded_file()` stores it.

The test uses the same payload as V1 so that the comparison measures the
defence rather than a changed attack input.

## Defence applied

V2 applies extension allowlisting and server-side MIME-type validation. This
prevents the test PHP file from being accepted as an upload. V2 does not yet
implement the separate V3 execution-isolation design.

## Outcome

**NO RCE OBSERVED.** `tests/rce_test.sh v2` exited `1` after the upload was
rejected with HTTP `403`.

This demonstrates that the V2 upload validation blocks this PHP upload; it
does not by itself prove that every possible file-upload or server
configuration weakness has been eliminated.

## Legitimate-function check

A benign control file such as
[`exploits/legitimate.txt`](../exploits/legitimate.txt) should remain
uploadable and retrievable in V2. This control should be run separately to
confirm that the validation blocks executable PHP without breaking ordinary
file sharing.
