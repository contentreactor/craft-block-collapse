import { beforeEach, describe, expect, it, vi } from 'vitest'
import * as registry from '../../assets/Collapse/src/js/registry.js'

const handler = (handle, overrides = {}) => ({
	handle,
	collapse: vi.fn(),
	expand: vi.fn(),
	count: () => ({ total: 0, collapsed: 0 }),
	...overrides,
})

beforeEach(() => registry.clear())

describe('register', () => {
	it('keeps handlers by handle', () => {
		const one = registry.register(handler('one'))

		expect(registry.get('one')).toBe(one)
		expect(registry.all()).toEqual([one])
	})

	it('replaces a handler registered under the same handle', () => {
		registry.register(handler('matrix'))
		const replacement = registry.register(handler('matrix'))

		expect(registry.all()).toEqual([replacement])
	})

	it.each([
		[null, 'has to be an object'],
		[{}, 'needs a handle'],
		[{ handle: 'x' }, 'collapse() and expand()'],
		[{ handle: 'x', collapse: () => {} }, 'collapse() and expand()'],
	])('turns down %s', (candidate, message) => {
		expect(() => registry.register(candidate)).toThrow(message)
	})

	it('forgets a handler on request', () => {
		registry.register(handler('one'))

		expect(registry.unregister('one')).toBe(true)
		expect(registry.unregister('one')).toBe(false)
		expect(registry.all()).toEqual([])
	})
})

describe('count', () => {
	it('adds up what every handler sees', () => {
		registry.register(handler('one', { count: () => ({ total: 3, collapsed: 1 }) }))
		registry.register(handler('two', { count: () => ({ total: 2, collapsed: 2 }) }))
		// A handler that doesn't count, and one that counts nothing, are both fine
		registry.register(handler('three', { count: undefined }))
		registry.register(handler('four', { count: () => null }))

		expect(registry.count(document)).toEqual({ total: 5, collapsed: 3 })
	})
})

describe('collapse and expand', () => {
	it('runs every handler against the given root', () => {
		const one = registry.register(handler('one'))
		const two = registry.register(handler('two'))
		const root = document.createElement('div')

		registry.collapse(root)
		registry.expand(root)

		expect(one.collapse).toHaveBeenCalledWith(root)
		expect(two.expand).toHaveBeenCalledWith(root)
	})
})
