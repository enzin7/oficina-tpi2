-- Banco de dados da Oficina Torque Certo (PostgreSQL).
-- 1) Crie o banco (uma vez):   CREATE DATABASE oficina ENCODING 'UTF8';
-- 2) Rode este arquivo nele:   psql -U postgres -d oficina -f sql/banco.sql
--    (ou no pgAdmin: botão direito no banco "oficina" > Query Tool > abrir arquivo > executar)

SET client_encoding = 'UTF8';

-- Usuários que podem acessar o sistema (login).
CREATE TABLE IF NOT EXISTS usuario (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    login VARCHAR(50) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cliente (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf CHAR(11) NOT NULL UNIQUE,
    data_nascimento DATE NOT NULL,
    telefone VARCHAR(11) NOT NULL,
    email VARCHAR(120) NOT NULL,
    cidade VARCHAR(80) NOT NULL,
    origem VARCHAR(20),
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS veiculo (
    id SERIAL PRIMARY KEY,
    cliente_id INT NOT NULL REFERENCES cliente(id),
    placa CHAR(7) NOT NULL UNIQUE,
    marca VARCHAR(50) NOT NULL,
    modelo VARCHAR(50) NOT NULL,
    ano SMALLINT NOT NULL,
    quilometragem INT NOT NULL CHECK (quilometragem >= 0),
    combustivel VARCHAR(20),
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS mecanico (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cpf CHAR(11) NOT NULL UNIQUE,
    telefone VARCHAR(11) NOT NULL,
    especialidade VARCHAR(30) NOT NULL,
    valor_hora NUMERIC(10,2) NOT NULL,
    data_admissao DATE NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS peca (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(15) NOT NULL UNIQUE,
    descricao VARCHAR(150) NOT NULL,
    fabricante VARCHAR(60) NOT NULL,
    preco_custo NUMERIC(10,2) NOT NULL,
    margem NUMERIC(6,2) NOT NULL,
    quantidade INT NOT NULL CHECK (quantidade >= 0),
    estoque_minimo INT NOT NULL CHECK (estoque_minimo >= 0),
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS servico (
    id SERIAL PRIMARY KEY,
    codigo VARCHAR(20) NOT NULL UNIQUE,
    nome VARCHAR(80) NOT NULL,
    valor_hora NUMERIC(10,2) NOT NULL
);

CREATE TABLE IF NOT EXISTS ordem_servico (
    id SERIAL PRIMARY KEY,
    cliente_id INT NOT NULL REFERENCES cliente(id),
    veiculo_id INT NOT NULL REFERENCES veiculo(id),
    mecanico_id INT NOT NULL REFERENCES mecanico(id),
    servico_id INT NOT NULL REFERENCES servico(id),
    usuario_id INT NOT NULL REFERENCES usuario(id),
    data_entrada DATE NOT NULL,
    data_previsao DATE NOT NULL,
    horas NUMERIC(6,1) NOT NULL,
    valor_pecas NUMERIC(10,2) NOT NULL,
    desconto NUMERIC(4,1) NOT NULL DEFAULT 0,
    total NUMERIC(10,2) NOT NULL,
    relato TEXT,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (data_previsao >= data_entrada)
);

CREATE TABLE IF NOT EXISTS ordem_servico_peca (
    ordem_servico_id INT NOT NULL REFERENCES ordem_servico(id),
    peca_id INT NOT NULL REFERENCES peca(id),
    quantidade INT NOT NULL,
    preco_unitario NUMERIC(10,2) NOT NULL,
    PRIMARY KEY (ordem_servico_id, peca_id)
);

-- Tabela de serviços (antes ficava fixa no ordem.php).
INSERT INTO servico (codigo, nome, valor_hora) VALUES
    ('revisao', 'Revisão preventiva', 120.00),
    ('freios', 'Troca de freios', 140.00),
    ('suspensao', 'Suspensão', 150.00),
    ('eletrica', 'Elétrica', 160.00),
    ('motor', 'Retífica de motor', 200.00)
ON CONFLICT (codigo) DO NOTHING;

-- Usuário inicial: login "admin", senha "admin123" (hash gerado com password_hash).
INSERT INTO usuario (nome, login, senha_hash) VALUES
    ('Administrador', 'admin', '$2y$10$2wXM2R5ZP6wSYn6BfV/KKO4dteiHMgitKcA6ESKueJez8Zto9fBd6')
ON CONFLICT (login) DO NOTHING;
