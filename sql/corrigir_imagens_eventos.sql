-- Correção de imagens de eventos inexistentes
-- Remove referências a arquivos de imagem que não existem fisicamente

UPDATE eventos_municipio 
SET imagem = '' 
WHERE id = 3 AND imagem = 'uploads/eventos/evento_689f4c4bb0a0e.jpeg';

UPDATE eventos_municipio 
SET imagem = '' 
WHERE id = 5 AND imagem = 'uploads/eventos/evento_689f4de3c2a80.jpeg';

-- Verificar resultado
SELECT id, nome, imagem FROM eventos_municipio ORDER BY id;
