<?php
declare(strict_types=1);

namespace ContentReactor\BlockCollapse;

use ContentReactor\BlockCollapse\models\Settings;
use ContentReactor\BlockCollapse\services\{
	Preferences,
	Providers,
};
use ContentReactor\BlockCollapse\web\assets\collapse\CollapseAsset;
use Craft;
use craft\base\{
	Element,
	Model,
	Plugin as BasePlugin,
};
use craft\controllers\UsersController;
use craft\events\DefineHtmlEvent;
use craft\web\View;
use yii\base\{
	ActionEvent,
	Controller,
	Event,
	InvalidConfigException,
};

/**
 * Block Collapse — collapse or expand every block on an element edit screen.
 *
 * @property-read Preferences $preferences
 * @property-read Providers $providers
 */
class Plugin extends BasePlugin
{
	public const HANDLE = 'block-collapse';

	/**
	 * @var string Name of the body param the shortcut fields are posted under
	 */
	public const PREFS_PARAM = 'blockCollapseShortcuts';

	public string $schemaVersion = '2.0.0';
	public bool $hasCpSettings = true;

	/**
	 * @var bool Whether the control panel already got the asset bundle and its config
	 */
	private bool $registeredAssets = false;

	/**
	 * @return array<string, mixed>
	 */
	public static function config(): array
	{
		return [
			'components' => [
				'preferences' => ['class' => Preferences::class],
				'providers' => ['class' => Providers::class],
			],
		];
	}

	/**
	 * The plugin instance, for the places that can't do anything useful without it.
	 */
	public static function instance(): self
	{
		$plugin = self::getInstance();

		if (!$plugin instanceof self) {
			throw new InvalidConfigException('Block Collapse isn’t installed.');
		}

		return $plugin;
	}

	public function init(): void
	{
		parent::init();

		$request = Craft::$app->getRequest();

		if ($request->getIsConsoleRequest() || !$request->getIsCpRequest()) {
			return;
		}

		$this->attachButtons();
		$this->attachUserPreferences();
	}

	public function getSettings(): Settings
	{
		$settings = parent::getSettings();

		if (!$settings instanceof Settings) {
			throw new InvalidConfigException('Block Collapse’s settings are missing.');
		}

		return $settings;
	}

	/**
	 * Everything the browser needs to know, for the signed-in user.
	 *
	 * @return array<string, mixed>
	 */
	public function getJsConfig(): array
	{
		$settings = $this->getSettings();

		return [
			'shortcuts' => $this->preferences->forCurrentUser()->getConfig(),
			'labels' => [
				'collapse' => Craft::t(self::HANDLE, 'Collapse'),
				'expand' => Craft::t(self::HANDLE, 'Expand'),
			],
			'providers' => $this->providers->getJsConfig(),
			'enableShortcuts' => $settings->enableShortcuts,
			'hideWhenEmpty' => $settings->hideWhenEmpty,
		];
	}

	protected function createSettingsModel(): ?Model
	{
		return new Settings();
	}

	protected function settingsHtml(): ?string
	{
		// The shortcut fields on this screen record keystrokes, which is the bundle's job too
		$this->registerAssets();

		return Craft::$app->getView()->renderTemplate(self::HANDLE . '/_settings.twig', [
			'plugin' => $this,
			'settings' => $this->getSettings(),
			'providers' => $this->providers->all(),
			'overrides' => array_keys(Craft::$app->getConfig()->getConfigFromFile(self::HANDLE)),
		], View::TEMPLATE_MODE_CP);
	}

	/**
	 * Adds the button group to element edit screens.
	 */
	private function attachButtons(): void
	{
		Event::on(
			Element::class,
			Element::EVENT_DEFINE_ADDITIONAL_BUTTONS,
			function(DefineHtmlEvent $event): void {
				$settings = $this->getSettings();

				if (!$settings->enableButtons && !$settings->enableShortcuts) {
					return;
				}

				$this->registerAssets();

				if ($settings->enableButtons) {
					$event->html .= Craft::$app->getView()->renderTemplate(self::HANDLE . '/_buttons.twig', [
						'shortcuts' => $this->preferences->forCurrentUser(),
					], View::TEMPLATE_MODE_CP);
				}
			}
		);
	}

	/**
	 * Adds the shortcut fields to My Account → Preferences, and saves them with the rest of the
	 * screen: Craft 5 posts them to `users/save-preferences`, Craft 4 to `users/save-user`.
	 */
	private function attachUserPreferences(): void
	{
		Craft::$app->getView()->hook('cp.users.edit.prefs', function(array &$context): string {
			if (!$this->getSettings()->allowUserOverrides) {
				return '';
			}

			$user = Craft::$app->getUser()->getIdentity();

			if ($user === null) {
				return '';
			}

			$this->registerAssets();

			return (string)Craft::$app->getView()->renderTemplate(self::HANDLE . '/_prefs.twig', [
				'shortcuts' => $this->preferences->forUser($user),
				'overridden' => $this->preferences->hasOverrides($user),
			], View::TEMPLATE_MODE_CP);
		});

		Event::on(
			UsersController::class,
			Controller::EVENT_AFTER_ACTION,
			function(ActionEvent $event): void {
				if (!in_array($event->action->id, ['save-preferences', 'save-user'], true)) {
					return;
				}

				if (!$this->getSettings()->allowUserOverrides) {
					return;
				}

				$posted = Craft::$app->getRequest()->getBodyParam(self::PREFS_PARAM);
				$user = Craft::$app->getUser()->getIdentity();

				if (!is_array($posted) || $user === null) {
					return;
				}

				// Emptying both fields is how a user goes back to the site defaults
				if (array_filter($posted, static fn(mixed $value): bool => is_string($value) && trim($value) !== '') === []) {
					$this->preferences->resetForUser($user);

					return;
				}

				$shortcuts = $this->preferences->saveForUser($user, $posted);

				if ($shortcuts->hasErrors()) {
					Craft::warning(sprintf(
						'Couldn’t save the Block Collapse shortcuts for user %d: %s',
						$user->id ?? 0,
						implode(' ', $shortcuts->getFirstErrors()),
					), self::HANDLE);

					Craft::$app->getSession()->setError(Craft::t(self::HANDLE, 'Couldn’t save your block shortcuts: {errors}', [
						'errors' => implode(' ', $shortcuts->getFirstErrors()),
					]));
				}
			}
		);
	}

	/**
	 * Registers the asset bundle, and the signed-in user's config along with it.
	 */
	private function registerAssets(): void
	{
		if ($this->registeredAssets) {
			return;
		}

		$this->registeredAssets = true;
		$view = Craft::$app->getView();
		$view->registerAssetBundle(CollapseAsset::class);
		$view->registerJsWithVars(
			fn(string $config): string => <<<JS
				window.BlockCollapse && window.BlockCollapse.start($config);
			JS,
			[$this->getJsConfig()],
			View::POS_END,
		);
	}
}
