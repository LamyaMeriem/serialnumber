

<div id="message-container" style="margin-top: 20px;"></div>

<form action="{$save_action_url}&ajax=1" method="post" id="serial-number-form">

    <input type="hidden" name="id_product" value="{$id_product}" />
    <input type="hidden" name="id_product_attribute" value="{$id_product_attribute}" />

    <div class="form-group">
        <label for="serial_numbers">Numéros de série (une ligne par numéro) :</label>
        <textarea name="serial_numbers" id="serial_numbers" class="form-control" rows="10" cols="50"
            placeholder="Entrez les numéros de série ici, un par ligne ou plusieurs sur la même ligne séparés par des espaces."></textarea>
    </div>

    <div class="form-group">
        <label for="status">État :</label>
        <input type="checkbox" name="active" id="status" value="1" checked> Activé
    </div>
    

    <div class="form-group">
        <button type="submit" name="save" class="btn btn-primary">Save</button>
        <button type="button" name="print" id="print-button" class="btn btn-secondary">Print</button>
    </div>
</form>