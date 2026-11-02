<?php
require_once __DIR__ . '/../includes/admin-auth.php';
$adminActive = 'orders';
$adminTitle = 'Orders';
include __DIR__ . '/../includes/admin-header.php';
?>
<section class="admin-page-heading">
    <div>
        <p class="admin-eyebrow">FULFILMENT &amp; PAYMENTS</p>
        <h1>Orders.</h1>
        <p>Review customer purchases and update fulfilment status.</p>
    </div><button class="admin-button admin-button-soft" type="button" data-refresh-orders>↻ &nbsp; Refresh</button>
</section>
<div class="admin-error hidden" data-orders-error role="alert"></div>
<div class="admin-toolbar"><input type="search" data-order-search placeholder="Search by customer or order number…" aria-label="Search orders"><select data-order-status aria-label="Filter orders by status">
        <option value="">All statuses</option>
        <option value="pending">Pending</option>
        <option value="confirmed">Confirmed</option>
        <option value="processing">Processing</option>
        <option value="shipped">Shipped</option>
        <option value="delivered">Delivered</option>
        <option value="cancelled">Cancelled</option>
    </select><span class="result-count" data-order-count></span></div>
<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Order</th>
                <th>Customer</th>
                <th>Placed</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody data-order-rows>
            <tr>
                <td colspan="7">
                    <div class="admin-loading">Loading orders…</div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
<div class="admin-pagination" data-order-pagination></div>
<div class="admin-modal-backdrop" data-order-modal aria-hidden="true">
    <section class="admin-modal admin-order-modal" role="dialog" aria-modal="true" aria-labelledby="order-modal-title">
        <header class="admin-modal-header">
            <div>
                <h2 id="order-modal-title">Order details</h2>
                <p>Customer, delivery and payment summary.</p>
            </div><button class="admin-modal-close" type="button" data-close-order aria-label="Close">×</button>
        </header>
        <div class="admin-modal-body" data-order-detail>
            <div class="admin-loading">Loading order details…</div>
        </div>
        <footer class="admin-form-footer"><button class="admin-button admin-button-soft" type="button" data-close-order>Close</button><label class="admin-field hidden" data-order-status-control><span class="admin-sr-only">New order status</span><select data-order-next-status aria-label="New order status"><option value="confirmed">Confirmed</option><option value="processing">Processing</option><option value="shipped">Shipped</option><option value="delivered">Delivered</option></select></label><button class="admin-button admin-button-dark hidden" type="button" data-order-status-update>Update status</button><button class="admin-button admin-button-soft hidden" type="button" data-order-edit>Edit status</button><button class="admin-button admin-button-soft hidden" type="button" data-order-edit-cancel>Discard edits</button><button class="admin-button admin-button-dark hidden" type="button" data-order-save>Save changes</button><button class="admin-button admin-button-dark hidden" type="button" data-order-deliver>Mark delivered</button><button class="admin-button admin-button-danger hidden" type="button" data-order-cancel>Cancel order</button></footer>
    </section>
</div>
<div class="admin-modal-backdrop" data-cancel-reason-modal aria-hidden="true">
    <section class="admin-modal admin-modal-small" role="dialog" aria-modal="true" aria-labelledby="cancel-reason-title">
        <header class="admin-modal-header"><div><p class="admin-eyebrow">CUSTOMER NOTIFICATION</p><h2 id="cancel-reason-title">Cancel this order?</h2><p>The selected reason will be included in the customer’s email.</p></div><button class="admin-modal-close" type="button" data-cancel-reason-close aria-label="Close">×</button></header>
        <form data-cancel-reason-form>
            <div class="admin-modal-body">
                <div class="admin-field"><label for="cancel-reason-select">CANCELLATION REASON</label><select id="cancel-reason-select" name="cancellation_reason" required><option value="" selected disabled>Choose a reason</option><option value="out_of_stock">An item is out of stock</option><option value="delivery_issue">Delivery issue</option><option value="payment_issue">Payment could not be confirmed</option><option value="address_unavailable">Delivery address unavailable</option><option value="customer_request">Customer request</option><option value="other">Other reason</option></select></div>
                <div class="admin-field hidden" data-custom-cancel-reason><label for="cancel-reason-custom">CUSTOM REASON</label><textarea id="cancel-reason-custom" name="custom_cancellation_reason" maxlength="500" rows="3" placeholder="Explain why the order is being cancelled"></textarea></div>
            </div>
            <footer class="admin-form-footer"><button class="admin-button admin-button-soft" type="button" data-cancel-reason-close>Back</button><button class="admin-button admin-button-danger" type="submit">Continue</button></footer>
        </form>
    </section>
</div>
<?php include __DIR__ . '/../includes/admin-footer.php'; ?>
