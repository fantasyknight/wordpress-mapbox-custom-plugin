<?php

declare(strict_types=1);

namespace AgentFire\Plugin\Test\Traits;

/**
 * Trait Singleton
 * @package AgentFire\Plugin\Test
 */
trait Singleton
{
	/**
	 * @return self;
	 */

	public static function getInstance()
	{
		static $_instance = null;
		$class = __CLASS__;

		return $_instance ?: $_instance = new $class;
	}

	private function __construct()
	{
	}

	public function __clone()
	{
	}

	public function __wakeup()
	{
	}
}