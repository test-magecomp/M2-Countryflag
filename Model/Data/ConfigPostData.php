<?php
namespace Magecomp\Countryflag\Model\Data;

use Magecomp\Countryflag\Api\Data\ConfigPostDataInterface;
use Magento\Framework\DataObject;

class ConfigPostData extends DataObject implements ConfigPostDataInterface
{
    public function getStatus()
    {
        return $this->getData('status');
    }

    public function setStatus($value)
    {
        return $this->setData('status', $value);
    }

    public function getMessage()
    {
        return $this->getData('message');
    }

    public function setMessage($value)
    {
        return $this->setData('message', $value);
    }

    public function getCountryflagipstatus()
    {
        return $this->getData('countryflagipstatus');
    }

    public function setCountryflagipstatus($value)
    {
        return $this->setData('countryflagipstatus', $value);
    }

    public function getEnableContact()
    {
        return $this->getData('enableContact');
    }

    public function setEnableContact($value)
    {
        return $this->setData('enableContact', $value);
    }

    public function getInitialCountry()
    {
        return $this->getData('initialCountry');
    }

    public function setInitialCountry($value)
    {
        return $this->setData('initialCountry', $value);
    }

    public function getDefaultCountry()
    {
        return $this->getData('defaultCountry');
    }

    public function setDefaultCountry($value)
    {
        return $this->setData('defaultCountry', $value);
    }

    public function getPinnedCountries()
    {
        return $this->getData('pinnedCountries');
    }

    public function setPinnedCountries($value)
    {
        return $this->setData('pinnedCountries', $value);
    }

    public function getBlockedCountries()
    {
        return $this->getData('blockedCountries');
    }

    public function setBlockedCountries($value)
    {
        return $this->setData('blockedCountries', $value);
    }

    public function getAvailableCountries()
    {
        return $this->getData('availableCountries');
    }

    public function setAvailableCountries($value)
    {
        return $this->setData('availableCountries', $value);
    }

    public function getCountrydigitvalidation()
    {
        return $this->getData('countrydigitvalidation');
    }

    public function setCountrydigitvalidation(array $value)
    {
        return $this->setData('countrydigitvalidation', $value);
    }
}
