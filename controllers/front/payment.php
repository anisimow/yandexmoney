<?php

/**
 * yamoney module success payment script.
 *
 * @author 0RS <anisimow@ua.fm>
 * @link 
 * @copyright Copyright &copy; 2009-2012 
 * @license    http://www.opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @version 0.7
 */

class yamoneypaymentModuleFrontController extends ModuleFrontController
{
    public $display_header = true;
    public $display_column_left = true;
    public $display_column_right = false;
    public $display_footer = true;
    public $ssl = true;

    public function postProcess()
    {

        parent::postProcess();
        
        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active)
                Tools::redirect('index.php?controller=order&step=1');

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer))
                Tools::redirect('index.php?controller=order&step=1');
                
        $this->module->payment_status = false;
        $requestId = base64_decode(urldecode($this->context->cookie->ya_encrypt_RequestId));
        $token =  base64_decode(urldecode($this->context->cookie->ya_encrypt_token));

        if(!empty($requestId) && !empty($token))
        {   
            $walet_type = (string)Tools::getValue('walet_type');
 
            //if we receive Token, than wee ca go 
            $ym = new YandexMoney(Configuration::get('YANDEX_MONEY_CLIENT_ID'),'./log/ym.log');
            if($walet_type == 'wallet')
            {
                $resp = $ym->processPaymentByWallet($token, $requestId, (Configuration::get('YANDEX_MONEY_DEMO_MODE')==1)? true: false, 'available', Configuration::get('YANDEX_MONEY_TEST_RESULT_PR-PAY'));
                $this->updateStatus($resp);
            }
            elseif($walet_type == 'card')
            {
                $resp = $ym->processPaymentByCard($token, $requestId, Tools::getValue('card_csc'), (Configuration::get('YANDEX_MONEY_DEMO_MODE')==1)? true: false, 'available', Configuration::get('YANDEX_MONEY_TEST_RESULT_PR-PAY')); 
                $this->updateStatus($resp);  
            }
            else
            {
                $this->errors[] = $this->module->l('neither  wallet or creditcart choosed');
                return;
            }
            
            //change status
            if( $this->module->payment_status == 100){
                
                $ps_status=false;

                if ( $status == 60 )
                    $ps_status=Configuration::get('PS_OS_PAYMENT');
                if ( ($status == 150)||($status == 151)||($status == 160)||($status == 161))
                    $ps_status=Configuration::get('PS_OS_CANCELED');

                if($ps_status) {
                    //Меняем статус заказа
                    $history = new OrderHistory();
                    $history->id_order = $this->module->currentOrder;
                    $history->changeIdOrderState($ps_status, $this->module->currentOrder);
                    $history->addWithemail(true);
                }
                $this->module->validateOrder((int)$cart->id, Configuration::get('PS_OS_PAYMENT'), $cart->getOrderTotal(true, Cart::BOTH), $this->module->displayName, NULL, array(), NULL, false, $cart->secure_key);
		Tools::redirect('index.php?controller=order-confirmation&id_cart='.$cart->id.'&id_module='.$this->module->id.'&id_order='.$this->module->currentOrder.'&key='.$customer->secure_key);
            }    
        }
        else
        {
            $this->errors[]=$this->module->l('invalid send data');
            return;
             
        }
    }

    public function updateStatus(&$resp)
    {   
                if ($resp->isSuccess()) {
                    $this->module->payment_status = 100;
                } elseif($resp->getStatus() == 'in_progress') {
                    $this->errors[]=$this->module->l('payment in progress, please wait');
                    $this->module->payment_status = 101;
                } else { 
                    $this->errors[]=$this->module->descriptionError($resp->getError());
                    $this->module->payment_status = 102;
                 } 
    }
    
    public function initContent()
    {
        parent::initContent();
        
		$cart = $this->context->cart;


		$this->context->smarty->assign(array(
			'payment_status' => $this->module->payment_status,
			'this_path' => $this->module->getPathUri(),
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
		));

		$this->setTemplate('error.tpl');
    }    
}