<h1>Documentação do Plugin de Pix Pagstar</h1>
<h2>Introdução</h2>
<p>O Plugin de Pix PagStar é uma extensão para o WooCommerce que permite oferecer pagamentos por Pix em sua loja virtual, utilizando a API da PagStar. Com este plugin, seus clientes poderão realizar pagamentos de forma rápida e segura, e você poderá gerenciar os pagamentos e transações de forma eficiente.</p>
<h2>Requisitos</h2>
<ul>
  <li>WordPress instalado e configurado</li>
  <li>WooCommerce instalado e ativado</li>
  <li>Chave de API da PagStar (Token Bearer) para acesso à API</li>
  <li>Endpoint de Webhook para receber as notificações de pagamentos aprovados</li>
</ul>
<h2>Instalação</h2>
<ol>
  <li>Faça o download do arquivo ZIP do Plugin de Pix PagStar.</li>
  <li>Acesse o painel de administração do WordPress e navegue para "Plugins" &gt; "Adicionar novo".</li>
  <li>Clique no botão "Enviar plugin" e selecione o arquivo ZIP que você baixou.</li>
  <li>Após o upload, clique em "Ativar" para ativar o plugin.</li>
</ol>
<h2>Configuração</h2>
<ol>
  <li>
    <p>Após ativar o plugin, navegue para "WooCommerce" &gt; "Configurações" &gt; "Pagamentos" para acessar as configurações do PagStar.</p>
  </li>
  <li>
    <p>Preencha os campos obrigatórios:</p>
    <ul>
      <li>Tenant ID: Identificador da sua conta na PagStar.</li>
      <li>Token: Chave de API (Token Bearer) da PagStar para autorização nas requisições da API.</li>
      <li>Empresa/Contato: Nome da empresa ou pessoa responsável pelo pagamento.</li>
      <li>Modo de operação: Selecione entre "Produção" e "Sandbox" para testes.</li>
    </ul>
  </li>
  <li>
    <p>Insira o link de redirecionamento após o pagamento feito, que será a página que o cliente será redirecionado após o pagamento ter sido concluído.</p>
  </li>
  <li>
    <p>Clique em "Salvar alterações" para salvar as configurações.</p>
  </li>
</ol>
<h2>Extrato PagStar</h2>
<p>O plugin adiciona uma página de "Extrato PagStar" no menu do administrador. Nesta página, você poderá visualizar um extrato das transações realizadas por Pix, incluindo o ID da transação, o ID do pedido, o valor, a data e o status do pagamento.</p>
<h2>Suporte e Documentação</h2>
<p>Para mais informações e suporte técnico, acesse a página oficial do <a href="https://pagstar.com/plugin" target="_new">Plugin de Pix PagStar</a>.</p>