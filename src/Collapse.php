<?php
declare (strict_types=1);

namespace ContentReactor\Collapse;

use Craft;
use craft\base\Element;
use craft\elements\User;
use craft\events\DefineHtmlEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\i18n\PhpMessageSource;
use craft\web\Application as CraftWebApp;
use craft\web\View;
use yii\base\BootstrapInterface;
use yii\base\Event;
use yii\base\InvalidConfigException;
use yii\base\Module;

class Collapse extends Module implements BootstrapInterface
{
	public const ID = 'craft-block-collapse';
	public string $craftVersion;

	public function construct($id = self::ID, $parent = null, array $config = []): void
	{
		parent::__construct($id, $parent, $config);
	}

	public function bootstrap($app): void
	{
		if (!$app instanceof CraftWebApp) return;
		if (!Craft::$app->getRequest()->getIsCpRequest()) return;

		static::setInstance($this);
		Craft::$app->setModule($this->id, $this);

		$craftVersion = (int)explode('.', Craft::$app->getVersion(), 1)[0];
		$this->craftVersion = match ($craftVersion) {
			4 => 'v4',
			5 => 'v5',
			default => throw new InvalidConfigException('Unsupported version of CraftCMS.'),
		};

		Event::on(
			Element::class,
			Element::EVENT_DEFINE_ADDITIONAL_BUTTONS,
			static function (DefineHtmlEvent $event) {
				if (!$event->sender instanceof Element || $event->sender instanceof User) return;
				CollapseBundle::register(Craft::$app->getView());
				$event->html = Craft::$app->getView()->renderTemplate('@block-collapse/collapse-toggle.twig');
			}
		);

		Event::on(
			View::class,
			View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
			static function (RegisterTemplateRootsEvent $event): void {
				$event->roots['@block-collapse'] = __DIR__ . '/Templates';
			}
		);

		Craft::$app->i18n->translations['block-collapse'] = [
			'class' => PhpMessageSource::class,
			'basePath' => __DIR__ . '/Translations',
			'allowOverrides' => true,
		];
	}
}
