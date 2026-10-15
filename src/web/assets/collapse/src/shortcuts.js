/**
 * The keyboard shortcuts.
 *
 * Craft's own shortcut manager is layer-scoped and fires inside text fields, so the plugin keeps
 * its own listener: it ignores keystrokes aimed at a shortcut recorder, and it acts on the
 * innermost button group on screen, which is the one belonging to the editor the user is in.
 */

import * as buttons from './buttons.js'
import * as keymap from './keymap.js'
import { RECORDER_SELECTOR } from './recorder.js'
import * as registry from './registry.js'

export function listen(getConfig, doc = document) {
	doc.addEventListener('keydown', event => {
		const config = getConfig() || {}

		if (config.enableShortcuts === false || event.repeat || event.isComposing) {
			return
		}

		// Don't steal the keystroke from someone recording a new shortcut
		if (event.target && typeof event.target.closest === 'function' && event.target.closest(RECORDER_SELECTOR)) {
			return
		}

		const shortcuts = config.shortcuts || {}
		const action = ['collapse', 'expand'].find(name => keymap.matchesEvent(shortcuts[name], event))

		if (!action) {
			return
		}

		// The shortcuts work with the buttons switched off, so they go by what is on the page
		const toggle = buttons.activeToggle(doc)
		const root = toggle ? buttons.rootFor(toggle) : doc

		if (config.hideWhenEmpty !== false && registry.count(root).total === 0) {
			return
		}

		event.preventDefault()

		if (action === 'collapse') {
			buttons.collapseAll(root)
		} else {
			buttons.expandAll(root)
		}
	})
}
