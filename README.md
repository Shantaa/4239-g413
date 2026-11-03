# CS4239 Assignment 2 — File Upload Lifecycle Security Lab

**Local, disposable VM only.** Deliberately vulnerable baseline; never expose the web app to a network or use real credentials/data.

## Overview

A shared PHP/Apache/SQLite file-management application for controlled security experiments:

| Investigation | Landing page | Active baseline | Planned defences |
| --- | --- | --- | --- |
| RCE | `/scenarios/rce/` | `/scenarios/rce/v1/` | `/v2/`, `/v3/` |
| Stored XSS | `/scenarios/xss/` | `/scenarios/xss/v1/` | `/v2/`, `/v3/` |
| IDOR | `/scenarios/idor/` | `/scenarios/idor/v1/` | `/v2/`, `/v3/` |
| Integrated | — | — | `/scenarios/patched/` placeholder |

The investigation V2/V3 and integrated patched pages are intentionally **not implemented**. These URLs display placeholders, not secure versions. Member C owns the defensive implementations.

Demo accounts: `arcie`, `crosley`, `igor`, all using `password123` in the isolated lab.

## Environment

- Ubuntu VM, Apache with `libapache2-mod-php`, PHP with PDO SQLite, SQLite database.
- Apache document root: `/var/www/cs4239-lab/public`.
- Loopback binding: `127.0.0.1:8080` (not a publicly reachable listener).
- Git development folder: `~/4239-g413/`; deployed application folder: `/var/www/cs4239-lab/`.

## Existing installation: upgrade to versioned routes

**Do not reinitialize or delete your existing SQLite database.** It already contains your working accounts. Use the non-destructive migration in `scripts/migrate_versions.php`.

1. Take a Git commit and make a VM snapshot (or back up the deployed application and SQLite file).
2. Apply the updated project files to the Git working tree, removing old unversioned `upload.php`/`preview.php`/`download.php` entry points as listed in `docs/VERSIONED_LAYOUT.md`.
3. Deploy updated `public/`, `src/` and `scripts/` into `/var/www/cs4239-lab/`, **without touching** `/var/www/cs4239-lab/data/`.
4. Create `/public/scenarios/rce/v1/uploads/` and `/public/scenarios/xss/v1/uploads/` on the deployed app, owned by `www-data`.
5. Run `sudo -u www-data php /var/www/cs4239-lab/scripts/migrate_versions.php` **after backing up SQLite**.
6. Verify V1 routes using `exploits/` fixtures, then commit your source changes to Git.

Detailed copy/paste commands are in `docs/VERSIONED_LAYOUT.md`.

For a completely new installation without an existing database, run `sudo -u www-data php /var/www/cs4239-lab/scripts/init_db.php` instead.

## Expected V1 results

- **RCE:** Upload `exploits/rce/fixtures/rce-marker.php` through `/scenarios/rce/v1/`; open its stored URL; observe the server-side marker.
- **XSS:** Upload `exploits/xss/fixtures/xss-marker.svg` through `/scenarios/xss/v1/`; choose Preview; observe the browser-side marker.
- **IDOR:** Upload `exploits/idor/fixtures/private-igor.txt` as Igor through `/scenarios/idor/v1/`; switch to Arcie; request the same ID through `/scenarios/idor/v1/download.php?id=N`.

`exploits/` holds lab test fixtures and eventual scripts; actual uploaded files are stored on the deployed Ubuntu filesystem and excluded from Git.

## Research outcome

The measurements concern **unauthorized server-side code execution**, **script execution in a victim browser in the app's origin**, and **cross-user disclosure of private files**. Simple upload acceptance or ordinary download alone does not prove a security vulnerability.
