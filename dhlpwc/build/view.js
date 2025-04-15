/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./src/delivery-times.js":
/*!*******************************!*\
  !*** ./src/delivery-times.js ***!
  \*******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   DeliveryTimes: () => (/* binding */ DeliveryTimes)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/components */ "@wordpress/components");
/* harmony import */ var _wordpress_components__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__);




const DeliveryTimes = props => {
  if (!window.dhlpwc_block_data.deliverytimes_enabled) {
    return '';
  }
  const deliveryTimeShippingMethods = ['dhlpwc-home', 'dhlpwc-home-evening', 'dhlpwc-home-next-day', 'dhlpwc-home-no-neighbour', 'dhlpwc-home-no-neighbour-evening', 'dhlpwc-home-no-neighbour-next-day'];
  const [deliveryTimes, setDeliveryTimes] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)({});
  const [selectedTime, setSelectedTime] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)([]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    const deliveryTimeDetails = document.getElementById('dhlpwc-shipping-method-delivery-times-option');
    if (deliveryTimeDetails === null) {
      return;
    }
    if (deliveryTimeShippingMethods.indexOf(props.selectedShippingMethod) !== -1) {
      deliveryTimeDetails.style.display = "block";
      // Wordpress doesn't like it when we post raw data, so we post it as a form instead
      const formData = new FormData();
      formData.append('action', 'dhlpwc_get_delivery_times');
      formData.append('shipping_method', props.selectedShippingMethod);
      formData.append('postal_code', props.postalCode);
      formData.append('country_code', props.countryCode);
      fetch(window.dhlpwc_block_data['ajax_url'], {
        method: 'POST',
        body: formData
      }).then(async response => {
        response = await response.json();
        let parsedDeliveryTimes = [];
        Object.values(response.data).forEach(value => {
          const parsedDeliveryTime = {
            label: `${value.date} (${value.start_time} - ${value.end_time})`,
            value: value.identifier
          };
          parsedDeliveryTimes.push(parsedDeliveryTime);
        });
        setDeliveryTimes(parsedDeliveryTimes);
        // initially set the first selection
        if (typeof parsedDeliveryTimes[0] !== "undefined") setSelectedTime(parsedDeliveryTimes[0].value.split('___'));
      });
    } else {
      deliveryTimeDetails.style.display = "none";
      setSelectedTime([]);
    }
  }, [props.postalCode, props.countryCode, props.selectedShippingMethod]);
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    var _selectedTime$, _selectedTime$2, _selectedTime$3;
    // Wordpress doesn't like it when we post raw data, so we post it as a form instead
    const formData = new FormData();
    formData.append('action', 'dhlpwc_delivery_time_selection_sync');
    formData.append('selected', true);
    formData.append('date', (_selectedTime$ = selectedTime[0]) !== null && _selectedTime$ !== void 0 ? _selectedTime$ : '');
    formData.append('start_time', (_selectedTime$2 = selectedTime[1]) !== null && _selectedTime$2 !== void 0 ? _selectedTime$2 : '');
    formData.append('end_time', (_selectedTime$3 = selectedTime[2]) !== null && _selectedTime$3 !== void 0 ? _selectedTime$3 : '');
    fetch(window.dhlpwc_block_data['ajax_url'], {
      method: 'POST',
      body: formData
    });
  }, [selectedTime]);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
    id: "dhlpwc-shipping-method-delivery-times-option",
    className: "dhlpwc-shipping-method-delivery-times-option",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("div", {
      className: "dhlpwc-delivery-times-selection-header",
      children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('DHL Delivery Times options')
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
      className: "dhlpwc-delivery-times-selection-text",
      children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Desired delivery moment:')
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)(_wordpress_components__WEBPACK_IMPORTED_MODULE_2__.SelectControl, {
      hideLabelFromVision: "true",
      className: "dhlpwc-delivery-times-selection-input",
      label: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Select desired delivery moment'),
      value: selectedTime.join('___'),
      options: deliveryTimes,
      onChange: deliveryTime => setSelectedTime(deliveryTime.split('___'))
    })]
  });
};

/***/ }),

/***/ "./src/same-day.js":
/*!*************************!*\
  !*** ./src/same-day.js ***!
  \*************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   SameDay: () => (/* binding */ SameDay)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);

