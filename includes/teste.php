<td>
    <?php if (!empty($link_publico)) : ?>
         ?>" target="_blank" rel="noopener">Abrir página</a><br>
        <button
            type="button"
            onclick="navigator.clipboard.writeText('<?php echo esc_js($link_publico); ?>'); alert('Link público copiado!');"
            class="button button-small"
            style="margin-top: 6px;"
        >
            Copiar link
        </button>
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

<td><?php echo esc_html($boleto['status_local']); ?></td>