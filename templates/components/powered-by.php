<?php if (Database::setting('powered_by', '1') === '1'): ?>
<button class="powered-trigger" type="button" data-powered-open>Powered by: <strong>Loadsss</strong></button>
<dialog class="powered-dialog" id="powered-dialog" aria-labelledby="powered-title">
    <button class="icon-button powered-close" type="button" data-powered-close aria-label="Close dialog">×</button>
    <p class="eyebrow">LOADSSS PORTFOLIO</p>
    <h2 id="powered-title">John Lodie Crisostomo</h2>
    <p class="muted">Web Developer · Santa Rosa, Laguna</p>
    <p>I build practical, reliable web systems for small businesses — clear workflows, careful data handling, and tools owners can actually use.</p>
    <div class="powered-cards">
        <span>Custom Web Systems</span><span>API Integrations</span>
        <span>Business Websites</span><span>Support &amp; Troubleshooting</span>
    </div>
    <div class="powered-actions">
        <a class="btn btn-gold" href="https://loadssss.vercel.app/resume" target="_blank" rel="noopener noreferrer">Download Resume ↗</a>
        <a class="btn btn-outline" href="https://loadssss.vercel.app" target="_blank" rel="noopener noreferrer">Visit Portfolio ↗</a>
    </div>
</dialog>
<?php endif; ?>
