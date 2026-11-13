<?php

declare(strict_types=1);

namespace AgUi\Bundle\Tests\App;

use AgUi\Bundle\AgUiBundle;
use Symfony\AI\AiBundle\AiBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\TwigComponent\TwigComponentBundle;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new TwigComponentBundle();
        yield new AiBundle();
        yield new AgUiBundle();
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/ag-ui-bundle-tests/'.md5(__DIR__).'/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/ag-ui-bundle-tests/'.md5(__DIR__).'/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', ['test' => true, 'secret' => 'test', 'http_method_override' => false, 'serializer' => ['enabled' => true], 'validation' => ['enabled' => true], 'property_info' => ['enabled' => true]]);
        $container->extension('twig', ['default_path' => __DIR__.'/templates']);
        $container->extension('twig_component', ['anonymous_template_directory' => 'components/', 'defaults' => ['AgUi\\Bundle\\Tests\\App\\Component\\' => 'components/']]);
        $container->extension('ai', ['agent' => [
            'shop' => ['platform' => 'test.platform', 'model' => 'mock-model', 'prompt' => 'You are a shop assistant.', 'tools' => true],
            'support' => ['platform' => 'test.platform', 'model' => 'mock-model', 'prompt' => 'You are the support desk.'],
        ]]);

        $services = $container->services()->defaults()->autowire()->autoconfigure();
        $services->set('logger', \Psr\Log\NullLogger::class);
        $services->set(Cart::class);
        $services->set('test.platform')->class(\Symfony\AI\Platform\Platform::class)->factory([ScriptedPlatform::class, 'create']);
        $services->load('AgUi\\Bundle\\Tests\\App\\Tool\\', __DIR__.'/Tool');
        $services->load('AgUi\\Bundle\\Tests\\App\\Component\\', __DIR__.'/Component');
        $services->load('AgUi\\Bundle\\Tests\\App\\Context\\', __DIR__.'/Context');
        $services->load('AgUi\\Bundle\\Tests\\App\\Controller\\', __DIR__.'/Controller');
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(__DIR__.'/Controller/', 'attribute');
    }
}
