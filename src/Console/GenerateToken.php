<?php
namespace Broadcaster\Console;

use Clicalmani\Console\Commands\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Clicalmani\Core\Sandbox\Sandbox;
use Symfony\Component\Console\Input\InputArgument;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Symfony\Component\Console\Input\InputOption;

/**
 * Broadcasting
 * 
 * @package Clicalmani\Console
 * @author clicalmani
 */
#[AsCommand(
    name: 'broadcast:generate-token',
    description: 'Generate a token for private subscription.',
    hidden: false
)]
class GenerateToken extends Command
{
    public function __construct(protected $rootPath)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $secret = env('MERCURE_JWT_SECRET', null);

        if (!$secret) {
            $output->writeln('<error>JWT secret is not set in the environment variables.</error>');
            return Command::FAILURE;
        }

        $publishClaim = $input->getOption('publish');
        $subscribeClaim = $input->getOption('subscribe');

        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($secret)
        );

        $token = $config->builder()
            ->withClaim('mercure', [
                'publish' => [$publishClaim],       // Autorisation de publication
                'subscribe' => [$subscribeClaim],   // Autorisation d'abonnement
            ])
            ->getToken($config->signer(), $config->signingKey());

        $output->writeln('Generated JWT Token:');
        $output->writeln('Token: ' . $token->toString());

        return Command::SUCCESS;
    }

    protected function configure() : void
    {
        $this->setDefinition([
            new InputOption('publish', 'p', InputOption::VALUE_REQUIRED, 'Publish claim', '*', ['https://mydomaine.com/orders/*']),
            new InputOption('subscribe', 's', InputOption::VALUE_REQUIRED, 'Subscribe claim', '*', ['https://mydomaine.com/users/123']),
        ]);
    }
}
