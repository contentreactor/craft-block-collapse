<?php
declare(strict_types=1);

use ContentReactor\BlockCollapse\models\Shortcuts;

test('it normalizes whatever it is given', function () {
	$shortcuts = new Shortcuts(['collapse' => 'Ctrl + ,', 'expand' => 'CMD+.']);

	expect($shortcuts->collapse)->toBe('ctrl+comma')
		->and($shortcuts->expand)->toBe('ctrl+period')
		->and($shortcuts->validate())->toBeTrue();
});

test('it falls back to the defaults for anything missing or unusable', function () {
	$defaults = new Shortcuts(['collapse' => 'ctrl+comma', 'expand' => 'ctrl+period']);

	$shortcuts = Shortcuts::fromConfig(['collapse' => 'alt+1', 'expand' => 'shift+nonsense'], $defaults);

	expect($shortcuts->collapse)->toBe('alt+1')
		->and($shortcuts->expand)->toBe('ctrl+period');

	expect(Shortcuts::fromConfig(null, $defaults)->getConfig())->toBe($defaults->getConfig());
	expect(Shortcuts::fromConfig('nope', $defaults)->getConfig())->toBe($defaults->getConfig());
});

test('it has no shortcuts at all without defaults to fall back to', function () {
	$shortcuts = Shortcuts::fromConfig([]);

	expect($shortcuts->collapse)->toBeNull()
		->and($shortcuts->validate())->toBeFalse()
		->and($shortcuts->getFirstError('collapse'))->not->toBeNull();
});

test('it turns down the combos the browser owns', function () {
	$shortcuts = new Shortcuts(['collapse' => 'ctrl+s', 'expand' => 'ctrl+period']);

	expect($shortcuts->validate())->toBeFalse()
		->and($shortcuts->getFirstError('collapse'))->toContain('reserved');
});

test('it turns down two shortcuts that are the same', function () {
	$shortcuts = new Shortcuts(['collapse' => 'ctrl+comma', 'expand' => 'Ctrl + ,']);

	expect($shortcuts->validate())->toBeFalse()
		->and($shortcuts->getFirstError('collapse'))->toContain('different');
});

test('it hands out what the browser needs', function () {
	$shortcuts = new Shortcuts(['collapse' => 'ctrl+comma', 'expand' => 'alt+period']);

	expect($shortcuts->getConfig())->toBe(['collapse' => 'ctrl+comma', 'expand' => 'alt+period'])
		->and($shortcuts->getLabels())->toBe(['collapse' => 'Ctrl + ,', 'expand' => 'Alt + .'])
		->and($shortcuts->getLabels(apple: true))->toBe(['collapse' => '⌘ + ,', 'expand' => '⌥ + .']);
});
