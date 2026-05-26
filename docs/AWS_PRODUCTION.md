# Running miniCal on AWS (production)

This app is **PHP 8.2 + MySQL/MariaDB** (see [`README.md`](../README.md)) with two web roots you must expose correctly:

| Purpose | Typical URL (examples) | Physical path |
|--------|-------------------------|---------------|
| Main app (CodeIgniter) | `https://your-domain.com/public/` | `public/index.php` |
| HTTP API | `https://your-domain.com/api/` | `api/index.php` |

Local Docker uses Nginx rewrites so both live under one host (see [`docker/nginx.conf`](../docker/nginx.conf)). **Use the same pattern in production:** one hostname (or two subdomains) with rules like that sample, not “raw” PHP files in the document root only.

Configuration is driven by **environment variables** (see [`.env.example`](../.env.example)); `PROJECT_URL` and `API_URL` must exactly match what browsers and your server use (scheme + host + path).

---

## 1. Choose a deployment shape

### Option A — **Amazon EC2 + RDS** (common, full control)

1. **VPC** — Public subnets for load balancer (or single EC2); **private subnet for RDS**; security groups least-privilege.
2. **RDS** — MySQL 8.x or MariaDB compatible with the app. Allow inbound **3306 only from the EC2 (or ECS) security group**.
3. **Compute** — One or more EC2 instances:
   - **Either** install **Nginx + PHP 8.2-FPM** on the box and clone the repo, `composer install --no-dev`, point Nginx `root` at the **repository root** (same layout as Docker: app root contains `public/` and `api/`).
   - **Or** run **`docker compose` on EC2** using [`docker/docker-compose.yaml`](../docker/docker-compose.yaml): **remove or override the `db` service** and set `DATABASE_HOST` to your RDS endpoint.
4. **TLS** — **Application Load Balancer (ALB)** + **AWS Certificate Manager (ACM)** certificate, **HTTPS listener** forwarding to targets on port 80 (or 443 end-to-end if you terminate TLS on Nginx).
5. **DNS** — Route 53 **A/AAAA alias** (or CNAME) to the ALB.

### Option B — **AWS Lightsail** (simplest)

- Lightsail **database** (MySQL) + **instance** or **container** running Nginx + PHP-FPM (or Docker as above).
- Attach a **static IP**, use Lightsail **load balancer + certificate** if you need scaling or managed TLS.
- Same env vars and Nginx routing ideas as EC2.

### Option C — **Amazon ECS on Fargate**

- Build a **production image** (Nginx + PHP-FPM in one task, or two containers with a shared volume). The dev [`docker/PHP.Dockerfile`](../docker/PHP.Dockerfile) enables **Xdebug** — **do not use that Dockerfile as-is in production**; use an image with only `pdo_mysql` / `mysqli` (and any extensions you need, e.g. `gd`, `intl`).
- Push to **ECR**; service behind **ALB**; secrets from **Secrets Manager** or **SSM Parameter Store** injected as env.
- **RDS** in the same VPC.

---

## 2. Production `.env` checklist

Create `.env` on the server (never commit it). Minimal flags:

| Variable | Production |
|----------|------------|
| `ENVIRONMENT` | `production` |
| `DATABASE_*` | RDS endpoint, user, password, database name |
| `PROJECT_URL` | Full base URL to the **public** app, e.g. `https://your-domain.com/public/` (trailing slash per your [`config.php`](../public/application/config/config.php) / routing) |
| `API_URL` | Full base URL to **api**, e.g. `https://your-domain.com/api/` |
| `CRON_AUTH_SECRET` | Long random string; required for secured cron HTTP calls |
| `SMTP_*` | Transactional email (booking, alerts) |
| `CURL_SSL_VERIFY` | `1` |
| `IS_HOSTED_PROD_SERVICE` | `1` only if you use the SaaS/marketing + tenant login layout documented in `.env.example` |

**AWS keys in `.env`:** If the app uses S3 (`AWS_*` in `.env.example`), prefer an **IAM role** attached to EC2/ECS task instead of long-lived access keys when possible.

**Secrets:** Prefer **Secrets Manager** or **SSM Parameter Store** and a small bootstrap that exports env vars before PHP-FPM starts, rather than a world-readable file on disk.

---

## 3. First-time install / migrations

- Run the **web installer** once (as in README):  
  `https://your-domain.com/public/install/` (exact path may be `install/index.php` depending on your Nginx rules).
- After go-live, **restrict or remove** public access to `/public/install/` (IP allowlist, HTTP auth, or deletion) so it cannot be abused.
- Optional: `MIGRATION_SECRET` in `.env` aligns with installer migration protection (see `.env.example`).

---

## 4. Scheduled jobs (cron)

The app expects HTTP cron endpoints (see `.env.example` comments). In AWS, use **Amazon EventBridge (CloudWatch Events)** to invoke:

- **Lambda** that performs `GET https://your-domain.com/cron/...` with header `X-Cron-Auth: <CRON_AUTH_SECRET>`, or  
- An **ECS scheduled task** / **EC2 user cron** with `curl` and the same secret.

Schedule nightly audit / trial expiry jobs per your product requirements.

---

## 5. Hardening checklist

- **PHP:** `display_errors` off in production, OPcache on, reasonable `upload_max_filesize` / `post_max_size`, FPM `pm` tuning under load.
- **Nginx:** hide version, rate-limit login if needed, only TLS 1.2+.
- **Database:** RDS backups + multi-AZ if downtime is costly.
- **Observability:** CloudWatch agent or ALB access logs; error log shipping from Nginx/PHP.
- **Dependencies:** `composer install --no-dev --optimize-autoloader` on deploy.

---

## 6. Cost and scaling path

- **Start:** Single-AZ RDS small instance + one EC2/Lightsail + ALB is enough for early users.
- **Grow:** Read replicas or larger RDS, horizontal EC2/ECS + ALB, ElastiCache only if you add session/cache integration later.

This document is operational guidance only; review **license** ([`LICENSE`](../LICENSE)), **PCI** scope if you handle payments, and **privacy** obligations for guest data in your jurisdiction.
