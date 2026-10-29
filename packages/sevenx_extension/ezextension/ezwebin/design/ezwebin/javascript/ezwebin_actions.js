/*
 * ezwebin: the behavior of the design's templates, in a file instead of inline scripts and on...= attributes,
 * so a site can run with a Content Security Policy of script-src 'self'.
 *
 * Templates print this file with {ezscript( array( 'ezwebin_actions.js' ) )} where they need it; it may be printed
 * more than once on a page and sets itself up only once. Markup it reads:
 *
 *   data-ezwebin-toggle-checkboxes="<name>"   click inverts the checkboxes named <name> in the form named by
 *     data-ezwebin-form (or the closest form)
 *   data-ezwebin-select-all                   button switching all DeleteIDArray[] checkboxes of its form on and off;
 *     labels in data-ezwebin-label-select / data-ezwebin-label-deselect
 *   data-ezwebin-datepicker="date|datetime"   click calls showDatePicker( data-ezwebin-datepicker-base,
 *     data-ezwebin-datepicker-id, type ) when the page loaded the date picker
 *   data-ezwebin-show="<id>"                  click shows the element <id> and hides the link
 *   data-ezwebin-noop                         click does nothing (menu links while editing)
 *   data-ezwebin-diff-mode="<class>"          click sets the class of data-ezwebin-diff-target (version differences);
 *     .ezwebin-js-only[hidden] is shown when scripts run
 *   data-ezwebin-disable-buttons              click disables #PublishButton and #CancelButton right after the submit
 *   data-ezwebin-reset="<id>"                 change sets the value of the element <id> to 0
 *   data-ezwebin-autofocus                    the field is selected and focused when the page has loaded
 *   meta[name="ezwebin-print-link"]           adds the print <link> with the current query string
 *   fieldset.ezcca-collapsible                collapsible attribute groups of the edit page (jQuery)
 *   object[data-ezwebin-silverlight-autosize] the Silverlight plug-in takes the size of its content
 */
