<?php

/**
 * TEST: App\CoreModule\Enums\MeasurementTimeRange
 * @covers App\CoreModule\Enums\MeasurementTimeRange
 * @phpVersion >= 8.1
 * @testCase
 *
 * Copyright 2022-2023 Roman Ondráček
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

declare(strict_types = 1);

namespace Tests\Unit\CoreModule\Enums;

use App\CoreModule\Enums\MeasurementTimeRange;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../bootstrap.php';

/**
 * Tests for measurement time range enum
 */
final class MeasurementTimeRangeTest extends TestCase {

	/**
	 * Returns data for testing the method 'getAggregationPeriod'
	 * @return array<array<MeasurementTimeRange|string>> Measurement time range and aggregation period
	 */
	public function getAggregationPeriodData(): array {
		return [
			[MeasurementTimeRange::Last5Minutes, '1s'],
			[MeasurementTimeRange::Last15Minutes, '3s'],
			[MeasurementTimeRange::Last1Hour, '12s'],
			[MeasurementTimeRange::Last6Hours, '72s'],
			[MeasurementTimeRange::Last12Hours, '3m'],
			[MeasurementTimeRange::Last1Day, '5m'],
			[MeasurementTimeRange::Last1Week, '30m'],
			[MeasurementTimeRange::Last1Month, '3h'],
		];
	}

	/**
	 * Tests the method 'getAggregationPeriod'
	 * @dataProvider getAggregationPeriodData
	 * @param MeasurementTimeRange $timeRange Measurement time range
	 * @param string $period Aggregation period
	 */
	public function testGetAggregationPeriod(MeasurementTimeRange $timeRange, string $period): void {
		Assert::same($period, $timeRange->getAggregationPeriod());
	}

}

$test = new MeasurementTimeRangeTest();
$test->run();
