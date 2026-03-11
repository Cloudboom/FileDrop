<?php

declare(strict_types=1);

namespace OCA\FileDrop\Tests\Unit\Controller;

use OCA\FileDrop\AppInfo\Application;
use OCA\FileDrop\Controller\ApiController;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase {
	public function testIndex(): void {
		$request = $this->createMock(IRequest::class);
		$controller = new ApiController(Application::APP_ID, $request);
		$data = $controller->index()->getData();

		$this->assertSame('filedrop', $data['app']);
		$this->assertSame('baseline-ready', $data['status']);
		$this->assertSame(['31', '32', '33'], $data['supportedNextcloudVersions']);
		$this->assertSame([
			'legacy-upload-flow',
			'mail-delivery-workflow',
		], $data['missingFeatures']);
	}
}
