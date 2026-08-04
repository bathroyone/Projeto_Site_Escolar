<?php
/**
 * page_hero.php — Componente de hero section reutilizável para todas as páginas internas.
 * Uso: <?php include 'includes/page_hero.php'; ?> após definir $pageHero.
 *
 * $pageHero = [
 *   'title'    => 'Título da Página',
 *   'sub'      => 'Subtítulo opcional',
 *   'icon'     => 'fas fa-icon',       // ícone FontAwesome
 *   'accent'   => '#3b82f6',           // cor de destaque
 *   'badge'    => 'Nome da Seção',     // badge superior
 * ];
 */
$title   = $pageHero['title']  ?? ($pageTitle ?? 'Página');
$sub     = $pageHero['sub']    ?? '';
$icon    = $pageHero['icon']   ?? 'fas fa-circle';
$accent  = $pageHero['accent'] ?? '#3b82f6';
$badge   = $pageHero['badge']  ?? '';
?>
<style>
  /* ─── SHARED INNER PAGE STYLES ─── */
  body { font-family: 'Outfit', sans-serif !important; }
  .inner-hero {
    background: #050c1a;
    padding: 72px 0 60px;
    position: relative;
    overflow: hidden;
  }
  .inner-hero::before {
    content: '';
    position: absolute; inset: 0;
    background-image:
      linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
      linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
    background-size: 50px 50px;
  }
  .inner-glow {
    position: absolute; top: -30%; left: 5%;
    width: 500px; height: 500px; border-radius: 50%;
    background: radial-gradient(circle, <?= $accent ?>33 0%, transparent 70%);
    filter: blur(60px); pointer-events: none;
  }
  .inner-glow-r {
    position: absolute; bottom: -30%; right: 5%;
    width: 350px; height: 350px; border-radius: 50%;
    background: radial-gradient(circle, rgba(139,92,246,0.15) 0%, transparent 70%);
    filter: blur(50px); pointer-events: none;
  }
  .inner-hero-content { position: relative; z-index: 10; }
  .inner-badge {
    display: inline-flex; align-items: center; gap: 8px;
    font-size: 0.72rem; font-weight: 700; letter-spacing: 0.12em;
    text-transform: uppercase; color: <?= $accent ?>;
    margin-bottom: 12px;
  }
  .inner-hero-icon {
    width: 56px; height: 56px; border-radius: 16px;
    background: <?= $accent ?>22;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 20px;
  }
  .inner-hero-icon i { font-size: 1.3rem; color: <?= $accent ?>; }
  .inner-hero-title {
    font-size: clamp(2rem, 4vw, 3.2rem);
    font-weight: 800; color: white;
    line-height: 1.15; letter-spacing: -0.02em;
    margin-bottom: 14px;
  }
  .inner-hero-sub {
    font-size: 1rem; color: rgba(255,255,255,0.45);
    max-width: 560px; line-height: 1.7;
  }
  /* Breadcrumb */
  .breadcrumb {
    display: flex; align-items: center; gap: 8px;
    font-size: 0.78rem; color: rgba(255,255,255,0.3);
    margin-bottom: 24px; flex-wrap: wrap;
  }
  .breadcrumb a { color: rgba(255,255,255,0.4); text-decoration: none; transition: color 0.2s; }
  .breadcrumb a:hover { color: rgba(255,255,255,0.8); }
  .breadcrumb i { font-size: 0.55rem; }

  /* Section styling for inner pages */
  .inner-section {
    background: #f8fafc;
    padding: 56px 0;
  }
  .inner-card {
    background: white;
    border-radius: 20px;
    padding: 32px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    margin-bottom: 24px;
  }
  .inner-card-title {
    font-size: 1.2rem; font-weight: 700; color: #0f172a;
    margin-bottom: 20px;
    display: flex; align-items: center; gap: 10px;
  }
  .inner-card-title i { color: <?= $accent ?>; }

  /* Section eyebrow shared */
  .page-eyebrow {
    font-size: 0.72rem; font-weight: 700; letter-spacing: 0.12em;
    text-transform: uppercase; color: <?= $accent ?>;
    margin-bottom: 8px;
  }
  .page-title {
    font-size: clamp(1.6rem,3vw,2.4rem); font-weight: 800;
    color: #0f172a; line-height: 1.2; margin-bottom: 12px;
  }
  .page-sub { font-size: 0.95rem; color: #64748b; line-height: 1.75; }
</style>

<section class="inner-hero">
  <div class="inner-glow"></div>
  <div class="inner-glow-r"></div>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 inner-hero-content">
    <div class="breadcrumb">
      <a href="index.php"><i class="fas fa-home"></i> Início</a>
      <i class="fas fa-chevron-right"></i>
      <span><?= htmlspecialchars($badge ?: $title) ?></span>
    </div>
    <?php if ($badge): ?>
    <div class="inner-badge"><i class="<?= $icon ?>"></i> <?= htmlspecialchars($badge) ?></div>
    <?php endif; ?>
    <div class="inner-hero-icon"><i class="<?= $icon ?>"></i></div>
    <h1 class="inner-hero-title"><?= htmlspecialchars($title) ?></h1>
    <?php if ($sub): ?>
    <p class="inner-hero-sub"><?= htmlspecialchars($sub) ?></p>
    <?php endif; ?>
  </div>
</section>
