<?php

if (!defined('ABSPATH')) {
    exit;
}

class PBB_Webhook
{
    private const NAMESPACE = 'pbb/v1';
    private const ROUTE = '/webhook/banrisul';

    public static function init(): void
    {
        add_action(
            'rest_api_init',
            [self::class, 'register_routes']
        );
    }

    public static function register_routes(): void
    {
        register_rest_route(
            self::NAMESPACE,
            self::ROUTE,
            [
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => [
                    self::class,
                    'handle_webhook',
                ],
                'permission_callback' => [
                    self::class,
                    'check_permission',
                ],
            ]
        );
    }

    public static function check_permission(
        WP_REST_Request $request
    ) {
        if (!is_ssl()) {
            return new WP_Error(
                'pbb_webhook_https_required',
                'O webhook exige uma conexão HTTPS.',
                [
                    'status' => 403,
                ]
            );
        }

        $remote_addr = sanitize_text_field(
            (string)(
                $_SERVER['REMOTE_ADDR'] ?? ''
            )
        );

        /*
        * Permite testes manuais somente quando a constante
        * estiver explicitamente habilitada no wp-config.php.
        */
        $allow_test_ip = (
            defined('PBB_WEBHOOK_ALLOW_TEST_IP')
            && PBB_WEBHOOK_ALLOW_TEST_IP === true
        );

        if (
            !$allow_test_ip
            && !self::ipv4_in_cidr(
                $remote_addr,
                '201.55.240.0/20'
            )
        ) {
            error_log(
                'PBB Webhook: origem não autorizada.'
            );

            return new WP_Error(
                'pbb_webhook_ip_forbidden',
                'Origem da notificação não autorizada.',
                [
                    'status' => 403,
                ]
            );
        }

        return true;
    }

