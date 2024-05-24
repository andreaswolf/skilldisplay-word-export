<?php
declare(strict_types=1);

namespace App\Export;

use App\Entity\SkillDependencies;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\Style\Font;
use SkillDisplay\PHPToolKit\Entity\Skill;

class SkillSetWordWriter
{
    /**
     * @param list<Skill> $skills A list of skills; must include the full API response for a single skill, so the list
     *                            endpoint response is not enough!
     * @return PhpWord
     */
    public function writeSkillsToWordDocument(string $title, array $skills, SkillDependencies $skillDependencies): PhpWord
    {
        $document = new PhpWord();

        $document->addTitleStyle(null, ['size' => 22, 'bold' => true]);
        $document->addTitleStyle(1, ['size' => 20, 'color' => '000000', 'bold' => true]);
        $document->addTitleStyle(2, ['size' => 16, 'color' => '000000', 'bold' => true]);
        $document->addTitleStyle(3, ['size' => 14]);
        $document->addTitleStyle(4, ['size' => 12]);

        $document->addLinkStyle('skill', ['color' => '000080', 'underline' => Font::UNDERLINE_SINGLE]);
        $document->addLinkStyle('url', ['color' => '000080', 'underline' => Font::UNDERLINE_SINGLE]);

        $skillsSection = $document->addSection();
        $skillsSection->addTitle($title, 1);
        $toc = $skillsSection->addTOC();
        $toc->setMinDepth(2);
        $toc->setMaxDepth(2);
        $skillsSection->addPageBreak();

        foreach ($skills as $skill) {
            $skillAsArray = $skill->toArray();

            $skillsSection->addTitle(htmlspecialchars($skill->getTitle(), ENT_QUOTES), 2);
            $skillsSection->addBookmark(sprintf('skill_%d', $skill->getId()));

            $this->writeOwner($skillsSection, $skillAsArray['owner']);

            $skillsSection->addTextBreak();

            $description = $skill->getDescription();
            $description = str_replace(['&nbsp;', '&gt;', '&lt;'], [' ', '&amp;gt;', '&amp;lt;'], $description);
            //$description = htmlspecialchars($description, ENT_QUOTES);
            echo $description, "\n\n----\n\n";
            Html::addHtml($skillsSection, $description, false, false);

            $this->addGoals($skillsSection, $skill);
            $this->addLinks($skillsSection, $skill);
            $this->addLinksToSkillItems(
                $skillsSection,
                $skills,
                $skillDependencies->getRequiringSkills($skill->getId()),
                $skillDependencies->getRequiredSkills($skill->getId())
            );

            $this->addTags($skillsSection, $skill);

            $skillsSection->addPageBreak();
        }

        return $document;
    }

    /**
     * @param array{firstName: string, lastName: string}|null $owner
     */
    private function writeOwner(Section $skillsSection, ?array $owner): void
    {
        $ownerLine = $skillsSection->addTextRun();
        $ownerLine->addText('Owner:', ['bold' => true]);
        if ($owner === null) {
            $ownerLine->addText('unbekannt', ['italic' => true]);
        } else {
            $ownerLine->addText(sprintf(' %s %s', $owner['firstName'], $owner['lastName']));
        }
    }

    private function addGoals(Section $skillsSection, Skill $skill): void
    {
        $skillsSection->addTitle('Goals', 3);
        Html::addHtml($skillsSection, $skill->getGoals(), false, false);
    }

    private function addLinks(Section $skillsSection, Skill $skill): void
    {
        $skillsSection->addTitle('Links', 3);
        foreach($skill->toArray()['links'] as $link) {
            $listItem = $skillsSection->addListItemRun();
            $listItem->addText(htmlspecialchars($link['title'], ENT_QUOTES));
            $listItem->addTextBreak();
            $listItem->addLink($link['url'], $link['url'], 'url');
        }
    }

    /**
     * @param list<SkilL> $skills
     * @param list<int> $requiringSkillIds
     * @param list<int> $requiredSkillIds
     */
    private function addLinksToSkillItems(Section $skillSection, array $skills, array $requiringSkillIds, array $requiredSkillIds): void
    {
        $skillSection->addTitle('Links to other skills', 3);

        $skillSection->addTitle('Skills required by this skill', 4);
        $this->addSkillLinks($skillSection, $skills, $requiredSkillIds);

        $skillSection->addTitle('Skills requiring this skill', 4);
        $this->addSkillLinks($skillSection, $skills, $requiringSkillIds);
    }

    /**
     * @param list<SkilL> $skills
     * @param list<int> $skillIds
     */
    private function addSkillLinks(Section $skillSection, array $skills, array $skillIds): void
    {
        $skillsPrinted = 0;
        foreach ($skillIds as $skillId) {
            foreach ($skills as $skill) {
                if ($skill->getId() !== $skillId) {
                    continue;
                }
                ++$skillsPrinted;

                $listItem = $skillSection->addListItemRun();
                $listItem->addLink(
                    sprintf('skill_%d', $skillId),
                    htmlspecialchars($skill->getTitle(), ENT_QUOTES),
                    'skill', null, true
                );

                continue 2;
            }
        }
        if ($skillsPrinted === 0) {
            $skillSection->addText('none', ['italic' => true]);
        }
    }

    private function addTags(Section $skillsSection, Skill $skill): void
    {
        $skillsSection->addTitle('Tags', 3);

        /** @var array{title: string} $tag */
        $tags = $skill->toArray()['tags'];
        if ($tags === []) {
            $skillsSection->addText('none', ['italic' => true]);
        }

        foreach ($tags as $tag) {
            $skillsSection->addListItem(htmlspecialchars($tag['title'], ENT_QUOTES));
        }
    }
}
