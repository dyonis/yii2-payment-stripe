<?php

namespace dyonis\yii2\payment\providers\stripe\widget;

use dyonis\yii2\payment\PaymentProviderInterface;
use dyonis\yii2\payment\providers\stripe\StripeProvider;
use Yii;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\web\View;

class PaymentWidget extends Widget
{
    /**
     * @var array JS widget options
     */
    public array $options = [];

    /**
     * @var StripeProvider
     */
    private PaymentProviderInterface $provider;

    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->provider = Yii::$app->payment->getProvider(StripeProvider::PROVIDER_NAME);
    }

    public function run(): string
    {
        parent::run();

        $this->registerAssets();
        $this->initJsOptions();
        $this->initJS();

        // todo: implement to prevent creating new PI any time
        /*if ($this->options['pi'] ?? null) {
            $paymentIntent = $this->provider->getPaymentIntent($this->options['pi']);
            $paymentIntent = $this->provider->updatePaymentIntent($paymentIntent, $this->options);
        } else {*/
            $paymentIntent = $this->provider->createPaymentIntent($this->options);
        /*}*/

        return $this->render('widget',[
            'publicKey' => $this->provider->publicKey,
            'clientSecret' => $paymentIntent->client_secret,
        ]);

    }

    private function registerAssets()
    {
        $this->view->registerAssetBundle(StripeWidgetAsset::class);
    }

    private function initJsOptions()
    {
        $default = [];

        $this->options = ArrayHelper::merge($default, $this->options);
    }

    private function initJS()
    {
        $this->view->registerJs('initPayForm();', View::POS_READY);
    }
}
