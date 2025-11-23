<?php
declare(strict_types=1);

namespace AlexNo\FieldLingoGii\AddLanguageColumn\Adapter\DirectSql;
/**
 * DirectSqlAdapter
 *
 * Generates SQL CodeFile(s) that add new language-localized columns directly.
 * 
 * @license https://opensource.org/licenses/MIT MIT
 * @package AlexNo\FieldLingoGii\AddLanguageColumn
 * @author Oleksandr Nosov <alex@4n.com.ua>
 * @copyright 2025 Oleksandr Nosov
 */
use AlexNo\FieldLingoGii\AddLanguageColumn\Adapter\AbstractAdapter;
use yii\db\TableSchema;
use AlexNo\FieldLingoGii\AddLanguageColumn\Adapter\AdapterInterface;

final class DirectSqlAdapter extends AbstractAdapter
{
    /**
     * @inheritDoc
     */
    public function generateFor(TableSchema $table, string $baseName, array $columns, string $newLanguageSuffix, array $options = []): array
    {
        $this->options = [...$this->options, ...$options];

        $sourceName = $this->resolveSourceColumn($table, $baseName, $columns);
        if ($sourceName === null || !isset($table->columns[$sourceName])) {
            return [];
        }

        $source = $table->columns[$sourceName];
        $newColumn = "{$baseName}_{$newLanguageSuffix}";

        $db = \Yii::$app->db;
        $position = $this->options['position'] ?? 'after_all';

        // Compute position clause
        $positionClause = match ($position) {
            'before_all' => $this->buildBeforeAllClause($table, $columns, $db),
            default => ' AFTER ' . $db->quoteColumnName(
                $position === 'after_all'
                    ? $columns[array_key_last($columns)]
                    : "{$baseName}_{$position}"
            )
        };

        $sql = sprintf(
            'ALTER TABLE %s ADD COLUMN %s %s %s%s;',
            $db->quoteTableName($table->name),
            $db->quoteColumnName($newColumn),
            $source->dbType,
            $source->allowNull ? 'NULL' : 'NOT NULL',
            $positionClause
        );

        $skip = array_key_exists($newColumn, $table->columns);

        return [new SqlCodeFile($table->name, $newColumn, $sql, $skip)];
    }

    /**
     * Build position clause for 'before_all' positioning
     *
     * @param TableSchema $table Table schema
     * @param array $columns Existing language-specific columns
     * @param \yii\db\Connection $db Database connection for quoting
     * @return string Position clause: either " AFTER column_name" or " FIRST"
     */
    private function buildBeforeAllClause(TableSchema $table, array $columns, $db): string
    {
        $allNames = array_keys($table->columns);
        $idx = array_search($columns[0], $allNames, true);

        return ($idx !== false && $idx > 0)
            ? ' AFTER ' . $db->quoteColumnName($allNames[$idx - 1])
            : ' FIRST';
    }
}
