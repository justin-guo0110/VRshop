#!/bin/zsh
set -e

XAMPP_BIN="/Applications/XAMPP/xamppfiles/xampp"

sudo "$XAMPP_BIN" stopmysql || true
sudo "$XAMPP_BIN" stopapache

echo "VR shop local site stopped."
