<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-generic-service',
    description: 'Creates a generic service class with basic functionality.',
    aliases: ['app:create-service', 'app:generate-service'],
    hidden: true
)]
class CreateGenericServiceCommand extends Command
{
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();
        $this->filesystem = $filesystem;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the service class')
            ->addOption('path', null, InputOption::VALUE_OPTIONAL, 'The directory path to create the service in', 'src/Service');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');
        $path = rtrim($input->getOption('path'), '/');

        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9]*$/', $name)) {
            $io->error('Service name must start with a letter and contain only letters and numbers.');
            return Command::FAILURE;
        }

        if (!$this->filesystem->exists($path)) {
            $this->filesystem->mkdir($path, 0755);
            $io->success("Created directory: $path");
        }

        $filePath = "$path/$name.php";
        $namespacePath = str_replace('/', '\\', ltrim($path, 'src/'));

        if ($this->filesystem->exists($filePath)) {
            $io->error("$name.php already exists.");
            return Command::FAILURE;
        }

        $content = <<<EOD
<?php

namespace App\\$namespacePath;

class $name
{
    public function __construct()
    {
        // Initialize your service here
    }

    public function someMethod()
    {
        // Add service logic here
    }
}
EOD;

        $this->filesystem->dumpFile($filePath, $content);
        $io->success("$name.php has been created successfully in $path.");

        return Command::SUCCESS;
    }
}
