<?php

namespace Magecomp\Countryflag\Plugin;

use Magento\Contact\Controller\Index\Post;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magecomp\Countryflag\Helper\Data as HelperData;

class ContactPost
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
     * @var RedirectFactory
     */
    private $resultRedirectFactory;

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @param RequestInterface $request
     * @param ManagerInterface $messageManager
     * @param HelperData $helper
     * @param RedirectFactory $resultRedirectFactory
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        RequestInterface $request,
        ManagerInterface $messageManager,
        HelperData $helper,
        RedirectFactory $resultRedirectFactory,
        DataPersistorInterface $dataPersistor
    ) {
        $this->request = $request;
        $this->messageManager = $messageManager;
        $this->helper = $helper;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->dataPersistor = $dataPersistor;
    }

    /**
     * Around plugin for execute() method
     *
     * @param Post $subject
     * @param callable $proceed
     * @return mixed
     */
    public function aroundExecute(Post $subject, callable $proceed)
    {
        if (!$this->helper->isContactEnabled()) {
            return $proceed();
        }

        try {
            $postData = $this->request->getPostValue();
            $telephone = isset($postData['telephone']) ? trim((string)$postData['telephone']) : '';

            if ($telephone !== '') {
                $countryCode = isset($postData['countrycode']) ? trim((string)$postData['countrycode'], '+ ') : '';
                $countryIso = isset($postData['country_iso2']) ? strtoupper(trim((string)$postData['country_iso2'])) : '';

                // Extract numeric digits
                $digitsOnly = preg_replace('/[^0-9]/', '', $telephone);

                // If telephone starts with '+' and country code, isolate national digits
                if ($countryCode !== '' && strpos($telephone, '+') === 0 && strpos($digitsOnly, $countryCode) === 0) {
                    $nationalDigits = substr($digitsOnly, strlen($countryCode));
                } else {
                    $nationalDigits = $digitsOnly;
                }

                // Server-side digit validation based on selected country
                if ($countryIso !== '') {
                    $expectedDigits = $this->helper->getCountryvalidation($countryIso);
                    if ($expectedDigits && strlen($nationalDigits) !== (int)$expectedDigits) {
                        $this->messageManager->addErrorMessage(
                            __('Your Mobile Number Must be %1 digit long without country code.', $expectedDigits)
                        );
                        $this->dataPersistor->set('contact_us', $this->request->getParams());
                        return $this->resultRedirectFactory->create()->setPath('contact/index');
                    }
                }

                // Format phone number to ensure full international number with country code is stored & submitted
                if (!empty($postData['telephone_full']) && strpos($postData['telephone_full'], '+') === 0) {
                    $postData['telephone'] = trim($postData['telephone_full']);
                } elseif ($countryCode !== '' && strpos($telephone, '+') !== 0) {
                    $postData['telephone'] = '+' . $countryCode . $nationalDigits;
                } elseif (strpos($telephone, '+') === 0) {
                    $postData['telephone'] = '+' . $digitsOnly;
                }

                $this->request->setPostValue($postData);
                $this->request->setParams($postData);
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error processing telephone number: ' . $e->getMessage()));
        }

        return $proceed();
    }
}
