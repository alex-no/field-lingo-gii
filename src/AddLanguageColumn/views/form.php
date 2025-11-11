<?php
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use \AlexNo\FieldLingoGii\AddLanguageColumn\ApplyMode;

/** @var yii\web\View $this */
/** @var yii\widgets\ActiveForm $form */
/** @var AlexNo\FieldLingoGii\AddLanguageColumn\AddLanguageColumnGenerator $generator */

/*
 * Register highlight.js (CSS + JS) for SQL preview
 * Note: we use CDN here; if you prefer local assets, replace the URLs.
 */
$this->registerCssFile('https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css');
$this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js', [
    'position' => \yii\web\View::POS_END,
    'depends' => [\yii\web\JqueryAsset::class],
]);

// Apply mode options for radioList
$applyModeOptions = [
    ApplyMode::MIGRATION->value   => ApplyMode::MIGRATION->label(),
    ApplyMode::DIRECT_SQL->value => ApplyMode::DIRECT_SQL->label(),
];
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
    $generator->getAvailableLanguages(),
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

<?= $form->field($generator, 'applyModeValue')->radioList($applyModeOptions) ?>

<?php
$js = <<<'JS'
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
