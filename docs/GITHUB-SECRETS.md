# Required GitHub Environment Secrets & Variables

Configure these separately under **Settings → Environments → `staging`** and **`production`**:

## Secrets (Environment-Scoped)
| Secret Name | Required | Description |
|---|---|---|
| `DEPLOY_HOST` | Yes | Hostname or IP address of the target Linux VPS |
| `DEPLOY_USER` | Yes | SSH deployment user with write access to `DEPLOY_PATH` and passwordless `sudo systemctl reload php8.2-fpm` |
| `DEPLOY_SSH_KEY` | Yes | Private SSH key (Ed25519/RSA) matching the target host's `authorized_keys` |
| `DEPLOY_SSH_KNOWN_HOSTS` | Yes | Output of `ssh-keyscan -p <port> -H <host>` for strict host-key verification (`StrictHostKeyChecking=yes`) |

## Variables (Environment-Scoped)
| Variable Name | Required | Default | Description |
|---|---|---|---|
| `APP_URL` | Yes | — | Public base URL of the environment (e.g. `https://staging.example.edu.np`) used for `/health/readiness` probes |
| `DEPLOY_PATH` | No | `/var/www/sms_academy` | Absolute non-root path on the target host containing `releases/`, `shared/`, and `current` |
| `DEPLOY_PORT` | No | `22` | SSH/SCP port on the target host |
| `KEEP_RELEASES` | No | `5` | Number of recent releases retained after successful deployment |
| `HEALTH_MAX_ATTEMPTS` | No | `6` | Maximum readiness probe attempts during post-deployment verification |
| `HEALTH_RETRY_INTERVAL` | No | `5` | Delay in seconds between readiness probe retry attempts |
| `HEALTH_REQUEST_TIMEOUT` | No | `15` | Timeout in seconds for individual readiness HTTP requests |

> **Security Note:** Never commit `.env` files, private keys, or host credentials to the repository. Production should enable **Required reviewers** on the `production` GitHub Environment.

