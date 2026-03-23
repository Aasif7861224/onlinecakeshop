    </main>

    <footer class="site-footer">
        <div class="container d-flex flex-column flex-lg-row justify-content-between align-items-center gap-2">
            <p class="mb-0">Fresh cakes, secure checkout, and smoother order tracking </p>
        </div>
    </footer>

    <div class="toast-container position-fixed bottom-0 end-0 p-3" id="appToastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?php echo e(asset_url('js/app.js')); ?>"></script>
    <?php if (!empty($pageScripts)): ?>
        <?php echo $pageScripts; ?>
    <?php endif; ?>
</body>
</html>
