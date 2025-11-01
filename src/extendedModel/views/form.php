<?php

use yii\gii\generators\model\Generator;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var yii\widgets\ActiveForm $form */
/** @var yii\gii\generators\model\Generator $generator */

echo $form->field($generator, 'db');
echo $form->field($generator, 'useTablePrefix')->checkbox();
echo $form->field($generator, 'useSchemaName')->checkbox();
echo $form->field($generator, 'tableName')->textInput([
    'autocomplete' => 'off',
    'data' => [
        'table-prefix' => $generator->getTablePrefix(),
        'action' => Url::to(['default/action', 'id' => 'model', 'name' => 'GenerateClassName'])
    ]
]);
echo $form->field($generator, 'standardizeCapitals')->checkbox();
echo $form->field($generator, 'singularize')->checkbox();
echo $form->field($generator, 'modelClass');
echo $form->field($generator, 'ns');
//echo $form->field($generator, 'baseClass');
echo $form->field($generator, 'baseClass')->textInput([
    'list' => 'base-class-options',
    'autocomplete' => 'off',
]);
echo Html::tag('datalist', implode("\n", array_map(fn($c) => Html::tag('option', '', ['value' => $c]), $generator->baseClassOptions)), [
    'id' => 'base-class-options',
]);
echo $form->field($generator, 'generateRelations')->dropDownList([
    Generator::RELATIONS_NONE => 'No relations',
    Generator::RELATIONS_ALL => 'All relations',
    Generator::RELATIONS_ALL_INVERSE => 'All relations with inverse',
]);
echo $form->field($generator, 'generateJunctionRelationMode')->dropDownList([
    Generator::JUNCTION_RELATION_VIA_TABLE => 'Via Table',
    Generator::JUNCTION_RELATION_VIA_MODEL => 'Via Model',
]);
echo $form->field($generator, 'generateRelationsFromCurrentSchema')->checkbox();
echo $form->field($generator, 'generateRelationNameFromDestinationTable')->checkbox();
echo $form->field($generator, 'useClassConstant')->checkbox();
echo $form->field($generator, 'generateLabelsFromComments')->checkbox();
echo $form->field($generator, 'generateQuery')->checkbox();
echo $form->field($generator, 'queryNs');
echo $form->field($generator, 'queryClass');
//echo $form->field($generator, 'queryBaseClass');
echo $form->field($generator, 'queryBaseClass')->textInput([
    'list' => 'query-base-class-options',
    'autocomplete' => 'off',
]);
echo Html::tag('datalist', implode("\n", array_map(fn($c) => Html::tag('option', '', ['value' => $c]), $generator->queryBaseClassOptions)), [
    'id' => 'query-base-class-options',
]);
echo $form->field($generator, 'enableI18N')->checkbox();
echo $form->field($generator, 'messageCategory');
echo $form->field($generator, 'generateChildClass')->checkbox();
