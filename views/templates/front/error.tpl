{*
* qiwi wayting payment page.
*
* @author 0RS <admin@prestalab.ru>
* @link http://prestalab.ru/
* @copyright Copyright &copy; 2009-2012 PrestaLab.Ru
* @license    http://www.opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
* @version 0.7
*}
{capture name=path}
    {if $payment_status == 101}
        {l s='At the moment of payment is not received. Once it is received you will be able to see your order in your account' mod='yamoney'}>
    {else}
        {l s='no payment' mod='yamoney'}
    {/if}
{/capture}

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}
{include file="$tpl_dir./errors.tpl"}

	<p class="warning">
                {if $payment_status == 101}
                    <p>{l s='At the moment of payment is not received. Once it is received you will be able to see your order in your account' mod='yamoney'}></p>
                {else}
                    <p>{l s='no payment' mod='yamoney'}</p>
                {/if}
		<p>{l s='We noticed a problem with your order. If you think this is an error, you can contact our' mod='yamoney'} </p>
		<a href="{$link->getPageLink('contact', true)}">{l s='customer support' mod='yamoney'}</a>.
	</p>