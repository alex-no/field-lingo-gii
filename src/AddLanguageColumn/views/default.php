<?php
/** @var yii\web\View $this */
/** @var AlexNo\FieldLingoGii\AddLanguageColumn\AddLanguageColumnGenerator $generator */
?>

<h2>Operation Summary</h2>

<?php if ($generator->applyMode === \AlexNo\FieldLingoGii\AddLanguageColumn\ApplyMode::DIRECT_SQL): ?>

    <?php if (!empty($generator->executedSql)): ?>
        <h3>Prepared SQL:</h3>
        <ul>
            <?php foreach ($generator->executedSql as $sql): ?>
                <li><code><?= htmlspecialchars($sql, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code></li>
            <?php endforeach; ?>
        </ul>
        <p>Click <strong>Save</strong> in Gii to execute selected statements (direct execution).</p>
    <?php else: ?>
        <p>No SQL was prepared.</p>
    <?php endif; ?>

<?php else: // migration mode ?>

    <?php if (!empty($generator->generatedMigrations)): ?>
        <h3>Generated migrations:</h3>
        <ul>
            <?php foreach ($generator->generatedMigrations as $mig): ?>
                <li><code><?= htmlspecialchars($mig, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code></li>
            <?php endforeach; ?>
        </ul>
        <p>Save these files to your migrations folder (usually <code>@app/migrations</code>) and run <code>yii migrate</code>.</p>
    <?php else: ?>
        <p>No migrations were generated.</p>
    <?php endif; ?>

<?php endif; ?>

<?php if (!empty($generator->skippedFields)): ?>
    <h3>Skipped Fields (already existed):</h3>
    <ul>
        <?php foreach ($generator->skippedFields as $field): ?>
            <li><code><?= htmlspecialchars($field, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></code></li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
