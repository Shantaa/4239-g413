# CS4239 Assignment 2 — Web File Upload Lifecycle Security Lab

**Project:** *Securing User-Uploaded Files in Web Applications: An Experimental Study of Security Risks and Defences*  
**Team:** Group 413 · CS4239 · November 2026  
**Status (3 November 2026):** Member A's versioned application is running; the three V1 scenarios have been verified on Member A's Ubuntu VM. V2/V3 and the integrated patched application are **placeholders**, not implemented defences.

> **Safety / scope:** This repository contains **deliberately vulnerable** PHP endpoints and harmless demonstration payloads. Run it only in a disposable, isolated Ubuntu VM, with Apache bound to `127.0.0.1`. Do not expose it on a public network or upload real or sensitive documents. The demo credentials below are not suitable for other systems.

## 1. Project objective

Study the security risks of handling untrusted user-uploaded files, from upload and storage to viewing and retrieval. We measure **security violations**, not merely whether an upload is accepted or a file can be downloaded.

| Investigation | Security outcome to test | Vulnerable V1 mechanism |
| --- | --- | --- |
| **RCE** — Server-side code execution | Can an uploaded PHP file execute code on the server? | Files are stored under an Apache-accessible directory where PHP execution is enabled; no file-type check is applied. |
| **XSS** — Stored cross-site scripting | Can uploaded active content execute JavaScript in a viewer's application origin? | Uploaded SVG is embedded in an unsandboxed, same-origin preview. |
| **IDOR** — Unauthorized file access | Can one logged-in user download another user's private file? | The download endpoint looks up a file by ID but does **not** check its owner. |

Each investigation will compare independently chosen defences. A **final integrated application** will then combine appropriate controls and rerun all three attack cases.

## 2. Current version plan and URLs

Application base URL (inside the Ubuntu VM): **`http://127.0.0.1:8080`**.

| Route | Purpose | Status |
| --- | --- | --- |
| `/` | Dashboard | Working |
| `/login.php` | Sign in | Working |
| `/scenarios/rce/` | RCE version selection | Working |
| `/scenarios/rce/v1/` | RCE vulnerable baseline | **Working and verified** |
| `/scenarios/rce/v2/` | Upload validation comparison | Placeholder |
| `/scenarios/rce/v3/` | Protected storage / execution isolation | Placeholder |
| `/scenarios/xss/` | XSS version selection | Working |
| `/scenarios/xss/v1/` | XSS vulnerable baseline | **Working and verified** |
| `/scenarios/xss/v2/` | Safe file delivery | Placeholder |
| `/scenarios/xss/v3/` | Content Security Policy comparison | Placeholder |
| `/scenarios/idor/` | IDOR version selection | Working |
| `/scenarios/idor/v1/` | IDOR vulnerable baseline | **Working and verified** |
| `/scenarios/idor/v2/` | Unpredictable identifiers without authorization | Placeholder |
| `/scenarios/idor/v3/` | Server-side ownership authorization | Placeholder |
| `/scenarios/patched/` | Combined defences and final regression testing | **Placeholder only** |

**Version numbers are experiment labels, not necessarily security rankings.** V2 and V3 are proposals; Member C can add V4 or change a comparison when supported by a clear security hypothesis. Do not describe a placeholder as patched or tested.

### Demo accounts

| User | Represents | Password |
| --- | --- | --- |
| `arcie` | RCE | `password123` |
| `crosley` | XSS | `password123` |
| `igor` | IDOR | `password123` |

**All users can visit all investigation routes.** Account names are just labels; they do not limit investigation access. IDOR tests require two different logged-in users.

## 3. Repository layout

