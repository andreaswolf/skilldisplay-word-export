<?php
declare(strict_types=1);

namespace App\Command;

use App\Entity\SkillDependencies;
use App\Export\SkillSetWordWriter;
use GuzzleHttp\Client;
use PhpOffice\PhpWord\IOFactory;
use SkillDisplay\PHPToolKit\Api\Skill as SkillService;
use SkillDisplay\PHPToolKit\Api\SkillSet;
use SkillDisplay\PHPToolKit\Configuration\Settings;
use SkillDisplay\PHPToolKit\Entity\Skill;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    'skilldisplay:word-export',
    'Exports a skillset as an editable Word document'
)]
class SkillsetWordExport extends Command
{
    public function __construct(
        private readonly Settings $skilldisplaySettings,
        private readonly Client $client,
        private readonly SkillSetWordWriter $writer
    ) {
        parent::__construct(null);
    }

    protected function configure()
    {
        parent::configure();

        $this->addArgument('skillset', InputArgument::REQUIRED);
        $this->addArgument('filename', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $skillsetId = $input->getArgument('skillset');
        if ((string)(int)$skillsetId !== $skillsetId) {
            $io->error('Skillset ID must be a number');
            return Command::FAILURE;
        }
        $skillsetId = (int)$skillsetId;
        $filename = $input->getArgument('filename');

        $skillsetService = new SkillSet($this->skilldisplaySettings, $this->client);
        $skillService = new SkillService($this->skilldisplaySettings, $this->client);

        $skillset = $skillsetService->getById($skillsetId);

        $skillsOrderedByTitle = $skillset->getSkills();
        usort(
            $skillsOrderedByTitle,
            static fn (Skill $left, Skill $right) => strnatcasecmp($left->getTitle(), $right->getTitle())
        );

        $skillDependencies = new SkillDependencies();
        foreach ($skillsOrderedByTitle as &$skill) {
            $skill = $skillService->getById($skill->getId());

            $skillData = $skill->toArray();
            if ($skillData['owner'] === null) {
                $io->warning(sprintf('Skill %s does not have an owner', $skill->getTitle()));
                continue;
            }
            if ($skillData['owner']['uid'] !== 612) {
                $skill = null;
                continue;
            }

            foreach ($skillData['prerequisites'] as $requiredSkill) {
                $skillDependencies->addRequirement($skill->getId(), $requiredSkill['uid']);
            }
        }
        $relevantSkills = array_values(array_filter($skillsOrderedByTitle));
        $document = $this->writer->writeSkillsToWordDocument($skillset->getName(), $relevantSkills, $skillDependencies);

        $objWriter = IOFactory::createWriter($document, 'Word2007');
        $objWriter->save($filename);

        return Command::SUCCESS;
    }
}
