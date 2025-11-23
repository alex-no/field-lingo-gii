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
        $this->options = array_merge($this->options, $options);

        $sourceName = $this->resolveSourceColumn($table, $baseName, $columns);
        if ($sourceName === null || !isset($table->columns[$sourceName])) {
            return [];
        }

        $source = $table->columns[$sourceName];
        $newColumn = "{$baseName}_{$newLanguageSuffix}";

        // compute position clause like before
        $positionClause = '';
        $position = $this->options['position'] ?? 'after_all';

        $db = \Yii::$app->db;

        if ($position === 'before_all') {
            $allNames = array_keys($table->columns);
            $idx = array_search($columns[0], $allNames, true);
            if ($idx !== false && $idx > 0) {
                $positionClause = " AFTER " . $db->quoteColumnName($allNames[$idx - 1]);
            } else {
                $positionClause = ' FIRST';
            }
        } else {
            $sourceForPos = ($position === 'after_all') ? $columns[array_key_last($columns)] : "{$baseName}_{$position}";
            $positionClause = " AFTER " . $db->quoteColumnName($sourceForPos);
        }

        $sql = sprintf(
            'ALTER TABLE %s ADD COLUMN %s %s %s%s;',
            $db->quoteTableName($table->name),
            $db->quoteColumnName($newColumn),
            $source->dbType,
            $source->allowNull ? 'NULL' : 'NOT NULL',
            $positionClause
        );

        $skip = array_key_exists($newColumn, $table->columns);

        $file = new SqlCodeFile($table->name, $newColumn, $sql, $skip);

        return [$file];
    }
}
