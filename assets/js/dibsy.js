jQuery(function ($) {
  "use strict";

  const inline_style = {
    borderRadius: "0!important",
    width: "100%!important;",
    "&:focus": {
      border: "1px solid rgb(108, 121, 133) !important",
    },
    "&.touched.invalid": {
      border: "1px solid rgb(240, 83, 72)!important",
      background:"rgba(240, 83, 72, 0.04)!important"
    },
  };

  const cardExpiryStyle = dibsy_params.inline_form
    ? { ...inline_style, borderLeft: "0!important", borderRight: "0!important" }
    : {};
  const cardNumberStyle = dibsy_params.inline_form
    ? { ...inline_style, borderRight: "0!important;" }
    : {};
  const cardCodeStyle = dibsy_params.inline_form
    ? { ...inline_style, borderLeft: "0!important;" }
    : {};

  var WC_Dibsy_Form = {
    transaction: null,
    order_id: null,
    amount: null,
    canSubmit: false,
    init: function () {
      // listen if checkout order review updated
      $(document.body).on("updated_checkout", async function () {
        if (!WC_Dibsy_Form.areElementsMounted()) {
          await WC_Dibsy_Form.mountElements();
        }
      });
    },
    mountElements: async function () {
      // if the element are already mounted
      if (!this.areElementsMounted()) {
        const dibsy = await Dibsy(dibsy_params.public_key, {
          type: "embed",
          onFieldReady: (_, fields) => {
            if (fields?.length >= 3) {
              WC_Dibsy_Form.removeFormLoading();
            }
          },
          canSubmit: (canSub) => {
            WC_Dibsy_Form.canSubmit = canSub;
          },
        });
        const cardNumber = dibsy.createComponent("cardNumber", {
          css: cardNumberStyle,
        });
        cardNumber.mount("#card-number");
        cardNumber.errorMessage("#card-number-error");

        const cardCode = dibsy.createComponent("cardCode", {
          placeHolder: "CVC/CVV",
          showCardIcon: !dibsy_params.inline_form,
          css: cardCodeStyle,
        });
        cardCode.mount("#card-code");
        cardCode.errorMessage("#card-code-error");

        const expiryDate = dibsy.createComponent("expiryDate", {
          css: cardExpiryStyle,
        });
        expiryDate.mount("#expiry-date");
        expiryDate.errorMessage("#expiry-date-error");

        // listen for checkout submit
        $("form.woocommerce-checkout").on("checkout_place_order", function () {
          
          if (!WC_Dibsy_Form.isDibsyCreditCardSelected()) {
            return true;
          }


          WC_Dibsy_Form.handleCheckoutSubmit(dibsy)
            .then((data) => console.log(data))
            .catch((error) => {
              WC_Dibsy_Form.removeLoader();
              WC_Dibsy_Form.addErrorMessage(error);
            });

          return false;
        });
      }
    },
    getSelectedPaymentElement: function() {
			return $( '.payment_methods input[name="payment_method"]:checked' );
		},
    areElementsMounted: function () {
      const cardNumber = document.getElementById("card-number");
      const expiryDate = document.getElementById("expiry-date");
      const cardCode = document.getElementById("card-code");
      return (
        cardNumber.hasChildNodes() &&
        expiryDate.hasChildNodes() &&
        cardCode.hasChildNodes()
      );
    },
    getBodyDetails: function () {
      var first_name = $("#billing_first_name")?.length
          ? $("#billing_first_name").val()
          : dibsy_params.billing_first_name,
        last_name = $("#billing_last_name")?.length
          ? $("#billing_last_name").val()
          : dibsy_params.billing_last_name,
        customer = { name: "", email: "", phone: "" };

      customer.name = first_name;

      if (first_name && last_name) {
        customer.name = first_name + " " + last_name;
      }

      customer.email = $("#billing_email").val();
      customer.phone = $("#billing_phone").val();

      /*
       * delete any undefined or empty field
       */
      if (
        typeof customer.phone === "undefined" ||
        0 >= customer.phone?.length
      ) {
        delete customer.phone;
      }

      if (
        typeof customer.email === "undefined" ||
        0 >= customer.email?.length
      ) {
        delete customer.email;
      }

      if (typeof customer.name === "undefined" || 0 >= customer.name?.length) {
        delete customer.name;
      }

      return {
        customer,
        redirectUrl: dibsy_params.redirectUrl || window.location.href,
        lang: dibsy_params.lang,
        description: dibsy_params.description,
      };
    },
    addErrorMessage: function (message) {
      $("form.woocommerce-checkout")
        .prepend(`<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout">
        <div class="woocommerce-error">${message}</div></div>`);
      $(window).scrollTop(0);
    },
    removeErrorMessage: function () {
      $("form.woocommerce-checkout .woocommerce-NoticeGroup-checkout").remove();
    },
    handleCheckoutSubmit: async function (dibsy) {
      if (WC_Dibsy_Form.canSubmit) {
        // remove any error message
        this.removeErrorMessage();
        this.injectLoader();

        // check if there's an order or create it
        if (!this.order_id) {
          const { order_id, amount } = (await this.createOrder()) || {};
          if (order_id && amount) {
            this.order_id = order_id;
            this.amount = amount;
          } else {
            return this.promiseError(ERROR_MESSAGES.CREATE_ORDER_FAILED);
          }
        }

        // check if there's a transaction or create it

        if (!this.transaction) {
          const { transaction } = (await this.createPayment()) || {};
          if (transaction) {
            this.transaction = transaction;
          } else {
            return this.promiseError(ERROR_MESSAGES.INIT_PAYMENT_FAILED);
          }
        }

        const { paymentToken } = this.transaction || {};

        if (paymentToken && this.order_id) {
          return new Promise(function (resolve, reject) {
            dibsy.submit({
              paymentToken,
              state: async (state) => {
                console.log("state ", state);
                if (state === "failed_pay") {
                  reject(ERROR_MESSAGES.FAILED_PAYMENT);
                }
              },
              onClose: () => {
                WC_Dibsy_Form.removeLoader();
                reject(ERROR_MESSAGES.POPUP_OR_TAB_CLOSED);
              },
              callback: async (transaction, error) => {
                if (error && error?.message) {
                  reject(error?.message);
                }
                if (transaction && transaction?.status === "succeeded") {
                  try {
                    const { order } =
                      (await WC_Dibsy_Form.updateOrder(
                        WC_Dibsy_Form.order_id
                      )) || {};
                    if (order) {
                      resolve(true);
                      window.location.href = `${transaction?.redirectUrl}${WC_Dibsy_Form.order_id}/?key=${order?.order_key}`;
                    } else {
                      reject(ERROR_MESSAGES.UPDATE_ORDER_FAILED);
                    }
                  } catch (error) {
                    reject(ERROR_MESSAGES.UPDATE_ORDER_FAILED);
                  }
                } else {
                  reject(ERROR_MESSAGES.FAILED_PAYMENT);
                }
              },
            });
          });
        } else {
          return this.promiseError(ERROR_MESSAGES.FAILED_PAYMENT);
        }
      }
    },
    getAjaxURL: function (endpoint) {
      return dibsy_params.ajaxurl
        .toString()
        .replace("%%endpoint%%", "wc_dibsy_" + endpoint);
    },
    createPayment: async function () {
      const data = this.getBodyDetails();
      return new Promise(function (resolve, reject) {
        $.post({
          url: WC_Dibsy_Form.getAjaxURL("create_payment"),
          data: {
            ...data,
            amount: WC_Dibsy_Form.amount,
            metadata: { order_id: WC_Dibsy_Form.order_id },
          },
          success: function (result) {
            if (result) {
              const data = JSON.parse(result);
              resolve(data);
            } else {
              reject(ERROR_MESSAGES.INIT_PAYMENT_FAILED);
            }
          },
          error: function (error) {
            reject(ERROR_MESSAGES.INIT_PAYMENT_FAILED);
          },
        });
      });
    },
    createOrder: async function () {
      return new Promise(function (resolve, reject) {
        $.post({
          url: WC_Dibsy_Form.getAjaxURL("create_order"),
          data: {
            action: "ajax_order",
            fields: $("form.checkout").serializeArray(),
            user_id: dibsy_params?.user_id,
          },
          success: function (result) {
            if (result) {
              const data = JSON.parse(result);
              if (data?.errors) {
                WC_Dibsy_Form.removeLoader();
                for (let error of data?.errors) {
                  WC_Dibsy_Form.addErrorMessage(error);
                }
              } else {
                resolve(data);
              }
            } else {
              reject(ERROR_MESSAGES.CREATE_ORDER_FAILED);
            }
          },
          error: function (error) {
            reject(ERROR_MESSAGES.CREATE_ORDER_FAILED);
          },
        });
      });
    },
    updateOrder: async function (order_id) {
      return new Promise(function (resolve, reject) {
        $.post({
          url: WC_Dibsy_Form.getAjaxURL("update_order"),
          data: {
            action: "ajax_order",
            transaction_id: WC_Dibsy_Form.transaction?.id,
            order_id,
          },
          success: function (result) {
            if (result) {
              const data = JSON.parse(result);
              resolve(data);
            } else {
              reject(ERROR_MESSAGES.UPDATE_ORDER_FAILED);
            }
          },
          error: function (error) {
            reject(ERROR_MESSAGES.UPDATE_ORDER_FAILED);
          },
        });
      });
    },
    injectLoader: function () {
      $("body #page").append(
        `<div id="dibsy-loader">
          <div class="dibsy_payment_loading"><div></div><div></div><div></div></div>
          <p>Processing payment. Do not close the tab.</p>
        </div>`
      );
      $("body").css("overflow", "hidden");
    },
    removeLoader: function () {
      $("body #dibsy-loader").remove();
      $("body").css("overflow", "unset");
    },
    isDibsyCreditCardSelected:function(){
        return $('#payment_method_dibsy').is(':checked');
    },
    isDibsyNAPSSelected:function(){
      return $('#payment_method_dibsy_naps').is(':checked');
  },
    promiseError: function (message) {
      return new Promise(function (resolve, reject) {
        reject(message);
      });
    },
    removeFormLoading: function () {
      document.querySelector("#wc-dibsy-cc-form").style.display = "block";
      document.querySelector("#checkout-loader-wrapper").style.display = "none";
    },
  };

  WC_Dibsy_Form.init();
});
