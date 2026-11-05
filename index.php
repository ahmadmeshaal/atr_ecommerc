<?php
// Ater storefront home: product and collection data are loaded from the project API.
$storeActive = 'home';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="theme-color" content="#f7f5f0">
    <title>Ater — Fragrance, thoughtfully found</title>
    <script>try{if(!sessionStorage.getItem('ater-home-intro-seen'))document.documentElement.classList.add('home-intro-pending')}catch{document.documentElement.classList.add('home-intro-pending')}</script>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/storefront.css?v=20261102-navbar-position">
    <script defer src="assets/js/storefront.js?v=20261102-autosignup"></script>
</head>

<body>
    <section class="home-intro" id="home-intro" role="dialog" aria-modal="true" aria-label="Ater fragrance introduction">
        <div class="home-intro-content">
            <p class="home-intro-kicker">A SCENT TO REMEMBER</p>
            <div class="home-intro-logo" aria-label="Ater">ater<span>®</span></div>
            <p class="home-intro-tagline">Scent, thoughtfully found.</p>
            <div class="home-intro-bottles" aria-hidden="true">
                <img class="intro-bottle intro-bottle-one" src="https://imagedelivery.net/dTnWiVKich4PS2lEWud18g/f6d040c6-2009-42aa-dd70-83c8b3b2fa00/public" alt="" fetchpriority="high">
                <img class="intro-bottle intro-bottle-two" src="https://imagedelivery.net/dTnWiVKich4PS2lEWud18g/a9a22b2e-4e39-47cf-95b9-c13c5f31e900/public" alt="" fetchpriority="high">
                <img class="intro-bottle intro-bottle-three" src="https://imagedelivery.net/dTnWiVKich4PS2lEWud18g/5e07f8fd-f8c7-4c4b-ef2e-f9852b680700/public" alt="" fetchpriority="high">
            </div>
            <button class="home-intro-enter" type="button" data-home-intro-skip>Enter Ater <span aria-hidden="true">↗</span></button>
        </div>
    </section>
    <?php include __DIR__ . '/includes/store-header.php'; ?><main>
        <section class="hero">
            <div class="hero-copy">
                <p class="eyebrow">A SCENT TO REMEMBER</p>
                <h1>Find the fragrance<br><em>that feels like you.</em></h1>
                <p>Explore authentic scents, thoughtful gifts, and little luxuries made for every day.</p><a class="button button-dark" href="products.php">Explore fragrances <span>↗</span></a>
                <div class="hero-note"><span class="stars">★★★★★</span><span>Curated with care in Jordan</span></div>
            </div>
            <div class="hero-visual"><img class="hero-product-image" src="https://imagedelivery.net/dTnWiVKich4PS2lEWud18g/yara-tous-lattafa-bottle-20261027/public" alt="Yara Tous by Lattafa, a bright fruity fragrance" />
                <div class="hero-caption"><span>THE ART OF SCENT.</span><strong>Make it yours.</strong></div><span class="hero-orbit orbit-one"></span><span class="hero-orbit orbit-two"></span>
            </div>
            <div class="hero-side">DISCOVER · WEAR · REMEMBER</div>
        </section>
        <section class="scent-marquee" aria-label="Explore fragrances">
            <div class="scent-marquee-heading"><span class="eyebrow">A SCENT FOR EVERY MOMENT</span><a href="products.php">Explore all fragrances ↗</a></div>
            <div class="scent-marquee-window"><div class="scent-marquee-track" id="scent-marquee-track"><span class="loading-state">A moving edit of our fragrances is on its way…</span></div></div>
        </section>
        <div class="trust-strip"><span>✳ 100% AUTHENTIC FRAGRANCES</span><span>✳ CASH ON DELIVERY</span><span>✳ THOUGHTFUL GIFT WRAPPING</span><span>✳ DELIVERY ACROSS JORDAN</span></div>
        <section class="split-promo">
            <div class="split-image"><img src="https://images.unsplash.com/photo-1608571423902-eed4a5ad8108?auto=format&fit=crop&w=1100&q=85" alt="A collection of perfume and beauty essentials"></div>
            <div class="split-copy">
                <p class="eyebrow">THE DAILY RITUAL</p>
                <h2>Good things<br>come in <em>beautiful</em><br>bottles.</h2>
                <p>From a fresh first spritz to the scent they remember, find a new signature in our carefully chosen collection.</p><a class="text-link" href="products.php">Shop all fragrances <span>↗</span></a>
            </div>
        </section>
        <section class="product-section best-sellers-section">
            <div class="section-head">
                <div>
                    <p class="eyebrow">THE SCENTS YOU COME BACK FOR</p>
                    <h2>Best sellers</h2>
                    <p>Three favorites, with a little more to discover.</p>
                </div><a class="text-link" href="products.php?sort=popular">Shop best sellers <span>↗</span></a>
            </div>
            <div class="best-sellers-grid" id="best-sellers-grid"><div class="loading-state">Finding the favorites…</div></div>
        </section>
        <section class="editorial-pair"><a class="editorial-card editorial-dark" href="products.php?gender=her"><span class="eyebrow">FOR HER</span><strong>A little<br>more <em>you.</em></strong><span class="editorial-cta">Explore her scents ↗</span><img src="https://images.unsplash.com/photo-1541643600914-78b084683601?auto=format&fit=crop&w=900&q=85" alt="Elegant perfume bottle"></a><a class="editorial-card editorial-light" href="products.php?gender=him"><span class="eyebrow">FOR HIM</span><strong>Leave a<br>lasting <em>impression.</em></strong><span class="editorial-cta">Explore his scents ↗</span><img src="https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?auto=format&fit=crop&w=900&q=85" alt="Men's fragrance bottle"></a></section>
        <section class="product-section">
            <div class="section-head">
                <div>
                    <p class="eyebrow">JUST LANDED</p>
                    <h2>Latest arrivals</h2>
                    <p>New arrivals from Tom Ford, waiting to meet you.</p>
                </div><a id="latest-view-all" class="text-link" href="products.php?sort=newest">View Tom Ford ↗</a>
            </div>
            <div class="product-grid" id="latest-products">
                <div class="loading-state">Discovering the latest scents…</div>
            </div>
        </section>
        <section class="quote-band">
            <p class="eyebrow">A NOTE FROM US</p>
            <blockquote>“The right fragrance is the finishing touch you carry with you.”</blockquote><span>A NOTE FROM ATER</span>
        </section>
        <section class="brand-section">
            <div class="section-head">
                <div>
                    <p class="eyebrow">A WORLD OF SCENT</p>
                    <h2>Discover the house</h2>
                </div><a class="text-link" href="products.php">All fragrances ↗</a>
            </div>
            <div id="brand-list" class="brand-list"><span class="loading-state">Our fragrance houses</span></div>
        </section>
        <section class="newsletter" id="about">
            <div>
                <p class="eyebrow">A LITTLE SOMETHING LOVELY</p>
                <h2>Notes from the world of scent.</h2>
                <p>New arrivals, gifting inspiration, and a considered collection of fragrance — from our home in Amman.</p>
            </div>
            <div class="newsletter-contact">
                <p>Need a hand choosing your next scent? We’d love to help.</p><a class="button button-dark" href="https://wa.me/962775831777" target="_blank" rel="noreferrer">CHAT WITH US ↗</a>
            </div>
        </section>
    </main><?php include __DIR__ . '/includes/store-footer.php'; ?></body>

</html>
