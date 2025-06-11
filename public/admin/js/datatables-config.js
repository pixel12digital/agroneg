/**
 * Configuração do DataTables para o painel administrativo
 */
$(document).ready(function() {
    if ($('#dataTable').length) {
        $('#dataTable').DataTable({
            responsive: true,
            language: {
                url: "//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json",
                // Sobrescrever algumas traduções específicas para garantir consistência
                sInfo: "Mostrando _START_ até _END_ de _TOTAL_ registros",
                sInfoEmpty: "Mostrando 0 até 0 de 0 registros",
                sInfoFiltered: "(filtrado de _MAX_ registros no total)",
                sZeroRecords: "Nenhum registro encontrado",
                sSearch: "Pesquisar:",
                sLengthMenu: "Mostrar _MENU_ registros",
                oPaginate: {
                    sFirst: "Primeiro",
                    sPrevious: "Anterior",
                    sNext: "Próximo",
                    sLast: "Último"
                }
            },
            columnDefs: [
                {
                    orderable: false,
                    targets: -1 // Última coluna (ações)
                }
            ],
            drawCallback: function() {
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        });
    }
}); 