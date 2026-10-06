<?php
// Recebe o cadastro de PEÇA do estoque (tabela: peca).
require __DIR__ . '/funcoes.php';
exigirPost();

$erros = validarObrigatorios([
    'codigo' => 'Código', 'descricao' => 'Descrição', 'fabricante' => 'Fabricante',
    'preco_custo' => 'Preço de custo', 'margem' => 'Margem de lucro',
    'quantidade' => 'Quantidade em estoque', 'estoque_minimo' => 'Estoque mínimo',
]);

$codigo = strtoupper(campo('codigo'));
if ($codigo !== '' && !preg_match('/^[A-Z0-9-]{4,15}$/', $codigo)) $erros[] = 'Código deve ter de 4 a 15 letras, números ou hífen.';

$custo = numero(campo('preco_custo'));
if (campo('preco_custo') !== '' && ($custo === null || $custo <= 0)) $erros[] = 'Preço de custo deve ser maior que zero.';

$margem = numero(campo('margem'));
if (campo('margem') !== '' && ($margem === null || $margem < 0 || $margem > 300)) $erros[] = 'Margem deve estar entre 0% e 300%.';

$qtd = campo('quantidade');
$minimo = campo('estoque_minimo');
if ($qtd !== '' && !ctype_digit($qtd)) $erros[] = 'Quantidade deve ser um número inteiro.';
if ($minimo !== '' && !ctype_digit($minimo)) $erros[] = 'Estoque mínimo deve ser um número inteiro.';

if ($erros) responder(false, $erros);

// Lógica adicional: preço de venda pela margem e alerta de reposição.
$precoVenda = $custo * (1 + $margem / 100);
$situacao = (int) $qtd <= (int) $minimo
    ? 'ATENÇÃO: repor estoque (sugestão de compra: ' . ((int) $minimo * 2 - (int) $qtd) . ' un.)'
    : 'Estoque OK';

responder(true, [
    'Código' => $codigo,
    'Descrição' => campo('descricao'),
    'Fabricante' => campo('fabricante'),
    'Preço de custo' => moeda($custo),
    'Preço de venda' => moeda($precoVenda),
    'Lucro por unidade' => moeda($precoVenda - $custo),
    'Situação do estoque' => $situacao,
], 200, 'Peça validada com sucesso!');
