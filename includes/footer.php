    </main>

    <footer class="site-footer">
        <div class="container d-flex flex-column flex-lg-row justify-content-between align-items-center gap-2">
            <p class="mb-0">Fresh cakes, secure checkout, and smoother order tracking built on a reusable PHP foundation.</p>
            <p class="mb-0 small">Default admin: <code>admin@cakeshop.local</code> / <code>admin123</code></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo e(asset_url('js/app.js')); ?>"></script>
    <?php if (!empty($pageScripts)): ?>
        <?php echo $pageScripts; ?>
    <?php endif; ?>
</body>
</html>
