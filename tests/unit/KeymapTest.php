<?php
declare(strict_types=1);

use ContentReactor\BlockCollapse\helpers\Keymap;

test('it normalizes however a shortcut was written', function (string $written, ?string $expected) {
	expect(Keymap::normalize($written))->toBe($expected);
})->with([
	['ctrl+comma', 'ctrl+comma'],
	['Ctrl + ,', 'ctrl+comma'],
	['CTRL+COMMA', 'ctrl+comma'],
	['cmd+shift+k', 'ctrl+shift+k'],
	['⌘+.', 'ctrl+period'],
	['shift + alt + arrowup', 'alt+shift+up'],
	['option+f5', 'alt+f5'],
	// Modifiers always come out in the same order
	['shift+ctrl+alt+j', 'ctrl+alt+shift+j'],
]);

test('it rejects what cannot be a shortcut', function (?string $written) {
	expect(Keymap::normalize($written))->toBeNull()
		->and(Keymap::isValid($written))->toBeFalse();
})->with([
	[null],
	[''],
	['   '],
	// No modifier that keeps it clear of plain typing
	['comma'],
	['shift+a'],
	// Two keys
	['ctrl+a+b'],
	// Not a key the plugin knows
	['ctrl+printscreen'],
	['ctrl'],
]);

test('it knows which combos the browser owns', function () {
	expect(Keymap::isReserved('ctrl+s'))->toBeTrue()
		->and(Keymap::isReserved('Ctrl + S'))->toBeTrue()
		->and(Keymap::isReserved('ctrl+comma'))->toBeFalse()
		->and(Keymap::isReserved('nonsense'))->toBeFalse();
});

test('it labels shortcuts for the platform', function () {
	expect(Keymap::label('ctrl+comma'))->toBe('Ctrl + ,')
		->and(Keymap::label('ctrl+comma', apple: true))->toBe('⌘ + ,')
		->and(Keymap::label('ctrl+alt+shift+k'))->toBe('Ctrl + Alt + Shift + K')
		->and(Keymap::label('alt+up'))->toBe('Alt + ↑')
		->and(Keymap::label('ctrl+escape'))->toBe('Ctrl + Esc')
		->and(Keymap::label('not a shortcut'))->toBe('');
});

test('its key list covers letters, digits, function keys and punctuation', function () {
	expect(Keymap::keys())
		->toContain('a', 'z', '0', '9', 'f1', 'f12', 'comma', 'period', 'pagedown')
		->not->toContain('ctrl', 'alt', 'shift');
});
