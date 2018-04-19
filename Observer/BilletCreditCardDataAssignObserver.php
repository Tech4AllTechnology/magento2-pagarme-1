<?php
/**
 * Class CreditCardDataAssignObserver
 *
 * @author      PagarMe Modules Team <modules@pagar.me>
 * @copyright   2018 PagarMe (http://pagar.me)
 * @license     http://pagar.me Copyright
 *
 * @link        http://pagar.me
 */

namespace PagarMe\PagarMe\Observer;


use Magento\Framework\DataObject;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Framework\Event\Observer;
use Magento\Quote\Api\Data\PaymentInterface;
use PagarMe\PagarMe\Helper\ModuleHelper;

class BilletCreditCardDataAssignObserver extends AbstractDataAssignObserver
{
    private $moduleHelperCreditCard;

    /**
     * @param InstallmentsByBrandManagementInterface $installmentsInterface
     */
    public function __construct(
        ModuleHelper $moduleHelperCreditCard
    )
    {
        $this->moduleHelperCreditCard = $moduleHelperCreditCard;
    }

    public function execute(Observer $observer)
    {
        $method = $this->readMethodArgument($observer);
        $info = $method->getInfoInstance();
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        if (!is_object($additionalData)) {
            $additionalData = new DataObject($additionalData ?: []);
        }

        $info->setAdditionalInformation('cc_saved_card', '0');

        if ($additionalData->getCcSavedCard()) {
            $card = $this->moduleHelperCreditCard->getById($additionalData->getCcSavedCard());
            $info->setAdditionalInformation('cc_saved_card', $additionalData->getCcSavedCard());
            $info->setAdditionalInformation('cc_cc_amount', $additionalData->getCcCcAmount());
            $info->setAdditionalInformation('cc_cc_tax_amount', $additionalData->getCcCcTaxAmount());
            $info->setAdditionalInformation('cc_type', $card->getBrand());
            $info->setAdditionalInformation('cc_last_4', $card->getLastFourNumbers());

            $info->addData([
                'cc_cc_amount' => $additionalData->getCcCcAmount(),
                'cc_billet_amount' => $additionalData->getCcBilletAmount()
            ]);
        }else{

            $info->setAdditionalInformation('cc_cc_amount', $additionalData->getCcCcAmount());
            $info->setAdditionalInformation('cc_cc_tax_amount', $additionalData->getCcCcTaxAmount());
            $info->setAdditionalInformation('cc_type', $additionalData->getCcType());
            $info->setAdditionalInformation('cc_last_4', $additionalData->getCcLast4());
            $info->setAdditionalInformation('cc_token_credit_card', $additionalData->getCcTokenCreditCard());

            $info->addData([
                'cc_type' => $additionalData->getCcType(),
                'cc_owner' => $additionalData->getCcOwner(),
                'cc_last_4' => $additionalData->getCcLast4(),
                'cc_exp_month' => $additionalData->getCcExpMonth(),
                'cc_exp_year' => $additionalData->getCcExpYear(),
                'cc_token_credit_card' => $additionalData->getCcTokenCreditCard(),
                'cc_cc_amount' => $additionalData->getCcCcAmount(),
                'cc_billet_amount' => $additionalData->getCcBilletAmount()
            ]);
            
            $info->setAdditionalInformation('cc_savecard', $additionalData->getCcSavecard());
        }

        $info->setAdditionalInformation('cc_installments', 1);
        $info->setAdditionalInformation('cc_cc_amount', $additionalData->getCcCcAmount());
        $info->setAdditionalInformation('cc_billet_amount', $additionalData->getCcBilletAmount());

        if ($additionalData->getCcInstallments()) {
            $info->setAdditionalInformation('cc_installments', (int) $additionalData->getCcInstallments());
        }

        return $this;
    }
}