const SameDay = props => {
  if (window.dhlpwc_block_data.sdd_as_time_window) {
    const sameDayElement = document.getElementById('radio-control-0-dhlpwc-home-same-day');
    if (sameDayElement) {
      sameDayElement.parentElement.style.display = "none";
    }
    const sameDayNoNeighborElement = document.getElementById('radio-control-0-dhlpwc-home-no-neighbour-same-day');
    if (sameDayNoNeighborElement) {
      sameDayNoNeighborElement.parentElement.style.display = "none";
    }
  }
  const sameDayDeliveryMethods = ['dhlpwc-home-same-day', 'dhlpwc-home-no-neighbour-same-day'];
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    if (sameDayDeliveryMethods.indexOf(props.selectedShippingMethod) !== -1) {
      // Wordpress doesn't like it when we post raw data, so we post it as a form instead
      const formData = new FormData();
      formData.append('action', 'dhlpwc_get_delivery_times');
      formData.append('shipping_method', props.selectedShippingMethod);
      formData.append('postal_code', props.postalCode);
      formData.append('country_code', props.countryCode);
      fetch(window.dhlpwc_block_data['ajax_url'], {
        method: 'POST',
        body: formData
      }).then(async response => {
        var _sameDayDeliveryTime$, _sameDayDeliveryTime$2, _sameDayDeliveryTime$3;
        response = await response.json();
        const sameDayDeliveryTime = response.data[0]['identifier'].split('___');
        const formData = new FormData();
        formData.append('action', 'dhlpwc_delivery_time_selection_sync');
        formData.append('selected', true);
        formData.append('date', (_sameDayDeliveryTime$ = sameDayDeliveryTime[0]) !== null && _sameDayDeliveryTime$ !== void 0 ? _sameDayDeliveryTime$ : '');
        formData.append('start_time', (_sameDayDeliveryTime$2 = sameDayDeliveryTime[1]) !== null && _sameDayDeliveryTime$2 !== void 0 ? _sameDayDeliveryTime$2 : '');
        formData.append('end_time', (_sameDayDeliveryTime$3 = sameDayDeliveryTime[2]) !== null && _sameDayDeliveryTime$3 !== void 0 ? _sameDayDeliveryTime$3 : '');
        fetch(window.dhlpwc_block_data['ajax_url'], {
          method: 'POST',
          body: formData
        });
      });
    }
  }, [props.postalCode, props.countryCode, props.selectedShippingMethod]);
};

/***/ }),

/***/ "./src/servicepoints.js":
/*!******************************!*\
  !*** ./src/servicepoints.js ***!
  \******************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   Servicepoints: () => (/* binding */ Servicepoints)
/* harmony export */ });
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @wordpress/data */ "@wordpress/data");
/* harmony import */ var _wordpress_data__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_wordpress_data__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__);




