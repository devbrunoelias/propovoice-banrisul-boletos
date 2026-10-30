<?php

if (!defined('ABSPATH')) {
    exit;
}

$boleto = isset($boleto) && is_array($boleto)
    ? $boleto
    : [];

$pdf_url = isset($pdf_url)
    ? (string) $pdf_url
    : '';

$nfe_url = isset($nfe_url)
    ? (string) $nfe_url
    : '';

$empresa_nome = isset($empresa_nome)
    && $empresa_nome !== ''
        ? (string) $empresa_nome
        : 'Empresa emissora';

$empresa_logo_url = isset($empresa_logo_url)
    ? (string) $empresa_logo_url
    : '';

/*
 * Logo colorido utilizado somente no rodapé.
 */
$empresa_footer_logo_url = defined('PBB_URL')
    ? PBB_URL
        . 'assets/images/'
        . 'logo-catania-spweb-colorido.png'
    : $empresa_logo_url;    

$status_raw = isset($status_raw)
    ? (string) $status_raw
    : (string) ($boleto['status_local'] ?? '');

$status_key = sanitize_key($status_raw);

/*
 * Rótulos dos status.
 */
$status_labels = [
    'aguardando_pagamento'
        => 'Aguardando pagamento',

    'rejeitado_cip'
        => 'Boleto rejeitado',

    'baixado'
        => 'Boleto baixado',

    'boleto_gerado'
        => 'Boleto gerado',

    'boleto_registrado_sem_pdf'
        => 'PDF indisponível',

    'boleto_sandbox_pdf_exemplo'
        => 'Ambiente de teste',

    'pago'
        => 'Pago',

    'pagamento_identificado'
        => 'Pagamento identificado',

    'credito_retido'
        => 'Crédito retido',

    'baixado_pagamento'
        => 'Baixado por pagamento',

    'baixado_devolucao'
        => 'Baixado por devolução',

    'reembolsado'
        => 'Reembolsado',

    'transferido_cl'
        => 'Transferido para cobrança',

    'protestado'
        => 'Protestado',

    'baixado_no_dia'
        => 'Baixado no dia',

    'devolucao_automatica'
        => 'Devolução automática',

    'em_cartorio'
        => 'Em cartório',

    'negativado'
        => 'Negativado',
];

$status_exibicao = $status_labels[$status_key]
    ?? ucfirst(
        str_replace('_', ' ', $status_key)
    );

$status_pago = in_array(
    $status_key,
    [
        'pago',
        'pagamento_identificado',
        'baixado_pagamento',
    ],
    true
);

$status_nao_pago = in_array(
    $status_key,
    [
        'rejeitado_cip',
        'baixado',
        'baixado_devolucao',
        'reembolsado',
        'baixado_no_dia',
    ],
    true
);

$status_atencao = in_array(
    $status_key,
    [
        'credito_retido',
        'devolucao_automatica',
        'transferido_cl',
        'protestado',
        'em_cartorio',
        'negativado',
    ],
    true
);

$status_visual = 'pendente';
$status_titulo = 'Aguardando pagamento';
$status_descricao = (
    'O boleto está disponível para pagamento.'
);

if ($status_pago) {
    $status_visual = 'pago';
    $status_titulo = 'Pagamento identificado';
    $status_descricao = (
        'Seu pagamento foi processado com sucesso.'
    );
} elseif ($status_nao_pago) {
    $status_visual = 'nao-pago';
    $status_titulo = 'Cobrança indisponível';
    $status_descricao = (
        'Este boleto não está disponível para pagamento.'
    );
} elseif ($status_atencao) {
    $status_visual = 'atencao';
    $status_titulo = $status_exibicao;
    $status_descricao = (
        'Esta cobrança possui uma situação '
        . 'que requer atenção.'
    );

    if ($status_key === 'credito_retido') {
        $status_titulo = 'Pagamento identificado';
        $status_descricao = (
            'O pagamento foi identificado, '
            . 'mas o crédito ainda está retido.'
        );
    } elseif (
        $status_key === 'devolucao_automatica'
    ) {
        $status_titulo = 'Devolução em processamento';
        $status_descricao = (
            'O boleto está em processo '
            . 'de devolução automática.'
        );
    } elseif ($status_key === 'negativado') {
        $status_titulo = 'Cobrança negativada';
        $status_descricao = (
            'O boleto permanece apto para pagamento.'
        );
    }
}

