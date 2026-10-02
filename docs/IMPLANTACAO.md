# Implantação e Supabase

Laravel controla autenticação, autorização e acesso PostgreSQL pelo backend. Não há chaves Supabase no navegador nem dependência de Supabase Auth.

Compose executa Apache/PHP, PostgreSQL e scheduler. A imagem publica assets Filament sem Node. O Dockerfile está disponível; o ambiente desta entrega não possui Docker para construir a imagem localmente.

## Supabase pendente

A conta precisa ser conectada pelo cartão da tarefa. Após conexão, identificar o projeto autorizado, confirmar o banco destinado à aplicação e configurar PostgreSQL com TLS:

    DB_CONNECTION=pgsql
    DB_HOST=<host PostgreSQL ou session pooler>
    DB_PORT=5432
    DB_DATABASE=postgres
    DB_USERNAME=<usuário do projeto>
    DB_PASSWORD=<segredo por canal seguro>
    DB_SSLMODE=require

Compose de desenvolvimento sobrescreve DB_HOST=db. Para Supabase, implante aplicação/scheduler com as variáveis hospedadas, sem essa sobrescrita.

**Não execute migrations em projeto compartilhado antes de conferir objetos existentes e exposição pela Data API.** As tabelas contêm usuários e custos. O ambiente final precisa de schema privado não exposto na Data API ou configuração de acesso revisada para esse projeto.

As migrations não alteram RLS nem revogam privilégios. Essa operação automática foi retirada após rejeição da revisão de aprovação por possível impacto em outros acessos. Configuração remota, isolamento e verificação da Data API ainda não foram executados. Nenhum banco hospedado foi criado/alterado.

## Produção

- APP_ENV=production, APP_DEBUG=false, APP_URL HTTPS e SESSION_SECURE_COOKIE=true.
- Preserve APP_KEY; não regenere após iniciar operação.
- Configure TLS/proxy. Não publique o banco pelo Compose.
- Anexos em storage/app/private têm download autenticado. Use volume compartilhado ou storage privado central antes de escalar réplicas.
- Preserve volumes; configure backup e teste restauração.
- Rode migrations uma vez por implantação e mantenha scheduler ativo.
- Cadastre usuários reais pelo administrador. Não carregue demonstração em produção.
- Login Filament limita tentativas; cadastro público não está habilitado.

## Referências oficiais

- [Laravel 12](https://laravel.com/docs/12.x/installation)
- [Filament 5](https://filamentphp.com/docs/5.x/introduction/installation)
- [Supabase PostgreSQL](https://supabase.com/docs/guides/database/connecting-to-postgres)
