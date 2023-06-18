<?php
declare(strict_types=1);

namespace App\Entity;

/**
 * A tree of skill IDs, grouped by levels. The levels are defined by the "prerequisites" relations of the skills,
 * i.e. a skill without any prerequisites is on level 0, while a skill depending on any of these skills is on level 1.
 *
 * Each skill is only contained once, on its highest level. This means this structure can easily be used to draw a directed
 * graph of skills.
 */
class SkillDependencyTree
{
    /**
     * @param list<int> $skillIds
     */
    public static function createFromSkillIdsAndDependencies(array $skillIds, SkillDependencies $skillDependencies): self
    {
        $skillsByLevel = self::groupSkillsByLevel($skillIds, $skillDependencies);

        $skillsOnlyByHighestLevel = self::removeDuplicateSkillsFromLowerLevels($skillsByLevel);

        return new self($skillsOnlyByHighestLevel);
    }

    /**
     * @param array<int, list<int>> $skillsByLevel The list of skill IDs by level (level 0 = no requirements) $skillsByLevel
     */
    public function __construct(public readonly array $skillsByLevel)
    {
    }

    /**
     * @param list<int> $skillIds
     * @param SkillDependencies $skillDependencies
     * @return array<int, list<int>> The list of skills grouped by level; level 0 is skills without any prerequisites,
     *                               i.e. the roots of the dependency trees. Skills can be in here multiple times.
     */
    private static function groupSkillsByLevel(array $skillIds, SkillDependencies $skillDependencies): array
    {
        $skillsWithoutRequirements = array_values(array_filter(
            $skillIds,
            static fn(int $skillId) => count($skillDependencies->getRequiredSkills($skillId)) === 0
        ));
        $level = 0;
        $skillsOnCurrentLevel = $skillsWithoutRequirements;
        sort($skillsOnCurrentLevel);
        $skillsByLevel = [
            0 => $skillsOnCurrentLevel,
        ];
        do {
            $skillsOnNextLevel = [];
            foreach ($skillsOnCurrentLevel as $skill) {
                foreach ($skillDependencies->getRequiringSkills($skill) as $skillDependency) {
                    $skillsOnNextLevel[] = $skillDependency;
                }
            }
            $skillsOnNextLevel = array_unique($skillsOnNextLevel);
            sort($skillsOnNextLevel);
            ++$level;

            if ($skillsOnNextLevel !== []) {
                $skillsByLevel[$level] = $skillsOnNextLevel;
                $skillsOnCurrentLevel = $skillsOnNextLevel;
            }
        } while ($skillsOnNextLevel !== [] && $level < 20);

        return $skillsByLevel;
    }

    /**
     * @param array<int, list<int>> $skillsByLevel
     * @return array<int, list<int>>
     */
    private static function removeDuplicateSkillsFromLowerLevels(array $skillsByLevel): array
    {
        $skillsOnHigherLevels = [];
        foreach (array_reverse(array_keys($skillsByLevel)) as $level) {
            $skillsOnCurrentLevel = $skillsByLevel[$level];
            $skillsOnCurrentLevel = array_diff($skillsOnCurrentLevel, $skillsOnHigherLevels);

            $skillsByLevel[$level] = $skillsOnCurrentLevel;
            $skillsOnHigherLevels = [
                ...$skillsOnHigherLevels,
                ...$skillsOnCurrentLevel,
            ];
        }
        return $skillsByLevel;
    }
}
