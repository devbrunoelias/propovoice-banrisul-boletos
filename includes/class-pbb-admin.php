<?php

if (!defined('ABSPATH')) {
    exit;
}

class PBB_Admin
{
    public static function init(): void
    {
        add_action('admin_menu', [__CLASS__, 'register_menu']);
        add_action('admin_post_pbb_save_settings', [__CLASS__, 'handle_save_settings']);
        add_action('admin_post_pbb_generate_boleto', [__CLASS__, 'handle_generate_boleto']);
        add_action('admin_post_pbb_consultar_status', [__CLASS__, 'handle_consultar_status']);
        add_action('admin_post_pbb_download_boleto', [__CLASS__, 'handle_download_boleto']);
        add_action('admin_post_nopriv_pbb_download_boleto', [__CLASS__, 'handle_download_boleto']);
        add_action('init', [__CLASS__, 'register_shortcodes']);
    }

    public static function register_menu(): void
    {
        add_menu_page(
            'Banrisul Boletos',
            'Banrisul Boletos',
            'manage_options',
            'pbb-settings',
            [__CLASS__, 'render_settings_page'],
            'dashicons-money-alt',
            56
        );

        add_submenu_page(
            'pbb-settings',
            'Configurações',
            'Configurações',
            'manage_options',
            'pbb-settings',
            [__CLASS__, 'render_settings_page']
        );

        add_submenu_page(
            'pbb-settings',
            'Gerar por Invoice',
            'Gerar por Invoice',
            'manage_options',
            'pbb-generate',
            [__CLASS__, 'render_generate_page']
        );
    }

    public static function register_shortcodes(): void
    {
        add_shortcode('banrisul_boleto', [__CLASS__, 'render_boleto_shortcode']);
    }

    public static function render_boleto_shortcode($atts = []): string
    {
        $invoice_id = isset($_GET['invoice_id']) ? absint($_GET['invoice_id']) : 0;
        $token = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';

        $empresa_nome = '';

        $empresa_id_boleto = sanitize_key(
            (string)($boleto['empresa_id'] ?? '')
        );

        if (
            $empresa_id_boleto !== ''
            && PBB_Empresas_Config::exists(
                $empresa_id_boleto
            )
        ) {
            $empresa_config = PBB_Empresas_Config::get(
                $empresa_id_boleto
            );

            $empresa_nome = (string)(
                $empresa_config['nome'] ?? ''
            );
        }

        if (!$invoice_id || !$token) {
            return '<div class="banrisul-boleto"><p>Boleto não localizado. Parâmetros ausentes.</p></div>';
        }

        try {
            $repo = new PBB_Boletos_Repository();
            $boleto = $repo->get_by_invoice_id($invoice_id);

            if (!$boleto) {
                return '<div class="banrisul-boleto"><p>Boleto não encontrado para esta invoice.</p></div>';
            }

            if (empty($boleto['propovoice_invoice_token']) || !hash_equals((string) $boleto['propovoice_invoice_token'], $token)) {
                return '<div class="banrisul-boleto"><p>Token inválido para acessar este boleto.</p></div>';
            }

            $pdf_url = '';

            if (!empty($boleto['pdf_path'])) {
                $pdf_url = add_query_arg(
                    [
                        'action' => 'pbb_download_boleto',
                        'invoice_id' => absint(
                            $boleto['propovoice_invoice_id']
                        ),
                        'token' => (string)(
                            $boleto['propovoice_invoice_token'] ?? ''
                        ),
                    ],
                    admin_url('admin-post.php')
                );
            }

            $status_raw = (string)($boleto['status_local'] ?? '');

            ob_start();
            ?>
            <div class="banrisul-boleto" style="max-width: 760px; margin: 0 auto; padding: 24px; border: 1px solid #e5e7eb; border-radius: 12px; background: #ffffff;">
                <h2 style="margin-top: 0;">Boleto Banrisul</h2>

                <p><strong>Invoice:</strong> <?php echo esc_html($boleto['propovoice_invoice_num'] ?? ''); ?></p>

                <?php if ($empresa_nome !== '') : ?>
                    <p><strong>Empresa emissora:</strong><?php echo esc_html($empresa_nome); ?></p>
                <?php endif; ?>

                <p><strong>Cliente:</strong> <?php echo esc_html($boleto['client_name'] ?? ''); ?></p>
                <p><strong>Valor:</strong> R$ <?php echo esc_html($boleto['valor_nominal'] ?? ''); ?></p>
                <p><strong>Vencimento:</strong> <?php echo esc_html($boleto['data_vencimento'] ?? ''); ?></p>
                <p><strong>Status:</strong> <?php echo esc_html(self::format_status($status_raw)); ?></p>
                <?php if ($status_raw === 'pago') : ?>
                <div
                    style="
                        margin: 16px 0;
                        padding: 14px;
                        border: 1px solid #b7dfc5;
                        border-radius: 6px;
                        background: #f0fff4;
                    "
                >
                    <p style="margin-top: 0;">
                        <strong>Pagamento identificado</strong>
                    </p>

                    <?php if (!empty($boleto['data_pagamento'])) : ?>
                        <p>
                            <strong>Data do pagamento:</strong>
                            <?php
                            echo esc_html(
                                self::format_date_display(
                                    $boleto['data_pagamento']
                                )
                            );
                        ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($boleto['valor_pagamento'])) : ?>
                        <p>
                            <strong>Valor pago:</strong>
                            <?php
                        echo esc_html(
                            self::format_money_display(
                                $boleto['valor_pagamento']
                            )
                        );
                        ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($boleto['data_credito'])) : ?>
                        <p>
                            <strong>Data do crédito:</strong>
                            <?php
                        echo esc_html(
                            self::format_date_display(
                                $boleto['data_credito']
                            )
                        );
                        ?>
                        </p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <hr>

                <p><strong>Linha digitável:</strong></p>

                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <input
                        type="text"
                        id="banrisul-linha-digitavel"
                        value="<?php echo esc_attr($boleto['linha_digitavel'] ?? ''); ?>"
                        readonly
                        style="flex: 1; min-width: 280px; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px;"
                    >

