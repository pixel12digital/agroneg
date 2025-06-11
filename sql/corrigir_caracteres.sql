-- Script para corrigir caracteres com problemas de codificação

-- Configurar conexão para UTF-8
SET NAMES utf8mb4;
SET character_set_client = utf8mb4;

-- Corrigir Agroindústrias
UPDATE categorias SET 
nome = 'Agroindústrias', 
descricao = 'Indústrias de processamento de produtos agrícolas'
WHERE nome LIKE '%Agroind%strias%';

-- Corrigir Consultoria Técnica
UPDATE categorias SET 
nome = 'Consultoria Técnica', 
descricao = 'Serviços de consultoria e assistência técnica'
WHERE nome LIKE '%Consultoria T%cnica%';

-- Corrigir Cooperativas Agrícolas
UPDATE categorias SET 
nome = 'Cooperativas Agrícolas', 
descricao = 'Associações de produtores e cooperativas do setor'
WHERE nome LIKE '%Cooperativas Agr%colas%';

-- Corrigir Criadores
UPDATE categorias SET 
nome = 'Criadores', 
descricao = 'Criadores de animais'
WHERE nome = 'Criadores';

-- Corrigir Distribuidores
UPDATE categorias SET 
nome = 'Distribuidores', 
descricao = 'Empresas de distribuição de produtos do agronegócio'
WHERE nome = 'Distribuidores';

-- Corrigir Insumos Agrícolas
UPDATE categorias SET 
nome = 'Insumos Agrícolas', 
descricao = 'Fornecedores de insumos para agricultura'
WHERE nome LIKE '%Insumos Agr%colas%';

-- Corrigir Lojas Agropecuárias
UPDATE categorias SET 
nome = 'Lojas Agropecuárias', 
descricao = 'Estabelecimentos de venda de produtos para o agronegócio'
WHERE nome LIKE '%Lojas Agropecu%rias%';

-- Corrigir Maquinário Agrícola
UPDATE categorias SET 
nome = 'Maquinário Agrícola', 
descricao = 'Venda e manutenção de equipamentos e maquinário'
WHERE nome LIKE '%Maquin%rio%';

-- Corrigir Mercados e Feiras
UPDATE categorias SET 
nome = 'Mercados e Feiras', 
descricao = 'Pontos de venda direta ao consumidor'
WHERE nome = 'Mercados e Feiras';

-- Corrigir Produtores Rurais
UPDATE categorias SET 
nome = 'Produtores Rurais', 
descricao = 'Agricultores e produtores do setor primário'
WHERE nome = 'Produtores Rurais';

-- Corrigir Serviços Veterinários
UPDATE categorias SET 
nome = 'Serviços Veterinários', 
descricao = 'Clínicas e profissionais de saúde animal'
WHERE nome LIKE '%Servi%os Veterin%rios%';

-- Atualizar slugs para as categorias
UPDATE categorias SET 
slug = LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(nome, 
    'á', 'a'), 'à', 'a'), 'ã', 'a'), 'â', 'a'), 'é', 'e'), 
    'ê', 'e'), 'í', 'i'), 'ó', 'o'), 'ô', 'o'), 'ú', 'u')),
slug = REPLACE(slug, ' ', '-'),
slug = REPLACE(slug, 'ç', 'c')
WHERE 1=1; 