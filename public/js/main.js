/*** Go Back Btn ***/
$('#go_back_btn').off('click').on('click', function() {
	window.history.back();
});

$(".do_not_submit_empty_fields").submit(function(){
	$("select").each(function(index, obj){
		if($(obj).val() == "") {
			$(obj).attr('name', '');
		}
	});
	$("input").each(function(index, obj){
		if($(obj).val() == "") {
			$(obj).attr('name', '');
		}
	});
});

// prevent parent window scrolling when end is reached on div scroll
var pps = document.getElementsByClassName('prevent-parent-scroll');
if (pps != undefined) {
	for (var i = 0; i < pps.length; i++) {
		pps[i].addEventListener('mousewheel', function (e) {
			if (this.clientHeight + this.scrollTop + e.deltaY >= this.scrollHeight) {
				e.preventDefault();
				this.scrollTop = this.scrollHeight;
			} else if (this.scrollTop + e.deltaY <= 0) {
				e.preventDefault();
				this.scrollTop = 0;
			}
		}, false);
	}
}

/**** Redirect ****/
$.fn.redirect = function (path, external) {

	external = external || 0;

	if (!external) {

		window.location.href = "https://" + window.location.hostname + path;

	} else {

		window.location.href = path;
	}
};

$.ajaxSetup({
	headers: { "Cache-Control": "no-cache" },
	dataType: 'text'
});

/* Text Blinker -- Used for "blinking" a text ONCE, eg: when it's updated */

var new_blink_count;

function blink(obj,blink_count,scroll) {
	blink_count = blink_count || 1;
	scroll = scroll || 0;
	if(scroll) {
		$(obj).scrollToMe();
	}
	$(obj).animate({opacity:0.1},120,"linear",function(){
		$(this).animate({opacity:1},120);
		if (blink_count > 1) {
			new_blink_count = blink_count - 1;
			setTimeout(blink(obj,new_blink_count),120);
		}
	});
}

jQuery.fn.extend({
	scrollToMe: function () {
		var x = jQuery(this).offset().top - 100;
		jQuery('html,body').animate({scrollTop: x}, 60);
	}
});

var error = '<div class="red">Something went wrong.</div>',
		saved = '<i class="ok-icon"></i> <b class="purple3">Saved</b>',
		loading_dots = '<span class="loading-dots"><span>.</span><span>.</span><span>.</span></span>',
		enter2save = 'Press Enter to save',
		csrf_token =  $('body').data('csrf-token');

/****** Form submit btn disable ********/

var disable_btn_and_submit = $('.disable_btn_and_submit');

disable_btn_and_submit.off('click').on('click', function() {
	$(this).closest(disable_btn_and_submit).attr('disabled','');
	$(this).html('Processing'+loading_dots);
	$(this).closest('form').submit();
});

/******* Form submit btn ********/

$('.form_submit_btn').off('click').on('click', function() {
	$(this).closest('form').submit();
});

/************ Next Form submit btn ********/

$('.next_form_submit_btn').off('click').on('click', function() {
	$(this).next('form').submit();
});

/******* Form submit btn w/ alert *****/

$('.form_submit_btn_w_alert').off('click').on('click', function() {

	var _this = $(this);

	var alert_msg = _this.data('alert');

	if(confirm(alert_msg)) {

		_this.closest('form').submit();

	} else {

		return false;
	}
});

/**** Close Module Btn ****/
$('.close_module_btn').off('click').on('click', function() {

	var target = $(this).data('target');

	$(this).closest('.close_module_target').slideUp(80);

	if (target == 'front_page_text') {
		$.ajax({
			type: 'post',
			url: '/ajax/disable_front_page_text',
			data: {_token: csrf_token}
		});
	}

});

/****************************************************
				< Core JS >
****************************************************/

