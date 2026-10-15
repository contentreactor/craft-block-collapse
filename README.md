# Block Collapse

<img src="src/icon.svg" alt="" width="64" height="64" align="right">

A Craft CMS control panel utility that collapses or expands **every block on an element edit
screen** at once — from a button or from a keyboard shortcut each user picks for themselves.

Editing an entry with long Matrix or Neo fields means a lot of scrolling and a lot of clicking
blocks open and closed one at a time. Block Collapse adds two buttons to the element's button bar
that toggle all of them, and gets out of the way on pages that have nothing to collapse.

## Requirements

- Craft CMS 4.4+ or 5.0+
- PHP 8.2+

## Installation

From the **Plugin Store** in your control panel, or with Composer:

```bash
composer require contentreactor/craft-block-collapse
php craft plugin/install block-collapse
```

With DDEV:

```bash
ddev composer require contentreactor/craft-block-collapse
ddev craft plugin/install block-collapse
```

> **Upgrading from 1.x:** Block Collapse used to be a self-bootstrapping module. It is a plugin
> now, so after updating install it once — from **Settings** → **Plugins**, or with
> `php craft plugin/install block-collapse`. The Composer package name hasn't changed.

## Usage

Open any element edit screen that holds collapsible blocks. Two buttons appear at the top of the
page:

- **Collapse** — collapses every block on the page
- **Expand** — expands every block on the page

Both have a keyboard shortcut, `Ctrl + ,` and `Ctrl + .` out of the box (`⌘` instead of `Ctrl` on
macOS). Inside a slideout or a nested element editor, the buttons and the shortcuts only act on the
blocks of the editor you're in.

### Per-user shortcuts

Each user picks their own combos under **My Account** → **Preferences**. Click a shortcut field and
press the keys you want; the field fills itself in. A shortcut needs `Ctrl`/`⌘` or `Alt` plus one
other key, the two shortcuts have to differ, and combos the browser owns (`Ctrl + S` and friends)
are turned down.

Shortcuts are stored as text — `ctrl+comma`, `alt+shift+k` — so they can also be typed by hand.

### Settings

**Settings** → **Plugins** → **Block Collapse**, or in `config/block-collapse.php`:

```php
<?php

return [
	// Defaults for users who haven't picked their own
	'collapseShortcut' => 'ctrl+comma',
	'expandShortcut' => 'ctrl+period',
	// Add the buttons to element edit screens
	'enableButtons' => true,
	// Bind the keyboard shortcuts
	'enableShortcuts' => true,
	// Let users override the defaults under My Account → Preferences
	'allowUserOverrides' => true,
	// Hide the buttons on pages without collapsible blocks
	'hideWhenEmpty' => true,
];
```

Anything set in the config file is locked on the settings screen, as usual in Craft.

## Supported blocks

Matrix (Craft 4 and 5) and [Neo](https://plugins.craftcms.com/neo) are supported out of the box.
The settings screen lists everything that is currently registered.

## Adding your own collapsible blocks

Most field types can be described rather than coded. Register a provider and Block Collapse does
the rest:

```php
use ContentReactor\BlockCollapse\events\RegisterBlockProvidersEvent;
use ContentReactor\BlockCollapse\models\BlockProvider;
use ContentReactor\BlockCollapse\services\Providers;
use yii\base\Event;

Event::on(
	Providers::class,
	Providers::EVENT_REGISTER_BLOCK_PROVIDERS,
	function(RegisterBlockProvidersEvent $event) {
		$event->providers['super-field'] = new BlockProvider([
			'handle' => 'super-field',
			'label' => 'Super Field',
			'blockSelector' => '.super-field .super-block',
			'strategy' => BlockProvider::STRATEGY_CLASS_TOGGLE,
			'collapsedClass' => 'is-collapsed',
		]);
	}
);
```

`$event->providers` is keyed by handle, so you can also replace or `unset()` a built-in one.
`$event->craftMajorVersion` tells you which Craft you're being asked about.

### Strategies

| Strategy | What it does | Fields it uses |
| --- | --- | --- |
| `jquery-block` | Calls `collapse()`/`expand()` on the block object the field left on the element's jQuery data — how Matrix and Neo work | `dataKey`, `collapseMethod`, `expandMethod` |
| `menu-action` | Activates a menu item or button inside each block | `collapseSelector`, `expandSelector` |
| `class-toggle` | Adds and removes a class on each block | `collapsedClass` |
| `js` | Hands off to a handler you registered in JavaScript | `handler` |

Every strategy uses `blockSelector` to find the blocks and `collapsedClass` to tell which of them
are already collapsed.

### Doing it in JavaScript

For anything that needs real logic, register a handler from an asset bundle that depends on
`ContentReactor\BlockCollapse\web\assets\collapse\CollapseAsset`:

```js
window.BlockCollapse.register({
	handle: 'super-field',
	collapse(root) {
		root.querySelectorAll('.super-block').forEach(block => block.superBlock.collapse())
	},
	expand(root) {
		root.querySelectorAll('.super-block').forEach(block => block.superBlock.expand())
	},
	// Optional — it's what the buttons use to decide whether they have anything to do
	count(root) {
		const blocks = [...root.querySelectorAll('.super-block')]

		return { total: blocks.length, collapsed: blocks.filter(block => block.superBlock.collapsed).length }
	},
})
```

`root` is the form the button group belongs to, so a handler should never reach outside it.
Registering under a handle that is already taken replaces that handler, which is how you swap out a
built-in one.

## Localization

Button labels, tooltips and settings are translatable through the `block-collapse` message
category. English and German ship with the plugin; `src/translations/en/block-collapse.php` lists
every string, so it's the file to copy for a new language.

## Development

```bash
composer install && npm install

composer check-cs   # PHP CS Fixer (Developion coding standards)
composer phpstan    # PHPStan, level 6
composer test       # Pest
npm test            # Vitest
npm run build       # Rebuild src/web/assets/collapse/dist
```

The control panel JavaScript lives in `src/web/assets/collapse/src` and is bundled with Rollup;
`dist` is committed because it is what the Composer package ships. Run `npm run build` before
tagging a release.

## License

[MIT](LICENSE.md)
