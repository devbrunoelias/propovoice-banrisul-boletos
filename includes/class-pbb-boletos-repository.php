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
            'pdf_path' => sanitize_text_field((string)($data['pdf_path'] ?? '')),
            'pdf_url' => esc_url_raw((string)($data['pdf_url'] ?? '')),
            'status_local' => sanitize_text_field((string)($data['status_local'] ?? 'criado')),
            'payload_registro' => $this->json_or_null($data['payload_registro'] ?? null),
            'payload_emissao' => $this->json_or_null($data['payload_emissao'] ?? null),
            'payload_consulta' => $this->json_or_null($data['payload_consulta'] ?? null),
            'updated_at' => current_time('mysql'),
        ];

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

        $row = [
            'situacao_banrisul' => sanitize_text_field((string)($titulo['situacao_banrisul'] ?? '')),
            'situacao_cip' => sanitize_text_field((string)($titulo['situacao_cip'] ?? '')),
            'situacao_pagamento' => sanitize_text_field((string)($titulo['situacao_pagamento'] ?? '')),
            'payload_consulta' => $this->json_or_null($consulta),
            'updated_at' => current_time('mysql'),
        ];

        if (($titulo['situacao_cip'] ?? '') === 'R') {
            $row['status_local'] = 'rejeitado_cip';
        } elseif (($titulo['situacao_banrisul'] ?? '') === 'A' && ($titulo['situacao_cip'] ?? '') === 'A') {
            $row['status_local'] = 'aguardando_pagamento';
        } elseif (($titulo['situacao_banrisul'] ?? '') === 'B') {
            $row['status_local'] = 'baixado';
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
}