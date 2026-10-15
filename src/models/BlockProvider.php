<?php
declare(strict_types=1);

namespace ContentReactor\BlockCollapse\models;

use ContentReactor\BlockCollapse\Plugin;
use Craft;
use craft\base\Model;

/**
 * A kind of collapsible block the plugin knows how to toggle.
 *
 * Built-in providers cover Matrix (both majors) and Neo; anything else can be added with the
 * {@see \ContentReactor\BlockCollapse\services\Providers::EVENT_REGISTER_BLOCK_PROVIDERS} event.
 */
class BlockProvider extends Model
{
	/**
	 * Call `collapse()`/`expand()` on the object stored under the block's jQuery data key —
	 * how Craft's own Matrix input and Neo expose their blocks.
	 */
	public const STRATEGY_JQUERY_BLOCK = 'jquery-block';

	/**
	 * Activate a menu item or button inside each block.
	 */
	public const STRATEGY_MENU_ACTION = 'menu-action';

	/**
	 * Add/remove a class on each block.
	 */
	public const STRATEGY_CLASS_TOGGLE = 'class-toggle';

	/**
	 * Hand off to a handler registered with `window.BlockCollapse.register()`.
	 */
	public const STRATEGY_JS = 'js';

	public string $handle = '';
	public ?string $label = null;

	/**
	 * @var list<int> Craft major versions this provider applies to
	 */
	public array $craftVersions = [4, 5];

	/**
	 * @var string CSS selector matching one collapsible block
	 */
	public string $blockSelector = '';

	public string $strategy = self::STRATEGY_JQUERY_BLOCK;

	/**
	 * @var string jQuery data key holding the block object — `jquery-block` strategy
	 */
	public string $dataKey = 'block';

	public string $collapseMethod = 'collapse';
	public string $expandMethod = 'expand';

	/**
	 * @var string|null Selector, relative to a block, of the element that collapses it —
	 * `menu-action` strategy
	 */
	public ?string $collapseSelector = null;
	public ?string $expandSelector = null;

	/**
	 * @var string Class a collapsed block carries, used to tell which blocks can still be toggled
	 */
	public string $collapsedClass = 'collapsed';

	/**
	 * @var string|null Name of the registered JS handler — `js` strategy
	 */
	public ?string $handler = null;

	public function getLabel(): string
	{
		return $this->label ?? $this->handle;
	}

	public function appliesTo(int $craftMajorVersion): bool
	{
		return in_array($craftMajorVersion, $this->craftVersions, true);
	}

	/**
	 * @return array{
	 *     handle: string,
	 *     strategy: string,
	 *     blockSelector: string,
	 *     dataKey: string,
	 *     collapseMethod: string,
	 *     expandMethod: string,
	 *     collapseSelector: string|null,
	 *     expandSelector: string|null,
	 *     collapsedClass: string,
	 *     handler: string|null,
	 * }
	 */
	public function getJsConfig(): array
	{
		return [
			'handle' => $this->handle,
			'strategy' => $this->strategy,
			'blockSelector' => $this->blockSelector,
			'dataKey' => $this->dataKey,
			'collapseMethod' => $this->collapseMethod,
			'expandMethod' => $this->expandMethod,
			'collapseSelector' => $this->collapseSelector,
			'expandSelector' => $this->expandSelector,
			'collapsedClass' => $this->collapsedClass,
			'handler' => $this->handler,
		];
	}

	public function attributeLabels(): array
	{
		return [
			'handle' => Craft::t(Plugin::HANDLE, 'Handle'),
			'blockSelector' => Craft::t(Plugin::HANDLE, 'Block selector'),
			'strategy' => Craft::t(Plugin::HANDLE, 'Strategy'),
		];
	}

	/**
	 * @return list<array<mixed>>
	 */
	protected function defineRules(): array
	{
		return [
			[['handle', 'blockSelector', 'strategy'], 'required'],
			[['strategy'], 'in', 'range' => [
				self::STRATEGY_JQUERY_BLOCK,
				self::STRATEGY_MENU_ACTION,
				self::STRATEGY_CLASS_TOGGLE,
				self::STRATEGY_JS,
			]],
			[['handler'], 'required', 'when' => fn(self $model): bool => $model->strategy === self::STRATEGY_JS],
			[['collapseSelector', 'expandSelector'], 'required', 'when' => fn(self $model): bool => $model->strategy === self::STRATEGY_MENU_ACTION],
		];
	}
}
