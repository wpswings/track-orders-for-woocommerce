/**
 * "Where is my order?" assistant widget.
 *
 * Vanilla JS, no dependencies. All server text is rendered with textContent;
 * only http(s)/mailto links returned by the server are turned into anchors.
 *
 * @since 1.3.0
 */
( function () {
	'use strict';

	var cfg = window.wps_tofw_wismo;
	if ( ! cfg || ! document.body ) {
		return;
	}
	var t = cfg.i18n || {};
	var state = { token: '', busy: false };
	var els = {};

	function el( tag, cls, text ) {
		var n = document.createElement( tag );
		if ( cls ) {
			n.className = cls;
		}
		if ( text !== undefined && text !== null ) {
			n.textContent = String( text );
		}
		return n;
	}

	function post( action, data ) {
		var body = new URLSearchParams();
		body.append( 'action', action );
		body.append( 'nonce', cfg.nonce );
		Object.keys( data || {} ).forEach( function ( k ) {
			body.append( k, data[ k ] );
		} );
		return fetch( cfg.ajaxurl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		} ).then( function ( r ) {
			return r.json().catch( function () {
				return { success: false, data: { message: t.error } };
			} );
		} );
	}

	function scroll() {
		els.log.scrollTop = els.log.scrollHeight;
	}

	function addMessage( who, text, links ) {
		var m = el( 'div', 'wps-tofw-wismo__msg wps-tofw-wismo__msg--' + who );
		m.appendChild( el( 'div', 'wps-tofw-wismo__bubble', text ) );
		if ( links && links.length ) {
			var wrap = el( 'div', 'wps-tofw-wismo__links' );
			links.forEach( function ( l ) {
				if ( ! l || ! /^(https?:|mailto:)/i.test( l.url ) ) {
					return;
				}
				var a = el( 'a', 'wps-tofw-wismo__link', l.label || l.url );
				a.href = l.url;
				if ( ! /^mailto:/i.test( l.url ) ) {
					a.target = '_blank';
					a.rel = 'noopener noreferrer';
				}
				wrap.appendChild( a );
			} );
			m.appendChild( wrap );
		}
		els.log.appendChild( m );
		scroll();
		return m;
	}

	function setFooter( node ) {
		els.footer.innerHTML = '';
		if ( node ) {
			els.footer.appendChild( node );
		}
	}

	function thinking() {
		return addMessage( 'bot', t.thinking );
	}

	function errorMessage( res ) {
		return ( res && res.data && res.data.message ) ? res.data.message : t.error;
	}

	/* ---------- Verification ---------- */

	function showEmailForm() {
		state.token = '';
		addMessage( 'bot', t.verify_intro );
		var form = el( 'form', 'wps-tofw-wismo__form' );
		var idLabel = el( 'label', 'wps-tofw-wismo__label', t.order_number );
		var id = el( 'input', 'wps-tofw-wismo__input' );
		id.type = 'text';
		id.required = true;
		id.inputMode = 'numeric';
		id.autocomplete = 'off';
		idLabel.appendChild( id );
		var mailLabel = el( 'label', 'wps-tofw-wismo__label', t.billing_email );
		var mail = el( 'input', 'wps-tofw-wismo__input' );
		mail.type = 'email';
		mail.required = true;
		mail.autocomplete = 'email';
		mailLabel.appendChild( mail );
		var btn = el( 'button', 'wps-tofw-wismo__btn', t.verify );
		btn.type = 'submit';
		form.appendChild( idLabel );
		form.appendChild( mailLabel );
		form.appendChild( btn );
		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			verify( { mode: 'email', order_id: id.value, email: mail.value } );
		} );
		setFooter( form );
		id.focus();
	}

	function showOrderPicker() {
		state.token = '';
		var wait = thinking();
		post( 'wps_tofw_wismo_my_orders', {} ).then( function ( res ) {
			wait.remove();
			var orders = ( res && res.success && res.data && res.data.orders ) ? res.data.orders : [];
			if ( ! orders.length ) {
				addMessage( 'bot', t.no_orders );
				showEmailForm();
				return;
			}
			addMessage( 'bot', t.pick_order );
			var list = el( 'div', 'wps-tofw-wismo__chips' );
			orders.forEach( function ( o ) {
				var b = el( 'button', 'wps-tofw-wismo__chip', '#' + o.number + ' · ' + o.status + ( o.date ? ' · ' + o.date : '' ) );
				b.type = 'button';
				b.addEventListener( 'click', function () {
					verify( { mode: 'account', order_id: String( o.id ) }, '#' + o.number );
				} );
				list.appendChild( b );
			} );
			var other = el( 'button', 'wps-tofw-wismo__chip wps-tofw-wismo__chip--ghost', t.other_order );
			other.type = 'button';
			other.addEventListener( 'click', showEmailForm );
			list.appendChild( other );
			setFooter( list );
		} ).catch( function () {
			wait.remove();
			showEmailForm();
		} );
	}

	function startVerification() {
		if ( Number( cfg.logged_in ) ) {
			showOrderPicker();
		} else {
			showEmailForm();
		}
	}

	function verify( data, echo ) {
		if ( state.busy ) {
			return;
		}
		state.busy = true;
		addMessage( 'user', echo || ( '#' + String( data.order_id ).replace( /^#/, '' ) ) );
		var wait = thinking();
		post( 'wps_tofw_wismo_verify', data ).then( function ( res ) {
			wait.remove();
			state.busy = false;
			if ( res && res.success && res.data && res.data.token ) {
				state.token = res.data.token;
				addMessage( 'bot', res.data.text, res.data.links );
				showChat();
			} else {
				addMessage( 'bot', errorMessage( res ) );
			}
		} ).catch( function () {
			wait.remove();
			state.busy = false;
			addMessage( 'bot', t.error );
		} );
	}

	/* ---------- Chat ---------- */

	function showChat() {
		var wrap = el( 'div', 'wps-tofw-wismo__chat' );
		var chips = el( 'div', 'wps-tofw-wismo__chips' );
		[
			[ t.q_status, t.q_status_text ],
			[ t.q_tracking, t.q_tracking_txt ],
			[ t.q_delivery, t.q_delivery_txt ],
			[ t.q_items, t.q_items_text ],
			[ t.q_return, t.q_return_text ]
		].forEach( function ( q ) {
			var b = el( 'button', 'wps-tofw-wismo__chip', q[ 0 ] );
			b.type = 'button';
			b.addEventListener( 'click', function () {
				ask( q[ 1 ] );
			} );
			chips.appendChild( b );
		} );
		var form = el( 'form', 'wps-tofw-wismo__ask' );
		var input = el( 'input', 'wps-tofw-wismo__input' );
		input.type = 'text';
		input.maxLength = 500;
		input.placeholder = t.ask;
		input.setAttribute( 'aria-label', t.ask );
		var send = el( 'button', 'wps-tofw-wismo__btn', t.send );
		send.type = 'submit';
		form.appendChild( input );
		form.appendChild( send );
		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			var q = input.value.trim();
			if ( q ) {
				input.value = '';
				ask( q );
			}
		} );
		var other = el( 'button', 'wps-tofw-wismo__switch', t.switch_order );
		other.type = 'button';
		other.addEventListener( 'click', startVerification );
		wrap.appendChild( chips );
		wrap.appendChild( form );
		wrap.appendChild( other );
		setFooter( wrap );
		input.focus();
	}

	function ask( question ) {
		if ( state.busy || ! state.token ) {
			return;
		}
		state.busy = true;
		addMessage( 'user', question );
		var wait = thinking();
		post( 'wps_tofw_wismo_ask', { token: state.token, question: question } ).then( function ( res ) {
			wait.remove();
			state.busy = false;
			if ( res && res.success && res.data ) {
				addMessage( 'bot', res.data.text, res.data.links );
				return;
			}
			if ( res && res.data && res.data.code === 'session_expired' ) {
				addMessage( 'bot', t.expired );
				startVerification();
				return;
			}
			addMessage( 'bot', errorMessage( res ) );
		} ).catch( function () {
			wait.remove();
			state.busy = false;
			addMessage( 'bot', t.error );
		} );
	}

	/* ---------- Shell ---------- */

	function toggle( open ) {
		var isOpen = typeof open === 'boolean' ? open : els.panel.hidden;
		els.panel.hidden = ! isOpen;
		els.launcher.setAttribute( 'aria-expanded', isOpen ? 'true' : 'false' );
		if ( isOpen && ! els.started ) {
			els.started = true;
			addMessage( 'bot', cfg.welcome );
			startVerification();
		}
	}

	function build() {
		var root = el( 'div', 'wps-tofw-wismo wps-tofw-wismo--' + ( cfg.position === 'left' ? 'left' : 'right' ) );
		root.id = 'wps-tofw-wismo';

		var launcher = el( 'button', 'wps-tofw-wismo__launcher' );
		launcher.type = 'button';
		launcher.setAttribute( 'aria-label', t.open );
		launcher.setAttribute( 'aria-expanded', 'false' );
		launcher.setAttribute( 'aria-controls', 'wps-tofw-wismo-panel' );
		launcher.innerHTML = '<svg viewBox="0 0 24 24" width="26" height="26" aria-hidden="true" focusable="false"><path fill="currentColor" d="M20 2H4a2 2 0 0 0-2 2v18l4-4h14a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2zm0 14H5.17L4 17.17V4h16v12zM7 9h10v2H7zm0-3h10v2H7zm0 6h7v2H7z"/></svg>';

		var panel = el( 'div', 'wps-tofw-wismo__panel' );
		panel.id = 'wps-tofw-wismo-panel';
		panel.hidden = true;
		panel.setAttribute( 'role', 'dialog' );
		panel.setAttribute( 'aria-label', cfg.title );

		var head = el( 'div', 'wps-tofw-wismo__head' );
		head.appendChild( el( 'span', 'wps-tofw-wismo__title', cfg.title ) );
		var close = el( 'button', 'wps-tofw-wismo__close', '×' );
		close.type = 'button';
		close.setAttribute( 'aria-label', t.close );
		close.addEventListener( 'click', function () {
			toggle( false );
			launcher.focus();
		} );
		head.appendChild( close );

		var log = el( 'div', 'wps-tofw-wismo__log' );
		log.setAttribute( 'aria-live', 'polite' );
		var footer = el( 'div', 'wps-tofw-wismo__footer' );

		panel.appendChild( head );
		panel.appendChild( log );
		panel.appendChild( footer );
		root.appendChild( panel );
		root.appendChild( launcher );
		document.body.appendChild( root );

		launcher.addEventListener( 'click', function () {
			toggle();
		} );
		panel.addEventListener( 'keydown', function ( e ) {
			if ( e.key === 'Escape' ) {
				toggle( false );
				launcher.focus();
			}
		} );

		els = { root: root, launcher: launcher, panel: panel, log: log, footer: footer, started: false };
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', build );
	} else {
		build();
	}
}() );
