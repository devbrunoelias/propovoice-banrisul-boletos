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
        if (get_option('pbb_banrisul_ambiente', 'sandbox') !== 'producao') {
            return;
        }

        try {
            $repo = new PBB_Boletos_Repository();
            $banrisul = new PBB_Banrisul_API();

            $boletos = $repo->list_for_status_sync(50);

            foreach ($boletos as $boleto) {
                if (
                    empty($boleto['id'])
                    || empty($boleto['codigo_barras'])
                ) {
                    continue;
                }

                try {
                    $consulta = $banrisul->consultar_boleto_por_codigo_barras(
                        (string) $boleto['codigo_barras']
                    );

                    $repo->update_status_from_consulta(
                        (int) $boleto['id'],
                        $consulta
                    );
                } catch (Throwable $e) {
                    error_log(
                        'PBB Cron, boleto ' .
                        absint($boleto['id']) .
                        ': ' .
                        $e->getMessage()
                    );
                }
            }
        } catch (Throwable $e) {
            error_log('PBB Cron: ' . $e->getMessage());
        }
    }
}