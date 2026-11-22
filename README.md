# CS4239 G413 — Web File Upload Security Lab

Experimental study of **server-side code execution (RCE)**, **stored XSS**, and **unauthorized file access (IDOR)** in one PHP/Apache file-sharing application. We evaluate **actual security violations**, not merely whether an upload is accepted.

**Current status:** All three **V1 vulnerable scenarios work**. RCE V2/V3,
XSS V2/V3, and IDOR V2/V3 are implemented for the defence comparisons.
`/scenarios/patched/` now combines the RCE, XSS, and IDOR hardened controls.

**Verified outcomes:** RCE V1 executes, RCE V2 rejects PHP uploads, and RCE
V3 serves PHP as data. XSS V1 executes the SVG payload, XSS V2 downloads it,
and XSS V3 blocks its script with CSP. IDOR V1 and V2 allow the unauthorized
test access, while IDOR V3 enforces ownership authorization.

> **Local lab only:** This project intentionally includes vulnerable code. Run it on a disposable Ubuntu VM, with Apache listening on `127.0.0.1:8080`. Do not expose it to other machines or use real files, accounts, or secrets.

## Versions and accounts

| Investigation | V1 (working) | V2 (working) | V3 (working) |
| --- | --- | --- | --- |
| **RCE** | Executable PHP uploads | File-type validation | Protected, non-executable storage |
| **XSS** | Same-origin SVG/HTML preview | Safe file delivery | Content Security Policy |
| **IDOR** | File ID without ownership check | Opaque identifiers only | Server-side ownership checks |

Visit `/scenarios/rce/`, `/scenarios/xss/`, or `/scenarios/idor/` to select a version. `/scenarios/patched/` is the **combined final hardened application**. Version numbers label the separate experimental configurations; they are not security rankings.

Demo users: `arcie`, `crosley`, `igor`. **All passwords:** `password123`. Every user can access every scenario; IDOR tests require two distinct accounts.

## Key files

```text
public/                   Apache document root: dashboard, login, scenario routes
  scenarios/rce/v1/       Vulnerable upload and web-executable storage
  scenarios/xss/v1/       Vulnerable upload and same-origin preview
  scenarios/idor/v1/      Vulnerable private-file download handler
  scenarios/*/v2,v3/     Defensive comparison routes
  scenarios/patched/     Combined hardened RCE, XSS, and IDOR routes
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

**Exploit naming:** `v1`, `v2`, `v3` identify the **application version to test**. For controlled comparison, the three RCE PHP files contain the **same payload**, as do the three XSS SVG files. The IDOR scripts share the same test flow and derive the target route from their filename; V1 uses a numeric ID, while V2/V3 use opaque tokens. V2 and V3 are implemented, but their security outcomes differ: IDOR V2 intentionally retains the ownership flaw, while IDOR V3 adds authorization. Instructions and notes are in the test files themselves.

The `exploits/` files are **inputs to the experiments**, not saved upload destinations. RCE V1/V2 uploads use public versioned upload directories; RCE V3 uploads go under `data/private/rce/v3/`. XSS V1 uses public storage, while XSS V2/V3 use `data/private/xss/v2/` and `data/private/xss/v3/`. IDOR V1/V2/V3 use `data/private/v1/`, `data/private/v2/`, and `data/private/v3/` respectively. The SQLite database is at `data/lab.sqlite` and records the owner, scenario, version, stored path and, for IDOR V2/V3, the opaque token.

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
  /var/www/cs4239-lab/public/scenarios/rce/v2/uploads \
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

## Verify the scenarios

| Scenario | Steps | Evidence |
| --- | --- | --- |
| **RCE** | Sign in as Arcie; upload `exploits/rce/v1.php` at `/scenarios/rce/v1/`; open the uploaded file. | `LAB_RCE_MARKER_EXECUTED` and `whoami` output (usually `www-data`) demonstrate server command execution. |
| **XSS** | Sign in as Crosley; upload `exploits/xss/v1.svg` at `/scenarios/xss/v1/`; click **Preview**. | Preview marker changes to `LAB_XSS_MARKER_EXECUTED`, demonstrating script execution in the app's origin. |
| **IDOR** | As Igor, upload `exploits/idor/private-igor.txt` at `/scenarios/idor/v1/` and record its file ID. Then try the IDOR script below as Arcie. | The script can read Igor's private marker despite Arcie not owning the file. |

The table above verifies the vulnerable V1 baselines. The same inputs must be
used against V2 and V3 to compare the defences; see
[`results/README.md`](results/README.md) for the complete versioned outcome
summary.

```bash
bash tests/rce_test.sh v1
bash tests/rce_test.sh v2
bash tests/rce_test.sh v3
bash tests/rce_test.sh patched
bash tests/idor_test.sh v1
bash tests/idor_test.sh v2
bash tests/idor_test.sh v3
bash tests/idor_test.sh patched
```

The `patched` target runs the final combined routes at
`/scenarios/patched/rce/`, `/scenarios/patched/xss/`, and
`/scenarios/patched/idor/`. RCE and IDOR have repeatable harnesses; XSS
requires manual browser verification.

**IDOR scripts are run locally, not uploaded.** IDOR V1 uses a numeric file
ID; V2 and V3 use opaque tokens. A normal owner download is not evidence of
IDOR. For RCE/XSS, repeat the **same content** against later versions and
record whether execution occurs, rather than changing the payload to fit the
defence. XSS is verified manually in a browser because script execution
cannot be judged reliably by `curl`.

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
  /var/www/cs4239-lab/public/scenarios/rce/v2/uploads \
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
