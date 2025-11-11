<?php
declare(strict_types=1);

namespace AlexNo\FieldLingoGii\AddLanguageColumn\Adapter\DirectSql;

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

        if ($position === 'before_all') {
            $allNames = array_keys($table->columns);
            $idx = array_search($columns[0], $allNames, true);
            if ($idx !== false && $idx > 0) {
                $positionClause = " AFTER `{$allNames[$idx - 1]}`";
            } else {
                $positionClause = ' FIRST';
            }
        } else {
            $sourceForPos = ($position === 'after_all') ? $columns[array_key_last($columns)] : "{$baseName}_{$position}";
            $positionClause = " AFTER `{$sourceForPos}`";
        }

        $sql = sprintf(
            'ALTER TABLE `%s` ADD COLUMN `%s` %s %s%s;',
            $table->name,
            $newColumn,
            $source->dbType,
            $source->allowNull ? 'NULL' : 'NOT NULL',
            $positionClause
        );

        $skip = array_key_exists($newColumn, $table->columns);

        $file = new SqlCodeFile($table->name, $newColumn, $sql, $skip);

        return [$file];
    }
}
