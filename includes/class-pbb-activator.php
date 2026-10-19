<?php

if (!defined('ABSPATH')) {
    exit;
}

class PBB_Activator
{
    public static function activate()
    {
        global $wpdb;

        $table = $wpdb->prefix . 'propovoice_banrisul_boletos';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, 
            propovoice_invoice_id BIGINT UNSIGNED NOT NULL, 
            propovoice_invoice_num VARCHAR(50) NOT NULL,
            propovoice_invoice_token VARCHAR(120) NULL,
            propovoice_contact_id BIGINT UNSIGNED NULL,
            empresa_id VARCHAR(50) NULL,
            beneficiario_codigo VARCHAR(30) NULL,
            client_name VARCHAR(255) NULL,
            client_document VARCHAR(20) NULL,
            client_email VARCHAR(190) NULL,
            nosso_numero VARCHAR(50) NULL,
            seu_numero VARCHAR(50) NULL,
            codigo_barras VARCHAR(100) NULL,
            linha_digitavel VARCHAR(150) NULL,
            valor_nominal DECIMAL(15,2) NULL,
            data_vencimento DATE NULL,
            juros_mensal_percentual DECIMAL(6,2) NULL,
            multa_percentual DECIMAL(5,1) NULL,
            baixa_dias SMALLINT UNSIGNED NULL,
            situacao_banrisul VARCHAR(10) NULL,
            situacao_cip VARCHAR(10) NULL,
            situacao_pagamento VARCHAR(20) NULL,
            data_pagamento DATE NULL,
            data_credito DATE NULL,
            valor_pagamento DECIMAL(15,2) NULL,
            valor_cobrado DECIMAL(15,2) NULL,
            valor_creditado_debitado DECIMAL(15,2) NULL,
            pdf_path TEXT NULL,
            pdf_url TEXT NULL,
            status_local VARCHAR(50) DEFAULT 'criado',
            payload_registro LONGTEXT NULL,
            payload_emissao LONGTEXT NULL,
            payload_consulta LONGTEXT NULL,
            payload_envio LONGTEXT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY invoice_id (propovoice_invoice_id),
            KEY nosso_numero (nosso_numero)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        if (!wp_next_scheduled(PBB_Cron::HOOK)) {
            PBB_Cron::schedule();
        }

    }
}
