<?php

if (!defined('ABSPATH')) {
    exit;
}

class PBB_Propovoice_API
{
    private string $base_url;
    private string $username;
    private string $password;

    public function __construct()
    {
        $this->base_url = home_url('/wp-json/ndpv/v1');
        $this->username = get_option('pbb_propovoice_username');
        $this->password = get_option('pbb_propovoice_app_password');
    }

    private function auth_header(): string
    {
        return 'Basic ' . base64_encode($this->username . ':' . $this->password);
    }

    public function list_invoices(): array
    {
        $response = wp_remote_get($this->base_url . '/invoices?path=invoice', [
            'timeout' => 20,
            'headers' => [
                'Authorization' => $this->auth_header(),
                'Accept' => 'application/json',
            ],
        ]);

        return $this->handle_response($response);
    }

    public function get_contact(int $contact_id): array
    {
        $response = wp_remote_get($this->base_url . '/contacts/' . $contact_id, [
            'timeout' => 20,
            'headers' => [
                'Authorization' => $this->auth_header(),
                'Accept' => 'application/json',
            ],
        ]);

        return $this->handle_response($response);
    }

    private function handle_response($response): array
    {
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        $data = json_decode($body, true);

        if ($code < 200 || $code >= 300) {
            throw new Exception('Erro PROPOVOICE HTTP ' . $code . ': ' . $body);
        }

        if (!is_array($data)) {
            throw new Exception('Resposta inválida do PROPOVOICE.');
        }

        return $data;
    }
}