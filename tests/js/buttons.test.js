import { beforeEach, describe, expect, it, vi } from 'vitest'
import * as buttons from '../../src/web/assets/collapse/src/buttons.js'
import * as registry from '../../src/web/assets/collapse/src/registry.js'

const TOGGLE = `
	<form id="outer">
		<div class="btngroup bc-toggle bc-pending" data-bc-toggle>
			<button type="button" data-bc-action="collapse" data-bc-label="Collapse"></button>
			<button type="button" data-bc-action="expand" data-bc-label="Expand"></button>
		</div>
		<div class="matrixblock"></div>
		<div class="matrixblock collapsed"></div>
	</form>
`

const matrixHandler = () => ({
	handle: 'matrix',
	collapse: vi.fn(),
	expand: vi.fn(),
	count: root => {
		const blocks = [...root.querySelectorAll('.matrixblock')]

		return { total: blocks.length, collapsed: blocks.filter(block => block.classList.contains('collapsed')).length }
	},
})

beforeEach(() => {
	registry.clear()
	document.body.innerHTML = ''
})

describe('rootFor', () => {
	it('scopes a button group to its own form', () => {
		document.body.innerHTML = TOGGLE

		expect(buttons.rootFor(document.querySelector('[data-bc-action]'))).toBe(document.getElementById('outer'))
	})

	it('falls back to the whole document', () => {
		document.body.innerHTML = '<div data-bc-toggle></div>'

		expect(buttons.rootFor(document.querySelector('[data-bc-toggle]'))).toBe(document)
		expect(buttons.rootFor(null)).toBe(document)
	})
})

describe('refresh', () => {
	it('reveals a button group that has blocks to toggle', () => {
		document.body.innerHTML = TOGGLE
		registry.register(matrixHandler())

		buttons.refresh({ shortcuts: { collapse: 'ctrl+comma', expand: 'ctrl+period' }, hideWhenEmpty: true })

		expect(document.querySelector('[data-bc-toggle]').classList.contains('bc-pending')).toBe(false)
	})

	it('hides a button group with nothing to toggle', () => {
		document.body.innerHTML = TOGGLE.replace(/<div class="matrixblock[^"]*"><\/div>/g, '')
		registry.register(matrixHandler())

		buttons.refresh({ hideWhenEmpty: true })

		expect(document.querySelector('[data-bc-toggle]').classList.contains('bc-pending')).toBe(true)
	})

	it('keeps the buttons up when asked to, blocks or not', () => {
		document.body.innerHTML = TOGGLE.replace(/<div class="matrixblock[^"]*"><\/div>/g, '')

		buttons.refresh({ hideWhenEmpty: false })

		expect(document.querySelector('[data-bc-toggle]').classList.contains('bc-pending')).toBe(false)
	})

	it('labels the buttons with the user’s own shortcut', () => {
		document.body.innerHTML = TOGGLE
		registry.register(matrixHandler())

		buttons.refresh({ shortcuts: { collapse: 'alt+1', expand: 'alt+2' }, hideWhenEmpty: false })

		const collapse = document.querySelector('[data-bc-action="collapse"]')

		expect(collapse.dataset.bcShortcut).toBe('alt+1')
		expect(collapse.dataset.bcTooltip).toMatch(/^Collapse \(Alt \+ 1\)$/)
		expect(collapse.getAttribute('aria-label')).toBe(collapse.dataset.bcTooltip)
	})

	it('leaves the shortcut out of the label when shortcuts are off', () => {
		document.body.innerHTML = TOGGLE

		buttons.refresh({ shortcuts: { collapse: 'alt+1' }, enableShortcuts: false, hideWhenEmpty: false })

		const collapse = document.querySelector('[data-bc-action="collapse"]')

		expect(collapse.dataset.bcShortcut).toBe('')
		expect(collapse.dataset.bcTooltip).toBe('Collapse')
	})
})

describe('activeToggle', () => {
	it('picks the innermost button group on screen', () => {
		document.body.innerHTML = `
			<div class="bc-toggle bc-pending" data-bc-toggle id="hidden"></div>
			<div class="bc-toggle" data-bc-toggle id="page"></div>
			<div class="bc-toggle" data-bc-toggle id="slideout"></div>
		`

		expect(buttons.activeToggle(document).id).toBe('slideout')
	})

	it('has nothing to act on when every group is hidden', () => {
		document.body.innerHTML = '<div class="bc-toggle bc-pending" data-bc-toggle></div>'

		expect(buttons.activeToggle()).toBeNull()
	})
})

describe('listen', () => {
	/** Each test listens on its own container, so listeners don't pile up on the document. */
	const container = html => {
		const element = document.createElement('div')
		element.innerHTML = html
		document.body.appendChild(element)
		buttons.listen(element)

		return element
	}

	it('runs the action of whichever button was clicked, scoped to its form', () => {
		const element = container(TOGGLE)
		const handler = registry.register(matrixHandler())

		element.querySelector('[data-bc-action="collapse"]').click()
		element.querySelector('[data-bc-action="expand"]').click()

		expect(handler.collapse).toHaveBeenCalledWith(element.querySelector('#outer'))
		expect(handler.expand).toHaveBeenCalledWith(element.querySelector('#outer'))
	})

	it('ignores clicks elsewhere', () => {
		const element = container(`${TOGGLE}<button id="other"></button>`)
		const handler = registry.register(matrixHandler())

		element.querySelector('#other').click()

		expect(handler.collapse).not.toHaveBeenCalled()
	})
})
