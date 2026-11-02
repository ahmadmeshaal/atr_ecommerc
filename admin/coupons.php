<?php
require_once __DIR__ . '/../includes/admin-auth.php';
$adminActive = 'coupons';
$adminTitle = 'Coupons';
include __DIR__ . '/../includes/admin-header.php';
?>
<section class="admin-page-heading">
  <div><p class="admin-eyebrow">PROMOTIONS</p><h1>Coupons.</h1><p>Manage discount codes, their usage, and availability.</p></div>
  <button class="admin-button admin-button-dark" type="button" data-open-coupon>＋ &nbsp; Add coupon</button>
</section>
<div class="admin-error hidden" data-coupon-error role="alert"></div>
<section class="admin-coupon-summary" aria-label="Coupon overview">
  <article><span>All codes</span><strong data-coupon-total>—</strong></article>
  <article><span>Available now</span><strong data-coupon-active>—</strong></article>
  <article><span>Total uses</span><strong data-coupon-uses>—</strong></article>
  <article><span>Fully redeemed</span><strong data-coupon-exhausted>—</strong></article>
</section>
<section class="admin-panel admin-coupon-panel">
  <div class="admin-panel-heading"><div><p class="admin-eyebrow">DISCOUNT CODES</p><h2>Coupon library</h2></div><span class="admin-panel-note" data-coupon-count></span></div>
  <div class="admin-toolbar admin-coupon-toolbar">
    <input type="search" data-coupon-search placeholder="Search code…" aria-label="Search coupon codes">
    <select data-coupon-filter aria-label="Filter coupons"><option value="all">All coupons</option><option value="active">Available</option><option value="scheduled">Scheduled</option><option value="exhausted">Fully redeemed</option><option value="expired">Expired</option><option value="inactive">Inactive</option></select>
  </div>
  <div class="admin-table-wrap"><table class="admin-table admin-coupon-table">
    <thead><tr><th>Code</th><th>Discount</th><th>Usage</th><th>Minimum order</th><th>Validity</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody data-coupon-rows><tr><td colspan="7"><div class="admin-loading">Loading coupons…</div></td></tr></tbody>
  </table></div>
</section>
<div class="admin-modal-backdrop" data-coupon-modal aria-hidden="true">
  <section class="admin-modal admin-modal-small" role="dialog" aria-modal="true" aria-labelledby="coupon-modal-title">
    <form data-coupon-form><input type="hidden" name="id">
      <header class="admin-modal-header"><div><p class="admin-eyebrow">PROMOTION SETTINGS</p><h2 id="coupon-modal-title">Add a coupon</h2><p>Set the discount, usage cap, and validity dates.</p></div><button class="admin-modal-close" type="button" data-close-coupon aria-label="Close">×</button></header>
      <div class="admin-modal-body">
        <div class="admin-form-grid">
          <div class="admin-field full"><label for="coupon-code">COUPON CODE</label><input id="coupon-code" name="coupon-code" maxlength="50" placeholder="e.g. WELCOME10" required autocomplete="off"></div>
          <div class="admin-field"><label for="coupon-discount-type">DISCOUNT TYPE</label><select id="coupon-discount-type" name="coupon-discount-type"><option value="percentage">Percentage</option><option value="fixed">Fixed amount (JOD)</option></select></div>
          <div class="admin-field"><label for="coupon-discount-value">DISCOUNT VALUE</label><input id="coupon-discount-value" name="coupon-discount-value" type="number" min="0.01" max="100" step="0.01" required></div>
          <div class="admin-field"><label for="coupon-usage-limit">USAGE LIMIT</label><input id="coupon-usage-limit" name="coupon-usage-limit" type="number" min="1" step="1" placeholder="Unlimited"></div>
          <div class="admin-field"><label for="coupon-min-order-amount">MINIMUM ORDER (JOD)</label><input id="coupon-min-order-amount" name="coupon-min-order-amount" type="number" min="0" step="0.01" value="0"></div>
          <div class="admin-field"><label for="coupon-start-at">STARTS AT</label><input id="coupon-start-at" name="coupon-start-at" type="datetime-local"></div>
          <div class="admin-field"><label for="coupon-expires-at">EXPIRES AT</label><input id="coupon-expires-at" name="coupon-expires-at" type="datetime-local"></div>
          <label class="admin-check admin-coupon-active"><input name="coupon-is-active" type="checkbox" value="1" checked> Coupon is active</label>
        </div>
        <div class="admin-error hidden" data-coupon-form-error role="alert"></div>
      </div>
      <footer class="admin-form-footer"><button class="admin-button admin-button-soft" type="button" data-close-coupon>Cancel</button><button class="admin-button admin-button-dark" type="submit">Save coupon</button></footer>
    </form>
  </section>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>