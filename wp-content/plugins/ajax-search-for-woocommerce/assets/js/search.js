/**
 *  Ajax Autocomplete for jQuery, version 1.2.27
 *  (c) 2015 Tomas Kirda
 *
 *  Ajax Autocomplete for jQuery is freely distributable under the terms of an MIT-style license.
 *  For details, see the web site: https://github.com/devbridge/jQuery-Autocomplete
 *
 *  Modified by Damian Góra: http://damiangora.com
 *  Minify: https://javascript-minifier.com/
 */

/*jslint  browser: true, white: true, single: true, this: true, multivar: true */
/*global define, window, document, jQuery, exports, require */

// Expose plugin as an AMD module if AMD loader is present:
(function (factory) {
    "use strict";
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery'], factory);
    } else if (typeof exports === 'object' && typeof require === 'function') {
        // Browserify
        factory(require('jquery'));
    } else {
        // Browser globals
        factory(jQuery);
    }
}(function ($) {
    'use strict';

    var utils = (function () {
            return {
                escapeRegExChars: function (value) {
                    return value.replace(/[|\\{}()[\]^$+*?.]/g, "\\$&");
                },
                createNode: function (containerClass) {
                    var div = document.createElement('div');
                    div.className = containerClass;
                    div.style.position = 'absolute';
                    div.style.display = 'none';
                    div.setAttribute('unselectable', 'on');
                    return div;
                },
				highlight: function (suggestionValue, phrase) {

					if (dgwt_wcas.is_premium) {
						var i,
							tokens = phrase.split(/ /),
							highlighted = false,
							last = '';

						if (tokens) {
							last = tokens[tokens.length - 1];
							tokens = tokens.sort(function (a, b) {
								return b.length - a.length;
							});

							for (i = 0; i < tokens.length; i++) {
								if (tokens[i] && tokens[i].length >= 1) {

									var token = tokens[i].replace(/[\^\@]/g, '');

									if (token.length > 0) {
										if (token.trim().length === 1 && tokens[i] !== last) {
											var pattern = '((\\s|^)' + utils.escapeRegExChars(token.trim()) + '\\s)';
										} else if (token.trim().length === 1 && tokens[i] === last) {
											var pattern = '((\\s|^)' + utils.escapeRegExChars(token.trim()) + ')';
										} else {
											var pattern = '(' + utils.escapeRegExChars(token.trim()) + ')';
										}

										suggestionValue = suggestionValue.replace(new RegExp(pattern, 'gi'), '\^\^$1\@\@');
										highlighted = true;
									}
								}
							}
						}

						if (highlighted) {
							suggestionValue = suggestionValue.replace(/\^\^/g, '<strong>');
							suggestionValue = suggestionValue.replace(/@@/g, '<\/strong>');
						}


					} else {
						var pattern = '(' + utils.escapeRegExChars(phrase) + ')';
						suggestionValue = suggestionValue.replace(new RegExp(pattern, 'gi'), '<strong>$1<\/strong>');
					}

					return suggestionValue;
				},
                debounce: function (func, wait) {
                    var timeout,
                        debounceID = new Date().getUTCMilliseconds();

                    // First query in the chain
                    if (ajaxDebounceState.id.length === 0) {
                        ajaxDebounceState.id = debounceID;
                        func();
                        return;
                    }

                    ajaxDebounceState.id = debounceID;

                    timeout = setTimeout(function () {

                        if (debounceID !== ajaxDebounceState.id) {
                            clearTimeout(timeout);
                            return;
                        }

                        // Last query in the chain
                        func();
                        ajaxDebounceState.id = '';

                    }, wait);
                },
                mouseHoverDebounce: function (func, selector, wait) {
                    var timeout;

                    timeout = setTimeout(function () {

                        if ($(selector + ':hover').length > 0) {
                            func();
                        } else {
                            clearTimeout(timeout);
                            return;
                        }


                    }, wait);
                },
                getActiveInstance: function () {
                    var $el = $('.dgwt-wcas-search-wrapp.dgwt-wcas-active'),
                        instance;
                    if ($el.length > 0) {
                        $el.each(function () {
                            var $input = $(this).find('.dgwt-wcas-search-input');
                            if (typeof $input.data('autocomplete') == 'object') {
                                instance = $input.data('autocomplete');
                                return false;
                            }
                        });
                    }

                    return instance;
                },
				hashCode: function (s) {
					var h = 0, i = s.length;
					while (i > 0) {
						h = (h << 5) - h + s.charCodeAt(--i) | 0;
					}
					return h < 0 ? h * -1 : h;
				}
            };
        }()),
        ajaxDebounceState = {
            id: '',
            callback: null,
            ajaxSettings: null,
            object: null,
        },
        keys = {
            ESC: 27,
            TAB: 9,
            RETURN: 13,
            LEFT: 37,
            UP: 38,
            RIGHT: 39,
            DOWN: 40
        },
        noop = $.noop;

    function DgwtWcasAutocompleteSearch(el, options) {
        var that = this;

        // Shared variables:
        that.element = el;
        that.el = $(el);
        that.suggestions = [];
        that.badQueries = [];
        that.selectedIndex = -1;
        that.currentValue = that.element.value;
        that.timeoutId = null;
        that.cachedResponse = {};
        that.cachedDetails = {};
        that.cachedPrices = {};
        that.detailsRequestsSent = [];
        that.onChangeTimeout = null;
        that.onChange = null;
        that.isLocal = false;
        that.suggestionsContainer = null;
        that.detailsContainer = null;
        that.autoAligmentprocess = null;
        that.noSuggestionsContainer = null;
        that.latestActivateSource = '';
        that.actionTriggerSource = '';
        that.options = $.extend(true, {}, DgwtWcasAutocompleteSearch.defaults, options);
        that.classes = {
            selected: 'dgwt-wcas-suggestion-selected',
            suggestion: 'dgwt-wcas-suggestion',
            suggestionsContainerOrientTop: 'dgwt-wcas-suggestions-wrapp--top'
        };
        that.hint = null;
        that.hintValue = '';
        that.selection = null;
        that.overlayMobileState = 'off';
		that.isMouseDownOnSearchElements = false;

        // Initialize and set options:
        that.initialize();
        that.setOptions(options);

    }

    DgwtWcasAutocompleteSearch.utils = utils;

    $.DgwtWcasAutocompleteSearch = DgwtWcasAutocompleteSearch;

    DgwtWcasAutocompleteSearch.defaults = {
        ajaxSettings: {},
        autoSelectFirst: false,
        appendTo: 'body',
        serviceUrl: null,
        lookup: null,
        onSelect: null,
        width: 'auto',
        containerDetailsWidth: 'auto',
        showDetailsPanel: false,
        showImage: false,
        showPrice: false,
        showSKU: false,
        showDescription: false,
        showSaleBadge: false,
        showFeaturedBadge: false,
        dynamicPrices: false,
        saleBadgeText: 'sale',
        featuredBadgeText: 'featured',
        minChars: 3,
        maxHeight: 600,
        deferRequestBy: 0,
        params: {},
        formatResult: _formatResult,
        delimiter: null,
        zIndex: 999999999,
        type: 'GET',
        noCache: false,
        isRtl: false,
        onSearchStart: noop,
        onSearchComplete: noop,
        onSearchError: noop,
        preserveInput: false,
        searchFormClass: 'dgwt-wcas-search-wrapp',
        containerClass: 'dgwt-wcas-suggestions-wrapp',
        containerDetailsClass: 'dgwt-wcas-details-wrapp',
        searchInputClass: 'dgwt-wcas-search-input',
        preloaderClass: 'dgwt-wcas-preloader',
        closeTrigger: 'dgwt-wcas-close',
        formClass: 'dgwt-wcas-search-form',
        tabDisabled: false,
        dataType: 'text',
        currentRequest: null,
        triggerSelectOnValidInput: true,
        isPremium: false,
        overlayMobile: false,
        preventBadQueries: true,
        lookupFilter: _lookupFilter,
        paramName: 'query',
        transformResult: _transformResult,
        noSuggestionNotice: 'No results',
        orientation: 'bottom',
        forceFixPosition: false,
        positionFixed: false,
        debounceWaitMs: 400,
        sendGAEvents: true,
		enableGASiteSearchModule: false,
		showProductVendor: false,
		disableHits: false,
		disableSubmit: false,
    }

    function _lookupFilter(suggestion, originalQuery, queryLowerCase) {
        return suggestion.value.toLowerCase().indexOf(queryLowerCase) !== -1;
    }

    function _transformResult(response) {
        return typeof response === 'string' ? $.parseJSON(response) : response;
    }

    function _formatResult(suggestionValue, currentValue, highlight, options) {
        // Do not replace anything if there current value is empty
        if (!currentValue) {
            return suggestionValue;
        }

        if (highlight) {
            suggestionValue = utils.highlight(suggestionValue, currentValue);
        }

        if(!options.convertHtml){
        	return suggestionValue;
		}

        return suggestionValue.replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&apos;')
            .replace(/&lt;sup/g, '<sup')
            .replace(/&lt;\/sup/g, '</sup')
            .replace(/sup&gt;/g, 'sup>')
            .replace(/&lt;(\/?(strong|b|br))&gt;/g, '<$1>');

    }

    DgwtWcasAutocompleteSearch.prototype = {
        initialize: function () {
            var that = this;

            // Remove autocomplete attribute to prevent native suggestions:
            that.element.setAttribute('autocomplete', 'off');

            that.createContainers();

            that.registerEventsSearchBar();
            that.registerEventsSuggestions();
            that.registerEventsDetailsPanel();
			that.registerIconHandler();
            that.registerEventsFixedMenu();

            that.fixPositionCapture = function () {
                that.adjustContainerWidth();
                if (that.visible) {
                    that.fixPosition();
                }
            };

            $(window).on('resize.autocomplete', function(){
				var that = utils.getActiveInstance();
				if(typeof that != 'undefined') {
					that.fixPositionCapture();
				}
			});

            that.initMobileMode();

            that.hideAfterClickOutsideListener();


            // Mark as initialized
            that.suggestionsContainer.addClass('js-dgwt-wcas-initialized');

            if (that.detailsContainer && that.detailsContainer.length > 0) {
                that.detailsContainer.addClass('js-dgwt-wcas-initialized');
            }

        },
        createContainers: function (type) {
            var that = this,
                options = that.options;

            // Suggestions
            if ($('.' + options.containerClass).length == 0) {

                that.suggestionsContainer = $(DgwtWcasAutocompleteSearch.utils.createNode(options.containerClass));

                that.suggestionsContainer.appendTo(options.appendTo || 'body');

                that.suggestionsContainer.addClass('woocommerce');

                // Add conditional classes
                if (options.showImage === true) {
                    that.suggestionsContainer.addClass('dgwt-wcas-has-img');
                }

                // Price
                if (options.showPrice === true) {
                    that.suggestionsContainer.addClass('dgwt-wcas-has-price');
                }

                // Description
                if (options.showDescription === true) {
                    that.suggestionsContainer.addClass('dgwt-wcas-has-desc');
                }

                // SKU
                if (options.showSKU === true) {
                    that.suggestionsContainer.addClass('dgwt-wcas-has-sku');
                }

                // Headings
                if (options.showHeadings === true) {
                    that.suggestionsContainer.addClass('dgwt-wcas-has-headings');
                }

                // Only set width if it was provided:
                if (options.width !== 'auto') {
                    that.suggestionsContainer.width(options.width);
                }

            } else {

                that.suggestionsContainer = $('.' + that.options.containerClass);

            }

            // Details Panel
            if (that.canShowDetailsBox()) {

                if ($('.' + options.containerDetailsClass).length == 0) {
                    that.detailsContainer = $(DgwtWcasAutocompleteSearch.utils.createNode(options.containerDetailsClass));
                    that.detailsContainer.appendTo(options.appendTo || 'body');

                    that.detailsContainer.addClass('woocommerce');
                } else {

                    that.detailsContainer = $('.' + options.containerDetailsClass);

                }

            }

        },
        registerEventsSearchBar: function () {
            var that = this;

            // Click close icon
			$(document).on('click.autocomplete', '.' + that.options.closeTrigger, function () {
				var that = utils.getActiveInstance();
				that.close();
			});

			// Extra tasks on submit
			that.el.closest('.' + that.options.formClass).on('submit.autocomplete', function (e) {

				if (that.options.disableSubmit) {
					e.preventDefault();
					return false;
				}

				// Prevent submit empty form
				var $input = $(this).find('.' + that.options.searchInputClass);
				if ($input.length && $input.val().length === 0) {
					e.preventDefault();
					return false;
				}

				// If variation suggestion exist, click it instead submit search results page
				if (that.suggestions.length > 0) {

					$.each(that.suggestions, function (i, suggestion) {

						if (
							typeof suggestion.type != 'undefined'
							&& suggestion.type == 'product_variation'
						) {
							that.select(i);
							e.preventDefault();
							return false;
						}
					});

				}
			});

			// Position preloader
			if (document.readyState === 'complete') {
				that.positionPreloader();
			} else {
				$(window).on('load', function () {
					that.positionPreloader();
				});
			}


            that.el.on('keydown.autocomplete', function (e) {
                that.onKeyPress(e);
            });
            that.el.on('keyup.autocomplete', function (e) {
                that.onKeyUp(e);
            });
            that.el.on('blur.autocomplete', function () {
                that.onBlur();
            });
            that.el.on('focus.autocomplete', function () {
                that.onFocus();
            });
            that.el.on('change.autocomplete', function (e) {
                that.onKeyUp(e);
            });
            that.el.on('input.autocomplete', function (e) {
                that.onKeyUp(e);
            });

        },
        registerEventsSuggestions: function () {
            var that = this,
                suggestionSelector = '.' + that.classes.suggestion,
                suggestionsContainer = that.getSuggestionsContainer();

            // Register these events only once
            if (suggestionsContainer.hasClass('js-dgwt-wcas-initialized')) {
                return;
            }

            // Select suggestion and enable details panel on hovering over it
            $(document).on('mouseenter.autocomplete', suggestionSelector, function () {
                var that = utils.getActiveInstance();

                if(typeof that == 'undefined'){
                	return;
				}

                var currentIndex = $(this).data('index');
                var selector = '.dgwt-wcas-suggestion[data-index="' + currentIndex + '"]';

                var timeOffset = that.canShowDetailsBox() ? 100 : 1;

                if (that.selectedIndex != currentIndex) {
                    utils.mouseHoverDebounce(function () {
                        if (that.selectedIndex !== currentIndex) {
							that.latestActivateSource = 'mouse';
                            that.getDetails(that.suggestions[currentIndex]);
                            that.activate(currentIndex);
                        }
                    }, selector, timeOffset);

                }
            });

            var alreadyClicked = false;
            // Redirect to the new URL after click a suggestions
			$(document).on('click.autocomplete', suggestionSelector, function (e) {
				if (!alreadyClicked) {
					var that = utils.getActiveInstance();
					that.actionTriggerSource = 'click';

					alreadyClicked = true;
					setTimeout(function () {
						alreadyClicked = false;
					}, 500);

					if (typeof e.ctrlKey === 'undefined' || e.ctrlKey === false) {
						that.select($(this).data('index'));
						e.preventDefault();
					}
				} else {
					e.preventDefault();
				}
			});

			// FIX issue with touchpads for some laptops (marginal cases)
			$(document).on('mousedown.autocomplete', suggestionSelector, function (e) {
				var _this = this;
				if (e.button === 0) {
					setTimeout(function () {
						if (!alreadyClicked) {
							var that = utils.getActiveInstance();
							that.select($(_this).data('index'));
						}
					}, 250);
				}
			});

			// Mark cursor position for onBlur event
			$('.' + that.options.containerClass).on('mousedown.autocomplete', function (e) {
				var that = utils.getActiveInstance();
				that.isMouseDownOnSearchElements = true;
			});

        },
        registerEventsDetailsPanel: function () {
            var that = this,
                detailsContainer = that.getDetailsContainer();

            if (!that.canShowDetailsBox() || detailsContainer.hasClass('js-dgwt-wcas-initialized')) {
                return;
            }

            // Update quantity
            $(document).on('change.autocomplete', '[name="js-dgwt-wcas-quantity"]', function (e) {
                var $input = $(this).closest('.js-dgwt-wcas-pd-addtc').find('[data-quantity]');
                $input.attr('data-quantity', $(this).val());
            });

			// Mark cursor position for onBlur event
			$('.' + that.options.containerDetailsClass).on('mousedown.autocomplete', function (e) {
				var that = utils.getActiveInstance();
				that.isMouseDownOnSearchElements = true;
			});

        },
		registerIconHandler: function () {
			var that = this,
				$formWrapper = that.getFormWrapper();
			var $form = $formWrapper.find('.' + that.options.formClass);

			$formWrapper.on('click.autocomplete', '.js-dgwt-wcas-search-icon-handler', function (e) {

				var $input = $formWrapper.find('.' + that.options.searchInputClass);

				if ($formWrapper.hasClass('dgwt-wcas-layout-icon-open')) {

					that.hide();
					$form.hide(true);

					$formWrapper.removeClass('dgwt-wcas-layout-icon-open');


				} else {
					var $arrow = $formWrapper.find('.dgwt-wcas-search-icon-arrow');
					$form.hide();
					$arrow.hide();
					$formWrapper.addClass('dgwt-wcas-layout-icon-open');
					that.positionIconSearchMode($formWrapper);

					$form.fadeIn(200, function () {
						$arrow.show();
						that.positionPreloader($formWrapper);
						$input.focus();
					});

				}


			});

			if ($('.js-dgwt-wcas-initialized').length == 0 && $('.js-dgwt-wcas-search-icon-handler').length > 0) {

				$(document).on('click.autocomplete', function (event) {

					if ($('.dgwt-wcas-layout-icon-open').length) {

						var $target = $(event.target);

						if (!($target.closest('.' + that.options.searchFormClass).length > 0
							|| $target.closest('.' + that.options.containerClass).length > 0
							|| $target.closest('.' + that.options.containerDetailsClass).length > 0
						)) {
							var activeInstance = utils.getActiveInstance();

							if (typeof activeInstance != 'undefined') {
								var $formWrapper = activeInstance.getFormWrapper();
								var $form = $formWrapper.find('.' + that.options.formClass);

								$form.hide();
								activeInstance.hide(true);
								$form.css({'left': '0'});
							}

							that.hideIconModeSearch();
						}

					}
				});
			}

			$(window).on('resize.autocomplete', function () {
				that.applyFlexibleMode();
			});

			if (document.readyState == 'complete') {
				that.applyFlexibleMode();
			} else {
				$(window).on('load.autocomplete', function () {
					that.applyFlexibleMode();
				});
			}

		},
        registerEventsFixedMenu: function () {
            var that = this;

            $(window).on('scroll.autocomplete', function () {

                if (that.suggestions.length > 0 && that.elementOrParentIsFixed(that.getFormWrapper())) {
                    if ($(window).scrollTop() === 0) {
                        var timeSteps = [1, 10, 20, 30, 40, 50, 60, 70, 80, 90, 120, 140, 170, 200, 250, 400, 700, 1000, 2000];

                        timeSteps.forEach(function (offset) {
                            setTimeout(function () {
                                that.fixHeight();
                                that.fixPositionCapture();
                            }, offset);
                        });
                    } else {
                        that.fixHeight();
                        that.fixPositionCapture();
                    }
                }
            });

        },
        initMobileMode: function () {
            var that = this;
			var $formWrapper = that.getFormWrapper();

            if (
				$formWrapper.hasClass('js-dgwt-wcas-mobile-overlay-enabled')
                && that.isMobileMode()
            ) {

                $formWrapper.prepend('<div class="js-dgwt-wcas-enable-mobile-form dgwt-wcas-enable-mobile-form"></div>');

                var $el = $formWrapper.find('.js-dgwt-wcas-enable-mobile-form');

                $el.on('click.autocomplete', function (e) {
                    that.enableOverlayMobile();
                });

            }

        },
		applyFlexibleMode: function () {
			var that = this;
			var $flexibleSearch = $('.js-dgwt-wcas-layout-icon-flexible');

			if ($flexibleSearch.length) {

				if (that.isMobileMode()) {
					$flexibleSearch.addClass('js-dgwt-wcas-layout-icon');
					$flexibleSearch.addClass('dgwt-wcas-layout-icon');
				} else {
					$flexibleSearch.removeClass('js-dgwt-wcas-layout-icon');
					$flexibleSearch.removeClass('dgwt-wcas-layout-icon');
				}

				$flexibleSearch.addClass('dgwt-wcas-layout-icon-flexible-loaded');
			}
		},
        onFocus: function () {
            var that = this;
            $('.' + this.options.searchFormClass).removeClass('dgwt-wcas-active');
            that.getFormWrapper().addClass('dgwt-wcas-active');
            that.fixPositionCapture();
            if (that.el.val().length >= that.options.minChars) {
                that.onValueChange();
            }

        },
        onBlur: function () {
            var that = this,
                options = that.options,
                value = that.el.val(),
                query = that.getQuery(value);

            if(that.isMobileMode()){
            	return;
			}

			if (!that.isMouseDownOnSearchElements) {
				that.hide();

				if (that.selection && that.currentValue !== query) {
					(options.onInvalidateSelection || $.noop).call(that.element);
				}
			}
        },
        abortAjax: function () {
            var that = this;
            if (that.currentRequest) {
                that.currentRequest.abort();
                that.currentRequest = null;
            }
        },
        setOptions: function (suppliedOptions) {
            var that = this,
                $suggestionsContainer = that.getSuggestionsContainer(),

                options = $.extend({}, that.options, suppliedOptions);

            that.isLocal = Array.isArray(options.lookup);

            if (that.isLocal) {
                options.lookup = that.verifySuggestionsFormat(options.lookup);
            }

            options.orientation = that.validateOrientation(options.orientation, 'bottom');

            // Adjust height, width and z-index:
            //@TODO nie działa obliczanie prawej krawędzi ekranu
            $suggestionsContainer.css({
                'max-height': !that.canShowDetailsBox() ? options.maxHeight + 'px' : 'none',
                'width': options.width + 'px',
                'z-index': options.zIndex
            });

            // Add classes
            if (options.showDetailsPanel === true) {
                var $detailsContainer = that.getDetailsContainer();

                $detailsContainer.css({
                    'z-index': (options.zIndex - 1)
                });
            }

            options.onSearchComplete = function () {
                var $searchForm = that.getFormWrapper();
                $searchForm.removeClass('dgwt-wcas-processing');
                that.preloader('hide', 'form', 'dgwt-wcas-inner-preloader');
                that.showCloseButton();
            };

            this.options = options;
        },
        clearCache: function () {
            this.cachedResponse = {};
            this.cachedDetails = {};
            this.cachedPrices = {};
            this.badQueries = [];
        },
        clear: function (cache) {
            if (cache) {
                this.clearCache();
            }
            this.currentValue = '';
            this.suggestions = [];
        },
		close: function (){
			var that = this;

			that.hide();
			that.clear(false);
			that.hideCloseButton();
			that.el.closest('.' + that.options.searchFormClass).find('.' + that.options.searchInputClass).val('').focus();
		},
        disable: function () {
            var that = this;
            that.disabled = true;
            clearTimeout(that.onChangeTimeout);
            that.abortAjax();
        },
        enable: function () {
            this.disabled = false;
        },
        fixPosition: function () {
            var that = this,
                offset = that.getFormOffset(),
                suggestionsContainer = that.getSuggestionsContainer();

            suggestionsContainer.css(offset);

            if (that.canShowDetailsBox()) {
                that.fixPositionDetailsBox();
            }

        },
        fixPositionDetailsBox: function () {

            var that = this,
                $form = that.getFormWrapper(),
                $containerSuggestions = that.getSuggestionsContainer(),
                $containerDetails = that.getDetailsContainer(),
                offset = that.getFormOffset(),
                nativeOffsetLeft = offset.left,
                maxWidth = 550;


            if ($containerDetails.length == 0) {
                return false;
            }

            var borderCorrection = that.options.isRtl === true ? 1 : 2;
            var leftOffset = Math.round(offset.left);
            offset.left = leftOffset + Math.round($containerSuggestions.width() + borderCorrection);

            $containerDetails.css(offset);


            // Not set position on the bigger search form
            if ($form.width() >= maxWidth) {

                $('body').removeClass('dgwt-wcas-details-outside');
                $('body').removeClass('dgwt-wcas-details-right');
                $('body').removeClass('dgwt-wcas-details-left');

                if (that.options.isRtl === true) {
                    $containerSuggestions.css('left', leftOffset + Math.round($containerDetails.width() + borderCorrection) + 'px');
                    $containerDetails.css('left', (nativeOffsetLeft) + 'px');
                }

                return;
            }

            var windowWidth = $(window).width(),
                cDWidth = $containerDetails.width(),
                cOffset = $containerDetails.offset();

            $('body').addClass('dgwt-wcas-details-outside');


            if (that.options.isRtl === true) {
                offset.left = offset.left + 1;
            }

            var rightBorderCrossed = false;
            var leftBorderCrossed = false;

            // Right border crossed
            if (windowWidth < (cOffset.left + cDWidth)) {
                rightBorderCrossed = true;

                $('body').removeClass('dgwt-wcas-details-right');
                $('body').addClass('dgwt-wcas-details-left');

                $containerDetails.css('left', (Math.round(parseFloat($containerSuggestions.css('left').replace('px', ''))) - $containerDetails.outerWidth()) + 'px');

            }

            cOffset = $containerDetails.offset();

            // Left border crossed
            if (cOffset.left < 1) {
                leftBorderCrossed = true;

                $('body').removeClass('dgwt-wcas-details-left');
                $('body').addClass('dgwt-wcas-details-right');

            }

            if (leftBorderCrossed && rightBorderCrossed) {
                $('body').removeClass('dgwt-wcas-details-left');
                $('body').removeClass('dgwt-wcas-details-right');
                $('body').addClass('dgwt-wcas-details-notfit');
            } else {
                $('body').removeClass('dgwt-wcas-details-notfit');
            }


        },
        fixHeight: function () {

            var that = this,
                options = that.options;

            if (options.showDetailsPanel != true) {
                return false;
            }

            var $suggestionsWrapp = that.getSuggestionsContainer(),
                $detailsWrapp = that.getDetailsContainer();

            $suggestionsWrapp.css('height', 'auto');
            $detailsWrapp.css('height', 'auto');

            var sH = $suggestionsWrapp.outerHeight(),
                dH = $detailsWrapp.outerHeight(),
                minHeight = 340;

            $suggestionsWrapp.find('.dgwt-wcas-suggestion:last-child').removeClass('dgwt-wcas-suggestion-no-border-bottom');

            if (sH <= minHeight && dH <= minHeight) {
                return false;
            }

            $suggestionsWrapp.find('.dgwt-wcas-suggestion:last-child').addClass('dgwt-wcas-suggestion-no-border-bottom');

            if (dH < sH) {
                $detailsWrapp.css('height', (sH) + 'px');
            }

            if (sH < dH) {
                $suggestionsWrapp.css('height', dH + 'px');
            }

            return false;
        },
        automaticAlignment: function () {
            var that = this,
                $input = that.getFormWrapper().find('.dgwt-wcas-search-input'),
                $suggestionsContainer = that.getSuggestionsContainer(),
                $detailsWrapp = that.getDetailsContainer();

            if (that.autoAligmentprocess != null) {
                return;
            }

            var markers = [$input.width(), $suggestionsContainer.height()];
            if (that.options.showDetailsPanel) {
                markers[2] = $detailsWrapp.height();
            }

            that.autoAligmentprocess = setInterval(function () {

                var newMarkers = [$input.width(), $suggestionsContainer.height()];
                if (that.options.showDetailsPanel) {
                    newMarkers[2] = $detailsWrapp.height();
                }

                for (var i = 0; i < markers.length; i++) {

                    if (markers[i] != newMarkers[i]) {

                        that.fixHeight();
                        that.fixPositionCapture();
                        markers = newMarkers;
                        break;
                    }
                }


                if (that.options.showDetailsPanel) {

                    var innerDetailsHeight = $detailsWrapp.find('.dgwt-wcas-details-inner').height();


                    if ((innerDetailsHeight - $detailsWrapp.height()) > 2) {
                        that.fixHeight();
                    }
                }

            }, 10);

        },
        getFormOffset: function () {
            var that = this,
                $form = that.getFormWrapper(),
                $suggestionsContainer = that.getSuggestionsContainer();

            // Choose orientation
            var orientation = that.options.orientation,
                containerHeight = $form.outerHeight(),
                height = that.el.outerHeight(),
                offset = that.el.offset(),
                styles = {'top': offset.top, 'left': offset.left};

            if (orientation === 'auto') {
                var viewPortHeight = $(window).height(),
                    scrollTop = $(window).scrollTop(),
                    topOverflow = -scrollTop + offset.top - containerHeight,
                    bottomOverflow = scrollTop + viewPortHeight - (offset.top + height + containerHeight);

                orientation = (Math.max(topOverflow, bottomOverflow) === topOverflow) ? 'top' : 'bottom';
            }

            if (orientation === 'top') {
                var sh = $suggestionsContainer[0].getBoundingClientRect().top,
                    ft = $form[0].getBoundingClientRect().top;

                $suggestionsContainer.css('height', 'auto');
                if (ft < $suggestionsContainer.height()) {
                    $suggestionsContainer.height(ft - 10);
                }
                styles.top += -$suggestionsContainer.outerHeight();
            } else {
                styles.top += height;
            }

            return styles;

        },
        getFormWrapper: function () {
            var that = this;

            return that.el.closest('.' + that.options.searchFormClass);
        },
        getSuggestionsContainer: function () {
            var that = this;
            return $('.' + that.options.containerClass);
        },
        getDetailsContainer: function () {
            var that = this;
            return $('.' + that.options.containerDetailsClass);
        },
        scrollDownSuggestions: function () {
            var that = this,
                $el = that.getSuggestionsContainer();
            $el[0].scrollTop = $el[0].scrollHeight;
        },
        isCursorAtEnd: function () {
            var that = this,
                valLength = that.el.val().length,
                selectionStart = that.element.selectionStart,
                range;

            if (typeof selectionStart === 'number') {
                return selectionStart === valLength;
            }
            if (document.selection) {
                range = document.selection.createRange();
                range.moveStart('character', -valLength);
                return valLength === range.text.length;
            }
            return true;
        },
        onKeyPress: function (e) {
            var that = this;

            // If suggestions are hidden and user presses arrow down, display suggestions:
            if (!that.disabled && !that.visible && e.which === keys.DOWN && that.currentValue) {
                that.suggest();
                return;
            }

            if (that.disabled || !that.visible) {
                return;
            }

            switch (e.which) {
                case keys.ESC:
                    that.el.val(that.currentValue);
                    that.hide();
                    break;
                case keys.RIGHT:
                    if (that.hint && that.options.onHint && that.isCursorAtEnd()) {
                        that.selectHint();
                        break;
                    }
                    return;
                case keys.TAB:
                    if (that.hint && that.options.onHint) {
                        that.selectHint();
                        return;
                    }
                    if (that.selectedIndex === -1) {
                        that.hide();
                        return;
                    }
                    that.select(that.selectedIndex);
                    if (that.options.tabDisabled === false) {
                        return;
                    }
                    break;
				case keys.RETURN:

					if (that.selectedIndex === -1) {
						that.hide();
						return;
					}
					that.actionTriggerSource = 'enter';
					that.select(that.selectedIndex);
					break;
				case keys.UP:
                    that.moveUp();
                    break;
                case keys.DOWN:
                    that.moveDown();
                    break;
                default:
                    return;
            }

            // Cancel event if function did not return:
            e.stopImmediatePropagation();
            e.preventDefault();
        },
        onKeyUp: function (e) {
            var that = this;

            if (that.disabled) {
                return;
            }

            switch (e.which) {
                case keys.UP:
                case keys.DOWN:
                    return;
            }

            clearTimeout(that.onChangeTimeout);

            if (that.currentValue !== that.el.val()) {
                that.findBestHint();
                if (that.options.deferRequestBy > 0) {
                    // Defer lookup in case when value changes very quickly:
                    that.onChangeTimeout = setTimeout(function () {
                        that.onValueChange();
                    }, that.options.deferRequestBy);
                } else {
                    that.onValueChange();
                }
            }
        },
        onValueChange: function () {
            if (this.ignoreValueChange) {
                this.ignoreValueChange = false;
                return;
            }

            var that = this,
                options = that.options,
                value = that.el.val(),
                query = that.getQuery(value);

            if (that.selection && that.currentValue !== query) {
                that.selection = null;
                (options.onInvalidateSelection || $.noop).call(that.element);
            }

            clearTimeout(that.onChangeTimeout);
            that.currentValue = value;
            that.selectedIndex = -1;

            // Check existing suggestion for the match before proceeding:
            if (options.triggerSelectOnValidInput && that.isExactMatch(query)) {
                that.select(0);
                return;
            }

            if (query.length < options.minChars) {
                that.hideCloseButton();
                that.hide();
            } else {
                that.getSuggestions(query);
            }
        },
        isExactMatch: function (query) {
            var suggestions = this.suggestions;

            return (suggestions.length === 1 && suggestions[0].value.toLowerCase() === query.toLowerCase());
        },
        canShowDetailsBox: function () {
            var that = this;

            return that.options.showDetailsPanel == true && !that.isMobileMode();
        },
        isMobileMode: function () {
            var that = this;
            return $(window).width() < that.options.mobileBreakpoint
        },
        getQuery: function (value) {
            var delimiter = this.options.delimiter,
                parts;

            if (!delimiter) {
                return value.trim();
            }
            parts = value.split(delimiter);
            return $.trim(parts[parts.length - 1]);
        },
        getSuggestionsLocal: function (query) {
            var that = this,
                options = that.options,
                queryLowerCase = query.toLowerCase(),
                filter = options.lookupFilter,
                limit = parseInt(options.lookupLimit, 10),
                data;

            data = {
                suggestions: $.grep(options.lookup, function (suggestion) {
                    return filter(suggestion, query, queryLowerCase);
                })
            };

            if (limit && data.suggestions.length > limit) {
                data.suggestions = data.suggestions.slice(0, limit);
            }

            return data;
        },
        getSuggestions: function (q) {
            var response,
                that = this,
                options = that.options,
                serviceUrl = options.serviceUrl,
                searchForm = that.getFormWrapper(),
                params,
                cacheKey,
                ajaxSettings;

            options.params[options.paramName] = q;

            if (typeof dgwt_wcas.current_lang != 'undefined') {
                options.params['l'] = dgwt_wcas.current_lang;
            }

			options.params = that.applyCustomParams(options.params);

            that.preloader('show', 'form', 'dgwt-wcas-inner-preloader');
            searchForm.addClass('dgwt-wcas-processing');

            if (options.onSearchStart.call(that.element, options.params) === false) {
                return;
            }

            params = options.ignoreParams ? null : options.params;

            if ($.isFunction(options.lookup)) {
                options.lookup(q, function (data) {
                    that.suggestions = data.suggestions;
                    that.suggest();
                    that.selectFirstSuggestion(data.suggestions);
                    options.onSearchComplete.call(that.element, q, data.suggestions);
                });
                return;
            }

            if (that.isLocal) {
                response = that.getSuggestionsLocal(q);
            } else {
                if ($.isFunction(serviceUrl)) {
                    serviceUrl = serviceUrl.call(that.element, q);
                }
                cacheKey = serviceUrl + '?' + $.param(params || {});
                response = that.cachedResponse[cacheKey];
            }

            if (response && Array.isArray(response.suggestions)) {
                that.suggestions = response.suggestions;
                that.suggest();
                that.selectFirstSuggestion(response.suggestions);
                options.onSearchComplete.call(that.element, q, response.suggestions);
            } else if (!that.isBadQuery(q)) {
                that.abortAjax();

                ajaxSettings = {
                    url: serviceUrl,
                    data: params,
                    type: options.type,
                    dataType: options.dataType
                };

                $.extend(ajaxSettings, options.ajaxSettings);

                ajaxDebounceState.object = that;
                ajaxDebounceState.ajaxSettings = ajaxSettings;

                utils.debounce(function () {

                    var that = ajaxDebounceState.object,
                        ajaxSettings = ajaxDebounceState.ajaxSettings;


                    that.currentRequest = $.ajax(ajaxSettings).done(function (data) {
                        var result;
                        that.currentRequest = null;
                        result = that.options.transformResult(data, q);

                        if (typeof result.suggestions !== 'undefined') {
                            that.processResponse(result, q, cacheKey);
                            that.selectFirstSuggestion(result.suggestions);

                            if (
                                result.suggestions.length === 1
                                && typeof result.suggestions[0].type !== 'undefined'
                                && result.suggestions[0].type === 'no-results'
                            ) {
                                that.gaEvent(q, 'Autocomplete Search without results');
                            } else {
                                that.gaEvent(q, 'Autocomplete Search with results');
                            }

                        }

                        that.fixPositionCapture();

                        that.options.onSearchComplete.call(that.element, q, result.suggestions);

						that.updatePrices();

                    }).fail(function (jqXHR, textStatus, errorThrown) {
                        that.options.onSearchError.call(that.element, q, jqXHR, textStatus, errorThrown);
                    });


				}, options.debounceWaitMs);


            } else {
                options.onSearchComplete.call(that.element, q, []);
            }

        },
        getDetails: function (suggestion) {
            var that = this;

            // Disable details panel
            if (!that.canShowDetailsBox()) {
                return false;
            }

            // Brake if there are no suggestions
            if (suggestion == null || typeof suggestion.type == 'undefined') {
                return;
            }

            // Disable on more product suggestion
            if (typeof suggestion.type == 'string' && suggestion.type === 'more_products') {
                return;
            }

            that.fixHeight();

            var $containerDetails = that.getDetailsContainer(),
                currentObjectID = that.prepareSuggestionObjectID(suggestion),
                result;

            // Check cache
            result = that.cachedDetails[currentObjectID];

            if (result != null) {

                // Load response from cache
				that.detailsPanelSetScene(currentObjectID);
                that.fixHeight();
                that.fixPositionCapture();

            } else {

                var data = {
                    action: dgwt_wcas.action_result_details,
                    items: []
                };

                $.each(that.suggestions, function (i, suggestion) {

                    if (
                        typeof suggestion.type != 'undefined'
                        && suggestion.type != 'more_products'
                        && suggestion.type != 'headline'
                    ) {
                        var itemData = {
                            objectID: that.prepareSuggestionObjectID(suggestion),
                            value: suggestion.value != null ? suggestion.value : ''
                        };
                        data.items.push(itemData);
                    }
                });


                that.detailsPanelClearScene();
                that.preloader('show', 'details', '');

                // Prevent duplicate ajax requests
                if ($.inArray(currentObjectID, that.detailsRequestsSent) != -1) {
                    return;
                } else {
                    that.detailsRequestsSent.push(currentObjectID);
                }

                $.ajax({
                    data: data,
                    type: 'post',
                    url: dgwt_wcas.ajax_details_endpoint,
                    success: function (response) {

                        var result = typeof response === 'string' ? jQuery.parseJSON(response) : response;

                        if (typeof result.items != 'undefined') {
                            for (var i = 0; i < result.items.length; i++) {
								var cacheKey = result.items[i]['objectID'];
								that.cachedDetails[cacheKey] = {html: result.items[i]['html']}
								that.detailsPanelAddToScene(cacheKey);

								if (typeof result.items[i]['price'] != 'undefined' && result.items[i]['price'].length > 0) {
									that.cachedPrices[cacheKey] = result.items[i]['price'];
								}

                                // Preload images
                                if (typeof result.items[i]['imageSrc'] != 'undefined' && result.items[i]['imageSrc'].length > 0) {
                                    var tempImg = new Image();
                                    tempImg.src = result.items[i]['imageSrc'];
                                }

                            }
                        }

                        that.preloader('hide', 'details', '');

                        var currentObjectID = that.prepareSuggestionObjectID(that.suggestions[that.selectedIndex]);

                        if (that.cachedDetails[currentObjectID] != null) {
							that.detailsPanelSetScene(currentObjectID);
                        } else {
                            // @TODO Maybe display some error or placeholder
							that.detailsPanelClearScene();
                        }
                        that.fixPositionCapture();
                        that.fixHeight();

                        that.updatePrices(true);
                    },
                    error: function (jqXHR, exception) {

                        that.preloader('hide', 'details', '');

						that.detailsPanelClearScene();
                        that.fixPositionCapture();
                        that.fixHeight();
                    },
                });
            }

            $(document).trigger('dgwtWcasDetailsPanelLoaded', that);
        },
		updatePrices: function (noAjax) {
			var that = this,
				i, j,
				productsToLoad = [];

			if (!(that.options.showPrice && that.options.dynamicPrices)) {
				return;
			}

			if (that.suggestions.length == 0) {
				return;
			}

			for (i = 0; i < that.suggestions.length; i++) {

				if (
					typeof that.suggestions[i].type != 'undefined'
					&& (that.suggestions[i].type == 'product' || that.suggestions[i].type == 'product_variation')
				) {
					var key = 'product__' + that.suggestions[i].post_id;

					if (typeof that.cachedPrices[key] != 'undefined') {

						that.updatePrice(i, that.cachedPrices[key]);

					} else {

						that.applyPreloaderForPrice(i);

						productsToLoad.push(that.suggestions[i].post_id);
					}
				}

			}


			if (!noAjax && productsToLoad.length > 0) {

				var data = {
					action: typeof dgwt_wcas.action_get_prices == 'undefined' ? 'dgwt_wcas_get_prices' : dgwt_wcas.action_get_prices,
					items: productsToLoad
				};

				$.ajax({
					data: data,
					type: 'post',
					url: dgwt_wcas.ajax_prices_endpoint,
					success: function (response) {

						if (typeof response.success != 'undefined' && response.success && response.data.length > 0) {
							for (i = 0; i < response.data.length; i++) {

								var postID = response.data[i].id,
									price = response.data[i].price;

								if (that.suggestions.length > 0) {
									for (j = 0; j < that.suggestions.length; j++) {
										if (
											typeof that.suggestions[j].type != 'undefined'
											&& (that.suggestions[j].type == 'product' || that.suggestions[j].type == 'product_variation')
											&& that.suggestions[j].post_id == postID
										) {

											var key = 'product__' + postID;

											that.cachedPrices[key] = price;

											that.updatePrice(j, price);

										}
									}
								}
							}
						}

					},
					error: function (jqXHR, exception) {

					},
				});

			}

		},
		updatePrice: function (index, price) {
			var that = this;

			if(typeof that.suggestions[index] != 'undefined'){

				that.suggestions[index].price = price;

				var $price = $('.dgwt-wcas-suggestions-wrapp').find('[data-index="' + index + '"] .dgwt-wcas-sp');

				if($price.length){
					$price.html(price);
				}
			}

		},
		applyCustomParams: function(params){
			var that = this;

			// Custom params (global)
			if (typeof dgwt_wcas.custom_params == 'object') {
				var cp = dgwt_wcas.custom_params;
				for (var property in cp) {
					params[property] = cp[property];
				}
			}

			// Custom params (local)
			var inputCustomParams = that.el.data('custom-params');

			if(typeof inputCustomParams === 'object'){
				for (var property in inputCustomParams) {
					params[property] = inputCustomParams[property];
				}
			}

        	return params;
		},
		applyPreloaderForPrice: function(index){
			var that = this;

			if(typeof that.suggestions[index] != 'undefined'){
				var $price = $('.dgwt-wcas-suggestions-wrapp').find('[data-index="' + index + '"] .dgwt-wcas-sp');
				if($price.length){
					$price.html('<div class="dgwt-wcas-preloader-price"><div class="dgwt-wcas-preloader-price-inner"> <div></div><div></div><div></div></div></div>');
				}
			}
		},
        prepareSuggestionObjectID: function (suggestion) {
            var objectID = '';

            if (typeof suggestion != 'undefined' && typeof suggestion.type != 'undefined') {

                //Products and post types
                if (suggestion.post_id != null) {
                    objectID = suggestion.type + '__' + suggestion.post_id;

                    if (suggestion.type === 'product_variation') {
                        objectID += '__' + suggestion.variation_id;
                    }

                    // Post types
                    if (typeof suggestion.post_type != 'undefined') {
                        objectID = suggestion.type + '__' + suggestion.post_id + '__' + suggestion.post_type;
                    }

                }

                //Terms
                if (suggestion.term_id != null && suggestion.taxonomy != null) {
                    objectID = suggestion.type + '__' + suggestion.term_id + '__' + suggestion.taxonomy;
                }
            }

            return objectID;
        },
		detailsPanelSetScene: function (objectID) {
			var that = this,
				$containerDetails = that.getDetailsContainer(),
				objectHash = utils.hashCode(objectID),
				$el = $containerDetails.find('.dgwt-wcas-details-inner[data-object="' + objectHash + '"]');

			if ($el.length) {
				that.preloader('hide', 'details', '');
				that.detailsPanelClearScene();
				$el.addClass('dgwt-wcas-details-inner-active');
			}
		},
		detailsPanelAddToScene: function (objectID) {
			var that = this,
				$containerDetails = that.getDetailsContainer(),
				object = that.cachedDetails[objectID],
				objectHash = utils.hashCode(objectID),
				html = '';

			if (typeof object != 'undefined' && typeof object.html == 'string') {
				html = object.html.replace('<div ', '<div data-object="' + objectHash + '" ');
			}

			if ($containerDetails.find('.dgwt-wcas-details-inner[data-object="' + objectHash + '"]').length == 0) {
				$containerDetails.append(html);
			}
		},
		detailsPanelClearScene: function () {
			var that = this,
				$containerDetails = that.getDetailsContainer(),
				$views = $containerDetails.find('.dgwt-wcas-details-inner');

			if ($views.length) {
				$views.removeClass('dgwt-wcas-details-inner-active');
			}
		},
        selectFirstSuggestion: function (suggestions) {
            var that = this,
                index = 0,
                noResults = false;

            if (!that.canShowDetailsBox()) {
                return;
            }

            if (suggestions != 'undefined' && suggestions.length > 0) {
                $.each(that.suggestions, function (i, suggestion) {

                    if (
                        typeof suggestion.type != 'undefined'
                        && suggestion.type != 'more_products'
                        && suggestion.type != 'headline'
                        && suggestion.type != 'no-results'
                    ) {
                        index = i;
                        return false;
                    }

                    if (typeof suggestion.type === 'undefined' || suggestion.type === 'no-results') {
                        noResults = true;
                    }

                });
            }

            if (noResults) {
                return;
            }

			that.latestActivateSource = 'system';
            that.getDetails(suggestions[index]);
            that.activate(index);
        },
        isBadQuery: function (q) {
            if (!this.options.preventBadQueries) {
                return false;
            }

            var badQueries = this.badQueries,
                i = badQueries.length;

            while (i--) {
                if (q.indexOf(badQueries[i]) === 0) {
                    return true;
                }
            }

            return false;
        },
        hide: function (clear) {
            var that = this,
				$formWrapper = that.getFormWrapper(),
                $container = that.getSuggestionsContainer(),
                $containerDetails = that.getDetailsContainer();

            if ($.isFunction(that.options.onHide) && that.visible) {
                that.options.onHide.call(that.element, container);
            }

            that.visible = false;
            that.selectedIndex = -1;
            clearTimeout(that.onChangeTimeout);
            $container.hide();
            $container.removeClass(that.classes.suggestionsContainerOrientTop);
            $containerDetails.hide();

            $('body').removeClass('dgwt-wcas-open');
            $('body').removeClass('dgwt-wcas-block-scroll');
            $('body').removeClass('dgwt-wcas-is-details');
            $('body').removeClass('dgwt-wcas-full-width');
            $('body').removeClass('dgwt-wcas-nores');
            $('body').removeClass('dgwt-wcas-details-outside');
            $('body').removeClass('dgwt-wcas-details-right');
            $('body').removeClass('dgwt-wcas-details-left');

            if (that.autoAligmentprocess != null) {
                clearInterval(that.autoAligmentprocess);
                that.autoAligmentprocess = null;
            }

			if (typeof clear == 'boolean' && clear) {

				that.hideCloseButton();

				that.currentValue = '';
				that.suggestions = [];

			}

			that.signalHint(null);
		},
		positionIconSearchMode: function ($formWrapper) {
			var that = this,
				side = 'right',
				formLeftValue = -20;

			var $form = $formWrapper.find('.' + that.options.formClass);
			var formWidth = $form.width(),
				windowWidth = $(window).width();

			var iconLeftOffset = $formWrapper[0].getBoundingClientRect().left;
			var formLeftOffset = $form[0].getBoundingClientRect().left;

			// Is the icon on left or right side of screen?
			if (iconLeftOffset + 10 < windowWidth / 2) {
				side = 'left';
			}

			var iconLeftRatio = (iconLeftOffset + 10) / windowWidth;

			formLeftValue = Math.floor(-1 * (formWidth * iconLeftRatio));

			// Prevent shifting to the left more than the icon position (also positioned from the left)
			formLeftValue = Math.max(formLeftValue, -1 * iconLeftOffset);

			$form.css({'left': formLeftValue + 'px'});

		},
		hideIconModeSearch: function () {

			var $openedElements = $('.dgwt-wcas-layout-icon-open');

			if ($openedElements.length > 0) {
				$openedElements.removeClass('dgwt-wcas-layout-icon-open');
			}
		},
        hideAfterClickOutsideListener: function () {
            var that = this;
            if (!that.isMobileMode()) {

                $(document).mouseup(function (e) {
                    if (!that.visible) {
                        return;
                    }

                    var $container = that.getSuggestionsContainer(),
                        $containerDetails = that.getDetailsContainer(),
                        outsideForm = !($(e.target).closest('.' + that.options.searchFormClass).length > 0 || $(e.target).hasClass(that.options.searchFormClass)),
                        outsideContainer = !($(e.target).closest('.' + that.options.containerClass).length > 0 || $(e.target).hasClass(that.options.containerClass));


                    if (!that.canShowDetailsBox()) {

                        if (outsideForm && outsideContainer) {
                            that.hide();
                        }

                    } else {

                        var outsidecontainerDetails = !($(e.target).closest('.' + that.options.containerDetailsClass).length > 0 || $(e.target).hasClass(that.options.containerDetailsClass));

                        if (outsideForm && outsideContainer && outsidecontainerDetails) {
                            that.hide();
                        }

                    }

                });

            }
        },
        suggest: function () {
            if (!this.suggestions.length) {
                this.hide();
                return;
            }

            var that = this,
                options = that.options,
                groupBy = options.groupBy,
                formatResult = options.formatResult,
                value = that.getQuery(that.currentValue),
                className = that.classes.suggestion,
                classSelected = that.classes.selected,
                container = that.getSuggestionsContainer(),
                containerDetails = that.getDetailsContainer(),
                noSuggestionsContainer = $(that.noSuggestionsContainer),
                beforeRender = options.beforeRender,
                html = '',
                category,
                formatGroup = function (suggestion, index) {
                    var currentCategory = suggestion.data[groupBy];

                    if (category === currentCategory) {
                        return '';
                    }

                    category = currentCategory;

                    return '<div class="autocomplete-group"><strong>' + category + '</strong></div>';
                };

            if (options.triggerSelectOnValidInput && that.isExactMatch(value)) {
                that.select(0);
                return;
            }

            $('body').removeClass('dgwt-wcas-nores');

            // Build suggestions inner HTML:
            $.each(that.suggestions, function (i, suggestion) {

                var parent = '',
                    dataAttrs = '',
                    is_img = false,
					url = typeof suggestion.url == 'string' && suggestion.url.length ? suggestion.url : '#';

                if (groupBy) {
                    html += formatGroup(suggestion, value, i);
                }

                if (typeof suggestion.type == 'undefined' || (suggestion.type != 'product') && suggestion.type != 'product_variation') {

                    var classes = className,
                        innerClass = 'dgwt-wcas-st',
                        prepend = '',
                        append = '',
                        title = '',
                        highlight = true,
                        isImg;

                    if (suggestion.taxonomy === 'product_cat') {
                        classes += ' dgwt-wcas-suggestion-tax dgwt-wcas-suggestion-cat';
                        if (!options.showHeadings) {
                            prepend += '<span class="dgwt-wcas-st--direct-headline">' + dgwt_wcas.labels.category + '</span>';
                        }
                        if (typeof suggestion.breadcrumbs != 'undefined' && suggestion.breadcrumbs) {
                            title = suggestion.breadcrumbs + ' &gt; ' + suggestion.value;
                            append += '<span class="dgwt-wcas-st-breadcrumbs">' + dgwt_wcas.labels.in + ' ' + suggestion.breadcrumbs + '</span>';
                            //@TODO RTL support
                        }

                    } else if (suggestion.taxonomy === 'product_tag') {
                        classes += ' dgwt-wcas-suggestion-tax dgwt-wcas-suggestion-tag';
                        if (!options.showHeadings) {
                            prepend += '<span class="dgwt-wcas-st--direct-headline">' + dgwt_wcas.labels.tag + '</span>';
                        }
                    } else if (options.isPremium && suggestion.taxonomy === options.taxonomyBrands) {
                        classes += ' dgwt-wcas-suggestion-tax dgwt-wcas-suggestion-brand';
                        if (!options.showHeadings) {
                            prepend += '<span class="dgwt-wcas-st--direct-headline">' + dgwt_wcas.labels.brand + '</span>';
                        }
                    } else if (options.isPremium && suggestion.type === 'vendor') {
						classes += ' dgwt-wcas-suggestion-vendor dgwt-wcas-suggestion-vendor';
						if (!options.showHeadings) {
							prepend += '<span class="dgwt-wcas-st--direct-headline">' + dgwt_wcas.labels.vendor + '</span>';
						}
					} else if (options.isPremium && suggestion.type === 'post') {
                        classes += ' dgwt-wcas-suggestion-pt dgwt-wcas-suggestion-tp-post';
                        if (!options.showHeadings) {
                            prepend += '<span class="dgwt-wcas-st--direct-headline">' + dgwt_wcas.labels.post + '</span>';
                        }
                    } else if (options.isPremium && suggestion.type === 'page') {
                        classes += ' dgwt-wcas-suggestion-pt dgwt-wcas-suggestion-pt-page';
                        if (!options.showHeadings) {
                            prepend += '<span class="dgwt-wcas-st--direct-headline">' + dgwt_wcas.labels.page + '</span>';
                        }
                    } else if (suggestion.type === 'more_products') {
                        classes += ' js-dgwt-wcas-suggestion-more dgwt-wcas-suggestion-more';
                        innerClass = 'dgwt-wcas-st-more';
                        suggestion.value = dgwt_wcas.labels.show_more + ' (' + suggestion.total + ')';
                        highlight = false;
                    } else if (options.showHeadings && suggestion.type === 'headline') {
                        classes += ' js-dgwt-wcas-suggestion-headline dgwt-wcas-suggestion-headline';
                        if (typeof dgwt_wcas.labels[suggestion.value + '_plu'] != 'undefined') {
                            suggestion.value = dgwt_wcas.labels[suggestion.value + '_plu'];
                        }
                        highlight = false;
                    } else {
                        classes += ' dgwt-wcas-suggestion-nores';
                        suggestion.value = dgwt_wcas.labels.no_results;
                        highlight = false;
                        if (options.showDetailsPanel === true) {
							that.detailsPanelClearScene();
                        }
                        $('body').addClass('dgwt-wcas-nores');
                    }

					// Image
					if (typeof suggestion.image_src != 'undefined' && suggestion.image_src) {
						isImg = true;
					}

                    title = title.length > 0 ? ' title="' + title + '"' : '';

					html += '<a href="' + url + '" class="' + classes + '" data-index="' + i + '">';

                    if(isImg) {
						html += '<span class="dgwt-wcas-si"><img src="' + suggestion.image_src + '" /></span>';
						html += '<div class="dgwt-wcas-content-wrapp">';
					}

					html += '<span' + title + ' class="' + innerClass + '">';

					if (suggestion.type === 'vendor') {
						html += '<span class="dgwt-wcas-st-title">' + prepend + formatResult(suggestion.value, value, highlight, options) + append + '</span>';

						// Vendor city
						if (suggestion.shop_city) {
							html += '<span class="dgwt-wcas-vendor-city"><span> - </span>' + formatResult(suggestion.shop_city, value, true, options) + '</span>';
						}

						// Description
						if (typeof suggestion.desc != 'undefined' && suggestion.desc) {
							html += '<span class="dgwt-wcas-sd">' + formatResult(suggestion.desc, value, true, options) + '</span>';
						}

					}else{
						html += prepend + formatResult(suggestion.value, value, highlight, options) + append;
					}

					html += '</span>';

					html += isImg ? '</div>' : '';
                    html += '</a>';
                } else {

                    // Image
                    if (options.showImage === true && typeof suggestion.thumb_html != 'undefined') {
                        is_img = true;
                    }

                    var sugVarClass = suggestion.type === 'product_variation' ? ' dgwt-wcas-suggestion-product-var' : '';
                    // One suggestion HTML
                    dataAttrs += typeof suggestion.post_id != 'undefined' ? 'data-post-id="' + suggestion.post_id + '" ' : '';
                    dataAttrs += typeof suggestion.taxonomy != 'undefined' ? 'data-taxonomy="' + suggestion.taxonomy + '" ' : '';
                    dataAttrs += typeof suggestion.term_id != 'undefined' ? 'data-term-id="' + suggestion.term_id + '" ' : '';
                    html += '<a href="' + url + '" class="' + className + ' dgwt-wcas-suggestion-product' + sugVarClass + '" data-index="' + i + '" ' + dataAttrs + '>';

                    // Image
                    if (is_img) {
                        html += '<span class="dgwt-wcas-si">' + suggestion.thumb_html + '</span>';
                    }


                    html += is_img ? '<div class="dgwt-wcas-content-wrapp">' : '';


                    // Title
                    html += '<span class="dgwt-wcas-st">';
                    html += '<span class="dgwt-wcas-st-title">' + formatResult(suggestion.value, value, true, options) + parent + '</span>';

                    // SKU
                    if (options.showSKU === true && typeof suggestion.sku != 'undefined' && suggestion.sku.length > 0) {
                        html += '<span class="dgwt-wcas-sku">(' + dgwt_wcas.labels.sku_label + ' ' + formatResult(suggestion.sku, value, true, options) + ')</span>';
                    }

                    // Description
                    if (options.showDescription === true && typeof suggestion.desc != 'undefined' && suggestion.desc) {
                        html += '<span class="dgwt-wcas-sd">' + formatResult(suggestion.desc, value, true, options) + '</span>';
                    }

					// Vendor
					if (options.showProductVendor === true && typeof suggestion.vendor != 'undefined' && suggestion.vendor) {
						var vendorBody = '<span class="dgwt-wcas-product-vendor"><span class="dgwt-wcas-product-vendor-label">' + dgwt_wcas.labels.vendor_sold_by + ' </span>' + suggestion.vendor + '</span>'

						if (typeof suggestion.vendor_url != 'undefined' && suggestion.vendor_url) {
							// Since version v1.12.0 suggestions tag was changed from <div> to <a> and vendor links are no longer supported.
							html += '<span class="dgwt-wcas-product-vendor-link" data-url="' + suggestion.vendor_url + '">' + vendorBody + '</span>';
						} else {
							html += vendorBody;
						}

					}

                    html += '</span>';

                    // Price
                    if (options.showPrice === true && typeof suggestion.price != 'undefined') {
                        html += '<span class="dgwt-wcas-sp">' + suggestion.price + '</span>';
                    }

                    // On sale product badge
                    if (options.showFeaturedBadge === true && suggestion.on_sale === true) {
                        html += '<span class="dgwt-wcas-badge dgwt-wcas-badge-os">' + options.saleBadgeText + '</span>';
                    }

                    // Featured product badge
                    if (options.showFeaturedBadge === true && suggestion.featured === true) {
                        html += '<span class="dgwt-wcas-badge dgwt-wcas-badge-f">' + options.featuredBadgeText + '</span>';
                    }


                    html += is_img ? '</div>' : '';
                    html += '</div>';

                }
            });

            this.adjustContainerWidth();

            noSuggestionsContainer.detach();
            container.html(html);

            if ($.isFunction(beforeRender)) {
                beforeRender.call(that.element, container, that.suggestions);
            }

            container.show();

            // Add class on show
            $('body').addClass('dgwt-wcas-open');

            // Reset the latest mousedown position
			that.isMouseDownOnSearchElements = false;

            that.automaticAlignment();

            if (options.showDetailsPanel === true) {
                $('body').addClass('dgwt-wcas-is-details');
                containerDetails.show();
                that.fixHeight();
            }

            // Select first value by default:
            if (options.autoSelectFirst) {
                that.selectedIndex = 0;
                container.scrollTop(0);
                container.children('.' + className).first().addClass(classSelected);
            }

            that.visible = true;
            that.fixPositionCapture();

            if (that.options.orientation === 'top') {
                that.getSuggestionsContainer().addClass(that.classes.suggestionsContainerOrientTop);
                $('body').addClass('dgwt-wcas-block-scroll');
                setTimeout(function () {
                    that.scrollDownSuggestions();
                }, 300);
            }

            that.findBestHint();
        },
        adjustContainerWidth: function () {
            var that = this,
                options = that.options,
                width,
                container = $('body'),
                searchForm = that.getFormWrapper(),
                containerSugg = that.getSuggestionsContainer(),
                containerDetails = that.getDetailsContainer(),
                maxWidth = 550,
                offset = that.getFormOffset();

            if(!searchForm.length){
                return;
            }

            var realWidth = getComputedStyle(searchForm[0]).width;

            realWidth = Math.round(parseFloat(realWidth.replace('px', '')));

            // If width is auto, adjust width before displaying suggestions,
            if (options.width === 'auto') {
                width = that.el.outerWidth();
                containerSugg.css('width', width + 'px');
            }

            if (that.canShowDetailsBox()) {

                // Set specific style on the bigger search form
                if (searchForm.width() >= maxWidth) {

                    container.addClass('dgwt-wcas-full-width');


                    if (realWidth % 2 == 0) {
                        containerSugg.css('width', Math.round(realWidth / 2));
                        containerDetails.css('width', Math.round(realWidth / 2));
                    } else {
                        containerSugg.css('width', Math.floor(realWidth / 2));
                        containerDetails.css('width', Math.ceil(realWidth / 2));
                    }


                    container.removeClass('dgwt-wcas-details-left');
                    container.removeClass('dgwt-wcas-details-right');

                    if (options.isRtl === true) {
                        containerDetails.css('left', '0');
                    } else {
                        containerSugg.css('left', (realWidth / 2 + offset.left) + 'px');
                    }

                    return;
                }

                container.addClass('dgwt-wcas-details-right');

            }

        },
		positionPreloader: function ($formWrapper) {

        	var $submit = typeof $formWrapper == 'object' ? $formWrapper.find('.dgwt-wcas-search-submit') : $('.dgwt-wcas-search-submit');

			if ($submit.length > 0) {
				$submit.each(function () {

					var $preloader = $(this).closest('.dgwt-wcas-search-wrapp').find('.dgwt-wcas-preloader');

					if (dgwt_wcas.is_rtl == 1) {
						$preloader.css('left', (6 + $(this).outerWidth()) + 'px');
					} else {
						$preloader.css('right', $(this).outerWidth() + 'px');
					}
				});
			}

		},
        findBestHint: function () {
            var that = this,
                value = that.el.val().toLowerCase(),
                bestMatch = null;

            if (!value) {
                return;
            }

            $.each(that.suggestions, function (i, suggestion) {
                var foundMatch = suggestion.value.toLowerCase().indexOf(value) === 0;
                if (foundMatch) {
                    bestMatch = suggestion;
                }
                return !foundMatch;
            });

            that.signalHint(bestMatch);
        },
        signalHint: function (suggestion) {
            var hintValue = '',
                that = this;
            if (suggestion) {
                hintValue = that.currentValue + suggestion.value.substr(that.currentValue.length);
            }
            if (that.hintValue !== hintValue) {
                that.hintValue = hintValue;
                that.hint = suggestion;
                (this.options.onHint || $.noop)(hintValue);
            }
        },
        /*
         * Manages preloader
         *
         * @param action (show or hide)
         * @param container (parent selector)
         * @param cssClass
         */
        preloader: function (action, place, cssClass) {
            var that = this,
                html,
                $container,
                defaultClass = 'dgwt-wcas-preloader-wrapp',
                cssClasses = cssClass == null ? defaultClass : defaultClass + ' ' + cssClass;

            if (place === 'form') {

				// Return early if the preloader is disable via the settings for a search bar
				if (dgwt_wcas.show_preloader != 1) {
					return;
				}

                $container = that.getFormWrapper().find('.dgwt-wcas-preloader');
            } else if (place === 'details') {
                $container = that.getDetailsContainer();
            }

            if ($container.length == 0) {
                return;
            }

            // Handle preloader for a search bar
            if (place === 'form') {
                if (action === 'hide') {
                    $container.removeClass(cssClass);
					$container.html('');
                } else {
                    $container.addClass(cssClass);
                    if(typeof dgwt_wcas.preloader_icon == 'string'){
						$container.html(dgwt_wcas.preloader_icon);
					}
                }
                return;
            }

			// Handle preloader for a details panel
			var $preloader = $container.find('.' + defaultClass);

			// Action hide
			if (action === 'hide') {
				if ($preloader.length) {
					$preloader.remove();
				}
				return
			}

            // Action show
            if (action === 'show') {
                var rtlSuffix = that.options.isRtl ? '-rtl' : '';
                html = '<div class="' + cssClasses + '"><img class="dgwt-wcas-placeholder-preloader" src="' + dgwt_wcas.img_url + 'placeholder' + rtlSuffix + '.png" /></div>';
                that.detailsPanelClearScene();
				if ($preloader.length) {
					$preloader.remove();
				}
                $container.prepend(html);
            }
        },
        verifySuggestionsFormat: function (suggestions) {

            // If suggestions is string array, convert them to supported format:
            if (suggestions.length && typeof suggestions[0] === 'string') {
                return $.map(suggestions, function (value) {
                    return {value: value, data: null};
                });
            }

            return suggestions;
        },
        validateOrientation: function (orientation, fallback) {
            orientation = $.trim(orientation || '').toLowerCase();

            if ($.inArray(orientation, ['auto', 'bottom', 'top']) === -1) {
                orientation = fallback;
            }

            return orientation;
        },
        processResponse: function (result, originalQuery, cacheKey) {
            var that = this,
                options = that.options;

            result.suggestions = that.verifySuggestionsFormat(result.suggestions);

            // Cache results if cache is not disabled:
            if (!options.noCache) {
                that.cachedResponse[cacheKey] = result;
                if (options.preventBadQueries && !result.suggestions.length) {
                    that.badQueries.push(originalQuery);
                }
            }

            // Return if originalQuery is not matching current query:
            if (originalQuery !== that.getQuery(that.currentValue)) {
                return;
            }

            if (that.options.orientation === 'top') {
                result.suggestions.reverse();
            }

            that.suggestions = result.suggestions;
            that.suggest();
        },
        activate: function (index) {
            var that = this,
                activeItem,
                selected = that.classes.selected,
                container = that.getSuggestionsContainer(),
                children = container.find('.' + that.classes.suggestion);

            container.find('.' + selected).removeClass(selected);

            that.selectedIndex = index;

            if (that.selectedIndex !== -1 && children.length > that.selectedIndex) {
                activeItem = children.get(that.selectedIndex);
                $(activeItem).addClass(selected);
                return activeItem;
            }

            return null;
        },
        selectHint: function () {
            var that = this,
                i = $.inArray(that.hint, that.suggestions);

            that.select(i);
        },
        select: function (i) {
            var that = this;

            if(that.options.disableHits){
            	return;
			}

            that.hide();
            that.onSelect(i);
        },
        moveUp: function () {
            var that = this;

            if (that.selectedIndex === -1) {
                return;
            }

			that.latestActivateSource = 'key';

            if (that.selectedIndex === 0) {
                that.getSuggestionsContainer().children('.' + that.classes.suggestion).first().removeClass(that.classes.selected);
                that.selectedIndex = -1;
                that.ignoreValueChange = false;
                that.el.val(that.currentValue);
                that.findBestHint();
                return;
            }

            that.adjustScroll(that.selectedIndex - 1, 'up');
        },
        moveDown: function () {
            var that = this;

            if (that.selectedIndex === (that.suggestions.length - 1)) {
                return;
            }

			that.latestActivateSource = 'key';

            that.adjustScroll(that.selectedIndex + 1, 'down');
        },
        adjustScroll: function (index, direction) {
            var that = this;


            if(that.suggestions[index].type === 'headline'){
            	index = direction === 'down' ? index + 1 : index - 1;
			}

			if(typeof that.suggestions[index] == 'undefined'){
				return;
			}

            var activeItem = that.activate(index);

            that.getDetails(that.suggestions[index]);

            if(that.suggestions[index].type === 'more_products'){

            	return;
			}

            if (!activeItem || that.canShowDetailsBox()) {
                return;
            }

            var offsetTop,
                upperBound,
                lowerBound,
                $suggestionContainer = that.getSuggestionsContainer(),
                heightDelta = $(activeItem).outerHeight();

            offsetTop = activeItem.offsetTop;
            upperBound = $suggestionContainer.scrollTop();
            lowerBound = upperBound + that.options.maxHeight - heightDelta;

            if (offsetTop < upperBound) {
                $suggestionContainer.scrollTop(offsetTop);
            } else if (offsetTop > lowerBound) {
                $suggestionContainer.scrollTop(offsetTop - that.options.maxHeight + heightDelta);
            }

            if (!that.options.preserveInput) {
                // During onBlur event, browser will trigger "change" event,
                // because value has changed, to avoid side effect ignore,
                // that event, so that correct suggestion can be selected
                // when clicking on suggestion with a mouse
                that.ignoreValueChange = true;
                //that.el.val(that.getValue(that.suggestions[index].value));
            }
            that.signalHint(null);
        },
        onSelect: function (index) {
            var that = this,
                onSelectCallback = that.options.onSelect,
                suggestion = that.suggestions[index];

			if (typeof suggestion.type != 'undefined') {
				if (
					suggestion.type === 'more_products'
					|| (that.actionTriggerSource === 'enter' && that.latestActivateSource != 'key' && suggestion.type != 'product_variation')
				) {
					that.el.closest('form').trigger('submit');
					return;
				}
			}

            that.currentValue = that.getValue(suggestion.value);

            if (that.currentValue !== that.el.val() && !that.options.preserveInput) {
                that.el.val(that.currentValue);
            }

            if (suggestion.url.length > 0) {
                window.location.href = suggestion.url;
            }

            that.signalHint(null);
            that.suggestions = [];
            that.selection = suggestion;
            if ($.isFunction(onSelectCallback)) {
                onSelectCallback.call(that.element, suggestion);
            }
        },
        getValue: function (value) {
            var that = this,
                delimiter = that.options.delimiter,
                currentValue,
                parts;

            if (!delimiter) {
                return value;
            }

            currentValue = that.currentValue;
            parts = currentValue.split(delimiter);

            if (parts.length === 1) {
                return value;
            }

            return currentValue.substr(0, currentValue.length - parts[parts.length - 1].length) + value;
        },
        dispose: function () {
            var that = this,
				$el = that.el,
				$formWrapper = that.getFormWrapper(),
				$suggestionsWrapper = that.getSuggestionsContainer(),
				$detailsWrapper = that.getDetailsContainer(),
				$form = $el.closest('.' + that.options.formClass),
				$mobileHandler = $formWrapper.find('.js-dgwt-wcas-enable-mobile-form');

            // Remove all events
            if($formWrapper.length){
            	var $items = $formWrapper.find('*');
				$items.each(function(){
					$(this).off('.autocomplete');
				});
			}

			$el.removeData('autocomplete');
			$(window).off('resize.autocomplete', that.fixPositionCapture);

			$formWrapper.removeClass('dgwt-wcas-active');

			that.close();

			// Remove mobile handler
			if ($mobileHandler.length) {
				$mobileHandler.remove();
			}

			if($suggestionsWrapper.length){
				$suggestionsWrapper.html('');
			}
        },
        enableOverlayMobile: function () {
            var that = this;

            if (that.overlayMobileState === 'on') {
                return;
            }

            that.overlayMobileState = 'on';

            var zIndex = 99999999999,
                $wrapper = that.getFormWrapper(),
                $suggestionsWrapp = that.getSuggestionsContainer(),
                $overlayWrap,
                html = '';

            $('html').addClass('dgwt-wcas-overlay-mobile-on');
            html += '<div class="js-dgwt-wcas-overlay-mobile dgwt-wcas-overlay-mobile">';
            html += '<div class="dgwt-wcas-om-bar js-dgwt-wcas-om-bar">';
            html += '<button class="dgwt-wcas-om-return js-dgwt-wcas-om-return">'
			if (typeof dgwt_wcas.back_icon == 'string') {
				html += dgwt_wcas.back_icon;
			}
            html += '</button>';
            html += '</div>';
            html += '</div>';

            // Create overlay
            $(that.options.mobileOverlayWrapper).append(html);
            $overlayWrap = $('.js-dgwt-wcas-overlay-mobile');
            $overlayWrap.css('zIndex', zIndex);

            $wrapper.after('<span class="js-dgwt-wcas-om-hook"></span>');
            $wrapper.appendTo('.js-dgwt-wcas-om-bar');
            $suggestionsWrapp.appendTo('.js-dgwt-wcas-om-bar');
            $wrapper.addClass('dgwt-wcas-search-wrapp-mobile');

            if ($wrapper.hasClass('dgwt-wcas-has-submit')) {
                $wrapper.addClass('dgwt-wcas-has-submit-off');
                $wrapper.removeClass('dgwt-wcas-has-submit');
            }

            $wrapper.find('.' + that.options.searchInputClass).focus();

            $(document).on('click.autocomplete', '.js-dgwt-wcas-om-return', function (e) {
                that.disableOverlayMobile($overlayWrap);
            });
        },
        disableOverlayMobile: function ($overlayWrap) {
            var that = this;

            var $suggestionsWrapp = that.getSuggestionsContainer();

            var $clonedForm = $('.js-dgwt-wcas-om-bar').find('.' + that.options.searchFormClass);

            if ($clonedForm.hasClass('dgwt-wcas-has-submit-off')) {
                $clonedForm.removeClass('dgwt-wcas-has-submit-off');
                $clonedForm.addClass('dgwt-wcas-has-submit');
            }

            $clonedForm.removeClass('dgwt-wcas-search-wrapp-mobile');
            $('html').removeClass('dgwt-wcas-overlay-mobile-on');
            $suggestionsWrapp.appendTo('body');
            $suggestionsWrapp.removeAttr('body-scroll-lock-ignore');
            $('.js-dgwt-wcas-om-hook').after($clonedForm);
            $('.js-dgwt-wcas-overlay-mobile').remove();
            $('.js-dgwt-wcas-om-hook').remove();

            setTimeout(function () {
                $clonedForm.find('.' + that.options.searchInputClass).val('');
                var $closeBtn = $clonedForm.find('.dgwt-wcas-close');
                if ($clonedForm.length > 0) {
                    $closeBtn.removeClass('dgwt-wcas-close');
					$closeBtn.html('');
                }

				that.hide();

            }, 150);


            that.overlayMobileState = 'off';
        },
		showCloseButton: function(){
			var that = this,
				iconBody = typeof dgwt_wcas.close_icon != 'undefined' ? dgwt_wcas.close_icon : '',
				$actionsEl = that.getFormWrapper().find('.' + that.options.preloaderClass);

			$actionsEl.addClass(that.options.closeTrigger);
			$actionsEl.html(iconBody);

		},
		hideCloseButton: function () {
			var that = this,
				$btn = that.getFormWrapper().find('.' + that.options.closeTrigger);

			if ($btn.length) {
				$btn.removeClass(that.options.closeTrigger);
				$btn.html('');
			}
		},
        elementOrParentIsFixed: function ($element) {

            var $checkElements = $element.add($element.parents());
            var isFixed = false;

            $checkElements.each(function () {

                if ($(this).css("position") === "fixed") {
                    isFixed = true;
                    return false;
                }
            });
            return isFixed;
        },
        gaEvent: function (label, category) {
			var that = this;
			var gaObj = window.hasOwnProperty('GoogleAnalyticsObject') && window.hasOwnProperty(window['GoogleAnalyticsObject']) ? window[window['GoogleAnalyticsObject']] : false;
			if (that.options.sendGAEvents) {
                try {
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'autocomplete_search', {
                            'event_label': label,
                            'event_category': category
                        });
                    } else if (gaObj !== false) {
                    	var tracker = gaObj.getAll()[0];
						if (tracker) tracker.send({
							hitType: 'event',
							eventCategory: category,
							eventAction: 'autocomplete_search',
							eventLabel: label
						});
					}
                } catch (error) {
                }
            }
			if (that.options.enableGASiteSearchModule) {
				try {
					if (typeof gtag !== 'undefined') {
						gtag('event', 'page_view', {
							'page_path': '/?s=' + encodeURI(label) + '&post_type=product&dgwt_wcas=1',
						});
					} else if (gaObj !== false) {
						var tracker2 = gaObj.getAll()[0];
						if (tracker2) {
							tracker2.set('page', '/?s=' + encodeURI(label) + '&post_type=product&dgwt_wcas=1');
							tracker2.send('pageview');
						}
					}
				} catch (error) {
				}
			}
        }
    };


    // Create chainable jQuery plugin:
    $.fn.dgwtWcasAutocomplete = function (options, args) {
        var dataKey = 'autocomplete';
        // If function invoked without argument return
        // instance of the first matched element:
        if (!arguments.length) {
            return this.first().data(dataKey);
        }

        return this.each(function () {
            var inputElement = $(this),
                instance = inputElement.data(dataKey);

            if (typeof options === 'string') {
                if (instance && typeof instance[options] === 'function') {
                    instance[options](args);
                }
            } else {
                // If instance already exists, destroy it:
                if (instance && instance.dispose) {
                    instance.dispose();
                }

                instance = new DgwtWcasAutocompleteSearch(this, options);
                inputElement.data(dataKey, instance);
            }
        });
    };

    // Don't overwrite if it already exists
    if (!$.fn.autocomplete) {
        $.fn.autocomplete = $.fn.dgwtWcasAutocomplete;
    }

    (function () {

		function isIOS() {
			return [
					'iPad Simulator',
					'iPhone Simulator',
					'iPod Simulator',
					'iPad',
					'iPhone',
					'iPod'
				].includes(navigator.platform)
				// iPad on iOS 13 detection
				|| (navigator.userAgent.includes("Mac") && "ontouchend" in document)
		}

		function isIE11() {
			return !!navigator.userAgent.match(/Trident\/7\./);
		}

		/*-----------------------------------------------------------------
		/* IE11 polyfills
		/*-----------------------------------------------------------------*/
		if (isIE11()) {
			// https://polyfill.io/v3/polyfill.min.js?features=Array.prototype.includes%2CString.prototype.includes
			(function(self, undefined) {function Call(t,l){var n=arguments.length>2?arguments[2]:[];if(!1===IsCallable(t))throw new TypeError(Object.prototype.toString.call(t)+"is not a function.");return t.apply(l,n)}function CreateMethodProperty(e,r,t){var a={value:t,writable:!0,enumerable:!1,configurable:!0};Object.defineProperty(e,r,a)}function Get(n,t){return n[t]}function IsCallable(n){return"function"==typeof n}function RequireObjectCoercible(e){if(null===e||e===undefined)throw TypeError(Object.prototype.toString.call(e)+" is not coercible to Object.");return e}function SameValueNonNumber(e,n){return e===n}function ToBoolean(o){return Boolean(o)}function ToObject(e){if(null===e||e===undefined)throw TypeError();return Object(e)}function GetV(t,e){return ToObject(t)[e]}function GetMethod(e,n){var r=GetV(e,n);if(null===r||r===undefined)return undefined;if(!1===IsCallable(r))throw new TypeError("Method not callable: "+n);return r}function Type(e){switch(typeof e){case"undefined":return"undefined";case"boolean":return"boolean";case"number":return"number";case"string":return"string";case"symbol":return"symbol";default:return null===e?"null":"Symbol"in self&&(e instanceof self.Symbol||e.constructor===self.Symbol)?"symbol":"object"}}function IsRegExp(e){if("object"!==Type(e))return!1;var n="Symbol"in self&&"match"in self.Symbol?Get(e,self.Symbol.match):undefined;if(n!==undefined)return ToBoolean(n);try{var t=e.lastIndex;return e.lastIndex=0,RegExp.prototype.exec.call(e),!0}catch(l){}finally{e.lastIndex=t}return!1}function OrdinaryToPrimitive(r,t){if("string"===t)var e=["toString","valueOf"];else e=["valueOf","toString"];for(var i=0;i<e.length;++i){var n=e[i],a=Get(r,n);if(IsCallable(a)){var o=Call(a,r);if("object"!==Type(o))return o}}throw new TypeError("Cannot convert to primitive.")}function SameValueZero(n,e){return Type(n)===Type(e)&&("number"===Type(n)?!(!isNaN(n)||!isNaN(e))||(1/n===Infinity&&1/e==-Infinity||(1/n==-Infinity&&1/e===Infinity||n===e)):SameValueNonNumber(n,e))}function ToInteger(n){if("symbol"===Type(n))throw new TypeError("Cannot convert a Symbol value to a number");var t=Number(n);return isNaN(t)?0:1/t===Infinity||1/t==-Infinity||t===Infinity||t===-Infinity?t:(t<0?-1:1)*Math.floor(Math.abs(t))}function ToLength(n){var t=ToInteger(n);return t<=0?0:Math.min(t,Math.pow(2,53)-1)}function ToPrimitive(e){var t=arguments.length>1?arguments[1]:undefined;if("object"===Type(e)){if(arguments.length<2)var i="default";else t===String?i="string":t===Number&&(i="number");var r="function"==typeof self.Symbol&&"symbol"==typeof self.Symbol.toPrimitive?GetMethod(e,self.Symbol.toPrimitive):undefined;if(r!==undefined){var n=Call(r,e,[i]);if("object"!==Type(n))return n;throw new TypeError("Cannot convert exotic object to primitive.")}return"default"===i&&(i="number"),OrdinaryToPrimitive(e,i)}return e}function ToString(t){switch(Type(t)){case"symbol":throw new TypeError("Cannot convert a Symbol value to a string");case"object":return ToString(ToPrimitive(t,String));default:return String(t)}}CreateMethodProperty(Array.prototype,"includes",function e(r){"use strict";var t=ToObject(this),o=ToLength(Get(t,"length"));if(0===o)return!1;var n=ToInteger(arguments[1]);if(n>=0)var a=n;else(a=o+n)<0&&(a=0);for(;a<o;){var i=Get(t,ToString(a));if(SameValueZero(r,i))return!0;a+=1}return!1});CreateMethodProperty(String.prototype,"includes",function e(t){"use strict";var r=arguments.length>1?arguments[1]:undefined,n=RequireObjectCoercible(this),i=ToString(n);if(IsRegExp(t))throw new TypeError("First argument to String.prototype.includes must not be a regular expression");var o=ToString(t),g=ToInteger(r),a=i.length,p=Math.min(Math.max(g,0),a);return-1!==String.prototype.indexOf.call(i,o,p)});})('object' === typeof window && window || 'object' === typeof self && self || 'object' === typeof global && global || {});
		}

        // RUN
        $(document).ready(function () {
            "use strict";

			/*-----------------------------------------------------------------
			/* Mobile detection
			/*-----------------------------------------------------------------*/
			if(isIOS()){
				$('html').addClass('dgwt-wcas-is-ios');
			}

            /*-----------------------------------------------------------------
            /* Fire autocomplete
            /*-----------------------------------------------------------------*/
            var showDetailsPanel = dgwt_wcas.show_details_box == 1 ? true : false;
            var mobileBreakpoint = dgwt_wcas.mobile_breakpoint;

            // Disable details panel on small screens
            if (jQuery(window).width() < mobileBreakpoint || ('ontouchend' in document)) {
                showDetailsPanel = false;
            }

            window.dgwt_wcas.config = {
                minChars: dgwt_wcas.min_chars,
                width: dgwt_wcas.sug_width,
                autoSelectFirst: false,
                triggerSelectOnValidInput: false,
                serviceUrl: dgwt_wcas.ajax_search_endpoint,
                paramName: 's',
                showDetailsPanel: showDetailsPanel,
                showImage: dgwt_wcas.show_images == 1 ? true : false,
                showPrice: dgwt_wcas.show_price == 1 ? true : false,
                showDescription: dgwt_wcas.show_desc == 1 ? true : false,
                showSKU: dgwt_wcas.show_sku == 1 ? true : false,
                showSaleBadge: dgwt_wcas.show_sale_badge == 1 ? true : false,
                showFeaturedBadge: dgwt_wcas.show_featured_badge == 1 ? true : false,
                dynamicPrices: typeof dgwt_wcas.dynamic_prices != 'undefined' && dgwt_wcas.dynamic_prices ? true : false,
                saleBadgeText: dgwt_wcas.labels.sale_badge,
                featuredBadgeText: dgwt_wcas.labels.featured_badge,
                isRtl: dgwt_wcas.is_rtl == 1 ? true : false,
                showHeadings: dgwt_wcas.show_headings == 1 ? true : false,
                isPremium: dgwt_wcas.is_premium == 1 ? true : false,
                taxonomyBrands: dgwt_wcas.taxonomy_brands,
                mobileBreakpoint: mobileBreakpoint,
                mobileOverlayWrapper: dgwt_wcas.mobile_overlay_wrapper,
                debounceWaitMs: dgwt_wcas.debounce_wait_ms,
                sendGAEvents: dgwt_wcas.send_ga_events,
                convertHtml: dgwt_wcas.convert_html,
				enableGASiteSearchModule: dgwt_wcas.enable_ga_site_search_module,
				appendTo: typeof dgwt_wcas.suggestions_wrapper  != 'undefined' ? dgwt_wcas.suggestions_wrapper : 'body',
				showProductVendor: typeof dgwt_wcas.show_product_vendor != 'undefined' && dgwt_wcas.show_product_vendor ? true : false,
				disableHits: typeof dgwt_wcas.disable_hits != 'undefined' && dgwt_wcas.disable_hits ? true : false,
				disableSubmit: typeof dgwt_wcas.disable_submit != 'undefined' && dgwt_wcas.disable_submit ? true : false
            };

            $('.dgwt-wcas-search-input').dgwtWcasAutocomplete(window.dgwt_wcas.config);

        });

		/*-----------------------------------------------------------------
        /* Fix broken search bars after click browser's back arrow.
        /* Not worked for some browsers especially Safari and FF
        /* Add dgwt-wcas-active class if wasn't added for some reason
		/*
        /*------------ -----------------------------------------------------*/
		$(window).on('load', function () {
			var i = 0;
			var interval = setInterval(function () {

				var activeEl = document.activeElement;

				if (
					typeof activeEl == 'object'
					&& $(activeEl).length
					&& $(activeEl).hasClass('dgwt-wcas-search-input')
				) {

					var $search = $(activeEl).closest('.dgwt-wcas-search-wrapp');

					if ($search.length && !$search.hasClass('dgwt-wcas-active')) {
						$search.addClass('dgwt-wcas-active');
						clearInterval(interval);
					}
				}

				// Stop after 5 seconds
				if (i > 10) {
					clearInterval(interval);
				}

				i++;

			}, 500);
		});

        /*-----------------------------------------------------------------
        /* Fix broken search bars by 3rd party plugins
        /* Some page builders can copy instances of search bar without events
        /* for eg. mobile usage. We try to fix all broken search bars.
        /*------------ -----------------------------------------------------*/
        $(document).ready(function () {
            setTimeout(function() {
                makeIDUnique();
                maybeReinit();
            }, 500);
        });

        $(window).on('load', function () {
            setTimeout(function() {
                makeIDUnique();
                maybeReinit();
            }, 500);

            // Support for Elementor popups
            if(
                typeof window.elementorFrontend != 'undefined'
                && typeof window.elementorFrontend.documentsManager != 'undefined'
                && typeof window.elementorFrontend.documentsManager.documents != 'undefined'
            ){

                $.each(elementorFrontend.documentsManager.documents, function (id, document) {
                    if (typeof document.getModal != 'undefined' && document.getModal) {

                        document.getModal().on('show', function () {

                            setTimeout(function () {
                                maybeReinit();
                            }, 300);

                        });

                    }
                });

            }
        });

        /**
         * Fix duplicated ID created by some themes or builders
         */
        function makeIDUnique() {

            var $inputs = $('.dgwt-wcas-search-input');

            var uniqueID = [];

            if ($inputs.length > 1) {
                $inputs.each(function () {
                    var id = $(this).attr('id');

                    if ($.inArray(id, uniqueID) == -1) {
                        uniqueID.push(id);

                    } else {
                        var newID = Math.random().toString(36).substring(2, 6);
                        newID = 'dgwt-wcas-search-input-' + newID;

                        $(this).attr('id', newID);
                        $(this).closest('form').find('label').attr('for', newID);

                    }
                });
            }

        }
        /**
         * Init not initialized search bar
         */
        function maybeReinit() {

            var $inputs = $('.dgwt-wcas-search-input');

            if ($inputs.length > 0) {
                $inputs.each(function () {

                    if (typeof $(this).data('autocomplete') != 'object') {
                        $(this).dgwtWcasAutocomplete(window.dgwt_wcas.config);
                    }

                });
            }

        }

    }());

}));
