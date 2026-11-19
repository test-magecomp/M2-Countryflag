<?php
namespace Magecomp\Countryflag\Api\Data;

interface ConfigPostDataInterface
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
     * @return string|null
     */
    public function getCountryflagipstatus();

    /**
     * @param string|null $value
     * @return $this
     */
    public function setCountryflagipstatus($value);

    /**
     * @return \Magecomp\Countryflag\Api\Data\CountryValidationItemInterface[]|null
     */
    public function getCountrydigitvalidation();

    /**
     * @param \Magecomp\Countryflag\Api\Data\CountryValidationItemInterface[] $value
     * @return $this
     */
    public function setCountrydigitvalidation(array $value);
}
