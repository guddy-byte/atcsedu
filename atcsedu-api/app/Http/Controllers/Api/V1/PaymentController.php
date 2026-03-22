<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\InitializePaymentRequest;
use App\Models\Exam;
use App\Models\Material;
use App\Models\PaymentWebhook;
use App\Models\Purchase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    public function initialize(InitializePaymentRequest $request): JsonResponse
    {
        $user = $request->user();
        $item = $this->resolveItem($request->validated('item_type'), (int) $request->validated('item_id'));

        if (! $item) {
            return response()->json([
                'status' => 'error',
                'message' => 'The selected item could not be found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $amount = (float) $item->price;

        if ($amount <= 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Free items do not require payment.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $reference = $this->generateReference();

        $purchase = Purchase::create([
            'user_id' => $user->id,
            'item_type' => $request->validated('item_type'),
            'item_id' => $item->id,
            'amount' => $amount,
            'status' => 'pending',
            'provider_reference' => $reference,
        ]);

        $secretKey = config('services.paystack.secret_key');

        if (! $secretKey) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'reference' => $reference,
                    'authorization_url' => null,
                    'access_code' => null,
                    'is_simulated' => true,
                    'purchase_id' => $purchase->id,
                ],
            ]);
        }

        try {
            $response = Http::withToken($secretKey)
                ->acceptJson()
                ->post(config('services.paystack.base_url').'/transaction/initialize', [
                    'email' => $user->email,
                    'amount' => (int) round($amount * 100),
                    'reference' => $reference,
                    'currency' => $purchase->currency,
                    'callback_url' => config('services.paystack.callback_url'),
                ])
                ->throw()
                ->json('data');
        } catch (RequestException $exception) {
            $purchase->update(['status' => 'failed']);

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to initialize payment at the moment.',
                'details' => $exception->response?->json(),
            ], Response::HTTP_BAD_GATEWAY);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'reference' => $reference,
                'authorization_url' => $response['authorization_url'] ?? null,
                'access_code' => $response['access_code'] ?? null,
                'is_simulated' => false,
                'purchase_id' => $purchase->id,
            ],
        ]);
    }

    public function verify(Request $request, string $reference): JsonResponse
    {
        $purchase = Purchase::query()
            ->where('provider_reference', $reference)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($purchase->status === 'paid') {
            return $this->paymentVerifiedResponse($purchase);
        }

        $secretKey = config('services.paystack.secret_key');
        $simulate = $request->boolean('simulate') || ! $secretKey;

        if ($simulate) {
            $this->markPurchasePaid($purchase);

            return $this->paymentVerifiedResponse($purchase->fresh());
        }

        try {
            $response = Http::withToken($secretKey)
                ->acceptJson()
                ->get(config('services.paystack.base_url').'/transaction/verify/'.$reference)
                ->throw()
                ->json('data');
        } catch (RequestException $exception) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to verify payment at the moment.',
                'details' => $exception->response?->json(),
            ], Response::HTTP_BAD_GATEWAY);
        }

        if (($response['status'] ?? null) === 'success') {
            $this->markPurchasePaid($purchase);
        } else {
            $purchase->update(['status' => 'failed']);
        }

        return $this->paymentVerifiedResponse($purchase->fresh());
    }

    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->all();
        $secretKey = config('services.paystack.secret_key');

        if ($secretKey) {
            $expectedSignature = hash_hmac('sha512', $request->getContent(), $secretKey);

            if (! hash_equals($expectedSignature, (string) $request->header('x-paystack-signature'))) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid webhook signature.',
                ], Response::HTTP_UNAUTHORIZED);
            }
        }

        PaymentWebhook::create([
            'provider' => 'paystack',
            'event' => $payload['event'] ?? null,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
        ]);

        if (($payload['event'] ?? null) === 'charge.success') {
            $reference = data_get($payload, 'data.reference');

            $purchase = Purchase::query()->where('provider_reference', $reference)->first();

            if ($purchase) {
                $this->markPurchasePaid($purchase);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    private function resolveItem(string $type, int $id): Exam|Material|null
    {
        return $type === 'exam'
            ? Exam::query()->find($id)
            : Material::query()->find($id);
    }

    private function generateReference(): string
    {
        return 'ATCS-PSK-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));
    }

    private function markPurchasePaid(Purchase $purchase): void
    {
        $purchase->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    private function paymentVerifiedResponse(Purchase $purchase): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Payment verified',
            'data' => [
                'reference' => $purchase->provider_reference,
                'payment_status' => $purchase->status,
                'purchase' => [
                    'id' => $purchase->id,
                    'item_type' => $purchase->item_type,
                    'item_id' => $purchase->item_id,
                    'status' => $purchase->status,
                    'paid_at' => optional($purchase->paid_at)->toISOString(),
                ],
            ],
        ]);
    }
}
