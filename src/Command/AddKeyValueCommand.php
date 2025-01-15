<?php

namespace App\Command;

use App\Entity\KeyValueStore;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:add-key-value',
    description: 'Adds a key-value pair to the "About Me" section for a specific user.',
    aliases: ['app:add-key', 'app:add-value'],
    hidden: false
)]
class AddKeyValueCommand extends Command
{
    private $entityManager;
    private $userRepository;

    public function __construct(EntityManagerInterface $entityManager, UserRepository $userRepository)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Adds a key-value pair to the "About Me" section for a specific user.')
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');

        $user = $this->userRepository->findOneByUsername($username);
        if (!$user) {
            $io->error("User '$username' not found.");
            return Command::FAILURE;
        }

        $helper = $this->getHelper('question');
        $keyQuestion = new Question('Please enter the key: ');
        $key = $helper->ask($input, $output, $keyQuestion);

        $valueQuestion = new Question('Please enter the value (comma separated for multiple values): ');
        $value = $helper->ask($input, $output, $valueQuestion);
        $valueArray = array_map('trim', explode(',', $value));

        $keyValueStore = new KeyValueStore();
        $keyValueStore->setUser($user);
        $keyValueStore->setKey($key);
        $keyValueStore->setValue($valueArray);

        $this->entityManager->persist($keyValueStore);
        $this->entityManager->flush();

        $io->success("Key-value pair has been added successfully for user '$username'.");

        return Command::SUCCESS;
    }
}
