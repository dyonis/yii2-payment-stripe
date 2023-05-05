
class StripeWidget {
    constructor(publicKey, secretKey)
    {
        this.paymentInProgress = false;

        this.stripe = Stripe(publicKey);
        this.elements = this.stripe.elements({
            clientSecret: secretKey
        });
    }

    init()
    {
        this.#buildForm();
        this.#initFormEvents();
    }

    #buildForm()
    {
        const paymentElement = this.elements.create('payment')
        const payButton = document.getElementById('submit')
        const loader = document.getElementById('loader')

        paymentElement.on('ready', () => {
            payButton.style.display = 'block';
            loader.style.display = 'none';
        })

        paymentElement.on('change', function(event) {
            if (event.complete) {
                payButton.disabled = false
            }
        });

        paymentElement.mount("#payment-element");
    }

    #initFormEvents()
    {
        // Handle submit form
        document
            .querySelector("#stripe-payment-form")
            .addEventListener("submit", this.#handleSubmit)

        /*

        triggerPaymentEvent('onPaymentComplete', {
            provider: 'Stripe',
            paymentResult,
            options
        });*/
    }

    async #handleSubmit(e)
    {
        e.preventDefault();

        if (this.paymentInProgress) {
            return
        }

        this.#setLoading(true);

        const {
            error,
            paymentIntent
        } = await stripe.confirmPayment({
            elements,
            confirmParams: {
                return_url: document.referrer
            },
            redirect: "if_required"
        });

        if (error) {
            console.error('Error. todo: remove', error);

            this.#triggerPaymentEvent('onPaymentFail', {
                provider: 'Stripe',
                error
            });
        }

        if (paymentIntent) {
            console.log('Success. todo: remove', paymentIntent)
            if(paymentIntent.status == 'succeeded') {
                this.#triggerPaymentEvent('onPaymentSuccess', {
                    provider: 'Stripe',
                    paymentIntent
                });
            }
        }

        this.#setLoading(false);
    }

    #setLoading(isLoading) {
        if (isLoading) {
            this.paymentInProgress = true
            document.querySelector("#submit").disabled = true;
        } else {
            this.paymentInProgress = false
            document.querySelector("#submit").disabled = false;
        }
    }

    static #triggerPaymentEvent(name, data)
    {
        console.log('Stripe event', name, data);

        const event = new CustomEvent(name, {detail: data});
        document.dispatchEvent(event);
    }
}
