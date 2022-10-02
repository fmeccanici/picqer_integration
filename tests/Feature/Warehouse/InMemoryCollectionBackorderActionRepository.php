<?php


namespace Tests\Feature\Warehouse;


use App\Warehouse\Domain\Actions\Action;
use Illuminate\Support\Collection;

class InMemoryCollectionBackorderActionRepository implements \App\Warehouse\Domain\Repositories\BackorderActionRepositoryInterface
{

    private \Illuminate\Support\Collection $backorderActions;

    public function __construct()
    {
        $this->backorderActions = collect();
    }

    public function add(Action $backorderAction): void
    {
        $this->backorderActions->push($backorderAction);
    }

    public function all(): Collection
    {
        return $this->backorderActions;
    }

}
