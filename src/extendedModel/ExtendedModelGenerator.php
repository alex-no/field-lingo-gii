<?php
namespace AlexNo\FieldLingoGii\ExtendedModel;
/**
 * Extended Model Generator for Field-Lingo (Yii2 / Gii)
 * This generator creates a pair of classes for each model:
 *  - A base class containing the generated code
 *  - A child class for custom logic
 * It also adds email validation rules for fields containing "mail" or "email".
 * @license MIT
 * @package AlexNo\FieldLingoGii\ExtendedModel
 * @author Oleksandr Nosov <alex@4n.com.ua>
 * @copyright 2025 Oleksandr Nosov
 */
use Yii;
use yii\gii\generators\model\Generator;
use yii\gii\CodeFile;

class ExtendedModelGenerator extends Generator
{
    public $generateChildClass = true;

    public $baseClassOptions = [
        'yii\db\ActiveRecord',
        'AlexNo\FieldLingo\Adapters\Yii2\LingoActiveRecord',
    ];

    public $queryBaseClassOptions = [
        'yii\db\ActiveQuery',
        'AlexNo\FieldLingo\Adapters\Yii2\LingoActiveQuery',
    ];

    public function init()
    {
        parent::init();

        // merge options from Gii config if provided (legacy behavior: merge from 'model' generator)
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
        return 'Field-Lingo Extended Model Generator';
    }

    public function getDescription()
    {
        return 'Generates a pair of classes - parent (base) and child. Parent contains generated code; child is for your custom logic.';
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

    /**
     * Generate the model files (override to put main class into base/ and create child class)
     *
     * @return CodeFile[]
     */
    public function generate()
    {
        $files = parent::generate();
        $modelClass = $this->getModelClass();
        $baseFileName = $modelClass . '.php';

        // move the generated model file into base/ directory
        foreach ($files as $i => $file) {
            if (str_ends_with($file->path, $baseFileName)) {
                $files[$i]->path = dirname($file->path) . '/base/' . $modelClass . '.php';
                if (file_exists($files[$i]->path) && $files[$i]->operation === CodeFile::OP_CREATE) {
                    $files[$i]->operation = CodeFile::OP_OVERWRITE;
                }
                break;
            }
        }

        // generate child class if requested
        if ($this->generateChildClass) {
            // $this->ns is provided by user in the generator form (e.g. app\models)
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

    /**
     * @return string
     * @throws \RuntimeException
     */
    public function getModelClass()
    {
        if (empty($this->modelClass)) {
            throw new \RuntimeException('modelClass is not set.');
        }
        return basename(str_replace('\\', '/', $this->modelClass));
    }

    /**
     * Return the path to custom form view shipped with the package.
     *
     * @return string
     */
    public function formView()
    {
        // path to the form view inside this package
        return '@vendor/alex-no/field-lingo-gii/src/extendedModel/views/form.php';
    }

    public function generateLabels($table)
    {
        $labels = parent::generateLabels($table);
        foreach ($labels as &$label) {
            $label = $this->enableI18N ? 'Yii::t(\'app\', \'' . $label . '\')' : '\'' . $label . '\'';
        }
        return $labels;
    }

    /**
     * Load posted data and attempt to autodetect base class / query base class from existing base file.
     *
     * @param array $data
     * @param null|string $formName
     * @return bool
     */
    public function load($data, $formName = null)
    {
        $loaded = parent::load($data, $formName);

        if ($loaded && isset($this->modelClass) && $this->modelClass !== '') {
            // look for existing base class file in app/models/base/<Model>.php
            $path = Yii::getAlias('@app/models/base/' . str_replace('\\', '/', $this->modelClass)) . '.php';
            if (is_file($path)) {
                $content = file_get_contents($path);

                // Find baseClass by "extends"
                if (preg_match('/class\s+\w+\s+extends\s+([\\\\\w]+)/', $content, $matches)) {
                    $this->baseClass = $matches[1];
                }

                // If queryClass is set, try to detect queryBaseClass
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
