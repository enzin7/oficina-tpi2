<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/conexao.php';

function iniciarSessao(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
        session_name('OFICINASESSID');
        session_start();
    }
}

// Encerra com 401 se não houver login ou se passou 30 min sem uso.
function exigirLogin(): array
{
    iniciarSessao();
    if (empty($_SESSION['usuario'])) {
        responder(false, ['Sessão expirada ou não iniciada. Faça login.'], 401);
    }
    if (time() - ($_SESSION['ultimo_acesso'] ?? 0) > 30 * 60) {
        session_unset();
        session_destroy();
        responder(false, ['Sessão expirada por inatividade. Faça login novamente.'], 401);
    }
    $_SESSION['ultimo_acesso'] = time();
    return $_SESSION['usuario'];
}

function exigirPost(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responder(false, ['Método não permitido. Use POST.'], 405);
    }
}

function campo(string $nome): string
{
    $valor = $_POST[$nome] ?? '';
    return is_string($valor) ? trim($valor) : '';
}

// Id enviado pelo formulário quando é edição (vazio = novo cadastro).
function idEdicao(): ?int
{
    $id = campo('id');
    return ctype_digit($id) ? (int) $id : null;
}

function validarObrigatorios(array $campos): array
{
    $erros = [];
    foreach ($campos as $nome => $rotulo) {
        if (campo($nome) === '') {
            $erros[] = "O campo \"$rotulo\" é obrigatório.";
        }
    }
    return $erros;
}

function apenasDigitos(string $valor): string
{
    return preg_replace('/\D/', '', $valor);
}

// Aceita "1.234,56" ou "1234.56".
function numero(string $valor): ?float
{
    if ($valor === '') return null;
    $valor = str_contains($valor, ',') ? str_replace(['.', ','], ['', '.'], $valor) : $valor;
    return is_numeric($valor) ? (float) $valor : null;
}

function moeda(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function dataValida(string $data): ?DateTime
{
    $d = DateTime::createFromFormat('!Y-m-d', $data);
    return ($d && $d->format('Y-m-d') === $data) ? $d : null;
}

function cpfValido(string $cpf): bool
{
    $cpf = apenasDigitos($cpf);
    if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) return false;
    for ($t = 9; $t < 11; $t++) {
        $soma = 0;
        for ($i = 0; $i < $t; $i++) {
            $soma += (int) $cpf[$i] * (($t + 1) - $i);
        }
        $digito = ((10 * $soma) % 11) % 10;
        if ((int) $cpf[$t] !== $digito) return false;
    }
    return true;
}

function formatarCpf(string $cpf): string
{
    $c = apenasDigitos($cpf);
    return substr($c, 0, 3) . '.' . substr($c, 3, 3) . '.' . substr($c, 6, 3) . '-' . substr($c, 9, 2);
}

// Padrão antigo (ABC1234) ou Mercosul (ABC1D23).
function placaValida(string $placa): bool
{
    return (bool) preg_match('/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/', $placa);
}

function normalizarPlaca(string $placa): string
{
    return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $placa));
}

// Devolve ao estoque as peças de uma OS e remove os itens dela.
function devolverPecasDaOrdem(int $ordemId): void
{
    executar(
        'UPDATE peca p SET quantidade = p.quantidade + i.quantidade
         FROM ordem_servico_peca i WHERE i.peca_id = p.id AND i.ordem_servico_id = ?',
        [$ordemId]
    );
    executar('DELETE FROM ordem_servico_peca WHERE ordem_servico_id = ?', [$ordemId]);
}

function responder(bool $sucesso, array $conteudo, int $status = 200, string $mensagem = ''): void
{
    if (conexaoAberta() && conexao()->inTransaction()) conexao()->rollBack();
    http_response_code($sucesso ? 200 : ($status === 200 ? 422 : $status));
    $saida = ['sucesso' => $sucesso];
    if ($sucesso) {
        $saida['mensagem'] = $mensagem;
        $saida['dados'] = $conteudo;
    } else {
        $saida['erros'] = $conteudo;
    }
    echo json_encode($saida, JSON_UNESCAPED_UNICODE);
    exit;
}
