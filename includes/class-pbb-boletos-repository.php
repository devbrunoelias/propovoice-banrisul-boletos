<?php

if (!defined('ABSPATH')) {
    exit;
}

class PBB_Boletos_Repository
{
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'propovoice_banrisul_boletos';
    }

    public function save(array $data): int
    {
        global $wpdb;

        $invoice_id = absint($data['propovoice_invoice_id'] ?? 0);

        if (!$invoice_id) {
            throw new Exception('ID da invoice ausente ao salvar boleto.');
        }

        $existing = $this->get_by_invoice_id($invoice_id);

        $row = [
            'propovoice_invoice_id' => $invoice_id,
            'propovoice_invoice_num' => sanitize_text_field((string)($data['propovoice_invoice_num'] ?? '')),
            'propovoice_invoice_token' => sanitize_text_field((string)($data['propovoice_invoice_token'] ?? '')),
            'propovoice_contact_id' => absint($data['propovoice_contact_id'] ?? 0),
            'empresa_id' => sanitize_key((string)($data['empresa_id'] ?? '')),
            'beneficiario_codigo' => sanitize_text_field((string)($data['beneficiario_codigo'] ?? '')),
            'client_name' => sanitize_text_field((string)($data['client_name'] ?? '')),
            'client_document' => sanitize_text_field((string)($data['client_document'] ?? '')),
            'client_email' => sanitize_email((string)($data['client_email'] ?? '')),
            'nosso_numero' => sanitize_text_field((string)($data['nosso_numero'] ?? '')),
            'seu_numero' => sanitize_text_field((string)($data['seu_numero'] ?? '')),
            'codigo_barras' => sanitize_text_field((string)($data['codigo_barras'] ?? '')),
            'linha_digitavel' => sanitize_text_field((string)($data['linha_digitavel'] ?? '')),
            'valor_nominal' => isset($data['valor_nominal']) ? (float)$data['valor_nominal'] : null,
            'data_vencimento' => !empty($data['data_vencimento']) ? sanitize_text_field((string)$data['data_vencimento']) : null,
            'situacao_banrisul' => sanitize_text_field((string)($data['situacao_banrisul'] ?? '')),
            'situacao_cip' => sanitize_text_field((string)($data['situacao_cip'] ?? '')),
            'situacao_pagamento' => sanitize_text_field((string)($data['situacao_pagamento'] ?? '')),
            'status_local' => sanitize_text_field((string)($data['status_local'] ?? 'criado')),
            'payload_registro' => $this->json_or_null($data['payload_registro'] ?? null),
            'payload_emissao' => $this->json_or_null($data['payload_emissao'] ?? null),
            'payload_envio' => $this->json_or_null($data['payload_envio'] ?? null),

            'juros_mensal_percentual' => isset($data['juros_mensal_percentual'])
                ? (float)$data['juros_mensal_percentual']
                : null,

            'multa_percentual' => isset($data['multa_percentual'])
                ? (float)$data['multa_percentual']
                : null,

            'baixa_dias' => isset($data['baixa_dias'])
                ? absint($data['baixa_dias'])
                : null,

            'updated_at' => current_time('mysql'),
        ];

        if (!empty($data['pdf_path'])) {
            $row['pdf_path'] = sanitize_text_field(
                (string)$data['pdf_path']
            );
        }

        if (!empty($data['pdf_url'])) {
            $row['pdf_url'] = esc_url_raw(
                (string)$data['pdf_url']
            );
        }

        if (
            isset($data['payload_consulta'])
            && $data['payload_consulta'] !== ''
            && $data['payload_consulta'] !== null
        ) {
            $row['payload_consulta'] = $this->json_or_null(
                $data['payload_consulta']
            );
        }

        if (!empty($data['data_pagamento'])) {
            $row['data_pagamento'] = sanitize_text_field(
                (string)$data['data_pagamento']
            );
        }

        if (!empty($data['data_credito'])) {
            $row['data_credito'] = sanitize_text_field(
                (string)$data['data_credito']
            );
        }

        if (
            isset($data['valor_pagamento'])
            && $data['valor_pagamento'] !== ''
        ) {
            $row['valor_pagamento'] = (float)$data['valor_pagamento'];
        }

        if (
            isset($data['valor_cobrado'])
            && $data['valor_cobrado'] !== ''
        ) {
            $row['valor_cobrado'] = (float)$data['valor_cobrado'];
        }

        if (
            isset($data['valor_creditado_debitado'])
            && $data['valor_creditado_debitado'] !== ''
        ) {
            $row['valor_creditado_debitado'] =
                (float)$data['valor_creditado_debitado'];
        }

        if ($existing) {
            $updated = $wpdb->update(
                $this->table,
                $row,
                ['id' => absint($existing['id'])],
                null,
                ['%d']
            );

            if ($updated === false) {
                throw new Exception('Erro ao atualizar boleto no banco: ' . $wpdb->last_error);
            }

            return absint($existing['id']);
        }

        $row['created_at'] = current_time('mysql');

        $inserted = $wpdb->insert($this->table, $row);

        if ($inserted === false) {
            throw new Exception('Erro ao salvar boleto no banco: ' . $wpdb->last_error);
        }

        return absint($wpdb->insert_id);
    }

    public function get_by_invoice_id(int $invoice_id): ?array
    {
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE propovoice_invoice_id = %d ORDER BY id DESC LIMIT 1",
            $invoice_id
        );

        $row = $wpdb->get_row($sql, ARRAY_A);

        return $row ?: null;
    }

    public function get_by_id(int $id): ?array
    {
        global $wpdb;

        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE id = %d LIMIT 1",
            $id
        );

        $row = $wpdb->get_row($sql, ARRAY_A);

        return $row ?: null;
    }

    public function list_recent(int $limit = 20): array
    {
        global $wpdb;

        $limit = max(1, min(100, $limit));

        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table} ORDER BY id DESC LIMIT %d",
            $limit
        );

        $rows = $wpdb->get_results($sql, ARRAY_A);

        return is_array($rows) ? $rows : [];
    }

    public function update_status_from_consulta(int $id, array $consulta): void
    {
        global $wpdb;

        $titulo = $consulta['titulo'] ?? [];
        $operacoes = is_array($titulo['operacoes'] ?? null)
            ? $titulo['operacoes']
            : [];

        $row = [
            'situacao_banrisul' => sanitize_text_field((string)($titulo['situacao_banrisul'] ?? '')),
            'situacao_cip' => sanitize_text_field((string)($titulo['situacao_cip'] ?? '')),
            'situacao_pagamento' => sanitize_text_field(
                (string) ($titulo['situacao_pagamento'] ?? '')
            ),

            'payload_consulta' => $this->json_or_null($consulta),
            'updated_at' => current_time('mysql'),
        ];

        if (!empty($operacoes['data_pagamento'])) {
            $row['data_pagamento'] = sanitize_text_field(
                (string)$operacoes['data_pagamento']
            );
        }

        if (!empty($operacoes['data_credito'])) {
            $row['data_credito'] = sanitize_text_field(
                (string)$operacoes['data_credito']
            );
        }

        if (
            isset($operacoes['valor_pagamento'])
            && $operacoes['valor_pagamento'] !== ''
        ) {
            $row['valor_pagamento'] = (float)$operacoes['valor_pagamento'];
        }

        if (
            isset($operacoes['valor_cobrado'])
            && $operacoes['valor_cobrado'] !== ''
        ) {
            $row['valor_cobrado'] = (float)$operacoes['valor_cobrado'];
        }

        if (
            isset($operacoes['valor_creditado_debitado'])
            && $operacoes['valor_creditado_debitado'] !== ''
        ) {
            $row['valor_creditado_debitado'] =
                (float)$operacoes['valor_creditado_debitado'];
        }

        $situacao_banrisul = (string)(
            $titulo['situacao_banrisul'] ?? ''
        );

        $situacao_cip = (string)(
            $titulo['situacao_cip'] ?? ''
        );

        $situacao_pagamento = (string)(
            $titulo['situacao_pagamento'] ?? ''
        );

        $pagamento_confirmado =
            !empty($operacoes['data_pagamento'])
            && isset($operacoes['valor_pagamento'])
            && (float)$operacoes['valor_pagamento'] > 0;

        if ($situacao_banrisul === 'L') {
            $row['status_local'] = 'pago';

        } elseif ($situacao_pagamento === '3') {
            $row['status_local'] = 'credito_retido';

        } elseif (
            $situacao_pagamento === '2'
            || (
                $pagamento_confirmado
                && $situacao_pagamento === ''
            )
        ) {
            $row['status_local'] = 'pagamento_identificado';

        } elseif ($situacao_cip === 'R') {
            $row['status_local'] = 'rejeitado_cip';

        } elseif ($situacao_banrisul === 'B') {
            $row['status_local'] = 'baixado_pagamento';

        } elseif ($situacao_banrisul === 'D') {
            $row['status_local'] = 'baixado_devolucao';

        } elseif ($situacao_banrisul === 'R') {
            $row['status_local'] = 'reembolsado';

        } elseif ($situacao_banrisul === 'T') {
            $row['status_local'] = 'transferido_cl';

        } elseif ($situacao_banrisul === 'P') {
            $row['status_local'] = 'protestado';

        } elseif ($situacao_pagamento === '4') {
            $row['status_local'] = 'baixado_no_dia';

        } elseif ($situacao_pagamento === '5') {
            $row['status_local'] = 'devolucao_automatica';

        } elseif ($situacao_pagamento === '6') {
            $row['status_local'] = 'em_cartorio';

        } elseif ($situacao_pagamento === '8') {
            $row['status_local'] = 'negativado';

        } elseif (
            $situacao_banrisul === 'A'
            && $situacao_cip === 'A'
        ) {
            $row['status_local'] = 'aguardando_pagamento';
        }

        $updated = $wpdb->update(
            $this->table,
            $row,
            ['id' => absint($id)],
            null,
            ['%d']
        );

        if ($updated === false) {
            throw new Exception('Erro ao atualizar status do boleto: ' . $wpdb->last_error);
        }
    }

    private function json_or_null($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function list_for_status_sync(int $limit = 50): array
    {
        global $wpdb;

        $limit = max(1, min(100, $limit));

        $sql = $wpdb->prepare(
            "SELECT *
        FROM {$this->table}
        WHERE status_local IN (
            'boleto_gerado',
            'aguardando_pagamento',
            'boleto_registrado_sem_pdf'
        )
        AND codigo_barras <> ''
        ORDER BY updated_at ASC
        LIMIT %d",
            $limit
        );

        $rows = $wpdb->get_results($sql, ARRAY_A);

        return is_array($rows) ? $rows : [];
    }

    public function find_for_webhook(
        string $beneficiario_codigo,
        string $nosso_numero,
        string $codigo_barras,
        string $linha_digitavel
    ): ?array {
        global $wpdb;

        $beneficiario_codigo = preg_replace(
            '/\D+/',
            '',
            $beneficiario_codigo
        );

        $nosso_numero = preg_replace(
            '/\D+/',
            '',
            $nosso_numero
        );

        $codigo_barras = preg_replace(
            '/\D+/',
            '',
            $codigo_barras
        );

        $linha_digitavel = preg_replace(
            '/\D+/',
            '',
            $linha_digitavel
        );

        if ($beneficiario_codigo === '') {
            return null;
        }

        $conditions = [];
        $values = [
            $beneficiario_codigo,
        ];

        if ($codigo_barras !== '') {
            $conditions[] = 'codigo_barras = %s';
            $values[] = $codigo_barras;
        }

        if ($linha_digitavel !== '') {
            $conditions[] = 'linha_digitavel = %s';
            $values[] = $linha_digitavel;
        }

        if ($nosso_numero !== '') {
            $conditions[] = 'nosso_numero = %s';
            $values[] = $nosso_numero;
        }

        if ($conditions === []) {
            return null;
        }

        $sql = "
            SELECT *
            FROM {$this->table}
            WHERE beneficiario_codigo = %s
            AND (
                " . implode(' OR ', $conditions) . "
            )
            ORDER BY id DESC
            LIMIT 1
        ";

        $prepared = $wpdb->prepare(
            $sql,
            ...$values
        );

        $result = $wpdb->get_row(
            $prepared,
            ARRAY_A
        );

        return is_array($result)
            ? $result
            : null;
    }
}
