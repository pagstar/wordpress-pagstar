# Changelog

Todas as mudanças notáveis neste projeto serão documentadas neste arquivo.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.0.0/),
e este projeto adere a [Semantic Versioning](https://semver.org/lang/pt-BR/).

## [1.0.2] - 2024-03-21

### Adicionado
- Validação de CPF no webhook
- Função utilitária para validação de CPF
- Sistema de rotação de logs diária
- Backup automático de logs antigos

### Melhorado
- Organização do código com arquivo utils.php
- Tratamento de erros no webhook
- Sistema de logs mais robusto
- Sanitização de dados recebidos

### Corrigido
- Erro de digitação no campo txid
- Permissões de diretório usando wp_mkdir_p
- Validação de campos obrigatórios

## [1.0.1] - 2024-03-20

### Adicionado
- Melhorias na detecção de versão do plugin
- Atualização automática do CHANGELOG.md
- Criação automática de releases no GitHub

### Melhorado
- Processo de atualização de versão
- Logs do GitHub Action

### Corrigido
- Problemas com permissões no GitHub Action
- Comandos de push e commit no workflow

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