<?php
declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\SkillDependencies;
use App\Entity\SkillDependencyTree;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\SkillDependencyTree
 */
class SkillDependencyTreeTest extends TestCase
{
    /** @test */
    public function createdObjectContainsAllSkillsOnCorrectLevels(): void
    {
        // This is a skill tree with one root (1) and two branches (1 <- 2, 1 <- 3 <- 4)
        $skillIds = [1, 2, 3, 4];
        $skillDependencies = new SkillDependencies();
        $skillDependencies->addRequirement(2, 1);
        $skillDependencies->addRequirement(3, 1);
        $skillDependencies->addRequirement(4, 3);

        $result = SkillDependencyTree::createFromSkillIdsAndDependencies($skillIds, $skillDependencies);

        self::assertSame([
            0 => [1],
            1 => [2, 3],
            2 => [4],
        ], $result->skillsByLevel);
    }

    /** @test */
    public function createdObjectHasEachSkillOnlyOnHighestLevel(): void
    {
        // This is a skill tree with one root (1) and three branches (1 <- 2, 1 <- 4, 1 <- 3 <- 4)
        // => 4 is on two levels in this branch, but should only be on the highest (3, or 2 if 0-indexed)
        $skillIds = [1, 2, 3, 4];
        $skillDependencies = new SkillDependencies();
        $skillDependencies->addRequirement(2, 1);
        $skillDependencies->addRequirement(3, 1);
        $skillDependencies->addRequirement(4, 1);
        $skillDependencies->addRequirement(4, 3);

        $result = SkillDependencyTree::createFromSkillIdsAndDependencies($skillIds, $skillDependencies);

        self::assertSame([
            0 => [1],
            1 => [2, 3],
            2 => [4],
        ], $result->skillsByLevel);
    }
}
