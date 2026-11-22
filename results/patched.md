# Patched — combined hardened RCE, XSS, and IDOR application

- **Scenario / target version:** RCE, XSS, and IDOR / patched
- **Date:** 2026-11-19
- **Attacker:** arcie for IDOR; crosley for the XSS manual payload; authenticated user for RCE
- **Victim:** Igor's private document (IDOR), server for RCE, browser/application context for XSS
- **Input:** [`exploits/rce/patched.php`](../exploits/rce/patched.php), [`exploits/xss/patched.svg`](../exploits/xss/patched.svg), [`exploits/idor/private-igor.txt`](../exploits/idor/private-igor.txt) plus [`exploits/idor/patched.py`](../exploits/idor/patched.py)
- **Method:** Prepared repeatable harnesses: [`tests/rce_test.sh`](../tests/rce_test.sh) `patched`, [`tests/idor_test.sh`](../tests/idor_test.sh) `patched`; XSS requires manual browser verification through `/scenarios/patched/xss/preview.php`

## Implemented defenses

### RCE

- Upload validation uses a strict extension allowlist and `finfo` MIME allowlist.
- RCE uploads are moved outside the web root under `data/private/rce/patched/`.
- The download route requires the authenticated owner and serves content as
  `application/octet-stream` with `Content-Disposition: attachment`,
  `X-Content-Type-Options: nosniff`, and an exact filename record check.

### XSS

- XSS uploads are limited to SVG/HTML/text extensions and matching detected MIME types.
- Content is stored under `data/private/xss/patched/` and is not exposed through a public
  uploads directory.
- The preview route requires the authenticated owner and serves private SVG/HTML
  content only through a controlled response. It adds an inline-script-blocking
  CSP, `sandbox`, `X-Content-Type-Options: nosniff`, and `X-Frame-Options: DENY`.

### IDOR

- Patched IDOR uploads use a random 32-byte token encoded as 64 hex characters.
- Files are stored in private storage and are not addressable by sequential public IDs.
- The download route validates the token, requires
  `files.owner_id === authenticated_user.id`, and then returns a neutral attachment.

## Observed impact

No live exploit suite was run for this repository update. The following is a
code-grounded implementation result, not a claim of browser or live-server
verification:

- RCE: the PHP payload is expected to be rejected by the upload allowlist
  before storage. Even if accepted as an allowlisted comparison file, private
  storage and octet-stream attachment delivery prevent PHP public execution.
- XSS: inline script payload support is prepared, but browser evidence has not
  yet been collected. **XSS status is manual and unverified until a browser run
  confirms the marker does not execute.**
- IDOR: the prepared PoC should receive a 404 response for Arcie when using
  Igor's token. This is not a live result until `tests/idor_test.sh patched`
  is run against a local server.

## Outcome

**IMPLEMENTED / LIVE TESTING PENDING.** The application code contains all three
requested defenses, but the RCE, XSS, and IDOR outcomes above must remain
marked as pending until their respective evidence is collected.

## Legitimate-function check

- Owners can use the combined RCE, XSS, and IDOR upload pages and download or preview
  their own ordinary files.
- RCE and IDOR protected retrieval are available only with the matched owner
  account.
- Static SVG/HTML/text preview remains useful for safe content; active content
  is intentionally unavailable to execute inline scripts.