if (
    $status_key === 'boleto_sandbox_pdf_exemplo'
) {
    $status_visual = 'teste';
    $status_titulo = 'Ambiente de teste';
    $status_descricao = (
        'Este documento utiliza o cenário de testes '
        . 'do Banrisul e não representa uma cobrança real.'
    );
}

/*
 * Dados do boleto.
 */
$invoice_num = (string) (
    $boleto['propovoice_invoice_num']
    ?? $boleto['propovoice_invoice_id']
    ?? ''
);

$cliente_nome = (string) (
    $boleto['client_name']
    ?? 'Cliente não informado'
);

$cliente_documento = (string) (
    $boleto['client_document']
    ?? ''
);

$valor_nominal = (float) (
    $boleto['valor_nominal']
    ?? 0
);

$data_vencimento = (string) (
    $boleto['data_vencimento']
    ?? ''
);

$data_emissao = (string) (
    $boleto['created_at']
    ?? ''
);

$linha_digitavel = (string) (
    $boleto['linha_digitavel']
    ?? ''
);

$nosso_numero = (string) (
    $boleto['nosso_numero']
    ?? ''
);

$beneficiario_codigo = (string) (
    $boleto['beneficiario_codigo']
    ?? ''
);

$data_pagamento = (string) (
    $boleto['data_pagamento']
    ?? ''
);

$data_credito = (string) (
    $boleto['data_credito']
    ?? ''
);

$valor_pagamento = (string) (
    $boleto['valor_pagamento']
    ?? ''
);

$especie = (string) get_option(
    'pbb_boleto_especie',
    '99'
);

$aceite = (string) get_option(
    'pbb_boleto_aceite',
    'A'
);

/*
 * Formatação.
 */
$formatar_data = static function (
    string $data
): string {
    $data = trim($data);

    if ($data === '') {
        return 'Não informada';
    }

    $timestamp = strtotime($data);

    if ($timestamp === false) {
        return $data;
    }

    return wp_date('d/m/Y', $timestamp);
};

$formatar_documento = static function (
    string $documento
): string {
    $documento = preg_replace(
        '/\D+/',
        '',
        $documento
    );

    if (strlen($documento) === 11) {
        return preg_replace(
            '/(\d{3})(\d{3})(\d{3})(\d{2})/',
            '$1.$2.$3-$4',
            $documento
        );
    }

    if (strlen($documento) === 14) {
        return preg_replace(
            '/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/',
            '$1.$2.$3/$4-$5',
            $documento
        );
    }

    return $documento;
};

$valor_formatado = number_format_i18n(
    $valor_nominal,
    2
);

$valor_pagamento_formatado = '';

if ($valor_pagamento !== '') {
    $valor_pagamento_formatado = number_format_i18n(
        (float) $valor_pagamento,
        2
    );
}

$tem_boleto = ($pdf_url !== '');
$tem_nfe = ($nfe_url !== '');

$mostrar_linha_digitavel = (
    $linha_digitavel !== ''
    && !$status_nao_pago
);

/*
 * Ícones SVG locais.
 */