(function ( window, document )
{
    'use strict';

    // Silverlight plug-in callbacks, named by <param name="onError|onLoad|onResize">; defined as soon as the file runs.
    if ( typeof window.onErrorHandler !== 'function' )
    {
        window.onErrorHandler = function () {};
    }
    if ( typeof window.onLoadHandler !== 'function' )
    {
        window.onLoadHandler = function () {};
    }
    if ( typeof window.onResizeHandler !== 'function' )
    {
        window.onResizeHandler = function ( sender )
        {
            var host = sender && sender.getHost ? sender.getHost() : null;
            if ( host && host.getAttribute && host.getAttribute( 'data-ezwebin-silverlight-autosize' ) !== null )
            {
                host.width = sender.content.actualWidth;
                host.height = sender.content.actualHeight;
            }
        };
    }

    // The print <link>: the template is cached without the query string, so it is added here.
    function addPrintLink()
    {
        var metas = document.querySelectorAll( 'meta[name="ezwebin-print-link"]:not([data-ezwebin-done])' );
        for ( var i = 0; i < metas.length; i++ )
        {
            var meta = metas[i], link = document.createElement( 'link' );
            meta.setAttribute( 'data-ezwebin-done', '1' );
            link.rel = 'Alternate';
            link.href = meta.getAttribute( 'content' ) + document.location.search;
            link.media = 'print';
            link.title = meta.getAttribute( 'data-title' ) || '';
            ( document.head || document.getElementsByTagName( 'head' )[0] ).appendChild( link );
        }
    }
    addPrintLink();

    if ( window.ezwebinActions )
    {
        return;
    }
    window.ezwebinActions = true;

    function closest( el, selector )
    {
        while ( el && el.nodeType === 1 )
        {
            if ( el.matches ? el.matches( selector ) : el.msMatchesSelector( selector ) )
            {
                return el;
            }
            el = el.parentNode;
        }
        return null;
    }

    function formOf( el )
    {
        var name = el.getAttribute( 'data-ezwebin-form' );
        return ( name && document.forms[name] ) || closest( el, 'form' );
    }

    function setCheckboxes( form, name, value )
    {
        if ( !form )
        {
            return;
        }
        for ( var i = 0, l = form.elements.length; i < l; i++ )
        {
            var e = form.elements[i];
            if ( e.type === 'checkbox' && e.name === name && !e.disabled )
            {
                e.checked = value === null ? !e.checked : value;
            }
        }
    }

    document.addEventListener( 'click', function ( event )
    {
        var el;
        if ( ( el = closest( event.target, '[data-ezwebin-toggle-checkboxes]' ) ) )
        {
            event.preventDefault();
            setCheckboxes( formOf( el ), el.getAttribute( 'data-ezwebin-toggle-checkboxes' ), null );
        }
        else if ( ( el = closest( event.target, '[data-ezwebin-select-all]' ) ) )
        {
            var select = el.value === el.getAttribute( 'data-ezwebin-label-select' );
            el.value = el.getAttribute( select ? 'data-ezwebin-label-deselect' : 'data-ezwebin-label-select' );
            setCheckboxes( formOf( el ), 'DeleteIDArray[]', select );
        }
        else if ( ( el = closest( event.target, '[data-ezwebin-datepicker]' ) ) )
        {
            if ( typeof window.showDatePicker === 'function' )
            {
                window.showDatePicker( el.getAttribute( 'data-ezwebin-datepicker-base' ),
                                       el.getAttribute( 'data-ezwebin-datepicker-id' ),
                                       el.getAttribute( 'data-ezwebin-datepicker' ) );
            }
        }
        else if ( ( el = closest( event.target, '[data-ezwebin-show]' ) ) )
        {
            var target = document.getElementById( el.getAttribute( 'data-ezwebin-show' ) );
            if ( target )
            {
                event.preventDefault();
                target.style.display = '';
                el.style.display = 'none';
            }
        }
        else if ( ( el = closest( event.target, '[data-ezwebin-noop]' ) ) )
        {
            event.preventDefault();
        }
        else if ( ( el = closest( event.target, '[data-ezwebin-diff-mode]' ) ) )
        {
            event.preventDefault();
            var view = document.getElementById( el.getAttribute( 'data-ezwebin-diff-target' ) || 'diffview' );
            if ( view )
            {
                view.className = el.getAttribute( 'data-ezwebin-diff-mode' );
            }
        }
        else if ( ( el = closest( event.target, '[data-ezwebin-disable-buttons]' ) ) )
        {
            window.setTimeout( function ()
            {
                var ids = [ 'PublishButton', 'CancelButton' ];
                for ( var i = 0; i < ids.length; i++ )
                {
                    var b = document.getElementById( ids[i] );
                    if ( b )
                    {
                        b.disabled = true;
                    }
                }
            }, 1 );
        }
    } );

    document.addEventListener( 'change', function ( event )
    {
        var el = closest( event.target, '[data-ezwebin-reset]' ), target;
        if ( el && ( target = document.getElementById( el.getAttribute( 'data-ezwebin-reset' ) ) ) )
        {
            target.value = 0;
        }
    } );

    function ready()
    {
        addPrintLink();

        var hidden = document.querySelectorAll( '.ezwebin-js-only[hidden]' );
        for ( var i = 0; i < hidden.length; i++ )
        {
            hidden[i].removeAttribute( 'hidden' );
        }

        var $ = window.jQuery;
        if ( $ && document.querySelector( 'fieldset.ezcca-collapsible' ) )
        {
            $( 'fieldset.ezcca-collapsible legend a' ).on( 'click', function ( e )
            {
                e.preventDefault();
                var container = $( this.parentNode.parentNode ), inner = container.find( 'div.ezcca-collapsible-fieldset-content' );
                if ( container.hasClass( 'ezcca-collapsed' ) )
                {
                    container.removeClass( 'ezcca-collapsed' );
                    inner.slideDown( 150 );
                }
                else
                {
                    inner.slideUp( 150, function ()
                    {
                        $( this.parentNode ).addClass( 'ezcca-collapsed' );
                    } );
                }
            } );
            // Collapse by default
            $( 'fieldset.ezcca-collapsible' ).addClass( 'ezcca-collapsed' ).find( 'div.ezcca-collapsible-fieldset-content' ).hide();
        }
    }

    window.addEventListener( 'load', function ()
    {
        var field = document.querySelector( '[data-ezwebin-autofocus]' );
        if ( field )
        {
            field.select();
            field.focus();
        }
    } );

    if ( document.readyState === 'loading' )
    {
        document.addEventListener( 'DOMContentLoaded', ready );
    }
    else
    {
        ready();
    }
})( window, document );
