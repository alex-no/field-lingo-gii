<?php

namespace app\gii\addLanguageColumn;

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

    public function preview()
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

    public function getRelativePath()
    {
        return "Table {$this->tableName} — Add column {$this->columnName}";
    }
}
