<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var yii\widgets\ActiveForm $form */
/** @var app\gii\addLanguageColumn\AddLanguageColumnGenerator $generator */
?>

<?php if ($generator->hasErrors()): ?>
    <div class="alert alert-danger">
        <?= Html::errorSummary($generator) ?>
    </div>
<?php endif; ?>

<?= $form->field($generator, 'newLanguageSuffix')->textInput([
    'maxlength' => true,
    'placeholder' => 'e.g., fr',
    'required' => true
]) ?>

<?= $form->field($generator, 'languages')->checkboxList(
    $generator->availableLanguages,
    [
        'itemOptions' => ['labelOptions' => ['class' => 'checkbox-inline']],
        'separator' => '<br>',
        'required' => true
    ]
) ?>

<?= $form->field($generator, 'position')->dropDownList(
    $generator->getPositionOptions(),
    [
        'options' => [
            'before_all' => ['class' => 'text-primary font-weight-bold'],
            'after_all' => ['class' => 'text-primary font-weight-bold'],
        ],
        'required' => true
    ]
) ?>

<?php
$js = <<<JS
$('form').on('submit', function (e) {
    var checked = $('input[name="AddLanguageColumnGenerator[languages][]"]:checked').length;
    var container = $('input[name="AddLanguageColumnGenerator[languages][]"]').closest('.form-group');

    if (checked === 0) {
        e.preventDefault();
        if (!container.find('.help-block').length) {
            container.addClass('has-error');
            container.append('<div class="help-block text-danger">Please select at least one base language.</div>');
        }
        return false;
    } else {
        container.removeClass('has-error');
        container.find('.help-block').remove();
    }
});
JS;

$this->registerJs($js);
?>
