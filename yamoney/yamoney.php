<?php

if (!defined('_PS_VERSION_'))
	exit;

include_once(dirname(__FILE__) . '/api/YandexMoney.php');

class YaMoney extends PaymentModule
{
	private $_html = '';
	private $_postErrors = array();

	public function __construct()
	{
		$this->name = 'yamoney';
		$this->tab = 'payments_gateways';
		$this->version = '0.1';
                $this->author = 'Anisimow';
		$this->need_instance = 1;
                
		//Ключик 
		$this->module_key='cbc9ac64942a9821c6e71409e85cdf53';   
                
                //Привязвать к валюте
		$this->currencies = true;
		$this->currencies_mode = 'checkbox';

		parent::__construct();

		$this->displayName = $this->l('Yandex.Money');
		$this->description = $this->l('Accept payments by Yandex.Money');
		$this->confirmUninstall = $this->l('Are you sure you want to delete your details?');
		if (!isset($this->client_id) OR !isset($this->redirect_uri))
			$this->warning = $this->l('Account owner and details must be configured in order to use this module correctly');
		if (!sizeof(Currency::checkPaymentCurrencies($this->id)))
			$this->warning = $this->l('No currency set for this module');
	}

	public function install()
	{
		if (!parent::install() OR !$this->registerHook('payment') OR !$this->registerHook('paymentReturn'))
			return false;
		return true;
	}

	public function uninstall()
	{
		if (            !Configuration::deleteByName('YANDEX_MONEY_SHOP_ID')
                                OR !Configuration::deleteByName('YANDEX_MONEY_CLIENT_ID')
								OR !Configuration::deleteByName('YANDEX_MONEY_CLIENT_SECRET')
								OR !Configuration::deleteByName('YANDEX_MONEY_REDIRECT_URI')
                                OR !Configuration::deleteByName('YANDEX_MONEY_POSTVALIDATE')                        
                                OR !Configuration::deleteByName('YANDEX_MONEY_DEMO_MODE')
                                OR !Configuration::deleteByName('YANDEX_MONEY_TEST_RESULT_REQ-PAY')
                                OR !Configuration::deleteByName('YANDEX_MONEY_TEST_RESULT_PR-PAY')
				OR !parent::uninstall())
			return false;
		return true;
	}

