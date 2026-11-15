# First-time deployment to AWS

A step-by-step guide for moving Kam Apps from the laptop to a small AWS Lightsail server. Follow it top to bottom once. Day-to-day upkeep after that lives in [AWS.md](AWS.md) and [CLOUDFLARE.md](CLOUDFLARE.md).

**Time:** about 2 hours. The site is only down for the ~10 minutes of the cutover (step 9).
**Cost:** roughly $8–10/month. See [AWS.md → Costs](AWS.md#costs).

## What you'll end up with

```
Family's phones ──HTTPS──▶ Cloudflare edge (TLS, Access, cache)
                                 │  Cloudflare Tunnel (outbound from the server; no open web ports)
                                 ▼
                 AWS Lightsail instance "kam-apps" (Ubuntu, Docker)
                   cloudflared → nginx → php-fpm (Laravel) ─┬─ Postgres
                                         horizon, scheduler ┴─ Redis
                                 │ nightly pg_dump
                                 ▼
                 S3 bucket (versioned, 90-day lifecycle) + Lightsail daily snapshots
```

It's the same Docker Compose stack the laptop runs today, on a machine that doesn't sleep. The Cloudflare side doesn't change: the same tunnel token moves to the new server.

## Why Lightsail

Lightsail is AWS's flat-price VPS: one monthly price covers the CPU, disk, a static IP and 1–2 TB of data transfer. A plain EC2 instance with the same specs costs more once you add the public IPv4 charge and the disk, and the bill varies. ECS/Fargate plus RDS and ElastiCache would be $60+/month. The whole app uses about 150 MB of RAM. If it outgrows Lightsail later, the same Compose stack runs on EC2 unchanged.

## Before you start

- [x] You have an AWS account (step 0 sets up a safe login for it).
- [x] Your laptop's stack is running and the site works.
- [x] You have your SSH public key (`cat ~/.ssh/id_ed25519.pub`). If you don't have one: `ssh-keygen -t ed25519`.
- [x] **Until the cutover, add `COMPOSE_PROFILES=tunnel` to the laptop's `.env`.** `compose.yaml` now starts `cloudflared` only when that profile is on. Without it, a rebuild on the laptop wouldn't start the tunnel and the live site would go down.

> **Golden rule:** only one machine may run `cloudflared` with the production token. If two run at once, Cloudflare splits visitors between them, and each machine has its own database.

---

## 0. Stop using the root login

Root can do anything, including closing the account, and can't be limited. Use it only for the jobs only root can do, and do everything else as an IAM admin user.

Signed in as **root**:

1. **Root MFA:** top-right account menu → *Security credentials* → *Assign MFA device*. Use a passkey or an authenticator app.
2. **Let IAM users see billing:** account menu → *Account* → *IAM user and role access to Billing information* → *Edit* → **Activate**. Without this, even an admin user can't open Budgets or Billing.
3. **Create the admin user:** IAM → *Users* → *Create user*.
   - User name: `kameron`.
   - Tick **Provide user access to the AWS Management Console** → *I want to create an IAM user*. AWS suggests Identity Center instead; that's overkill for one person.
   - Set a password and untick *must create a new password*.
   - Permissions: *Attach policies directly* → **AdministratorAccess**.
4. Save the **console sign-in URL** shown at the end (`https://<account-id>.signin.aws.amazon.com/console`). Bookmark it.
5. Sign out, then sign in at that URL as `kameron`. IAM → *Users* → `kameron` → *Security credentials* → **Assign MFA device**.

From here on, sign in as `kameron`. Put root's password and MFA somewhere safe and leave them alone.

The laptop CLI uses this user too: `aws login --profile kam-apps` opens the browser, and you sign in as `kameron`. Credentials last 12 hours and renew automatically for 90 days.

## 1. Put a spending alarm on the account

Do this first so a mistake can't run up a bill quietly.

1. Console → **Billing and Cost Management → Budgets → Create budget**.
2. Pick the **Monthly cost budget** template. Name it `monthly-cap` and set the amount to **$20**.
3. Enter your email and create it. AWS emails you at 85% and 100% of actual spend, and when the forecast goes over.

## 2. Create the server

1. Open the [Lightsail console](https://lightsail.aws.amazon.com/) → **Create instance**.
2. **Location:** the AWS region nearest your family, e.g. `ca-central-1` (Montréal) or `us-west-2` (Oregon). Everything else you create should use the same region.
3. **Platform:** Linux/Unix. **Blueprint:** *OS Only* → the newest **Ubuntu LTS** listed (24.04 or 26.04).
4. **SSH key pair:** *Change SSH key pair* → *Upload new* → paste your public key and name it `kam-apps`.
5. **Network type:** *Dual-stack*. Not IPv6-only: GitHub has no IPv6, so `git pull` would fail.
6. **Size:** the **1 GB RAM** plan (about $7/month; check the price shown). Builds use the swap file the setup script adds. If deploys feel slow, pick 2 GB instead; you can move up later from a snapshot.
7. **Name:** `kam-apps` → **Create instance**. It's ready in about a minute.

## 3. Lock down networking

On the instance page:

1. **Networking → IPv4 Firewall:** delete the **HTTP (80)** rule. Keep **SSH (22)**. Web traffic arrives through the tunnel, so no web port is needed.
   - Optional: edit the SSH rule → *Restrict to IP address* → your home IP, and tick *Allow Lightsail browser SSH*. If your home IP changes, you can still get in from the browser terminal and update the rule.
2. Do the same on the **IPv6 Firewall** tab (delete HTTP).
3. **Networking → Attach static IP** → name it `kam-apps-ip`. It's free while attached and keeps the address the same across stops and starts.
4. **Snapshots → Automatic snapshots: On**, at **09:00 UTC** (after the 03:15 backup). Lightsail keeps the last 7 daily snapshots.

Write the static IP into the table in [AWS.md → Inventory](AWS.md#inventory).

## 4. Connect over SSH

On the laptop, add this to `~/.ssh/config`:

```
Host kam-apps
    HostName <static IP>
    User ubuntu
    IdentityFile ~/.ssh/id_ed25519
```

Then run `ssh kam-apps`. Accept the host fingerprint the first time.

## 5. Install Docker and the basics

On the server:

```bash
git clone https://github.com/Kameroni33/web-app.git ~/web-app
cd ~/web-app
sudo bash scripts/server-setup.sh
exit          # log out and back in so the docker group applies
```

`server-setup.sh` is safe to re-run. It:

- adds a 2 GB swap file;
- installs Docker and the Compose plugin, and caps Docker logs at 30 MB per container;
- installs the AWS CLI;
- turns on automatic security updates, rebooting at 04:00 UTC when one needs it;
- adds a cron job for the 03:15 UTC backup.

Check it worked with `ssh kam-apps` then `docker run --rm hello-world`.

## 6. Set up off-site backups (S3)

### 6a. Bucket

1. Console → **S3 → Create bucket**, in the same region.
2. **Name:** `kam-apps-backups-<something random>` (names are global), e.g. `kam-apps-backups-7f3k`.
3. Leave **Block all public access** on. Turn **Bucket Versioning** on. Create it.
4. Open the bucket → **Management → Create lifecycle rule**:
   - Name: `expire-old-dumps`. Scope: prefix `db/`.
   - Actions: *Expire current versions* after **90** days, and *Permanently delete noncurrent versions* after **7** days.

### 6b. A key that can only upload

1. Console → **IAM → Policies → Create policy → JSON**, and paste this (with your bucket name):

   ```json
   {
     "Version": "2012-10-17",
     "Statement": [
       { "Effect": "Allow", "Action": "s3:ListBucket", "Resource": "arn:aws:s3:::BUCKET" },
       { "Effect": "Allow", "Action": "s3:PutObject", "Resource": "arn:aws:s3:::BUCKET/db/*" }
     ]
   }
   ```

   Name it `kam-apps-backup-upload`.
2. **IAM → Users → Create user** `kam-apps-backup`. No console access. **Attach policies directly** → `kam-apps-backup-upload`.
3. Open the user → **Security credentials → Create access key** → *Command Line Interface* → copy both values.

The key can add backups but can't download or delete them. A compromised server can't wipe its own backups, and versioning keeps anything it overwrites for 7 days.

### 6c. Give the key to the server

```bash
aws configure --profile kam-backup
# Access key ID / Secret: from 6b. Region: your region. Output: json
aws --profile kam-backup s3 ls s3://<bucket>     # no error = it works (it's empty for now)
```

## 7. Create the production `.env`

Start from the laptop's `.env`. It already has the secrets that must not change. Copy it across:

```bash
# on the laptop
scp .env kam-apps:~/web-app/.env
```

Then on the server run `nano ~/web-app/.env` and make these changes:

| Setting | Server value | Why |
| --- | --- | --- |
| `COMPOSE_FILE` | **delete the line** | Laptop-only dev overlay (live-mounts code) |
| `COMPOSE_PROFILES` | `tunnel` | Starts `cloudflared` here |
| `APP_ENV` / `APP_DEBUG` | `production` / `false` | |
| `APP_URL` | `https://app.network-kam.com` | Used in emailed sign-in links |
| `SESSION_SECURE_COOKIE` | `true` | HTTPS-only cookies |
| `BACKUP_S3_BUCKET` | your bucket name | Turns on the S3 upload |

**Keep these exactly as they are:**

- `APP_KEY`: sessions, sign-in links and codes are signed with it. Changing it signs everyone out and breaks any link already sent.
- `DB_PASSWORD`, `MAIL_PASSWORD` and `TUNNEL_TOKEN`.

Then lock the file down: `chmod 600 ~/web-app/.env`.

## 8. Build and start everything except the tunnel

Name the services explicitly so `cloudflared` doesn't start yet; the laptop is still serving the site:

```bash
cd ~/web-app
docker compose up -d --build postgres redis app horizon scheduler nginx
docker compose exec -T app php artisan migrate --force
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:23417/up    # expect 200
```

The first build takes 5–10 minutes on the 1 GB plan.

## 9. Cutover

The site is down from step 3 to step 5 of this list.

1. **Laptop:** stop writes, then take the final dump:

   ```bash
   docker compose stop app horizon scheduler
   scripts/db-dump.sh backups/cutover.dump
   ```

2. **Laptop:** copy the dump to the server: `scp backups/cutover.dump kam-apps:~/`
3. **Laptop:** stop the tunnel. **The site is now down.**

   ```bash
   docker compose stop cloudflared
   ```

4. **Server:** restore the data and run migrations:

   ```bash
   cd ~/web-app
   scripts/db-restore.sh ~/cutover.dump        # type: yes
   docker compose exec -T app php artisan migrate --force
   ```

5. **Server:** start everything, tunnel included (the profile comes from `.env`). **The site is back.**

   ```bash
   docker compose up -d
   docker compose ps
   ```

6. **Cloudflare dashboard → Zero Trust → Networks → Tunnels → `web-app`:** it should show **Healthy**, with one connector whose origin IP is the static IP.

You should still be signed in on your phone, because sessions live in the database and `APP_KEY` didn't change.

## 10. Check everything

- [ ] `https://app.network-kam.com` loads, and your gift list is there.
- [ ] Sign out, then sign in. The code email arrives, which proves Horizon (the queue) and email both work.
- [ ] Settings → About shows the same app and server version.
- [ ] Run `~/web-app/scripts/backup.sh`. It says *Uploaded to s3://…*, and the file appears in the S3 console under `db/`.
- [ ] Tomorrow: `~/backup.log` shows last night's run, and Lightsail → Snapshots shows an automatic snapshot.

## 11. Turn the laptop back into a dev machine

On the laptop, edit `.env`:

- **Delete `TUNNEL_TOKEN` and `COMPOSE_PROFILES`.** The laptop must never connect to the production tunnel again.
- `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL=http://localhost:23417`.
- **Delete `SESSION_SECURE_COOKIE`** (the laptop uses plain http).
- `MAIL_MAILER=log`, so dev sign-in codes go to the log instead of real inboxes.
- Keep `COMPOSE_FILE=compose.yaml:compose.dev.yaml`.

Then:

```bash
docker compose rm -sf cloudflared
docker compose up -d
```

The laptop keeps its own copy of the data as of the cutover. It's fine for development, but it's no longer the real data.

## 12. Let family in

Cloudflare Access currently lets only your email reach the site, so invite links won't work for anyone else. Pick one:

- **Remove the Access application.** The app does its own invite-only sign-in, and this is what it was built for. **Recommended.**
- Or add each family member's email to the Access policy.

Both are covered in [CLOUDFLARE.md → Access](CLOUDFLARE.md#access-who-can-reach-the-site).

Then make your account an admin so you can create families and invites:

```bash
ssh kam-apps
cd ~/web-app && docker compose exec app php artisan accounts:admin khr675@gmail.com
```

## 13. Fill in the record

Update the **Inventory** table in [AWS.md](AWS.md#inventory) with the region, static IP, bucket name and today's date, and add a line to its **Change log**. Commit it. Future you will thank you.

---

**From now on, deploying is one command:** `ssh kam-apps '~/web-app/scripts/deploy.sh'`. See [AWS.md → Deploy](AWS.md#deploy-a-new-version).
