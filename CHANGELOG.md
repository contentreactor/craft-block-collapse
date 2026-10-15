# Release Notes for Block Collapse

## 2.0.0 - 2026-10-15

> {warning} Block Collapse is now a Craft plugin rather than a self-bootstrapping module. After
> updating, install it from **Settings** → **Plugins**, or run
> `php craft plugin/install block-collapse`. The Composer package name is unchanged.

### Added
- Every control panel user can pick their own key combos under **My Account** → **Preferences**.
- Plugin settings (**Settings** → **Plugins** → **Block Collapse**): default shortcuts, whether the
  buttons and shortcuts are enabled, and whether users may override the defaults. Overridable from
  `config/block-collapse.php`.
- Other plugins and modules can register their own collapsible block types, in PHP with
  `Providers::EVENT_REGISTER_BLOCK_PROVIDERS` or in JavaScript with `window.BlockCollapse.register()`.
- The settings screen lists every registered block type.
- A plugin icon, and an English translation file that doubles as the string catalogue.
- Unit tests for the PHP (Pest) and the control panel JavaScript (Vitest), PHPStan level 6 and
  PHP CS Fixer with the Developion coding standards.

### Changed
- The buttons are now hidden whenever the page holds nothing collapsible, instead of only on user
  edit screens.
- The buttons no longer replace other plugins' additional buttons.
- The shortcuts no longer fire while you are typing in a text field, and `Ctrl` means `⌘` on macOS.
- Button icons follow the control panel's colors, in light and in dark mode.
- The control panel assets are bundled from ES modules under
  `src/web/assets/collapse/src` and ship built in `dist`.
- Matrix and Neo are now declared as block providers rather than hard-coded, and the package
  requires PHP 8.2 or later.

### Fixed
- Collapsing and expanding Matrix blocks works again on Craft 5, where the block object moved from
  `$(block).data('block')` to `$(block).data('entry')`.
- Removed the `console.log()` calls that shipped in 1.2.0.
- Button groups in slideouts and nested element editors now only toggle their own blocks.

## 1.2.0 - 2026-09-09

### Added
- A README.

### Fixed
- Tooltips no longer linger after the button bar re-renders.

## 1.1.1 - 2025-10-18

### Fixed
- Templates root capitalization.

## 1.1.0 - 2025-10-18

### Added
- Registered the `block-collapse` translation message category, with a German translation.

## 1.0.2 - 2025-10-05

### Fixed
- Timing of the asset registration.

## 1.0.1 - 2025-09-10

### Added
- Installation instructions.

### Changed
- Reduced the tooltip delay.

## 1.0.0 - 2025-09-04

### Added
- Initial release.
