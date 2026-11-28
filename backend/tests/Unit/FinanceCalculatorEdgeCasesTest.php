<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\FinanceCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FinanceCalculatorEdgeCasesTest extends TestCase
{
    #[DataProvider('totalCases')]
    public function test_totals_handle_boundary_cases(array $transactions, int $income, int $expense): void
    {
        $calculator = new FinanceCalculator;

        $this->assertSame($income, $calculator->totalIncome($transactions));
        $this->assertSame($expense, $calculator->totalExpense($transactions));
        $this->assertSame($income - $expense, $calculator->balance($transactions));
    }

    public static function totalCases(): array
    {
        return [
            'empty' => [[], 0, 0],
            'income only' => [[self::transaction('income', 1)], 1, 0],
            'expense only' => [[self::transaction('expense', 150)], 0, 150],
            'zero balance' => [[self::transaction('income', 250), self::transaction('expense', 250)], 250, 250],
            'multiple incomes' => [[self::transaction('income', 100), self::transaction('income', 300)], 400, 0],
            'distinct equal expenses' => [[self::transaction('expense', 99), self::transaction('expense', 99)], 0, 198],
            'integer minor units' => [[self::transaction('income', 100000000001), self::transaction('expense', 1)], 100000000001, 1],
        ];
    }

    #[DataProvider('invalidTransactions')]
    public function test_calculation_rejects_invalid_transactions(array $transaction): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new FinanceCalculator)->balance([$transaction]);
    }

    public static function invalidTransactions(): array
    {
        return [
            'unknown type' => [self::transaction('transfer', 100)],
            'zero income' => [self::transaction('income', 0)],
            'negative expense' => [self::transaction('expense', -1)],
        ];
    }

    #[DataProvider('positiveAmounts')]
    public function test_positive_amounts_are_accepted(int $amount): void
    {
        $this->assertTrue((new FinanceCalculator)->validateAmount($amount));
    }

    public static function positiveAmounts(): array
    {
        return ['one minor unit' => [1], 'large amount' => [999999999999]];
    }

    #[DataProvider('positiveAmounts')]
    public function test_positive_budgets_are_accepted(int $amount): void
    {
        $this->assertTrue((new FinanceCalculator)->validateBudget($amount));
    }

    public function test_negative_budget_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new FinanceCalculator)->validateBudget(-1);
    }

    #[DataProvider('remainingBudgetCases')]
    public function test_remaining_budget_preserves_overspending(int $budget, int $expenses, int $expected): void
    {
        $this->assertSame($expected, (new FinanceCalculator)->remainingBudget($budget, $expenses));
    }

    public static function remainingBudgetCases(): array
    {
        return [
            'nothing spent' => [500, 0, 500],
            'exact limit' => [500, 500, 0],
            'overspent' => [500, 650, -150],
        ];
    }

    #[DataProvider('invalidBudgetCases')]
    public function test_remaining_budget_rejects_invalid_inputs(int $budget, int $expenses): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new FinanceCalculator)->remainingBudget($budget, $expenses);
    }

    public static function invalidBudgetCases(): array
    {
        return ['zero limit' => [0, 0], 'negative expenses' => [100, -1]];
    }

    #[DataProvider('periodCases')]
    public function test_period_statistics_respect_calendar_boundaries(string $method, string $date, array $transactions, int $income, int $expense): void
    {
        $result = (new FinanceCalculator)->{$method}($transactions, $date);

        $this->assertSame([
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
        ], $result);
    }

    public static function periodCases(): array
    {
        $transactions = [
            self::transaction('income', 500, '2024-02-29'),
            self::transaction('expense', 100, '2024-02-28'),
            self::transaction('income', 300, '2024-03-01'),
            self::transaction('expense', 50, '2023-12-31'),
            self::transaction('expense', 70, '2025-01-01'),
        ];

        return [
            'leap day' => ['dailyStatistics', '2024-02-29', $transactions, 500, 0],
            'different day excluded' => ['dailyStatistics', '2024-02-28', $transactions, 0, 100],
            'month boundary' => ['monthlyStatistics', '2024-02-01', $transactions, 500, 100],
            'year boundary' => ['yearlyStatistics', '2024-01-01', $transactions, 800, 100],
            'empty period' => ['monthlyStatistics', '2026-11-01', $transactions, 0, 0],
        ];
    }

    #[DataProvider('invalidDates')]
    public function test_invalid_reference_dates_are_rejected(string $date): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new FinanceCalculator)->dailyStatistics([], $date);
    }

    public static function invalidDates(): array
    {
        return [
            'non leap year' => ['2025-02-29'],
            'wrong delimiter' => ['2026/11/28'],
            'invalid month' => ['2026-13-01'],
            'not zero padded' => ['2026-1-01'],
        ];
    }

    public function test_invalid_transaction_date_is_not_silently_ignored(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new FinanceCalculator)->yearlyStatistics([self::transaction('income', 1, '2026-02-30')], '2026-01-01');
    }

    private static function transaction(string $type, int $amount, string $date = '2026-11-28'): array
    {
        return ['type' => $type, 'amount' => $amount, 'date' => $date];
    }
}
