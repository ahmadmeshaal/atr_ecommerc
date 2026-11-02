<?php
require_once __DIR__ . '/../includes/admin-auth.php';
$adminActive = 'products';
$adminTitle = 'Products';
include __DIR__ . '/../includes/admin-header.php';
?>
<section class="admin-page-heading">
  <div>
    <p class="admin-eyebrow">CATALOG MANAGEMENT</p>
    <h1>Your fragrances.</h1>
    <p>Manage product stories, scent categories, images, sizes, pricing and inventory.</p>
  </div><button class="admin-button admin-button-dark" type="button" data-open-new-product>＋ &nbsp; Add product</button>
</section>
<div class="admin-error hidden" data-product-error role="alert"></div>
<div class="admin-toolbar admin-product-toolbar"><input type="search" data-product-search placeholder="Search by product or house…" aria-label="Search products"><div class="admin-filter-picker" data-product-brand-filter>
    <button class="admin-filter-toggle" type="button" data-product-brand-toggle aria-expanded="false"><span data-product-brand-label>All fragrance houses</span><span aria-hidden="true">⌄</span></button>
    <div class="admin-filter-panel" data-product-brand-panel hidden><label class="admin-filter-search"><span aria-hidden="true">⌕</span><input type="search" data-product-brand-search placeholder="Type a house name…" autocomplete="off" aria-label="Search fragrance houses"></label><div class="admin-filter-options" data-product-brand-options role="group" aria-label="Filter by fragrance house"></div><span class="admin-filter-empty" data-product-brand-empty hidden>No houses match that name.</span><button class="admin-filter-clear" type="button" data-clear-product-brands>Clear selection</button></div>
  </div><select data-product-status aria-label="Filter by status">
    <option value="">All products</option>
    <option value="active">Active</option>
    <option value="inactive">Drafts</option>
  </select><select data-product-stock aria-label="Filter by stock">
    <option value="">Any stock level</option>
    <option value="low">Low stock · 5 or fewer</option>
  </select><button class="admin-button admin-button-soft" type="button" data-reload-products>↻ &nbsp; Refresh</button></div>
<div class="admin-table-wrap">
  <table class="admin-table">
    <thead>
      <tr>
        <th>Fragrance</th>
        <th>House</th>
        <th>Variants</th>
        <th>Stock</th>
        <th>From</th>
        <th>Status</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody data-product-rows>
      <tr>
        <td colspan="7">
          <div class="admin-loading">Loading product catalog…</div>
        </td>
      </tr>
    </tbody>
  </table>
</div>
<div class="admin-pagination" data-product-pagination></div>

