<?php
declare(strict_types=1);

use ContentReactor\BlockCollapse\models\BlockProvider;

test('it falls back to its handle for a name', function () {
	expect((new BlockProvider(['handle' => 'super-field']))->getLabel())->toBe('super-field')
		->and((new BlockProvider(['handle' => 'super-field', 'label' => 'Super Field']))->getLabel())->toBe('Super Field');
});

test('it only applies to the Craft versions it was declared for', function () {
	$provider = new BlockProvider(['handle' => 'x', 'craftVersions' => [5, 6]]);

	expect($provider->appliesTo(4))->toBeFalse()
		->and($provider->appliesTo(5))->toBeTrue()
		->and($provider->appliesTo(6))->toBeTrue();
});

test('it needs a handle, a selector and a strategy', function () {
	$provider = new BlockProvider();

	expect($provider->validate())->toBeFalse()
		->and(array_keys($provider->getErrors()))->toContain('handle', 'blockSelector');
});

test('it needs a handler for the js strategy', function () {
	$provider = new BlockProvider([
		'handle' => 'x',
		'blockSelector' => '.x',
		'strategy' => BlockProvider::STRATEGY_JS,
	]);

	expect($provider->validate())->toBeFalse()
		->and($provider->getFirstError('handler'))->not->toBeNull();

	$provider->handler = 'x-handler';

	expect($provider->validate())->toBeTrue();
});

test('it needs both selectors for the menu-action strategy', function () {
	$provider = new BlockProvider([
		'handle' => 'x',
		'blockSelector' => '.x',
		'strategy' => BlockProvider::STRATEGY_MENU_ACTION,
	]);

	expect($provider->validate())->toBeFalse()
		->and($provider->getFirstError('collapseSelector'))->not->toBeNull();
});

test('it turns down a strategy it cannot run', function () {
	$provider = new BlockProvider(['handle' => 'x', 'blockSelector' => '.x', 'strategy' => 'telepathy']);

	expect($provider->validate())->toBeFalse()
		->and($provider->getFirstError('strategy'))->not->toBeNull();
});
