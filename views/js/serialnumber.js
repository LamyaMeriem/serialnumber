$(document).ready(function() {
    console.log("Script Serial Number initialisé");

    // Fonction de recherche de produits
    function searchProducts(searchQuery) {
        console.log("Recherche de produits avec la requête :", searchQuery);
        
        if (typeof ajaxUrl === 'undefined') {
            console.error("ajaxUrl n'est pas défini");
            return;
        }
        
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                ajax: true,
                action: 'searchProduct',
                search_query: searchQuery || ''
            },
            beforeSend: function() {
                $('#search_results .panel-body').html('<div class="text-center"><i class="icon-spinner icon-spin"></i> Recherche en cours...</div>');
            },
            success: function(response) {
                console.log("Résultats de recherche reçus");
                $('#search_results .panel-body').html(response);
            },
            error: function(xhr, status, error) {
                console.error("Erreur lors de la recherche de produits:", error);
                $('#search_results .panel-body').html('<div class="alert alert-danger">Une erreur est survenue lors de la recherche.</div>');
            }
        });
    }

    // Déclenche la recherche lors de la saisie dans le champ
    $('#search_query').on('keyup', function(e) {
        var searchQuery = $(this).val();
        
        // Recherche automatique après 3 caractères ou sur Entrée
        if (e.key === 'Enter' || searchQuery.length >= 3 || searchQuery.length === 0) {
            searchProducts(searchQuery);
        }
    });

    // Recherche au focus si le champ est vide (pour afficher des résultats par défaut)
    $('#search_query').on('focus', function() {
        if ($(this).val().length === 0) {
            searchProducts('');
        }
    });

    // Gestion de l'impression des numéros de série
    $(document).on('click', '#print-button', function() {
        console.log("Bouton d'impression cliqué");
        var serialNumbers = $('#serial_numbers').val().trim();
        if (!serialNumbers) {
            alert("Veuillez entrer des numéros de série avant d'imprimer.");
            return;
        }

        var numbers = serialNumbers.split(/[\r\n\s]+/).filter(function(n) { return n.trim(); });
        var printContent = '<h2>Numéros de série</h2>';
        
        // Ajouter le nom du produit si disponible
        var productName = $('#product_name').text() || $('input[name="product_name"]').val();
        if (productName) {
            printContent += '<p><strong>Produit :</strong> ' + productName + '</p>';
        }
        
        printContent += '<div style="margin-top: 20px;">';
        numbers.forEach(function(number) {
            printContent += '<div style="border: 1px solid #000; padding: 10px; margin-bottom: 10px; page-break-inside: avoid;">';
            printContent += '<p style="font-size: 14px; margin: 0;"><strong>' + number + '</strong></p>';
            printContent += '</div>';
        });
        printContent += '</div>';

        var printWindow = window.open('', 'PRINT', 'height=600,width=800');
        printWindow.document.write('<html><head><title>Impression Numéros de Série</title>');
        printWindow.document.write('<style>');
        printWindow.document.write('body { font-family: Arial, sans-serif; margin: 20px; }');
        printWindow.document.write('@media print { body { margin: 0; } }');
        printWindow.document.write('</style>');
        printWindow.document.write('</head><body>' + printContent + '</body></html>');
        printWindow.document.close();
        printWindow.focus();
        
        setTimeout(function() {
            printWindow.print();
            printWindow.close();
        }, 250);
    });

    // Gestion du formulaire des numéros de série avec validation améliorée
    $(document).on('submit', '#serial-number-form', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        var submitButton = $(this).find('button[type="submit"]');
        var originalButtonText = submitButton.html();
        
        // Validation côté client
        var serialNumbers = $('#serial_numbers').val().trim();
        if (!serialNumbers) {
            $('#message-container').html('<div class="alert alert-warning">Veuillez entrer au moins un numéro de série.</div>');
            return;
        }
        
        // Désactiver le bouton pendant le traitement
        submitButton.prop('disabled', true).html('<i class="icon-spinner icon-spin"></i> Enregistrement...');
        
        $.ajax({
            type: 'POST',
            url: $(this).attr('action'),
            data: formData,
            dataType: 'json',
            timeout: 30000, // 30 secondes de timeout
            success: function(response) {
                var messageContainer = $('#message-container');
                if (response.success) {
                    messageContainer.html('<div class="alert alert-success"><i class="icon-check"></i> ' + response.message + '</div>');
                    // Vider le textarea en cas de succès
                    $('#serial_numbers').val('');
                } else {
                    messageContainer.html('<div class="alert alert-danger"><i class="icon-warning"></i> ' + response.message + '</div>');
                }
                
                // Faire défiler vers le message
                $('html, body').animate({
                    scrollTop: messageContainer.offset().top - 100
                }, 500);
            },
            error: function(xhr, status, error) {
                var errorMessage = 'Une erreur est survenue';
                if (status === 'timeout') {
                    errorMessage = 'Délai d\'attente dépassé. Veuillez réessayer.';
                } else if (xhr.responseText) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        errorMessage = response.message || errorMessage;
                    } catch (e) {
                        errorMessage += ' : ' + error;
                    }
                }
                $('#message-container').html('<div class="alert alert-danger"><i class="icon-warning"></i> ' + errorMessage + '</div>');
            },
            complete: function() {
                // Réactiver le bouton
                submitButton.prop('disabled', false).html(originalButtonText);
            }
        });
    });

    // Auto-resize du textarea
    $(document).on('input', '#serial_numbers', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });

    // Validation en temps réel du format des numéros de série
    $(document).on('blur', '#serial_numbers', function() {
        var serialNumbers = $(this).val().trim();
        if (serialNumbers) {
            var numbers = serialNumbers.split(/[\r\n\s]+/).filter(function(n) { return n.trim(); });
            var invalidNumbers = [];
            
            // Ici, vous pourriez ajouter une validation basée sur le format configuré
            // Pour l'instant, on vérifie juste que ce ne sont pas des chaînes vides
            numbers.forEach(function(number) {
                if (number.length < 3) { // Exemple de validation minimale
                    invalidNumbers.push(number);
                }
            });
            
            if (invalidNumbers.length > 0) {
                $('#message-container').html(
                    '<div class="alert alert-warning">' +
                    '<i class="icon-warning"></i> Attention : certains numéros semblent trop courts : ' + 
                    invalidNumbers.join(', ') +
                    '</div>'
                );
            } else {
                $('#message-container').html('');
            }
        }
    });

    // Amélioration de l'interface : compteur de numéros de série
    $(document).on('input', '#serial_numbers', function() {
        var serialNumbers = $(this).val().trim();
        var count = 0;
        if (serialNumbers) {
            count = serialNumbers.split(/[\r\n\s]+/).filter(function(n) { return n.trim(); }).length;
        }
        
        var counterElement = $('#serial-count');
        if (counterElement.length === 0) {
            $(this).after('<small id="serial-count" class="help-block"></small>');
            counterElement = $('#serial-count');
        }
        
        counterElement.text(count + ' numéro(s) de série détecté(s)');
    });
});