```text
4239-g413/
├── README.md                         # This handover guide
├── .gitignore                        # Excludes runtime data and uploaded files
├── public/                           # Apache document root (HTTP-accessible)
│   ├── index.php                     # Dashboard
│   ├── login.php / logout.php        # Demo authentication
│   ├── assets/lab.css                # Styling
│   ├── common/                       # Shared upload pages/handlers and placeholders
│   └── scenarios/
│       ├── rce/                      # Landing page; v1, v2, v3
│       │   └── v1/uploads/           # Runtime RCE test uploads
│       ├── xss/                      # Landing page; v1, v2, v3
│       │   └── v1/uploads/           # Runtime XSS test uploads
│       ├── idor/                     # Landing page; v1, v2, v3
│       │   └── v1/download.php       # Deliberately missing owner check
│       └── patched/index.php        # Integrated-app placeholder
├── src/bootstrap.php                 # Sessions, SQLite, auth, file/route helpers
├── scripts/
│   ├── init_db.php                   # Fresh installation only
│   └── migrate_versions.php         # Existing pre-versioned database only
├── setup/cs4239-lab.conf             # Apache localhost virtual host
├── data/.gitkeep                     # Runtime SQLite + private uploads go here
├── exploits/                         # Attack fixtures, future tests, sanitized results
│   ├── rce/fixtures/rce-marker.php
│   ├── xss/fixtures/xss-marker.svg
│   ├── idor/fixtures/private-igor.txt
│   ├── common/fixtures/legitimate.txt
│   └── results/
└── docs/VERSIONED_LAYOUT.md          # Notes for upgrading the original app
```

**Where files go:**

- The **Git working copy** is where we edit code and commit changes (e.g., `~/4239-g413/`).
- The **deployed copy** lives at `/var/www/cs4239-lab/`; Apache serves only its `public/` directory.
- **Attack inputs and scripts** belong under `exploits/` and may be committed when they contain only harmless lab data.
- **Files actually uploaded through the app** are stored on the VM: RCE/XSS V1 in `public/scenarios/{rce,xss}/v1/uploads/`; IDOR V1 in `data/private/v1/`.
- **File metadata and demo user accounts** are stored in `data/lab.sqlite`. Do not commit the database or runtime uploads.

The upload handler stores a generated filename and records the `scenario` and `variant` (such as `rce` + `v1`) in SQLite. This keeps tests for different versions distinguishable. **Do not manually copy payloads into `public/` to claim successful file-upload exploitation**; submit them through the application.

## 4. Fresh setup on another Ubuntu VM

These commands assume a **dedicated/disposable Ubuntu VM**. Member A's tested environment is Ubuntu **26.04 LTS**, Apache **2.4.66**, PHP **8.5.4** with PDO SQLite. Other versions may work, but record them: Apache handler settings and browser behaviour can affect results.

### 4.1 Obtain source and install packages

```bash
git clone https://github.com/Shantaa/4239-g413.git
cd 4239-g413
sudo apt update
sudo apt install -y apache2 php libapache2-mod-php php-cli php-sqlite3 sqlite3 curl
php -v
apache2 -v
php -m | grep -iE 'PDO|sqlite'
```

Because the repository is private, your GitHub account must be invited. Use GitHub's supported HTTPS token / credential manager or SSH authentication, **not a GitHub account password**.

### 4.2 Deploy code and create writable locations

```bash
sudo mkdir -p /var/www/cs4239-lab
sudo cp -a public src scripts setup /var/www/cs4239-lab/

sudo install -d -o www-data -g www-data -m 750 /var/www/cs4239-lab/data
sudo install -d -o www-data -g www-data -m 755 \
  /var/www/cs4239-lab/public/scenarios/rce/v1/uploads \
  /var/www/cs4239-lab/public/scenarios/xss/v1/uploads

# FRESH setup only: creates SQLite database, private storage and the demo users.
sudo -u www-data php /var/www/cs4239-lab/scripts/init_db.php
```

Do **not** run `init_db.php` as a replacement for migrating an existing pre-versioned installation; see `docs/VERSIONED_LAYOUT.md`.

### 4.3 Configure the local Apache site

> The following changes Apache's listening ports for the **entire VM**. Do this only if the VM is dedicated to this lab and has no other sites that need ports 80/443.

