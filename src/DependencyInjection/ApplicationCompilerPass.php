<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ApplicationCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(Application::class)) {
            return;
        }

        $commands = $container->findTaggedServiceIds('console.command');
        $arguments = [array_map(fn ($item) => new Reference($item), array_keys($commands))];

        $container->getDefinition(Application::class)->addMethodCall('addCommands', $arguments);
    }
}
