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
# Ziel-Verzeichnis (FTP-Root = Subscription-Ebene, seit 2026-08-14):
#   FTP_DIR=uftritt.mklkreativ.ch   # Staging (Default)
#   FTP_DIR=uftritt.ch-neu          # Produktion (Docroot-Wechsel via Plesk)
# Pro Ziel gibt es ein eigenes Manifest (.deploy-manifest[-<ziel>].txt).
#
set -euo pipefail

HOST="${FTP_HOST:-janus.kreativmedia.ch}"
USER="${FTP_USER:-uftritt_1988}"
PW="${FTP_PW:?Bitte Passwort setzen:  FTP_PW='...' bash scripts/deploy.sh}"
DIST="dist"
FTP_DIR="${FTP_DIR:-uftritt.mklkreativ.ch}"

if [ "$FTP_DIR" = "uftritt.mklkreativ.ch" ]; then
  MANIFEST=".deploy-manifest.txt"   # lokal, in .gitignore
else
  MANIFEST=".deploy-manifest-$(echo "$FTP_DIR" | tr '/.' '__').txt"
fi

cd "$(dirname "$0")/.."

MODE="${1:-}"

echo "==> Build (astro)"
npm run build >/dev/null

echo "==> Pruefsummen berechnen (Ziel: $FTP_DIR)"
NEW="$(mktemp)"; UPLOAD="$(mktemp)"; DELS="$(mktemp)"
trap 'rm -f "$NEW" "$UPLOAD" "$DELS"' EXIT
( cd "$DIST" && find . -type f -exec sha256sum {} \; | sort ) > "$NEW"

if [ "$MODE" = "--seed" ]; then
  cp "$NEW" "$MANIFEST"
  echo "==> Manifest gesetzt ($(wc -l < "$MANIFEST") Dateien) – nichts hochgeladen."
  exit 0
fi

# Zu uebertragende Dateien bestimmen
# Manifest immer CR-bereinigt lesen: ein Manifest mit CRLF (z. B. nach Lauf aus
# PowerShell heraus) laesst comm alle Zeilen als "abweichend" sehen und loescht
# sonst den ganzen Server-Bestand (passiert 2026-11-04).
MAN_CLEAN="$(mktemp)"; trap 'rm -f "$NEW" "$UPLOAD" "$DELS" "$MAN_CLEAN"' EXIT
[ -f "$MANIFEST" ] && tr -d '\r' < "$MANIFEST" > "$MAN_CLEAN" || : > "$MAN_CLEAN"
if [ -s "$MAN_CLEAN" ] && [ "$MODE" != "--full" ]; then
  comm -23 "$NEW" <(sort "$MAN_CLEAN") | sed 's/^[0-9a-f]\{64\} \*\{0,1\}//' > "$UPLOAD"
  # Geloeschte Dateien: Pfad in Manifest, aber nicht mehr im Build
  comm -13 <(sed 's/^[0-9a-f]\{64\} \*\{0,1\}//' "$NEW" | sort) \
           <(sed 's/^[0-9a-f]\{64\} \*\{0,1\}//' "$MAN_CLEAN" | sort) > "$DELS"
else
  sed 's/^[0-9a-f]\{64\} \*\{0,1\}//' "$NEW" > "$UPLOAD"
  : > "$DELS"
fi

UP_N=$(grep -c . "$UPLOAD" || true)
DEL_N=$(grep -c . "$DELS" || true)
echo "==> $UP_N Datei(en) hochladen, $DEL_N loeschen"

# Sicherheitsgurt: Massen-DELE (mehr als 20 Dateien oder mehr als 5 % des
# Builds) deutet fast immer auf ein kaputtes Manifest hin, nicht auf echte
# Loeschungen. Dann abbrechen statt die Site zu demolieren.
TOTAL=$(wc -l < "$NEW")
if [ "$DEL_N" -gt 20 ] || [ "$DEL_N" -gt $((TOTAL / 20)) ]; then
  echo "ABBRUCH: $DEL_N Loeschungen bei $TOTAL Build-Dateien wirkt verdaechtig." >&2
  echo "Manifest pruefen (Zeilenenden?) oder bewusst --full deployen." >&2
  exit 1
fi

n=0
while IFS= read -r f; do
  rel="${f#./}"; [ -z "$rel" ] && continue
  n=$((n+1))
  printf "  [%d/%d] %s\n" "$n" "$UP_N" "$rel"
  curl -s -S --ftp-create-dirs --connect-timeout 25 --max-time 300 \
       --user "$USER:$PW" -T "$DIST/$rel" "ftp://$HOST/$FTP_DIR/$rel" \
    || { echo "FEHLER beim Upload: $rel"; exit 1; }
done < "$UPLOAD"

# Geloeschte Dateien entfernen (best effort)
while IFS= read -r f; do
  rel="${f#./}"; [ -z "$rel" ] && continue
  echo "  loesche: $rel"
  curl -s -S --connect-timeout 25 --max-time 60 \
       --user "$USER:$PW" -Q "DELE /$FTP_DIR/$rel" "ftp://$HOST/" >/dev/null 2>&1 || true
done < "$DELS"

cp "$NEW" "$MANIFEST"
echo "==> Fertig. Ziel: ftp://$HOST/$FTP_DIR/"
