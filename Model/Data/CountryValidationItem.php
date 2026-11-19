<?php
namespace Magecomp\Countryflag\Model\Data;

use Magecomp\Countryflag\Api\Data\CountryValidationItemInterface;
use Magento\Framework\DataObject;

class CountryValidationItem extends DataObject implements CountryValidationItemInterface
{
    public function getDigit()
    {
        return $this->getData('digit');
    }

    public function setDigit($digit)
    {
        return $this->setData('digit', $digit);
    }

    public function getCountry()
    {
        return $this->getData('country');
    }

    public function setCountry($country)
    {
        return $this->setData('country', $country);
    }
}
