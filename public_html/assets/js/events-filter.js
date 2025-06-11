/**
 * Script para filtrar eventos na página de eventos
 */
document.addEventListener('DOMContentLoaded', function() {
    // Elementos do filtro
    const filterButtons = document.querySelectorAll('.filter-button');
    const eventCards = document.querySelectorAll('.event-card');
    
    // Adicionar evento de clique em cada botão de filtro
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remover classe 'active' de todos os botões
            filterButtons.forEach(btn => btn.classList.remove('active'));
            
            // Adicionar classe 'active' ao botão clicado
            this.classList.add('active');
            
            // Obter o valor do filtro
            const filter = this.getAttribute('data-filter');
            
            // Mostrar/ocultar cards com base no filtro
            eventCards.forEach(card => {
                if (filter === 'all' || card.getAttribute('data-category') === filter) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
    
    // Lidar com a paginação
    const paginationButtons = document.querySelectorAll('.pagination-btn');
    
    paginationButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remover classe 'active' de todos os botões de paginação
            paginationButtons.forEach(btn => btn.classList.remove('active'));
            
            // Adicionar classe 'active' ao botão clicado
            this.classList.add('active');
            
            // Aqui você pode implementar a lógica de paginação real
            // Por exemplo, carregar diferentes conjuntos de eventos via AJAX
            // Por enquanto, apenas uma simulação visual
        });
    });
}); 