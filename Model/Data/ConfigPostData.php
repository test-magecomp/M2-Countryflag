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

    public function getCountrydigitvalidation()
    {
        return $this->getData('countrydigitvalidation');
    }

    public function setCountrydigitvalidation(array $value)
    {
        return $this->setData('countrydigitvalidation', $value);
    }
}
