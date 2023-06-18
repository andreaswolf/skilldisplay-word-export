<?php
declare(strict_types=1);

namespace App\Entity;

class SkillDependencies
{
    /** @var array<int, list<int> */
    private array $requirementsByRequiringSkill = [];

    /** @var array<int, list<int> */
    private array $requirementsByRequiredSkill = [];

    /**
     * Adds a skill requirement to the adjacency list
     *
     * @param int $requiringSkill
     * @param int $requiredSkill
     */
    public function addRequirement(int $requiringSkill, int $requiredSkill): void
    {
        $this->requirementsByRequiringSkill[$requiringSkill][] = $requiredSkill;
        $this->requirementsByRequiredSkill[$requiredSkill][] = $requiringSkill;
    }

    /**
     * @param int $requiringSkill
     * @return list<int>
     */
    public function getRequiredSkills(int $requiringSkill): array
    {
        return $this->requirementsByRequiringSkill[$requiringSkill] ?? [];
    }

    /**
     * @param int $requiredSkill
     * @return list<int>
     */
    public function getRequiringSkills(int $requiredSkill): array
    {
        return $this->requirementsByRequiredSkill[$requiredSkill] ?? [];
    }
}
