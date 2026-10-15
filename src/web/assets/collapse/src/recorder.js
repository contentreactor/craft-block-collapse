/**
 * The “press the keys you want” inputs on the settings and preferences screens.
 */

import * as keymap from './keymap.js'

export const RECORDER_SELECTOR = '[data-bc-recorder]'

/** Keys that have to keep working so the field can be left or emptied by keyboard. */
const PASSTHROUGH = ['Tab', 'Escape']

export function listen(doc = document) {
	doc.addEventListener('keydown', event => {
		const input =
			event.target && typeof event.target.closest === 'function' ? event.target.closest(RECORDER_SELECTOR) : null

		if (!input || PASSTHROUGH.includes(event.key)) {
			return
		}

		// Let the user clear the field and type a shortcut out by hand
		if (!event.ctrlKey && !event.metaKey && !event.altKey) {
			return
		}

		const combo = keymap.fromEvent(event)

		if (!combo) {
			return
		}

		event.preventDefault()
		input.value = combo
		input.setAttribute('title', keymap.format(combo))
		input.dispatchEvent(new Event('input', { bubbles: true }))
		input.dispatchEvent(new Event('change', { bubbles: true }))
	})
}
