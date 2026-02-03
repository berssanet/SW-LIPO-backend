<?php declare(strict_types=1);

namespace Allquanto\Datatrans\DatatransApi\Struct\Response;

use Shopware\Core\Framework\Struct\Struct;

class InitResponseStruct extends Struct
{
    /**
     * @var string
     */
    protected string $transactionId;

    /**
     * @var string
     */
    protected string $redirectUrl;

    /**
     * @return string
     */
    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    /**
     * @param string $transactionId
     */
    public function setTransactionId(string $transactionId): void
    {
        $this->transactionId = $transactionId;
    }

    /**
     * @return string
     */
    public function getRedirectUrl(): string
    {
        return $this->redirectUrl;
    }

    /**
     * @param string $redirectUrl
     */
    public function setRedirectUrl(string $redirectUrl): void
    {
        $this->redirectUrl = $redirectUrl;
    }


}
