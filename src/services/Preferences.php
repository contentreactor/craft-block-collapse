<?php
declare(strict_types=1);

namespace ContentReactor\BlockCollapse\services;

use ContentReactor\BlockCollapse\models\Shortcuts;
use ContentReactor\BlockCollapse\Plugin;
use Craft;
use craft\base\Component;
use craft\elements\User;

/**
 * Reads and writes each user's own shortcuts, stored in their Craft user preferences.
 */
class Preferences extends Component
{
	public const PREFERENCE_KEY = 'blockCollapse.shortcuts';

	/**
	 * The shortcuts the given user works with: their own, or the site-wide defaults.
	 */
	public function forUser(?User $user): Shortcuts
	{
		$settings = Plugin::instance()->getSettings();
		$defaults = $settings->getDefaultShortcuts();

		if ($user === null || !$settings->allowUserOverrides) {
			return $defaults;
		}

		return Shortcuts::fromConfig($user->getPreference(self::PREFERENCE_KEY), $defaults);
	}

	/**
	 * The shortcuts the signed-in user works with.
	 */
	public function forCurrentUser(): Shortcuts
	{
		return $this->forUser(Craft::$app->getUser()->getIdentity());
	}

	/**
	 * Whether the given user has shortcuts of their own.
	 */
	public function hasOverrides(?User $user): bool
	{
		return $user !== null && is_array($user->getPreference(self::PREFERENCE_KEY));
	}

	/**
	 * Validates and stores a user's own shortcuts.
	 *
	 * Returns the shortcuts model, which carries the validation errors if there were any.
	 *
	 * @param array<string, mixed> $config
	 */
	public function saveForUser(User $user, array $config): Shortcuts
	{
		$defaults = Plugin::instance()->getSettings()->getDefaultShortcuts();
		$shortcuts = Shortcuts::fromConfig($config, $defaults);

		if (!$shortcuts->validate()) {
			return $shortcuts;
		}

		Craft::$app->getUsers()->saveUserPreferences($user, [
			self::PREFERENCE_KEY => $shortcuts->getConfig(),
		]);

		return $shortcuts;
	}

	/**
	 * Drops a user's own shortcuts, putting them back on the site-wide defaults.
	 */
	public function resetForUser(User $user): void
	{
		Craft::$app->getUsers()->saveUserPreferences($user, [
			self::PREFERENCE_KEY => null,
		]);
	}
}
