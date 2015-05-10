<?php

class Shopgo_SkynetShipping_Model_Carrier_Skynet extends Mage_Shipping_Model_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
    protected $_code   = 'skynet';
    protected $_result = null;

    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!Mage::getStoreConfig('carriers/' . $this->_code . '/active')) {
            return false;
        }

        $helper = Mage::helper('skynetshipping');

        $this->_updateFreeMethodQuote($request);

        if ($request->getFreeShipping()
            || ($this->getConfigData('free_shipping_enable')
                && $request->getBaseSubtotalInclTax() >=
                $this->getConfigData('free_shipping_subtotal'))
        ) {
            return false;
        }

        $price = 0;
        $error = false;
        $methodTitle = '';

        $sessionQoute = Mage::app()->getStore()->isAdmin()
            ? Mage::getSingleton('adminhtml/session_quote')
            : Mage::getSingleton('checkout/session');

        $quote = $sessionQoute->getQuote();

        $result = Mage::getModel('skynetshipping/shipment')
            ->calculateRate($quote);

        $error = false !== strpos($result['status'], 'ERR');
        $errorMessage = $this->getConfigData('specificerrmsg');

        if ($error
            && $helper->getConfigData('skynet_error', 'carriers_skynet')) {
            $errorMessage =
            $skynetErrorMessage = 'SkyNet Error: ' . $result['status_description'];
        }

        $price = $result['price'];

        $handling = Mage::getStoreConfig('carriers/' . $this->_code . '/handling');
        $result = Mage::getModel('shipping/rate_result');

        if (!$error && $price > 0) {
            $method = Mage::getModel('shipping/rate_result_method');

            $method->setCarrier($this->_code);
            $method->setMethod($this->_code);
            $method->setCarrierTitle($this->getConfigData('title'));
            $method->setMethodTitle($methodTitle);
            $method->setPrice($price);
            $method->setCost($price);

            $result->append($method);
        } else {
            $error = Mage::getModel('shipping/rate_result_error');

            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage($errorMessage);

            $result->append($error);

            if ($skynetErrorMessage) {
                $helper->log($skynetErrorMessage, '', 'skynet_rate_request');
            }
        }

        return $result;
    }

    public function getAllowedMethods()
    {
        return array($this->_code => $this->getConfigData('name'));
    }

    public function isTrackingAvailable()
    {
        return $this->getConfigData('tracking_service');
    }

    public function isShippingLabelsAvailable()
    {
        return false;
    }

    public function isCityRequired()
    {
        return false;
    }

    public function isZipCodeRequired($countryId = null)
    {
        if ($countryId != null) {
            return !Mage::helper('directory')->isZipCodeOptional($countryId);
        }

        return true;
    }

    public function isGirthAllowed($countyDest = null)
    {
        return false;
    }

    protected function _updateFreeMethodQuote($request)
    {
        $freeShipping = false;
        $items = $request->getAllItems();
        $c = count($items);

        for ($i = 0; $i < $c; $i++) {
            if ($items[$i]->getProduct() instanceof Mage_Catalog_Model_Product) {
                if ($items[$i]->getFreeShipping()) {
                    $freeShipping = true;
                } else {
                    return;
                }
            }
        }

        if ($freeShipping) {
            $request->setFreeShipping(true);
        }
    }
}