        public function getContent()
	{
		if (Tools::isSubmit('submityamoney'))
		{
			$this->_postValidation();
			if (!sizeof($this->_postErrors))
				$this->_postProcess();
			else
				foreach ($this->_postErrors AS $err)
					$this->_html .= $this->displayError($err);;
		}
		$this->_displayForm();
		return $this->_html;
	}
	private function initToolbar()
	{
		$this->toolbar_btn['save'] = array(
			'href' => '#',
			'desc' => $this->l('Save')
		);
		return $this->toolbar_btn;
	}
	protected function _displayForm()
	{
		$this->_display = 'index';
		
		
		$this->fields_form[0]['form'] = array(
				'legend' => array(
				'title' => $this->l('Settings'),
				'image' => _PS_ADMIN_IMG_.'information.png'
			),
			'input' => array(
				array(
					'type' => 'text',
					'label' => $this->l('wallet id'),
					'desc' => $this->l('number of your wallet'),
					'name' => 'shop_id',
					'size' => 33,
				),
				array(
					'type' => 'text',
					'label' => $this->l('application id'),
					'desc' => $this->l('Id of your aplication in yandex system.'),
					'name' => 'client_id',
					'size' => 33,
				),
				array(
					'type' => 'text',
					'label' => $this->l('secret world'),
					'desc' => $this->l('Secret world in yandex system. Taken from magasine operator'),
					'name' => 'client_secret',
					'size' => 33,
				),                          
				array(
					'type' => 'radio',
					'label' => $this->l('Order after payment'),
					'name' => 'yamoney_postvalidate',
					'desc' => $this->l('Create order after receive payment notification'),
					'class' => 't',
					'is_bool' => true,
					'values' => array(
						array(
							'id' => 'yamoney_postvalidate_on',
							'value' => 1,
							'label' => $this->l('Enabled')
						),
						array(
							'id' => 'yamoney_postvalidate_off',
							'value' => 0,
							'label' => $this->l('Disabled')
						)
					)
				),
			
			),
			
			'submit' => array(
				'name' => 'submityamoney',
				'title' => $this->l('Save'),
				'class' => 'button'
			)
		);

		$this->fields_form[1]['form'] = array(
			'legend' => array(
				'title' => $this->l('Merchant configuration information') ,
				'image' => _PS_ADMIN_IMG_.'information.png'
			),
                        'input' => array(
				array(
					'type' => 'text',
					'label' => $this->l('Redirect uri'),
					'desc' => $this->l('Used for payment.'),
					'name' => 'url1',
					'size' => 120,
				)
			)
		);
                
                // prepare select of error for request-payment
                $select1 = array(
			array(
				'id' => 'success',
				'name' => $this->l('success')
			),
			array(
				'id' => 'illegal_params',
				'name' => $this->l('illegal_params')
			),
			array(
				'id' => 'illegal_param_label',
				'name' => $this->l('illegal_param_label')
			),
			array(
				'id' => 'phone_unknown',
				'name' => $this->l('phone_unknown')
			),
			array(
				'id' => 'payment_refused',
				'name' => $this->l('payment_refused')
			),
			array(
				'id' => 'authorization_reject',
				'name' => $this->l('authorization_reject')
			),  
			array(
				'id' => 'limit_exceeded',
				'name' => $this->l('limit_exceeded')
			), 
			array(
				'id' => 'illegal_params',
				'name' => $this->l('illegal_params')
			)                     
		);
                
                // prepare select of error for process-payment
                $select2 = array(
			array(
				'id' => 'success',
				'name' => $this->l('success')
			),
			array(
				'id' => 'contract_not_found',
				'name' => $this->l('contract_not_found')
			),
			array(
				'id' => 'not_enough_funds',
				'name' => $this->l('not_enough_funds')
			),
			array(
				'id' => 'limit_exceeded',
				'name' => $this->l('limit_exceeded')
			),
			array(
				'id' => 'money_source_not_available',
				'name' => $this->l('money_source_not_available')
			),
			array(
				'id' => 'illegal_param_csc',
				'name' => $this->l('illegal_param_csc')
			),  
			array(
				'id' => 'payment_refused',
				'name' => $this->l('payment_refused')
			), 
			array(
				'id' => 'authorization_reject',
				'name' => $this->l('authorization_reject')
			)                     
		);                
		$this->fields_form[2]['form'] = array(
			'legend' => array(
				'title' => $this->l('Debug mode') ,
				'image' => _PS_ADMIN_IMG_.'information.png'
			),
                        'input' => array(
				array(
					'type' => 'radio',
					'label' => $this->l('demo mode'),
					'name' => 'demo_mode',
					'desc' => $this->l('Turn off for working mode'),
					'class' => 't',
					'is_bool' => true,
					'values' => array(
						array(
							'id' => 'demo_mode_on',
							'value' => 1,
							'label' => $this->l('Enabled')
						),
						array(
							'id' => 'demo_mode_off',
							'value' => 0,
							'label' => $this->l('Disabled')
						)
					)
				), 
                           
                                array(
					'type' => 'select',
					'label' => $this->l('test result for request-payment'),
					'name' => 'test_result_request-payment',
					'desc' => $this->l(''),
                                          'options' => array(                                  // only if type == select
                                            'query' => $select1,
                                            'id' => 'id',
                                            'name' => 'name',                                // key that will be used for each option "value" attribute
                                          ),
                                ),
                            
                                array(
					'type' => 'select',
					'label' => $this->l('test result for process-payment'),
					'name' => 'test_result_process-payment',
					'desc' => $this->l(''),
                                          'options' => array(                                  // only if type == select
                                            'query' => $select2,
                                            'id' => 'id',
                                            'name' => 'name',                                // key that will be used for each option "value" attribute
                                          ),
                                )                            
			)
		);                
                $this->context->controller->getLanguages();
		$this->fields_value['shop_id'] = Configuration::get('YANDEX_MONEY_SHOP_ID');
		$this->fields_value['client_id'] = Configuration::get('YANDEX_MONEY_CLIENT_ID');
		$this->fields_value['client_secret'] = Configuration::get('YANDEX_MONEY_CLIENT_SECRET');
                $this->fields_value['demo_mode'] = Configuration::get('YANDEX_MONEY_DEMO_MODE');
		$this->fields_value['url1'] = $this->context->link->getModuleLink('yamoney', 'redirect', array(), true);
                Configuration::updateValue('YANDEX_MONEY_REDIRECT_URI', $this->fields_value['url1']);
                
                $this->fields_value['test_result_request-payment'] = Configuration::get('YANDEX_MONEY_TEST_RESULT_REQ-PAY');
                $this->fields_value['test_result_process-payment'] = Configuration::get('YANDEX_MONEY_TEST_RESULT_PR-PAY');
                
                //необходимо реализовать. Пока не работает
		$this->fields_value['yamoney_postvalidate'] = Configuration::get('YANDEX_MONEY_POSTVALIDATE');
                
		$helper = $this->initForm();
		$helper->submit_action = '';
		
		$helper->title = $this->displayName;
		
		$helper->fields_value = $this->fields_value;
		$this->_html .= $helper->generateForm($this->fields_form);
		return;
	}  
      
