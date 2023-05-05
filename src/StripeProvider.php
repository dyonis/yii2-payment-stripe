<?php

namespace dyonis\yii2\payment\providers\stripe;

use dyonis\yii2\payment\BasePaymentProvider;
use dyonis\yii2\payment\exceptions\PaymentException;
use dyonis\yii2\payment\PaymentLogger;
use dyonis\yii2\payment\providers\stripe\exceptions\StripePaymentException;
use dyonis\yii2\payment\response\BaseResponse;
use dyonis\yii2\payment\response\SuccessResponse;
use Stripe\Event;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;
use yii\web\Request;
use yii\web\Response;

final class StripeProvider extends BasePaymentProvider
{
    const PROVIDER_NAME = 'Stripe';

    public string $publicKey = '';
    public string $secretKey = '';
    public string $hookKey = '';
    public string $deviceName = '';

    public string $name = self::PROVIDER_NAME;

    private PaymentLogger $logger;

    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->logger = (new PaymentLogger())->setProvider($this);
    }

    public function init()
    {
        parent::init();

        $this->checkConfig();
    }

    /**
     * todo: replace $params array to PaymentIntentDto
     * @param array $params
     *
     * @return PaymentIntent
     * @throws StripePaymentException
     */
    public function createPaymentIntent(array $params): PaymentIntent
    {
        Stripe::setApiKey($this->secretKey);

        try {
            return PaymentIntent::create($params);
        }
        catch (ApiErrorException $e) {
            throw new StripePaymentException('Unable to create PaymentIntent: '.$e->getMessage(), 0, $e);
        }
    }

    public function processPaymentRequest(Request $request, Response $response): Response
    {
        try
        {
            $event = $this->getEventFromRequest($request);
            $this->logRequestData($request, $event->type);

            // Handle the event
            // https://stripe.com/docs/api/events/types
            switch ($event->type) {
                //case 'payment_intent.created':
                //case 'payment_intent.payment_failed':
                //case 'payment_intent.succeeded':
                    //$this->paymentIntentSucceeded($event);
                    //break;
                case 'charge.succeeded':
                    $paymentResponse = new SuccessResponse();
                    $this->loadDataToResponseObject($paymentResponse, $event->toArray());
                    $this->triggerPaymentSuccess($paymentResponse);
                    break;
                //case 'charge.failed':
                    //$this->triggerPaymentFail();
                    //break;
                // Платеж отозван (возврат средств покупателю)
                //case 'charge.refunded':
                    //$this->stripeChargeRefunded($event);
                    //break;
                default:
                    $this->logger
                        ->setType(PaymentLogger::TYPE_ERROR)
                        ->setMessage('Received unknown event type ' . $event->type)
                        ->log();
            }
        }
        catch(PaymentException $e)
        {
            $this->logger
                ->setType(PaymentLogger::TYPE_ERROR)
                //->setData($data)
                ->setMessage($e->getMessage())
                ->log();

            return $this->getUnsuccessfulResponse($response);
        }

        return $this->getSuccessResponse($response);
    }

    private function logRequestData(Request $request, string $type = null)
    {
        $data = [
            'type' => $type ?? 'null',
            'headers' => $request->headers->toOriginalArray(),
            'body' => $request->post(),
        ];

        $this->logger
            ->setData($data)
            ->log();
    }

    private function getEventFromRequest(Request $request): Event
    {
        // Check request
        if(!$signHeader = $request->headers->get('stripe-signature'))
            throw new StripePaymentException('Invalid signature');

        // Get Event object
        try {
            return Webhook::constructEvent(
                $request->rawBody,
                $signHeader,
                $this->hookKey
            );
        }
        catch (\UnexpectedValueException $e)
        {
            throw new StripePaymentException('Invalid payload');
        }
        catch (SignatureVerificationException $e)
        {
            throw new StripePaymentException('Invalid signature');
        }
        catch(\Throwable $e)
        {
            throw new StripePaymentException('Unknown error', 0, $e);
        }
    }

    private function loadDataToResponseObject(BaseResponse $response, array $data)
    {
        $eventData = $data['data']['object'];

        $response->data = $eventData;
        $response->paySystemName = $this->name;
        $response->amount = (float)$eventData['amount'];
        $response->currency = strtoupper($eventData['currency'] ?? '');
        $response->invoiceId = $eventData['invoice'] ?? '';
        $response->transactionId = $eventData['id'];
        $response->testMode = !($data['livemode'] ?? true);
        //$response->userId = (int)$eventData['metadata']['user_id'] ?? 0;
        $response->payload = $eventData['metadata'];
    }

    private function checkConfig()
    {
        $required = [
            'publicKey',
            'hookKey',
            'secretKey',
        ];

        foreach ($required as $param) {
            if (!$this->{$param}) {
                throw new StripePaymentException("Parameter $param must be set");
            }
        }
    }

    protected function getSuccessResponse(Response $response): Response
    {
        $response->setStatusCode(200);

        return $response;
    }

    protected function getUnsuccessfulResponse(Response $response): Response
    {
        $response->setStatusCode(400);

        return $response;
    }
}
