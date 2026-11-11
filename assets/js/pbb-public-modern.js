(function () {
    'use strict';

    document.documentElement.classList.add(
    'pbb-public-document'
    );

    function markPublicDocument() {
        if (document.body) {
            document.body.classList.add(
                'pbb-public-document'
            );
        }
    }
    /**
     * Remove tags residuais inseridas por formatadores
     * de conteúdo depois que o shortcode foi processado.
     */
    function cleanFormattingArtifacts() {
        var roots = document.querySelectorAll(
            '.pbb-v15'
        );

        roots.forEach(function (root) {

            /*
            * Remove elementos vazios inseridos imediatamente
            * antes do conteúdo público.
            */
            var previous = root.previousElementSibling;

            while (
                previous
                && (
                    previous.tagName === 'BR'
                    || (
                        previous.tagName === 'P'
                        && (
                            previous.textContent
                            || ''
                        ).trim() === ''
                    )
                )
            ) {
                var elementBefore = (
                    previous.previousElementSibling
                );

                previous.remove();

                previous = elementBefore;
            }

            root.querySelectorAll('br').forEach(
                function (element) {
                    element.remove();
                }
            );

            root.querySelectorAll('p').forEach(
                function (paragraph) {
                    var text = (
                        paragraph.textContent
                        || ''
                    ).trim();

                    var hasRelevantElement = (
                        paragraph.querySelector(
                            'a, button, input, img, svg'
                        ) !== null
                    );

                    /*
                    * Remove parágrafos completamente vazios.
                    */
                    var isFeedback = paragraph.hasAttribute(
                        'data-pbb-copy-feedback'
                    );

                    if (
                        text === ''
                        && !hasRelevantElement
                        && paragraph.children.length === 0
                        && !isFeedback
                    ) {
                        paragraph.remove();
                        return;
                    }

                    /*
                    * Se wpautop colocou um componente inteiro
                    * dentro de um parágrafo, remove apenas o
                    * invólucro e preserva seu conteúdo.
                    */
                    if (
                        paragraph.children.length === 1
                        && [
                            'DIV',
                            'SECTION',
                            'HEADER',
                            'MAIN',
                            'FOOTER',
                            'ASIDE',
                        ].indexOf(
                            paragraph.firstElementChild.tagName
                        ) !== -1
                    ) {
                        paragraph.replaceWith(
                            paragraph.firstElementChild
                        );
                    }
                }
            );
        });
    }

    /**
     * Exibe o resultado da tentativa de cópia.
     */
    function setCopyFeedback(
        button,
        message,
        success
    ) {
        var container = button.closest(
            '.pbb-v15__barcode'
        );

        var label = button.querySelector(
            '[data-pbb-copy-label]'
        );

        var feedback = container
            ? container.querySelector(
                '[data-pbb-copy-feedback]'
            )
            : null;

        if (label) {
            label.textContent = success
                ? 'Código copiado'
                : 'Copiar código';
        }

        if (feedback) {
            feedback.textContent = message;
        }

        button.classList.toggle(
            'is-copied',
            success
        );

        window.setTimeout(function () {
            if (label) {
                label.textContent = 'Copiar código';
            }

            if (feedback) {
                feedback.textContent = '';
            }

            button.classList.remove(
                'is-copied'
            );
        }, 3000);
    }

    /**
     * Copia a linha digitável usando a Clipboard API.
     */
    function handleCopy(button) {
        var targetId = button.getAttribute(
            'data-copy-target'
        );

        var target = document.getElementById(
            targetId
        );

        if (!target) {
            setCopyFeedback(
                button,
                'Linha digitável não encontrada.',
                false
            );

            return;
        }

        var text = (
            target.value
            || target.textContent
            || ''
        ).trim();

        if (!text) {
            setCopyFeedback(
                button,
                'Linha digitável indisponível.',
                false
            );

            return;
        }

        if (
            !window.isSecureContext
            || !navigator.clipboard
            || !navigator.clipboard.writeText
        ) {
            target.focus();
            target.select();

            setCopyFeedback(
                button,
                'Selecione o código e copie manualmente.',
                false
            );

            return;
        }

        navigator.clipboard
            .writeText(text)
            .then(function () {
                setCopyFeedback(
                    button,
                    'Linha digitável copiada com sucesso.',
                    true
                );
            })
            .catch(function () {
                target.focus();
                target.select();

                setCopyFeedback(
                    button,
                    'Não foi possível copiar automaticamente. '
                        + 'Selecione o código e copie manualmente.',
                    false
                );
            });
    }

    /**
 * Fecha uma resposta do FAQ.
 */
function closeAccordion(button) {
    var contentId = button.getAttribute(
        'aria-controls'
    );

    var content = document.getElementById(
        contentId
    );

    button.setAttribute(
        'aria-expanded',
        'false'
    );

    if (content) {
        content.hidden = true;
    }
}

/**
 * Abre uma resposta do FAQ.
 */
    function openAccordion(button) {
    var contentId = button.getAttribute(
        'aria-controls'
    );

    var content = document.getElementById(
        contentId
    );

    button.setAttribute(
        'aria-expanded',
        'true'
    );

    if (content) {
        content.hidden = false;
    }
}

/**
 * Mantém somente uma resposta aberta por vez.
 */
    function handleAccordion(button) {
        var isExpanded = (
            button.getAttribute(
                'aria-expanded'
            ) === 'true'
        );

        var allButtons = document.querySelectorAll(
            '[data-pbb-accordion]'
        );

        allButtons.forEach(function (item) {
            if (item !== button) {
                closeAccordion(item);
            }
        });

        if (isExpanded) {
            closeAccordion(button);
        } else {
            openAccordion(button);
        }
    }

    /**
     * Delegação dos eventos da página pública.
     */
    document.addEventListener(
        'click',
        function (event) {
            var copyButton = event.target.closest(
                '[data-pbb-copy]'
            );

            if (copyButton) {
                handleCopy(copyButton);
                return;
            }

            var accordionButton = (
                event.target.closest(
                    '[data-pbb-accordion]'
                )
            );

            if (accordionButton) {
                handleAccordion(
                    accordionButton
                );
            }
        }
    );
    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            function () {
                markPublicDocument();
                cleanFormattingArtifacts();
            }
        );
    } else {
        markPublicDocument();
        cleanFormattingArtifacts();
    }
}());