<?php
/**
 * @author Tom Needham <tom@owncloud.com>
 *
 * @copyright Copyright (c) 2018, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace Test\Repair;
use OC\Helper\EnvironmentHelper;
use OC\Repair\Apps;
use OCP\App\IAppManager;
use OCP\IConfig;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Test\TestCase;

/**
 * Tests to check version comparison
 *
 * @see \OC\Repair\AppsTest
 */
class AppsTest extends TestCase {

	/** @var Apps */
	protected $repair;
	/** @var IAppManager | \PHPUnit_Framework_MockObject_MockObject */
	protected $appManager;
	/** @var  EventDispatcherInterface | \PHPUnit_Framework_MockObject_MockObject */
	protected $eventDispatcher;
	/** @var  IConfig | \PHPUnit_Framework_MockObject_MockObject*/
	protected $config;
	/** @var \OC_Defaults | \PHPUnit_Framework_MockObject_MockObject */
	private $defaults;
	/** @var EnvironmentHelper | \PHPUnit_Framework_MockObject_MockObject */
	private $environmentHelper;

	protected function setUp() {
		parent::setUp();
		$this->appManager = $this->createMock(IAppManager::class);
		$this->defaults = $this->createMock(\OC_Defaults::class);
		$this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
		$this->config = $this->createMock(IConfig::class);
		$this->environmentHelper = $this->createMock(EnvironmentHelper::class);
		$this->repair = new Apps(
			$this->appManager,
			$this->eventDispatcher,
			$this->config,
			$this->defaults,
			$this->environmentHelper
		);
	}

	public function testMarketEnableVersionCompare10() {
		$this->config->expects($this->once())->method('getSystemValue')->with('version', '0.0.0')->willReturn('10.0.0');
		$this->assertTrue($this->invokePrivate($this->repair, 'requiresMarketEnable'));
	}

	public function testMarketEnableVersionCompare9() {
		$this->config->expects($this->once())->method('getSystemValue')->with('version', '0.0.0')->willReturn('9.1.5');
		$this->assertTrue($this->invokePrivate($this->repair, 'requiresMarketEnable'));
	}

	public function testMarketEnableVersionCompareFuture() {
		$this->config->expects($this->once())->method('getSystemValue')->with('version', '0.0.0')->willReturn('10.0.2');
		$this->assertFalse($this->invokePrivate($this->repair, 'requiresMarketEnable'));
	}

	public function testMarketEnableVersionCompareCurrent() {
		$this->config->expects($this->once())->method('getSystemValue')->with('version', '0.0.0')->willReturn('10.0.1');
		$this->assertFalse($this->invokePrivate($this->repair, 'requiresMarketEnable'));
	}
}
