<?php
declare(strict_types=1);

use ContentReactor\BlockCollapse\events\RegisterBlockProvidersEvent;
use ContentReactor\BlockCollapse\models\BlockProvider;
use ContentReactor\BlockCollapse\services\Providers;

test('it ships providers for Matrix and Neo', function () {
	$providers = (new Providers())->all(5);

	expect(array_keys($providers))->toBe(['matrix', 'neo'])
		->and($providers['matrix']->blockSelector)->toBe('.matrix .blocks .matrixblock')
		->and($providers['matrix']->strategy)->toBe(BlockProvider::STRATEGY_JQUERY_BLOCK);
});

test('it knows Matrix blocks moved between Craft 4 and 5', function () {
	$service = new Providers();

	// Craft 5 renders Matrix blocks as nested entries, so the block object sits elsewhere
	expect($service->all(4)['matrix']->dataKey)->toBe('block')
		->and($service->all(4)['matrix']->blockSelector)->toBe('.matrix-field .blocks .matrixblock')
		->and($service->all(5)['matrix']->dataKey)->toBe('entry');
});

test('it reads the major version off the running Craft install', function () {
	expect(withCraftVersion('4.18.8', fn() => Providers::craftMajorVersion()))->toBe(4)
		->and(withCraftVersion('5.11.5.1', fn() => Providers::craftMajorVersion()))->toBe(5)
		->and(withCraftVersion('6.0.0-alpha.20', fn() => Providers::craftMajorVersion()))->toBe(6);
});

test('it defaults to the running Craft version', function () {
	$service = new Providers();

	expect(withCraftVersion('4.18.8', fn() => $service->all()['matrix']->dataKey))->toBe('block');
});

test('it lets plugins add their own kinds of blocks', function () {
	$service = new Providers();
	$service->on(
		Providers::EVENT_REGISTER_BLOCK_PROVIDERS,
		function (RegisterBlockProvidersEvent $event) {
			expect($event->craftMajorVersion)->toBe(5);

			$event->providers['super-field'] = new BlockProvider([
				'handle' => 'super-field',
				'label' => 'Super Field',
				'blockSelector' => '.super-field .super-block',
				'strategy' => BlockProvider::STRATEGY_CLASS_TOGGLE,
			]);
		}
	);

	expect(array_keys($service->all(5)))->toContain('super-field');
});

test('it lets plugins drop the built-in ones', function () {
	$service = new Providers();
	$service->on(
		Providers::EVENT_REGISTER_BLOCK_PROVIDERS,
		function (RegisterBlockProvidersEvent $event) {
			unset($event->providers['neo']);
		}
	);

	expect(array_keys($service->all(5)))->toBe(['matrix']);
});

test('it hands the browser a flat list', function () {
	$config = (new Providers())->getJsConfig(5);

	expect($config)->toHaveCount(2)
		->and($config[0])->toHaveKeys([
			'handle',
			'strategy',
			'blockSelector',
			'dataKey',
			'collapseMethod',
			'expandMethod',
			'collapseSelector',
			'expandSelector',
			'collapsedClass',
			'handler',
		])
		->and($config[0]['handle'])->toBe('matrix');
});
