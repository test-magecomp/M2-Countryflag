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
     * @return string|null
     */
    public function getEnableContact();

    /**
     * @param string|null $value
     * @return $this
     */
    public function setEnableContact($value);

    /**
     * @return string|null
     */
    public function getInitialCountry();

    /**
     * @param string|null $value
     * @return $this
     */
    public function setInitialCountry($value);

    /**
     * @return string|null
     */
    public function getDefaultCountry();

    /**
     * @param string|null $value
     * @return $this
     */
    public function setDefaultCountry($value);

    /**
     * @return string[]|null
     */
    public function getPinnedCountries();

    /**
     * @param string[]|null $value
     * @return $this
     */
    public function setPinnedCountries($value);

    /**
     * @return string[]|null
     */
    public function getBlockedCountries();

    /**
     * @param string[]|null $value
     * @return $this
     */
    public function setBlockedCountries($value);

    /**
     * @return string[]|null
     */
    public function getAvailableCountries();

    /**
     * @param string[]|null $value
     * @return $this
     */
    public function setAvailableCountries($value);

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
