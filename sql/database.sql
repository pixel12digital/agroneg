-- Estrutura do banco de dados para o Agroneg
-- Criação das tabelas principais

-- Tabela de usuários para autenticação
CREATE TABLE usuarios (
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

-- Inserir usuário admin padrão (senha: admin123)
INSERT INTO usuarios (nome, usuario, email, senha, nivel) 
VALUES ('Administrador', 'admin', 'admin@agroneg.com', 'admin123', 'admin');

-- Tabela de estados
CREATE TABLE estados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    sigla CHAR(2) NOT NULL UNIQUE
);

-- Inserir estados brasileiros
INSERT INTO estados (nome, sigla) VALUES
('São Paulo', 'SP'),
('Minas Gerais', 'MG'),
('Rio de Janeiro', 'RJ'),
('Espírito Santo', 'ES'),
('Bahia', 'BA'),
('Sergipe', 'SE'),
('Alagoas', 'AL'),
('Pernambuco', 'PE'),
('Paraíba', 'PB'),
('Rio Grande do Norte', 'RN'),
('Ceará', 'CE'),
('Piauí', 'PI'),
('Maranhão', 'MA'),
('Pará', 'PA'),
('Amapá', 'AP'),
('Amazonas', 'AM'),
('Roraima', 'RR'),
('Acre', 'AC'),
('Rondônia', 'RO'),
('Tocantins', 'TO'),
('Goiás', 'GO'),
('Distrito Federal', 'DF'),
('Mato Grosso', 'MT'),
('Mato Grosso do Sul', 'MS'),
('Paraná', 'PR'),
('Santa Catarina', 'SC'),
('Rio Grande do Sul', 'RS');

-- Tabela de municípios
CREATE TABLE municipios (
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

-- Tabela de categorias de parceiros
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    descricao TEXT,
    icone VARCHAR(50)
);

-- Inserir categorias padrão
INSERT INTO categorias (nome, slug, descricao) VALUES
('Produtores', 'produtores', 'Produtores rurais e agricultores'),
('Criadores', 'criadores', 'Criadores de animais'),
('Veterinários', 'veterinarios', 'Profissionais e clínicas veterinárias'),
('Lojas Agropet', 'lojas', 'Lojas de produtos agropecuários e pet'),
('Cooperativas', 'cooperativas', 'Cooperativas agrícolas'),
('Eventos', 'eventos', 'Feiras, exposições e eventos agrícolas');

-- Tabela de parceiros
CREATE TABLE parceiros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    municipio_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    tipo VARCHAR(100),
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
    FOREIGN KEY (municipio_id) REFERENCES municipios(id)
);

-- Tabela de fotos (para parceiros e municípios)
CREATE TABLE fotos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entidade_tipo ENUM('parceiro', 'municipio') NOT NULL,
    entidade_id INT NOT NULL,
    arquivo VARCHAR(255) NOT NULL,
    legenda VARCHAR(255),
    ordem INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de tags (para filtragem)
CREATE TABLE tags (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    nome VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

-- Inserir algumas tags padrão para cada categoria
INSERT INTO tags (categoria_id, nome, slug) VALUES
(1, 'Orgânicos', 'organicos'),
(1, 'Grãos', 'graos'),
(1, 'Frutas', 'frutas'),
(1, 'Hortaliças', 'hortalicas'),
(1, 'Café', 'cafe'),
(2, 'Bovinos', 'bovinos'),
(2, 'Aves', 'aves'),
(2, 'Suínos', 'suinos'),
(2, 'Equinos', 'equinos'),
(2, 'Ovinos', 'ovinos'),
(3, 'Pequenos Animais', 'pequenos'),
(3, 'Grandes Animais', 'grandes'),
(3, 'Aves', 'aves'),
(3, 'Animais Silvestres', 'silvestres'),
(3, 'Equinos', 'equinos'),
(4, 'Insumos Agrícolas', 'insumos'),
(4, 'Máquinas e Equipamentos', 'maquinas'),
(4, 'Rações e Suplementos', 'racoes'),
(4, 'Produtos Veterinários', 'veterinaria'),
(4, 'Pet Shop', 'petshop'),
(5, 'Agrícola', 'agricola'),
(5, 'Leite e Derivados', 'leite'),
(5, 'Agricultura Familiar', 'familiar'),
(5, 'Grãos', 'graos'),
(5, 'Fruticultura', 'fruticultura'),
(6, 'Feiras', 'feira'),
(6, 'Exposições', 'exposicao'),
(6, 'Congressos', 'congresso'),
(6, 'Workshops', 'workshop'),
(6, 'Leilões', 'leilao');

-- Tabela de relacionamento entre parceiros e tags
CREATE TABLE parceiros_tags (
    parceiro_id INT NOT NULL,
    tag_id INT NOT NULL,
    PRIMARY KEY (parceiro_id, tag_id),
    FOREIGN KEY (parceiro_id) REFERENCES parceiros(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
);

-- Tabela para eventos municipais
CREATE TABLE eventos_municipio (
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
CREATE TABLE avaliacoes (
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
CREATE TABLE configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(50) NOT NULL UNIQUE,
    valor TEXT,
    descricao VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Inserir algumas configurações básicas
INSERT INTO configuracoes (chave, valor, descricao) VALUES
('site_titulo', 'AgroNeg - Parceiros do Agronegócio', 'Título do site'),
('site_descricao', 'Encontre parceiros do agronegócio em todo o Brasil', 'Descrição do site'),
('email_contato', 'contato@agroneg.com.br', 'Email de contato'),
('telefone_contato', '(11) 99999-9999', 'Telefone de contato'),
('facebook', 'https://facebook.com/agroneg', 'URL do Facebook'),
('instagram', 'https://instagram.com/agroneg', 'URL do Instagram'),
('twitter', 'https://twitter.com/agroneg', 'URL do Twitter'); 