-- Script para atualizar a estrutura da tabela parceiros
-- Configurar conexão para UTF-8
SET NAMES utf8mb4;
SET character_set_client = utf8mb4;

-- 1. Primeiro, atualizar os registros existentes sem tipo definido
UPDATE parceiros SET tipo = 'Não especificado' WHERE tipo IS NULL OR tipo = '';

-- 2. Criar tabela de referência para os tipos de parceiros
CREATE TABLE IF NOT EXISTS tipos_parceiros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Inserir valores padrão na tabela de tipos
INSERT INTO tipos_parceiros (nome, descricao) VALUES 
('Produtor Rural', 'Agricultores e produtores do setor primário'),
('Criador', 'Criadores de animais como gado, aves, suínos, etc.'),
('Veterinário', 'Profissionais e clínicas de saúde animal'),
('Loja Agropecuária', 'Estabelecimentos de venda de produtos para o agronegócio'),
('Cooperativa', 'Associações de produtores e cooperativas do setor'),
('Distribuidora', 'Empresas de distribuição de produtos do agronegócio'),
('Agroindústria', 'Indústrias de processamento de produtos agrícolas'),
('Mercado/Feira', 'Pontos de venda direta ao consumidor'),
('Consultor Técnico', 'Serviços de consultoria e assistência técnica'),
('Fornecedor de Insumos', 'Fornecedores de insumos para agricultura'),
('Maquinário Agrícola', 'Venda e manutenção de equipamentos e maquinário');

-- 4. Modificar coluna tipo para NOT NULL na tabela parceiros
ALTER TABLE parceiros MODIFY COLUMN tipo VARCHAR(100) NOT NULL;

-- Nota: Este script pode ser executado com segurança mesmo que você já tenha dados na tabela parceiros,
-- pois primeiro atualizamos os registros existentes e depois alteramos a estrutura da tabela. 