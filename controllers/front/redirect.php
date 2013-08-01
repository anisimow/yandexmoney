<?php

/**
* qiwi module redirect controller.
*
* @author Anisimow <anisimow@ua.fm>
* @link 
* @copyright Copyright &copy; 2009-2012 
* @license    http://www.opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
* @version 0.7
*/

class yamoneyredirectModuleFrontController extends ModuleFrontController
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
                
        $this->module->payment_link = '';
        $this->myCart=$this->context->cart;

        $currency_rub = new Currency(Currency::getIdByIsoCode('RUB'));

        $total_to_pay = $this->myCart->getOrderTotal(true, Cart::BOTH);
        if($this->myCart->id_currency!=$currency_rub->id)
        {
            $currency = new Currency($this->myCart->id_currency);
            $total_to_pay=$total_to_pay/$currency->conversion_rate*$currency_rub->conversion_rate;
        }
        if ($total_to_pay > 0 && $total_to_pay < 1)
            $total_to_pay_limit = '1.00';
        else
            $total_to_pay_limit = number_format($total_to_pay, 2, '.', '');
        $total_to_pay = number_format($total_to_pay, 2, '.', '');

        $code = Tools::getValue('code');
        if (empty($code)) { // If we are just begginig OAuth
            
            $scope = //"account-info " .
                //"operation-history " .
                //"operation-details " .
                "payment.to-account(\"".Configuration::get('YANDEX_MONEY_SHOP_ID')."\",\"account\").limit(1,".$total_to_pay_limit.") " .
                //"payment-shop.limit(1,".$total_to_pay_limit.") " .
                "money-source(\"wallet\",\"card\") ";
            $authUri = YandexMoney::authorizeUri(Configuration::get('YANDEX_MONEY_CLIENT_ID'), Configuration::get('YANDEX_MONEY_REDIRECT_URI'), $scope);
            Tools::redirect($authUri , '');

        } else {
            
            //if we receive Token, than wee ca go 
            $ym = new YandexMoney(Configuration::get('YANDEX_MONEY_CLIENT_ID'),'./log/ym.log');
            $receiveTokenResp = $ym->receiveOAuthToken($code, Configuration::get('YANDEX_MONEY_REDIRECT_URI'), Configuration::get('YANDEX_MONEY_CLIENT_SECRET'));
            if ($receiveTokenResp->isSuccess()) {
                $token = $receiveTokenResp->getAccessToken();
            } else {
                $this->errors[] = $this->module->descriptionError($receiveTokenResp->getError());
                return;
            }
            
            //payment
            $comment = $message = $this->module->l('total:').$total_to_pay.$this->module->l(' rub').'<br />'.
                                $this->module->l('current order:').$message = $this->module->currentOrder;
            $resp = $ym->requestPaymentP2P($token, Configuration::get('YANDEX_MONEY_SHOP_ID'), $total_to_pay,$comment,$message, (Configuration::get('YANDEX_MONEY_DEMO_MODE')==1)? true: false, 'available', Configuration::get('YANDEX_MONEY_TEST_RESULT_REQ-PAY'));
             if ($resp->isSuccess()) {
                $MoneySource = $resp->getMoneySource();
                //cookie in prestashop are encrypted
                $this->context->cookie->ya_encrypt_token = urlencode(base64_encode($token));
                $this->context->cookie->ya_encrypt_RequestId = urlencode(base64_encode($resp->getRequestId()));
                $this->module->payment_link = $this->context->link->getModuleLink('yamoney', 'payment', array(), true);
                $this->module->card_allowed = $MoneySource['card']['allowed'];
                $this->module->wallet_alowed = $MoneySource['wallet']['allowed'];
             } else {
                 $this->errors[]=$this->module->descriptionError($resp->getError());
                 return;
             }        
            
        }
    }
    
    public function initContent()
    {
        parent::initContent();
        
		$cart = $this->context->cart;


		$this->context->smarty->assign(array(
                        'payment_link' => $this->module->payment_link,
                        'card_allowed' => $this->module->card_allowed ,
                        'wallet_alowed' => $this->module->wallet_alowed ,
			'nbProducts' => $cart->nbProducts(),
			'cust_currency' => $cart->id_currency,
			'currencies' => $this->module->getCurrency((int)$cart->id_currency),
			'total' => $cart->getOrderTotal(true, Cart::BOTH),
			'this_path' => $this->module->getPathUri(),
			'this_path_ssl' => Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->module->name.'/'
		));

		$this->setTemplate('redirect.tpl');
    }   
}
