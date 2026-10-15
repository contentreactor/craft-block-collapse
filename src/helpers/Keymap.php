<?php
declare(strict_types=1);

namespace ContentReactor\BlockCollapse\helpers;

/**
 * Parses, normalizes and labels keyboard shortcuts.
 *
 * A shortcut is stored as a lowercase, `+` separated list of modifiers followed by a single key:
 * `ctrl+comma`, `alt+shift+k`. `ctrl` means the platform's primary modifier — Control on
 * Windows/Linux, Command on macOS — which is the same thing Craft's own
 * `Garnish.isCtrlKeyPressed()` resolves it to.
 *
 * The key names are the lowercased `KeyboardEvent.code` values without their `Key`/`Digit`
 * prefixes, so the browser side can match a keystroke without a key code table and without
 * caring about the keyboard layout.
 */
final class Keymap
{
	/**
	 * @var list<string> Modifiers, in the order they are written out
	 */
	public const MODIFIERS = ['ctrl', 'alt', 'shift'];

	/**
	 * @var list<string> Combos the browser or the operating system is likely to own
	 */
	public const RESERVED = [
		'ctrl+a',
		'ctrl+c',
		'ctrl+f',
		'ctrl+n',
		'ctrl+p',
		'ctrl+q',
		'ctrl+r',
		'ctrl+s',
		'ctrl+t',
		'ctrl+v',
		'ctrl+w',
		'ctrl+x',
		'ctrl+y',
		'ctrl+z',
		'ctrl+shift+n',
		'ctrl+shift+p',
		'ctrl+shift+t',
		'ctrl+shift+z',
	];

	/**
	 * @var array<string, string>
	 */
	private const MODIFIER_ALIASES = [
		'control' => 'ctrl',
		'ctl' => 'ctrl',
		'cmd' => 'ctrl',
		'command' => 'ctrl',
		'meta' => 'ctrl',
		'super' => 'ctrl',
		'win' => 'ctrl',
		'⌘' => 'ctrl',
		'^' => 'ctrl',
		'option' => 'alt',
		'opt' => 'alt',
		'⌥' => 'alt',
		'⇧' => 'shift',
	];

	/**
	 * @var array<string, string>
	 */
	private const KEY_ALIASES = [
		',' => 'comma',
		'.' => 'period',
		'/' => 'slash',
		';' => 'semicolon',
		'\'' => 'quote',
		'\\' => 'backslash',
		'[' => 'bracketleft',
		']' => 'bracketright',
		'-' => 'minus',
		'=' => 'equal',
		'`' => 'backquote',
		'esc' => 'escape',
		'del' => 'delete',
		'ins' => 'insert',
		'return' => 'enter',
		'arrowup' => 'up',
		'arrowdown' => 'down',
		'arrowleft' => 'left',
		'arrowright' => 'right',
		'pgup' => 'pageup',
		'pgdn' => 'pagedown',
		'spacebar' => 'space',
	];

	/**
	 * @var array<string, string> Display labels for keys that aren't printed as-is
	 */
	private const KEY_LABELS = [
		'comma' => ',',
		'period' => '.',
		'slash' => '/',
		'semicolon' => ';',
		'quote' => '\'',
		'backslash' => '\\',
		'bracketleft' => '[',
		'bracketright' => ']',
		'minus' => '-',
		'equal' => '=',
		'backquote' => '`',
		'space' => 'Space',
		'enter' => 'Enter',
		'tab' => 'Tab',
		'escape' => 'Esc',
		'backspace' => 'Backspace',
		'delete' => 'Del',
		'insert' => 'Ins',
		'up' => '↑',
		'down' => '↓',
		'left' => '←',
		'right' => '→',
		'home' => 'Home',
		'end' => 'End',
		'pageup' => 'Page Up',
		'pagedown' => 'Page Down',
	];

