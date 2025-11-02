<?php
namespace AlexNo\FieldLingoGii\AddLanguageColumn;
/**
 * Class AddLanguageColumnGenerator
 * Add Language Column Generator for Field-Lingo (Yii2 / Gii)
 *
 * This generator creates SQL statements to add new language-localized columns
 * based on existing localized fields in the database tables.
 *
 * @license MIT
 * @package AlexNo\FieldLingoGii\ExtendedModel
 * @author Oleksandr Nosov <alex@4n.com.ua>
 * @copyright 2025 Oleksandr Nosov
 */
use Yii;
use yii\gii\Generator;
use yii\db\TableSchema;
use yii\helpers\ArrayHelper;
use yii\web\View;

class AddLanguageColumnGenerator extends Generator
{
    // Constants for insert position
    public const POSITION_BEFORE_ALL = 'before_all';
    public const POSITION_AFTER_ALL  = 'after_all';

    // Form inputs
    public string $newLanguageSuffix = '';
    public array $languages = [];
    public ?string $position = null; // Position to insert the new field (before/after existing localized fields)

    // Internal state
    private array $_availableLanguages = []; // ALL available languages (retrieved from the database)
    public array $executedSql = [];
    public array $skippedFields = [];

    public function init(): void
    {
        parent::init();
        $this->loadAvailableLanguages();
        $this->initSelectedLanguages();

        // Ensure that "after_all" is selected by default if position is empty
        if ($this->position === null) {
            $this->position = self::POSITION_AFTER_ALL;
        }

        $this->newLanguageSuffix = strtolower($this->newLanguageSuffix);
    }

    // Populate available languages (from the database)
    private function loadAvailableLanguages(): void
    {
        $this->_availableLanguages = ArrayHelper::map(
            Yii::$app->db->createCommand('SELECT `code`, `full_name` FROM `language` WHERE `is_enabled` = 1 ORDER BY `order`')->queryAll(),
            'code',
            fn($row) => "{$row['code']} ({$row['full_name']})"
        );
    }

    // If the list of languages is not yet filled (e.g., when the form is opened for the first time) — set all available
    private function initSelectedLanguages(): void
    {
        if (empty($this->languages) && Yii::$app->request->isGet) {
            $this->languages = array_keys($this->getAvailableLanguages());
        }
    }

    public function getName(): string
    {
        return 'Add Language Column Generator';
    }

