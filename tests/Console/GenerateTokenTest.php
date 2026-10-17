<?php
namespace Broadcaster\Test\Console;

use Broadcaster\Console\GenerateToken;
use Broadcaster\Test\BroadcasterTestCase;
use Lcobucci\JWT\Configuration as JwtConfiguration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Symfony\Component\Console\Tester\CommandTester;

class GenerateTokenTest extends BroadcasterTestCase
{
    private const SECRET = 'generate-token-secret-0123456789abcdef';

    protected function setUp(): void
    {
        parent::setUp();
        $_ENV['MERCURE_JWT_SECRET'] = self::SECRET;
    }

    protected function tearDown(): void
    {
        unset($_ENV['MERCURE_JWT_SECRET']);
        parent::tearDown();
    }

    private function runCommand(array $options): array
    {
        $tester = new CommandTester(new GenerateToken(sys_get_temp_dir()));
        $status = $tester->execute($options);

        return [$status, $tester->getDisplay()];
    }

    public function test_genere_un_token_avec_les_claims_publish_et_subscribe(): void
    {
        [$status, $display] = $this->runCommand([
            '--publish'   => 'https://mydomaine.com/orders/*',
            '--subscribe' => 'https://mydomaine.com/users/123',
        ]);

        $this->assertSame(0, $status);

        $this->assertMatchesRegularExpression('/Token: (\S+)/', $display, $display);
        [$token] = sscanf($display, 'Generated JWT Token:' . PHP_EOL . 'Token: %s');

        $parsed = JwtConfiguration::forSymmetricSigner(
            new Sha256,
            InMemory::plainText(self::SECRET)
        )->parser()->parse($token);

        $this->assertSame(['https://mydomaine.com/orders/*'], $parsed->claims()->get('mercure')['publish']);
        $this->assertSame(['https://mydomaine.com/users/123'], $parsed->claims()->get('mercure')['subscribe']);
    }

    public function test_echoue_sans_secret_dans_l_environnement(): void
    {
        unset($_ENV['MERCURE_JWT_SECRET']);

        [$status, $display] = $this->runCommand([]);

        $this->assertSame(1, $status);
        $this->assertStringContainsString('JWT secret is not set', $display);
    }
}
