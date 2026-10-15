<?php
declare(strict_types=1);

namespace ContentReactor\BlockCollapse\web\assets\collapse;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * The control panel's collapse/expand behaviour.
 */
class CollapseAsset extends AssetBundle
{
	public $sourcePath = __DIR__ . '/dist';

	public $depends = [
		CpAsset::class,
	];

	public $js = [
		'collapse.js',
	];

	public $css = [
		'collapse.css',
	];
}
