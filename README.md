# CS4239 G413 — Web File Upload Security Lab

Experimental study of **server-side code execution (RCE)**, **stored XSS**, and **unauthorized file access (IDOR)** in one PHP/Apache file-sharing application. We evaluate **actual security violations**, not merely whether an upload is accepted.

**Current status:** All three **V1 vulnerable scenarios work**. RCE V2/V3,
XSS V2/V3, and IDOR V2/V3 are implemented for the defence comparisons.
`/scenarios/patched/` remains a placeholder.

> **Local lab only:** This project intentionally includes vulnerable code. Run it on a disposable Ubuntu VM, with Apache listening on `127.0.0.1:8080`. Do not expose it to other machines or use real files, accounts, or secrets.

## Versions and accounts

| Investigation | V1 (working) | V2 (planned) | V3 (planned) |
| --- | --- | --- | --- |
| **RCE** | Executable PHP uploads | File-type validation | Protected, non-executable storage |
| **XSS** | Same-origin SVG/HTML preview | Safe file delivery | Content Security Policy |
| **IDOR** | File ID without ownership check | Opaque identifiers only | Server-side ownership checks |

Visit `/scenarios/rce/`, `/scenarios/xss/`, or `/scenarios/idor/` to select a version. `/scenarios/patched/` is the **final integrated-app placeholder**, not a completed fix. Version numbers label experimental configurations; they are not security rankings.

Demo users: `arcie`, `crosley`, `igor`. **All passwords:** `password123`. Every user can access every scenario; IDOR tests require two distinct accounts.

## Key files

```text
public/                   Apache document root: dashboard, login, scenario routes
  scenarios/rce/v1/       Vulnerable upload and web-executable storage
  scenarios/xss/v1/       Vulnerable upload and same-origin preview
  scenarios/idor/v1/      Vulnerable private-file download handler
  scenarios/*/v2,v3/     Defensive comparison routes
  scenarios/patched/     Final integrated-app placeholder
  common/                Shared upload page and handler
src/bootstrap.php        Auth/session, SQLite and route helpers
scripts/init_db.php      Fresh database and three demo users
scripts/migrate_versions.php  Upgrade older pre-versioned database
setup/cs4239-lab.conf    Apache virtual host configuration
data/                    Runtime SQLite and private files (never commit)
exploits/                Source attack inputs and IDOR request scripts
  rce/v1.php, v2.php, v3.php
  xss/v1.svg, v2.svg, v3.svg
  idor/v1.py, v2.py, v3.py
  idor/private-igor.txt   Dummy private document (uploaded by Igor)
  legitimate.txt         Harmless control upload
results/                 Sanitized observations (not session cookies/secrets)
docs/                    Background migration/versioning details
```

**Exploit naming:** `v1`, `v2`, `v3` identify the **application version to test**. For controlled comparison, the three RCE PHP files contain the **same payload**, as do the three XSS SVG files. The IDOR scripts contain identical test logic and derive the target route from their filename. **Do not assume V2/V3 are implemented or safe.** Instructions and notes are in the test files themselves.

The `exploits/` files are **inputs to the experiments**, not saved upload destinations. Uploaded RCE/XSS V1 files go under the deployed `public/scenarios/<scenario>/v1/uploads/`; IDOR's private uploads go under deployed `data/private/v1/`. The SQLite database is at `data/lab.sqlite` and records the owner, scenario, version and stored path.

## Fresh Ubuntu setup

Tested by Member A on Ubuntu **26.04**, Apache **2.4.66**, PHP **8.5.4** with SQLite support. Other versions may work but can affect results. Use a **dedicated VM**, since the commands alter Apache's default listening configuration.

