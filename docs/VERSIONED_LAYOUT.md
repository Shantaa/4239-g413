# Versioned layout refactor — upgrade instructions

This is a refactor of the **existing working** `~/4239-g413/` app. Preserve the actual SQLite database, existing user accounts, and already-uploaded test files.

## Version plan (not fixed)

RCE: V1 vulnerable; V2 upload validation; V3 execution isolation. Optional V4 if justified.

XSS: V1 vulnerable; V2 safe file delivery; V3 CSP. Optional V4 for an additional independent defence.

IDOR: V1 vulnerable; V2 opaque identifiers without ownership checks; V3 ownership authorization. These are separate hypotheses, not necessarily a linear progression.

The `/scenarios/patched/` routes combine the hardened controls and must be
verified with live RCE, XSS, and IDOR tests before security claims are made.

## Source structure

```text
public/scenarios/<rce|xss|idor>/index.php      investigation landing page
public/scenarios/<rce|xss|idor>/v1/index.php   functioning baseline
public/scenarios/<rce|xss|idor>/v1/upload.php  baseline upload endpoint
public/scenarios/<rce|xss|idor>/v2/index.php   first defensive comparison
public/scenarios/<rce|xss|idor>/v3/index.php   second defensive comparison
public/scenarios/patched/index.php            combined hardened application
public/scenarios/patched/<rce|xss|idor>/       scenario-specific patched routes
public/common/                               shared rendering and upload handler
src/bootstrap.php                            database, auth, and route helpers
scripts/migrate_versions.php                 preserves existing SQLite records
exploits/                                    versioned test fixtures and later automation
```

RCE V1 newly uploaded files go to `public/scenarios/rce/v1/uploads/`. RCE V3
files go to `data/private/rce/v3/` and are delivered without execution. XSS V1
files remain web-accessible for the vulnerable baseline, XSS V2 files go to
`data/private/xss/v2/` and are delivered as attachments, and XSS V3 files go
to `data/private/xss/v3/` and are previewed with a CSP that blocks scripts.
Existing uploads from the old unversioned layout remain available from the old
`public/scenarios/<scenario>/uploads/` folders. IDOR V1 files go to
`data/private/v1/`; IDOR V2/V3 files go to `data/private/v2/` or
`data/private/v3/` and use opaque tokens. IDOR V3 additionally checks
ownership. Older IDOR files remain at their existing paths.

## Deploy on existing Ubuntu VM

**Before starting**, commit local changes and take a snapshot or backup. In these commands the update files have already been applied to `~/4239-g413/`.

```bash
cd ~/4239-g413
# Confirm files are where expected and inspect Git's proposed changes.
ls public/scenarios/rce/v1/index.php scripts/migrate_versions.php
php -l src/bootstrap.php

git status

# Back up running database and deployed PHP code BEFORE migrating.
sudo cp -a /var/www/cs4239-lab /var/www/cs4239-lab.before-versioning

# Deploy code (leave data and generated uploads alone).
sudo cp -a public/. /var/www/cs4239-lab/public/
sudo cp -a src/. /var/www/cs4239-lab/src/
sudo cp -a scripts/. /var/www/cs4239-lab/scripts/

# Delete only the obsolete, previously active unversioned PHP endpoints.
# Keep the existing /uploads/ directories so legacy uploaded test files remain accessible.
sudo rm -f /var/www/cs4239-lab/public/scenarios/{rce,xss,idor}/upload.php
sudo rm -f /var/www/cs4239-lab/public/scenarios/xss/preview.php
sudo rm -f /var/www/cs4239-lab/public/scenarios/idor/download.php

# New V1 upload directories must be writable by Apache.
sudo mkdir -p /var/www/cs4239-lab/public/scenarios/{rce,xss}/v1/uploads
sudo chown -R www-data:www-data /var/www/cs4239-lab/public/scenarios/{rce,xss}/v1/uploads

# Existing data is retained; only add the files.variant column if absent.
sudo -u www-data php /var/www/cs4239-lab/scripts/migrate_versions.php

# Existing Apache config still points to /var/www/cs4239-lab/public.
sudo apache2ctl configtest
sudo ss -ltnp | grep ':8080'
```

Avoid `sudo cp` over `/data/` and never rerun a destructive database reset. The backup `cs4239-lab.before-versioning` contains generated uploaded files and potentially readable lab database data: keep it private and outside Git.

## Remove old routes from the Git source

If they still exist, delete the outdated files **only after** verifying that V1 works:

```bash
cd ~/4239-g413
rm -f public/scenarios/{rce,xss,idor}/upload.php
rm -f public/scenarios/xss/preview.php public/scenarios/idor/download.php
# Leave the old uploads/ directories alone if you have previously uploaded test files.
```

## Test routes

- `http://127.0.0.1:8080/` — dashboard
- `/scenarios/rce/` — version selection → `/scenarios/rce/v1/`
- `/scenarios/xss/` — version selection → `/scenarios/xss/v1/`
- `/scenarios/idor/` — version selection → `/scenarios/idor/v1/`
- `/scenarios/patched/` — combined hardened RCE, XSS, and IDOR application
- `/scenarios/rce/v2/` — upload validation comparison
- `/scenarios/xss/v2/` — safe file delivery comparison
- `/scenarios/idor/v2/` — opaque-token comparison without ownership checks
- `/scenarios/rce/v3/` — non-executable private storage comparison
- `/scenarios/xss/v3/` — CSP-protected preview comparison
- `/scenarios/idor/v3/` — opaque-token ownership authorization comparison

If V1 uploads fail, check ownership of the V1 upload directory and `/var/log/apache2/cs4239-lab-error.log`.
