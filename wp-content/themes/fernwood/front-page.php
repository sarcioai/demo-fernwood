<?php
/**
 * Fernwood Journal's front page: the latest essays and the newsletter sign-up.
 *
 * The newsletter form carries the seeded DOM bug — its Subscribe button ships
 * with `disabled` on it, so nobody can subscribe. Sarcio fixes it live with a
 * DOM patch (a single `removeAttr`), and the permanent fix is an edit to this
 * file. Once the button works, the form reaches the newsletter REST route in
 * the `fernwood-newsletter` mu-plugin, which has a server-side bug of its own.
 */

defined('ABSPATH') || exit;

$essays = [
    ['title' => 'The Understory in November', 'meta' => 'Field notes · 9 min read', 'excerpt' => 'When the canopy thins, the forest floor gets its one long look at the sky. A season of mosses, late fungi, and the patient work of decay.'],
    ['title' => 'Listening for Wrens', 'meta' => 'Essay · 6 min read', 'excerpt' => 'The smallest bird in the hedge has the loudest voice. On learning a landscape by ear before you learn it by name.'],
    ['title' => 'A Fern That Outlived the Dinosaurs', 'meta' => 'Natural history · 12 min read', 'excerpt' => 'Horsetails and royal ferns have survived three mass extinctions. What their stubbornness says about resilience, and about us.'],
];
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
  <div class="wrap">
    <header class="masthead">
      <h1><?php bloginfo('name'); ?></h1>
      <p class="tagline">Slow writing about wild places</p>
    </header>

    <div class="kicker">Latest essays</div>
    <?php foreach ($essays as $essay) : ?>
      <article>
        <h2><?php echo esc_html($essay['title']); ?></h2>
        <p class="meta"><?php echo esc_html($essay['meta']); ?></p>
        <p><?php echo esc_html($essay['excerpt']); ?></p>
      </article>
    <?php endforeach; ?>

    <section class="newsletter">
      <h3>The Sunday Letter</h3>
      <p>One essay, one field sketch, every Sunday morning. No noise.</p>
      <form id="newsletter">
        <input id="subscriber-email" type="email" placeholder="you@example.com" required />
        <button type="submit" class="subscribe" id="subscribe" disabled>Subscribe</button>
      </form>
      <div class="result" id="newsletter-result"></div>
    </section>

    <footer class="site">Fernwood Journal · Independent since 2014</footer>
  </div>
  <script>
    document.getElementById('newsletter').addEventListener('submit', async (event) => {
      event.preventDefault();
      const result = document.getElementById('newsletter-result');
      result.className = 'result';
      result.textContent = 'Subscribing…';
      const response = await fetch(<?php echo wp_json_encode(rest_url('fernwood/v1/subscribe')); ?>, {
        body: JSON.stringify({ email: document.getElementById('subscriber-email').value }),
        headers: { 'content-type': 'application/json' },
        method: 'POST'
      });
      const data = await response.json();
      result.className = response.ok ? 'result good' : 'result bad';
      result.textContent = response.ok
        ? 'You are on the list — see you Sunday.'
        : response.status + ' — ' + (data.error || 'something went wrong');
    });
  </script>
  <?php wp_footer(); ?>
</body>
</html>
