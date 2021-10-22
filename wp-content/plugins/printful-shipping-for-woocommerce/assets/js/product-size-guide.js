/** Define class */
var Printful_Product_Size_Guide;

(function () {
    'use strict';

    /**
     * @type {{modal: null, onSizeGuideClick: onSizeGuideClick, closeModal: closeModal, createModal: (function(string): HTMLDivElement)}}
     */
    Printful_Product_Size_Guide = {
        modal: null,
        modalContentNode: null,
        modalBodyNode: null,
        sizeChartNode: null,
        /**
         * @var {{}}
         * @var {Array<string>} sizeGuideData.availableSizes
         * @var {{}} sizeGuideData.modelMeasurements
         * @var {{}} sizeGuideData.productMeasurements
         */
        sizeGuideData: null,

        /**
         * Key is unit identifier (e.g. 'inch') value is translated unit name (e.g. 'Inches')
         * @var {{}}
         */
        translatedUnitNames: null,

        /**
         * Handle click event
         */
        onSizeGuideClick: function () {
            if (!window.pfGlobal || !window.pfGlobal.sg_data_raw) {
                return;
            }
            this.sizeGuideData = JSON.parse(window.pfGlobal.sg_data_raw);

            this.translatedUnitNames = {};
            if (window.pfGlobal && window.pfGlobal.sg_unit_translations) {
                this.translatedUnitNames = JSON.parse(window.pfGlobal.sg_unit_translations);
            }
            document.body.appendChild(this.createModal());
        },

        /**
         * Close the modal element
         */
        closeModal: function () {
            this.removeNode(this.sizeChartNode);
            this.removeNode(this.modalContentNode);
            this.removeNode(this.modalBodyNode);
            this.removeNode(this.modal);
            this.sizeChartNode = null;
            this.modalContentNode = null;
            this.modalBodyNode = null;
            this.modal = null;
        },

        /**
         * Create modal content
         * @returns {HTMLDivElement}
         */
        createModal: function () {
            // Clear the old one just to be sure
            this.closeModal();
            this.modal = this.buildEl('div', 'pf-size-guide-modal-wrapper');
            this.modalBodyNode = this.buildEl('div', 'pf-size-guide-modal');
            this.modalBodyNode.style.color = this.getTextColor();
            this.modalBodyNode.style.backgroundColor = this.getBackGroundColor();
            this.modalBodyNode.appendChild(this.buildModalHeader());

            // Render initial active tab
            this.renderModalContent(Printful_Product_Size_Guide.TAB_TYPE_PERSON);
            this.modal.appendChild(this.modalBodyNode);

            return this.modal;
        },

        buildEl: function (tagName, className, attributes) {
            attributes = attributes || {};
            var el = document.createElement(tagName);
            if (className) {
                el.className = className;
            }

            if (attributes.id) {
                el.setAttribute('id', attributes.id);
            }

            if (attributes.innerHTML) {
                el.innerHTML = attributes.innerHTML;
            }

            if (attributes.src) {
                el.setAttribute('src', attributes.src);
            }

            if (attributes.onclick) {
                el.onclick = attributes.onclick;
            }

            if (['td', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p'].indexOf(tagName) > -1) {
                el.style.backgroundColor = this.getBackGroundColor();
                el.style.color = this.getTextColor();
            }

            return el;
        },

        removeNode: function (node) {
            if (node && node.parentNode && node.parentNode.tagName) {
                node.parentNode.removeChild(node);
            }
        },

        renderModalContent: function (activeTabType) {
            this.removeNode(this.modalContentNode);
            this.modalContentNode = this.buildEl('div', 'pf-size-guide-modal__content');

            var tabData = this.getSizeGuideDataForTab(activeTabType),
                tabTitle = this.getTabTitle(activeTabType);

            this.modalContentNode.appendChild(this.buildSizeGuideTabsNode(activeTabType));
            this.modalContentNode.appendChild(this.buildTabContent(tabData, tabTitle));

            this.modalBodyNode.appendChild(this.modalContentNode);
        },

        getSizeGuideDataForTab: function (tabType) {
            if (tabType === Printful_Product_Size_Guide.TAB_TYPE_PERSON) {
                return this.sizeGuideData.modelMeasurements;
            }

            if (tabType === Printful_Product_Size_Guide.TAB_TYPE_PRODUCT) {
                return this.sizeGuideData.productMeasurements;
            }

            return {};
        },

        getTabTitle: function (tabType) {
            if (tabType === Printful_Product_Size_Guide.TAB_TYPE_PERSON) {
                return window.pfGlobal && window.pfGlobal.sg_tab_title_person ? window.pfGlobal.sg_tab_title_person : 'Measure yourself';
            }

            return window.pfGlobal && window.pfGlobal.sg_tab_title_product ? window.pfGlobal.sg_tab_title_product : 'Product measurements';
        },

        renderSizeChart: function (measurementData, selectedUnit) {
            if (this.sizeChartNode) {
                this.removeNode(this.sizeChartNode.firstChild);
            } else {
                this.sizeChartNode = this.buildEl('div', 'pf-size-guide-modal-size-chart');
            }

            this.sizeChartNode.appendChild(this.buildSizeChartBlock(measurementData, selectedUnit));
        },

        buildModalHeader: function () {
            var title = window.pfGlobal && window.pfGlobal.sg_modal_title ? window.pfGlobal.sg_modal_title : 'Size guide',
                wrapper = this.buildEl('div', 'pf-size-guide-modal__header');

            wrapper.appendChild(this.buildEl('h4', 'pf-size-guide-modal__title', {innerHTML: title}));

            var closeBtn = this.buildEl('button', 'pf-size-guide-modal__close'),
                img = this.buildEl('img');

            img.src = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADwAAAA8CAYAAAA6/NlyAAAAAXNSR0IArs4c6QAAAgVJREFUaAXtmU2KwkAQhZ0hQbyDu7mIB3PhwoN5EXfeIYguJo9JQRMydurnNYxTDTH+dNWr71VD2mSzyZEOpAPpQDqQDqQD6UA6kA6kA80dOJ1O++Px+NVKGFrQ9Oh9WoMhPAzD5fF4XFpAQwNa0PRAf1iABXaMle7e+r4/nM/nqyVfLUZgx3nS3etutzuMddxqsfPf1cALsJKTAr0AK3omaPWSfj6f21ERx3zso5f3C1hob6da5nW8/KzuMLJVCgnpNEvDBMyGZsGibjMwC5oJ6waOhmbDhgBHQbeADQP2QreCDQW2QreEDQfWQreGpQCvhcY8bFTGk2wX8ZWMkGu5JCvPrstSmWj+vta9aX5TWGjSgJG8Ao0p80HrrAhRgSGigKbDoh468EroJrCoRf1vCUF/edA7/K+WtAJWFg19adM6XIGVWzPvcVmqweL+F1r6FhuPNbBys08zV9a89xy6pC0AlhgPdBiwp3BPrBY+BDii4Igca+DdwJGFRub6Dd4FzCiQkbOENwMzC2PmNgEzC5JusDTUwKxCBLQ8M7TU/5a6rruPReGYj/B9MDYo065MtqKl5n2qpfyu+l7dYWRceIIYDltWvtBp05ND5DQBI7CA3jKfDUMLo4C+W58N/2RyvAIahThSqEKhBU1VUE5OB9KBdCAdSAfSgXQgHUgH0oEQB74BG1sUIwNoL3cAAAAASUVORK5CYII=";
            closeBtn.appendChild(img);
            closeBtn.onclick = (this.closeModal).bind(this);

            wrapper.appendChild(closeBtn);
            wrapper.append(this.buildEl('div', 'pf-size-guide-modal-clear'));

            return wrapper;
        },

        buildSizeGuideTabsNode: function (activeTabType) {
            var tabs = this.buildEl('ul', 'pf-product-size-guide__tabs');
            tabs.appendChild(this.buildSizeGuideTabNode(this.getTabTitle(Printful_Product_Size_Guide.TAB_TYPE_PERSON), Printful_Product_Size_Guide.TAB_TYPE_PERSON, activeTabType));
            tabs.appendChild(this.buildSizeGuideTabNode(this.getTabTitle(Printful_Product_Size_Guide.TAB_TYPE_PRODUCT), Printful_Product_Size_Guide.TAB_TYPE_PRODUCT, activeTabType));

            return tabs;
        },

        buildSizeGuideTabNode: function (title, type, activeType) {
            var className = 'pf-product-size-guide__tab';

            if (type === activeType) {
                className += ' pf-product-size-guide__tab--active';
            }

            var tab = this.buildEl('li', className, {
                innerHTML: title,
                onclick: (this.renderModalContent).bind(this, type)
            });

            if (type === activeType) {
                tab.style.backgroundColor = this.getTabBackGroundColor(true);
            } else {
                tab.style.backgroundColor = this.getTabBackGroundColor(false);
            }

            return tab;
        },

        buildTabContent: function (measurementData, title) {
            measurementData = measurementData || {};
            var node = this.buildEl('div');
            node.appendChild(this.buildEl('h4', null, {innerHTML: title}));
            if (measurementData.hasOwnProperty('description')) {
                node.appendChild(this.buildEl('div', null, {innerHTML: measurementData.description}));
            }

            // Model description
            node.appendChild(this.buildDescriptionBlock(measurementData));

            // Size table
            this.renderSizeChart(measurementData);
            node.appendChild(this.sizeChartNode);

            return node;
        },

        buildDescriptionBlock: function (measurementData) {
            var node = this.buildEl('div', 'pf-size-guide-modal-measurements');
            if (measurementData.hasOwnProperty('imageUrl') && measurementData.imageUrl) {
                var measurementImgNode = this.buildEl('div', 'pf-size-guide-modal-measurements__image');
                measurementImgNode.appendChild(this.buildEl('img', null, {src: measurementData.imageUrl}));

                if (measurementData.hasOwnProperty('modelDescription')) {
                    measurementImgNode.appendChild(this.buildEl('div', null, {innerHTML: measurementData.modelDescription}));
                }
                node.appendChild(measurementImgNode);
            }

            if (measurementData.hasOwnProperty('imageDescription')) {
                node.appendChild(this.buildEl('div', 'pf-size-guide-modal-measurements__description', {innerHTML: measurementData.imageDescription}));
            }
            node.append(this.buildEl('div', 'pf-size-guide-modal-clear'));

            return node;
        },

        /**
         * @param {{}} measurementData
         * @param {string} [selectedUnit]
         * @return {*}
         */
        buildSizeChartBlock: function (measurementData, selectedUnit) {
            var node = this.buildEl('div');

            if (!measurementData.hasOwnProperty('sizeTableRows') || measurementData.sizeTableRows.length < 1) {
                return node;
            }
            var sizeRows = measurementData.sizeTableRows,
                availableUnits = this.getUniqueUnits(sizeRows);

            // Selected or first if nothing selected
            selectedUnit = selectedUnit || this.getDefaultUnit(availableUnits);
            node.appendChild(this.buildSizeChartTabsNode(measurementData, availableUnits, selectedUnit));
            node.appendChild(this.buildSizeChartTable(this.getSortedChartRows(sizeRows, selectedUnit)));

            return node;
        },

        /**
         * @param {{}} measurementData
         * @param {Array<{key, title}>} availableUnits
         * @param {string} selectedUnit
         * @return {*}
         */
        buildSizeChartTabsNode: function (measurementData, availableUnits, selectedUnit) {
            availableUnits = availableUnits || [];
            var tabsNode = this.buildEl('ul', 'pf-size-guide-modal-size-chart__tabs');

            availableUnits.map((function (item) {
                var className = 'pf-size-guide-modal-size-chart__tab';
                if (item.key === selectedUnit) {
                    className += ' pf-size-guide-modal-size-chart__tab--active';
                }
                tabsNode.appendChild(this.buildEl('li', className, {
                    innerHTML: item.title,
                    onclick: (this.renderSizeChart).bind(this, measurementData, item.key)
                }));
            }).bind(this));

            return tabsNode;
        },

        buildSizeChartTable: function (rows) {
            var tableNode = this.buildEl('table', 'pf-size-guide-modal-size-chart__table'),
                availableSizes = this.sizeGuideData.availableSizes;

            var tableHeader = this.buildEl('thead'),
                tableHeaderRow = this.buildEl('tr');

            var tableHeaderRowText = window.pfGlobal && window.pfGlobal.sg_table_header_size ? window.pfGlobal.sg_table_header_size : 'Size';

            tableHeaderRow.appendChild(this.buildEl('td', null, {innerHTML: tableHeaderRowText}));
            rows.map((function (row) {
                tableHeaderRow.appendChild(this.buildEl('td', null, {innerHTML: row.title}));
            }).bind(this));

            tableHeader.appendChild(tableHeaderRow);
            tableNode.appendChild(tableHeader);

            // Loop all available sizes and print out a row for each type
            var tableBody = this.buildEl('tbody');
            availableSizes.map((function (size) {
                var tableBodyRow = this.buildEl('tr');
                tableBodyRow.appendChild(this.buildEl('td', null, {innerHTML: size}));
                rows.map((function (row) {
                    tableBodyRow.appendChild(this.buildEl('td', null, {innerHTML: row.sizes[size] || ''}));
                }).bind(this));

                tableBody.appendChild(tableBodyRow);
            }).bind(this));

            tableNode.appendChild(tableBody);

            return tableNode;
        },

        getSortedChartRows: function (sizeTableRows, selectedUnit) {
            return sizeTableRows
                .filter(function (row) {
                    return row.unit === selectedUnit;
                }).map((function (row) {
                    var sizes = {};
                    for (var k in row.sizes) {
                        if (!row.sizes.hasOwnProperty(k)) {
                            continue;
                        }

                        // Convert to fractions if needed, round and join
                        sizes[k] = row.sizes[k].map((function (size) {
                            if (row.unit === Printful_Product_Size_Guide.UNIT_INCH) {
                                return this.convertToFraction(size);
                            } else {
                                return size.toFixed(1);
                            }
                        }).bind(this)).join(' - ');
                    }

                    return {
                        title: row.title,
                        sizes: sizes
                    };
                }).bind(this));
        },

        convertToFraction: function (value) {
            var split = String(value).split('.'),
                integer = parseInt(split[0], 10);

            if (!split[1]) {
                return value;
            }

            var decimal = parseFloat('0.' + split[1]),
                fraction = 0;

            for (var fra in Printful_Product_Size_Guide.FRACTION_MAP) {
                var compareValue = Printful_Product_Size_Guide.FRACTION_MAP[fra];

                if (decimal < compareValue) {
                    fraction = fra;
                    break;
                }
            }

            if (fraction === 0 && integer) {
                integer++;
                fraction = '';
            }

            return (integer > 0 ? integer + ' ' : '') + fraction;
        },

        /**
         * @param {Array<{unit, unitName}>}sizeTableRows
         * @return {Array<{key, title}>}
         */
        getUniqueUnits: function (sizeTableRows) {
            var uniqueUnits = {};
            sizeTableRows.map(function (row) {
                // Gather unique units
                if (!uniqueUnits.hasOwnProperty(row.unit)) {
                    uniqueUnits[row.unit] = row.unitName;
                }
            });

            var units = [];
            for (var i in uniqueUnits) {
                if (uniqueUnits.hasOwnProperty(i)) {
                    units.push({
                        key: i,
                        title: this.getTranslatedUnitName(i, uniqueUnits[i])
                    });
                }
            }

            return units;
        },

        /**
         * @param {string} unit
         * @param {string} defaultValue
         * @return {null|string}
         */
        getTranslatedUnitName: function(unit, defaultValue) {
            if (this.translatedUnitNames[unit]) {
                return this.translatedUnitNames[unit];
            }

            return defaultValue;
        },

        getBackGroundColor: function () {
            return window.pfGlobal && window.pfGlobal.sg_modal_background_color ? window.pfGlobal.sg_modal_background_color : '#FFF';
        },

        getTextColor: function () {
            return window.pfGlobal && window.pfGlobal.sg_modal_text_color ? window.pfGlobal.sg_modal_text_color : '#000';
        },

        getTabBackGroundColor: function (isActive) {
            if (isActive) {
                return window.pfGlobal && window.pfGlobal.sg_active_tab_background_color ? window.pfGlobal.sg_active_tab_background_color : '#FFF';
            }

            return window.pfGlobal && window.pfGlobal.sg_tab_background_color ? window.pfGlobal.sg_tab_background_color : '#EEE';
        },

        getDefaultUnit: function (availableUnits) {
            availableUnits = availableUnits || [];
            var uniqueUnitTypes = availableUnits.map(function (item) {
                return item.key;
            });

            var defaultUnit = uniqueUnitTypes[0];
            if (window.pfGlobal && window.pfGlobal.sg_primary_unit && uniqueUnitTypes.indexOf(window.pfGlobal.sg_primary_unit) > -1) {
                return window.pfGlobal.sg_primary_unit;
            }

            return defaultUnit;
        }
    };

    Printful_Product_Size_Guide.TAB_TYPE_PERSON = 'person';
    Printful_Product_Size_Guide.TAB_TYPE_PRODUCT = 'product';
    Printful_Product_Size_Guide.UNIT_INCH = 'inch';

    Printful_Product_Size_Guide.FRACTION_MAP = {
        ' ': 0.0625, //don't add any fraction
        '⅛': 0.187,
        '¼': 0.3125,
        '⅜': 0.4375,
        '½': 0.5625,
        '⅝': 0.6875,
        '¾': 0.8125,
        '⅞': 0.9375
    };
})();