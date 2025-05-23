
{block name="content"}
<div class="panel">
    <div class="panel-heading">
        Rechercher un produit
    </div>
    <div class="panel-body">
        <form id="searchForm" method="post">
            <div class="form-group">
                <label for="search_query">Référence produit, déclinaison, nom, EAN13</label>
                <input type="text" name="search_query" id="search_query" class="form-control" placeholder="Recherche..." autocomplete="off">
            </div>
        </form>
    </div>
</div>

<div id="search_results" class="panel">
    <div class="panel-body">
        {if isset($groupedProducts) && $groupedProducts|@count > 0}
            {foreach from=$groupedProducts item=group}
                {foreach from=$group item=product}
                    <p>{$product.name}</p>
                {/foreach}
            {/foreach}
        {else}
            <p>Aucun produit trouvé.</p>
        {/if}
    </div>
</div>

<script type="text/javascript">
    var ajaxUrl = '{$link->getAdminLink('AdminSerialNumberProduct')}';
</script>
{/block}
