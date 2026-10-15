import { beforeEach, describe, expect, it, vi } from 'vitest'
import * as registry from '../../src/web/assets/collapse/src/registry.js'
import { createHandler, registerProviders } from '../../src/web/assets/collapse/src/strategies.js'

/** A jQuery stand-in that only does what the strategies ask of it. */
function fakeJQuery(data = new Map()) {
	const $ = element => ({
		data: key => data.get(element)?.[key],
		trigger: vi.fn(event => {
			$.triggered.push([element, event])
		}),
	})
	$.triggered = []

	return $
}

beforeEach(() => {
	registry.clear()
	delete window.jQuery
	delete window.$
	document.body.innerHTML = ''
})

describe('jquery-block strategy', () => {
	const provider = {
		handle: 'matrix',
		strategy: 'jquery-block',
		blockSelector: '.matrixblock',
		dataKey: 'entry',
		collapseMethod: 'collapse',
		expandMethod: 'expand',
		collapsedClass: 'collapsed',
	}

	it('calls through to the block objects Craft left on the elements', () => {
		document.body.innerHTML = '<div class="matrixblock a"></div><div class="matrixblock b collapsed"></div>'
		const [a, b] = [...document.querySelectorAll('.matrixblock')]
		const entries = new Map([
			[a, { entry: { collapse: vi.fn(), expand: vi.fn() } }],
			[b, { entry: { collapse: vi.fn(), expand: vi.fn() } }],
		])
		window.jQuery = fakeJQuery(entries)

		const handler = createHandler(provider)
		handler.collapse(document)
		handler.expand(document)

		expect(entries.get(a).entry.collapse).toHaveBeenCalledOnce()
		expect(entries.get(b).entry.expand).toHaveBeenCalledOnce()
	})

	it('counts the blocks and the collapsed ones among them', () => {
		document.body.innerHTML = '<div class="matrixblock"></div><div class="matrixblock collapsed"></div>'

		expect(createHandler(provider).count(document)).toEqual({ total: 2, collapsed: 1 })
	})

	it('does nothing without jQuery, and nothing on blocks without a block object', () => {
		document.body.innerHTML = '<div class="matrixblock"></div>'
		const handler = createHandler(provider)

		expect(() => handler.collapse(document)).not.toThrow()

		window.jQuery = fakeJQuery()

		expect(() => handler.collapse(document)).not.toThrow()
	})
})

describe('menu-action strategy', () => {
	const provider = {
		handle: 'menus',
		strategy: 'menu-action',
		blockSelector: '.block',
		collapseSelector: '[data-action="collapse"]',
		expandSelector: '[data-action="expand"]',
	}

	it('activates the menu item inside each block', () => {
		document.body.innerHTML = `
			<div class="block"><button data-action="collapse"></button><button data-action="expand"></button></div>
			<div class="block"><button data-action="collapse"></button></div>
		`
		window.jQuery = fakeJQuery()

		createHandler(provider).collapse(document)

		expect(window.jQuery.triggered).toHaveLength(2)
		expect(window.jQuery.triggered[0][1]).toBe('activate')
	})

	it('clicks the menu item when there is no jQuery around', () => {
		document.body.innerHTML = '<div class="block"><button data-action="collapse"></button></div>'
		const click = vi.fn()
		document.querySelector('button').addEventListener('click', click)

		createHandler(provider).collapse(document)

		expect(click).toHaveBeenCalledOnce()
	})
})

describe('class-toggle strategy', () => {
	it('adds and removes the collapsed class', () => {
		document.body.innerHTML = '<div class="super-block"></div><div class="super-block"></div>'
		const handler = createHandler({
			handle: 'super',
			strategy: 'class-toggle',
			blockSelector: '.super-block',
			collapsedClass: 'is-collapsed',
		})

		handler.collapse(document)

		expect([...document.querySelectorAll('.is-collapsed')]).toHaveLength(2)
		expect(handler.count(document)).toEqual({ total: 2, collapsed: 2 })

		handler.expand(document)

		expect([...document.querySelectorAll('.is-collapsed')]).toHaveLength(0)
	})
})

describe('js strategy', () => {
	it('hands off to whatever registered under the handler name', () => {
		const handler = createHandler({ handle: 'custom', strategy: 'js', handler: 'custom-impl' })

		// Nothing registered yet: the calls are no-ops rather than errors
		expect(() => handler.collapse(document)).not.toThrow()
		expect(handler.count(document)).toEqual({ total: 0, collapsed: 0 })

		const impl = registry.register({
			handle: 'custom-impl',
			collapse: vi.fn(),
			expand: vi.fn(),
			count: () => ({ total: 7, collapsed: 2 }),
		})

		handler.collapse(document)
		handler.expand(document)

		expect(impl.collapse).toHaveBeenCalledOnce()
		expect(impl.expand).toHaveBeenCalledOnce()
		expect(handler.count(document)).toEqual({ total: 7, collapsed: 2 })
	})

	it('refuses to point at itself', () => {
		expect(createHandler({ handle: 'loop', strategy: 'js', handler: 'loop' })).toBeNull()
	})
})

describe('createHandler', () => {
	it.each([[null], [{ handle: 'x', strategy: 'telepathy' }], [{ strategy: 'class-toggle' }]])(
		'returns null for %s',
		provider => {
			expect(createHandler(provider)).toBeNull()
		},
	)
})

describe('registerProviders', () => {
	it('registers a handler per provider and skips the ones it cannot build', () => {
		const handlers = registerProviders([
			{ handle: 'matrix', strategy: 'jquery-block', blockSelector: '.matrixblock' },
			{ handle: 'broken', strategy: 'telepathy', blockSelector: '.x' },
		])

		expect(handlers).toHaveLength(1)
		expect(registry.all().map(handler => handler.handle)).toEqual(['matrix'])
	})
})
