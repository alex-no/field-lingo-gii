<?php
/** @var yii\web\View $this */
/** @var app\generators\addLanguageColumn\AddLanguageColumnGenerator $generator */
?>

<h2>Operation Summary</h2>

<?php if (!empty($generator->executedSql)): ?>
    <h3>Executed SQL:</h3>
    <ul>
        <?php foreach ($generator->executedSql as $sql): ?>
            <li><code><?= htmlspecialchars($sql) ?></code></li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>No SQL was executed.</p>
<?php endif; ?>

<?php if (!empty($generator->skippedFields)): ?>
    <h3>Skipped Fields (already existed):</h3>
    <ul>
        <?php foreach ($generator->skippedFields as $field): ?>
            <li><code><?= htmlspecialchars($field) ?></code></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