```bash
sudo cp /etc/apache2/ports.conf /etc/apache2/ports.conf.before-cs4239
printf 'Listen 127.0.0.1:8080\n' | sudo tee /etc/apache2/ports.conf
sudo cp setup/cs4239-lab.conf /etc/apache2/sites-available/cs4239-lab.conf
sudo a2dissite 000-default.conf
sudo a2ensite cs4239-lab.conf
sudo apache2ctl configtest
sudo systemctl restart apache2
sudo ss -ltnp | grep ':8080'
```

Confirm the Apache listener is bound to **`127.0.0.1:8080`**, **not** `0.0.0.0:8080` or a publicly accessible interface. Open Firefox **inside the VM** and visit `http://127.0.0.1:8080/`. Log in using a demo account.

### 4.4 If using an already initialized database

Back up the deployed `data/lab.sqlite` first. For the original unversioned application, deploy the new code **without overwriting `data/`**, then run:

```bash
sudo -u www-data php /var/www/cs4239-lab/scripts/migrate_versions.php
```

The migration adds the `files.variant` column if absent and preserves existing users and file records. See `docs/VERSIONED_LAYOUT.md` for the full upgrade procedure. **Do not delete or recreate an existing database just to change versions.**

## 5. Verify the vulnerable V1 scenarios

These are **non-destructive markers**, not interactive shells or attacks against real services. Use only the isolated VM. Sign in before opening scenario pages.

### RCE — uploaded PHP is executed by Apache

1. Visit `/scenarios/rce/v1/`.
2. Upload `exploits/rce/fixtures/rce-marker.php` from the cloned repository.
3. Click **Open uploaded file** on the resulting upload entry.
4. Expected: the response displays `LAB_RCE_MARKER_EXECUTED`.

This demonstrates that the **server executes uploaded PHP**. The supplied fixture does not run operating-system commands; the security significance is that arbitrary attacker-controlled PHP would execute in this configuration.

### Stored XSS — uploaded SVG executes in the site's origin

1. Visit `/scenarios/xss/v1/`.
2. Upload `exploits/xss/fixtures/xss-marker.svg`.
3. Click **Preview**.
4. Expected: the preview page's marker changes to `LAB_XSS_MARKER_EXECUTED`.

The SVG is displayed in an **unsandboxed iframe** and can access its same-origin parent document. Note the browser/version used. Merely accepting or storing an SVG would not prove XSS.

### IDOR — cross-user access to a private upload

1. Sign in as **Igor** and visit `/scenarios/idor/v1/`.
2. Upload `exploits/idor/fixtures/private-igor.txt` and note the displayed file **ID** (e.g., `N`).
3. Sign out; sign in as **Arcie**.
4. Request `/scenarios/idor/v1/download.php?id=N` using Igor's actual ID.
5. Expected: Arcie can download Igor's dummy private file despite not owning it.

This demonstrates an **authorization failure**. Ordinary file download by the owner is expected behaviour and is not evidence of IDOR.

**Handoff verification:** Member A reports that all three V1 scenarios worked on the original VM on 3 November 2026. Members B/C should repeat these checks on their own environments before changing the application.

## 6. How to make and deploy source changes

The **Git repo and Apache deployment are separate copies**. Editing the repository does not update the running site until you deploy it.

```bash
cd ~/4239-g413
# Edit source files here, then check PHP syntax if changed:
php -l src/bootstrap.php

# Copy only application source; preserve runtime database and uploads.
sudo cp -a public/. /var/www/cs4239-lab/public/
sudo cp -a src/. /var/www/cs4239-lab/src/
sudo cp -a scripts/. /var/www/cs4239-lab/scripts/

# 'cp -a' preserves source ownership; restore write access to runtime upload folders.
sudo chown -R www-data:www-data \
  /var/www/cs4239-lab/public/scenarios/rce/v1/uploads \
  /var/www/cs4239-lab/public/scenarios/xss/v1/uploads
```

