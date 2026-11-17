# IDOR — V3 (opaque identifiers with ownership authorization)

- **Scenario / target version:** IDOR / v3
- **Date:** 2026-11-17
- **Attacker:** arcie
- **Victim:** igor (owner of a private file)
- **Input:** [`exploits/idor/private-igor.txt`](../exploits/idor/private-igor.txt) (victim's private doc) + [`exploits/idor/v3.py`](../exploits/idor/v3.py) (attacker PoC)
- **Method:** [`tests/idor_test.sh`](../tests/idor_test.sh) `v3`

## Observed security impact

Igor uploaded a private file and the application assigned it an opaque
32-character token. The V3 PoC then authenticated as Arcie and attempted to
download the file using Igor's valid token.

The request was denied:

```
Version: v3 | HTTP status: 404
No unauthorized private-document disclosure observed.
```

Arcie did not receive Igor's private marker.

## Root cause addressed

- V3 continues using opaque tokens instead of predictable sequential IDs.
- [`public/scenarios/idor/v3/download.php`](../public/scenarios/idor/v3/download.php)
  looks up the file by token.
- The handler compares the file's `owner_id` with the authenticated user's ID.
- A valid token belonging to another user is rejected.

This addresses both identifier enumeration and the missing object-level
authorization check present in V1 and V2.

## Defence applied

V3 applies server-side ownership authorization in addition to opaque
identifiers. The file remains in private storage and is only returned when
the authenticated user owns the requested record.

## Outcome

**PROTECTED.** `tests/idor_test.sh v3` denied Arcie's request and returned the
non-disclosure result. The test harness exited `1`, indicating that no
unauthorized private-document disclosure occurred.

## Legitimate-function check

Igor can still download his own private file using its opaque token. Arcie is
denied access to the same file, even when the valid token is known.
