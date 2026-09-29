# Notes App — Laravel + CI/CD + Rancher Fleet

A small Laravel app for learning deployment:

- Register, log in, log out
- Create, edit and delete notes (each user sees only their own)
- JSON API with tokens (`/api/login`, `/api/notes`)
- PostgreSQL (CloudNativePG) in production
- GitHub Actions (CI): tests → Docker image → new tag in Git
- Rancher Fleet (CD): pulls Git → deploys to RKE2 with zero-downtime rolling updates

---

## Part 1 — Set up the project on your laptop

These files go **on top of** a fresh Laravel project.

```bash
composer create-project laravel/laravel notes-app
cd notes-app
php artisan install:api --without-migration-prompt
```

Now copy everything from this folder into `notes-app/` and **overwrite** when asked.
(The files `bootstrap/app.php`, `routes/web.php`, `routes/api.php`,
`app/Models/User.php` and `tests/Feature/ExampleTest.php` replace Laravel's defaults.)

Then:

```bash
php artisan migrate      # uses SQLite by default, no database install needed
php artisan test         # all tests should pass
php artisan serve        # open http://localhost:8000
```

Want to use your local MySQL/MariaDB or PostgreSQL instead? Change the `DB_*`
lines in `.env`, then run `php artisan migrate` again.

### Try the API

```bash
# 1. Get a token (use an account you registered on the website)
curl -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" \
  -d "email=you@example.com" -d "password=yourpassword"

# 2. Use it
curl http://localhost:8000/api/notes \
  -H "Accept: application/json" -H "Authorization: Bearer <token>"

curl -X POST http://localhost:8000/api/notes \
  -H "Accept: application/json" -H "Authorization: Bearer <token>" \
  -d "title=From curl" -d "body=Hello API"
```

---

## Part 2 — Push to GitHub (CI)

1. Create a new GitHub repo called `notes-app`.
2. Push the project:

   ```bash
   git init
   git add .
   git commit -m "Notes app"
   git branch -M main
   git remote add origin https://github.com/iindiG0/notes-app.git
   git push -u origin main
   ```

3. Open the **Actions** tab. `test` runs your tests against a real PostgreSQL,
   `build` pushes the image to `ghcr.io/iindig0/notes-app`, and `release`
   commits the new image tag into `k8s/kustomization.yaml`.

If you use a different repo name, update the image name in `k8s/kustomization.yaml`,
`k8s/deployment.yaml` (both `image:` lines) and the repository URL in Fleet (or `argocd/application.yaml`).

---

## Part 3 — Deploy to RKE2 with Rancher Fleet (CD)

### How it fits together

```
git push ──► GitHub Actions (CI)                      Rancher Fleet (CD)
             test → build image → push to GHCR  ──►   watches Git (every 60s)
             → commit new tag to kustomization.yaml   → applies k8s/ → rolling update
```

Git is the only link between CI and CD. GitHub never connects to the cluster;
Fleet **pulls** from GitHub. This works even though the cluster API (port 6443)
is private.

### Before the first deploy

Edit and push:
- `k8s/network.yaml`: `host` (currently `notes.izzat.local`) and `ingressClassName`
- `k8s/config.yaml`: `APP_URL` to match that host
- `k8s/database.yaml`: `storageClass` if you don't want the cluster default (`nfs-csi`)

Add DNS for the host (AD DNS A record `notes` → `10.27.60.80`, or a hosts-file line).

### One-time setup in the Rancher UI

Secrets are created by hand and **never committed to Git**.

1. **Namespace:** ☰ → `local` → Cluster → Projects/Namespaces → **Create Namespace** → `notes`
   (leave Container Resource Limits empty).
2. **App key:** on the laptop run `php artisan key:generate --show`, then
   Storage → Secrets → **Create → Opaque**:
   namespace `notes`, name `notes-app-secrets`, key `APP_KEY`, value = the `base64:...` output.
3. **Image pull secret** (only if the GHCR package is private):
   Storage → Secrets → **Create → Registry**: namespace `notes`, name `ghcr-pull`,
   registry `ghcr.io`, username `iindiG0`, password = a token with only `read:packages`.
4. **Fleet:** ☰ → Continuous Delivery → workspace `fleet-local` → App Bundles → **Create**
   - Repository URL: `https://github.com/iindiG0/notes-app.git`
   - Branch: `main`
   - Path: `k8s` (so the `argocd/` folder is not deployed)
   - Target: `local` cluster
   - Advanced: no Git authentication (public repo), tick **Enable self-healing**

CNPG creates the database and its `notes-db-app` Secret automatically. For the first
minute or two the app pods wait (`CreateContainerConfigError` / `Init`) until the database
is ready. That is normal.

### What happens on every deploy

| Step | What | Why |
|---|---|---|
| 1 | ConfigMap + PostgreSQL (CNPG `Cluster`) are applied | The database must exist |
| 2 | Each new pod runs the `migrate` **initContainer** (`php artisan migrate --force`) | Tables are updated before the new code starts |
| 3 | The `web` container starts; the rolling update replaces pods one at a time | `maxUnavailable: 0` keeps the old pods serving until a new one is Ready |

If a migration fails, the new pod never becomes Ready, the rollout stops and the old
version keeps running.

Why an initContainer instead of a Job? Fleet (like plain `kubectl`) ignores Argo CD hooks,
and a Job can't be updated with a new image. The initContainer works the same with any tool.
`k8s/migrate-job.yaml` is kept for the Argo CD exercise but is not in `kustomization.yaml`.

### Useful commands

```bash
kubectl get pods -n notes                                 # is everything running?
kubectl logs -n notes deploy/notes-app -c web             # app logs
kubectl logs -n notes deploy/notes-app -c migrate         # migration output
kubectl get cluster -n notes                              # CNPG database status
kubectl rollout status deploy/notes-app -n notes          # did the rollout finish?
kubectl kustomize "https://github.com/iindiG0/notes-app//k8s?ref=main"   # preview what Fleet deploys
```

In Rancher: Continuous Delivery → App Bundles shows which commit is deployed
(`main @ <sha>`) and any errors.

### After every push

The `release` job commits the new tag to `main`, so run `git pull` on the laptop
before your next change.

### Roll back

Set `newTag` in `k8s/kustomization.yaml` to an older commit SHA and push; Fleet deploys it.
(Rolling back code does not undo migrations. Keep migrations backwards-compatible.)

---

## Other ways to deploy (learning roadmap)

The same `k8s/` folder works with each of these. Let only **one** tool manage the app at a
time, or give each tool its own namespace.

| Method | CI | CD | Status |
|---|---|---|---|
| Manual `kubectl apply -k k8s/` | GitHub Actions | you | |
| Rancher Fleet | GitHub Actions | Fleet (pull) | ✅ Done |
| Self-hosted runner (ARC) | GitHub Actions | runner inside the cluster | |
| Argo CD | GitHub Actions | Argo CD (pull), uses `argocd/application.yaml` | |
| Flux CD | GitHub Actions | Flux (pull) | |
| Epinio | Epinio builds from source | Epinio | Next |
