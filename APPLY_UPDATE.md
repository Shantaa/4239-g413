# Apply this update to your existing repository

1. In Ubuntu, ensure current work is committed or backed up. Download the ZIP and extract it to `~/Downloads/`.
2. Copy **the extracted folder's contents** into the repository root:

```bash
cd ~/4239-g413
git status
cp -a ~/Downloads/cs4239-flat-exploits-update/. .
```

3. The old versioned folders are superseded by the new flat files. **First review them for any custom payloads or results you want to keep**:

```bash
find exploits -type f | sort
```

   If they contain only the old starter files, remove the obsolete tracked paths:

```bash
git rm -r --ignore-unmatch \
  exploits/rce/v1 exploits/rce/v2 exploits/rce/v3 \
  exploits/xss/v1 exploits/xss/v2 exploits/xss/v3 \
  exploits/idor/v1 exploits/idor/v2 exploits/idor/v3 \
  exploits/rce/fixtures exploits/xss/fixtures exploits/idor/fixtures \
  exploits/common exploits/README.md
```

   If `exploits/results/` contains useful evidence, move/copy it into the new root `results/` first. Then remove only the old tracked results placeholder when it is no longer needed:

```bash
git rm -r --ignore-unmatch exploits/results
```

4. Check the new files and PHP syntax:

```bash
ls exploits/rce/*.php exploits/xss/*.svg exploits/idor/*.py
php -l exploits/rce/v1.php
python3 exploits/idor/v1.py --help
```

5. Review and commit:

```bash
git add -A
git status
git diff --cached --stat
git commit -m "Flatten exploit files and simplify README"
git push
```

**No Apache restart, PHP deployment or database migration is needed:** this update only changes Git-managed test inputs, `.gitignore` and documentation. Test the new RCE input by uploading `exploits/rce/v1.php` through the browser as usual. Application V2/V3 routes remain placeholders.

The RCE and XSS inputs are identical across V1/V2/V3 to support fair comparisons; the three IDOR request scripts share identical logic, deriving their target version from their own filenames. IDOR scripts are **run locally, not uploaded**.
