# Propovoice Banrisul Boletos

Plugin interno para integrar invoices do PROPOVOICE à API de Cobrança do Banrisul, permitindo registrar boletos, emitir e armazenar PDFs, consultar situações bancárias e disponibilizar uma página pública protegida por token.

> **Uso interno:** este projeto foi desenvolvido para uso interno da empresa. Não é um plugin público, não possui suporte oficial de distribuição e deve ser revisado antes de qualquer implantação em outro site.

## Estado atual

- Versão do plugin: `0.1.0`
- PHP mínimo declarado: `8.2`
- Ambiente validado: `Sandbox`
- Homologação/Produção: pendente de credenciais e orientações do Banrisul
- Integração PROPOVOICE: validada
- WP-Cron: validado
- Download protegido de PDFs: validado em servidor compatível com `.htaccess`

## Funcionalidades

- Autenticação OAuth2 na API de Cobrança Banrisul.
- Leitura de invoices e contatos pela API pública do PROPOVOICE.
- Registro de boleto a partir de uma invoice.
- Emissão de boleto em PDF Base64.
- Armazenamento local do PDF.
- Consulta manual da situação do boleto.
- Consulta automática por WP-Cron em Produção.
- Página pública do boleto protegida por token.
- Download do PDF por handler protegido, sem acesso direto à pasta.
- Registro dos payloads de registro, emissão e consulta.
- Persistência dos dados de liquidação.
- Proteção contra emissão duplicada da mesma invoice em Produção.
- Aviso visual do ambiente ativo no painel.

## Estrutura principal

```text
propovoice-banrisul-boletos/
├── propovoice-banrisul-boletos.php
├── README.md
└── includes/
    ├── class-pbb-activator.php
    ├── class-pbb-admin.php
    ├── class-pbb-banrisul-api.php
    ├── class-pbb-boletos-repository.php
    ├── class-pbb-cron.php
    └── class-pbb-propovoice-api.php
```

Os PDFs são armazenados em:

```text
wp-content/uploads/banrisul-boletos/
```

A pasta recebe automaticamente:

```text
.htaccess
index.php
```

## Responsabilidade dos arquivos

### `propovoice-banrisul-boletos.php`

Bootstrap do plugin. Define constantes, carrega as classes e registra os hooks de ativação e desativação.

### `class-pbb-activator.php`

Cria ou atualiza a tabela do plugin com `dbDelta()` e agenda o evento WP-Cron.

### `class-pbb-admin.php`

Responsável por:

- menus e telas administrativas;
- configurações não secretas;
- geração de boleto por invoice;
- consulta manual;
- página pública por shortcode;
- salvamento do PDF;
- download protegido;
- mensagens e formatação visual;
- criação automática dos arquivos de proteção da pasta de PDFs.

### `class-pbb-banrisul-api.php`

Cliente da API Banrisul. Gerencia OAuth2 e chamadas de registro, emissão, consulta e demais operações implementadas.

### `class-pbb-propovoice-api.php`

Cliente da API pública do PROPOVOICE, autenticado com usuário WordPress e Application Password.

### `class-pbb-boletos-repository.php`

Camada de persistência da tabela personalizada. Insere, atualiza, consulta registros e preserva dados históricos de liquidação, PDF e payload de consulta.

### `class-pbb-cron.php`

Agenda e executa a sincronização automática de situações. A rotina retorna sem consultar o Banrisul enquanto o ambiente do plugin estiver em Sandbox.

## Dependências

- WordPress compatível com PHP 8.2.
- PHP 8.2 ou superior.
- Extensão cURL ou transporte HTTP funcional no WordPress.
- HTTPS ativo.
- Plugin PROPOVOICE com API pública disponível.
- Application Password do WordPress para autenticação na API do PROPOVOICE.
- Credenciais da API de Cobrança Banrisul.
- Permissão de escrita em `wp-content/uploads`.
- WP-Cron funcional ou cron real configurado para acionar o WordPress.

