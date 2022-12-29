<?php

namespace App\Policies;

use App\Models\User;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Auth;

class UserPolicy
{
    use HandlesAuthorization;

    public function show(User $user)
    {
      return (Auth::user()->id == $user->id);
    }

    public function update(User $user, User $user_to_evaluate){
      $role = Auth::user()->roles()->get()->pluck('userrole')->contains('Administrator');
      return (Auth::user()->id == $user_to_evaluate->id || $role);
    }

    public function delete(User $user, User $user_to_evaluate){
      $role = Auth::user()->roles()->get()->pluck('userrole')->contains('Administrator');
      return (Auth::user()->id == $user_to_evaluate->id || $role);
    }

    public function changeTags(User $user, User $user_to_evaluate){
      return (Auth::user()->id == $user_to_evaluate->id);
    }
}
