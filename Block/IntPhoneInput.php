<?php
namespace Magecomp\Countryflag\Block;

class IntPhoneInput extends \Magento\Framework\View\Element\Template
{

    protected $_helper;
    protected $_objectManager;
    protected $remoteaddress;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magecomp\Countryflag\Helper\Data $helper,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteaddress,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->remoteaddress=$remoteaddress;
        $this->_helper = $helper;
        $this->_objectManager = $objectManager;
    }

    public function getDetectByIp()
    {
        return $this->_helper->getDetectByIp();
    }
    public function getMobileCountryValidation()
    {
        return $this->_helper->getMobileCountryValidation();
    }
    public function getApplicableCountry()
    {
        return $this->_helper->getApplicableCountry();
    }

    public function isEnabled($storeid = null)
    {
        return $this->_helper->isEnabled($storeid);
    }
    public function isContactEnabled($storeid = null)
    {
        return $this->_helper->isContactEnabled($storeid);
    }
    public function getPinnedCountries($storeid = null)
    {
        return $this->_helper->getPinnedCountries($storeid);
    }
    public function getPinnedCountriesJson($storeid = null)
    {
        return $this->_helper->getPinnedCountriesJson($storeid);
    }
    public function getBlockedCountries($storeid = null)
    {
        return $this->_helper->getBlockedCountries($storeid);
    }
    public function getBlockedCountriesJson($storeid = null)
    {
        return $this->_helper->getBlockedCountriesJson($storeid);
    }
    public function getAllowedCountries($storeid = null)
    {
        return $this->_helper->getAllowedCountries($storeid);
    }
    public function getAvailableCountries($storeid = null)
    {
        return $this->_helper->getAvailableCountries($storeid);
    }
    public function getAvailableCountriesJson($storeid = null)
    {
        return $this->_helper->getAvailableCountriesJson($storeid);
    }
    public function getDefualtCountry($storeid = null)
    {
        return $this->_helper->getDefualtCountry($storeid);
    }
    public function getInitialCountry($storeid = null)
    {
        return $this->_helper->getInitialCountry($storeid);
    }
    public function getCountryData()
    {
        return $this->_helper->getCountryData();
    }
    public function getCustomerIPAddress()
    {
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
        $local = ($remoteAddr == '127.0.0.1' || $remoteAddr == '::1' || empty($remoteAddr)) ? true : false;
        if ($local) {
            return '8.8.8.8';
        }
        return $this->remoteaddress->getRemoteAddress();
    }
    public function getCustomerIPDetails()
    {
        return $this->_helper->getCustomerIPDetails();
    }
}