<?php

namespace dyonis\yii2\payment\providers\stripe\widget;

use yii\web\AssetBundle;

class StripeWidgetAsset extends AssetBundle
{
    public $sourcePath = __DIR__.'/asset';

    public $js = [
        'https://js.stripe.com/v3/',
        'stripe-widget.js',
    ];
}
