<?php declare(strict_types=1);

namespace Allquanto\Datatrans\DatatransApi\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class DatatransApiException extends ShopwareHttpException
{
    public const ERROR_CODE_DUPLICATE_ORDER_NUMBER = 'DUPLICATE_TRANSACTION';
    public const ERROR_CODE_DUPLICATE_INVOICE_ID = 'DUPLICATE_INVOICE_ID';

    private int $datatransApiStatusCode;

    private ?string $issue;

    public function __construct(
        string  $name,
        string  $message,
        int     $datatransApiStatusCode = Response::HTTP_INTERNAL_SERVER_ERROR,
        ?string $issue = null
    )
    {
        $this->datatransApiStatusCode = $datatransApiStatusCode;
        $this->issue = $issue;
        parent::__construct(
            'The error "{{ name }}" occurred with the following message: {{ message }}',
            ['name' => $name, 'message' => $message]
        );
    }

    public function getStatusCode(): int
    {
        return $this->datatransApiStatusCode;
    }

    public function getIssue(): ?string
    {
        return $this->issue;
    }

    public function getErrorCode(): string
    {
        return 'ALLQUANTO_DATATRANS__API_EXCEPTION';
    }
}
