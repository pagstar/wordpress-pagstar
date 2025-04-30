# Changelog

Todas as alterações notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere ao [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [1.0.0] - 2024-03-20

### Adicionado
- Implementação inicial do plugin de pagamento PIX
- Integração com API Pagstar
- Página de configurações com campos para credenciais
- Upload e validação de certificados MTLS
- Configurações extras para informações de pagamento
- Configuração de tempo de expiração do QR Code
- Página de extrato de transações
- Validação de segurança para uploads
- Documentação de segurança
- Contribuição e licença

### Melhorado
- Validação de extensão de arquivos para certificados
- Feedback visual no formulário de configurações
- Organização do código e documentação
- Segurança geral do plugin

### Corrigido
- Validação de tipos MIME para certificados
- Sanitização de inputs e outputs
- Proteção contra CSRF
- Validação de permissões

## [0.1.0] - 2024-03-15

### Adicionado
- Estrutura inicial do plugin
- Integração básica com WooCommerce
- Configurações iniciais de pagamento
- Sistema de webhook para notificações 