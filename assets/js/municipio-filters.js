/**
 * Script para manipulação dos filtros de categoria na página de município - AgroNeg
 * 
 * Funcionalidades:
 * - Seleção de categorias 
 * - Atualização dinâmica dos filtros via AJAX
 * - Filtros funcionam na página atual do município sem recarregar
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elementos do DOM
    const categorias = document.querySelectorAll('.category-option');
    const parceirosContainer = document.querySelector('#parceiros-container');
    const resultadosContador = document.querySelector('.resultados-contador');
    const semResultados = document.querySelector('.sem-resultados');
    
    // Parâmetros da URL atual - detectar se é slug ou ID
    const urlParams = new URLSearchParams(window.location.search);
    const estadoAtual = urlParams.get('estado');
    const municipioAtual = urlParams.get('municipio');
    const slugEstado = urlParams.get('slug_estado');
    const slugMunicipio = urlParams.get('slug_municipio');
    
    // Detectar tipo de URL (slug ou ID)
    const isSlugUrl = window.location.pathname.includes('/') && !window.location.search.includes('estado=');
    
    // Função para obter URL base
    function getBaseUrl() {
        if (isSlugUrl && slugEstado && slugMunicipio) {
            return `/${slugEstado}/${slugMunicipio}`;
        } else if (estadoAtual && municipioAtual) {
            return `municipio.php?estado=${estadoAtual}&municipio=${municipioAtual}`;
        }
        return '';
    }
    
    // Event listener para as categorias
    categorias.forEach(function(categoria) {
        categoria.addEventListener('click', function(e) {
            // Se for a categoria "Todos", usar o onclick já definido no HTML
            if (this.dataset.value === 'todos') {
                return; // Deixar o onclick do HTML funcionar
            }
            
            // Para outras categorias, aplicar filtro via AJAX
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle da classe active
            this.classList.toggle('active');
            
            // Aplicar filtros via AJAX
            aplicarFiltrosAJAX();
        });
    });
    
    // Função para aplicar filtros via AJAX
    function aplicarFiltrosAJAX() {
        const categoriasAtivas = [];
        document.querySelectorAll('.category-option.active').forEach(function(cat) {
            if (cat.dataset.value !== 'todos') {
                categoriasAtivas.push(cat.dataset.value);
            }
        });
        
        // Construir URL para a requisição AJAX
        let url = `api/filtrar_parceiros.php?estado=${estadoAtual}&municipio=${municipioAtual}`;
        
        // Adicionar categorias à URL, se houver
        if (categoriasAtivas.length > 0) {
            url += `&categorias=${categoriasAtivas.join(',')}`;
        }
        
        // Mostrar indicador de carregamento
        mostrarCarregamento();
        
        // Fazer requisição AJAX
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na resposta da rede');
                }
                return response.json();
            })
            .then(data => {
                // Atualizar resultados
                atualizarResultados(data);
                
                // Atualizar URL sem recarregar a página
                atualizarURL(categoriasAtivas);
                
                // Atualizar indicador de filtros ativos
                atualizarIndicadorFiltros();
            })
            .catch(error => {
                console.error('Erro ao carregar filtros:', error);
                mostrarErro('Erro ao carregar os resultados. Tente novamente.');
            });
    }
    
    // Função para mostrar carregamento
    function mostrarCarregamento() {
        if (parceirosContainer) {
            parceirosContainer.innerHTML = `
                <div class="carregando-resultados">
                    <div class="spinner"></div>
                    <p>Carregando resultados...</p>
                </div>
            `;
        }
    }
    
    // Função para atualizar resultados
    function atualizarResultados(data) {
        if (parceirosContainer) {
            if (data.parceiros && data.parceiros.length > 0) {
                // Atualizar contador
                if (resultadosContador) {
                    resultadosContador.innerHTML = `
                        <h4>
                            ${data.contador_texto}
                        </h4>
                    `;
                }
                
                // Atualizar grid de parceiros
                parceirosContainer.innerHTML = data.html_parceiros;
                
                // Esconder mensagem de sem resultados
                if (semResultados) {
                    semResultados.style.display = 'none';
                }
            } else {
                // Mostrar mensagem de sem resultados
                parceirosContainer.innerHTML = '';
                if (semResultados) {
                    semResultados.style.display = 'block';
                    semResultados.innerHTML = `
                        <p>Nenhum parceiro encontrado para as categorias selecionadas.</p>
                        <p>Tente selecionar outras categorias ou limpar os filtros para ver todos os parceiros.</p>
                        <a href="${getBaseUrl()}" class="btn-limpar-filtro">Limpar filtros</a>
                    `;
                }
                
                // Atualizar contador
                if (resultadosContador) {
                    resultadosContador.innerHTML = `
                        <h4>0 parceiros encontrados para os filtros selecionados</h4>
                    `;
                }
            }
        }
    }
    
    // Função para mostrar erro
    function mostrarErro(mensagem) {
        if (parceirosContainer) {
            parceirosContainer.innerHTML = `
                <div class="erro-carregamento">
                    <p>${mensagem}</p>
                    <button onclick="aplicarFiltrosAJAX()" class="btn-tentar-novamente">Tentar novamente</button>
                </div>
            `;
        }
    }
    
    // Função para atualizar URL sem recarregar
    function atualizarURL(categoriasAtivas) {
        let novaURL = getBaseUrl();
        
        if (categoriasAtivas.length > 0) {
            if (isSlugUrl) {
                novaURL += `?categorias=${categoriasAtivas.join(',')}`;
            } else {
                novaURL += `&categorias=${categoriasAtivas.join(',')}`;
            }
        }
        
        // Atualizar URL sem recarregar a página
        window.history.pushState({}, '', novaURL);
    }
    
    // Adicionar indicador visual de filtros ativos
    function atualizarIndicadorFiltros() {
        const filtrosAtivos = document.querySelectorAll('.category-option.active');
        const indicador = document.getElementById('filtros-ativos-indicador');
        
        if (filtrosAtivos.length > 0) {
            if (!indicador) {
                const novoIndicador = document.createElement('div');
                novoIndicador.id = 'filtros-ativos-indicador';
                novoIndicador.className = 'filtros-ativos-indicador';
                novoIndicador.innerHTML = `
                    <span>Filtros ativos: ${filtrosAtivos.length}</span>
                    <button onclick="limparFiltros()" class="btn-limpar-filtros">Limpar</button>
                `;
                
                const filtrosContainer = document.querySelector('.municipio-filters');
                if (filtrosContainer) {
                    filtrosContainer.appendChild(novoIndicador);
                }
            } else {
                indicador.querySelector('span').textContent = `Filtros ativos: ${filtrosAtivos.length}`;
            }
        } else if (indicador) {
            indicador.remove();
        }
    }
    
    // Função global para limpar filtros
    window.limparFiltros = function() {
        // Remover classe active de todas as categorias
        document.querySelectorAll('.category-option.active').forEach(function(cat) {
            cat.classList.remove('active');
        });
        
        // Aplicar filtros vazios
        aplicarFiltrosAJAX();
    };
    
    // Atualizar indicador ao carregar a página
    atualizarIndicadorFiltros();
}); 