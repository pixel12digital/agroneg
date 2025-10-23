-- Script para adicionar coluna slug na tabela tipos_parceiros
-- Configurar conexão para UTF-8
SET NAMES utf8mb4;
SET character_set_client = utf8mb4;

-- Verificar se a coluna slug já existe
SET @colexists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'tipos_parceiros' 
    AND COLUMN_NAME = 'slug'
);

-- Adicionar a coluna slug apenas se ela não existir
SET @addcol = IF(@colexists = 0, 
    'ALTER TABLE tipos_parceiros ADD COLUMN slug VARCHAR(100) NULL AFTER nome',
    'SELECT "Coluna slug já existe"'
);

PREPARE stmt FROM @addcol;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Atualizar os slugs baseados nos nomes existentes
UPDATE tipos_parceiros SET slug = 'produtores' WHERE nome = 'Produtor' OR nome = 'Produtores';
UPDATE tipos_parceiros SET slug = 'criadores' WHERE nome = 'Criador' OR nome = 'Criadores';
UPDATE tipos_parceiros SET slug = 'veterinarios' WHERE nome = 'Veterinário' OR nome = 'Veterinarios';
UPDATE tipos_parceiros SET slug = 'lojas-agropet' WHERE nome = 'Lojas Agropet' OR nome = 'Loja Agropecuária';
UPDATE tipos_parceiros SET slug = 'cooperativas' WHERE nome = 'Cooperativas' OR nome = 'Cooperativa';

-- Tornar a coluna slug NOT NULL após atualizar os valores
ALTER TABLE tipos_parceiros MODIFY COLUMN slug VARCHAR(100) NOT NULL;

-- Adicionar índice único para slug
ALTER TABLE tipos_parceiros ADD UNIQUE KEY unique_slug (slug);
