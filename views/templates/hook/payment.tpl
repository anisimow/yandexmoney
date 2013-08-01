{*
* qiwi payment module display in payment list template.
*
* @author 0RS <anisimow@ua.fm>
* @copyright Copyright &copy; 2009-2013
* @license    http://www.opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
*}

<p class="payment_module">
	<a href="{$link->getModuleLink('yamoney', 'redirect', ['id_cart'=>$id_cart], true)}" title="{l s='yandex money' mod='yamoney'}" class="yandex_money">
		<img src="{$this_path}yamoney.jpg" alt="{l s='yandex money' mod='yamoney'}" style="float:left;" />
		<br />{l s='Payment with yandex money' mod='yamoney'}
		<br style="clear:both;" />
	</a>
</p>
