<?php
declare(strict_types=1);

namespace AlexNo\FieldLingoGii\AddLanguageColumn\Adapter\DirectSql;
/**
 * SqlCodeFile — wrapper for SQL "files" in Gii, used by DirectSqlAdapter.
 *
 * @license https://opensource.org/licenses/MIT MIT
 * @package AlexNo\FieldLingoGii\AddLanguageColumn
 * @author Oleksandr Nosov <alex@4n.com.ua>
 * @copyright 2025 Oleksandr Nosov
 */
use Yii;
use yii\gii\CodeFile;

final class SqlCodeFile extends CodeFile
{
    /**
     * Table name for which the SQL is generated.
     * @var string
     */
    public string $tableName;
    /**
     * Column name to be added.
     * @var string
     */
    public string $columnName;
    /**
     * Whether to skip execution of this SQL.
     * @var bool
     */
    public bool $skip;

    /**
     * Constructor.
     *
     * @param string $tableName
     * @param string $columnName
     * @param string $content
     * @param bool $skip
     */
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
     * @return bool whether the execution was successful
     * @see CodeFile::save()
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

    /**
     * Returns a preview of the SQL code file with syntax highlighting.
     * @return string HTML content with syntax-highlighted SQL
     * @see https://highlightjs.org/usage/
     */
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
     * Returns the content of the code file.
     * @return string the content of the code file
     * @see CodeFile::content
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Returns the file name of the code file.
     * @return string the file name of the code file
     * @see CodeFile::path
     */
    public function getFileName(): string
    {
        return basename($this->path);
    }

    /**
     * Returns the relative path of the code file for display purposes.
     * @return string the relative path of the code file
     * @see CodeFile::getRelativePath()
     */
    public function getRelativePath(): string
    {
        return "Table {$this->tableName} — Add column {$this->columnName}";
    }
}
