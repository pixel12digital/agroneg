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
    function carregarMunicipios(estado) {
        // Mostrar indicador de carregamento
        municipioSelect.innerHTML = '<option value="">Carregando municípios...</option>';
        
        // Fazer requisição AJAX para o endpoint
        fetch(`api/municipios.php?estado=${estado}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro ao carregar municípios');
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
                        option.value = municipio.slug;
                        option.textContent = municipio.nome;
                        municipioSelect.appendChild(option);
                    });
                } else {
                    municipioSelect.innerHTML = '<option value="">Nenhum município encontrado</option>';
                }
                // Selecionar município correto se já houver na URL
                const urlParams = new URLSearchParams(window.location.search);
                const selectedMunicipio = urlParams.get('municipio');
                if (selectedMunicipio) {
                    municipioSelect.value = selectedMunicipio;
                    municipioSelect.disabled = false;
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                municipioSelect.innerHTML = '<option value="">Erro ao carregar municípios</option>';
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
            let url = `municipio.php?estado=${estado}&municipio=${municipio}`;
            
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
            const estado = document.getElementById('estado').value;
            const municipio = document.getElementById('municipio').value;
            console.log('Submit interceptado', { estado, municipio });
            if (!estado || !municipio) {
                alert('Selecione um estado e um município!');
                e.preventDefault();
                return false;
            }
            // Redireciona manualmente para a URL correta
            e.preventDefault();
            console.log('Redirecionando para:', `municipio.php?estado=${estado}&municipio=${municipio}`);
            window.location.href = `municipio.php?estado=${estado}&municipio=${municipio}`;
        });
    }

    // Forçar seleção do município ao carregar a página, se já houver valor na URL
    if (estadoSelect && municipioSelect) {
        const urlParams = new URLSearchParams(window.location.search);
        const selectedMunicipio = urlParams.get('municipio');
        if (selectedMunicipio) {
            estadoSelect.addEventListener('change', function() {
                setTimeout(function() {
                    municipioSelect.value = selectedMunicipio;
                    municipioSelect.disabled = false;
                }, 500);
            });
            if (estadoSelect.value !== '') {
                const event = new Event('change');
                estadoSelect.dispatchEvent(event);
            }
        }
    }
}); 