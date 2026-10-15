import { describe, expect, it } from 'vitest'
import * as keymap from '../../src/web/assets/collapse/src/keymap.js'

const press = (code, modifiers = {}) => ({
	code,
	ctrlKey: false,
	metaKey: false,
	altKey: false,
	shiftKey: false,
	...modifiers,
})

describe('normalize', () => {
	it.each([
		['ctrl+comma', 'ctrl+comma'],
		['Ctrl + ,', 'ctrl+comma'],
		['CTRL+COMMA', 'ctrl+comma'],
		['cmd+shift+k', 'ctrl+shift+k'],
		['⌘+.', 'ctrl+period'],
		['shift + alt + arrowup', 'alt+shift+up'],
		['shift+ctrl+alt+j', 'ctrl+alt+shift+j'],
	])('rewrites %s as %s', (written, expected) => {
		expect(keymap.normalize(written)).toBe(expected)
	})

	it.each([null, undefined, '', '   ', 'comma', 'shift+a', 'ctrl+a+b', 'ctrl+printscreen', 'ctrl', 42])(
		'rejects %s',
		written => {
			expect(keymap.normalize(written)).toBeNull()
			expect(keymap.isValid(written)).toBe(false)
		},
	)

	it('agrees with the PHP helper on the canonical form', () => {
		// Both sides write modifiers in this order, so a shortcut stored by PHP matches here
		expect(keymap.MODIFIERS).toEqual(['ctrl', 'alt', 'shift'])
	})
})

describe('format', () => {
	it('labels shortcuts for the platform', () => {
		expect(keymap.format('ctrl+comma', false)).toBe('Ctrl + ,')
		expect(keymap.format('ctrl+comma', true)).toBe('⌘ + ,')
		expect(keymap.format('ctrl+alt+shift+k', false)).toBe('Ctrl + Alt + Shift + K')
		expect(keymap.format('alt+up', false)).toBe('Alt + ↑')
		expect(keymap.format('nonsense', false)).toBe('')
	})
})

describe('matchesEvent', () => {
	it('matches the primary modifier per platform', () => {
		expect(keymap.matchesEvent('ctrl+comma', press('Comma', { ctrlKey: true }), false)).toBe(true)
		expect(keymap.matchesEvent('ctrl+comma', press('Comma', { metaKey: true }), true)).toBe(true)

		// Control on a Mac is not the primary modifier, and neither is the Windows key elsewhere
		expect(keymap.matchesEvent('ctrl+comma', press('Comma', { ctrlKey: true }), true)).toBe(false)
		expect(keymap.matchesEvent('ctrl+comma', press('Comma', { metaKey: true }), false)).toBe(false)
	})

	it('is strict about the other modifiers', () => {
		expect(keymap.matchesEvent('ctrl+comma', press('Comma', { ctrlKey: true, shiftKey: true }), false)).toBe(false)
		expect(keymap.matchesEvent('ctrl+shift+comma', press('Comma', { ctrlKey: true, shiftKey: true }), false)).toBe(
			true,
		)
		expect(keymap.matchesEvent('alt+comma', press('Comma', { altKey: true }), false)).toBe(true)
		expect(keymap.matchesEvent('alt+comma', press('Comma', { altKey: true, ctrlKey: true }), false)).toBe(false)
	})

	it('accepts the numpad twins of a key', () => {
		expect(keymap.matchesEvent('ctrl+period', press('NumpadDecimal', { ctrlKey: true }), false)).toBe(true)
		expect(keymap.matchesEvent('ctrl+1', press('Numpad1', { ctrlKey: true }), false)).toBe(true)
	})

	it('says no to anything it cannot read', () => {
		expect(keymap.matchesEvent('nonsense', press('Comma', { ctrlKey: true }), false)).toBe(false)
		expect(keymap.matchesEvent('ctrl+comma', null, false)).toBe(false)
	})
})

describe('fromEvent', () => {
	it('turns a keystroke into a shortcut', () => {
		expect(keymap.fromEvent(press('Comma', { ctrlKey: true }), false)).toBe('ctrl+comma')
		expect(keymap.fromEvent(press('KeyK', { metaKey: true, shiftKey: true }), true)).toBe('ctrl+shift+k')
		expect(keymap.fromEvent(press('ArrowUp', { altKey: true }), false)).toBe('alt+up')
	})

	it('refuses keystrokes that would not survive a round trip', () => {
		// No modifier, an unknown key, or nothing at all
		expect(keymap.fromEvent(press('Comma'), false)).toBeNull()
		expect(keymap.fromEvent(press('PrintScreen', { ctrlKey: true }), false)).toBeNull()
		expect(keymap.fromEvent(null, false)).toBeNull()
	})
})
