# Apprumo

Sistema de gestao em PHP + MySQL para profissionais autonomos, com foco em agenda, financeiro, servicos, produtos, relatorios, onboarding e perfil publico.

## O que ja esta implementado

- Autenticacao por formulario na tela de login (`POST /auth/simple-login`) com CSRF e redirecionamento por perfil/status.
- Onboarding com criacao de vendor pendente e aprovacao manual no admin.
- Telas de pending, suspended e plan-expired com CTA para WhatsApp e logout.
- Painel admin com filtros por status, ativacao/suspensao/reativacao de vendors e CRUD de planos.
- Painel vendor com dashboard, agenda semanal, cadastro manual de agendamentos, fila de espera, servicos, produtos, financeiro, relatorios, clientes e configuracoes.
- Perfil publico em `/p/:slug`.
- Booking publico em `/book/:vendorSlug/:serviceId` com datas/slots disponiveis.
- Banco modelado para Hostinger em `database/schema.sql` e dados de demo em `database/seed.sql`.

## Estrutura

- `index.php`: front controller.
- `app/`: core, controllers, services e views.
- `assets/`: CSS, JS e logo SVG.
- `uploads/`: destino de imagens.
- `database/`: schema e seed.

## Como rodar localmente

1. Copie `.env.example` para `.env` e ajuste as credenciais.
2. Crie o banco MySQL.
3. Importe `database/schema.sql`.
4. Importe `database/seed.sql`.
5. Aponte o servidor web para a raiz do projeto.
6. Garanta que `uploads/` tenha permissao de escrita.

## Deploy na Hostinger

1. Envie todos os arquivos para `public_html` ou para a pasta raiz configurada do site.
2. Crie o banco MySQL no painel da Hostinger.
3. Em producao, importe apenas `database/schema.sql` pelo phpMyAdmin.
4. Use `database/seed.sql` somente em ambiente local/demo, nunca na base real.
5. Se o sistema ficar em uma subpasta, use `APP_URL` com o caminho completo. Para este projeto, o alvo de deploy e `https://jssistemasinteligentes.com/Apprumo`.
6. Crie um arquivo `.env` na raiz com os valores reais de `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` e `APP_URL`.
7. Preencha `ADMIN_EMAILS` com o e-mail real que deve entrar como administrador (separados por virgula se forem varios).
8. Confirme que o Apache esta com `mod_rewrite` ativo. O projeto ja inclui `.htaccess`.
9. Se a Hostinger servir a pasta mas ainda responder 404 nas rotas limpas, abra `.htaccess` e descomente `RewriteBase /Apprumo/`.
10. Garanta permissao de escrita na pasta `uploads/` e em `storage/`.


## Acesso demo

- Admin: use `/dev/login?email=admin@apprumo.local&role=admin` quando `APP_ENV` nao for `production`.
- Vendor ativo: use `/dev/login?email=demo@apprumo.local&role=vendor&status=active`.
- Vendor pendente: use `/dev/login?email=pending@apprumo.local&role=vendor&status=pending`.

## Login

- Em producao, use a pagina `/login`: e-mail, nome e perfil. Administradores precisam constar em `ADMIN_EMAILS` no `.env`.
- Em ambiente local (`APP_ENV` diferente de `production`), existem atalhos em `/dev/login?...` para testes.

## Observacoes

- O sistema foi desenhado com abordagem mobile-first.
- Os graficos foram implementados com barras em CSS/HTML para evitar dependencia externa no deploy compartilhado.