     	private function initForm()
	{
		$helper = new HelperForm();
		
		$helper->module = $this;
                
                $helper->languages = $this->context->controller->_languages;
                $helper->default_form_language = $this->context->controller->default_form_language;
		$helper->allow_employee_form_lang = $this->context->controller->allow_employee_form_lang;
		$helper->name_controller = 'yamoney';
		$helper->identifier = $this->identifier;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
		$helper->toolbar_scroll = true;
		$helper->tpl_vars['version'] = $this->version;
		$helper->tpl_vars['author'] = $this->author;
		$helper->tpl_vars['this_path'] = $this->_path;
		$helper->toolbar_btn = $this->initToolbar();
		
		return $helper;
	}
        
	private function _postValidation()
	{
		if(Tools::getValue('shop_id')&&(!Validate::isString(Tools::getValue('shop_id'))))
			$this->_postErrors[] = $this->l('Invalid').' '.$this->l('wallet id');
		if(Tools::getValue('client_id')&&(!Validate::isString(Tools::getValue('client_id'))))
			$this->_postErrors[] = $this->l('Invalid').' '.$this->l('application id');
		if(Tools::getValue('client_secret')&&(!Validate::isString(Tools::getValue('client_secret'))))
			$this->_postErrors[] = $this->l('Invalid').' '.$this->l('secret world');                   
		if(Tools::getValue('yamoney_postvalidate')&&(!Validate::isBool(Tools::getValue('yamoney_postvalidate'))))
			$this->_postErrors[] = $this->l('Invalid').' '.$this->l('Order after payment');
                //debug mode
		if(Tools::getValue('yamoney_demo_mode')&&(!Validate::isBool(Tools::getValue('yamoney_demo_mode'))))
			$this->_postErrors[] = $this->l('Invalid').' '.$this->l('demo mode');     
		if(Tools::getValue('test_result_request-payment')&&(!Validate::isString(Tools::getValue('test_result_request-payment'))))
			$this->_postErrors[] = $this->l('Invalid').' '.$this->l('request-payment list of errors');   
		if(Tools::getValue('test_result_process-payment')&&(!Validate::isString(Tools::getValue('test_result_process-payment'))))
			$this->_postErrors[] = $this->l('Invalid').' '.$this->l('process-payment list of errors');                 
	}

