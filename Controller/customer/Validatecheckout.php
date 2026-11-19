<?php
namespace Magecomp\Countryflag\Controller\customer;
 
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\JsonFactory;

class Validatecheckout extends \Magento\Framework\App\Action\Action
{
    protected $helperdata;
    protected $_jsonResultFactory;

    public function __construct(
        Context $context,
        \Magecomp\Countryflag\Helper\Data $helperdata,
        JsonFactory $jsonResultFactory,
        \Magento\Email\Model\Template\Filter $filter
    ) {
        $this->helperdata = $helperdata;
        $this->_jsonResultFactory = $jsonResultFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        try {
            $jsonResult = $this->_jsonResultFactory->create();
            $mobilenumber = $this->getRequest()->getParam('mobile');
            $countrycode = $this->getRequest()->getParam('countrycode');
            $country_id = $this->getRequest()->getParam('country_id');
            $moblenght = (int)$this->getRequest()->getParam('mobilelength');

            $validate = $this->helperdata->getCountryvalidation($countrycode);

            if ($validate !== false && $moblenght !== (int)$validate) {
                $jsonResult->setData([
                    'digit' => $validate,
                    'validate' => false,
                    'message' => __("Your Mobile Number Must be %1 digit long without country code.", $validate)
                ]);
                return $jsonResult;
            } else {
                $jsonResult->setData([
                    'digit' => $validate,
                    'validate' => true,
                    'message' => "",
                    'mobilenumber' => $mobilenumber
                ]);
                return $jsonResult;
            }
        } catch (\Exception $e) {
            $data = array("error");
            $jsonResult = $this->_jsonResultFactory->create();
            $jsonResult->setData($data);
            return $jsonResult;
        }
    }
}