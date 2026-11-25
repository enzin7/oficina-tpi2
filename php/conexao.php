<?php
const DB_HOST = 'localhost';
const DB_PORTA = '5432';
const DB_NOME = 'oficina';
const DB_USUARIO = 'postgres';
const DB_SENHA = 'postgres';

$GLOBALS['pdo'] = null;

function conexao(): PDO
{
    if ($GLOBALS['pdo'] === null) {
        try {
            $GLOBALS['pdo'] = new PDO(
                'pgsql:host=' . DB_HOST . ';port=' . DB_PORTA . ';dbname=' . DB_NOME,
                DB_USUARIO,
                DB_SENHA,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
            $GLOBALS['pdo']->exec("SET client_encoding TO 'UTF8'");
        } catch (PDOException $e) {
            responder(false, ['Não foi possível conectar ao banco de dados. Verifique o php/conexao.php.'], 500);
        }
    }
    return $GLOBALS['pdo'];
}

function conexaoAberta(): bool
{
    return $GLOBALS['pdo'] !== null;
}

function executar(string $sql, array $parametros = []): PDOStatement
{
    $stmt = conexao()->prepare($sql);
    $stmt->execute($parametros);
    return $stmt;
}

function buscar(string $sql, array $parametros = []): ?array
{
    return executar($sql, $parametros)->fetch() ?: null;
}

// INSERT quando $id é null, UPDATE caso contrário. Devolve o id do registro.
// $tabela e as chaves de $dados vêm sempre do código, nunca do usuário.
function salvar(string $tabela, array $dados, ?int $id, string $msgDuplicado): int
{
    $colunas = array_keys($dados);
    $valores = array_values($dados);

    if ($id === null) {
        $sql = "INSERT INTO $tabela (" . implode(', ', $colunas) . ') VALUES ('
            . implode(', ', array_fill(0, count($colunas), '?')) . ') RETURNING id';
    } else {
        $sql = "UPDATE $tabela SET " . implode(' = ?, ', $colunas) . ' = ? WHERE id = ? RETURNING id';
        $valores[] = $id;
    }

    try {
        $novoId = executar($sql, $valores)->fetchColumn();
    } catch (PDOException $e) {
        if ($e->getCode() === '23505') responder(false, [$msgDuplicado], 409);
        responder(false, ['Erro ao gravar no banco de dados.'], 500);
    }
    if ($novoId === false) responder(false, ['Registro não encontrado.'], 404);
    return (int) $novoId;
}
