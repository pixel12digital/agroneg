-- Script para adicionar tabela de mensagens de contato
-- Configurar conexão para UTF-8
SET NAMES utf8mb4;
SET character_set_client = utf8mb4;

-- Criar tabela de mensagens de contato
CREATE TABLE IF NOT EXISTS mensagens_contato (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    telefone VARCHAR(20),
    assunto VARCHAR(200) NOT NULL,
    mensagem TEXT NOT NULL,
    status ENUM('novo', 'lido', 'respondido', 'arquivado') DEFAULT 'novo',
    data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    observacoes TEXT,
    respondido_por INT,
    FOREIGN KEY (respondido_por) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 