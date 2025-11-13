<?php
declare(strict_types=1);

namespace AlexNo\FieldLingoGii\AddLanguageColumn;
/**
 * Class AddLanguageColumnGenerator
 *
 * Generator for adding language-specific columns (field_{lang}) to tables.
 * Delegates output to adapters (direct SQL / migration).
 *
 * @license https://opensource.org/licenses/MIT MIT
 * @package AlexNo\FieldLingoGii\AddLanguageColumn
 * @author Oleksandr Nosov <alex@4n.com.ua>
 * @copyright 2025 Oleksandr Nosov
 */
use Yii;
use yii\gii\Generator;
use yii\db\TableSchema;
use yii\helpers\ArrayHelper;
use AlexNo\FieldLingoGii\AddLanguageColumn\Adapter\AdapterInterface;
use AlexNo\FieldLingoGii\AddLanguageColumn\Adapter\DirectSql\DirectSqlAdapter;
use AlexNo\FieldLingoGii\AddLanguageColumn\Adapter\Migration\MigrationAdapter;

class AddLanguageColumnGenerator extends Generator
{
    /**
     * Form inputs
     */
    public string $newLanguageSuffix = '';
    public array $languages = [];
    public ?string $position = null;

    /**
     * Where to write generated migrations. Can be an alias (e.g. migrations) or absolute path.
     * If empty — defaults to 'migrations'.
     *
     * @var string
     */
    public string $migrationPath = 'migrations';

    /**
     * @var ApplyMode Selected apply mode (migration/direct sql)
     */
    public ?ApplyMode $applyMode = null;

    /**
     * Internal state
     */
    private array $_availableLanguages = [];
    public array $executedSql = [];
    public array $skippedFields = [];
    public array $generatedMigrations = [];

    /**
     * Initialize generator defaults.
     *
     * @return void
     */
    public function init(): void
    {
        parent::init();

        $this->loadAvailableLanguages();
        $this->initSelectedLanguages();

        if ($this->position === null) {
            $this->position = 'after_all';
        }

        $this->newLanguageSuffix = strtolower($this->newLanguageSuffix);

        // default apply mode — migration
        $this->applyMode ??= ApplyMode::MIGRATION;
    }

    /**
     * Load available languages from DB.
     *
     * @return void
     */
    private function loadAvailableLanguages(): void
    {
        try {
            $rows = Yii::$app->db
                ->createCommand('SELECT `code`, `full_name` FROM `language` WHERE `is_enabled` = 1 ORDER BY `order`')
                ->queryAll();
        } catch (\Throwable $e) {
            $rows = [];
        }

        $this->_availableLanguages = ArrayHelper::map(
            $rows,
            'code',
            static fn($row) => "{$row['code']} ({$row['full_name']})"
        );
    }

    /**
     * If languages aren't set yet (GET form), preselect all available.
     *
     * @return void
     */
    private function initSelectedLanguages(): void
    {
        if (empty($this->languages) && Yii::$app->request->isGet) {
            $this->languages = array_keys($this->getAvailableLanguages());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Add Language Column Generator';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription(): string
    {
        return 'Generates either SQL statements or migration classes to add missing language-localized columns.';
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            [['newLanguageSuffix', 'position'], 'required'],
            ['newLanguageSuffix', 'match', 'pattern' => '/^[a-z]{2}$/i', 'message' => 'Language suffix must be 2 letters.'],
            ['languages', 'each', 'rule' => ['string']],
            ['languages', 'validateLanguagesNotEmpty'],
            ['applyModeValue', 'required'],
            ['applyModeValue', 'in', 'range' => [ApplyMode::DIRECT_SQL->value, ApplyMode::MIGRATION->value]],
        ]);
    }

