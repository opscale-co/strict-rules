<?php

namespace Opscale\Models;

use Illuminate\Database\Eloquent\Model;

class OrgPolicy extends Model
{
    protected $fillable = [
        'name',
    ];

    /**
     * Helper method whose body happens to call `belongsTo` on a non-Eloquent
     * receiver. The previous rule implementation would have flagged this
     * model as a child entity because it scanned method bodies for any
     * `belongsTo()` call. The current rule only inspects return types.
     *
     * @param  object  $actor
     * @param  object  $group  any object with a custom belongsTo($actor) helper
     */
    public function isAuthorizedFor(object $actor, object $group): bool
    {
        return $group->belongsTo($actor);
    }
}
