<?php
// Recebe a abertura de ORDEM DE SERVIÇO (tabela: ordem_servico, ligada a
// cliente, veiculo, mecanico, servico e ordem_servico_peca).
require __DIR__ . '/funcoes.php';
exigirPost();

$erros = validarObrigatorios([
    'cpf_cliente' => 'CPF do cliente', 'placa' => 'Placa', 'mecanico' => 'Mecânico responsável',
    'servico' => 'Tipo de serviço', 'data_entrada' => 'Data de entrada', 'data_previsao' => 'Previsão de entrega',
    'horas' => 'Horas de mão de obra', 'valor_pecas' => 'Valor das peças',
]);

if (campo('cpf_cliente') !== '' && !cpfValido(campo('cpf_cliente'))) $erros[] = 'CPF do cliente inválido.';

$placa = normalizarPlaca(campo('placa'));
if ($placa !== '' && !placaValida($placa)) $erros[] = 'Placa inválida.';

// Tabela "servico": tipo => valor da hora de mão de obra.
$servicos = [
    'revisao' => ['Revisão preventiva', 120.00],
    'freios' => ['Troca de freios', 140.00],
    'suspensao' => ['Suspensão', 150.00],
    'eletrica' => ['Elétrica', 160.00],
    'motor' => ['Retífica de motor', 200.00],
];
$servico = campo('servico');
if ($servico !== '' && !isset($servicos[$servico])) $erros[] = 'Tipo de serviço inválido.';

$entrada = dataValida(campo('data_entrada'));
$previsao = dataValida(campo('data_previsao'));
if (campo('data_entrada') !== '' && !$entrada) $erros[] = 'Data de entrada inválida.';
if (campo('data_previsao') !== '' && !$previsao) $erros[] = 'Previsão de entrega inválida.';
if ($entrada && $previsao && $previsao < $entrada) $erros[] = 'A previsão de entrega não pode ser anterior à entrada.';

$horas = numero(campo('horas'));
if (campo('horas') !== '' && ($horas === null || $horas <= 0 || $horas > 200)) $erros[] = 'Horas de mão de obra devem ficar entre 0 e 200.';

$valorPecas = numero(campo('valor_pecas'));
if (campo('valor_pecas') !== '' && ($valorPecas === null || $valorPecas < 0)) $erros[] = 'Valor das peças não pode ser negativo.';

$desconto = numero(campo('desconto') ?: '0');
if ($desconto === null || $desconto < 0 || $desconto > 15) $erros[] = 'Desconto deve estar entre 0% e 15%.';

if ($erros) responder(false, $erros);

// Lógica adicional: orçamento total com desconto e prazo em dias.
[$nomeServico, $valorHora] = $servicos[$servico];
$maoDeObra = $horas * $valorHora;
$subtotal = $maoDeObra + $valorPecas;
$total = $subtotal * (1 - $desconto / 100);
$prazo = $entrada->diff($previsao)->days;

responder(true, [
    'Cliente (CPF)' => formatarCpf(campo('cpf_cliente')),
    'Veículo' => $placa,
    'Mecânico' => campo('mecanico'),
    'Serviço' => $nomeServico,
    'Mão de obra' => number_format($horas, 1, ',', '') . ' h x ' . moeda($valorHora) . ' = ' . moeda($maoDeObra),
    'Peças' => moeda($valorPecas),
    'Desconto' => number_format($desconto, 1, ',', '') . '%',
    'Total do orçamento' => moeda($total),
    'Prazo' => "$prazo dia(s)",
], 200, 'Ordem de serviço aberta com sucesso!');
