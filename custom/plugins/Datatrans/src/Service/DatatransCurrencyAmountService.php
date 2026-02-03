<?php declare(strict_types=1);

namespace Allquanto\Datatrans\Service;

class DatatransCurrencyAmountService
{
    /**
     * @var float
     */
    private $amount;

    /**
     * @var int
     */
    private $decimalPlaces;

    public function __construct(float $amount, int $decimalPlaces)
    {
        $this->amount = $amount;
        $this->decimalPlaces = $decimalPlaces;
    }

    public function getAmountInSmallestUnit(): int
    {
        $factor = 10 ** $this->decimalPlaces;

        return intval(round($factor * $this->amount));
    }
}
