# Propovoice Banrisul Boletos
Plugin WordPress desenvolvido para integrar invoices do Propovoice ao sistema de cobrança do Banrisul.

O plugin permite gerar, registrar, consultar e disponibilizar boletos bancários por meio de um portal público protegido por token.

## Funcionalidades
1. integração com invoices do Propovoice;
2. emissão de boletos pelo Banrisul;
3. ambientes Sandbox e Produção;
4. consulta manual do status bancário;
5. atualização periódica da situação dos boletos;
6. geração de links públicos protegidos por token;
7. download protegido do PDF do boleto;
8. vinculação e disponibilização de nota fiscal;
9. suporte a diferentes empresas emissoras;
10. controle do prazo de baixa automática;
11. tratamento visual dos principais status bancários;
12. portal público responsivo para desktop e dispositivos móveis;
13. linha digitável com botão para cópia;
14. FAQ com orientações de pagamento e resolução de problemas;
15. exibição do prazo para pagamento após o vencimento;
16. aviso para solicitação de um novo boleto após a baixa;
17. logotipos distintos para cabeçalho e rodapé.

# Portal público de cobrança
Cada cobrança pode possuir um link exclusivo semelhante a:

https://exemplo.com/banrisul-boleto/?invoice_id=1234&token=TOKEN

# O endereço utiliza:

ID da invoice;
token exclusivo associado à cobrança.

O token funciona como uma chave de acesso ao portal. O endereço completo não deve ser publicado nem compartilhado com terceiros.

No portal, o cliente pode visualizar:

- número da invoice;
- situação da cobrança;
- nome e documento do cliente;
- valor do boleto;
- data de vencimento;
- beneficiário;
- prazo de baixa automática;
- linha digitável;
- PDF do boleto;
- nota fiscal, quando disponível;
- resumo da cobrança;
- orientações de segurança;
- dúvidas frequentes e soluções de problemas.

# Status dos boletos

O portal possui tratamento visual para status como:

- aguardando pagamento;
- boleto gerado;
- boleto registrado;
- pagamento identificado;
- pago;
- crédito retido;
- boleto baixado;
- baixado por pagamento;
- baixado por devolução;
- rejeitado;
- protestado;
- em cartório;
- negativado;
- devolução em processamento;
- ambiente de teste.

A aparência e a mensagem apresentadas ao cliente variam de acordo com a situação armazenada para o boleto.

# Baixa automática

Durante a geração do boleto, é possível informar após quantos dias do vencimento o registro deverá ser baixado ou devolvido automaticamente.

O portal público utiliza o valor salvo no boleto para informar ao cliente:

- que existe um prazo adicional após o vencimento;
- quando ocorrerá a baixa automática;
- que um novo boleto deverá ser solicitado após o encerramento desse prazo.

O prazo não deve ser fixado diretamente no template, pois pode variar entre cobranças.

# Nota fiscal

A nota fiscal pode ser vinculada ao boleto correspondente.

Quando disponível, o portal apresenta um botão para abertura ou download do documento. Quando não houver nota fiscal vinculada, o portal informa que o documento ainda não está disponível.

# Estrutura principal do Plugin
propovoice-banrisul-boletos/
├── assets/
│   ├── css/
│   │   └── pbb-public-modern.css
│   ├── images/
│   │   ├── README.md
│   │   ├── logo-catania-studio.png
│   │   └── logo-catania-spweb-colorido.png
│   └── js/
│       └── pbb-public-modern.js
├── docs/
├── includes/
│   ├── class-pbb-admin.php
│   ├── class-pbb-activator.php
│   ├── class-pbb-boletos-repository.php
│   ├── class-pbb-banrisul-api.php
│   ├── class-pbb-cron.php
│   ├── class-pbb-empresas-config.php
│   ├── class-pbb-propovoice-api.php
|   └── class-pbb-webhook.php
├── templates/
│   └── public-boleto-modern.php
├── LICENSE
├── README.md
└── propovoice-banrisul-boletos.php
A estrutura exata da pasta includes pode variar conforme a versão do plugin.

# Configuração dos logotipos

Os logotipos reais utilizados em produção não estão incluídos neste repositório.

Antes de utilizar o portal público, adicione:
assets/images/logo-catania-studio.png
assets/images/logo-catania-spweb-colorido.png

O plugin utiliza:

