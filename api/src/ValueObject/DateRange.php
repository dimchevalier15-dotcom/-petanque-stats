<?php

declare(strict_types=1);

namespace App\ValueObject;

final readonly class DateRange
{
    public function __construct(
        public \DateTimeImmutable $from,
        public \DateTimeImmutable $to,
    ) {
        if ($this->from > $this->to) {
            throw new \InvalidArgumentException('The start date must be before or equal to the end date.');
        }
    }

    public static function fromQueryStrings(string $from, string $to): self
    {
        $fromDate = self::parseDate($from)->setTime(0, 0, 0);
        $toDate = self::parseDate($to)->setTime(23, 59, 59);

        return new self($fromDate, $toDate);
    }

    public static function defaultLastMonth(): self
    {
        $today = new \DateTimeImmutable('today');
        $from = $today->modify('-1 month')->setTime(0, 0, 0);
        $to = $today->setTime(23, 59, 59);

        return new self($from, $to);
    }

    private static function parseDate(string $value): \DateTimeImmutable
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw new \InvalidArgumentException(sprintf('Invalid date "%s". Expected YYYY-MM-DD.', $value));
        }

        return $date;
    }
}
