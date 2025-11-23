<?php
declare(strict_types=1);

namespace AlexNo\FieldLingoGii\AddLanguageColumn\Adapter;
/**
 * AbstractAdapter
 *
 * Base class for adapters that generate code files for adding language-specific columns.
 *
 * @license https://opensource.org/licenses/MIT MIT
 * @package AlexNo\FieldLingoGii\AddLanguageColumn
 * @author Oleksandr Nosov <alex@4n.com.ua>
 * @copyright 2025 Oleksandr Nosov
 */
use AlexNo\FieldLingoGii\AddLanguageColumn\ColumnHelperTrait;
use yii\db\TableSchema;

abstract class AbstractAdapter implements AdapterInterface
{
    /**
     * Include column helper methods.
     * @see ColumnHelperTrait
     */
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
     * Resolve which existing column to use as a template for the new column.
     * Determines the source column based on the positioning strategy.
     *
     * @param TableSchema $table Table schema
     * @param string $baseName Base name of the localized field
     * @param array $columns Existing language-specific columns
     * @return string|null Column name to use as template for the new column, or null if not found
     */
    protected function resolveSourceColumn(TableSchema $table, string $baseName, array $columns): ?string
    {
        $position = $this->options['position'] ?? 'after_all';

        return match ($position) {
            'before_all' => $columns[0] ?? null,
            'after_all' => $columns[array_key_last($columns)] ?? null,
            default => isset($table->columns["{$baseName}_{$position}"])
                ? "{$baseName}_{$position}"
                : ($columns[array_key_last($columns)] ?? null)
        };
    }
}