logo-catania-studio.png no cabeçalho;
logo-catania-spweb-colorido.png no rodapé.

consulte - assets/images/README.md

### Página pública

Crie uma página no WordPress contendo o shortcode:
[banrisul_boleto]

Para que o layout fique 100% responsivo, deixe esse shortcode bem ao topo da página e com 0 de margin e padding na seção onde o Shortcode foi inserido;
O endereço dessa página deve ser configurado no plugin como a página pública utilizada para os links das cobranças.

A página não deve ser adicionada a menus públicos nem utilizada sem os parâmetros de invoice e token.

# INSTALAÇÃO 
1. Faça backup dos arquivos e do banco de dados do WordPress.

2. Envie a pasta do plugin para:
wp-content/plugins/propovoice-banrisul-boletos/

3. Adicione os logotipos necessários em:
assets/images/

4. Ative o plugin no painel do WordPress.
5. Configure o ambiente desejado.
6. Informe as credenciais e os dados do beneficiário.
7. Configure a página que contém o shortcode público.
8. Valide a integração em Sandbox.
9. Somente depois da validação, configure o ambiente de Produção.

# Ambientes

O plugin suporta ambientes separados:
- Sandbox;
- Produção.

Antes de emitir ou consultar boletos, confirme o ambiente selecionado nas configurações.

Nunca utilize credenciais de Produção em instalações públicas de teste ou em arquivos versionados.

# Credenciais

Dados sensíveis devem ser armazenados fora dos arquivos versionados, preferencialmente por meio de constantes no wp-config.php ou de outro mecanismo seguro definido para a instalação.

Nunca versione:

Client ID real;
Client Secret;
certificado privado;
senha;
token bancário;
credenciais do Propovoice;
credenciais do Banrisul;
tokens dos links públicos;
dados pessoais de clientes;
PDFs de boletos;
notas fiscais reais.

O arquivo wp-config-example.php, contém somente valores fictícios ou marcadores de exemplo.

## Segurança

O portal público foi projetado para exigir um token associado à invoice.

Recomendações:

- utilize HTTPS;
- preserve tokens longos e não previsíveis;
- não publique os links completos;
- não registre tokens completos em logs;
- não envie tokens para ferramentas de análise;
- mantenha o plugin e o WordPress atualizados;
- restrinja o painel administrativo a usuários autorizados;
- faça backup antes de atualizações;
- valide token, invoice e documento antes de disponibilizar arquivos;
- utilize mensagens genéricas quando o acesso for inválido;
- não revele se uma invoice existe após uma tentativa inválida.

### Cache

A página pública contém informações específicas de cada cobrança.

Evite configurar cache público ou compartilhado para:

1. a página protegida;
2. o PDF do boleto;
3. a nota fiscal;
4. respostas que dependam do token.

Após alterações no template, CSS ou JavaScript, limpe:

- cache do WordPress;
- cache da hospedagem;
- cache da CDN;
- cache do navegador.

### Solução de problemas

1. O portal abriu sem estilos

Confirme a existência de:
assets/css/pbb-public-modern.css

Depois limpe os caches.

2. Os botões ou o FAQ não funcionam

Confirme a existência de:
assets/js/pbb-public-modern.js

Verifique também o console do navegador.

3. Os logotipos não aparecem

Confirme:
assets/images/logo-catania-studio.png
assets/images/logo-catania-spweb-colorido.png

Os nomes devem ser idênticos aos esperados pelo template.

4. O acesso é recusado

Confirme se:

- a invoice existe;
- o token está completo;
- o token pertence à invoice correta;
- o link não foi editado;
- o registro permanece disponível.

5. O PDF não abriu

Verifique se o navegador bloqueou uma nova guia, pop-up ou download. Teste também em uma janela anônima ou privativa.

### Licença

Consulte o arquivo:
LICENSE
para verificar os termos de uso, distribuição e modificação deste projeto.

# Aviso

Este projeto realiza integração com sistemas externos e serviços bancários. Antes de qualquer utilização em Produção, valide:

- credenciais;
- ambiente;
- dados do beneficiário;
- datas;
- valores;
- regras de baixa;
- documentos disponibilizados;
- segurança do token;
- política de cache;
- comportamento dos links públicos.

Mantenha sempre uma cópia de segurança funcional antes de realizar alterações em produção.