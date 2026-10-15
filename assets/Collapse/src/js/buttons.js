/**
 * The collapse/expand button group: clicks, visibility and labels.
 */

import * as keymap from './keymap.js'
import * as registry from './registry.js'

export const TOGGLE_SELECTOR = '[data-bc-toggle]'
export const BUTTON_SELECTOR = '[data-bc-action]'
const PENDING_CLASS = 'bc-pending'

/**
 * The part of the page a button group is responsible for.
 *
 * Craft wraps an element edit screen, button bar included, in a form — so scoping to it keeps
 * nested editors from toggling each other's blocks.
 */
export function rootFor(element) {
	if (!element || typeof element.closest !== 'function') {
		return document
	}

	// Whichever comes first: an element edit screen's form, or the slideout holding one
	return element.closest('form, .slideout') || document
}

/** The button group to act on when the keyboard is used: the innermost one on screen. */
export function activeToggle(doc = document) {
	const toggles = [...doc.querySelectorAll(TOGGLE_SELECTOR)].filter(
		toggle => !toggle.classList.contains(PENDING_CLASS),
	)

	return toggles.length ? toggles[toggles.length - 1] : null
}

export function collapseAll(root = document) {
	registry.collapse(root)
}

export function expandAll(root = document) {
	registry.expand(root)
}

/** Runs the action a button stands for, scoped to that button's part of the page. */
export function run(action, element) {
	const root = rootFor(element)

	if (action === 'collapse') {
		collapseAll(root)
	} else if (action === 'expand') {
		expandAll(root)
	}
}

/**
 * Shows the button groups that have something to toggle, hides the ones that don't, and labels
 * the buttons with the shortcut the current user actually has.
 */
export function refresh(config = {}, doc = document) {
	const shortcuts = config.shortcuts || {}

	doc.querySelectorAll(TOGGLE_SELECTOR).forEach(toggle => {
		toggle.querySelectorAll(BUTTON_SELECTOR).forEach(button => {
			const action = button.dataset.bcAction
			const label = button.dataset.bcLabel || ''
			const shortcut = config.enableShortcuts === false ? null : shortcuts[action]
			const formatted = keymap.format(shortcut)
			const title = formatted ? `${label} (${formatted})` : label

			button.dataset.bcShortcut = shortcut || ''
			button.dataset.bcTooltip = title
			button.setAttribute('aria-label', title)
		})

		const hide = config.hideWhenEmpty !== false && registry.count(rootFor(toggle)).total === 0

		toggle.classList.toggle(PENDING_CLASS, hide)
	})
}

/** Wires up the clicks. Delegated, so button groups added later work too. */
export function listen(doc = document) {
	doc.addEventListener('click', event => {
		const button = event.target.closest ? event.target.closest(BUTTON_SELECTOR) : null

		if (!button) {
			return
		}

		event.preventDefault()
		run(button.dataset.bcAction, button)
	})
}
