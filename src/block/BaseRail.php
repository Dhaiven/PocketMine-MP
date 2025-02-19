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

use pocketmine\block\utils\RailConnectionInfo;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\BlockTransaction;
use function array_reverse;
use function array_search;
use function array_shift;
use function count;
use function in_array;

abstract class BaseRail extends Flowable{

	public function place(BlockTransaction $tx, Item $item, Block $blockReplace, Block $blockClicked, Facing $face, Vector3 $clickVector, ?Player $player = null) : bool{
		if($blockReplace->getAdjacentSupportType(Facing::DOWN)->hasEdgeSupport()){
			return parent::place($tx, $item, $blockReplace, $blockClicked, $face, $clickVector, $player);
		}

		return false;
	}

	public function onPostPlace() : void{
		$this->tryReconnect();
	}

	/**
	 * @param RailConnectionInfo[]          $connections
	 * @param RailConnectionInfo[][]        $lookup
	 *
	 * @phpstan-param array<int, list<int>> $lookup
	 */
	protected static function searchState(array $connections, array $lookup) : ?int{
		$shape = array_search($connections, $lookup, true);
		if($shape === false){
			$shape = array_search(array_reverse($connections), $lookup, true);
		}
		return $shape === false ? null : $shape;
	}

	/**
	 * Sets the rail shape according to the given connections, if a shape matches.
	 *
	 * @param RailConnectionInfo[] $connections
	 *
	 * @throws \InvalidArgumentException if no shape matches the given connections
	 */
	abstract protected function setShapeFromConnections(array $connections) : void;

	/**
	 * Returns the connection directions of this rail (depending on the current block state)
	 *
	 * @return RailConnectionInfo[]
	 */
	abstract protected function getCurrentShapeConnections() : array;

	/**
	 * Returns all the directions this rail is already connected in.
	 *
	 * @return RailConnectionInfo[]
	 */
	private function getConnectedDirections() : array{
		$connections = [];

		foreach($this->getCurrentShapeConnections() as $connection){
			$other = $this->getSide($connection->facing);
			$otherConnection = new RailConnectionInfo($connection->facing->opposite(), $connection->ascend);

			if($connection->ascend){
				$other = $other->getSide(Facing::UP);
			}elseif(!($other instanceof BaseRail)){ //check for rail sloping up to meet this one
				$other = $other->getSide(Facing::DOWN);
				$otherConnection = new RailConnectionInfo($otherConnection->facing, true);
			}

			if(
				$other instanceof BaseRail &&
				in_array($otherConnection, $other->getCurrentShapeConnections(), true)
			){
				$connections[] = $connection;
			}
		}

		return $connections;
	}

	/**
	 * @param RailConnectionInfo[] $constraints
	 *
	 * @return RailConnectionInfo[]
	 */
	private function getPossibleConnectionDirections(array $constraints) : array{
		switch(count($constraints)){
			case 0:
				//No constraints, can connect in any direction
				return [
					new RailConnectionInfo(Facing::NORTH, true),
					new RailConnectionInfo(Facing::SOUTH, true),
					new RailConnectionInfo(Facing::WEST, true),
					new RailConnectionInfo(Facing::EAST, true),
				];
			case 1:
				return $this->getPossibleConnectionDirectionsOneConstraint(array_shift($constraints));
			case 2:
				return [];
			default:
				throw new \InvalidArgumentException("Expected at most 2 constraints, got " . count($constraints));
		}
	}

	/**
	 * @return RailConnectionInfo[]
	 */
	protected function getPossibleConnectionDirectionsOneConstraint(RailConnectionInfo $constraint) : array{
		$opposite = $constraint->facing->opposite();

		$possible = [new RailConnectionInfo($opposite, $constraint->ascend)];

		if(!$constraint->ascend){
			//We can slope the other way if this connection isn't already a slope
			$possible[] = new RailConnectionInfo($constraint->facing, true);
		}

		return $possible;
	}

	private function tryReconnect() : void{
		$thisConnections = $this->getConnectedDirections();
		$changed = false;

		$world = $this->position->getWorld();
		do{
			$possible = $this->getPossibleConnectionDirections($thisConnections);
			$continue = false;

			foreach($possible as $connection){
				$otherSide = new RailConnectionInfo($connection->facing->opposite(), $connection->ascend);

				$other = $this->getSide($connection->facing);

				if($connection->ascend){
					$other = $other->getSide(Facing::UP);
				}elseif(!($other instanceof BaseRail)){ //check if other rails can slope up to meet this one
					$other = $other->getSide(Facing::DOWN);
					$otherSide = new RailConnectionInfo($otherSide->facing, true);
				}

				if(!($other instanceof BaseRail) || count($otherConnections = $other->getConnectedDirections()) >= 2){
					//we can only connect to a rail that has less than 2 connections
					continue;
				}

				$otherPossible = $other->getPossibleConnectionDirections($otherConnections);

				if(in_array($connection, $otherPossible)){
					$otherConnections[] = $otherSide;
					$other->setConnections($otherConnections);
					$world->setBlock($other->position, $other);

					$changed = true;
					$thisConnections[] = $connection;
					$continue = count($thisConnections) < 2;

					break; //force recomputing possible directions, since this connection could invalidate others
				}
			}
		}while($continue);

		if($changed){
			$this->setConnections($thisConnections);
			$world->setBlock($this->position, $this);
		}
	}

	/**
	 * @param RailConnectionInfo[] $connections
	 */
	private function setConnections(array $connections) : void{
		if(count($connections) === 1){
			$connections[] = new RailConnectionInfo($connections[0]->facing->opposite(), $connections[0]->ascend);
		}elseif(count($connections) !== 2){
			throw new \InvalidArgumentException("Expected exactly 2 connections, got " . count($connections));
		}

		$this->setShapeFromConnections($connections);
	}

	public function onNearbyBlockChange() : void{
		$world = $this->position->getWorld();
		if(!$this->getAdjacentSupportType(Facing::DOWN)->hasEdgeSupport()){
			$world->useBreakOn($this->position);
		}else{
			foreach($this->getCurrentShapeConnections() as $connection){
				if($connection->ascend && !$this->getSide($connection->facing)->getSupportType(Facing::UP)->hasEdgeSupport()){
					$world->useBreakOn($this->position);
					break;
				}
			}
		}
	}
}
