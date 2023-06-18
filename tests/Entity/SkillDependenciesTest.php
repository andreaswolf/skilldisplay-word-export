<?php
declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\SkillDependencies;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Entity\SkillDependencies
 */
class SkillDependenciesTest extends TestCase
{
    /** @test */
    public function addedRequirementRelationsAreAvailableAsRequiredAndRequiring(): void
    {
        $subject = new SkillDependencies();

        $subject->addRequirement(1, 2);
        $subject->addRequirement(1, 3);
        $subject->addRequirement(2, 3);

        self::assertSame([2, 3], $subject->getRequiredSkills(1));
        self::assertSame([3], $subject->getRequiredSkills(2));
        self::assertSame([1], $subject->getRequiringSkills(2));
        self::assertSame([1, 2], $subject->getRequiringSkills(3));
    }
}
