<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
 */

declare(strict_types=1);

namespace pocketmine\block\utils;

use pocketmine\data\bedrock\block\BlockLegacyMetadata;
use pocketmine\math\Facing;

final class RailConnectionInfo{

	public const FLAG_ASCEND = 1 << 24; //used to indicate direction-up

	public static function CONNECTIONS() : array{
		return [
			//straights
			BlockLegacyMetadata::RAIL_STRAIGHT_NORTH_SOUTH => [
				new RailConnectionInfo(Facing::NORTH),
				new RailConnectionInfo(Facing::SOUTH)
			],
			BlockLegacyMetadata::RAIL_STRAIGHT_EAST_WEST => [
				new RailConnectionInfo(Facing::EAST),
				new RailConnectionInfo(Facing::WEST)
			],

			//ascending
			BlockLegacyMetadata::RAIL_ASCENDING_EAST => [
				new RailConnectionInfo(Facing::WEST),
				new RailConnectionInfo(Facing::EAST, true)
			],
			BlockLegacyMetadata::RAIL_ASCENDING_WEST => [
				new RailConnectionInfo(Facing::EAST),
				new RailConnectionInfo(Facing::WEST, true)
			],
			BlockLegacyMetadata::RAIL_ASCENDING_NORTH => [
				new RailConnectionInfo(Facing::SOUTH),
				new RailConnectionInfo(Facing::NORTH, true)
			],
			BlockLegacyMetadata::RAIL_ASCENDING_SOUTH => [
				new RailConnectionInfo(Facing::NORTH),
				new RailConnectionInfo(Facing::SOUTH, true)
			]
		];
	}

	/* extended meta values for regular rails, to allow curving */
	public static function CURVE_CONNECTIONS() : array{
		return [
			BlockLegacyMetadata::RAIL_CURVE_SOUTHEAST => [
				new RailConnectionInfo(Facing::SOUTH),
				new RailConnectionInfo(Facing::EAST)
			],
			BlockLegacyMetadata::RAIL_CURVE_SOUTHWEST => [
				new RailConnectionInfo(Facing::SOUTH),
				new RailConnectionInfo(Facing::WEST)
			],
			BlockLegacyMetadata::RAIL_CURVE_NORTHWEST => [
				new RailConnectionInfo(Facing::NORTH),
				new RailConnectionInfo(Facing::WEST)
			],
			BlockLegacyMetadata::RAIL_CURVE_NORTHEAST => [
				new RailConnectionInfo(Facing::NORTH),
				new RailConnectionInfo(Facing::EAST)
			]
		];
	}

	public function __construct(
		public readonly Facing $facing,
		public readonly bool $ascend = false
	){
	}
}
