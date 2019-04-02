{*
* 2015 PJ CONSEIL
*
* NOTICE OF LICENSE
*
* This source file is subject to License
* You may not distribute this module even for free
*
* @author PJ CONSEIL
* @version RC2
*}
<ul id="menuTab">
	<li id="menuTab1" class="menuTabButton selected">&nbsp;{l s='Info Service' mod='prestashoptodolibarrpro'}&nbsp;</li>
	<li id="menuTab2" class="menuTabButton">&nbsp;{l s='Webservices Access Configuration' mod='prestashoptodolibarrpro'}&nbsp;</li>
	<li id="menuTab3" class="menuTabButton">&nbsp;{l s='Export Settings' mod='prestashoptodolibarrpro'}&nbsp;</li>
	<li id="menuTab4" class="menuTabButton">&nbsp;{l s='Export Prestashop data to Dolibarr' mod='prestashoptodolibarrpro'}&nbsp;</li>
</ul>
<div id="tabList">	
	<div id="menuTab1Sheet" class="tabItem selected">
		<form action = "{if {$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}|strstr:"&id_tab"|escape:'htmlall':'UTF-8'}{{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}|strstr:"&id_tab":true|escape:'htmlall':'UTF-8'}{else}{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}{/if}&id_tab=1" method= "post">
			<img src= "../modules/prestashoptodolibarrpro/views/img/dolibarr.jpg" style = "float:left; margin-right:15px;">
			<b>{l s='This module synchronises Prestashop with dolibarr in real time.' mod='prestashoptodolibarrpro'}</b>
			<br><br><br>
			{l s='You can export existing data at first use of the module or for catching up. After that, each data will be updated in real time to Dolibarr.' mod='prestashoptodolibarrpro'}<br>
			{l s='It communicates with dolibarr thanks to webservices technologie.' mod='prestashoptodolibarrpro'}<br>
			{l s='Then, you must enable "dolipresta" module into Dolibarr to make this module works :' mod='prestashoptodolibarrpro'}<br><br>
			{l s='1/ Unzip' mod='prestashoptodolibarrpro'}&nbsp;<a href= "../modules/prestashoptodolibarrpro/dolipresta.zip"><strong><u><span style = "color:blue;">dolipresta</span></u></strong></a>&nbsp;{l s='to your Dolibarr "htdocs/" directory' mod='prestashoptodolibarrpro'}<br>
			{l s='2/ Go to Home > Configuration > Modules > Interfaces Modules' mod='prestashoptodolibarrpro'}<br>
			{l s='3/ Switch the dolipresta module button on' mod='prestashoptodolibarrpro'}<br>
			{l s='4/ Go to the configuration (by clicking on the tools icon)' mod='prestashoptodolibarrpro'}<br>
			{l s='5/ Set the webservices by entering the key you want and save.' mod='prestashoptodolibarrpro'}<br>
			<br><br><br>
			<table>
			<tr><td>{l s='If you have some questions or problems, the community can helps you here : ' mod='prestashoptodolibarrpro'}</td><td> &nbsp;&nbsp;<a href='https://www.dolibarr.fr/forum/505-e-commerce/62597-prestashop-to-dolibarr'><strong><u><span style = "color:#009e00;"> >> QUESTIONS & INFO</span></u></strong></a></td></tr>
			<tr><td>{l s='If you want to participate to the developpement of this module you can do it here : ' mod='prestashoptodolibarrpro'}</td><td> &nbsp;&nbsp;<a href='https://github.com/johanbernard/prestashop_to_dolibarr'><strong><u><span style = "color:#ff8300;"> >> GITHUB </span></u></strong></a></td></tr>
			<tr><td>{l s='Then If you want to donate for the developpers you can do it here : ' mod='prestashoptodolibarrpro'}</td><td> &nbsp;&nbsp;<a href='https://www.paypal.com/pools/c/8cV83UMDH5/guest/amount'><strong><u><span style = "color:#0064ff;"> >> DONATION</span></u></strong></a></td></tr>
			</table>
			{l s='Thank you ! ' mod='prestashoptodolibarrpro'}
		</form>
	</div>
	<div id="menuTab2Sheet" class="tabItem">
		<form action = "{if {$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}|strstr:"&id_tab"|escape:'htmlall':'UTF-8'}{{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}|strstr:"&id_tab":true|escape:'htmlall':'UTF-8'}{else}{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}{/if}&id_tab=2" method= "post">
			<fieldset>
				<legend><img src= "../img/admin/contact.gif" />{l s='Webservices Access Configuration' mod='prestashoptodolibarrpro'}</legend>
				<table border = "0" width= "800" cellpadding = "0" cellspacing = "0" id= "form">
					<tr><td colspan = "2">{l s='Please fill this informations needed to communicate with the Dolibarr\'s Webservice' mod='prestashoptodolibarrpro'}<br /><br /></td></tr>
					<tr><td style = "height: 35px;width: 125px;">{l s='Dolibarr url*' mod='prestashoptodolibarrpro'}</td><td><input type = "text" name = "adress" value = "{$varMain.ws_adress_value|escape:'htmlall':'UTF-8'}"
					style = "width: 300px;" /> <font style = "font-weight:700;color:#666666;font-size:11px">
					{l s='example : http://your_host/dolibarr/htdocs/' mod='prestashoptodolibarrpro'}</font></td></tr>
					<tr><td style = "height: 35px;">{l s='Webservice key*' mod='prestashoptodolibarrpro'}</td><td><input type = "text" name = "WSkey" value = "{$varMain.ws_key_value|escape:'htmlall':'UTF-8'}"
					style = "width: 100px;" /> <font style = "font-weight:700;color:#666666;font-size:11px">
					{l s='The key you specify in Dolibarr\'s webservices configuration' mod='prestashoptodolibarrpro'}</font></td></tr>  
					<tr><td style = "height: 35px;">{l s='Dolibarr login*' mod='prestashoptodolibarrpro'}</td><td><input type = "text" name = "login" value = "{$varMain.ws_login_value|escape:'htmlall':'UTF-8'}"
					style = "width: 100px;" /> <font style = "font-weight:700;color:#666666;font-size:11px">{l s='Your login' mod='prestashoptodolibarrpro'}</font></td></tr>
					<tr><td style = "height: 35px;">{l s='Dolibarr password*' mod='prestashoptodolibarrpro'}</td><td><input type = "password" name = "password" value = "{$varMain.ws_passwd_value|escape:'htmlall':'UTF-8'}"
					style = "width: 100px;" /></td></tr>
					<tr><td style = "height: 35px;">{l s='Your shop trigram' mod='prestashoptodolibarrpro'}</td><td><input type = "text" name = "trigram" value = "{$varMain.ws_trigram_value|escape:'htmlall':'UTF-8'}"
					style = "width: 30px;" /> 
					<font style = "font-weight:700;color:#666666;font-size:11px">{l s='Your Shop trigram (optional). All products, invoices, orders and clients references will be prefixed with it. Useful if you manage many shops in your Dolibarr.' mod='prestashoptodolibarrpro'}
					</font></td></tr>
					<tr><td colspan = "2" align = "center"><br />
					<input class = "button" name = "btnSubmitAccesWS" value = "{l s='Update settings' mod='prestashoptodolibarrpro'}" type = "submit" />

					{if {$varMain.ws_adress_value|escape:'htmlall':'UTF-8'} neq '' && {$varMain.ws_key_value|escape:'htmlall':'UTF-8'} neq '' && {$varMain.ws_login_value|escape:'htmlall':'UTF-8'} neq '' && {$varMain.ws_passwd_value|escape:'htmlall':'UTF-8'} neq ''}
						&nbsp; &nbsp; <input class = "button" name = "btnTestAccesWS" value = "{l s='Test Webservices Access' mod='prestashoptodolibarrpro'}" type = "submit" />
					{/if}
						</td></tr>
				</table>
			</fieldset>
		</form>
	</div>
	<div id="menuTab3Sheet" class="tabItem">
		<form action = "{if {$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}|strstr:"&id_tab"|escape:'htmlall':'UTF-8'}{{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}|strstr:"&id_tab":true|escape:'htmlall':'UTF-8'}{else}{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}{/if}&id_tab=3" method= "post" id="formone">
			{if {$varMain.ws_accesss_ok|escape:'htmlall':'UTF-8'} neq 'OK'} {l s='Please configure Webservices Access first' mod='prestashoptodolibarrpro'}
			{else}
				<fieldset>
					<legend><img src= "../img/admin/contact.gif" />{l s='Synchronizes in real time Prestashop with Dolibarr' mod='prestashoptodolibarrpro'}</legend>
					<table border = "0" cellpadding = "0" cellspacing = "0" id= "form">
						<tr><td colspan='2'>&nbsp;</td></tr>
						<tr><td>{l s='Synchronize customers -> added to Dolibarr when an account is created' mod='prestashoptodolibarrpro'}</td>
							<td><input type = "checkbox" name = "checkSynchCustomer"
							{if {$varMain.is_checked_synch_customer|escape:'htmlall':'UTF-8'} eq 'true'} checked= "checked"{/if}
							value = "true"></td>
						</tr>
						<tr><td>{l s='Synchronize products -> added to Dolibarr when a product is added or modified' mod='prestashoptodolibarrpro'}</td>
							<td><input type = "checkbox" name = "checkSynchProducts"';
							{if {$varMain.is_checked_synch_product|escape:'htmlall':'UTF-8'} eq 'true'} checked= "checked"{/if}
							value = "true"></td>
						</tr>
						<tr><td>{l s='Synchronize products Stocks -> updated to Dolibarr when a product Stock is updated in Prestashop' mod='prestashoptodolibarrpro'}
								<br><font size='1'><i>{l s='for stock update on shopping, go in dolibarr on the settings of the stock (Modules > main modules > stock settings)' mod='prestashoptodolibarrpro'}</i></font>
							</td>
							<td>
								<input type = "checkbox" name = "checkSynchStock"';
								{if {$varMain.is_checked_synch_stock|escape:'htmlall':'UTF-8'} eq 'true'} checked= "checked"{/if}
								value = "true">
								&nbsp; &nbsp; {l s='On which warehouse ?' mod='prestashoptodolibarrpro'}&nbsp;
								<input type = "text" name = "warehouse" value = "{$varMain.ws_warehouse_value|escape:'htmlall':'UTF-8'}"/>
								<font size='1'><i>{l s='same in dolibarr > products > warehouses' mod='prestashoptodolibarrpro'}</i></font>
							</td>
						</tr>
						<tr><td>{l s='Synchronize invoices -> added to Dolibarr after command paiement' mod='prestashoptodolibarrpro'}</td>
							<td><input type = "checkbox" name = "checkSynchInvoice"';
							{if {$varMain.is_checked_synch_invoice|escape:'htmlall':'UTF-8'} eq 'true'} checked= "checked"{/if}
							value = "true"></td>
						</tr>
						<tr><td>{l s='Synchronize orders -> added to Dolibarr after command paiement' mod='prestashoptodolibarrpro'}</td>
							<td><input type = "checkbox" name = "checkSynchOrder"';
							{if {$varMain.is_checked_synch_order|escape:'htmlall':'UTF-8'} eq 'true'} checked= "checked"{/if}
							value = "true"></td>
						</tr>
						<tr><td>{l s='Synchronize categories -> added to Dolibarr when a category is added or modified or deleted' mod='prestashoptodolibarrpro'} &nbsp;</td>
							<td><input type = "checkbox" name = "checkSynchCategory"';
							{if {$varMain.is_checked_synch_category|escape:'htmlall':'UTF-8'} eq 'true'} checked= "checked"{/if}
							value = "true"></td>
						</tr>
						<tr><td>{l s='Synchronize status -> updated in Dolibarr after status update in Prestashop' mod='prestashoptodolibarrpro'}</td>
							<td><input type = "checkbox" name = "checkSynchStatus" onclick= "document.getElementById('btnSubmitSynchro').click();"';
							{if {$varMain.is_checked_synch_status|escape:'htmlall':'UTF-8'} eq 'true'} checked= "checked"{/if}
							value = "true"></td>
						</tr>
						{if {$varMain.is_checked_synch_status|escape:'htmlall':'UTF-8'} eq 'true'}
							<tr>
								<td align='right'><b>{l s='Prestashop status' mod='prestashoptodolibarrpro'}</b></td>
								<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>{l s='Dolibarr matching status' mod='prestashoptodolibarrpro'}</b></td>
							</tr>
							{foreach from=$varMain.order_states item=order_state}
								<tr>
									<td align='right'>{$order_state['name']|escape:'htmlall':'UTF-8'}</td>
									<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<select name="select_{$order_state['id_order_state']|escape:'htmlall':'UTF-8'}">
										{foreach from=$varMain.order_states_options key=id item=order_states_option}
											<option value="{$id|escape:'htmlall':'UTF-8'}" {if {$order_state['id_order_state_doli']|escape:'htmlall':'UTF-8'} eq {$id|escape:'htmlall':'UTF-8'}} selected{/if}>{$order_states_option|escape:'htmlall':'UTF-8'}</option>
										{/foreach}
									</select></td>
								</tr>
							{/foreach}
						{/if}						
						<tr>
							<td colspan = "2" align = "center"><br><input class = "button" id = "btnSubmitSynchro" name = "btnSubmitSynchro" value = "
							{l s='Validate' mod='prestashoptodolibarrpro'}" type = "submit" /></td>
						</tr>
					</table>
				</fieldset>
			{/if}
		</form>
	</div>
	<div id="menuTab4Sheet" class="tabItem">
		<form action = "{if {$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}|strstr:"&id_tab"|escape:'htmlall':'UTF-8'}{{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}|strstr:"&id_tab":true|escape:'htmlall':'UTF-8'}{else}{$smarty.server.REQUEST_URI|escape:'htmlall':'UTF-8'}{/if}&id_tab=4" method= "post">
			{if {$varMain.ws_accesss_ok|escape:'htmlall':'UTF-8'} neq 'OK'} {l s='Please configure Webservices Access first' mod='prestashoptodolibarrpro'}
			{else}
				<fieldset>
					<legend><img src= "../img/admin/contact.gif" />{l s='Export Prestashop data to Dolibarr (for first use or catching up)' mod='prestashoptodolibarrpro'}</legend>
					<table border = "0" width= "800" cellpadding = "0" cellspacing = "0" id= "form">
						<tr><td colspan = "2">{l s='Warning : This Steps may take several minutes!' mod='prestashoptodolibarrpro'}<br /><br /></td></tr>
						<tr><td>{l s='Export customers' mod='prestashoptodolibarrpro'}</td><td><input class = "button" name = "btnSubmitExportClient" value = "{l s='Start' mod='prestashoptodolibarrpro'}" type = "submit"
							onclick="this.style.backgroundImage='url(../modules/prestashoptodolibarrpro/views/img/loader.gif)'; 
							this.style.backgroundRepeat='no-repeat'; this.style.backgroundPosition='center'; this.value=' . . . ';"/></td> 
							<td>{l s='Reset customers' mod='prestashoptodolibarrpro'}</td><td><input class = "button" name = "btnResetExportClient" value = "{l s='Reset' mod='prestashoptodolibarrpro'}" type = "submit" /></td>
						</tr>
						<tr>
							<td>{l s='Export categories' mod='prestashoptodolibarrpro'}</td>
							<td><input class = "button" name = "btnSubmitImportCategory" value = "{l s='Start' mod='prestashoptodolibarrpro'}" type = "submit" onclick="this.style.backgroundImage='url(../modules/prestashoptodolibarrpro/views/img/loader.gif)'; this.style.backgroundRepeat='no-repeat'; this.style.backgroundPosition='center'; this.value=' . . . ';"/></td>
						</tr>	
						<tr>
							<td>{l s='Export products' mod='prestashoptodolibarrpro'}<br>
							<i>{l s='(stock are synchronised if you set a warehouse in the exportations settings)' mod='prestashoptodolibarrpro'}</i></td>
							<td><input class = "button" name = "btnSubmitExportProduct" value = "{l s='Start' mod='prestashoptodolibarrpro'}" type = "submit" onclick="this.style.backgroundImage='url(../modules/prestashoptodolibarrpro/views/img/loader.gif)'; 
							this.style.backgroundRepeat='no-repeat'; this.style.backgroundPosition='center'; this.value=' . . . ';"/>
							</td>  
							<td>{l s='Reset products' mod='prestashoptodolibarrpro'}</td><td><input class = "button" name = "btnResetExportProduct" value = "{l s='Reset' mod='prestashoptodolibarrpro'}" type = "submit" /></td>
						</tr>
						<tr>
						<td>{l s='Export invoices' mod='prestashoptodolibarrpro'}</td><td><input class = "button" name = "btnSubmitImportInvoice" value = "{l s='Start' mod='prestashoptodolibarrpro'}" type = "submit" 
							onclick="this.style.backgroundImage='url(../modules/prestashoptodolibarrpro/views/img/loader.gif)'; 
							this.style.backgroundRepeat='no-repeat'; this.style.backgroundPosition='center'; this.value=' . . . ';"/>
							</td>
							<td>{l s='Reset invoices' mod='prestashoptodolibarrpro'}</td><td><input class = "button" name = "btnResetExportInvoice" value = "{l s='Reset' mod='prestashoptodolibarrpro'}" type = "submit" /></td></tr>
						<tr><td>{l s='Export orders' mod='prestashoptodolibarrpro'}</td><td><input class = "button" name = "btnSubmitImportOrder" value = "{l s='Start' mod='prestashoptodolibarrpro'}" type = "submit" 
							onclick="this.style.backgroundImage='url(../modules/prestashoptodolibarrpro/views/img/loader.gif)'; 
							this.style.backgroundRepeat='no-repeat'; this.style.backgroundPosition='center'; this.value=' . . . ';"
						/></td>
							<td>{l s='Reset orders' mod='prestashoptodolibarrpro'}</td><td><input class = "button" name = "btnResetExportOrder" value = "{l s='Reset' mod='prestashoptodolibarrpro'}" type = "submit" /></td></tr>
					</table>
			</fieldset>
			{/if}
		</form>
	</div>
</div>
<br clear="left" />
<br />
<style>
{literal}
	#menuTab { float: left; padding: 0; margin: 0; text-align: left; }
	#menuTab li { text-align: left; float: left; display: inline; padding: 5px; padding-right: 10px; background: #EFEFEF; font-weight: bold; cursor: pointer; border-left: 1px solid #CCCCCC; border-right: 1px solid #CCCCCC; border-top: 1px solid #CCCCCC; }
	#menuTab li.menuTabButton.selected { background: #FFF6D3; border-left: 1px solid #CCCCCC; border-right: 1px solid #CCCCCC; border-top: 1px solid #CCCCCC; }
	#tabList { clear: left; }
	.tabItem { display: none; }
	.tabItem.selected { display: block; background: #FFFFF0; border: 1px solid #CCCCCC; padding: 10px; padding-top: 20px; }	
{/literal}
</style>
<script type="text/javascript">
{literal}
	$(".menuTabButton").click(function () {
		$(".menuTabButton.selected").removeClass("selected");
		$(this).addClass("selected");
		$(".tabItem.selected").removeClass("selected");
		$("#" + this.id + "Sheet").addClass("selected");
	});
{/literal}
</script>
