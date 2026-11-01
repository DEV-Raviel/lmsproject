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
</body>
</html>