## Instalação

1. Copiar a pasta do plugin para:

   ```text
   wp-content/plugins/propovoice-banrisul-boletos/
   ```

2. Inserir as credenciais no `wp-config.php`, antes da linha de encerramento das edições.

3. Ativar o plugin no painel do WordPress.

4. Abrir **Banrisul Boletos > Configurações**.

5. Manter o ambiente em `Sandbox` durante os testes.

6. Preencher e salvar as configurações não secretas.

7. Confirmar os avisos de credenciais configuradas.

8. Gerar uma invoice de teste e validar registro, PDF, página pública e consulta.

## Credenciais no `wp-config.php`

Nunca grave Client Secret ou Application Password em `wp_options` nem em arquivos versionados.

Use placeholders como estes:

```php
define('PBB_BANRISUL_SANDBOX_CLIENT_ID', '');
define('PBB_BANRISUL_SANDBOX_CLIENT_SECRET', '');

define('PBB_PROPOVOICE_USERNAME', '');
define('PBB_PROPOVOICE_APP_PASSWORD', '');

define('PBB_BANRISUL_PRODUCAO_CLIENT_ID', '');
define('PBB_BANRISUL_PRODUCAO_CLIENT_SECRET', '');
```

Regras importantes:

- Não incluir valores reais neste README.
- Não enviar o `wp-config.php` para repositórios.
- Gerar uma Application Password exclusiva para esta integração.
- Revogar e substituir a Application Password se ela for exposta.
- Preencher as constantes de Produção somente após receber as credenciais oficiais.

## Configurações administrativas

As configurações não secretas permanecem no banco, por exemplo:

- ambiente;
- URL OAuth2;
- URL base da API;
- código do beneficiário;
- espécie;
- aceite;
- código de juros;
- autorização e código de pagamento parcial.

As credenciais secretas são apenas lidas das constantes do `wp-config.php`.

## Banco de dados

Tabela criada com o prefixo configurado no WordPress:

```text
{prefixo}propovoice_banrisul_boletos
```

Exemplo comum:

```text
wp_propovoice_banrisul_boletos
```

Principais grupos de campos:

- identificação da invoice e contato;
- nosso número, seu número, código de barras e linha digitável;
- valor nominal e vencimento;
- situações Banrisul, CIP e pagamento;
- caminho físico e URL legada do PDF;
- status local;
- payloads de registro, emissão e consulta;
- dados de liquidação;
- datas de criação e atualização.

Campos de liquidação:

```text
data_pagamento
data_credito
valor_pagamento
valor_cobrado
valor_creditado_debitado
```

O campo `pdf_url` é legado e não deve ser usado para criar links na interface. Os links devem ser calculados dinamicamente pelo handler protegido. O `pdf_path` é usado internamente para localizar o arquivo.

## Situações e status locais

Mapeamentos validados no Sandbox:

```text
Banrisul A + CIP A → aguardando_pagamento
Banrisul L         → pago
Banrisul B         → baixado
CIP R              → rejeitado_cip
```

Outros status locais usados:

```text
boleto_gerado
boleto_registrado_sem_pdf
boleto_sandbox_pdf_exemplo
```

Rótulos exibidos:

```text
aguardando_pagamento       → Aguardando pagamento
pago                       → Pago
baixado                    → Boleto baixado
rejeitado_cip              → Boleto rejeitado
boleto_gerado              → Boleto gerado
boleto_registrado_sem_pdf  → Boleto registrado, PDF indisponível
boleto_sandbox_pdf_exemplo → Ambiente de teste
```

## Comportamento do Sandbox

O Sandbox utiliza cenários simulados do Banrisul.

O plugin possui um fallback de PDF exemplo quando o título recém-registrado no Sandbox ainda não está disponível para emissão. Esse fallback não deve ser executado em Produção.

A consulta manual no Sandbox usa um código de barras de teste fixo. Não confundir esse código com o código de barras do registro real da invoice.

