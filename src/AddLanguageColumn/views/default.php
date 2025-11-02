<?php
/** @var yii\web\View $this */
/** @var AlexNo\FieldLingoGii\AddLanguageColumn\AddLanguageColumnGenerator $generator */
?>

<h2>Operation Summary</h2>

<?php if (!empty($generator->executedSql)): ?>
    <h3>Executed SQL:</h3>
    <ul>
        <?php foreach ($generator->executedSql as $sql): ?>
            <li><code><?= htmlspecialchars($sql, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code></li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>No SQL was executed.</p>
<?php endif; ?>

<?php if (!empty($generator->skippedFields)): ?>
    <h3>Skipped Fields (already existed):</h3>
    <ul>
        <?php foreach ($generator->skippedFields as $field): ?>
            <li><code><?= htmlspecialchars($field, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
