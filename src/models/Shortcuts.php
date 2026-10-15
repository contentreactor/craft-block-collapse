<?php
declare(strict_types=1);

namespace ContentReactor\BlockCollapse\models;

use ContentReactor\BlockCollapse\helpers\Keymap;
use ContentReactor\BlockCollapse\Plugin;
use Craft;
use craft\base\Model;

/**
 * One user's pair of keyboard shortcuts.
 */
class Shortcuts extends Model
{
	public ?string $collapse = null;
	public ?string $expand = null;

	/**
	 * Builds a set of shortcuts from whatever is stored in a user's preferences, falling back to
	 * the given defaults for anything missing or unusable.
	 *
	 * @param mixed $config The stored value — normally an array with `collapse` and `expand` keys
	 */
	public static function fromConfig(mixed $config, ?self $defaults = null): self
	{
		$config = is_array($config) ? $config : [];
		$shortcuts = new self();

		foreach (['collapse', 'expand'] as $attribute) {
			$value = $config[$attribute] ?? null;
			$shortcuts->$attribute = Keymap::normalize(is_string($value) ? $value : null)
				?? ($defaults !== null ? $defaults->$attribute : null);
		}

		return $shortcuts;
	}

	public function init(): void
	{
		parent::init();

		$this->collapse = Keymap::normalize($this->collapse) ?? $this->collapse;
		$this->expand = Keymap::normalize($this->expand) ?? $this->expand;
	}

	/**
	 * @return array{collapse: string|null, expand: string|null}
	 */
	public function getConfig(): array
	{
		return [
			'collapse' => $this->collapse,
			'expand' => $this->expand,
		];
	}

	/**
	 * @return array{collapse: string, expand: string}
	 */
	public function getLabels(bool $apple = false): array
	{
		return [
			'collapse' => Keymap::label($this->collapse, $apple),
			'expand' => Keymap::label($this->expand, $apple),
		];
	}

	public function attributeLabels(): array
	{
		return [
			'collapse' => Craft::t(Plugin::HANDLE, 'Collapse all blocks'),
			'expand' => Craft::t(Plugin::HANDLE, 'Expand all blocks'),
		];
	}

	/**
	 * @return list<array<mixed>>
	 */
	protected function defineRules(): array
	{
		return [
			[['collapse', 'expand'], 'required'],
			[['collapse', 'expand'], 'validateShortcut'],
			[['collapse'], 'compare', 'compareAttribute' => 'expand', 'operator' => '!=', 'message' => Craft::t(Plugin::HANDLE, 'The two shortcuts must be different.')],
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
