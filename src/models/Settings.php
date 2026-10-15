<?php
declare(strict_types=1);

namespace ContentReactor\BlockCollapse\models;

use ContentReactor\BlockCollapse\helpers\Keymap;
use ContentReactor\BlockCollapse\Plugin;
use Craft;
use craft\base\Model;

/**
 * The plugin's project config settings, overridable from `config/block-collapse.php`.
 */
class Settings extends Model
{
	public string $collapseShortcut = 'ctrl+comma';
	public string $expandShortcut = 'ctrl+period';
	public bool $enableButtons = true;
	public bool $enableShortcuts = true;
	public bool $allowUserOverrides = true;
	public bool $hideWhenEmpty = true;

	/**
	 * The site-wide default shortcuts, used by every user who hasn't picked their own.
	 */
	public function getDefaultShortcuts(): Shortcuts
	{
		return new Shortcuts([
			'collapse' => $this->collapseShortcut,
			'expand' => $this->expandShortcut,
		]);
	}

	public function attributeLabels(): array
	{
		return [
			'collapseShortcut' => Craft::t(Plugin::HANDLE, 'Default “collapse all” shortcut'),
			'expandShortcut' => Craft::t(Plugin::HANDLE, 'Default “expand all” shortcut'),
			'enableButtons' => Craft::t(Plugin::HANDLE, 'Show the buttons'),
			'enableShortcuts' => Craft::t(Plugin::HANDLE, 'Enable the keyboard shortcuts'),
			'allowUserOverrides' => Craft::t(Plugin::HANDLE, 'Let users pick their own shortcuts'),
			'hideWhenEmpty' => Craft::t(Plugin::HANDLE, 'Hide the buttons when there is nothing to collapse'),
		];
	}

	public function attributeHints(): array
	{
		return [
			'enableButtons' => Craft::t(Plugin::HANDLE, 'Add the collapse/expand buttons to element edit screens.'),
			'allowUserOverrides' => Craft::t(Plugin::HANDLE, 'Users can override the defaults under My Account → Preferences.'),
			'hideWhenEmpty' => Craft::t(Plugin::HANDLE, 'Element edit screens without any collapsible blocks won’t show the buttons.'),
		];
	}

	/**
	 * @return list<array<mixed>>
	 */
	protected function defineRules(): array
	{
		return [
			[['collapseShortcut', 'expandShortcut'], 'required'],
			[['collapseShortcut', 'expandShortcut'], 'validateShortcut'],
			[['enableButtons', 'enableShortcuts', 'allowUserOverrides', 'hideWhenEmpty'], 'boolean'],
			[['collapseShortcut'], 'compare', 'compareAttribute' => 'expandShortcut', 'operator' => '!=', 'message' => Craft::t(Plugin::HANDLE, 'The two shortcuts must be different.')],
		];
	}

	public function validateShortcut(string $attribute): void
	{
		/** @var string|null $value */
		$value = $this->$attribute;

		if (!Keymap::isValid($value)) {
			$this->addError($attribute, Craft::t(Plugin::HANDLE, 'Pick a key combined with Ctrl/⌘ or Alt.'));

			return;
		}

		if (Keymap::isReserved($value)) {
			$this->addError($attribute, Craft::t(Plugin::HANDLE, '{shortcut} is reserved by the browser.', [
				'shortcut' => Keymap::label($value),
			]));
		}
	}
}