	/**
	 * @var array<string, array{0: string, 1: string}> Modifier labels, as [generic, apple]
	 */
	private const MODIFIER_LABELS = [
		'ctrl' => ['Ctrl', '⌘'],
		'alt' => ['Alt', '⌥'],
		'shift' => ['Shift', '⇧'],
	];

	/**
	 * @var list<string>|null
	 */
	private static ?array $keys = null;

	/**
	 * Every key that may be used as a shortcut's main key.
	 *
	 * @return list<string>
	 */
	public static function keys(): array
	{
		return self::$keys ??= [
			...range('a', 'z'),
			...array_map('strval', range(0, 9)),
			...array_map(static fn(int $i): string => "f$i", range(1, 12)),
			'comma',
			'period',
			'slash',
			'semicolon',
			'quote',
			'backslash',
			'bracketleft',
			'bracketright',
			'minus',
			'equal',
			'backquote',
			'space',
			'enter',
			'escape',
			'backspace',
			'delete',
			'insert',
			'up',
			'down',
			'left',
			'right',
			'home',
			'end',
			'pageup',
			'pagedown',
		];
	}

	/**
	 * Splits a shortcut into its parts, or returns `null` if it isn't a usable shortcut.
	 *
	 * @return array{ctrl: bool, alt: bool, shift: bool, key: string}|null
	 */
	public static function parse(?string $combo): ?array
	{
		if ($combo === null || trim($combo) === '') {
			return null;
		}

		$parts = preg_split('/\s*\+\s*/u', mb_strtolower(trim($combo))) ?: [];
		$parts = array_values(array_filter($parts, static fn(string $part): bool => $part !== ''));

		$parsed = ['ctrl' => false, 'alt' => false, 'shift' => false, 'key' => ''];

		foreach ($parts as $part) {
			$modifier = self::MODIFIER_ALIASES[$part] ?? (in_array($part, self::MODIFIERS, true) ? $part : null);

			if ($modifier !== null) {
				$parsed[$modifier] = true;
				continue;
			}

			$key = self::KEY_ALIASES[$part] ?? $part;

			if ($parsed['key'] !== '' || !in_array($key, self::keys(), true)) {
				return null;
			}

			$parsed['key'] = $key;
		}

		// A shortcut needs a key, and a modifier that keeps it out of the way of plain typing
		if ($parsed['key'] === '' || (!$parsed['ctrl'] && !$parsed['alt'])) {
			return null;
		}

		return $parsed;
	}

	/**
	 * Rewrites a shortcut in its canonical form, or returns `null` if it isn't a usable shortcut.
	 */
	public static function normalize(?string $combo): ?string
	{
		$parsed = self::parse($combo);

		if ($parsed === null) {
			return null;
		}

		$parts = array_values(array_filter(self::MODIFIERS, static fn(string $modifier): bool => $parsed[$modifier]));
		$parts[] = $parsed['key'];

		return implode('+', $parts);
	}

	public static function isValid(?string $combo): bool
	{
		return self::normalize($combo) !== null;
	}

	/**
	 * Whether the browser or the operating system is likely to swallow this shortcut.
	 */
	public static function isReserved(?string $combo): bool
	{
		$normalized = self::normalize($combo);

		return $normalized !== null && in_array($normalized, self::RESERVED, true);
	}

	/**
	 * Formats a shortcut for humans, e.g. `Ctrl + ,` or, on Apple platforms, `⌘ + ,`.
	 */
	public static function label(?string $combo, bool $apple = false): string
	{
		$parsed = self::parse($combo);

		if ($parsed === null) {
			return '';
		}

		$parts = [];

		foreach (self::MODIFIERS as $modifier) {
			if ($parsed[$modifier]) {
				$parts[] = self::MODIFIER_LABELS[$modifier][$apple ? 1 : 0];
			}
		}

		$key = $parsed['key'];
		$parts[] = self::KEY_LABELS[$key] ?? mb_strtoupper($key);

		return implode(' + ', $parts);
	}
}
