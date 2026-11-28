<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('transactionTypes')]
    public function test_creates_transactions_for_the_authenticated_owner(string $type): void
    {
        $category = $this->authenticateWithCategory($type);
        $payload = $this->payload($category);

        $response = $this->postJson('/api/transactions', $payload);

        $response->assertCreated()
            ->assertJsonPath('type', $type)
            ->assertJsonPath('amount', '1500.50');
        $this->assertDatabaseHas('transactions', [
            'id' => $response->json('id'),
            'user_id' => $category->user_id,
            'category_id' => $category->id,
            'type' => $type,
            'amount' => 1500.50,
        ]);
    }

    public static function transactionTypes(): array
    {
        return ['income' => ['income'], 'expense' => ['expense']];
    }

    public function test_lists_only_owned_transactions(): void
    {
        $category = $this->authenticateWithCategory();
        $owned = $this->createTransaction($category);
        $this->createTransaction($this->createCategory(User::factory()->create()));

        $this->getJson('/api/transactions')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $owned->id);
    }

    public function test_gets_owned_transaction_by_id(): void
    {
        $category = $this->authenticateWithCategory();
        $transaction = $this->createTransaction($category);

        $this->getJson('/api/transactions/'.$transaction->id)
            ->assertOk()
            ->assertJsonPath('id', $transaction->id);
    }

    public function test_updates_owned_transaction(): void
    {
        $category = $this->authenticateWithCategory();
        $transaction = $this->createTransaction($category);
        $payload = [...$this->payload($category), 'amount' => '2300.75'];

        $this->putJson('/api/transactions/'.$transaction->id, $payload)
            ->assertOk()
            ->assertJsonPath('amount', '2300.75');
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'amount' => 2300.75]);
    }

    public function test_deletes_owned_transaction(): void
    {
        $category = $this->authenticateWithCategory();
        $transaction = $this->createTransaction($category);

        $this->deleteJson('/api/transactions/'.$transaction->id)->assertNoContent();
        $this->assertDatabaseMissing('transactions', ['id' => $transaction->id]);
        $this->getJson('/api/transactions/'.$transaction->id)->assertNotFound();
    }

    public function test_other_users_transactions_cannot_be_read_modified_or_deleted(): void
    {
        $category = $this->authenticateWithCategory();
        $foreign = $this->createTransaction($this->createCategory(User::factory()->create()));
        $url = '/api/transactions/'.$foreign->id;

        $this->getJson($url)->assertNotFound();
        $this->putJson($url, [...$this->payload($category), 'amount' => '999.00'])->assertNotFound();
        $this->deleteJson($url)->assertNotFound();
        $this->assertDatabaseHas('transactions', ['id' => $foreign->id, 'amount' => 1500.50]);
    }

    public function test_category_must_belong_to_the_current_user(): void
    {
        $this->authenticateWithCategory();
        $foreign = $this->createCategory(User::factory()->create());

        $this->postJson('/api/transactions', $this->payload($foreign))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_category_type_must_match_transaction_type(): void
    {
        $category = $this->authenticateWithCategory('income');

        $this->postJson('/api/transactions', [...$this->payload($category), 'type' => 'expense'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('category_id');
        $this->assertDatabaseCount('transactions', 0);
    }

    #[DataProvider('invalidAmounts')]
    public function test_invalid_amount_is_rejected(string $amount): void
    {
        $category = $this->authenticateWithCategory();

        $this->postJson('/api/transactions', [...$this->payload($category), 'amount' => $amount])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('amount');
        $this->assertDatabaseCount('transactions', 0);
    }

    public static function invalidAmounts(): array
    {
        return ['zero' => ['0'], 'negative' => ['-1'], 'excess precision' => ['1.001']];
    }

    public function test_category_list_is_isolated_by_owner(): void
    {
        $category = $this->authenticateWithCategory();
        $this->createCategory(User::factory()->create());

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $category->id);
    }

    private function authenticateWithCategory(string $type = 'income'): Category
    {
        $user = User::factory()->create();
        $this->withToken($user->createToken('test')->plainTextToken);

        return $this->createCategory($user, $type);
    }

    private function createCategory(User $user, string $type = 'income'): Category
    {
        return Category::create([
            'user_id' => $user->id,
            'name' => 'Test category',
            'type' => $type,
        ]);
    }

    private function payload(Category $category): array
    {
        return [
            'category_id' => $category->id,
            'type' => $category->type,
            'amount' => '1500.50',
            'description' => 'Test transaction',
            'transaction_date' => '2026-11-28',
        ];
    }

    private function createTransaction(Category $category): Transaction
    {
        return Transaction::create([
            ...$this->payload($category),
            'user_id' => $category->user_id,
        ]);
    }
}
