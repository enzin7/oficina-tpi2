// <body class="protegida">: só aparece com login.
// <form data-endpoint>: envia para o PHP indicado (novo cadastro ou edição).
// <table data-lista> e <select data-opcoes>: preenchidos com dados do banco.
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
  prepararFormulario(form);
  if (document.getElementById('pecas-os')) prepararPecas();
});

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

// ---------- Formulário: cadastro e edição ----------

function prepararFormulario(form) {
  const caixa = document.getElementById('resultado');
  const enviar = form.querySelector('button[type="submit"]');

  const campoId = document.createElement('input');
  campoId.type = 'hidden';
  campoId.name = 'id';
  form.appendChild(campoId);

  const cancelar = document.createElement('button');
  cancelar.type = 'button';
  cancelar.className = 'secundario';
  cancelar.textContent = 'Cancelar edição';
  cancelar.hidden = true;
  enviar.after(cancelar);

  form.limpar = () => {
    form.reset();
    campoId.value = '';
    enviar.textContent = 'Enviar';
    cancelar.hidden = true;
    form.classList.remove('editando');
    if (form.limparPecas) form.limparPecas();
  };

  form.editar = (dados) => {
    form.limpar();
    Object.entries(dados).forEach(([nome, valor]) => {
      if (nome === 'pecas' && form.definirPecas) return form.definirPecas(valor);
      if (form.elements[nome]) form.elements[nome].value = valor ?? '';
    });
    campoId.value = dados.id;
    enviar.textContent = 'Salvar alterações';
    cancelar.hidden = false;
    form.classList.add('editando');
    form.scrollIntoView({ behavior: 'smooth' });
  };

  cancelar.addEventListener('click', () => form.limpar());

  form.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    try {
      const json = await requisitar(form.dataset.endpoint, { method: 'POST', body: new FormData(form) });
      mostrarResultado(caixa, json);
      if (json.sucesso) {
        form.limpar();
        atualizarDados();
      }
    } catch (erro) {
      mostrarResultado(caixa, { sucesso: false, erros: ['Falha na comunicação com o servidor: ' + erro.message] });
    }
  });
}

function atualizarDados() {
  document.querySelectorAll('select[data-opcoes]').forEach(carregarOpcoes);
  document.querySelectorAll('table[data-lista]').forEach(carregarLista);
}

// ---------- Listagem com Editar / Excluir ----------

async function carregarOpcoes(select) {
  const json = await requisitar('php/listar.php?tipo=opcoes-' + select.dataset.opcoes);
  const atual = select.value;
  select.querySelectorAll('option:not([value=""])').forEach((o) => o.remove());
  (json.dados || []).forEach(({ valor, texto }) => select.add(new Option(texto, valor)));
  select.value = atual;
}

async function carregarLista(tabela) {
  const tipo = tabela.dataset.lista;
  const json = await requisitar('php/listar.php?tipo=' + tipo);
  const linhas = json.dados || [];
  tabela.innerHTML = '';
  if (!linhas.length) {
    tabela.innerHTML = '<tr><td>Nenhum registro cadastrado ainda.</td></tr>';
    return;
  }

  const colunas = Object.keys(linhas[0]).filter((c) => c !== 'id');
  const cabecalho = tabela.createTHead().insertRow();
  [...colunas, 'Ações'].forEach((coluna) => {
    const th = document.createElement('th');
    th.textContent = coluna;
    cabecalho.appendChild(th);
  });

  const corpo = tabela.createTBody();
  linhas.forEach((linha) => {
    const tr = corpo.insertRow();
    colunas.forEach((c) => (tr.insertCell().textContent = linha[c] ?? ''));
    const acoes = tr.insertCell();
    acoes.className = 'acoes';
    acoes.append(
      botao('Editar', 'editar', () => editarRegistro(tipo, linha.id)),
      botao('Excluir', 'excluir', () => excluirRegistro(tipo, linha.id))
    );
  });
}

function botao(texto, classe, acao) {
  const b = document.createElement('button');
  b.type = 'button';
  b.textContent = texto;
  b.className = classe;
  b.addEventListener('click', acao);
  return b;
}

async function editarRegistro(tipo, id) {
  const json = await requisitar(`php/obter.php?tipo=${tipo}&id=${id}`);
  const form = document.querySelector('form[data-endpoint]');
  if (json.sucesso) form.editar(json.dados);
  else mostrarResultado(document.getElementById('resultado'), json);
}

async function excluirRegistro(tipo, id) {
  if (!confirm('Excluir este registro? Essa ação não pode ser desfeita.')) return;
  const dados = new FormData();
  dados.append('tipo', tipo);
  dados.append('id', id);
  const json = await requisitar('php/excluir.php', { method: 'POST', body: dados });
  mostrarResultado(document.getElementById('resultado'), json);
  if (json.sucesso) {
    const form = document.querySelector('form[data-endpoint]');
    if (form.elements.id.value === String(id)) form.limpar();
    atualizarDados();
  }
}

// ---------- Peças da ordem de serviço ----------

function prepararPecas() {
  const form = document.querySelector('form[data-endpoint]');
  const select = document.getElementById('peca-select');
  const qtd = document.getElementById('peca-qtd');
  const lista = document.getElementById('pecas-lista');
  const itens = new Map(); // id da peça => { texto, quantidade }

  const desenhar = () => {
    lista.innerHTML = '';
    if (!itens.size) {
      lista.innerHTML = '<li class="vazio">Nenhuma peça adicionada.</li>';
      return;
    }
    itens.forEach((item, id) => {
      const li = document.createElement('li');
      const texto = document.createElement('span');
      texto.textContent = `${item.quantidade}x ${item.texto}`;
      const oculto = document.createElement('input');
      oculto.type = 'hidden';
      oculto.name = `pecas[${id}]`;
      oculto.value = item.quantidade;
      li.append(texto, oculto, botao('Remover', 'excluir', () => { itens.delete(id); desenhar(); }));
      lista.appendChild(li);
    });
  };

  document.getElementById('peca-adicionar').addEventListener('click', () => {
    const quantidade = parseInt(qtd.value, 10);
    if (!select.value || !(quantidade > 0)) return;
    const atual = itens.get(select.value);
    const texto = select.selectedOptions[0].text.replace(/ \(estoque.*$/, '');
    itens.set(select.value, { texto, quantidade: (atual ? atual.quantidade : 0) + quantidade });
    select.value = '';
    qtd.value = 1;
    desenhar();
  });

  form.limparPecas = () => { itens.clear(); desenhar(); };
  form.definirPecas = (pecas) => {
    itens.clear();
    pecas.forEach((p) => itens.set(String(p.id), { texto: p.texto, quantidade: p.quantidade }));
    desenhar();
  };
  desenhar();
}

function mostrarResultado(caixa, json) {
  caixa.className = json.sucesso ? 'sucesso' : 'erro';
  caixa.innerHTML = '';
  const titulo = document.createElement('strong');
  titulo.textContent = json.sucesso ? json.mensagem : 'Atenção:';
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
