#!/bin/zsh
set -e

XAMPP_BIN="/Applications/XAMPP/xamppfiles/xampp"
URL="http://localhost/VR%20shop/"

# Start Apache and MySQL for local website runtime
sudo "$XAMPP_BIN" startapache
sudo "$XAMPP_BIN" startmysql || true

open "$URL"
echo "VR shop local site started: $URL"
