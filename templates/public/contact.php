<div class="detail-wrap">
    <p class="eyebrow">CONTACT</p><h1>Get in touch</h1>
    <?php $messenger = Database::setting('messenger'); ?>
    <?php if ($messenger !== '' && filter_var($messenger, FILTER_VALIDATE_URL)): ?>
        <p class="muted">Message us on Messenger:</p>
        <a class="btn btn-gold" href="<?= e($messenger) ?>" target="_blank" rel="noopener noreferrer">Open Messenger ↗</a>
    <?php else: ?>
        <p class="muted">Messenger link is not configured yet.</p>
    <?php endif; ?>
</div>
