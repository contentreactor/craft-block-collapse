/**
 * The collapse/expand handlers currently in play.
 *
 * A handler is a plain object:
 *
 * ```js
 * {
 *     handle: 'super-field',
 *     collapse(root) {},
 *     expand(root) {},
 *     count(root) { return { total: 0, collapsed: 0 } },
 * }
 * ```
 *
 * Handlers registered under a handle that is already taken replace the earlier one, which is how a
 * site can swap out a built-in provider for its own.
 */

const handlers = new Map()

export function register(handler) {
	if (!handler || typeof handler !== 'object') {
		throw new TypeError('A block collapse handler has to be an object.')
	}

	if (typeof handler.handle !== 'string' || handler.handle === '') {
		throw new TypeError('A block collapse handler needs a handle.')
	}

	if (typeof handler.collapse !== 'function' || typeof handler.expand !== 'function') {
		throw new TypeError(`The “${handler.handle}” block collapse handler needs collapse() and expand() methods.`)
	}

	handlers.set(handler.handle, handler)

	return handler
}

export function unregister(handle) {
	return handlers.delete(handle)
}

export function get(handle) {
	return handlers.get(handle) || null
}

export function all() {
	return [...handlers.values()]
}

export function clear() {
	handlers.clear()
}

/** Blocks the handlers can see below the given root, summed up. */
export function count(root = document) {
	return all().reduce(
		(totals, handler) => {
			if (typeof handler.count !== 'function') {
				return totals
			}

			const counted = handler.count(root) || {}

			return {
				total: totals.total + (counted.total || 0),
				collapsed: totals.collapsed + (counted.collapsed || 0),
			}
		},
		{ total: 0, collapsed: 0 },
	)
}

export function collapse(root = document) {
	all().forEach(handler => handler.collapse(root))
}

export function expand(root = document) {
	all().forEach(handler => handler.expand(root))
}
