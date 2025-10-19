(function ($) {
    $('.js-copy-link').on('click', function (e) {
        e.preventDefault();
        let copyContainer = $(this).parent();
        let copyText = copyContainer.find('.js-copy-value').text();
        copyToClipboard(copyText.trim());
    });

    $('.js-copy-all-link').on('click', function (e) {
        e.preventDefault();
        let copyText = '';
        const copyContainer = $(this).parents('.box');
        console.log(copyContainer);
        copyContainer.find('.js-copy-title, .js-copy-value').each(function() {
            console.log(copyContainer);
            if ($(this).hasClass('js-copy-value')) {
                const parentLi = $(this).parents('li');
                let label = parentLi.find('b').text();
                let value = parentLi.find('.js-copy-value').text();

                copyText += label.trim() + ' ' + value.trim() + '\r\n';
            } else {
                copyText += '\r\n' + $(this).text().trim() + '\r\n';
            }

        });
        console.log(copyText);
        copyToClipboard(copyText);
    });

    function copyToClipboard(textToCopy) {
        // navigator clipboard api needs a secure context (https)
        if (navigator.clipboard && window.isSecureContext) {
            // navigator clipboard api method'
            return navigator.clipboard.writeText(textToCopy);
        } else {
            // text area method
            let textArea = document.createElement("textarea");
            textArea.value = textToCopy;
            // make the textarea out of viewport
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            return new Promise((res, rej) => {
                // here the magic happens
                document.execCommand('copy') ? res() : rej();
                textArea.remove();
            });
        }
    }

})(jQuery);