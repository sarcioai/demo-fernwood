# demo-fernwood

**Fernwood Journal** is a fictional publication running a real WordPress. Its
front page carries two seeded bugs that [Sarcio](https://www.sarcio.io) fixes
live: one in the browser, one on the server.

|           |                                                              |
| --------- | ------------------------------------------------------------ |
| Live demo | [demos.sarcio.io/fernwood](https://demos.sarcio.io/fernwood) |
| Shows     | a front-end fix and a server-side fix, through the Sarcio WordPress plugin |
| Fix PR    | opens on this repository (GitHub)                            |

WordPress core comes from wordpress.org, the database is the official **SQLite
drop-in** (no database server), and the Sarcio plugin is an ordinary Composer
dependency, exactly as a customer installs it.

## The seeded bugs

1. **In the browser.** The Sunday Letter's Subscribe button in
   `wp-content/themes/fernwood/front-page.php` ships with `disabled` on it, so
   nobody can subscribe. Once a fix is approved in Sarcio the button works for
   every visitor. The pull request with the permanent fix edits
   `front-page.php`.
2. **On the server.** With the button working, the form reaches
   `POST /fernwood/wp-json/fernwood/v1/subscribe` (in
   `wp-content/mu-plugins/fernwood-newsletter.php`), which since the journal
   added a second newsletter demands a `list` the Sunday Letter form never
   sends:

   ```bash
   curl -XPOST localhost:4013/fernwood/wp-json/fernwood/v1/subscribe \
     -H content-type:application/json -d '{"email":"ada@example.com"}'
   # -> 400 {"error":"list is required"}
   ```

   Once a fix is approved in Sarcio the signup succeeds, without a restart. If
   Sarcio is unavailable the site simply behaves as it did before.

## Run it

```bash
docker build --secret id=composer_auth,src=auth.json -t demo-fernwood .
docker run -p 4013:4013 -e SARCIO_BASE_PATH=/fernwood demo-fernwood
# http://localhost:4013/fernwood
```

Environment: `PORT`, `SARCIO_BASE_PATH`, `SARCIO_SITE_KEY`,
`SARCIO_SIDECAR_DSN`, `SARCIO_SHIM_TOKEN`, `SARCIO_API_URL`,
`SARCIO_WIDGET_SRC`. Behind a proxy, send `X-Forwarded-Proto` so WordPress emits
URLs in the visitor's scheme.

The database is baked into the image and not on a volume, so recreating the
container resets the site to its seeded state. `router.php` refuses database,
archive and log files and the plugin's backup directory, so the image is safe
without a proxy in front; a proxy should still deny the same paths.

Outside Docker, `php setup.php --dir=… --data-dir=… --cache-dir=…` provisions
the same layout.
