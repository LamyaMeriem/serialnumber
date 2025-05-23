<div class="panel">
   
    <!-- Formulaire de recherche -->
    <form method="GET" action="{$link->getAdminLink($current_tab)}" class="form-inline text-center">
        <input type="hidden" name="controller" value="{$current_tab}">
        <input type="text" name="search_query" value="{$search_query|escape:'html'}" placeholder="{l s='Rechercher...'}" class="form-control">
        <button type="submit" class="btn btn-primary">{l s='Rechercher'}</button>
    </form>

    {if $serial_numbers}
        <table class="table">
            <thead>
                <tr>
                    <th>{l s='ID' mod='serialnumber'}</th>
                    <th>{l s='ID Commande' mod='serialnumber'}</th>
                    <th>{l s='Produit' mod='serialnumber'}</th>
                    <th>{l s='Déclinaison' mod='serialnumber'}</th>
                    <th>{l s='Client' mod='serialnumber'}</th>
                    <th>{l s='Numéro de Série' mod='serialnumber'}</th>
                    <th>{l s='Actif' mod='serialnumber'}</th>
                    <th>{l s='Actions' mod='serialnumber'}</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$serial_numbers item=serial}
                    <tr>
                        <td>{$serial.id_serial|default:'--'}</td>
                        <td>{$serial.id_order|default:'--'}</td>
                        <td>{$serial.product_name|default:'--'}</td>
                        <td>{$serial.attribute_reference|default:'--'}</td>
                        <td>{$serial.customer_name|default:'--'}</td>
                        <td>{$serial.serial_number|default:'--'}</td>
                        <td>
                            {if $serial.active}
                                <i class="icon-check text-success"></i>
                            {else}
                                <i class="icon-remove text-danger"></i>
                            {/if}
                        </td>
                        <td>
                            <a href="{$link->getAdminLink($current_tab)}&id_serial={$serial.id_serial}&action=edit" class="btn btn-default">
                                <i class="icon-edit"></i> {l s='Modifier' mod='serialnumber'}
                            </a>
                            <a href="{$link->getAdminLink($current_tab)}&id_serial={$serial.id_serial}&action=liberate" class="btn btn-default">
                                <i class="icon-unlock"></i> {l s='Libérer' mod='serialnumber'}
                            </a>
                            <a href="{$link->getAdminLink($current_tab)}&id_serial={$serial.id_serial}&action=delete" class="btn btn-danger" onclick="return confirm('{l s='Are you sure you want to delete this item?' mod='serialnumber'}');">
                                <i class="icon-trash"></i> {l s='Supprimer' mod='serialnumber'}
                            </a>
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    {else}
        <p class="text-center">{l s='No serial numbers found.' mod='serialnumber'}</p>
    {/if}
</div>
