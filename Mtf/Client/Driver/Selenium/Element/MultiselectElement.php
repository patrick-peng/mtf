<?php
/**
 * {license_notice}
 *
 * @copyright   {copyright}
 * @license     {license_link}
 */

namespace Mtf\Client\Driver\Selenium\Element;

/**
 * Class MultiselectElement
 * Class provides ability to work with page element multiselect
 * (Such as setting/getting value, clicking, drag-n-drop element, etc)
 *
 * @api
 */
class MultiselectElement extends SelectElement
{
    /**
     * Wrapped Selenium element
     *
     * @var \PHPUnit_Extensions_Selenium2TestCase_Element_Select
     */
    protected $_wrappedElement;

    /**
     * Return Wrapped Element.
     * If element was not created before:
     * 1. Context is defined. If context was not passed to constructor - test case (all page) is taken as context
     * 2. Attempt to get selenium element is performed in loop
     * that is terminated if element is found or after timeout set in configuration
     *
     * @param bool $waitForElementPresent
     * @return \PHPUnit_Extensions_Selenium2TestCase_Element_Select
     * @throws \PHPUnit_Extensions_Selenium2TestCase_WebDriverException
     */
    protected function _getWrappedElement($waitForElementPresent = true)
    {
        return $this->_driver->select(parent::_getWrappedElement($waitForElementPresent));
    }

    /**
     * Get value of the selected option of the element
     *
     * @return array
     */
    public function getValue()
    {
        $this->_eventManager->dispatchEvent(['get_value'], [(string) $this->_locator]);
        return $this->_getWrappedElement()->selectedLabels();
    }

    /**
     * Get Selected Options Labels
     *
     * @return array
     */
    public function getText()
    {
        return $this->_getWrappedElement()->selectedLabels();
    }

    /**
     * Select Options by Label in Multiple Select
     *
     * @param string|array $values
     * @return void
     */
    public function setValue($values)
    {
        $this->_eventManager->dispatchEvent(['set_value'], [__METHOD__, $this->getAbsoluteSelector()]);
        $this->clearSelectedOptions();
        foreach ((array)$values as $label) {
            $criteria = new \PHPUnit_Extensions_Selenium2TestCase_ElementCriteria('xpath');
            $criteria->value('.//option[contains(text(), "' . $label . '")]');
            $this->_getWrappedElement()->selectOptionByCriteria($criteria);
        }
    }

    /**
     * Clear selected options in multiple select
     *
     * @return void
     */
    public function clearSelectedOptions()
    {
        $this->_getWrappedElement()->clearSelectedOptions();
    }
}
