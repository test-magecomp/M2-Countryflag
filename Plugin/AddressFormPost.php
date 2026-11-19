<?php

namespace Magecomp\Countryflag\Plugin;

use Magento\Customer\Controller\Address\FormPost;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use Magecomp\Countryflag\Helper\Data as HelperData;

class AddressFormPost
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var HelperData
     */
    private $helper;

    /**
     * Constructor
     *
     * @param RequestInterface $request
     * @param ManagerInterface $messageManager
     * @param HelperData $helper
     */
    public function __construct(
        RequestInterface $request,
        ManagerInterface $messageManager,
        HelperData $helper
    ) {
        $this->request = $request;
        $this->messageManager = $messageManager;
        $this->helper = $helper;
    }

    /**
     * Around plugin for execute() method
     *
     * @param FormPost $subject
     * @param callable $proceed
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function aroundExecute(FormPost $subject, callable $proceed)
    {
        if (!$this->helper->isEnabled()) {
            return $proceed();
        }

        try {
            $postData = $this->request->getPostValue();
            if (isset($postData['countrycode']) && isset($postData['telephone'])) {
                $countryCode = $postData['countrycode'];
                $mobileNo = $postData['telephone'];
                $postData['telephone'] = '+'.$countryCode . $mobileNo;              
                $this->request->setPostValue($postData);
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error updating phone number: ' . $e->getMessage()));
        }

        return $proceed();
    }
}
