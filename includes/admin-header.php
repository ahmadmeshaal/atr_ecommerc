<?php
$adminActive = $adminActive ?? 'dashboard';
$adminTitle = $adminTitle ?? 'Dashboard';
$adminName = $_SESSION['user_name'] ?? 'Administrator';
$adminLinks = [
  ['dashboard', 'Overview', 'index.php', 'dashboard'],
  ['products', 'Products', 'products.php', 'products'],
  ['categories', 'Categories', 'categories.php', 'categories'],
  ['brands', 'Fragrance houses', 'brands.php', 'brands'],
  ['coupons', 'Coupons', 'coupons.php', 'coupons'],
  ['orders', 'Orders', 'orders.php', 'orders'],
  ['customers', 'Customers', 'customers.php', 'customers'],
  ['reviews', 'Reviews', 'reviews.php', 'reviews']
];
$adminIcons = [
  'dashboard' => '<svg viewBox="0 0 24 24"><rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/></svg>',
  'products' => '<svg viewBox="0 0 24 24"><path d="M9 3.5h6v3l1.6 1.8v11.2a1.5 1.5 0 0 1-1.5 1.5h-7A1.5 1.5 0 0 1 6.6 19V8.3L9 6.5v-3Z"/><path d="M9 6.5h6M7 12h10M10 3.5h4"/></svg>',
  'categories' => '<svg viewBox="0 0 24 24"><path d="M4 5.5h7v6H4zM13 5.5h7v6h-7zM4 13.5h7v6H4zM13 13.5h7v6h-7z"/></svg>',
  'brands' => '<svg viewBox="0 0 24 24"><path d="M4 20V7.5L12 4l8 3.5V20M8 20v-5h8v5M8 9.5h.01M12 9.5h.01M16 9.5h.01"/><path d="M3 20h18"/></svg>',
  'coupons' => '<svg viewBox="0 0 24 24"><path d="M4 6.5h16v11H4z"/><path d="M8 6.5v3a2 2 0 0 1-4 0M16 6.5v3a2 2 0 0 0 4 0M8 12h8M8 15h5"/></svg>',
  'orders' => '<svg viewBox="0 0 24 24"><path d="M6 3.5h12v17l-2.5-1.7L13 20.5l-3-1.7-4 1.7v-17Z"/><path d="M9 8h6M9 12h6M9 16h3"/></svg>',
  'customers' => '<svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.3"/><path d="M3.5 20v-1.5a5.5 5.5 0 0 1 11 0V20M16 5.2a3.3 3.3 0 0 1 0 6.3M17 14.5a5.4 5.4 0 0 1 3.5 5.1"/></svg>',
  'reviews' => '<svg viewBox="0 0 24 24"><path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1-4.4-4.3 6.1-.9L12 3Z"/></svg>',
  'store' => '<svg viewBox="0 0 24 24"><path d="M4 10v10h16V10M3 9l1.5-5h15L21 9a2.5 2.5 0 0 1-4.5 1.5A2.5 2.5 0 0 1 12 10a2.5 2.5 0 0 1-4.5.5A2.5 2.5 0 0 1 3 9Z"/><path d="M9 20v-5h6v5"/></svg>',
  'signout' => '<svg viewBox="0 0 24 24"><path d="M10 4H5.5A1.5 1.5 0 0 0 4 5.5v13A1.5 1.5 0 0 0 5.5 20H10M14 8l4 4-4 4M8 12h10"/></svg>'
];
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= htmlspecialchars($adminTitle, ENT_QUOTES, 'UTF-8') ?> · Ater Admin</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/admin.css?v=20261102-responsive">
  <script defer src="../assets/js/admin-panel.js?v=20261102-admin-product-detail-links"></script>
</head>

<body class="admin-body">
  <div class="admin-app">
    <aside class="admin-sidebar" id="admin-sidebar">
      <a class="admin-brand" href="index.php"><span class="admin-brand-mark">A</span><span>ATER<small>FRAGRANCE HOUSE</small></span></a>
      <p class="admin-nav-label">WORKSPACE</p>
      <nav class="admin-nav" aria-label="Admin navigation">
        <?php foreach ($adminLinks as [$key, $label, $href, $icon]): ?>
          <a class="<?= $adminActive === $key ? 'active' : '' ?>" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"><span class="admin-nav-icon" aria-hidden="true"><?= $adminIcons[$icon] ?? '' ?></span><span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span></a>
        <?php endforeach; ?>
      </nav>
      <div class="admin-sidebar-bottom"><a class="admin-storefront-link" href="../index.php"><?= $adminIcons['store'] ?> <span>View storefront</span></a><button type="button" class="admin-signout" data-admin-logout><?= $adminIcons['signout'] ?> <span>Sign out</span></button><span class="admin-version">ATER ADMIN · LOCAL</span></div>
    </aside>
    <div class="admin-main-wrap">
      <header class="admin-topbar"><button class="admin-menu-toggle" type="button" aria-label="Toggle navigation" aria-expanded="false">☰</button>
        <div class="admin-breadcrumb"><span>ATER ADMIN</span><b>/</b><strong><?= htmlspecialchars($adminTitle, ENT_QUOTES, 'UTF-8') ?></strong></div>
        <div class="admin-user"><span class="admin-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($adminName, 0, 1, 'UTF-8'), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?></span><span><strong><?= htmlspecialchars($adminName, ENT_QUOTES, 'UTF-8') ?></strong><small>Administrator</small></span></div>
      </header>
      <main class="admin-main">
