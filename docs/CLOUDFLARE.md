# Cloudflare: DNS, tunnel, access and email

Cloudflare sits in front of everything. It owns the domain's DNS, terminates HTTPS, carries traffic to the server through a tunnel, can gate who reaches the site (Access), and sends the app's email. The server side is in [AWS.md](AWS.md).

Dashboard: <https://dash.cloudflare.com>. Most of the app-related settings are under **Zero Trust** (left sidebar, opens one.dash.cloudflare.com).

> Items marked **☐ verify** were recorded from memory of the setup sessions, not checked against the dashboard. Tick them off, or correct them, next time you're in there.

## Quick reference

| I want to… | Where |
| --- | --- |
| See whether the tunnel is connected | Zero Trust → Networks → **Tunnels** → `web-app` (status and connectors) |
| Change who can reach the site | Zero Trust → Access → **Applications** |
| Check email sending | Dashboard → `network-kam.com` → **Email** → Email Sending |
| Edit DNS | Dashboard → `network-kam.com` → **DNS → Records** |
| Clear a stale cached file | Dashboard → `network-kam.com` → **Caching → Configuration → Purge Cache → Custom purge** |

## Inventory

| Thing | Value |
| --- | --- |
| Zone (domain) | `network-kam.com` |
| App hostname | `app.network-kam.com` → tunnel `web-app` |
| Tunnel | `web-app`, remotely managed (configured in the dashboard, token-based) |
| Tunnel public hostname | `app.network-kam.com` → service `HTTP` `nginx:80` |
| Tunnel connector | `cloudflared` container on the Lightsail server (`compose.yaml`, profile `tunnel`) |
| Access application | Covers `app.network-kam.com`; policy allows only your email. **☐ verify**, and see *Access* below (it should probably be removed at launch). |
| Email sending domain | `mail.network-kam.com`, from `noreply@mail.network-kam.com` |
| SMTP | `smtp.mx.cloudflare.net:465` (implicit TLS), user `api_token`, password = an API token with **Email Sending: Edit** |
| Plan | Free zone. Email Sending needs the **Workers Paid** plan (~$5/mo). **☐ verify** in Billing that it's active. |
| Other DNS | The Minecraft server records predate this app; don't touch them. **☐ verify** the names. |

## Tunnel: how traffic reaches the server

```
browser ─HTTPS─▶ Cloudflare edge ─(tunnel)─▶ cloudflared ─HTTP─▶ nginx:80 (inside Docker network "app")
```

- `cloudflared` keeps an *outbound* connection open to Cloudflare. The server needs no open web ports and no public certificate; Cloudflare issues and renews the HTTPS certificate for `app.network-kam.com` itself.
- The tunnel's settings, including the public hostname → `http://nginx:80`, live in the dashboard. The server only holds the **token** (`TUNNEL_TOKEN` in `.env`).
- `nginx:80` is the Compose service name, resolved inside the Docker network. That's why the hostname isn't `localhost`.
- **Only one machine may run the connector.** Several connectors with the same token are treated as replicas, and traffic is split between them. That's why `cloudflared` only starts with `COMPOSE_PROFILES=tunnel`, and the laptop must never have `TUNNEL_TOKEN` set.

**Check health:**

- Zero Trust → Networks → Tunnels: `web-app` should say **Healthy**, with **one** connector whose origin IP is the Lightsail static IP.
- On the server: `docker compose logs --tail=50 cloudflared`, which shows "Registered tunnel connection" ×4.

**Rotate the token** (if `.env` ever leaks):

1. Tunnels → `web-app` → *Configure* → *Refresh token* (or *Rotate*), then copy the new token.
2. On the server, set `TUNNEL_TOKEN=` to the new value in `.env`.
3. Run `docker compose up -d cloudflared`.

The old token stops working. Expect a few seconds of downtime.

**Move the tunnel to a new server:** stop `cloudflared` on the old one *first*, then start it on the new one with the same `.env`. Nothing changes in Cloudflare.

**Errors you might see:**

- **1033:** no connector is running.
- **502/504 from Cloudflare:** the connector is up but nginx isn't answering, so check `docker compose ps`.

## Access: who can reach the site

Cloudflare Access puts a Cloudflare sign-in page in front of the hostname, *before* any request reaches the server. It was turned on while the app was being built, with only your email allowed.

