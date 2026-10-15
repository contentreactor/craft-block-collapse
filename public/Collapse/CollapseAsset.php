<?php
declare(strict_types=1);

namespace ContentReactor\BlockCollapse\Web\Assets\Collapse;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * The control panel's collapse/expand behaviour.
 *
 * Built from `assets/Collapse/src`; see the README.
 */
class CollapseAsset extends AssetBundle
{
	public $sourcePath = __DIR__ . '/dist';

	public $depends = [
		CpAsset::class,
	];

	public $js = [
		'js/app.js',
	];

	public $css = [
		'css/app.css',
	];
}
