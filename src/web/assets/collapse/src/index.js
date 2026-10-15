/**
 * Block Collapse — collapse or expand every block on an element edit screen.
 *
 * PHP hands the control panel its configuration through `window.BlockCollapse.start()`. Plugins
 * and modules can add their own kinds of blocks with `window.BlockCollapse.register()`, from an
 * asset bundle that depends on this one.
 */

import * as buttons from './buttons.js'
import * as keymap from './keymap.js'
import * as recorder from './recorder.js'
import * as registry from './registry.js'
import * as shortcuts from './shortcuts.js'
import * as strategies from './strategies.js'
import * as tooltip from './tooltip.js'

const REFRESH_DELAY = 100

let config = {
	shortcuts: {},
	labels: {},
	providers: [],
	enableShortcuts: true,
	hideWhenEmpty: true,
}

let listening = false

function ready(callback, doc = document) {
	if (doc.readyState === 'loading') {
		doc.addEventListener('DOMContentLoaded', callback, { once: true })
	} else {
		callback()
	}
}

/**
 * Element edit screens come and go — slideouts, nested editors, Matrix blocks added on the fly —
 * so the button groups get re-evaluated whenever the page changes.
 */
function watch(doc = document) {
	if (typeof MutationObserver === 'undefined' || !doc.body) {
		return
	}

	let timeout = null
	const observer = new MutationObserver(() => {
		clearTimeout(timeout)
		timeout = setTimeout(() => buttons.refresh(config, doc), REFRESH_DELAY)
	})

	observer.observe(doc.body, { childList: true, subtree: true })
}

/** Takes the configuration PHP rendered and gets everything going. */
export function start(incoming = {}) {
	config = { ...config, ...incoming }

	strategies.registerProviders(config.providers)

	ready(() => {
		if (!listening) {
			listening = true
			buttons.listen()
			shortcuts.listen(() => config)
			recorder.listen()
			tooltip.listen()
			watch()
		}

		buttons.refresh(config)
	})

	return api
}

export const api = {
	start,
	register: registry.register,
	unregister: registry.unregister,
	handlers: registry.all,
	count: registry.count,
	collapseAll: buttons.collapseAll,
	expandAll: buttons.expandAll,
	refresh: () => buttons.refresh(config),
	config: () => config,
	keymap,
}

if (typeof window !== 'undefined') {
	const existing = window.BlockCollapse && typeof window.BlockCollapse === 'object' ? window.BlockCollapse : {}
	const pending = Array.isArray(existing._pending) ? existing._pending : []

	window.BlockCollapse = Object.assign(existing, api)
	delete window.BlockCollapse._pending

	// Handlers that got in before the bundle did
	pending.forEach(handler => {
		try {
			registry.register(handler)
		} catch (error) {
			console.error(error)
		}
	})
}
