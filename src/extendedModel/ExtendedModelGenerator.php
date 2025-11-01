<?php
/**
 * [Brief description of the file or class/module]
 *
 * @author [Oleksandr Nosov]
 * @copyright [2025] [Oleksandr Nosov]
 * @license [License Type, GPL]
 * @version [Version Number, 1.0.0]
 *
 * [Advanced Model Generator]
 */
namespace app\gii\extendedModel;

use Yii;
use yii\gii\generators\model\Generator;
use yii\gii\CodeFile;

class ExtendedModelGenerator extends Generator
{
    public $generateChildClass = true;

    public $baseClassOptions = [
        'yii\db\ActiveRecord',
        'app\components\i18n\AdvActiveRecord',
    ];

    public $queryBaseClassOptions = [
        'yii\db\ActiveQuery',
        'app\components\i18n\AdvActiveQuery',
    ];

    public function init()
    {
        parent::init();

        $config = Yii::$app->getModule('gii')->generators['model'] ?? [];

        if (!empty($config['baseClassOptions']) && is_array($config['baseClassOptions'])) {
            $this->baseClassOptions = array_unique(array_merge($this->baseClassOptions, $config['baseClassOptions']));
        }

        if (!empty($config['queryBaseClassOptions']) && is_array($config['queryBaseClassOptions'])) {
            $this->queryBaseClassOptions = array_unique(array_merge($this->queryBaseClassOptions, $config['queryBaseClassOptions']));
        }
    }

    public function getName()
    {
        return 'Advanced Model Generator';
    }

    public function getDescription()
    {
        return 'Generates a pair of classes - parent and child. The parent class will contain standard model-procedures, while the child class will include your own methods and properties.';
    }

    public function rules()
    {
        return array_merge(parent::rules(), [
            ['generateChildClass', 'boolean'],
        ]);
    }

    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'generateChildClass' => 'Generate child model',
        ]);
    }

    public function hints()
    {
        return array_merge(parent::hints(), [
            'generateChildClass' => 'If checked, an empty child class will be created for your logic.',
        ]);
    }

    public function stickyAttributes()
    {
        return array_merge(parent::stickyAttributes(), ['generateChildClass']);
    }

    public function generateRules($table)
    {
        $rules = parent::generateRules($table);

        $emailFields = [];

        foreach ($table->columns as $column) {
            $name = strtolower($column->name);

            // Searching for "mail" or "email", but excluding those containing "mail_"
            if ((str_contains($name, 'mail') || str_contains($name, 'email')) && !str_contains($name, 'mail_')) {
                $emailFields[] = $column->name;
            }
        }

        if (!empty($emailFields)) {
            $rules[] = "[['" . implode("', '", $emailFields) . "'], 'email']";
        }

        return $rules;
    }

    //  Generate the model files
    public function generate()
    {
        $files = parent::generate();
        $modelClass = $this->getModelClass();
        $baseFileName = $modelClass . '.php';

        foreach ($files as $i => $file) {
            if (str_ends_with($file->path, $baseFileName)) {
                $files[$i]->path = dirname($file->path) . '/base/' . $modelClass . '.php';
                if (file_exists($files[$i]->path) && $files[$i]->operation === CodeFile::OP_CREATE) {
                    $files[$i]->operation = CodeFile::OP_OVERWRITE;
                }
                break;
            }
        }

        if ($this->generateChildClass) {
            $childPath = Yii::getAlias('@' . str_replace('\\', '/', $this->ns)) . '/' . $modelClass . '.php';
            if (!file_exists($childPath)) {
                $files[] = new CodeFile(
                    $childPath,
                    $this->render('model-child.php', [
                        'className' => $modelClass,
                    ])
                );
            }
        }

        return $files;
    }
    public function getModelClass()
    {
        // Check if the model class is set
        if (empty($this->modelClass)) {
            throw new \RuntimeException('modelClass is not set.');
        }
        return basename(str_replace('\\', '/', $this->modelClass));
    }

    public function formView()
    {
        return '@gii/extendedModel/views/form.php';
    }

    public function generateLabels($table)
    {
        $labels = parent::generateLabels($table);
        foreach ($labels as &$label) {
            $label = $this->enableI18N ? 'Yii::t(\'app\', \'' . $label . '\')' : '\'' . $label . '\'';
        }
        return $labels;
    }
    // Set the default base class and query base class
    public function load($data, $formName = null)
    {
        $loaded = parent::load($data, $formName);

        if ($loaded && isset($this->modelClass) && $this->modelClass !== '') {
            $path = Yii::getAlias('@app/models/base/' . str_replace('\\', '/', $this->modelClass)) . '.php';
            if (is_file($path)) {
                $content = file_get_contents($path);

                // Find baseClass by "extends"
                if (preg_match('/class\s+\w+\s+extends\s+([\\\\\w]+)/', $content, $matches)) {
                    $this->baseClass = $matches[1];
                }

                // Find by queryClass
                if ($this->queryClass !== '') {
                    $queryPath = Yii::getAlias('@app/models/base/' . str_replace('\\', '/', $this->queryClass)) . '.php';
                    if (is_file($queryPath)) {
                        $queryContent = file_get_contents($queryPath);
                        if (preg_match('/public\s+static\s+function\s+find\s*\(\)\s*:\s*([\\\\\w]+)/', $queryContent, $matches)) {
                            $this->queryBaseClass = $matches[1];
                        }
                    }
                }
            }
        }

        return $loaded;
    }
}
