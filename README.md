# Plugin de Pagamento PIX Pagstar para WooCommerce

[![WordPress](https://img.shields.io/badge/WordPress-%23117AC9.svg?style=for-the-badge&logo=WordPress&logoColor=white)](https://wordpress.org/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-%23965A3E.svg?style=for-the-badge&logo=WooCommerce&logoColor=white)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white)](https://php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg?style=for-the-badge)](https://opensource.org/licenses/MIT)

## Introdução

Este plugin permite a integração do método de pagamento PIX da Pagstar com o WooCommerce. Com este plugin, seus clientes poderão realizar pagamentos de forma rápida e segura, e você poderá gerenciar os pagamentos e transações de forma eficiente.

## Funcionalidades

- Integração com a API Pagstar
- Geração de QR Code PIX
- Notificações automáticas de pagamento
- Página de extrato de transações
- Configurações personalizáveis
- Suporte a certificados MTLS
- Backup automático de configurações

## Requisitos

- WordPress 5.0 ou superior
- WooCommerce 5.0 ou superior
- PHP 7.4 ou superior
- OpenSSL
- cURL

## Instalação

1. Faça o download do plugin
2. Acesse o painel administrativo do WordPress
3. Vá em Plugins > Adicionar Novo > Enviar Plugin
4. Selecione o arquivo do plugin e clique em "Instalar Agora"
5. Ative o plugin após a instalação

## Configuração

1. Acesse WooCommerce > Configurações > Pagamentos
2. Localize o método de pagamento "Pagstar PIX"
3. Configure as credenciais da API:
   - Client ID
   - Client Secret
   - Chave PIX
   - URL de Redirecionamento
4. Faça upload dos certificados MTLS
5. Configure as informações adicionais:
   - Nome da Empresa
   - Email da Empresa
   - Tempo de Expiração do QR Code
   - Limite de Requisições por Minuto

## Atualização de Versão

O plugin utiliza um sistema automatizado de versionamento baseado em [Semantic Versioning](https://semver.org/lang/pt-BR/).

### Como atualizar a versão

1. Execute o script de atualização:
   ```bash
   php version-update.php --type=[major|minor|patch]
   ```
   ou especifique uma versão específica:
   ```bash
   php version-update.php --version=1.0.0
   ```

2. O script irá:
   - Atualizar a versão no arquivo do plugin
   - Atualizar o CHANGELOG.md
   - Criar um novo commit
   - Criar uma nova tag
   - Enviar as alterações para o repositório

### Tipos de Atualização

- **major**: Atualiza a versão principal (ex: 1.0.0 -> 2.0.0)
- **minor**: Atualiza a versão secundária (ex: 1.0.0 -> 1.1.0)
- **patch**: Atualiza a versão de correção (ex: 1.0.0 -> 1.0.1)

## Segurança

- Todas as credenciais são armazenadas de forma segura no banco de dados
- Os certificados MTLS são validados antes do upload
- As requisições à API são feitas via HTTPS
- O plugin implementa proteção contra CSRF
- Os logs são rotacionados diariamente

## Suporte

Para suporte técnico, entre em contato através do email: atendimento@pagstar.com

## Licença

Este plugin está licenciado sob a Licença de Software Livre Pagstar. Consulte o arquivo [LICENSE](LICENSE) para obter mais detalhes.

## Contribuição

Contribuições são bem-vindas! Por favor, leia as diretrizes de contribuição antes de enviar um pull request.

## Changelog

Todas as mudanças notáveis deste projeto serão documentadas no arquivo [CHANGELOG.md](CHANGELOG.md).

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
