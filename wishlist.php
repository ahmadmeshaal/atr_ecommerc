<?php $storeActive = ''; ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Saved fragrances — Ater</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/storefront.css?v=20261102-navbar-position">
    <script defer src="assets/js/storefront.js?v=20261102-autosignup"></script>
</head>

<body><?php include __DIR__ . '/includes/store-header.php'; ?><main class="page-shell">
        <div class="page-heading">
            <p class="eyebrow">YOUR FAVORITES</p>
            <h1>Saved fragrances.</h1>
            <p>The scents you’d love to come back to.</p>
        </div>
        <div id="wishlist-items" class="product-grid">
            <div class="loading-state">Loading your saved fragrances…</div>
        </div>
    </main><?php include __DIR__ . '/includes/store-footer.php'; ?></body>

</html>
