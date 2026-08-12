<?php

namespace App\Http\Middleware;

use App\Models\OfflineSyncRequest;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReplayOfflineRequest
{
    /**
     * Make queued admin mutations idempotent and return a JSON response even
     * when the original controller normally redirects after a form submit.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $clientId = trim((string) $request->input('offline_client_id', ''));

        if (! $this->isReplayableRequest($request, $clientId) || ! $request->user()) {
            return $next($request);
        }

        $existing = OfflineSyncRequest::where('client_id', $clientId)->first();

        if ($existing) {
            if ((int) $existing->user_id !== (int) $request->user()->getAuthIdentifier()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data offline ini dibuat oleh pengguna lain.',
                ], 409);
            }

            return $this->replayedResponse($existing);
        }

        $response = $next($request);

        if (! $response->isSuccessful() && ! $response->isRedirection()) {
            return $response;
        }

        $payload = $this->responsePayload($response);
        $location = $response->headers->get('Location');

        if (($payload['success'] ?? true) === false || $this->isLoginRedirect($location)) {
            return $response;
        }

        OfflineSyncRequest::create([
            'client_id' => $clientId,
            'user_id' => $request->user()->getAuthIdentifier(),
            'method' => strtoupper($request->method()),
            'path' => '/'.$request->path(),
            'response_status' => $response->getStatusCode(),
            'response_payload' => $payload ? json_encode($payload) : null,
            'response_location' => $location,
            'completed_at' => now(),
        ]);

        $status = $response->isRedirection() || $response->getStatusCode() === 204
            ? 200
            : $response->getStatusCode();

        return response()->json($this->normalisePayload($payload, $location), $status);
    }

    private function isReplayableRequest(Request $request, string $clientId): bool
    {
        return $clientId !== '' && in_array(strtoupper($request->method()), ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    private function responsePayload(Response $response): ?array
    {
        if (! $response instanceof JsonResponse) {
            return null;
        }

        $data = $response->getData(true);

        return is_array($data) ? $data : null;
    }

    private function replayedResponse(OfflineSyncRequest $request): JsonResponse
    {
        $payload = null;

        if ($request->response_payload) {
            $payload = json_decode($request->response_payload, true);
        }

        return response()->json($this->normalisePayload($payload, $request->response_location, true));
    }

    private function normalisePayload(?array $payload, ?string $location, bool $replayed = false): array
    {
        $payload = is_array($payload) ? $payload : [];
        $payload['success'] = true;
        $payload['created'] = $replayed ? false : ($payload['created'] ?? true);
        $payload['replayed'] = $replayed;

        if ($location && empty($payload['redirect'])) {
            $payload['redirect'] = $location;
        }

        return $payload;
    }

    private function isLoginRedirect(?string $location): bool
    {
        return $location && parse_url($location, PHP_URL_PATH) === '/login';
    }
}
