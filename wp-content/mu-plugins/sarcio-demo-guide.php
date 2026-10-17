<?php
/**
 * Plugin Name: Sarcio demo guide
 * Description: The small "you're in a Sarcio demo" card. Not part of the business: it tells a visitor what to try and how to report what breaks.
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

const SARCIO_DEMO_GUIDE = [
    'business' => 'Fernwood Nursery',
    'try' => 'Try buying a plant for delivery to a Portland address.',
];

add_action('wp_footer', static function (): void {
    if (is_admin()) {
        return;
    }
    $guide = SARCIO_DEMO_GUIDE;
    ?>
<aside id="sarcio-demo-guide" aria-label="About this demo">
  <div class="sdg-card" hidden>
    <button type="button" class="sdg-close" aria-label="Hide this note">×</button>
    <p class="sdg-kicker"><span class="sdg-mark" aria-hidden="true"></span>Sarcio live demo</p>
    <p><strong><?php echo esc_html($guide['business']); ?></strong> is a made-up business on a real WordPress site, with a couple of things broken the way they break after an update.</p>
    <p><?php echo esc_html($guide['try']); ?> If something goes wrong, report it with the button in the bottom-right corner.</p>
  </div>
  <button type="button" class="sdg-tab" hidden><span class="sdg-mark" aria-hidden="true"></span>Sarcio demo</button>
</aside>
<style>
  #sarcio-demo-guide{position:fixed;left:16px;bottom:16px;z-index:99990;font:13px/1.45 system-ui,-apple-system,"Segoe UI",sans-serif;color:#1d2b24}
  #sarcio-demo-guide .sdg-card{position:relative;max-width:290px;background:#fff;border:1px solid #d7e3dc;border-radius:12px;box-shadow:0 10px 28px rgba(16,40,28,.18);padding:12px 14px 6px}
  #sarcio-demo-guide p{margin:0 0 7px}
  #sarcio-demo-guide .sdg-kicker{display:flex;align-items:center;gap:7px;font-size:10.5px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#047857}
  #sarcio-demo-guide .sdg-mark{display:inline-block;width:12px;height:12px;border-radius:3px 50% 3px 50%;background:#059669}
  #sarcio-demo-guide .sdg-close{position:absolute;top:4px;right:6px;border:0;background:none;font-size:18px;line-height:1;color:#5b6b63;cursor:pointer;padding:4px}
  #sarcio-demo-guide .sdg-tab{display:flex;align-items:center;gap:7px;border:1px solid #d7e3dc;background:#fff;border-radius:999px;padding:6px 12px;font:600 12px system-ui,sans-serif;color:#047857;box-shadow:0 6px 16px rgba(16,40,28,.14);cursor:pointer}
  #sarcio-demo-guide [hidden]{display:none}
</style>
<script>
  (function () {
    var key = 'sarcio-demo-guide-collapsed';
    var root = document.getElementById('sarcio-demo-guide');
    var card = root.querySelector('.sdg-card');
    var tab = root.querySelector('.sdg-tab');
    var collapsed = false;
    try { collapsed = !!localStorage.getItem(key); } catch (e) {}
    var show = function (open) {
      card.hidden = !open;
      tab.hidden = open;
      try { open ? localStorage.removeItem(key) : localStorage.setItem(key, '1'); } catch (e) {}
    };
    show(!collapsed);
    root.querySelector('.sdg-close').addEventListener('click', function () { show(false); });
    tab.addEventListener('click', function () { show(true); });
  })();
</script>
    <?php
});