    public static function handle_webhook(
        WP_REST_Request $request
    ): WP_REST_Response {
        $payload = $request->get_json_params();

        if (!is_array($payload)) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => 'Payload JSON inválido.',
                ],
                400
            );
        }

        $validation = self::validate_payload(
            $payload
        );

        if (is_wp_error($validation)) {
            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => (
                        $validation->get_error_message()
                    ),
                ],
                400
            );
        }

        $event_key = self::build_event_key(
            $validation
        );

        if (
            self::was_processed_recently(
                $event_key
            )
        ) {
            return new WP_REST_Response(
                [
                    'success' => true,
                    'message' => (
                        'Notificação já processada.'
                    ),
                ],
                200
            );
        }

        if (!self::acquire_lock($event_key)) {
            return new WP_REST_Response(
                [
                    'success' => true,
                    'message' => (
                        'Notificação já está em processamento.'
                    ),
                ],
                202
            );
        }

        $repo = new PBB_Boletos_Repository();

        $boleto = $repo->find_for_webhook(
            $validation['beneficiario_codigo'],
            $validation['nosso_numero'],
            $validation['codigo_barras'],
            $validation['linha_digitavel']
        );

        if (!$boleto) {
            self::release_lock($event_key);

            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => (
                        'Boleto não localizado para os '
                        . 'identificadores recebidos.'
                    ),
                ],
                404
            );
        }

        $empresa_id = sanitize_key((string)($boleto['empresa_id'] ?? ''));

        if (
            $empresa_id === ''
            || !PBB_Empresas_Config::exists($empresa_id)
        ) {
            self::release_lock($event_key);

            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => (
                        'Boleto localizado sem empresa '
                        . 'emissora válida.'
                    ),
                ],
                422
            );
        }

        try {
            $banrisul = new PBB_Banrisul_API(
                $empresa_id
            );

            $ambiente = (string)get_option(
                'pbb_banrisul_ambiente',
                'sandbox'
            );

            if ($ambiente === 'sandbox') {
                /*
                * No Sandbox, o Banrisul aceita somente os
                * identificadores previstos nos cenários de teste.
                */
                $codigo_consulta =
                    '04191861100000150502100100000010000000024047';
            } else {
                $codigo_consulta = (
                    $validation['codigo_barras'] ?? ''
                );
            }

            if ($codigo_consulta === '') {
                self::release_lock($event_key);

                return new WP_REST_Response(
                    [
                        'success' => false,
                        'message' => (
                            'A notificação não possui código '
                            . 'de barras para consulta.'
                        ),
                    ],
                    422
                );
            }

            $consulta =
                $banrisul
                    ->consultar_boleto_por_codigo_barras(
                        $codigo_consulta
                    );

            $repo->update_status_from_consulta(
                absint($boleto['id'] ?? 0),
                $consulta
            );

            self::mark_as_processed(
                $event_key
            );

            self::release_lock(
                $event_key
            );

        } catch (Throwable $e) {
            self::release_lock($event_key);

            error_log(
                'PBB Webhook: erro ao consultar boleto '
                . absint($boleto['id'] ?? 0)
                . ': '
                . $e->getMessage()
            );

            return new WP_REST_Response(
                [
                    'success' => false,
                    'message' => (
                        'Não foi possível confirmar a situação '
                        . 'do boleto no Banrisul.'
                    ),
                ],
                502
            );
        }

        return new WP_REST_Response(
            [
                'success' => true,
                'message' => (
                    'Notificação processada com sucesso.'
                ),
            ],
            200
        );
        
    }

    private static function validate_payload(
        array $payload
    ) {
        $nosso_numero = sanitize_text_field(
            (string)(
                $payload['nosso_numero'] ?? ''
            )
        );

        $codigo_barras = preg_replace(
            '/\D+/',
            '',
            (string)(
                $payload['codigo_barras'] ?? ''
            )
        );

        $linha_digitavel = preg_replace(
            '/\D+/',
            '',
            (string)(
                $payload['linha_digitavel'] ?? ''
            )
        );

        $beneficiario_codigo = preg_replace(
            '/\D+/',
            '',
            (string)(
                $payload['beneficiario']['codigo']
                ?? ''
            )
        );

        if ($nosso_numero === '') {
            return new WP_Error(
                'pbb_webhook_nosso_numero',
                'Nosso número não informado.'
            );
        }

        if (
            $codigo_barras === ''
            && $linha_digitavel === ''
        ) {
            return new WP_Error(
                'pbb_webhook_identificador',
                'Código de barras e linha digitável '
                . 'não informados.'
            );
        }

        if (
            strlen($beneficiario_codigo) !== 13
        ) {
            return new WP_Error(
                'pbb_webhook_beneficiario',
                'Código de Beneficiário inválido.'
            );
        }

        return [
            'nosso_numero' => $nosso_numero,
            'codigo_barras' => $codigo_barras,
            'linha_digitavel' => $linha_digitavel,
            'beneficiario_codigo' => ($beneficiario_codigo),
        ];
    }

    private static function build_event_key(
    array $validation
    ): string {
        $content = implode(
            '|',
            [
                $validation['beneficiario_codigo'] ?? '',
                $validation['nosso_numero'] ?? '',
                $validation['codigo_barras'] ?? '',
                $validation['linha_digitavel'] ?? '',
            ]
        );

        return hash(
            'sha256',
            $content
        );
    }

    private static function get_processed_key(
        string $event_key
    ): string {
        return 'pbb_webhook_processed_' . $event_key;
    }

    private static function get_lock_key(
        string $event_key
    ): string {
        return 'pbb_webhook_lock_' . $event_key;
    }

    private static function acquire_lock(
        string $event_key
    ): bool {
        $lock_key = self::get_lock_key(
            $event_key
        );

        $created = add_option(
            $lock_key,
            time(),
            '',
            false
        );

        if ($created) {
            return true;
        }

        $created_at = absint(
            get_option($lock_key, 0)
        );

        /*
        * Remove uma trava abandonada após 60 segundos.
        */
        if (
            $created_at > 0
            && (time() - $created_at) > 60
        ) {
            delete_option($lock_key);

            return add_option(
                $lock_key,
                time(),
                '',
                false
            );
        }

        return false;
    }

    private static function release_lock(
        string $event_key
    ): void {
        delete_option(
            self::get_lock_key($event_key)
        );
    }

    private static function was_processed_recently(
        string $event_key
    ): bool {
        return (
            get_transient(
                self::get_processed_key($event_key)
            ) !== false
        );
    }

    private static function mark_as_processed(
        string $event_key
    ): void {
        set_transient(
            self::get_processed_key($event_key),
            '1',
            45
        );
    }

    private static function ipv4_in_cidr(
        string $ip,
        string $cidr
    ): bool {
        if (
            filter_var(
                $ip,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4
            ) === false
        ) {
            return false;
        }

        $parts = explode('/', $cidr, 2);

        if (count($parts) !== 2) {
            return false;
        }

        [$network, $prefix] = $parts;

        if (
            filter_var(
                $network,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4
            ) === false
        ) {
            return false;
        }

        $prefix = (int)$prefix;

        if ($prefix < 0 || $prefix > 32) {
            return false;
        }

        $ip_long = ip2long($ip);
        $network_long = ip2long($network);

        if (
            $ip_long === false
            || $network_long === false
        ) {
            return false;
        }

        $mask = $prefix === 0
            ? 0
            : (-1 << (32 - $prefix));

        return (
            ($ip_long & $mask)
            === ($network_long & $mask)
        );
    }

}