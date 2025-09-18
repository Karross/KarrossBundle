<?php

namespace TestedApp;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Karross\KarrossBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as SFKernel;

class Kernel extends SFKernel
{
    public function __construct(string $environment, bool $debug, private array $configFiles = [])
    {
        parent::__construct($environment, $debug);
    }
    public function registerBundles(): iterable
    {
        return [
            new DoctrineBundle(),
            new FrameworkBundle(),
            new KarrossBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container) {
            $container->loadFromExtension('framework', [
                'secret' => 'test',
                'test' => true,
                'router' => [
                    'utf8' => true,
                    'resource' => __DIR__ . '/config/routes.php',  // fichier PHP
                    'type' => 'php',
                ],
            ]);
        });

        foreach ($this->configFiles as $file) {
            $loader->load($file);
        }
    }
}

