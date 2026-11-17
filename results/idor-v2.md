# IDOR — V2 (opaque identifiers without ownership checks)

- **Scenario / target version:** IDOR / v2
- **Date:** 2026-11-17
- **Attacker:** arcie
- **Victim:** igor (owner of a private file)
- **Input:** [`exploits/idor/private-igor.txt`](../exploits/idor/private-igor.txt) (victim's private doc) + [`exploits/idor/v2.py`](../exploits/idor/v2.py) (attacker PoC)
- **Method:** [`tests/idor_test.sh`](../tests/idor_test.sh) `v2`

## Observed security impact

IDOR V2 assigns a new upload an opaque 32-character hexadecimal token instead
of exposing its sequential database ID. The download route accepts the token:

```text
/scenarios/idor/v2/download.php?token=<opaque-token>
```

The V2 route intentionally does not check `owner_id`. Therefore, if Arcie
obtains Igor's token, the PoC is expected to retrieve Igor's private marker:

```
Version: v2 | HTTP status: 200
UNAUTHORIZED ACCESS CONFIRMED: Arcie retrieved Igor's private marker.
```

The numeric-ID route is no longer used for V2, but opacity alone does not
prevent unauthorized access when the identifier is disclosed.

## Root cause addressed

- V2 creates a cryptographically random token using `random_bytes(16)`.
- The token is stored in the `files.public_token` database column.
- V2 looks up files by token rather than by sequential integer ID.
- The token is validated as a 32-character hexadecimal value before lookup.

This addresses trivial enumeration of sequential IDs, but it does not address
the underlying authorization failure.

## Defence applied

V2 applies opaque identifiers and removes the predictable numeric identifier
from the V2 download link. The V2 download handler intentionally retains the
missing ownership check so that V3 can measure the effect of server-side
authorization separately.

Opaque identifiers are not an access-control mechanism. Anyone who obtains a
valid token can still request the corresponding private file.

## Outcome

**VULNERABLE BY DESIGN.** After the V2 route and migration are deployed,
`tests/idor_test.sh v2` should exit `0` and report unauthorized disclosure.
This confirms that opaque identifiers are implemented, but ownership
authorization is not.

The intended secure result is deferred to V3, where Arcie's request should
receive a denial or not-found response even with Igor's valid token.

## Legitimate-function check

Igor should be able to upload and download his own V2 file using the opaque
token. A normal owner download is not evidence that the authorization
defence works; the relevant V2 finding is that Arcie can also retrieve the
file when given the same token.
