<?php
declare(strict_types=1);

namespace AlexNo\FieldLingoGii\AddLanguageColumn\Adapter\Migration;
/**
 * MigrationAdapter
 * Generates migration CodeFile(s) that add new language-localized columns.
 *
 * @license https://opensource.org/licenses/MIT MIT
 * @package AlexNo\FieldLingoGii\AddLanguageColumn
 * @author Oleksandr Nosov <alex@4n.com.ua>
 * @copyright 2025 Oleksandr Nosov
 */
use Yii;
use AlexNo\FieldLingoGii\AddLanguageColumn\Adapter\AbstractAdapter;
use yii\db\TableSchema;
use AlexNo\FieldLingoGii\AddLanguageColumn\Adapter\AdapterInterface;

/**
 * MigrationAdapter
 *
 * Generates migration CodeFile(s) that add new language-localized columns.
 */
final class MigrationAdapter extends AbstractAdapter implements AdapterInterface
{
    /**
     * Get or create deterministic a session-based timestamp for current generation session.
     * Stores timestamp in session to ensure same timestamp between Preview and Generate requests.
     * Session key includes table and column to allow parallel generations.
     *
     * @param string $table
     * @param string $column
     * @return int
     * @see https://www.yiiframework.com/doc/api/2.0/yii-web-session
     * @see https://www.php.net/manual/en/function.time.php
     */
    private function getSessionTimestamp(string $table, string $column): int
    {
        $sessionKey = 'migration_ts_' . md5($table . $column);
        $session = Yii::$app->session;

        if (!$session->has($sessionKey)) {
            $session->set($sessionKey, time());
        }

        return (int)$session->get($sessionKey);
    }

    /**
     * @inheritDoc
     */
    public function generateFor(TableSchema $table, string $baseName, array $columns, string $newLanguageSuffix, array $options = []): array
    {
        $this->options = array_merge($this->options, $options);

        $newColumn = "{$baseName}_{$newLanguageSuffix}";
        $sourceName = $this->resolveSourceColumn($table, $baseName, $columns);
        if ($sourceName === null || !isset($table->columns[$sourceName])) {
            return [];
        }

        // Skip if column already exists (generator will record skipped)
        if (array_key_exists($newColumn, $table->columns)) {
            return [];
        }

        $source = $table->columns[$sourceName];

        // Use session-based timestamp to ensure same timestamp for Preview and Generate
        $ts = $this->getSessionTimestamp($table->name, $newColumn);
        $className = $this->buildMigrationClassName($table->name, $newColumn, $ts);
        $fileName = "m" . date('ymd_His', $ts) . "_add_{$newColumn}_to_{$table->name}.php";

        $migrationContent = $this->buildMigrationContent($table, $baseName, $columns, $newColumn, $className);

        $migrationsPath = $this->options['migrationPath'] ?? 'migrations';
        $file = new MigrationCodeFile($fileName, $migrationContent, $className, $migrationsPath);

        return [$file];
    }

    /**
     * Build migration class name based on table, column and timestamp.
     * @param string $tableName
     * @param string $columnName
     * @param int $ts
     * @return string
     * @see https://www.php.net/manual/en/function.preg-replace.php
     */
    private function buildMigrationClassName(string $tableName, string $columnName, int $ts): string
    {
        $safeTable = preg_replace('/[^A-Za-z0-9_]/', '_', $tableName);
        $safeColumn = preg_replace('/[^A-Za-z0-9_]/', '_', $columnName);
        return 'm' . date('ymd_His', $ts) . "_add_{$safeColumn}_to_{$safeTable}";
    }

    /**
     * Build migration class content. We generate conservative code: addColumn with generic `$this->string()`
     * and a commented example of how to run ALTER TABLE for MySQL position if needed.
     * @param TableSchema $table
     * @param string $baseName
     * @param array $columns
     * @param string $newColumnName
     * @param string $className
     * @return string
     * @see https://www.php.net/manual/en/language.types.string.php#language.types.string.syntax.heredoc
     * @see https://www.php.net/manual/en/function.sprintf.php
     * @see https://www.yiiframework.com/doc/api/2.0/yii-db-migration
     */
    private function buildMigrationContent(TableSchema $table, string $baseName, array $columns, string $newColumnName, string $className): string
    {
        $sourceName = $this->resolveSourceColumn($table, $baseName, $columns) ?? $columns[0];
        $source = $table->columns[$sourceName];

        $dbTypeHint = $this->normalizeDbTypeForMigration($source->dbType ?? null);
        $tableName = $table->name;

        $position = $this->options['position'] ?? 'after_all';

        // MySQL: execute precise ADD COLUMN with positioning
        if (Yii::$app->db->driverName === 'mysql') {
            $positionSql = $this->buildAddColumnPositionedSql(
                $table,
                $baseName,
                $newColumnName,
                $columns,
                $position,
                $source->dbType ?? 'VARCHAR(255)',
                $source->allowNull ?? true
            );

            $safeUp = "\$this->execute(\"{$positionSql}\");";
        } else {
            // fallback for other DBs
            $safeUp = "\$this->addColumn('{$tableName}', '{$newColumnName}', \$this->string());";
        }

        // produce migration class content (conservative, user can edit for exact types/positions)
        $class = <<<PHP
<?php
declare(strict_types=1);

use yii\db\Migration;

/**
 * Class {$className}
 * Auto-generated by Field-Lingo Gii: add column {$newColumnName} to table {$tableName}
 */
final class {$className} extends Migration
{
    public function safeUp(): void
    {
        // Column hint (derived from existing column): {$dbTypeHint}
        // Adjust the expression below if you want an exact migration expression like \$this->string(255)->notNull()
        {$safeUp}
    }

    public function safeDown(): void
    {
        if (\$this->db->getSchema()->getTableSchema('{$tableName}', true)->getColumn('{$newColumnName}') !== null) {
            \$this->dropColumn('{$tableName}', '{$newColumnName}');
        }
    }
}
PHP;

        return $class;
    }

    /**
     * Build MySQL ADD COLUMN SQL with proper positioning based on options.
     *
     * @param TableSchema $table
     * @param string $newColumnName
     * @param array $columns
     * @param string $position 'before_all', 'after_all', or column name
     * @param string $dbType
     * @param bool $allowNull
     * @return string
     */
    private function buildAddColumnPositionedSql(
        TableSchema $table,
        string $baseName,
        string $newColumnName,
        array $columns,
        string $position,
        string $dbType = 'VARCHAR(255)',
        bool $allowNull = true
    ): string {
        $tableName = $table->name;

        if ($position === 'before_all') {
            return "ALTER TABLE `{$tableName}` ADD COLUMN `{$newColumnName}` {$dbType} "
                 . ($allowNull ? 'NULL' : 'NOT NULL') . " FIRST;";
        }

        $after = $position === 'after_all' ? $columns[array_key_last($columns)] : $position;
        return "ALTER TABLE `{$tableName}` ADD COLUMN `{$newColumnName}` {$dbType} "
             . ($allowNull ? 'NULL' : 'NOT NULL') . " AFTER `{$baseName}_{$after}`;";
    }

}
