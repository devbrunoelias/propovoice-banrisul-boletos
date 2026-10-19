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

            $pdf_url = esc_url($boleto['pdf_url'] ?? '');
            $status_raw = (string)($boleto['status_local'] ?? '');

            ob_start();
            ?>
            <div class="banrisul-boleto" style="max-width: 760px; margin: 0 auto; padding: 24px; border: 1px solid #e5e7eb; border-radius: 12px; background: #ffffff;">
                <h2 style="margin-top: 0;">Boleto Banrisul</h2>

                <p><strong>Invoice:</strong> <?php echo esc_html($boleto['propovoice_invoice_num'] ?? ''); ?></p>
                <p><strong>Cliente:</strong> <?php echo esc_html($boleto['client_name'] ?? ''); ?></p>
                <p><strong>Valor:</strong> R$ <?php echo esc_html($boleto['valor_nominal'] ?? ''); ?></p>
                <p><strong>Vencimento:</strong> <?php echo esc_html($boleto['data_vencimento'] ?? ''); ?></p>
                <p><strong>Status:</strong> <?php echo esc_html($status_raw); ?></p>

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

                <?php if (!empty($pdf_url)) : ?>
                    <p style="margin-top: 20px;">
                        <a href="<?php echo esc_url($pdf_url); ?>" target="_blank" rel="noopener" style="display: inline-block; padding: 10px 14px; border-radius: 6px; background: #111827; color: #fff; text-decoration: none;">
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

        $ambiente = get_option('pbb_banrisul_ambiente', 'sandbox');
        $base_url = get_option('pbb_banrisul_base_url', 'https://apidev.banrisul.com.br/cobranca/v1');
        $oauth_url = get_option('pbb_banrisul_oauth_url', '');
        $client_id = get_option('pbb_banrisul_client_id', '');
        $client_secret = get_option('pbb_banrisul_client_secret', '');
        $beneficiario = get_option('pbb_banrisul_beneficiario', '0010000001088');
        $propovoice_username = get_option('pbb_propovoice_username', '');
        $propovoice_password = get_option('pbb_propovoice_app_password', '');
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
                                <option value="sandbox" <?php selected($ambiente, 'sandbox'); ?>>Sandbox</option>
                                <option value="producao" <?php selected($ambiente, 'producao'); ?>>Produção</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <h2>Banrisul</h2>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="pbb_banrisul_client_id">Client ID</label></th>
                        <td><input type="text" class="regular-text" name="pbb_banrisul_client_id" id="pbb_banrisul_client_id" value="<?php echo esc_attr($client_id); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pbb_banrisul_client_secret">Client Secret</label></th>
                        <td><input type="password" class="regular-text" name="pbb_banrisul_client_secret" id="pbb_banrisul_client_secret" value="<?php echo esc_attr($client_secret); ?>" autocomplete="off"></td>
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
                        <th scope="row"><label for="pbb_propovoice_username">Usuário WordPress</label></th>
                        <td><input type="text" class="regular-text" name="pbb_propovoice_username" id="pbb_propovoice_username" value="<?php echo esc_attr($propovoice_username); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="pbb_propovoice_app_password">Application Password</label></th>
                        <td><input type="password" class="regular-text" name="pbb_propovoice_app_password" id="pbb_propovoice_app_password" value="<?php echo esc_attr($propovoice_password); ?>" autocomplete="off"></td>
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

        update_option('pbb_banrisul_ambiente', sanitize_text_field($_POST['pbb_banrisul_ambiente'] ?? 'sandbox'));
        update_option('pbb_banrisul_client_id', sanitize_text_field($_POST['pbb_banrisul_client_id'] ?? ''));
        update_option('pbb_banrisul_client_secret', sanitize_text_field($_POST['pbb_banrisul_client_secret'] ?? ''));
        update_option('pbb_banrisul_oauth_url', esc_url_raw($_POST['pbb_banrisul_oauth_url'] ?? ''));
        update_option('pbb_banrisul_base_url', esc_url_raw($_POST['pbb_banrisul_base_url'] ?? ''));
        update_option('pbb_banrisul_beneficiario', sanitize_text_field($_POST['pbb_banrisul_beneficiario'] ?? ''));
        update_option('pbb_propovoice_username', sanitize_text_field($_POST['pbb_propovoice_username'] ?? ''));
        update_option('pbb_propovoice_app_password', sanitize_text_field($_POST['pbb_propovoice_app_password'] ?? ''));
        update_option('pbb_boleto_especie', sanitize_text_field($_POST['pbb_boleto_especie'] ?? '99'));
        update_option('pbb_boleto_aceite', sanitize_text_field($_POST['pbb_boleto_aceite'] ?? 'A'));
        update_option('pbb_boleto_juros_codigo', sanitize_text_field($_POST['pbb_boleto_juros_codigo'] ?? '3'));
        update_option('pbb_boleto_pag_parcial_autoriza', sanitize_text_field($_POST['pbb_boleto_pag_parcial_autoriza'] ?? '1'));
        update_option('pbb_boleto_pag_parcial_codigo', sanitize_text_field($_POST['pbb_boleto_pag_parcial_codigo'] ?? '3'));

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

        $repo = new PBB_Boletos_Repository();
        $boletos = $repo->list_recent(20);
        ?>
        <div class="wrap">
            <h1>Gerar Boleto Banrisul por Invoice</h1>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('pbb_generate_boleto'); ?>
                <input type="hidden" name="action" value="pbb_generate_boleto">

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="invoice_id">ID da Invoice PROPOVOICE</label></th>
                        <td>
                            <input type="number" name="invoice_id" id="invoice_id" class="regular-text" required>
                            <p class="description">Exemplo do seu teste: 2296.</p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Gerar boleto Banrisul'); ?>
            </form>

            <hr>

            <h2>Últimos boletos gerados</h2>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Cliente</th>
                        <th>Valor</th>
                        <th>Vencimento</th>
                        <th>Nosso número</th>
                        <th>Linha digitável</th>
                        <th>PDF</th>
                        <th>Link público</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($boletos)) : ?>
                        <tr><td colspan="9">Nenhum boleto gerado ainda.</td></tr>
                    <?php else : ?>
                        <?php foreach ($boletos as $boleto) : ?>
                            <?php
                            $link_publico = '';
                            if (!empty($boleto['propovoice_invoice_id']) && !empty($boleto['propovoice_invoice_token'])) {
                                $link_publico = add_query_arg(
                                    [
                                        'invoice_id' => $boleto['propovoice_invoice_id'],
                                        'token' => $boleto['propovoice_invoice_token'],
                                    ],
                                    site_url('/banrisul-boleto/')
                                );
                            }
                            ?>
                            <tr>
                                <td><?php echo esc_html($boleto['propovoice_invoice_num']); ?></td>
                                <td><?php echo esc_html($boleto['client_name']); ?></td>
                                <td><?php echo esc_html($boleto['valor_nominal']); ?></td>
                                <td><?php echo esc_html($boleto['data_vencimento']); ?></td>
                                <td><?php echo esc_html($boleto['nosso_numero']); ?></td>
                                <td style="max-width: 280px; word-break: break-all;"><?php echo esc_html($boleto['linha_digitavel']); ?></td>
                                <td>
                                    <?php if (!empty($boleto['pdf_url'])) : ?>
                                        <a href="<?php echo esc_url($boleto['pdf_url']); ?>" target="_blank" rel="noopener">Abrir PDF</a>
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
                                <td><?php echo esc_html($boleto['status_local']); ?></td>
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

        if (!$invoice_id) {
            self::set_notice('Informe um ID de invoice válido.', 'error');
            wp_safe_redirect(admin_url('admin.php?page=pbb-generate'));
            exit;
        }

        try {
            $propovoice = new PBB_Propovoice_API();
            $banrisul = new PBB_Banrisul_API();
            $repo = new PBB_Boletos_Repository();

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

    private static function find_invoice_by_id(array $response, int $invoice_id): ?array
    {
        $items = $response['data']['result'] ?? [];
        foreach ($items as $item) {
            if ((int)($item['id'] ?? 0) === $invoice_id) {
                return $item;
            }
        }
        return null;
    }

    private static function build_banrisul_payload(array $invoice, array $contact): array
    {
        self::validate_invoice_and_contact($invoice, $contact);

        $ambiente_plugin = get_option('pbb_banrisul_ambiente', 'sandbox');
        $ambiente_banrisul = $ambiente_plugin === 'producao' ? 'P' : 'T';
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

    private static function save_pdf_from_base64(string $base64, string $nosso_numero): array
    {
        $upload = wp_upload_dir();
        if (!empty($upload['error'])) {
            throw new Exception('Erro no diretório de upload: ' . $upload['error']);
        }

        $dir = trailingslashit($upload['basedir']) . 'banrisul-boletos';
        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }

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
