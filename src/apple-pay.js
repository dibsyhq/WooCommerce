import { registerExpressPaymentMethod } from '@woocommerce/blocks-registry';

registerExpressPaymentMethod({
    name: 'dibsy-v2-apple-pay',
	content: typeof (window.ApplePaySession && ApplePaySession.canMakePayments()) != "undefined" ? <apple-pay-button buttonstyle="black" type="plain" locale="en" style={{width: '100%'}}></apple-pay-button> : null,
	edit: null,
	canMakePayment: () => typeof (window.ApplePaySession && ApplePaySession.canMakePayments()) != "undefined",
	supports: {
		features: undefined,
	},
});