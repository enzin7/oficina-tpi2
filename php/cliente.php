<?php
require __DIR__ . '/funcoes.php';
exigirPost();
exigirLogin();
$id = idEdicao();

$erros = validarObrigatorios([
    'nome' => 'Nome completo', 'cpf' => 'CPF', 'telefone' => 'Telefone',
    'email' => 'E-mail', 'data_nascimento' => 'Data de nascimento', 'cidade' => 'Cidade',
]);

$cpf = apenasDigitos(campo('cpf'));
if ($cpf !== '' && !cpfValido($cpf)) $erros[] = 'CPF inválido (dígitos verificadores não conferem).';

$email = strtolower(campo('email'));
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $erros[] = 'E-mail em formato inválido.';

$telefone = apenasDigitos(campo('telefone'));
if ($telefone !== '' && !in_array(strlen($telefone), [10, 11])) $erros[] = 'Telefone deve ter DDD + 8 ou 9 dígitos.';

$nascimento = dataValida(campo('data_nascimento'));
$idade = null;
if (campo('data_nascimento') !== '' && !$nascimento) {
    $erros[] = 'Data de nascimento inválida.';
} elseif ($nascimento) {
    $idade = $nascimento->diff(new DateTime('today'))->y;
    if ($idade < 18) $erros[] = 'O cliente precisa ter 18 anos ou mais para abrir ordens de serviço.';
}

if ($erros) responder(false, $erros);

$nome = mb_convert_case(campo('nome'), MB_CASE_TITLE, 'UTF-8');
$id = salvar('cliente', [
    'nome' => $nome,
    'cpf' => $cpf,
    'data_nascimento' => campo('data_nascimento'),
    'telefone' => $telefone,
    'email' => $email,
    'cidade' => campo('cidade'),
    'origem' => campo('origem') ?: null,
], $id, 'Já existe um cliente cadastrado com este CPF.');

responder(true, [
    'Código' => $id,
    'Nome' => $nome,
    'CPF' => formatarCpf($cpf),
    'Idade' => "$idade anos",
    'Telefone' => $telefone,
    'E-mail' => $email,
    'Cidade' => campo('cidade'),
    'Observação' => campo('cidade') === 'Uberlândia' ? 'Cliente local: elegível para leva-e-traz grátis.' : 'Cliente de outra cidade.',
], 200, idEdicao() ? 'Cliente atualizado com sucesso!' : 'Cliente cadastrado com sucesso!');
