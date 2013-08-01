{*
* yandex money payment module redirect to paysystem template.
*
* @author Anisimow <anisimow@ua.fm>
* @link 
* @copyright Copyright &copy; 2009-2013
* @license    http://www.opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

{literal}
    <script type="text/javascript">
        <!--
        $(document).ready(function(){
            $('input[name=walet_type]:radio').change( function(){
                if($('input[name=walet_type]:radio:checked').val()== 'wallet')
                {
                    $('.card_csc').hide();
                }
                else{
                    $('.card_csc').show();    
                }
            })
        })
        // -->
    </script>
{/literal}

{capture name=path}{l s='Payment' mod='yamoney'}{/capture}
{include file="$tpl_dir./breadcrumb.tpl"}

<h2>{l s='Order summation' mod='yamoney'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}
{include file="$tpl_dir./errors.tpl"}

<h3>{l s='Yandex Money wallet or creditcard payment' mod='yamoney'}</h3>

<form action="{$payment_link}" method="post">

	<p>
		<img src="{$this_path_ssl}yamoney.jpg" alt="{l s='yamoney wallet or cart payment' mod='yamoney'}" style="float:left; margin: 0px 10px 5px 0px;" />
	{l s='You have chosen the Yandex payment method.' mod='yamoney'}
		<br/><br />
	{l s='The total amount of your order is' mod='yamoney'}
		<span class="price">{convertPrice price=$total}</span>
	</p>
        <p>
                <b>{l s='Please choose your payment method' mod='yamoney'}.</b><br />
                {if !empty($wallet_alowed)}
                    <input type="radio" name="walet_type" value="wallet" checked /> <span class = "walet-method">{l s='Walet' mod='yamoney'}</span> <br />
                {/if}
                
                {if !empty($card_allowed)}
                    <input type="radio" name="walet_type" value="card" {if empty($wallet_alowed)}checked{/if} /> <span class = "walet-method">{l s='Card' mod='yamoney'}</span>&nbsp;&nbsp;&nbsp;  
                    <span class = "card_csc" {if !empty($wallet_alowed)}style = "display:none;"{/if}><input  type="text" name = "card_csc" size = "3" maxlength = "3" />&nbsp;{l s='CVV code  3 digit card verification number' mod='yamoney'}</span>
                {/if}
                
        </p>
	<p>
		<br /><br />
		<br /><br />
		<b>{l s='Please confirm your order by clicking \'I confirm my order\'' mod='yamoney'}.</b>
	</p>
	<p class="cart_navigation">
		<a href="{$link->getPageLink('order', true, null, 'step=3')}" class="button_large">{l s='Other payment methods' mod='yamoney'}</a>
		{if empty($wallet_alowed) && empty($card_allowed)}
                    {l s='Neither wallet or cart payment allowed' mod='yamoney'} 
                {else}    
                    <input type="submit" name="submit" value="{l s='I confirm my order' mod='yamoney'}" class="exclusive_large" />
                {/if}
	</p>


</form>
