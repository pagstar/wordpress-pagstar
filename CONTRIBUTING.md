# Guia de Contribuição

## 📋 Índice

1. [Como Contribuir](#como-contribuir)
2. [Configuração do Ambiente](#configuração-do-ambiente)
3. [Padrões de Código](#padrões-de-código)
4. [Fluxo de Trabalho](#fluxo-de-trabalho)
5. [Testes](#testes)
6. [Documentação](#documentação)
7. [Pull Requests](#pull-requests)
8. [Código de Conduta](#código-de-conduta)

## 🤝 Como Contribuir

### Tipos de Contribuição

- 🐛 Reportar bugs
- 💡 Sugerir melhorias
- 📝 Melhorar documentação
- 🔧 Corrigir bugs
- ✨ Adicionar novas funcionalidades

### Primeiros Passos

1. Faça um fork do repositório
2. Clone seu fork localmente
3. Configure o ambiente de desenvolvimento
4. Crie uma branch para sua contribuição
5. Faça suas alterações
6. Envie um pull request

## 🛠️ Configuração do Ambiente

### Requisitos

- PHP 7.4+
- Composer
- WordPress 5.0+
- WooCommerce 5.0+
- Node.js 14+
- npm ou yarn

### Instalação

1. Clone o repositório:
```bash
git clone https://github.com/seu-usuario/wordpress-pagstar.git
cd wordpress-pagstar
```

2. Instale as dependências:
```bash
composer install
npm install
```

3. Configure o ambiente WordPress:
```bash
wp core download
wp config create
wp db create
```

4. Ative o plugin:
```bash
wp plugin activate pagstar-woocommerce-plugin
```

## 📝 Padrões de Código

### PHP

- Seguir PSR-12
- Usar type hints
- Documentar funções e classes
- Manter código limpo e legível
- Usar nomes descritivos

### JavaScript

- Seguir ESLint
- Usar ES6+
- Documentar funções
- Manter código modular

### CSS

- Seguir BEM
- Usar variáveis CSS
- Manter especificidade baixa
- Documentar classes complexas

## 🔄 Fluxo de Trabalho

1. Crie uma branch:
```bash
git checkout -b feature/nova-funcionalidade
```

2. Faça suas alterações:
```bash
git add .
git commit -m "feat: adiciona nova funcionalidade"
```

3. Atualize sua branch:
```bash
git pull origin main
```

4. Envie suas alterações:
```bash
git push origin feature/nova-funcionalidade
```

## 🧪 Testes

### Testes Unitários

```bash
composer test
```

### Testes de Integração

```bash
composer test:integration
```

### Testes E2E

```bash
npm run test:e2e
```

## 📚 Documentação

### Comentários

- Documentar funções públicas
- Explicar lógica complexa
- Manter documentação atualizada
- Usar PHPDoc

### README

- Atualizar README.md
- Documentar novas funcionalidades
- Atualizar changelog
- Manter exemplos atualizados

## 🔄 Pull Requests

### Processo

1. Crie uma issue
2. Descreva as alterações
3. Referencie a issue no PR
4. Aguarde revisão
5. Faça ajustes se necessário

### Template

```markdown
## Descrição

[Descreva suas alterações]

## Tipo de Alteração

- [ ] Bug fix
- [ ] Nova funcionalidade
- [ ] Breaking change
- [ ] Documentação

## Checklist

- [ ] Testes adicionados
- [ ] Documentação atualizada
- [ ] Código segue padrões
- [ ] Build passa
- [ ] Lint passa
```

## 👥 Código de Conduta

### Regras

1. Seja respeitoso
2. Mantenha foco técnico
3. Ajude outros contribuidores
4. Aceite críticas construtivas
5. Mantenha comunicação profissional

### Comunicação

- Use português claro
- Seja objetivo
- Mantenha tom profissional
- Evite linguagem ofensiva

## 📞 Suporte

Para dúvidas sobre contribuição:

- Email: atendimento@pagstar.com.br

---

Obrigado por contribuir! 🙏 