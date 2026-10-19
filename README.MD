## Propovoice Banrisul Boletos
> Estado histórico da V12

- A V12 inicia a estrutura necessária para vincular uma Nota Fiscal Eletrônica, NFe, ao boleto correspondente.

- Nesta primeira etapa, o objetivo é preparar a estrutura de dados sem realizar upload, exposição ou envio de arquivos.

## Alterações da V12
- Preparação de colunas para armazenar os dados da NFe.
- Criação de método no repositório para atualizar o boleto correto.
- Preparação do vínculo entre a NFe e o registro local do boleto.
- Separação entre a estrutura de dados e o futuro processamento dos arquivos.
- Estado da implementação

> Nesta etapa:

- a estrutura de persistência está sendo preparada;
- a NFe deverá permanecer vinculada ao boleto correto;
- nenhum arquivo deve ser exposto publicamente;
- o upload ainda não é considerado concluído;
- o download protegido ainda não é considerado concluído;
- a página pública ainda não deve exibir a NFe.

## Próximas etapas planejadas
1. Criar o formulário Anexar NFe na tabela administrativa.
2. alidar o arquivo enviado.
3. Proteger o diretório utilizado para armazenar as notas.
4. Criar um endpoint de download protegido.
5. Validar a invoice e o token antes da entrega do arquivo.
6. Mostrar o botão da NFe na página pública.
7. Testar conjuntamente o boleto e a nota fiscal.

## Requisitos de segurança planejados
> A implementação futura do upload deverá considerar:

- permissão administrativa;
- nonce no formulário;
- validação do boleto selecionado;
- limite de tamanho;
- validação do tipo real do arquivo;
- nome de arquivo gerado pelo plugin;
- armazenamento protegido;
- bloqueio do acesso direto;
- download por endpoint controlado;
- tratamento da URL com token como informação privada;
- ausência de documentos reais no repositório.

## Dados que não integram o repositório
- arquivos XML reais de NFe;
- PDFs reais de notas fiscais;
- documentos de clientes;
- CPF ou CNPJ de clientes;
- chaves de acesso de NFe;
- payloads fiscais reais;
- arquivos armazenados na pasta de uploads.

* Autor: Bruno Elias Ferreira
* Licença: Este projeto é distribuído sob a licença GNU General Public License v2.0 ou posterior (GPL-2.0-or-later).

Copyright © 2026 Bruno Elias Ferreira.