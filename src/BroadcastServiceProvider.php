<?php
namespace Broadcaster;

use Clicalmani\Container\Application;
use Clicalmani\Foundation\Providers\ServiceProviderInterface;
use Broadcaster\Event\ShouldBroadcastInterface;
use Broadcaster\BroadcastManager;

class BroadcastServiceProvider implements ServiceProviderInterface
{
    public function register(): void
    {
        $configFile = config_path('/broadcasting.php');
        $config = file_exists($configFile) ? require $configFile : ['default' => 'null', 'connections' => []];
        
        app()->addService(BroadcastManager::class, 
            BroadcastManager::class,
            static function($serviceConfig) use($config) {
                $serviceConfig->args([$config]);
            }
        );

        foreach ([
            Console\MakeEvent::class
        ] as $command) {
            app()->addCommand($command);
        }

        if ( isConsoleMode() ) {
            app()->console->make();
        }
    }

    public function boot(): void
    {
        if ( is_file(config_path('/broadcasting.php')) ) {
            app()->config->set('broadcasting', require_once config_path('/broadcasting.php'));
        }
    }
}