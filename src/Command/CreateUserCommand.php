<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-user',
    description: 'Creates a new user with a specified username, email, and password.',
    aliases: ['app:add-user', 'app:new-user'],
    hidden: false
)]
class CreateUserCommand extends Command
{
    private $userRepository;
    private $passwordHasher;

    public function __construct(UserRepository $userRepository, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'The username of the new user')
            ->addArgument('email', InputArgument::REQUIRED, 'The email of the new user')
            ->addArgument('password', InputArgument::REQUIRED, 'The password of the new user');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $username = $input->getArgument('username');
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');

        if ($this->userRepository->findOneByUsername($username)) {
            $io->error("Username '$username' already exists.");
            return Command::FAILURE;
        }

        if ($this->userRepository->findOneByEmail($email)) {
            $io->error("Email '$email' already exists.");
            return Command::FAILURE;
        }

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->userRepository->add($user);

        $io->success("User '$username' has been created successfully.");

        return Command::SUCCESS;
    }
}
