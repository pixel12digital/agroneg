-- Script para adicionar descrições às tags

-- Configurar conexão para UTF-8
SET NAMES utf8mb4;
SET character_set_client = utf8mb4;

-- Atualizar descrições das tags
UPDATE tags SET descricao = 'Produtos cultivados sem uso de agrotóxicos ou adubos químicos' WHERE nome LIKE '%Orgânicos%' OR slug = 'organicos';

UPDATE tags SET descricao = 'Produção realizada por pequenos agricultores familiares' WHERE nome LIKE '%Familiar%' OR slug = 'familiar';

UPDATE tags SET descricao = 'Produção com práticas de sustentabilidade ambiental' WHERE nome LIKE '%Sustentável%' OR slug = 'sustentavel';

UPDATE tags SET descricao = 'Produtos destinados ao mercado internacional' WHERE nome LIKE '%Exportação%' OR slug = 'exportacao';

UPDATE tags SET descricao = 'Produção baseada em princípios ecológicos e orgânicos' WHERE nome LIKE '%Agroecologia%' OR slug = 'agroecologia';

UPDATE tags SET descricao = 'Sistemas de irrigação para cultivos' WHERE nome LIKE '%Irrigação%' OR slug = 'irrigacao';

UPDATE tags SET descricao = 'Uso de tecnologias avançadas na produção' WHERE nome LIKE '%Tecnologia%' OR slug = 'tecnologia';

UPDATE tags SET descricao = 'Produtos com certificação de qualidade ou origem' WHERE nome LIKE '%Certificado%' OR slug = 'certificado';

UPDATE tags SET descricao = 'Serviços de assistência e consultoria técnica agrícola' WHERE nome LIKE '%Assistência%' OR slug = 'assistencia-tecnica';

UPDATE tags SET descricao = 'Serviço de entrega rápida de produtos' WHERE nome LIKE '%Entrega%' OR slug = 'entrega-rapida';

UPDATE tags SET descricao = 'Venda em grandes quantidades para comerciantes' WHERE nome LIKE '%Atacado%' OR slug = 'atacado';

UPDATE tags SET descricao = 'Venda direta ao consumidor final' WHERE nome LIKE '%Varejo%' OR slug = 'varejo';

UPDATE tags SET descricao = 'Criação de bovinos para produção de carne' WHERE nome LIKE '%Gado de Corte%' OR slug = 'gado-de-corte';

UPDATE tags SET descricao = 'Criação de bovinos para produção de leite' WHERE nome LIKE '%Gado Leiteiro%' OR slug = 'gado-leiteiro';

UPDATE tags SET descricao = 'Criação de aves como frangos, galinhas e outras espécies' WHERE nome LIKE '%Aves%' OR slug = 'aves';

UPDATE tags SET descricao = 'Produção de milho, soja, trigo, arroz e outros grãos' WHERE nome LIKE '%Grãos%' OR slug = 'graos';

UPDATE tags SET descricao = 'Cultivo de verduras, legumes e outros vegetais' WHERE nome LIKE '%Hortaliças%' OR slug = 'hortalicas';

UPDATE tags SET descricao = 'Cultivo de frutas diversas' WHERE nome LIKE '%Fruticultura%' OR slug = 'fruticultura'; 