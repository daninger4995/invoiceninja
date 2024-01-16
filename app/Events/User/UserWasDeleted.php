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

namespace App\Events\User;

use App\Models\Company;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Class UserWasDeleted.
 */
class UserWasDeleted
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public $user;

    public $creating_user;

    public $company;

    public $event_vars;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param Company $company
     * @param array $event_vars
     */
    public function __construct(User $user, User $creating_user, Company $company, array $event_vars)
    {
        $this->user = $user;
        $this->creating_user = $creating_user;
        $this->company = $company;
        $this->event_vars = $event_vars;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return [];
    }
}
