# Documentation deployment

The VitePress documentation is hosted on Cloudflare Workers static assets.
`wrangler.jsonc` serves `docs/.vitepress/dist` at
`https://fetch-php.thavarshan.com`. Clean URLs use automatic HTML routing;
missing pages return VitePress's `404.html` with HTTP 404.

## Local deployment

Use Node 24 and install the locked documentation dependencies:

```sh
npm ci
npm run build
npm run deploy:check
npm run preview:worker
```

After checking the local site, authenticate and deploy:

```sh
npx --yes wrangler@4.157.0 login
npm run deploy
```

Wrangler is pinned in the npm scripts and downloaded by npm on demand. It is
Cloudflare's maintained deployment CLI, licensed MIT OR Apache-2.0, and requires
Node 22 or newer. It does not change PHP dependencies or supported PHP versions.
Update the version in all scripts, this guide, and the config schema together.

## GitHub Actions

The `deploy-docs` job in `.github/workflows/ci.yml` deploys only `main`, after
all documentation checks pass. Pull requests and other branches only validate.
A manual CI run on `main` can also deploy. Production deployments are
serialized.

Configure these repository Actions secrets before enabling automated deploys:

- `CLOUDFLARE_ACCOUNT_ID`: the account containing the Worker and DNS zone.
- `CLOUDFLARE_API_TOKEN`: a scoped deployment token with Workers Scripts Edit
  and Workers Routes Edit, plus Zone Read for the `thavarshan.com` zone.
  Use Cloudflare's Workers token template and restrict it to this account/zone.

Never commit credentials. Local OAuth authentication is separate from CI
secrets.

## Netlify cutover

The repository previously configured Netlify via `netlify.toml`; it had no
Netlify deployment GitHub Action. The Cloudflare configuration replaces that
file and removes the unused `docs/empty-netlify` placeholder.

The production domain has been attached to the Worker, and the Netlify project
has been deleted.

For future hosting migrations, the custom domain requires an active DNS zone
in the deployment
account. If an existing Netlify CNAME prevents attaching the Worker, replace
that record through Cloudflare's custom-domain setup. Keep Netlify available
until the production hostname serves the verified Worker deployment. Then
disable Netlify automatic builds and detach its custom domain in Netlify.
Removing repository configuration alone does not disconnect its Git integration.

Verify the home page, a nested guide, a static asset, and a nonexistent URL on
both the workers.dev URL printed by Wrangler and the production hostname.
Canonical URLs and sitemap entries retain the production hostname.

To roll back, use Cloudflare's Worker deployment history or
`npx --yes wrangler@4.157.0 rollback`. A hosting rollback to Netlify requires
recreating the project and restoring its domain/DNS mapping and deployment
configuration.
