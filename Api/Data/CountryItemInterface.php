<?php
namespace Magecomp\Countryflag\Api\Data;

interface CountryItemInterface
{
    /**
     * @return string|null
     */
    public function getCountryCode();

    /**
     * @param string $countryCode
     * @return $this
     */
    public function setCountryCode($countryCode);

    /**
     * @return string|null
     */
    public function getDialCode();

    /**
     * @param string $dialCode
     * @return $this
     */
    public function setDialCode($dialCode);

    /**
     * @return string|null
     */
    public function getCountryName();

    /**
     * @param string $countryName
     * @return $this
     */
    public function setCountryName($countryName);

    /**
     * @return string|null
     */
    public function getImageUrl();

    /**
     * @param string $imageUrl
     * @return $this
     */
    public function setImageUrl($imageUrl);
}
