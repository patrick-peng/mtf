<?php
/**
 * {license_notice}
 *
 * @copyright   {copyright}
 * @license     {license_link}
 */
namespace Magento\Framework\Config;

interface DataInterface
{
    /**
     * Merge config data to the object
     *
     * @param array $config
     * @return void
     */
    public function merge(array $config);

    /**
     * Get config value by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null);
}
