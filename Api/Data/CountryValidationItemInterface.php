<?php
namespace Magecomp\Countryflag\Api\Data;

interface CountryValidationItemInterface
{
    /**
     * @return string|null
     */
    public function getDigit();

    /**
     * @param string $digit
     * @return $this
     */
    public function setDigit($digit);

    /**
     * @return string|null
     */
    public function getCountry();

    /**
     * @param string $country
     * @return $this
     */
    public function setCountry($country);
}
