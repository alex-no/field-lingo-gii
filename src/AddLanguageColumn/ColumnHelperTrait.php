<?php
declare(strict_types=1);

namespace AlexNo\FieldLingoGii\AddLanguageColumn;
/**
 * ColumnHelperTrait
 * Provides helper methods for column type normalization.
 * Small utility trait for working with column dbType -> migration expression.
 * The implementation is intentionally conservative; extend later if needed.
 *
 * @license https://opensource.org/licenses/MIT MIT
 * @package AlexNo\FieldLingoGii\AddLanguageColumn
 * @author Oleksandr Nosov <alex@4n.com.ua>
 * @copyright 2025 Oleksandr Nosov
 */
trait ColumnHelperTrait
{
    /**
     * Try to convert DB type string (e.g. "varchar(255)") to a constructive migration
     * expression like "string(255)". This helper does not return final PHP code
     * for `$this->...` but a short descriptor (string) which can be used in migration
     * comments / for simpler generation.
     *
     * To make fully programmatic migrations (e.g. `$this->string(255)->notNull()`),
     * implement a more sophisticated parser.
     *
     * @param string|null $dbType
     * @return string
     */
    protected function normalizeDbTypeForMigration(?string $dbType): string
    {
        if ($dbType === null) {
            return 'string';
        }

        $dbType = trim($dbType);

        if (preg_match('/^varchar\((\d+)\)$/i', $dbType, $m)) {
            return "string({$m[1]})";
        }

        if (preg_match('/^char\((\d+)\)$/i', $dbType, $m)) {
            return "char({$m[1]})";
        }

        if (preg_match('/^int(?:eger)?(?:\(\d+\))?$/i', $dbType)) {
            return 'integer';
        }

        if (stripos($dbType, 'tinyint(1)') !== false) {
            return 'boolean';
        }

        if (stripos($dbType, 'text') !== false) {
            return 'text';
        }

        if (stripos($dbType, 'datetime') !== false) {
            return 'dateTime';
        }

        if (stripos($dbType, 'timestamp') !== false) {
            return 'timestamp';
        }

        // fallback: return raw dbType so it can be embedded into SQL if necessary
        return $dbType;
    }
}
