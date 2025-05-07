# Plugin Pagstar para WooCommerce

![Versão](https://img.shields.io/badge/versão-1.0.4-blue.svg)
![WooCommerce](https://img.shields.io/badge/WooCommerce-5.0%2B-green.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![Licença](https://img.shields.io/badge/licença-GPLv2-orange.svg)

## 📋 Descrição

O Plugin Pagstar para WooCommerce é uma solução completa de integração de pagamentos via PIX para sua loja WordPress. Desenvolvido com foco em segurança, performance e facilidade de uso, este plugin permite que seus clientes realizem pagamentos instantâneos através do sistema PIX.

## ✨ Características Principais

- 💳 Processamento de pagamentos via PIX
- 🔄 Suporte a reembolsos automáticos
- 🔐 Sistema seguro de tokenização
- 📊 Painel administrativo com extrato de transações
- 📱 Interface responsiva
- 🔔 Notificações automáticas de status
- 💾 Backup automático de configurações
- 🔄 Suporte a assinaturas recorrentes
- 🛒 Compatível com pre-orders
- 🔒 Certificação SSL integrada

## 🚀 Requisitos do Sistema

- WordPress 5.0 ou superior
- WooCommerce 5.0 ou superior
- PHP 7.4 ou superior
- Certificado SSL ativo
- Acesso à API da Pagstar

## 📥 Instalação

1. Faça o download do plugin
2. Acesse o painel administrativo do WordPress
3. Vá para Plugins > Adicionar Novo
4. Clique em "Enviar Plugin" e selecione o arquivo baixado
5. Ative o plugin
6. Configure as credenciais da Pagstar em WooCommerce > Configurações > Pagamentos > Pagstar

## ⚙️ Configuração

### Credenciais da API

1. Acesse o painel da Pagstar
2. Obtenha suas credenciais de API (Client ID e Client Secret)
3. Configure sua chave PIX
4. Insira as credenciais no painel do plugin

### Configurações do Gateway

- **Título**: Nome do método de pagamento exibido no checkout
- **Descrição**: Descrição do método de pagamento
- **Instruções**: Instruções para o cliente após o pagamento
- **Status do Pedido**: Configuração automática de status

## 🔧 Uso

### Processamento de Pagamentos

1. Cliente seleciona PIX como método de pagamento
2. Sistema gera QR Code ou código PIX
3. Cliente realiza o pagamento
4. Sistema confirma automaticamente o pagamento
5. Pedido é atualizado com status de pagamento

### Reembolsos

1. Acesse o pedido no painel administrativo
2. Clique em "Reembolsar"
3. Insira o valor e motivo do reembolso
4. Confirme a operação

## 📊 Painel Administrativo

O plugin inclui um painel administrativo completo com:

- Extrato de transações
- Status de pagamentos
- Histórico de reembolsos
- Configurações do gateway
- Backup de configurações

## 🔒 Segurança

- Validação de certificados SSL
- Sanitização de dados
- Verificação de integridade
- Proteção contra CSRF
- Logs de transações
- Backup automático

## 🔄 Webhooks

O plugin utiliza webhooks para:

- Confirmação automática de pagamentos
- Atualização de status de pedidos
- Notificações de reembolsos
- Sincronização de dados

## 📝 Changelog

### Versão 1.0.4
- Melhorias na segurança
- Otimização de performance
- Correção de bugs
- Novas funcionalidades

[Ver changelog completo](CHANGELOG.md)

## 🤝 Contribuindo

Contribuições são bem-vindas! Por favor, leia nosso [guia de contribuição](CONTRIBUTING.md) antes de enviar pull requests.

## 🐛 Reportando Bugs

Se você encontrar algum bug, por favor:

1. Verifique se o bug já foi reportado
2. Use o template de issue
3. Forneça informações detalhadas
4. Inclua logs relevantes

## 📄 Licença

Este projeto está licenciado sob a GPLv2 - veja o arquivo [LICENSE.md](LICENSE.md) para detalhes.

## 🔗 Links Úteis

- [Documentação da API Pagstar](https://docs.pagstar.com.br)
- [Suporte WooCommerce](https://woocommerce.com/support)
- [Fórum de Suporte](https://suporte.pagstar.com.br)

## 📞 Suporte

Para suporte técnico:

- Email: atendimento@pagstar.com.br
- Telefone: +55 (11) 94241-2844
- Horário: Segunda a Sexta, 9h às 18h

## 🙏 Agradecimentos

- Equipe WooCommerce
- Comunidade WordPress
- Todos os contribuidores

## 👥 Contribuidores

<table>
  <tr>
    <td align="center">
      <a href="https://github.com/kleberandrade">
        <img src="https://github.com/kleberandrade.png" width="100px;" alt="Kleber Andrade"/>
        <br />
        <sub><b>Kleber Andrade</b></sub>
      </a>
    </td>
    <td align="center">
      <a href="https://github.com/WillBorgesDev">
        <img src="https://github.com/WillBorgesDev.png" width="100px;" alt="Wilker Borges"/>
        <br />
        <sub><b>Wilker Borges</b></sub>
      </a>
    </td>
    <td align="center">
      <a href="https://github.com/pagstar">
        <img src="https://github.com/pagstar.png" width="100px;" alt="Equipe Pagstar"/>
        <br />
        <sub><b>Equipe Pagstar</b></sub>
      </a>
    </td>
  </tr>
</table>

---

Desenvolvido com ❤️ pela equipe Pagstar
