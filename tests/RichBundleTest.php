<?php

namespace OneToMany\RichBundle\Tests;

use OneToMany\RichBundle\Maker\MakeRichModule;
use OneToMany\RichBundle\RichBundle;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

use function sys_get_temp_dir;

#[Group('UnitTests')]
#[Group('BundleTests')]
final class RichBundleTest extends TestCase
{
    public function testLoadingExtensionRegistersMakersWhenMakerBundleIsEnabled(): void
    {
        $container = $this->loadExtension([
            'MakerBundle' => MakerBundle::class,
        ]);

        $this->assertTrue($container->hasDefinition(MakeRichModule::class));

        $definition = $container->getDefinition(MakeRichModule::class);
        $this->assertTrue($definition->hasTag('maker.command'));

        $fileManager = $definition->getArgument('$fileManager');
        $this->assertInstanceOf(Reference::class, $fileManager);
        $this->assertSame('maker.file_manager', (string) $fileManager);
    }

    public function testLoadingExtensionDoesNotRegisterMakersWhenMakerBundleIsNotEnabled(): void
    {
        $container = $this->loadExtension([]);

        $this->assertFalse($container->hasDefinition(MakeRichModule::class));
    }

    /**
     * @param array<string, class-string> $bundles
     */
    private function loadExtension(array $bundles): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.bundles', $bundles);
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.build_dir', sys_get_temp_dir());

        new RichBundle()->getContainerExtension()?->load([], $container);

        return $container;
    }
}
