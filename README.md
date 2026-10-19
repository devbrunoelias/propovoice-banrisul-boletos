## Proteção de PDFs validada na V9
- A proteção dos PDFs dos boletos é composta por duas camadas:

1. O cliente acessa o PDF por um endpoint protegido por token.
2. O servidor bloqueia o acesso HTTP direto à pasta onde o PDF está armazenado.

Página pública com invoice e token
    ↓
Endpoint protegido do plugin
    ↓
Validação da invoice e do token
    ↓
Entrega controlada do PDF

* Autor: Bruno Elias Ferreira
* Licença: Este projeto é distribuído sob a licença GNU General Public License v2.0 ou posterior (GPL-2.0-or-later).

Copyright © 2026 Bruno Elias Ferreira.