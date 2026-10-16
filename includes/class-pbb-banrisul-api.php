<?php

if (!defined('ABSPATH')) {
    exit;
}

class PBB_Banrisul_API
{
    private string $base_url;
    private string $oauth_url;
    private string $client_id;
    private string $client_secret;
    private string $beneficiario;

    public function __construct()
    {
        $this->base_url = rtrim((string) get_option('pbb_banrisul_base_url'), '/');
        $this->oauth_url = (string) get_option('pbb_banrisul_oauth_url');
        $this->client_id = (string) get_option('pbb_banrisul_client_id');
        $this->client_secret = (string) get_option('pbb_banrisul_client_secret');
        $this->beneficiario = (string) get_option('pbb_banrisul_beneficiario');
    }

    private function get_token(): string
{
    $this->validate_settings();

    delete_transient('pbb_banrisul_access_token');

    $basic = base64_encode($this->client_id . ':' . $this->client_secret);

    $response = wp_remote_post($this->oauth_url, [
        'timeout' => 30,
        'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Basic ' . $basic,
        ],
        'body' => [
            'grant_type' => 'client_credentials',
            'scope' => 'boletos',
        ],
    ]);

    $data = $this->handle_response($response, 'OAuth Banrisul');

    if (empty($data['access_token'])) {
        throw new Exception('Token Banrisul não retornado. Resposta: ' . wp_json_encode($data));
    }

    return (string) $data['access_token'];
}

    private function get_ambiente_banrisul(): string
{
    $ambiente_plugin = get_option('pbb_banrisul_ambiente', 'sandbox');

    return $ambiente_plugin === 'producao' ? 'P' : 'T';
}

    public function registrar_boleto(array $payload): array
    {
        $this->validate_settings();

        $token = $this->get_token();

        $response = wp_remote_post($this->base_url . '/boletos', [
            'timeout' => 45,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'bergs-beneficiario' => $this->beneficiario,
                'bergs-ambiente' => $this->get_ambiente_banrisul(),
            ],
            'body' => wp_json_encode($payload),
            'data_format' => 'body',
        ]);

        return $this->handle_response($response, 'Registrar boleto');
    }

    public function emitir_boleto_por_codigo_barras(string $codigo_barras): array
    {
        $this->validate_settings();

        $token = $this->get_token();

        $url = $this->base_url . '/boletos/' . rawurlencode($codigo_barras) . '/emitir?tipo_id=CODIGO_BARRAS';

        $response = wp_remote_get($url, [
            'timeout' => 45,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'bergs-beneficiario' => $this->beneficiario,
                'bergs-ambiente' => $this->get_ambiente_banrisul(),
            ],
        ]);

        return $this->handle_response($response, 'Emitir boleto');
    }

    public function consultar_boleto_por_codigo_barras(string $codigo_barras): array
    {
        $this->validate_settings();

        $token = $this->get_token();

        $url = $this->base_url . '/boletos/' . rawurlencode($codigo_barras) . '?tipo_id=CODIGO_BARRAS';

        $response = wp_remote_get($url, [
            'timeout' => 45,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'bergs-beneficiario' => $this->beneficiario,
                'bergs-ambiente' => $this->get_ambiente_banrisul(),
            ],
        ]);

        return $this->handle_response($response, 'Consultar boleto');
    }

    public function baixar_boleto_por_codigo_barras(string $codigo_barras): array
    {
        $this->validate_settings();

        $token = $this->get_token();

        $url = $this->base_url . '/boletos/' . rawurlencode($codigo_barras) . '/baixar?tipo_id=CODIGO_BARRAS';

        $response = wp_remote_post($url, [
            'timeout' => 45,
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'bergs-beneficiario' => $this->beneficiario,
                'bergs-ambiente' => $this->get_ambiente_banrisul(),
            ],
            'body' => '{}',
            'data_format' => 'body',
        ]);

        return $this->handle_response($response, 'Baixar boleto');
    }

    private function validate_settings(): void
    {
        if (!$this->base_url) {
            throw new Exception('Configure a URL Base API do Banrisul.');
        }

        if (!$this->oauth_url) {
            throw new Exception('Configure a URL OAuth2 do Banrisul.');
        }

        if (!$this->client_id) {
            throw new Exception('Configure o Client ID do Banrisul.');
        }

        if (!$this->client_secret) {
            throw new Exception('Configure o Client Secret do Banrisul.');
        }

        if (!$this->beneficiario) {
            throw new Exception('Configure o código do beneficiário Banrisul.');
        }
    }

    private function handle_response($response, string $context): array
    {
        if (is_wp_error($response)) {
            throw new Exception($context . ': ' . $response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        $data = json_decode($body, true);

        if ($code < 200 || $code >= 300) {
            throw new Exception($context . ' HTTP ' . $code . ': ' . $body);
        }

        if (!is_array($data)) {
            throw new Exception($context . ': resposta JSON inválida. Body: ' . $body);
        }

        return $data;
    }
}