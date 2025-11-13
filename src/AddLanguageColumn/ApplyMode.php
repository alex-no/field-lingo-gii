<?php
declare(strict_types=1);

namespace AlexNo\FieldLingoGii\AddLanguageColumn;
/**
 * ApplyMode
 *
 * Defines modes for applying new language columns.
 * - DIRECT_SQL: Execute SQL directly on the database.
 * - MIGRATION: Generate a migration file to be applied later.
 *
 * @license https://opensource.org/licenses/MIT MIT
 * @package AlexNo\FieldLingoGii\AddLanguageColumn
 * @author Oleksandr Nosov <alex@4n.com.ua>
 * @copyright 2025 Oleksandr Nosov
 */
enum ApplyMode: string
{
    case DIRECT_SQL = 'direct_sql';
    case MIGRATION   = 'migration';

    /**
     * Get human-readable label for the apply mode.
     * @return string
     */
    public function label(): string
    {
        return match ($this) {
            self::MIGRATION => 'Create migration (recommended)',
            self::DIRECT_SQL => 'Direct SQL (execute now)',
        };
    }

    /**
     * Create ApplyMode from string value.
     * @param string $value
     * @return self
     * @psalm-return self
     * @throws \ValueError
     */
    public static function fromValue(string $value): self
    {
        return match ($value) {
            self::DIRECT_SQL->value => self::DIRECT_SQL,
            default => self::MIGRATION,
        };
    }
}
