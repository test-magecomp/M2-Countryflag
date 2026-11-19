<?php

namespace Magecomp\Countryflag\Api;

interface FlagInterface
{
    /**
     * Returns country collection
     * @api
     * @param int|null $storeid
     * @return \Magecomp\Countryflag\Api\Data\CountryPostDataInterface
     */
    public function countrycollection($storeid = null);

    /**
     * Get config value
     * @api
     * @param int|null $storeid
     * @return \Magecomp\Countryflag\Api\Data\ConfigPostDataInterface
     */
    public function getConfigvalue($storeid = null);
}
