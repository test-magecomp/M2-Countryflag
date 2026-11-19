<?php
namespace Magecomp\Countryflag\Api\Data;

interface CountryPostDataInterface
{
    /**
     * @return bool
     */
    public function getStatus();

    /**
     * @param bool $value
     * @return $this
     */
    public function setStatus($value);

    /**
     * @return string|null
     */
    public function getMessage();

    /**
     * @param string|null $value
     * @return $this
     */
    public function setMessage($value);

    /**
     * @return \Magecomp\Countryflag\Api\Data\CountryItemInterface[]|null
     */
    public function getCollection();

    /**
     * @param \Magecomp\Countryflag\Api\Data\CountryItemInterface[] $value
     * @return $this
     */
    public function setCollection(array $value);
}
