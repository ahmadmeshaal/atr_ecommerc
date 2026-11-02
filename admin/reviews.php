<?php
require_once __DIR__ . '/../includes/admin-auth.php';
$adminActive = 'reviews';
$adminTitle = 'Reviews';
include __DIR__ . '/../includes/admin-header.php';
?>
<section class="admin-page-heading">
    <div>
        <p class="admin-eyebrow">CUSTOMER VOICES</p>
        <h1>Reviews.</h1>
        <p>AI checks reviews when they are submitted. Clear reviews publish automatically; flagged reviews stay hidden here until you review them.</p>
    </div>
</section>
<div class="admin-error hidden" data-reviews-error role="alert"></div>
<div class="admin-review-attention hidden" data-review-attention role="status" aria-live="polite"></div>
<div class="admin-toolbar"><input type="search" data-review-search placeholder="Search review, customer or fragrance…" aria-label="Search reviews"><select data-review-rating aria-label="Filter by rating">
        <option value="">All ratings</option>
        <option value="5">5 stars</option>
        <option value="4">4 stars</option>
        <option value="3">3 stars</option>
        <option value="2">2 stars</option>
        <option value="1">1 star</option>
    </select><select data-review-status aria-label="Filter by moderation state"><option value="">All moderation states</option><option value="pending">Needs review</option><option value="flagged">Needs admin review</option><option value="approved">Published</option><option value="rejected">Kept hidden</option></select><span class="result-count" data-review-count></span></div>
<div class="admin-review-grid" data-review-list>
    <div class="admin-loading">Loading reviews…</div>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