Cenários usados durante o desenvolvimento devem ser confirmados na documentação vigente antes de manutenção futura.

## Dados de liquidação

Quando o retorno inclui dados de operações, o plugin grava:

- data do pagamento;
- data do crédito;
- valor pago;
- valor cobrado;
- valor creditado ou debitado.

Esses campos são adicionados ao `UPDATE` somente quando vierem preenchidos. Consultas sem dados de liquidação e novas gerações no Sandbox não devem apagar o histórico já salvo.

Datas bancárias no formato `Y-m-d` são tratadas como datas civis, sem conversão de fuso horário, evitando exibição no dia anterior.

## WP-Cron

Hook:

```text
pbb_consultar_boletos_cron
```

Callback:

```text
PBB_Cron::consultar_boletos()
```

Periodicidade atual:

```text
hourly
```

Comportamento:

- na ativação, o evento é agendado se ainda não existir;
- na desativação, o evento é removido;
- em Sandbox, a rotina retorna sem consultar boletos;
- em Produção, consulta boletos elegíveis para sincronização.

O WP-Cron é acionado por acessos ao WordPress e pode executar com atraso em sites de baixo tráfego. Para maior previsibilidade em Produção, avaliar um cron real no servidor.

## Proteção dos PDFs

### Fluxo autorizado

```text
Página pública com invoice e token
→ endpoint admin-post.php
→ validação com hash_equals()
→ validação do caminho físico
→ entrega do PDF com readfile()
```

Ações registradas:

```text
admin_post_pbb_download_boleto
admin_post_nopriv_pbb_download_boleto
```

A segunda ação permite o download por clientes não autenticados, desde que possuam o token correto.

### Pasta protegida

Antes de salvar cada PDF, o plugin garante a existência de:

```text
wp-content/uploads/banrisul-boletos/.htaccess
wp-content/uploads/banrisul-boletos/index.php
```

Conteúdo esperado do `.htaccess`:

```apache
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>

<IfModule !mod_authz_core.c>
    Order Allow,Deny
    Deny from all
</IfModule>

Options -Indexes
```

Conteúdo esperado do `index.php`:

```php
<?php
// Silence is golden.
```

### Testes de segurança esperados

```text
URL protegida + token válido   → PDF abre
URL protegida + token inválido → acesso negado
URL direta do arquivo          → 403, 401 ou 404
```

### Atenção para Nginx

Nginx puro não processa `.htaccess`. Em servidores Nginx, o administrador deve criar uma regra equivalente na configuração do servidor, por exemplo:

```nginx
location ^~ /wp-content/uploads/banrisul-boletos/ {
    deny all;
    return 403;
}
```

A sintaxe exata deve ser validada pelo administrador da hospedagem antes da aplicação. Reiniciar ou recarregar o Nginx pode exigir acesso administrativo.

O arquivo `index.php` evita listagem simples da pasta, mas não bloqueia o acesso a um PDF cuja URL direta seja conhecida. Ele é apenas uma camada complementar.

### Teste após migração

Sempre que o site mudar de hospedagem ou servidor:

1. Gerar um boleto de teste.
2. Confirmar que o botão protegido abre o PDF.
3. Copiar a URL legada direta armazenada em `pdf_url`.
4. Confirmar que a URL direta retorna `401`, `403` ou `404`.
5. Alterar um caractere do token na URL protegida.
6. Confirmar que o plugin nega o acesso.

## Página pública

A página pública utiliza os parâmetros:

```text
invoice_id
token
```

O token salvo na invoice é comparado com `hash_equals()`.

A página apresenta:

- invoice;
- cliente;
- valor;
- vencimento;
- situação;
- linha digitável;
- botão protegido do PDF;
- dados de pagamento quando o status local for `pago`.

Não substituir o link protegido por `$boleto['pdf_url']`.

## Proteção contra duplicidade

