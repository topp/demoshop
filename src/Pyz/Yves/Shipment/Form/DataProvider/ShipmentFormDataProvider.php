<?php

/**
 * This file is part of the Spryker Demoshop.
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Pyz\Yves\Shipment\Form\DataProvider;

use Generated\Shared\Transfer\QuoteTransfer;
use Generated\Shared\Transfer\ShipmentMethodTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use Pyz\Yves\Shipment\Form\ShipmentForm;
use Spryker\Client\Glossary\GlossaryClientInterface;
use Spryker\Client\Shipment\ShipmentClientInterface;
use Spryker\Shared\Kernel\Store;
use Spryker\Shared\Library\Currency\CurrencyManager;
use Spryker\Shared\Transfer\AbstractTransfer;
use Spryker\Yves\StepEngine\Dependency\Form\StepEngineFormDataProviderInterface;

class ShipmentFormDataProvider implements StepEngineFormDataProviderInterface
{

    const FIELD_ID_SHIPMENT_METHOD = 'idShipmentMethod';

    /**
     * @var \Spryker\Client\Shipment\ShipmentClientInterface
     */
    protected $shipmentClient;

    /**
     * @var \Spryker\Client\Glossary\GlossaryClientInterface
     */
    protected $glossaryClient;

    /**
     * @var \Spryker\Shared\Kernel\Store
     */
    protected $store;

    /**
     * @var \Spryker\Shared\Library\Currency\CurrencyManager
     */
    protected $currencyManager;

    /**
     * @param \Spryker\Client\Shipment\ShipmentClientInterface $shipmentClient
     * @param \Spryker\Client\Glossary\GlossaryClientInterface $glossaryClient
     * @param \Spryker\Shared\Kernel\Store $store
     * @param \Spryker\Shared\Library\Currency\CurrencyManager $currencyManager
     */
    public function __construct(
        ShipmentClientInterface $shipmentClient,
        GlossaryClientInterface $glossaryClient,
        Store $store,
        CurrencyManager $currencyManager
    ) {

        $this->shipmentClient = $shipmentClient;
        $this->glossaryClient = $glossaryClient;
        $this->store = $store;
        $this->currencyManager = $currencyManager;
    }

    /**
     * @param \Spryker\Shared\Transfer\AbstractTransfer|\Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Spryker\Shared\Transfer\AbstractTransfer
     */
    public function getData(AbstractTransfer $quoteTransfer)
    {
        if ($quoteTransfer->getShipment() === null) {
            $shipmentTransfer = new ShipmentTransfer();
            $quoteTransfer->setShipment($shipmentTransfer);
        }

        return $quoteTransfer;
    }

    /**
     * @param \Spryker\Shared\Transfer\AbstractTransfer|\Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return array
     */
    public function getOptions(AbstractTransfer $quoteTransfer)
    {
        return [
            ShipmentForm::OPTION_SHIPMENT_METHODS => $this->createAvailableShipmentChoiceList($quoteTransfer)
        ];
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return array
     */
    protected function createAvailableShipmentChoiceList(QuoteTransfer $quoteTransfer)
    {
        $shipmentMethods = [];

        $shipmentMethodsTransfer = $this->getAvailableShipmentMethods($quoteTransfer);
        foreach ($shipmentMethodsTransfer->getMethods() as $shipmentMethodTransfer) {
            $shipmentMethods[$shipmentMethodTransfer->getIdShipmentMethod()] = $this->getShipmentDescription(
                $shipmentMethodTransfer
            );
        }

        return $shipmentMethods;
    }

    /**
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     *
     * @return \Generated\Shared\Transfer\ShipmentMethodsTransfer
     */
    protected function getAvailableShipmentMethods(QuoteTransfer $quoteTransfer)
    {
        return $this->shipmentClient->getAvailableMethods($quoteTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\ShipmentMethodTransfer $shipmentMethodTransfer
     *
     * @return string
     */
    protected function getShipmentDescription(ShipmentMethodTransfer $shipmentMethodTransfer)
    {
        $shipmentDescription = $this->translate($shipmentMethodTransfer->getName());

        $shipmentDescription = $this->appendDeliveryTime($shipmentMethodTransfer, $shipmentDescription);
        $shipmentDescription = $this->appendShipmentPrice($shipmentMethodTransfer, $shipmentDescription);

        return $shipmentDescription;
    }

    /**
     * @param \Generated\Shared\Transfer\ShipmentMethodTransfer $shipmentMethodTransfer
     * @param string $shipmentDescription
     *
     * @return string
     */
    protected function appendDeliveryTime(ShipmentMethodTransfer $shipmentMethodTransfer, $shipmentDescription)
    {
        $deliveryTime = $this->getDeliveryTime($shipmentMethodTransfer);

        if ($deliveryTime !== 0) {
            $shipmentDescription .= ' (' . $this->translate('page.checkout.shipping.delivery_time') . ' ' . $deliveryTime . ')';
        }

        return $shipmentDescription;
    }

    /**
     * @param \Generated\Shared\Transfer\ShipmentMethodTransfer $shipmentMethodTransfer
     * @param string $shipmentDescription
     *
     * @return string
     */
    protected function appendShipmentPrice(ShipmentMethodTransfer $shipmentMethodTransfer, $shipmentDescription)
    {
        $shipmentPrice = $this->getFormattedShipmentPrice($shipmentMethodTransfer);
        $shipmentDescription .= ': <strong>' . $shipmentPrice . '</strong>';

        return $shipmentDescription;
    }

    /**
     * @param \Generated\Shared\Transfer\ShipmentMethodTransfer $method
     *
     * @return int
     */
    protected function getDeliveryTime(ShipmentMethodTransfer $method)
    {
        $deliveryTime = 0;

        if ($method->getDeliveryTime()) {
            $deliveryTime = ($method->getDeliveryTime() / 3600);
        }

        return $deliveryTime;
    }

    /**
     * @param \Generated\Shared\Transfer\ShipmentMethodTransfer $shipmentMethodTransfer
     *
     * @return int
     */
    protected function getFormattedShipmentPrice(ShipmentMethodTransfer $shipmentMethodTransfer)
    {
        return $this->currencyManager->format(
            $this->currencyManager->convertCentToDecimal($shipmentMethodTransfer->getDefaultPrice())
        );
    }

    /**
     * @param string $translationKey
     *
     * @return string
     */
    protected function translate($translationKey)
    {
        return $this->glossaryClient->translate($translationKey, $this->store->getCurrentLocale());
    }

}
