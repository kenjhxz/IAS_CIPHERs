<?php

/**
 * index.php — Entry point / Controller
 *
 * Responsibilities (and ONLY these):
 *   1. Bootstrap: load autoloader, register ciphers
 *   2. Handle POST: read params, call cipher, catch errors
 *   3. Pass data to renderer and output HTML
 *
 * SOLID:
 *  (S) Controller only — no cipher logic, no HTML string building
 *  (O) Add a new cipher: register it below, done. Nothing else changes.
 *  (D) Depends on CipherInterface / registry abstractions only
 */

require_once __DIR__ . '/autoload.php';

use CipherLab\Ciphers\PigpenCipher;
use CipherLab\Ciphers\HillCipher;
use CipherLab\Ciphers\PlayfairCipher;
use CipherLab\Ciphers\AffineCipher;
use CipherLab\Ciphers\VigenereCipher;
use CipherLab\Contracts\KeyedCipherInterface;
use CipherLab\Registry\CipherRegistry;
use CipherLab\Renderer\CipherRenderer;

// ── 1. Bootstrap ───────────────────────────────────────────────────────────────

$registry = new CipherRegistry();

// Register all ciphers. To add a new one: just register it here.
$registry->register(new PigpenCipher());
$registry->register(new HillCipher());
$registry->register(new PlayfairCipher());
$registry->register(new AffineCipher());
$registry->register(new VigenereCipher());

$renderer = new CipherRenderer($registry);

// ── 2. Handle POST ─────────────────────────────────────────────────────────────

$activeCipherId = $_POST['cipher'] ?? '';
$activeAction   = $_POST['action'] ?? '';
$plaintext      = trim($_POST['plaintext'] ?? '');
$output         = '';
$error          = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && $registry->has($activeCipherId)
    && in_array($activeAction, ['encrypt', 'decrypt'], true)
    && $plaintext !== ''
) {
    try {
        $cipher = $registry->get($activeCipherId);

        // Inject params only for keyed ciphers (ISP: Pigpen never sees this)
        if ($cipher instanceof KeyedCipherInterface) {
            $cipher->setParams($_POST);
        }

        $output = ($activeAction === 'encrypt')
            ? $cipher->encrypt($plaintext)
            : $cipher->decrypt($plaintext);

    } catch (InvalidArgumentException $e) {
        $error = $e->getMessage();
    }
}

// ── 3. Render ──────────────────────────────────────────────────────────────────

