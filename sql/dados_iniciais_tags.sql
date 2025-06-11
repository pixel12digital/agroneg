-- Script para inserir dados iniciais na tabela de tags

-- Configurar conexão para UTF-8
SET NAMES utf8mb4;
SET character_set_client = utf8mb4;

-- Limpar dados existentes (se necessário)
-- TRUNCATE TABLE tags;

-- Inserir tags
INSERT INTO tags (nome, slug) VALUES 
('Orgânicos', 'organicos'),
('Familiar', 'familiar'),
('Sustentável', 'sustentavel'),
('Exportação', 'exportacao'),
('Agroecologia', 'agroecologia'),
('Irrigação', 'irrigacao'),
('Tecnologia', 'tecnologia'),
('Certificado', 'certificado'),
('Assistência Técnica', 'assistencia-tecnica'),
('Entrega Rápida', 'entrega-rapida'),
('Atacado', 'atacado'),
('Varejo', 'varejo'),
('Gado de Corte', 'gado-de-corte'),
('Gado Leiteiro', 'gado-leiteiro'),
('Aves', 'aves'),
('Grãos', 'graos'),
('Hortaliças', 'hortalicas'),
('Fruticultura', 'fruticultura'); 