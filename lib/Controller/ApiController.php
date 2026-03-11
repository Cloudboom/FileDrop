<?php

declare(strict_types=1);

namespace OCA\FileDrop\Controller;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;

/**
 * @psalm-suppress UnusedClass
 */
class ApiController extends OCSController {
	/**
	 * Returns app health information for the modernized baseline.
	 *
	 * @return DataResponse<Http::STATUS_OK, array{app: string, status: string, supportedNextcloudVersions: list<string>, missingFeatures: list<string>}, array{}>
	 *
	 * 200: Health data returned
	 */
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api')]
	public function index(): DataResponse {
		return new DataResponse(
			[
				'app' => 'filedrop',
				'status' => 'baseline-ready',
				'supportedNextcloudVersions' => ['31', '32', '33'],
				'missingFeatures' => [
					'legacy-upload-flow',
					'mail-delivery-workflow',
				],
			]
		);
	}
}
