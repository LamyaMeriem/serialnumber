<div class="panel">
    <div class="panel-heading">
        <i class="icon-plus"></i>
        Ajouter des numéros de série
        {if isset($product_name)}
            <small>pour {$product_name}</small>
        {/if}
    </div>
    
    <div id="message-container" style="margin-top: 20px;"></div>

    <form action="{$save_action_url}&ajax=1" method="post" id="serial-number-form" class="form-horizontal">
        <input type="hidden" name="id_product" value="{$id_product}" />
        <input type="hidden" name="id_product_attribute" value="{$id_product_attribute}" />

        <div class="form-group">
            <label class="control-label col-lg-3" for="serial_numbers">
                <span class="label-tooltip" data-toggle="tooltip" title="Entrez les numéros de série, un par ligne ou séparés par des espaces">
                    Numéros de série
                </span>
            </label>
            <div class="col-lg-9">
                <textarea name="serial_numbers" id="serial_numbers" class="form-control" rows="10" 
                    placeholder="Entrez les numéros de série ici, un par ligne ou plusieurs sur la même ligne séparés par des espaces."></textarea>
                <p class="help-block">
                    Vous pouvez entrer plusieurs numéros de série en les séparant par des retours à la ligne ou des espaces.
                </p>
            </div>
        </div>

        <div class="form-group">
            <label class="control-label col-lg-3" for="active">État</label>
            <div class="col-lg-9">
                <span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" name="active" id="active_on" value="1" checked="checked">
                    <label for="active_on">Oui</label>
                    <input type="radio" name="active" id="active_off" value="0">
                    <label for="active_off">Non</label>
                    <a class="slide-button btn"></a>
                </span>
                <p class="help-block">
                    Les numéros de série inactifs ne seront pas assignés automatiquement.
                </p>
            </div>
        </div>

        <div class="panel-footer">
            <button type="submit" name="save" class="btn btn-default pull-right">
                <i class="process-icon-save"></i> Enregistrer
            </button>
            <button type="button" name="print" id="print-button" class="btn btn-default">
                <i class="icon-print"></i> Imprimer
            </button>
        </div>
    </form>
</div>

<script type="text/javascript">
$(document).ready(function() {
    // Gestion du formulaire d'ajout de numéros de série
    $('#serial-number-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var submitButton = $(this).find('button[type="submit"]');
        
        // Désactiver le bouton pendant le traitement
        submitButton.prop('disabled', true).html('<i class="icon-spinner icon-spin"></i> Enregistrement...');
        
        $.ajax({
            type: 'POST',
            url: $(this).attr('action'),
            data: formData,
            dataType: 'json',
            success: function(response) {
                var messageContainer = $('#message-container');
                if (response.success) {
                    messageContainer.html('<div class="alert alert-success">' + response.message + '</div>');
                    // Vider le textarea en cas de succès
                    $('#serial_numbers').val('');
                } else {
                    messageContainer.html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function(xhr, status, error) {
                $('#message-container').html('<div class="alert alert-danger">Une erreur est survenue : ' + error + '</div>');
            },
            complete: function() {
                // Réactiver le bouton
                submitButton.prop('disabled', false).html('<i class="process-icon-save"></i> Enregistrer');
                
                // Faire défiler vers le message
                $('html, body').animate({
                    scrollTop: $('#message-container').offset().top - 100
                }, 500);
            }
        });
    });

    // Gestion de l'impression (fonctionnalité basique)
    $('#print-button').on('click', function() {
        var serialNumbers = $('#serial_numbers').val().trim();
        if (!serialNumbers) {
            alert("Veuillez entrer des numéros de série avant d'imprimer.");
            return;
        }

        var numbers = serialNumbers.split(/[\r\n\s]+/);
        var printContent = '<h2>Numéros de série</h2>';
        
        {if isset($product_name)}
            printContent += '<p><strong>Produit :</strong> {$product_name}</p>';
        {/if}
        
        printContent += '<ul>';
        numbers.forEach(function(number) {
            if (number.trim()) {
                printContent += '<li>' + number.trim() + '</li>';
            }
        });
        printContent += '</ul>';

        var printWindow = window.open('', 'PRINT', 'height=600,width=800');
        printWindow.document.write('<html><head><title>Numéros de série</title>');
        printWindow.document.write('<style>body { font-family: Arial, sans-serif; margin: 20px; }</style>');
        printWindow.document.write('</head><body>' + printContent + '</body></html>');
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    });
});
</script>