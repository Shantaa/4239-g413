# XSS — V2 (safe file delivery)

- **Scenario / target version:** XSS / v2
- **Date:** 2026-11-17
- **Attacker / uploader:** crosley
- **Victim:** arcie (a different authenticated user)
- **Input:** [`exploits/xss/v2.svg`](../exploits/xss/v2.svg) — the same SVG with an inline script used for the other XSS versions
- **Method:** manual (browser; script execution cannot be judged reliably by curl)

## Observed security impact

The V2 implementation stores the uploaded SVG outside Apache's public document
root and serves it through a controlled download endpoint. The response is
intended to download the file as inert data rather than render it as active
SVG content:

```
Content-Type: application/octet-stream
Content-Disposition: attachment
X-Content-Type-Options: nosniff
```

The `LAB_XSS_MARKER_EXECUTED` marker should not appear, and the SVG should not
execute JavaScript in either Crosley's or Arcie's browser session. Runtime
verification should confirm the download behavior and absence of script
execution before treating this result as final evidence.

## Root cause addressed

- XSS V2 uploads are stored under `data/private/xss/v2/`, outside the web root.
- The raw `/scenarios/xss/v2/uploads/` path cannot expose the uploaded file.
- [`public/scenarios/xss/v2/download.php`](../public/scenarios/xss/v2/download.php)
  serves the file with a neutral content type and attachment disposition.
- The V2 page does not provide the unsandboxed inline preview used by V1.

The same SVG is used as the V1 comparison input so that the result measures
the delivery defence rather than a changed payload.

## Defence applied

V2 applies safe file delivery by separating uploaded active content from the
web root and forcing downloads with `Content-Disposition: attachment`,
`application/octet-stream`, and `X-Content-Type-Options: nosniff`.

This prevents the V1 same-origin SVG execution path. V2 does not yet apply
the separate V3 Content Security Policy design.

## Outcome

**DEFENCE IMPLEMENTED; RUNTIME VERIFICATION PENDING.** The source implements
safe file delivery, but the final report should be marked **PROTECTED** only
after the browser test confirms that the SVG downloads and the execution
marker does not appear.

## Legitimate-function check

The uploaded SVG should remain retrievable as a downloaded file for an
authenticated user. Confirm that the download completes and that the file
contents remain intact, while no preview or JavaScript execution occurs.
