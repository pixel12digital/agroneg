-- Limpar tabelas existentes
DELETE FROM tags;
DELETE FROM categorias WHERE id > 6;

-- Atualizar categorias existentes
UPDATE categorias SET 
nome = 'Produtores Rurais', 
descricao = 'Agricultores e produtores do setor primário'
WHERE id = 1;

UPDATE categorias SET 
nome = 'Cooperativas Agrícolas', 
descricao = 'Associações de produtores e cooperativas do setor'
WHERE id = 5;

UPDATE categorias SET 
nome = 'Lojas Agropecuárias', 
descricao = 'Estabelecimentos de venda de produtos para o agronegócio'
WHERE id = 4;

UPDATE categorias SET 
nome = 'Serviços Veterinários', 
descricao = 'Clínicas e profissionais de saúde animal'
WHERE id = 3;

UPDATE categorias SET 
nome = 'Consultoria Técnica', 
descricao = 'Serviços de consultoria e assistência técnica'
WHERE id = 6;

-- Inserir novas categorias
INSERT INTO categorias (nome, slug, descricao) VALUES
('Maquinário Agrícola', 'maquinario', 'Venda e manutenção de equipamentos e maquinário'),
('Insumos Agrícolas', 'insumos', 'Fornecedores de insumos para agricultura'),
('Agroindústrias', 'agroindustrias', 'Indústrias de processamento de produtos agrícolas'),
('Distribuidores', 'distribuidores', 'Empresas de distribuição de produtos do agronegócio'),
('Mercados e Feiras', 'mercados', 'Pontos de venda direta ao consumidor');

-- Inserir tags iniciais com categorias diversas
INSERT INTO tags (categoria_id, nome, slug) VALUES
(1, 'Agricultura Familiar', 'agricultura-familiar'),
(7, 'Produtos Orgânicos', 'produtos-organicos'),
(6, 'Assistência Técnica', 'assistencia-tecnica'),
(7, 'Venda de Sementes', 'venda-sementes'),
(7, 'Adubos e Fertilizantes', 'adubos-fertilizantes'),
(7, 'Defensivos Agrícolas', 'defensivos-agricolas'),
(7, 'Irrigação', 'irrigacao'),
(2, 'Pecuária', 'pecuaria'),
(1, 'Horticultura', 'horticultura'),
(1, 'Fruticultura', 'fruticultura'),
(2, 'Produção de Leite', 'producao-leite'),
(2, 'Avicultura', 'avicultura'),
(2, 'Suinocultura', 'suinocultura'),
(6, 'Agricultura de Precisão', 'agricultura-precisao'),
(5, 'Crédito Rural', 'credito-rural'); 