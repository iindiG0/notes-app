# Notes App — Laravel + CI/CD + Argo CD

A small Laravel app for learning deployment:

- Register, log in, log out
- Create, edit and delete notes (each user sees only their own)
- JSON API with tokens (`/api/login`, `/api/notes`)
- PostgreSQL (CloudNativePG) in production
- GitHub Actions: tests → Docker image → Argo CD deploys to RKE2

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
`k8s/deployment.yaml`, `k8s/migrate-job.yaml` and the `repoURL` in `argocd/application.yaml`.

---

## Part 3 — Deploy to your RKE2 cluster (CD)

**Before the first deploy**, edit:
- `k8s/network.yaml`: the `host` (and `ingressClassName`, check with `kubectl get ingressclass`)
- `k8s/config.yaml`: `APP_URL` to match that host
- `k8s/database.yaml`: `storageClass` if you don't want the cluster default

Then, once:

```bash
# 1. Namespace for the app
kubectl create namespace notes

# 2. The app's encryption key (run this in your laptop project folder)
php artisan key:generate --show
kubectl create secret generic notes-app-secrets -n notes \
  --from-literal=APP_KEY='base64:....paste the output here....'

# 3. Let the cluster pull your image from ghcr.io
#    (token: GitHub → Settings → Developer settings → token with read:packages)
kubectl create secret docker-registry ghcr-pull -n notes \
  --docker-server=ghcr.io --docker-username=iindiG0 --docker-password=<TOKEN>

# 4. Give Argo CD read access to the repo (skip if the repo is public)
#    Argo CD UI → Settings → Repositories → Connect Repo

# 5. Register the app with Argo CD
kubectl apply -f argocd/application.yaml
```

### What happens on every deploy

Argo CD syncs in **waves**:

| Wave | What | Why |
|---|---|---|
| -1 | ConfigMap + PostgreSQL (CNPG `Cluster`) | The database must exist first |
| 0 | `notes-app-migrate` Job | `php artisan migrate --force` updates the tables |
| 1 | Deployment (2 pods) | New code starts only after migrations succeed |

If a migration fails, the deploy stops and the old version keeps running.

### Useful commands

```bash
kubectl get pods -n notes                     # is everything running?
kubectl logs -n notes deploy/notes-app        # app logs
kubectl logs -n notes job/notes-app-migrate   # migration output
kubectl get cluster -n notes                  # CNPG database status
```

### Roll back

Set `newTag` in `k8s/kustomization.yaml` to an older commit SHA and push.
(Rolling back code does not undo migrations. Keep migrations backwards-compatible.)
