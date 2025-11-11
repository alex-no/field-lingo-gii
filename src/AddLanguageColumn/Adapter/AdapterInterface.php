<?php
declare(strict_types=1);

namespace AlexNo\FieldLingoGii\AddLanguageColumn\Adapter;

use yii\gii\CodeFile;
use yii\db\TableSchema;

interface AdapterInterface
{
    /**
     * Generate CodeFile(s) for the provided table/localized-group.
     *
     * @param TableSchema $table Table schema object
     * @param string $baseName Base field name (without suffix)
     * @param string[] $columns Ordered list of existing localized columns for this base
     * @param string $newLanguageSuffix Suffix for new column (e.g. "fr")
     * @param array $options Additional options (position, etc.)
     * @return CodeFile[] array of CodeFile objects to return to Gii
     */
    public function generateFor(
        TableSchema $table,
        string $baseName,
        array $columns,
        string $newLanguageSuffix,
        array $options = []
    ): array;
}