$icon = static function (
    string $name,
    string $class = ''
): string {
    $paths = [
        'file' => (
            '<path d="M6 2h7l5 5v15H6z"/>'
            . '<path d="M13 2v6h6"/>'
            . '<path d="M9 13h6M9 17h6"/>'
        ),

        'user' => (
            '<circle cx="12" cy="7" r="4"/>'
            . '<path d="M4 22v-2a8 8 0 0 1 16 0v2"/>'
        ),

        'money' => (
            '<circle cx="12" cy="12" r="10"/>'
            . '<path d="M16 8.5c-.8-.9-2-1.5-4-1.5'
            . 's-3 .8-3 2.2c0 3.3 7 1.8 7 5'
            . ' 0 1.5-1.4 2.8-4 2.8-2 0-3.4-.7-4.2-1.7'
            . 'M12 5v14"/>'
        ),

        'calendar' => (
            '<rect x="3" y="5" width="18" height="16" rx="2"/>'
            . '<path d="M7 3v4M17 3v4M3 10h18"/>'
        ),

        'bank' => (
            '<path d="M3 10h18L12 3z"/>'
            . '<path d="M5 10v8M9 10v8M15 10v8M19 10v8"/>'
            . '<path d="M3 18h18M2 22h20"/>'
        ),

        'check' => (
            '<circle cx="12" cy="12" r="10"/>'
            . '<path d="m7 12 3 3 7-7"/>'
        ),

        'alert' => (
            '<circle cx="12" cy="12" r="10"/>'
            . '<path d="M12 7v6M12 17h.01"/>'
        ),

        'copy' => (
            '<rect x="8" y="8" width="12" height="12" rx="2"/>'
            . '<path d="M16 8V6a2 2 0 0 0-2-2H6'
            . 'a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/>'
        ),

        'barcode' => (
            '<path d="M3 5v14M7 5v14M11 5v14'
            . 'M16 5v14M21 5v14"/>'
        ),

        'help' => (
            '<circle cx="12" cy="12" r="10"/>'
            . '<path d="M9.2 9a3 3 0 1 1 5.5 1.6'
            . 'c-.8 1-2.7 1.4-2.7 3.4M12 18h.01"/>'
        ),

        'shield' => (
            '<path d="M12 2 20 5v6c0 5-3.4 9-8 11'
            . '-4.6-2-8-6-8-11V5z"/>'
            . '<path d="m8.5 12 2.2 2.2 4.8-5"/>'
        ),

        'info' => (
            '<circle cx="12" cy="12" r="10"/>'
            . '<path d="M12 11v6M12 7h.01"/>'
        ),

        'lock' => (
            '<rect x="5" y="10" width="14" height="11" rx="2"/>'
            . '<path d="M8 10V7a4 4 0 0 1 8 0v3"/>'
        ),

        'chevron' => (
            '<path d="m9 18 6-6-6-6"/>'
        ),

        'hash' => (
            '<path d="M10 3 8 21M16 3l-2 18'
            . 'M4 9h16M3 15h16"/>'
        ),
    ];

    $path = $paths[$name]
        ?? $paths['info'];

    return sprintf(
        '<svg class="%1$s" viewBox="0 0 24 24" '
        . 'aria-hidden="true" focusable="false" '
        . 'fill="none" stroke="currentColor" '
        . 'stroke-width="2" stroke-linecap="round" '
        . 'stroke-linejoin="round">%2$s</svg>',
        esc_attr($class),
        $path
    );
};

$status_icon = $status_pago
    ? 'check'
    : (
        $status_nao_pago
            ? 'alert'
            : (
                $status_visual === 'teste'
                    ? 'info'
                    : 'alert'
            )
    );
?>

<!-- wpautop-control-start -->
<div
    class="pbb-v15 pbb-v15--<?php
        echo esc_attr($status_visual);
    ?>"
