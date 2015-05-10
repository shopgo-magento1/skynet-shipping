<?php

class Shopgo_SkynetShipping_Model_Shipment extends Mage_Core_Model_Abstract
{
    const POUNDS           = 'LB';
    const KILOGRAMS        = 'KG';
    const CUBIC_CENTIMETER = 'cm3';
    const CUBIC_INCH       = 'inch3';


    public function getCode($type, $code = '')
    {
        $helper = Mage::helper('skynetshipping');

        $codes = array(
            'unit_of_measure' => array(
                self::POUNDS    => $helper->__('Pounds'),
                self::KILOGRAMS => $helper->__('Kilograms')
            ),
            'unit_of_volume' => array(
                self::CUBIC_CENTIMETER => $helper->__(self::CUBIC_CENTIMETER),
                self::CUBIC_INCH       => $helper->__(self::CUBIC_INCH)
            )
        );

        if (!isset($codes[$type])) {
            return false;
        } elseif ('' === $code) {
            return $codes[$type];
        }

        $code = strtoupper($code);
        if (!isset($codes[$type][$code])) {
            return false;
        } else {
            return $codes[$type][$code];
        }
    }

    public function isEnabled()
    {
        return Mage::helper('skynetshipping')
            ->getConfigData('shipping_service', 'carriers_skynet');
    }

    public function verifyAccount($params)
    {
        $helper = Mage::helper('skynetshipping');

        $data = array(
            'UserName' => $params['username'],
            'Password' => $params['password'],
            'StationCode' => $params['station_code'],
            'ConsignorAccount' => $params['consignor_account']
        );

        $result = array(
            'status' => 0,
            'description' => $helper->__('An error has occurred. Please, contact the store administrator.')
        );

        $soapResult = $helper->soapClient('verify_account', $data);

        $debugLog = array(
            array('message' => $helper->hideLogPrivacies($data)),
            array('message' => $soapResult)
        );

        $helper->debug($debugLog);

        if ($soapResult != '[soapfault]') {
            $response = $helper->parseXmlResponse($soapResult, 'verify_account');

            if (false === strpos($response['status'], 'ERR')) {
                $result = array(
                    'status' => 1,
                    'description' => $helper->__('Valid Account'),
                    'list' => $list
                );
            } else {
                $result['description'] = $helper->__('Invalid Account. If the issue persists, please contact the store administrator.');
            }
        }

        return $result;
    }

    public function calculateRate($quote)
    {
        $helper = Mage::helper('skynetshipping');

        $requestData = $this->_getRateRequestData($quote);

        $supplierInfo = $helper->getOriginSupplier();
        $params = $helper->getClientInfo($supplierInfo);

        $service = Mage::getModel('skynetshipping/service')->getServiceText(
            $helper->getConfigData('service', 'carriers_skynet')
        );

        $params['TR'] = array(
            'CONSIGNORACCOUNT'        => $params['ConsignorAccount'],
            'DESTINATIONCOUNTRYCODE'  => strtoupper($requestData['country_code']),
            'DESTINATIONDELIVERYAREA' => ucwords(strtolower($requestData['state_or_province_code'])),
            'NOOFPIECES'              => $requestData['qty'],
            'ORIGINCOUNTRYCODE'       => $supplierInfo['country_code'],
            'PIECESOFSHIPMENT'        => array(),
            'SERVICE'                 => $service,
            'STATIONCODE'             => $params['StationCode']
        );

        $params['TR']['PIECESOFSHIPMENT'] = $requestData['pieces'];

        $result = array(
            'currency' => Mage::app()->getStore()->getBaseCurrencyCode(),
            'price'    => 0
        );

        $soapResult = $helper->soapClient('rate_request', $params);

        $debugLog = array(
            array('message' => $helper->hideLogPrivacies($params)),
            array('message' => $soapResult)
        );

        $helper->debug($debugLog, 'skynet_rate_request');

        if ($soapResult != '[soapfault]') {
            $result = $helper->parseXmlResponse($soapResult, 'rate_request');

            if (false === strpos($result['status'], 'ERR')) {
                $conversion = $helper->convertRateCurrency($result['price'], $result['currency']);
                $result['currency'] = $conversion['currency'];
                $result['price']    = $conversion['price'];
            }
        } else {
            $result['status'] = 'ERR';
        }

        return $result;
    }