$menu   = $renderer->renderMenu($activeCipherId);
$panels = $renderer->renderPanels(
    $activeCipherId,
    $plaintext,
    $output,
    $error,
    $activeAction,
    $_POST
);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CipherLab — Classic Encryption Suite</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=Space+Mono:wght@400;700&display=swap');

  :root {
    --bg:      #0a0a0f;
    --surface: #13131a;
    --surf2:   #1c1c28;
    --border:  #2a2a3d;
    --accent:  #6c63ff;
    --red:     #ff6584;
    --green:   #43e97b;
    --text:    #e8e8f0;
    --muted:   #7070a0;
    --r:       12px;
  }

  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Space Mono', monospace;
    background: var(--bg);
    color: var(--text);
    min-height: 100vh;
  }

  /* ── Header ── */
  .header {
    text-align: center;
    padding: 3rem 1rem 2rem;
    position: relative;
  }
  .header::before {
    content: '';
    position: absolute; top: -80px; left: 50%; transform: translateX(-50%);
    width: 600px; height: 400px;
    background: radial-gradient(ellipse, rgba(108,99,255,.18) 0%, transparent 70%);
    pointer-events: none;
  }
  .header h1 {
    font-family: 'Syne', sans-serif;
    font-size: clamp(2rem, 6vw, 3.2rem);
    font-weight: 800;
    background: linear-gradient(135deg, #a78bfa, #6c63ff, #43e97b);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }
  .header p { color: var(--muted); font-size: .8rem; margin-top: .4rem; letter-spacing: 3px; text-transform: uppercase; }

  /* ── Layout ── */
  .container { max-width: 1080px; margin: 0 auto; padding: 0 1.5rem 4rem; }

  .section-label {
    font-size: .7rem; letter-spacing: 3px; text-transform: uppercase;
    color: var(--muted); margin-bottom: 1rem;
    font-family: 'Syne', sans-serif;
  }

  /* ── Cipher grid ── */
  .cipher-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(185px, 1fr));
    gap: 1rem;
    margin-bottom: 2.5rem;
  }
  .cipher-card {
    background: var(--surface); border: 2px solid var(--border);
    border-radius: var(--r); padding: 1.2rem;
    cursor: pointer; transition: all .2s ease; position: relative; overflow: hidden;
  }
  .cipher-card::after {
    content: ''; position: absolute; bottom: 0; left: 0;
    width: 100%; height: 3px;
    background: linear-gradient(90deg, var(--accent), var(--green));
    transform: scaleX(0); transform-origin: left; transition: transform .2s;
  }
  .cipher-card:hover, .cipher-card.active {
    border-color: var(--accent); background: var(--surf2);
    transform: translateY(-2px); box-shadow: 0 8px 28px rgba(108,99,255,.2);
  }
  .cipher-card:hover::after, .cipher-card.active::after { transform: scaleX(1); }
  .cipher-icon  { font-size: 1.5rem; margin-bottom: .4rem; }
  .cipher-label { font-family: 'Syne', sans-serif; font-weight: 700; font-size: .92rem; margin-bottom: .2rem; }
  .cipher-desc  { font-size: .68rem; color: var(--muted); line-height: 1.5; }

  /* ── Panel ── */
  .panel { background: var(--surface); border: 1px solid var(--border); border-radius: var(--r); padding: 2rem; display: none; }
  .panel.visible { display: block; animation: fadeUp .3s ease; }
  @keyframes fadeUp { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: none; } }

  .panel-header { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.75rem; padding-bottom: 1rem; border-bottom: 1px solid var(--border); }
  .panel-icon   { font-size: 1.8rem; }
  .panel-title  { font-family: 'Syne', sans-serif; font-size: 1.3rem; font-weight: 800; }
  .panel-subtitle { font-size: .72rem; color: var(--muted); margin-top: .1rem; }

  /* ── Form ── */
  .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
  @media (max-width: 600px) { .form-grid { grid-template-columns: 1fr; } }

  .form-group { display: flex; flex-direction: column; gap: .4rem; }
  .form-group.full { grid-column: 1 / -1; }

  label { font-size: .68rem; letter-spacing: 2px; text-transform: uppercase; color: var(--muted); }

  input[type="text"],
  input[type="number"],
  textarea {
    background: var(--bg); border: 1px solid var(--border); border-radius: 8px;
    color: var(--text); font-family: 'Space Mono', monospace; font-size: .875rem;
    padding: .7rem 1rem; width: 100%; transition: border-color .2s; outline: none;
  }
  input:focus, textarea:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(108,99,255,.12); }
  textarea { resize: vertical; min-height: 88px; }

  .field-hint { font-size: .68rem; color: var(--muted); }

  .note {
    background: rgba(108,99,255,.07); border-left: 3px solid var(--accent);
    border-radius: 0 8px 8px 0; padding: .75rem 1rem;
    font-size: .72rem; color: var(--muted); line-height: 1.6; grid-column: 1/-1;
  }
  .note strong { color: var(--text); }

  /* ── Buttons ── */
  .action-row { display: flex; gap: .75rem; margin-top: 1.5rem; flex-wrap: wrap; }
  .btn {
    font-family: 'Syne', sans-serif; font-weight: 700; font-size: .875rem;
    border: none; border-radius: 8px; padding: .72rem 1.6rem;
    cursor: pointer; transition: all .18s; display: flex; align-items: center; gap: .4rem;
  }
  .btn-encrypt { background: linear-gradient(135deg, var(--accent), #8b83ff); color: #fff; }
  .btn-encrypt:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(108,99,255,.4); }
  .btn-decrypt { background: var(--surf2); color: var(--text); border: 1px solid var(--border); }
  .btn-decrypt:hover { border-color: var(--red); color: var(--red); }
  .btn-clear   { background: transparent; color: var(--muted); border: 1px solid var(--border); margin-left: auto; }
  .btn-clear:hover { color: var(--red); border-color: var(--red); }

  /* ── Output ── */
  .output-box { margin-top: 1.75rem; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; padding: 1.25rem; display: none; }
  .output-box.visible { display: block; }
  .output-label { font-size: .65rem; letter-spacing: 2px; text-transform: uppercase; color: var(--muted); margin-bottom: .5rem; }
  .output-text  { font-size: .9rem; color: var(--green); word-break: break-all; white-space: pre-wrap; line-height: 1.7; }
  .error-text   { color: var(--red) !important; }

  /* ── Footer ── */
  .footer { text-align: center; color: var(--muted); font-size: .68rem; padding: 2rem; border-top: 1px solid var(--border); letter-spacing: 1px; }
</style>
</head>
<body>

<div class="header">
  <h1>🔐 CipherLab</h1>
  <p>Classic Encryption Suite — PHP · SOLID Architecture</p>
</div>

<div class="container">

  <p class="section-label">Select a Cipher</p>

  <?= $menu ?>

  <form method="POST" id="cipher-form">
    <input type="hidden" name="cipher" id="cipher-input" value="<?= htmlspecialchars($activeCipherId) ?>">
    <input type="hidden" name="action" id="action-input" value="">
    <?= $panels ?>
  </form>

</div>

<div class="footer">
  CipherLab · Pigpen · Hill · Playfair · Affine · Vigenère · Built with PHP &amp; SOLID Principles
</div>

<script>
  function selectCipher(id) {
    document.querySelectorAll('.cipher-card').forEach(c => c.classList.remove('active'));
    document.querySelectorAll('.panel').forEach(p => p.classList.remove('visible'));
    document.querySelector(`.cipher-card[onclick="selectCipher('${id}')"]`).classList.add('active');
    document.getElementById('panel-' + id).classList.add('visible');
    document.getElementById('cipher-input').value = id;
  }

  function setAction(a) {
    document.getElementById('action-input').value = a;
  }

  function clearForm() {
    document.querySelectorAll('.panel.visible textarea, .panel.visible input[type="text"]')
      .forEach(el => el.value = '');
    document.querySelectorAll('.output-box').forEach(b => b.classList.remove('visible'));
  }
</script>
</body>
</html>
