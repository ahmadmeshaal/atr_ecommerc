<?php $storeActive = $storeActive ?? ''; ?><div class="announcement"><span>AUTHENTIC FRAGRANCES, ALWAYS</span><i></i><span>CASH ON DELIVERY</span></div>
<header class="store-header"><a class="wordmark" href="index.php" aria-label="Ater home">ater<span>®</span></a><button class="menu-toggle" aria-label="Open navigation" aria-expanded="false"><span></span><span></span><span></span></button>
    <nav class="main-nav"><a data-nav-route="home" class="<?= $storeActive === 'home' ? 'active' : '' ?>" href="index.php">Home</a><a data-nav-route="fragrances" class="<?= $storeActive === 'products' ? 'active' : '' ?>" href="products.php">Fragrances</a><a data-nav-route="collections" class="<?= $storeActive === 'collections' ? 'active' : '' ?>" href="products.php?category=sets">Collections & sets</a></nav>
    <div class="header-actions">
        <a class="icon-link account-link" href="login.php" aria-label="Sign in"><span class="profile-avatar" id="profile-avatar" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3.5"/><path d="M5 21v-1.7a7 7 0 0 1 14 0V21"/></svg></span><span class="account-meta"><small>YOUR ACCOUNT</small><strong class="account-label">Sign in</strong></span></a>
        <a class="icon-link wishlist-nav-link" href="wishlist.php" aria-label="Saved fragrances"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.4 8.8c0 5.1-8.4 10.2-8.4 10.2S3.6 13.9 3.6 8.8a4.3 4.3 0 0 1 8.4-1.2 4.3 4.3 0 0 1 8.4 1.2Z"/></svg><span class="action-label">Saved</span><span class="wishlist-count" id="wishlist-count" hidden>0</span></a>
        <button class="icon-link bag-link" type="button" data-mini-cart-open aria-label="Open shopping bag" aria-controls="mini-cart" aria-expanded="false"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 8h14l1 13H4L5 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg><span class="action-label">Bag</span><span class="bag-count" id="bag-count">0</span></button>
    </div>
</header>
<div class="mini-cart-backdrop" data-mini-cart-close hidden></div>
<aside class="mini-cart" id="mini-cart" role="dialog" aria-modal="true" aria-labelledby="mini-cart-title" aria-hidden="true" inert>
    <div class="mini-cart-header"><div><p class="eyebrow">YOUR SELECTION</p><h2 id="mini-cart-title">Shopping bag <span id="mini-cart-item-count">(0)</span></h2></div><button class="mini-cart-close" type="button" data-mini-cart-close aria-label="Close shopping bag">×</button></div>
    <div class="mini-cart-items" id="mini-cart-items" aria-live="polite"><div class="mini-cart-loading">Your bag is ready when you are…</div></div>
    <div class="mini-cart-footer"><div class="mini-cart-subtotal"><span>Subtotal</span><strong id="mini-cart-subtotal">0.000 JOD</strong></div><p>Shipping and any discounts are calculated at checkout.</p><a class="mini-cart-checkout" href="checkout.php">Continue to checkout <span aria-hidden="true">↗</span></a><a class="mini-cart-view" href="cart.php">View full bag</a></div>
</aside>
