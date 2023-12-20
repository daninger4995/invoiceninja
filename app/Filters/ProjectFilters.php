<?php
/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2023. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * ProjectFilters.
 */
class ProjectFilters extends QueryFilters
{
    /**
     * Filter based on search text.
     *
     * @param string $filter
     * @return Builder
     * @deprecated
     */
    public function filter(string $filter = ''): Builder
    {
        if (strlen($filter) == 0) {
            return $this->builder;
        }

        return  $this->builder->where(function ($query) use ($filter) {
            $query->where('name', 'like', '%'.$filter.'%')
                  ->orWhereHas('client', function ($q) use ($filter) {
                        $q->where('name', 'like', '%'.$filter.'%');
                    })
                  ->orWhere('public_notes', 'like', '%'.$filter.'%')
                  ->orWhere('private_notes', 'like', '%'.$filter.'%');
        });
    }
    
    public function number(string $number = ''): Builder
    {
        if (strlen($number) == 0) {
            return $this->builder;
        }

        return $this->builder->where('number', $number);
    }

    /**
     * Sorts the list based on $sort.
     *
     * @param string $sort formatted as column|asc
     * @return Builder
     */
    public function sort(string $sort = ''): Builder
    {
        $sort_col = explode('|', $sort);

        if ($sort_col[0] == 'client_id') {
            return $this->builder->orderBy(\App\Models\Client::select('name')
                    ->whereColumn('clients.id', 'projects.client_id'), $sort_col[1]);
        }

        if (!is_array($sort_col) || count($sort_col) != 2) {
            return $this->builder;
        }

        if (is_array($sort_col) && in_array($sort_col[1], ['asc','desc'])) {
            return $this->builder->orderBy($sort_col[0], $sort_col[1]);
        }

        return $this->builder;
    }

    /**
     * Filters the query by the users company ID.
     *
     * @return Builder
     */
    public function entityFilter(): Builder
    {
        return $this->builder->company();
    }
}
