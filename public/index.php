<?php
$pageTitle = 'Beauty Shop | Everyday care, thoughtfully chosen';
$catalogUrl = 'catalog/index.php';
require __DIR__ . '/../app/includes/site-header.php';
?>
<main id="main">
    <section class="hero" aria-labelledby="hero-title">
        <div class="hero-copy">
            <p class="eyebrow">A little care, every day</p>
            <h1 id="hero-title">Feel good in<br><em>your own skin.</em></h1>
            <p class="hero-description">Discover beauty essentials selected to make your everyday routine feel a little more like you.</p>
            <div class="hero-actions">
                <a class="button" href="#shop">Explore the collection <span aria-hidden="true">↗</span></a>
                <a class="underlined-link" href="register.php">Create your account</a>
            </div>
            <div class="hero-note"><span class="note-line" aria-hidden="true"></span><span>Simple rituals. Considered essentials.</span></div>
        </div>
        <div class="hero-art">
            <img src="../assets/images/hero-beauty.jpg" alt="Beauty products arranged in warm natural light">
            <div class="image-caption"><span>YOUR DAILY RITUAL</span><span>01 / CARE</span></div>
            <div class="hero-stamp" aria-hidden="true"><span>made for</span><strong>you</strong></div>
        </div>
        <div class="hero-index" aria-hidden="true">01 — A MORE PERSONAL ROUTINE</div>
    </section>

    <section class="collection section-wrap" id="shop" aria-labelledby="collection-title">
        <div class="section-heading">
            <div><p class="eyebrow">The edit</p><h2 id="collection-title">Find your everyday.</h2></div>
            <p>Good beauty starts with the things you’ll reach for again and again.</p>
        </div>
        <div class="category-grid">
            <a class="category-card" href="catalog/index.php">
                <img src="../assets/images/category-skincare.jpg" alt="Skincare essentials">
                <span class="category-number">01</span><span class="category-label">Skincare <span aria-hidden="true">↗</span></span>
            </a>
            <a class="category-card" href="catalog/index.php">
                <img src="../assets/images/category-makeup.jpg" alt="Makeup essentials">
                <span class="category-number">02</span><span class="category-label">Makeup <span aria-hidden="true">↗</span></span>
            </a>
            <a class="category-card" href="catalog/index.php">
                <img src="../assets/images/category-bodycare.jpg" alt="Body care essentials">
                <span class="category-number">03</span><span class="category-label">Body care <span aria-hidden="true">↗</span></span>
            </a>
        </div>
        <p class="catalog-note"><a href="catalog/index.php">Browse the product catalog</a></p>
    </section>

    <section class="values-band" id="about" aria-labelledby="values-title">
        <p class="eyebrow">Beauty, without the fuss</p>
        <h2 id="values-title">A thoughtful routine<br>starts with <em>what matters.</em></h2>
        <p>Explore a considered edit of beauty products for the rituals that make a day your own.</p>
    </section>

    <section class="account-invite section-wrap">
        <div><p class="eyebrow">Your next favorite is waiting</p><h2>Make room for a little <em>you-time.</em></h2></div>
        <a class="button" href="register.php">Create an account <span aria-hidden="true">↗</span></a>
    </section>
</main>
<?php require __DIR__ . '/../app/includes/site-footer.php'; ?>
