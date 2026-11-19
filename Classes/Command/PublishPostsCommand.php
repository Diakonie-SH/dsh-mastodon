<?php

namespace DiakonieSH\DshMastodon\Command;

use DiakonieSH\DshMastodon\Domain\Repository\PostRepository;
use DiakonieSH\DshMastodon\Service\StatusService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsCommand(name: 'dsh_mastodon:publish')]
class PublishPostsCommand extends Command
{
    protected function configure(): void
    {
        parent::configure();

        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'If set, only show the posts that would be published');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $postRepository = GeneralUtility::makeInstance(PostRepository::class);
        $statusService = GeneralUtility::makeInstance(StatusService::class);

        $dryRun = $input->getOption('dry-run');
        $posts = $postRepository->findPublishableEverywhere();

        if ($dryRun) {
            $columns = ['UID', 'Account', 'Text', 'Images'];
            $rows = [];

            foreach ($posts as $post) {
                $rows[] = [
                    'UID' => $post->getUid(),
                    'Account' => $post->account ? $post->account->domain . "/@" . $post->account->username : '?',
                    'Text' => wordwrap($post->bodytext, 120),
                    'Images' => $post->images?->count() ?? 0,
                ];
                $rows[] = new TableSeparator();
            }

            array_pop($rows);

            $io = new SymfonyStyle($input, $output);
            $table = $io->createTable()->setStyle('box');
            $table->setHeaders($columns)->setRows($rows)->render();
            $io->newLine();
        } else {
            foreach ($posts as $post) {
                $statusService->publish($post);
            }
        }

        return Command::SUCCESS;
    }
}
