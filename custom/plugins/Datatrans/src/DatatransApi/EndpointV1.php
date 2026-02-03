<?php declare(strict_types=1);

namespace Allquanto\Datatrans\DatatransApi;

final class EndpointV1
{
    public const INIT = '/v1/transactions';

    public const REDIRECT = '/v1/start';

    public const SECURE_FIELDS_INIT = '/v1/transactions/secureFields';

    public const AUTHORIZE = '/v1/transactions/authorize';

    public const AUTORIZE_SPLIT = '/v1/transactions/%s/authorize';

    public const VALIDATE = '/v1/transactions/validate';

    public const SETTLE = '/v1/transactions/%s/settle';

    public const CANCEL = '/v1/transactions/%s/cancel';

    public const REFUND = '/v1/transactions/%s/credit';

    public const STATUS = '/v1/transactions';

    public const HEALTH_CHECK = '/upp/check';

    private function __construct()
    {
    }
}
