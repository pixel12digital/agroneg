-- Criação do banco de dados (execute apenas se ainda não existir)
-- CREATE DATABASE IF NOT EXISTS agroneg DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE agroneg;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nivel ENUM('admin', 'editor') DEFAULT 'editor',
    ativo TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT IGNORE INTO usuarios (nome, usuario, email, senha, nivel) 
VALUES ('Administrador', 'admin', 'admin@agroneg.com', 'admin123', 'admin');

-- Tabela de estados
CREATE TABLE IF NOT EXISTS estados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    sigla CHAR(2) NOT NULL UNIQUE
);

-- Tabela de municípios
CREATE TABLE IF NOT EXISTS municipios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    estado_id INT,
    nome VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    populacao VARCHAR(50),
    area_rural VARCHAR(50),
    principais_culturas TEXT,
    website VARCHAR(255),
    facebook VARCHAR(255),
    instagram VARCHAR(255),
    twitter VARCHAR(255),
    imagem_principal VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (estado_id) REFERENCES estados(id)
);

-- Tabela de categorias
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    descricao TEXT,
    icone VARCHAR(50)
);

-- Tabela tipos_parceiros
CREATE TABLE IF NOT EXISTS tipos_parceiros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO tipos_parceiros (nome, descricao) VALUES 
('Criador', 'Criadores de animais como gado, aves, suínos, etc.'),
('Produtor', 'Agricultores e produtores rurais do setor primário'),
('Veterinário', 'Profissionais e clínicas de saúde animal'),
('Lojas Agropet', 'Estabelecimentos de venda de produtos para o agronegócio e animais'),
('Cooperativas', 'Associações de produtores e cooperativas do setor');

-- Tabela de parceiros
CREATE TABLE IF NOT EXISTS parceiros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    municipio_id INT NOT NULL,
    tipo_id INT NULL,
    nome VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    descricao TEXT,
    endereco TEXT,
    telefone VARCHAR(50),
    email VARCHAR(100),
    website VARCHAR(255),
    facebook VARCHAR(255),
    instagram VARCHAR(255),
    twitter VARCHAR(255),
    imagem_destaque VARCHAR(255),
    status TINYINT(1) DEFAULT 1,
    destaque TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id),
    FOREIGN KEY (municipio_id) REFERENCES municipios(id),
    FOREIGN KEY (tipo_id) REFERENCES tipos_parceiros(id)
);

-- Tabela de fotos
CREATE TABLE IF NOT EXISTS fotos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entidade_tipo ENUM('parceiro', 'municipio') NOT NULL,
    entidade_id INT NOT NULL,
    arquivo VARCHAR(255) NOT NULL,
    legenda VARCHAR(255),
    ordem INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de tags
CREATE TABLE IF NOT EXISTS tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    nome VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

-- Tabela de relacionamento entre parceiros e tags
CREATE TABLE IF NOT EXISTS parceiros_tags (
    parceiro_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (parceiro_id, tag_id),
    FOREIGN KEY (parceiro_id) REFERENCES parceiros(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- Tabela para eventos municipais
CREATE TABLE IF NOT EXISTS eventos_municipio (
    id INT AUTO_INCREMENT PRIMARY KEY,
    municipio_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE,
    descricao TEXT,
    local VARCHAR(255),
    imagem VARCHAR(255),
    status TINYINT(1) DEFAULT 1,
    destaque TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (municipio_id) REFERENCES municipios(id) ON DELETE CASCADE,
    INDEX (data_inicio),
    INDEX (status)
);

-- Tabela para avaliações de parceiros
CREATE TABLE IF NOT EXISTS avaliacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    parceiro_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    nota INT NOT NULL CHECK (nota BETWEEN 1 AND 5),
    comentario TEXT,
    aprovado TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parceiro_id) REFERENCES parceiros(id) ON DELETE CASCADE,
    INDEX (aprovado),
    INDEX (parceiro_id, aprovado)
);

-- Tabela de configurações do site
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(50) NOT NULL UNIQUE,
    valor TEXT,
    descricao VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
); 