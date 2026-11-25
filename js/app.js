// Comunicação com o PHP via fetch (requisições assíncronas em JS).
// - Páginas com <body class="protegida"> só aparecem se houver sessão de login.
// - Cada <form data-endpoint> envia os dados para o script PHP indicado.
// - <table data-lista="..."> e <select data-opcoes="..."> são preenchidos com dados do banco.
document.addEventListener('DOMContentLoaded', async () => {
  const formLogin = document.getElementById('form-login');
  if (formLogin) return iniciarLogin(formLogin);

  if (document.body.classList.contains('protegida')) {
    const usuario = await verificarSessao();
    if (!usuario) return;
    mostrarUsuario(usuario);
    document.body.classList.remove('protegida');
  }

  document.querySelectorAll('select[data-opcoes]').forEach(carregarOpcoes);
  document.querySelectorAll('table[data-lista]').forEach(carregarLista);

  const form = document.querySelector('form[data-endpoint]');
  if (!form) return;
  const caixa = document.getElementById('resultado');

  form.addEventListener('submit', async (evento) => {
    evento.preventDefault(); // impede o envio tradicional (recarregar a página)
    try {
      const json = await requisitar(form.dataset.endpoint, { method: 'POST', body: new FormData(form) });
      mostrarResultado(caixa, json);
      if (json.sucesso) {
        form.reset();
        document.querySelectorAll('table[data-lista]').forEach(carregarLista);
      }
    } catch (erro) {
      mostrarResultado(caixa, { sucesso: false, erros: ['Falha na comunicação com o servidor: ' + erro.message] });
    }
  });
});

// Faz a requisição e, se a sessão tiver expirado (401), volta para o login.
async function requisitar(url, opcoes = {}) {
  const resposta = await fetch(url, { credentials: 'same-origin', ...opcoes });
  const json = await resposta.json();
  if (resposta.status === 401 && !url.includes('login.php')) {
    window.location.href = 'login.html';
    throw new Error('sessão expirada');
  }
  return json;
}

async function verificarSessao() {
  try {
    const resposta = await fetch('php/sessao.php', { credentials: 'same-origin' });
    if (!resposta.ok) throw new Error();
    return (await resposta.json()).dados;
  } catch {
    window.location.href = 'login.html';
    return null;
  }
}

function iniciarLogin(form) {
  const caixa = document.getElementById('resultado');
  form.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    try {
      const json = await requisitar('php/login.php', { method: 'POST', body: new FormData(form) });
      if (json.sucesso) return (window.location.href = 'index.html');
      mostrarResultado(caixa, json);
    } catch (erro) {
      mostrarResultado(caixa, { sucesso: false, erros: ['Falha na comunicação com o servidor: ' + erro.message] });
    }
  });
}

// Adiciona "Olá, Fulano · Sair" no menu.
function mostrarUsuario(usuario) {
  const menu = document.querySelector('nav ul');
  if (!menu) return;
  const li = document.createElement('li');
  li.className = 'usuario';
  const nome = document.createElement('span');
  nome.textContent = 'Olá, ' + usuario.nome;
  const sair = document.createElement('a');
  sair.href = '#';
  sair.textContent = 'Sair';
  sair.addEventListener('click', async (e) => {
    e.preventDefault();
    await fetch('php/logout.php', { method: 'POST', credentials: 'same-origin' });
    window.location.href = 'login.html';
  });
  li.append(nome, sair);
  menu.appendChild(li);
}

async function carregarOpcoes(select) {
  const json = await requisitar('php/listar.php?tipo=opcoes-' + select.dataset.opcoes);
  (json.dados || []).forEach(({ valor, texto }) => select.add(new Option(texto, valor)));
}

async function carregarLista(tabela) {
  const json = await requisitar('php/listar.php?tipo=' + tabela.dataset.lista);
  const linhas = json.dados || [];
  tabela.innerHTML = '';
  if (!linhas.length) {
    tabela.innerHTML = '<tr><td>Nenhum registro cadastrado ainda.</td></tr>';
    return;
  }
  const cabecalho = tabela.createTHead().insertRow();
  Object.keys(linhas[0]).forEach((coluna) => {
    const th = document.createElement('th');
    th.textContent = coluna;
    cabecalho.appendChild(th);
  });
  const corpo = tabela.createTBody();
  linhas.forEach((linha) => {
    const tr = corpo.insertRow();
    Object.values(linha).forEach((valor) => (tr.insertCell().textContent = valor ?? ''));
  });
}

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
