-- Script para atualizar os tipos de parceiros para restringir às opções específicas
-- Configurar conexão para UTF-8
SET NAMES utf8mb4;
SET character_set_client = utf8mb4;

-- Limpar a tabela existente
TRUNCATE TABLE tipos_parceiros;

-- Inserir apenas os 5 tipos desejados
INSERT INTO tipos_parceiros (nome, descricao) VALUES 
('Produtores', 'Agricultores e produtores rurais do setor primário'),
('Criadores', 'Criadores de animais como gado, aves, suínos, etc.'),
('Veterinarios', 'Profissionais e clínicas de saúde animal'),
('Lojas Agropet', 'Estabelecimentos de venda de produtos para o agronegócio e animais'),
('Cooperativas', 'Associações de produtores e cooperativas do setor');

-- Atualizar os registros existentes com tipos fora da lista para manter a integridade
UPDATE parceiros 
SET tipo = 'Produtores' 
WHERE tipo NOT IN ('Produtores', 'Criadores', 'Veterinarios', 'Lojas Agropet', 'Cooperativas'); 