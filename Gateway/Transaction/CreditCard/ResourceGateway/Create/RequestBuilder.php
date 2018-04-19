<?php
/**
 * Class RequestBuilder
 *
 * @author      PagarMe Modules Team <modules@pagar.me>
 * @copyright   2018 PagarMe (http://pagar.me)
 * @license     http://pagar.me Copyright
 *
 * @link        http://pagar.me
 */

namespace PagarMe\PagarMe\Gateway\Transaction\CreditCard\ResourceGateway\Create;

use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Model\Order\Item;
use PagarMe\PagarMe\Api\CartItemRequestDataProviderInterface;
use PagarMe\PagarMe\Api\CreditCardRequestDataProviderInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use MundiAPILib\Models\CreateOrderRequest as Request;
use PagarMe\PagarMe\Api\CreditCardRequestDataProviderInterfaceFactory;
use PagarMe\PagarMe\Api\CartItemRequestDataProviderInterfaceFactory;
use Magento\Checkout\Model\Cart;
use PagarMe\PagarMe\Gateway\Transaction\Base\Config\Config;
use PagarMe\PagarMe\Gateway\Transaction\CreditCard\Config\Config as ConfigCreditCard;
use PagarMe\PagarMe\Helper\ModuleHelper;
use PagarMe\PagarMe\Helper\Cards\CreateCard;
use PagarMe\PagarMe\Helper\Logger;

class RequestBuilder implements BuilderInterface
{

    const MODULE_NAME = 'PagarMe_PagarMe';
    const NAME_METADATA = 'Magento 2';
    const SHIPPING = 1;
    const BILLING = 0;

    protected $request;
    protected $requestDataProviderFactory;
    protected $cartItemRequestDataProviderFactory;
    protected $orderAdapter;
    protected $paymentData;
    protected $cart;
    protected $config;
    protected $configCreditCard;
    protected $moduleHelper;
    protected $createCrad;
    protected $logger;

    /**
     * RequestBuilder constructor.
     * @param Request $request
     * @param CreditCardRequestDataProviderInterfaceFactory $requestDataProviderFactory
     * @param CartItemRequestDataProviderInterfaceFactory $cartItemRequestDataProviderFactory
     * @param Cart $cart
     * @param Config $config
     * @param ConfigCreditCard $configCreditCard
     * @param ModuleHelper $moduleHelper
     */
    public function __construct(
        Request $request,
        CreditCardRequestDataProviderInterfaceFactory $requestDataProviderFactory,
        CartItemRequestDataProviderInterfaceFactory $cartItemRequestDataProviderFactory,
        Cart $cart,
        Config $config,
        ConfigCreditCard $configCreditCard,
        ModuleHelper $moduleHelper,
        CreateCard $createCrad,
        Logger $logger
    )
    {
        $this->setRequest($request);
        $this->setRequestDataProviderFactory($requestDataProviderFactory);
        $this->setCartItemRequestProviderFactory($cartItemRequestDataProviderFactory);
        $this->setCart($cart);
        $this->setConfig($config);
        $this->setConfigCreditCard($configCreditCard);
        $this->setModuleHelper($moduleHelper);
        $this->setCreateCardHelper($createCrad);
        $this->setLogger($logger);
    }

    /**
     * {@inheritdoc}
     */
    public function build(array $buildSubject)
    {
        if (!isset($buildSubject['payment']) || !$buildSubject['payment'] instanceof PaymentDataObjectInterface) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $paymentDataObject */
        $paymentDataObject = $buildSubject['payment'];

        $this->setOrderAdapter($paymentDataObject->getOrder());
        $this->setPaymentData($paymentDataObject->getPayment());

        $requestDataProvider = $this->createRequestDataProvider();

        return $this->createNewRequest($requestDataProvider);

    }

