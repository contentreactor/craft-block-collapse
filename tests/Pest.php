<?php
declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

/**
 * A booted plugin instance, the way Craft builds one.
 *
 * @param array<string, mixed> $settings
 */
function plugin(array $settings = []): ContentReactor\BlockCollapse\Plugin
{
	$plugin = new ContentReactor\BlockCollapse\Plugin(
		ContentReactor\BlockCollapse\Plugin::HANDLE,
		Craft::$app,
		ContentReactor\BlockCollapse\Plugin::config(),
	);

	if ($settings !== []) {
		$plugin->setSettings($settings);
	}

	return $plugin;
}

/**
 * Pretends Craft reports the given version, for the duration of the callback.
 */
function withCraftVersion(string $version, callable $callback): mixed
{
	/** @var ContentReactor\BlockCollapse\Tests\TestApplication $app */
	$app = Craft::$app;
	$previous = $app->craftVersion;
	$app->craftVersion = $version;

	try {
		return $callback();
	} finally {
		$app->craftVersion = $previous;
	}
}