const Servicepoints = props => {
  var _parcelshop$name;
  const VALIDATION_ERROR_PARCELSHOP_EMPTY = 'validation-error-parcelshop-empty';
  const getValidationError = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_2__.useSelect)(select => {
    const store = select('wc/store/validation');
    return store.getValidationError(VALIDATION_ERROR_PARCELSHOP_EMPTY);
  });
  const [parcelshop, setParcelshop] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useState)({});

  // Only run this once, intentionally no dependencies given
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    const script = document.createElement('script');
    script.src = 'https://static.dhlecommerce.nl/components/servicepoint-locator-component@latest/servicepoint-locator-component.js';
    script.async = true;
    script.onload = () => loadModal();
    document.body.appendChild(script);
  }, []);
  const loadModal = () => {
    var _window$dhlpwc_block_;
    var options = {
      language: window.dhlpwc_block_data['language'],
      country: (_window$dhlpwc_block_ = window.dhlpwc_block_data['country_code']) !== null && _window$dhlpwc_block_ !== void 0 ? _window$dhlpwc_block_ : '',
      limit: window.dhlpwc_block_data['limit'],
      googleMapsApiKey: window.dhlpwc_block_data['google_maps_key'],
      header: false,
      resizable: true,
      onSelect: parcelshop => {
        setParcelshop(parcelshop);
      },
      filter: {
        serviceType: 'pick-up'
      }
    };
    const servicePointElement = document.getElementById('dhl-servicepoint-locator-component');
    if (servicePointElement === null) {
      return;
    }
    window.dhlparcel_shipping_servicepoint_locator = new dhl.servicepoint.Locator(servicePointElement, options);

    // Hide the parcelshop element when parcelshop is not selected
    const parcelshopDetails = document.getElementById('dhlpwc-shipping-method-parcelshop-option');
    if (parcelshopDetails !== null) {
      if (props.selectedShippingMethod === 'dhlpwc-parcelshop') {
        parcelshopDetails.style.display = 'block';
      } else {
        parcelshopDetails.style.display = 'none';
      }
    }

    // Hide the modal when clicking the X button
    const modalCloseElement = document.getElementById('dhlpwc-modal-close');
    if (modalCloseElement !== null) {
      modalCloseElement.onclick = () => {
        const ServicePointModalElement = document.getElementById('dhlpwc-servicepoint-modal');
        if (ServicePointModalElement) ServicePointModalElement.style.display = 'none';
      };
    }

    // Set background image dynamically
    const modalContentElement = document.getElementById('dhlpwc-modal-content');
    if (modalContentElement !== null) modalContentElement.style['background-image'] = 'url(' + window.dhlpwc_block_data['modal_background'] + ')';
  };

  /**
   * Set the initial parcelshop based on postalCode or countryCode
   */
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    const parcelshopDetails = document.getElementById('dhlpwc-shipping-method-parcelshop-option');
    if (parcelshopDetails === null) {
      return;
    }
    if (props.selectedShippingMethod === 'dhlpwc-parcelshop') {
      parcelshopDetails.style.display = 'block';

      // Wordpress doesn't like it when we post raw data, so we post it as a form instead
      const formData = new FormData();
      formData.append('action', 'dhlpwc_get_initial_parcelshop');
      formData.append('postal_code', props.postalCode);
      formData.append('country_code', props.countryCode);
      fetch(window.dhlpwc_block_data['ajax_url'], {
        method: 'POST',
        body: formData
      }).then(async response => {
        response = await response.json();
        if (response.data?.parcelshop) {
          setParcelshop(response.data.parcelshop);
        } else {
          setParcelshop({});
        }
      });
    } else {
      parcelshopDetails.style.display = 'none';
      setParcelshop({});
    }
  }, [props.postalCode, props.selectedShippingMethod]);
  const {
    setValidationErrors,
    clearValidationError
  } = (0,_wordpress_data__WEBPACK_IMPORTED_MODULE_2__.useDispatch)('wc/store/validation');

  /**
   * Sync the parcelshop (or lack of) with frontend elements and the backend
   *
   * @param parcelshop
   */
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_0__.useEffect)(() => {
    const ServicePointModalElement = document.getElementById('dhlpwc-servicepoint-modal');
    if (ServicePointModalElement === null) {
      return;
    }
    ServicePointModalElement.style.display = 'none';

    // Wordpress doesn't like it when we post raw data, so we post it as a form instead
    const formData = new FormData();
    formData.append('action', 'dhlpwc_parcelshop_selection_sync');
    if (Object.keys(parcelshop).length !== 0 || props.selectedShippingMethod !== 'dhlpwc-parcelshop') {
      var _parcelshop$id;
      props.setValidationClass('dhlpwc_notice');
      formData.append('parcelshop_id', (_parcelshop$id = parcelshop['id']) !== null && _parcelshop$id !== void 0 ? _parcelshop$id : '');
      formData.append('country_code', props.countryCode);
      if (getValidationError) {
        clearValidationError(VALIDATION_ERROR_PARCELSHOP_EMPTY);
      }
    } else {
      props.setValidationClass('dhlpwc-servicepoint-error');
      setValidationErrors({
        [VALIDATION_ERROR_PARCELSHOP_EMPTY]: {
          message: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('Please add some text', 'shipping-workshop'),
          hidden: true
        }
      });
    }
    fetch(window.dhlpwc_block_data['ajax_url'], {
      method: 'POST',
      body: formData
    });
  }, [parcelshop]);

  /**
   * Show the parcelshop selector
   */
  const showModal = () => {
    if (props.postalCode === '') {
      document.getElementById('shipping-postcode').value;
    }
    const modal = document.getElementById('dhlpwc-servicepoint-modal');
    if (modal !== null) {
      modal.style.display = 'block';
    }
    if (typeof window.dhlparcel_shipping_servicepoint_locator !== 'undefined') {
      window.dhlparcel_shipping_servicepoint_locator.setCountry(props.countryCode);
      window.dhlparcel_shipping_servicepoint_locator.setQuery(props.postalCode);
    }
  };
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("div", {
    id: "dhlpwc-shipping-method-parcelshop-option",
    className: "dhlpwc-shipping-method-parcelshop-option",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
      className: "dhlpwc-parcelshop-selection-text",
      children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('The following ServicePoint is selected:')
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("span", {
      className: `dhlpwc-parcelshop-option-message ${props.validationClass}`,
      children: (_parcelshop$name = parcelshop.name) !== null && _parcelshop$name !== void 0 ? _parcelshop$name : (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_1__.__)('⚠ No location selected.')
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("span", {
      className: `dhlpwc-parcelshop-option-message ${props.validationClass}`,
      children: [parcelshop?.address?.postal_code, " ", parcelshop?.address?.city]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsxs)("span", {
      className: `dhlpwc-parcelshop-option-message ${props.validationClass}`,
      children: [parcelshop?.address?.street, " ", parcelshop?.address?.number, " ", parcelshop?.address?.addition]
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_3__.jsx)("input", {
      type: "button",
      className: "dhlpwc-parcelshop-option-change",
      value: "Change",
      onClick: () => showModal()
    })]
  });
};

/***/ }),

