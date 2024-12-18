<?php

/**
 * Copyright 2022-2024 Roman Ondráček <mail@romanondracek.cz>
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

namespace App\Enums;

/**
 * Measurement time range enum
 */
enum MeasurementTimeRange: string {

	/// Last 5 minutes
	case Last5Minutes = '5m';

	/// Last 15 minutes
	case Last15Minutes = '15m';

	/// Last 1 hour
	case Last1Hour = '1h';

	/// Last 6 hours
	case Last6Hours = '6h';

	/// Last 12 hours
	case Last12Hours = '12h';

	/// Last 1 day
	case Last1Day = '1d';

	/// Last 1 week
	case Last1Week = '1w';

	/// Last 1 month
	case Last1Month = '1mo';

	/**
	 * Get the aggregation period for aggregate window
	 * @return string The aggregation period
	 */
	public function getAggregationPeriod(): string {
		return match ($this) {
			self::Last5Minutes => '1s',
			self::Last15Minutes => '3s',
			self::Last1Hour => '12s',
			self::Last6Hours => '72s',
			self::Last12Hours => '3m',
			self::Last1Day => '5m',
			self::Last1Week => '30m',
			self::Last1Month => '3h',
		};
	}

}
