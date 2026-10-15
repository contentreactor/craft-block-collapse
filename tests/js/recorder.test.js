import { beforeEach, describe, expect, it, vi } from 'vitest'
import * as recorder from '../../src/web/assets/collapse/src/recorder.js'

let container

function keydown(code, modifiers = {}) {
	const input = container.querySelector('[data-bc-recorder]')
	const event = new KeyboardEvent('keydown', { code, bubbles: true, cancelable: true, ...modifiers })
	input.dispatchEvent(event)

	return event
}

beforeEach(() => {
	document.body.innerHTML = ''
	container = document.createElement('div')
	container.innerHTML = '<input data-bc-recorder value="ctrl+comma">'
	document.body.appendChild(container)
	vi.stubGlobal('navigator', { platform: 'Linux x86_64', userAgent: 'vitest' })
	recorder.listen(container)
})

describe('listen', () => {
	it('writes the pressed combo into the field', () => {
		const changed = vi.fn()
		const input = container.querySelector('[data-bc-recorder]')
		input.addEventListener('change', changed)

		const event = keydown('KeyK', { ctrlKey: true, shiftKey: true })

		expect(input.value).toBe('ctrl+shift+k')
		expect(input.getAttribute('title')).toBe('Ctrl + Shift + K')
		expect(event.defaultPrevented).toBe(true)
		expect(changed).toHaveBeenCalledOnce()
	})

	it('lets the field be typed into and left by keyboard', () => {
		const input = container.querySelector('[data-bc-recorder]')

		// Plain typing, Tab and Escape all pass through untouched
		expect(keydown('KeyA').defaultPrevented).toBe(false)
		expect(keydown('Tab').defaultPrevented).toBe(false)
		expect(keydown('Escape').defaultPrevented).toBe(false)
		expect(input.value).toBe('ctrl+comma')
	})

	it('keeps the field as it was when the keystroke is not a usable shortcut', () => {
		const input = container.querySelector('[data-bc-recorder]')

		keydown('PrintScreen', { ctrlKey: true })

		expect(input.value).toBe('ctrl+comma')
	})
})
