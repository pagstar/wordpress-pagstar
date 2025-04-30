# Política de Segurança do Plugin Pagstar

## Relatando Vulnerabilidades

Se você descobrir uma vulnerabilidade de segurança no Plugin Pagstar, por favor, entre em contato conosco através do email tecnologia@pagstar.com. Não divulgue a vulnerabilidade publicamente até que tenhamos tido a chance de corrigi-la.

## Requisitos de Segurança

### Ambiente
- PHP 7.4 ou superior
- WordPress 5.6 ou superior
- WooCommerce 5.0 ou superior
- SSL/TLS ativado no servidor
- Permissões de arquivo adequadas (755 para diretórios, 644 para arquivos)

### Certificados
- Certificados MTLS (.crt e .key) devem ser mantidos em local seguro
- Certificados devem ser renovados antes da expiração
- Chaves privadas devem ter permissões restritas (600)
- Certificados devem ser validados regularmente

### Configurações
- Use senhas fortes para todas as credenciais
- Mantenha o Client ID e Client Secret em segurança
- Configure URLs de redirecionamento seguras (HTTPS)
- Limite o acesso administrativo ao mínimo necessário

## Boas Práticas

### Desenvolvimento
- Sempre valide e sanitize dados de entrada
- Use nonces para todas as requisições
- Implemente rate limiting para APIs
- Mantenha logs de segurança
- Faça backup regular das configurações

### Implantação
- Mantenha o WordPress e WooCommerce atualizados
- Use HTTPS em todo o site
- Configure firewalls adequadamente
- Monitore logs de acesso
- Faça backup regular do banco de dados

### Manutenção
- Verifique logs de segurança regularmente
- Monitore tentativas de acesso
- Atualize certificados quando necessário
- Revise permissões periodicamente
- Mantenha backups atualizados

## Procedimentos de Emergência

### Em caso de violação de segurança:
1. Isole o sistema afetado
2. Notifique a equipe de segurança
3. Preserve logs e evidências
4. Avalie o impacto
5. Implemente correções
6. Notifique usuários afetados
7. Atualize documentação

### Recuperação de dados:
1. Restaure backup mais recente
2. Verifique integridade dos dados
3. Atualize todas as senhas
4. Revise permissões
5. Monitore atividade suspeita

## Contato

Para questões de segurança, entre em contato através dos canais oficiais:

- Email: [tecnologia@pagstar.com](mailto:tecnologia@pagstar.com)
- Telefone: [+55 (11) 94241-2844](tel:+5511942412844)