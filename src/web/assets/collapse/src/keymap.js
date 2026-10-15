/**
 * Shortcut parsing, matching and formatting.
 *
 * The canonical form of a shortcut is a lowercase, `+` separated list of modifiers followed by a
 * single key: `ctrl+comma`, `alt+shift+k`. It is the same format PHP's `Keymap` helper writes, and
 * `ctrl` means the platform's primary modifier — Control on Windows/Linux, Command on macOS.
 */

export const MODIFIERS = ['ctrl', 'alt', 'shift']

const MODIFIER_ALIASES = {
	control: 'ctrl',
	ctl: 'ctrl',
	cmd: 'ctrl',
	command: 'ctrl',
	meta: 'ctrl',
	super: 'ctrl',
	win: 'ctrl',
	'⌘': 'ctrl',
	'^': 'ctrl',
	option: 'alt',
	opt: 'alt',
	'⌥': 'alt',
	'⇧': 'shift',
}

const KEY_ALIASES = {
	',': 'comma',
	'.': 'period',
	'/': 'slash',
	';': 'semicolon',
	"'": 'quote',
	'\\': 'backslash',
	'[': 'bracketleft',
	']': 'bracketright',
	'-': 'minus',
	'=': 'equal',
	'`': 'backquote',
	esc: 'escape',
	del: 'delete',
	ins: 'insert',
	return: 'enter',
	arrowup: 'up',
	arrowdown: 'down',
	arrowleft: 'left',
	arrowright: 'right',
	pgup: 'pageup',
	pgdn: 'pagedown',
	spacebar: 'space',
}

const NAMED_KEY_CODES = {
	comma: ['Comma'],
	period: ['Period', 'NumpadDecimal'],
	slash: ['Slash', 'NumpadDivide'],
	semicolon: ['Semicolon'],
	quote: ['Quote'],
	backslash: ['Backslash'],
	bracketleft: ['BracketLeft'],
	bracketright: ['BracketRight'],
	minus: ['Minus', 'NumpadSubtract'],
	equal: ['Equal'],
	backquote: ['Backquote'],
	space: ['Space'],
	enter: ['Enter', 'NumpadEnter'],
	escape: ['Escape'],
	backspace: ['Backspace'],
	delete: ['Delete'],
	insert: ['Insert'],
	up: ['ArrowUp'],
	down: ['ArrowDown'],
	left: ['ArrowLeft'],
	right: ['ArrowRight'],
	home: ['Home'],
	end: ['End'],
	pageup: ['PageUp'],
	pagedown: ['PageDown'],
}

const KEY_LABELS = {
	comma: ',',
	period: '.',
	slash: '/',
	semicolon: ';',
	quote: "'",
	backslash: '\\',
	bracketleft: '[',
	bracketright: ']',
	minus: '-',
	equal: '=',
	backquote: '`',
	space: 'Space',
	enter: 'Enter',
	escape: 'Esc',
	backspace: 'Backspace',
	delete: 'Del',
	insert: 'Ins',
	up: '↑',
	down: '↓',
	left: '←',
	right: '→',
	home: 'Home',
	end: 'End',
	pageup: 'Page Up',
	pagedown: 'Page Down',
}

const MODIFIER_LABELS = {
	ctrl: ['Ctrl', '⌘'],
	alt: ['Alt', '⌥'],
	shift: ['Shift', '⇧'],
}

const KEY_CODES = (() => {
	const codes = { ...NAMED_KEY_CODES }

	for (let i = 0; i < 26; i++) {
		const letter = String.fromCharCode(97 + i)
		codes[letter] = [`Key${letter.toUpperCase()}`]
	}

	for (let i = 0; i <= 9; i++) {
		codes[String(i)] = [`Digit${i}`, `Numpad${i}`]
	}

	for (let i = 1; i <= 12; i++) {
		codes[`f${i}`] = [`F${i}`]
	}

	return codes
})()

const CODE_KEYS = (() => {
	const keys = {}

	for (const [key, codes] of Object.entries(KEY_CODES)) {
		for (const code of codes) {
			if (keys[code] === undefined) {
				keys[code] = key
			}
		}
	}

	return keys
})()

/** Every key that may be used as a shortcut's main key. */
export function keys() {
	return Object.keys(KEY_CODES)
}

/** Whether this is an Apple platform, where the primary modifier is Command rather than Control. */
export function isApple() {
	const platform =
		(typeof navigator !== 'undefined' &&
			(navigator.userAgentData?.platform || navigator.platform || navigator.userAgent)) ||
		''

	return /mac|iphone|ipad|ipod/i.test(platform)
}

/** Splits a shortcut into its parts, or returns `null` if it isn't a usable shortcut. */
export function parse(combo) {
	if (typeof combo !== 'string' || combo.trim() === '') {
		return null
	}

	const parsed = { ctrl: false, alt: false, shift: false, key: '' }
	const parts = combo
		.toLowerCase()
		.trim()
		.split('+')
		.map(part => part.trim())
		.filter(part => part !== '')

	for (const part of parts) {
		const modifier = MODIFIER_ALIASES[part] || (MODIFIERS.includes(part) ? part : null)

		if (modifier) {
			parsed[modifier] = true
			continue
		}

		const key = KEY_ALIASES[part] || part

		if (parsed.key !== '' || KEY_CODES[key] === undefined) {
			return null
		}

		parsed.key = key
	}

	// A shortcut needs a key, and a modifier that keeps it out of the way of plain typing
	if (parsed.key === '' || (!parsed.ctrl && !parsed.alt)) {
		return null
	}

	return parsed
}

/** Rewrites a shortcut in its canonical form, or returns `null` if it isn't a usable shortcut. */
export function normalize(combo) {
	const parsed = parse(combo)

	if (!parsed) {
		return null
	}

	return [...MODIFIERS.filter(modifier => parsed[modifier]), parsed.key].join('+')
}

export function isValid(combo) {
	return normalize(combo) !== null
}

/** Formats a shortcut for humans, e.g. `Ctrl + ,` or, on Apple platforms, `⌘ + ,`. */
export function format(combo, apple = isApple()) {
	const parsed = parse(combo)

	if (!parsed) {
		return ''
	}

	const parts = MODIFIERS.filter(modifier => parsed[modifier]).map(
		modifier => MODIFIER_LABELS[modifier][apple ? 1 : 0],
	)
	parts.push(KEY_LABELS[parsed.key] || parsed.key.toUpperCase())

	return parts.join(' + ')
}

/** Whether the keystroke is the given shortcut. */
export function matchesEvent(combo, event, apple = isApple()) {
	const parsed = parse(combo)

	if (!parsed || !event) {
		return false
	}

	const primary = apple ? event.metaKey : event.ctrlKey
	const secondary = apple ? event.ctrlKey : event.metaKey

	if (primary !== parsed.ctrl || secondary || !!event.altKey !== parsed.alt || !!event.shiftKey !== parsed.shift) {
		return false
	}

	return (KEY_CODES[parsed.key] || []).includes(event.code)
}

/** The shortcut a keystroke stands for, or `null` if it isn't a usable one. */
export function fromEvent(event, apple = isApple()) {
	if (!event) {
		return null
	}

	const key = CODE_KEYS[event.code]

	if (key === undefined) {
		return null
	}

	const parts = []

	if (apple ? event.metaKey : event.ctrlKey) {
		parts.push('ctrl')
	}

	if (event.altKey) {
		parts.push('alt')
	}

	if (event.shiftKey) {
		parts.push('shift')
	}

	parts.push(key)

	return normalize(parts.join('+'))
}
