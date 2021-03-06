<?php
/**
 * {license_notice}
 *
 * @copyright   {copyright}
 * @license     {license_link}
 */

namespace Mtf\TestSuite;

use Mtf\ObjectManager;
use Mtf\TestRunner\Process\ProcessManager;

/**
 * Class TestCase
 * This Test Suite class uses Test Case Iterator to collect appropriate Test Cases
 * as defined in TestCase Configuration
 *
 * @api
 */
class TestCase extends TestSuite
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var TestSuiteFactory
     */
    protected $testSuiteFactory;

    /**
     * @var array
     */
    protected $callback;

    /**
     * @var array
     */
    protected $callbackArguments = [];

    /**
     * @constructor
     * @param string $theClass
     * @param string $name
     */
    public function __construct($theClass = '', $name = '')
    {
        $this->initObjectManager();

        $this->testSuiteFactory = $this->objectManager->get('Mtf\TestSuite\TestSuiteFactory');

        /** @var $testIterator \Mtf\Util\Iterator\TestCase */
        $testIterator = $this->objectManager->create('Mtf\Util\Iterator\TestCase');
        while ($testIterator->valid()) {
            $arguments = $testIterator->current();

            $class = $arguments['class'];

            $factory = $this->testSuiteFactory;
            $testCallback = $this->objectManager->create(
                'Mtf\TestSuite\Callback',
                ['factory' => $factory, 'arguments' => $arguments, 'theClass' => $class]
            );
            $rule = $this->objectManager->get('Mtf\TestRunner\Rule\SuiteComposite');
            $testCaseSuite = $this->testSuiteFactory->get($class);
            $allow = $rule->filterSuite($testCaseSuite);
            if ($allow) {
                $this->addTest($testCallback, \PHPUnit_Util_Test::getGroups($class));
            }
            $testIterator->next();
        }

        parent::__construct($name);
    }

    /**
     * To execute callback if specified
     *
     * @param \PHPUnit_Framework_TestResult $result
     * @return \PHPUnit_Framework_TestResult
     */
    public function run(\PHPUnit_Framework_TestResult $result = null)
    {
        if ($this->callback) {
            $processManager = ProcessManager::factory();
            if ($processManager->isParallelModeSupported()) {
                $processManager->applyAppState($this->callback, $this->callbackArguments);
            } else {
                call_user_func_array($this->callback, $this->callbackArguments);
            }
        }

        return parent::run($result);
    }

    /**
     * Set callback
     *
     * @param array $callback
     * @param array $arguments
     * @return void
     */
    public function setCallback(array $callback, array $arguments = [])
    {
        $this->callback = $callback;
        $this->callbackArguments = $arguments;
    }

    /**
     * Initialize Object Manager
     * @return void
     */
    protected function initObjectManager()
    {
        if (!isset($this->objectManager)) {
            $this->objectManager = \Mtf\ObjectManager::getInstance();
        }
    }

    /**
     * Avoid attempt to serialize callback
     *
     * @return array
     */
    public function __sleep()
    {
        return [];
    }
}
