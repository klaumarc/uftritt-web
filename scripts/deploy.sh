#!/usr/bin/env bash
#
# Direkter, INKREMENTELLER FTP-Deploy auf Kreativmedia (Plesk / ProFTPD).
#
# Warum so:
#  - FTPS bricht auf diesem Server am Datenkanal ab (ECONNRESET).
#  - Ein rekursives Server-Listing (fuer "nur Geaendertes") killt ebenfalls die
#    Verbindung. Reine STOR-Uploads funktionieren aber zuverlaessig.
#  => Wir vergleichen LOKAL per Pruefsumme (.deploy-manifest.txt) und laden nur
#     geaenderte/neue Dateien hoch. Kein Listing, kein 62-MB-Vollupload.
#
# Benutzung (Passwort nur zur Laufzeit, NICHT im Code):
#   FTP_PW='dasPasswort' bash scripts/deploy.sh            # inkrementell (Standard)
#   FTP_PW='dasPasswort' bash scripts/deploy.sh --full     # alles neu hochladen
#   FTP_PW='dasPasswort' bash scripts/deploy.sh --seed     # nur Manifest setzen, NICHT hochladen
#                                                           # (wenn Server bereits == aktueller Build)
#
set -euo pipefail

HOST="${FTP_HOST:-janus.kreativmedia.ch}"
USER="${FTP_USER:-uftritt_1988}"
PW="${FTP_PW:?Bitte Passwort setzen:  FTP_PW='...' bash scripts/deploy.sh}"
DIST="dist"
MANIFEST=".deploy-manifest.txt"   # lokal, in .gitignore

cd "$(dirname "$0")/.."

MODE="${1:-}"

echo "==> Build (astro)"
npm run build >/dev/null

echo "==> Pruefsummen berechnen"
NEW="$(mktemp)"; UPLOAD="$(mktemp)"; DELS="$(mktemp)"
trap 'rm -f "$NEW" "$UPLOAD" "$DELS"' EXIT
( cd "$DIST" && find . -type f -exec sha256sum {} \; | sort ) > "$NEW"

if [ "$MODE" = "--seed" ]; then
  cp "$NEW" "$MANIFEST"
  echo "==> Manifest gesetzt ($(wc -l < "$MANIFEST") Dateien) – nichts hochgeladen."
  exit 0
fi

# Zu uebertragende Dateien bestimmen
if [ -f "$MANIFEST" ] && [ "$MODE" != "--full" ]; then
  comm -23 "$NEW" <(sort "$MANIFEST") | sed 's/^[0-9a-f]\{64\}  //' > "$UPLOAD"
  # Geloeschte Dateien: Pfad in Manifest, aber nicht mehr im Build
  comm -13 <(sed 's/^[0-9a-f]\{64\}  //' "$NEW" | sort) \
           <(sed 's/^[0-9a-f]\{64\}  //' "$MANIFEST" | sort) > "$DELS"
else
  sed 's/^[0-9a-f]\{64\}  //' "$NEW" > "$UPLOAD"
  : > "$DELS"
fi

UP_N=$(grep -c . "$UPLOAD" || true)
DEL_N=$(grep -c . "$DELS" || true)
echo "==> $UP_N Datei(en) hochladen, $DEL_N loeschen"

n=0
while IFS= read -r f; do
  rel="${f#./}"; [ -z "$rel" ] && continue
  n=$((n+1))
  printf "  [%d/%d] %s\n" "$n" "$UP_N" "$rel"
  curl -s -S --ftp-create-dirs --connect-timeout 25 --max-time 300 \
       --user "$USER:$PW" -T "$DIST/$rel" "ftp://$HOST/$rel" \
    || { echo "FEHLER beim Upload: $rel"; exit 1; }
done < "$UPLOAD"

# Geloeschte Dateien entfernen (best effort)
while IFS= read -r f; do
  rel="${f#./}"; [ -z "$rel" ] && continue
  echo "  loesche: $rel"
  curl -s -S --connect-timeout 25 --max-time 60 \
       --user "$USER:$PW" -Q "DELE /$rel" "ftp://$HOST/" >/dev/null 2>&1 || true
done < "$DELS"

cp "$NEW" "$MANIFEST"
echo "==> Fertig. Live: https://uftritt.mklkreativ.ch/"
