# CS4239 Assignment 2: Member A Starter Application

**For coursework only. Intentionally insecure. Operate inside a disposable Ubuntu VM; do not expose the server on external interfaces or use real data.**

This is the **unhardened application**, not the finished defence comparison. It implements a single PHP/Apache application with three controlled vulnerable scenarios for Member B to test. Member C will implement and evaluate defences later.

## Components

- User logins: `alice / alice123`, `bob / bob123` (local test accounts only)
- `/scenarios/rce/` — upload to a PHP-enabled directory in the Apache document root; no file-type validation
- `/scenarios/xss/` — upload SVG/HTML and preview from the same origin without sandboxing
- `/scenarios/idor/` — private file uploads stored outside the webroot, but `download.php?id=N` omits an owner check
- A single SQLite file holds file metadata and accounts
- Upload files are stored on the **Ubuntu VM itself**. No separate storage server or database daemon.

## 0. Preflight

Use a fresh Ubuntu VM with a snapshot. Access the website **from a browser inside the same VM** at `http://127.0.0.1:8080/`. If you need access from your host computer later, use an SSH port forward rather than making the deliberately insecure server publicly reachable.

## 1. Install dependencies

```bash
sudo apt update
sudo apt install -y apache2 php libapache2-mod-php php-cli php-sqlite3 sqlite3 unzip curl
php -v
php -m | grep -iE 'PDO|sqlite'
```

## 2. Install source

Unzip the downloaded archive somewhere in your home directory, then copy the `cs4239-member-a-starter` folder contents to `/var/www/cs4239-lab`.

```bash
cd ~/Downloads
unzip cs4239-member-a-starter.zip
sudo mkdir -p /var/www/cs4239-lab
sudo cp -a cs4239-member-a-starter/. /var/www/cs4239-lab/
```

If the ZIP was extracted elsewhere, adjust the `cd` and source folder path.

## 3. Give Apache write access only to the necessary lab directories

```bash
sudo chown -R www-data:www-data /var/www/cs4239-lab/data
sudo chown -R www-data:www-data /var/www/cs4239-lab/public/scenarios/rce/uploads
sudo chown -R www-data:www-data /var/www/cs4239-lab/public/scenarios/xss/uploads
sudo -u www-data php /var/www/cs4239-lab/scripts/init_db.php
```

## 4. Configure Apache to listen ONLY on loopback

These commands modify Apache's default port configuration, so use a **dedicated VM**, not a machine hosting another site.

```bash
sudo cp /etc/apache2/ports.conf /etc/apache2/ports.conf.cs4239.bak
printf 'Listen 127.0.0.1:8080\n' | sudo tee /etc/apache2/ports.conf
sudo cp /var/www/cs4239-lab/setup/cs4239-lab.conf /etc/apache2/sites-available/cs4239-lab.conf
sudo a2dissite 000-default.conf
sudo a2ensite cs4239-lab.conf
sudo apache2ctl configtest
sudo systemctl restart apache2
sudo ss -ltnp | grep ':8080'
```

The listening address should be `127.0.0.1:8080`, not `0.0.0.0:8080` or `[::]:8080`. If you see an Apache configuration error, **do not continue** until you resolve it.

## 5. First end-to-end smoke tests

1. Open `http://127.0.0.1:8080/` in the Ubuntu VM.
2. Log in as `alice` with `alice123`.
3. Visit each scenario using the dashboard.
4. Upload `tests/fixtures/legitimate.txt` through the RCE page and verify that it has an Open link.
5. Upload `tests/fixtures/rce-marker.php` through the RCE page. Use the Open link and verify the output `LAB_RCE_MARKER_EXECUTED`. **This is a lab-only server-side execution marker, not a reverse shell.**
6. Upload `tests/fixtures/xss-marker.svg` through the XSS page. Click Preview; the marker should change to `LAB_XSS_MARKER_EXECUTED`. This confirms that uploaded active content can execute in the application's browser origin under this configuration.
7. Upload `tests/fixtures/private-alice.txt` through the IDOR page and write down its numeric file ID.
8. Log out and log in as `bob` with `bob123`.
9. Visit `http://127.0.0.1:8080/scenarios/idor/download.php?id=THE_ID`. The Alice document should download, despite Bob not owning it. This is the IDOR vulnerability.

**Never use sensitive files or production sessions in this lab.** If a marker fails, check Apache error log at `/var/log/apache2/cs4239-lab-error.log`, PHP modules, and the file permissions.

## 6. Expected handoff to Member B

- One Git repository containing this source code and setup instructions
- One reproducible snapshot/tag: `vulnerable-baseline`
- The three attack scenarios working on a clean machine
- The exact Apache/PHP versions and a copy of the local site configuration
- Screenshots or notes showing all three lab markers, including Alice/Bob cross-access
- An architecture section and relevant explanations for the report

## 7. Implementation notes / deliberate limitations

- Intentional vulnerabilities are isolated to the lab scenario paths. This is not a secure production application.
- `basename()` and generated storage prefixes avoid *accidental* pathname traversal and overwriting in these exercises; those are outside the three selected investigations.
- Upload size is limited to 1 MB to protect lab stability. There is intentionally **no** file-type/content filtering in the vulnerable scenarios.
- Both direct-upload scenarios keep files under the Apache document root; IDOR files are stored in `data/private/` outside the document root and delivered through a PHP endpoint.
- The IDOR handler intentionally performs login authentication without object-level authorization.
- The app omits production concerns such as CSRF tokens, rate limits, account management, and encryption, so do not claim the final system is production-safe.
- The README test scripts are non-destructive; Member B should record what each security violation actually proves.
