<?php

declare(strict_types=1);

namespace App\Services;

use DateTimeImmutable;
use InvalidArgumentException;

class FinanceCalculator
{
    // Жалпы кірісті есептеу
    public function totalIncome(array $transactions): int
    {
        return $this->calculateTotals($transactions)['income'];
    }

    // Жалпы шығысты есептеу
    public function totalExpense(array $transactions): int
    {
        return $this->calculateTotals($transactions)['expense'];
    }

    // Ағымдағы балансты есептеу
    public function balance(array $transactions): int
    {
        return $this->calculateTotals($transactions)['balance'];
    }

    // Транзакция сомасын тексеру
    public function validateAmount(int $amount): bool
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException(
                'Транзакция сомасы нөлден үлкен болуы тиіс.'
            );
        }

        return true;
    }

    // Айлық бюджет сомасын тексеру
    public function validateBudget(int $budget): bool
    {
        if ($budget <= 0) {
            throw new InvalidArgumentException(
                'Бюджет сомасы нөлден үлкен болуы тиіс.'
            );
        }

        return true;
    }

    // Қалған бюджетті есептеу
    public function remainingBudget(int $budget, int $expenses): int
    {
        $this->validateBudget($budget);

        if ($expenses < 0) {
            throw new InvalidArgumentException(
                'Шығыс сомасы теріс болмауы тиіс.'
            );
        }

        return $budget - $expenses;
    }

    // Күндік статистика
    public function dailyStatistics(
        array $transactions,
        string $date
    ): array {
        $period = $this->parseDate($date)->format('Y-m-d');

        return $this->statisticsForPeriod($transactions, $period);
    }

    // Айлық статистика
    public function monthlyStatistics(
        array $transactions,
        string $date
    ): array {
        $period = $this->parseDate($date)->format('Y-m');

        return $this->statisticsForPeriod($transactions, $period);
    }

    // Жылдық статистика
    public function yearlyStatistics(
        array $transactions,
        string $date
    ): array {
        $period = $this->parseDate($date)->format('Y');

        return $this->statisticsForPeriod($transactions, $period);
    }

    // Кіріс, шығыс және балансты есептеу
    private function calculateTotals(array $transactions): array
    {
        $income = 0;
        $expense = 0;

        foreach ($transactions as $transaction) {
            $amount = $transaction['amount'];
            $type = $transaction['type'];

            $this->validateAmount($amount);

            if ($type === 'income') {
                $income += $amount;
            } elseif ($type === 'expense') {
                $expense += $amount;
            } else {
                throw new InvalidArgumentException(
                    'Транзакция түрі жарамсыз.'
                );
            }
        }

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
        ];
    }

    // Белгілі бір кезеңнің статистикасын есептеу
    private function statisticsForPeriod(
        array $transactions,
        string $period
    ): array {
        $filtered = array_filter(
            $transactions,
            function (array $transaction) use ($period): bool {
                $date = $this->parseDate(
                    $transaction['date']
                )->format('Y-m-d');

                return str_starts_with($date, $period);
            }
        );

        return $this->calculateTotals($filtered);
    }

    // Күн форматын тексеру
    private function parseDate(string $date): DateTimeImmutable
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        if ($parsed === false || $parsed->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException(
                'Күн форматы YYYY-MM-DD болуы тиіс.'
            );
        }

        return $parsed;
    }
}