    /**
     * Validate that languages array is not empty.
     *
     * @param string $attribute
     * @param mixed $params
     * @return void
     */
    public function validateLanguagesNotEmpty($attribute, $params): void
    {
        if (empty($this->$attribute)) {
            $this->addError($attribute, 'Please select at least one base language.');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'newLanguageSuffix' => 'New Language Suffix (e.g., fr)',
            'languages' => 'Base Languages',
            'position' => 'Position to Insert',
            'applyModeValue' => 'How to add fields',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function hints(): array
    {
        return [
            'newLanguageSuffix' => 'Two-letter code for the new language (lowercase or uppercase).',
            'languages' => 'Select which existing languages to consider when finding fields.',
            'position' => 'Choose where to insert the new field among existing localized fields.',
            'applyModeValue' => 'Direct SQL executes immediately. Creating migration generates PHP migration files to run later (recommended).',
        ];
    }

    /**
     * Return available languages for the form.
     *
     * @return array
     */
    public function getAvailableLanguages(): array
    {
        return $this->_availableLanguages;
    }

    /**
     * Options for position drop-down.
     *
     * @return array
     */
    public function getPositionOptions(): array
    {
        $options = [
            'before_all' => 'Before all',
        ];
        foreach ($this->languages as $lang) {
            $options[$lang] = "After fields ending with _{$lang}";
        }
        $options['after_all'] = 'After all';

        return $options;
    }

    /**
     * Factory: create adapter instance according to selected apply mode.
     *
     * @return AdapterInterface
     */
    private function createAdapter(): AdapterInterface
    {
        $options = [
            'position' => $this->position,
            'migrationPath' => $this->migrationPath,
        ];

        return match ($this->applyMode) {
            ApplyMode::DIRECT_SQL => new DirectSqlAdapter($options),
            default => new MigrationAdapter($options),
        };
    }

    /**
     * {@inheritdoc}
     *
     * Delegates generation of CodeFile objects to adapters.
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

        $adapter = $this->createAdapter();

        foreach ($schemas as $table) {
            $localizedFields = $this->findLocalizedFields($table);

            foreach ($localizedFields as $baseName => $columns) {
                $newColumn = "{$baseName}_{$this->newLanguageSuffix}";

                // If exists — skip and record
                if (array_key_exists($newColumn, $table->columns)) {
                    $this->skippedFields[] = "{$table->name}.{$newColumn}";
                    continue;
                }

                // Adapter returns array of CodeFile instances (one or more)
                /** @var \yii\gii\CodeFile[] $generatedFiles */
                $generatedFiles = $adapter->generateFor($table, $baseName, $columns, $this->newLanguageSuffix, [
                    'position' => $this->position,
                ]);

                foreach ($generatedFiles as $file) {
                    $files[] = $file;

                    // bookkeeping
                    // For direct sql: record executed SQL (content) if file not skipped
                    if ($this->applyMode === ApplyMode::DIRECT_SQL && $file instanceof \yii\gii\CodeFile) {
                        // CodeFile exposes $content and $path publicly in Yii2
                        $content = $file->content ?? null;
                        if ($content !== null) {
                            $this->executedSql[] = $content;
                        }
                    }

                    // For migrations: record filename
                    if ($this->applyMode === ApplyMode::MIGRATION && $file instanceof \yii\gii\CodeFile) {
                        $path = $file->path ?? null;
                        if ($path !== null) {
                            $this->generatedMigrations[] = basename($path);
                        }
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
     * @return array<string, string[]> [ baseName => [col1, col2, ...] ]
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
                usort($langs, function (string $a, string $b) use ($tableSchema): int {
                    $keys = array_keys($tableSchema->columns);
                    return array_search($a, $keys, true) <=> array_search($b, $keys, true);
                });
                $validGroups[$base] = $langs;
            }
        }

        return $validGroups;
    }

    /**
     * Success message shown after generation in Gii UI.
     *
     * @return string
     */
    public function successMessage(): string
    {
        $skipped = count($this->skippedFields);

        if ($this->applyMode === ApplyMode::MIGRATION) {
            $migs = count($this->generatedMigrations);
            return "Generated {$migs} migration(s). Skipped {$skipped} fields (already existed).";
        }

        $applied = count($this->executedSql);
        return "Successfully prepared {$applied} SQL statement(s). Skipped {$skipped} fields.";
    }

    /**
     * Path to custom form view.
     *
     * @return string
     */
    public function formView(): string
    {
        return '@vendor/alex-no/field-lingo-gii/src/AddLanguageColumn/views/form.php';
    }

    /**
     * Returns the enum string value for the form
     */
    public function getApplyModeValue(): string
    {
        return $this->applyMode->value;
    }

    /**
     * Sets the enum to a string from the form
     */
    public function setApplyModeValue(string $value): void
    {
        $this->applyMode = ApplyMode::from($value);
    }
}