    /**
     * @param Request $request
     * @return $this
     */
    protected function setRequest(Request $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @return Request
     */
    protected function getRequest()
    {
        return $this->request;
    }

    /**
     * @return CreditCardRequestDataProviderInterface
     */
    protected function createRequestDataProvider()
    {
        return $this->getRequestDataProviderFactory()->create([
            'orderAdapter' => $this->getOrderAdapter(),
            'payment' => $this->getPaymentData()
        ]);
    }

    /**
     * @param Item $item
     * @return CartItemRequestDataProviderInterface
     */
    protected function createCartItemRequestDataProvider(Item $item)
    {
        return $this->getCartItemRequestProviderFactory()->create([
            'item' => $item
        ]);
    }

    /**
     * @return RequestDataProviderFactory
     */
    protected function getRequestDataProviderFactory()
    {
        return $this->requestDataProviderFactory;
    }

    /**
     * @param CreditCardRequestDataProviderInterfaceFactory $requestDataProviderFactory
     * @return RequestBuilder
     */
    protected function setRequestDataProviderFactory(CreditCardRequestDataProviderInterfaceFactory $requestDataProviderFactory)
    {
        $this->requestDataProviderFactory = $requestDataProviderFactory;
        return $this;
    }

    /**
     * @return CartItemRequestDataProviderInterfaceFactory
     */
    protected function getCartItemRequestProviderFactory()
    {
        return $this->cartItemRequestDataProviderFactory;
    }

    /**
     * @param CartItemRequestDataProviderInterfaceFactory $cartItemRequestDataProviderFactory
     * @return self
     */
    protected function setCartItemRequestProviderFactory(CartItemRequestDataProviderInterfaceFactory $cartItemRequestDataProviderFactory)
    {
        $this->cartItemRequestDataProviderFactory = $cartItemRequestDataProviderFactory;
        return $this;
    }


    /**
     * @return OrderAdapterInterface
     */
    protected function getOrderAdapter()
    {
        return $this->orderAdapter;
    }

    /**
     * @param OrderAdapterInterface $orderAdapter
     * @return $this
     */
    protected function setOrderAdapter(OrderAdapterInterface $orderAdapter)
    {
        $this->orderAdapter = $orderAdapter;
        return $this;
    }

    /**
     * @return InfoInterface
     */
    public function getPaymentData()
    {
        return $this->paymentData;
    }

    /**
     * @param InfoInterface $paymentData
     * @return $this
     */
    protected function setPaymentData(InfoInterface $paymentData)
    {
        $this->paymentData = $paymentData;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getCart()
    {
        return $this->cart;
    }

    /**
     * @param $cart
     */
    public function setCart($cart)
    {
        $this->cart = $cart;
    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return mixed
     */
    public function setConfig($config)
    {
        $this->config = $config;
        
        return $this;
    }

    /**
     * @return mixed
     */
    public function getConfigCreditCard()
    {
        return $this->configCreditCard;
    }

    /**
     * @return mixed
     */
    public function setConfigCreditCard($configCreditCard)
    {
        $this->configCreditCard = $configCreditCard;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getModuleHelper()
    {
        return $this->moduleHelper;
    }

    /**
     * @return mixed
     */
    public function setModuleHelper($moduleHelper)
    {
        $this->moduleHelper = $moduleHelper;

        return $this;
    }

    /**
     * @return \MundiAPILib\MundiAPIClient
     */
    public function getApi()
    {
        return new \MundiAPILib\MundiAPIClient($this->getConfig()->getSecretKey(), '');
    }

    /**
     * @return \MundiAPILib\Models\CreateOrderRequest
     */
    public function getOrderRequest()
    {
        return new \MundiAPILib\Models\CreateOrderRequest();
    }

    /**
     * @param $requestDataProvider
     * @return mixed
     */
    protected function createNewRequest($requestDataProvider)
    {

        $quote = $this->getCart()->getQuote();

        $payment = $quote->getPayment();

        $statement = $this->getConfigCreditCard()->getSoftDescription();

        $order = $this->getOrderRequest();
        if($this->getConfigCreditCard()->getPaymentAction() == 'authorize_capture')
        {
            $capture = true;
        }else{
            $capture = false;
        }
        
        if ($payment->getAdditionalInformation('cc_saved_card')) {
            
            $model = $this->getCreateCardHelper();
            $card = $model->getById($payment->getAdditionalInformation('cc_saved_card'));

            $order->payments = [
                [
                    'payment_method' => 'credit_card',
                    'amount' => $requestDataProvider->getAmountInCents(),
                    'credit_card' => [
                        'recurrence' => false,
                        'installments' => $requestDataProvider->getInstallmentCount(),
                        'statement_descriptor' => $statement,
                        'capture' => $capture,
                        'card_id' => $card->getCardToken(),
                        'card' => [
                            'billing_address' => [
                                'street' => $requestDataProvider->getCustomerAddressStreet(self::BILLING),
                                'number' => $requestDataProvider->getCustomerAddressNumber(self::BILLING),
                                'complement' => $requestDataProvider->getCustomerAddressComplement(self::BILLING),
                                'zip_code' => trim(str_replace('-','',$quote->getBillingAddress()->getPostCode())),
                                'neighborhood' => $requestDataProvider->getCustomerAddressDistrict(self::BILLING),
                                'city' => $quote->getBillingAddress()->getCity(),
                                'state' => $quote->getBillingAddress()->getRegionCode(),
                                'country' => $quote->getBillingAddress()->getCountryId()
                            ]
                        ]
                    ]
                ]
            ];
        }else{
            $tokenCard = $requestDataProvider->getCcTokenCreditCard();

            $order->payments = [
                [
                    'payment_method' => 'credit_card',
                    'amount' => $requestDataProvider->getAmountInCents(),
                    'credit_card' => [
                        'recurrence' => false,
                        'installments' => $requestDataProvider->getInstallmentCount(),
                        'statement_descriptor' => $statement,
                        'capture' => $capture,
                        'card_token' => $tokenCard,
                        'card' => [
                            'billing_address' => [
                                'street' => $requestDataProvider->getCustomerAddressStreet(self::BILLING),
                                'number' => $requestDataProvider->getCustomerAddressNumber(self::BILLING),
                                'complement' => $requestDataProvider->getCustomerAddressComplement(self::BILLING),
                                'zip_code' => trim(str_replace('-','',$quote->getBillingAddress()->getPostCode())),
                                'neighborhood' => $requestDataProvider->getCustomerAddressDistrict(self::BILLING),
                                'city' => $quote->getBillingAddress()->getCity(),
                                'state' => $quote->getBillingAddress()->getRegionCode(),
                                'country' => $quote->getBillingAddress()->getCountryId()
                            ]
                        ]
                    ]
                ]
            ];
        }
        	
        $quote->reserveOrderId()->save();
        $order->code = $this->paymentData->getOrder()->getIncrementId();

        $order->items = [];

        foreach ($requestDataProvider->getCartItems() as $key => $item) {

            $cartItemDataProvider = $this->createCartItemRequestDataProvider($item);

            $itemValues = [
                'amount' => $cartItemDataProvider->getUnitCostInCents(),
                'description' => $cartItemDataProvider->getName(),
                'quantity' => $cartItemDataProvider->getQuantity()
            ];

            array_push($order->items, $itemValues);

        }

        $document = $quote->getCustomerTaxvat() ? $quote->getCustomerTaxvat() : $quote->getShippingAddress()->getVatId() ;
        $this->getModuleHelper()->setTaxVat($document,true);

        $order->customer = [
            'name' => !empty($requestDataProvider->getName()) ? $requestDataProvider->getName() :  $quote->getBillingAddress()->getFirstName() . ' ' . $quote->getBillingAddress()->getLastName(),
            'email' => !empty($requestDataProvider->getEmail()) ? $requestDataProvider->getEmail() : $quote->getBillingAddress()->getEmail(),
            'document' => $this->getModuleHelper()->getTaxVat(),
            'type' => 'individual',
            'address' => [
                'street' => $requestDataProvider->getCustomerAddressStreet(self::SHIPPING),
                'number' => $requestDataProvider->getCustomerAddressNumber(self::SHIPPING),
                'complement' => $requestDataProvider->getCustomerAddressComplement(self::SHIPPING),
                'zip_code' => trim(str_replace('-','',$quote->getShippingAddress()->getPostCode())),
                'neighborhood' => $requestDataProvider->getCustomerAddressDistrict(self::SHIPPING),
                'city' => $quote->getShippingAddress()->getCity(),
                'state' => $quote->getShippingAddress()->getRegionCode(),
                'country' => $quote->getShippingAddress()->getCountryId()
            ]
        ];

        $order->ip = $requestDataProvider->getIpAddress();

        $order->shipping = [
            'amount' => $quote->getShippingAddress()->getShippingAmount() * 100,
            'description' => $cartItemDataProvider->getName(),
            'address' => [
                'street' => $requestDataProvider->getCustomerAddressStreet(self::SHIPPING),
                'number' => $requestDataProvider->getCustomerAddressNumber(self::SHIPPING),
                'complement' => $requestDataProvider->getCustomerAddressComplement(self::SHIPPING),
                'zip_code' => trim(str_replace('-','',$quote->getShippingAddress()->getPostCode())),
                'neighborhood' => $requestDataProvider->getCustomerAddressDistrict(self::SHIPPING),
                'city' => $quote->getShippingAddress()->getCity(),
                'state' => $quote->getShippingAddress()->getRegionCode(),
                'country' => $quote->getShippingAddress()->getCountryId()
            ]
        ];

        $order->session_id = $requestDataProvider->getSessionId();

        $order->metadata = [
            'module_name' => self::NAME_METADATA,
            'module_version' => $this->getModuleHelper()->getVersion(self::MODULE_NAME),
        ];

        if ($this->getConfigCreditCard()->getAntifraudActive() && $quote->getGrandTotal() > $this->getConfigCreditCard()->getAntifraudMinAmount()) {
            $order->antifraudEnabled = true;
        }

        try {
            $this->getLogger()->logger($order->jsonSerialize());
            $response = $this->getApi()->getOrders()->createOrder($order);

            if($requestDataProvider->getSaveCard() == '1')
            {
                $customer = $response->customer;

                $this->getCreateCardHelper()->createCard($response->charges[0]->lastTransaction->card, $customer, $quote);
            }

        } catch (\MundiAPILib\Exceptions\ErrorException $error) {
            $this->getLogger()->logger($error);
            throw new \InvalidArgumentException($error);
        } catch (\Exception $ex) {
            $this->getLogger()->logger($ex);
            throw new \InvalidArgumentException($ex->getMessage());
        }

        return $response;

    }

    /**
     * @return mixed
     */
    public function getCreateCardHelper()
    {
        return $this->createCrad;
    }

    /**
     * @param mixed $createCrad
     *
     * @return self
     */
    public function setCreateCardHelper($createCrad)
    {
        $this->createCrad = $createCrad;

        return $this;
    }


    /**
     * @return mixed
     */
    public function getCartItemRequestDataProviderFactory()
    {
        return $this->cartItemRequestDataProviderFactory;
    }

    /**
     * @param mixed $cartItemRequestDataProviderFactory
     *
     * @return self
     */
    public function setCartItemRequestDataProviderFactory($cartItemRequestDataProviderFactory)
    {
        $this->cartItemRequestDataProviderFactory = $cartItemRequestDataProviderFactory;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param mixed $logger
     *
     * @return self
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;

        return $this;
    }
}
