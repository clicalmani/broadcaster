<?php
namespace Broadcaster\Console;

use Clicalmani\Console\Commands\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Clicalmani\Foundation\Sandbox\Sandbox;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Broadcasting
 * 
 * @package Clicalmani\Console
 * @author clicalmani
 */
#[AsCommand(
    name: 'broadcast:event',
    description: 'Create a new broadcasting event class',
    hidden: false
)]
class MakeEvent extends Command
{
    private $broadcasting_path;

    public function __construct(protected $rootPath)
    {
        $this->broadcasting_path = $rootPath . '/app/Broadcasting';
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $this->mkdir($this->broadcasting_path);

        $name = $input->getArgument('name');
        $filename = $this->broadcasting_path . '/' . $name . '.php';

        $success = file_put_contents(
            $filename, 
            ltrim( 
                Sandbox::eval(file_get_contents( __DIR__ . "/samples/Event.sample"), ['class' => $name])
            )
        );

        if ($success) {
            $output->writeln('Command executed successfully');
            return Command::SUCCESS;
        }

        $output->writeln('Failed to execute the command');

        return Command::FAILURE;
    }

    protected function configure() : void
    {
        $this->setDefinition([
            new InputArgument('name', InputArgument::REQUIRED, 'Broadcasting event class name')
        ]);
    }
}
