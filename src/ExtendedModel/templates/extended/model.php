<?php

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\model\Generator */
/* @var $tableName string */
/* @var $className string */
/* @var $queryClassName string */
/* @var $tableSchema yii\db\TableSchema */
/* @var $properties array */
/* @var $labels string[] */
/* @var $rules string[] */
/* @var $relations array */

$baseClassArr = explode('\\', $generator->baseClass);
$queryBaseClassArr = explode('\\', $generator->queryBaseClass);
$isLingoActiveQuery = end($queryBaseClassArr) === 'LingoActiveQuery';

echo "<?php\n";
?>

namespace <?= $generator->ns ?>\base;

use Yii;
use <?= $generator->baseClass ?>;
<?= $isLingoActiveQuery ? 'use ' . $generator->queryBaseClass . ';' : '' ?>


/**
 * This is the base model class for table "<?= $tableName ?>".
 *
<?php foreach ($properties as $property): ?>
 * @property <?= $property['type'] . ' $' . $property['name'] . "\n" ?>
<?php endforeach; ?>
 */
class <?= $className ?> extends <?= end($baseClassArr) . "\n" ?>
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%<?= $tableName ?>}}';
    }
<?php if ($isLingoActiveQuery): ?>

    /**
     * {@inheritdoc}
     */
    public static function find(): <?= end($queryBaseClassArr) . "\n" ?>
    {
        return new <?= end($queryBaseClassArr) ?>(static::class);
    }
<?php endif; ?>

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
<?= empty($rules) ? '' : '            ' . implode(",\n            ", $rules) . ",\n" ?>
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
<?= empty($labels) ? '' : '            ' . implode(",\n            ", array_map(
    fn($name, $label) => "'$name' => " . $label,
    array_keys($labels),
    $labels
)) . ",\n" ?>
        ];
    }

<?php if (!empty($relations)): ?>

    // Relations
<?php foreach ($relations as $name => $relation): ?>
    /**
     * Gets query for [[<?= $name ?>]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function get<?= $name ?>()
    {
        <?= $relation[0] ?>

    }
<?php endforeach; ?>

<?php endif; ?>
}
