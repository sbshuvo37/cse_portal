    <footer class="portal-footer">
        <div class="footer-brand">
            <div class="footer-brand-icon">
                <?php if (!empty($settings['logo'])): ?>
                    <img src="<?= UPLOAD_URL . htmlspecialchars($settings['logo']) ?>" alt="">
                <?php else: ?>🎓<?php endif; ?>
            </div>
            <div class="footer-brand-text">
                <strong><?= htmlspecialchars($settings['department_name'] ?? 'Department of Computer Science and Engineering') ?></strong>
                &middot; <?= htmlspecialchars($settings['university_name'] ?? 'JKKNIU') ?>
            </div>
        </div>

        <?php if (!empty($settings['contact_info'])): ?>
        <div class="footer-contact">
            <?php foreach (explode('|', $settings['contact_info']) as $part): ?>
                <span><?= htmlspecialchars(trim($part)) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="footer-copyright">
            &copy; <?= date('Y') ?> <?= htmlspecialchars($settings['portal_name'] ?? 'CSE Department Portal') ?>. All rights reserved.
        </div>
    </footer>
    <script src="<?= BASE_URL ?>assets/js/script.js"></script>
</body>
</html>
