<?php

// Создано sdk/codegen/php.py из sdk/openapi.json. Не править руками: python -m codegen php

declare(strict_types=1);

namespace Wazzapo\Generated;

/** Машинные коды ошибок API v2 и их HTTP-статусы (таблица из описания API). */
final class ErrorCodes
{
    public const STATUS = [
        'BAD_GATEWAY' => 502,
        'BAD_REQUEST' => 400,
        'CONFLICT' => 409,
        'DEVICE_QUOTA_EXCEEDED' => 403,
        'FEATURE_NOT_IN_PLAN' => 403,
        'FORBIDDEN' => 403,
        'GATEWAY_TIMEOUT' => 504,
        'IDEMPOTENCY_IN_PROGRESS' => 409,
        'IDEMPOTENCY_KEY_REUSED' => 422,
        'INSUFFICIENT_SCOPE' => 403,
        'INTERNAL_ERROR' => 500,
        'INVALID_API_KEY' => 401,
        'IP_NOT_ALLOWED' => 403,
        'METHOD_NOT_ALLOWED' => 405,
        'NOT_FOUND' => 404,
        'NOT_IMPLEMENTED' => 501,
        'PAYLOAD_TOO_LARGE' => 413,
        'RATE_LIMITED' => 429,
        'RATE_LIMIT_EXCEEDED' => 429,
        'SERVICE_UNAVAILABLE' => 503,
        'UNAUTHORIZED' => 401,
        'UNSUPPORTED_MEDIA_TYPE' => 415,
        'USAGE_LIMIT_EXCEEDED' => 403,
        'VALIDATION_ERROR' => 422,
        'account_restricted' => 403,
        'amount_field_missing' => 422,
        'contact_exists' => 409,
        'contact_not_found' => 404,
        'conversation_not_found' => 404,
        'currency_mismatch' => 422,
        'deal_exists' => 409,
        'deal_not_found' => 404,
        'invalid_event_types' => 422,
        'invalid_parameters' => 422,
        'invalid_phone' => 422,
        'media_unavailable' => 422,
        'meta_payment_required' => 402,
        'not_supported' => 501,
        'open_deal_exists' => 409,
        'outside_24h_window' => 422,
        'payment_required' => 402,
        'phone_number_not_found' => 404,
        'pipeline_change_not_supported' => 422,
        'pipeline_not_found' => 404,
        'provider_error' => 502,
        'rate_limited' => 429,
        'recipient_opted_out' => 422,
        'recipient_unavailable' => 422,
        'required_fields' => 422,
        'responsible_user_not_found' => 422,
        'stage_not_found' => 422,
        'task_not_found' => 404,
        'template_disabled' => 422,
        'template_not_approved' => 422,
        'template_not_found' => 422,
        'template_paused' => 422,
        'unknown_custom_field' => 422,
        'webhook_delivery_not_found' => 404,
        'webhook_not_found' => 404,
    ];
}
