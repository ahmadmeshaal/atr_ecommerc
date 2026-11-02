<?php $storeActive = (($_GET['category'] ?? '') === 'sets') ? 'collections' : 'products'; ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Fragrances — Ater</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/storefront.css?v=20261102-responsive">
    <script defer src="assets/js/storefront.js?v=20261102-autosignup"></script>
</head>

<body><?php include __DIR__ . '/includes/store-header.php'; ?><main class="page-shell fragrance-page">
        <section class="filter-panel fragrance-filter-panel" aria-label="Find and filter fragrances">
            <div class="filter-tools">
                <span class="filter-tools-label"><span aria-hidden="true">✳</span> Find your fragrance</span>
                <button id="clear-filters" type="button" class="filter-reset">Reset filters <span aria-hidden="true">↺</span></button>
            </div>
            <div class="catalog-toolbar">
                <div class="filter-search filter-card"><label for="catalog-search">Search</label>
                    <div class="filter-input-wrap"><span aria-hidden="true">⌕</span><input id="catalog-search" type="search" placeholder="Name, note or house…"></div>
                </div>
                <div class="filter-control filter-card house-filter-control" id="house-filter-control"><label>Fragrance houses</label>
                    <button class="house-picker-toggle" id="house-picker-toggle" type="button" aria-expanded="false" aria-controls="house-picker-panel"><span id="house-picker-label">Any house</span><span class="house-picker-arrow" aria-hidden="true">⌄</span></button>
                    <div class="house-picker-panel" id="house-picker-panel" hidden>
                        <label class="house-picker-search"><span aria-hidden="true">⌕</span><input id="house-picker-search" type="search" placeholder="Type a house name…" autocomplete="off" aria-label="Search fragrance houses"></label>
                        <div class="house-picker-options" id="house-picker-options" role="group" aria-label="Choose fragrance houses"><span class="facet-loading">Loading houses…</span></div>
                        <span class="house-picker-empty" id="house-picker-empty" hidden>No houses match that name.</span>
                    </div>
                </div>
                <div class="filter-control filter-sort filter-card"><label for="catalog-sort">Sort by</label><select id="catalog-sort" class="sr-only" aria-label="Sort fragrances">
                        <option value="featured">Featured</option>
                        <option value="newest">Newest</option>
                        <option value="price-low">Price: low to high</option>
                        <option value="price-high">Price: high to low</option>
                    </select><details class="sort-picker" id="sort-picker"><summary class="house-picker-toggle"><span id="sort-picker-label">Featured</span><span class="house-picker-arrow" aria-hidden="true">⌄</span></summary><div class="sort-picker-options" role="group" aria-label="Sort fragrances"><button class="sort-picker-choice" type="button" data-sort-value="featured" aria-pressed="true">Featured</button><button class="sort-picker-choice" type="button" data-sort-value="newest" aria-pressed="false">Newest</button><button class="sort-picker-choice" type="button" data-sort-value="price-low" aria-pressed="false">Price: low to high</button><button class="sort-picker-choice" type="button" data-sort-value="price-high" aria-pressed="false">Price: high to low</button></div></details></div>
                <div class="result-count" id="result-count" aria-live="polite">Loading fragrances…</div>
            </div>
        </section>
        <div class="catalog-layout">
            <aside class="catalog-sidebar" aria-label="Additional fragrance filters">
                <section class="filter-facets filter-card" aria-label="Scent and occasion filters">
                    <div class="facet-heading"><div><strong>Explore by scent &amp; moment</strong><span>Choose one or more options in any group.</span></div><span class="facet-selected-count" id="facet-selected-count">0 selected</span></div>
                    <label class="facet-search-wrap"><span aria-hidden="true">⌕</span><input id="facet-search" type="search" placeholder="Type a family, season or occasion…" autocomplete="off" aria-label="Search filter options"></label>
                    <div class="facet-groups" id="catalog-facets"><span class="facet-loading">Loading scent and occasion filters…</span></div>
                </section>
            </aside>
            <section class="catalog-results" aria-label="Fragrance results">
                <div class="product-grid" id="catalog-products">
                    <div class="loading-state">Loading our collection…</div>
                </div><button id="catalog-more" class="button button-dark catalog-more hidden" type="button">LOAD MORE</button>
            </section>
        </div>
    </main><?php include __DIR__ . '/includes/store-footer.php'; ?></body>

</html>
