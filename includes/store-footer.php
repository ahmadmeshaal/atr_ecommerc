<footer class="store-footer">
    <div class="footer-main">
        <div class="footer-brand"><a class="wordmark" href="index.php">ater<span>®</span></a>
            <p>Fragrance, thoughtfully found.<br>Curated in Amman, Jordan.</p>
        </div>
        <div>
            <h3>Explore</h3><a href="products.php">All fragrances</a><a href="products.php?category=sets">Collections & sets</a><a href="wishlist.php">Your wishlist</a>
        </div>
        <div>
            <h3>Your account</h3><a href="login.php">Sign in</a><a href="register.php">Create an account</a><a href="account.php">Order history</a>
        </div>
        <div>
            <h3>Visit us</h3>
            <p>Amman, Jordan</p>
            <p>Questions? We’re happy to help.</p><a href="https://wa.me/962775831777" target="_blank" rel="noreferrer">Chat on WhatsApp ↗</a>
        </div>
    </div>
    <div class="footer-bottom"><span>© <?= date('Y') ?> Ater. Made for the moments that stay.</span><span>JOD&nbsp; · &nbsp;Jordan</span></div>
</footer><div class="scent-chat" id="scent-chat">
    <button class="scent-chat-launch" id="scent-chat-launch" type="button" aria-label="Open fragrance assistant" aria-expanded="false"><span class="scent-chat-launch-icon">✦</span><span class="scent-chat-launch-label">Find your scent</span></button>
    <section class="scent-chat-panel" id="scent-chat-panel" aria-label="Fragrance assistant" aria-hidden="true">
        <header class="scent-chat-head"><div><span class="scent-chat-kicker">ATER FRAGRANCE GUIDE</span><strong>Find your next scent</strong><small>Tell me what you like and I’ll search our collection.</small></div><button id="scent-chat-close" type="button" aria-label="Close chat">×</button></header>
        <div class="scent-chat-messages" id="scent-chat-messages" aria-live="polite"><div class="scent-chat-message assistant">Hi! Looking for something fresh, warm, sweet, or for a particular season?</div></div>
        <form class="scent-chat-form" id="scent-chat-form"><input id="scent-chat-input" name="message" maxlength="1000" autocomplete="off" placeholder="e.g. a warm winter fragrance" aria-label="Message the fragrance assistant" required><button type="submit" aria-label="Send message">↑</button></form>
        <button class="scent-chat-reset" id="scent-chat-reset" type="button">Start a new conversation</button>
    </section>
</div>
<script defer src="assets/js/ai-chat.js?v=20261101-price"></script>