Em Sandbox, gerar novamente a mesma invoice é permitido para testes.

Em Produção, se já existir código de barras para a invoice, uma nova emissão é bloqueada. O operador deve usar o boleto existente ou consultar sua situação.

## Ativação e desativação

### Ativação

- executa `PBB_Activator::activate()`;
- cria ou atualiza a tabela;
- agenda o WP-Cron se necessário.

### Desativação

- chama `PBB_Cron::unschedule()`;
- não apaga a tabela;
- não apaga boletos;
- não apaga PDFs;
- não apaga configurações;
- não remove constantes do `wp-config.php`.

Desativação não é desinstalação.

## Checklist para ativar em outro site

- [ ] WordPress e PHP atendem aos requisitos.
- [ ] PROPOVOICE e sua API pública estão funcionando.
- [ ] HTTPS está ativo.
- [ ] Application Password exclusiva foi criada.
- [ ] Constantes foram adicionadas ao `wp-config.php`.
- [ ] URLs e beneficiário do ambiente foram confirmados.
- [ ] Plugin foi ativado sem erro fatal.
- [ ] Tabela personalizada foi criada.
- [ ] Evento WP-Cron foi agendado.
- [ ] Pasta de PDFs possui `.htaccess` e `index.php`.
- [ ] URL direta do PDF foi bloqueada.
- [ ] Endpoint protegido abre o PDF com token válido.
- [ ] Token inválido bloqueia o download.
- [ ] Geração e consulta foram testadas em Sandbox.
- [ ] Backup foi realizado antes da homologação.

## Checklist antes da Produção

- [ ] Receber credenciais oficiais de Produção.
- [ ] Confirmar URL OAuth2 e URL base de Produção.
- [ ] Confirmar escopos OAuth2 exigidos.
- [ ] Confirmar Código de Beneficiário/convênio real.
- [ ] Confirmar procedimento e payload de homologação.
- [ ] Confirmar limites e frequência de consultas.
- [ ] Confirmar comportamento e autenticação do webhook.
- [ ] Confirmar interpretação oficial das situações bancárias.
- [ ] Preencher constantes de Produção no `wp-config.php`.
- [ ] Manter aviso visual de Produção ativo.
- [ ] Confirmar que o fallback de PDF do Sandbox não roda em Produção.
- [ ] Emitir um boleto controlado de homologação.
- [ ] Validar registro, PDF, pagamento, liquidação e crédito.
- [ ] Só automatizar a baixa da invoice no PROPOVOICE após validar uma liquidação real.

## Segurança

- Todos os arquivos PHP devem bloquear acesso direto com:

  ```php
  if (!defined('ABSPATH')) {
      exit;
  }
  ```

- Ações administrativas devem verificar `current_user_can('manage_options')`.
- Formulários devem usar nonce.
- Entradas devem ser sanitizadas.
- Saídas devem usar escaping adequado.
- Consultas SQL com valores variáveis devem usar APIs seguras do `$wpdb` ou queries preparadas.
- Tokens, Client Secrets e Application Passwords nunca devem aparecer em logs.
- O `wp-config.php` nunca deve ser compartilhado ou versionado.
- PDFs contêm dados pessoais e financeiros, portanto o acesso direto deve permanecer bloqueado.
- A URL com token deve ser tratada como informação privada.

## Logs e payloads

Os payloads são úteis para diagnóstico, mas podem conter dados pessoais. Evitar expô-los no painel para usuários sem necessidade administrativa.

Não registrar:

- Client Secret;
- access token OAuth2;
- Application Password;
- cabeçalho `Authorization`;
- conteúdo integral do `wp-config.php`.

## Problemas conhecidos e diagnóstico

### Plugin gera erro fatal na ativação

Verificar:

- existência de todos os arquivos carregados por `require_once`;
- ordem de carregamento das classes;
- chaves e fechamento de métodos;
- nomes exatos das propriedades e constantes;
- versão do PHP;
- `wp-content/debug.log`.

