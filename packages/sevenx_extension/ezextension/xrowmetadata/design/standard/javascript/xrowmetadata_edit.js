/**
 * xrowmetadata: the object edit form of the metadata attribute (content/datatype/edit/xrowmetadata.tpl).
 *
 * "More options" (#more_checkbox) shows and hides the sitemap options (#options_div), as before. One listener on the
 * document, so the file may be printed once per attribute and needs no inline script (Content Security Policy
 * without 'unsafe-inline').
 */
(function () {
    if ( window.xrowMetaDataEditReady ) { return; }
    window.xrowMetaDataEditReady = true;
    document.addEventListener( 'click', function ( e ) {
        var box = e.target;
        if ( !box || box.id !== 'more_checkbox' ) { return; }
        var options = document.getElementById( 'options_div' );
        if ( !options ) { return; }
        // show or hide, whatever hides it now (the "hide" class or a display of none)
        if ( window.getComputedStyle( options ).display === 'none' ) {
            options.style.display = '';
            if ( window.getComputedStyle( options ).display === 'none' ) { options.style.display = 'block'; }
        } else {
            options.style.display = 'none';
        }
    } );
})();
