<div class="panel">
    <h1 class="text-center">{l s='Serial numbers' mod='serialnumber'}</h1>
    <hr class="text-center" />
    <ul class="text-center nav nav-tabs" style="display: flex; justify-content: center">
        <li class="text-center serial-tab {if $current_tab == 'AdminSerialNumberOrder'}active{/if}">
            <a href="{$link->getAdminLink('AdminSerialNumberOrder')}"><i class="icon-credit-card"></i> Commandes</a>
        </li>
        <li class="text-center serial-tab {if $current_tab == 'AdminSerialNumberProduct'}active{/if}">
            <a href="{$link->getAdminLink('AdminSerialNumberProduct')}"><i class="icon-shopping-cart"></i> Produits</a>
        </li>
        <li class="text-center serial-tab {if $current_tab == 'AdminSerialNumberProducts'}active{/if}">
            <a href="{$link->getAdminLink('AdminSerialNumberProducts')}"><i class="icon-serial-number"></i> N° série</a>
        </li>
    </ul>
</div>