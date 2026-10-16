# Propovoice Banrisul Boletos

## 🚀 Pra que serve
Integra invoices(Faturas) do Plugin PROPOVOICE PRO com a API de Cobrança Banrisul para geração de boletos.

> Estrutura inicial:

**O que já está funcionando:**

1. O plugin aparece no painel do WordPress.

2. A tela Configurações salva os dados corretamente.

3. A tela Gerar por Invoice busca a invoice de teste. *Boleto de exemplo para testes no Sandbox Banrisul*

4. O plugin busca o cliente vinculado no PROPOVOICE.

5. Monta o payload para o Banrisul.

6. Registra o boleto no sandbox.

7. Salva os dados do boleto na tabela.

8. Cria a pasta:
/wp-content/uploads/banrisul-boletos/

### Pré-requisitos
* Requisito 1 (PHP 8.2+)
* Requisito 2 (Wordpress 7.0.4 ou Versão 7.1.3) Em outras versões não foi feito teste.
* Requisito 3 (Plugin Propovoice Pro - All-in-One Client Management System)

### Instalação
1. Baixe os arquivos do plugin.
2. Ative no painel do seu sistema.

## 🛠️ Como Usar
↓ Estrutura de arquivos ↓

↓ Dentro de Public_html > wp-content > plugins Criar a pasta ↓
    **propovoice-banrisul-boletos e subir o arquivo ↓** 
        **propovoice-banrisul-boletos.php**

> Depois dentro da pasta propovoice-banrisul-boletos/ criar a pasta ↓ 

includes/
    **class-pbb-activator.php**
    **class-pbb-admin.php**
    **class-pbb-propovoice-api.php**
    **class-pbb-banrisul-api.php**
    **class-pbb-boletos-repository.php**

## ⚙️ Configurações

>Fluxo Geral< ↓

    propovoice-banrisul-boletos.php
    ↓
    carrega classes
    ↓
    class-pbb-admin.php
    ↓
    busca invoice com class-pbb-propovoice-api.php
    ↓
    busca cliente com class-pbb-propovoice-api.php
    ↓
    registra/emite boleto com class-pbb-banrisul-api.php
    ↓
    salva resultado com class-pbb-boletos-repository.php
    ↓
    PDF salvo em /uploads/banrisul-boletos/

>/Fluxo Geral< ↑
    

1. propovoice-banrisul-boletos.php
    >É o arquivo principal do plugin.<
    **Ele faz:**
    1. identifica o plugin para o WordPress;
    2. define constantes como caminho da pasta do plugin;
    3. carrega os arquivos da pasta includes;
    4. registra o hook de ativação;
    5. inicializa o admin do plugin.

2.  includes/class-pbb-activator.php   
    >É executado quando o plugin é ativado.<
    **Ele Faz:**
    1. cria a tabela própria do plugin no banco de dados;
    2. essa tabela guarda os vínculos entre invoices do PROPOVOICE e boletos Banrisul.

    >Exemplo do que ele prepara para salvar.<
    1. ID da invoice;
    2. cliente;
    3. linha digitável;
    4. código de barras;
    5. PDF;
    6. status do boleto;
    7. payloads de retorno.

3.  includes/class-pbb-admin.php
    >É o arquivo mais importante da parte visual/admin.<
    >Em resumo: é o “controlador” do fluxo dentro do painel WordPress.<
    **Ele faz:**
    1. cria o menu lateral Banrisul Boletos;
    2. cria a tela Configurações;
    3. cria a tela Gerar por Invoice;
    4. salva as configurações do plugin;
    5. recebe o ID da invoice;
    6. busca invoice e cliente;
    7. monta o payload do Banrisul;
    8. chama a API Banrisul;
    9. salva o boleto;
    10. exibe a tabela “Últimos boletos gerados”.

4.  includes/class-pbb-propovoice-api.php
    >É responsável por conversar com a API do PROPOVOICE.<
    >Em resumo: é a ponte entre o plugin e o PROPOVOICE.<
    **Ele faz:**
    1. autentica usando usuário WordPress + Application Password;
    2. busca a lista de invoices;
    3. busca dados completos do contato/cliente;
    4. retorna os dados para o class-pbb-admin.php.

5.  includes/class-pbb-banrisul-api.php
    >É responsável por conversar com a API de Cobrança do Banrisul.<
    >Em resumo: é a ponte entre o plugin e o Banrisul.<
    **Ele faz:**
    1. gera token OAuth2;
    2. registra boleto;
    3. emite boleto;
    4. consulta boleto;
    5. baixa boleto;
    6. envia headers como:
        - Authorization;
        - bergs-beneficiario;
        - bergs-ambiente;
    7. trata erros de retorno da API Banrisul.

6.  includes/class-pbb-boletos-repository.php
    >É responsável por salvar e buscar boletos no banco de dados.<
    >Em resumo: é a camada de banco de dados do plugin.<
    **Ele faz:**
    1. salva os dados do boleto gerado;
    2. atualiza boleto existente da mesma invoice;
    3. lista boletos recentes;
    4. atualiza status quando houver consulta futura;
    5. guarda payloads de registro, emissão e consulta.

* Autor: Bruno Elias Ferreira
* Licença: Este projeto é distribuído sob a licença GNU General Public License v2.0 ou posterior (GPL-2.0-or-later).

Copyright © 2026 Bruno Elias Ferreira.