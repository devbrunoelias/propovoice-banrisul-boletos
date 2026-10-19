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