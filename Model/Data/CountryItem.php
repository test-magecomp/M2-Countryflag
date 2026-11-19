<?php
namespace Magecomp\Countryflag\Model\Data;

use Magecomp\Countryflag\Api\Data\CountryItemInterface;
use Magento\Framework\DataObject;

class CountryItem extends DataObject implements CountryItemInterface
{
    public function getCountryCode()
    {
        return $this->getData('countryCode');
    }

    public function setCountryCode($countryCode)
    {
        return $this->setData('countryCode', $countryCode);
    }

    public function getDialCode()
    {
        return $this->getData('dialCode');
    }

    public function setDialCode($dialCode)
    {
        return $this->setData('dialCode', $dialCode);
    }

    public function getCountryName()
    {
        return $this->getData('countryName');
    }

    public function setCountryName($countryName)
    {
        return $this->setData('countryName', $countryName);
    }

    public function getImageUrl()
    {
        return $this->getData('imageUrl');
    }

    public function setImageUrl($imageUrl)
    {
        return $this->setData('imageUrl', $imageUrl);
    }
}
