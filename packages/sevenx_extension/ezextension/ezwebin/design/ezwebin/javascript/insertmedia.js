/*
 * ezwebin: insertMedia( html, ... ) put the markup it was given in place of the script tag that called it (once
 * needed for plug-in media, written with document.write). The media templates print their <video>, <audio> and
 * <object> markup directly now. Kept for sites whose design.ini still lists it in JavaScriptList, and for content
 * that still calls it: the markup is inserted before the calling script, without document.write.
 */
window.insertMedia = window.insertMedia || function insertMedia( ...parts )
{
    const script = document.currentScript;
    const html = parts.join( '' );
    if ( script && script.parentNode )
    {
        script.insertAdjacentHTML( 'beforebegin', html );
    }
    else
    {
        document.body.insertAdjacentHTML( 'beforeend', html );
    }
};
