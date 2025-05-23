$(document).ready(function() {
    console.log("Script initialisé");

    // Charger JsBarcode dynamiquement
    var script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js';
    script.type = 'text/javascript';
    script.onload = function() {
        console.log('JsBarcode loaded successfully');
    };
    document.head.appendChild(script);

    // Fonction de recherche de produits
    function searchProducts(searchQuery) {
        console.log("Recherche de produits avec la requête :", searchQuery);
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            data: {
                ajax: true,
                action: 'searchProduct',
                search_query: searchQuery || ''
            },
            success: function(response) {
                console.log("Résultats de recherche reçus :", response);
                $('#search_results .panel-body').html(response);
            },
            error: function() {
                console.error("Erreur lors de la recherche de produits");
                $('#search_results .panel-body').html('<p>Une erreur est survenue lors de la recherche.</p>');
            }
        });
    }

    // Charger les 20 derniers produits au démarrage
    //searchProducts('');

    // Déclenche la recherche lors de la saisie dans le champ
    $('#search_query').on('keyup', function(e) {
        var searchQuery = $(this).val();
        if (e.key === 'Enter' || searchQuery.length > 2) {
            searchProducts(searchQuery);
        }
    });

    // Gestion de l'impression des numéros de série
    $('#print-button').on('click', function() {
        console.log("Bouton d'impression cliqué");
        var serialNumbers = $('#serial_numbers').val().trim();
        if (!serialNumbers) {
            alert("Veuillez entrer des numéros de série avant d'imprimer.");
            return;
        }

        var numbers = serialNumbers.split(/\s+/);
        var printWindow = window.open('', 'PRINT', 'height=600,width=800');
        printWindow.document.write('<html><head><title>Print Numéros de Série</title>');
        printWindow.document.write('<style>body { font-family: Arial, sans-serif; margin: 20px; }</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write('<h1>Numéros de série</h1>');

        numbers.forEach(function(number) {
            printWindow.document.write('<div style="border: 1px solid #000; padding: 10px; margin-bottom: 10px;">');
            printWindow.document.write('<p><strong>Produit :</strong> ' + ($('#product_name').text() || '--') + '</p>');
            printWindow.document.write('<svg id="barcode' + number + '"></svg>');
            printWindow.document.write('<p>' + number + '</p>');
            printWindow.document.write('</div>');
        });

        printWindow.document.write('</body></html>');
        printWindow.document.close();

        printWindow.onload = function() {
            console.log("Fenêtre d'impression chargée");
            if (typeof JsBarcode !== 'undefined') {
                numbers.forEach(function(number) {
                    var barcodeElement = printWindow.document.getElementById('barcode' + number);
                    if (barcodeElement) {
                        JsBarcode(barcodeElement, number, {
                            format: "CODE128",
                            lineColor: "#000",
                            width: 2,
                            height: 40,
                            displayValue: false
                        });
                    }
                });
                printWindow.print();
                printWindow.close();
            } else {
                console.error("JsBarcode n'est pas chargé");
            }
        };
    });

    // Gestion du formulaire des numéros de série
    /* $('#serial-number-form').on('submit', function(e) {
        console.log("1er step");
        e.preventDefault();
        console.log("2 step");
        var formData = $(this).serialize();
        console.log($(this).attr('action'));
        $.ajax({
            type: 'POST',
            url: $(this).attr('action'),
            data: formData,
            dataType: 'json',
            success: function(response) {
                var messageContainer = $('#message-container');
                if (response.success) {
                    messageContainer.html('<div class="alert alert-success">' + response.message + '</div>');
                } else {
                    messageContainer.html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function(xhr, status, error) {
                $('#message-container').html('<div class="alert alert-danger">Une erreur est survenue : ' + error + '</div>');
            }
        });
    }); */
    $('#serial-number-form').on('submit', function(e) {
        e.preventDefault(); // Empêche le formulaire de se soumettre normalement
        var formData = $(this).serialize(); // Capture les données du formulaire
    
        $.ajax({
            type: 'POST',
            url: $(this).attr('action'),
            data: formData,
            dataType: 'text', // Remplacez temporairement "json" par "text"
            success: function(response) {
                console.log(response);
                console.log("Réponse texte brute :", response); // Affichez la réponse brute
                try {
                    var jsonResponse = JSON.parse(response); // Essayez de convertir en JSON
                    var messageContainer = $('#message-container');
                    if (jsonResponse.success) {
                        messageContainer.html('<div class="alert alert-success">' + jsonResponse.message + '</div>');
                    } else {
                        messageContainer.html('<div class="alert alert-danger">' + jsonResponse.message + '</div>');
                    }
                } catch (e) {
                    console.error("Erreur de conversion JSON :", e);
                    $('#message-container').html('<div class="alert alert-danger">Réponse non valide reçue du serveur.</div>');
                }
            },
            error: function(xhr, status, error) {
                $('#message-container').html('<div class="alert alert-danger">Une erreur est survenue : ' + error + '</div>');
            }
        });
    });
    
    
});