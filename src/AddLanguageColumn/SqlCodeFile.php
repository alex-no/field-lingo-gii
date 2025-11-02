<?php
namespace AlexNo\FieldLingoGii\AddLanguageColumn;
/**
 * Class SqlCodeFile
 * SQL Code File for Add Language Column Generator (Yii2 / Gii)
 * This class represents a SQL code file to add a new language-localized column
 * based on existing localized fields in the database tables.
 *
 * SqlCodeFile — wrapper for SQL "files" in Gii.
 * It stores table/column metadata and can execute the SQL directly (save()).
 *
 * @license MIT
 * @package AlexNo\FieldLingoGii\AddLanguageColumn
 * @author Oleksandr Nosov <alex@4n.com.ua>
 * @copyright 2025 Oleksandr Nosov
 */
use Yii;
use yii\gii\CodeFile;

class SqlCodeFile extends CodeFile
{
    public string $tableName;
    public string $columnName;
    public bool $skip;

    public function __construct(
        string $tableName,
        string $columnName,
        string $content, // ready SQL
        bool $skip = false
    ) {
        // Call the parent constructor — a fake "path" is needed for compatibility with Gii
        parent::__construct(
            '@db/' . $tableName . '_' . $columnName . '.sql',
            $content
        );

        $this->tableName = $tableName;
        $this->columnName = $columnName;
        $this->skip = $skip;

        $this->operation = $skip ? parent::OP_SKIP : parent::OP_CREATE;
    }

    /**
     * Execute SQL directly against DB (used by Gii action).
     *
     * @return bool
     */
    public function save(): bool
    {
        if ($this->skip) {
            return true;
        }

        try {
            Yii::$app->db->createCommand($this->content)->execute();
            return true;
        } catch (\Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Return HTML preview (with highlight.js class)
     *
     * @return string
     */
    public function preview(): string
    {
        $escapedSql = htmlspecialchars($this->content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return <<<HTML
<pre><code class="sql hljs">{$escapedSql}</code></pre>
<script type="text/javascript">
if (typeof hljs !== 'undefined') {
    hljs.highlightAll();
} else {
    console.warn('highlight.js not loaded');
}
</script>
HTML;
    }

    /**
     * Return relative path description for Gii UI.
     * @return string
     */
    public function getRelativePath()
    {
        return "Table {$this->tableName} — Add column {$this->columnName}";
    }
}
