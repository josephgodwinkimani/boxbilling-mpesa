<?php
/**
 * MPESA Payment Plugin for Boxbilling
 *
 * @copyright Copyright (C) 2021 Joseph Godwin Kimani
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 *
 */

require_once("bb-library/Payment/Adapter/mpesa/vendor/autoload.php");

class Payment_Adapter_Mpesa implements \Box\InjectionAwareInterface
{
    private $config = array();

    protected $di;

    public function setDi($di)
    {
        $this->di = $di;
    }

    public function getDi()
    {
        return $this->di;
    }

    public function __construct($config)
    {
        $this->config = $config;

        if (!isset($this->config['consumerKey'])) {
            throw new Payment_Exception('Payment gateway "Lipa Na M-PESA" is not configured properly. Please update configuration parameter "Consumer Key" at "Configuration -> Payments".');
        }

        if (!isset($this->config['consumerSecret'])) {
            throw new Payment_Exception('Payment gateway "Lipa Na M-PESA" is not configured properly. Please update configuration parameter "Consumer Secret" at "Configuration -> Payments".');
        }
    }

    public static function getConfig()
    {
        return array(
            'supports_one_time_payments' => true,
            'description' => 'To setup Lipa Na M-PESA merchant account in BoxBilling you need to go to <i>Dashboard &gt; Integration &gt; API Settings</i> and copy Consumer Key and Consumer Secret and paste here in form fields.',
            'form' => array(
                'consumerKey' => array('text', array(
                    'label' => 'Consumer Key',
                    'description' => 'MPESA_CONSUMER_KEY',
                    ),
                ),
                'consumerSecret' => array('text', array(
                    'label' => 'Consumer Secret',
                    'description' => 'MPESA_CONSUMER_SECRET',
                    ),
                ),
                'testConsumerKey' => array('text', array(
                    'label' => 'Test Consumer Key',
                    'description' => 'Daraja API Test Consumer Key',
                    ),
                ),
                'testConsumerSecret' => array('text', array(
                    'label' => 'Test Consumer Secret',
                    'description' => 'Daraja API Test Consumer Secret',
                    ),
                ),
            ),
        );
    }

    public function get_test_consumerKey()
    {
        if (!isset($this->config['testConsumerKey'])) {
            throw new Payment_Exception('Payment gateway "Lipa Na M-PESA" is not configured properly. Please update configuration parameter "Test Consumer Key" at "Configuration -> Payments".');
        }
        return $this->config['testConsumerKey'];
    }

    public function get_test_consumerSecret()
    {
        if (!isset($this->config['testConsumerSecret'])) {
            throw new Payment_Exception('Payment gateway "Lipa Na M-PESA" is not configured properly. Please update configuration parameter "Test Consumer Secret" at "Configuration -> Payments".');
        }
        return $this->config['testConsumerSecret'];
    }

    public function getHtml($api_admin, $invoice_id, $subscription)
    {
        $invoiceModel = $this->di['db']->load('Invoice', $invoice_id);

        return $this->_generateForm($invoiceModel);
    }

    public function getAmount(\Model_Invoice $invoice)
    {
        $invoiceService = $this->di['mod_service']('Invoice');
        return (float)$invoiceService->getTotalWithTax($invoice);
    }

    public function getInvoiceTitle(\Model_Invoice $invoice)
    {
        $invoiceItems = $this->di['db']->getAll('SELECT title from invoice_item WHERE invoice_id = :invoice_id', array(':invoice_id' => $invoice->id));

        $params = array(
            ':id' => sprintf('%05s', $invoice->nr),
            ':serie' => $invoice->serie,
            ':title' => $invoiceItems[0]['title']);
        $title = __('Payment for invoice :serie:id [:title]', $params);
        if (count($invoiceItems) > 1) {
            $title = __('Payment for invoice :serie:id', $params);
        }
        return $title;
    }

    public function logError(Exception $e, Model_Transaction $tx)
    {
        $body = $e->getJsonBody();
        $err = $body['error'];
        $tx->txn_status = $err ['type'];
        $tx->error = $err['message'];
        $tx->status = 'processed';
        $tx->updated_at = date('Y-m-d H:i:s');
        $this->di['db']->store($tx);


        if ($this->di['config']['debug']) {
            error_log(json_encode($e->getJsonBody()));
        }
        throw new Exception($tx->error);
    }

}