# Garion Projetos - Technical SEO Toolkit

Plugin WordPress independente de SEO técnico, auditoria e manutenção, com interoperabilidade opcional com outros plugins de SEO (Rank Math e Yoast).

**Versão atual:** 0.6.2 · **Requer WordPress:** 6.0+ · **Requer PHP:** 8.1+

## Autoria e independência

O código deste plugin é uma implementação original da Garion Projetos. O Rank Math foi analisado apenas como referência de boas práticas de produto, segurança, desempenho e convivência com o ecossistema WordPress.

Nenhum arquivo, classe, função, algoritmo, texto, interface, asset ou trecho de código-fonte do Rank Math foi copiado, adaptado ou redistribuído. Quando o Rank Math está instalado, a comunicação ocorre exclusivamente por hooks públicos e pelas APIs do WordPress. O toolkit não depende do Rank Math e funciona normalmente sem ele.

## Principais recursos

- **Redirecionamentos** 301, 302, 307 e 308, com prevenção de loops, contador de acessos atômico e importação/exportação CSV.
- **Monitor de 404** com retenção automática de 90 dias; pausa automaticamente quando o monitor 404 do Rank Math está ativo.
- **Scanner de links quebrados** assíncrono, protegido contra SSRF (`wp_safe_remote_head()`/`wp_safe_remote_get()`), executado em pequenos lotes e iniciado diariamente pelo WP-Cron.
- **Auditoria de SEO on-page** com motor extensível de checks independentes (título, meta description, imagem destacada e indexabilidade, hoje), pontuação ponderada por severidade com limite de penalidade por categoria, e histórico persistente.
- **Gestão de problemas (Issues)**: busca, filtros, ordenação, paginação, detalhes com evidência e orientação de correção, e ações de ciclo de vida (ignorar/reabrir).
- **Execuções assíncronas retomáveis** via WP-Cron, com lock contra concorrência, heartbeat, cancelamento e recuperação após timeout.
- **Sitemap XML** próprio em `/sitemap.xml`, desativado automaticamente quando o módulo de sitemap do Rank Math está ativo.
- **Schema.org (dados estruturados)** próprio, desativado quando o módulo Schema do Rank Math está ativo.
- **Canonical, meta description e robots** com overrides por conteúdo, mescláveis com as diretivas do Rank Math.
- **Open Graph e Twitter Cards** com overrides por conteúdo e preview ao vivo no metabox do editor.
- **Regras extras de `robots.txt`.**
- **Exportação** de auditorias e problemas em JSON/CSV.
- **Endpoints REST** protegidos por capacidade administrativa e nonce.
- **Traduções completas** do painel administrativo: inglês (padrão), português do Brasil, espanhol, russo e chinês simplificado (283 strings cada), aplicadas automaticamente conforme o idioma do site ou do usuário.

## Interoperabilidade opcional com Rank Math

Desde a versão 0.4.0, o toolkit detecta o Rank Math e evita saídas duplicadas:

| Recurso | Com Rank Math ativo |
| --- | --- |
| Canonical e meta description | Overrides do toolkit são enviados aos filtros oficiais do Rank Math. |
| Meta robots | `noindex` e `nofollow` do toolkit são mesclados com as diretivas do Rank Math. |
| Open Graph e Twitter | Overrides sociais são aplicados às tags geradas pelo Rank Math. |
| Schema | A saída própria é desativada quando o módulo Schema do Rank Math está ativo. |
| Sitemap | O sitemap próprio é desativado quando o módulo Sitemap do Rank Math está ativo. |
| `robots.txt` | Regras extras são preservadas sem repetir a linha do sitemap. |
| Monitor 404 | O registro próprio pausa quando o monitor do Rank Math está ativo, evitando gravação duplicada. |
| Redirecionamentos | Continuam disponíveis; regras do toolkit têm prioridade para os caminhos cadastrados nele. |
| Links quebrados e auditoria | Permanecem ativos como recursos complementares. |

Os campos do metabox do toolkit continuam editáveis. Quando o Rank Math está ativo, eles funcionam como overrides de frontend e não imprimem um segundo conjunto de tags. Há também um provider dedicado para metadados do **Yoast SEO**, usado pelo motor de auditoria para ler título/descrição já preenchidos por ele.

## Painel administrativo

Menu **Technical SEO** com as seguintes telas:

