define(['jquery', 'mage/translate'], function ($) {
    "use strict";

    // widget element
    const downloadWidgetElement = document.querySelector('.download-widget');

    // error message element
    const errorMessageElement = downloadWidgetElement.querySelector('.error-message');

    // download selection button
    const downloadSelectionButton = downloadWidgetElement.querySelector('#download-widget-download-selection');

    // select buttons
    const selectButton = downloadWidgetElement.querySelector('#download-widget-select');
    const selectDropdown = downloadWidgetElement.querySelector('.dropdown-buttons');
    const selectDropdownButtons = Array.prototype.slice.call(downloadWidgetElement.querySelectorAll('button[data-file]'));

    // collapsable elements
    const category1Elements = Array.prototype.slice.call(downloadWidgetElement.querySelectorAll('.category1'));

    /**
     * Show error message to the user
     * @param {string} message
     */
    let timeOut = null;

    function showError(message) {
        if (timeOut !== null) {
            clearTimeout(timeOut);
            errorMessageElement.style.display = 'none';
        }
        setTimeout(function () {
            errorMessageElement.innerHTML = $.mage.__(message);
            errorMessageElement.style.display = 'block';
        }, 0);
        timeOut = setTimeout(function () {
            errorMessageElement.style.display = 'none';
            timeOut = null;
        }, 5000);
    }

    /**
     * Set both mousedown listener
     * @param {HTMLElement} el
     * @param {function} fn
     * @return void
     */
    function setListeners(el, fn) {
        el.addEventListener('mousedown', fn);
    }

    function toggleButton(button, onOrOff) {
        if (onOrOff) {
            button.classList.remove('loading');
            button.disabled = false;
            return;
        }
        button.classList.add('loading');
        button.disabled = true;
    }

    /**
     * Download all checkboxes provided and disable/enable button
     * @param {HTMLElement[]} checkboxes
     * @param {HTMLElement} button
     * @return {void}
     */
    function download(checkboxes, button) {
        const json = {};
        checkboxes.forEach(function (checkbox) {
            const assetReference = checkbox.getAttribute('data-asset-reference');
            const file = checkbox.getAttribute('data-asset-file');
            if (!json[assetReference]) {
                json[assetReference] = [];
            }
            json[assetReference].push(file);
        });

        const endpoint = button.getAttribute('data-endpoint');
        toggleButton(button, false);
        $.ajax({
            type: "POST",
            url: endpoint,
            contentType: 'application/json',
            data: JSON.stringify(json)
        }).success(function (result) {
            if (!result || !result.token) {
                showError('Unknown error while downloading the files');
            }
            toggleButton(button, true);

            window.location = endpoint + 'token/' + result.token;
        }).fail(function (xhr) {
            const result = JSON.parse(xhr.responseText);
            if (!result || !result.error) {
                showError('Unknown error while downloading the files');
            }
            if (!result.token) {
                showError(result.error);
            }
            toggleButton(button, true);
        });
    }

    /**
     * Download selected files from the assets
     * @return {void}
     */
    function downloadSelection() {
        const checkboxes = Array.prototype.slice.call(downloadWidgetElement.querySelectorAll('input[data-asset-file]:checked'));
        if (!checkboxes.length) {
            showError('Nothing selected');
            return;
        }
        download(checkboxes, downloadSelectionButton);
    }

    /**
     * Toggle or set a className on an element,
     * check correct element when triggered by an Event
     * @param {HTMLElement} el
     * @param {?boolean} val
     * @param {string} className
     * @param {?Event} e
     * @param {?HTMLElement} eventEl
     * @returns {*}
     */
    function toggleClassName(el, val, className, e, eventEl) {
        if (e && eventEl && e.currentTarget !== eventEl) {
            return;
        }
        if (val) {
            return el.classList.remove(className);
        }
        if (val === false) {
            return el.classList.add(className);
        }
        return el.classList.toggle(className);
    }

    /**
     * Toggle className 'hidden'
     * @param {HTMLElement} el
     * @param {?boolean} val
     * @param {?Event} e
     * @param {?HTMLElement} eventEl
     * @return {void}
     */
    function toggleHidden(el, val, e, eventEl) {
        toggleClassName(el, val, 'hidden', e, eventEl);
    }

    /**
     * Toggle className 'active'
     * @param {HTMLElement} el
     * @param {?boolean} val
     * @param {?Event} e
     * @param {?HTMLElement} eventEl
     * @return {void}
     */
    function toggleActive(el, val, e, eventEl) {
        toggleClassName(el, val, 'active', e, eventEl);
    }

    /**
     * Get checkboxes
     * @param {string} file
     * @returns {HTMLElement[]}
     */
    function getCheckboxes(file) {
        if (file === 'none') {
            return Array.prototype.slice.call(downloadWidgetElement.querySelectorAll('input[data-asset-file]'));
        }
        return Array.prototype.slice.call(downloadWidgetElement.querySelectorAll('input[data-asset-file=' + file + ']'));
    }

    /**
     * Select/deselect checkboxes
     * @param {Event} e
     * @return {void}
     */
    function selectFileXCheckboxes(e) {
        if (selectDropdownButtons.indexOf(e.currentTarget) === -1) {
            return;
        }
        const file = e.currentTarget.getAttribute('data-file');
        const checkboxes = getCheckboxes(file);
        checkboxes.forEach(function (checkbox) {
            checkbox.checked = file !== 'none';
        });
    }

    function lazyLoadImagesForCategory(el) {
        [].forEach.call(el.querySelectorAll('img.lazy[data-src]'), function(img) {
            img.setAttribute('src', img.getAttribute('data-src'));
            img.onload = function() {
                img.removeAttribute('data-src');
            };
        });
    }

    /**
     * Show/hide a root category
     * @param {Event} e
     * @return {void}
     */
    function showHideCategory1(e) {
        if (category1Elements.indexOf(e.currentTarget) === -1) {
            return;
        }
        const category1 = e.currentTarget.getAttribute('data-category1');
        toggleActive(e.currentTarget);
        const elementsToShow = Array.prototype.slice.call(downloadWidgetElement.querySelectorAll('.asset-wrapper[data-category1="' + category1 + '"], .category2[data-category1="' + category1 + '"]'));
        const val = e.currentTarget.classList.contains('active');
        elementsToShow.forEach(function (elementToShow) {
            lazyLoadImagesForCategory(elementToShow);
            toggleHidden(elementToShow, val);
        });
    }

    // download selection listeners
    setListeners(downloadSelectionButton, downloadSelection);

    // select listeners
    setListeners(selectButton, function (e) {
        toggleHidden(selectDropdown, null, e, selectButton);
    });
    selectDropdown.addEventListener('mouseleave', function () {
        toggleHidden(selectDropdown, false);
    });
    selectDropdownButtons.forEach(function (selectDropdownButton) {
        setListeners(selectDropdownButton, selectFileXCheckboxes);
    });

    // collapse listeners
    category1Elements.forEach(function (category1Element) {
        setListeners(category1Element, showHideCategory1);
    });
});
