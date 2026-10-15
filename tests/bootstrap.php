<?php
declare(strict_types=1);

use ContentReactor\BlockCollapse\Tests\TestApplication;

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';
require $root . '/vendor/yiisoft/yii2/Yii.php';
require $root . '/vendor/craftcms/cms/src/Craft.php';

// The plugin's models and services only need translations and a version number, so the tests run
// against a bare Yii application rather than a full Craft install — no database, no .env
new TestApplication([
	'id' => 'block-collapse-tests',
	'basePath' => $root,
	'vendorPath' => $root . '/vendor',
	'components' => [
		'user' => [
			'class' => craft\console\User::class,
		],
		'i18n' => [
			'translations' => [
				'block-collapse' => [
					'class' => craft\i18n\PhpMessageSource::class,
					'sourceLanguage' => 'en-US',
					'basePath' => $root . '/src/translations',
					'forceTranslation' => true,
					// Overrides live in a site's translations folder, which doesn't exist here
					'allowOverrides' => false,
				],
			],
		],
	],
]);