<div class="admin-modal-backdrop" data-product-modal aria-hidden="true">
  <section class="admin-modal admin-product-modal" role="dialog" aria-modal="true" aria-labelledby="product-modal-title">
    <form data-product-form>
      <header class="admin-modal-header">
        <div>
          <h2 id="product-modal-title">Add a fragrance</h2>
          <p>Add the essentials first. You can add scent details whenever you’re ready.</p>
        </div><button class="admin-modal-close" type="button" data-close-product-modal aria-label="Close">×</button>
      </header>
      <div class="admin-modal-body admin-product-form-body">
        <input type="hidden" name="id" data-product-id>
        <input id="product-slug" type="hidden" name="slug" required>
        <section class="admin-product-section admin-product-basics">
          <div class="admin-product-section-title"><span>01</span><div><h3>Product basics</h3><p>Name it and choose its fragrance house.</p></div></div>
          <div class="admin-form-grid">
            <div class="admin-field"><label for="product-name">PRODUCT NAME</label><input id="product-name" name="name" maxlength="150" required placeholder="e.g. Khamrah Qahwa"></div>
            <div class="admin-field admin-product-house-field"><label>FRAGRANCE HOUSE</label><input id="product-brand" name="brand_id" type="hidden"><div class="admin-filter-picker admin-product-house-picker" data-product-house-picker><button class="admin-filter-toggle" type="button" data-product-house-toggle aria-expanded="false"><span data-product-house-label>Choose a house</span><span aria-hidden="true">⌄</span></button><div class="admin-filter-panel" data-product-house-panel hidden><label class="admin-filter-search"><span aria-hidden="true">⌕</span><input type="search" data-product-house-search placeholder="Type a house name…" autocomplete="off" aria-label="Search and choose a fragrance house"></label><div class="admin-filter-options" data-product-house-options role="group" aria-label="Choose one fragrance house"></div><span class="admin-filter-empty" data-product-house-empty hidden>No houses match that name.</span></div></div><div class="admin-inline-brand"><button class="admin-inline-brand-toggle" type="button" data-toggle-inline-brand>＋ Add a new house</button><div class="admin-inline-brand-form" data-inline-brand-form hidden><input type="text" data-inline-brand-name maxlength="100" placeholder="New house name" aria-label="New fragrance house name"><button class="admin-button admin-button-soft" type="button" data-save-inline-brand>Add house</button></div><p class="admin-inline-brand-message" data-inline-brand-message aria-live="polite"></p></div></div>
            <label class="admin-check admin-product-publish"><input type="checkbox" name="is_active" checked><span><strong>Show in storefront</strong><small>Turn off to save this as a draft.</small></span></label>
          </div>
        </section>
        <section class="admin-form-section">
          <div class="admin-form-section-head">
            <div>
              <h3>Product photos</h3>
              <p>Drop images here, or choose them from your device.</p>
            </div>
          </div>
          <div class="admin-dropzone" data-product-dropzone role="button" tabindex="0" aria-label="Choose or drop product images">
            <input id="product-image-files" class="admin-dropzone-input" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
            <span class="admin-dropzone-icon" aria-hidden="true">＋</span><strong>Drop product photos here</strong><span>or <button type="button" data-browse-product-images>browse files</button></span><small>JPG, PNG, WEBP or GIF · first image can be the main photo</small>
          </div>
          <div class="admin-pending-images" data-pending-image-preview aria-live="polite"></div>
          <div class="admin-image-list" data-product-images></div>
          <div class="admin-form-grid admin-product-image-options">
            <div class="admin-field"><label for="product-image-url">ADD A PHOTO BY URL</label><input id="product-image-url" type="url" placeholder="https://…"></div>
            <label class="admin-check"><input type="checkbox" data-primary-new-image> Make the first new image the main photo</label>
          </div>
        </section>
        <section class="admin-form-section">
          <div class="admin-form-section-head">
            <div>
              <h3>Sizes, price &amp; stock</h3>
              <p>Add one row for each bottle size you sell.</p>
            </div><button class="admin-button admin-button-soft" type="button" data-add-variant>＋ Add size</button>
          </div>
          <div data-variant-list></div>
        </section>
        <details class="admin-product-advanced">
          <summary>More details <span>Categories and product description</span></summary>
          <div class="admin-product-advanced-body">
            <div class="admin-field"><label for="product-description">PRODUCT DESCRIPTION</label><textarea id="product-description" name="description" maxlength="60000" placeholder="Describe the fragrance and its character…"></textarea></div>
            <div><p class="admin-product-advanced-label">CATEGORIES &amp; FRAGRANCE CHARACTER <span data-product-category-count>0 selected</span></p><label class="admin-category-search"><span aria-hidden="true">⌕</span><input type="search" data-product-category-search placeholder="Search categories…" autocomplete="off" aria-label="Search product categories"></label><div class="admin-categories-checklist" data-product-categories><div class="admin-loading">Loading categories…</div></div></div>
          </div>
        </details>
        <div class="admin-error hidden" data-product-form-error role="alert"></div>
      </div>
      <footer class="admin-form-footer"><button class="admin-button admin-button-soft" type="button" data-close-product-modal>Cancel</button><button class="admin-button admin-button-dark" type="submit" data-save-product>Save product</button></footer>
    </form>
  </section>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
