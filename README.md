# demo-fernwood

**Fernwood Nursery** is a fictional plant nursery in Portland, Oregon, selling
online through a real WordPress and WooCommerce store. Two things on it are
broken, the way things break on real stores after an update, and
[Sarcio](https://www.sarcio.io) fixes both live: one in the browser, one on the
server.

|           |                                                              |
| --------- | ------------------------------------------------------------ |
| Live demo | [demos.sarcio.io/fernwood](https://demos.sarcio.io/fernwood) |
| Shows     | a front-end fix and a server-side fix through the Sarcio WordPress plugin, with no sidecar |
| Fix PR    | opens on this repository (GitHub)                            |

WordPress core, WooCommerce and the official **SQLite drop-in** (no database
server) come from wordpress.org, and the Sarcio plugin is an ordinary Composer
dependency, exactly as a customer installs it. The server tier runs the way it
does on shared hosting: the plugin fetches and verifies the site's signed fixes
itself with the site's sidecar key, and nothing else runs beside WordPress.

## What's broken

1. **"Add to cart is greyed out."** The theme's copy of WooCommerce's
   add-to-cart template (`wp-content/themes/fernwood/woocommerce/single-product/add-to-cart/simple.php`)
   was overridden for a spring pre-order season and still carries `disabled`
   on the button, so no plant page can take an order. A fix approved in Sarcio
   re-enables the button for every visitor; the pull request with the
   permanent fix edits that template.
2. **"Checkout says we don't deliver to 97202."** The nursery's own delivery
   plugin (`wp-content/mu-plugins/fernwood-local-delivery.php`) imports its ZIP
   list as numbers and compares it strictly against the ZIP typed at checkout,
   so it refuses every address, the nursery's own included. A fix approved in
   Sarcio lets checkout through while the permanent fix is reviewed. If Sarcio
   is unavailable the site behaves exactly as it did before.

## Run it

```bash
docker build --secret id=composer_auth,src=auth.json -t demo-fernwood .
docker run -p 4013:4013 -e SARCIO_BASE_PATH=/fernwood demo-fernwood
# http://localhost:4013/fernwood
```

Environment: `PORT`, `SARCIO_BASE_PATH`, `SARCIO_SITE_KEY`, `SARCIO_API_URL`,
`SARCIO_WIDGET_SRC`, `SARCIO_SIDECAR_KEY` (the site's `sk_` key, for the server
tier), and optionally `SARCIO_SIDECAR_DSN` + `SARCIO_SHIM_TOKEN` to use a sidecar
instead, and `FERNWOOD_ADMIN_PASSWORD` for wp-admin. Behind a proxy, send
`X-Forwarded-Proto` so WordPress emits URLs in the visitor's scheme.

The store is stocked at build time (`setup.php`, then `provision.php`) and the
database is baked into the image, so recreating the container resets the store
to its broken state. `router.php` refuses database, archive and log files, so
the image is safe without a proxy in front; a proxy should still deny the same
paths.

Photos are from Unsplash, used under the Unsplash License (see
[`assets/CREDITS.md`](assets/CREDITS.md)).
