<?php declare(strict_types=1);

namespace SnapAdmin\Core\Framework\Increment;

use SnapAdmin\Core\Framework\Adapter\Cache\RedisConnectionFactory;
use SnapAdmin\Core\Framework\Log\Package;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
#[Package('core')]
class IncrementerGatewayCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        /** @var array{type?: string, config?: array<string, mixed>}[] $services */
        $services = $container->getParameter('snap.increment');
        $tag = 'snap.increment.gateway';

        foreach ($services as $pool => $service) {
            $type = $service['type'] ?? null;

            if (!\is_string($type)) {
                throw new \RuntimeException(sprintf('snap.increment.gateway type of %s pool must be a string', $pool));
            }

            $active = sprintf('snap.increment.%s.gateway.%s', $pool, $type);
            $config = [];

            // If service is not registered directly in the container, try to resolve them using fallback gateway
            if (!$container->hasDefinition($active)) {
                if (\array_key_exists('config', $service)) {
                    $config = (array) $service['config'];
                }

                $active = $this->resolveTypeDefinition($container, $pool, $type, $config);
            }

            if (!$container->hasDefinition($active)) {
                throw new \RuntimeException(sprintf(
                    'Can not find increment gateway for configured type %s of pool %s, expected service id %s can not be found',
                    $type,
                    $pool,
                    $active,
                ));
            }

            $definition = $container->getDefinition($active);

            if (!$definition->hasTag($tag)) {
                $definition->addTag($tag);
            }

            $class = $definition->getClass();

            if ($class === null || !is_subclass_of($class, AbstractIncrementer::class)) {
                throw new \RuntimeException(sprintf(
                    'Increment gateway with id %s, expected service instance of %s',
                    $active,
                    AbstractIncrementer::class
                ));
            }

            $definition->addMethodCall('setPool', [$pool]);
            $definition->addMethodCall('setConfig', [$config]);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function resolveTypeDefinition(ContainerBuilder $container, string $pool, string $type, array $config = []): string
    {
        // snap.increment.gateway.mysql is fallback gateway if custom gateway is not set
        $fallback = sprintf('snap.increment.gateway.%s', $type);

        $active = sprintf('snap.increment.%s.gateway.%s', $pool, $type);

        switch ($type) {
            case 'array':
            case 'mysql':
                $referenceDefinition = $container->getDefinition($fallback);

                $definition = new Definition($referenceDefinition->getClass());
                $definition->setArguments($referenceDefinition->getArguments());
                $definition->setTags($referenceDefinition->getTags());

                $container->setDefinition($active, $definition);

                return $active;
            case 'redis':
                $definition = new Definition('Redis');

                if (!\array_key_exists('url', $config)) {
                    return $active;
                }

                $definition->setFactory([new Reference(RedisConnectionFactory::class), 'create'])->addArgument($config['url']);

                $adapter = sprintf('snap.increment.%s.redis_adapter', $pool);

                $container->setDefinition($adapter, $definition);

                $definition = new Definition(RedisIncrementer::class);
                $definition->addArgument(new Reference($adapter));

                $container->setDefinition($active, $definition);

                return $active;

            default:
                return $active;
        }
    }
}
