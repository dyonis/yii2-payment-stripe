<?php
/** @var string $publicKey */
/** @var string $clientSecret */

?>
<script>
    function initPayForm() {
        const widget = new StripeWidget(
            '<?= $publicKey ?>',
            '<?= $clientSecret ?>'
        );

        widget.init();
    }


</script>

<div class="loader" id="loader">
    <?= Yii::t('app', 'Loading...') ?>
</div>
<form id="stripe-payment-form">
   <div id="payment-element"></div>
   <button id="submit" class="d-none" disabled>
		<span id="button-text">
         <?= Yii::t('app', 'Pay') ?>
      </span>
   </button>
   <div id="payment-loader" class="d-none"></div>
   <div id="payment-message" class="hidden"></div>
</form>
