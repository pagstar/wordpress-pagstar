jQuery(document).ready(function($) {
    // Atualizar o botão de ativar/desativar
    $('input[name="woocommerce_pagstar_enabled"]').on('change', function() {
        var checkbox = $(this);
        var value = checkbox.is(':checked') ? 'yes' : 'no';
        
        $.ajax({
            url: pagstar_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'woocommerce_update_options_payment_gateways_pagstar',
                enabled: value,
                nonce: pagstar_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Atualizar o status visual
                    var status = $('.woocommerce_pagstar_enabled_field .description');
                    if (value === 'yes') {
                        status.html('<span style="color: #4CAF50;">✓ Ativo</span>');
                    } else {
                        status.html('<span style="color: #f44336;">✗ Inativo</span>');
                    }
                    
                    // Mostrar toast de sucesso
                    showToast('Sucesso', response.data.message, 'success');
                } else {
                    // Reverter o estado do checkbox
                    checkbox.prop('checked', !checkbox.is(':checked'));
                    showToast('Erro', 'Erro ao atualizar status', 'error');
                }
            },
            error: function(xhr, status, error) {
                // Reverter o estado do checkbox
                checkbox.prop('checked', !checkbox.is(':checked'));
                showToast('Erro', 'Erro ao atualizar status: ' + error, 'error');
            }
        });
    });

    // Função para mostrar toast
    function showToast(title, message, type) {
        var toast = $('<div class="pagstar-toast ' + type + '">' +
            '<div class="toast-header">' +
            '<span class="dashicons ' + getIcon(type) + ' icon"></span>' +
            '<span class="title">' + title + '</span>' +
            '</div>' +
            '<div class="toast-body">' + message + '</div>' +
            '</div>');
        
        $('body').append(toast);
        setTimeout(function() {
            toast.addClass('show');
        }, 100);

        setTimeout(function() {
            toast.remove();
        }, 5000);
    }

    // Função para obter o ícone correto
    function getIcon(type) {
        switch(type) {
            case 'success':
                return 'dashicons-yes-alt';
            case 'error':
                return 'dashicons-warning';
            case 'warning':
                return 'dashicons-info';
            case 'info':
                return 'dashicons-info';
        }
    }
}); 