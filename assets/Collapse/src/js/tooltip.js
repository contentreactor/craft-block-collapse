/**
 * A small tooltip for the buttons.
 *
 * Craft's own tooltips sit on `title`/`aria-label`, which the control panel renders with a delay
 * that leaves them hanging around after the button bar re-renders — hence this one, which is
 * delegated and only ever has one node in the DOM.
 */

const SHOW_DELAY = 500
const HIDE_DELAY = 250

export function create(doc = document) {
	let wrapper = doc.getElementById('bc-tooltip')

	if (!wrapper) {
		const text = doc.createElement('span')
		text.className = 'bc-tooltip-text'

		wrapper = doc.createElement('div')
		wrapper.id = 'bc-tooltip'
		wrapper.setAttribute('role', 'presentation')
		wrapper.appendChild(text)
		doc.body.appendChild(wrapper)
	}

	return wrapper
}

export function listen(doc = document, win = typeof window !== 'undefined' ? window : undefined) {
	const wrapper = create(doc)
	const text = wrapper.querySelector('.bc-tooltip-text')
	let showTimeout = null
	let hideTimeout = null

	const hide = () => {
		wrapper.style.display = 'none'
	}

	const show = button => {
		const label = button.dataset.bcTooltip || button.getAttribute('aria-label') || ''

		if (!label) {
			return
		}

		clearTimeout(hideTimeout)
		clearTimeout(showTimeout)
		hide()
		text.textContent = label

		const rect = button.getBoundingClientRect()
		wrapper.style.left = `${rect.left + rect.width / 2 + (win ? win.scrollX : 0)}px`
		wrapper.style.top = `${rect.bottom + 5 + (win ? win.scrollY : 0)}px`

		showTimeout = setTimeout(() => {
			wrapper.style.display = 'block'
		}, SHOW_DELAY)
	}

	doc.addEventListener('mouseover', event => {
		const button = event.target.closest ? event.target.closest('[data-bc-action]') : null

		if (button) {
			show(button)
		}
	})

	doc.addEventListener('mouseout', event => {
		const button = event.target.closest ? event.target.closest('[data-bc-action]') : null

		if (!button) {
			return
		}

		clearTimeout(showTimeout)
		hideTimeout = setTimeout(hide, HIDE_DELAY)
	})

	// A button bar that goes away while its tooltip is up shouldn't leave the tooltip behind
	doc.addEventListener('click', hide)

	return wrapper
}
