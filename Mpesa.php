<?php
/**
 * MPESA Payment Plugin for BoxBilling
 *
 * @copyright Copyright (C) 2021 Joseph Godwin Kimani
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 *
 */

require_once("vendor/autoload.php");

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

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

        $log = new Logger('mpesa');
        $log->pushHandler(new StreamHandler('mpesa.log', Logger::WARNING));
    }

    public static function getConfig()
    {
        return array(
            'supports_one_time_payments' => true,
            'description' => 'To setup Lipa Na M-PESA merchant account in BoxBilling you need to go to <i>https://developer.safaricom.co.ke/MyApps &gt; Sandox/Production Apps &gt; Products: M-PESA Sandbox</i> and copy Consumer Key and Consumer Secret and paste here in form fields.',
            'form' => array(
                'shortCode' => array('text', array(
                    'label' => 'ShortCode',
                    'description' => 'Pay bill, Buy Goods or Till Number',
                    ),
                ),
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
                    'description' => 'Daraja API Sandbox Consumer Key',
                    ),
                ),
                'testConsumerSecret' => array('text', array(
                    'label' => 'Test Consumer Secret',
                    'description' => 'Daraja API Sandbox Consumer Secret',
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

    /**
     * Generate payment text
     *
     * @param Api_Admin $api_admin
     * @param int $invoice_id
     * @param bool $subscription
     *
     * @since BoxBilling v2.9.15
     *
     * @return string - html form with auto submit javascript
     */

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

    /**
     * Process transaction received from payment gateway
     *
     * @since BoxBilling v2.9.15
     *
     * @param Api_Admin $api_admin
     * @param int $id - transaction id to process
     * @param array $ipn - post, get, server, http_raw_post_data
     * @param int $gateway_id - payment gateway id on BoxBilling
     *
     * @return mixed
     */

    public function processTransaction($api_admin, $id, $data, $gateway_id)
    {
        $invoice = $this->di['db']->getExistingModelById('Invoice', $data['get']['bb_invoice_id']);
        $tx = $this->di['db']->getExistingModelById('Transaction', $id);

        $invoiceAmountInCents = $this->getAmount($invoice);

        $consumerKey = $this->config['consumerKey'];
        if ($this->config['test_mode']) {
            $consumerKey = $this->get_test_consumerKey();
        }

        $consumerSecret = $this->config['consumerSecret'];
        if ($this->config['test_mode']) {
            $consumerSecret = $this->get_test_consumerSecret();
        }

        $shortCode = $this->config['shortCode'];

        $title = $this->getInvoiceTitle($invoice);
                
        $mpesa= new \Safaricom\Mpesa\Mpesa();

        try {

            $CommandID = "CustomerPayBillOnline";
            $Amount = (float)$invoiceAmountInCents;
            $Msisdn = $data['post']['msisdn'];            
            $BillRefNumber = $tx->invoice_id;

            $c2bTransaction=$mpesa->c2b($ShortCode, $CommandID, $Amount, $Msisdn, $BillRefNumber );
            
            
            $tx->invoice_id = $invoice->id;
            $tx->txn_status = $status;          
            $tx->amount = $Amount;  
            $tx->txn_id = $data['post']['transaction_id'];            
            $tx->currency = $invoice->currency;  
            $tx->note =  $Msisdn;     

            $bd = array(
                'amount' => $tx->amount,
                'description' => 'Lipa Na MPESA with Transaction ID.' . $tx->txn_id,
                'type' => 'transaction',
                'rel_id' => $tx->id,                                
            );            

            $client = $this->di['db']->getExistingModelById('Client', $invoice->client_id);

            $clientService = $this->di['mod_service']('client');
            $clientService->addFunds($client, $bd['amount'], $bd['description'], $bd);

            $invoiceService = $this->di['mod_service']('Invoice');
            if ($tx->invoice_id) {
                $invoiceService->payInvoiceWithCredits($invoice);
            }
            $invoiceService->doBatchPayWithCredits(array('client_id' => $client->id));  
            
            
           
        } catch (Exception\Declined $exception) {              
            $payment = $exception->getResult();           
            $errors = $exception->getErrors(); 
            $log->error('Lipa Na MPESA Transaction Declined for invoice no/'.$tx->invoice_id);
        }        

        $tx->status = 'pending';
        $tx->updated_at = date('Y-m-d H:i:s');
        $this->di['db']->store($tx); 

        if(isset($_SERVER['HTTP_REFERER'])) {
                    
        header('Location: ' . $_SERVER['HTTP_REFERER']);

        }
    }

    protected function _generateForm(Model_Invoice $invoice)
    {
        $consumerKey = $this->config['consumerKey'];
        if ($this->config['test_mode']) {
            $consumerKey = $this->get_test_consumerKey();
        }

        $consumerSecret = $this->config['consumerSecret'];
        if ($this->config['test_mode']) {
            $consumerSecret = $this->get_test_consumerSecret();
        }

        $dataAmount = $this->getAmount($invoice);

        $settingService = $this->di['mod_service']('System');
        $company = $settingService->getCompany();

        $title = $this->getInvoiceTitle($invoice);

        $form = '<form action=":callbackUrl" method="POST" class="mpesa_form" data-api-redirect=":redirectUrl">';
        $form .= '<div class="mpesa_box">';
        $form .= '<p class="mpesa_instructions_header">INSTRUCTIONS - HOW TO PAY</p>';
        //$form .= '<p class="mpesa_instructions">Go to your Sim Toolkit</p>';
        //$form .= '<p class="mpesa_instructions">Select M-PESA</p>';
        //$form .= '<p class="mpesa_instructions">Select Lipa na M-PESA</p>';
        $form .= '<p class="mpesa_instructions">Select <b>PayBill</b> in SIM TOOLKIT</p>';
        $form .= '<p class="mpesa_instructions">Enter Business No. xxxxx</p>';
        $form .= '<p class="mpesa_instructions">Enter Account No. xxxxx</p>';
        $form .= '<p class="mpesa_instructions">Enter Amount as: <span class="amount">'. number_format($dataAmount, 2) .'</span></p>';
        //$form .= '<p class="mpesa_instructions">Enter your secret service PIN</p>';
        $form .= '<label for="msisdn">Msisdn:</label>';
        $form .= '<input type="text" name="msisdn" id="msisdn" required />';
        $form .= '<label for="transaction_id">Transaction ID:</label>';
        $form .= '<input type="text" name="transaction_id" id="transaction_id" required />';
        $form .= '</div>';
        $form .= '<div class="mpesa_box_bsnsref">'; 
        $form .= '</div>';

        $form .= '<div class="mpesa_submit_box">
                        <div>
                            <input class="submit" type="submit" name="sub" value=":label"/>
                        </div>
                        <div>
                            Order total: ' . number_format($dataAmount, 2) . ' :currency
                        </div>
                    </div>
                </form>
                <div id="g_form"></div>
                <style>
                .mpesa_box label{}
                .mpesa_instructions_header{color:red;font-weight:bold;}
                .mpesa_instructions{color:green;}
                .mpesa_box input{width:100%;height: 30px;}
                .amount{font-weight:bold;}
                .mpesa_form input.submit{background-color: #0A77BA;color: #FFF;border: none;padding: 10px;float: right;margin-bottom: 17px;}
                .mpesa_submit_box{float:left;width:100%;     margin: 20px 0 50px 0;}
                .mpesa_submit_box div{float: right;text-align: center;height: 39px;line-height: 30px;margin-right: 18px;font-size: 16px;font-weight: bold;}
                </style>
                <script type="text/javascript">
                    $("form.mpesa_form").bind("submit", function(){
 
                    }
                </script>';
        $payGatewayService = $this->di['mod_service']('Invoice', 'PayGateway');
        $payGateway = $this->di['db']->findOne('PayGateway', 'gateway = "Mpesa"');
        $bindings = array(
            ':consumerKey' => $consumerKey,
            ':consumerSecret' => $consumerSecret,
            ':amount' => $dataAmount,
            ':currency' => $invoice->currency,
            ':name' => $company['name'],
            ':description' => $title,
            ':image' => $company['logo_url'],
            ':email' => $invoice->buyer_email,
            ':label' => __('Pay now'),
            ':callbackUrl' => $payGatewayService->getCallbackUrl($payGateway, $invoice),
            ':redirectUrl' => $this->di['tools']->url('invoice/' . $invoice->hash)
        );
        return strtr($form, $bindings);
    }

}