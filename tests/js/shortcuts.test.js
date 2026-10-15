import { beforeEach, describe, expect, it, vi } from 'vitest'
import * as registry from '../../assets/Collapse/src/js/registry.js'
import * as shortcuts from '../../assets/Collapse/src/js/shortcuts.js'

const config = overrides => ({
	shortcuts: { collapse: 'ctrl+comma', expand: 'ctrl+period' },
	enableShortcuts: true,
	hideWhenEmpty: true,
	...overrides,
})

const handler = (total = 1) =>
	registry.register({
		handle: 'matrix',
		collapse: vi.fn(),
		expand: vi.fn(),
		count: () => ({ total, collapsed: 0 }),
	})

/** Each test listens on its own container, so listeners don't pile up on the document. */
let container

function keydown(code, modifiers = {}, target = container.querySelector('form') || container) {
	const event = new KeyboardEvent('keydown', { code, bubbles: true, cancelable: true, ...modifiers })
	target.dispatchEvent(event)

	return event
}

beforeEach(() => {
	registry.clear()
	document.body.innerHTML = ''
	container = document.createElement('div')
	container.innerHTML = '<form><div class="bc-toggle" data-bc-toggle></div></form>'
	document.body.appendChild(container)
	// The tests run as a non-Apple platform, so Ctrl is the primary modifier
	vi.stubGlobal('navigator', { platform: 'Linux x86_64', userAgent: 'vitest' })
})

describe('listen', () => {
	it('collapses and expands on the configured shortcuts', () => {
		const matrix = handler()
		shortcuts.listen(() => config(), container)

		const collapse = keydown('Comma', { ctrlKey: true })
		const expand = keydown('Period', { ctrlKey: true })

		expect(matrix.collapse).toHaveBeenCalledOnce()
		expect(matrix.expand).toHaveBeenCalledOnce()
		expect(collapse.defaultPrevented).toBe(true)
		expect(expand.defaultPrevented).toBe(true)
	})

	it('leaves other keystrokes alone', () => {
		const matrix = handler()
		shortcuts.listen(() => config(), container)

		const event = keydown('Comma')

		expect(matrix.collapse).not.toHaveBeenCalled()
		expect(event.defaultPrevented).toBe(false)
	})

	it('stays out of the way while a shortcut is being recorded', () => {
		const matrix = handler()
		container.innerHTML += '<input data-bc-recorder>'
		shortcuts.listen(() => config(), container)

		keydown('Comma', { ctrlKey: true }, container.querySelector('[data-bc-recorder]'))

		expect(matrix.collapse).not.toHaveBeenCalled()
	})

	it('does nothing when shortcuts are switched off', () => {
		const matrix = handler()
		shortcuts.listen(() => config({ enableShortcuts: false }), container)

		keydown('Comma', { ctrlKey: true })

		expect(matrix.collapse).not.toHaveBeenCalled()
	})

	it('ignores held-down keys', () => {
		const matrix = handler()
		shortcuts.listen(() => config(), container)

		keydown('Comma', { ctrlKey: true, repeat: true })

		expect(matrix.collapse).not.toHaveBeenCalled()
	})

	it('does nothing on a page that holds nothing collapsible', () => {
		const matrix = handler(0)
		shortcuts.listen(() => config(), container)

		keydown('Comma', { ctrlKey: true })

		expect(matrix.collapse).not.toHaveBeenCalled()
	})

	it('works with the buttons switched off', () => {
		const matrix = handler()
		container.innerHTML = '<form></form>'
		shortcuts.listen(() => config(), container)

		keydown('Comma', { ctrlKey: true })

		expect(matrix.collapse).toHaveBeenCalledOnce()
	})

	it('acts anyway when the buttons are told to stay up', () => {
		const matrix = handler(0)
		shortcuts.listen(() => config({ hideWhenEmpty: false }), container)

		keydown('Comma', { ctrlKey: true })

		expect(matrix.collapse).toHaveBeenCalledOnce()
	})
})
