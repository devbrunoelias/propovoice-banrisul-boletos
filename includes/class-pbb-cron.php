<?php

if (!defined('ABSPATH')) {
    exit;
}

class PBB_Cron
{
    public const HOOK = 'pbb_consultar_boletos_cron';

    public static function init(): void
    {
        add_action(self::HOOK, [__CLASS__, 'consultar_boletos']);
    }

    public static function schedule(): void
    {
        if (!wp_next_scheduled(self::HOOK)) {
            wp_schedule_event(time() + 300, 'hourly', self::HOOK);
        }
    }

    public static function unschedule(): void
    {
        wp_clear_scheduled_hook(self::HOOK);
    }

    public static function consultar_boletos(): void
    {
        $ambiente = (string)get_option(
            'pbb_banrisul_ambiente',
            'sandbox'
        );

        if (
            !in_array(
                $ambiente,
                ['homologacao', 'producao'],
                true
            )
        ) {
            return;
        }

        try {
            $repo = new PBB_Boletos_Repository();

            $boletos = $repo->list_for_status_sync(50);

            foreach ($boletos as $boleto) {
                try {
                    $boleto_id = absint(
                        $boleto['id'] ?? 0
                    );

                    $codigo_barras = (string)(
                        $boleto['codigo_barras'] ?? ''
                    );

                    if (
                        !$boleto_id
                        || $codigo_barras === ''
                    ) {
                        continue;
                    }

                    $empresa_id = sanitize_key(
                        (string)(
                            $boleto['empresa_id'] ?? ''
                        )
                    );

                    if ($empresa_id === '') {
                        error_log(
                            'PBB Cron: boleto '
                            . $boleto_id
                            . ' sem empresa emissora vinculada.'
                        );

                        continue;
                    }

                    if (
                        !PBB_Empresas_Config::exists(
                            $empresa_id
                        )
                    ) {
                        error_log(
                            'PBB Cron: empresa inválida no boleto '
                            . $boleto_id
                            . '.'
                        );

                        continue;
                    }

                    $banrisul = new PBB_Banrisul_API(
                        $empresa_id
                    );

                    $consulta =
                        $banrisul
                            ->consultar_boleto_por_codigo_barras(
                                $codigo_barras
                            );

                    $repo->update_status_from_consulta(
                        $boleto_id,
                        $consulta
                    );
                } catch (Throwable $e) {
                    error_log(
                        'PBB Cron: erro no boleto '
                        . absint($boleto['id'] ?? 0)
                        . ': '
                        . $e->getMessage()
                    );
                }
            }
        } catch (Throwable $e) {
            error_log(
                'PBB Cron: erro geral: '
                . $e->getMessage()
            );
        }
    }
}