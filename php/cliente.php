<?php
// Recebe o cadastro de CLIENTE (tabela: cliente).
require __DIR__ . '/funcoes.php';
exigirPost();

$erros = validarObrigatorios([
    'nome' => 'Nome completo', 'cpf' => 'CPF', 'telefone' => 'Telefone',
    'email' => 'E-mail', 'data_nascimento' => 'Data de nascimento', 'cidade' => 'Cidade',
]);

$cpf = campo('cpf');
if ($cpf !== '' && !cpfValido($cpf)) $erros[] = 'CPF inválido (dígitos verificadores não conferem).';

$email = campo('email');
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail em formato inválido.';

$telefone = apenasDigitos(campo('telefone'));
if ($telefone !== '' && !in_array(strlen($telefone), [10, 11])) $erros[] = 'Telefone deve ter DDD + 8 ou 9 dígitos.';

$nascimento = dataValida(campo('data_nascimento'));
$idade = null;
if (campo('data_nascimento') !== '' && !$nascimento) {
    $erros[] = 'Data de nascimento inválida.';
} elseif ($nascimento) {
    // Lógica adicional: cálculo da idade e bloqueio de menores de 18 anos.
    $idade = $nascimento->diff(new DateTime('today'))->y;
    if ($idade < 18) $erros[] = 'O cliente precisa ter 18 anos ou mais para abrir ordens de serviço.';
}

if ($erros) responder(false, $erros);

responder(true, [
    'Nome' => mb_convert_case(campo('nome'), MB_CASE_TITLE, 'UTF-8'),
    'CPF' => formatarCpf($cpf),
    'Idade' => "$idade anos",
    'Telefone' => $telefone,
    'E-mail' => strtolower($email),
    'Cidade' => campo('cidade'),
    'Observação' => campo('cidade') === 'Uberlândia' ? 'Cliente local: elegível para leva-e-traz grátis.' : 'Cliente de outra cidade.',
], 200, 'Cliente validado com sucesso!');
