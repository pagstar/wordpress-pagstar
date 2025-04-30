# Plugin de Pix Pagstar para WooCommerce

[![WordPress](https://img.shields.io/badge/WordPress-%23117AC9.svg?style=for-the-badge&logo=WordPress&logoColor=white)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-%23965A3E.svg?style=for-the-badge&logo=WooCommerce&logoColor=white)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white)](https://php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge)](https://opensource.org/licenses/MIT)

## Introdução

O Plugin de Pix Pagstar é uma extensão para o WooCommerce que permite oferecer pagamentos por Pix em sua loja virtual, utilizando a API da Pagstar. Com este plugin, seus clientes poderão realizar pagamentos de forma rápida e segura, e você poderá gerenciar os pagamentos e transações de forma eficiente.

## Requisitos

* WordPress instalado e configurado
* WooCommerce instalado e ativado
* Chave de API da Pagstar (Token Bearer) para acesso à API
* Endpoint de Webhook para receber as notificações de pagamentos aprovados
* Certificados MTLS (.crt e .key) para autenticação segura

## Instalação

1. Faça o download do arquivo ZIP do Plugin de Pix Pagstar
2. Acesse o painel de administração do WordPress e navegue para "Plugins" > "Adicionar novo"
3. Clique no botão "Enviar plugin" e selecione o arquivo ZIP que você baixou
4. Após o upload, clique em "Ativar" para ativar o plugin

## Configuração

1. Após ativar o plugin, navegue para "WooCommerce" > "Configurações" > "Pagamentos" para acessar as configurações do Pagstar

2. Preencha os campos obrigatórios:
   * Client ID: Identificador do cliente fornecido pela Pagstar
   * Client Secret: Chave secreta do cliente fornecida pela Pagstar
   * Chave PIX: Chave PIX cadastrada na Pagstar
   * Empresa/Contato: Nome da empresa ou pessoa responsável pelo pagamento
   * URL de Redirecionamento: Página para onde o cliente será redirecionado após o pagamento

3. Configurações de Certificados MTLS:
   * Certificado CRT: Arquivo de certificado (.crt) para autenticação MTLS
   * Chave Privada: Arquivo de chave privada (.key) para autenticação MTLS

4. Configurações Extras:
   * Informações de Pagamento: Instruções personalizadas para o processo de pagamento (opcional)
   * Tempo de Expiração: Tempo em segundos para expiração do QR Code (padrão: 1 hora, mínimo: 5 minutos, máximo: 24 horas)
   * Limite de Requisições: Número máximo de requisições permitidas por minuto no webhook (padrão: 100, máximo: 20.000)

5. Clique em "Salvar Configurações" para salvar as alterações

## Como Gerar Credenciais

Para gerar suas credenciais de acesso à API da Pagstar, siga os passos abaixo:

1. Acesse o [Portal Pagstar Finance](https://finance.pagstar.com)
2. No menu lateral, clique em "Configurações"
3. Na aba superior, você terá duas opções:
   - **API QRCODES**: Para gerar credenciais específicas para QR Codes
4. Clique no botão "Gerar Credenciais"
5. Guarde com segurança o `Client ID` e `Client Secret` gerados
6. Configure estas credenciais no plugin através do menu WooCommerce > Configurações > Pagstar

![Página de Configurações Pagstar](https://files.readme.io/c8f878449fba88d625143aaca203439d79fa49a69b007fbdec5767a2be745dd5-image.png)

**Importante**: Mantenha suas credenciais em local seguro e nunca compartilhe seu `Client Secret`.

## API Pagstar

O plugin implementa uma classe `Pagstar_API` para interagir com a API da Pagstar. Esta classe oferece os seguintes métodos:

### Autenticação
- `get_access_token()`: Obtém o token de acesso usando as credenciais do cliente
- Sistema de cache automático do token por 4 minutos
- Renovação automática do token quando expirado

### Cobranças PIX
- `create_cob()`: Cria uma nova cobrança PIX
- `get_cob()`: Consulta uma cobrança PIX existente

### Webhook
- `configure_webhook()`: Configura a URL do webhook

### Segurança
- Autenticação MTLS em todas as requisições
- Validação de certificados
- Sanitização de dados
- Tratamento de erros

## Extrato Pagstar

O plugin adiciona uma página de "Extrato Pagstar" no menu do administrador. Nesta página, você poderá visualizar um extrato das transações realizadas por Pix, incluindo o ID da transação, o ID do pedido, o valor, a data e o status do pagamento.

## Webhook

O plugin implementa um endpoint de webhook para receber notificações de pagamentos. O webhook inclui:

* Validação de CPF do pagador
* Rate limiting configurável
* Sanitização de dados
* Sistema de logs com rotação diária
* Backup automático de logs antigos
* Respostas padronizadas em JSON

### Formato do Webhook

O webhook espera receber dados no seguinte formato:

```json
{
    "txid": "457e7d5d-c666-4471-b1ff-6c79ebe28c69",
    "valor": "230.00",
    "horario": "2023-08-22T22:29:38.751Z",
    "pagador": {
        "cpf": "45757346833",
        "nome": "Wilker Ferreira Borges"
    },
    "endToEndId": "E0825353920230822222938743964460"
}
```

## Segurança

O plugin implementa várias medidas de segurança:

* Validação de extensão de arquivos para certificados (.crt e .key)
* Verificação de tipos MIME para uploads de certificados
* Sanitização de inputs e outputs
* Proteção contra CSRF com nonce
* Validação de permissões de usuário
* Validação de CPF no webhook
* Rate limiting configurável
* Sistema de logs com rotação e backup
* Autenticação MTLS em todas as requisições à API
* Cache seguro de tokens de acesso

## Colaboradores

<table>
  <tr>
    <td align="center">
      <a href="https://github.com/kleberandrade">
        <img src="https://github.com/kleberandrade.png" width="100px;" alt="Kleber Andrade"/>
        <br />
        <sub><b>Kleber Andrade</b></sub>
        <br />
        <sub>Desenvolvedor</sub>
      </a>
    </td>
    <td align="center">
      <a href="https://github.com/WillBorgesDev">
        <img src="https://github.com/WillBorgesDev.png" width="100px;" alt="Will Borges"/>
        <br />
        <sub><b>Will Borges</b></sub>
        <br />
        <sub>Desenvolvedor</sub>
      </a>
    </td>
    <td align="center">
      <a href="https://github.com/pagstar">
        <img src="https://github.com/pagstar.png" width="100px;" alt="Equipe Pagstar"/>
        <br />
        <sub><b>Equipe Pagstar</b></sub>
        <br />
        <sub>Mantenedores</sub>
      </a>
    </td>
  </tr>
</table>
