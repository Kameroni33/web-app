#!/usr/bin/env bash
# One-time setup of a fresh Ubuntu server (AWS Lightsail) for Kam Apps.
# Usage: sudo bash scripts/server-setup.sh
# Safe to re-run: every step checks before changing anything.
#
# What it does:
#   1. Adds a 2 GB swap file (image builds need more memory than the app does).
#   2. Installs Docker Engine + the Compose plugin and lets the login user run it.
#   3. Caps Docker's log files so they can't fill the disk.
#   4. Installs the AWS CLI v2 (used by scripts/backup.sh to upload to S3).
#   5. Turns on automatic security updates with a 04:00 UTC reboot when needed.
#   6. Schedules scripts/backup.sh nightly at 03:15 UTC for the login user.
set -euo pipefail

if [[ $EUID -ne 0 ]]; then
  echo "Run with sudo: sudo bash $0" >&2
  exit 1
fi
user="${SUDO_USER:-ubuntu}"
repo="$(cd "$(dirname "$0")/.." && pwd)"

echo "==> Swap"
if ! swapon --show | grep -q /swapfile; then
  fallocate -l 2G /swapfile
  chmod 600 /swapfile
  mkswap /swapfile >/dev/null
  swapon /swapfile
  grep -q '^/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
  echo 'vm.swappiness=10' > /etc/sysctl.d/99-swappiness.conf
  sysctl -q -p /etc/sysctl.d/99-swappiness.conf
fi
swapon --show

echo "==> Docker"
if ! command -v docker >/dev/null; then
  curl -fsSL https://get.docker.com | sh
fi
usermod -aG docker "$user"
mkdir -p /etc/docker
if [[ ! -f /etc/docker/daemon.json ]]; then
  cat > /etc/docker/daemon.json <<'JSON'
{
  "log-driver": "json-file",
  "log-opts": { "max-size": "10m", "max-file": "3" }
}
JSON
  systemctl restart docker
fi
systemctl enable --now docker >/dev/null
docker --version
docker compose version

echo "==> AWS CLI"
if ! command -v aws >/dev/null; then
  apt-get update -qq
  apt-get install -y -qq unzip >/dev/null
  tmp="$(mktemp -d)"
  arch="$(uname -m)" # x86_64 or aarch64
  curl -fsSL "https://awscli.amazonaws.com/awscli-exe-linux-${arch}.zip" -o "$tmp/awscliv2.zip"
  unzip -q "$tmp/awscliv2.zip" -d "$tmp"
  "$tmp/aws/install"
  rm -rf "$tmp"
fi
aws --version

echo "==> Automatic security updates"
apt-get install -y -qq unattended-upgrades >/dev/null
cat > /etc/apt/apt.conf.d/52kam-apps-reboot <<'CONF'
// Reboot at 04:00 UTC when an update needs it. Containers restart on their own.
Unattended-Upgrade::Automatic-Reboot "true";
Unattended-Upgrade::Automatic-Reboot-Time "04:00";
CONF
systemctl enable --now unattended-upgrades >/dev/null

echo "==> Nightly backup (03:15 UTC)"
line="15 3 * * * $repo/scripts/backup.sh >> /home/$user/backup.log 2>&1"
if ! crontab -u "$user" -l 2>/dev/null | grep -qF "$repo/scripts/backup.sh"; then
  (crontab -u "$user" -l 2>/dev/null || true; echo "$line") | crontab -u "$user" -
fi
crontab -u "$user" -l | grep backup.sh

echo
echo "Done. Log out and back in so '$user' can run docker without sudo."
