<?php
declare(strict_types=1);

use ContentReactor\BlockCollapse\models\Settings;
use ContentReactor\BlockCollapse\Plugin;
use ContentReactor\BlockCollapse\services\{
	Preferences,
	Providers,
};

test('it is a Craft plugin', function () {
	expect(Plugin::class)->toExtend(craft\base\Plugin::class);
});

test('it boots with its settings and services', function () {
	$plugin = plugin();

	expect($plugin->getSettings())->toBeInstanceOf(Settings::class)
		->and($plugin->providers)->toBeInstanceOf(Providers::class)
		->and($plugin->preferences)->toBeInstanceOf(Preferences::class)
		->and($plugin->hasCpSettings)->toBeTrue();
});

test('it translates under its own handle', function () {
	$plugin = plugin();

	expect($plugin->t9nCategory)->toBe(Plugin::HANDLE)
		->and(Craft::$app->getI18n()->translations)->toHaveKey(Plugin::HANDLE);
});

test('it hands the browser the shortcuts, the labels and the providers', function () {
	$config = plugin()->getJsConfig();

	expect($config)->toHaveKeys(['shortcuts', 'labels', 'providers', 'enableShortcuts', 'hideWhenEmpty'])
		->and($config['shortcuts'])->toBe(['collapse' => 'ctrl+comma', 'expand' => 'ctrl+period'])
		->and($config['labels'])->toBe(['collapse' => 'Collapse', 'expand' => 'Expand'])
		->and($config['providers'])->not->toBeEmpty();
});

test('its settings reach the browser', function () {
	$config = plugin([
		'collapseShortcut' => 'alt+1',
		'expandShortcut' => 'alt+2',
		'enableShortcuts' => false,
		'hideWhenEmpty' => false,
	])->getJsConfig();

	expect($config['shortcuts'])->toBe(['collapse' => 'alt+1', 'expand' => 'alt+2'])
		->and($config['enableShortcuts'])->toBeFalse()
		->and($config['hideWhenEmpty'])->toBeFalse();
});

test('its manifest says what the Plugin Store expects', function () {
	/** @var array<string, mixed> $composer */
	$composer = json_decode((string)file_get_contents(dirname(__DIR__, 2) . '/composer.json'), true);

	expect($composer['type'])->toBe('craft-plugin')
		->and($composer['license'])->toBe('MIT')
		->and($composer['extra']['handle'])->toBe(Plugin::HANDLE)
		->and($composer['extra']['class'])->toBe(Plugin::class)
		->and($composer['extra']['name'])->toBe('Block Collapse')
		// The store reads the icon from src/icon.svg, and the changelog from the repository
		->and($composer['extra'])->toHaveKeys(['developer', 'documentationUrl', 'changelogUrl'])
		->and(dirname(__DIR__, 2) . '/src/icon.svg')->toBeFile();
});

test('every language it ships names its message category after the handle', function () {
	$languages = glob(dirname(__DIR__, 2) . '/src/translations/*', GLOB_ONLYDIR) ?: [];

	expect($languages)->not->toBeEmpty();

	foreach ($languages as $language) {
		expect($language . '/' . Plugin::HANDLE . '.php')->toBeFile();
	}
});
