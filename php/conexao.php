<?php
// Conexão com o PostgreSQL via PDO. Ajuste os dados abaixo conforme o seu ambiente
// (na instalação padrão o usuário é "postgres" e a senha é a definida no instalador).
const DB_HOST = 'localhost';
const DB_PORTA = '5432';
const DB_NOME = 'oficina';
const DB_USUARIO = 'postgres';
const DB_SENHA = 'postgres';

function conexao(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                'pgsql:host=' . DB_HOST . ';port=' . DB_PORTA . ';dbname=' . DB_NOME,
                DB_USUARIO,
                DB_SENHA,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            $pdo->exec("SET client_encoding TO 'UTF8'");
        } catch (PDOException $e) {
            responder(false, ['Não foi possível conectar ao banco de dados. Verifique o php/conexao.php.'], 500);
        }
    }
    return $pdo;
}

// Executa um INSERT ... RETURNING id e devolve o id gerado. Trata chave duplicada (UNIQUE).
function inserir(string $sql, array $parametros, string $msgDuplicado): int
{
    try {
        $stmt = conexao()->prepare($sql . ' RETURNING id');
        $stmt->execute($parametros);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        // 23505 = unique_violation no PostgreSQL
        if ($e->getCode() === '23505') responder(false, [$msgDuplicado], 409);
        responder(false, ['Erro ao gravar no banco de dados.'], 500);
    }
}

// Busca uma única linha (ou null).
function buscar(string $sql, array $parametros = []): ?array
{
    $stmt = conexao()->prepare($sql);
    $stmt->execute($parametros);
    return $stmt->fetch() ?: null;
}
