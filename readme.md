<h1>Documentação do Plugin de Pix Pagstar</h1>
<h2>Introdução</h2>
<p>O Plugin de Pix Pagstar é uma extensão para o WooCommerce que permite oferecer pagamentos por Pix em sua loja virtual, utilizando a API da Pagstar. Com este plugin, seus clientes poderão realizar pagamentos de forma rápida e segura, e você poderá gerenciar os pagamentos e transações de forma eficiente.</p>
<h2>Requisitos</h2>
<ul>
  <li>WordPress instalado e configurado</li>
  <li>WooCommerce instalado e ativado</li>
  <li>Chave de API da Pagstar (Token Bearer) para acesso à API</li>
  <li>Endpoint de Webhook para receber as notificações de pagamentos aprovados</li>
  <li>Certificados MTLS (.crt e .key) para autenticação segura</li>
</ul>
<h2>Instalação</h2>
<ol>
  <li>Faça o download do arquivo ZIP do Plugin de Pix Pagstar.</li>
  <li>Acesse o painel de administração do WordPress e navegue para "Plugins" &gt; "Adicionar novo".</li>
  <li>Clique no botão "Enviar plugin" e selecione o arquivo ZIP que você baixou.</li>
  <li>Após o upload, clique em "Ativar" para ativar o plugin.</li>
</ol>
<h2>Configuração</h2>
<ol>
  <li>
    <p>Após ativar o plugin, navegue para "WooCommerce" &gt; "Configurações" &gt; "Pagamentos" para acessar as configurações do Pagstar.</p>
  </li>
  <li>
    <p>Preencha os campos obrigatórios:</p>
    <ul>
      <li>Client ID: Identificador do cliente fornecido pela Pagstar.</li>
      <li>Client Secret: Chave secreta do cliente fornecida pela Pagstar.</li>
      <li>Chave PIX: Chave PIX cadastrada na Pagstar.</li>
      <li>Empresa/Contato: Nome da empresa ou pessoa responsável pelo pagamento.</li>
      <li>URL de Redirecionamento: Página para onde o cliente será redirecionado após o pagamento.</li>
    </ul>
  </li>
  <li>
    <p>Configurações de Certificados MTLS:</p>
    <ul>
      <li>Certificado CRT: Arquivo de certificado (.crt) para autenticação MTLS.</li>
      <li>Chave Privada: Arquivo de chave privada (.key) para autenticação MTLS.</li>
    </ul>
  </li>
  <li>
    <p>Configurações Extras:</p>
    <ul>
      <li>Informações de Pagamento: Instruções personalizadas para o processo de pagamento (opcional).</li>
      <li>Tempo de Expiração: Tempo em segundos para expiração do QR Code (padrão: 1 hora, mínimo: 5 minutos, máximo: 24 horas).</li>
    </ul>
  </li>
  <li>
    <p>Clique em "Salvar Configurações" para salvar as alterações.</p>
  </li>
</ol>
<h2>Extrato Pagstar</h2>
<p>O plugin adiciona uma página de "Extrato Pagstar" no menu do administrador. Nesta página, você poderá visualizar um extrato das transações realizadas por Pix, incluindo o ID da transação, o ID do pedido, o valor, a data e o status do pagamento.</p>
<h2>Segurança</h2>
<p>O plugin implementa várias medidas de segurança:</p>
<ul>
  <li>Validação de extensão de arquivos para certificados (.crt e .key)</li>
  <li>Verificação de tipos MIME para uploads de certificados</li>
  <li>Sanitização de inputs e outputs</li>
  <li>Proteção contra CSRF com nonce</li>
  <li>Validação de permissões de usuário</li>
</ul>
<h2>Suporte e Documentação</h2>
<p>Para mais informações e suporte técnico, acesse a página oficial do <a href="https://pagstar.com/plugin" target="_new">Plugin de Pix Pagstar</a>.</p>