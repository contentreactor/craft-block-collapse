<?php
declare(strict_types=1);

namespace ContentReactor\BlockCollapse\services;

use ContentReactor\BlockCollapse\events\RegisterBlockProvidersEvent;
use ContentReactor\BlockCollapse\models\BlockProvider;
use ContentReactor\BlockCollapse\Plugin;
use Craft;
use craft\base\Component;
use craft\helpers\App;

/**
 * Collects the kinds of collapsible blocks the plugin acts on.
 */
class Providers extends Component
{
	/**
	 * @event RegisterBlockProvidersEvent Fired when the collapsible block providers are collected.
	 *
	 * ```php
	 * use ContentReactor\BlockCollapse\events\RegisterBlockProvidersEvent;
	 * use ContentReactor\BlockCollapse\models\BlockProvider;
	 * use ContentReactor\BlockCollapse\services\Providers;
	 * use yii\base\Event;
	 *
	 * Event::on(
	 *     Providers::class,
	 *     Providers::EVENT_REGISTER_BLOCK_PROVIDERS,
	 *     function(RegisterBlockProvidersEvent $event) {
	 *         $event->providers['super-field'] = new BlockProvider([
	 *             'handle' => 'super-field',
	 *             'label' => 'Super Field',
	 *             'blockSelector' => '.super-field .super-block',
	 *             'strategy' => BlockProvider::STRATEGY_CLASS_TOGGLE,
	 *         ]);
	 *     }
	 * );
	 * ```
	 */
	public const EVENT_REGISTER_BLOCK_PROVIDERS = 'registerBlockProviders';

	/**
	 * @var array<int, array<string, BlockProvider>> Resolved providers, per Craft major version
	 */
	private array $providers = [];

	/**
	 * The providers that apply to the given — by default the running — Craft version.
	 *
	 * @return array<string, BlockProvider>
	 */
	public function all(?int $craftMajorVersion = null): array
	{
		$craftMajorVersion ??= self::craftMajorVersion();

		if (!isset($this->providers[$craftMajorVersion])) {
			$providers = [];

			foreach ($this->defaultProviders() as $provider) {
				if ($provider->appliesTo($craftMajorVersion)) {
					$providers[$provider->handle] = $provider;
				}
			}

			$event = new RegisterBlockProvidersEvent([
				'providers' => $providers,
				'craftMajorVersion' => $craftMajorVersion,
			]);
			$this->trigger(self::EVENT_REGISTER_BLOCK_PROVIDERS, $event);

			$this->providers[$craftMajorVersion] = $event->providers;
		}

		return $this->providers[$craftMajorVersion];
	}

	/**
	 * The providers, ready to be handed to the browser.
	 *
	 * @return list<array<string, mixed>>
	 */
	public function getJsConfig(?int $craftMajorVersion = null): array
	{
		return array_values(array_map(
			static fn(BlockProvider $provider): array => $provider->getJsConfig(),
			$this->all($craftMajorVersion),
		));
	}

	/**
	 * The major version of the running Craft install.
	 */
	public static function craftMajorVersion(): int
	{
		return (int)explode('.', App::normalizeVersion(Craft::$app->getVersion()))[0];
	}

	/**
	 * The providers that ship with the plugin.
	 *
	 * @return list<BlockProvider>
	 */
	protected function defaultProviders(): array
	{
		return [
			new BlockProvider([
				'handle' => 'matrix',
				'label' => Craft::t(Plugin::HANDLE, 'Matrix'),
				'craftVersions' => [4],
				'blockSelector' => '.matrix-field .blocks .matrixblock',
				'strategy' => BlockProvider::STRATEGY_JQUERY_BLOCK,
				'dataKey' => 'block',
			]),
			new BlockProvider([
				'handle' => 'matrix',
				'label' => Craft::t(Plugin::HANDLE, 'Matrix'),
				'craftVersions' => [5, 6],
				'blockSelector' => '.matrix .blocks .matrixblock',
				'strategy' => BlockProvider::STRATEGY_JQUERY_BLOCK,
				// Craft 5 renders Matrix blocks as nested entries, so the block object moved
				'dataKey' => 'entry',
			]),
			new BlockProvider([
				'handle' => 'neo',
				'label' => Craft::t(Plugin::HANDLE, 'Neo'),
				'craftVersions' => [4, 5, 6],
				'blockSelector' => '.ni_blocks .ni_block',
				'strategy' => BlockProvider::STRATEGY_JQUERY_BLOCK,
				'dataKey' => 'block',
			]),
		];
	}
}
