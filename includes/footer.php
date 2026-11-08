<?php
// includes/footer.php - closes the layout opened in header.php
$basePath = $basePath ?? '../';
$extraJs  = $extraJs ?? [];         // e.g. ['dashboard.js']
?>
</div><!-- /.layout -->
<footer class="site-footer">
    <p>e-GURO Learning Management System</p>
    <p>City College of Calamba</p>
</footer>
<?php foreach ($extraJs as $js): ?>
<script src="<?= e($basePath) ?>assets/js/<?= e($js) ?>"></script>
<?php endforeach; ?>
<script src="<?= e($basePath) ?>assets/js/sidebar-nav.js?v=<?= (int) filemtime(__DIR__ . '/../assets/js/sidebar-nav.js') ?>" defer></script>
</body>
</html>
