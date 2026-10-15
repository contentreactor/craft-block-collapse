<?php
declare(strict_types=1);

namespace ContentReactor\BlockCollapse\Tests;

use craft\console\User;
use yii\console\Application;

/**
 * Just enough of a Craft application for the plugin's models and services: a Yii application with
 * the translation category registered and a Craft version to report.
 */
class TestApplication extends Application
{
	public string $craftVersion = '5.0.0';

	public function getVersion(): string
	{
		return $this->craftVersion;
	}

	/**
	 * Craft's applications expose the signed-in user this way; Yii's don't.
	 */
	public function getUser(): User
	{
		/** @var User $user */
		$user = $this->get('user');

		return $user;
	}
}
