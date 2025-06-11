-- Script para criar a tabela de relacionamento entre eventos e tags
-- Configurar conexão para UTF-8
SET NAMES utf8mb4;
SET character_set_client = utf8mb4;

-- Criar tabela de relacionamento
CREATE TABLE IF NOT EXISTS eventos_tags (
    evento_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (evento_id, tag_id),
    FOREIGN KEY (evento_id) REFERENCES eventos_municipio(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Adicionar índices para melhor performance
CREATE INDEX idx_evento_tag_evento ON eventos_tags(evento_id);
CREATE INDEX idx_evento_tag_tag ON eventos_tags(tag_id); 