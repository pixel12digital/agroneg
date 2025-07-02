/**
 * Script para manipulação dos filtros de categoria na página de município - AgroNeg
 * 
 * Funcionalidades:
 * - Seleção de categorias 
 * - Atualização dinâmica dos filtros
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elementos do DOM
    const categorias = document.querySelectorAll('.category-option');
    
    // Parâmetros da URL atual
    const urlParams = new URLSearchParams(window.location.search);
    const estadoAtual = urlParams.get('estado');
    const municipioAtual = urlParams.get('municipio');
    
    // Event listener para as categorias (exceto "Todos" que já tem onclick)
    categorias.forEach(function(categoria) {
        // Ignorar a categoria "Todos" que já tem onclick
        if (categoria.dataset.value !== 'todos') {
            categoria.addEventListener('click', function() {
                // Toggle da classe active
                this.classList.toggle('active');
                
                // Coletar todas as categorias ativas
                const categoriasAtivas = [];
                document.querySelectorAll('.category-option.active').forEach(function(cat) {
                    if (cat.dataset.value !== 'todos') {
                        categoriasAtivas.push(cat.dataset.value);
                    }
                });
                
                // Construir URL para a página de resultados
                let url = `produtores.php?estado=${estadoAtual}&municipio=${municipioAtual}`;
                
                // Adicionar categorias à URL, se houver
                if (categoriasAtivas.length > 0) {
                    url += `&categorias=${categoriasAtivas.join(',')}`;
                }
                
                // Redirecionar para a página de resultados
                window.location.href = url;
            });
        }
    });
}); 