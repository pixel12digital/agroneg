-- Script para adicionar campo whatsapp na tabela parceiros
-- Configurar conexão para UTF-8
SET NAMES utf8mb4;
SET character_set_client = utf8mb4;

-- Verificar se a coluna já existe
SET @colexists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = 'agroneg' 
    AND TABLE_NAME = 'parceiros' 
    AND COLUMN_NAME = 'whatsapp'
);

-- Adicionar a coluna apenas se ela não existir
SET @addcol = IF(@colexists = 0, 
    'ALTER TABLE parceiros ADD COLUMN whatsapp VARCHAR(20) NULL AFTER telefone',
    'SELECT "Coluna whatsapp já existe"'
);

PREPARE stmt FROM @addcol;
EXECUTE stmt;
DEALLOCATE PREPARE stmt; 