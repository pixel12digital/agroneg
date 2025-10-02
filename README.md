# AgroNeg - Portal de Agronegócio 🚀

## ✅ Deploy Automático Ativo!
Sistema de deploy automático via GitHub Actions configurado e funcionando!

## 📋 Descrição
Portal web para conectar produtores, criadores, veterinários e outros profissionais do agronegócio.

## 🔑 Token de Acesso GitHub (Produção)

**⚠️ ATENÇÃO: Este token é CONFIDENCIAL e deve ser tratado como senha!**

### Token Atual:
```
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
```

### Configurações do Token:
- **Nome:** AgroNeg-Producao
- **Expira em:** 25 de novembro de 2025
- **Permissões:**
  - Contents: Read and write
  - Metadata: Read-only
- **Escopo:** All repositories
- **Conta:** pixel12digital

### Uso no Servidor:
```bash
# Configurar no servidor de produção (agroneg.eco.br)
git config --global user.name "pixel12digital"
git config --global user.email "seu-email@github.com"
git config --global credential.helper store

# Ao fazer git pull, usar:
# Username: pixel12digital
# Password: [TOKEN_ACIMA]
```

## 🚀 Funcionalidades Implementadas

### Galeria de Imagens (Município)
- ✅ Modal/Lightbox para visualização
- ✅ Imagens no tamanho original
- ✅ Sistema de zoom (clique duplo)
- ✅ Navegação entre imagens
- ✅ Design responsivo
- ✅ Navegação por teclado (ESC, setas)

## 📁 Estrutura do Projeto
```
AgroNeg/
├── admin/           # Painel administrativo
├── api/            # APIs e endpoints
├── assets/         # CSS, JS, imagens
├── config/         # Configurações (DB)
├── partials/       # Componentes reutilizáveis
├── uploads/        # Arquivos enviados
└── *.php          # Páginas principais
```

## 🔧 Tecnologias
- **Backend:** PHP 7.4+
- **Banco de Dados:** MySQL
- **Frontend:** HTML5, CSS3, JavaScript
- **Framework CSS:** Custom (Bootstrap-like)
- **Servidor:** Apache (XAMPP)

## 📱 Responsividade
- ✅ Desktop (Windows, Mac, Linux)
- ✅ Mobile (iOS, Android)
- ✅ Todos os navegadores modernos
- ✅ Diferentes resoluções de tela

## 🔒 Segurança
- ✅ Validação de entrada
- ✅ Prepared statements
- ✅ Sanitização de dados
- ✅ Controle de acesso
- ✅ Tokens seguros

## 📅 Próximas Atualizações
- [ ] Sistema de notificações
- [ ] Chat entre usuários
- [ ] App mobile
- [ ] Integração com APIs externas

## 📞 Suporte
Para suporte técnico ou dúvidas, entre em contato através do portal.

---

**Última atualização:** 27 de agosto de 2025
**Versão:** 2.0.0
**Desenvolvedor:** Pixel12 Digital
