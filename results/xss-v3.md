# XSS — V3 (Content Security Policy)

- **Scenario / target version:** XSS / v3
- **Date:** 2026-11-17
- **Attacker / uploader:** crosley
- **Victim:** arcie (a different authenticated user)
- **Input:** [`exploits/xss/v3.svg`](../exploits/xss/v3.svg) — the same SVG with an inline script used for the other XSS versions
- **Method:** manual (browser; response headers and script execution were verified in the browser)

## Observed security impact

The uploaded SVG was stored outside the public document root and opened
through the V3 preview endpoint. The response included a Content Security
Policy that disables script execution:

```http
Content-Security-Policy: default-src 'none'; script-src 'none'; style-src 'unsafe-inline'; img-src data:;
```

The SVG content could be retrieved for preview, but the inline script did not
execute. The `LAB_XSS_MARKER_EXECUTED` marker did not appear.

## Root cause addressed

- V3 stores uploads under `data/private/xss/v3/`.
- The raw uploaded file is not exposed through a public `uploads/` directory.
- [`public/scenarios/xss/v3/preview.php`](../public/scenarios/xss/v3/preview.php)
  serves the preview with a restrictive Content Security Policy.
- `script-src 'none'` prevents the uploaded SVG's inline script from running.

The same SVG is used for the V1, V2, and V3 comparisons so that only the
delivery and browser policy change between versions.

## Defence applied

V3 applies a Content Security Policy to the preview response:

```text
default-src 'none'
script-src 'none'
```

This blocks active script execution while allowing the application to provide
a controlled preview response. The preview also requires authentication and
the owner check used by the V3 route.

## Outcome

**PROTECTED AGAINST THIS XSS PATH.** The SVG preview did not execute the
attacker-controlled script, and the browser execution marker was not set.

This result demonstrates that the V3 CSP blocks the tested inline SVG script.

## Legitimate-function check

Crosley can still retrieve and preview the uploaded SVG content. Visual or
non-script content remains available, while embedded JavaScript is blocked.
