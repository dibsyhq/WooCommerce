import { registerPaymentMethod } from '@woocommerce/blocks-registry';

var payment_method_data = window.wc.wcSettings.getPaymentMethodData('dibsy-v2');

import clickToPayIcon from '../assets/images/click-to-pay.svg';
import visaIcon from '../assets/images/visa.svg';
import masterCardIcon from '../assets/images/mc.svg';
import amexIcon from '../assets/images/amex.svg';

if ( 'yes' === payment_method_data?.enabled ) {
    registerPaymentMethod({
        name: 'dibsy-v2',
        label: <div>{payment_method_data.title}<div className='dibsy-icons'><img src={clickToPayIcon} alt='Click To Pay Icon' /><img src={visaIcon} alt='Visa Icon' /><img src={masterCardIcon} alt='MasterCard Icon' /><img src={amexIcon} alt='Amex Icon' /></div></div>,
        content: <div>{payment_method_data.description}</div>,
        edit: null,
        icons: null,
        canMakePayment: () => true,
        ariaLabel: "Dibsy Checkout",
        supports: {
            features: undefined,
        }
    });
}