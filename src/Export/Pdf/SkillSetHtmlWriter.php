<?php
declare(strict_types=1);

namespace App\Export\Pdf;

use App\Entity\SkillDependencies;
use SkillDisplay\PHPToolKit\Entity\Skill;
use Twig\Environment;

final readonly class SkillSetHtmlWriter
{
    public function __construct(private Environment $twig)
    {
    }

    /**
     * @param list<Skill> $skills
     */
    public function writeSkillsHtmlForPdf(array $skills, SkillDependencies $dependencies): PdfData
    {
        $skillsIndexedById = [];
        foreach ($skills as $skill) {
            $skillsIndexedById[$skill->getId()] = $skill;
        }
        $requiringSkills = [];
        foreach ($skills as $skill) {
            $requiringSkills[$skill->getId()] = array_map(
                static fn (int $skillId) => $skillsIndexedById[$skillId]->toArray(),
                $dependencies->getRequiringSkills($skill->getId())
            );
        }

        $template = $this->twig->load('skillset.html.twig');
        return new PdfData('', $template->render([
            'skills' => $skills,
            'requiringSkills' => $requiringSkills,
        ]));
    }
}