<?php
declare (strict_types=1);

namespace ContentReactor\Collapse;

use Craft;
use craft\web\AssetBundle;
use craft\web\View;

class CollapseBundle extends AssetBundle
{
	public $css = [
		'style.css',
	];

	public function init(): void
	{
		$this->sourcePath = dirname(__DIR__) . '/resources';

		$craftVersion = Collapse::getInstance()->craftVersion;
		$this->js = [
			[
				"$craftVersion.js",
				'position' => View::POS_HEAD,
			],
			'script.js',
		];

		parent::init();
	}
}