- **Why it matters now:** family members can't open invite links, or even the login page, while Access only allows you.
- **The app doesn't need it.** Its own sign-in is invite-only, with email codes and server-side rate limits, and debug mode is off in production.

**At launch, choose one:**

1. **Remove it (recommended):** Zero Trust → Access → Applications → the `app.network-kam.com` app → *Delete*. The site is then public, but useless without an invite.
2. **Keep it and list people:** edit the application's policy → *Include → Emails* → add each person's email. Each person then signs in twice: Cloudflare's PIN email, then the app's code. Invitees you forget to add get stuck.

Possible later use: protect only an admin path, or a future Finance app, with its own Access application on that path or subdomain.

## Email: sign-in codes and invites

The app sends mail over SMTP through **Cloudflare Email Sending**:

- Laravel queues the mail, and the `horizon` container sends it.
- Settings are in `.env`: `MAIL_*`. The defaults are documented in `.env.example`.
- Mail comes from `noreply@mail.network-kam.com`. Using a subdomain keeps the root domain's reputation and records separate.
- **Credential:** `MAIL_PASSWORD` is a Cloudflare **API token** with the *Email Sending: Edit* permission (My Profile → API Tokens), not a password.

**DNS records for deliverability.** Email Sending onboarding adds these on `mail.network-kam.com`:

- **SPF** (TXT);
- **DKIM** (TXT/CNAME);
- a **DMARC** policy (`_dmarc`, TXT, start with `p=none`).

At one point the DNS page showed none of them. **☐ verify** that Email → Email Sending shows the domain as *verified*, and that those records exist. Without them Gmail may spam-folder or reject sign-in codes.

**Rotate the SMTP token:**

1. Create a new API token with the same permission.
2. Update `MAIL_PASSWORD` in the server's `.env`.
3. Run `docker compose up -d app horizon`.
4. Send yourself a sign-in code.
5. Delete the old token.

**Email isn't arriving:**

1. Check the spam folder.
2. On the server: `docker compose logs --since=30m horizon app | grep -i -E "mail|smtp|exception"`.
3. Is the API token still valid (not expired, not deleted)?
4. Email Sending → activity/logs in the dashboard.
5. As a stopgap, `MAIL_MAILER=log` makes codes appear in `docker compose logs app`, so you can read one out to a family member.

**Receiving mail** (e.g. `admin@network-kam.com` → Gmail) would be **Email Routing** (free). **☐ verify** whether it's set up. The app doesn't need it.

## Caching

Cloudflare caches some file types (`.svg`, `.png`, `.js`, `.css`…) at its edge by default. The app controls this with `Cache-Control` headers from nginx (`docker/nginx/default.conf`):

| Path | Header | Why |
| --- | --- | --- |
| `/assets/*` (hashed JS/CSS) | `immutable`, 1 year | File names change on every build |
| `/<name>-<8 hex>.(png\|ico\|svg)` (hashed icons) | `immutable`, 1 year | The icon name carries a content hash |
| `/index.html`, `/sw.js`, `/manifest.webmanifest`, everything else | `no-cache` | Must revalidate, so updates reach phones |

**Lesson learned:** a fixed-name `/logo.svg` once stayed stale for hours because the edge cached it and the service worker precached the stale copy. New static files should either get a hashed name or fall under `no-cache`. If something is stuck: Caching → Purge Cache → *Custom purge* → the full URL.

## Security settings

- **SSL/TLS:** the edge certificate is automatic. The tunnel connection is encrypted, so the "SSL mode" setting doesn't affect tunnelled traffic.
- **Real client IPs:** Laravel trusts the forwarded headers (`trustProxies(at: '*')` in `backend/bootstrap/app.php`). That's safe because the only way in is the tunnel.
- **Rate limiting:** done in the app, backed by Redis:
  - sign-in requests: 5/hour per email and 30/hour per IP;
  - code checks: 10/minute per IP.
- **Not done yet:** a Cloudflare WAF rate-limit rule on `/api/auth/*` would add an edge layer. It's optional.

## Change log

| Date | Change |
| --- | --- |
| 2026 (Phase 1) | Tunnel `web-app` created with public hostname `app.network-kam.com` → `http://nginx:80`. Access application limited to your email. Email Sending set up for `mail.network-kam.com`. |
| 2026-11 | nginx cache headers changed after the stale-logo incident (hashed icons, `no-cache` elsewhere). |
| 2026-11-14 | `cloudflared` moved behind the Compose `tunnel` profile ahead of the move to AWS. |
