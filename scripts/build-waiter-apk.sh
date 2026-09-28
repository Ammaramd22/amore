#!/usr/bin/env bash
# Build an Android APK that wraps the QRPOS Waiter Panel PWA (Trusted Web Activity).
# Requires: Node.js, Java 17+, Android SDK, and a public HTTPS URL for the POS.
#
# Usage:
#   export WAITER_URL="https://your-domain.com"
#   ./scripts/build-waiter-apk.sh
#
# Output: android APK via Bubblewrap (Google's PWA → Play/sideload tooling).

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
APP_DIR="${ROOT}/mobile/qrpos-waiter-twa"
WAITER_URL="${WAITER_URL:-}"
PACKAGE_ID="${PACKAGE_ID:-io.avenque.qrpos.waiter}"

if [[ -z "$WAITER_URL" ]]; then
  echo "Set WAITER_URL to your live HTTPS site, e.g.:"
  echo "  export WAITER_URL=https://pos.yourrestaurant.com"
  echo "  ./scripts/build-waiter-apk.sh"
  exit 1
fi

# Manifest must be reachable on the live host after you deploy this repo.
MANIFEST_URL="${WAITER_URL%/}/manifest-waiter.webmanifest"

if ! command -v npx >/dev/null 2>&1; then
  echo "Node.js / npx is required."
  exit 1
fi

mkdir -p "$(dirname "$APP_DIR")"
if [[ ! -d "$APP_DIR" ]]; then
  echo "Initializing Bubblewrap TWA project..."
  npx --yes @bubblewrap/cli init \
    --manifest "$MANIFEST_URL" \
    --directory "$APP_DIR" \
    --packageId "$PACKAGE_ID" \
    --name "QRPOS Waiter Panel" \
    --launcherName "QRPOS Waiter" \
    --display standalone \
    --themeColor "#1c1410" \
    --backgroundColor "#1c1410" \
    --startUrl "/waiter/dashboard?source=pwa" \
    --iconUrl "${WAITER_URL%/}/pwa/waiter/icon-512.png" \
    --maskableIconUrl "${WAITER_URL%/}/pwa/waiter/maskable-512.png" || true
fi

cd "$APP_DIR"
echo "Building APK..."
npx --yes @bubblewrap/cli build
echo ""
echo "Done. Look for app-release-signed.apk (or similar) under:"
echo "  $APP_DIR/app/build/outputs/apk/"
echo ""
echo "Install on a waiter phone: adb install -r <apk>"
echo "Or share the APK for sideload. For Play Store, use the AAB Bubblewrap produces."
