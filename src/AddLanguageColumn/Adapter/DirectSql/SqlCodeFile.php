<?php
declare(strict_types=1);

namespace AlexNo\FieldLingoGii\AddLanguageColumn\Adapter\DirectSql;

use Yii;
use yii\gii\CodeFile;

/**
 * SqlCodeFile — wrapper for SQL "files" in Gii, used by DirectSqlAdapter.
 */
final class SqlCodeFile extends CodeFile
{
    public string $tableName;
    public string $columnName;
    public bool $skip;

    public function __construct(string $tableName, string $columnName, string $content, bool $skip = false)
    {
        // use runtime path so Gii can preview; actual execution uses content
        parent::__construct('@app/runtime/gii-sql/' . $tableName . '_' . $columnName . '.sql', $content);

        $this->tableName = $tableName;
        $this->columnName = $columnName;
        $this->skip = $skip;
        $this->operation = $skip ? parent::OP_SKIP : parent::OP_CREATE;
    }

    /**
     * Execute SQL directly against DB (used by Gii action "Save" if user chooses).
     */
    public function save(): bool
    {
        if ($this->skip) {
            return true;
        }

        try {
            Yii::$app->db->createCommand($this->content)->execute();
            return true;
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    public function preview(): string
    {
        $escapedSql = htmlspecialchars($this->content, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return <<<HTML
<pre><code class="sql hljs">{$escapedSql}</code></pre>
<script type="text/javascript">
if (typeof hljs !== 'undefined') {
    hljs.highlightAll();
}
</script>
HTML;
    }

    /**
     * Public getter for content (convenience).
     */
    public function getContent(): string
    {
        return $this->content;
    }

    public function getFileName(): string
    {
        return basename($this->path);
    }

    public function getRelativePath(): string
    {
        return "Table {$this->tableName} — Add column {$this->columnName}";
    }
}
