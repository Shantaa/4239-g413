#!/usr/bin/env bash
# CS4239 G413 — repeatable IDOR test (Member B).
#
# Drives the whole IDOR experiment end to end:
#   1. Logs in as Igor (victim) and uploads a private file.
#   2. Reads back the version-specific identifier assigned to it.
#   3. Runs exploits/idor/<ver>.py, which logs in as Arcie (attacker) and
#      requests Igor's file through the download route with no ownership.
# Receiving Igor's private marker demonstrates unauthorized disclosure (IDOR).
#
# Usage:   tests/idor_test.sh [version] [base_url]
#   version   v1 (default) | v2 | v3 | patched -- application version to test
#   base_url  default http://127.0.0.1:8080  (localhost only)
#
# Exit 0 = IDOR occurred (Arcie read Igor's file), 1 = blocked/placeholder,
#        2 = test harness error.
set -u
REPO="$(cd "$(dirname "$0")/.." && pwd)"
VER="${1:-v1}"
BASE="${2:-http://127.0.0.1:8080}"
PRIVATE="$REPO/exploits/idor/private-igor.txt"
POC="$REPO/exploits/idor/$VER.py"

case "$BASE" in http://127.0.0.1*|http://localhost*) ;; *) echo "localhost only"; exit 2;; esac
[ -f "$PRIVATE" ] || { echo "Missing $PRIVATE"; exit 2; }
[ -f "$POC" ]     || { echo "Missing PoC $POC"; exit 2; }

CJ="$(mktemp)"; trap 'rm -f "$CJ"' EXIT

echo "== IDOR test: version=$VER base=$BASE =="
echo "-- Victim Igor logs in"
curl -s -L -c "$CJ" -b "$CJ" -d "username=igor&password=password123" \
     "$BASE/login.php" | grep -q "Signed in as <strong>igor" \
  || { echo "FAIL: Igor login failed"; exit 2; }

if [ "$VER" = "patched" ]; then
  UPLOAD_URL="$BASE/scenarios/patched/idor/upload.php"
  LIST_URL="$BASE/scenarios/patched/idor/"
else
  UPLOAD_URL="$BASE/scenarios/idor/$VER/upload.php"
  LIST_URL="$BASE/scenarios/idor/$VER/"
fi
echo "-- Igor uploads his private file to $UPLOAD_URL"
code=$(curl -s -o /dev/null -w '%{http_code}' -b "$CJ" \
       -F "userfile=@$PRIVATE;type=text/plain" \
       "$UPLOAD_URL")
if [ "$code" != "200" ] && [ "$code" != "302" ]; then
  echo "Upload returned HTTP $code (version likely not implemented)."; exit 1
fi

echo "-- Reading back the identifier assigned to Igor's newest upload"
if [ "$VER" = "patched" ]; then
  FID=$(curl -s -b "$CJ" "$LIST_URL" \
        | grep -oE '<td>[a-f0-9]{64}</td>' | head -1 | sed -E 's#</?td>##g')
elif [ "$VER" = "v2" ] || [ "$VER" = "v3" ]; then
  FID=$(curl -s -b "$CJ" "$LIST_URL" \
        | grep -oE '<td>[a-f0-9]{32}</td>' | head -1 | sed -E 's#</?td>##g')
else
  FID=$(curl -s -b "$CJ" "$BASE/scenarios/idor/$VER/" \
        | grep -oE '<td>[0-9]+</td>' | head -1 | grep -oE '[0-9]+')
fi
[ -n "$FID" ] || { echo "FAIL: could not read Igor's file identifier"; exit 2; }
echo "   Igor's file identifier = $FID"

echo "-- Running attacker PoC as Arcie: python3 exploits/idor/$VER.py $FID"
echo "------------------- PoC output -------------------"
python3 "$POC" "$FID" --base "$BASE"
rc=$?
echo "--------------------------------------------------"
if [ $rc -eq 0 ]; then
  echo "RESULT: *** IDOR CONFIRMED *** Arcie read Igor's private file (PoC exit 0)."
else
  echo "RESULT: no IDOR — Arcie was denied Igor's file (PoC exit $rc)."
fi
exit $rc
