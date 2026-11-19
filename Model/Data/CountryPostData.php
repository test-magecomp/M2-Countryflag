<?php
namespace Magecomp\Countryflag\Model\Data;

use Magecomp\Countryflag\Api\Data\CountryPostDataInterface;
use Magento\Framework\DataObject;

class CountryPostData extends DataObject implements CountryPostDataInterface
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

    public function getCollection()
    {
        return $this->getData('collection');
    }

    public function setCollection(array $value)
    {
        return $this->setData('collection', $value);
    }
}
