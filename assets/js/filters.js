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

// Teste simples para verificar se o JavaScript está funcionando
console.log('🧪 Teste básico do JavaScript - se você vê esta mensagem, o JS está funcionando');

// Teste adicional - alert para confirmar que o JS está funcionando
// alert('JavaScript está funcionando!');

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 DOM carregado, iniciando configuração dos filtros...');
    
    // Elementos do DOM
    const estadoSelect = document.getElementById('estado');
    const municipioSelect = document.getElementById('municipio');
    const categorias = document.querySelectorAll('.category-option');
    const buscarBtn = document.getElementById('buscar-btn');
    
    console.log('🔍 Elementos encontrados:');
    console.log('- estadoSelect:', estadoSelect ? '✅' : '❌');
    console.log('- municipioSelect:', municipioSelect ? '✅' : '❌');
    console.log('- categorias:', categorias.length);
    console.log('- buscarBtn:', buscarBtn ? '✅' : '❌');
    
    // Teste adicional - verificar se os elementos têm os atributos corretos
    if (estadoSelect) {
        console.log('📋 Estado select - value:', estadoSelect.value, 'options:', estadoSelect.options.length);
    }
    if (municipioSelect) {
        console.log('📋 Município select - disabled:', municipioSelect.disabled, 'options:', municipioSelect.options.length);
    }
    
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
        console.log('✅ Event listener do estado configurado');
        estadoSelect.addEventListener('change', function() {
            console.log('🔄 Estado alterado para:', this.value);
            console.log('🔄 Tipo do valor:', typeof this.value);
            console.log('🔄 Valor é string vazia?', this.value === '');
            
            if (this.value !== '') {
                // Habilitar o select de municípios quando um estado for selecionado
                municipioSelect.disabled = false;
                console.log('✅ Select de município habilitado');
                
                // Carregar municípios via AJAX com base no estado selecionado
                console.log('🚀 Iniciando carregamento de municípios...');
                carregarMunicipios(this.value);
            } else {
                // Se nenhum estado for selecionado, desabilitar o select de municípios
                municipioSelect.disabled = true;
                municipioSelect.innerHTML = '<option value="">Selecione um município</option>';
                console.log('❌ Select de município desabilitado');
            }
        });
        
        // Teste adicional - verificar se o event listener foi adicionado
        console.log('🧪 Testando se o event listener foi adicionado...');
        
    } else {
        console.error('❌ Elemento estadoSelect não encontrado!');
    }
    
    // Função para carregar municípios via AJAX
    function carregarMunicipios(estadoId) {
        console.log('🔍 Função carregarMunicipios chamada com estado ID:', estadoId);
        console.log('🔍 Tipo do estadoId:', typeof estadoId);
        console.log('🔍 EstadoId é válido?', estadoId && estadoId !== '');
        
        // Mostrar indicador de carregamento
        municipioSelect.innerHTML = '<option value="">Carregando municípios...</option>';
        
        // Detectar caminho correto da API baseado na URL atual
        const currentPath = window.location.pathname;
        const hostname = window.location.hostname;
        
        // Se estiver no localhost, usar caminho com /Agroneg/
        // Se estiver na produção (agroneg.eco.br), usar caminho relativo
        // Usar API com fallback para dados estáticos
        let apiPath;
        if (hostname === 'localhost' || hostname === '127.0.0.1') {
            // Verificar se estamos no subdiretório /Agroneg/
            if (currentPath.includes('/Agroneg/')) {
                apiPath = '/Agroneg/api/get_municipios_fallback.php';
            } else {
                apiPath = 'api/get_municipios_fallback.php';
            }
        } else {
            apiPath = 'api/get_municipios_fallback.php';
        }
        
        console.log('🌐 Hostname:', hostname);
        console.log('📁 Caminho atual:', currentPath);
        console.log('🔗 Caminho da API:', apiPath);
        console.log('📡 URL completa:', `${apiPath}?estado_id=${estadoId}`);
        
        // Fazer requisição AJAX para o endpoint correto, usando estado_id
        console.log('📡 Fazendo requisição para:', `${apiPath}?estado_id=${estadoId}`);
        fetch(`${apiPath}?estado_id=${estadoId}`)
            .then(response => {
                console.log('📨 Resposta recebida:', response.status, response.statusText);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('📊 Dados recebidos:', data);
                
                // Limpar select de municípios
                municipioSelect.innerHTML = '<option value="">Selecione um município</option>';
                
                // Adicionar municípios retornados pela API
                if (data && data.length > 0) {
                    console.log(`✅ Carregando ${data.length} municípios`);
                    data.forEach(municipio => {
                        const option = document.createElement('option');
                        option.value = municipio.id;
                        option.textContent = municipio.nome;
                        // Armazenar o slug no dataset para uso posterior
                        if (municipio.slug) {
                            option.dataset.slug = municipio.slug;
                        }
                        municipioSelect.appendChild(option);
                        console.log(`  - ${municipio.nome} (ID: ${municipio.id}, Slug: ${municipio.slug || 'não informado'})`);
                    });
                    console.log('✅ Municípios carregados com sucesso!');
                } else {
                    console.log('⚠️ Nenhum município encontrado');
                    municipioSelect.innerHTML = '<option value="">Nenhum município encontrado</option>';
                }
                // Habilitar o select após o carregamento
                municipioSelect.disabled = false;
            })
            .catch(error => {
                console.error('❌ Erro ao carregar municípios:', error);
                console.error('❌ Detalhes do erro:', error.message);
                console.error('❌ Stack trace:', error.stack);
                municipioSelect.innerHTML = '<option value="">Erro ao carregar municípios</option>';
                // Mantém desabilitado em caso de erro
                municipioSelect.disabled = true;
            });
    }
    
    // REMOVIDO: Disparar evento change automaticamente
    // Isso estava causando requisições desnecessárias ao banco
    // O usuário deve selecionar manualmente o estado
    
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
        
        // Obter slugs do estado e município
        const estadoSelect = document.getElementById('estado');
        const municipioSelect = document.getElementById('municipio');
        
        if (estadoSelect && municipioSelect && estadoSelect.value && municipioSelect.value) {
            const estadoSlug = estadoSelect.options[estadoSelect.selectedIndex].dataset.slug;
            const municipioOption = municipioSelect.options[municipioSelect.selectedIndex];
            
            // Verificar se o slug do estado foi obtido corretamente
            if (!estadoSlug) {
                console.error('❌ Slug do estado não encontrado!');
                alert('Erro: Slug do estado não encontrado. Tente novamente.');
                return;
            }
            
            // Usar slug da API se disponível, senão gerar dinamicamente
            let municipioSlug;
            if (municipioOption.dataset.slug) {
                municipioSlug = municipioOption.dataset.slug;
            } else {
                const municipioNome = municipioOption.textContent;
                municipioSlug = municipioNome
                    .toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }
            
            let url;
            
            if (categoriasAtivas.length > 0) {
                // Se há categorias selecionadas, redirecionar para página específica do tipo
                const tipoSlug = categoriasAtivas[0];
                url = `/${tipoSlug}/${estadoSlug}/${municipioSlug}`;
                
                // Se houver múltiplas categorias, adicionar como parâmetro
                if (categoriasAtivas.length > 1) {
                    url += `?categorias=${categoriasAtivas.join(',')}`;
                }
            } else {
                // Se não há categorias selecionadas, redirecionar para página do município
                url = `/${estadoSlug}/${municipioSlug}`;
            }
            
            // Redirecionar para a página de resultados
            window.location.href = url;
        } else {
            alert('Por favor, selecione um estado e um município.');
        }
    }

    // Garantir e validar selects antes do submit
    const filterForm = document.querySelector('.filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const estadoSelect = document.getElementById('estado');
            const municipioSelect = document.getElementById('municipio');
            
            const estadoId = estadoSelect.value;
            const municipioId = municipioSelect.value;

            if (!estadoId || !municipioId) {
                alert('Por favor, selecione um estado e um município.');
                return;
            }
            
            // Verificar se há categorias selecionadas
            const categoriasAtivas = [];
            document.querySelectorAll('.category-option.active').forEach(function(cat) {
                categoriasAtivas.push(cat.dataset.value);
            });
            
            // Obter slugs do estado e município
            const estadoSlug = estadoSelect.options[estadoSelect.selectedIndex].dataset.slug;
            const municipioOption = municipioSelect.options[municipioSelect.selectedIndex];
            
            console.log('🔍 Debug - Estado selecionado:', estadoSelect.value, 'Slug:', estadoSlug);
            console.log('🔍 Debug - Município selecionado:', municipioSelect.value, 'Nome:', municipioOption.textContent);
            
            // Verificar se o slug do estado foi obtido corretamente
            if (!estadoSlug) {
                console.error('❌ Slug do estado não encontrado!');
                alert('Erro: Slug do estado não encontrado. Tente novamente.');
                return;
            }
            
            // Usar slug da API se disponível, senão gerar dinamicamente
            let municipioSlug;
            if (municipioOption.dataset.slug) {
                municipioSlug = municipioOption.dataset.slug;
                console.log('✅ Usando slug da API:', municipioSlug);
            } else {
                const municipioNome = municipioOption.textContent;
                municipioSlug = municipioNome
                    .toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-+|-+$/g, '');
                console.log('⚠️ Gerando slug dinamicamente:', municipioSlug, 'de:', municipioNome);
            }
            
            let url;
            
            if (categoriasAtivas.length > 0) {
                // Se há categorias selecionadas, redirecionar para página específica do tipo
                const tipoSlug = categoriasAtivas[0];
                url = `/${tipoSlug}/${estadoSlug}/${municipioSlug}`;
                
                // Se houver múltiplas categorias, adicionar como parâmetro
                if (categoriasAtivas.length > 1) {
                    url += `?categorias=${categoriasAtivas.join(',')}`;
                }
            } else {
                // Se não há categorias selecionadas, redirecionar para página do município
                url = `/${estadoSlug}/${municipioSlug}`;
            }
            
            console.log('🎯 URL final gerada:', url);
            console.log('🚀 Iniciando redirecionamento...');
            window.location.href = url;
        });
    }

    // Remove a lógica antiga de forçar seleção, pois a nova abordagem é mais limpa
}); 