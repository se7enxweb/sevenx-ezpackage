/**
 * xrowmetadata: the license picker of the metadata edit forms (xrowmetadata/license_picker.tpl).
 *
 * Every .xrowmetadata-license-picker of the page is set up once (data-ready): the search field, the group filter and
 * "Show retired licenses" filter the list, the count says how many are shown, and the terms summary and the custom
 * license fields (data-custom-target) follow the chosen license. The texts come from the picker's data-* attributes.
 *
 * The template prints this file with each picker (ezscript): the first copy sets up the pickers of the page, a later
 * copy only sets up pickers added since. The page needs no inline script, so it runs under a Content Security Policy
 * without 'unsafe-inline'. window.xrowMetaDataLicensePicker( element ) sets up a picker added later by a script.
 */
(function () {
    function all() {
        Array.prototype.forEach.call( document.querySelectorAll( '.xrowmetadata-license-picker' ), window.xrowMetaDataLicensePicker );
    }
    if ( window.xrowMetaDataLicensePicker ) {
        if ( document.readyState !== 'loading' ) { all(); }
        return;
    }
    function norm( s ) { return String( s || '' ).toLowerCase().replace( /[\s\-_.,:;()\/]+/g, ' ' ).trim(); }
    function picker( root ) {
        if ( !root || root.getAttribute( 'data-ready' ) ) { return; }
        // The custom fields follow the picker in the form: wait for them
        if ( document.readyState === 'loading' ) {
            document.addEventListener( 'DOMContentLoaded', function () { picker( root ); } );
            return;
        }
        root.setAttribute( 'data-ready', '1' );
        var select = root.querySelector( '.xrowmetadata-license-select' ),
            search = root.querySelector( '.xrowmetadata-license-search' ),
            group = root.querySelector( '.xrowmetadata-license-group' ),
            retired = root.querySelector( '.xrowmetadata-license-retired' ),
            count = root.querySelector( '.xrowmetadata-license-count' ),
            summary = root.querySelector( '.xrowmetadata-license-summary' ),
            customId = root.getAttribute( 'data-custom-target' ),
            custom = customId ? document.getElementById( customId ) : null,
            head = [], groups = [], total = 0;
        if ( !select ) { return; }
        // A snapshot of the list: the options before the first group, then each group with its options
        Array.prototype.forEach.call( select.children, function ( child ) {
            if ( child.tagName === 'OPTGROUP' ) {
                var g = { el: child, options: Array.prototype.slice.call( child.children ) };
                g.options.forEach( function ( o ) { o._x = norm( o.text + ' ' + o.value ); } );
                total += g.options.length;
                groups.push( g );
            } else {
                head.push( child );
            }
        } );
        function filter() {
            var words = norm( search ? search.value : '' ).split( ' ' ).filter( Boolean ),
                wanted = group ? group.value : '',
                showRetired = retired ? retired.checked : false,
                current = select.value, shown = 0;
            groups.forEach( function ( g ) {
                var keep = g.options.filter( function ( o ) {
                    if ( o.value === current ) { return true; }
                    if ( o.getAttribute( 'data-r' ) && !showRetired ) { return false; }
                    if ( wanted && ( ' ' + ( o.getAttribute( 'data-g' ) || '' ) + ' ' ).indexOf( ' ' + wanted + ' ' ) < 0 ) { return false; }
                    for ( var i = 0; i < words.length; i++ ) { if ( o._x.indexOf( words[i] ) < 0 ) { return false; } }
                    return true;
                } );
                while ( g.el.firstChild ) { g.el.removeChild( g.el.firstChild ); }
                keep.forEach( function ( o ) { g.el.appendChild( o ); } );
                shown += keep.length;
                if ( keep.length && g.el.parentNode !== select ) { select.appendChild( g.el ); }
                if ( !keep.length && g.el.parentNode === select ) { select.removeChild( g.el ); }
            } );
            // Keep the groups in their order
            groups.forEach( function ( g ) { if ( g.el.parentNode === select ) { select.appendChild( g.el ); } } );
            head.forEach( function ( o ) { if ( o.parentNode !== select ) { select.insertBefore( o, select.firstChild ); } } );
            if ( select.value !== current ) { select.value = current; }
            if ( count ) {
                count.textContent = ( root.getAttribute( 'data-count' ) || '' ).replace( '%shown', shown ).replace( '%total', total );
            }
        }
        function word( key, v ) {
            if ( v === '-' ) { return root.getAttribute( 'data-not-stated' ); }
            if ( key < 2 ) { return root.getAttribute( v === '1' ? 'data-allowed' : 'data-not-allowed' ); }
            return root.getAttribute( v === '1' ? 'data-required' : 'data-not-required' );
        }
        function update() {
            var o = select.options[select.selectedIndex];
            if ( custom ) {
                var needs = !!( o && o.getAttribute( 'data-c' ) ),
                    filled = Array.prototype.some.call( custom.querySelectorAll( 'input, textarea' ), function ( f ) { return f.value !== ''; } );
                custom.hidden = !( needs || filled );
            }
            if ( !summary ) { return; }
            var names4 = ( root.getAttribute( 'data-term-labels' ) || '' ).split( '|' ),
                code = o ? o.getAttribute( 'data-t' ) : null;
            while ( summary.firstChild ) { summary.removeChild( summary.firstChild ); }
            if ( !o || !code || names4.length !== 4 ) { return; }
            var title = document.createElement( 'span' ),
                list = document.createElement( 'ul' );
            title.className = 'xrowmetadata-license-summary-name';
            title.textContent = ( root.getAttribute( 'data-terms-of' ) || '%name' ).replace( '%name', o.getAttribute( 'data-n' ) || o.text.replace( / - [^()]*$/, '' ) );
            list.className = 'xrowmetadata-license-terms';
            for ( var i = 0; i < 4; i++ ) {
                var li = document.createElement( 'li' );
                li.textContent = names4[i] + ': ' + word( i, code.charAt( i ) || '-' );
                list.appendChild( li );
            }
            summary.appendChild( title );
            summary.appendChild( list );
            var url = o.getAttribute( 'data-u' ) || '';
            if ( url === 's:' ) { url = 'https://spdx.org/licenses/' + o.value + '.html'; }
            else if ( url.indexOf( 'c:' ) === 0 ) { url = 'https://creativecommons.org/licenses/' + url.substring( 2 ); }
            if ( url && /^https?:\/\//i.test( url ) ) {
                var a = document.createElement( 'a' );
                a.className = 'xrowmetadata-license-url';
                a.href = url; a.target = '_blank'; a.rel = 'noopener noreferrer license';
                a.textContent = root.getAttribute( 'data-read-terms' );
                summary.appendChild( a );
            }
        }
        if ( search ) { search.addEventListener( 'input', filter ); }
        if ( group ) { group.addEventListener( 'change', filter ); }
        if ( retired ) { retired.addEventListener( 'change', filter ); }
        select.addEventListener( 'change', function () { update(); } );
        // Enter in the search field picks the first match instead of sending the form
        if ( search ) {
            search.addEventListener( 'keydown', function ( e ) {
                if ( e.key !== 'Enter' ) { return; }
                e.preventDefault();
                var first = select.querySelector( 'optgroup option' );
                if ( first ) { select.value = first.value; update(); select.focus(); }
            } );
        }
        update();
        filter();
    }
    window.xrowMetaDataLicensePicker = picker;
    if ( document.readyState === 'loading' ) { document.addEventListener( 'DOMContentLoaded', all ); } else { all(); }
})();