    public function getDescription(): string
    {
        return 'Generates SQL statements to add missing language-localized columns.';
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            [['newLanguageSuffix', 'position'], 'required'],
            ['newLanguageSuffix', 'match', 'pattern' => '/^[a-z]{2}$/i', 'message' => 'Language suffix must be 2 letters.'],
            ['languages', 'each', 'rule' => ['string']],
            ['languages', 'validateLanguagesNotEmpty'],
        ]);
    }

    public function validateLanguagesNotEmpty($attribute, $params): void
    {
        if (empty($this->$attribute)) {
            $this->addError($attribute, 'Please select at least one base language.');
        }
    }

    public function attributeLabels(): array
    {
        return [
            'newLanguageSuffix' => 'New Language Suffix (e.g., fr)',
            'languages' => 'Base Languages',
            'position' => 'Position to Insert',
        ];
    }

    public function hints(): array
    {
        return [
            'newLanguageSuffix' => 'Two-letter code for the new language (lowercase or uppercase).',
            'languages' => 'Select which existing languages to consider when finding fields.',
            'position' => 'Choose where to insert the new field among existing localized fields.',
        ];
    }

    public function getAvailableLanguages(): array
    {
        return $this->_availableLanguages;
    }

    public function getPositionOptions(): array
    {
        $options = [
            self::POSITION_BEFORE_ALL => 'Before all',
        ];
        foreach ($this->languages as $lang) {
            $options[$lang] = "After fields ending with _{$lang}";
        }
        $options[self::POSITION_AFTER_ALL] = 'After all';

        return $options;
    }

    /**
     * Generate "files" (SqlCodeFile instances) — Gii will show them as results.
     *
     * @return \yii\gii\CodeFile[]
     */
    public function generate(): array
    {
        if (empty($this->languages)) {
            return [];
        }

        $db = Yii::$app->db;
        $schemas = ArrayHelper::index($db->schema->getTableSchemas(), 'name');
        $files = [];

        foreach ($schemas as $table) {
            $localizedFields = $this->findLocalizedFields($table);

            foreach ($localizedFields as $baseName => $columns) {
                $sql = $this->generateAlterTableSql($table, $baseName, $columns);
                if ($sql !== null) {
                    $columnName = $baseName . '_' . $this->newLanguageSuffix;
                    $skip = array_key_exists($columnName, $table->columns);

                    $files[] = new SqlCodeFile(
                        $table->name,
                        $columnName,
                        $sql,
                        $skip
                    );

                    if ($skip) {
                        $this->skippedFields[] = "{$table->name}.{$columnName}";
                    } else {
                        $this->executedSql[] = $sql;
                    }
                }
            }
        }

        return $files;
    }

    /**
     * Find groups of localized fields that match selected base languages.
     *
     * @param TableSchema $tableSchema
     * @return array [ baseName => [col1, col2, ...] ]
     */
    public function findLocalizedFields(TableSchema $tableSchema): array
    {
        $candidates = [];

        foreach ($tableSchema->columns as $name => $column) {
            $pos = strrpos($name, '_');
            if ($pos === false) {
                continue;
            }

            $base = substr($name, 0, $pos);
            $suffix = substr($name, $pos + 1);

            if (in_array($suffix, $this->languages, true)) {
                $candidates[$base][] = $name;
            }
        }

        $validGroups = [];
        $languageCount = count($this->languages);
        foreach ($candidates as $base => $langs) {
            $langs = array_unique($langs);
            if (count($langs) === $languageCount) {
                // sort columns by appearance order in table schema (preserve original order)
                usort($langs, function ($a, $b) use ($tableSchema) {
                    $keys = array_keys($tableSchema->columns);
                    return array_search($a, $keys, true) <=> array_search($b, $keys, true);
                });
                $validGroups[$base] = $langs;
            }
        }

        return $validGroups;
    }

    /**
     * Build ALTER TABLE SQL statement based on source column.
     *
     * @param TableSchema $table
     * @param string $baseName
     * @param array $columns
     * @return string|null
     */
    private function generateAlterTableSql(TableSchema $table, $baseName, $columns): ?string
    {
        if (empty($columns)) {
            return null;
        }

        $positionClause = '';

        if ($this->position === self::POSITION_BEFORE_ALL) {
            $allNames = array_keys($table->columns);
            $idx = array_search($columns[0], $allNames, true);
            $sourceName = $allNames[$idx];
            if ($idx > 0) {
                $positionClause = " AFTER `{$allNames[$idx - 1]}`";
            } else {
                $positionClause = ' FIRST';
            }
        } else {
            $sourceName = $this->position === self::POSITION_AFTER_ALL ? $columns[array_key_last($columns)] : "{$baseName}_{$this->position}";
            $positionClause = " AFTER `{$sourceName}`";
        }
        if (!isset($table->columns[$sourceName])) {
            return null;
        }

        $source = $table->columns[$sourceName];

        return sprintf(
            'ALTER TABLE `%s` ADD COLUMN `%s` %s %s%s;',
            $table->name,
            "{$baseName}_{$this->newLanguageSuffix}",
            $source->dbType,
            $source->allowNull ? 'NULL' : 'NOT NULL',
            $positionClause
        );
    }

    public function successMessage(): string
    {
        $applied = count($this->executedSql);
        $skipped = count($this->skippedFields);

        return "Successfully applied {$applied} changes. Skipped {$skipped} fields.";
    }

    /**
     * Return the path to custom form view shipped with the package.
     *
     * @return string
     */
    public function formView(): string
    {
        return '@vendor/alex-no/field-lingo-gii/src/AddLanguageColumn/views/form.php';
    }
}