/***/ "@wordpress/components":
/*!************************************!*\
  !*** external ["wp","components"] ***!
  \************************************/
/***/ ((module) => {

module.exports = window["wp"]["components"];

/***/ }),

/***/ "@wordpress/data":
/*!******************************!*\
  !*** external ["wp","data"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["data"];

/***/ }),

/***/ "@wordpress/dom-ready":
/*!**********************************!*\
  !*** external ["wp","domReady"] ***!
  \**********************************/
/***/ ((module) => {

module.exports = window["wp"]["domReady"];

/***/ }),

/***/ "@wordpress/element":
/*!*********************************!*\
  !*** external ["wp","element"] ***!
  \*********************************/
/***/ ((module) => {

module.exports = window["wp"]["element"];

/***/ }),

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ }),

/***/ "react/jsx-runtime":
/*!**********************************!*\
  !*** external "ReactJSXRuntime" ***!
  \**********************************/
/***/ ((module) => {

module.exports = window["ReactJSXRuntime"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!*********************!*\
  !*** ./src/view.js ***!
  \*********************/
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   App: () => (/* binding */ App)
/* harmony export */ });
/* harmony import */ var _wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/dom-ready */ "@wordpress/dom-ready");
/* harmony import */ var _wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @wordpress/element */ "@wordpress/element");
/* harmony import */ var _wordpress_element__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_wordpress_element__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _servicepoints__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./servicepoints */ "./src/servicepoints.js");
/* harmony import */ var _delivery_times__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./delivery-times */ "./src/delivery-times.js");
/* harmony import */ var _same_day__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./same-day */ "./src/same-day.js");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! react/jsx-runtime */ "react/jsx-runtime");
/* harmony import */ var react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__);








