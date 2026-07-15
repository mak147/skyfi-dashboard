# SkyFi CI/CD Documentation

## Continuous integration

The CI workflow template is defined in `docs/deployment/github-actions/ci.yml`. To activate it in a repository environment with GitHub workflow-write permission, copy it to `.github/workflows/ci.yml`. It is designed to run for pull requests plus pushes to `main` and `arena/**` branches.

### Backend gates

- PHP 8.3 setup with required extensions
- Composer manifest validation
- Composer dependency installation
- Repository-wide PHP syntax checks
- PHPUnit test suite

### Frontend gates

- Node.js 22 setup with npm cache
- `npm ci`
- ESLint
- Vitest with coverage
- TypeScript and Vite production build

### Docker gates

- Development Compose config validation
- Production Compose config validation
- Backend PHP-FPM image build
- Frontend static image build
- Production Nginx gateway image build

## Continuous delivery

The CD workflow template is defined in `docs/deployment/github-actions/cd.yml`. To activate it in a repository environment with GitHub workflow-write permission, copy it to `.github/workflows/cd.yml`. It is intentionally manual through `workflow_dispatch`.

Inputs:

| Input | Description |
| --- | --- |
| `image_tag` | Tag applied to build artifacts and images. |
| `publish_images` | When true, pushes backend, supervisor, and nginx images to GitHub Container Registry. |

The CD workflow always creates a deployment bundle artifact containing:

- `docker-compose.prod.yml`
- `.env.example`
- `DEPLOYMENT_GUIDE.md`
- `CI_CD.md`
- `IMAGE_TAGS.txt`

When `publish_images=true`, images are pushed to:

```text
ghcr.io/<owner>/<repo>/backend:<image_tag>
ghcr.io/<owner>/<repo>/supervisor:<image_tag>
ghcr.io/<owner>/<repo>/nginx:<image_tag>
```

## Recommended release flow

1. Merge a phase PR into `main` after review.
2. Run CI on `main` and confirm all gates pass.
3. Start the CD workflow manually with a semantic or release-candidate tag.
4. Download the deployment bundle artifact.
5. Copy the bundle to the target host.
6. Populate `.env` using the production template and the generated image tags.
7. Deploy with:

   ```bash
   docker compose -f docker-compose.prod.yml pull
   docker compose -f docker-compose.prod.yml up -d
   ```

8. Run migrations and smoke checks from the deployment guide.

## Secrets and permissions

- The CI workflow only requires read access to repository contents.
- The CD workflow uses `GITHUB_TOKEN` with package write permission when image publishing is enabled.
- Runtime secrets are never stored in workflows. They are supplied through the production host `.env` file or platform secret manager.

## Failure handling

- Backend or frontend job failure blocks Docker validation.
- Docker validation failure blocks merge readiness.
- CD image publishing can be run with `publish_images=false` to generate an artifact without writing to GHCR.
- If a deployment fails readiness checks, follow the rollback procedure in `DEPLOYMENT_GUIDE.md`.
