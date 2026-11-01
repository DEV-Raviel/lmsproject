<?php
// includes/widgets/bulletin-widget.php - latest bulletins, reusable on any dashboard
$latestBulletins = get_bulletins(4);
?>
<aside class="widget bulletin-widget">
    <h3>Bulletin</h3>
<?php foreach ($latestBulletins as $b): ?>
    <article>
        <h4><a href="bulletin.php?id=<?= (int) $b['id'] ?>"><?= e($b['title']) ?></a></h4>
        <p class="muted"><?= e(time_ago($b['created_at'])) ?></p>
    </article>
<?php endforeach; ?>
    <p><a href="bulletins.php">View All Bulletins</a></p>
</aside>
