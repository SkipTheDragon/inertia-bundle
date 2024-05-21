<?php

namespace Rompetomp\InertiaBundle\DependencyInjection;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

/**
 * Class InertiaTwigExtension.
 *
 * @author  Hannes Vermeire <hannes@codedor.be>
 *
 * @since   2019-08-02
 */
class InertiaExtension extends ConfigurableExtension
{
    /**
     * Configures the passed container according to the merged configuration.
     *
     * @throws Exception
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        /**
         * Example: inertia.csrf.enabled
         */
        foreach (self::transformKeys($mergedConfig) as $key => $value) {
            $container->setParameter('inertia.' . $key, $value);
        }
    }

    /**
     * Transforms the keys of a multidimensional array to dot notation.
     * @param array $array
     * @param string $parentKey
     * @return array
     */
    private function transformKeys(array $array, string $parentKey = ''): array
    {
        $result = array();

        foreach ($array as $key => $value) {
            $newKey = ($parentKey !== '') ? $parentKey . '.' . $key : $key;

            if (is_array($value)) {
                $result += self::transformKeys($value, $newKey);
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}
