<?php
declare(strict_types=1);

namespace ContentReactor\BlockCollapse\events;

use ContentReactor\BlockCollapse\models\BlockProvider;
use yii\base\Event;

/**
 * Lets other plugins and modules add their own kinds of collapsible blocks — or drop the
 * built-in ones.
 */
class RegisterBlockProvidersEvent extends Event
{
	/**
	 * @var array<string, BlockProvider> Providers, keyed by handle
	 */
	public array $providers = [];

	/**
	 * @var int The Craft major version the providers are being collected for
	 */
	public int $craftMajorVersion = 5;
}
