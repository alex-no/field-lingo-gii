# ⚙️ Field-Lingo Gii

[![Packagist Version](https://img.shields.io/packagist/v/alex-no/field-lingo-gii.svg)](https://packagist.org/packages/alex-no/field-lingo-gii)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/packagist/php-v/alex-no/field-lingo-gii.svg)](https://www.php.net/)
[![Downloads](https://img.shields.io/packagist/dt/alex-no/field-lingo-gii.svg)](https://packagist.org/packages/alex-no/field-lingo-gii)

> A set of Gii tools for **Field-Lingo** (Yii2):  
> - **Extended Model Generator** — generates parent + child model classes wired to `LingoActiveRecord` / `LingoActiveQuery`.  
> - **Add Language Generator** — helps produce SQL-scripts to bulk-add language-suffixed fields (e.g. `name_en`, `name_uk`) across tables.

---

## 🔎 Overview

Field-Lingo stores localized columns directly in DB (e.g. `title_en`, `title_uk`, ...). These Gii tools help automate two repetitive tasks:

1. Generate models that extend `AlexNo\FieldLingo\Adapters\Yii2\LingoActiveRecord` and use `LingoActiveQuery`.
2. Generate SQL-scripts to add a language (set of `_xx` fields) across many tables.

---

## 📦 Installation

Add as a development dependency:

```bash
composer require --dev alex-no/field-lingo-gii
```

> Note: This package depends on alex-no/field-lingo. Ensure your project requires that package as well (composer will attempt to resolve it). Replace `*` with specific version constraints before tagging stable releases.

---

## ⚙️ Registering generators in Yii2 (example)

In your Yii2 config/web.php (or config/main.php) register generators for the Gii module:

```php
'modules' => [
    'gii' => [
        'class' => \yii\gii\Module::class,
        'generators' => [
            'field-lingo-extended-model' => [
                'class' => \AlexNo\FieldLingoGii\Gii\ExtendedModelGenerator::class,
                'templates' => [
                    'extended' => '@vendor/alex-no/field-lingo-gii/templates/extended',
                ],
                'baseClassOptions' => [
                    'yii\db\ActiveRecord',
                    'AlexNo\FieldLingo\Adapters\Yii2\LingoActiveRecord',
                ],
                'queryBaseClassOptions' => [
                    'yii\db\ActiveQuery',
                    'AlexNo\FieldLingo\Adapters\Yii2\LingoActiveQuery',
                ],
            ],
            'field-lingo-add-language' => [
                'class' => \AlexNo\FieldLingoGii\Gii\AddLanguageGenerator::class,
                'templates' => [
                    'default' => '@vendor/alex-no/field-lingo-gii/templates/add-language',
                ],
            ],
        ],
    ],
],
```

After registering with Gii, two new generators will appear:

 - **FieldLingo Extended Model**
 - **FieldLingo Add Language**

---

## ✨ Extended Model Generator — what it does
 - Generates models/base/YourModel.php — main (regenerated) logic;
 - Generates models/YourModel.php — empty child class for custom logic;;
 - Optionally generates models/YourModelQuery.php (using LingoActiveQuery when chosen).

---

## ✨ Add Language Generator — what it does

 - Accepts a language code (e.g., es) and a list of tables/fields;
 - Generates SQL with addColumn for each field and table.

---
 
## 📁 Directory Structure

```css
field-lingo-gii/          # repo root
├─ src/
│  ├─ extendedModel
│  │  ├─ ExtendedModelGenerator.php
│  │  ├─ Helpers/
│  │  │  └─ ViewRenderer.php
│  │  ├─ templates/
│  │  │  ├─ extended/
│  │  │  │  ├─ model.php
│  │  │  │  ├─ model-child.php
│  │  │  │  └─ query.php
│  │  │  └─ add-language/
│  │  │     ├─ migration.php
│  │  │     └─ preview.php
│  │  └─ views/
│  │     └─ form.php
│  └─ addLanguageColumn
│     ├─ AddLanguageGenerator.php
│     ├─ SqlCodeFile.php
│     ├─ Helpers/
│     │  └─ ViewRenderer.php
│     └─ views/
│        ├─ default.php
│        ├─ form.php
│        └─ generator.php
├─ examples/
│  └─ yii2-setup.md
├─ tests/
├─ README.md
├─ composer.json
├─ LICENSE
└─ .gitattributes
```

---

## 📁 Examples

examples/yii2-setup.md contains step-by-step instructions for installing and registering generators.

---

## 🛠 Development & Tests

```bash
composer install
vendor/bin/phpunit
```

---

## 🤝 Contributing

PRs welcome. Follow PSR-12, add tests for significant logic.

---

## 📜 License

MIT — see LICENSE