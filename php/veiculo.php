<?php
// Recebe o cadastro de VEÍCULO (tabela: veiculo — pertence a um cliente).
require __DIR__ . '/funcoes.php';
exigirPost();

$erros = validarObrigatorios([
    'cpf_cliente' => 'CPF do proprietário', 'placa' => 'Placa', 'marca' => 'Marca',
    'modelo' => 'Modelo', 'ano' => 'Ano de fabricação', 'quilometragem' => 'Quilometragem',
]);

$cpf = campo('cpf_cliente');
if ($cpf !== '' && !cpfValido($cpf)) $erros[] = 'CPF do proprietário inválido.';

$placa = normalizarPlaca(campo('placa'));
if ($placa !== '' && !placaValida($placa)) $erros[] = 'Placa inválida. Use ABC1234 ou ABC1D23 (Mercosul).';

$anoAtual = (int) date('Y');
$ano = (int) campo('ano');
if (campo('ano') !== '' && ($ano < 1950 || $ano > $anoAtual + 1)) $erros[] = "Ano deve estar entre 1950 e " . ($anoAtual + 1) . '.';

$km = numero(campo('quilometragem'));
if (campo('quilometragem') !== '' && ($km === null || $km < 0)) $erros[] = 'Quilometragem deve ser um número positivo.';

if ($erros) responder(false, $erros);

// Lógica adicional: média de km/ano e sugestão de revisão a cada 10.000 km.
$idade = max(1, $anoAtual - $ano);
$proximaRevisao = (floor($km / 10000) + 1) * 10000;

responder(true, [
    'Placa' => $placa,
    'Padrão da placa' => ctype_alpha($placa[4]) ? 'Mercosul' : 'Antigo',
    'Veículo' => campo('marca') . ' ' . campo('modelo') . " ($ano)",
    'Combustível' => campo('combustivel') ?: 'Não informado',
    'Média de uso' => number_format($km / $idade, 0, ',', '.') . ' km/ano',
    'Próxima revisão' => number_format($proximaRevisao, 0, ',', '.') . ' km',
], 200, 'Veículo validado com sucesso!');
