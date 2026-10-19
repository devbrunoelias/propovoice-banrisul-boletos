<?php

if (!defined('ABSPATH')) {
    exit;
}

class PBB_Empresas_Config
{
    public static function all(): array
    {
        return [
            'empresa_1' => [
                'id' => 'empresa_1',

                'nome' => defined('PBB_EMPRESA_1_NOME')
                    ? (string)PBB_EMPRESA_1_NOME
                    : 'Empresa 1',

                'cnpj' => defined('PBB_EMPRESA_1_CNPJ')
                    ? preg_replace(
                        '/\D+/',
                        '',
                        (string)PBB_EMPRESA_1_CNPJ
                    )
                    : '',

                'beneficiario_codigo' => defined(
                    'PBB_EMPRESA_1_BENEFICIARIO'
                )
                    ? (string)PBB_EMPRESA_1_BENEFICIARIO
                    : '',

                'producao_client_id' => defined(
                    'PBB_EMPRESA_1_PRODUCAO_CLIENT_ID'
                )
                    ? (string)PBB_EMPRESA_1_PRODUCAO_CLIENT_ID
                    : '',

                'producao_client_secret' => defined(
                    'PBB_EMPRESA_1_PRODUCAO_CLIENT_SECRET'
                )
                    ? (string)PBB_EMPRESA_1_PRODUCAO_CLIENT_SECRET
                    : '',
            ],

            'empresa_2' => [
                'id' => 'empresa_2',

                'nome' => defined('PBB_EMPRESA_2_NOME')
                    ? (string)PBB_EMPRESA_2_NOME
                    : 'Empresa 2',

                'cnpj' => defined('PBB_EMPRESA_2_CNPJ')
                    ? preg_replace(
                        '/\D+/',
                        '',
                        (string)PBB_EMPRESA_2_CNPJ
                    )
                    : '',

                'beneficiario_codigo' => defined(
                    'PBB_EMPRESA_2_BENEFICIARIO'
                )
                    ? (string)PBB_EMPRESA_2_BENEFICIARIO
                    : '',

                'producao_client_id' => defined(
                    'PBB_EMPRESA_2_PRODUCAO_CLIENT_ID'
                )
                    ? (string)PBB_EMPRESA_2_PRODUCAO_CLIENT_ID
                    : '',

                'producao_client_secret' => defined(
                    'PBB_EMPRESA_2_PRODUCAO_CLIENT_SECRET'
                )
                    ? (string)PBB_EMPRESA_2_PRODUCAO_CLIENT_SECRET
                    : '',
            ],
        ];
    }

    public static function get(string $empresa_id): array
    {
        $empresa_id = sanitize_key($empresa_id);
        $empresas = self::all();

        if (
            $empresa_id === ''
            || !isset($empresas[$empresa_id])
        ) {
            throw new Exception(
                'Empresa emissora inválida.'
            );
        }

        return $empresas[$empresa_id];
    }

    public static function exists(string $empresa_id): bool
    {
        $empresa_id = sanitize_key($empresa_id);
        $empresas = self::all();

        return (
            $empresa_id !== ''
            && isset($empresas[$empresa_id])
        );
    }

    public static function validate_for_environment(
        string $empresa_id,
        string $ambiente
    ): array {
        $empresa = self::get($empresa_id);

        if ($empresa['beneficiario_codigo'] === '') {
            throw new Exception(
                'Código de Beneficiário não configurado para '
                . $empresa['nome']
                . '.'
            );
        }

        if (in_array($ambiente, ['homologacao', 'producao'], true)) 
        {
            if (
                $empresa['producao_client_id'] === ''
                || $empresa['producao_client_secret'] === ''
            ) {
                throw new Exception(
                    'Credenciais de produção não configuradas para '
                    . $empresa['nome']
                    . '.'
                );
            }
        }

        return $empresa;
    }
}