>
    <header class="pbb-v15__header">
        <div class="pbb-v15__header-inner">
            <div class="pbb-v15__brand">
                <?php if ($empresa_logo_url !== '') : ?>
                    <img
                        src="<?php
                            echo esc_url(
                                $empresa_logo_url
                            );
                        ?>"
                        alt="Catânia e SPWEB"
                        loading="eager"
                        fetchpriority="high"
                    >
                <?php endif; ?>
            </div>

            <div class="pbb-v15__portal">
                <strong>Portal de Cobranças</strong>
                <span>
                    Seus documentos de forma segura e prática
                </span>
            </div>

            <div class="pbb-v15__header-security">
                <?php echo $icon('lock'); ?>

                <div>
                    <strong>Acesso seguro</strong>
                    <span>Seus dados estão protegidos</span>
                </div>
            </div>
        </div>
    </header>

    <main class="pbb-v15__container">
        <div class="pbb-v15__layout">
            <div class="pbb-v15__primary">
                <div class="pbb-v15__charge">
                    <div class="pbb-v15__charge-heading">
                        <div class="pbb-v15__invoice">
                            <span class="pbb-v15__icon-box">
                                <?php echo $icon('file'); ?>
                            </span>

                            <div>
                                <span class="pbb-v15__label">
                                    Invoice
                                </span>

                                <h1>
                                    <?php
                                    echo esc_html($invoice_num);
                                    ?>
                                </h1>

                                <?php if ($data_emissao !== '') : ?>
                                    <small>
                                        Emitida em
                                        <?php
                                        echo esc_html(
                                            $formatar_data(
                                                $data_emissao
                                            )
                                        );
                                        ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="pbb-v15__status-badge">
                            <?php
                            echo $icon(
                                $status_icon,
                                'pbb-v15__status-icon'
                            );
                            ?>

                            <div>
                                <strong>
                                    <?php
                                    echo esc_html(
                                        $status_titulo
                                    );
                                    ?>
                                </strong>

                                <span>
                                    <?php
                                    echo esc_html(
                                        $status_exibicao
                                    );
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="pbb-v15__metrics">
                        <div class="pbb-v15__metric">
                            <?php echo $icon('user'); ?>

                            <div>
                                <span>Cliente</span>

                                <strong>
                                    <?php
                                    echo esc_html(
                                        $cliente_nome
                                    );
                                    ?>
                                </strong>

                                <?php
                                if (
                                    $cliente_documento !== ''
                                ) :
                                    ?>
                                    <small>
                                        <?php
                                        echo esc_html(
                                            $formatar_documento(
                                                $cliente_documento
                                            )
                                        );
                                        ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="pbb-v15__metric">
                            <?php echo $icon('money'); ?>

                            <div>
                                <span>Valor do boleto</span>

                                <strong>
                                    R$
                                    <?php
                                    echo esc_html(
                                        $valor_formatado
                                    );
                                    ?>
                                </strong>
                            </div>
                        </div>

                        <div class="pbb-v15__metric">
                            <?php echo $icon('calendar'); ?>

                            <div>
                                <span>Vencimento</span>

                                <strong>
                                    <?php
                                    echo esc_html(
                                        $formatar_data(
                                            $data_vencimento
                                        )
                                    );
                                    ?>
                                </strong>
                            </div>
                        </div>

                        <div class="pbb-v15__metric">
                            <?php echo $icon('bank'); ?>

                            <div>
                                <span>Beneficiário</span>

                                <strong>
                                    <?php
                                    echo esc_html(
                                        $empresa_nome
                                    );
                                    ?>
                                </strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pbb-v15__payment">
                    <div class="pbb-v15__payment-heading">
                        <?php
                        echo $icon(
                            $status_icon,
                            'pbb-v15__payment-icon'
                        );
                        ?>

                        <div>
                            <h2>
                                <?php
                                echo esc_html(
                                    $status_titulo
                                );
                                ?>
                            </h2>

                            <p>
                                <?php
                                echo esc_html(
                                    $status_descricao
                                );
                                ?>
                            </p>
                        </div>
                    </div>

                    <?php if ($status_pago) : ?>
                        <div class="pbb-v15__payment-data">
                            <div>
                                <?php echo $icon('calendar'); ?>

                                <span>Data do pagamento</span>

                                <strong>
                                    <?php
                                    echo esc_html(
                                        $formatar_data(
                                            $data_pagamento
                                        )
                                    );
                                    ?>
                                </strong>
                            </div>

                            <div>
                                <?php echo $icon('money'); ?>

                                <span>Valor pago</span>

                                <strong>
                                    R$
                                    <?php
                                    echo esc_html(
                                        $valor_pagamento_formatado
                                    );
                                    ?>
                                </strong>
                            </div>

                            <div>
                                <?php echo $icon('bank'); ?>

                                <span>Data do crédito</span>

                                <strong>
                                    <?php
                                    echo esc_html(
                                        $formatar_data(
                                            $data_credito
                                        )
                                    );
                                    ?>
                                </strong>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($mostrar_linha_digitavel) : ?>
                    <div class="pbb-v15__barcode">
                        <h2>
                            <?php echo $icon('barcode'); ?>
                            Linha digitável
                        </h2>

                        <div class="pbb-v15__barcode-row">
                            <label
                                class="screen-reader-text"
                                for="pbb-linha-digitavel"
                            >
                                Linha digitável do boleto
                            </label>

                            <input
                                id="pbb-linha-digitavel"
                                type="text"
                                dir="ltr"
                                spellcheck="false"
                                autocomplete="off"
                                value="<?php
                                    echo esc_attr(
                                        $linha_digitavel
                                    );
                                ?>"
                                readonly
                                inputmode="numeric"
                            >

                            <button
                                type="button"
                                data-pbb-copy
                                data-copy-target="pbb-linha-digitavel"
                            >
                                <?php echo $icon('copy'); ?>

                                <span data-pbb-copy-label>
                                    Copiar
                                </span>
                            </button>
                        </div>

                        <p
                            class="pbb-v15__copy-feedback"
                            data-pbb-copy-feedback
                            aria-live="polite"
                        ></p>
                    </div>
                <?php endif; ?>

                <div class="pbb-v15__main-documents">
                    <?php if ($tem_boleto) : ?>
                        <a
                            class="pbb-v15__main-document
                                pbb-v15__main-document--boleto"
                            href="<?php
                                echo esc_url($pdf_url);
                            ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <?php echo $icon('file'); ?>

                            <span>
                                <strong>
                                    Abrir PDF do boleto
                                </strong>

                                <small>
                                    Visualizar ou baixar o boleto
                                </small>
                            </span>

                            <?php echo $icon('chevron'); ?>
                        </a>
                    <?php endif; ?>

                    <?php if ($tem_nfe) : ?>
                        <a
                            class="pbb-v15__main-document
                                pbb-v15__main-document--nfe"
                            href="<?php
                                echo esc_url($nfe_url);
                            ?>"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <?php echo $icon('file'); ?>

                            <span>
                                <strong>
                                    Abrir PDF da nota fiscal
                                </strong>

                                <small>
                                    Visualizar ou baixar a NFe
                                </small>
                            </span>

                            <?php echo $icon('chevron'); ?>
                        </a>
                    <?php else : ?>
                        <div
                            class="pbb-v15__main-document
                                pbb-v15__main-document--disabled"
                        >
                            <?php echo $icon('file'); ?>

                            <span>
                                <strong>Nota fiscal</strong>

                                <small>
                                    Documento ainda não anexado
                                </small>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="pbb-v15__document-help">
                    <?php echo $icon('help'); ?>

                    <div>
                        <strong>
                            Não conseguiu abrir um documento?
                        </strong>

                        <span>
                            Consulte “Precisa de ajuda?” nesta página.
                            As orientações resolvem os problemas
                            mais comuns de navegador e acesso.
                        </span>
                    </div>

                    <a href="#pbb-v15-help">
                        Ver soluções
                        <?php echo $icon('chevron'); ?>
                    </a>
                </div>

                <div class="pbb-v15__summary">
                    <h2>Resumo da cobrança</h2>

                    <dl>
                        <div>
                            <dt>
                                <?php echo $icon('hash'); ?>
                                Nosso número
                            </dt>

                            <dd>
                                <?php
                                echo esc_html(
                                    $nosso_numero
                                    ?: 'Não informado'
                                );
                                ?>
                            </dd>
                        </div>

                        <div>
                            <dt>
                                <?php echo $icon('file'); ?>
                                Espécie
                            </dt>

                            <dd>
                                <?php
                                echo esc_html($especie);
                                ?>
                                - Outros
                            </dd>
                        </div>

                        <div>
                            <dt>
                                <?php echo $icon('check'); ?>
                                Aceite
                            </dt>

                            <dd>
                                <?php
                                echo esc_html($aceite);
                                ?>
                                - Aceite
                            </dd>
                        </div>

                        <?php
                        if (
                            $beneficiario_codigo !== ''
                        ) :
                            ?>
                            <div>
                                <dt>
                                    <?php echo $icon('bank'); ?>
                                    Beneficiário
                                </dt>

                                <dd>
                                    <?php
                                    echo esc_html(
                                        $beneficiario_codigo
                                    );
                                    ?>
                                </dd>
                            </div>
                        <?php endif; ?>

                        <div>
                            <dt>
                                <?php echo $icon('bank'); ?>
                                Emissora
                            </dt>

                            <dd>
                                <?php
                                echo esc_html(
                                    $empresa_nome
                                );
                                ?>
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="pbb-v15__contact">
                    <?php echo $icon('info'); ?>

                    <div>
                        <strong>
                            Ainda precisa de ajuda?
                        </strong>

                        <span>
                            Se as orientações da seção “Precisa de ajuda?”
                            não resolverem, entre em contato pelos canais
                            habituais de atendimento e informe o número
                            <?php echo esc_html($invoice_num); ?>.
                        </span>
                    </div>
                </div>
            </div>

            <aside class="pbb-v15__sidebar">
                <div class="pbb-v15__side-card pbb-v15__side-documents">
                    <div class="pbb-v15__side-heading">
                        <span>
                            <?php echo $icon('file'); ?>
                        </span>

                        <div>
                            <h2>
                                Documentos disponíveis
                            </h2>

                            <p>
                                Acesse seus documentos.
                            </p>
                        </div>
                    </div>

                    <?php if ($tem_boleto) : ?>
                        <div class="pbb-v15__side-document">
                            <span
                                class="pbb-v15__pdf-icon
                                    pbb-v15__pdf-icon--red"
                            >
                                PDF
                            </span>

                            <div>
                                <strong>Boleto bancário</strong>
                                <small>Documento em PDF</small>
                            </div>

                            <a
                                href="<?php
                                    echo esc_url($pdf_url);
                                ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Abrir
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="pbb-v15__side-document">
                        <span
                            class="pbb-v15__pdf-icon
                                pbb-v15__pdf-icon--green"
                        >
                            NFe
                        </span>

                        <div>
                            <strong>Nota fiscal eletrônica</strong>

                            <small>
                                <?php
                                echo $tem_nfe
                                    ? 'Documento em PDF'
                                    : 'Ainda não anexada';
                                ?>
                            </small>
                        </div>

                        <?php if ($tem_nfe) : ?>
                            <a
                                href="<?php
                                    echo esc_url($nfe_url);
                                ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                Abrir
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="pbb-v15-help" class="pbb-v15__side-card pbb-v15__help">
                    <div class="pbb-v15__side-heading">
                        <span>
                            <?php echo $icon('help'); ?>
                        </span>

                        <div>
                            <h2>Precisa de ajuda?</h2>

                            <p>
                                Respostas para dúvidas comuns.
                            </p>
                        </div>
                    </div>

                    <?php
                    $faq_items = [
                        [
                            'id' => 'pbb-v15-faq-token',
                            'question' => (
                                'O que significa acesso protegido por token?'
                            ),
                            'answer' => (
                                'O token é uma chave exclusiva incluída no endereço '
                                . 'desta página. Ele permite consultar os documentos '
                                . 'sem informar usuário e senha. Não encaminhe este '
                                . 'link para terceiros e não publique o endereço.'
                            ),
                        ],
                        [
                            'id' => 'pbb-v15-faq-link',
                            'question' => (
                                'O link não abriu. O que devo fazer?'
                            ),
                            'answer' => (
                                'Confirme se o endereço foi copiado por completo, '
                                . 'incluindo todos os caracteres após “token=”. '
                                . 'Abra o link novamente diretamente da mensagem '
                                . 'recebida, sem editar ou remover partes do endereço.'
                            ),
                        ],
                        [
                            'id' => 'pbb-v15-faq-browser',
                            'question' => (
                                'A página ou o documento não carregou corretamente'
                            ),
                            'answer' => (
                                'Atualize a página e tente novamente. Se o problema '
                                . 'continuar, abra o link em uma janela anônima ou '
                                . 'privativa. Você também pode testar outro navegador '
                                . 'atualizado, como Chrome, Edge, Firefox ou Safari.'
                            ),
                        ],
                        [
                            'id' => 'pbb-v15-faq-blocked',
                            'question' => (
                                'Cliquei no documento e nada aconteceu'
                            ),
                            'answer' => (
                                'O navegador pode ter bloqueado a abertura de uma '
                                . 'nova guia ou o download. Verifique o aviso de '
                                . 'pop-up bloqueado próximo à barra de endereços, '
                                . 'permita a abertura para este site e tente novamente.'
                            ),
                        ],
                        [
                            'id' => 'pbb-v15-faq-payment',
                            'question' => 'Como faço o pagamento?',
                            'answer' => (
                                'Abra o PDF do boleto ou copie a linha digitável. '
                                . 'Antes de confirmar o pagamento, confira o valor, '
                                . 'a data de vencimento e o nome do beneficiário '
                                . 'exibido pela instituição financeira.'
                            ),
                        ],
                        [
                            'id' => 'pbb-v15-faq-expired',
                            'question' => (
                                'O que fazer se o boleto estiver vencido?'
                            ),
                            'answer' => (
                                'Verifique se sua instituição financeira atualiza '
                                . 'automaticamente os valores. Se o pagamento não '
                                . 'for aceito, entre em contato com a Catânia '
                                . 'e solicite um novo boleto para pagamento.'
                            ),
                        ],
                        [
                            'id' => 'pbb-v15-faq-nfe',
                            'question' => (
                                'Onde encontro a nota fiscal?'
                            ),
                            'answer' => (
                                'Quando disponível, a nota fiscal pode ser aberta '
                                . 'pelo botão “Abrir PDF da nota fiscal”. Se o documento '
                                . 'estiver indicado como não anexado, aguarde sua '
                                . 'disponibilização ou entre em contato.'
                            ),
                        ],
                        [
                            'id' => 'pbb-v15-faq-confirmation',
                            'question' => (
                                'Como posso confirmar o pagamento?'
                            ),
                            'answer' => (
                                'A identificação bancária pode não ser imediata. '
                                . 'Guarde o comprovante. O status desta página será '
                                . 'atualizado assim que o pagamento for processado.'
                            ),
                        ],
                    ];
                    ?>

                    <div class="pbb-v15__faq-items">
                        <?php foreach ($faq_items as $item) : ?>
                            <div class="pbb-v15__faq-item">
                                <button
                                    type="button"
                                    aria-expanded="false"
                                    aria-controls="<?php
                                        echo esc_attr(
                                            $item['id']
                                        );
                                    ?>"
                                    data-pbb-accordion
                                >
                                    <span>
                                        <?php
                                        echo esc_html(
                                            $item['question']
                                        );
                                        ?>
                                    </span>

                                    <?php echo $icon('chevron'); ?>
                                </button>

                                <div
                                    id="<?php
                                        echo esc_attr(
                                            $item['id']
                                        );
                                    ?>"
                                    class="pbb-v15__faq-answer"
                                    hidden
                                >
                                    <?php
                                    echo esc_html(
                                        $item['answer']
                                    );
                                    ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="pbb-v15__secure">
                    <?php echo $icon('shield'); ?>

                    <div>
                        <strong>Ambiente seguro</strong>

                        <p>
                            Esta página é exclusiva para
                            consulta da cobrança.
                            O acesso é protegido por token.
                        </p>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <footer class="pbb-v15__footer">
        <div class="pbb-v15__footer-brand">
            <?php
            if (
                $empresa_footer_logo_url !== ''
            ) :
                ?>
                <img
                    src="<?php
                        echo esc_url(
                            $empresa_footer_logo_url
                        );
                    ?>"
                    alt="Catânia e SPWEB"
                    loading="lazy"
                >
            <?php endif; ?>

            <span></span>

            <small>Desenvolvido por Catânia Studio</small>
        </div>

        <p>
            © <?php echo esc_html(wp_date('Y')); ?>
            <?php echo esc_html($empresa_nome); ?>.
            Todos os direitos reservados.
        </p>
    </footer>
</div>
<!-- wpautop-control-end -->