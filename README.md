# CMF Maintenance Enterprise

Gestão de manutenção de equipamentos pesados em **Laravel 12, Filament 5, PHP 8.4 e PostgreSQL 17**, com interface em português, tema escuro e destaque laranja.

## Funcionalidades

- Autenticação e cinco perfis: Operador, Mecânico, Controlador, Gerente e Administrador.
- Equipamentos, contratos, centros de custo, localizações e QR Code com acesso autenticado.
- Solicitações em lista e Kanban; criação automática de OS; análise, prioridade, designação e cronograma.
- Execução, diagnóstico, causa raiz, peças, mão de obra, fotos antes/depois, encerramento e timeline.
- Estoque transacional, saldos por localização, transferência parcial, inventário, ajustes e custo médio ponderado.
- Preventivas por horímetro, quilometragem ou calendário; alertas e geração sem duplicidade.
- Componentes e pneus com histórico e horas acumuladas; SOS com laudos e tendência.
- Dashboard, disponibilidade, MTBF/MTTR, custos, backlog e PDF/XLSX.
- Assinatura por imagem associada ao usuário, perfil, data e hash da OS.
- Auditoria, arquivos privados, permissões no servidor e testes automatizados.

[Requisitos originais](docs/REQUISITOS.md) · [Operação](docs/OPERACAO.md) · [Implantação / Supabase](docs/IMPLANTACAO.md) · [Validação](docs/VALIDACAO.md)

## Instalação com Docker

1. Copie .env.example para .env e defina DB_PASSWORD.
2. Execute **docker compose build**.
3. Gere APP_KEY: **docker compose run --rm --no-deps app php artisan key:generate --show**. Copie o resultado para APP_KEY no .env.
4. Execute:

    docker compose up -d
    docker compose exec app php artisan migrate --force
    docker compose exec app php artisan maintenance:admin admin@empresa.com "Administrador"

O comando solicita a senha interativamente. Acesse **http://localhost:8080/admin**. O serviço scheduler executa preventivas; banco e anexos usam volumes persistentes.

Para demonstração, com APP_ENV=local, defina DEMO_PASSWORD no .env:

    docker compose up -d --force-recreate
    docker compose exec app php artisan db:seed

Contas: admin@cmf.example, operador@cmf.example, mecanico@cmf.example, controlador@cmf.example e gerente@cmf.example. Todas usam DEMO_PASSWORD. A carga inclui 25 equipamentos, peças, componentes, pneus, SOS e ordens em diferentes etapas. As pequenas imagens são marcadores de demonstração, não fotografias reais.

## Desenvolvimento sem Docker

Requer PHP 8.4, Composer e extensões intl, mbstring, pdo_pgsql, pdo_sqlite (testes), zip, dom, xml, fileinfo, curl, iconv e gd.

    cp .env.example .env
    composer install
    php artisan key:generate

Configure o PostgreSQL no .env, então:

    php artisan migrate
    php artisan maintenance:admin admin@empresa.com "Administrador"
    php artisan serve

Execute **php artisan schedule:work** em outro terminal. Não é necessário Node/Vite; Filament publica seus próprios assets.

## Testes

    php artisan test
    vendor/bin/pint --test
    composer audit

A suíte padrão usa SQLite em memória. Para PostgreSQL, forneça DB_* de um banco **exclusivo de testes**: a suíte recria tabelas. O workflow GitHub Actions prepara PostgreSQL 17.

## Situação

Código e configuração PostgreSQL disponíveis. **Supabase hospedado pendente da conexão da conta**. Não há publicação em servidor de produção nesta entrega. A matriz de validação distingue o que foi testado do que depende do ambiente final.
