-- Script para adicionar campo de imagem de destaque na tabela parceiros
-- Configurar conexão para UTF-8
SET NAMES utf8mb4;
SET character_set_client = utf8mb4;

-- Verificar se a coluna já existe
SET @colexists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'agroneg' 
    AND TABLE_NAME = 'parceiros' 
    AND COLUMN_NAME = 'imagem_destaque'
);

-- Adicionar a coluna apenas se ela não existir
SET @addcol = IF(@colexists = 0, 
    'ALTER TABLE parceiros ADD COLUMN imagem_destaque VARCHAR(255) NULL AFTER twitter',
    'SELECT "Coluna imagem_destaque já existe"'
);

PREPARE stmt FROM @addcol;
EXECUTE stmt;
DEALLOCATE PREPARE stmt; 