```bash
git clone https://github.com/Shantaa/4239-g413.git
cd 4239-g413
sudo apt update
sudo apt install -y apache2 php libapache2-mod-php php-cli php-sqlite3 sqlite3 curl
sudo mkdir -p /var/www/cs4239-lab
sudo cp -a public src scripts setup /var/www/cs4239-lab/
sudo install -d -o www-data -g www-data -m 750 /var/www/cs4239-lab/data
sudo install -d -o www-data -g www-data -m 755 \
  /var/www/cs4239-lab/public/scenarios/rce/v1/uploads \
  /var/www/cs4239-lab/public/scenarios/xss/v1/uploads
sudo -u www-data php /var/www/cs4239-lab/scripts/init_db.php
sudo cp /etc/apache2/ports.conf /etc/apache2/ports.conf.cs4239.bak
printf 'Listen 127.0.0.1:8080\n' | sudo tee /etc/apache2/ports.conf
sudo cp setup/cs4239-lab.conf /etc/apache2/sites-available/cs4239-lab.conf
sudo a2dissite 000-default.conf
sudo a2ensite cs4239-lab.conf
sudo apache2ctl configtest
sudo systemctl restart apache2
sudo ss -ltnp | grep ':8080'
```

Open **Firefox inside Ubuntu** at `http://127.0.0.1:8080/`. Confirm Apache listens on `127.0.0.1:8080`, not `0.0.0.0` or `[::]`.

**Existing installation:** Keep the deployed `data/` and uploaded files. Only use `scripts/migrate_versions.php` when upgrading an **older pre-versioned** database; do not reset the existing accounts. See `docs/VERSIONED_LAYOUT.md`.

## Verify vulnerable V1

| Scenario | Steps | Evidence |
| --- | --- | --- |
| **RCE** | Sign in as Arcie; upload `exploits/rce/v1.php` at `/scenarios/rce/v1/`; open the uploaded file. | `LAB_RCE_MARKER_EXECUTED` and `whoami` output (usually `www-data`) demonstrate server command execution. |
| **XSS** | Sign in as Crosley; upload `exploits/xss/v1.svg` at `/scenarios/xss/v1/`; click **Preview**. | Preview marker changes to `LAB_XSS_MARKER_EXECUTED`, demonstrating script execution in the app's origin. |
| **IDOR** | As Igor, upload `exploits/idor/private-igor.txt` at `/scenarios/idor/v1/` and record its file ID. Then try the IDOR script below as Arcie. | The script can read Igor's private marker despite Arcie not owning the file. |

```bash
python3 exploits/idor/v1.py <IGOR_FILE_ID>
```

**IDOR scripts are run locally, not uploaded.** Future versions use `v2.py` and `v3.py`, with file IDs obtained from the corresponding version. These routes are currently placeholders. A normal owner download is not evidence of IDOR. For RCE/XSS, repeat the **same content** against later versions and record whether execution occurs, rather than changing the payload to fit the defence.

## Development and handoff

- **Git working tree:** `~/4239-g413/` (edit and commit here). **Live deployment:** `/var/www/cs4239-lab/` (Apache serves its `public/`).
- To deploy updated code **without overwriting the database**:

```bash
cd ~/4239-g413
sudo cp -a public/. /var/www/cs4239-lab/public/
sudo cp -a src/. /var/www/cs4239-lab/src/
sudo cp -a scripts/. /var/www/cs4239-lab/scripts/
sudo chown -R www-data:www-data \
  /var/www/cs4239-lab/public/scenarios/rce/v1/uploads \
  /var/www/cs4239-lab/public/scenarios/xss/v1/uploads
```

- **Member A:** shared application and V1 baseline, setup guide, verified Git checkpoint.
- **Member B:** exploit reproduction, root-cause analysis, repeatable tests and evidence under `results/`.
- **Member C:** independent defensive configurations, `/scenarios/patched/`, integrated regression tests and report/video consolidation.
- Record for each test: **scenario + target version**, attacker/victim, input, observed security impact, defence applied, outcome and legitimate-function check.
- Commit only source code, harmless test inputs and sanitized evidence. Never commit the live SQLite database, uploaded runtime files, cookies or secrets.

For errors: `sudo apache2ctl configtest`, `sudo systemctl status apache2`, and `sudo tail -n 50 /var/log/apache2/cs4239-lab-error.log`. Keep the baseline preserved (e.g. Git tag `vulnerable-baseline`) for comparison.

## References

[OWASP File Upload](https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html) · [OWASP XSS Prevention](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html) · [OWASP IDOR Prevention](https://cheatsheetseries.owasp.org/cheatsheets/Insecure_Direct_Object_Reference_Prevention_Cheat_Sheet.html)
