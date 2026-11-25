<?php
require __DIR__ . '/funcoes.php';
exigirPost();
exigirLogin();
$id = idEdicao();

$erros = validarObrigatorios([
    'nome' => 'Nome', 'cpf' => 'CPF', 'especialidade' => 'Especialidade',
    'valor_hora' => 'Valor da hora', 'data_admissao' => 'Data de admissão', 'telefone' => 'Telefone',
]);

$cpf = apenasDigitos(campo('cpf'));
if ($cpf !== '' && !cpfValido($cpf)) $erros[] = 'CPF inválido.';

$especialidades = ['Motor', 'Suspensão', 'Elétrica', 'Freios', 'Funilaria'];
if (campo('especialidade') !== '' && !in_array(campo('especialidade'), $especialidades)) $erros[] = 'Especialidade inválida.';

$valorHora = numero(campo('valor_hora'));
if (campo('valor_hora') !== '' && ($valorHora === null || $valorHora < 30 || $valorHora > 500)) $erros[] = 'Valor da hora deve ficar entre R$ 30,00 e R$ 500,00.';

$telefone = apenasDigitos(campo('telefone'));
if ($telefone !== '' && !in_array(strlen($telefone), [10, 11])) $erros[] = 'Telefone deve ter DDD + 8 ou 9 dígitos.';

$admissao = dataValida(campo('data_admissao'));
if (campo('data_admissao') !== '' && (!$admissao || $admissao > new DateTime('today'))) $erros[] = 'Data de admissão inválida ou no futuro.';

if ($erros) responder(false, $erros);

$id = salvar('mecanico', [
    'nome' => campo('nome'),
    'cpf' => $cpf,
    'telefone' => $telefone,
    'especialidade' => campo('especialidade'),
    'valor_hora' => $valorHora,
    'data_admissao' => campo('data_admissao'),
], $id, 'Já existe um mecânico cadastrado com este CPF.');

$anos = $admissao->diff(new DateTime('today'))->y;
$nivel = $anos < 2 ? 'Júnior' : ($anos < 5 ? 'Pleno' : 'Sênior');

responder(true, [
    'Código' => $id,
    'Nome' => campo('nome'),
    'CPF' => formatarCpf($cpf),
    'Especialidade' => campo('especialidade'),
    'Tempo de casa' => "$anos ano(s)",
    'Nível' => $nivel,
    'Valor hora' => moeda($valorHora),
    'Estimativa mensal (176 h)' => moeda($valorHora * 176),
], 200, idEdicao() ? 'Mecânico atualizado com sucesso!' : 'Mecânico cadastrado com sucesso!');