_wordpress_dom_ready__WEBPACK_IMPORTED_MODULE_0___default()(async function () {
  const getElementByIdAsync = id => new Promise(resolve => {
    const getElement = () => {
      const element = document.getElementById(id);
      if (element) {
        resolve(element);
      } else {
        requestAnimationFrame(getElement);
      }
    };
    getElement();
  });
  const container = await getElementByIdAsync('dhlpwc-app');
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.render)(/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(App, {}), container);
});
const App = () => {
  const [postalCode, setPostalCode] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(window.dhlpwc_block_data['postal_code']);
  const [countryCode, setCountryCode] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(window.dhlpwc_block_data['country_code']);
  const [selectedShippingMethod, setSelectedShippingMethod] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)(window.dhlpwc_block_data['initial_shipping_method']);
  const [validationClass, setValidationClass] = (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useState)('dhlpwc_warning');

  // Only run this once, intentionally no dependencies given
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    // Track selected shipping method
    document.addEventListener("change", trackSelectedShippingMethod);
    function trackSelectedShippingMethod(event) {
      let element = event.target;
      if (element.name === 'radio-control-0') {
        setSelectedShippingMethod(event.target.value);
      }
    }

    /**
     * Create observer for when shipping methods fundamentally change (WooCommerce does not call the Change method)
     * We observe the parent of the radio buttons. When a shipping methods gets added or removed, we update the selectedShippingMethod
     */
    const observer = new MutationObserver(mutationList => {
      for (const mutation of mutationList) {
        if (mutation.type === "childList") {
          let radioControlElement = document.getElementsByName('radio-control-0');
          if (typeof radioControlElement !== "undefined" && typeof radioControlElement[0] !== "undefined" && typeof radioControlElement[0].value !== "undefined") {
            setSelectedShippingMethod(radioControlElement[0].value);
          }
        }
      }
    });
    const radioControlElement = document.getElementsByName('radio-control-0');
    // TODO clean up check
    if (typeof radioControlElement !== "undefined" && typeof radioControlElement[0] !== "undefined" && typeof radioControlElement[0].parentElement !== "undefined" && typeof radioControlElement[0].parentElement.parentElement !== "undefined") {
      observer.observe(radioControlElement[0].parentElement.parentElement, {
        childList: true
      });
    }

    // TODO find a better method to retrieve address data instead of observing requests
    const nativeFetch = window.fetch;
    window.fetch = function (...args) {
      let isWantedCall = false;
      const urlParams = new URLSearchParams(args[0]);
      urlParams.forEach(function (param) {
        if (isWantedCall) {
          return;
        }
        if (param.includes('batch')) {
          isWantedCall = true;
        }
      });
      if (!isWantedCall) {
        return nativeFetch.apply(window, args);
      }
      JSON.parse(args[1].body).requests.forEach(request => {
        if (request.path.includes('update-customer')) {
          if (typeof request.body.shipping_address.country !== 'undefined') {
            setCountryCode(request.body.shipping_address.country);
          }
          if (typeof request.body.shipping_address.postcode !== 'undefined') {
            setPostalCode(request.body.shipping_address.postcode);
          }
        }
      });
      return nativeFetch.apply(window, args);
    };
  }, []);
  const deliveryTimeShippingMethods = ['dhlpwc-parcelshop', 'dhlpwc-home', 'dhlpwc-home-evening', 'dhlpwc-home-next-day', 'dhlpwc-home-no-neighbour', 'dhlpwc-home-no-neighbour-evening', 'dhlpwc-home-no-neighbour-next-day'];
  (0,_wordpress_element__WEBPACK_IMPORTED_MODULE_1__.useEffect)(() => {
    const deliveryOptionsElement = document.getElementById('dhlpwc-shipping-method-delivery-options');
    if (!deliveryOptionsElement) {
      return;
    }
    if (deliveryTimeShippingMethods.indexOf(selectedShippingMethod) !== -1) {
      deliveryOptionsElement.style.display = "block";
    } else {
      deliveryOptionsElement.style.display = "none";
    }
  }, [selectedShippingMethod]);
  return /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsxs)("div", {
    id: "dhlpwc-shipping-method-delivery-options",
    className: "dhlpwc-shipping-method-delivery-options",
    children: [/*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)("div", {
      className: "dhlpwc-delivery-options-header",
      children: (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_5__.__)('DHL Delivery options')
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_delivery_times__WEBPACK_IMPORTED_MODULE_3__.DeliveryTimes, {
      postalCode: postalCode,
      countryCode: countryCode,
      selectedShippingMethod: selectedShippingMethod
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_servicepoints__WEBPACK_IMPORTED_MODULE_2__.Servicepoints, {
      postalCode: postalCode,
      countryCode: countryCode,
      selectedShippingMethod: selectedShippingMethod,
      validationClass: validationClass,
      setValidationClass: setValidationClass
    }), /*#__PURE__*/(0,react_jsx_runtime__WEBPACK_IMPORTED_MODULE_6__.jsx)(_same_day__WEBPACK_IMPORTED_MODULE_4__.SameDay, {
      postalCode: postalCode,
      countryCode: countryCode,
      selectedShippingMethod: selectedShippingMethod
    })]
  });
};
})();

/******/ })()
;
//# sourceMappingURL=view.js.map