    private function _getRateRequestData($quote)
    {
        $qty = 0;
        $dmAttr = array();
        $pieces = array(
            'Piece' => array()
        );

        $helper = Mage::helper('skynetshipping');

        $shippingAddress = $quote->getShippingAddress()->getData();

        $dwaCodes = $helper->getDwaCodes();

        foreach ($quote->getAllVisibleItems() as $item) {
            $product = Mage::getModel('catalog/product')->load($item->getProductId());
            $qty += $item->getQty();

            foreach ($helper->getDwaNames() as $attr) {
                $_prodData = $product->getData($dwaCodes[$attr]);
                $dmAttr[$attr] = !empty($_prodData) ? $_prodData : 1;
            }

            $weight = $item->getWeight();

            for ($i = 0; $i < $item->getQty(); $i++) {
                $pieces['Piece'][] = array(
                    'HEIGHTINCENTIMETERS' => $dmAttr[Shopgo_ShippingCore_Helper_Abstract::HEIGHT],
                    'LENGTHINCENTIMETERS' => $dmAttr[Shopgo_ShippingCore_Helper_Abstract::LENGTH],
                    'WIDHTINCENTIMETERS'  => $dmAttr[Shopgo_ShippingCore_Helper_Abstract::WIDTH],
                    'WEIGHTINKGS'         => $weight
                );
            }
        }

        $data = array(
            'country_code' => $shippingAddress['country_id'],
            'state_or_province_code' => $shippingAddress['region'],
            'qty' => $qty
        );

        $data['pieces'] = $pieces;

        return $data;
    }

    public function getShipmentItemsData($shipment)
    {
        $data = array(
            'TOTALWEIGHT' => 0,
            'TOTALLENGTH' => 0,
            'TOTALWIDTH' => 0,
            'TOTALHEIGHT' => 0,
            'ITEMNAME' => array(),
            'SKYBILLITEMDESC' => array()
        );

        $dwaCodes = Mage::helper('skynetshipping')->getDwaCodes();

        foreach ($shipment->getAllItems() as $item) {
            $qty = $item->getQty();

            if (($qty - $item->getQtyShipped()) == 0) {
                continue;
            }

            $data['TOTALWEIGHT'] += $item->getWeight() * $qty;

            $data['TOTALLENGTH'] +=
                ($item->getData($dwaCodes[Shopgo_ShippingCore_Helper_Abstract::LENGTH]) * $qty) + 0;
            $data['TOTALWIDTH']  +=
                ($item->getData($dwaCodes[Shopgo_ShippingCore_Helper_Abstract::WIDTH]) * $qty) + 0;
            $data['TOTALHEIGHT'] +=
                ($item->getData($dwaCodes[Shopgo_ShippingCore_Helper_Abstract::HEIGHT]) * $qty) + 0;

            $data['ITEMNAME'][] = $item->getName();

            $data['SKYBILLITEMDESC'][] = array(
                'ITEMQTY' => $qty,
                'UNITPRICE' => $item->getBasePrice(),
                'CUSTOMCODE' => $item->getHsCode(),
                'ITEMDESC' => $item->getName(),
                'UNITPRICE' => $item->getPrice(),
                'MFGCOUNTRY' => $item->getCountryOfManufacture(),
                'ORIGINSTATIONID' => 0,
                'REASONFOREXPORT' => '',
                'SKYBILLITEMNO' => 0
            );
        }

        return $data;
    }

