requirejs([
    'jquery'
], function ( $ ) {
    $('li[data-ui-id="menu-straker-easytranslationplatform-accounts"]').on('click', function (event) {
        event.preventDefault();
        var url = 'https://deltaray.strakertranslations.com/user/login';
        window.open(url, '_blank');
    });

    $('li[data-ui-id="menu-straker-easytranslationplatform-termsandconditions"]').on('click', function (event) {
        event.preventDefault();
        var url = 'https://www.strakertranslations.com/terms-conditions';
        window.open(url, '_blank');
    });
});