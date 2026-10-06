// Envio genérico dos formulários via fetch (requisição assíncrona em JS).
// Cada <form> informa no atributo data-endpoint qual script PHP vai recebê-lo.
document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('form[data-endpoint]');
  if (!form) return;
  const caixa = document.getElementById('resultado');

  form.addEventListener('submit', async (evento) => {
    evento.preventDefault(); // impede o envio tradicional (recarregar a página)
    const dados = new FormData(form);

    try {
      const resposta = await fetch(form.dataset.endpoint, { method: 'POST', body: dados });
      const json = await resposta.json();
      mostrarResultado(caixa, json);
      if (json.sucesso) form.reset();
    } catch (erro) {
      mostrarResultado(caixa, { sucesso: false, erros: ['Falha na comunicação com o servidor: ' + erro.message] });
    }
  });
});

function mostrarResultado(caixa, json) {
  caixa.className = json.sucesso ? 'sucesso' : 'erro';
  caixa.innerHTML = '';
  const titulo = document.createElement('strong');
  titulo.textContent = json.sucesso ? json.mensagem : 'Corrija os campos abaixo:';
  caixa.appendChild(titulo);

  const lista = document.createElement('ul');
  const itens = json.sucesso ? Object.entries(json.dados || {}).map(([k, v]) => `${k}: ${v}`) : (json.erros || []);
  itens.forEach((texto) => {
    const li = document.createElement('li');
    li.textContent = texto;
    lista.appendChild(li);
  });
  caixa.appendChild(lista);
}
