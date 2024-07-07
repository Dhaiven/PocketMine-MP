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

namespace pocketmine\block;

use pocketmine\block\utils\SupportType;
use pocketmine\block\utils\WallConnectionType;
use pocketmine\data\runtime\RuntimeDataDescriber;
use pocketmine\math\Axis;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Facing;
use function in_array;

/**
 * @phpstan-type WallConnectionSet array<Facing::NORTH|Facing::EAST|Facing::SOUTH|Facing::WEST, WallConnectionType>
 */
class Wall extends Transparent{

	/**
	 * @var WallConnectionType[]
	 * @phpstan-var WallConnectionSet
	 */
	protected array $connections = [];
	protected bool $post = false;

	protected function describeBlockOnlyState(RuntimeDataDescriber $w) : void{
		$w->wallConnections($this->connections);
		$w->bool($this->post);
	}

	/**
	 * @return WallConnectionType[]
	 * @phpstan-return WallConnectionSet
	 */
	public function getConnections() : array{ return $this->connections; }

	public function getConnection(int $face) : ?WallConnectionType{
		return $this->connections[$face] ?? null;
	}

	/**
	 * @param WallConnectionType[] $connections
	 * @phpstan-param WallConnectionSet $connections
	 * @return $this
	 */
	public function setConnections(array $connections) : self{
		$this->connections = $connections;
		return $this;
	}

	/** @return $this */
	public function setConnection(int $face, ?WallConnectionType $type) : self{
		if($face !== Facing::NORTH && $face !== Facing::SOUTH && $face !== Facing::WEST && $face !== Facing::EAST){
			throw new \InvalidArgumentException("Facing can only be north, east, south or west");
		}
		if($type !== null){
			$this->connections[$face] = $type;
		}else{
			unset($this->connections[$face]);
		}
		return $this;
	}

	public function isPost() : bool{ return $this->post; }

	/** @return $this */
	public function setPost(bool $post) : self{
		$this->post = $post;
		return $this;
	}

	public function onNearbyBlockChange(Block $block, ?int $face) : void{
		if($face != null && $this->recalculateConnection($block, $face) > 0){
			$this->position->getWorld()->setBlock($this->position, $this);
		}
	}

	protected function recalculateConnection(Block $block, int $face) : int{
		$changed = 0;

		//TODO: implement tall/short connections - right now we only support short as per pre-1.16

		if (in_array($face, Facing::HORIZONTAL, true)) {
			if($block instanceof static || $block instanceof FenceGate || $block instanceof Thin || $block->getSupportType(Facing::opposite($face)) === SupportType::FULL){
				if(!isset($this->connections[$face])){
					$this->connections[$face] = WallConnectionType::SHORT;
					$changed++;
				}
			}elseif(isset($this->connections[$face])){
				unset($this->connections[$face]);
				$changed++;
			}
		} elseif ($face === Facing::UP) {
			$up = $block->getTypeId() !== BlockTypeIds::AIR;
			if($up !== $this->post){
				$this->post = $up;
				$changed++;
			}
		}

		return $changed;
	}

	protected function recalculateConnections() : bool{
		$changed = 0;
		foreach(Facing::ALL as $facing){
			if($facing === Facing::DOWN){
				continue;
			}
			$changed += $this->recalculateConnection($this->getSide($facing), $facing);
		}

		return $changed > 0;
	}

	protected function recalculateCollisionBoxes() : array{
		//walls don't have any special collision boxes like fences do

		$north = isset($this->connections[Facing::NORTH]);
		$south = isset($this->connections[Facing::SOUTH]);
		$west = isset($this->connections[Facing::WEST]);
		$east = isset($this->connections[Facing::EAST]);

		$inset = 0.25;
		if(
			!$this->post && //if there is a block on top, it stays as a post
			(
				($north && $south && !$west && !$east) ||
				(!$north && !$south && $west && $east)
			)
		){
			//If connected to two sides on the same axis but not any others, AND there is not a block on top, there is no post and the wall is thinner
			$inset = 0.3125;
		}

		return [
			AxisAlignedBB::one()
				->extend(Facing::UP, 0.5)
				->trim(Facing::NORTH, $north ? 0 : $inset)
				->trim(Facing::SOUTH, $south ? 0 : $inset)
				->trim(Facing::WEST, $west ? 0 : $inset)
				->trim(Facing::EAST, $east ? 0 : $inset)
		];
	}

	public function getSupportType(int $facing) : SupportType{
		return Facing::axis($facing) === Axis::Y ? SupportType::CENTER : SupportType::NONE;
	}
}
