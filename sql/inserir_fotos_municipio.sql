-- Script para inserir fotos de teste para o município Santa Cruz do Capibaribe (ID 2)
-- Execute este script no banco de dados para testar a galeria

-- Inserir fotos para o município ID 2 (Santa Cruz do Capibaribe)
INSERT INTO fotos (entidade_tipo, entidade_id, arquivo, legenda, ordem) VALUES
('municipio', 2, '1750185237_WhatsApp_Image_2025-06-17_at_15.21.26.jpeg', 'Santa Cruz do Capibaribe - Agricultura Familiar', 1),
('municipio', 2, '1750185237_WhatsApp_Image_2025-06-17_at_15.20.02__1_.jpeg', 'Santa Cruz do Capibaribe - Campo de Cultivo', 2),
('municipio', 2, '1749218833_ChatGPT_Image_21_05_2025__08_59_05.png', 'Santa Cruz do Capibaribe - Paisagem Rural', 3),
('municipio', 2, '1750182821_WhatsApp_Image_2025-06-17_at_14.26.04.jpeg', 'Santa Cruz do Capibaribe - Trabalho no Campo', 4),
('municipio', 2, '1750182821_WhatsApp_Image_2025-06-17_at_14.26.05.jpeg', 'Santa Cruz do Capibaribe - Produtores Locais', 5);

-- Verificar se as fotos foram inseridas
SELECT * FROM fotos WHERE entidade_tipo = 'municipio' AND entidade_id = 2 ORDER BY ordem;
