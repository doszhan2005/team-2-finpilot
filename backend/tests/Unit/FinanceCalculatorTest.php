<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\FinanceCalculator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class FinanceCalculatorTest extends TestCase
{
    private FinanceCalculator $calculator;

    private array $transactions;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = new FinanceCalculator;

        $this->transactions = [
            [
                'type' => 'income',
                'amount' => 150000,
                'date' => '2026-11-26',
            ],
            [
                'type' => 'expense',
                'amount' => 30000,
                'date' => '2026-11-26',
            ],
            [
                'type' => 'expense',
                'amount' => 20000,
                'date' => '2026-11-25',
            ],
            [
                'type' => 'income',
                'amount' => 50000,
                'date' => '2026-10-15',
            ],
            [
                'type' => 'expense',
                'amount' => 10000,
                'date' => '2025-12-20',
            ],
        ];
    }

    // UT-01: Жалпы кірісті есептеу
    public function test_total_income(): void
    {
        $result = $this->calculator->totalIncome(
            $this->transactions
        );

        $this->assertSame(200000, $result);
    }

    // UT-02: Жалпы шығысты есептеу
    public function test_total_expense(): void
    {
        $result = $this->calculator->totalExpense(
            $this->transactions
        );

        $this->assertSame(60000, $result);
    }

    // UT-03: Балансты есептеу
    public function test_balance(): void
    {
        $result = $this->calculator->balance(
            $this->transactions
        );

        $this->assertSame(140000, $result);
    }

    // UT-04: Теріс соманы қабылдамау
    public function test_negative_amount_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $this->calculator->validateAmount(-5000);
    }

    // UT-05: Нөлдік соманы қабылдамау
    public function test_zero_amount_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $this->calculator->validateAmount(0);
    }

    // UT-06: Айлық бюджетті тексеру
    public function test_budget_validation(): void
    {
        $this->assertTrue(
            $this->calculator->validateBudget(100000)
        );

        $this->expectException(
            InvalidArgumentException::class
        );

        $this->calculator->validateBudget(0);
    }

    // UT-07: Қалған бюджетті есептеу
    public function test_remaining_budget(): void
    {
        $result = $this->calculator->remainingBudget(
            100000,
            35000
        );

        $this->assertSame(65000, $result);
    }

    // UT-08: Күндік статистика
    public function test_daily_statistics(): void
    {
        $result = $this->calculator->dailyStatistics(
            $this->transactions,
            '2026-11-26'
        );

        $this->assertSame([
            'income' => 150000,
            'expense' => 30000,
            'balance' => 120000,
        ], $result);
    }

    // UT-09: Айлық статистика
    public function test_monthly_statistics(): void
    {
        $result = $this->calculator->monthlyStatistics(
            $this->transactions,
            '2026-11-26'
        );

        $this->assertSame([
            'income' => 150000,
            'expense' => 50000,
            'balance' => 100000,
        ], $result);
    }

    // UT-10: Жылдық статистика
    public function test_yearly_statistics(): void
    {
        $result = $this->calculator->yearlyStatistics(
            $this->transactions,
            '2026-11-26'
        );

        $this->assertSame([
            'income' => 200000,
            'expense' => 50000,
            'balance' => 150000,
        ], $result);
    }
}
