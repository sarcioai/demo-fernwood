# demo-fernwood

**Fernwood Journal** is a fictional publication running a real WordPress. Its
front page carries two seeded bugs that [Sarcio](https://sarcio.io) fixes live,
one per tier. It is one demo in Sarcio's catalog (see `docs/demo.md` in the
Sarcio repo):

| | |
| --- | --- |
| Workspace | `fernwood` (`fernwood.sarcio.io`) |
| Served at | `demos.sarcio.io/fernwood` |
| Patch kinds | DOM patch (browser) + server patch (the Sarcio WordPress plugin, through a Go sidecar) |
| Forge | GitHub — this repo, where the permanent-fix PR opens |

Core comes from wordpress.org, the database is the official **SQLite drop-in**
(no database server), and the Sarcio plugin is an ordinary Composer dependency —
exactly as a customer installs it.

## The seeded bugs

1. **The DOM bug.** The Sunday Letter's Subscribe button in
   `wp-content/themes/fernwood/front-page.php` ships with `disabled` on it, so
   nobody can subscribe. Sarcio drafts a single `removeAttr` DOM patch; the
   reviewer rates removing `disabled` **elevated**, so it needs two different
   approvers in the `fernwood` workspace before it goes live. `front-page.php` is
   the file the permanent-fix PR edits (the site's `sourcePath`).
2. **The server bug.** With the button working, the form reaches
   `POST /fernwood/wp-json/fernwood/v1/subscribe` (in
   `wp-content/mu-plugins/fernwood-newsletter.php`), which since the journal
   added a second newsletter demands a `list` the Sunday Letter form never sends:

   ```bash
   curl -XPOST localhost:4013/fernwood/wp-json/fernwood/v1/subscribe \
     -H content-type:application/json -d '{"email":"ada@example.com"}'
   # -> 400 {"error":"list is required"}
   ```

   A `defaultValue` server patch supplies `list: "sunday-letter"` through the
   plugin; stop the sidecar
   and the route fails closed to the original 400.

## Run it

```bash
docker build --secret id=composer_auth,src=auth.json -t demo-fernwood .
docker run -p 4013:4013 -e SARCIO_BASE_PATH=/fernwood demo-fernwood
# http://localhost:4013/fernwood
```

Env, per the Sarcio demo image contract: `PORT`, `SARCIO_BASE_PATH`,
`SARCIO_SITE_KEY`, `SARCIO_SIDECAR_DSN`, `SARCIO_SHIM_TOKEN`, `SARCIO_API_URL`,
`SARCIO_WIDGET_SRC`. Behind a proxy, send `X-Forwarded-Proto` so WordPress emits
URLs in the visitor's scheme.

The web root is `/var/www/wp`. The SQLite database lives outside it, in
`/var/lib/fernwood` (the drop-in's `DB_DIR`), and the core download cache is
build-only, because PHP's built-in server ignores the drop-in's `.htaccess`.
`router.php` also 404s dot-segments, `wp-content/database/`,
`wp-content/sarcio/` (the plugin's file-swap backups) and database, archive and
log files, so the image is safe without a proxy in front; a proxy should still
deny the same paths. The database is baked at build and not on a volume:
recreating the container resets the site to its seeded state.

Outside Docker, `php setup.php --dir=… --data-dir=… --cache-dir=…` provisions
the same layout (both default to directories under the system temp dir, and
setup refuses either one inside `--dir`).

## Release

Push a `vX.Y.Z` tag: `.github/workflows/release.yml` builds
`ghcr.io/sarcioai/demo-fernwood` for arm64 + amd64. Bump `DEMO_FERNWOOD_TAG` in
the Sarcio stack to roll it out.
