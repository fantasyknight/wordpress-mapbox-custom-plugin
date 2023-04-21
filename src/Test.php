<?php

declare(strict_types=1);

namespace AgentFire\Plugin;

use AgentFire\Plugin\Test\Traits\Singleton;
use AgentFire\Plugin\Test\Rest;
use AgentFire\Plugin\Test\Admin;

/**
 * Class Test
 * @package AgentFire\Plugin\Test
 */
class Test
{
	use Singleton;

	public function __construct()
	{
		Rest::getInstance();
		// Admin::getInstance();
	}
}