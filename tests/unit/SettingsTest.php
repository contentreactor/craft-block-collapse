<?php
declare(strict_types=1);

use ContentReactor\BlockCollapse\models\Settings;

test('it ships with the shortcuts the plugin always had', function () {
	$settings = new Settings();

	expect($settings->collapseShortcut)->toBe('ctrl+comma')
		->and($settings->expandShortcut)->toBe('ctrl+period')
		->and($settings->enableButtons)->toBeTrue()
		->and($settings->enableShortcuts)->toBeTrue()
		->and($settings->allowUserOverrides)->toBeTrue()
		->and($settings->hideWhenEmpty)->toBeTrue()
		->and($settings->validate())->toBeTrue();
});

test('its defaults come out as a set of shortcuts', function () {
	$settings = new Settings(['collapseShortcut' => 'Alt + 1', 'expandShortcut' => 'alt+2']);

	expect($settings->getDefaultShortcuts()->getConfig())->toBe([
		'collapse' => 'alt+1',
		'expand' => 'alt+2',
	]);
});

test('it turns down unusable defaults', function (string $collapse, string $expand, string $attribute) {
	$settings = new Settings(['collapseShortcut' => $collapse, 'expandShortcut' => $expand]);

	expect($settings->validate())->toBeFalse()
		->and($settings->getFirstError($attribute))->not->toBeNull();
})->with([
	'no modifier' => ['comma', 'ctrl+period', 'collapseShortcut'],
	'reserved' => ['ctrl+s', 'ctrl+period', 'collapseShortcut'],
	'identical' => ['ctrl+comma', 'ctrl+comma', 'collapseShortcut'],
	'nonsense' => ['ctrl+comma', 'wat', 'expandShortcut'],
]);
