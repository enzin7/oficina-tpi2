<?php
// Funções auxiliares usadas por todos os scripts de cadastro.
header('Content-Type: application/json; charset=utf-8');

function exigirPost(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responder(false, ['Método não permitido. Use POST.'], 405);
    }
}

// Lê um campo do $_POST, removendo espaços extras.
function campo(string $nome): string
{
    return trim($_POST[$nome] ?? '');
}

// Verifica se os campos obrigatórios foram preenchidos e devolve a lista de erros.
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

// Converte "1.234,56" ou "1234.56" em número.
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

// Validação de CPF pelo cálculo dos dois dígitos verificadores.
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

// Placa no padrão antigo (ABC1234) ou Mercosul (ABC1D23).
function placaValida(string $placa): bool
{
    return (bool) preg_match('/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/', $placa);
}

function normalizarPlaca(string $placa): string
{
    return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $placa));
}

// Envia a resposta JSON para o JavaScript e encerra o script.
function responder(bool $sucesso, array $conteudo, int $status = 200, string $mensagem = ''): void
{
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