### Credenciais aparecem como não configuradas

Verificar se as constantes existem e não estão vazias no `wp-config.php`. Não duplicar constantes.

### OAuth ou PROPOVOICE falha

Verificar:

- HTTPS;
- URLs configuradas;
- usuário da Application Password;
- senha revogada ou rotacionada;
- cabeçalho `Authorization` preservado pela hospedagem.

### PDF indisponível no Sandbox

Verificar `payload_emissao`. O Sandbox pode retornar título inexistente para o código recém-registrado e o fallback pode sofrer timeout. O timeout de emissão foi ampliado para acomodar respostas lentas do PDF exemplo.

### Botão PDF mostra `-`

Verificar se o registro possui `pdf_path` e se a URL protegida é criada dentro do mesmo `foreach` da tabela administrativa.

### Botão PDF retorna 403

O link da interface provavelmente está usando `$boleto['pdf_url']`. Trocar pelo endpoint calculado com `admin-post.php`, invoice e token.

### URL direta não retorna 403

Possíveis causas:

- servidor Nginx puro;
- `.htaccess` ignorado;
- `AllowOverride` desabilitado;
- regra incompatível com a hospedagem;
- cache ou CDN servindo o arquivo.

Solicitar uma regra equivalente ao administrador do servidor.

### Datas aparecem um dia antes

Não aplicar conversão de fuso a datas bancárias sem horário. Usar leitura estrita de `Y-m-d` e exibição `d/m/Y`.

### WP-Cron não executa no horário exato

WP-Cron depende de requisições ao site. Em Produção, avaliar cron real e monitorar o hook `pbb_consultar_boletos_cron`.

## Backups e versionamento

Antes de qualquer alteração:

- fazer backup da pasta do plugin;
- fazer backup do banco;
- guardar uma cópia privada do `wp-config.php`;
- nunca incluir segredos no repositório;
- registrar mudanças em changelog ou commits claros.

Sugestão de tags internas:

```text
v0.1.0-sandbox-validado
v0.2.0-homologacao
v1.0.0-producao
```

## Próximas evoluções sugeridas

- Autoteste visual da proteção da URL direta do PDF.
- Integração do webhook após confirmação oficial de payload e autenticação.
- Registro da origem da atualização: manual, cron ou webhook.
- Atualização automática da invoice PROPOVOICE somente após homologação real.
- Remoção futura da coluna legada `pdf_url` após migração controlada.
- Armazenamento dos PDFs fora da raiz pública, quando a hospedagem permitir.
- Logs administrativos com retenção e mascaramento de dados pessoais.
- Testes automatizados de unidade e integração.

## Referências oficiais

- [Documentação da API de Cobrança Banrisul](https://developers.banrisul.com.br/pages/docs/clientes-banrisul/api-cobranca-v1.html)
- [Application Passwords do WordPress](https://developer.wordpress.org/advanced-administration/security/application-passwords/)
- [WP-Cron no WordPress](https://developer.wordpress.org/plugins/cron/)
- [Agendamento do WP-Cron](https://developer.wordpress.org/plugins/cron/understanding-wp-cron-scheduling/)
- [Configuração Apache e .htaccess no WordPress](https://developer.wordpress.org/advanced-administration/server/web-server/httpd/)
- [Documentação do Nginx](https://nginx.org/en/docs/)

## Aviso final

Este plugin realiza operações financeiras e trata dados pessoais. Qualquer alteração deve ser testada primeiro em Sandbox ou homologação, com backup disponível. Nunca testar mudanças diretamente em Produção sem revisar credenciais, ambiente, beneficiário, payloads, proteção dos PDFs e comportamento de liquidação.


* Autor: Bruno Elias Ferreira
* Licença: Este projeto é distribuído sob a licença GNU General Public License v2.0 ou posterior (GPL-2.0-or-later).

Copyright © 2026 Bruno Elias Ferreira.