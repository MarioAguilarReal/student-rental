<?php

namespace App\Modules\Shared;

use Brick\Math\RoundingMode;
use Brick\Money\Money as BrickMoney;
use InvalidArgumentException;
use JsonSerializable;

/**
 * Immutable money value backed by integer minor units (cents). Never use floats for money.
 *
 * Persist it as two columns: a bigint amount in minor units and a currency code.
 */
final readonly class Money implements JsonSerializable
{
    public const DEFAULT_CURRENCY = 'MXN';

    private function __construct(private BrickMoney $money) {}

    public static function ofMinor(int $minorAmount, string $currency = self::DEFAULT_CURRENCY): self
    {
        return new self(BrickMoney::ofMinor($minorAmount, $currency));
    }

    public static function zero(string $currency = self::DEFAULT_CURRENCY): self
    {
        return new self(BrickMoney::zero($currency));
    }

    public function minorAmount(): int
    {
        return $this->money->getMinorAmount()->toInt();
    }

    public function currency(): string
    {
        return $this->money->getCurrency()->getCurrencyCode();
    }

    public function plus(self $other): self
    {
        return new self($this->money->plus($other->money));
    }

    public function minus(self $other): self
    {
        return new self($this->money->minus($other->money));
    }

    public function multipliedBy(int $times): self
    {
        return new self($this->money->multipliedBy($times));
    }

    /**
     * Percentage expressed in basis points (1000 = 10 %), rounded half up to the minor unit.
     */
    public function percentage(int $basisPoints): self
    {
        return new self($this->money->multipliedBy($basisPoints)->dividedBy(10_000, RoundingMode::HalfUp));
    }

    /**
     * Splits the amount into equal parts; the last part absorbs the rounding remainder (RN-70).
     *
     * @return list<self>
     */
    public function split(int $parts): array
    {
        if ($parts < 1) {
            throw new InvalidArgumentException('Money can only be split into one or more parts.');
        }

        $base = intdiv($this->minorAmount(), $parts);
        $remainder = $this->minorAmount() - ($base * $parts);

        $result = [];

        for ($part = 1; $part < $parts; $part++) {
            $result[] = self::ofMinor($base, $this->currency());
        }

        $result[] = self::ofMinor($base + $remainder, $this->currency());

        return $result;
    }

    public function isZero(): bool
    {
        return $this->money->isZero();
    }

    public function isNegative(): bool
    {
        return $this->money->isNegative();
    }

    public function equals(self $other): bool
    {
        return $this->currency() === $other->currency() && $this->money->isEqualTo($other->money);
    }

    public function greaterThan(self $other): bool
    {
        return $this->money->isGreaterThan($other->money);
    }

    /**
     * @return array{amount: int, currency: string}
     */
    public function jsonSerialize(): array
    {
        return ['amount' => $this->minorAmount(), 'currency' => $this->currency()];
    }
}
