<?php
require __DIR__ . '/funcoes.php';
exigirPost();
exigirLogin();

// tipo => [tabela, mensagem quando há registros vinculados]
$tipos = [
    'clientes' => ['cliente', 'Não é possível excluir: o cliente possui veículos ou ordens de serviço.'],
    'veiculos' => ['veiculo', 'Não é possível excluir: o veículo possui ordens de serviço.'],
    'mecanicos' => ['mecanico', 'Não é possível excluir: o mecânico possui ordens de serviço.'],
    'pecas' => ['peca', 'Não é possível excluir: a peça já foi usada em ordens de serviço.'],
    'ordens' => ['ordem_servico', ''],
];

$tipo = campo('tipo');
$id = campo('id');
if (!isset($tipos[$tipo]) || !ctype_digit($id)) responder(false, ['Requisição inválida.'], 400);
[$tabela, $msgVinculo] = $tipos[$tipo];

conexao()->beginTransaction();
try {
    if ($tipo === 'ordens') devolverPecasDaOrdem((int) $id);
    $apagados = executar("DELETE FROM $tabela WHERE id = ?", [(int) $id])->rowCount();
} catch (PDOException $e) {
    // 23503 = violação de chave estrangeira
    if ($e->getCode() === '23503') responder(false, [$msgVinculo], 409);
    responder(false, ['Erro ao excluir.'], 500);
}
if (!$apagados) responder(false, ['Registro não encontrado.'], 404);
conexao()->commit();

responder(true, [], 200, $tipo === 'ordens' ? 'Ordem excluída e peças devolvidas ao estoque.' : 'Registro excluído.');