- **Visão geral** — indicadores de saúde de SEO do site.
- **Auditorias** — histórico de execuções, progresso, comparação entre auditorias e exportação.
- **Problemas** — lista consolidada de issues com filtros, detalhes e ações de ciclo de vida.
- **Conteúdos** — auditoria e problemas por post/página individual.
- **Redirecionamentos** — CRUD com importação/exportação CSV.
- **Monitor 404** — hits registrados com referrer e contagem.
- **Links quebrados** — status do scanner e disparo de varredura manual.
- **Configurações** — retenção de dados e demais preferências.

## Segurança e desempenho

- O scanner usa `wp_safe_remote_head()` e `wp_safe_remote_get()` para bloquear requisições a destinos inseguros ou redes privadas.
- URLs relativas são normalizadas antes da verificação.
- Cada conteúdo verifica no máximo 100 links por ciclo.
- Resultados antigos são substituídos a cada nova leitura, evitando falsos positivos persistentes.
- A varredura automática completa inicia uma vez por dia e processa pequenos lotes em segundo plano.
- Redirecionamentos recusam protocolos não HTTP(S) e loops para a própria URL.
- Endpoints REST exigem capacidade administrativa e nonce válido.
- Atualizações de versão executam migrações de tabelas automaticamente (`GPSEO_DB_VERSION`).
- Retenção configurável e remoção de dados somente mediante consentimento explícito na desinstalação.
- Este plugin não envia dados do site a serviços externos; o scanner de links quebrados apenas acessa URLs encontradas no próprio conteúdo publicado para verificar a resposta HTTP.

## Requisitos

- WordPress 6.0 ou superior.
- PHP 8.1 ou superior.
- Rank Math e Yoast SEO são opcionais.

## Instalação

1. Envie a pasta `garion-projetos-technical-seo-toolkit` para `/wp-content/plugins/`.
2. Ative o plugin em **Plugins**.
3. Abra **Technical SEO** no painel.
4. Quando usar Rank Math, mantenha os módulos desejados ativos; o toolkit detectará a configuração automaticamente.

## Estrutura

```text
garion-projetos-technical-seo-toolkit/
├── admin/                      # Telas do painel e metabox do editor
├── assets/                     # CSS/JS do admin
├── docs/                       # Documentação técnica
├── includes/
│   ├── audit/
│   │   └── checks/             # Checks de auditoria (título, descrição, imagem, indexabilidade)
│   └── providers/               # Providers de metadados (Toolkit, WordPress, Rank Math, Yoast)
├── languages/                  # Traduções (.po/.mo) e template .pot
├── tests/                      # Testes PHPUnit
├── garion-projetos-technical-seo-toolkit.php
├── readme.txt                  # readme padrão WordPress.org, com o changelog completo
└── uninstall.php
```

## Documentação técnica

- [Arquitetura da Fase 1](docs/phase-1-architecture.md)
- [Tabelas](docs/database.md)
- [API REST](docs/rest-api.md)
- [Hooks públicos](docs/hooks.md)
- [Testes e diagnóstico](docs/testing.md)
- [Riscos e roteiro das próximas fases](docs/implementation-roadmap.md)

## Changelog

O histórico completo de versões fica em [`readme.txt`](readme.txt#L76). Destaques recentes:

- **0.6.2** — Padronização final dos comentários `phpcs:ignore` nas queries de escrita do repositório de auditoria.
- **0.6.1** — Correções de preparo de SQL (`$wpdb->prepare()`) e de posicionamento dos comentários de exceção do PHPCS.
- **0.6.0** — **Breaking change:** todos os hooks públicos foram renomeados para o prefixo `gpseo_` (antes `garion_technical_seo/...`). Também corrige os últimos erros do WordPress Plugin Check.
- **0.5.10 – 0.5.5** — Correções de segurança e padrões de código no WordPress Plugin Check (escaping, nonces, comentários de tradutor).
- **0.5.8** — Traduções completas do admin para pt_BR, es_ES, ru_RU e zh_CN.
- **0.5.3** — Redesenho do painel administrativo (dashboard, badges, breadcrumbs, tabelas responsivas).
- **0.5.0 – 0.5.1** — Fundação do motor de auditoria assíncrono e gestão completa de problemas (issues), pontuação explicável e endpoints REST de auditoria.
- **0.4.0 – 0.4.1** — Interoperabilidade opcional com Rank Math, hardening do scanner de links (SSRF) e migrações automáticas de banco.

> ⚠️ Ao atualizar a partir de uma versão anterior à 0.6.0, ajuste qualquer código próprio que dependa dos hooks antigos `garion_technical_seo/...` para os novos nomes `gpseo_...`.
