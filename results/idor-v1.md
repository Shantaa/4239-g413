# IDOR — V1 (vulnerable baseline)

- **Scenario / target version:** IDOR / v1
- **Date:** 2026-11-05
- **Attacker:** arcie
- **Victim:** igor (owner of a private file)
- **Input:** [`exploits/idor/private-igor.txt`](../exploits/idor/private-igor.txt) (victim's private doc) + [`exploits/idor/v1.py`](../exploits/idor/v1.py) (attacker PoC)
- **Method:** `tests/idor_test.sh v1`

## Observed security impact

Igor uploaded a private file; the app assigned it an integer file ID. Arcie,
through the download route, requested that ID and received Igor's private
content despite not owning it:

```
Version: v1 | HTTP status: 200
UNAUTHORIZED ACCESS CONFIRMED: Arcie retrieved Igor's private marker.
```

(Observed file ID in one run: `4`. IDs are sequential integers, so other users'
files can be enumerated by guessing IDs.)

## Root cause

- `public/scenarios/idor/v1/download.php` looks up the file by ID and streams it
  with **no `owner_id` authorization check** — any authenticated user can read
  any file.
- File identifiers are **sequential integers**, making enumeration trivial.

## Defence applied

None (vulnerable baseline). Planned: V2 opaque identifiers, V3 server-side
ownership checks (the real fix — opaque IDs alone are not sufficient).

## Outcome

**VULNERABLE.** `tests/idor_test.sh v1` exited `0`.

## Legitimate-function check

Igor downloading his own file succeeds normally. A normal owner download is
**not** evidence of IDOR; the violation is Arcie reading Igor's file.
