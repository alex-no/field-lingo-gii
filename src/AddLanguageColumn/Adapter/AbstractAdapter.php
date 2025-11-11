<?php
declare(strict_types=1);

namespace AlexNo\FieldLingoGii\AddLanguageColumn\Adapter;

use AlexNo\FieldLingoGii\AddLanguageColumn\ColumnHelperTrait;
use yii\db\TableSchema;

abstract class AbstractAdapter implements AdapterInterface
{
    use ColumnHelperTrait;

    /**
     * @param array $options
     * @psalm-param array<string, mixed> $options
     */
    public function __construct(
        protected array $options = []
    ) {
    }

    /**
     * Helpers common to adapters can be placed here.
     *
     * @param TableSchema $table
     * @param string $baseName
     * @param array $columns
     * @return string|null
     * @psalm-return ?string
     */
    protected function resolveSourceColumn(TableSchema $table, string $baseName, array $columns): ?string
    {
        $position = $this->options['position'] ?? 'after_all';

        if ($position === 'before_all') {
            return $columns[0] ?? null;
        }

        if ($position === 'after_all') {
            return $columns[array_key_last($columns)] ?? null;
        }

        $candidate = "{$baseName}_{$position}";
        return $table->columns[$candidate] ? $candidate : ($columns[array_key_last($columns)] ?? null);
    }
}
