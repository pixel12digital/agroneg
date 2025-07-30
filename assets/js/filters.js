/**
 * Script para manipulação dos filtros de busca - AgroNeg
 * 
 * Funcionalidades:
 * - Ativação/desativação do select de municípios
 * - Seleção de categorias
 * - Validação do formulário
 * - Carregamento assíncrono de municípios do banco de dados
 */

console.log('JS filters.js carregado');

document.addEventListener('DOMContentLoaded', function() {
    // Elementos do DOM
    const estadoSelect = document.getElementById('estado');
    const municipioSelect = document.getElementById('municipio');
    const categorias = document.querySelectorAll('.category-option');
    const buscarBtn = document.getElementById('buscar-btn');
    
    // Desabilitar o select de municípios inicialmente
    if (municipioSelect) {
        municipioSelect.disabled = true;
    }
    
    // Verificar se há categorias na URL para pré-selecionar
    function preencherCategoriasDaURL() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('categorias')) {
            const categoriasURL = urlParams.get('categorias').split(',');
            categorias.forEach(function(categoria) {
                if (categoriasURL.includes(categoria.dataset.value)) {
                    categoria.classList.add('active');
                }
            });
        }
    }
    
    // Chamar função para pré-selecionar categorias da URL
    preencherCategoriasDaURL();
    
    // Se já houver um estado selecionado ao carregar a página, habilite o select de município
    if (estadoSelect && estadoSelect.value !== '') {
        municipioSelect.disabled = false;
    }
    
    // Event listener para o select de estados
    if (estadoSelect) {
        estadoSelect.addEventListener('change', function() {
            if (this.value !== '') {
                // Habilitar o select de municípios quando um estado for selecionado
                municipioSelect.disabled = false;
                
                // Carregar municípios via AJAX com base no estado selecionado
                carregarMunicipios(this.value);
            } else {
                // Se nenhum estado for selecionado, desabilitar o select de municípios
                municipioSelect.disabled = true;
                municipioSelect.innerHTML = '<option value="">Selecione um município</option>';
            }
        });
    }
    
    // Função para carregar municípios via AJAX
    function carregarMunicipios(estadoId) {
        // Mostrar indicador de carregamento
        municipioSelect.innerHTML = '<option value="">Carregando municípios...</option>';
        
        // Detectar caminho correto da API baseado na URL atual
        const currentPath = window.location.pathname;
        const hostname = window.location.hostname;
        
        // Se estiver no localhost, usar caminho com /Agroneg/
        // Se estiver na produção (agroneg.eco.br), usar caminho relativo
        let apiPath;
        if (hostname === 'localhost' || hostname === '127.0.0.1') {
            apiPath = '/Agroneg/api/get_municipios.php';
        } else {
            apiPath = '../api/get_municipios.php';
        }
        
        console.log('Filters.js - Hostname:', hostname);
        console.log('Filters.js - Caminho atual:', currentPath);
        console.log('Filters.js - Caminho da API:', apiPath);
        
        // Fazer requisição AJAX para o endpoint correto, usando estado_id
        fetch(`${apiPath}?estado_id=${estadoId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na resposta da rede');
                }
                return response.json();
            })
            .then(data => {
                // Limpar select de municípios
                municipioSelect.innerHTML = '<option value="">Selecione um município</option>';
                
                // Adicionar municípios retornados pela API
                if (data && data.length > 0) {
                    data.forEach(municipio => {
                        const option = document.createElement('option');
                        // O valor do município agora será seu ID numérico, não o slug
                        option.value = municipio.id;
                        option.textContent = municipio.nome;
                        municipioSelect.appendChild(option);
                    });
                } else {
                    municipioSelect.innerHTML = '<option value="">Nenhum município encontrado</option>';
                }
                // Habilitar o select após o carregamento
                municipioSelect.disabled = false;
            })
            .catch(error => {
                console.error('Erro ao carregar municípios:', error);
                municipioSelect.innerHTML = '<option value="">Erro ao carregar municípios</option>';
                // Mantém desabilitado em caso de erro
                municipioSelect.disabled = true;
            });
    }
    
    // Disparar o evento change do estado ao carregar a página se já houver valor
    if (estadoSelect && estadoSelect.value !== '') {
        const event = new Event('change');
        estadoSelect.dispatchEvent(event);
    }
    
    // Event listener para as categorias
    categorias.forEach(function(categoria) {
        categoria.addEventListener('click', function() {
            // Toggle da classe active
            this.classList.toggle('active');
        });
    });
    
    // Função para aplicar filtros por categoria na página atual
    function aplicarFiltrosCategoria() {
        // Coletar categorias selecionadas
        const categoriasAtivas = [];
        document.querySelectorAll('.category-option.active').forEach(function(cat) {
            categoriasAtivas.push(cat.dataset.value);
        });
        
        // Obter parâmetros atuais da URL
        const urlParams = new URLSearchParams(window.location.search);
        const estado = urlParams.get('estado');
        const municipio = urlParams.get('municipio');
        
        if (estado && municipio) {
            // Construir URL para a página de resultados
            let url = `produtores.php?estado=${estado}&municipio=${municipio}`;
            
            // Adicionar categorias à URL, se houver
            if (categoriasAtivas.length > 0) {
                url += `&categorias=${categoriasAtivas.join(',')}`;
            }
            
            // Redirecionar para a página de resultados
            window.location.href = url;
        }
    }

    // Garantir e validar selects antes do submit
    const filterForm = document.querySelector('.filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            // Remover o preventDefault e o redirecionamento
            // Apenas validação simples
            const estadoSelect = document.getElementById('estado');
            const municipioSelect = document.getElementById('municipio');
            
            const estadoId = estadoSelect.value;
            const municipioId = municipioSelect.value;

            if (!estadoId || !municipioId) {
                alert('Por favor, selecione um estado e um município.');
                e.preventDefault();
                return;
            }
            // O submit padrão irá acontecer, enviando para a própria página
        });
    }

    // Remove a lógica antiga de forçar seleção, pois a nova abordagem é mais limpa
}); 