$(function() {

	var route_name = $('body').attr('id');

	/* Focus on input on page load */
	$(".ready_input_focus").focus();

	/* Initiate clipboard copy buttons */
	var clipboard = new Clipboard('.clipboard-btn');

	clipboard.on('success', function(e) {
		e.trigger.innerText = 'Copied!';
		e.trigger.closest('.hint--top').setAttribute("aria-label", "Copied!");
	});

	$("#signin_btn").on('click', function() {
		$(this).find('.header-signin-btn-text2').html('<span class="header-sign-btn-loading">'+loading_dots+'</span>');
	});

	$('#items_search').on('focus', function() {
		$('#items_search_options').fadeIn(200);
	});

	if (route_name == 'home' || route_name == 'search') {

		$('.jscroll').jscroll({
			loadingHtml: '<div id="auto_scroll_loading">' + loading_dots + '</div>',
			padding: 400,
			nextSelector: '.pagination > li > a',
			contentSelector: '.jscroll'
		});
	}

	if (route_name == 'verify_id') {

		$('.select_file_input:file').change(function () {

			var file = this.files[0],
				file_name = $(this).closest('div').find('.select_file_name'),
				max_bytes = 20972000; // 20mb

			if (typeof file === 'object') {

				var name = file.name;
				var sizeInBytes = file.size;
				var type = file.type;

				console.log(file_name);

				if ($.inArray(type, ['image/jpg', 'image/jpeg', 'image/png', 'image/gif']) == -1) {

					file_name.html('<span class="red">Invalid image type</span>');

				} else if (sizeInBytes > max_bytes) {

					file_name.html('<span class="red">File must be less than 20 MB</span>');

				} else {

					file_name.text(name);
				}
			}

		});

	}

	/********************************************** Cart **********************************************/

	var
		cart_item_count_span = $("#cart_item_count"),
		cart_item_count = cart_item_count_span.text(),
		header_cart_btn = $("#header_cart_btn"),
		cart_total = header_cart_btn.data('cart-total');

	$(document).off('click', '.add_to_cart_btn').on('click', '.add_to_cart_btn', function () {
		$.fn.addToCart($(this));
	});

	$('.add_to_cart_btn').keydown(function (e) {
		e.which == 13 ? $.fn.addToCart($(this)) : 0;
	});

	$.fn.addToCart = function (_this) {

		// not already in cart (green btn)
		if (!_this.hasClass('btn-green')) {

			_this.html('Adding' + loading_dots);

			var sale_id = _this.data('sale-id'),
				sale_price = _this.data('sale-price'),
				ajax_msg = _this.siblings('.item_display_ajax_msg');

			$.ajax({

				type: 'post',
				url: '/ajax/cart',
				data: {sale_id: sale_id, _token: csrf_token},

				success: function (response) {

					try {

						response = JSON.parse(response);

						if (response.status == 1) {

							_this.hide().html('Add to Cart');
							_this.siblings('.remove_from_cart_btn').show();

							cart_item_count = (+cart_item_count + 1);

							cart_item_count_span.text(cart_item_count);
							blink(header_cart_btn);

							cart_total = accounting.toFixed((+cart_total + +sale_price), 2);

							header_cart_btn.data('cart-total', cart_total);
							header_cart_btn.attr('aria-label', 'Cart: ' + accounting.formatMoney(cart_total));

						} else {

							ajax_msg.html(response.error).slideDown(100).addClass('shake');
							_this.html('Add to Cart');
						}

					} catch (e) {

						ajax_msg.html('Something went wrong.').slideDown(100).addClass('shake');
						_this.html('Add to Cart');
					}

				},

				error: function () {

					ajax_msg.html('Connection error.').slideDown(100).addClass('shake');
					_this.html('Add to Cart');
				}

			});

		}

	};

	$(document).off('click', '.remove_from_cart_btn').on('click', '.remove_from_cart_btn', function () {
			$.fn.removeFromCart($(this));
	});

	$('.remove_from_cart_btn').keydown(function (e) {
		e.which == 13 ? $.fn.removeFromCart($(this)) : 0;
	});

	$.fn.removeFromCart = function (_this) {

		_this.html('Removing' + loading_dots);

		var sale_id = _this.data('sale-id'),
			sale_price = _this.data('sale-price'),
			ajax_msg = _this.siblings('.item_display_ajax_msg');

		$.ajax({

			type: 'post',
			url: '/ajax/cart',
			data: {sale_id: sale_id, _token: csrf_token},

			success: function (response) {

				try {

					response = JSON.parse(response);

					if (response.status == 1) {

						cart_item_count = (+cart_item_count - 1);
						cart_total = accounting.toFixed((+cart_total - +sale_price), 2);

						if (_this.hasClass('in_cart')) {

							if (cart_item_count == 0) {

								location.reload();

							} else {

								_this.closest('.module').slideUp(150);
								$('.cart_deliver_btn').data('item-count', cart_item_count).data('total-cost', cart_total);
								$('.cart_item_count').text(cart_item_count);
								$('.cart_item_total').text(accounting.formatMoney(cart_total));
							}

						} else {

							_this.hide().html('Remove');
							_this.siblings('.add_to_cart_btn').show();
						}

						cart_item_count_span.text(cart_item_count);
						blink(header_cart_btn);

						header_cart_btn.data('cart-total', cart_total);
						header_cart_btn.attr('aria-label', 'Cart: ' + accounting.formatMoney(cart_total));

					} else {

						ajax_msg.html(response.error).slideDown(100).addClass('shake');
						_this.html('Remove');
					}

				} catch (e) {

					ajax_msg.html('Something went wrong.').slideDown(100).addClass('shake');
					_this.html('Remove');
				}

			},

			error: function () {

				ajax_msg.html('Connection error.').slideDown(100).addClass('shake');
				_this.html('Remove');
			}

		});

	};

	var cart_deliver_btn = $('.cart_deliver_btn');

	cart_deliver_btn.off('click').on('click', function() {
		$.fn.cartDeliver($(this));
	});

	cart_deliver_btn.keydown(function (e) {
		e.which == 13 ? $.fn.cartDeliver($(this)) : 0;
	});

	$.fn.cartDeliver = function (_this) {

		var item_count = _this.data('item-count'),
				total_cost = _this.data('total-cost');

		_this.html('Processing' + loading_dots);

		var ajax_msg = $('.cart_ajax_msg');

		$.ajax({

			type: 'post',
			url: '/ajax/cart',
			data: {checkout: 1,item_count: item_count, total_cost: total_cost, _token: csrf_token},

			success: function (response) {

				try {

					response = JSON.parse(response);

					if (response.status == 1) {

						$.fn.redirect('/wallet/item_purchases');

					} else if (response.status == 0) {

						blink(ajax_msg.html(response.error).show());
						_this.html('Purchase & Deliver');

					} else {

						blink(ajax_msg.html(response.error).show());
						_this.html('Purchase & Deliver');
					}

				} catch (e) {

					blink(ajax_msg.html(error).show());
					_this.html('Purchase & Deliver');
				}

			},

			error: function () {

				blink(ajax_msg.html('Connection error.').show());
				_this.html('Purchase & Deliver');
			}

		});

	};

	var cart_store_btn = $('.cart_store_btn');

	cart_store_btn.off('click').on('click', function() {
		$.fn.cartStore($(this));
	});

	cart_store_btn.keydown(function (e) {
		e.which == 13 ? $.fn.cartStore($(this)) : 0;
	});

	$.fn.cartStore = function(_this) {

		var item_count = _this.data('item-count'),
				total_cost = _this.data('total-cost');

		_this.html('Processing' + loading_dots);

		var ajax_msg = $('.cart_ajax_msg');

		$.ajax({

			type: 'post',
			url: '/ajax/cart',
			data: {checkout: 1, item_count: item_count, total_cost: total_cost, _token: csrf_token},

			success: function (response) {

				try {

					response = JSON.parse(response);

					if (response.status == 1) {

						$.fn.redirect('/wallet/item_purchases');

					} else if (response.status == 0) {

						blink(ajax_msg.html(response.error).show());
						_this.html('Purchase & Store');

					} else {

						blink(ajax_msg.html(response.error).show());
						_this.html('Purchase & Store');
					}

				} catch (e) {

					blink(ajax_msg.html(error).show());
					_this.html('Purchase & Store');
				}

			},

			error: function () {

				blink(ajax_msg.html('Connection error.').show());
				_this.html('Purchase & Store');
			}

		});

	};

	/******* Item Purchases -- Wallet *******/

	if (route_name == 'wallet.item_purchases') {

		$('.resend_purchase_delivery').off('click').on('click', function() {

			var _this = $(this),
					_parent = _this.parent('span'),
					delivery_id = _this.data('delivery-id');

			if (delivery_id == '') {
				return false;
			}

			_this.html(loading_dots);

			$.ajax({

				type: 'post',
				url: '/ajax/resend_purchase_delivery',
				data: {delivery_id: delivery_id, _token: csrf_token},

				success: function (response) {

					try {

						response = JSON.parse(response);

						if (response.status == 1) {

							_this.html('In Progress');
							_parent.attr('aria-label', 'Delivery in progress, may take up to 3 minutes.');
							_this.removeClass('link').data('delivery-id', '');

						} else if (response.status == 0) {

							_this.html('Try again');
							_parent.attr('aria-label', response.error);

						} else {

							_this.html('Try again');
							_parent.attr('aria-label', 'Connection error');
						}

					} catch (e) {

						_this.html('Try again');
						_parent.attr('aria-label', 'Connection error');
					}

				},

				error: function () {

					_this.html('Try again');
					_parent.attr('aria-label', 'Connection error');
				}

			});

		});

	}

	/****************************************** Add Funds & Cashout -- Wallet **************************************/

	if (route_name == 'wallet.add_funds' || route_name == 'wallet.cashout') {

		var
			payment = {},
			paypal_btn = $('#wallet_add_funds_paypal_btn'),
			wallet_verified_wrap = $('#wallet_verified_wrap'),
			g2a_btn = $('#wallet_add_funds_g2a_btn'),
			bitcoin_btn = $('#wallet_add_funds_bitcoin_btn'),
			amount_wrap = $('#wallet_add_funds_amount_wrap'),
			add_funds_ajax_msg = $('.wallet_add_funds_ajax_msg'),
			_pi = 0;

		$('.wallet_add_funds_payment_btn').off('click').on('click', function () {

			var _this = $(this),
					payment_method = _this.data('payment-method');

			_pi++;

			payment[_pi] = {};
			payment[_pi]['method'] = payment_method;
			payment[_pi]['min_amount'] = +_this.data('min-amount');
			payment[_pi]['max_amount'] = +_this.data('max-amount');

			var amount_input = $('.wallet_amount_input');

			amount_input.val(accounting.toFixed(payment[_pi]['min_amount'], 2));
			amount_input.attr('min', payment[_pi]['min_amount']);
			amount_input.attr('max', payment[_pi]['max_amount']);

			amount_wrap.slideUp(50);
			wallet_verified_wrap.slideUp(50);
			add_funds_ajax_msg.slideUp(50);

			if (payment[_pi]['method'] == 10) {

				amount_wrap.slideDown(100);
				amount_input.focus();

				g2a_btn.fadeTo(0, 1);
				bitcoin_btn.fadeTo(50, 0.5);
				paypal_btn.fadeTo(50, 0.5);

			} else if (payment[_pi]['method'] == 8) {

				amount_wrap.slideDown(100);
				amount_input.focus();

				g2a_btn.fadeTo(50, 0.5);
				bitcoin_btn.fadeTo(50, 1);
				paypal_btn.fadeTo(50, 0.5);

			} else if (payment[_pi]['method'] == 9) {

				if (_this.data('id-verified') != 1 || _this.data('paypal-linked') != 1) {

					// id not verified or paypal account not connected

					amount_wrap.slideUp(100);
					wallet_verified_wrap.slideDown(100);

				} else {

					amount_wrap.slideDown(100);
					amount_input.focus();
				}

				g2a_btn.fadeTo(50, 0.5);
				bitcoin_btn.fadeTo(50, 0.5);
				paypal_btn.fadeTo(50, 1);
			}

		});

		var wallet_amount_input = $('.wallet_amount_input');

		$('#wallet_add_funds_continue').off('click').on('click', function () {

			var _this = $(this),
					amount = +wallet_amount_input.val(),
					ajax_msg = add_funds_ajax_msg,
					user_data = $('#wallet_user_data');

			ajax_msg.slideUp(60);

			if (amount < payment[_pi]['min_amount']) {

				ajax_msg.html('Minimum amount is ' + accounting.formatMoney(payment[_pi]['min_amount'])).slideDown(60);
				_this.html('Continue');

			} else if (amount > payment[_pi]['max_amount']) {

				ajax_msg.html('Maximum amount is ' + accounting.formatMoney(payment[_pi]['max_amount'])).slideDown(60);
				_this.html('Continue');

			} else {

				if (payment[_pi]['method'] == 10) {

					// G2A Payment
					ajax_msg.html('Coming soon!').slideDown(60);

				} else if (payment[_pi]['method'] == 8) {

					// Bitcoin via BitPay

					_this.html(loading_dots);

					bitpay.onModalWillEnter(function() {
						_this.html('Checkout');
					});

					$.ajax({

						type: 'post',
						url: '/payments/bitpay',
						data: {amount: amount, _token: csrf_token},

						success: function (response) {

							try {

								response = JSON.parse(response);

								if (response.status == 1) {

									bitpay.onModalWillLeave(function() {
										$.fn.redirect('/wallet');
									});

									bitpay.showInvoice(response.id);

									ajax_msg.slideUp(100);

								} else {

									_this.html('Checkout');
									addFundsError(ajax_msg);
								}

							} catch (e) {

								_this.html('Checkout');
								addFundsError(ajax_msg);
							}

						},

						error: function () {
							_this.html('Checkout');
							addFundsError(ajax_msg);
						}

					});

				} else if (payment[_pi]['method'] == 9) {

					// PayPal

					_this.html(loading_dots);

					$.ajax({

						type: 'post',
						url: '/payments/paypal',
						data: {amount: amount, _token: csrf_token},

						success: function (response) {

							try {

								response = JSON.parse(response);

								if (response.status == 1) {

									$.fn.redirect(response.url, 1);
									ajax_msg.slideUp(100);

								} else if (response.status == 0) {

									_this.html('Checkout');
									addFundsError(ajax_msg,response.error);

								} else {

									_this.html('Checkout');
									addFundsError(ajax_msg);
								}

							} catch (e) {

								_this.html('Checkout');
								addFundsError(ajax_msg);
							}

						},

						error: function () {
							_this.html('Checkout');
							addFundsError(ajax_msg);
						}

					});

				}

			}

		});

		function addFundsError(ajax_msg,error_msg) {

			if (error_msg != undefined) {

				blink(ajax_msg.html('<span class="red">'+error_msg+'</span>').slideDown(100));

			} else {

				blink(ajax_msg.html('<span class="red">Something went wrong. You were not charged.</span>').slideDown(100));
			}
		}

		/**************** Cashout **************/

		var cashout_payment_btn = $('.wallet_cashout_payment_btn'),
				cashout_amount_wrap = $('#wallet_cashout_amount_wrap'),
				cashout_paypal_wrap = $('.wallet_cashout_paypal_wrap'),
				cashout_paypal_btn = $('#wallet_cashout_paypal_btn'),
				cashout_bitcoin_wrap = $('.wallet_cashout_bitcoin_wrap'),
				cashout_bitcoin_btn = $('#wallet_cashout_bitcoin_btn');

		cashout_payment_btn.off('click').on('click', function () {

			var cashout_payment_method = $(this).data('payment-method');

			$('#cashout_payment_method').val(cashout_payment_method);

			wallet_verified_wrap.hide();

			if (cashout_payment_method == 5) {

				// paypal

				cashout_amount_wrap.show();
				cashout_paypal_wrap.show();
				cashout_bitcoin_wrap.hide();
				cashout_bitcoin_btn.fadeTo(50, 0.5);
				cashout_paypal_btn.fadeTo(50, 1);
				$('#wallet_cashout_paypal_input').focus();

			} else {

				// bitcoin

				cashout_paypal_wrap.hide();
				cashout_paypal_btn.fadeTo(50, 0.5);
				cashout_bitcoin_btn.fadeTo(50, 1);

				//if (id_verified === 1) {

					cashout_amount_wrap.show();
					cashout_bitcoin_wrap.show();
					$('#wallet_cashout_bitcoin_input').focus();

				//} else {

				//	cashout_amount_wrap.hide();
				//	wallet_verified_wrap.show();
				//}

			}

		});

	}

	/************************************* Manage Sales ************************************/

	if (route_name == 'manage_sales' || route_name == 'sale') {

		var manage_sales_price_input = $('.manage_sales_price_input'),
			ms_input_price_val = 0;

		manage_sales_price_input.focus(function () {

			ms_input_price_val = $(this).val();

		}).on('keydown blur', function (e) {

			if (e.which == 13) {
				$(this).blur();
			}

			if (e.type == 'blur') {
				$.fn.change_price($(this));
			}
		});

		$.fn.change_price = function (_this) {

			var new_price_val = _this.val(),
				sale_id = _this.data('sale-id'),
				ajax_msg = _this.siblings('.manage_sales_saved_msg');

			if (new_price_val != ms_input_price_val) {

				_this.siblings('.manage_sales_saved_msg').slideDown(100).html(loading_dots);

				$.ajax({

					type: 'post',
					url: '/ajax/manage_sales',
					data: {sale_id: sale_id, new_price: new_price_val, _token: csrf_token},

					success: function (response) {

						try {

							response = JSON.parse(response);

							if (response.status == 1) {

								ajax_msg.html('Saved').delay(900).slideUp(100);

							} else {

								ajax_msg.html('<span class="red">Error</span>').delay(1000).slideUp(200);
							}

						} catch (e) {

							ajax_msg.html('<span class="red">Error</span>').delay(1000).slideUp(200);
						}

					},

					error: function () {
						ajax_msg.html('<span class="red">Error</span>').delay(1000).slideUp(200);
					}

				});

			}

		};

		/******************** Manage Sale Options *****************/

		$('.manage_sales_boost_btn').off('click').on('click', function () {

			var _this = $(this),
					_btn = _this.find('.btn'),
					sale_id = _this.data('sale-id'),
					market_name = _this.data('market-name');

			if (confirm('Please confirm that you will be charged $3.00 to promote: \n'+market_name)) {

				_btn.html(loading_dots);
				_this.attr('aria-label', 'Processing...');

				$.ajax({

					type: 'post',
					url: '/ajax/manage_sales',
					data: {sale_id: sale_id, boost_item: 1, _token: csrf_token},

					success: function (response) {

						try {

							response = JSON.parse(response);

							if (response.status == 1) {

								blink(_btn.html('<span class="font-size-13 purple3">Promoted</span>').attr('class', ''), 2);
								_this.removeClass('hint--top');

							} else {

								_btn.html('?');
								_this.attr('aria-label', 'Something went wrong');
							}

						} catch (e) {

							_btn.html('?');
							_this.attr('aria-label', 'Something went wrong');
						}

					},

					error: function () {
						_btn.html('?');
						_this.attr('aria-label', 'Something went wrong');
					}

				});

			}

		});

		$('.cancel_sale_btn').off('click').on('click', function () {

			var _this = $(this),
					sale_id = _this.data('sale-id'),
					ajax_msg = _this.parent('.manage_sales_status'),
					sale_item_page = _this.hasClass('sale_item_page');

			if (sale_item_page) {
				ajax_msg = _this;
			}

			ajax_msg.html(loading_dots);

			if (sale_item_page) {
				_this.html(loading_dots);
			}

			$.ajax({

				type: 'post',
				url: '/ajax/manage_sales',
				data: {sale_id: sale_id, cancel_sale: 1, _token: csrf_token},

				success: function (response) {

					try {

						response = JSON.parse(response);

						if (response.status == 1) {

							ajax_msg.html('<span class="hint--top" aria-label="Check Steam for Trade Offer">Cancelled</span>');

							if (sale_item_page) {
								_this.attr('class', '');
								_this.parent().attr('class', '');
							}

						} else if (response.status == 2) {

							var _msg_label = 'Sale was cancelled but we failed to send trade offer. Click \'send offer\' to try again.',
									_msg = 'Cancelled [<span class="cancelled_item_send_offer link" data-sale-id="'+sale_id+'">send offer</span>]';

							ajax_msg.html('<span class="hint--top hint--long" aria-label="'+_msg_label+'">'+ _msg + '</span>');

							if (sale_item_page) {
								_this.data('sales-page', 1);
								_this.attr('class', '');
								_this.parent().attr('class', '');
							}

						} else if (response.status == 3) {

							ajax_msg.html('<span class="hint--top" aria-label="Item already sold.">Sold</span>');

							if (sale_item_page) {
								_this.attr('class', '');
								_this.parent().attr('class', '');
							}

						} else {

							ajax_msg.html('<span class="red">Error</span>');

							if (sale_item_page) {
								_this.html('(Error)');
							}
						}

					} catch (e) {

						ajax_msg.html('<span class="red">Error</span>');

						if (sale_item_page) {
							_this.html('(Error)');
						}
					}

				},

				error: function () {
					ajax_msg.html('<span class="red">Error</span>');

					if (sale_item_page) {
						_this.html('(Error)');
					}
				}

			});

		});

		/***** Cancelled sale but failed to send trade offer ******/

		$(document).off('click', '.cancelled_item_send_offer').on('click', '.cancelled_item_send_offer', function () {

			var _this = $(this),
					sale_id = _this.data('sale-id'),
					sale_item_page = _this.data('sales-page');

			_this.html(loading_dots);

			$.ajax({

				type: 'post',
				url: '/ajax/manage_sales',
				data: {sale_id: sale_id, cancelled_sale_send_offer: 1, _token: csrf_token},

				success: function (response) {

					try {

						response = JSON.parse(response);

						if (response.status == 1) {

							var __msg = '<span class="hint--top" aria-label="Check Steam for Trade Offer">Offer Sent</span>';

							_this.parent().removeClass();
							blink(_this.parent().html(__msg), 2);

							if (!sale_item_page) {
								_this.parent().parent().removeClass();
							}

						} else if (response.status == 2) {

							_this.text('send offer');
							_this.parent().attr('aria-label', 'Bots are down, try again later.');

						} else {

							_this.html('<span class="red">Error</span>');
						}

					} catch (e) {

						_this.html('<span class="red">Error</span>');
					}

				},

				error: function () {
					_this.html('<span class="red">Error</span>');
				}

			});

		});

	}

	if (route_name == 'settings') {

		/********************************************* Change Email *********************************************/

		var change_email = $("#change_email"),
				email_ajax_msg = $('#change_email_ajax_msg'),
				enter_valid_email = '<div class="red">Please enter a valid email.</div>';

		change_email.keydown(function (e) {

			email_ajax_msg.html(enter2save);
			e.which == 13 ? $.fn.change_email() : 0;
		});

		// Change email function
		$.fn.change_email = function () {

			var email = change_email.val();

			email_ajax_msg.html(loading_dots);

			$.ajax({

				type: 'post',
				url: '/ajax/settings',
				data: {email: email, _token: csrf_token},

				success: function (response) {

					if (response == 'ok') {
						change_email.blur();
						email_ajax_msg.html(saved);
					}

				},

				error: function (xhr) {

					if (xhr.status == 422) {

						email_ajax_msg.html(enter_valid_email);

					} else {

						email_ajax_msg.html(error);
					}

				}

			});

			blink(email_ajax_msg);
		};

		/********************************************* Change Trade URL *********************************************/

		var change_trade_url = $("#change_trade_url"),
			trade_url_ajax_msg = $('#change_trade_url_ajax_msg'),
			enter_valid_url = '<div class="red">Not a valid URL.</div>';

		change_trade_url.keydown(function (e) {

			trade_url_ajax_msg.html(enter2save);

			if (e.which == 13) {
				$.fn.change_trade_url();
			}

		});

		// Change trade url function
		$.fn.change_trade_url = function () {

			var trade_url = change_trade_url.val();

			trade_url_ajax_msg.html(loading_dots);

			if (trade_url.length < 200) {

				$.ajax({

					type: 'post',
					url: '/ajax/settings',
					data: {trade_url: trade_url, _token: csrf_token},

					success: function (response) {

						if (response == 'ok') {

							change_trade_url.blur();
							trade_url_ajax_msg.html(saved);

						} else if (response != 'ok') {

							change_trade_url.blur();
							trade_url_ajax_msg.html('<span class="red">'+response+'</span>');
						}

					},

					error: function (xhr) {

						if (xhr.status == 422) {

							trade_url_ajax_msg.html(enter_valid_url);

						} else {

							trade_url_ajax_msg.html(error);
						}

					}

				});

			}

			blink(trade_url_ajax_msg);
		};

		/********************************************* Change Time Zone *********************************************/

		var change_time_zone = $("#change_time_zone"),
			time_zone_ajax_msg = $('#change_time_zone_ajax_msg');

		change_time_zone.change(function () {

			var time_zone = $('option:selected', $(this)).val();

			time_zone_ajax_msg.html(loading_dots);

			$.ajax({

				type: 'post',
				url: '/ajax/settings',
				data: {time_zone: time_zone, _token: csrf_token},

				success: function (response) {

					if (response == 'ok') {
						change_time_zone.blur();
						time_zone_ajax_msg.html(saved);
					}

				},

				error: function () {
					time_zone_ajax_msg.html(error);
				}

			});

			blink(time_zone_ajax_msg);

		});

		/********************************************* Show Wallet Amount *********************************************/

		var show_wallet_amnt = $("#show_wallet_amount"),
				show_wallet_amnt_label = $('#show_wallet_amount_label');

		show_wallet_amnt.change(function () {

			var is_checked = show_wallet_amnt.is(':checked'),
					_show_module = $('.header_mid_btns_acc_balance'),
					_show_module_text = _show_module.find('.header_mid_btns_text'),
					_slider_loading = $(this).closest('div').find('.slider_loading');

			_slider_loading.html(loading_dots);

			$.ajax({

				type: 'post',
				url: '/ajax/settings',
				data: {show_wallet_amount: is_checked, _token: csrf_token},

				success: function (response) {

					_slider_loading.html('');

					if (response == 'ok') {

						if (is_checked) {

							blink(_show_module_text.text(accounting.formatMoney(show_wallet_amnt.data('balance'))));
							_show_module.removeClass('_no_show');

						} else {

							blink(_show_module_text.text('Wallet'));
							_show_module.addClass('_no_show');
						}


					} else {

						show_wallet_amnt_label.html('Failed to save.');
					}

				},

				error: function () {
					show_wallet_amnt_label.html('Connection error.');
					_slider_loading.html('');
				}

			});

		});

		/********************************************* Send Sales Receipts *********************************************/

		var send_sales_receipts = $("#send_sales_receipts"),
				send_sales_receipts_label = $('#send_sales_receipts_label');

		send_sales_receipts.change(function () {

			var is_checked = send_sales_receipts.is(':checked'),
					_slider_loading = $(this).closest('div').find('.slider_loading');

			_slider_loading.html(loading_dots);

			$.ajax({

				type: 'post',
				url: '/ajax/settings',
				data: {send_sales_receipts: is_checked, _token: csrf_token},

				success: function (response) {

					_slider_loading.html('');

					if (response != 'ok') {
						send_sales_receipts_label.html('Failed to save.');
					}

				},

				error: function () {
					_slider_loading.html('');
					send_sales_receipts_label.html('Connection error.');
				}

			});

		});

		/********************************************* Send Purchase Receipts *********************************************/

		var send_purchase_receipts = $("#send_purchase_receipts"),
				send_purchase_receipts_label = $('#send_purchase_receipts_label');

		send_purchase_receipts.change(function () {

			var is_checked = send_purchase_receipts.is(':checked'),
				 _slider_loading = $(this).closest('div').find('.slider_loading');

			_slider_loading.html(loading_dots);

			$.ajax({

				type: 'post',
				url: '/ajax/settings',
				data: {send_purchase_receipts: is_checked, _token: csrf_token},

				success: function (response) {

					_slider_loading.html('');

					if (response != 'ok') {
						send_purchase_receipts_label.html('Failed to save.');
					}

				},

				error: function () {
					_slider_loading.html('');
					send_purchase_receipts_label.html('Connection error.');
				}

			});

		});

	}

	/********************************************* Sell Page *********************************************/

	if (route_name == 'sell') {

		var steam_inventory_wrap = $('#steam_inventory_wrap'),
				error_loading_inven = '<div class="steam_inventory_center_text">Could not load inventory.<br>Please check back later.</div>';

		steam_inventory_wrap.html('<div class="steam_inventory_center_text">Loading' + loading_dots + '</div>');

		$.ajax({

			type: 'get',
			url: '/ajax/steam_inventory',
			data: {_token: csrf_token},

			success: function (response) {

				if (response != 'error' && response != '') {

					steam_inventory_wrap.html(response);

				} else {
					steam_inventory_wrap.html(error_loading_inven);
				}

			},

			error: function () {
				steam_inventory_wrap.html(error_loading_inven);
			}

		});

		var
			sell_item_title = $('#sell_item_title'),
			sell_item_grade = $('#sell_item_title_grade'),
			sell_item_img = $('#sell_item_img'),
			sell_item_right_img,
			sell_item_stickers_wrap3 = $('.sell_item_stickers_wrap3'),
			sell_item_inspect_btn_wrap = $('.sell_item_inspect_btn_wrap'),
			suggested_price_amount = $('#suggested_price_amount'),
			sell_item_your_price = $('#sell_item_your_price'),
			sell_item_quantity = $('#sell_item_quantity'),
			sell_item_quantity_wrap = $('#sell_item_quantity_wrap'),
			sell_item_obj = {};

		$(document).off('click', '.steam_inventory_item').on('click', '.steam_inventory_item', function () {
			$.fn.itemClick($(this));
		});

		$.fn.itemClick = function (_this) {

			var _parent = _this.parent();

			sell_item_obj.title = _this.attr('title');
			sell_item_obj.assetid = _parent.data('assetid');
			sell_item_obj.name_color = _parent.data('name-color');
			sell_item_obj.grade = _parent.data('grade');
			sell_item_obj.stickers = _parent.data('stickers');
			sell_item_obj.inspect_link = _parent.data('inspect-link');
			sell_item_obj.quantity = _this.find('.steam_inventory_item_quantity').text();
			sell_item_obj.commodity = _parent.data('commodity');

			sell_item_title.text(_this.find('.steam_inventory_item_name').text());

			var grade_color_display = '#' + sell_item_obj.name_color;

			if (sell_item_obj.name_color == '') {
				grade_color_display = '';
			}

			sell_item_grade.fadeIn(100).text(sell_item_obj.grade).css('color', grade_color_display);

			suggested_price_amount.html(loading_dots);

			sell_item_your_price.focus();

			if (sell_item_obj.quantity > 1) {
				sell_item_quantity_wrap.slideDown(50).css('display', 'inline-block');
				sell_item_quantity.val(sell_item_obj.quantity);
				sell_item_quantity.attr('max', _this.find('.steam_inventory_item_quantity').text());
			} else {
				sell_item_quantity_wrap.slideUp(50);
			}

			sell_item_right_img = _this.children('.steam_inventory_item_img').attr('src').slice(0, -7);

			// clear it first to make transition more visible
			sell_item_img.attr('src', '');
			sell_item_img.attr('src', sell_item_right_img + '226fx226f');
			sell_item_img.removeClass('sell_item_default_img');
			$('#sell_item_sale_options option[value=0]').prop('selected', true).change();

			// stickers
			if (sell_item_obj.stickers != '') {

				sell_item_stickers_wrap3.html(_this.find('.sell_item_stickers_wrap').html());
				sell_item_stickers_wrap3.show();

			} else {

				sell_item_stickers_wrap3.hide();
			}

			// inspect button
			if (sell_item_obj.inspect_link != 'steam://rungame/') {

				sell_item_inspect_btn_wrap.find('.sell_item_inspect_btn').attr('href', sell_item_obj.inspect_link);
				sell_item_inspect_btn_wrap.show();

			} else {

				sell_item_inspect_btn_wrap.hide();
			}

			$.ajax({

				type: 'get',
				url: '/ajax/suggested_price',
				data: {suggested_price_item_name: sell_item_obj.title, _token: csrf_token},

				success: function (response) {

					if (response != 'not_found' && response != '') {

						suggested_price_amount.text(accounting.formatMoney(response));
						sell_item_your_price.val(response);
						$.fn.yourPriceUpdate();

					} else {

						suggested_price_amount.text('N/A');
						sell_item_your_price.val('');
					}

				},

				error: function () {
					suggested_price_amount.text('N/A');
				}

			});

		};

		var steam_inventory_search = $('#steam_inventory_search');

		steam_inventory_search.keyup(function (e) {

			var _this = $(this);

			// clear field on ESC
			if (e.keyCode === 27) {

				$('.steam_inventory_item').each(function () {
					var _this2 = $(this);
					_this2.parent().show();
				});

				steam_inventory_search.val('');

			} else {

				$('.steam_inventory_item').each(function () {

					var _this2 = $(this);

					if (_this2.attr('title').toUpperCase().indexOf(_this.val().toUpperCase()) != -1) {

						_this2.parent().show();

					} else {

						_this2.parent().hide();
					}

				});

			}
		});

		var sell_item_you_get = $('#sell_item_you_get'),
			sell_item_fee = $('#sell_item_fee'),
			max_price = sell_item_your_price.attr('max'),
			sale_fee = (sell_item_fee.data('sale-fee') / 100),
			sell_item_error_msg = $('.sell_item_error_msg'),
			price_check_regex = /^[0-9]\d*(((,\d{3}){1})?(\.\d{0,2})?)$/;

		sell_item_your_price.keyup(function () {
			$.fn.yourPriceUpdate();
		});

		sell_item_your_price.mouseup(function () {
			$.fn.yourPriceUpdate();
		});

		$.fn.yourPriceUpdate = function () {

			var price_val = sell_item_your_price.val();

			if (price_check_regex.test(price_val)) {

				if (+price_val > +max_price) {

					sell_item_error_msg.text('Maximum ' + accounting.formatMoney(max_price)).addClass('shake');
					sell_item_error_msg.show();

				} else {

					sell_item_error_msg.text('');

					var we_get = accounting.toFixed((price_val * sale_fee), 2);
					var you_get = accounting.toFixed((price_val - we_get), 2);

					// Fee can't be less than 1 cent
					if (we_get < 0.01) {
						we_get = 0.01;
						you_get = (you_get - we_get);
					}

					sell_item_you_get.text(accounting.formatMoney(you_get));
					sell_item_fee.text(accounting.formatMoney(we_get));
				}

			} else {

				sell_item_error_msg.text('Invalid price');
				sell_item_error_msg.show();
			}

		};

		var sell_item_confirm_item_btn = $('.sell_item_confirm_item_btn'),
			sell_item_sale_options = $('#sell_item_sale_options'),
			sell_list_wrap = $('#sell_list_wrap'),
			steam_inventory_item_wrap = $('.steam_inventory_item_wrap'),
			sell_list_item_count = 0,
			sell_list_items_total_value = 0,
			sell_list_items_total_value_span = $('.sell_list_items_total_value'),
			sell_list_item_count_span = $('.sell_list_item_count'),
			sell_list_deposit_btn = $('#sell_list_deposit_btn'),
			sell_list_processing_btn = $('#sell_list_processing_btn'),
			sell_list_items_obj = {},
			sell_list_key = 0,
			sell_list_max_items_count = 30,
			sell_list_deposit_ajax_msg = $('.sell_list_deposit_ajax_msg');

		sell_item_confirm_item_btn.off('click').on('click', function () {
			$.fn.addItemSellList();
		});

		sell_item_your_price.keydown(function (e) {
			// 13=Enter
			if (e.which == 13) {
				this.blur();
				$.fn.addItemSellList();
			}
		});

		sell_item_quantity.keydown(function (e) {
			// 13=Enter
			if (e.which == 13) {
				this.blur();
				$.fn.addItemSellList();
			}
		});

		$.fn.addItemSellList = function () {

			var item_sale_option = '',
				item_price_raw = sell_item_your_price.val(),
				item_quantity = sell_item_quantity.val(),
				min_price = sell_item_your_price.attr('min');

			if (item_price_raw == '') {

				sell_item_error_msg.text('Set a price');
				blink(sell_item_error_msg.show(), 1, 1);

			} else if (sell_item_img.hasClass('sell_item_default_img')) {

				sell_item_error_msg.text('Pick an item');
				blink(sell_item_error_msg.show(), 1, 1);

			} else if ((Number(sell_list_item_count) + Number(item_quantity)) > Number(sell_list_max_items_count)) {

				sell_item_error_msg.text('Maximum ' + sell_list_max_items_count + ' items');
				blink(sell_item_error_msg.show(), 1, 1);

			} else if (item_price_raw < min_price) {

				sell_item_error_msg.text('Minimum $' + min_price);
				blink(sell_item_error_msg.show(), 1, 1);

			} else {

				sell_item_error_msg.text('');

				var item_price = accounting.formatMoney(item_price_raw);

				/* Handle Sale Options */
				if (sell_item_sale_options.val() == 'boost')
					item_sale_option = 'boost';

				else if (sell_item_sale_options.val() == 'private_1hr')
					item_sale_option = 'private_1hr';

				else if (sell_item_sale_options.val() == 'private_24hr')
					item_sale_option = 'private_24hr';

				else if (sell_item_sale_options.val() == 'private_1wk')
					item_sale_option = 'private_1wk';


				var steam_inventory_list_item_div = steam_inventory_wrap.find('.steam_inventory_item_wrap[data-assetid="' + sell_item_obj.assetid + '"]');

				var item_div = steam_inventory_list_item_div.clone();

				sell_list_items_total_value += Number(item_price_raw);
				sell_list_items_total_value_span.text(accounting.formatMoney(sell_list_items_total_value));

				sell_list_deposit_btn.show();

				sell_list_deposit_ajax_msg.slideUp(100);

				if (sell_item_obj.commodity == 1) {
					var new_quantity = (sell_item_obj.quantity - item_quantity);
					steam_inventory_wrap.find('.steam_inventory_item_wrap[data-assetid="' + sell_item_obj.assetid + '"]').find('.steam_inventory_item_quantity').text(new_quantity);
				}

				if (sell_item_obj.quantity <= 1) {

					// deactivate item on steam inventory list
					steam_inventory_list_item_div.fadeOut(165);

				} else {

					// deactivate only if it is the last item in commodity
					if (sell_item_obj.quantity == item_quantity) {
						steam_inventory_list_item_div.fadeOut(165);
					}

				}

				if (sell_list_item_count >= 1) {
					// already have some items on sell list
					sell_list_wrap.append(item_div);
				} else {
					// no items on sell list
					sell_list_wrap.html(item_div);
				}

				sell_list_item_count += Number(item_quantity);
				sell_list_item_count_span.text(sell_list_item_count);

				sell_list_key++;

				// add this new item to sell list obj
				sell_list_items_obj[sell_list_key] = {};
				sell_list_items_obj[sell_list_key].assetid = sell_item_obj.assetid;
				sell_list_items_obj[sell_list_key].market_name = sell_item_obj.title;
				sell_list_items_obj[sell_list_key].quantity = item_quantity;
				sell_list_items_obj[sell_list_key].commodity = sell_item_obj.commodity;
				sell_list_items_obj[sell_list_key].price = item_price_raw;
				sell_list_items_obj[sell_list_key].sale_option = item_sale_option;

				// Change and append/prepend extra stuff to sell list item display
				var sell_list_item_div = sell_list_wrap.find('.steam_inventory_item_wrap[data-assetid="' + sell_item_obj.assetid + '"]');
				sell_list_item_div.find('.sell_item_stickers_wrap').removeClass('sell_item_stickers_wrap').addClass('sell_item_stickers_wrap2');
				sell_list_item_div.addClass('sell_list_item_wrap').removeClass('steam_inventory_item_wrap');
				sell_list_item_div.children('.steam_inventory_item').addClass('sell_list_item').removeClass('steam_inventory_item');
				sell_list_item_div.prepend('<i class="sell_list_item_x_icon icon-x" title="Remove" data-price="' + item_price_raw + '"></i>');
				sell_list_item_div.prepend('<div class="sell_list_item_price_wrap"><div class="sell_list_item_price" title="Sale Price">' + item_price + '</div></div>');
				sell_list_item_div.data('price', item_price_raw);
				sell_list_item_div.data('key', sell_list_key);
				sell_list_item_div.find('.steam_inventory_item_quantity').text(item_quantity);

				// handle item sale options
				if (item_sale_option == 'boost') {
					sell_list_item_div.prepend('<i class="sell_list_item_left_icon icon-rocket" title="Promoted"></i>');
				} else if (item_sale_option == 'private_1hr') {
					sell_list_item_div.prepend('<i class="sell_list_item_left_icon icon-locked" title="Private (1 hour)"></i>');
				} else if (item_sale_option == 'private_24hr') {
					sell_list_item_div.prepend('<i class="sell_list_item_left_icon icon-locked" title="Private (24 hours)"></i>');
				} else if (item_sale_option == 'private_1wk') {
					sell_list_item_div.prepend('<i class="sell_list_item_left_icon icon-locked" title="Private (7 days)"></i>');
				}

				// if no more inventory items, let user know
				if (sell_list_item_count == $('#steam_inventory_data_div').data('total-item-count')) {
					steam_inventory_wrap.prepend('<div class="sell_items_module_middle_text">That\'s it.</div>');
				}

				// Clear Item Set Your Price Fields ... Set Fields Back to Default
				sell_item_title.text('Pick an Item to Sell');
				sell_item_grade.hide();
				sell_item_inspect_btn_wrap.hide();
				sell_item_stickers_wrap3.hide();
				sell_item_img.attr('src', 'https://steamcommunity-a.akamaihd.net/economy/image/-9a81dlWLwJ2UUGcVs_nsVtzdOEdtWwKGZZLQHTxDZ7I56KU0Zwwo4NUX4oFJZEHLbXH5ApeO4YmlhxYQknCRvCo04DEVlxkKgpot7HxfDhjxszJemkV086jloKOhcj5Nr_Yg2YfvZcg0rmXrI2n31ex8ks9Zjz2JIKdcVA4ZArRqVm-wLzn1sC8uJnMwWwj5HcoJjKuZA/226fx226f');
				sell_item_img.addClass('sell_item_default_img');
				suggested_price_amount.text('$0.00');
				sell_item_your_price.val('');
				sell_item_quantity.val(1);
				sell_item_quantity_wrap.slideUp(50);
				sell_item_you_get.text('$0.00');
				sell_item_fee.text('$0.00');
				$('#sell_item_sale_options option[value=0]').prop('selected', true).change();
				sell_item_sale_options.val(0);

			}

		};

		sell_list_deposit_btn.off('click').on('click', function () {
			$.fn.deposit($(this));
		});

		$.fn.deposit = function (_this) {

			_this.hide();
			sell_list_processing_btn.show();

			$.ajax({

				type: 'post',
				url: '/ajax/sell_items',
				data: {sell_list_items_obj_str: JSON.stringify(sell_list_items_obj), _token: csrf_token},

				success: function (response) {

					try {

						response = JSON.parse(response);

						if (response.success == 0) {
							sellListAjaxMsg(response.error);
							sell_list_deposit_btn.show();
						}

					} catch (e) {

						if (response.indexOf('Token') > -1) {
							// our trade offer was sent
							// clean up so they can do another deposit w/ other items
							sell_list_wrap.html('');
							sell_list_item_count = 0;
							sell_list_item_count_span.text('0');
							sell_list_items_total_value = 0;
							sell_list_items_total_value_span.text('$0.00');

							// clear the sell list obj
							for (var member in sell_list_items_obj) delete sell_list_items_obj[member];

							// hide the deposit btn because trade offer has been sent
							sell_list_deposit_btn.hide();
							sellListAjaxMsg(response);

						} else {

							sellListAjaxMsg('Connection error, try again later.');
							sell_list_deposit_btn.show();
						}

					}

				},

				error: function () {
					sellListAjaxMsg('Connection error, try again later.');
					sell_list_deposit_btn.show();
				}

			});

		};

		function sellListAjaxMsg(msg) {
			sell_list_processing_btn.hide();
			blink(sell_list_deposit_ajax_msg.slideDown(60).html(msg));
		}

		// handle removing of item from sell list
		$(document).off('click', '.sell_list_item_x_icon').on('click', '.sell_list_item_x_icon', function () {

			var _this = $(this);

			var item_assetid = _this.parent('.sell_list_item_wrap').data('assetid'),
					key = _this.parent('.sell_list_item_wrap').data('key'),
					item_commodity = _this.parent('.sell_list_item_wrap').data('commodity');

			steam_inventory_wrap.find('.steam_inventory_item_wrap[data-assetid="' + item_assetid + '"]').show();

			var sell_list_item_quantity = 1;

			if (item_commodity == 1) {

				sell_list_item_quantity = _this.next().find('.steam_inventory_item_quantity').text();

				var inventory_item_quantity = steam_inventory_wrap.find('.steam_inventory_item_wrap[data-assetid="' + item_assetid + '"]').find('.steam_inventory_item_quantity').text(),
					new_quantity = Number(sell_list_item_quantity) + Number(inventory_item_quantity);

				steam_inventory_wrap.find('.steam_inventory_item_wrap[data-assetid="' + item_assetid + '"]').find('.steam_inventory_item_quantity').text(new_quantity);
			}

			// remove item from sell_list_items_obj
			delete sell_list_items_obj[key];
			sell_list_key--;

			_this.parent('.sell_list_item_wrap').remove();

			if (sell_list_item_count == $('#steam_inventory_data_div').data('total-item-count')) {
				steam_inventory_wrap.find('.sell_items_module_middle_text').remove();
			}

			if (sell_list_wrap.html() == '') {
				sell_list_deposit_ajax_msg.slideUp(100);
				sell_list_deposit_btn.hide();
				sell_list_wrap.html('<div class="sell_items_module_middle_text">Pick some items to sell.</div>');
			}

			sell_list_item_count -= Number(sell_list_item_quantity);
			sell_list_item_count_span.text(sell_list_item_count);

			sell_list_items_total_value -= Number(_this.data('price'));
			sell_list_items_total_value_span.text(accounting.formatMoney(sell_list_items_total_value));
		});

	}

}); // end of Core JS