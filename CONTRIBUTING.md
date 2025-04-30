# Guia de Contribuição

Obrigado por seu interesse em contribuir com o Plugin Pagstar! Este documento fornece um conjunto de diretrizes para contribuir com o projeto.

## Como Contribuir

1. **Reportando Problemas**
   - Use o sistema de issues do GitHub
   - Inclua versão do plugin, WordPress e WooCommerce
   - Descreva o problema de forma clara e detalhada
   - Inclua logs de erro quando relevante

2. **Sugerindo Melhorias**
   - Descreva a melhoria proposta
   - Explique o benefício da mudança
   - Forneça exemplos de uso quando possível

3. **Enviando Pull Requests**
   - Crie uma branch para sua feature/fix
   - Siga as convenções de código
   - Inclua testes quando possível
   - Atualize a documentação
   - Descreva as mudanças no PR

## Padrões de Código

### PHP
- Siga as [PSR-12](https://www.php-fig.org/psr/psr-12/)
- Use type hints quando possível
- Documente funções e classes
- Mantenha funções pequenas e focadas

### JavaScript
- Use ES6+ quando possível
- Siga as convenções do WordPress
- Documente funções complexas
- Use JSDoc para documentação

### CSS
- Use BEM para nomenclatura
- Mantenha especificidade baixa
- Use variáveis CSS quando possível
- Documente classes complexas

## Ambiente de Desenvolvimento

1. **Requisitos**
   - PHP 7.4+
   - WordPress 5.6+
   - WooCommerce 5.0+
   - Node.js (para desenvolvimento)
   - Composer (para dependências PHP)

2. **Configuração**
   ```bash
   # Clone o repositório
   git clone https://github.com/pagstar/wordpress-pagstar.git
   
   # Instale dependências
   composer install
   npm install
   
   # Configure o ambiente
   cp .env.example .env
   ```

3. **Testes**
   - Execute testes PHP: `composer test`
   - Execute testes JavaScript: `npm test`
   - Verifique padrões de código: `composer lint`

## Processo de Revisão

1. **Submissão**
   - Crie uma branch descritiva
   - Faça commits atômicos
   - Escreva mensagens claras
   - Atualize o CHANGELOG.md

2. **Revisão**
   - PRs serão revisados por mantenedores
   - Feedback será fornecido em até 48h
   - Correções podem ser solicitadas
   - Discussões são bem-vindas

3. **Aprovação**
   - PRs precisam de 2 aprovações
   - Todos os testes devem passar
   - Documentação deve estar atualizada
   - Código deve seguir padrões

## Código de Conduta

- Seja respeitoso
- Mantenha discussões construtivas
- Respeite diferentes pontos de vista
- Ajude outros contribuidores
- Siga as diretrizes da comunidade

## Suporte

Para dúvidas sobre contribuição:
- Issues do GitHub
- Comunidade no Discord
- Email: tecnologia@pagstar.com

## Agradecimentos

Obrigado por contribuir para o crescimento do Plugin Pagstar! 