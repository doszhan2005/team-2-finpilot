<?php

namespace App\Repositories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;

class TransactionRepository
{
    public function all(int $userId): Collection
    {
        return Transaction::where('user_id', $userId)
            ->latest('id')
            ->get();
    }

    public function find(int $userId, int $id): Transaction
    {
        return Transaction::where('user_id', $userId)
            ->findOrFail($id);
    }

    public function create(int $userId, array $data): Transaction
    {
        return Transaction::create([
            ...$data,
            'user_id' => $userId,
        ]);
    }

    public function update(
        int $userId,
        int $id,
        array $data
    ): Transaction {
        $transaction = $this->find($userId, $id);

        $transaction->update($data);

        return $transaction->refresh();
    }

    public function delete(int $userId, int $id): void
    {
        $this->find($userId, $id)->delete();
    }
}
