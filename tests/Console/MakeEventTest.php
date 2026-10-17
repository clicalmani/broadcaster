<?php
namespace Broadcaster\Test\Console;

use Broadcaster\Console\MakeEvent;
use Broadcaster\Test\BroadcasterTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class MakeEventTest extends BroadcasterTestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir() . '/broadcaster-maketest-' . uniqid();
    }

    protected function tearDown(): void
    {
        $broadcasting = $this->root . '/app/Broadcasting';
        if (is_dir($broadcasting)) {
            foreach (glob($broadcasting . '/*.php') ?: [] as $file) unlink($file);
            rmdir($broadcasting);
            rmdir(dirname($broadcasting));
        }
        parent::tearDown();
    }

    public function test_genere_la_classe_evenement(): void
    {
        $tester = new CommandTester(new MakeEvent($this->root));

        $status = $tester->execute(['name' => 'OrderShipped']);

        $file = $this->root . '/app/Broadcasting/OrderShipped.php';

        $this->assertSame(\Clicalmani\Console\Commands\Command::SUCCESS, $status);
        $this->assertFileExists($file);
        $this->assertStringContainsString('class OrderShipped', file_get_contents($file));
        $this->assertStringContainsString('ShouldBroadcastInterface', file_get_contents($file));
    }
}
