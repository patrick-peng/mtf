<?php
/**
 * {license_notice}
 *
 * @copyright {copyright}
 * @license   {license_link}
 */
namespace Magento\Framework\ObjectManager\Factory;

class Factory implements \Magento\Framework\ObjectManager\Factory
{
    /**
     * Object manager
     *
     * @var \Magento\Framework\ObjectManager
     */
    protected $objectManager;

    /**
     * Object manager config
     *
     * @var \Magento\Framework\ObjectManager\Config
     */
    protected $config;

    /**
     * Definition list
     *
     * @var \Magento\Framework\ObjectManager\Definition
     */
    protected $definitions;

    /**
     * Object creation stack
     *
     * @var array
     */
    protected $creationStack = array();

    /**
     * @param \Magento\Framework\ObjectManager\Config $config
     * @param \Magento\Framework\ObjectManager $objectManager
     * @param \Magento\Framework\ObjectManager\Definition $definitions
     * @param array $globalArguments
     */
    public function __construct(
        \Magento\Framework\ObjectManager\Config $config,
        \Magento\Framework\ObjectManager $objectManager = null,
        \Magento\Framework\ObjectManager\Definition $definitions = null,
        $globalArguments = array()
    ) {
        $this->config = $config;
        $this->objectManager = $objectManager;
        $this->definitions = $definitions ? : new \Magento\Framework\ObjectManager\Definition\Runtime();
        $this->globalArguments = $globalArguments;
    }

    /**
     * Set object manager
     *
     * @param \Magento\Framework\ObjectManager $objectManager
     * @return void
     */
    public function setObjectManager(\Magento\Framework\ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Resolve constructor arguments
     *
     * @param string $requestedType
     * @param array $parameters
     * @param array $arguments
     * @return array
     * @throws \UnexpectedValueException
     * @throws \BadMethodCallException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _resolveArguments($requestedType, array $parameters, array $arguments = array())
    {
        $resolvedArguments = array();
        $arguments = count($arguments)
            ? array_replace($this->config->getArguments($requestedType), $arguments)
            : $this->config->getArguments($requestedType);
        foreach ($parameters as $parameter) {
            list($paramName, $paramType, $paramRequired, $paramDefault) = $parameter;
            $argument = null;
            if (array_key_exists($paramName, $arguments)) {
                $argument = $arguments[$paramName];
            } else {
                if ($paramRequired) {
                    if ($paramType) {
                        $argument = array('instance' => $paramType);
                    } else {
                        $this->creationStack = array();
                        throw new \BadMethodCallException(
                            'Missing required argument $' . $paramName . ' of ' . $requestedType . '.'
                        );
                    }
                } else {
                    $argument = $paramDefault;
                }
            }
            if ($paramType && !is_object($argument) && $argument !== $paramDefault) {
                if (!is_array($argument) || !isset($argument['instance'])) {
                    throw new \UnexpectedValueException(
                        'Invalid parameter configuration provided for $' . $paramName . ' argument of ' . $requestedType
                    );
                }
                $argumentType = $argument['instance'];
                $isShared = (isset($argument['shared']) ? $argument['shared'] : $this->config->isShared($argumentType));
                $argument = $isShared
                    ? $this->objectManager->get($argumentType)
                    : $this->objectManager->create($argumentType);
            } else {
                if (is_array($argument)) {
                    if (isset($argument['argument'])) {
                        $argKey = $argument['argument'];
                        $argument = isset($this->globalArguments[$argKey])
                            ? $this->globalArguments[$argKey]
                            : $paramDefault;
                    } else {
                        $this->parseArray($argument);
                    }
                }
            }
            $resolvedArguments[] = $argument;
        }
        return $resolvedArguments;
    }

    /**
     * Parse array argument
     *
     * @param array $array
     * @return void
     */
    protected function parseArray(&$array)
    {
        foreach ($array as $key => $item) {
            if (is_array($item)) {
                if (isset($item['instance'])) {
                    $itemType = $item['instance'];
                    $isShared = (isset($item['shared'])) ? $item['shared'] : $this->config->isShared($itemType);
                    $array[$key] = $isShared
                        ? $this->objectManager->get($itemType)
                        : $this->objectManager->create($itemType);
                } elseif (isset($item['argument'])) {
                    $array[$key] = isset($this->globalArguments[$item['argument']])
                        ? $this->globalArguments[$item['argument']]
                        : null;
                } else {
                    $this->parseArray($item);
                }
            }
        }
    }

    /**
     * Create instance with call time arguments
     *
     * @param string $requestedType
     * @param array $arguments
     * @return object
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function create($requestedType, array $arguments = array())
    {
        $type = $this->config->getInstanceType($requestedType);
        $parameters = $this->definitions->getParameters($type);
        if ($parameters == null) {
            return new $type();
        }
        if (isset($this->creationStack[$requestedType])) {
            $lastFound = end($this->creationStack);
            $this->creationStack = array();
            throw new \LogicException("Circular dependency: {$requestedType} depends on {$lastFound} and vice versa.");
        }
        $this->creationStack[$requestedType] = $requestedType;
        try {
            $args = $this->_resolveArguments($requestedType, $parameters, $arguments);
            unset($this->creationStack[$requestedType]);
        } catch (\Exception $e) {
            unset($this->creationStack[$requestedType]);
            throw $e;
        }

        $reflection = new \ReflectionClass($type);

        return $reflection->newInstanceArgs($args);
    }

    /**
     * Set global arguments
     *
     * @param array $arguments
     * @return void
     */
    public function setArguments($arguments)
    {
        $this->globalArguments = $arguments;
    }
}