                    <button
                        type="button"
                        onclick="navigator.clipboard.writeText(document.getElementById('banrisul-linha-digitavel').value); alert('Linha digitável copiada!');"
                        style="padding: 10px 14px; border: 0; border-radius: 6px; background: #2563eb; color: #fff; cursor: pointer;"
                    >
                        Copiar
                    </button>
                </div>

                <?php if ($pdf_url !== '') : ?>
                    <p style="margin-top: 20px;">
                        <a href="<?php echo esc_url($pdf_url); ?>" target="_blank" rel="noopener noreferrer" style="display: inline-block; padding: 10px 14px; border-radius: 6px; background: #111827; color: #fff; text-decoration: none;">
                            Abrir PDF do boleto
                        </a>
                    </p>
                <?php else : ?>
                    <p style="margin-top: 20px;">PDF ainda não disponível para este boleto.</p>
                <?php endif; ?>

                <?php if ($status_raw === 'boleto_sandbox_pdf_exemplo') : ?>
                    <p style="margin-top: 16px; color: #92400e; background: #fffbeb; padding: 10px; border-radius: 6px;">
                        Ambiente sandbox: o PDF exibido é um boleto exemplo do Banrisul.
                    </p>
                <?php endif; ?>
            </div>
            <?php
            return ob_get_clean();
        } catch (Throwable $e) {
            return '<div class="banrisul-boleto"><p>Erro ao carregar boleto: ' . esc_html($e->getMessage()) . '</p></div>';
        }
    }

    public static function render_settings_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sem permissão.');
        }

        self::render_notice();
        self::render_environment_notice();

        $ambiente = get_option('pbb_banrisul_ambiente', 'sandbox');
        $base_url = get_option('pbb_banrisul_base_url', 'https://apidev.banrisul.com.br/cobranca/v1');
        $oauth_url = get_option('pbb_banrisul_oauth_url', '');
        $producao_base_url = get_option('pbb_banrisul_producao_base_url', '');
        $producao_oauth_url = get_option('pbb_banrisul_producao_oauth_url', '');
        $beneficiario = get_option('pbb_banrisul_beneficiario', '0010000001088');
        $especie = get_option('pbb_boleto_especie', '99');
        $aceite = get_option('pbb_boleto_aceite', 'A');
        $juros_codigo = get_option('pbb_boleto_juros_codigo', '3');
        $pag_parcial_autoriza = get_option('pbb_boleto_pag_parcial_autoriza', '1');
        $pag_parcial_codigo = get_option('pbb_boleto_pag_parcial_codigo', '3');
        ?>
        <div class="wrap">
            <h1>Banrisul Boletos - Configurações</h1>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('pbb_save_settings'); ?>
                <input type="hidden" name="action" value="pbb_save_settings">

                <h2>Ambiente</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="pbb_banrisul_ambiente">Ambiente</label></th>
                        <td>
                            <select name="pbb_banrisul_ambiente" id="pbb_banrisul_ambiente">
                                <option value="sandbox" <?php selected($ambiente, 'sandbox'); ?> >
                                    Sandbox
                                </option>

                                <option value="homologacao" <?php selected($ambiente, 'homologacao'); ?> >
                                    Homologação
                                </option>

                                <option value="producao" <?php selected($ambiente, 'producao'); ?> >
                                    Produção
                                </option>
                            </select>

                            <p class="description">
                                Sandbox utiliza credenciais e URLs de teste.
                                Homologação utiliza credenciais e URLs produtivas,
                                mas envia ambiente T no registro.
                                Produção envia ambiente P.
                            </p>
                        </td>
                    </tr>
                    <tr>
                    <th scope="row"><label for="pbb_banrisul_producao_oauth_url">URL OAuth2 de Homologação/Produção</label></th>
                        <td>
                            <input
                                type="url"
                                name="pbb_banrisul_producao_oauth_url"
                                id="pbb_banrisul_producao_oauth_url"
                                class="regular-text"
                                value="<?php
                                    echo esc_attr($producao_oauth_url);
        ?>"
                            >

                            <p class="description">
                                Utilizada nos ambientes Homologação e Produção.
                                Preencher somente com a URL oficial do Banrisul.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="pbb_banrisul_producao_base_url">
                                URL Base API de Homologação/Produção
                            </label>
                        </th>

                        <td>
                            <input
                                type="url"
                                name="pbb_banrisul_producao_base_url"
                                id="pbb_banrisul_producao_base_url"
                                class="regular-text"
                                value="<?php
            echo esc_attr($producao_base_url);
        ?>"
                            >

                            <p class="description">
                                URL base produtiva da API de Cobrança.
                                Não incluir barra no final.
                            </p>
                        </td>
                    </tr>
                </table>

                <h2>Banrisul</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Credenciais</th>
                        <td>
                            <?php
        $banrisul_configurado = false;
        $empresa_1_configurada = false;
        $empresa_2_configurada = false;

        if ($ambiente === 'sandbox') {
            $banrisul_configurado =
                defined('PBB_BANRISUL_SANDBOX_CLIENT_ID')
                && PBB_BANRISUL_SANDBOX_CLIENT_ID !== ''
                && defined('PBB_BANRISUL_SANDBOX_CLIENT_SECRET')
                && PBB_BANRISUL_SANDBOX_CLIENT_SECRET !== '';
        } else {
            $empresa_1_configurada =
                defined('PBB_EMPRESA_1_PRODUCAO_CLIENT_ID')
                && PBB_EMPRESA_1_PRODUCAO_CLIENT_ID !== ''
                && defined('PBB_EMPRESA_1_PRODUCAO_CLIENT_SECRET')
                && PBB_EMPRESA_1_PRODUCAO_CLIENT_SECRET !== '';

            $empresa_2_configurada =
                defined('PBB_EMPRESA_2_PRODUCAO_CLIENT_ID')
                && PBB_EMPRESA_2_PRODUCAO_CLIENT_ID !== ''
                && defined('PBB_EMPRESA_2_PRODUCAO_CLIENT_SECRET')
                && PBB_EMPRESA_2_PRODUCAO_CLIENT_SECRET !== '';

            $banrisul_configurado =
                $empresa_1_configurada
                && $empresa_2_configurada;
        }
        ?>

                            <?php if ($banrisul_configurado) : ?>
                                <strong style="color: #008a20;">
                                    Credenciais Banrisul configuradas no wp-config.php.
                                </strong>
                            <?php else : ?>
                                <strong style="color: #b32d2e;">
                                    Credenciais Banrisul não configuradas para este ambiente.
                                </strong>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pbb_banrisul_oauth_url">URL OAuth2</label></th>
                        <td><input type="url" class="large-text" name="pbb_banrisul_oauth_url" id="pbb_banrisul_oauth_url" value="<?php echo esc_attr($oauth_url); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pbb_banrisul_base_url">URL Base API</label></th>
                        <td><input type="url" class="large-text" name="pbb_banrisul_base_url" id="pbb_banrisul_base_url" value="<?php echo esc_attr($base_url); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pbb_banrisul_beneficiario">Código Beneficiário</label></th>
                        <td><input type="text" class="regular-text" name="pbb_banrisul_beneficiario" id="pbb_banrisul_beneficiario" value="<?php echo esc_attr($beneficiario); ?>"></td>
                    </tr>
                </table>

                <h2>PROPOVOICE Public API</h2>

                <table class="form-table">
                    <tr>
                        <th scope="row">Credenciais</th>
                        <td>
                            <?php
        $propovoice_configurado =
            defined('PBB_PROPOVOICE_USERNAME')
            && PBB_PROPOVOICE_USERNAME !== ''
            && defined('PBB_PROPOVOICE_APP_PASSWORD')
            && PBB_PROPOVOICE_APP_PASSWORD !== '';
        ?>

                            <?php if ($propovoice_configurado) : ?>
                                <strong style="color: #008a20;">
                                    Credenciais PROPOVOICE configuradas no wp-config.php.
                                </strong>
                            <?php else : ?>
                                <strong style="color: #b32d2e;">
                                    Credenciais PROPOVOICE não configuradas.
                                </strong>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <h2>Padrões do Boleto</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="pbb_boleto_especie">Espécie</label></th>
                        <td><input type="text" name="pbb_boleto_especie" id="pbb_boleto_especie" value="<?php echo esc_attr($especie); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pbb_boleto_aceite">Aceite</label></th>
                        <td><input type="text" name="pbb_boleto_aceite" id="pbb_boleto_aceite" value="<?php echo esc_attr($aceite); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pbb_boleto_juros_codigo">Código Juros</label></th>
                        <td><input type="text" name="pbb_boleto_juros_codigo" id="pbb_boleto_juros_codigo" value="<?php echo esc_attr($juros_codigo); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pbb_boleto_pag_parcial_autoriza">Pagamento Parcial Autoriza</label></th>
                        <td><input type="text" name="pbb_boleto_pag_parcial_autoriza" id="pbb_boleto_pag_parcial_autoriza" value="<?php echo esc_attr($pag_parcial_autoriza); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pbb_boleto_pag_parcial_codigo">Pagamento Parcial Código</label></th>
                        <td><input type="text" name="pbb_boleto_pag_parcial_codigo" id="pbb_boleto_pag_parcial_codigo" value="<?php echo esc_attr($pag_parcial_codigo); ?>"></td>
                    </tr>
                </table>

                <?php submit_button('Salvar configurações'); ?>
            </form>
        </div>
        <?php
    }

    public static function handle_save_settings(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sem permissão.');
        }

        check_admin_referer('pbb_save_settings');

        $ambiente = sanitize_key(wp_unslash($_POST['pbb_banrisul_ambiente'] ?? 'sandbox'));

        $ambientes_permitidos = ['sandbox', 'homologacao', 'producao',];

        if (!in_array($ambiente, $ambientes_permitidos, true)) {
            $ambiente = 'sandbox';
        }

        update_option('pbb_banrisul_ambiente', $ambiente);

        update_option('pbb_banrisul_oauth_url', esc_url_raw($_POST['pbb_banrisul_oauth_url'] ?? ''));
        update_option('pbb_banrisul_base_url', esc_url_raw($_POST['pbb_banrisul_base_url'] ?? ''));
        update_option('pbb_banrisul_beneficiario', sanitize_text_field($_POST['pbb_banrisul_beneficiario'] ?? ''));
        update_option('pbb_boleto_especie', sanitize_text_field($_POST['pbb_boleto_especie'] ?? '99'));
        update_option('pbb_boleto_aceite', sanitize_text_field($_POST['pbb_boleto_aceite'] ?? 'A'));
        update_option('pbb_boleto_juros_codigo', sanitize_text_field($_POST['pbb_boleto_juros_codigo'] ?? '3'));
        update_option('pbb_boleto_pag_parcial_autoriza', sanitize_text_field($_POST['pbb_boleto_pag_parcial_autoriza'] ?? '1'));
        update_option('pbb_boleto_pag_parcial_codigo', sanitize_text_field($_POST['pbb_boleto_pag_parcial_codigo'] ?? '3'));

        update_option('pbb_banrisul_producao_oauth_url', esc_url_raw(wp_unslash($_POST['pbb_banrisul_producao_oauth_url'] ?? '')));

        update_option('pbb_banrisul_producao_base_url', untrailingslashit(esc_url_raw(wp_unslash($_POST['pbb_banrisul_producao_base_url'] ?? ''))));

        self::set_notice('Configurações salvas com sucesso.', 'success');
        wp_safe_redirect(admin_url('admin.php?page=pbb-settings'));
        exit;
    }

    public static function render_generate_page(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sem permissão.');
        }

        self::render_notice();
        self::render_environment_notice();

        $repo = new PBB_Boletos_Repository();
        $boletos = $repo->list_recent(20);
        $empresas = PBB_Empresas_Config::all();
        ?>

        <div class="wrap">
            <h1>Gerar Boleto Banrisul por Invoice</h1>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('pbb_generate_boleto'); ?>
                <input type="hidden" name="action" value="pbb_generate_boleto">

                <table class="form-table">
                    
                    <tr>
                        <th scope="row">
                            <label for="empresa_id">
                                Empresa emissora
                            </label>
                        </th>

                        <td>
                            <select
                                name="empresa_id"
                                id="empresa_id"
                                required
                            >
                                <option value="">
                                    Selecione a empresa
                                </option>

                                <option value="empresa_1">
                                    CATÂNIA COMUNICAÇÃO E MARKETING LTDA
                                </option>

                                <option value="empresa_2">
                                    SITES E PRODUTOS WEB LTDA
                                </option>
                            </select>

                            <p class="description">
                                Selecione a empresa e o Código de Beneficiário
                                que serão utilizados para registrar o boleto.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="invoice_id">Número ou ID da Invoice PROPOVOICE</label>
                        </th>
                        <td>
                            <input type="number" name="invoice_id" id="invoice_id" class="regular-text" required>
                            <p class="description">
                                Informe somente a parte numérica.
                                Exemplo: para Inv-2298, informe 2298.
                                Exemplo de teste: 2296.
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Gerar boleto Banrisul'); ?>
            </form>

            <hr>

            <h2>Últimos boletos gerados</h2>

             <style>
                .widefat tr.pbb-boleto-pago > td {
                    background-color: #d7f0dc !important;
                    border-top-color: #9dccaa;
                    border-bottom-color: #9dccaa;
                }

                .widefat tr.pbb-boleto-pago > td:first-child {
                    border-left: 4px solid #008a20;
                }
            </style>
                   
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Empresa Emissora</th>
                        <th>Cliente</th>
                        <th>Valor</th>
                        <th>Vencimento</th>
                        <th>Nosso número</th>
                        <th>Linha digitável</th>
                        <th>PDF</th>
                        <th>Link público</th>
                        <th>Consultar status</th>
                        <th>Status</th>
                        <th>Pagamento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($boletos)) : ?>
                        <tr><td colspan="12">Nenhum boleto gerado ainda.</td></tr>
                    <?php else : ?>
                        <?php foreach ($boletos as $boleto) : ?>
                            <?php
                            $link_publico = '';

                            if (!empty($boleto['propovoice_invoice_id'])
                                && !empty($boleto['propovoice_invoice_token'])
                            ) {
                                $link_publico = add_query_arg(
                                    [
                                        'invoice_id' => absint(
                                            $boleto['propovoice_invoice_id']
                                        ),
                                        'token' => (string)(
                                            $boleto['propovoice_invoice_token']
                                        ),
                                    ],
                                    site_url('/banrisul-boleto/')
                                );
                            }

                            $pdf_url = '';

                            if (
                                !empty($boleto['propovoice_invoice_id'])
                                && !empty($boleto['propovoice_invoice_token'])
                                && !empty($boleto['pdf_path'])
                            ) {
                                $pdf_url = add_query_arg(
                                    [
                                        'action' => 'pbb_download_boleto',
                                        'invoice_id' => absint(
                                            $boleto['propovoice_invoice_id']
                                        ),
                                        'token' => (string)(
                                            $boleto['propovoice_invoice_token']
                                        ),
                                    ],
                                    admin_url('admin-post.php')
                                );
                            }

                            $empresa_nome = 'Não informada';

                            $empresa_id_boleto = sanitize_key(
                                (string)($boleto['empresa_id'] ?? '')
                            );

                            if (
                                $empresa_id_boleto !== ''
                                && PBB_Empresas_Config::exists(
                                    $empresa_id_boleto
                                )
                            ) {
                                $empresa_config = PBB_Empresas_Config::get(
                                    $empresa_id_boleto
                                );

                                $empresa_nome = (string)(
                                    $empresa_config['nome']
                                    ?? $empresa_id_boleto
                                );
                            }

                            $row_class = (
                                ($boleto['status_local'] ?? '') === 'pago'
                            )
                                ? 'pbb-boleto-pago'
                                : '';
                            ?>
                            <tr class="<?php echo esc_attr($row_class); ?>">
                                <td><?php echo esc_html($boleto['propovoice_invoice_num']); ?></td>
                                <td><?php echo esc_html($empresa_nome); ?>
                                    <?php
                                        if (!empty($boleto['beneficiario_codigo'])) :
                                            ?>
                                    <br>
                                        <small>
                                            Beneficiário:
                                            <?php
                                                    echo esc_html(
                                                        $boleto['beneficiario_codigo']
                                                    );
                                            ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($boleto['client_name']); ?></td>
                                <td><?php echo esc_html($boleto['valor_nominal']); ?></td>
                                <td><?php echo esc_html($boleto['data_vencimento']); ?></td>
                                <td><?php echo esc_html($boleto['nosso_numero']); ?></td>
                                <td style="max-width: 280px; word-break: break-all;"><?php echo esc_html($boleto['linha_digitavel']); ?></td>
                                <td>
                                    <?php if (!empty($pdf_url)) : ?>
                                        <a href="<?php echo esc_url($pdf_url); ?>" target="_blank" rel="noopener">Abrir PDF</a>
                                    <?php else : ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($link_publico)) : ?>
                                        <a href="<?php echo esc_url($link_publico); ?>" target="_blank" rel="noopener">Abrir página</a><br>
                                        <button type="button" onclick="navigator.clipboard.writeText('<?php echo esc_js($link_publico); ?>'); alert('Link público copiado!');" class="button button-small" style="margin-top: 6px;">Copiar link</button>
                                    <?php else : ?>
                                        -
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php if (!empty($boleto['id']) && !empty($boleto['codigo_barras'])) : ?>
                                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" 
                                            style="margin:0;">
                                            <?php wp_nonce_field('pbb_consultar_status_' . absint($boleto['id'])); ?>
                                            <input type="hidden" name="action" value="pbb_consultar_status">
                                            <input type="hidden" name="boleto_id" value="<?php echo esc_attr(absint($boleto['id'])); ?>">
                                            <button type="submit" class="button button-small">Consultar</button>
                                        </form>
                                    <?php else : ?>
                                        -
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php echo esc_html(
                                        self::format_status((string) ($boleto['status_local'] ?? ''))
                                    ); ?>
                                </td>
                                <td>
                                    <?php if (($boleto['status_local'] ?? '') === 'pago') : ?>

                                        <?php if (!empty($boleto['data_pagamento'])) : ?>
                                            <strong>Pago em:</strong><br>
                                            <?php
                                            echo esc_html(
                                                self::format_date_display(
                                                    $boleto['data_pagamento']
                                                )
                                            );
                                            ?>
                                            <br>
                                        <?php endif; ?>

                                        <?php if (!empty($boleto['valor_pagamento'])) : ?>
                                            <strong>Valor:</strong><br>
                                            <?php
                                            echo esc_html(
                                                self::format_money_display(
                                                    $boleto['valor_pagamento']
                                                )
                                            );
                                            ?>
                                        <?php endif; ?>

                                    <?php else : ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public static function handle_generate_boleto(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sem permissão.');
        }

        check_admin_referer('pbb_generate_boleto');

        $invoice_id = absint($_POST['invoice_id'] ?? 0);
        $empresa_id = sanitize_key(wp_unslash($_POST['empresa_id'] ?? ''));

        if (!$invoice_id) {
            self::set_notice(
                'Informe um ID de invoice válido.',
                'error'
            );

            wp_safe_redirect(
                admin_url('admin.php?page=pbb-generate')
            );
            exit;
        }

        if (!PBB_Empresas_Config::exists($empresa_id)) {
            self::set_notice(
                'Selecione uma empresa emissora válida.',
                'error'
            );

            wp_safe_redirect(
                admin_url('admin.php?page=pbb-generate')
            );

            exit;
        }

        try {
            $ambiente = get_option('pbb_banrisul_ambiente', 'sandbox');
            $empresa = PBB_Empresas_Config::validate_for_environment($empresa_id, $ambiente);
            $propovoice = new PBB_Propovoice_API();
            $banrisul = new PBB_Banrisul_API($empresa_id);

            $beneficiario_codigo = $banrisul->get_beneficiario();

            if ($beneficiario_codigo === '') {
                throw new Exception(
                    'Código de Beneficiário não configurado '
                    . 'para a empresa selecionada.'
                );
            }

            $repo = new PBB_Boletos_Repository();

            $boleto_existente = $repo->get_by_invoice_id($invoice_id);

            if (
                in_array(
                    $ambiente,
                    ['homologacao', 'producao'],
                    true
                )
                && !empty($boleto_existente['codigo_barras'])
            ) {
                self::set_notice(
                    'Já existe um boleto registrado para esta invoice. ' .
                    'Use o botão Consultar status ou abra o boleto existente.',
                    'error'
                );

                wp_safe_redirect(
                    admin_url('admin.php?page=pbb-generate')
                );

                exit;
            }

            $invoices_response = $propovoice->list_invoices();
            $invoice = self::find_invoice_by_id($invoices_response, $invoice_id);

            if (!$invoice) {
                throw new Exception('Invoice não encontrada na API do PROPOVOICE.');
            }

            $contact_id = absint($invoice['to']['id'] ?? 0);
            if (!$contact_id) {
                throw new Exception('A invoice não possui cliente vinculado.');
            }

            $contact_response = $propovoice->get_contact($contact_id);
            $contact = $contact_response['data']['person'] ?? null;

            if (!$contact || !is_array($contact)) {
                throw new Exception('Contato do cliente não encontrado ou inválido.');
            }

            $payload = self::build_banrisul_payload($invoice, $contact);
            $registro = $banrisul->registrar_boleto($payload);
            $titulo_registro = $registro['titulo'] ?? [];

            if (empty($titulo_registro['codigo_barras'])) {
                throw new Exception('Banrisul não retornou código de barras no registro.');
            }

            $emissao = null;
            $pdf = ['path' => '', 'url' => ''];
            $status_local = 'boleto_gerado';

            try {
                $codigo_barras_para_emitir = (string)($titulo_registro['codigo_barras'] ?? '');
                if (!$codigo_barras_para_emitir) {
                    throw new Exception('Código de barras não retornado pelo Banrisul.');
                }

                $emissao = $banrisul->emitir_boleto_por_codigo_barras($codigo_barras_para_emitir);
                $base64_pdf = $emissao['titulo']['boleto'] ?? '';

                if (!$base64_pdf) {
                    throw new Exception('Banrisul não retornou o PDF Base64 do boleto.');
                }

                $nosso_numero = (string)($titulo_registro['nosso_numero'] ?? 'sem-nosso-numero');
                $pdf = self::save_pdf_from_base64($base64_pdf, $nosso_numero);
            } catch (Throwable $emitError) {
                $ambiente_plugin = get_option('pbb_banrisul_ambiente', 'sandbox');

                if ($ambiente_plugin === 'sandbox') {
                    $codigo_barras_exemplo = '04191861100000150502100100000010000000024047';
                    try {
                        $emissao = $banrisul->emitir_boleto_por_codigo_barras($codigo_barras_exemplo);
                        $base64_pdf = $emissao['titulo']['boleto'] ?? '';

                        if (!$base64_pdf) {
                            throw new Exception('Banrisul não retornou PDF Base64 no boleto exemplo.');
                        }

                        $nosso_numero = (string)($titulo_registro['nosso_numero'] ?? 'sandbox-exemplo');
                        $pdf = self::save_pdf_from_base64($base64_pdf, $nosso_numero . '-exemplo');
                        $status_local = 'boleto_sandbox_pdf_exemplo';
                    } catch (Throwable $fallbackError) {
                        $status_local = 'boleto_registrado_sem_pdf';
                        $emissao = [
                            'erro_emissao_pdf_original' => $emitError->getMessage(),
                            'erro_emissao_pdf_exemplo' => $fallbackError->getMessage(),
                        ];
                    }
                } else {
                    $status_local = 'boleto_registrado_sem_pdf';
                    $emissao = ['erro_emissao_pdf' => $emitError->getMessage()];
                }
            }

            $repo->save([
                'propovoice_invoice_id' => $invoice_id,
                'propovoice_invoice_num' => (string)($invoice['num'] ?? ''),
                'propovoice_invoice_token' => (string)($invoice['token'] ?? ''),
                'propovoice_contact_id' => $contact_id,
                'empresa_id' => $empresa_id,
                'beneficiario_codigo' => $beneficiario_codigo,
                'client_name' => (string)($contact['first_name'] ?? ''),
                'client_document' => self::only_digits((string)($contact['org_name'] ?? '')),
                'client_email' => (string)($contact['email'] ?? ''),
                'nosso_numero' => (string)($titulo_registro['nosso_numero'] ?? ''),
                'seu_numero' => (string)($titulo_registro['seu_numero'] ?? self::invoice_number_digits($invoice)),
                'codigo_barras' => (string)($titulo_registro['codigo_barras'] ?? ''),
                'linha_digitavel' => (string)($titulo_registro['linha_digitavel'] ?? ''),
                'valor_nominal' => self::format_money($invoice['total'] ?? ($invoice['invoice']['total'] ?? 0)),
                'data_vencimento' => self::format_date_ymd((string)($invoice['due_date'] ?? '')),
                'situacao_banrisul' => (string)($titulo_registro['situacao_banrisul'] ?? ''),
                'situacao_cip' => (string)($titulo_registro['situacao_cip'] ?? ''),
                'situacao_pagamento' => (string)($titulo_registro['situacao_pagamento'] ?? ''),
                'pdf_path' => $pdf['path'],
                'pdf_url' => $pdf['url'],
                'status_local' => $status_local,
                'payload_registro' => $registro,
                'payload_emissao' => $emissao,
                'payload_consulta' => null,
            ]);

            self::set_notice('Boleto gerado com sucesso. Linha digitável: ' . ($titulo_registro['linha_digitavel'] ?? ''), 'success');
        } catch (Throwable $e) {
            self::set_notice('Erro ao gerar boleto: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine(), 'error');
        }

        wp_safe_redirect(admin_url('admin.php?page=pbb-generate'));
        exit;
    }

    public static function handle_consultar_status(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Sem permissão.');
        }

        $boleto_id = absint($_POST['boleto_id'] ?? 0);

        if (!$boleto_id) {
            self::set_notice('Boleto inválido para consulta.', 'error');
            wp_safe_redirect(admin_url('admin.php?page=pbb-generate'));
            exit;
        }

        check_admin_referer('pbb_consultar_status_' . $boleto_id);

        try {
            $repo = new PBB_Boletos_Repository();
            $boleto = $repo->get_by_id($boleto_id);

            if (!$boleto) {
                throw new Exception('Boleto não encontrado no banco de dados.');
            }

            if (empty($boleto['codigo_barras'])) {
                throw new Exception('Boleto sem código de barras para consulta.');
            }

            $ambiente = (string)get_option('pbb_banrisul_ambiente', 'sandbox');

            $empresa_id = sanitize_key((string)($boleto['empresa_id'] ?? ''));

            if ($empresa_id === '') {
                if ($ambiente === 'sandbox') {
                    $empresa_id = 'empresa_1';
                } else {
                    throw new Exception(
                        'O boleto não possui empresa emissora vinculada.'
                    );
                }
            }

            if (!PBB_Empresas_Config::exists($empresa_id)) {
                throw new Exception(
                    'A empresa emissora vinculada ao boleto é inválida.'
                );
            }

            $banrisul = new PBB_Banrisul_API($empresa_id);

            if ($ambiente === 'sandbox') {
                $codigo_consulta = '04191861100000150502100100000010000000024047';
            } else {
                $codigo_consulta = (string) $boleto['codigo_barras'];
            }

            $consulta = $banrisul->consultar_boleto_por_codigo_barras(
                $codigo_consulta
            );

            $repo->update_status_from_consulta($boleto_id, $consulta);

            if ($ambiente === 'sandbox') {
                self::set_notice(
                    'Consulta sandbox realizada usando o título exemplo do Banrisul.',
                    'success'
                );
            } else {
                self::set_notice(
                    'Status do boleto consultado com sucesso.',
                    'success'
                );
            }
        } catch (Throwable $e) {
            self::set_notice(
                'Erro ao consultar status: ' . $e->getMessage() . ' em ' . $e->getFile() . ':' . $e->getLine(),
                'error'
            );
        }

        wp_safe_redirect(admin_url('admin.php?page=pbb-generate'));
        exit;
    }

    public static function handle_download_boleto(): void
    {
        $invoice_id = absint($_GET['invoice_id'] ?? 0);

        $token = sanitize_text_field(
            wp_unslash($_GET['token'] ?? '')
        );

        if (!$invoice_id || $token === '') {
            status_header(400);
            wp_die(
                esc_html('Parâmetros inválidos para o download.'),
                esc_html('Download inválido'),
                ['response' => 400]
            );
        }

        $repo = new PBB_Boletos_Repository();
        $boleto = $repo->get_by_invoice_id($invoice_id);

        if (!$boleto) {
            status_header(404);
            wp_die(
                esc_html('Boleto não encontrado.'),
                esc_html('Boleto não encontrado'),
                ['response' => 404]
            );
        }

        $token_salvo = (string)(
            $boleto['propovoice_invoice_token'] ?? ''
        );

        if (
            $token_salvo === ''
            || !hash_equals($token_salvo, $token)
        ) {
            status_header(403);
            wp_die(
                esc_html('Token inválido para acessar este boleto.'),
                esc_html('Acesso negado'),
                ['response' => 403]
            );
        }

        $pdf_path = (string)($boleto['pdf_path'] ?? '');

        if ($pdf_path === '') {
            status_header(404);
            wp_die(
                esc_html('O PDF deste boleto não está disponível.'),
                esc_html('PDF indisponível'),
                ['response' => 404]
            );
        }

        $arquivo_real = realpath($pdf_path);

        $upload = wp_upload_dir();

        $diretorio_permitido = realpath(
            trailingslashit($upload['basedir'])
            . 'banrisul-boletos'
        );

        if (
            !$arquivo_real
            || !$diretorio_permitido
            || !is_file($arquivo_real)
            || !is_readable($arquivo_real)
            || strpos(
                $arquivo_real,
                $diretorio_permitido . DIRECTORY_SEPARATOR
            ) !== 0
            || strtolower(pathinfo($arquivo_real, PATHINFO_EXTENSION)) !== 'pdf'
        ) {
            status_header(404);
            wp_die(
                esc_html('Arquivo PDF não localizado.'),
                esc_html('PDF indisponível'),
                ['response' => 404]
            );
        }

        $nome_download = 'boleto-' . sanitize_file_name(
            (string)($boleto['nosso_numero'] ?? $invoice_id)
        )
        . '.pdf';

        nocache_headers();

        header('Content-Type: application/pdf');

        header(
            'Content-Disposition: inline; filename="'
            . $nome_download
            . '"'
        );
        header('Content-Length: ' . filesize($arquivo_real));
        header('X-Content-Type-Options: nosniff');
        header('Content-Security-Policy: sandbox');
        header('Referrer-Policy: no-referrer');

        readfile($arquivo_real);
        exit;
    }

    private static function find_invoice_by_id(
        array $response,
        int $invoice_id
    ): ?array {
        $items = $response['data']['result'] ?? [];

        if (!is_array($items)) {
            return null;
        }

        /*
         * Primeiro procura pelo número visual da invoice.
         *
         * Exemplo:
         * Inv-2298 → 2298
         */
        foreach ($items as $item) {
            $invoice_numero = self::only_digits(
                (string)($item['num'] ?? '')
            );

            if (
                $invoice_numero !== ''
                && (int)$invoice_numero === $invoice_id
            ) {
                return $item;
            }
        }

        /*
         * Se não encontrar pelo número visual,
         * procura pelo ID interno para manter
         * compatibilidade com os testes anteriores.
         */
        foreach ($items as $item) {
            if (
                (int)($item['id'] ?? 0)
                === $invoice_id
            ) {
                return $item;
            }
        }

        return null;
    }

    private static function build_banrisul_payload(array $invoice, array $contact): array
    {
        self::validate_invoice_and_contact($invoice, $contact);

        $ambiente_plugin = get_option('pbb_banrisul_ambiente', 'sandbox');
        $ambiente_banrisul = ($ambiente_plugin === 'producao' ? 'P' : 'T');
        $documento = self::only_digits((string)$contact['org_name']);
        $cep = self::only_digits((string)$contact['zip']);

        return [
            'ambiente' => $ambiente_banrisul,
            'titulo' => [
                'seu_numero' => self::invoice_number_digits($invoice),
                'data_vencimento' => self::format_date_ymd((string)$invoice['due_date']),
                'valor_nominal' => self::format_money($invoice['total'] ?? ($invoice['invoice']['total'] ?? 0)),
                'especie' => get_option('pbb_boleto_especie', '99'),
                'data_emissao' => self::format_date_ymd((string)$invoice['date']),
                'pagador' => [
                    'tipo_pessoa' => self::detect_tipo_pessoa($documento),
                    'cpf_cnpj' => $documento,
                    'nome' => (string)$contact['first_name'],
                    'endereco' => (string)$contact['address'],
                    'cep' => $cep,
                    'cidade' => (string)$contact['city'],
                    'uf' => (string)$contact['region'],
                    'aceite' => get_option('pbb_boleto_aceite', 'A'),
                ],
                'instrucoes' => [
                    'juros' => [
                        'codigo' => (int)get_option('pbb_boleto_juros_codigo', '3'),
                    ],
                ],
                'pag_parcial' => [
                    'autoriza' => (int)get_option('pbb_boleto_pag_parcial_autoriza', '1'),
                    'codigo' => (int)get_option('pbb_boleto_pag_parcial_codigo', '3'),
                ],
            ],
        ];
    }

    private static function validate_invoice_and_contact(array $invoice, array $contact): void
    {
        foreach (['num', 'date', 'due_date'] as $field) {
            if (empty($invoice[$field])) {
                throw new Exception('Campo obrigatório ausente na invoice: ' . $field);
            }
        }

        $total = $invoice['total'] ?? ($invoice['invoice']['total'] ?? null);
        if ($total === null || (float)$total <= 0) {
            throw new Exception('Invoice sem valor total válido.');
        }

        $required_contact = [
            'first_name' => 'Nome/Razão Social',
            'org_name' => 'CPF/CNPJ no campo Organization',
            'address' => 'Endereço',
            'city' => 'Cidade',
            'region' => 'UF',
            'zip' => 'CEP',
        ];

        foreach ($required_contact as $field => $label) {
            if (empty($contact[$field])) {
                throw new Exception('Campo obrigatório ausente no cliente: ' . $label);
            }
        }

        $doc = self::only_digits((string)$contact['org_name']);
        if (!in_array(strlen($doc), [11, 14], true)) {
            throw new Exception('CPF/CNPJ inválido no campo Organization. Informe 11 ou 14 dígitos.');
        }
    }

    private static function invoice_number_digits(array $invoice): string
    {
        $digits = self::only_digits((string)($invoice['num'] ?? ''));
        return $digits ?: (string)($invoice['id'] ?? '');
    }

    private static function only_digits(string $value): string
    {
        return preg_replace('/\D+/', '', $value);
    }

    private static function detect_tipo_pessoa(string $documento): string
    {
        $digits = self::only_digits($documento);
        if (strlen($digits) === 11) {
            return 'F';
        }
        if (strlen($digits) === 14) {
            return 'J';
        }
        throw new Exception('CPF/CNPJ inválido.');
    }

    private static function format_date_ymd(string $date): string
    {
        $timestamp = strtotime($date);
        if (!$timestamp) {
            throw new Exception('Data inválida: ' . $date);
        }
        return gmdate('Y-m-d', $timestamp);
    }

    private static function format_money($value): string
    {
        return number_format((float)$value, 2, '.', '');
    }

    private static function ensure_protected_dir(string $dir): void
    {
        if (!is_dir($dir) && !wp_mkdir_p($dir)) {
            throw new Exception(
                'Não foi possível criar o diretório protegido dos boletos.'
            );
        }

        $htaccess_path = trailingslashit($dir) . '.htaccess';

        $htaccess_content =
            "<IfModule mod_authz_core.c>\n" .
            "    Require all denied\n" .
            "</IfModule>\n\n" .
            "<IfModule !mod_authz_core.c>\n" .
            "    Order Allow,Deny\n" .
            "    Deny from all\n" .
            "</IfModule>\n\n" .
            "Options -Indexes\n";

        if (!is_file($htaccess_path)) {
            $written = file_put_contents(
                $htaccess_path,
                $htaccess_content,
                LOCK_EX
            );

            if ($written === false) {
                throw new Exception(
                    'Não foi possível criar a proteção .htaccess dos boletos.'
                );
            }
        }

        $index_path = trailingslashit($dir) . 'index.php';

        if (!is_file($index_path)) {
            $written = file_put_contents(
                $index_path,
                "<?php\n// Silence is golden.\n",
                LOCK_EX
            );

            if ($written === false) {
                throw new Exception(
                    'Não foi possível criar o arquivo de proteção index.php.'
                );
            }
        }
    }

    private static function save_pdf_from_base64(string $base64, string $nosso_numero): array
    {
        $upload = wp_upload_dir();
        if (!empty($upload['error'])) {
            throw new Exception('Erro no diretório de upload: ' . $upload['error']);
        }

        $dir = trailingslashit($upload['basedir']) . 'banrisul-boletos';

        self::ensure_protected_dir($dir);

        $filename = 'boleto-' . sanitize_file_name($nosso_numero ?: uniqid('boleto-', true)) . '.pdf';
        $path = trailingslashit($dir) . $filename;
        $pdf = base64_decode($base64, true);

        if ($pdf === false) {
            throw new Exception('PDF Base64 inválido.');
        }

        $bytes = file_put_contents($path, $pdf);
        if ($bytes === false) {
            throw new Exception('Não foi possível salvar o PDF do boleto.');
        }

        return [
            'path' => $path,
            'url' => trailingslashit($upload['baseurl']) . 'banrisul-boletos/' . $filename,
        ];
    }

    private static function format_date_display(?string $date): string
    {
        if (empty($date)) {
            return '';
        }

        $date_object = DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $date
        );

        if (!$date_object) {
            return '';
        }

        return $date_object->format('d/m/Y');
    }

    private static function format_money_display($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return 'R$ ' . number_format_i18n((float)$value, 2);
    }

    private static function format_status(string $status): string
    {
        $labels = [
            'aguardando_pagamento' => 'Aguardando pagamento',
            'rejeitado_cip' => 'Boleto rejeitado',
            'baixado' => 'Boleto baixado',
            'boleto_gerado' => 'Boleto gerado',
            'boleto_registrado_sem_pdf' => 'Boleto registrado, PDF indisponível',
            'boleto_sandbox_pdf_exemplo' => 'Ambiente de teste',
            'pago' => 'Pago',
            'pagamento_identificado' => 'Pagamento identificado',
            'credito_retido' => 'Pagamento identificado, crédito retido',
            'baixado_pagamento' => 'Baixado por pagamento',
            'baixado_devolucao' => 'Baixado por devolução',
            'reembolsado' => 'Reembolsado',
            'transferido_cl' => 'Transferido para cobrança',
            'protestado' => 'Protestado',
            'baixado_no_dia' => 'Baixado no dia',
            'devolucao_automatica' => 'Em processo de devolução automática',
            'em_cartorio' => 'Em cartório',
            'negativado' => 'Negativado e apto para pagamento',
        ];

        return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    private static function render_environment_notice(): void
    {
        $ambiente = get_option(
            'pbb_banrisul_ambiente',
            'sandbox'
        );

        if ($ambiente === 'homologacao') {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong>AMBIENTE DE HOMOLOGAÇÃO ATIVO.</strong>
                    Serão utilizadas credenciais e URLs produtivas,
                    mas o registro será enviado com ambiente T.
                </p>
            </div>
            <?php

            return;
        }

        if ($ambiente === 'producao') {
            ?>
            <div class="notice notice-error">
                <p>
                    <strong>AMBIENTE DE PRODUÇÃO ATIVO.</strong>
                    As operações realizadas nesta tela podem registrar
                    cobranças bancárias reais.
                </p>
            </div>
            <?php

            return;
        }

        ?>
        <div class="notice notice-info">
            <p>
                <strong>Ambiente Sandbox ativo.</strong>
                As operações utilizam os cenários de teste do Banrisul.
            </p>
        </div>
        <?php
    }

    private static function set_notice(string $message, string $type = 'success'): void
    {
        set_transient('pbb_admin_notice', ['message' => $message, 'type' => $type], 60);
    }

    private static function render_notice(): void
    {
        $notice = get_transient('pbb_admin_notice');
        if (!$notice) {
            return;
        }
        delete_transient('pbb_admin_notice');
        $type = $notice['type'] === 'error' ? 'notice-error' : 'notice-success';
        echo '<div class="notice ' . esc_attr($type) . ' is-dismissible"><p>' . esc_html($notice['message']) . '</p></div>';
    }
}