	private function _postProcess()
	{
		Configuration::updateValue('YANDEX_MONEY_SHOP_ID', Tools::getValue('shop_id'));
		Configuration::updateValue('YANDEX_MONEY_CLIENT_ID', Tools::getValue('client_id'));
                Configuration::updateValue('YANDEX_MONEY_CLIENT_SECRET', Tools::getValue('client_secret'));
		Configuration::updateValue('YANDEX_MONEY_POSTVALIDATE', (int)Tools::getValue('yamoney_postvalidate'));
                //debug mode
		Configuration::updateValue('YANDEX_MONEY_DEMO_MODE', (int)Tools::getValue('demo_mode'));                
                Configuration::updateValue('YANDEX_MONEY_TEST_RESULT_REQ-PAY', Tools::getValue('test_result_request-payment'));
                Configuration::updateValue('YANDEX_MONEY_TEST_RESULT_PR-PAY', Tools::getValue('test_result_process-payment'));
 
		$this->_html .= $this->displayConfirmation($this->l('Settings updated.'));
	}
        
	public function hookPayment($params)
	{
		if (!$this->active)
			return ;
		if (!$this->_checkCurrency($params['cart']))
			return ;

		global $smarty;

		$smarty->assign(array(
			'this_path' => $this->_path,
			'this_path_ssl' => Tools::getHttpHost(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/'
		));
		return $this->display(__FILE__, 'payment.tpl');
	}

        public function hookdisplayPaymentReturn($params)
        {
            if (!$this->active)
                return ;

            if(!$order=$params['objOrder'])
                return;

            if ($this->context->cookie->id_customer!=$order->id_customer)
                return;
            if (!$order->hasBeenPaid())
                return;
            $this->smarty->assign(array(
                'products' =>$order->getProducts()
            ));
            return $this->display(__FILE__, 'paymentReturn.tpl');

        }
	
	private function _checkCurrency($cart)
	{
		$currency_order = new Currency(intval($cart->id_currency));
		$currencies_module = $this->getCurrency();
		$currency_default = Configuration::get('PS_CURRENCY_DEFAULT');
		
		if (is_array($currencies_module))
			foreach ($currencies_module AS $currency_module)
				if ($currency_order->id == $currency_module['id_currency'])
					return true;
	}
	public function descriptionError($error)
	{
		$error_array = array(
			'invalid_request' => $this->l('Your request is missing required parameters or settings are incorrect or invalid values'),
			'invalid_scope' => $this->l('The scope parameter is missing or has an invalid value or a logical contradiction'),
			'unauthorized_client' => $this->l('Invalid parameter client_id, or the application does not have the right to request authorization (such as its client_id blocked Yandex.Money)'),
			'access_denied' => $this->l('Has declined a request authorization application'),
			'invalid_grant' => $this->l('The issue access_token denied. Issued a temporary token is not Google search or expired, or on the temporary token is issued access_token (second request authorization token with the same time token)'),
			'illegal_params' => $this->l('Required payment options are not available or have invalid values.'),
			'illegal_param_label' => $this->l('Invalid parameter value label'),
			'phone_unknown' => $this->l('A phone number is not associated with a user account or payee'),
			'payment_refused' => $this->l('Shop refused to accept payment (eg a user tried to pay for a product that is not in the store)'),
			'limit_exceeded' => $this->l('Exceeded one of the limits on operations: on the amount of the transaction for authorization token issued; transaction amount for the period of time for the token issued by the authorization; Yandeks.Deneg restrictions for different types of operations.'),
			'authorization_reject' => $this->l('In payment authorization is denied. Possible reasons are: transaction with the current parameters is not available to the user; person does not accept the Agreement on the use of the service "shops".'),
			'contract_not_found' => $this->l('None exhibited a contract with a given request_id'),
			'not_enough_funds' => $this->l('Insufficient funds in the account of the payer. Need to recharge and carry out a new delivery'),
			'money_source_not_available' => $this->l('The requested method of payment (money_source) is not available for this payment'),
			'illegal_param_csc' => $this->l('tsutstvuet or an invalid parameter value cs'),
			'payment_refused' => $this->l('Shop for whatever reason, refused to accept payment.')
		);
		if(array_key_exists($error,$error_array))
			$return = $error_array[$error];
		else
			$return = $error;
		return $return;
	}
        
      
}
