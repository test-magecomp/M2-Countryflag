<?php
namespace Magecomp\Countryflag\Helper;
use Magento\Store\Model\ScopeInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const ENABLED = 'countryflag/general/enable';
    const ENABLE_CONTACT = 'countryflag/general/enable_contact';
    const PINNED_COUNTRIES = 'countryflag/general/pinned_countries';
    const BLOCKED_COUNTRIES = 'countryflag/general/blocked_countries';
    const DETECT_BY_IP = 'countryflag/general/detect_by_ip';
 
    const MOBILE_COUNTRY_VALIDATION = 'countryflag/countryflag/countryvalidate';
    const DEFAULT_COUNTRY = 'general/country/default';
    const COUNTRY_CODE_ALLOW = 'general/country/allow';
    const COUNTRY_CODE_PATH = 'general/country/default';

    protected $jsonHelper;
    protected $remoteaddress;
    protected $resolver;
    protected $_storeManager;
    protected $assetRepository;
    protected $httpFile;
    protected $curl;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteaddress,
        \Magento\Framework\Locale\Resolver $resolver,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\View\Asset\Repository $assetRepository,
        \Magento\Framework\Filesystem\Driver\Http $httpFile,
        \Magento\Framework\HTTP\Client\Curl $curl
    ) {
        $this->assetRepository = $assetRepository;
        $this->_storeManager = $storeManager;
        $this->jsonHelper = $jsonHelper;
        $this->remoteaddress = $remoteaddress;
        $this->resolver = $resolver;
        $this->httpFile = $httpFile;
        $this->curl = $curl;
        parent::__construct($context);
    }

    public function getDefualtCountry($storeid = null)
    {
         return $this->scopeConfig->getValue(self::DEFAULT_COUNTRY,
            ScopeInterface::SCOPE_STORE,
            $storeid);
    }

    public function getCountryvalidation($code)
    {
        $validation = $this->scopeConfig->getValue(self::MOBILE_COUNTRY_VALIDATION, ScopeInterface::SCOPE_STORE, $this->getStoreid());
        if (!$validation) {
            return 10;
        }
        $validation_array = json_decode($validation, true);
        if (!is_array($validation_array)) {
            return 10;
        }
        $validation_value = array_values($validation_array);

        if (($this->isEnabled() || $this->isContactEnabled()) && count($validation_value) > 0) {
            $defaultDigit = 10;
            foreach ($validation_value as $value) {
                if (isset($value['country']) && strtoupper((string)$value['country']) === strtoupper((string)$code)) {
                    return (int)$value['digit'];
                }
                if (isset($value['country']) && ($value['country'] == 1 || $value['country'] == "1")) {
                    $defaultDigit = (int)$value['digit'];
                }
            }
            return $defaultDigit;
        } else {
            return 10;
        }
    }

    public function getAllowCountry()
    {
        $selectcounty= $this->scopeConfig->getValue(
            self::COUNTRY_CODE_ALLOW,
            ScopeInterface::SCOPE_WEBSITES
        );
        return $selectcounty;
    }

    public function getApplicableCountry($isArray = false)
    {
        $_defaultCountry = [];
        $_country = [];
        $_defaultCountry[] = $this->getDefaultCountry();
        $_country = array_merge($_defaultCountry, explode(",", (string)$this->getAllowCountry()));
        if (!$isArray) {
            $_country = $this->jsonHelper->jsonEncode($_country);
        }

       return $_country;
    }

    public function getDefaultCountry()
    {
        return $this->scopeConfig->getValue(
            self::DEFAULT_COUNTRY,
            ScopeInterface::SCOPE_WEBSITES
        );
    }

    public function getMobileCountryValidation()
    {
        $validation = $this->scopeConfig->getValue(self::MOBILE_COUNTRY_VALIDATION, ScopeInterface::SCOPE_STORE);
        $validation_array = json_decode($validation, true);
        if (!is_array($validation_array)) {
            return json_encode([['digit' => 10, 'country' => 1]]);
        }
        $validation_value = array_values($validation_array);
        if (($this->isEnabled() || $this->isContactEnabled()) && count($validation_value) > 0) {
            return json_encode($validation_value);
        } else {
            return json_encode([['digit' => 10, 'country' => 1]]);
        }
    }

    public function getMobileValidation($storeid=null)
    {
        $validation = $this->scopeConfig->getValue(self::MOBILE_COUNTRY_VALIDATION, ScopeInterface::SCOPE_STORE,$storeid);
        $validation_array = json_decode($validation, true);
        if (!is_array($validation_array)) {
            return [['digit' => 10, 'country' => 1]];
        }
        return array_values($validation_array);
    }

    public function imagepath()
    {
        return $this->assetRepository->getUrl('Magecomp_Countryflag::img/flags.png');
    }

    public function getStoreid()
    {
        return $this->_storeManager->getStore()->getId();
    }

    public function isEnabled($storeid = null)
    {
        return $this->scopeConfig->getValue(
            self::ENABLED,ScopeInterface::SCOPE_STORE,$storeid
        );
    }

    public function isContactEnabled($storeid = null)
    {
        return (bool)$this->scopeConfig->getValue(
            self::ENABLE_CONTACT,
            ScopeInterface::SCOPE_STORE,
            $storeid
        );
    }

    /**
     * Get pinned countries as an array of unique lowercase ISO codes
     *
     * @param int|null $storeid
     * @return array
     */
    public function getPinnedCountries($storeid = null)
    {
        $pinned = $this->scopeConfig->getValue(
            self::PINNED_COUNTRIES,
            ScopeInterface::SCOPE_STORE,
            $storeid
        );

        if (empty($pinned)) {
            return [];
        }

        if (is_array($pinned)) {
            $countries = $pinned;
        } else {
            $countries = explode(',', (string)$pinned);
        }

        $result = [];
        foreach ($countries as $c) {
            $c = strtolower(trim((string)$c));
            if ($c !== '' && !in_array($c, $result, true)) {
                $result[] = $c;
            }
        }

        $allowed = $this->getAllowedCountries($storeid);
        $blocked = $this->getBlockedCountries($storeid);
        if (!empty($allowed) || !empty($blocked)) {
            $available = $this->getAvailableCountries($storeid);
            $result = array_values(array_intersect($result, $available));
        }

        return $result;
    }

    /**
     * Get pinned countries encoded as JSON array
     *
     * @param int|null $storeid
     * @return string
     */
    public function getPinnedCountriesJson($storeid = null)
    {
        return json_encode($this->getPinnedCountries($storeid));
    }

    /**
     * Get blocked countries as an array of unique lowercase ISO codes
     *
     * @param int|null $storeid
     * @return array
     */
    public function getBlockedCountries($storeid = null)
    {
        $blocked = $this->scopeConfig->getValue(
            self::BLOCKED_COUNTRIES,
            ScopeInterface::SCOPE_STORE,
            $storeid
        );

        if (empty($blocked)) {
            return [];
        }

        if (is_array($blocked)) {
            $countries = $blocked;
        } else {
            $countries = explode(',', (string)$blocked);
        }

        $result = [];
        foreach ($countries as $c) {
            $c = strtolower(trim((string)$c));
            if ($c !== '' && !in_array($c, $result, true)) {
                $result[] = $c;
            }
        }

        return $result;
    }

    /**
     * Get blocked countries encoded as JSON array
     *
     * @param int|null $storeid
     * @return string
     */
    public function getBlockedCountriesJson($storeid = null)
    {
        return json_encode($this->getBlockedCountries($storeid));
    }

    /**
     * Get Magento's allowed countries as an array of unique lowercase ISO codes
     *
     * @param int|null $storeid
     * @return array
     */
    public function getAllowedCountries($storeid = null)
    {
        $allowed = $this->scopeConfig->getValue(
            self::COUNTRY_CODE_ALLOW,
            ScopeInterface::SCOPE_STORE,
            $storeid
        );

        if (empty($allowed)) {
            return [];
        }

        if (is_array($allowed)) {
            $countries = $allowed;
        } else {
            $countries = explode(',', (string)$allowed);
        }

        $result = [];
        foreach ($countries as $c) {
            $c = strtolower(trim((string)$c));
            if ($c !== '' && !in_array($c, $result, true)) {
                $result[] = $c;
            }
        }

        return $result;
    }

    /**
     * Get available countries after applying Magento allowed-country and extension blocked-country rules
     *
     * @param int|null $storeid
     * @return array
     */
    public function getAvailableCountries($storeid = null)
    {
        $allowed = $this->getAllowedCountries($storeid);
        $blocked = $this->getBlockedCountries($storeid);

        if (!empty($allowed)) {
            $available = array_values(array_diff($allowed, $blocked));
            if (empty($available)) {
                $available = $allowed;
            }
        } elseif (!empty($blocked)) {
            $allCountryData = json_decode($this->getCountryData(), true);
            $all = is_array($allCountryData) ? array_map('strtolower', array_keys($allCountryData)) : [];
            $available = array_values(array_diff($all, $blocked));
        } else {
            $available = [];
        }

        return $available;
    }

    /**
     * Get the resolved initial country code (lowercase) guaranteed to be in available countries if restricted
     *
     * @param int|null $storeid
     * @return string
     */
    public function getInitialCountry($storeid = null)
    {
        $candidate = '';
        if ($this->getDetectByIp()) {
            $candidate = strtolower((string)$this->getCustomerIPDetails());
        }
        if (empty($candidate)) {
            $candidate = strtolower((string)$this->getDefualtCountry($storeid));
        }
        if (empty($candidate)) {
            $candidate = strtolower((string)$this->getDefaultCountry());
        }

        $available = $this->getAvailableCountries($storeid);
        if (!empty($available)) {
            if ($candidate !== '' && in_array($candidate, $available, true)) {
                return $candidate;
            }
            $pinned = $this->getPinnedCountries($storeid);
            foreach ($pinned as $p) {
                if (in_array($p, $available, true)) {
                    return $p;
                }
            }
            return $available[0];
        }

        return $candidate !== '' ? $candidate : 'us';
    }

    /**
     * Get available countries encoded as JSON array
     *
     * @param int|null $storeid
     * @return string
     */
    public function getAvailableCountriesJson($storeid = null)
    {
        return json_encode($this->getAvailableCountries($storeid));
    }

    public function getDetectByIp($storeid = null)
    {
        return $this->scopeConfig->getValue(
            self::DETECT_BY_IP,
            ScopeInterface::SCOPE_STORE,
            $storeid
        );
    }

    public function getTemplate()
    {
        if ($this->isEnabled()) {
            $template = 'Magecomp_Countryflag::form/edit.phtml';
        } else {
            $template = 'Magento_Customer::address/edit.phtml';
        }
        return $template;
    }
    
    public function getCustomerIPAddress()
    {
        $ip = null;

        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        }
        elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        }

        $ip = trim((string)$ip);

        if ($ip === '127.0.0.1' || $ip === '::1') {
            return '8.8.8.8';
        }

        return $ip;
    }

    public function getCustomerIPDetails()
    {
        try {
            $ip = $this->getCustomerIPAddress();

            $url = "https://ipwho.is/" . $ip;
            $this->curl->get($url);
            $response = $this->curl->getBody();
            $ipData = $this->jsonHelper->jsonDecode($response);

            if (isset($ipData['success']) && $ipData['success'] && !empty($ipData['country_code'])) {
                return $ipData['country_code'];
            }

            return $this->getDefualtCountry();
        } catch (\Exception $e) {
            return $this->getDefualtCountry();
        }
    }

    public function getCountryData()
    {
        $countrydialcode = [
            'AD'=>'376', 'AE'=>'971', 'AF'=>'93', 'AG'=>'1268', 'AI'=>'1264',
            'AL'=>'355', 'AM'=>'374', 'AN'=>'599', 'AO'=>'244', 'AQ'=>'672',
            'AR'=>'54', 'AS'=>'1684', 'AT'=>'43', 'AU'=>'61', 'AW'=>'297',
            'AZ'=>'994', 'BA'=>'387', 'BB'=>'1246', 'BD'=>'880', 'BE'=>'32',
            'BF'=>'226', 'BG'=>'359', 'BH'=>'973', 'BI'=>'257', 'BJ'=>'229',
            'BL'=>'590', 'BM'=>'1441', 'BN'=>'673', 'BO'=>'591', 'BR'=>'55',
            'BS'=>'1242', 'BT'=>'975', 'BW'=>'267', 'BY'=>'375', 'BZ'=>'501',
            'CA'=>'1', 'CC'=>'61', 'CD'=>'243', 'CF'=>'236', 'CG'=>'242',
            'CH'=>'41', 'CI'=>'225', 'CK'=>'682', 'CL'=>'56', 'CM'=>'237',
            'CN'=>'86', 'CO'=>'57', 'CR'=>'506', 'CU'=>'53', 'CV'=>'238',
            'CX'=>'61', 'CY'=>'357', 'CZ'=>'420', 'DE'=>'49', 'DJ'=>'253',
            'DK'=>'45', 'DM'=>'1767', 'DO'=>'1809', 'DZ'=>'213', 'EC'=>'593',
            'EE'=>'372', 'EG'=>'20', 'ER'=>'291', 'ES'=>'34', 'ET'=>'251',
            'FI'=>'358', 'FJ'=>'679', 'FK'=>'500', 'FM'=>'691', 'FO'=>'298',
            'FR'=>'33', 'GA'=>'241', 'GB'=>'44', 'GD'=>'1473', 'GE'=>'995',
            'GH'=>'233', 'GI'=>'350', 'GL'=>'299', 'GM'=>'220', 'GN'=>'224',
            'GQ'=>'240', 'GR'=>'30', 'GT'=>'502', 'GU'=>'1671', 'GW'=>'245',
            'GY'=>'592', 'HK'=>'852', 'HN'=>'504', 'HR'=>'385', 'HT'=>'509',
            'HU'=>'36', 'ID'=>'62', 'IE'=>'353', 'IL'=>'972', 'IM'=>'44',
            'IN'=>'91', 'IQ'=>'964', 'IR'=>'98', 'IS'=>'354', 'IT'=>'39',
            'JM'=>'1876', 'JO'=>'962', 'JP'=>'81', 'KE'=>'254', 'KG'=>'996',
            'KH'=>'855', 'KI'=>'686', 'KM'=>'269', 'KN'=>'1869', 'KP'=>'850',
            'KR'=>'82', 'KW'=>'965', 'KY'=>'1345', 'KZ'=>'7', 'LA'=>'856',
            'LB'=>'961', 'LC'=>'1758', 'LI'=>'423', 'LK'=>'94', 'LR'=>'231',
            'LS'=>'266', 'LT'=>'370', 'LU'=>'352', 'LV'=>'371', 'LY'=>'218',
            'MA'=>'212', 'MC'=>'377', 'MD'=>'373', 'ME'=>'382', 'MF'=>'1599',
            'MG'=>'261', 'MH'=>'692', 'MK'=>'389', 'ML'=>'223', 'MM'=>'95',
            'MN'=>'976', 'MO'=>'853', 'MP'=>'1670', 'MR'=>'222', 'MS'=>'1664',
            'MT'=>'356', 'MU'=>'230', 'MV'=>'960', 'MW'=>'265', 'MX'=>'52',
            'MY'=>'60', 'MZ'=>'258', 'NA'=>'264', 'NC'=>'687', 'NE'=>'227',
            'NG'=>'234', 'NI'=>'505', 'NL'=>'31', 'NO'=>'47', 'NP'=>'977',
            'NR'=>'674', 'NU'=>'683', 'NZ'=>'64', 'OM'=>'968', 'PA'=>'507',
            'PE'=>'51', 'PF'=>'689', 'PG'=>'675', 'PH'=>'63', 'PK'=>'92',
            'PL'=>'48', 'PM'=>'508', 'PN'=>'870', 'PR'=>'1', 'PT'=>'351',
            'PW'=>'680', 'PY'=>'595', 'QA'=>'974', 'RO'=>'40', 'RS'=>'381',
            'RU'=>'7', 'RW'=>'250', 'SA'=>'966', 'SB'=>'677', 'SC'=>'248',
            'SD'=>'249', 'SE'=>'46', 'SG'=>'65', 'SH'=>'290', 'SI'=>'386',
            'SK'=>'421', 'SL'=>'232', 'SM'=>'378', 'SN'=>'221', 'SO'=>'252',
            'SR'=>'597', 'ST'=>'239', 'SV'=>'503', 'SY'=>'963', 'SZ'=>'268',
            'TC'=>'1649', 'TD'=>'235', 'TG'=>'228', 'TH'=>'66', 'TJ'=>'992',
            'TK'=>'690', 'TL'=>'670', 'TM'=>'993', 'TN'=>'216', 'TO'=>'676',
            'TR'=>'90', 'TT'=>'1868', 'TV'=>'688', 'TW'=>'886', 'TZ'=>'255',
            'UA'=>'380', 'UG'=>'256', 'US'=>'1', 'UY'=>'598', 'UZ'=>'998',
            'VA'=>'39', 'VC'=>'1784', 'VE'=>'58', 'VG'=>'1284', 'VI'=>'1340',
            'VN'=>'84', 'VU'=>'678', 'WF'=>'681', 'WS'=>'685', 'XK'=>'381',
            'YE'=>'967', 'YT'=>'262', 'ZA'=>'27', 'ZM'=>'260', 'ZW'=>'263'
        ];
        return json_encode($countrydialcode);
    }

    public function arraychange($array){
        foreach ($array as $key => $val) {
            if (isset($val["country"]) && ($val["country"] == 1 || $val["country"] == "1")) {
                $item = $array[$key];
                unset($array[$key]);
                array_push($array, $item);
                break;
            }
        }
        return $array;
    }

    public function getCountryCode()
    {
        return $this->resolver->getLocale();
    }
}
