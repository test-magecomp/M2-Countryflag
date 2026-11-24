<?php

namespace Magecomp\Countryflag\Model\Api;

use Magecomp\Countryflag\Api\FlagInterface;
use Magecomp\Countryflag\Api\Data\CountryPostDataInterfaceFactory;
use Magecomp\Countryflag\Api\Data\CountryItemInterfaceFactory;
use Magecomp\Countryflag\Api\Data\ConfigPostDataInterfaceFactory;
use Magecomp\Countryflag\Api\Data\CountryValidationItemInterfaceFactory;

class Flag implements FlagInterface
{
    protected $helper;
    protected $block;
    protected $allowedcountries;
    protected $countryFactory;
    protected $_themeProvider;
    protected $_storeManager;
    protected $_scopeConfig;
    protected $countryPostDataFactory;
    protected $countryItemFactory;
    protected $configPostDataFactory;
    protected $validationItemFactory;

    public function __construct(
        \Magento\Directory\Model\AllowedCountries $allowedcountries,
        \Magento\Directory\Model\CountryFactory $countryFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $_scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $_storeManager,
        \Magento\Framework\View\Design\Theme\ThemeProviderInterface $_themeProvider,
        \Magecomp\Countryflag\Helper\Data $helper,
        \Magecomp\Countryflag\Block\IntPhoneInput $block,
        CountryPostDataInterfaceFactory $countryPostDataFactory,
        CountryItemInterfaceFactory $countryItemFactory,
        ConfigPostDataInterfaceFactory $configPostDataFactory,
        CountryValidationItemInterfaceFactory $validationItemFactory
    ) {
        $this->allowedcountries = $allowedcountries;
        $this->countryFactory = $countryFactory;
        $this->_themeProvider = $_themeProvider;
        $this->_storeManager = $_storeManager;
        $this->_scopeConfig = $_scopeConfig;
        $this->block = $block;
        $this->helper = $helper;
        $this->countryPostDataFactory = $countryPostDataFactory;
        $this->countryItemFactory = $countryItemFactory;
        $this->configPostDataFactory = $configPostDataFactory;
        $this->validationItemFactory = $validationItemFactory;
    }

    public function countrycollection($storeid = null)
    {
        $response = $this->countryPostDataFactory->create();

        try {
            if ($this->helper->isEnabled($storeid)) {
                $themename = 'Magento/blank';
                $themeId = $this->_scopeConfig->getValue(
                    \Magento\Framework\View\DesignInterface::XML_PATH_THEME_ID,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $this->_storeManager->getStore()->getId()
                );

                if ($themeId) {
                    try {
                        $theme = $this->_themeProvider->getThemeById($themeId);
                        if ($theme && $theme->getThemePath()) {
                            $themename = $theme->getThemePath();
                        }
                    } catch (\Exception $e) {
                        // fallback to blank
                    }
                }

                $url = $this->helper->imagepath();
                $imageurl = str_replace('/webapi_rest/_view/', '/frontend/' . $themename . '/', $url);

                $countrydialcode = json_decode($this->helper->getCountryData(), true);
                if (!is_array($countrydialcode)) {
                    $countrydialcode = [];
                }

                $availableCountries = $this->helper->getAvailableCountries($storeid);
                $pinnedCountries = $this->helper->getPinnedCountries($storeid);
                $blockedCountries = $this->helper->getBlockedCountries($storeid);

                if (!empty($availableCountries)) {
                    $countryCodesToLoad = array_map('strtoupper', $availableCountries);
                } else {
                    $countryCodesToLoad = $this->allowedcountries->getAllowedCountries();
                }

                $pinnedItems = [];
                $regularItems = [];

                foreach ($countryCodesToLoad as $countryCode) {
                    $countryCodeUpper = strtoupper((string)$countryCode);
                    $countryCodeLower = strtolower((string)$countryCode);

                    if ($countryCodeUpper && isset($countrydialcode[$countryCodeUpper])) {
                        $countryModel = $this->countryFactory->create()->loadByCode($countryCodeUpper);
                        $countryItem = $this->countryItemFactory->create();
                        $countryItem->setCountryCode($countryCodeUpper);
                        $countryItem->setDialCode($countrydialcode[$countryCodeUpper]);
                        $countryItem->setCountryName($countryModel->getName());
                        $countryItem->setImageUrl($imageurl);

                        $isPinned = in_array($countryCodeLower, $pinnedCountries, true);
                        $countryItem->setIsPinned($isPinned);

                        $isBlocked = in_array($countryCodeLower, $blockedCountries, true);
                        $countryItem->setIsBlocked($isBlocked);

                        if ($isPinned) {
                            $pinnedItems[$countryCodeLower] = $countryItem;
                        } else {
                            $regularItems[] = $countryItem;
                        }
                    }
                }

                // Preserve pinned country order as configured in admin
                $orderedPinned = [];
                foreach ($pinnedCountries as $pCode) {
                    if (isset($pinnedItems[$pCode])) {
                        $orderedPinned[] = $pinnedItems[$pCode];
                        unset($pinnedItems[$pCode]);
                    }
                }
                foreach ($pinnedItems as $remainingPinned) {
                    $orderedPinned[] = $remainingPinned;
                }

                // Sort regular items alphabetically by country name
                usort($regularItems, function ($a, $b) {
                    return strcasecmp((string)$a->getCountryName(), (string)$b->getCountryName());
                });

                $collection = array_merge($orderedPinned, $regularItems);

                $response->setStatus(true);
                $response->setMessage("Fetch country collection successfully.");
                $response->setCollection($collection);
            } else {
                $response->setStatus(false);
                $response->setMessage("Please Enable International Flag Input");
            }
        } catch (\Exception $e) {
            $response->setStatus(false);
            $response->setMessage($e->getMessage());
        }

        return $response;
    }

    public function getConfigvalue($storeid = null)
    {
        $response = $this->configPostDataFactory->create();

        try {
            if ($this->helper->isEnabled($storeid)) {
                $ipStatus = $this->helper->getDetectByIp($storeid);
                $validation = $this->helper->getMobileValidation($storeid);

                $validationItems = [];
                if (is_array($validation)) {
                    foreach ($validation as $row) {
                        if (is_array($row)) {
                            $item = $this->validationItemFactory->create();
                            $item->setDigit(isset($row['digit']) ? (string)$row['digit'] : '');
                            $item->setCountry(isset($row['country']) ? (string)$row['country'] : '');
                            $validationItems[] = $item;
                        }
                    }
                }

                $response->setStatus(true);
                $response->setCountryflagipstatus($ipStatus ? "1" : "0");
                $response->setEnableContact($this->helper->isContactEnabled($storeid) ? "1" : "0");
                $response->setInitialCountry($this->helper->getInitialCountry($storeid));
                $response->setDefaultCountry(strtolower((string)$this->helper->getDefualtCountry($storeid)));
                $response->setPinnedCountries($this->helper->getPinnedCountries($storeid));
                $response->setBlockedCountries($this->helper->getBlockedCountries($storeid));
                $response->setAvailableCountries($this->helper->getAvailableCountries($storeid));
                $response->setCountrydigitvalidation($validationItems);
                $response->setMessage("Fetch configuration successfully.");
            } else {
                $response->setStatus(false);
                $response->setMessage("Please enable Countryflag service.");
            }
        } catch (\Exception $e) {
            $response->setStatus(false);
            $response->setMessage($e->getMessage());
        }

        return $response;
    }
}