    public function getShipmentData($shipment, $additionalData)
    {
        $helper = Mage::helper('skynetshipping');

        $consignorData = $helper->getOriginSupplier();

        $shipmentItemsData = $this->getShipmentItemsData($shipment);

        $accountInfo = $helper->getClientInfo($consignorData);

        $order = Mage::getModel('sales/order')->load($shipment->getOrder()->getId());

        $consigneeData = $order->getShippingAddress()->getData();

        if (empty($consigneeData['email'])) {
            $consigneeData['email'] = $order->getCustomerEmail();
        }

        $consigneeName = !empty($consigneeData['company'])
            ? $consigneeData['company']
            : $consigneeData['firstname'] . ' ' . $consigneeData['lastname'];

        $service = Mage::getModel('skynetshipping/service')->getServiceText(
            $helper->getConfigData('service', 'carriers_skynet')
        );

        $contents = implode(',', $shipmentItemsData['ITEMNAME']);

        if (strlen($contents) > 100) {
            $contents = substr($contents, 0, 96) . '...';
        }

        $quote = Mage::getModel('sales/quote')->loadByIdWithoutStore($order->getQuoteId());

        $calculatedWeights = $this->calculateRate($quote);

        $valueAmount = $order->getBaseSubtotal();
        $codAmount = 0;

        $codMethods = $helper->getCodMethodList();

        if ($helper->getConfigData('cod', 'carriers_skynet')
            && in_array($order->getPayment()->getMethodInstance()->getCode(), $codMethods)) {
            $codAmount = round($order->getBaseGrandTotal(), 2);
        }

        $data = array(
            'SkybillObject' => array(
                'BILLDATE' => date('c', time()),

                'CONSIGNORACCOUNT' => $accountInfo['ConsignorAccount'],
                'CONSIGNOR' => $consignorData['consignor_name'],
                'CONSIGNORADDRESS' => $consignorData['address_line1'],
                'CONSIGNORCITY' => $consignorData['city'],
                'CONSIGNORCOUNTRY' => $consignorData['country_code'],
                'CONSIGNOREMAIL' => $consignorData['email'],
                'CONSIGNORFAX' => $consignorData['fax_number'],
                'CONSIGNORPHONE' => $consignorData['phone_number'],
                'CONSIGNORREF' => $additionalData['consignor_ref'],
                'CONSIGNORSTATE' => $consignorData['state_or_province_code'],
                'CONSIGNORZIPCODE' => $consignorData['zipcode'],

                'CONSIGNEE' => $consigneeName,
                'CONSIGNEEADDRESS' => preg_replace("/\r\n|\n|\r/", ' ', $consigneeData['street']),
                'CONSIGNEECOUNTRY' => $consigneeData['country_id'],
                'CONSIGNEEEMAILADDRESS' => $consigneeData['email'],
                'CONSIGNEETOWN' => $consigneeData['city'],
                'CONSIGNEESTATE' => $consigneeData['region'],
                'CONSIGNEEZIPCODE' => $consigneeData['postcode'],
                'CONSIGNEETELEPHONE' => $consigneeData['telephone'],
                'CONSIGNEEATTENTION' => $consigneeData['firstname'] . ' ' . $consigneeData['lastname'],
                'CONSIGNEEFAX' => '',
                'CONSIGNEEMOBILE' => '',
                'CONSIGNEETAXID' => 0,

                'TYPEOFSHIPMENT' => $additionalData['type'],
                'SERVICES' => $service,
                'PIECES' => 1, // In our case, we will only have 1 SKYBILLITEM.
                'TOTALWEIGHT' => $shipmentItemsData['TOTALWEIGHT'],
                'VALUEAMT' => $valueAmount,
                'CURRENCY' => Mage::app()->getStore()->getBaseCurrencyCode(),
                'CODAMOUNT' => $codAmount,
                'CODCURRENCY' => Mage::app()->getStore()->getBaseCurrencyCode(),
                'DESTINATIONCODE' => '',
                'ORIGINSTATION' => $accountInfo['StationCode'],

                'TOTALVOLWEIGHT' => $calculatedWeights['vol_weight'],
                'CHARGABLEWEIGHT' => $calculatedWeights['charged_weight'],
                'CONTENTS' => $contents,
                'CONTRACTORNAME' => '',
                'CONTRACTORREF' => '',
                'NEWCONTENTS' => '',
                'ORIGINSTATIONID' => 0,
                'SKYBILLID' => 0,
                'SKYBILLNUMBER' => '',
                'SKYBILLPREFIX' => '',
                'TOSTATIONID' => 0,

                'SKYBILLITEMS' => array(
                    'SKYBILLITEM' => array(array(
                        'WEIGHT' => $shipmentItemsData['TOTALWEIGHT'],
                        'VOLWEIGHT' => 0,
                        'LEN' => $shipmentItemsData['TOTALLENGTH'],
                        'WIDTH' => $shipmentItemsData['TOTALWIDTH'],
                        'HEIGHT' => $shipmentItemsData['TOTALHEIGHT'],
                        'DECLAREDWEIGHT' => 0,
                        'ITEMDESCRIPTION' => '',
                        'ITEMNO' => 0,
                        'ORIGINSTATIONID' => 0,
                        'SKYBILLID' => 0,
                        'SkybillItemDescs' => array(
                            'SKYBILLITEMDESC' => $shipmentItemsData['SKYBILLITEMDESC']
                        )
                    ))
                )
            )
        );

        $data = array_merge($data, $accountInfo);

        return $data;
    }

