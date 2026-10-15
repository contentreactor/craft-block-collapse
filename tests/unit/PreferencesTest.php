<?php
declare(strict_types=1);

test('it falls back to the site defaults when nobody is signed in', function () {
	$plugin = plugin(['collapseShortcut' => 'alt+1', 'expandShortcut' => 'alt+2']);

	expect($plugin->preferences->forUser(null)->getConfig())->toBe(['collapse' => 'alt+1', 'expand' => 'alt+2'])
		->and($plugin->preferences->forCurrentUser()->getConfig())->toBe(['collapse' => 'alt+1', 'expand' => 'alt+2']);
});

test('nobody has overrides of their own', function () {
	expect(plugin()->preferences->hasOverrides(null))->toBeFalse();
});
