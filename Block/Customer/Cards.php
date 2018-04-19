<?php
/**
 * Class Billet
 *
 * @author      PagarMe Modules Team <modules@pagar.me>
 * @copyright   2018 PagarMe (http://pagar.me)
 * @license     http://pagar.me Copyright
 *
 * @link        http://pagar.me
 */

namespace PagarMe\PagarMe\Block\Customer;


use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use PagarMe\PagarMe\Model\CardsRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use \Magento\Customer\Model\Session;

class Cards extends Template
{
    protected $customerSession;

    protected $cardsRepository;

    protected $criteria;

    /**
     * Link constructor.
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(
        Context $context,
        CardsRepository $cardsRepository,
        SearchCriteriaBuilder $criteria,
        Session $customerSession
    ){
        parent::__construct($context, []);
        $this->setCardsRepository($cardsRepository);
        $this->setCriteria($criteria);
        $this->setCustomerSession($customerSession);
    }

    public function addFilterCriteria($fieldName, $fieldValue, $filterType = 'eq')
    {
        $searchCriteria = $this->getCriteria()->addFilter($fieldName, $fieldValue, $filterType)->create();

        return $searchCriteria;
    }

    public function getCardsList()
    {
        $customerId = $this->getIdCustomer();
        $searchCriteria = $this->addFilterCriteria('customer_id', $customerId);
        $listCards = $this->getCardsRepository()->getList($searchCriteria);

        return $listCards->getItems();
    }

    public function getIdCustomer()
    {
        return $this->getCustomerSession()->getId();
    }

    /**
     * @return mixed
     */
    public function getCardsRepository()
    {
        return $this->cardsRepository;
    }

    /**
     * @param mixed $cardsRepository
     *
     * @return self
     */
    public function setCardsRepository($cardsRepository)
    {
        $this->cardsRepository = $cardsRepository;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * @param mixed $criteria
     *
     * @return self
     */
    public function setCriteria($criteria)
    {
        $this->criteria = $criteria;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getCustomerSession()
    {
        return $this->customerSession;
    }

    /**
     * @param mixed $customerSession
     *
     * @return self
     */
    public function setCustomerSession($customerSession)
    {
        $this->customerSession = $customerSession;

        return $this;
    }
}
