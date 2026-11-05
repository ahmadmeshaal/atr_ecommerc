<?php $storeActive = ''; ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Your fragrance space — Ater</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/storefront.css?v=20261102-navbar-position">
    <script defer src="assets/js/storefront.js?v=20261102-autosignup"></script>
</head>

<body><?php include __DIR__ . '/includes/store-header.php'; ?><main class="page-shell account-page-shell">
        <section class="account-welcome">
            <div>
                <p class="eyebrow">A PLACE FOR YOUR FAVORITES</p>
                <h1>Your fragrance space.</h1>
                <p>Your orders and saved fragrances, together in one place.</p>
            </div>
            <span class="account-welcome-mark" aria-hidden="true">ATER <i>✳</i></span>
        </section>
        <div id="account-content" class="account-layout">
            <aside class="account-sidebar">
                <div class="account-profile-card">
                    <span class="account-profile-avatar" id="account-avatar" aria-hidden="true">A</span>
                    <div><p class="eyebrow">YOUR PROFILE</p><h2 id="account-name">Welcome</h2><p id="account-email"></p></div>
                </div>
                <nav class="account-menu" aria-label="Your account">
                    <a class="is-active" href="#orders"><span>Orders</span><span aria-hidden="true">↗</span></a>
                    <a href="wishlist.php"><span>Saved fragrances</span><span aria-hidden="true">↗</span></a>
                    <a href="products.php"><span>Explore fragrances</span><span aria-hidden="true">↗</span></a>
                    <button id="logout-button" class="account-signout" type="button"><span><span class="signout-icon" aria-hidden="true">↗</span>Sign out</span><span aria-hidden="true">Log out</span></button>
                </nav>
                <p class="account-sidebar-note">A little space to keep track of the scents you love.</p>
            </aside>
            <section id="orders" class="account-orders-panel">
                <div class="section-head">
                    <div>
                        <p class="eyebrow">YOUR PURCHASES</p>
                        <h2>Order history</h2>
                        <p>From the moment you choose a scent to its arrival.</p>
                    </div>
                </div>
                <div id="orders-list">
                    <div class="loading-state">Loading your orders…</div>
                </div>
            </section>
        </div>
    </main><?php include __DIR__ . '/includes/store-footer.php'; ?></body>

</html>
