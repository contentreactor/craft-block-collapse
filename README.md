# Craft Block Collapse

A small Craft CMS control panel utility that adds **collapse all / expand all**
buttons for Matrix and Neo blocks to an element's edit screen.

Editing entries with long Matrix or Neo fields means a lot of scrolling and
clicking each block open/closed one at a time. This plugin adds two buttons
to the element's button bar that collapse or expand every block at once,
plus keyboard shortcuts to do the same without leaving the keyboard.

## Requirements

- Craft CMS `^4.4` or `^5.8`

## Installation

```bash
composer require contentreactor/craft-block-collapse
```

```bash
ddev composer require contentreactor/craft-block-collapse
```

The plugin is a Yii2 module that bootstraps itself — there's nothing to
install or enable in the control panel.

## Usage

Open any element edit screen that contains a Matrix or Neo field. Two
buttons appear in the button bar (top of the page):

- **Collapse** — collapses every Matrix/Neo block on the page
- **Expand** — expands every Matrix/Neo block on the page

### Keyboard shortcuts

| Shortcut | Action  |
| -------- | ------- |
| `Ctrl+,` | Collapse all |
| `Ctrl+.` | Expand all |

The buttons are hidden on user edit screens, since they don't have
Matrix/Neo fields to toggle.

## Localization

Button labels and tooltips are translatable via the `block-collapse`
message category. A German translation is included out of the box; add
your own by overriding the category in your site's translations.

## License

MIT