New PHP edits normally take effect on the next request (refresh the browser); Apache configuration changes require `configtest` and an Apache reload/restart. **Never deploy over `data/` or commit `data/lab.sqlite`.** Future versions will require their own storage permissions as appropriate.

Before pushing:

```bash
git status
git diff --stat
git add <specific-source-files>
git commit -m "Describe change"
git push
```

Use a named checkpoint for the confirmed vulnerable baseline, before Member C changes its behaviour:

```bash
git tag -a vulnerable-baseline -m "Member A verified V1 scenarios"
git push origin vulnerable-baseline
```

Only create this tag after all intended source changes are committed and the three V1 tests pass. Preserve the original baseline for before/after comparisons; implement defences on a branch or in version-specific routes.

## 7. Team handoff and experimental evidence

| Phase / owner | Work | Expected handoff |
| --- | --- | --- |
| **Member A — Build** | Shared PHP/Apache/SQLite app, demo accounts, V1 routes, version placeholders and architecture | Runnable baseline, setup guide, code checkpoint, architecture write-up |
| **Member B — Exploit** | Reproduce RCE, XSS, IDOR; examine root causes; prepare tests | Reproduction steps/scripts, screenshots/logs, explanation of each violated security boundary, exploitation section and presentation segment |
| **Member C — Defend + consolidate** | Implement V2/V3 as justified, integrate final `/scenarios/patched/`, rerun attacks and legitimate-use tests, assemble report/video | Defences, comparison matrix, final patched results, submission package |

**Required evidence for each investigation:** configuration/version; logged-in actor and target; test input and HTTP route; expected legitimate behaviour; observed security violation; specific code/setting responsible; result after each defence; and whether normal functionality still works. Store sanitized evidence under `exploits/results/` and cite it in the report. Never commit session cookies, real user data, or database dumps.

Final comparison should answer **which security boundaries each control protects and which it does not**. Do not infer that a file is dangerous solely because its content is malformed, or that a control is effective solely because an upload is rejected.

## 8. Troubleshooting and limitations

| Symptom | Check |
| --- | --- |
| Website does not load | `sudo apache2ctl configtest`, `sudo systemctl status apache2`, `sudo ss -ltnp | grep ':8080'` |
| HTTP 500 / blank PHP page | `sudo tail -n 50 /var/log/apache2/cs4239-lab-error.log` |
| Upload directory not writable | Ensure the relevant `public/scenarios/<scenario>/v1/uploads/` is writable by `www-data`. |
| SQLite cannot open database | Ensure `data/` and `data/lab.sqlite` are owned/readable/writable by `www-data`; verify `pdo_sqlite` is installed. |
| Route shows “planned” | Expected for V2/V3 and `/scenarios/patched/`; these are not finished. |
| RCE marker is downloaded rather than executed | Check Apache/PHP module and handler configuration; do not assume identical behaviour across machines. |
| XSS marker is not triggered | Check preview context and browser developer console; an SVG loaded as `<img>` behaves differently from an unsandboxed document/iframe. |
| IDOR test fails | Verify you're using an actual Igor file ID from V1 and are signed in as another account. |

**Known scope:** single PHP/Apache application, lab-local SQLite/filesystem storage, and three defined security violations. No malware scanning, denial-of-service research, cloud storage, or production-grade authentication. The final hardened application has not yet been implemented or validated.

## 9. Useful references

- [OWASP — File Upload Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html)
- [OWASP — Cross Site Scripting Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)
- [OWASP — Insecure Direct Object Reference Prevention](https://cheatsheetseries.owasp.org/cheatsheets/Insecure_Direct_Object_Reference_Prevention_Cheat_Sheet.html)
- [MITRE CWE-434 — Unrestricted Upload of File with Dangerous Type](https://cwe.mitre.org/data/definitions/434.html)
- [PHP — Handling File Uploads](https://www.php.net/manual/en/features.file-upload.php)