    public function prepareShipment($shipment, $additionalData)
    {
        $result = false;

        $order = Mage::getModel('sales/order')
            ->load($shipment->getOrder()->getId());

        if (!$this->isShippingEnabled() || !$order->canShip()) {
            return;
        }

        $result = $this->_createShipment($shipment, $additionalData);

        return $result;
    }

    private function _createShipment($shipment, $additionalData)
    {
        $helper = Mage::helper('skynetshipping');

        $result = false;
        $shipmentData = $this->getShipmentData($shipment, $additionalData['shipment']);

        $soapResult = $helper->soapClient(
            'create_shipment',
            $shipmentData
        );

        if ($soapResult != '[soapfault]') {
            $result = $helper->parseXmlResponse($soapResult, 'shipping_service');

            $debugLog = array(
                array('message' => $helper->hideLogPrivacies($shipmentData)),
                array('message' => $result)
            );

            $helper->debug($debugLog, 'skynet_create_shipment');

            if (false === strpos($result['status'], 'ERR')) {
                $result = $this->_saveShipment($shipment, $result, 0);
            } else {
                $helper->log($result['status_description'], '', 'skynet_create_shipment');
                $userMsg = $helper->__(
                    'Shipment could not be created, please contact us to know more about this issue.<br/>Error:&nbsp;%s',
                    $result['status_description']
                );
                $helper->userMessage($userMsg, 'error');

                $result = false;
            }
        } else {
            $debugLog = array(
                array('message' => $helper->hideLogPrivacies($shipmentData)),
                array('message' => $soapResult)
            );

            $helper->debug($debugLog, 'skynet_create_shipment');
        }

        return $result;
    }

    private function _saveShipment($shipment, $shipmentData, $trackingNo = null)
    {
        $helper = Mage::helper('skynetshipping');
        $result = false;

        try {
            $comments = array(
                'shipment_number' =>
                    sprintf(
                        '<strong>' . $helper->__('Shipment No.') . ':</strong>&nbsp;%s',
                        $shipmentData['shipment_number']
                    ),
                'request_id' =>
                    sprintf(
                        '<strong>' . $helper->__('Request ID') . ':</strong>&nbsp;%s',
                        $shipmentData['request_id']
                    ),
                'shipping_label' =>
                    sprintf(
                        '<strong>' . $helper->__('Shipping Label') . ':</strong>&nbsp;%s%s',
                        'https://www.skynetwwe.info/Reports/AWBPrint.aspx?Skybill=',
                        $shipmentData['shipment_number']
                    )
            );

            foreach ($comments as $comment) {
                $shipment->addComment($comment, false, false);
            }

            $order = $shipment->getOrder();

            $order->setIsInProcess(true);

            $transactionSave = Mage::getModel('core/resource_transaction')
                ->addObject($shipment)
                ->addObject($order)
                ->save();

            $comments = '<br/><br/>' . implode('<br/>', $comments);

            $this->_sendShipmentEmail($shipment, $comments);

            $result = true;
        } catch (Exception $e) {
            $helper->log($e, 'exception');
            $helper->log(
                array('message' => $helper->__('Shipment could not be created (Saved), check exception log.')),
                '', 'skynet_create_shipment'
            );

            $helper->userMessage(
                $helper->__('Shipment could not be created, please contact us to look into the issue.'), 'error'
            );
        }

        return $result;
    }

    private function _sendShipmentEmail($shipment, $comments)
    {
        $helper = Mage::helper('skynetshipping');
        $result = false;

        try {
            if ($shipment->getOrder()->getCustomerEmail() && !$shipment->getEmailSent()) {
                $comments = '<br/><strong>' . $helper->__('Comments') . ':</strong>'
                          . $comments;

                $shipment->sendEmail(true, $comments);
                $shipment->setEmailSent(true);

                $result = true;
            }
        } catch (Exception $e) {
            $helper->log($e, 'exception');
            $helper->log(
                array('message' => $helper->__('Shipment email could not be sent, check exception log.')),
                '', 'skynet_create_shipment'
            );

            $helper->userMessage(
                $helper->__('Shipment email could not be sent, please contact us to look into the issue.'), 'error'
            );
        }

        return $result;
    }
}
