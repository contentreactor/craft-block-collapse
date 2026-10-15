/**
 * Turns the block providers declared in PHP into handlers the registry can run.
 */

import * as registry from './registry.js'

function blocksIn(root, selector) {
	if (!root || !selector) {
		return []
	}

	const scope = root.querySelectorAll ? root : document

	return [...scope.querySelectorAll(selector)]
}

function jQuery() {
	return typeof window !== 'undefined' ? window.jQuery || window.$ || null : null
}

function counter(provider) {
	return root => {
		const blocks = blocksIn(root, provider.blockSelector)
		const collapsedClass = provider.collapsedClass || 'collapsed'

		return {
			total: blocks.length,
			collapsed: blocks.filter(block => block.classList.contains(collapsedClass)).length,
		}
	}
}

/**
 * Craft's Matrix input and Neo both keep a block object on the element's jQuery data, with
 * `collapse()` and `expand()` methods on it.
 */
function jqueryBlockHandler(provider) {
	const run = (root, method) => {
		const $ = jQuery()

		if (!$) {
			return
		}

		blocksIn(root, provider.blockSelector).forEach(block => {
			const instance = $(block).data(provider.dataKey || 'block')

			if (instance && typeof instance[method] === 'function') {
				instance[method]()
			}
		})
	}

	return {
		handle: provider.handle,
		collapse: root => run(root, provider.collapseMethod || 'collapse'),
		expand: root => run(root, provider.expandMethod || 'expand'),
		count: counter(provider),
	}
}

/**
 * Blocks whose collapsing is wired to a menu item or button.
 */
function menuActionHandler(provider) {
	const run = (root, selector) => {
		if (!selector) {
			return
		}

		const $ = jQuery()

		blocksIn(root, provider.blockSelector).forEach(block => {
			const trigger = block.matches(selector) ? block : block.querySelector(selector)

			if (!trigger) {
				return
			}

			// Craft's menu items listen for jQuery's `activate` event; anything else takes a click
			if ($) {
				$(trigger).trigger('activate')
			} else if (typeof trigger.click === 'function') {
				trigger.click()
			}
		})
	}

	return {
		handle: provider.handle,
		collapse: root => run(root, provider.collapseSelector),
		expand: root => run(root, provider.expandSelector),
		count: counter(provider),
	}
}

/**
 * Blocks that are collapsed by a class alone.
 */
function classToggleHandler(provider) {
	const collapsedClass = provider.collapsedClass || 'collapsed'

	return {
		handle: provider.handle,
		collapse: root => blocksIn(root, provider.blockSelector).forEach(block => block.classList.add(collapsedClass)),
		expand: root => blocksIn(root, provider.blockSelector).forEach(block => block.classList.remove(collapsedClass)),
		count: counter(provider),
	}
}

/**
 * Blocks handled by JavaScript that registered itself under `provider.handler`.
 */
function jsHandler(provider) {
	const delegate = method => root => {
		const handler = registry.get(provider.handler)

		if (handler && typeof handler[method] === 'function') {
			handler[method](root)
		}
	}

	return {
		handle: provider.handle,
		collapse: delegate('collapse'),
		expand: delegate('expand'),
		count: root => {
			const handler = registry.get(provider.handler)

			return handler && typeof handler.count === 'function' ? handler.count(root) : { total: 0, collapsed: 0 }
		},
	}
}

const STRATEGIES = {
	'jquery-block': jqueryBlockHandler,
	'menu-action': menuActionHandler,
	'class-toggle': classToggleHandler,
	js: jsHandler,
}

/** Builds a handler for a provider, or returns `null` if its strategy is unknown. */
export function createHandler(provider) {
	if (!provider || typeof provider !== 'object') {
		return null
	}

	const strategy = STRATEGIES[provider.strategy]

	if (!strategy || !provider.handle) {
		return null
	}

	// A `js` provider only points at a handler that registered itself under another name
	if (provider.strategy === 'js' && provider.handler === provider.handle) {
		return null
	}

	return strategy(provider)
}

/** Registers handlers for every provider that came from PHP. */
export function registerProviders(providers = []) {
	return providers
		.map(provider => createHandler(provider))
		.filter(handler => handler !== null)
		.map(handler => registry.register(handler))
}
