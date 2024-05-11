/**
 * {eac}Doojigger for WordPress - Administrator options screen javascript
 * @version 24.0430.1
 */

/*
 * Global eacDoojigger admin script object
 */
if (typeof eacDoojigger == 'undefined') eacDoojigger = {};

/*
 * Create and show a dismissible admin notice
 * Based on https://isabelcastillo.com/wp-admin-notice-javascript
 */
eacDoojigger.adminNotice = function( msg, type )
{
    if (!type) type = 'info'; // info (blue), success (green), warning (yellow), error (red)

    /* create notice div */
    var div = document.createElement( 'div' );
    div.classList.add( 'notice', 'notice-'+type, 'is-dismissible' );

    /* create paragraph element to hold message */
    var p = document.createElement( 'p' );
    var s = document.createElement( 'strong' );
    s.appendChild( document.createTextNode( msg ) );
    p.appendChild( s );

    /* Add the whole message to notice div */
    div.appendChild( p );

    /* Create Dismiss icon */
    var b = document.createElement( 'button' );
    b.setAttribute( 'type', 'button' );
    b.classList.add( 'notice-dismiss' );

    /* Add screen reader text to Dismiss icon */
    var bSpan = document.createElement( 'span' );
    bSpan.classList.add( 'screen-reader-text' );
    bSpan.appendChild( document.createTextNode( 'Dismiss this notice' ) );
    b.appendChild( bSpan );

    /* Add dismiss icon to notice */
    div.appendChild( b );

    /* Insert notice after the header */
    var h1 = document.querySelector('.wp-header-end') || document.querySelector('.wrap h1') || document.querySelector('.wrap h2');
    if (h1) h1.after( div );

    /* Make the notice dismissable when the Dismiss icon is clicked */
    b.addEventListener( 'click', function () {div.parentNode.removeChild( div );} );
}

/*
 * Toggle section header & fieldset via details toggle event
 */
eacDoojigger.toggle_settings = function(setName,setOpen)
{
    document.querySelector('details[data-toggle="'+setName+'"]').open = setOpen;
}

/*
 * On DOMContentLoaded
 */
document.addEventListener('DOMContentLoaded',function()
{
    // settings page banner
    var settings_banner = document.getElementById('settings_banner');
    if (settings_banner)
    {
        // make banner sticky
        document.getElementById('wpbody-content').before(settings_banner);
        var height = settings_banner.offsetHeight;
        settings_banner.nextSibling.style.paddingTop = height+'px';
        // move nag notices after the banner
        var elem = document.querySelector('div.wp-header-end');
        document.querySelectorAll('div.update-nag').forEach(function(e) {
            elem.appendChild(e);
        });
        // apply scroll style
        document.addEventListener('scroll',function() {
            window.requestAnimationFrame(function() {
                if (window.scrollY > 20) {
                    settings_banner.classList.add('sticky');
                } else {
                    settings_banner.classList.remove('sticky');
                }
            });
        });
    }
    // togglers - a <details> tag toggling a sibling fieldset
/*
	document.querySelectorAll('fieldset.settings-grid-container').forEach(function(fieldset)
	{
		fieldset.addEventListener('animationend',function(e) {
		//	e.target.style.display = (e.animationName=='settings-easeOut') ? 'none' : '';
		//	e.target.style.height = (e.animationName=='settings-easeOut') ? '0' : 'auto';
		});
	});
 */
    document.querySelectorAll('details[data-toggle]').forEach(function(details)
    {
        details.addEventListener('toggle', function() {
            document.querySelectorAll('fieldset[data-name="'+details.dataset.toggle+'"]').forEach(function(fieldset) {
                if (details.open) {
                    fieldset.classList.replace('settings-closed','settings-opened');
                //    fieldset.querySelectorAll('textarea.code-editor').forEach(function(textarea) {
                //        if (textarea.cmEditor) textarea.cmEditor.codemirror.refresh();
                //    });
                } else {
                    fieldset.classList.replace('settings-opened','settings-closed');
                }
            });
        });
    });
    // Code editor
/*
    ['js','css','html','php'].forEach(function(type)
    {
        document.querySelectorAll('textarea.input-codeedit-'+type).forEach(function(textarea) {
            textarea.cmEditor = wp.codeEditor.initialize(textarea, cm_settings[type]);
        });
    });
 */
    // read-only input type
    document.querySelectorAll('input.input-readonly').forEach(function(input)
    {
        input.readOnly=true;
        input.addEventListener('dblclick',(e) => {input.readOnly=false;input.type='text';});
        input.addEventListener('touchend',(e) => {input.readOnly=false;input.type='text';});
        input.addEventListener('blur',(e) => {input.readOnly=true;});
    });
    // options form submit
    if (options_form = document.getElementById('options_form')) {
        options_form.addEventListener('submit',(e) => {options_form.style.opacity = .5;});
    }
});
